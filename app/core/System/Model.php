<?php

namespace Vixedin\System;

use PDO;
use Exception;
use PDOException;
use PDOStatement;
use Vixedin\System\Modules\UserSettings;
use Vixedin\System\Modules\CustomException as EXP;
use Vixedin\System\Modules\CustomerSettings;

/**
 * @class Model
 */
class Model
{
    /**
     *
     */
    public const USER_SESSION_TOKEN_KEY = BEARER_TOKEN_NAME;

    /**
     * @var array
     */
    private array $extraParams = [];

    /**
     * @var PDO
     */
    public PDO $db;

    protected $dbName;

    private int $lastId;
    private int $rowCount;

    /**
     * Model constructor.
     */
    public function __construct()
    {
        //@todo fix issues with db connection after sql import
        try {
            if (CURRENT_STATUS == 'dev') {
                $this->db = new PDO(TEST_DB_DRIVER . ":host=" . TEST_DB_HOST . ';dbname=' . TEST_DB_NAME . ';port=' . TEST_DB_PORT, TEST_DB_USER, TEST_DB_PASS);
            } else {
                $this->db = new PDO(APP_DB_DRIVER . ':host=' . APP_DB_HOST . ';dbname=' . APP_DB_NAME . ';port=' . APP_DB_PORT, APP_DB_USER, APP_DB_PASS);
            }

        } catch (PDOException $e) {
            EXP::showException('Error establshing connection: ', SERVER_ERROR_EXCEPTION);
        }
    }


    /**
     * Undocumented function
     *
     * @param string $email
     * @param string $pass
     * @param integer $storeId
     * @return void
     */
    public function registerUser(string $email, string $pass): array
    {

        $customer = $this->getCustomerByEmail($email);

        if (!empty($customer)) {
            EXP::showException('email already exists', BAD_REQUEST_EXCEPTION);
        }

        $user = $this->getUserByEmail($email, false);

        if (!empty($user)) {
            EXP::showException('email already exists as an admin', BAD_REQUEST_EXCEPTION);

        }

        return [
            'token' => $this->addCustomer($email, $pass)
        ];

    }

    /**
     * Undocumented function
     *
     * @param integer $customerId
     * @param integer $storeId
     * @return array
     */
    public function getCustomerById(int $customerId): array
    {
        return $this->_execute(
            "SELECT i.* FROM customers i WHERE i.id = :sid AND
            (SELECT COUNT(*) FROM deleted_deactivated d WHERE d.t_name = 'customers' AND d.t_id = i.id) = 0",
            [':sid' => $customerId],
            1
        );
    }

    /**
     * Undocumented function
     *
     * @param integer $customerId
     * @return array
     */
    public function getCustomerSettings(int $customerId): array
    {
        return $this->_execute(
            'SELECT * FROM `customer_settings` WHERE customer_id = :ui',
            [':ui' => $customerId],
            2
        );
    }

    /**
     * Undocumented function
     *
     * @param integer $customerId
     * @param mixed $password
     * @return boolean
     */
    public function updateCustomerPassword(int $customerId, mixed $password): bool
    {
        $sql = "UPDATE `customers` SET pass = :pass WHERE id = :id";

        $this->_execute(
            $sql,
            [':pass' => password_hash($password, PASSWORD_DEFAULT), ':id' => $customerId]
        );

        $updated = $this->getRowCount();

        return boolval($updated);
    }

    /**
     * Undocumented function
     *
     * @param string $customerEmail
     * @param integer $storeId
     * @return array
     */
    public function getCustomerByEmail(string $customerEmail): array
    {
        return $this->_execute(
            "SELECT i.* FROM customers i WHERE i.email = :sid AND
            (SELECT COUNT(*) FROM deleted_deactivated d WHERE d.t_name = 'customers' AND d.t_id = i.id) = 0",
            [':sid' => $customerEmail],
            1
        );
    }

