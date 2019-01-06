<?php

namespace Viloveul\Media\Contracts;

use Closure;
use Viloveul\Media\Contracts\Validation;

interface Uploader
{
    /**
     * @param string     $index
     * @param Validation $validator
     */
    public function addValidation(string $index, Validation $validator): Validation;

    /**
     * @param string $rawname
     */
    public function transform(string $rawname): string;

    /**
     * @param string  $index
     * @param Closure $handler
     */
    public function upload(string $index, Closure $handler);
}
