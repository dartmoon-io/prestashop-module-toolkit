<?php

/**
 * Copyright (c) 2023-2026 Dartmoon S.r.l. <hello@dartmoon.io>
 * SPDX-License-Identifier: MIT
 * See LICENSE for the full license text.
 */

declare(strict_types=1);

namespace Dartmoon\Filters;

use Dartmoon\Filters\Filters\AbstractFilter;
use Dartmoon\Filters\Filters\Append;
use Dartmoon\Filters\Filters\Clean;
use Dartmoon\Filters\Filters\Slugify;
use Dartmoon\Filters\Filters\Title;

class FilterService
{
    public const FILTERS = [Append::class, Clean::class, Slugify::class, Title::class];
    private array $filters = [];

    public function __construct(iterable $filters = [])
    {
        foreach (self::FILTERS as $filter) {
            $this->registerFilter(new $filter());
        }
        foreach ($filters as $filter) {
            $this->registerFilter($filter);
        }
    }

    public function registerFilter(AbstractFilter $filter): self
    {
        $name = $filter->getName();
        if (!preg_match('/^[a-z][a-z0-9_-]*$/D', $name)) {
            throw new \InvalidArgumentException('Invalid filter name: ' . $name);
        }
        $this->filters[$name] = $filter;

        return $this;
    }

    /** Array notation supports literal pipes/colons: ['append' => ['a|b:c']]. */
    public function filter(mixed $value, string|array $filters): mixed
    {
        if (is_string($filters)) {
            $filters = $filters === '' ? [] : explode('|', $filters);
        }
        foreach ($filters as $name => $definition) {
            if (is_int($name) && is_string($definition)) {
                [$name, $parameter] = array_pad(explode(':', $definition, 2), 2, null);
                $params = $parameter === null ? [] : [$parameter];
            } elseif (is_string($name) && is_array($definition)) {
                $params = array_values($definition);
            } else {
                throw new \InvalidArgumentException('Use filter strings or named parameter arrays.');
            }
            if (!isset($this->filters[$name])) {
                throw new \InvalidArgumentException("Filter '{$name}' is not registered.");
            }
            $value = $this->filters[$name]->filter($value, $params);
        }

        return $value;
    }
}
