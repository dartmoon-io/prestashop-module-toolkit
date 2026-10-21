<?php

/**
 * Copyright (c) 2023-2026 Dartmoon S.r.l. <hello@dartmoon.io>
 * SPDX-License-Identifier: MIT
 * See LICENSE for the full license text.
 */

declare(strict_types=1);

namespace Dartmoon\TabManager;

use Dartmoon\Utils\MultiLangText;
use Tab;

/** Uses the public ObjectModel API, including its ACL and cache invalidation. */
class TabManager
{
    public function __construct(private MultiLangText $translations = new MultiLangText())
    {
    }

    public function install(array $tabs, ?\Module $module = null): bool
    {
        $created = [];
        $failed = false;
        try {
            foreach ($tabs as $details) {
                $class = $details['class_name'] ?? '';
                if (!is_string($class) || !preg_match('/^[A-Za-z][A-Za-z0-9_]{0,63}$/D', $class)) {
                    throw new \InvalidArgumentException('Each tab requires a valid class_name (maximum 64 characters).');
                }
                $id = (int) \Tab::getIdFromClassName($class);
                if ($id > 0) {
                    $tab = new \Tab($id);
                    if ((string) $tab->module !== ($module?->name ?? '')) {
                        throw new \InvalidArgumentException("Tab {$class} belongs to another module.");
                    }
                    continue;
                }
                $parent = $details['parent_class_name'] ?? '';
                $parentId = $parent === '' ? 0 : (int) \Tab::getIdFromClassName($parent);
                if ($parent !== '' && $parentId === 0) {
                    throw new \InvalidArgumentException("Parent tab {$parent} does not exist; declare parents first.");
                }
                $tab = new \Tab();
                $tab->class_name = $class;
                $tab->module = $module?->name ?? '';
                $tab->id_parent = $parentId;
                $tab->route_name = $details['route_name'] ?? '';
                $tab->icon = $details['icon'] ?? '';
                $tab->active = (bool) ($details['visible'] ?? true);
                $tab->name = $this->translations->generate($details['name'] ?? $class);
                $tab->wording = $details['wording'] ?? null;
                $tab->wording_domain = $details['wording_domain'] ?? null;
                // Retain the object before save: Tab::add can persist a row
                // before an ACL failure, including an exception.
                $created[] = $tab;
                if (!$tab->save()) {
                    $failed = true;
                    break;
                }
            }
        } catch (\Throwable $exception) {
            $this->rollback($created);
            throw $exception;
        }
        if ($failed) {
            $this->rollback($created);

            return false;
        }

        return true;
    }

    public function uninstall(array $tabs): bool
    {
        $success = true;
        foreach (array_reverse($tabs) as $details) {
            $id = (int) \Tab::getIdFromClassName($details['class_name']);
            if ($id > 0 && !(new \Tab($id))->delete()) {
                $success = false;
            }
        }

        return $success;
    }

    public function uninstallForModule(\Module $module): bool
    {
        $tabs = iterator_to_array((function () use ($module) {
            yield from \Tab::getCollectionFromModule($module->name);
        })());
        // Children before parents, even if database IDs do not reflect the tree.
        $byId = [];
        foreach ($tabs as $tab) {
            $byId[(int) $tab->id] = $tab;
        }
        $depth = static function ($tab) use ($byId): int {
            $seen = [];
            while (isset($byId[(int) $tab->id_parent]) && !isset($seen[(int) $tab->id])) {
                $seen[(int) $tab->id] = true;
                $tab = $byId[(int) $tab->id_parent];
            }

            return count($seen);
        };
        usort($tabs, static fn ($a, $b) => $depth($b) <=> $depth($a));
        $success = true;
        foreach ($tabs as $tab) {
            if (!$tab->delete()) {
                $success = false;
            }
        }

        return $success;
    }

    private function rollback(array $tabs): void
    {
        $failed = [];
        $previous = null;
        foreach (array_reverse($tabs) as $tab) {
            if (!$tab->id) {
                continue;
            }
            try {
                if (!$tab->delete()) {
                    $failed[] = $tab->class_name;
                }
            } catch (\Throwable $exception) {
                $failed[] = $tab->class_name;
                $previous ??= $exception;
            }
        }
        if ($failed !== []) {
            throw new \RuntimeException('Unable to roll back tabs: ' . implode(', ', $failed), 0, $previous);
        }
    }
}
