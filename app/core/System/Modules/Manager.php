<?php

namespace Vixedin\System\Modules;

use Vixedin\System\Modules\CustomException as EXP;
use Vixedin\System\Model;
use Vixedin\System\Modules\Helper;
use Vixedin\System\Modules\Mailer;
use Vixedin\System\Modules\Messages;
use Vixedin\System\Modules\Utils\Cache;
use Vixedin\System\Modules\Uploader;

/**
 * @class Access
 */
class Manager
{
    public function __construct(
        protected Model $model,
        protected mixed $conHandler,
        protected Mailer $mailer
    ) {

    }


    /**
     * Undocumented function
     *
     * @param array $user
     * @return void
     */
    protected function initSession(array $user): void
    {
        $sessionToken = $this->model->generateAndInsertToken(Model::USER_SESSION_TOKEN_KEY, $user['id']);
        $cache = Helper::getUtilsClass(Cache::class);
        $cache->setCache($sessionToken, $user);
        jsonOutput('Logged in', ['sessionToken' => $sessionToken]);
    }

    /**
     * @param string $email
     * @param mixed $password
     * @return void
     * @throws Exception
     */
    public function login(string $email, mixed $password): void
    {
        $user = $this->model->getUser($email, $password);
        unset($user['pass']);
        $settings = $user['settings'];

        if (!isset($settings['email_validation']) || $settings['email_validation'] !== 'validated') {
            EXP::showException(
                'You must validate your email first! Please click (Forget Password) and submit your email to receive your validation email',
                BAD_REQUEST_EXCEPTION
            );
        }

        $this->initSession($user);
    }

    /**
     * Undocumented function
     *
     * @param string $token
     * @return void
     */
    public function activateAccount(string $token): void
    {
        $userId = intval($this->model->getTimedTokenData($token, 'user_email_validation'));

        if ($userId <= 0) {
            EXP::showException('invalid token', BAD_REQUEST_EXCEPTION);
        }

        $user = $this->model->getUserById($userId);

        $userSettings = (new UserSettings($user['id'], $this->model))->getSettings();

        if ($userSettings['email_validation'] === 'validated') {
            EXP::showException('User already validated', BAD_REQUEST_EXCEPTION);
        }

        //set to validate
        $this->model->updateUserSettings('email_validation', 'validated', $user['id']);

        //used token
        $this->model->setTokenUsed($token);

        jsonOutput('Your account has been activated');
    }


    /**
     * Undocumented function
     *
     * @param string $userEmail
     * @return void
     */
    public function forgotPassword(string $userEmail): void
    {
        $user = $this->model->getUserByEmail($userEmail);
        $email = $user['email'];

        if (empty($user)) {
            EXP::showException('invalid user', BAD_REQUEST_EXCEPTION);
        }

        $userSettings = (new UserSettings($user['id'], $this->model))->getSettings();

        if ($userSettings['email_validation'] !== 'validated') {
            $validationToken = $this->model->generateAndInsertToken('user_email_validation', $user['id']);

            $activationUrl = ACTIVATION_URL . $validationToken;
            try {
                ///send validation email
                if (!boolval($this->mailer->send([
                    'body' => Messages::welcomeMessage(
                        APP_NAME,
                        [$activationUrl]
                    ),
                    'subject' => APP_NAME . 'account activation',
                    'to' => $email,
                    'toName' => APP_NAME . ' support',

                ]))) {
                    EXP::showException(
                        'Failed to send a new activation email,
                        please try again and we will attempt to send you a new validation email',
                        SERVER_ERROR_EXCEPTION
                    );

                }
            } catch (Throwable $th) {
                //throw $th;
                EXP::showException('Error with mail server, please dont try again', SERVER_ERROR_EXCEPTION);
            }
            jsonOutput('a new activation email has been sent to you');
        } else {
            //send forgot password email
            $resetToken = $this->model->generateAndInsertToken('user_password_reset', $user['id']);
            $resetUrl = RESET_URL . $resetToken;

            try {
                ///send validation email
                if (!boolval($this->mailer->send([
                    'body' => Messages::resetMessage(
                        APP_NAME,
                        [$resetUrl]
                    ),
                    'subject' => APP_NAME . ' reset your password',
                    'to' => $email,
                    'toName' => APP_NAME . ' support',

                ]))) {
                    EXP::showException(
                        'Failed to send password reset email, please contact us or try again later',
                        SERVER_ERROR_EXCEPTION
                    );

                }
            } catch (Throwable $th) {
                //throw $th;
                EXP::showException('Error with mail server, please contact us first and dont try again', SERVER_ERROR_EXCEPTION);
            }

        }

        jsonOutput('We have sent the password reset instruction to your email');
    }


