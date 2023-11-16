<?php

require_once 'config.php';
require_once 'access.php';

class AmoCRMConnector
{
    private $access_token;
    private $subdomain;
    private $pipeline_id;
    private $user_amo;

    public function __construct($access_token, $subdomain, $pipeline_id, $user_amo)
    {
        $this->access_token = $access_token;
        $this->subdomain = $subdomain;
        $this->pipeline_id = $pipeline_id;
        $this->user_amo = $user_amo;
    }

    public function sendData($name, $price, $email, $phone)
    {
        $data = [
            [
                "name" => $name,
                "price" => $price,
                "responsible_user_id" => (int) $this->user_amo,
                "pipeline_id" => (int) $this->pipeline_id,
                "_embedded" => [
                    "metadata" => [
                        "category" => "forms",
                        "form_id" => 1,
                        "form_name" => "Форма",
                        "form_page" => "https://tonityur.site",
                        "form_sent_at" => time(),
                        "ip" => $_SERVER['REMOTE_ADDR'],
                        "referer" => $_SERVER['HTTP_REFERER'],
                    ],
                    "contacts" => [
                        [
                            "first_name" => $name,
                            "custom_fields_values" => [
                                [
                                    "field_code" => "EMAIL",
                                    "values" => [
                                        [
                                            "enum_code" => "WORK",
                                            "value" => $email,
                                        ],
                                    ],
                                ],
                                [
                                    "field_code" => "PHONE",
                                    "values" => [
                                        [
                                            "enum_code" => "WORK",
                                            "value" => $phone,
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ];

        $method = "/api/v4/leads/complex";

        $headers = [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $this->access_token,
        ];

        $curl = $this->initCurl($method, json_encode($data), $headers);

        $out = curl_exec($curl);
        $this->handleCurlResponse($out);

        curl_close($curl);
    }

    private function initCurl($method, $data, $headers)
    {
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_USERAGENT, 'amoCRM-API-client/1.0');
        curl_setopt($curl, CURLOPT_URL, "https://{$this->subdomain}.amocrm.ru" . $method);
        curl_setopt($curl, CURLOPT_CUSTOMREQUEST, 'POST');
        curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
        curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($curl, CURLOPT_HEADER, false);
        curl_setopt($curl, CURLOPT_COOKIEFILE, 'amo/cookie.txt');
        curl_setopt($curl, CURLOPT_COOKIEJAR, 'amo/cookie.txt');
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, 0);
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 0);

        return $curl;
    }

    private function handleCurlResponse($out)
    {
        $Response = json_decode($out, true);

        if (isset($Response['_embedded']['items'])) {
            $items = $Response['_embedded']['items'];

            $output = 'ID добавленных элементов списков:' . PHP_EOL;
            foreach ($items as $v) {
                if (is_array($v)) {
                    $output .= $v['id'] . PHP_EOL;
                }
            }
            echo $output;
        } else {
            echo "Ключ '_embedded' отсутствует в ответе API amoCRM." . PHP_EOL;
        }
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $amoCRMConnector = new AmoCRMConnector($access_token, $subdomain, $pipeline_id, $user_amo);

    $name = isset($_POST['name']) ? $_POST['name'] : '';
    $price = isset($_POST['price']) ? $_POST['price'] : '';
    $email = isset($_POST['email']) ? $_POST['email'] : '';
    $phone = isset($_POST['phone']) ? $_POST['phone'] : '';

    $amoCRMConnector->sendData($name, $price, $email, $phone);
}
