<?php

/**
 * Copyright (c) 2023-2026 Dartmoon S.r.l. <hello@dartmoon.io>
 * SPDX-License-Identifier: MIT
 * See LICENSE for the full license text.
 */

declare(strict_types=1);

namespace Dartmoon\Importers;

/** @internal Allows native Image::delete() to roll back a failed initial file write. */
class ImportedImage extends \Image
{
    public function deleteImage($forceDelete = false)
    {
        $file = $this->image_dir . $this->getExistingImgPath() . '.' . $this->image_format;

        return !is_file($file) || parent::deleteImage($forceDelete);
    }
}
