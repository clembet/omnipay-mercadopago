<?php namespace Omnipay\MercadoPago\Message;

use Omnipay\Common\Message\AbstractResponse;
use Omnipay\Common\Message\RedirectResponseInterface;

/**
 * Pagarme Response
 *
 * This is the response class for all Pagarme requests.
 *
 * @see \Omnipay\Pagarme\Gateway
 */
class Response extends AbstractResponse
{
    /**
     * Is the transaction successful?
     *
     * @return bool
     */
    public function isSuccessful()
    {
        //$result = $this->data;
        if(isset($this->data['error']) || isset($this->data['cause']))
            return false;

        $status = $this->getStatus();
        if(strcmp($status, "rejected")==0) {
            $this->data['status'] = 400;
            $this->data['erro'] = "Erro no pagmento";
            $this->data['message'] = "Erro no pagmento";
            return false;
        }

        if((strcmp($status, "approved")==0) || (strcmp($status, "authorized")==0) || (strcmp($status, "pending")==0) || (strcmp($status, "in_process")==0) || (strcmp($status, "refunded")==0) || (strcmp($status, "cancelled")==0))
            return true;

        return false;
    }

    /**
     * Get the transaction reference.
     *
     * @return string|null
     */
    public function getTransactionID()
    {
        if(isset($this->data['id']))
            return @$this->data['id'];

        return @$this->data['code'];
    }

    public function getTransactionAuthorizationCode()
    {
        if(isset($this->data['id']))
            return @$this->data['id'];

        return @$this->data['code'];
    }

    public function getStatus()
    {
        $status = null;
        if(isset($this->data['status']))
            $status = @$this->data['status'];

        return $status;
    }

    public function isPaid()
    {
        $status = $this->getStatus();
        return strcmp($status, "approved")==0;
    }

    public function isAuthorized()
    {
        $status = $this->getStatus();
        return strcmp($status, "authorized")==0;
    }

    public function isPending()
    {
        $status = $this->getStatus();
        return (strcmp($status, "pending")==0 || strcmp($status, "in_process")==0);
    }

    public function isVoided()
    {
        $status = $this->getStatus();
        return (strcmp($status, "refunded")==0 || strcmp($status, "cancelled")==0);
    }

    /**
     * Get the error message from the response.
     *
     * Returns null if the request was successful.
     *
     * @return string|null
     */
    public function getMessage()
    {
        //print_r($this->data);
        if(isset($this->data['error']))
            if(!isset($this->data['status']))
                print_r($this->data);
            return "{$this->data['status']} - {$this->data['message']}";
        
        return null;
    }

    public function getBoleto()//https://www.mercadopago.com.br/developers/pt/guides/online-payments/checkout-api/other-payment-ways
    {
        $data = $this->getData();
        $boleto = array();
        $boleto['boleto_url'] = @$data['transaction_details']['external_resource_url'];
        $boleto['boleto_url_pdf'] = @$data['transaction_details']['external_resource_url'];
        $boleto['boleto_barcode'] = "";//@$data['transaction_details']['DigitableLine'];//TODO:
        $boleto['boleto_expiration_date'] = NULL;//@$data['transaction_details']['ExpirationDate'];//TODO:
        $boleto['boleto_valor'] = (@$data['transaction_details']['total_paid_amount']*1.0);
        $boleto['boleto_transaction_id'] = @$data['id'];
        //@$this->setTransactionReference(@$data['transaction_id']);

        return $boleto;
    }

    public function getPix() // https://www.mercadopago.com.br/developers/pt/guides/online-payments/checkout-api/receiving-payment-by-pix
    {
        $data = $this->getData();
        $pix = array();
        $pix['pix_qrcodebase64image'] = @$data['point_of_interaction']['transaction_data']['qr_code_base64'];
        $pix['pix_qrcodestring'] = @$data['point_of_interaction']['transaction_data']['qr_code'];
        $pix['pix_valor'] = (@$data['transaction_details']['total_paid_amount']*1.0);
        $pix['pix_transaction_id'] = @$data['id'];

        return $pix;
    }
}