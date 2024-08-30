<?php

declare(strict_types=1);

namespace App\Poortman;

use App\Poortman\Model\FullyQualifiedName;
use App\Poortman\Model\Transformation;

class TransformerConfiguration
{
    protected ?array $transformersMap = null;

    protected ?array $fullyQualifiedNameMap = null;

    protected ?array $namespaceMap = null;

    protected ?array $classNameMap = null;

    protected ?array $fileDocBlockMap = null;

    /**
     * @return Transformation[]
     */
    public function getNamespaceMap(): array
    {
        if ($this->namespaceMap === null) {
            $this->namespaceMap = array_values(array_filter($this->getFullyQualifiedNameMap(), fn($fqn) => !$fqn->isClass && $fqn->rename));
        }

        return $this->namespaceMap;
    }

    /**
     * @return Transformation[]
     */
    public function getFullyQualifiedNameMap(): array
    {
        if ($this->fullyQualifiedNameMap === null) {
            $transformersMap = array_filter(
                $this->getTransformersMap(),
                fn($fqn) => $fqn->rename
            );
            usort($transformersMap, fn($a, $b) => count($b->nameParts) <=> count($a->nameParts));
            $this->fullyQualifiedNameMap = $transformersMap;
        }

        return $this->fullyQualifiedNameMap;
    }

    public function getTransformersMap(): array
    {
        if ($this->transformersMap === null) {
            $transformations       = poortman_config('transformations', []);
            $this->transformersMap = $this->recurseTransformations($transformations);
        }

        return $this->transformersMap;
    }

    /**
     * @param array $transformations
     *
     * @return Transformation[]
     */
    protected function recurseTransformations(array $transformations, int $level = 0): array
    {
        $namespaceMap = [];
        $sort         = 0;
        foreach ($transformations as $key => $transformation) {
            $name   = trim($key, '\\');
            $rename = null;
            if (isset($transformation['rename'])) {
                $rename = trim($transformation['rename'], '\\');
            }
            $namespaceMap[] = new Transformation(
                isClass: !str_ends_with($key, '\\'),
                name: $name,
                rename: $rename,
                fileDocBlock: $transformation['file-doc-block'] ?? null,
                level: $level,
                sort: $sort
            );
            $sort++;
            if (isset($transformation['children'])) {
                foreach ($this->recurseTransformations($transformation['children'], $level + 1) as $child) {
                    $child->name = $name . '\\' . $child->name;
                    if ($child->rename) {
                        $child->rename = (($rename ? (string)$rename . '\\' : '') . $child->rename);
                    }
                    $namespaceMap[] = $child;
                }
            }
        }

        return $namespaceMap;
    }

    public function getFileDocBlock(FullyQualifiedName $fullyQualifiedName): ?string
    {
        $parts           = $fullyQualifiedName->getParts();
        $fileDocBlockMap = $this->getFileDocBlockMap();
        while (count($parts) !== 0) {
            foreach ($fileDocBlockMap as $transform) {
                if ($transform->nameParts === $parts) {
                    return $transform->fileDocBlock;
                }
            }
            array_pop($parts);
        }

        return null;
    }

    /**
     * @return Transformation[]
     */
    public function getFileDocBlockMap(): array
    {
        if ($this->fileDocBlockMap === null) {
            $this->fileDocBlockMap = array_values(array_filter($this->getTransformersMap(), fn($fqn) => $fqn->fileDocBlock));
        }

        return $this->fileDocBlockMap;
    }

    public function getClassNameMap(): array
    {
        if ($this->classNameMap === null) {
            $classNameMap = [];
            foreach ($this->getFullyQualifiedNameMap() as $fqn) {
                if ($fqn->isClass && $fqn->rename) {
                    $classNameMap[$fqn->nameParts[array_key_last($fqn->nameParts)]] = $fqn->renameParts[array_key_last($fqn->renameParts)];
                }
            }
            $this->classNameMap = array_filter(array_unique($classNameMap));
        }

        return $this->classNameMap;
    }
}
