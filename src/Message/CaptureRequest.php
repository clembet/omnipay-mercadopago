<?php namespace Omnipay\MercadoPago\Message;


class CaptureRequest extends AbstractRequest
{
    protected $resource = 'payments';
    protected $requestMethod = 'PUT';


    public function getData()
    {
        $this->validate('transactionId', 'amount');
        //$data = parent::getData();
        $data = [
            "capture"=> true,
            //"status"=> "approved",
            "transaction_amount"=> (float)$this->getAmount()
        ];

        return $data;
    }

    protected function getEndpoint()
    {
        $endPoint = parent::getEndpoint();
        return  "{$endPoint}/{$this->getTransactionID()}";
    }
}