    /**
     * @param string $email
     * @param mixed $passwordP
     * @return void
     * @throws Exception
     */
    public function signUp(string $email, mixed $password): void
    {
        $validationToken = $this->model->addUser($email, $password);
        $activationUrl = ACTIVATION_URL . $validationToken;
        try {
            ///send validation email
            if (!boolval($this->mailer->send([
                'body' => Messages::welcomeMessage(
                    APP_NAME,
                    [$activationUrl]
                ),
                'subject' => APP_NAME . ' account activation',
                'to' => $email,
                'toName' => APP_NAME . ' support',

            ]))) {
                EXP::showException(
                    'Sign up completed but failed to send activation email,
                    please try to login and we will attempt to send you a new validation email',
                    SERVER_ERROR_EXCEPTION
                );

            }
        } catch (Throwable $th) {
            //throw $th;
            EXP::showException('Error with mail server, please dont try again', SERVER_ERROR_EXCEPTION);
        }

        jsonOutput('Sign up completed, please click link on your account to activate');

    }


    /**
     * Undocumented function
     *
     * @param string $settingKey
     * @param mixed $settingsValue
     * @return void
     */
    public function addUserSetting(string $settingKey, mixed $settingsValue): void
    {
        $user = $this->conHandler->getActiveUser();

        if (!array_key_exists($settingKey, UserSettings::getSettingKeys())) {
            EXP::showException('invalid setting', BAD_REQUEST_EXCEPTION);
        }

        if ($this->model->userSettingExists($user['id'], $settingKey)) {
            //update
            $this->model->updateUserSettings($settingKey, $settingsValue, $user['id']);
            $actionType = 'updated';
        } else {
            //insert
            $this->model->addUserSetting($settingKey, $settingsValue, $user['id']);
            $actionType = 'added';
        }

        jsonOutput('User setting ' . $actionType);

    }

    /**
     * Undocumented function
     *
     * @param string $settingKey
     * @param mixed $settingsValue
     * @return void
     */
    public function addCustomerSetting(string $settingKey, mixed $settingsValue): void
    {
        $user = $this->conHandler->getActiveCustomer();

        if (!array_key_exists($settingKey, CustomerSettings::getSettingKeys())) {
            EXP::showException('invalid setting', BAD_REQUEST_EXCEPTION);
        }

        if ($this->model->customerSettingExists($user['id'], $settingKey)) {
            //update
            $this->model->updateCustomerSettings($settingKey, $settingsValue, $user['id']);
            $actionType = 'updated';
        } else {
            //insert
            $this->model->addCustomerSetting($settingKey, $settingsValue, $user['id']);
            $actionType = 'added';
        }

        jsonOutput('User setting ' . $actionType);

    }







    /**
     * Undocumented function
     *
     * @param string $password
     * @param string $token
     * @return void
     */
    public function resetPassword(string $password, string $token): void
    {
        $userId = intval($this->model->getTimedTokenData($token, 'user_password_reset'));

        if ($userId <= 0) {
            EXP::showException('invalid token', BAD_REQUEST_EXCEPTION);
        }

        $user = $this->model->getUserById($userId);

        $userSettings = (new UserSettings($user['id'], $this->model))->getSettings();

        if ($userSettings['email_validation'] !== 'validated') {
            EXP::showException(
                'You must validate your email first! please go to forgot password page to request a new validation email',
                BAD_REQUEST_EXCEPTION
            );
        }

        if (!$this->model->updateUserPassword($user['id'], $password)) {
            EXP::showException('unable to change password, please try again later', BAD_REQUEST_EXCEPTION);
        }

        //used token
        $this->model->setTokenUsed($token);

        jsonOutput('Password updated');
    }


