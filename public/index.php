<?php

declare (strict_types = 1);
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, DELETE, PUT, OPTIONS');
header('Access-Control-Allow-Headers: *');

use Vixedin\System\Modules\CustomException as EXP;
use Vixedin\System\Modules\ErrorManager;
use Vixedin\System\Modules\Helper;
use Vixedin\System\Modules\Utils\Cache;
use Vixedin\System\Router;

//get auto loader
require __DIR__ . '/../vendor/autoload.php';
//load env files
try {
    $dotenv = \Dotenv\Dotenv::createImmutable(__DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'env');
    $dotenv->load();
} catch (\Throwable $e) {
    //what to do if unable to load configs
    // http_response_code(500);
    // echo 'offline error';
    // exit(1); //indicates issue
    EXP::showException($e->getMessage(), 500);
}

require __DIR__ . '/../app/configs/config_defined.php';
require __DIR__ . '/../app/configs/helper_functions.php';

//add utils classes
Helper::addUtilsClass(Cache::class, ['127.0.0.1', 11211]);

$app = new Router(require __DIR__ . '/../app/configs/config.php');

/* container */
$c = $app->getContainer();

/* home page */
$app->get('/', function ($request, $response, $args) use ($app) {
    if (MAIN_STATUS == 'development') {
        die('Offline');
    } else {
        header("HTTP/1.1 301 Moved Permanently");
        header('Location: https://' . DEFAULT_HOME_APP);
        exit();
    }
});

/* app requests */
$app->any('/{appClass}/{appAction}', function ($request, $response, $args) use ($app, $c) {
    $appClassName = $args['appClass']; //ucfirst(strtolower($args['appClass']));
    $className = "\Vixedin\Applications\\{$appClassName}\Controllers\\{$appClassName}";
    $method = $args['appAction'];
    $methodFull = $args['appAction'];
    if (!class_exists($className)) {
        EXP::showException('UTLA');
    }
    Router::setClassName(strtolower($args['appClass']));
    $confFile = __DIR__ . "/../app/core/Applications/{$appClassName}/Configs/{$appClassName}Configs.php";
    if (!file_exists($confFile)) {
        EXP::showException('UTLCF');
    }
    require $confFile;

    $appClass = new $className($app, $request, $response, $args, $method);
    if (!method_exists($appClass, $method)) {
        if (!method_exists($appClass, $methodFull)) {
            EXP::showException('UTPAA');
        }
    }
    $appClass->$method();
});

/* custom error handler */
$c['errorHandler'] = function ($c) {
    return new ErrorManager();

};

$c['phpErrorHandler'] = function ($c) {
    return new ErrorManager();
};

//run app
$app->run();