    /**
     * Undocumented function
     *
     * @param string $keyName
     * @param mixed $keyValue
     * @param integer $customerId
     * @return void
     */
    public function addCustomerSetting(string $keyName, mixed $keyValue, int $customerId): void
    {

        if (in_array($keyName, CustomerSettings::getUniqueSettings())) {
            //check if exists
            if ($this->countQuery(
                'SELECT COUNT(*) FROM `customer_settings` WHERE key_name = :kn AND key_value = :kv AND customer_id != :uid',
                [':kn' => $keyName, ':kv' => $keyValue, ':uid' => $customerId],
                true
            )
            ) {
                EXP::showException('setting already exists', BAD_REQUEST_EXCEPTION);
            }
        }


        if ($this->countQuery(
            'SELECT COUNT(*) FROM `customer_settings` WHERE key_name = :kn AND customer_id = :ui',
            [':kn' => $keyName, ':ui' => $customerId],
            true
        )
        ) {
            EXP::showException('User setting already exists', BAD_REQUEST_EXCEPTION);
        }

        $this->_execute(
            'INSERT INTO `customer_settings` (key_name, key_value, customer_id) VALUES (:name, :value, :ui)',
            [':name' => $keyName, ':value' => $keyValue, ':ui' => $customerId]
        );
    }

    /**
     * Undocumented function
     *
     * @param string $email
     * @param mixed $password
     * @param integer $storeId
     * @return string
     */
    public function addCustomer(string $email, mixed $password): string
    {
        $sql = "INSERT INTO `customers` (email, pass) VALUES (:email, :pass)";

        $this->_execute(
            $sql,
            [
                ':email' => $email,
                ':pass' => password_hash($password, PASSWORD_DEFAULT),
            ]
        );

        $customerId = $this->getLastId();

        $this->addCustomerSetting('email_validation', 'not_validated', $customerId);
        $this->addCustomerSetting('user_level', 0, $customerId); //@todo decide if we allow multi login for one account later

        return $this->generateAndInsertToken('customer_email_validation', $customerId);
    }

    /**
     * @param string $keyName
     * @param mixed $keyValue
     * @param int $userId
     * @return void
     * @throws Exception
     */
    public function updateUserSettings(string $keyName, mixed $keyValue, int $userId): void
    {


        if (in_array($keyName, UserSettings::getUniqueSettings())) {
            //check if exists
            if ($this->countQuery(
                'SELECT COUNT(*) FROM `user_settings` WHERE key_name = :kn AND key_value = :kv AND user_id != :uid',
                [':kn' => $keyName, ':kv' => $keyValue, ':uid' => $userId],
                true
            )
            ) {
                EXP::showException('setting already exists', BAD_REQUEST_EXCEPTION);
            }
        }


        if (!$this->_execute(
            'UPDATE `users_settings` SET key_value = :kv WHERE user_id = :ui AND key_name = :kn',
            [':kv' => $keyValue, ':ui' => $userId, ':kn' => $keyName]
        )
        ) {
            EXP::showException('Unable to update user settings', BAD_REQUEST_EXCEPTION);
        }
    }

    /**
     * @param string $keyName
     * @param mixed $keyValue
     * @param int $userId
     * @return void
     * @throws Exception
     */
    public function addUserSetting(string $keyName, mixed $keyValue, int $userId): void
    {

        if (in_array($keyName, UserSettings::getUniqueSettings())) {
            //check if exists
            if ($this->countQuery(
                'SELECT COUNT(*) FROM `user_settings` WHERE key_name = :kn AND key_value = :kv AND user_id != :uid',
                [':kn' => $keyName, ':kv' => $keyValue, ':uid' => $userId],
                true
            )
            ) {
                EXP::showException('setting already exists', BAD_REQUEST_EXCEPTION);
            }
        }



        if ($this->countQuery(
            'SELECT COUNT(*) FROM `users_settings` WHERE key_name = :kn AND user_id = :ui',
            [':kn' => $keyName, ':ui' => $userId],
            true
        )
        ) {
            EXP::showException('User setting already exists', BAD_REQUEST_EXCEPTION);
        }

        $this->_execute(
            'INSERT INTO `users_settings` (key_name, key_value, user_id) VALUES (:name, :value, :ui)',
            [':name' => $keyName, ':value' => $keyValue, ':ui' => $userId]
        );
    }

