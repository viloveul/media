<?php

namespace Viloveul\Media;

use Closure;
use RuntimeException;
use InvalidArgumentException;
use Viloveul\Media\TargetUploadException;
use Viloveul\Media\Contracts\Uploader as IUploader;
use Viloveul\Media\Contracts\Validation as IValidation;
use Psr\Http\Message\UploadedFileInterface as IUploadedFile;
use Psr\Http\Message\ServerRequestInterface as IServerRequest;

class Uploader implements IUploader
{
    /**
     * @var string
     */
    protected $baseurl = '';

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
            $this->directory = realpath($this->configs['target']);

            if (!is_dir($this->directory)) {
                throw new TargetUploadException("target directory does not exists.");
            }
            if (!is_writable($this->directory)) {
                throw new TargetUploadException("target directory is not writeable.");
            }
        }

        if (array_key_exists('baseurl', $this->configs)) {
            $this->baseurl = rtrim($this->configs['baseurl'], '/');
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
        if (mb_strlen($filename, 'UTF-8') > 100) {
            $filename = substr($filename, 0, 50) . substr($filename, -50);
        }
        return md5($filename) . $filename;
    }

    /**
     * @param  string  $index
     * @param  Closure $handler
     * @return mixed
     */
    public function upload(string $index, Closure $handler)
    {
        $files = [];
        $errors = [];
        $uploadedFiles = [];
        $year = date('Y');
        $month = date('m');
        $day = date('d');
        $directory = "{$this->directory}/{$year}/{$month}/{$day}";

        is_dir($directory) or mkdir($directory, 0777, true);

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
                    $file->moveTo($directory . '/' . $filename);
                    $url = "{$this->baseurl}/{$year}/{$month}/{$day}/{$filename}";
                    $uploadedFiles[] = [
                        'category' => $tags[0],
                        'tags' => $tags,
                        'filename' => $filename,
                        'realpath' => realpath("{$directory}/{$filename}"),
                        'type' => $file->getClientMediaType(),
                        'name' => $file->getClientFilename(),
                        'size' => $file->getSize(),
                        'url' => $url,
                    ];
                } catch (RuntimeException $e) {
                    $errors[] = [
                        'code' => $e->getCode(),
                        'title' => 'Uploader ' . get_class($e) . ' ' . $index,
                        'detail' => $e->getMEssage(),
                    ];
                }
            }
        }

        return $handler($uploadedFiles, $errors, $files);
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
                $errors[] = [
                    'code' => 400,
                    'title' => 'Uploader ' . get_class($validator) . ' ' . $index,
                    'detail' => $validator->message(),
                ];
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
            } elseif (is_array($file)) {
                $this->prepare($file, $prefix . $key . '.');
            } else {
                throw new InvalidArgumentException("Error Processing Request");
            }
        }
    }
}
