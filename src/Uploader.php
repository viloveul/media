<?php

namespace Viloveul\Media;

use Closure;
use InvalidArgumentException;
use Psr\Http\Message\ServerRequestInterface as IServerRequest;
use Psr\Http\Message\UploadedFileInterface as IUploadedFile;
use RuntimeException;
use Viloveul\Media\Contracts\Uploader as IUploader;
use Viloveul\Media\Contracts\Validation as IValidation;
use Viloveul\Media\TargetUploadException;

class Uploader implements IUploader
{
    /**
     * @var array
     */
    protected $configs = [];

    /**
     * @var mixed
     */
    protected $directory = false;

    /**
     * @var array
     */
    protected $files = [];

    /**
     * @var array
     */
    protected $messages = [];

    /**
     * @var array
     */
    protected $validators = [];

    /**
     * @param array $configs
     */
    public function __construct(IServerRequest $request, array $configs = [])
    {
        $this->configs = $configs;

        if (!array_key_exists('target', $this->configs)) {
            throw new InvalidArgumentException("configs target must be set.");
        }

        if (!preg_match('/https?\:\/\//', $this->configs['target'])) {
            $this->directory = realpath($this->configs['target']) . '/' . date('Y-m-d_H');

            is_dir($this->directory) or mkdir($this->directory, 0777, true);

            if (!is_dir($this->directory)) {
                throw new TargetUploadException("target directory does not exists.");
            }
            if (!is_writable($this->directory)) {
                throw new TargetUploadException("target directory is not writeable.");
            }
        }

        $this->prepare($request->getUploadedFiles());
    }

    /**
     * @param  string      $index
     * @param  IValidation $validator
     * @return mixed
     */
    public function addValidation(string $index = '*', IValidation $validator): IValidation
    {
        if (!array_key_exists($index, $this->validators)) {
            $this->validators[$index] = [];
        }
        $this->validators[$index][] = $validator;
        return $validator;
    }

    /**
     * @param string $rawname
     */
    public function transform(string $rawname): string
    {
        $filename = mt_rand() . '-' . preg_replace('/[^a-z0-9\-\.]+/', '-', strtolower($rawname));
        if (mb_strlen($filename, 'UTF-8') > 200) {
            $filename = substr($filename, 0, 100) . substr($filename, -100);
        }
        return "{$this->directory}/{$filename}";
    }

    /**
     * @param  string  $index
     * @param  Closure $handler
     * @return mixed
     */
    public function upload(string $index, Closure $handler)
    {
        $time = date('Y-m-d H:i:s');
        $files = [];
        $errors = [];
        $uploadedFiles = [];

        if ($index !== '*') {
            $files = array_filter($this->files, function ($key) use ($index) {
                return $key === $index || 0 === strpos($key . '.', $index);
            }, ARRAY_FILTER_USE_KEY);
        } else {
            $files = $this->files;
        }

        if ($this->check($index, $files, $errors) === true) {
            foreach ($files as $key => $file) {
                try {
                    $tags = $this->fillCategories($key);
                    $filename = $this->transform($file->getClientFilename());
                    $file->moveTo($filename);
                    $parts = explode('/', $filename);
                    $uploadedFiles[] = [
                        'category' => $tags[0],
                        'tags' => $tags,
                        'filename' => array_pop($parts),
                        'directory' => implode('/', $parts),
                        'type' => $file->getClientMediaType(),
                        'name' => $file->getClientFilename(),
                        'size' => $file->getSize(),
                        'time' => $time,
                    ];
                } catch (RuntimeException $e) {
                    $errors[] = $e->getMEssage();
                }
            }
        }

        return $handler($uploadedFiles, $errors, $this->files);
    }

    /**
     * @param  $index
     * @return mixed
     */
    protected function check($index = '*', array $files, &$errors = [])
    {
        $validators = array_key_exists('*', $this->validators) ? $this->validators['*'] : [];
        $parts = explode('.', $index);
        if (array_key_exists($parts[0], $this->validators)) {
            $validators = array_merge($validators, $this->validators[$parts[0]]);
        }

        foreach ($validators as $validator) {
            if (!$validator->validate($files)) {
                $errors[] = $validator->message();
                return false;
            }
        }
        return true;
    }

    /**
     * @param  array   $params
     * @return mixed
     */
    protected function fillCategories(string $name)
    {
        $tags = [];
        $params = explode('.', $name);
        foreach ($params as $key => $value) {
            $tags[$key] = isset($tags[$key - 1]) ? ($tags[$key - 1] . '.' . $value) : $value;
        }
        return $tags;
    }

    /**
     * @param array     $files
     * @param $prefix
     */
    protected function prepare(array $files, $prefix = null)
    {
        foreach ($files as $key => $file) {
            if ($file instanceof IUploadedFile) {
                $this->files[$prefix . $key] = $file;
            } else {
                $this->prepare($file, $prefix . $key . '.');
            }
        }
    }
}
