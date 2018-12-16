<?php

namespace Viloveul\Media\Contracts;

use Closure;
use Viloveul\Media\Contracts\Validation;

interface Uploader
{
    /**
     * @param Validation $validator
     */
    public function addValidation(Validation $validator);

    /**
     * @param Closure $handler
     */
    public function upload(Closure $handler);
}
