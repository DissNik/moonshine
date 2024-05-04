<?php

namespace MoonShine\Exceptions;

class InvalidHome extends MoonShineException
{
    public static function create(string $class): self
    {
        return new self("Could not create the home, `$class` does not implement `Page` or `Resource`");
    }
}
