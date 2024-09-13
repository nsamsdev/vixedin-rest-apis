<?php

namespace Vixedin\System\Modules;

class CurlPostRequest
{
    private mixed $curl;
    private string $url;
    private array $data;
    private array $headers = [];
    private mixed $statusCode;
    private mixed $error;

    public function __construct(string $url)
    {
        $this->url = $url;
    }

    /**
     * Undocumented function
     *
     * @return mixed
     */
    public function completeGet(): mixed
    {
        $curl = curl_init();

        curl_setopt_array($curl, [
            CURLOPT_URL => $this->url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_CUSTOMREQUEST => 'GET',
            CURLOPT_HTTPHEADER => $this->headers,
        ]);

        $response = curl_exec($curl);

        curl_close($curl);

        return $response;

    }

    /**
     * Undocumented function
     *
     * @param string $url
     * @return CurlPostRequest
     */
    public function updateUrl(string $url): CurlPostRequest
    {
        $this->url = $url;
        return $this;
    }

    /**
     * Undocumented function
     *
     * @param array $data
     * @return CurlPostRequest
     */
    public function withData(array $data): CurlPostRequest
    {
        $this->data = $data;
        return $this;
    }

    /**
     * Undocumented function
     *
     * @param array $headers
     * @return CurlPostRequest
     */
    public function withHeaders(array $headers): CurlPostRequest
    {
        foreach ($headers as $key => $value) {
            $this->headers[] = $key . ': ' . $value;
        }
        return $this;
    }

    /**
     * Undocumented function
     *
     * @return mixed
     */
    public function getStatusCode(): mixed
    {
        return $this->statusCode;
    }

    /**
     * Undocumented function
     *
     * @return mixed
     */
    public function completeWithoutData(): mixed
    {
        $curl = curl_init();

        curl_setopt_array($curl, [
            CURLOPT_URL => $this->url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            //CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_POSTFIELDS => '',
            CURLOPT_HTTPHEADER => $this->headers,
        ]);

        $response = curl_exec($curl);

        curl_close($curl);

        return $response;

    }

    /**
     * Undocumented function
     *
     * @return mixed
     */
    public function completeWithJson(): mixed
    {

        $this->headers[] = 'Content-Type: application/json';
        $curl = curl_init();

        curl_setopt_array($curl, [
            CURLOPT_URL => $this->url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            //CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_POSTFIELDS => json_encode($this->data),
            CURLOPT_HTTPHEADER => $this->headers,
        ]);

        $response = curl_exec($curl);

        curl_close($curl);

        return $response;

    }

    /**
     * Undocumented function
     *
     * @return mixed
     */
    public function complete(): mixed
    {

        $this->headers[] = 'Content-Type: application/x-www-form-urlencoded';
        $curl = curl_init();

        curl_setopt_array($curl, [
            CURLOPT_URL => $this->url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            //CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_POSTFIELDS => http_build_query($this->data),
            CURLOPT_HTTPHEADER => $this->headers,
        ]);

        $response = curl_exec($curl);

        curl_close($curl);

        return $response;
    }

}