    /**
     * Undocumented function
     *
     * @param integer $userId
     * @param string $keyName
     * @return boolean
     */
    public function userSettingExists(int $userId, string $keyName): bool
    {
        return (boolval(
            $this->countQuery(
                'SELECT COUNT(*) FROM `users_settings` WHERE key_name = :kn AND user_id = :ui',
                [':kn' => $keyName, ':ui' => $userId],
                true
            )
        ));
    }

    public function customerSettingExists(int $userId, string $keyName): bool
    {
        return (boolval(
            $this->countQuery(
                'SELECT COUNT(*) FROM `customer_settings` WHERE key_name = :kn AND customer_id = :ui',
                [':kn' => $keyName, ':ui' => $userId],
                true
            )
        ));
    }


    /**
     * Undocumented function
     *
     * @param string $sessionToken
     * @return array
     */
    public function getUserById(int $userId): array
    {

        if ($userId === 0) {
            EXP::showException('Session expired! user not found', UNAUTHORIZED_EXCEPTION);
        }

        $user = $this->_execute(
            "SELECT u.* FROM `users` u WHERE u.`id` = :id AND
            (SELECT COUNT(*) FROM deleted_deactivated d WHERE d.t_name = 'users' AND d.t_id = u.id) = 0",
            [':id' => $userId],
            1
        );

        if (empty($user)) {
            EXP::showException('User does not exist', UNAUTHORIZED_EXCEPTION);
        }

        return $user;
    }

    /**
     * Undocumented function
     *
     * @param string $sessionToken
     * @return array
     */
    public function getUserBySessionToken(string $sessionToken): array
    {
        $userId = intval($this->getTimedTokenData($sessionToken, self::USER_SESSION_TOKEN_KEY));

        if ($userId === 0) {
            EXP::showException('Session expired! user not found', UNAUTHORIZED_EXCEPTION);
        }

        $user = $this->_execute(
            "SELECT u.* FROM `users` u WHERE u.`id` = :id AND
            (SELECT COUNT(*) FROM deleted_deactivated d WHERE d.t_name = 'users' AND d.t_id = u.id) = 0",
            [':id' => $userId],
            1
        );

        if (empty($user)) {
            EXP::showException('User does not exist', UNAUTHORIZED_EXCEPTION);
        }

        return $user;
    }

    /**
     * Undocumented function
     *
     * @return void
     */
    protected function generateDBTables(): void
    {

        $bluePrintFilePath = APP_DB_MIGRATION_BLUEPRINT_FILE_PATH ?? 0;

        if (is_string($bluePrintFilePath)) {

            if (file_exists($bluePrintFilePath)) {

                $sqlContents = file_get_contents($bluePrintFilePath);

                $this->_query(trim($sqlContents));

                $newFileName = APP_MIGRATIONS . time() . "_old_" . uniqid() . ".sql";

                while (file_exists($newFileName)) {
                    $newFileName = APP_MIGRATIONS . time() . "_old_" . uniqid() . ".sql";
                }

                rename($bluePrintFilePath, $newFileName);
            }
        }

    }

    /**
     * Undocumented function
     *
     * @param string $sessionToken
     * @return array
     */
    public function getUserByEmail(string $email, bool $toCheckError = true): array
    {

        $user = $this->_execute(
            "SELECT u.* FROM `users` u WHERE u.`email` = :email AND
            (SELECT COUNT(*) FROM deleted_deactivated d WHERE d.t_name = 'users' AND d.t_id = u.id) = 0",
            [':email' => $email],
            1
        );

        if ($toCheckError) {
            if (empty($user)) {
                EXP::showException('User does not exist', UNAUTHORIZED_EXCEPTION);
            }
        }

        return $user;
    }

