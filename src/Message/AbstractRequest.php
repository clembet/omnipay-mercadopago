<?php namespace Omnipay\MercadoPago\Message;


use Omnipay\Common\Exception\InvalidRequestException;

abstract class AbstractRequest extends \Omnipay\Common\Message\AbstractRequest
{
    protected $liveEndpoint = 'https://api.mercadopago.com';
    protected $testEndpoint = 'https://api.mercadopago.com';
    protected $version = 1;
    protected $resource = '';
    protected $requestMethod = "POST";

    public function getClientID()
    {
        return $this->getParameter('clientId');
    }

    public function setClientID($value)
    {
        return $this->setParameter('clientId', $value);
    }

    public function getClientSecret()
    {
        return $this->getParameter('clientSecret');
    }

    public function setClientSecret($value)
    {
        return $this->setParameter('clientSecret', $value);
    }

    public function getPubKey()
    {
        return $this->getParameter('pubKey');
    }

    public function setPubKey($value)
    {
        return $this->setParameter('pubKey', $value);
    }

    public function getAccessToken()
    {
        return $this->getParameter('accessToken');
    }

    public function setAccessToken($value)
    {
        return $this->setParameter('accessToken', $value);
    }

    public function getData()
    {
        $this->validate('clientId', 'clientSecret', 'pubKey', 'accessToken');

        return [];
    }

    public function sendData($data)
    {
        $method = $this->requestMethod;
        $url = $this->getEndpoint();

        $headers = [
            'Authorization' => 'Bearer '.$this->getAccessToken(),
            'Content-Type' => 'application/json',
            //'x-idempotency-key'
        ];

        //print_r([$method, $url, $headers, json_encode($data)]);exit();
        $response = $this->httpClient->request(
            $method,
            $url,
            $headers,
            $this->toJSON($data)
            //http_build_query($data, '', '&')
        );
        //print_r($response);
        //print_r($data);

        if ($response->getStatusCode() != 200 && $response->getStatusCode() != 201 && $response->getStatusCode() != 400) {
            $array = [
                'error' => [
                    'code' => $response->getStatusCode(),
                    'message' => $response->getReasonPhrase()
                ]
            ];

            return $this->response = $this->createResponse($array);
        }

        $json = $response->getBody()->getContents();
        $array = @json_decode($json, true);
        //print_r($array);

        return $this->response = $this->createResponse(@$array);
    }

    protected function setBaseEndpoint($value)
    {
        $this->baseEndpoint = $value;
    }

    public function __get($name)
    {
        return $this->getParameter($name);
    }

    protected function decode($data)
    {
        return json_decode($data, true);
    }

    public function setOrderId($value)
    {
        return $this->setParameter('order_id', $value);
    }
    public function getOrderId()
    {
        return $this->getParameter('order_id');
    }

    public function setInstallments($value)
    {
        return $this->setParameter('installments', $value);
    }
    public function getInstallments()
    {
        return $this->getParameter('installments');
    }

    public function setSoftDescriptor($value)
    {
        return $this->setParameter('soft_descriptor', $value);
    }
    public function getSoftDescriptor()
    {
        return $this->getParameter('soft_descriptor');
    }

    public function getCustomerName()
    {
        return $this->getParameter('customer_name');
    }

    public function setCustomerName($value)
    {
        $this->setParameter('customer_name', $value);
    }

    public function getPaymentType()
    {
        return $this->getParameter('paymentType');
    }

    public function setPaymentType($value)
    {
        $this->setParameter('paymentType', $value);
    }

    public function getDueDate()
    {
        $dueDate = $this->getParameter('dueDate');
        if($dueDate)
            return $dueDate;

        $time = localtime(time());
        $ano = $time[5]+1900;
        $mes = $time[4]+1+1;
        $dia = 1;// $time[3];
        if($mes>12)
        {
            $mes=1;
            ++$ano;
        }

        $dueDate = sprintf("%04d-%02d-%02dT23:59:59.000-04:00", $ano, $mes, $dia);
        $this->setDueDate($dueDate);

        return $dueDate;
    }

    public function setDueDate($value)
    {
        return $this->setParameter('dueDate', $value);
    }

