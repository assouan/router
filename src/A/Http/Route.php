<?php

namespace A\Http;

/**
 * @property-read string $path
 */
#[\Attribute(\Attribute::TARGET_CLASS|\Attribute::IS_REPEATABLE)]
class Route
{
    public function __construct(protected string $path)
    {
    }

    public function __get(string $name) : mixed
    {
        return match ($name) {
            'path' => $this->path,
        };
    }

    public function inherit(self $parent) : static
    {
        $this->path = trim($parent->path, '/') . '/' . trim($this->path, '/');
        $this->path = trim($this->path, '/');

        return $this;
    }
}
