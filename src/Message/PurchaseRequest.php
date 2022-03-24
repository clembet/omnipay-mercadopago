<?php namespace Omnipay\MercadoPago\Message;


class PurchaseRequest extends AuthorizeRequest
{
    protected $resource = '/payments';

    public function getData()
    {
        $data = parent::getData();
        if(strcmp(strtolower($this->getPaymentType()), "creditcard")==0)
            $data["capture"]=true;

        return $data;

    }
}