    /**
     * Undocumented function
     *
     * @param integer $userId
     * @return array
     */
    private function getUserSettings(int $userId): array
    {
        return $this->_execute(
            'SELECT * FROM `users_settings` WHERE user_id = :ui',
            [':ui' => $userId],
            2
        );
    }

    /**
     * Undocumented function
     *
     * @param integer $userId
     * @return array
     */
    public function getUserSettingsByUserId(int $userId): array
    {
        return $this->getUserSettings($userId);
    }

    protected function getRowCount(): int
    {
        return $this->rowCount;
    }

    /**
     * Undocumented function
     *
     * @param string $email
     * @param mixed $password
     * @param integer $storeId
     * @return array
     */
    public function getCustomer(string $email, mixed $password): array
    {
        $user = $this->_execute(
            "SELECT u.* FROM `customers` u WHERE u.email = :e AND
            (SELECT COUNT(*) FROM deleted_deactivated d WHERE d.t_name = 'customers' AND d.t_id = u.id) = 0",
            [':e' => $email],
            1
        );
        if (empty($user)) {
            EXP::showException('Please check your details again', UNAUTHORIZED_EXCEPTION);
        }

        if (!password_verify($password, $user['pass'])) {
            EXP::showException('invalid details', UNAUTHORIZED_EXCEPTION);
        }

        $user['settings'] = (new CustomerSettings($user['id'], $this))->getSettings();

        return $user;
    }

    /**
     * Undocumented function
     *
     * @param string $keyName
     * @param mixed $keyValue
     * @param integer $customerId
     * @return void
     */
    public function updateCustomerSettings(string $keyName, mixed $keyValue, int $customerId): void
    {

        if (in_array($keyName, CustomerSettings::getUniqueSettings())) {
            //check if exists
            if ($this->countQuery(
                'SELECT COUNT(*) FROM `customer_settings` WHERE key_name = :kn AND key_value = :kv AND customer_id != :uid',
                [':kn' => $keyName, ':kv' => $keyValue, ':uid' => $customerId],
                true
            )
            ) {
                EXP::showException('setting already exists', BAD_REQUEST_EXCEPTION);
            }
        }



        if (!$this->_execute(
            'UPDATE `customer_settings` SET key_value = :kv WHERE customer_id = :ui AND key_name = :kn',
            [':kv' => $keyValue, ':ui' => $customerId, ':kn' => $keyName]
        )
        ) {
            EXP::showException('Unable to update customer settings', BAD_REQUEST_EXCEPTION);
        }
    }

    /**
     * Undocumented function
     *
     * @param string $email
     * @param mixed $password
     * @return string
     */
    public function addUser(string $email, mixed $password): string
    {
        $customer = $this->getCustomerByEmail($email);

        if (!empty($customer)) {
            EXP::showException('customer email already exists', BAD_REQUEST_EXCEPTION);
        }

        $user = $this->getUserByEmail($email, false);

        if (!empty($user)) {
            EXP::showException('user email already exists', BAD_REQUEST_EXCEPTION);

        }

        $sql = "INSERT INTO `users` (email, pass) VALUES (:email, :pass)";

        $this->_execute(
            $sql,
            [':email' => $email, ':pass' => password_hash($password, PASSWORD_DEFAULT)]
        );

        $userId = $this->getLastId();

        $this->addUserSetting('email_validation', 'not_validated', $userId);
        $this->addUserSetting('user_level', 1, $userId); //@todo decide if we allow multi login for one account later

        return $this->generateAndInsertToken('user_email_validation', $userId);
    }


    public function registerCustomerUser(string $email, string $pass): array
    {
        $customer = $this->getCustomerByEmail($email);

        if (!empty($customer)) {
            EXP::showException('customer email already exists', BAD_REQUEST_EXCEPTION);
        }

        $user = $this->getUserByEmail($email, false);

        if (!empty($user)) {
            EXP::showException('user email already exists', BAD_REQUEST_EXCEPTION);

        }

        return [
            'token' => $this->addCustomer($email, $pass),
        ];

    }



