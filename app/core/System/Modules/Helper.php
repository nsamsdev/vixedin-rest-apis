<?php

namespace Vixedin\System\Modules;

use Exception;
use Vixedin\System\Modules\CustomException as EXP;

/**
 * @class Helper
 */
class Helper
{
    private static $utils = [];

    /**
     * @param  array $numbers
     * @return int|mixed
     */
    public static function getTotal($numbers = array()): int
    {
        $total = 0;
        foreach ($numbers as $number) {
            $total += $number;
        }
        return $total;
    }

    /**
     * @param mixed $class
     * @param array $instanceParams
     * @return void
     * @throws Exception
     */
    public static function addUtilsClass(mixed $class, array $instanceParams): void
    {
        if (!class_exists($class)) {
            Exp::showException('unable to add utils class');
        }

        if (!array_key_exists($class, self::$utils)) {
            self::$utils[$class] = new $class($instanceParams);
        }
    }

    /**
     * @param mixed $class
     * @return mixed
     * @throws Exception
     */
    public static function getUtilsClass(mixed $class): mixed
    {
        if (!array_key_exists($class, self::$utils)) {
            Exp::showException('utils class does not exist');
        }

        return self::$utils[$class];
    }

    /**
     * @param  $string
     * @return string
     */
    public static function escape($string)
    {
        return htmlspecialchars($string);
    }

    /**
     * @param  $value
     * @return bool
     */
    public static function checkIsNumber($value): bool
    {
        return is_numeric($value);
    }

    /**
     * @param mixed $con
     * @param string $imageMain
     * @return string
     * @throws Exception
     */
    public static function uploadFileFromString(mixed $con, string $imageMain): string
    {

        [$imageType, $imageData] = explode(';', $imageMain);
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

        $randomString = $con->generateRandomString();

        while (file_exists(APP_STORAGE . $randomString . $ext)) {
            $randomString = $con->generateRandomString();
        }

        $newPath = APP_STORAGE . $randomString . $ext;
        file_put_contents($newPath, base64_decode($data));
        return $newPath;

    }

    /**
     * @return string
     * @throws Exception
     */
    public static function getCustomName(): string
    {
        $names = [];
        $names[0] = [
            'didyDo',
            'bottom',
            'captin',
            'funny',
            'Edwarshu',
            'Kennedydazzle',
            'Twinklelmer',
            'Azdner',
            'Greenckson',
            'Ryanuka',
            'Stinkbson',
            'Mischieker',
            'Wellsgoodness',
            'Gonzalette',
            'bumbaclart',
            'WoDingo',
            'ShutUp',
            'SayWha',
            'WagWan',
            'Haaa',
        ];

        $names[1] = [
            'Dat',
            'Farter',
            'crackPack',
            'Snack',
            'Industries',
            'Schmandywork',
            'Thornton',
            'Greenway',
            'Unlimited',
            'Code',
            'Farts',
            'Sharts',
            'DingleBerry',
            'PooDink',
            'Stinks',
            'Flabz',
            'Rabz',
            'Jazz',
            'Mazz',
            'SuckIt',
        ];
        $count = 0;

        $maxCount = min(count($names[0]), count($names[1]));

        return $names[random_int(0, 1)][random_int(0, ($maxCount - 1))] . $names[random_int(0, 1)][random_int(0, ($maxCount - 1))];
    }

    /**
     * custom regex use.
     *
     * @param string $string
     * @param string $pattern
     */
    public static function userRegEx($string, $pattern)
    {
    }
}
