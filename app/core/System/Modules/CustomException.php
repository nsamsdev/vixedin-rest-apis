<?php

namespace Vixedin\System\Modules;

use Exception;

/**
 * Class CustomException
 *
 * @package Vixedin\System\Modules
 */
class CustomException
{
    /**
     * @var array
     */
    private static array $exceptionCodes = [
        1 => 'Empty user Id',
    ];

    /**
     * @var bool
     */
    public static bool $isCustom = false;

    /**
     * @param  string $message
     * @param  int    $code
     * @throws Exception
     */
    public static function showException(string $message, int $code = 400)
    {
        self::$isCustom = true;
        throw new Exception($message, $code);
    }
}
