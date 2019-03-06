<?php

/**
 * Class CashbackCocote
 */
class CashbackCocote
{
    public $_shopId;
    public $_privateKey;
    public $_email;
    public $_orderId;
    public $_orderPrice;
    public $_priceCurrency;
    public $_orderState;
    public $_skus;

    public function __construct($shopId, $privateKey, $email, $orderId, $orderPrice, $priceCurrency, $orderState, $skus){
        $this->_shopId          = $shopId;
        $this->_privateKey      = $privateKey;
        $this->_email           = $email;
        $this->_orderId         = $orderId;
        $this->_orderPrice      = $orderPrice;
        $this->_priceCurrency   = $priceCurrency ;
        $this->_orderState      = $orderState;
        $this->_skus            = $skus;
    }

    public function sendOrderToCocote()
    {
        if(! is_dir(__DIR__ . DIRECTORY_SEPARATOR . 'log')) {mkdir (__DIR__ . DIRECTORY_SEPARATOR . 'log', 0755);}

        $fp = fopen(__DIR__ . DIRECTORY_SEPARATOR . 'log' . DIRECTORY_SEPARATOR . 'log_' . date('Ymd') . '.log', 'w');
        if($this->isCurlLoad()) {
            $observer = '[LOG ' . date('Y-m-d H:i:s') . '] Start function sendOrderToCocote()';
            fwrite($fp, $observer . "\n");

            fwrite($fp, '[LOG ' . date('Y-m-d H:i:s') . '] curl is load = ' . extension_loaded('curl') . "\n");

            $elements = explode(',', $this->_skus);
            try {
                $data = array(
                    'shopId' => $this->_shopId,
                    'privateKey' => $this->_privateKey,
                    'email' => $this->_email,
                    'orderId' => $this->_orderId,
                    'orderPrice' => $this->_orderPrice,
                    'priceCurrency' => $this->_priceCurrency,
                    'orderState' => $this->_orderState,
                    'skus' => $elements,
                );

                fwrite($fp, '[LOG ' . date('Y-m-d H:i:s') . '] data = '
                    . $data['shopId'] . ' - '
                    . $data['privateKey'] . ' - '
                    . $data['email'] . ' - '
                    . $data['orderId'] . ' - '
                    . $data['orderPrice'] . ' - '
                    . $data['priceCurrency'] . ' - '
                    . $data['orderState'] . ' - '
                    . $this->_skus
                    . "\n");

                $start = mktime();

                $curl = curl_init();
                curl_setopt($curl, CURLOPT_POST, 1);
                curl_setopt($curl, CURLOPT_POSTFIELDS, $data);

                curl_setopt($curl, CURLOPT_URL, "https://fr.cocote.com/api/cashback/request");  // API de prod
                curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
                curl_setopt($curl, CURLOPT_TIMEOUT_MS, 1000);

                $result = curl_exec($curl);
                $curl_errno = curl_errno($curl);
                $curl_error = curl_error($curl);
                curl_close($curl);

                if($curl_errno > 0) {
                    fwrite($fp, '[LOG ' . date('Y-m-d H:i:s') . '] curl_errno = ' . $curl_errno . "\n");
                    fwrite($fp, '[LOG ' . date('Y-m-d H:i:s') . '] curl_error = ' . $curl_error . "\n");
                }else{
                    $json_data = json_decode($result);
                    $status = '';
                    $errors = '';
                    if ($json_data != '') {
                        foreach ($json_data as $v) {
                            if ($v->status != '')
                                $status = $v->status;

                            if ($v->errors[0] != '')
                                $errors = $v->errors[0];

                        }
                    }

                    fwrite($fp, '[LOG ' . date('Y-m-d H:i:s') . '] Status Curl = ' . $status . " \n");
                    fwrite($fp, '[LOG ' . date('Y-m-d H:i:s') . '] Errors Curl = ' . $errors . " \n");
                    fwrite($fp, '[LOG ' . date('Y-m-d H:i:s') . '] Response Curl = ' . $result . " \n");
                    $end = mktime();
                    $dure = date("s", $end - $start);
                    fwrite($fp, '[LOG ' . date('Y-m-d H:i:s') . '] durée Curl = ' . $dure . " s.\n");
                }

            } catch (Exception $e) {
                fwrite($fp, '[LOG ' . date('Y-m-d H:i:s') . '] ' . $e->getMessage() . "\n");
            }

            $observer = '[LOG ' . date('Y-m-d H:i:s') . '] End function sendOrderToCocote()';
            fwrite($fp, $observer . "\n");
            fclose($fp);
        }else{
            fwrite($fp, '[LOG ' . date('Y-m-d H:i:s') . '] CURL IS NOT LOAD'. "\n");
        }
    }

    private function isCurlLoad()
    {
        return extension_loaded('curl') ? true : false;
    }
}

if(isset($argv[1]) && isset($argv[2]) && isset($argv[3]) && isset($argv[4]) && isset($argv[5]) && isset($argv[7])) {
    $cashback_cocote = new CashbackCocote($argv[1], $argv[2], $argv[3], $argv[4], number_format($argv[5], 2, '.', ' '), 'EUR', $argv[6], $argv[7]);
    $cashback_cocote->sendOrderToCocote();
}