    /**
     * Undocumented function
     *
     * @param string $email
     * @param string $pass
     * @return void
     */
    public function signUpCustomer(string $email, string $pass): void
    {
        $regDataArray = $this->model->registerCustomerUser($email, $pass);
        $token = $regDataArray['token'];

        $activationUrl = CUSTOMER_ACTIVATION_URL . $token;
        try {
            ///send validation email
            if (!boolval($this->mailer->send([
                'body' => Messages::customerWelcomeMessage(
                    APP_NAME,
                    [$activationUrl, APP_NAME]
                ),
                'subject' => APP_NAME . ' account activation',
                'to' => $email,
                'toName' => APP_NAME,

            ]))) {
                EXP::showException(
                    'Sign up completed but failed to send activation email,
                    please try to login and we will attempt to send you a new activation email',
                    SERVER_ERROR_EXCEPTION
                );

            }
        } catch (Throwable $th) {
            //throw $th;
            EXP::showException('Error with mail server, please contact us first before trying again', SERVER_ERROR_EXCEPTION);
        }

        jsonOutput('Registration completed, we have sent you a link to activate your account');
    }

    /**
     * Undocumented function
     *
     * @param string $email
     * @param string $pass
     * @param integer $storeId
     * @return void
     */
    public function signInCustomer(string $email, string $pass): void
    {
        $user = $this->model->getCustomer($email, $pass);
        unset($user['pass']);
        $settings = $user['settings'];

        if (!isset($settings['email_validation']) || $settings['email_validation'] !== 'validated') {
            EXP::showException(
                'You must validate your email first! Please click (Forget Password) and submit your email to receive your validation email',
                BAD_REQUEST_EXCEPTION
            );
        }

        $this->initSession($user);

    }


    /**
     * Undocumented function
     *
     * @param string $email
     * @param integer $storeId
     * @return void
     */
    public function recoverGeneralCustomerPassword(string $email): void
    {

        $customer = $this->model->getCustomerByEmail($email);

        if (empty($customer)) {
            EXP::showException('invalid email not found', BAD_REQUEST_EXCEPTION);
        }

        $customerSettings = (new CustomerSettings($customer['id'], $this->model))->getSettings();

        if ($customerSettings['email_validation'] !== 'validated') {
            $validationToken = $this->model->generateAndInsertToken('customer_email_validation', $customer['id']);

            $activationUrl = CUSTOMER_ACTIVATION_URL . $validationToken;
            try {
                ///send validation email
                if (!boolval($this->mailer->send([
                    'body' => Messages::customerWelcomeMessage(
                        APP_NAME,
                        [$activationUrl, APP_NAME]
                    ),
                    'subject' => APP_NAME . ' account activation',
                    'to' => $email,
                    'toName' => APP_NAME,

                ]))) {
                    EXP::showException(
                        'Failed to send a new activation email,
                        please try again and we will attempt to send you a new validation email',
                        SERVER_ERROR_EXCEPTION
                    );

                }
            } catch (Throwable $th) {
                //throw $th;
                EXP::showException('Error with mail server, please contact us first before trying again', SERVER_ERROR_EXCEPTION);
            }
            jsonOutput('a new activation email has been sent to you');
        } else {
            //send forgot password email
            $resetToken = $this->model->generateAndInsertToken('customer_password_reset', $customer['id']);
            $resetUrl = CUSTOMER_RESET_URL. $resetToken;

            try {

                ///send validation email
                if (!boolval($this->mailer->send([
                    'body' => Messages::customerResetMessage(
                        APP_NAME,
                        [$resetUrl, APP_NAME]
                    ),
                    'subject' => APP_NAME . ' - reset your password',
                    'to' => $email,
                    'toName' => APP_NAME,

                ]))) {
                    EXP::showException(
                        'Failed to send password reset email, please contact us or try again later',
                        SERVER_ERROR_EXCEPTION
                    );

                }
            } catch (Throwable $th) {
                //throw $th;
                EXP::showException('Error with mail server, please contact us first and dont try again', SERVER_ERROR_EXCEPTION);
            }
        }
        jsonOutput('We have sent the password reset instruction to your email');
    }


    /**
     * Undocumented function
     *
     * @param integer $storeId
     * @param string $token
     * @param string $password
     * @return void
     */
    public function resetGeneralCustomerPassword(string $token, string $password): void
    {
        $customerId = intval($this->model->getTimedTokenData($token, 'customer_password_reset'));

        if ($customerId <= 0) {
            EXP::showException('invalid token', BAD_REQUEST_EXCEPTION);
        }



        $customer = $this->model->getCustomerById($customerId);

        if (empty($customer)) {
            EXP::showException('invalid customer', BAD_REQUEST_EXCEPTION);
        }

        $customerSettings = (new CustomerSettings($customer['id'], $this->model))->getSettings();

        if ($customerSettings['email_validation'] !== 'validated') {
            EXP::showException(
                'You must validate your email first! please go to forgot password page to request a new validation email',
                BAD_REQUEST_EXCEPTION
            );
        }

        if (!$this->model->updateCustomerPassword($customer['id'], $password)) {
            EXP::showException(
                'unable to change password, please try again later and make sure new password is not the same as your old password',
                SERVER_ERROR_EXCEPTION
            );
        }

        //used token
        $this->model->setTokenUsed($token);

        jsonOutput('Password updated, you can now login with your new password');

    }


