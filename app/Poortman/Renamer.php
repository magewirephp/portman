<?php
declare(strict_types=1);

namespace App\Poortman;

use PhpParser\Node\Identifier;
use PhpParser\Node\Name;

class Renamer
{
    protected ?array $namespaceMap = null;

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
            $namespaceMap = [];
            $namespaces   = poortman_config('rename-namespaces', []);

            // make a object for all namespaces with explodes
            foreach ($namespaces as $from => $to) {
                $namespaceMap[] = (object)[
                    'from'    => $from,
                    'to'      => $to,
                    'fromArr' => explode('\\', $from),
                    'toArr'   => explode('\\', $to),
                ];
            }

            // make sure the more specific namespaces get renamed first (the more parts the earlier renamed)
            usort($namespaceMap, fn($a, $b) => count($b->fromArr) <=> count($a->fromArr));
            $this->namespaceMap = $namespaceMap;
        }

        return $this->namespaceMap;
    }

    function hasNamespace(array $first, array $second): bool
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
            $this->classNameMap = poortman_config('rename-classes', []);
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