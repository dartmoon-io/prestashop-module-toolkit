<?php

declare(strict_types=1);

namespace Dartmoon\Utils\Facades;

class MultiLangText
{
    public static function __callStatic(string $name, array $arguments): mixed
    {
        return (new \Dartmoon\Utils\MultiLangText())->{$name}(...$arguments);
    }
}
