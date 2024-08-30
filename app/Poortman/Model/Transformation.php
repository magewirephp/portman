<?php

namespace App\Poortman\Model;

class Transformation
{
    public array  $nameParts;
    public ?array $renameParts;

    public function __construct(
        public bool    $isClass,
        public string  $name,
        public ?string $rename,
        public ?string $fileDocBlock,
        public int     $level,
        public int     $sort,
    )
    {
        $this->__set('name', $name);
        $this->__set('rename', $rename);
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