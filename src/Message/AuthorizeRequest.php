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

    public function getShippingType()
    {
        return $this->getParameter('shippingType');
    }

    public function setShippingType($value)
    {
        return $this->setParameter('shippingType', $value);
    }

    public function getShippingCost()
    {
        return $this->getParameter('shippingCost');
    }

    public function setShippingCost($value)
    {
        return $this->setParameter('shippingCost', $value);
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
        $card = $this->getCard();

        return [
            "first_name"=> $card->getFirstName(),
            "last_name"=> $card->getLastName(),
            "phone"=> [
                "area_code"=> $card->getAreaCode(),
                "number"=> substr($card->getPhone(), 2, 9)
            ],
            "address"=> [
                "zip_code"=> $card->getShippingPostcode(),
                "street_name"=> $card->getShippingAddress1(),
                "street_number"=> $card->getShippingNumber()
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
}
