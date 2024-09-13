<?php

require __DIR__ . '/../vendor/autoload.php';
$baseDir = __DIR__ . '/../app/core/Applications/';
const BASE_DIR = __DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR;
$newAppName = @$argv[1];

$app = ucwords($newAppName);
$appLower = strtolower($app);
$mainAppDir = $baseDir . $app . '/';
mkdir($baseDir . $app);
mkdir($baseDir . $app . '/Models');
mkdir($baseDir . $app . '/Configs');
mkdir($baseDir . $app . '/Controllers');
mkdir($baseDir . $app . '/Modules');
mkdir($baseDir . $app . '/Files');
file_put_contents($mainAppDir . 'Files/allowed_tokens.txt', '');
mkdir($baseDir . $app . '/DeletedFiles');
mkdir($baseDir . $app . '/Backup');
mkdir($baseDir . $app . '/Crons');
mkdir($baseDir . $app . '/Logs');
mkdir($baseDir . $app . '/Migrations');

$mandetoryDbSetup = "
        CREATE DATABASE IF NOT EXISTS " . $app . "; 
        CREATE TABLE IF NOT EXISTS `customer_media` (
          `id` int NOT NULL AUTO_INCREMENT,
          `customer_id` int NOT NULL,
          `media_details` json NOT NULL,
          `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
          PRIMARY KEY (`id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
        CREATE TABLE IF NOT EXISTS `user_media` (
          `id` int NOT NULL AUTO_INCREMENT,
          `user_id` int NOT NULL,
          `media_details` json NOT NULL,
          `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
          PRIMARY KEY (`id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
        CREATE TABLE IF NOT EXISTS `users` (
            `id` int NOT NULL AUTO_INCREMENT,
            `email` varchar(255) NOT NULL,
            `pass` varchar(255) NOT NULL,
            `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`)
          ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
          CREATE TABLE IF NOT EXISTS `users_settings` (
            `id` int NOT NULL AUTO_INCREMENT,
            `user_id` int NOT NULL,
            `key_name` varchar(255) NOT NULL,
            `key_value` varchar(255) NOT NULL,
            `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`)
          ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
          CREATE TABLE IF NOT EXISTS `customers` (
            `id` int NOT NULL AUTO_INCREMENT,
            `email` varchar(255) NOT NULL,
            `pass` varchar(255) NOT NULL,
            `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`)
          ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
          CREATE TABLE IF NOT EXISTS `customer_settings` (
            `id` int NOT NULL AUTO_INCREMENT,
            `customer_id` int NOT NULL,
            `key_name` varchar(255) NOT NULL,
            `key_value` varchar(255) NOT NULL,
            `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`)
          ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
          CREATE TABLE IF NOT EXISTS `deleted_deactivated` (
            `id` int NOT NULL AUTO_INCREMENT,
            `t_name` varchar(255) DEFAULT NULL,
            `t_id` int DEFAULT NULL,
            `comment_reason` varchar(255) DEFAULT NULL,
            `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`)
          ) ENGINE=InnoDB DEFAULT CHARSET=latin1;
          CREATE TABLE IF NOT EXISTS `tokens` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `token` varchar(255) NOT NULL,
            `for_type` varchar(255) NOT NULL,
            `for_value` varchar(255) NOT NULL,
            `used` tinyint(6) NOT NULL DEFAULT 0,
            `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`)
          ) ENGINE=InnoDB DEFAULT CHARSET=latin1;

";
file_put_contents($mainAppDir . 'Migrations/migration_blueprint.sql', $mandetoryDbSetup);

$controller = fopen($baseDir . $app . '/Controllers/' . $app . '.php', 'w');
$cTxt = '<?php
namespace Vixedin\Applications\\' . $app . '\Controllers;

use Vixedin\System\Router as App;
use Vixedin\Applications\\' . $app . '\Modules\\' . $app . 'Manager as Manager;
use Vixedin\Applications\\'. $app . '\Models\\' . $app . 'Model;
use Vixedin\System\Controller;
use Vixedin\System\Modules\ImageUploader as Upload;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;

