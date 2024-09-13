<?php

namespace Vixedin\System\Modules;

use Exception;
use Vixedin\System\Modules\CustomException as EXP;

/**
 * Class Uploader
 *
 * @package Vixedin\System\Modules
 */
class CloudUpload
{
    public function __construct()
    {
        \Cloudinary::config(
            [
                "cloud_name" => CLOUDINARY_NAME,
                "api_key" => CLOUDINARY_API_KEY,
                "api_secret" => CLOUDINARY_SECRET,
                "secure" => true,
            ]
        );
    }

    /**
     * @param string $image
     * @return mixed
     * @throws Exception
     */
    public function uploadAppImage(string $image): mixed
    {
        list($imageType, $imageData) = explode(';', $image);
        list($base, $data) = explode(',', $imageData);

        if (empty($imageType) || empty($imageData)) {
            EXP::showException('Unable to get image data');
        }

        $ext = match ($imageType) {
            'data:image/png' => '.png',
            'data:image/jpg' => '.jpg',
            'data:image/jpeg' => '.jpeg',
            default => EXP::showException('Only PNG, JPG and JPEG are allowed'),
        };

        $randomString = md5(random_int(1, 1000) . '__' . uniqid() . '_' . time());

        while (file_exists(APP_STORAGE . $randomString . $ext)) {
            $randomString = md5(random_int(1, 1000) . '__' . uniqid() . '_' . time());

        }

        file_put_contents(APP_STORAGE . $randomString . $ext, base64_decode($data));

        $uploadData = $this->simpleUpload(APP_STORAGE . $randomString . $ext, strtolower(APP_NAME));

        return $uploadData['secure_url'];

    }

    /**
     * Undocumented function
     *
     * @param  string $images
     * @return array
     */
    public function uploadMultipleAppImages(string $images)
    {
        $imagesData = [];

        foreach (explode('_|_', $images) as $image) {
            if (empty($image)) {
                continue;
            }
            list($imageType, $imageData) = explode(';', $image);
            list($base, $data) = explode(',', $imageData);

            //echo '<pre>';var_dump([$imageType, $imageData]);die;

            if (empty($imageType) || empty($imageData)) {
                EXP::showException('Unable to get image data' . print_r($image, true));
            }

            switch ($imageType) {
                case 'data:image/png':
                    $ext = '.png';
                    break;
                case 'data:image/jpg':
                    $ext = '.jpg';
                    break;
                case 'data:image/jpeg':
                    $ext = '.jpeg';
                    break;
                default:
                    EXP::showException('Only PNG, JPG and JPEG are allowed');
            }

            $randomString = md5(random_int(1, 1000) . '__' . uniqid() . '_' . time());

            while (file_exists(APP_STORAGE . $randomString . $ext)) {
                $randomString = md5(random_int(1, 1000) . '__' . uniqid() . '_' . time());

            }

            file_put_contents(APP_STORAGE . $randomString . $ext, base64_decode($data));

            $uploadData = $this->simpleUpload(APP_STORAGE . $randomString . $ext, strtolower(APP_NAME));
            $imagesData[] = $uploadData['secure_url'];
        }

        return $imagesData;

    }

    /**
     * @param $filePath
     * @param string $folderName
     * @return mixed
     */
    public function simpleUpload($filePath, string $folderName = ''): mixed
    {
        if (empty($folderName)) {
            $response = \Cloudinary\Uploader::upload($filePath);
        } else {
            $response = \Cloudinary\Uploader::upload($filePath, ["folder" => $folderName]);
        }
        return $response;
    }
}
