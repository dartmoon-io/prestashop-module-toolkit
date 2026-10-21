<?php

/**
 * Copyright (c) 2023-2026 Dartmoon S.r.l. <hello@dartmoon.io>
 * SPDX-License-Identifier: MIT
 * See LICENSE for the full license text.
 */

declare(strict_types=1);

namespace Dartmoon\Importers;

class ImageImporter
{
    public function importProductImageFromUrl(\Product $product, string $url, bool $cover = false, bool $generate_thumbnails = true): \Image
    {
        if (!in_array(strtolower((string) parse_url($url, PHP_URL_SCHEME)), ['http', 'https'], true)) {
            throw new \InvalidArgumentException('Remote product images require an HTTP or HTTPS URL.');
        }
        $temporary = tempnam(_PS_TMP_IMG_DIR_, 'dartmoon-image-');
        if ($temporary === false) {
            throw new \RuntimeException('Cannot create an image download file.');
        }
        try {
            if (!\Tools::copyFromUntrustedSource($url, $temporary)) {
                throw new \RuntimeException('Unable to download the product image.');
            }

            return $this->importProductImageFromFile($product, $temporary, $cover, $generate_thumbnails);
        } finally {
            @unlink($temporary);
        }
    }

    /** Local files are explicit, trusted inputs; never interpret a remote URL as a local path. */
    public function importProductImageFromFile(\Product $product, string $file, bool $cover = false, bool $generate_thumbnails = true): \Image
    {
        if (\Shop::getContext() !== \Shop::CONTEXT_SHOP) {
            throw new \LogicException('Select a single PrestaShop shop context before importing images.');
        }
        if (!\Validate::isLoadedObject($product) || !$product->isAssociatedToShop((int) \Context::getContext()->shop->id)) {
            throw new \InvalidArgumentException('Use a saved product associated with the current shop.');
        }
        if (str_contains($file, '://') || !is_file($file) || !is_readable($file)
            || @getimagesize($file) === false || !\ImageManager::checkImageMemoryLimit($file)) {
            throw new \InvalidArgumentException('The source file is not a readable image within the memory limit.');
        }
        if ($cover && \Image::getCover((int) $product->id)) {
            throw new \InvalidArgumentException('The product already has a cover; change it explicitly before importing another cover.');
        }
        $image = new ImportedImage();
        $image->id_product = (int) $product->id;
        $image->cover = $cover;
        if (!$image->save()) {
            throw new \RuntimeException('Unable to save the product image.');
        }
        try {
            $path = $image->getPathForCreation();
            if (!$path || !\ImageManager::resize($file, $path . '.jpg')) {
                throw new \RuntimeException('Unable to write the product image.');
            }
            if ($generate_thumbnails) {
                $this->generateProductImageThumbs($image);
            }
        } catch (\Throwable $exception) {
            if (!$image->delete()) {
                throw new \RuntimeException('Unable to roll back imported image ID ' . $image->id, 0, $exception);
            }
            throw $exception;
        }

        return $image;
    }

    public function generateProductImageThumbs(\Image $image, bool $delete_thumbs = true): void
    {
        $base = $this->imagePath($image);
        if (!is_file($base . '.jpg')) {
            throw new \RuntimeException('The product image source is missing.');
        }
        if ($delete_thumbs) {
            $this->deleteThumbs($image);
        }
        $formats = array_intersect(['jpg', 'png', 'webp', 'avif'], array_unique(array_merge(
            ['jpg'], explode(',', (string) \Configuration::get('PS_IMAGE_FORMAT'))
        )));
        foreach (\ImageType::getImagesTypes('products') as $type) {
            $this->validateImageType($type);
            foreach ($formats as $format) {
                $error = $targetWidth = $targetHeight = $sourceWidth = $sourceHeight = 0;
                if (!\ImageManager::resize(
                    $base . '.jpg', $base . '-' . $type['name'] . '.' . $format,
                    (int) $type['width'], (int) $type['height'], $format, false,
                    $error, $targetWidth, $targetHeight, 5, $sourceWidth, $sourceHeight,
                    $type['image_fitment'] ?? 'fit'
                )) {
                    throw new \RuntimeException('Unable to generate thumbnail: ' . $type['name'] . '.' . $format);
                }
            }
        }
        \Hook::exec('actionWatermark', ['id_image' => (int) $image->id, 'id_product' => (int) $image->id_product]);
    }

    /** Delete only declared variants for this exact image, never neighboring originals. */
    public function deleteThumbs(\Image $image): void
    {
        $base = $this->imagePath($image);
        foreach (\ImageType::getImagesTypes('products') as $type) {
            $this->validateImageType($type);
            foreach (['jpg', 'png', 'webp', 'avif'] as $format) {
                foreach (['', '2x'] as $density) {
                    $file = $base . '-' . $type['name'] . $density . '.' . $format;
                    if (is_file($file) && !@unlink($file)) {
                        throw new \RuntimeException('Unable to remove thumbnail: ' . basename($file));
                    }
                }
            }
        }
    }

    private function imagePath(\Image $image): string
    {
        if (!\Validate::isLoadedObject($image)) {
            throw new \InvalidArgumentException('Use a saved product image.');
        }
        $path = $image->getExistingImgPath();
        if (!preg_match('~^[0-9/]+$~D', (string) $path)) {
            throw new \UnexpectedValueException('Invalid native product image path.');
        }

        return rtrim(_PS_PRODUCT_IMG_DIR_, '/\\') . '/' . $path;
    }

    private function validateImageType(array $type): void
    {
        if (!preg_match('/^[a-zA-Z0-9_-]+$/D', $type['name'] ?? '') || (int) ($type['width'] ?? 0) < 1 || (int) ($type['height'] ?? 0) < 1) {
            throw new \UnexpectedValueException('Invalid PrestaShop image type.');
        }
    }
}