    /**
     * Undocumented function
     *
     * @param integer $userId
     * @param mixed $password
     * @return boolean
     */
    public function updateUserPassword(int $userId, mixed $password): bool
    {
        $sql = "UPDATE `users` SET pass = :pass WHERE id = :id";

        $this->_execute(
            $sql,
            [':pass' => password_hash($password, PASSWORD_DEFAULT), ':id' => $userId]
        );

        $updated = $this->getRowCount();

        return boolval($updated);
    }

    /**
     * Undocumented function
     *
     * @param string $email
     * @param mixed $password
     * @return array
     */
    public function getUser(string $email, mixed $password): array
    {
        $user = $this->_execute(
            "SELECT u.* FROM `users` u WHERE u.email = :e AND
            (SELECT COUNT(*) FROM deleted_deactivated d WHERE d.t_name = 'users' AND d.t_id = u.id) = 0",
            [':e' => $email],
            1
        );
        if (empty($user)) {
            EXP::showException('Please check your details again', UNAUTHORIZED_EXCEPTION);
        }

        if (!password_verify($password, $user['pass'])) {
            EXP::showException('invalid details', UNAUTHORIZED_EXCEPTION);
        }

        $user['settings'] = (new UserSettings($user['id']))->getSettings();

        return $user;
    }

    /**
     * @return int
     * @throws Exception
     */
    public function getLastId(): int
    {
        if (is_null($this->lastId) || $this->lastId === 0) {
            EXP::showException('Unable to obtain id', SERVER_ERROR_EXCEPTION);
        }

        return $this->lastId;
    }

    /**
     * @param  string $stringToCheck
     * @return int
     */
    public function checkLength(string $stringToCheck): int
    {
        return strlen($stringToCheck);
    }

    /*
     * @param string $dbName
     * @param array $filters
     */
    /**
     * @throws Exception
     */
    public function checkUserByFilters(string $dbName, array $filters = [])
    {
        if (empty($filters)) {
            EXP::showException('Filter may not be empty', BAD_REQUEST_EXCEPTION);
        }
        $sql = "SELECT * FROM {$dbName} WHERE ";
        foreach ($filters as $filter => $value) {
            $sql .= "{$filter} = '{$value}' AND";
        }

        $sql = chop($sql, ' AND');

        $query = $this->_query($sql);

        return $query->rowCount() == 0 ? null : $query->fetchObject();
    }

    /**
     * @param  $sql
     * @return PDOStatement
     * @throws Exception
     */
    public function _query($sql): PDOStatement
    {
        try {
            $query = $this->db->query($sql);

            if (!$query) {
                EXP::showException('Unable to execute some mdo ' /*.$sql*/, SERVER_ERROR_EXCEPTION);
            }
            if (str_contains(strtolower($sql), 'insert into')) {
                $this->lastId = $this->db->lastInsertId();
            }

            return $query;

        } catch (PDOException $e) {
            EXP::showException($e->getMessage(), SERVER_ERROR_EXCEPTION);
            // EXP::showException('Error with lookup');
        }
    }

    /**
     * @param $sql
     * @param $data
     * @param bool $toUseData
     * @return int
     * @throws Exception
     */
    public function countQuery($sql, $data = [], bool $toUseData = false): int
    {
        if (!$toUseData) {
            $q = $this->_query($sql);
            return $q->fetchColumn();
        }

        $q = $this->db->prepare($sql);
        $q->execute($data);
        return (int) $q->fetchColumn();
    }

    /**
     * @param  array  $data
     * @param  string $table
     * @param  bool   $disableTimeInsertion
     * @return int
     * @throws Exception
     */
    public function doInsert(array $data, string $table, bool $disableTimeInsertion = false): int
    {
        $data['action'] = 'insert';
        $data['table'] = $table;
        if (!$disableTimeInsertion) {
            $data['data']['updated_at'] = $this->getCurrentDataTime();
            $data['data']['created_at'] = $this->getCurrentDataTime();
        }
        $sql = $this->generateSql($data);
        $this->_query($sql);

        return $this->getLastId();
    }

