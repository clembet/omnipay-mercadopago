<?php namespace Omnipay\MercadoPago\Message;

use Omnipay\Common\Exception\InvalidRequestException;
use Omnipay\Common\ItemBag;

class AuthorizeRequest extends AbstractRequest
{
    protected $resource = 'payments';
    /**
     * Get the raw data array for this message. The format of this varies from gateway to
     * gateway, but will usually be either an associative array, or a SimpleXMLElement.
     *
     * @return mixed
     */

    public function getData()
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
}
