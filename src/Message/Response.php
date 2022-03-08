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

        if((strcmp($status, "approved")==0) || (strcmp($status, "authorized")==0))
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
}