    /**
     * @return string
     */
    public function getRandomToken(): string
    {
        return md5(uniqid());
    }

    /**
     * @param string $tableName
     * @param int $tableId
     * @return bool
     * @throws Exception
     */
    public function addToDeleted(string $tableName, int $tableId): bool
    {
        $q = $this->_execute('INSERT INTO deleted_deactivated (t_name, t_id) VALUES (:n, :i)', [':n' => $tableName, ':i' => $tableId]);
        return (int) $this->getLastId() !== 0;
    }

    /**
     * @param string $name
     * @param int $id
     * @return void
     * @throws Exception
     */
    public function checkDeleted(string $name, int $id): void
    {
        $q = $this->db->query("SELECT COUNT(*) FROM deleted_deactivated WHERE t_name = '{$name}' AND t_id = {$id}");
        //echo $q->fetchColumn();die;
        if ((int) $q->fetchColumn() !== 0) {
            EXP::showException('this item has been deleted or disabled', BAD_REQUEST_EXCEPTION);
        }
    }

    public function isDeletedOrDisabled(string $name, int $id): bool
    {
        $q = $this->db->query("SELECT COUNT(*) FROM deleted_deactivated WHERE t_name = '{$name}' AND t_id = {$id}");
        //echo $q->fetchColumn();die;
        return intval($q->fetchColumn()) !== 0;
    }

    /**
     * @param string $sql
     * @param array $data
     * @param int $level
     * @param int $userId
     * @param string $table
     * @return array|bool|mixed
     * @throws Exception
     */
    public function _execute(string $sql, array $data, int $level = 0, int $userId = 0, string $table = 'none_default'): mixed
    {
        //$this->recordChanges($sql, $data, $userId, $table);//record every single query
        try {
            $preparedStm = $this->db->prepare($sql);

            if (!$stm = $preparedStm->execute($data)) {
                EXP::showException('failed with mdo ', SERVER_ERROR_EXCEPTION); // . print_r([$sql, $data], true));
            }

            if (str_contains(strtolower($sql), 'insert into')) {
                $this->lastId = $this->db->lastInsertId();
            }

            if (strtolower(substr(trim($sql), 0, 6)) === 'update') {
                $this->rowCount = intval($preparedStm?->rowCount());
            }

        } catch (PDOException $e) {
            EXP::showException($e->getMessage(), SERVER_ERROR_EXCEPTION);
        }
        if ($level == 1 || $level == 2) {
            $res = ($level == 1) ? $preparedStm->fetch(FETCH_ARR) : $preparedStm->fetchAll(FETCH_ARR);
            if (!is_array($res)) {
                return [];
            }
            return $res;
        }
        return $level == 3 ? $preparedStm->fetchColumn() : $stm;
    }

    /**
     * Undocumented function
     *
     * @param  string  $qRan
     * @param  array   $data
     * @param  integer $userId
     * @param  string  $table
     * @return void
     */
    private function recordChanges(string $qRan, array $data = [], int $userId = 0, string $table): void
    {
        $dataString = implode(',', $data);
        $sql = 'INSERT INTO changes (q_ran, user_id, table_name, data_set) VALUES (:q, :u, :t, :d)';
        $q = $this->db->prepare($sql);
        $q->execute(
            [
                ':q' => $qRan,
                ':u' => $userId,
                ':t' => $table,
                ':d' => $dataString,
            ]
        );
    }

    /**
     * @return string
     */
    public function getCurrentDataTime(): string
    {
        return date('Y-m-d H:i:s');
    }

