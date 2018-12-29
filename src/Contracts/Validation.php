<?php

namespace Viloveul\Media\Contracts;

interface Validation
{
    public function message(): string;

    /**
     * @param array $files
     */
    public function validate(array $files): bool;
}
