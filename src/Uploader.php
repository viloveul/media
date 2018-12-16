<?php

namespace Viloveul\Media;

use Closure;
use Viloveul\Media\Contracts\Uploader as IUploader;
use Viloveul\Media\Contracts\Validation as IValidation;

class Uploader implements IUploader
{
    /**
     * @var array
     */
    protected $validators = [];

    /**
     * @param IValidation $validator
     */
    public function addValidation(IValidation $validator)
    {
        $this->validators[] = $validator;
    }

    /**
     * @param Closure $handler
     */
    public function upload(Closure $handler)
    {
    	foreach ($this->validators as $validator) {
    		
    	}
    }
}