    public function getCustomer(): void
    {
        $customer = $this->conHandler->getActiveCustomer();
        jsonOutput('customer details', ['customer' => $customer]);
    }

    public function getUser(): void
    {
        $customer = $this->conHandler->getActiveUser();
        jsonOutput('user details', ['user' => $customer]);
    }


    /**
     * Undocumented function
     *
     * @param string $token
     * @param integer $storeId
     * @return void
     */
    public function activateGeneralCustomerAccount(string $token): void
    {
        $customerId = intval($this->model->getTimedTokenData($token, 'customer_email_validation'));

        if ($customerId <= 0) {
            EXP::showException('invalid token', BAD_REQUEST_EXCEPTION);
        }



        $customer = $this->model->getCustomerById($customerId);

        $customerSettings = (new CustomerSettings($customer['id'], $this->model))->getSettings();

        if ($customerSettings['email_validation'] === 'validated') {
            EXP::showException('Customer already validated', BAD_REQUEST_EXCEPTION);
        }

        //set to validate
        $this->model->updateCustomerSettings('email_validation', 'validated', $customer['id']);

        //used token
        $this->model->setTokenUsed($token);

        jsonOutput('Your account has been activated');
    }


    public function closeUserAccount(): void
    {
        $user = $this->conHandler->getActiveUser();

        $userFromDB = $this->model->getUserById($user['id']);

        if (empty($userFromDB)) {
            EXP::showException('User not found', BAD_REQUEST_EXCEPTION);
        }

        if (!$this->model->addToDeleted('users', $userFromDB['id'])) {
            EXP::showException('Unable to delete user, please contact us', SERVER_ERROR_EXCEPTION);
        }

        jsonOutput('user deleted', []);
    }

    public function closeCustomerAccount(): void
    {
        $user = $this->conHandler->getActiveCustomer();

        $customerFromDb = $this->model->getCustomerById($user['id']);

        if (empty($customerFromDb)) {
            EXP::showException('Customer not found', BAD_REQUEST_EXCEPTION);
        }

        if (!$this->model->addToDeleted('customers', $user['id'])) {
            EXP::showException('Unable to delete customer, please contact us', SERVER_ERROR_EXCEPTION);
        }

        jsonOutput('user deleted', []);
    }


    public function uploadCustomerMedia(string $fileName): void
    {
        $user = $this->conHandler->getActiveCustomer();
        $uploader = new Uploader('5M', Uploader::DEFAULT_ALLOWED_FILE_TYPES, APP_STORAGE, $fileName);

        if (!$uploader->upload()) {
            EXP::showException(
                'Unable to upload item set image due to : ' . ($uploader->getUploadErrors()[0] ?? 'unkown error'),
                BAD_REQUEST_EXCEPTION
            );
        }

        $fileData = $uploader->getFileData();

        $fileId = $this->model->addCustomerMedia($fileData, $user['id']);
        if (!boolval($fileId)) {
            EXP::showException('unable to register media', SERVER_ERROR_EXCEPTION);
        }
        $fileData['fileId'] = $fileId;


        unset($fileData['path']);
        jsonOutput('file added', ['file' => $fileData]);
    }


    public function uploadUserMedia(string $fileName): void
    {
        $user = $this->conHandler->getActiveUser();
        $uploader = new Uploader('5M', Uploader::DEFAULT_ALLOWED_FILE_TYPES, APP_STORAGE, $fileName);

        if (!$uploader->upload()) {
            EXP::showException(
                'Unable to upload item set image due to : ' . ($uploader->getUploadErrors()[0] ?? 'unkown error'),
                BAD_REQUEST_EXCEPTION
            );
        }

        $fileData = $uploader->getFileData();

        $fileId = $this->model->addUserMedia($fileData, $user['id']);
        if (!boolval($fileId)) {
            EXP::showException('unable to register media', SERVER_ERROR_EXCEPTION);
        }
        $fileData['fileId'] = $fileId;

        unset($fileData['path']);
        jsonOutput('file added', ['file' => $fileData]);

    }



}
