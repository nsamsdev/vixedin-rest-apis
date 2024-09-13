<?php

namespace Vixedin\System\Modules;

use Exception;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;

/**
 * Class ErrorManager
 *
 * @package Vixedin\System\Modules
 */
class ErrorManager
{
    /**
     * @param $request
     * @param $response
     * @param $exception
     * @return mixed
     * @throws Exception
     */
    public function __invoke($request, $response, $exception): mixed
    {
        $this->log($exception);
        return $response->withJson(
            [
                'status' => 'error',
                'message' => $exception->getMessage(),
                'data' => [],
            ],
            CustomException::$isCustom ? $exception->getCode() : 500
        );
    }

    /**
     * @param $exception
     * @return void
     * @throws Exception
     */
    private function log($exception): void
    {
        $logType = CustomException::$isCustom ? 'info' : 'crit';
        $logger = new Logger((defined('APP_NAME') ? APP_NAME : 'DEFAULT') . '_logger');
        $logger->pushHandler(new StreamHandler((defined('APP_LOGS') ? APP_LOGS : LOG_PATH) . str_replace('-', '_', date('d-m-Y')) . '_' . $logType . '.log', CustomException::$isCustom ? Logger::INFO : Logger::CRITICAL));
        $generalMessage = CustomException::$isCustom ? 'User error' : 'System error';
        $logger->{$logType}(
            $generalMessage,
            [
                'message' => $exception->getMessage(),
                'line' => $exception->getLine(),
                'code' => $exception->getCode(),
                'file' => $exception->getFile(),
                'trace' => $exception->getTraceAsString(),
            ]
        );
    }
}
