<?php

declare(strict_types=1);

namespace App\Portman;

use PhpParser\Node\Identifier;
use PhpParser\Node\Name;

class Renamer
{

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
        foreach (app(TransformerConfiguration::class)->getNamespaceMap() as $map) {
            if ($this->hasNamespace($map->nameParts, $parts)) {
                return [...$map->renameParts, ...array_diff($parts, $map->nameParts)];
            }
        }

        return $parts;
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
        foreach (app(TransformerConfiguration::class)->getClassNameMap() as $from => $to) {
            if ($oldName === $from) {
                return $to;
            }
        }

        return $oldName;
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
