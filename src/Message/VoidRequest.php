<?php

namespace Omnipay\MercadoPago\Message;

/**
 * MercadoPago Refund Request
 *
 * https://dev.pagseguro.uol.com.br/reference/charge-refund
 *
 * <code>
 *   // Do a refund transaction on the gateway
 *   $transaction = $gateway->void(array(
 *       'amount'                   => '10.00',
 *       'transactionId'     => $transactionCode,
 *   ));
 *
 *   $response = $transaction->send();
 *   if ($response->isSuccessful()) {
 *   }
 * </code>
 */

class VoidRequest extends AbstractRequest   // /cancels é utilizado em pagamentos om cartão com o status em AUTHORIZED, ou seja para transações authorized (2 etapas)
{
    protected $resource = 'payments';
    protected $requestMethod = 'POST';


    public function getData()
    {
        $this->validate('transactionId', 'amount');
        //$data = parent::getData();
        $data = [
            "amount"=> (float)$this->getAmount()
        ];

        return $data;
    }

    protected function getEndpoint()
    {
        $endPoint = parent::getEndpoint();
        return  "{$endPoint}/{$this->getTransactionID()}/refunds";
    }
}
