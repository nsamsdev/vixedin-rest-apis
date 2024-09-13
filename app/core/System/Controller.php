<?php

namespace Vixedin\System;

use Exception;
use Slim\App;
use Vixedin\System\Modules\CustomerSettings;
use Vixedin\System\Model;
use Vixedin\System\Modules\CustomException as EXP;
use Vixedin\System\Modules\Helper;
use Vixedin\System\Modules\UserSettings;
use Vixedin\System\Modules\Utils\Cache;
use Vixedin\System\Modules\Validate as V;

/**
 * @class Controller
 */
class Controller
{
    private ?array $authActions = null;

    private ?array $actionsRequestMapping = null;

    private ?array $adminLevelActions = null;

    private ?array $customerLevelActions = null;

    private $action;
    /**
     * @var app
     */
    protected App $app;

    /**
     * @var mixed
     */
    protected mixed $request;

    /**
     * @var mixed
     */
    protected mixed $response;

    /**
     * @var array
     */
    protected array $args;

    /**
     * @var mixed
     */
    protected mixed $body;

    protected $cache;

    protected ?string $tokenType = null;

    protected ?string $token = null;

    /**
     * @var array
     */
    protected array $disallowedCharsSlug = [
        "'",
        '"',
        ' ',
        '/',
        '\\',
        '>',
        '<',
        '~',
        '{',
        '}',
        '`',
        '¬',
        '?',
        '!',
        '£',
        '$',
        '%',
        '^',
        '&',
        '*',
        '(',
        ')',
        '[',
        ']',
    ];

    /**
     * @var array
     */
    protected array $disallowedChars = [
        "'",
        '"',
        '-',
        '/',
        '\\',
        '>',
        '<',
        '~',
        '{',
        '}',
        '`',
        '¬',
        '?',
        '!',
        '£',
        '$',
        '%',
        '^',
        '&',
        '*',
        '(',
        ')',
        '[',
        ']',
    ];

    /**
     * @var array
     */
    protected array $disallowedCharsUsername = [
        '@',
        "'",
        '"',
        '-',
        '/',
        '\\',
        '>',
        '<',
        '~',
        '{',
        '}',
        '`',
        '¬',
        '?',
        '!',
        '£',
        '$',
        '%',
        '^',
        '&',
        '*',
        '(',
        ')',
        '[',
        ']',
    ];

    /**
     * @var array
     */
    protected array $disallowedCharsEmail = [
        "'",
        '"',
        '-',
        '/',
        '\\',
        '>',
        '<',
        '~',
        '{',
        '}',
        '`',
        '¬',
        '?',
        '!',
        '£',
        '$',
        '%',
        '^',
        '&',
        '*',
        '(',
        ')',
        '[',
        ']',
    ];

    /**
     * @mixed Model
     */
    private Model|null $authClass;

    protected $getParams;

    protected V $validator;

    /**
     * Undocumented function
     *
     * @param [type]  $app
     * @param [type]  $request
     * @param [type]  $response
     * @param [type]  $args
     * @param boolean $defaultBody
     */
    public function __construct(
        $app,
        $request,
        $response,
        $args,
        bool $defaultBody = false,
        string $action,
        array $authActions,
        array $actionsRequestMapping,
        array $adminLevelActions,
        array $customerActions,
        mixed $authClass
    ) {
        $this->authClass = $authClass;
        $this->action = $action;
        $this->customerLevelActions = $customerActions;
        $this->authActions = $authActions;
        $this->actionsRequestMapping = $actionsRequestMapping;
        $this->adminLevelActions = $adminLevelActions;
        $this->app = $app;
        $this->request = $request;
        $this->response = $response;
        $this->args = $args;
        $this->init($defaultBody);
        $this->getParams = $this->request->getQueryParams();
        $this->cache = Helper::getUtilsClass(Cache::class);
        $this->validateActionAccess();
        $this->validateToken();
        $this->validator = new V();
    }

    private function validateActionAccess(): void
    {
        $allowedRequestMethods = array_keys($this->actionsRequestMapping);

        $allowedRequestMethodForCurrentAction = null;

        foreach ($allowedRequestMethods as $method) {
            if (in_array($this->action, $this->actionsRequestMapping[$method])) {
                $allowedRequestMethodForCurrentAction = $method;
            }
        }

        if (is_null($allowedRequestMethodForCurrentAction)) {
            EXP::showException('Current method not mapped', SERVER_ERROR_EXCEPTION);
        }

        if ($this->getRequestMethod() !== $allowedRequestMethodForCurrentAction) {
            if ($this->getRequestMethod() === "OPTIONS") {
                jsonOutput('Request is allowed');
            }
            EXP::showException('request method not allowed', SERVER_ERROR_EXCEPTION);
        }
    }

