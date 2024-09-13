<?php

namespace Vixedin\System\Modules;

use Vixedin\System\Modules\Mailer;
use Vixedin\System\Model;

//App Messages Classes
use Vixedin\Applications\MGAuth\Modules\MGAuthMessages;
use Vixedin\Applications\Trivia\Modules\TriviaMessages;
use Vixedin\Applications\Soog\Modules\SoogMessages;
use Vixedin\Applications\Secure\Modules\SecureMessages;

/**
 * @class Access
 */
class Messages
{
    /**
     * Undocumented function
     *
     * @param string $appName
     * @param array $arguments
     * @return string
     */
    public static function welcomeMessage(string $appName, array $arguments): string
    {
        $message = ' ';

        switch($appName) {
            case 'MGAuth':
                $message = MGAuthMessages::welcomeMessage($arguments[0]);
                break;
            case 'Trivia':
                $message = TriviaMessages::welcomeMessage($arguments[0]);
                break;
            case 'Soog':
                $message = SoogMessages::welcomeMessage($arguments[0]);
                break;
            case 'Secure':
                $message = SecureMessages::welcomeMessage($arguments[0]);
                break;
            default:
                $message = 'Welcome Message';
        }
        return $message;
    }

    /**
     * Undocumented function
     *
     * @param [type] $appName
     * @param array $arguments
     * @return string
     */
    public static function customerWelcomeMessage($appName, array $arguments): string
    {
        $message = '';

        switch($appName) {
            case 'MGAuth':
                $message = MGAuthMessages::customerWelcomeMessage($arguments[0], $arguments[1]);
                break;
            case 'Trivia':
                $message = TriviaMessages::customerWelcomeMessage($arguments[0], $arguments[1]);
                break;
            case 'Soog':
                $message = SoogMessages::customerWelcomeMessage($arguments[0], $arguments[1]);
                break;
            case 'Secure':
                $message = SecureMessages::customerWelcomeMessage($arguments[0], $arguments[1]);
                break;
            default:
                $message = 'Welcome Message';
        }
        return $message;
    }

    /**
    * Undocumented function
    *
    * @param [type] $appName
    * @param array $arguments
    * @return string
    */
    public static function resetMessage($appName, array $arguments): string
    {
        $message = '';

        switch($appName) {
            case 'MGAuth':
                $message = MGAuthMessages::resetMessage($arguments[0]);
                break;
            case 'Trivia':
                $message = TriviaMessages::resetMessage($arguments[0]);
                break;
            case 'Soog':
                $message = SoogMessages::resetMessage($arguments[0]);
                break;
            case 'Secure':
                $message = SecureMessages::resetMessage($arguments[0]);
                break;
            default:
                $message = 'Welcome Message';
        }
        return $message;
    }

    /**
    * Undocumented function
    *
    * @param [type] $appName
    * @param array $arguments
    * @return string
    */
    public static function customerResetMessage($appName, array $arguments): string
    {
        $message = '';

        switch($appName) {
            case 'MGAuth':
                $message = MGAuthMessages::customerResetMessage($arguments[0], $arguments[1]);
                break;
            case 'Trivia':
                $message = TriviaMessages::customerResetMessage($arguments[0], $arguments[1]);
                break;
            case 'Soog':
                $message = SoogMessages::customerResetMessage($arguments[0], $arguments[1]);
                break;
            default:
                $message = 'Welcome Message';
        }
        return $message;
    }


}
