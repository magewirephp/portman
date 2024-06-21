<?php
declare(strict_types=1);

namespace App\Poortman;

use PhpParser\Comment\Doc;
use PhpParser\Node;
use PhpParser\NodeVisitorAbstract;

class ClassMerger extends NodeVisitorAbstract
{
    protected ?string $className                   = null;
    private string    $mode                        = 'collect';
    private array     $augmentationClassTraits     = [];
    private array     $augmentationClassMethods    = [];
    private array     $augmentationClassProperties = [];
    private array     $augmentationClasses         = [];
    private array     $augmentationUses            = [];
    private string    $augmentationClassDoc        = '';

    public function __construct(protected Renamer $renamer)
    {
    }

    public function startMerging(): void
    {
        $this->mode = 'merge';
    }

    public function getClasses(): array
    {
        return $this->augmentationClasses;
    }

    public function startCollecting(): void
    {
        $this->mode = 'collect';
    }

    public function enterNode(Node $node): void
    {
        $this->{$this->mode}($node);
    }

    public function collect(Node $node): void
    {
        if ($node instanceof Node\Stmt\Use_) {
            foreach ($node->uses as $use) {
                $use->name                                      = $this->renamer->renameFullyQualifiedName($use->name);
                $this->augmentationUses[$use->name->toString()] = new Node\Stmt\Use_(
                    [$use],
                    $node->type,
                    $node->getAttributes()
                );
            }
        }
        if ($node instanceof Node\Stmt\Class_) {
            $this->augmentationClassDoc = $node->getDocComment()?->getText() ?? '';
            // Collect methods and properties of the augmentation class
            foreach ($node->stmts as $stmt) {
                if ($stmt instanceof Node\Stmt\ClassMethod) {
                    $this->augmentationClassMethods[$stmt->name->toString()] = $stmt;
                }
                elseif ($stmt instanceof Node\Stmt\Property) {
                    $this->augmentationClassProperties[$stmt->props[0]->name->toString()] = $stmt;
                }
                elseif ($stmt instanceof Node\Stmt\TraitUse) {
                    foreach ($stmt->traits as $trait) {
                        $this->augmentationClassTraits[$this->renamer->renameFullyQualifiedName($trait)->toString()] = $trait;
                    }
                }
            }
        }
    }

    public function finalize(array $nodes)
    {
        if (!!poortman_config('add-define-scrict') && $nodes[0] instanceof Node\Stmt\Namespace_) {
            $nodes = [
                new Node\Stmt\Declare_([
                    new Node\DeclareItem(
                        new Node\Identifier('strict_types'),
                        new Node\Scalar\Int_(1)
                    )
                ])
                , ...$nodes
            ];
        }
        $docBlock = poortman_config('file-doc-block');
        if ($docBlock) {
            $nodes[0]->setDocComment(new Doc($docBlock));
        }

        return $nodes;
    }

    public function getClassName(): ?string
    {
        return $this->className;
    }

    public function merge(Node $node)
    {
        if ($node instanceof Node\Stmt\Namespace_) {
            // rename the namespaces
            $node->name = $this->renamer->renameNamespace($node->name);

            // remove uses from source for later adding
            foreach ($node->stmts as $key => $stmt) {
                if ($stmt instanceof Node\Stmt\Use_ || $stmt instanceof Node\Stmt\GroupUse) {
                    $prefix = isset($stmt->prefix) ? $stmt->prefix->toString() . "\\" : '';
                    foreach ($stmt->uses as $use) {
                        $name = $this->renamer->renameFullyQualifiedName(
                            new Node\Name($prefix . $use->name->toString())
                        );
                        if (!isset($this->augmentationUses[$name->toString()])) {
                            $this->augmentationUses[$name->toString()] = new Node\Stmt\Use_(
                                [new Node\UseItem($name, $use->alias, $use->type, $use->getAttributes())],
                                $stmt->type,
                                $stmt->getAttributes()
                            );
                        }
                    }
                    unset($node->stmts[$key]);
                }
            }

            // add combined use statements
            if (count($this->augmentationUses) > 0) {
                $node->stmts = [...$this->augmentationUses, ...$node->stmts];
            }
        }

        if ($node instanceof Node\Stmt\Class_) {
            // rename class
            $node->name      = $this->renamer->renameClassName($node->name);
            $this->className = $node->name?->toString() ?? null;

            // rename extends
            if ($node->extends) {
                $node->extends = $this->renamer->renameFullyQualifiedName($node->extends);
            }

            // add augmentation class block
            if (!empty($this->augmentationClassDoc)) {
                $node->setDocComment(new Doc($this->augmentationClassDoc));
            }

            // Merge methods
            foreach ($node->stmts as $key => $stmt) {
                if ($stmt instanceof Node\Stmt\ClassMethod) {
                    $methodName = $stmt->name->toString();
                    if (isset($this->augmentationClassMethods[$methodName])) {
                        $node->stmts[$key] = $this->augmentationClassMethods[$methodName];
                        unset($this->augmentationClassMethods[$methodName]);
                    }
                }
                elseif ($stmt instanceof Node\Stmt\Property) {
                    $propertyName = $stmt->props[0]->name->toString();
                    if (isset($this->augmentationClassProperties[$propertyName])) {
                        $node->stmts[$key] = $this->augmentationClassProperties[$propertyName];
                        unset($this->augmentationClassProperties[$propertyName]);
                    }
                }
                elseif ($stmt instanceof Node\Stmt\TraitUse) {
                    foreach ($stmt->traits as $trait) {
                        $trait = $this->renamer->renameFullyQualifiedName($trait);
                        if (isset($this->augmentationClassTraits[$trait->toString()])) {
                            unset($this->augmentationClassTraits[$trait->toString()]);
                        }
                    }
                }
            }
            // Add remaining methods from the augmentation class
            foreach ($this->augmentationClassMethods as $method) {
                $node->stmts[] = $method;
            }
            // Add properties from the augmentation class
            foreach ($this->augmentationClassProperties as $property) {
                $node->stmts[] = $property;
            }

            // add the augmentation traits
            $traits = [];
            foreach ($this->augmentationClassTraits as $trait) {
                $traits[] = new Node\Stmt\TraitUse([$trait]);
            }
            $node->stmts = [...$traits, ...$node->stmts];
        }

        // rename properties and parameters
        if (
            ($node instanceof Node\Stmt\Property || $node instanceof Node\Param) &&
            $node->type &&
            $node->type instanceof Node\Name
        ) {
            $node->type = $this->renamer->renameFullyQualifiedName($node->type);
        }

        // rename used traits
        if ($node instanceof Node\Stmt\TraitUse) {
            foreach ($node->traits as &$trait) {
                $trait = $this->renamer->renameFullyQualifiedName($trait);
            }
        }

        return $node;
    }
}
