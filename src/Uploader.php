<?php

namespace Viloveul\Media;

use Closure;
use InvalidArgumentException;
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
    public function __construct(array $configs = [])
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
    }

    /**
     * @param IValidation $validator
     */
    public function addValidation(IValidation $validator): IValidation
    {
        $this->validators[] = $validator;
        return $validator;
    }

    public function clear()
    {
        $this->files = [];
        $this->validators = [];
        $this->messages = [];
    }

    /**
     * @return mixed
     */
    public function errors(): array
    {
        return $this->messages;
    }

    public function passed(): bool
    {
        foreach ($this->validators as $validator) {
            if (false === $validator->validate($this->files)) {
                $this->messages[] = $validator->message();
            }
        }
        return count($this->messages) === 0;
    }

    /**
     * @param $from
     * @param $to
     */
    public function transfer($from, $to = null)
    {
        try {
            if (empty($to)) {
                $to = pathinfo($from, PATHINFO_BASENAME);
            }

            $to = mt_rand() . '-' . preg_replace('/[^a-z0-9\-\.]+/', '-', strtolower($to));

            if (mb_strlen($to, 'UTF-8') > 200) {
                $to = substr($to, 0, 100) . substr($to, -100);
            }

            if ($this->directory !== false && is_uploaded_file($from)) {
                if (false !== move_uploaded_file($from, "{$this->directory}/{$to}")) {
                    return "{$this->directory}/{$to}";
                }
            }
        } catch (Exception $e) {
            $this->messages[] = $e->getMessage();
        }
        return false;
    }

    /**
     * @param  $index
     * @param  Closure  $handler
     * @return mixed
     */
    public function upload($index, Closure $handler)
    {
        $this->prepare($index);
        $files = [];
        $time = date('Y-m-d H:i:s');
        if (true === $this->passed()) {
            foreach ($this->files as $key => $file) {
                $filename = $this->transfer($file['tmp_name'], $file['name']);
                if (false !== $filename) {
                    $parts = explode('.', $key);
                    $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
                    $mimetype = $file['type'];
                    $size = $file['size'];
                    $rawname = $file['name'];
                    $category = $parts[0];
                    $files[] = compact('filename', 'rawname', 'extension', 'mimetype', 'size', 'category', 'time');
                }
            }
        }
        return $handler($files, $this->errors(), $this->files);
    }

    /**
     * @param  $index
     * @return mixed
     */
    protected function prepare($index = '*')
    {
        if (isset($_FILES) && !empty($_FILES)) {
            $uploadedFiles = [];
            $arr = $index === '*' ? $_FILES : (array_key_exists($index, $_FILES) ? [$index => $_FILES[$index]] : []);
            foreach ($arr as $category => $files) {
                foreach (['name', 'tmp_name', 'error', 'type', 'size'] as $key) {
                    if (array_key_exists($key, $files) && is_scalar($files[$key])) {
                        $uploadedFiles[$category][$key] = $files[$key];
                    } else {
                        $this->recursive($category, $files[$key], $key, $uploadedFiles);
                    }
                }
            }
            $this->files = array_filter($uploadedFiles, function ($value) {
                return $value['error'] == UPLOAD_ERR_OK;
            });
        }
    }

    /**
     * @param $category
     * @param $file
     * @param $key
     * @param $files
     */
    protected function recursive($category, $file, $key, &$files)
    {
        foreach ($file as $name => $value) {
            if (is_scalar($value)) {
                $files[$category . '.' . $name][$key] = $value;
            } else {
                $this->recursive($category . '.' . $name, $value, $key, $files);
            }
        }
    }
}