    public function getCustomer()
    {
        return $this->getParameter('customer');
    }

    public function setCustomer($value)
    {
        return $this->setParameter('customer', $value);
    }

    public function setExtraAmount($value)
    {
        return $this->setParameter('extraAmount', $value);
    }

    public function getExtraAmount()//TODO: refazer
    {
        $extraAmount = $this->getParameter('extraAmount');

        if ($extraAmount !== null && $extraAmount !== 0) {
            if ($this->getCurrencyDecimalPlaces() > 0) {
                if (is_int($extraAmount) || (is_string($extraAmount) && strpos((string)$extraAmount, '.') === false)) {
                    throw new InvalidRequestException(
                        'Please specify extra amount as a string or float, with decimal places.'
                    );
                }
            }

            // Check for rounding that may occur if too many significant decimal digits are supplied.
            $decimal_count = strlen(substr(strrchr(sprintf('%.8g', $extraAmount), '.'), 1));
            if ($decimal_count > $this->getCurrencyDecimalPlaces()) {
                throw new InvalidRequestException('Amount precision is too high for currency.');
            }

            return $this->formatCurrency($extraAmount);
        }
    }

    public function getShipment()
    {
        $card = $this->getCard();
        return [
            "receiver_address"=> [
                "zip_code"=> $card->getShippingPostcode(),
                "state_name"=> $card->getShippingState(),
                "city_name"=> $card->getShippingCity(),
                "street_name"=> $card->getShippingAddress1(),
                "street_number"=> $card->getShippingNumber()
            ]
        ];
    }

    public function getPayerData()
    {
        $customer = $this->getCustomer();

        return [
            "first_name"=> $customer->getFirstName(),
            "last_name"=> $customer->getLastName(),
            "phone"=> [
                "area_code"=> $customer->getAreaCode(),
                "number"=> substr($customer->getPhone(), 2, 9)
            ],
            "address"=> [
                "zip_code"=> $customer->getBillingPostcode(),
                "street_name"=> $customer->getBillingAddress1(),
                "street_number"=> $customer->getBillingNumber()
            ]
        ];
    }

    public function getItemData()
    {
        $data = [];
        $items = $this->getItems();

        if ($items) {
            foreach ($items as $n => $item) {
                $item_array = [];
                $item_array['id'] = $n+1;
                $item_array['title'] = $item->getName();
                $item_array['description'] = $item->getName();
                //$item_array['category_id'] = $item->getCategoryId();
                $item_array['quantity'] = (int)$item->getQuantity();
                //$item_array['currency_id'] = $this->getCurrency();
                $item_array['unit_price'] = (double)($this->formatCurrency($item->getPrice()));

                array_push($data, $item_array);
            }
        }

        return $data;
    }

    public function getCardToken()
    {
        return $this->getParameter('cardToken');
    }
    public function setCardToken($value)
    {
        $this->setParameter('cardToken', $value);
    }

    public function getTransactionID()
    {
        return $this->getParameter('transactionId');
    }

    public function setTransactionID($value)
    {
        return $this->setParameter('transactionId', $value);
    }

    protected function createResponse($data)
    {
        return $this->response = new Response($this, $data);
    }

    protected function getEndpoint()
    {
        $endPoint = $this->getTestMode() ? $this->testEndpoint : $this->liveEndpoint;
        return  "{$endPoint}/v{$this->getVersion()}/{$this->getResource()}";
    }

    protected function getVersion()
    {
        return $this->version;
    }

    protected  function getResource()
    {
        return $this->resource;
    }

    protected function getMethod()
    {
        return $this->requestMethod;
    }

    public function toJSON($data, $options = 0)
    {
        if (version_compare(phpversion(), '5.4.0', '>=') === true) {
            return json_encode($data, $options | 64);
        }
        return str_replace('\\/', '/', json_encode($data, $options));
    }