    /**
     * @param  array  $data
     * @param  string $table
     * @return PDOStatement
     * @throws Exception
     */
    public function doUpdate(array $data, string $table): PDOStatement
    {
        $data['action'] = 'update';
        $data['table'] = $table;
        $data['data']['updated_at'] = $this->getCurrentDataTime();
        $sql = $this->generateSql($data);

        return $this->_query($sql);
    }

    /**
     * @param  array  $data
     * @param  string $table
     * @return PDOStatement
     * @throws Exception
     */
    public function doDelete(array $data, string $table): PDOStatement
    {
        $data['action'] = 'delete';
        $data['table'] = $table;
        $sql = $this->generateSql($data);

        return $this->_query($sql);
    }

    /**
     * @param  array  $data
     * @param  string $table
     * @return PDOStatement
     * @throws Exception
     */
    public function doSelect(array $data, string $table): PDOStatement
    {
        $data['action'] = 'select';
        $data['table'] = $table;
        $sql = $this->generateSql($data);

        return $this->_query($sql);
    }

    /**
     * @param  $data
     * @return string
     * @throws Exception
     */
    public function generateSql($data): string
    {
        $action = $data['action'];
        $table = $data['table'];
        $sql = '';
        unset($data['action']);
        unset($data['table']);

        switch ($action) {
            case 'select':
                $where = $data['where'];
                unset($data['where']);
                $sql = 'SELECT * FROM ' . $table . ' WHERE ';
                foreach ($where as $key => $value) {
                    $value = $this->db->quote($value);
                    $sql .= "{$key} = {$value} AND ";
                }
                $sql = chop($sql, ' AND');
                break;
            case 'update':
                $where = $data['where'];
                $update = $data['data'];
                unset($data['data']);
                unset($data['where']);
                $sql = 'UPDATE ' . $table . ' SET ';
                foreach ($update as $key => $value) {
                    $value = $this->db->quote($value);
                    $sql .= "{$key} = {$value} ,";
                }
                $sql = chop($sql, ' ,') . ' WHERE ';
                foreach ($where as $key => $value) {
                    $value = $this->db->quote($value);
                    $sql .= "{$key} = {$value} AND ";
                }
                $sql = chop($sql, ' AND ');
                break;
            case 'delete':
                $where = $data['where'];
                unset($data['where']);
                $sql = 'DELETE FROM ' . $table . ' WHERE ';
                foreach ($where as $key => $value) {
                    $value = $this->db->quote($value);
                    $sql .= "{$key} = {$value} AND ";
                }
                $sql = chop($sql, ' AND');
                break;
            case 'insert':
                $insertData = $data['data'];
                unset($data['data']);
                $sql = 'INSERT INTO ' . $table . ' (';
                $values = ' VALUES (';
                foreach ($insertData as $key => $value) {
                    $sql .= "{$key} ,";
                    $value = $this->db->quote($value);
                    $values .= "{$value} ,";
                }
                $sql = chop($sql, ' ,') . ')' . chop($values, ' ,') . ')';
                break;
            default:
                EXP::showException('Unable to break action', SERVER_ERROR_EXCEPTION);
        }

        return $sql;
    }

    /**
     * @param  string $type
     * @param  string $typeId
     * @return string
     * @throws Exception
     */
    public function generateAndInsertToken(string $type, string $typeId): string
    {
        $randomToken = md5(random_int(0, 10000) . uniqid());

        while ($this->countQuery("SELECT COUNT(*) FROM tokens WHERE for_type = '{$type}' AND token = '{$randomToken}' AND for_value = '{$typeId}'", [], false)) {
            $randomToken = md5(random_int(0, 10000) . uniqid());
        }

        $sql = 'INSERT INTO tokens (token, for_type, for_value) VALUES (:t, :ty, :ti)';
        $this->_execute(
            $sql,
            [
                ':t' => $randomToken,
                ':ty' => $type,
                ':ti' => $typeId,
            ]
        );

        return $randomToken;
    }

    /**
     * @param  $token
     * @return array
     * @throws Exception
     */
    public function getTokenData($token): array
    {
        return $this->_execute('SELECT * FROM tokens WHERE token = :token', [':token' => $token], 1);
    }

