<?php

function debugPrint(mixed $data)
{
    ini_set('xdebug.var_display_max_depth', 99);
    echo '<pre>';
    var_dump($data);
    echo '</pre>';
    exit();
}

/**
 * @param string $message
 * @param array  $data
 */
function jsonOutput(string $message, array $data = []): void
{
    //$data = $this->clearOutputArray($data);
    $statusCode = 200;
    $final_output = [];
    $final_output['status'] = 'success';
    $final_output['message'] = $message;
    $final_output['data'] = $data;

    //return $this->response->withJson($final_output, $statusCode);

    header('Content-Type: application/json');
    http_response_code($statusCode);
    array_walk_recursive(
        $final_output,
        function (&$item) {
            if ($item !== true && $item !== false && !is_int($item)) {
                $item = utf8_encode($item);
            }
        }
    );
    echo json_encode($final_output);
    exit(0);
}

/**
 * @param string $message
 * @param array  $data
 */
function jsonOutputError(string $message, array $data = []): void
{
    //$data = $this->clearOutputArray($data);
    $statusCode = 200;
    $final_output = [];
    $final_output['status'] = 'error';
    $final_output['message'] = $message;
    $final_output['data'] = $data;

    //return $this->response->withJson($final_output, $statusCode);
    header('Content-Type: application/json');
    http_response_code($statusCode);
    echo json_encode($final_output);
    exit(0);
}

/**
 * @param string $file
 * @param string $value
 */
function writeToFile(string $file, string $value): void
{
    $data = $value . PHP_EOL;
    $fp = fopen($file, 'a');
    fwrite($fp, $data);
    fclose($fp);
}

/**
 * @param  string $file
 * @return string
 */
function getListFromFile(string $file): string
{
    $list = '(';
    $fh = fopen($file, 'r');
    while ($line = fgets($fh)) {
        $list .= $line . ',';
    }
    fclose($fh);
    $list = chop($list, ',') . ')';
    if ($list == '()') {
        $list = '(0)';
    }
    return $list;
}

/**
 * @param string $url
 * @param string $serverFileLocation
 * @param string $dest
 * @param bool   $errorIfNotFound
 */
function getFileFromServer(string $url, string $serverFileLocation, string $dest, bool $errorIfNotFound = false): void
{
    $ch = curl_init();
    $source = "{$url}?getFile={$serverFileLocation}";
    curl_setopt($ch, CURLOPT_URL, $source);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    $data = curl_exec($ch);
    curl_close($ch);

    if ($errorIfNotFound && (empty($data))) {
        jsonOutputError('Unable to locate file', []);
        return;
    }

    $destination = $dest;
    $file = fopen($destination, "w+");
    fputs($file, $data);
    fclose($file);
}