    public function getCurrentAction(): string
    {
        return $this->action;
    }

    /**
     * Undocumented function
     *
     * @return string|null
     */
    public function getAuthTokenType(): ?string
    {
        return $this->tokenType;
    }

    /**
     * Undocumented function
     *
     * @return string|null
     */
    public function getAutToken(): ?string
    {
        return $this->token;
    }

    /**
     * Undocumented function
     *
     * @param  string $name
     * @return string
     */
    public function getParam(string $name): string
    {
        return $this->getParams[$name] ?? '';
    }

    /**
     * Undocumented function
     *
     * @return void
     */
    protected function getRequestMethod()
    {
        return $this->request->getMethod();
    }

    /**
     * Undocumented function
     *
     * @return void
     */
    public function getRequestBody()
    {
        return $this->body;
    }

    /**
     * @param callable $callBack
     */
    public function addValidationCalls(callable $callBack): void
    {
        $this->validator->addValidation($callBack);
    }

    /**
     * Undocumented function
     *
     * @return void
     */
    public function runValidation(): void
    {
        $this->validator->runFuncValidations();
    }

    /**
     * @param  string $message
     * @param  array  $data
     * @return mixed
     */
    public function output(string $message, array $data = []): mixed
    {
        //$data = $this->clearOutputArray($data);
        $statusCode = 200;
        $final_output = [];
        $final_output['status'] = 'success';
        $final_output['message'] = $message;
        $final_output['data'] = $data;

        return $this->response->withJson($final_output, $statusCode);
    }

    /**
     * @param  string $data
     * @param  string $dataType
     * @return mixed|string
     */
    public function cleanCustomData(string $data, string $dataType = ''): mixed
    {
        return match ($dataType) {
            'email' => str_replace($this->disallowedCharsEmail, '', $data),
            'username' => str_replace($this->disallowedCharsUsername, '', $data),
            'password' => $data,
            default => $this->cleanOutput($data),
        };
    }

    /**
     * @return string
     */
    public function getRefUrl(): string
    {
        return $_SERVER['HTTP_REFERER'] ?? '';
    }

