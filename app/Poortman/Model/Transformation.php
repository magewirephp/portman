<?php

namespace App\Poortman\Model;

class Transformation
{
    protected array  $nameParts;
    protected ?array $renameParts;

    public function __construct(
        protected bool    $isClass,
        protected string  $name,
        protected ?string $rename,
        protected ?string $fileDocBlock,
        protected ?array $removeMethods,
        protected int     $level,
        protected int     $sort,
    )
    {
        $this->__set('name', $name);
        $this->__set('rename', $rename);
    }

    public function __get($name)
    {
        return $this->$name;
    }

    public function __set($name, $value)
    {
        switch ($name) {
            case 'name':
                $this->nameParts = explode('\\', $value);
                break;
            case 'rename':
                $this->renameParts = $value ? explode('\\', $value) : null;
                break;
        }

        $this->$name = $value;
    }
}