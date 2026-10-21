<?php

/**
 * Copyright (c) 2023-2026 Dartmoon S.r.l. <hello@dartmoon.io>
 * SPDX-License-Identifier: MIT
 * See LICENSE for the full license text.
 */

declare(strict_types=1);

namespace Dartmoon\Assets;

/** Resolve Vite entries and render the tags returned by PrestaShop display hooks. */
class AssetManager
{
    private ?array $manifest = null;

    public function __construct(
        private string $moduleDirectory,
        private string $moduleUri,
        private string $buildDirectory = 'views/assets',
    ) {
        $this->assertRelativePath($buildDirectory);
    }

    public static function forModule(\Module $module, string $buildDirectory = 'views/assets'): static
    {
        return new static($module->getLocalPath(), $module->getPathUri(), $buildDirectory);
    }

    /** Return the public URL for a source entry, or null before assets are built. */
    public function url(string $entry): ?string
    {
        if ($this->manifest() === []) {
            return null;
        }

        return $this->fileUrl($this->chunk($entry)['file']);
    }

    /** Render styles before scripts, including CSS belonging to static imports. */
    public function render(string|array $entries): string
    {
        if ($this->manifest() === []) {
            return '';
        }
        $styles = $scripts = $visited = [];
        foreach ((array) $entries as $entry) {
            $chunk = $this->chunk($entry);
            $this->collectStyles($entry, $styles, $visited);
            if (preg_match('/\.(?:m?js)$/D', $chunk['file'])) {
                $scripts[$chunk['file']] = true;
            } elseif (!str_ends_with($chunk['file'], '.css')) {
                throw new \InvalidArgumentException('Only CSS and JavaScript entries can be rendered: ' . $entry);
            }
        }
        $html = '';
        foreach (array_keys($styles) as $file) {
            $html .= '<link rel="stylesheet" href="' . $this->escapeUrl($file) . '">' . "\n";
        }
        foreach (array_keys($scripts) as $file) {
            $html .= '<script type="module" src="' . $this->escapeUrl($file) . '"></script>' . "\n";
        }

        return $html;
    }

    private function collectStyles(string $entry, array &$styles, array &$visited): void
    {
        if (isset($visited[$entry])) {
            return;
        }
        $visited[$entry] = true;
        $chunk = $this->chunk($entry);
        foreach ($this->list($chunk, 'imports') as $import) {
            $this->collectStyles($import, $styles, $visited);
        }
        foreach ($this->list($chunk, 'css') as $file) {
            $this->assertRelativePath($file);
            $styles[$file] = true;
        }
        if (str_ends_with($chunk['file'], '.css')) {
            $styles[$chunk['file']] = true;
        }
    }

    private function manifest(): array
    {
        if ($this->manifest === null) {
            $path = rtrim($this->moduleDirectory, '/\\') . '/' . $this->buildDirectory . '/manifest.json';
            if (!is_file($path)) {
                return [];
            }
            $manifest = json_decode(file_get_contents($path), true, 512, JSON_THROW_ON_ERROR);
            if (!is_array($manifest)) {
                throw new \UnexpectedValueException('The Vite manifest must contain an object: ' . $path);
            }
            $this->manifest = $manifest;
        }

        return $this->manifest;
    }

    private function chunk(string $entry): array
    {
        $manifest = $this->manifest();
        if (!array_key_exists($entry, $manifest)) {
            throw new \OutOfBoundsException('Entry not found in the Vite manifest: ' . $entry);
        }
        $chunk = $manifest[$entry];
        if (!is_array($chunk) || !isset($chunk['file']) || !is_string($chunk['file'])) {
            throw new \UnexpectedValueException('Invalid Vite manifest entry: ' . $entry);
        }
        $this->assertRelativePath($chunk['file']);

        return $chunk;
    }

    private function list(array $chunk, string $key): array
    {
        $values = $chunk[$key] ?? [];
        if (!is_array($values) || !array_is_list($values)) {
            throw new \UnexpectedValueException('Invalid Vite manifest list: ' . $key);
        }
        foreach ($values as $value) {
            if (!is_string($value)) {
                throw new \UnexpectedValueException('Vite manifest lists must contain strings: ' . $key);
            }
        }

        return $values;
    }

    private function assertRelativePath(string $path): void
    {
        $decoded = rawurldecode($path);
        if ($decoded === '' || preg_match('~[\\\\:\x00-\x1f?#]|^/|(?:^|/)\.{1,2}(?:/|$)~', $decoded)) {
            throw new \UnexpectedValueException('Expected a relative asset path: ' . $path);
        }
    }

    private function fileUrl(string $file): string
    {
        return rtrim($this->moduleUri, '/') . '/' . $this->buildDirectory . '/' . $file;
    }

    private function escapeUrl(string $file): string
    {
        return htmlspecialchars($this->fileUrl($file), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }
}
