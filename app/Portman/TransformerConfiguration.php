<?php

declare(strict_types=1);

namespace App\Portman;

use App\Portman\Configuration\Data\Transformation;
use App\Portman\Model\FullyQualifiedName;
use App\Portman\Model\VersionedFullyQualifiedName;

class TransformerConfiguration
{
    protected ?array $transformersMap = null;

    protected ?array $fullyQualifiedNameMap = null;

    protected ?array $namespaceMap = null;

    protected ?array $removeMethodsMap    = null;
    protected ?array $removePropertiesMap = null;
    protected ?array $classNameMap        = null;

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
            $transformations       = portman_config_data()->transformations;
            $this->transformersMap = $this->recurseTransformations($transformations);
        }

        return $this->transformersMap;
    }

    /**
     * @param Transformation[] $transformations
     *
     * @return Transformation[]
     */
    protected function recurseTransformations(array $transformations): array
    {
        $namespaceMap = [];
        foreach ($transformations as $transformation) {
            $namespaceMap[] = $transformation;
            if (is_array($transformation->children)) {
                foreach ($this->recurseTransformations($transformation->children) as $child) {
                    $namespaceMap[] = $child;
                }
            }
        }

        return $namespaceMap;
    }

    public function getRemoveProperties(VersionedFullyQualifiedName $versionedFullyQualifiedName): array
    {
        $data = $this->getTransformationFromVersionedFullyQualifiedName($this->getRemovePropertiesMap(), $versionedFullyQualifiedName);

        return $data && is_array($data->removeProperties) ? $data->removeProperties : [];
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
    public function getRemovePropertiesMap(): array
    {
        if ($this->removePropertiesMap === null) {
            $this->removePropertiesMap = array_values(array_filter($this->getTransformersMap(), fn($tf) => $tf->isClass && $tf->removeProperties));
        }

        return $this->removePropertiesMap;
    }

    public function getRemoveMethods(VersionedFullyQualifiedName $versionedFullyQualifiedName): array
    {
        return $this->getTransformationFromVersionedFullyQualifiedName($this->getRemoveMethodsMap(), $versionedFullyQualifiedName)?->removeMethods ?? [];
    }

    /**
     * @return Transformation[]
     */
    public function getRemoveMethodsMap(): array
    {
        if ($this->removeMethodsMap === null) {
            $this->removeMethodsMap = array_values(array_filter($this->getTransformersMap(), fn($tf) => $tf->isClass && is_array($tf->removeMethods)));
        }

        return $this->removeMethodsMap;
    }

    public function getFileDocBlock(VersionedFullyQualifiedName $versionedFullyQualifiedName): ?string
    {
        return $this->getTransformationFromVersionedFullyQualifiedName($this->getFileDocBlockMap(), $versionedFullyQualifiedName)?->fileDocBlock ?? null;
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
