<?php

namespace Vixedin\System\Modules;

use Upload\Storage\FileSystem;

/**
 * Class Uploader
 *
 * @package Vixedin\System\Modules
 */
class Uploader
{
    /**
     * @var FileSystem
     */
    private FileSystem $storage;

    /**
     * @var
     */
    private $file;

    /**
     * @var array
     */
    private array $allowedTypes;

    /**
     * @var string|string
     */
    private string $size;

    /**
     * @var string|string
     */
    private string $location;

    /**
     * @var
     */
    private mixed $fileData;

    /**
     * @var
     */
    private $errors;

    public const DEFAULT_ALLOWED_FILE_TYPES = [
        'image/png',
        'image/jpeg',
        'image/jpg',
        'image/gif'
    ];

    /**
     * Uploader constructor.
     *
     * @param  string $size
     * @param  array  $allowedTypes
     * @param  string $location
     * @param  string $uploadName
     * @throws \Exception
     */
    public function __construct(string $size, array $allowedTypes, string $location, string $uploadName)
    {
        $this->makeSurePathExists($location);
        $this->storage = new FileSystem($location);
        $this->size = $size;
        $this->allowedTypes = $allowedTypes;
        $this->location = $location;
        $this->uploadFile($uploadName);
    }

    /**
     * @param string $location
     */
    private function makeSurePathExists(string $location): void
    {
        if (!file_exists($location)) {
            mkdir($location);
        }
    }

    /**
     * @param  string $name
     * @throws \Exception
     */
    private function uploadFile(string $name): void
    {
        $this->file = new \Upload\File($name, $this->storage);
        $newName = md5(random_int(1, 10000) . random_int(1, 1000));
        while (file_exists($this->location . $newName)) {
            $newName = md5(random_int(1, 10000) . random_int(1, 1000));
        }
        $this->file->setName($newName);
        $settings = [
            new \Upload\Validation\Size($this->size),
            new \Upload\Validation\Mimetype($this->allowedTypes),
        ];

        $this->file->addValidations($settings);
        $this->fileData = [
            'name' => $this->file->getNameWithExtension(),
            'extension' => $this->file->getExtension(),
            'mime' => $this->file->getMimetype(),
            'size' => $this->file->getSize(),
            'md5' => $this->file->getMd5(),
            'dimensions' => $this->file->getDimensions(),
            'path' => $this->location . $this->file->getNameWithExtension(),
        ];
    }

    /**
     * @return bool
     */
    public function upload(): bool
    {
        try {
            // Success!
            $this->file->upload();
            $uploaded = true;
        } catch (\Exception $e) {
            // Fail!
            $this->errors = $this->file->getErrors();
            $uploaded = false;
        }
        return $uploaded;
    }

    /**
     * @return mixed
     */
    public function getUploadErrors()
    {
        return $this->errors;
    }

    /**
     * @return mixed
     */
    public function getFileData()
    {
        return $this->fileData;
    }
}