    /**
     * @return string
     */
    public function getFullUrl(): string
    {
        return (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://{$_SERVER['HTTP_HOST']}{$_SERVER['REQUEST_URI']}";
    }

    /**
     * all the time validation for a hardcoded token in the mobile app
     * @throws Exception
     */
    protected function validateToken(): void
    {
        //get token from header first
        $headers = $this->request->getHeaders();
        $authToken = $headers['HTTP_AUTHORIZATION'][0] ?? false;

        if (!$authToken) {
            EXP::showException('missing app auth token', BAD_REQUEST_EXCEPTION);
        }
        $tokenValues = explode(' ', $authToken);
        $tokenType = $tokenValues[0] ?? false;
        $token = $tokenValues[1] ?? false;

        if (!$tokenType || !in_array($tokenType, ['Basic', 'Bearer'])) {
            EXP::showException('invalid token type', UNAUTHORIZED_EXCEPTION);
        }

        $this->tokenType = $tokenType;

        //validate method/action access
        if (in_array($this->getCurrentAction(), $this->authActions) && $tokenType !== 'Bearer') {

            EXP::showException('you dont have correct permission to perform this action', UNAUTHORIZED_EXCEPTION);
        }

        if ($tokenType === "Basic" && $token === APP_TOKEN) {
            //just a basic token for app valls
            return;
        } elseif ($tokenType === 'Bearer') {
            //validate specific user token

            if ($this->authClass === null) {
                $model = new Model();
                $tokenData = $model->getTimedTokenData($token, BEARER_TOKEN_NAME);
                $this->token = $token;
                if (empty($tokenData) || $tokenData === 0) {
                    EXP::showException('invalid or expired token', UNAUTHORIZED_EXCEPTION);
                }
                if ($this->cache->getCache('user_' . $token) === null) {
                    $this->cache->removeCache('user_' . $token);
                    $userData = $model->_execute(
                        "SELECT u.* FROM users u WHERE u.id = :id AND (SELECT COUNT(*) FROM deleted_deactivated d
                        WHERE d.t_name =  'users' AND d.t_id = u.id) = 0",
                        [':id' => $tokenData],
                        1
                    );
                    if (empty($userData)) {
                        EXP::showException('invalid user not found', UNAUTHORIZED_EXCEPTION);
                    }


                    unset($userData['pass']);
                    $this->cache->setCache('user_' . $token, $userData, 30);
                    //make sure action is not admin only
                }
                $user = $this->getActiveUser();
                $accessLevel = intval($user['settings']['user_level'] ?? 0);

                if (in_array($this->action, $this->adminLevelActions) && $accessLevel !== 1) {
                    EXP::showException('Your user does not have correct permissions', UNAUTHORIZED_EXCEPTION);
                }

            } else {
                $tokenData = $this->authClass->getTimedTokenData($token, BEARER_TOKEN_NAME);

                $this->token = $token;

                if (empty($tokenData) || $tokenData === 0) {
                    EXP::showException('invalid or expired token', UNAUTHORIZED_EXCEPTION);
                }

                if ($this->cache->getCache('customer_' . $token) === null) {

                    $this->cache->removeCache('customer_' . $token);
                    $userData = $this->authClass->_execute(
                        "SELECT u.* FROM customers u WHERE u.id = :id AND (SELECT COUNT(*) FROM deleted_deactivated d
                        WHERE d.t_name =  'customers' AND d.t_id = u.id) = 0",
                        [':id' => $tokenData],
                        1
                    );

                    if (empty($userData)) {
                        EXP::showException('invalid customer not found', UNAUTHORIZED_EXCEPTION);
                    }

                    unset($userData['pass']);

                    $this->cache->setCache('customer_' . $token, $userData, 30);
                    //make sure action is not admin only
                }

                $user = $this->getActiveCustomer();

                if (!in_array($this->action, $this->customerLevelActions)) {
                    EXP::showException('Your user/customer does not have correct permissions', UNAUTHORIZED_EXCEPTION);
                }

            }

        } else {
            EXP::showException('wrong token value', BAD_REQUEST_EXCEPTION);
        }
    }

    public function getActiveCustomer(): array
    {
        $customer = $this->cache->getCache('customer_' . $this->token);
        $customer = array_merge($customer, ['settings' => (new CustomerSettings($customer['id']))->getSettings()]);
        return $customer;
    }

    public function getActiveUser(): array
    {
        $user = $this->cache->getCache('user_' . $this->token);
        $user = array_merge($user, ['settings' => (new UserSettings($user['id']))->getSettings()]);
        return $user;
    }

    /**
     * Undocumented function
     *
     * @param  boolean $defaultBody
     * @return void
     */
    protected function init(bool $defaultBody): void
    {
        $this->body = json_decode(file_get_contents('php://input'), true);
        if (is_null($this->body)) {
            $this->body = array_merge($_POST, $_GET);
        }
        $this->body['files'] = $_FILES;
    }

    /**
     * Undocumented function
     *
     * @param string $fileName
     * @param integer $maxSize
     * @param array $typesAllowed
     * @param string $readableName
     * @return void
     */
    public function requiredFile(string $fileName, int $maxSize, array $typesAllowed, string $readableName = ''): void
    {
        $file = $this->body['files'][$fileName] ?? null;
        if (empty($readableName)) {
            $readableName = $fileName;
        }
        if (is_null($file)) {
            EXP::showException('File ' . $readableName . ' is required, but it is missing from your request', BAD_REQUEST_EXCEPTION);
        }

        if ($file['size'] > $maxSize) {
            EXP::showException('file size exceeds the limit of ' . $maxSize . ' bytes', BAD_REQUEST_EXCEPTION);
        }

        if (!in_array($file['type'], $typesAllowed)) {
            EXP::showException('invalid file type', BAD_REQUEST_EXCEPTION);
        }
    }

    /**
     * Undocumented function
     *
     * @param string $fileName
     * @return array
     */
    public function getFileFromRequest(string $fileName): array
    {
        $file = $this->body['files'][$fileName] ?? null;

        if (is_null($file)) {
            EXP::showException('Required file: ' . $fileName . ' is missing', BAD_REQUEST_EXCEPTION);
        }

        return $file;
    }

    /**
     * @param string $fieldName
     * @param $clean
     * @return mixed|string
     */
    public function getField(string $fieldName, $clean = 0): mixed
    {
        $data = $this->body[$fieldName] ?? '';

        return match ($clean) {
            1 => $this->cleanInput($data),
            2 => $this->cleanInput($data, ['@', '.', '-', '_']),
            3 => $this->cleanInput($data, ['#', '!', '?']),
            default => $data,
        };
    }

    /**
     * @param string $string
     * @param array $toIgnoreCharsArray
     * @return array|string|string[]
     */
    public function cleanOutput(string $string, array $toIgnoreCharsArray = []): array | string
    {
        $disAllowed = array_diff($this->disallowedChars, $toIgnoreCharsArray);
        return str_replace($disAllowed, '', $string);
    }

    /**
     * @param string $string
     * @param array $toIgnoreCharsArray
     * @return array|string|string[]
     */
    public function cleanInput(string $string, array $toIgnoreCharsArray = []): array | string
    {
        $disAllowed = array_diff($this->disallowedChars, $toIgnoreCharsArray);
        return str_replace($disAllowed, '', $string);
    }

    /**
     * @param string $string
     * @param array $toIgnoreCharsArray
     * @return string
     */
    public function cleanString(string $string, array $toIgnoreCharsArray = []): string
    {
        if ($string != '') {
            $disAllowed = array_diff($this->disallowedChars, $toIgnoreCharsArray);

            return str_replace($disAllowed, '', $string);
        }

        return '';
    }

    /**
     * @param  int    $min
     * @param  int    $max
     * @param  string $field
     * @param  string $fieldName
     * @throws Exception
     */
    public function validateLength(int $min, int $max, string $field, string $fieldName): void
    {
        $len = strlen($field);

        if ($len < $min || $len > $max) {
            EXP::showException("{$fieldName} must be between {$min} and {$max} characaters", BAD_REQUEST_EXCEPTION);
        }
    }

    /**
     * @param  array $values
     * @param  array $names
     * @throws Exception
     */
    public function validateMatch(array $values, array $names): void
    {
        if (count($values) != 2) {
            EXP::showException('Invalid values for matching', BAD_REQUEST_EXCEPTION);
        }

        if ($values[0] != $values[1]) {
            EXP::showException("({$names[0]}) does not match ({$names[1]})", BAD_REQUEST_EXCEPTION);
        }
    }

    /**
     * @return mixed
     */
    public function getRequestIp(): mixed
    {
        $client = @$_SERVER['HTTP_CLIENT_IP'];
        $forward = @$_SERVER['HTTP_X_FORWARDED_FOR'];
        $remote = $_SERVER['REMOTE_ADDR'];

        if (filter_var($client, FILTER_VALIDATE_IP)) {
            $ip = $client;
        } elseif (filter_var($forward, FILTER_VALIDATE_IP)) {
            $ip = $forward;
        } else {
            $ip = $remote;
        }

        return $ip;
    }

    /**
     * @return string
     * @throws Exception
     */
    public function generateRandomString(): string
    {
        return md5(random_int(0, 1000) . uniqid());
    }

    /**
     * @param  string $email
     * @return bool
     */
    public function isEmail(string $email): bool
    {
        return filter_var($email, FILTER_VALIDATE_EMAIL);
    }

    public function isURL(string $url): bool
    {
        return filter_var($url, FILTER_VALIDATE_URL);
    }

    /**
     * @param array $fieldsData
     * @return      void
     * @throws Exception
     * @description to pass $fieldData in this format
     * [
     *    0 => [fieldname, name shown],
     *    1 => [0 => minLength,  1 => maxLength],
     *    2 => data filtr type in numbers 1,2,3
     *    3 => string filter check i.e email, number, regex
     * ]
     */
    protected function requiredFields(array $fieldsData): void
    {
        foreach ($fieldsData as $fieldData) {
            if (!array_key_exists($fieldData[0][0], $this->body)) {
                EXP::showException('Missing field: ' . $fieldData[0][1], BAD_REQUEST_EXCEPTION);
            }
            //only if something is passed then check for
            if (isset($fieldData[1]) && is_array($fieldData[1])) {
                if (is_array($this->body[$fieldData[0][0]])) {
                    EXP::showException('Field ' . $fieldData[0][1] . ' Cant not be an array', BAD_REQUEST_EXCEPTION);

                }
                if (strlen($this->body[$fieldData[0][0]]) < $fieldData[1][0] || strlen($this->body[$fieldData[0][0]]) > $fieldData[1][1]) {
                    //echo strlen($this->body[$fieldData[0][0]]);die;
                    EXP::showException('Field ' . $fieldData[0][1] . ' must be between ' . $fieldData[1][0] . ' and ' . $fieldData[1][1] . ' characaters long', BAD_REQUEST_EXCEPTION);
                }
            }

            //clean data
            if (isset($fieldData[2])) {
                $this->setField($fieldData[0][0], $fieldData[2]);
            }

            //filters check
            if (isset($fieldData[3])) {
                switch ($fieldData[3]) {
                    case 'email':
                        if (!$this->isEmail($this->body[$fieldData[0][0]])) {
                            //var_dump($this->body[$fieldData[0]]);die;
                            EXP::showException('Invalid Email provided', BAD_REQUEST_EXCEPTION);
                        }
                        break;
                    case 'number':
                        if (!is_numeric($this->body[$fieldData[0][0]])) {
                            EXP::showException('Field ' . $fieldData[0][1] . ' must be a number', BAD_REQUEST_EXCEPTION);
                        }
                        break;
                    case 'url':
                        if (!$this->isURL($this->body[$fieldData[0][0]])) {
                            //var_dump($this->body[$fieldData[0]]);die;
                            EXP::showException('Invalid URL provided', BAD_REQUEST_EXCEPTION);
                        }
                        break;
                    case 'regex':
                        //to implement regex
                        break;
                    case 'date':
                        $this->body[$fieldData[0][0]] = str_replace(['/', '_'], '-', $this->body[$fieldData[0][0]]);
                        if (\DateTime::createFromFormat('Y-m-d', $this->body[$fieldData[0][0]]) === false) {
                            EXP::showException('Invalid date provided: ' . $this->body[$fieldData[0][0]], BAD_REQUEST_EXCEPTION);
                        }
                        $today = new \DateTime('today');
                        $date = new \DateTime($this->body[$fieldData[0][0]]);
                        $checkValueType = function ($checkIndicator) {
                            return match ($checkIndicator) {
                                'year' => 'y',
                                'month' => 'm',
                                'day' => 'd',
                                default => EXP::showException('Invalid date indicator type', BAD_REQUEST_EXCEPTION),
                            };
                        };

                        if (isset($fieldData[4]) && is_array($fieldData[4])) {
                            $valueType = $checkValueType($fieldData[4][2]);
                            if ($fieldData[4][0] == 'before') {
                                if ($date >= $today) {
                                    EXP::showException('Provided date must be before todays date', BAD_REQUEST_EXCEPTION);
                                } else {
                                    $diff = $today->diff($date);
                                    if ($diff->{$valueType} < $fieldData[4][1]) {
                                        EXP::showException('Provided date must be ' . $fieldData[4][1] . ' ' . $fieldData[4][2] . '\'s before todays yet', BAD_REQUEST_EXCEPTION);
                                    }
                                }
                            } elseif ($fieldData[4][0] == 'after') {
                                if ($date <= $today) {
                                    EXP::showException('Provided date must be after todays date', BAD_REQUEST_EXCEPTION);
                                } else {
                                    $diff = $today->diff($date);
                                    if ($diff->{$valueType} > $fieldData[4][1]) {
                                        EXP::showException('Provided date must be ' . $fieldData[4][1] . ' ' . $fieldData[4][2] . '\'s after todays yet', BAD_REQUEST_EXCEPTION);
                                    }
                                }
                            } elseif ($fieldData[4][0] == 'equals') {
                                if ($date != $today) {
                                    EXP::showException('Provided date must be after todays date', BAD_REQUEST_EXCEPTION);
                                }
                            }
                        }
                        break;
                    default:
                        EXP::showException('Invalid validation variable', BAD_REQUEST_EXCEPTION);
                }
            }
        }
    }

    /**
     * @param string $fieldName
     * @param int $clean
     * @return void
     */
    public function setField(string $fieldName, int $clean = 0): void
    {
        $data = $this->body[$fieldName] ?? '';
        $this->body[$fieldName] = match ($clean) {
            1 => $this->cleanInput($data),
            2 => $this->cleanInput($data, ['@', '.', '-', '_']),
            3 => $this->cleanInput($data, ['#', '!', '?']),
            4 => $this->cleanInput($data, ['_']),
            5 => $this->cleanInput($data, ['/', '-']),
            default => $data,
        };
    }

    public function getUserAgent(): array
    {
        return get_browser(null, true);
    }

    /**
     * @return void
     */
    protected function hangCheck(): void
    {
        $i = 0;

        while ($i < 100000000000000000000000000) {
            echo $i;
            $i++;
        }
    }
}
