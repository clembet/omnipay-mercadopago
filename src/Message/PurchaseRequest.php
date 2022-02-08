<?php namespace Omnipay\MercadoPago\Message;


class PurchaseRequest extends AuthorizeRequest
{
    protected $resource = '/payments';

    public function getData()
    {
        $data = parent::getData();

        $data["capture"]=true;

        return $data;

    }
}