    public function getDataCreditCard()
    {
        $this->validate('cardToken');

        $payer = $this->getPayerData();
        $card = $this->getCard();

        $data = [
            "token"=> $this->getCardToken(),
            "capture"=> false,
            "binary_mode"=>true,
            "transaction_amount"=> (float)$this->getAmount(),
            "installments"=> $this->getInstallments(),
            "description"=> $this->getSoftDescriptor(),
            "external_reference"=> $this->getOrderId(),
            "payer"=> [
                "entity_type"=> "individual",
                "type"=> "customer",
                "first_name"=> $card->getFirstName(),
                "last_name"=> $card->getLastName(),
                "email"=> $card->getEmail(),
                "identification"=> [
                    "type"=> "CPF",
                    "number"=> $card->getHolderDocumentNumber()
                ],
            ],
            "notification_url"=> $this->getNotifyUrl(),
            "additional_info"=> [
                "items"=> $this->getItemData(),
                "payer"=> $payer,
                "shipments"=> $this->getShipment()
            ]
        ];

        return $data;
    }

    public function getDataBoleto() //https://www.mercadopago.com.br/developers/pt/guides/online-payments/checkout-api/other-payment-ways   => $payment->payment_method_id = "bolbradesco";  date_of_expiration 
    {
        //$payer = $this->getPayerData();
        $customer = $this->getCustomer();

        $data = [
            "transaction_amount"=> (float)$this->getAmount(),
            "description"=> $this->getSoftDescriptor(),
            "external_reference"=> $this->getOrderId(),
            "payment_method_id"=> "bolbradesco",
            "date_of_expiration" => $this->getDueDate(), // formato (yyyy-MM-dd'T'HH:mm:ssz)
            "payer"=> [
                //"entity_type"=> "individual",
                //"type"=> "customer",
                "email"=> $customer->getEmail(),
                "first_name"=> $customer->getFirstName(),
                "last_name"=> $customer->getLastName(),
                "identification"=> [
                    "type"=> "CPF",
                    "number"=> $customer->getDocumentNumber()
                ],
                "address"=>  [
                    "street_name" => $customer->getBillingAddress1(),
                    "street_number" => $customer->getBillingNumber(),
                    "zip_code" => $customer->getBillingPostcode(),
                    "neighborhood" => $customer->getBillingDistrict(),
                    "city" => $customer->getBillingCity(),
                    "federal_unit" => $customer->getBillingState()
                ]
            ],
            "notification_url"=> $this->getNotifyUrl(),
            /*"additional_info"=> [
                "items"=> $this->getItemData(),
                "payer"=> $payer,
                "shipments"=> $this->getShipment()
            ]*/
        ];

        return $data;
    }

    // https://docs.linxdigital.com.br/docs/mercado-pago-configurando-pix
    // https://www.mercadopago.com.br/pix-flows
    // https://www.mercadopago.com.br/ajuda/17843
    public function getDataPix() //https://www.mercadopago.com.br/developers/pt/guides/online-payments/checkout-api/receiving-payment-by-pix
    {
        //$payer = $this->getPayerData();
        $customer = $this->getCustomer();

        $data = [
            "transaction_amount"=> (float)$this->getAmount(),
            "description"=> $this->getSoftDescriptor(),
            "external_reference"=> $this->getOrderId(),
            "payment_method_id"=> "pix",
            "date_of_expiration" => $this->getDueDate(), // formato (yyyy-MM-dd'T'HH:mm:ssz)
            "payer"=> [
                //"entity_type"=> "individual",
                //"type"=> "customer",
                "email"=> $customer->getEmail(),
                "first_name"=> $customer->getFirstName(),
                "last_name"=> $customer->getLastName(),
                "identification"=> [
                    "type"=> "CPF",
                    "number"=> $customer->getDocumentNumber()
                ],
                "address"=>  [
                    "street_name" => $customer->getBillingAddress1(),
                    "street_number" => $customer->getBillingNumber(),
                    "zip_code" => $customer->getBillingPostcode(),
                    "neighborhood" => $customer->getBillingDistrict(),
                    "city" => $customer->getBillingCity(),
                    "federal_unit" => $customer->getBillingState()
                ]
            ],
            "notification_url"=> $this->getNotifyUrl(),
            /*"additional_info"=> [
                "items"=> $this->getItemData(),
                "payer"=> $payer,
                "shipments"=> $this->getShipment()
            ]*/
        ];

        return $data;
    }
}
