<?php

//required configs - safe to store here instead of env
const BASE_DIR = __DIR__ . '/../../';
const LOG_PATH = __DIR__ . '/../../logs/';
const DEFAULT_HOME = 'example.com';
const DEFAULT_HOME_APP = 'example.com';
const BASE_URL = 'example.com';
const BASE_API_URL = 'api.example.com';
const BEARER_TOKEN_NAME = 'session_token';
const ENV_PATH = BASE_DIR . 'env' . DIRECTORY_SEPARATOR;
const FETCH_ARR = \PDO::FETCH_ASSOC;
const FETCH_OBJ = \PDO::FETCH_OBJ;


define("MAIN_STATUS", $_ENV['MAIN_STATUS']);
define("EMAIL_DEBUG", $_ENV['EMAIL_DEBUG']);
define("TEST_DB_HOST", $_ENV['TEST_DB_HOST']);
define("TEST_DB_NAME", $_ENV['TEST_DB_NAME']);
define("TEST_DB_PORT", $_ENV['TEST_DB_PORT']);
define("TEST_DB_USER", $_ENV['TEST_DB_USER']);
define("TEST_DB_PASS", $_ENV['TEST_DB_PASS']);
define("TEST_DB_DRIVER", $_ENV['TEST_DB_DRIVER']);
define("CLOUDINARY_API_KEY", $_ENV['CLOUDINARY_API_KEY']);
define("CLOUDINARY_SECRET", $_ENV['CLOUDINARY_SECRET']);
define("CLOUDINARY_NAME", $_ENV['CLOUDINARY_NAME']);



//status codes
define("UNAUTHORIZED_EXCEPTION", 401);
define("NOTFOUND_EXCEPTION", 404);
define("BAD_REQUEST_EXCEPTION", 400);
define("SERVER_ERROR_EXCEPTION", 500);
