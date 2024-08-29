<?php

declare(strict_types=1);

namespace App\Poortman;

class TransformerConfiguration
{
    protected ?array $transformersMap = null;

    protected ?array $fullyQualifiedNameMap = null;

    protected ?array $namespaceMap = null;

    protected ?array $classNameMap = null;

    public function getNamespaceMap(): array
    {
        if (!$this->namespaceMap) {
            $this->namespaceMap = array_filter(array_filter($this->getFullyQualifiedNameMap(), fn($fqn) => !$fqn->isClass));
        }

        return $this->namespaceMap;
    }

    public function getFullyQualifiedNameMap(): array
    {
        if (!$this->fullyQualifiedNameMap) {
            $this->fullyQualifiedNameMap = $this->getTransformersMap();
        }

        return $this->fullyQualifiedNameMap;
    }

    public function getTransformersMap(): array
    {
        if (!$this->transformersMap) {
            $transformations = poortman_config('transformations', []);
            $transformersMap = $this->recurseTransformations($transformations);

            // make sure the more specific namespaces get renamed first (the more parts the earlier renamed)
            usort($transformersMap, fn($a, $b) => count($b->fromArr) <=> count($a->fromArr));
            $this->transformersMap = $transformersMap;
        }

        return $this->transformersMap;
    }

    protected function recurseTransformations(array $transformations): array
    {
        $namespaceMap = [];
        foreach ($transformations as $key => $transformation) {
            $from = trim($key, '\\');
            $to   = $key;
            if (isset($transformation['rename'])) {
                $to             = trim($transformation['rename'], '\\');
                $namespaceMap[] = $this->getTransformObject($from, $to, !str_ends_with($key, '\\'));
            }
            if (isset($transformation['children'])) {
                foreach ($this->recurseTransformations($transformation['children']) as $child) {
                    $namespaceMap[] = $this->getTransformObject(
                        $from . '\\' . $child->from,
                        $to . '\\' . $child->to,
                        $child->isClass
                    );
                }
            }
        }

        return $namespaceMap;
    }

    protected function getTransformObject(string $from, string $to, bool $isClass = false): object
    {
        return (object)[
            'isClass' => $isClass,
            'from'    => $from,
            'to'      => $to,
            'fromArr' => explode('\\', $from),
            'toArr'   => explode('\\', $to),
        ];
    }

    public function getClassNameMap(): array
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
}