/**
 * @class {$app}
 */
class ' . $app . ' extends Controller
{

    //const CHATCAR_ACCESS_TOKEN = "' . $appLower . '";
    private $manager;


    public function __construct(App $app, ServerRequestInterface $request, ResponseInterface $response, array $args, string $action)
    {


        $authClassModel = in_array($action, $this->getAdminLevelOnlyActions()) ? null : new ' . $app . 'Model();

        parent::__construct(
            $app,
            $request,
            $response,
            $args,
            true,
            $action,
            $this->getAuthActions(),
            $this->getRquestActionsMethodMapping(),
            $this->getAdminLevelOnlyActions(),
            $this->getCustomerLevelActions(),
            $authClassModel
        );
        $this->manager = new Manager($this);
    }

    public function closeCustomerAccount() : void
    {
        $this->manager->closeCustomerAccount();
    }

    public function closeUserAccount() : void
    {
        $this->manager->closeUserAccount();
    }


    private function getCustomerLevelActions(): array
    {
        return [
            "addCustomerMedia",
            "addCustomerSetting",
            "getCustomer",
            "closeCustomerAccount"
        ];
    }

    public function forgotPassword(): void
    {
        $this->requiredFields([

            [
                ["userEmail", "Email"],
                [10, 100],
                2,
                "email",
            ],
        ]);

        $this->manager->forgotPassword(
            $this->getField("userEmail")
        );
    }

    public function resetPassword(): void
    {
        $this->requiredFields([

            [
                ["password", "Password"],
                [5, 100],
                0,
            ],
            [
                ["resetToken", "Reset Token"],
                [10, 100],
                1,
            ],
        ]);

        $this->manager->resetPassword(
            $this->getField("password"),
            $this->getField("resetToken")
        );
    }

    public function getCustomer() : void
    {
          $this->manager->getCustomer();
    }

    public function getUser() : void
    {
          $this->manager->getUser();
    }

     public function addCustomerSetting(): void
    {
        $this->requiredFields([
            [
                ["customerSettingName", "Setting Name"],
                [1, 50],
                4,
            ],
            [
                ["customerSettingValue", "Setting Value"],
                [1, 2000],
                1,
            ],
        ]);

        $this->manager->addCustomerSetting(
            $this->getField("customerSettingName"),
            $this->getField("customerSettingValue")
        );

    }

    public function login(): void
    {
        $this->requiredFields(
            [
                [
                    ["email", "Email"],
                    [EMAIL_SIGN_UP_MIN_CHAR, EMAIL_SIGN_UP_MAX_CHAR],
                    2,
                    "email",
                ],
                [
                    ["pass", "Password"],
                    [5, 50],
                    0,
                ],

            ]
        );

        $this->manager->login(
            $this->getField("email"),
            $this->getField("pass")
        );

    }

    public function activateCustomerAccount(): void
    {
        $this->requiredFields([
            [
                ["validationToken", "Activation Token"],
                [10, 100],
                1,
            ],
        ]);

        $this->manager->activateGeneralCustomerAccount(
            $this->getField("validationToken")
        );
    }

    public function activateAccount(): void
    {
        $this->requiredFields([

            [
                ["validationToken", "Activation Token"],
                [10, 100],
                1,
            ],
        ]);

        $this->manager->activateAccount(
            $this->getField("validationToken")
        );
    }

    public function registerCustomer(): void
    {
        $this->requiredFields([

            [
                ["customerEmail", "Email"],
                [1, 50],
                2,
                "email",
            ],
            [
                ["customerPass", "Password"],
                [1, 50],
                0,
            ]

        ]);

        $this->manager->signUpCustomer(
            $this->getField("customerEmail"),
            $this->getField("customerPass")
        );
    }

    public function loginCustomer(): void
    {
        $this->requiredFields([

            [
                ["customerEmail", "Email"],
                [1, 50],
                2,
                "email",
            ],
            [
                ["customerPass", "Password"],
                [1, 50],
                0,
            ]

        ]);

        $this->manager->signInCustomer(
            $this->getField("customerEmail"),
            $this->getField("customerPass")
        );

    }

