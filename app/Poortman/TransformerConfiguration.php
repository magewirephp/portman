<?php

declare(strict_types=1);

namespace App\Poortman;

use App\Poortman\Model\FullyQualifiedName;
use App\Poortman\Model\Transformation;
use App\Poortman\Model\VersionedFullyQualifiedName;

class TransformerConfiguration
{
    protected ?array $transformersMap = null;

    protected ?array $fullyQualifiedNameMap = null;

    protected ?array $namespaceMap = null;

    protected ?array $removeMethodsMap = null;
    protected ?array $classNameMap     = null;

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
                removeMethods: $transformation['remove-methods'] ?? null,
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

    public function getRemoveMethods(VersionedFullyQualifiedName $versionedFullyQualifiedName): array
    {
        return $this->getTransformationFromVersionedFullyQualifiedName($this->getRemoveMethodsMap(), $versionedFullyQualifiedName)?->removeMethods ?? [];
    }

    /**
     * @param Transformation[]            $transformMap
     * @param VersionedFullyQualifiedName $versionedFullyQualifiedName
     *
     * @return Transformation|null
     */
    public function getTransformationFromVersionedFullyQualifiedName(array $transformMap, VersionedFullyQualifiedName $versionedFullyQualifiedName): ?Transformation
    {
        $fileDocBlock = $this->getTransformationFromFullyQualifiedName($transformMap, $versionedFullyQualifiedName->current);
        if (!$fileDocBlock) {
            $fileDocBlock = $this->getTransformationFromFullyQualifiedName($transformMap, $versionedFullyQualifiedName->original);
        }

        return $fileDocBlock;
    }

    /**
     * @param Transformation[]   $transformMap
     * @param FullyQualifiedName $fullyQualifiedName
     *
     * @return Transformation|null
     */
    public function getTransformationFromFullyQualifiedName(array $transformMap, FullyQualifiedName $fullyQualifiedName): ?Transformation
    {
        $parts = $fullyQualifiedName->getParts();
        while (count($parts) !== 0) {
            foreach ($transformMap as $transform) {
                if ($transform->nameParts === $parts) {
                    return $transform;
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
            $this->fileDocBlockMap = array_values(array_filter($this->getTransformersMap(), fn($tf) => $tf->fileDocBlock));
        }

        return $this->fileDocBlockMap;
    }

    public function getFileDocBlock(VersionedFullyQualifiedName $versionedFullyQualifiedName): ?string
    {
        return $this->getTransformationFromVersionedFullyQualifiedName($this->getFileDocBlockMap(), $versionedFullyQualifiedName)?->fileDocBlock ?? null;
    }

    /**
     * @return Transformation[]
     */
    public function getRemoveMethodsMap(): array
    {
        if ($this->removeMethodsMap === null) {
            $this->removeMethodsMap = array_values(array_filter($this->getTransformersMap(), fn($tf) => $tf->isClass && $tf->removeMethods));
        }

        return $this->removeMethodsMap;
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
