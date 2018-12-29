<?php

namespace Viloveul\Media\Contracts;

use Closure;
use Viloveul\Media\Contracts\Validation;

interface Uploader
{
    /**
     * @param Validation $validator
     */
    public function addValidation(Validation $validator): Validation;

    public function clear();

    public function errors(): array;

    public function passed(): bool;

    /**
     * @param $from
     * @param $to
     */
    public function transfer($from, $to = null);

    /**
     * @param $index
     * @param Closure  $handler
     */
    public function upload($index, Closure $handler);
}