    public function register(): void
    {
        $this->requiredFields(
            [
                [
                    ["email", "Email"],
                    [EMAIL_SIGN_UP_MIN_CHAR, EMAIL_SIGN_UP_MAX_CHAR],
                    2,
                    "email",
                ],
                [
                    ["pass", "Password"],
                    [5, 50],
                    0,
                ],

            ]
        );

        $this->manager->signUp(
            $this->getField("email"),
            $this->getField("pass")
        );
    }

    public function addUserSetting(): void
    {
        $this->requiredFields([
            [
                ["userSettingName", "Setting Name"],
                [1, 50],
                4,
            ],
            [
                ["userSettingValue", "Setting Value"],
                [1, 2000],
                1,
            ],
        ]);

        $this->manager->addUserSetting(
            $this->getField("userSettingName"),
            $this->getField("userSettingValue")
        );

    }


    private function getAuthActions(): array
    {
        return array_merge(
            $this->getAdminLevelOnlyActions(),
            $this->getCustomerLevelActions()
        );
    }

    public function forgotCustomerPassword(): void
    {
        $this->requiredFields([

            [
                ["email", "Email"],
                [1, 50],
                2,
                "email",
            ]
        ]);

        $this->manager->recoverGeneralCustomerPassword(
            $this->getField("email")
        );

    }

    public function resetCustomerPassword(): void
    {
        $this->requiredFields([

            [
                ["password", "Password"],
                [5, 100],
                0,
            ],
            [
                ["resetToken", "Reset Token"],
                [10, 100],
                1,
            ],
        ]);

        $this->manager->resetGeneralCustomerPassword(
            $this->getField("resetToken"),
            $this->getField("password"),
        );

    }

    private function getRquestActionsMethodMapping(): array
    {
        return [
            "GET" => [
                "getCustomer",
                "getUser",
            ],
            "POST" => [
                "addUserSetting",
                "addUserMedia",
                "addCustomerMedia",
                "addCustomerSetting",
                "register",
                "loginCustomer",
                "login",
                "registerCustomer",
                "activateCustomerAccount",
                "activateAccount",
                "forgotPassword",
                "resetPassword",
                "resetCustomerPassword",
                "forgotCustomerPassword",
                "closeUserAccount",
                "closeCustomerAccount"
            ]
        ];
    }

    public function addCustomerMedia(): void
    {

        $this->requiredFile("postMedia", 5242880, Uploader::DEFAULT_ALLOWED_FILE_TYPES, "Media Files");
        $this->manager->uploadCustomerMedia("postMedia");
    }

    public function addUserMedia(): void
    {

        $this->requiredFile("postMedia", 5242880, Uploader::DEFAULT_ALLOWED_FILE_TYPES, "Media Files");
        $this->manager->uploadUserMedia("postMedia");
    }



    private function getAdminLevelOnlyActions(): array
    {
        return [
            "addUserMedia",
            "addUserSetting",
            "getUser",
            "closeUserAccount"
        ];
    }

}

';
fwrite($controller, $cTxt);
fclose($controller);

$model = fopen($baseDir . $app . '/Models/' . $app . 'Model.php', 'w');

$mText = '<?php
namespace Vixedin\Applications\\' . $app . '\Models;

use Vixedin\System\Model;
use Vixedin\System\Modules\CustomException as EXP;

class ' . $app . 'Model extends Model
{

    public function __construct()
    {
        parent::__construct();
        $this->generateDB();
    }

    private function generateDB() : void
    {
        $this->generateDBTables();
    }

}
';

fwrite($model, $mText);
fclose($model);

$conf = fopen($baseDir . $app . '/Configs/' . $app . 'Configs.php', 'w');

$ConfText = '<?php

