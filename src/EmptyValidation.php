<?php

namespace Viloveul\Media;

use Viloveul\Media\Contracts\Validation as IValidation;

class EmptyValidation implements IValidation
{
    public function message(): string
    {
        return 'No Uploaded File(s).';
    }

    /**
     * @param $files
     */
    public function validate(array $files): bool
    {
        if (empty($files) || count($files) === 0) {
            return false;
        }
        return true;
    }
}