    /**
     * @param string $token
     * @param string $for
     * @return int|mixed
     * @throws Exception
     */
    public function getTimedTokenData(string $token, string $for): mixed
    {
        //2 hours time
        $tokenData = $this->_execute(
            'SELECT * FROM tokens WHERE used = 0 AND for_type = :tf AND token = :t AND TIMESTAMPDIFF(HOUR, created_at, NOW()) < 2',
            [':tf' => $for, ':t' => $token],
            1
        );
        //echo '<pre>';var_dump($tokenData);die;
        return $tokenData['for_value'] ?? 0;
    }

    /**
     * @param  string $token
     * @throws Exception
     */
    public function setTokenUsed(string $token): void
    {
        $this->_execute('UPDATE tokens SET used = 1 WHERE token = :token', [':token' => $token]);
    }

    /**
     * @param $time1
     * @param $time2
     * @param $diffVar
     * @return int
     * @throws Exception
     */
    private function getTimeStampDif($time1, $time2, $diffVar): int
    {
        $t1 = new \DateTime($time1);
        $diff = $t1->diff(new \DateTime($time2));
        return $diff->{$diffVar};
    }

    /**
     * @param  string $token
     * @throws Exception
     */
    public function validateSessionToken(string $token): void
    {
        $token = $this->getTokenData($token);
        if (empty($token) || $token['used'] == '1' || $this->getTimeStampDif($token['created_at'], 'now', 'h') > 2) {
            EXP::showException('IOET', BAD_REQUEST_EXCEPTION); //invalid or expired token
        }
    }


    public function addCustomerMedia(array $itemDetails, $customerId): int
    {
        $sql = "INSERT INTO `customer_media` (customer_id, media_details) VALUES (:ci, :md)";

        $this->_execute(
            $sql,
            [':ci' => $customerId, ':md' => json_encode($itemDetails)]
        );

        return $this->getLastId();
    }


    public function getUserMediaByIdAndUserId(int $mediaId, int $userId): array
    {
        return $this->_execute(
            "SELECT * FROM user_media WHERE id = :id AND user_id = :ci  AND " .
            "(SELECT COUNT(*) FROM deleted_deactivated d WHERE d.t_name = 'user_media' AND d.t_id = user_media.id) = 0",
            [':id' => $mediaId, ':ci' => $userId],
            1
        );
    }

    public function getCustomerMediaByIdAndCustomerId(int $mediaId, int $customerId): array
    {
        return $this->_execute(
            "SELECT * FROM customer_media WHERE id = :id AND customer_id = :ci AND " .
            "(SELECT COUNT(*) FROM deleted_deactivated d WHERE d.t_name = 'customer_media' AND d.t_id = customer_media.id) = 0",
            [':id' => $mediaId, ':ci' => $customerId],
            1
        );
    }

    public function getAllUserMedia(int $userId): array
    {
        return $this->_execute(
            "SELECT * FROM user_media WHERE user_id = :ci AND " .
            "(SELECT COUNT(*) FROM deleted_deactivated d WHERE d.t_name = 'user_media' AND d.t_id = user_media.id) = 0",
            [':ci' => $userId],
            2
        );
    }


    public function getAllCustomerMedia(int $customerId): array
    {
        return $this->_execute(
            "SELECT * FROM customer_media WHERE customer_id = :ci  AND " .
            "(SELECT COUNT(*) FROM deleted_deactivated d WHERE d.t_name = 'customer_media' AND d.t_id = customer_media.id) = 0",
            [':ci' => $customerId],
            2
        );
    }

    public function addUserMedia(array $itemDetails, $userId): int
    {
        $sql = "INSERT INTO `user_media` (user_id, media_details) VALUES (:ci, :md)";

        $this->_execute(
            $sql,
            [':ci' => $userId, ':md' => json_encode($itemDetails)]
        );

        $this->getLastId();

    }

}