define("APP_TOKEN",$_ENV["' . $app . '_APP_TOKEN"]);
define("APP_BASE_URL",$_ENV["' . $app . '_APP_BASE_URL"]);
define("APP_DB_HOST",$_ENV["' . $app . '_APP_DB_HOST"]);
define("APP_DB_DRIVER",$_ENV["' . $app . '_APP_DB_DRIVER"]);
define("APP_SUPPORT_EMAIL_NAME",$_ENV["' . $app . '_APP_SUPPORT_EMAIL_NAME"]);
define("APP_EMAIL_DEBUG",$_ENV["' . $app . '_APP_EMAIL_DEBUG"]);
define("APP_SUPPORT_EMAIL",$_ENV["' . $app . '_APP_SUPPORT_EMAIL"]);
define("APP_NAME",$_ENV["' . $app . '_APP_NAME"]);
define("APP_DB_NAME",$_ENV["' . $app . '_APP_DB_NAME"]);
define("APP_DB_USER",$_ENV["' . $app . '_APP_DB_USER"]);
define("APP_DB_PORT",$_ENV["' . $app . '_APP_DB_PORT"]);
define("APP_DB_PASS",$_ENV["' . $app . '_APP_DB_PASS"]);
define("APP_DB_MIGRATION_BLUEPRINT_FILE_PATH", $_ENV["' . $app . '_APP_DB_MIGRATION_BLUEPRINT_FILE_PATH"]);
define("APP_HASH",$_ENV["' . $app . '_APP_HASH"]);
define("APP_STORAGE",$_ENV["' . $app . '_APP_STORAGE"]);
define("APP_RUBBISH_BIN",$_ENV["' . $app . '_APP_RUBBISH_BIN"]);
define("APP_BACKUP",$_ENV["' . $app . '_APP_BACKUP"]);
define("APP_LOGS",$_ENV["' . $app . '_APP_LOGS"]);
define("APP_MIGRATIONS",$_ENV["' . $app . '_APP_MIGRATIONS"]);
define("CURRENT_STATUS",$_ENV["' . $app . '_CURRENT_STATUS"]);
define("APP_SMTP_USER",$_ENV["' . $app . '_APP_SMTP_USER"]);
define("APP_SMTP_PASS",$_ENV["' . $app . '_APP_SMTP_PASS"]);
define("APP_SMTP_HOST",$_ENV["' . $app . '_APP_SMTP_HOST"]);
define("APP_SMTP_PORT",$_ENV["' . $app . '_APP_SMTP_PORT"]);
define("APP_SMTP_FROM_EMAIL",$_ENV["' . $app . '_APP_SMTP_FROM_EMAIL"]);
define("APP_SMTP_SECURITY",$_ENV["' . $app . '_APP_SMTP_SECURITY"]);
define("APP_SMTP_BODY_TYPE",$_ENV["' . $app . '_APP_SMTP_BODY_TYPE"]);
define("EMAIL_SIGN_UP_MAX_CHAR",$_ENV["' . $app . '_EMAIL_SIGN_UP_MAX_CHAR"]);
define("EMAIL_SIGN_UP_MIN_CHAR",$_ENV["' . $app . '_EMAIL_SIGN_UP_MIN_CHAR"]);
define("ACTIVATION_URL",$_ENV["' . $app . '_ACTIVATION_URL"]);
define("RESET_URL",$_ENV["' . $app . '_RESET_URL"]);
define("CUSTOMER_ACTIVATION_URL",$_ENV["' . $app . '_CUSTOMER_ACTIVATION_URL"]);
define("CUSTOMER_RESET_URL",$_ENV["' . $app . '_CUSTOMER_RESET_URL"]);
define("PIXELS_API_KEY",$_ENV["' . $app . '_PIXELS_API_KEY"]);
define("PIXELS_SEARCH_URL",$_ENV["' . $app . '_PIXELS_SEARCH_URL"]);

';

fwrite($conf, $ConfText);
fclose($conf);

$confEnv = fopen(__DIR__ . '/../env/.env', 'a+');

