<?php

/**
 * Copyright (c) 2023-2026 Dartmoon S.r.l. <hello@dartmoon.io>
 * SPDX-License-Identifier: MIT
 * See LICENSE for the full license text.
 */

declare(strict_types=1);

namespace Dartmoon\Config;

/** A repository belongs to one module; paths never depend on vendor depth. */
class Config
{
    private array $items = [];

    public function __construct(private string $configFilePath)
    {
        $this->configFilePath = rtrim($configFilePath, '/\\') . DIRECTORY_SEPARATOR;
    }

    public function get(string $path, mixed $default = false): mixed
    {
        return $this->getConfig($path, $default);
    }

    public function getConfig(string $path, mixed $default = false): mixed
    {
        [$file, $key] = array_pad(explode('.', $path, 2), 2, null);
        $config = $this->getConfigFile($file);

        return $key === null ? $config : $this->getConfigValue($config, $key, $default);
    }

    public function getConfigValue(array $config, string $key, mixed $default = false): mixed
    {
        foreach (explode('.', $key) as $segment) {
            if (!is_array($config) || !array_key_exists($segment, $config)) {
                return $default;
            }
            $config = $config[$segment];
        }

        return $config;
    }

    public function getConfigFile(string $fileName): array
    {
        if (!preg_match('/^[A-Za-z0-9_-]+$/D', $fileName)) {
            throw new \InvalidArgumentException('Invalid configuration file name.');
        }
        if (!array_key_exists($fileName, $this->items)) {
            $file = $this->configFilePath . $fileName . '.php';
            $items = is_file($file) ? (static fn () => require $file)() : [];
            if (!is_array($items)) {
                throw new \UnexpectedValueException("Configuration {$file} must return an array.");
            }
            $this->items[$fileName] = $items;
        }

        return $this->items[$fileName];
    }
}
