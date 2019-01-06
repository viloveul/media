<?php

namespace Viloveul\Media;

use Viloveul\Media\Contracts\Validation as IValidation;

class ImageValidation implements IValidation
{
    public function message(): string
    {
        return 'The file(s) is not image.';
    }

    /**
     * @param array $files
     */
    public function validate(array $files): bool
    {
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $hasError = false;
        foreach ($files as $file) {
            if (0 !== stripos(finfo_file($finfo, $file->getStream()->getMetadata('uri')), 'image')) {
                $hasError = true;
                continue;
            }
        }
        finfo_close($finfo);
        return !$hasError;
    }
}