$ConfEnvText = '
#' . $app . '
' . $app . '_APP_TOKEN="' . $app . '_' . md5(random_int(1, 10000)) . '"
' . $app . '_APP_BASE_URL=""
' . $app . '_APP_DB_HOST="localhost"
' . $app . '_APP_DB_DRIVER="mysql"
' . $app . '_APP_SUPPORT_EMAIL_NAME=""
' . $app . '_APP_EMAIL_DEBUG=false
' . $app . '_APP_SUPPORT_EMAIL=""
' . $app . '_APP_NAME="' . $app . '"
' . $app . '_APP_DB_NAME="' . $app . '"
' . $app . '_APP_DB_USER="' . $app . '"
' . $app . '_APP_DB_PORT=3306
' . $app . '_APP_DB_PASS="' . $app . md5(random_int(1, 2000)) . '"
' . $app . '_APP_DB_MIGRATION_BLUEPRINT_FILE_PATH="${BASE_DIR}app/core/Applications/' . $app . '/Migrations/migration_blueprint.sql"
' . $app . '_APP_HASH="' . md5($app . random_int(1, 2000)) . '"
' . $app . '_APP_STORAGE="${BASE_DIR}app/core/Applications/' . $app . '/Files/"
' . $app . '_APP_RUBBISH_BIN="${BASE_DIR}app/core/Applications/' . $app . '/DeletedFiles/"
' . $app . '_APP_BACKUP="${BASE_DIR}app/core/Applications/' . $app . '/Backup/"
' . $app . '_APP_LOGS="${BASE_DIR}app/core/Applications/' . $app . '/Logs/"
' . $app . '_APP_MIGRATIONS="${BASE_DIR}app/core/Applications/' . $app . '/Migrations/"
' . $app . '_CURRENT_STATUS="dev"
' . $app . '_APP_SMTP_USER=""
' . $app . '_APP_SMTP_PASS=""
' . $app . '_APP_SMTP_HOST=""
' . $app . '_APP_SMTP_PORT=""
' . $app . '_APP_SMTP_FROM_EMAIL=""
' . $app . '_APP_SMTP_SECURITY="tls"
' . $app . '_APP_SMTP_BODY_TYPE="text/html"
' . $app . '_EMAIL_SIGN_UP_MAX_CHAR="100"
' . $app . '_EMAIL_SIGN_UP_MIN_CHAR="10"
' . $app . '_ACTIVATION_URL="http://localhost:5173/activate-account/"
' . $app . '_RESET_URL="http://localhost:5173/reset-password/"
' . $app . '_CUSTOMER_ACTIVATION_URL="http://localhost:5173/activate-account/"
' . $app . '_CUSTOMER_RESET_URL="http://localhost:5173/reset-password/"
' . $app . '_PIXELS_API_KEY="rXRet7r6egK8ZgpMw3sPSBOcAXtGUpo65liDK8LoqsHsmyLliPERUnI6"
' . $app . '_PIXELS_SEARCH_URL="https://api.pexels.com/v1/search?query="
';

fwrite($confEnv, $ConfEnvText);
fclose($confEnv);

$cronSet = fopen($baseDir . $app . '/Crons/' . $app . 'crons_setup.php', 'w');

$CronSetText = '<?php

require_once __DIR__ . "/../../../../configs/helper_functions.php";
require_once __DIR__ . "/../../../../../vendor/autoload.php";

try {
    $dotenv = \Dotenv\Dotenv::createImmutable(__DIR__ .  "/../../../../../env");
    $dotenv->load();
} catch (\Throwable $e) {
    //what to do if unable to load configs
    echo $e->getMessage();
    exit(1); //indicates issue
}

require_once __DIR__ . "/../../../../configs/config_defined.php";
require_once __DIR__ . "/../Configs/' . $app . 'Configs.php";


use Vixedin\Applications\\'. $app .'\Models\\'. $app .'Model;

//remove file longer than an hour
$dir = new DirectoryIterator(APP_STORAGE);
foreach ($dir as $file) {
    if (!$file->isDot()) {
        //manage file
        if ($file->getExtension() == "txt") {
            if (strtotime("- 1 hour") > $file->getMTime()) {
                unlink($file->getPathname());
            }

        }
    }
}


$db = new '.$app.'Model();

