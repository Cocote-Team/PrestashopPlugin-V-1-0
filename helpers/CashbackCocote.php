<?php

/**
 * Created by PhpStorm.
 * User: wang
 * Date: 20/12/18
 * Time: 16:45
 */
class CashbackCocote extends ObjectModel
{
    public $_shopId;
    public $_privateKey;
    public $_email;
    public $_orderId;
    public $_orderPrice;
    public $_priceCurrency;

    public function __construct($shopId, $privateKey, $email, $orderId, $orderPrice, $priceCurrency){
        $this->_shopId          = $shopId;
        $this->_privateKey      = $privateKey;
        $this->_email           = $email;
        $this->_orderId         = $orderId;
        $this->_orderPrice      = $orderPrice;
        $this->_priceCurrency   = $priceCurrency ;
    }

    public function sendOrderToCocote()
    {
        $fp = fopen(_PS_MODULE_DIR_ . 'cocotefeed' . DIRECTORY_SEPARATOR . 'log' . DIRECTORY_SEPARATOR . 'log_' . date('Ymd') . '.log', 'a+');
        $observer = '[LOG ' . date('Y-m-d H:i:s') . '] Start function sendOrderToCocote()';
        fwrite($fp, $observer . "\n");

        try {
        $data = array(
            'shopId' => $this->_shopId,
            'privateKey' => $this->_privateKey,
            'email' => $this->_email,
            'orderId' => $this->_orderId,
            'orderPrice' => $this->_orderPrice,
            'priceCurrency' => $this->_priceCurrency,
        );

            fwrite($fp, '[LOG ' . date('Y-m-d H:i:s') . '] data = '
                .$data['shopId'].' - '
                .$data['privateKey'].' - '
                .$data['email'].' - '
                .$data['orderId'].' - '
                .$data['orderPrice'].' - '
                .$data['priceCurrency'].' - '
                . "\n");

            if (!function_exists('curl_version')) {
                fwrite($fp, '[LOG ' . date('Y-m-d H:i:s') . '] no curl'. "\n");
                throw new Exception('no curl');
            }

            $curl = curl_init();
            curl_setopt($curl, CURLOPT_POST, 1);
            curl_setopt($curl, CURLOPT_POSTFIELDS, $data);

            curl_setopt($curl, CURLOPT_URL, "https://fr.cocote.com/api/cashback/request");    // API de prod
            curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);

            $result = curl_exec($curl);
            curl_close($curl);

            $json_data = json_decode($result);
            $status = '';
            $errors = '';
            if($json_data != '') {
                foreach ($json_data as $v) {
                    if($v->status !='')
                        $status = $v->status;


                    if($v->errors[0] !='')
                        $errors = $v->errors[0];

                }
            }

            fwrite($fp, '[LOG ' . date('Y-m-d H:i:s') . '] Status Curl = '.$status. " \n");
            fwrite($fp, '[LOG ' . date('Y-m-d H:i:s') . '] Errors Curl = '.$errors. " \n");

        } catch (Exception $e) {
            fwrite($fp, '[LOG ' . date('Y-m-d H:i:s') . '] '.$e->getMessage(). "\n");
        }

        $observer = '[LOG ' . date('Y-m-d H:i:s') . '] End function sendOrderToCocote()';
        fwrite($fp, $observer . "\n");
        fclose($fp);
    }
}