<?php

namespace App\Poortman\Model;

class FullyQualifiedName
{
    public ?array  $namespace = null;
    public ?string $class     = null;

    public function toString()
    {
        return (string)$this->__toString();
    }

    public function __toString()
    {
        return join('\\', $this->getParts());
    }

    public function getParts()
    {
        return array_filter([...$this->namespace ?? [], $this->class]);
    }
}