$mail = new Vixedin\System\Modules\Mailer();
';

fwrite($cronSet, $CronSetText);
fclose($cronSet);

$lib = fopen($baseDir . $app . '/Modules/' . $app . 'Manager.php', 'w');
$lText = '<?php
namespace Vixedin\Applications\\' . $app . '\Modules;

use Vixedin\System\Modules\CustomException as EXP;
use Vixedin\Applications\\' . $app . '\Models\\' . $app . 'Model;
use Vixedin\System\Model;
use Vixedin\System\Modules\Mailer;
use Vixedin\Applications\\' . $app . '\Modules\\' . $app . 'Messages;
use Vixedin\System\Modules\CloudUpload;
use Vixedin\System\Modules\Helper;
use Vixedin\System\Modules\Manager;

/**
 * @class Access
 */
class ' . $app . 'Manager extends Manager
{
    protected Model $model;
    protected mixed $conHandler;
    private $refName = "";
    private $allowedIps = [];
    protected Mailer $mailer;
    private $cUpload;

    public function __construct($con)
    {
        $this->model = new ' . $app . 'Model();
        $this->conHandler = $con;
        $this->mailer = new Mailer();
        parent::__construct(
            $this->model,
            $this->conHandler,
            $this->mailer
        );
        $this->appValidations();
        $this->cUpload = new CloudUpload();
    }

    /**
     * @param stdClass $con
     */
    private function appValidations()
    {
        $this->conHandler->addValidationCalls(function () {
            if (strpos($this->conHandler->getRefUrl(), "{$this->refName}") === false) {
                EXP::showException("Invalid call");
            }
        });

        $this->conHandler->runValidation();
    }


}

';
fwrite($lib, $lText);
fclose($lib);

$lib2 = fopen($baseDir . $app . '/Modules/' . $app . 'Messages.php', 'w');
$lText2 = '<?php
namespace Vixedin\Applications\\' . $app . '\Modules;

use Vixedin\System\Modules\CustomException as EXP;

/**
 * @class Access
 */
class ' . $app . 'Messages
{
    /**
     * @return string
     */
    public static function welcomeMessage(string $activateUrl = ""): string
    {
        return "
            <b>Greetings from " . APP_NAME . "</b><br />
<br /><p>Thank you for registering, please click on the link below to activate your account</p><br />
<a href=\'" . $activateUrl . "\'>Activate Account</a>
<br />
<br />
<p>Thank you</p>
<p>" . APP_NAME . "</p>
";
    }

    /**
     * Undocumented function
     *
     * @param string $activateUrl
     * @param [type] $storeName
     * @return string
     */
    public static function customerWelcomeMessage(string $activateUrl = "", $storeName): string
    {
        return "
            <b>Greetings from " . $storeName . "</b><br />
<br /><p>Thank you for registering, please click on the link below to activate your account</p><br />
<a href=\'" . $activateUrl . "\'>Activate Account</a>
<br />
<br />
<p>Thank you</p>
<p>" . $storeName . "</p>
";
    }

    /**
     * Undocumented function
     *
     * @param string $activationUrl
     * @return string
     */
    public static function resetMessage(string $resetUrl): string
    {
        return "
            <b>Greetings from " . APP_NAME . "</b><br />
<br /><p>You have requested to reset your password, please click on the link below to set a new password</p><br />
<a href=\'" . $resetUrl . "\'>Reset Your Password</a>
<br />
<br />
<p>Thank you</p>
<p>" . APP_NAME . "</p>
";
    }

    public static function customerResetMessage(string $resetUrl, $storeName): string
    {
        return "
            <b>Greetings from " . $storeName . "</b><br />
<br /><p>You have requested to reset your password, please click on the link below to set a new password</p><br />
<a href=\'" . $resetUrl . "\'>Reset Your Password</a>
<br />
<br />
<p>Thank you</p>
<p>" . $storeName . "</p>
";
    }
}

';
fwrite($lib2, $lText2);
fclose($lib2);

print("\nNew Aplication Files Created\n");
exit();
