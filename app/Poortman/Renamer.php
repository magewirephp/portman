<?php

declare(strict_types=1);

namespace App\Poortman;

use PhpParser\Node\Identifier;
use PhpParser\Node\Name;

class Renamer
{
    protected ?array $fullyQualifiedNameMap = null;

    protected ?array $namespaceMap          = null;

    protected ?array $classNameMap = null;

    public function renameNamespace(?Name $namespace): ?Name
    {
        if (!$namespace) {
            return $namespace;
        }

        $parts = $this->renameNamespaceParts($namespace->getParts());

        return new Name($parts, $namespace->getAttributes());
    }

    protected function renameNamespaceParts(array $parts): array
    {
        foreach ($this->getNamespaceMap() as $map) {
            if ($this->hasNamespace($map->fromArr, $parts)) {
                return [...$map->toArr, ...array_diff($parts, $map->fromArr)];
            }
        }

        return $parts;
    }

    protected function getNamespaceMap(): array
    {
        if (!$this->namespaceMap) {
            $this->namespaceMap = array_filter(array_filter($this->getFullyQualifiedNameMap(), fn($fqn) => !$fqn->isClass));
        }

        return $this->namespaceMap;
    }

    protected function getFullyQualifiedNameMap(): array
    {
        if (!$this->fullyQualifiedNameMap) {
            $transformations       = poortman_config('transformations', []);
            $fullyQualifiedNameMap = $this->recurseTransformations($transformations);

            // make sure the more specific namespaces get renamed first (the more parts the earlier renamed)
            usort($fullyQualifiedNameMap, fn($a, $b) => count($b->fromArr) <=> count($a->fromArr));
            $this->fullyQualifiedNameMap = $fullyQualifiedNameMap;
        }

        return $this->fullyQualifiedNameMap;
    }

    protected function recurseTransformations(array $transformations): array
    {
        $namespaceMap = [];
        foreach ($transformations as $key => $transformation) {
            $from = trim($key, '\\');
            $to   = $key;
            if (isset($transformation['rename'])) {
                $to             = trim($transformation['rename'], '\\');
                $namespaceMap[] = $this->getFullyQualifiedNameObject($from, $to, !str_ends_with($key, '\\'));
            }
            if (isset($transformation['children'])) {
                foreach ($this->recurseTransformations($transformation['children']) as $child) {
                    $namespaceMap[] = $this->getFullyQualifiedNameObject(
                        $from . '\\' . $child->from,
                        $to . '\\' . $child->to,
                        $child->isClass
                    );
                }
            }
        }

        return $namespaceMap;
    }

    protected function getFullyQualifiedNameObject(string $from, string $to, bool $isClass = false): object
    {
        return (object)[
            'isClass' => $isClass,
            'from'    => $from,
            'to'      => $to,
            'fromArr' => explode('\\', $from),
            'toArr'   => explode('\\', $to),
        ];
    }

    public function hasNamespace(array $first, array $second): bool
    {
        foreach ($first as $key => $value) {
            if (!array_key_exists($key, $second) || $second[$key] !== $value) {
                return false;
            }
        }

        return true;
    }

    public function renameFullyQualifiedName(null|Name|Name\FullyQualified $fqn): null|Name|Name\FullyQualified
    {
        if (!$fqn) {
            return $fqn;
        }

        $parts     = $fqn->getParts();
        $className = $this->renameClassNameString(array_pop($parts));
        if ($fqn instanceof Name\FullyQualified) {
            return new Name\FullyQualified([...$this->renameNamespaceParts($parts), $className], $fqn->getAttributes());
        }

        return new Name([...$this->renameNamespaceParts($parts), $className], $fqn->getAttributes());
    }

    protected function renameClassNameString(string $oldName): string
    {
        foreach ($this->getClassNameMap() as $from => $to) {
            if ($oldName === $from) {
                return $to;
            }
        }

        return $oldName;
    }

    protected function getClassNameMap(): array
    {
        if (!$this->classNameMap) {
            $classNameMap = [];
            foreach ($this->getFullyQualifiedNameMap() as $fqn) {
                if ($fqn->isClass) {
                    $classNameMap[array_pop($fqn->fromArr)] = array_pop($fqn->toArr);
                }
            }
            $this->classNameMap = array_filter(array_unique($classNameMap));
        }

        return $this->classNameMap;
    }

    public function renameClassName(?Identifier $identifier): ?Identifier
    {
        if (!$identifier) {
            return $identifier;
        }
        $oldName = $identifier->toString();

        return new Identifier($this->renameClassNameString($oldName), $identifier->getAttributes());
    }
}
