<?php

namespace Vixedin\System\Modules;

use Vixedin\System\Modules\CurlPostRequest;
use Vixedin\System\Modules\CustomException as EXP;

class PaypalPayments
{
    private array $actions = [
        'accessToken' => 'v1/oauth2/token',
        'createOrder' => 'v2/checkout/orders',
    ];

    private CurlPostRequest $curl;

    private string $liveUrl = 'https://api-m.paypal.com/';

    private string $sandboxUrl = 'https://api-m.sandbox.paypal.com/';

    private string $activeUrl = '';

    /**
     * Undocumented function
     *
     * @param string $clientId
     * @param string $clientSecret
     * @param boolean $liveEnv
     */
    public function __construct(private string $clientId, private string $clientSecret, private bool $liveEnv = true)
    {
        $liveEnv = MAIN_STATUS === 'production';
        $this->activeUrl = $liveEnv ? $this->liveUrl : $this->sandboxUrl;
        $this->clientId = trim($this->clientId);
        $this->clientSecret = trim($this->clientSecret);
    }

    /**
     * Undocumented function
     *
     * @param string $actionName
     * @return string
     */
    private function getUrlFor(string $actionName): string
    {
        if (!array_key_exists($actionName, $this->actions)) {
            EXP::showException('Invalid action name');
        }

        return $this->activeUrl . $this->actions[$actionName];
    }

    /**
     * Undocumented function
     *
     * @param integer $orderId
     * @param mixed $accessToken
     * @param mixed $paypalOrderId
     * @return mixed
     */
    public function completePayment(int|string $orderId, mixed $accessToken, mixed $paypalOrderId): mixed
    {
        $url = $this->activeUrl . 'v2/checkout/orders/' . $paypalOrderId . '/capture';
        $headers = [
            'Authorization' => 'Bearer ' . $accessToken,
            'PayPal-Request-Id' => $orderId,
            'Content-Type' => 'application/json',
        ];

        $this->curl = new CurlPostRequest($url);
        $response = $this->curl->withHeaders($headers)->completeWithoutData();
        $resArray = json_decode($response, true);
        return $resArray;
    }

    /**
     * Undocumented function
     *
     * @param string $sessionToken
     * @param integer $orderId
     * @param array $items
     * @param array $customer
     * @param array $store
     * @param integer $orderTotal
     * @param mixed $code
     * @return string
     */
    public function generateOrderId(string $sessionToken, int|string $orderId, array $items, array $customer, array $store, int|float $orderTotal, mixed $code): string
    {
        //    $returnUrl = 'https://localhost/MGAuth/completeOrder';
        //  $cancelUrl = 'https://localhost:5317/cart';
        $headers = [
            'Authorization' => 'Bearer ' . $sessionToken,
            'PayPal-Request-Id' => $orderId,
            'Content-Type' => 'application/json',
        ];
        if (!is_null($code)) {
            $breakdown = [
                'item_total' => [
                    'value' => $orderTotal,
                    'currency_code' => 'GBP',
                ],
                'discount' => [
                    'currency_code' => 'GBP',
                    'value' => $code,
                ],
            ];

        } else {
            $breakdown = [
                'item_total' => [
                    'value' => $orderTotal,
                    'currency_code' => 'GBP',
                ],
            ];

        }
        $data = [
            'intent' => 'CAPTURE',
            'purchase_units' => [[
                'payment_instruction' => [
                    'disbursement_mode' => 'INSTANT'
                ],
                'reference_id' => md5($orderId),
                'amount' => [
                    'currency_code' => 'GBP',
                    'value' => $orderTotal - $code,
                    'breakdown' => $breakdown,
                ],
                'items' => $items,
            ]],
            'payment_source' => [
                'paypal' => [
                    'experiance_context' => [
                        'landing_page' => 'LOGIN',
                        'brand_name' => $store['name'],
                        'locale' => 'en-GB',
                        'user_action' => 'PAY_NOW',
                        //                 'return_url' => $returnUrl,
                        //                'cancel_url' => $cancelUrl,
                        'payment_method_preference' => 'IMMEDIATE_PAYMENT_REQUIRED',
                    ],
                ],
            ],
        ];
        $this->curl = new CurlPostRequest($this->getUrlFor('createOrder'));
        $response = $this->curl->withData($data)->withHeaders($headers)->completeWithJson();
        $resArray = json_decode($response, true);
        $links = $resArray['links'] ?? EXP::showException('unable to obtain payment link');
        return $resArray['id'] ?? EXP::showException('unable to obtain id');

    }

    /**
     * Undocumented function
     *
     * @return string
     */
    public function getAccessToken(): string
    {
        $basicAuth = base64_encode($this->clientId . ':' . $this->clientSecret);

        $headers = ['Authorization' => 'Basic ' . $basicAuth];

        $this->curl = new CurlPostRequest($this->getUrlFor('accessToken'));

        $response = $this->curl->withData([
            'grant_type' => 'client_credentials',
        ])->withHeaders(
            $headers
        )->complete();

        $data = json_decode($response, true);

        return $data['access_token'] ?? EXP::showException('unable to obtain access token');
    }
}
