<?php

namespace Vixedin\System\Modules\Utils;

use Exception;
use Memcached;
use Vixedin\System\Modules\CustomException as EXP;

/**
 * @class Helper
 */
class Cache
{
    private static Memcached $instance;
    private Memcached $memcachedServer;
    public function __construct(array $params = [])
    {
        if (!isset(self::$instance)) {
            $server = $params[0];
            $port = $params[1];
            self::$instance = new Memcached();
            self::$instance->addServer($server, $port);
        }
    }

    public function removeCache(string $key): void
    {
        self::$instance->delete($key);
    }

    /**
     * @throws Exception
     */
    public function setCache(string $key, mixed $value, int $minutes = 10): mixed
    {
        if (empty($key) || empty($value)) {
            EXP::showException('key value pair data required');
        }

        //storing for 10 mins
        self::$instance->set($key, $value, (60 * $minutes));
        if (Memcached::RES_FAILURE === self::$instance->getResultCode()) {
            EXP::showException('unable to store');
        }

        return true;
    }

    /**
     * @param mixed $key
     * @return mixed
     */
    public function getCache(mixed $key): mixed
    {
        $data = self::$instance->get($key);

        if (Memcached::RES_NOTFOUND === self::$instance->getResultCode()) {
            return null;
        }

        return $data;
    }
}
