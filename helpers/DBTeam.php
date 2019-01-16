<?php

require_once( _PS_MODULE_DIR_  . $this->name . DIRECTORY_SEPARATOR . 'helpers' . DIRECTORY_SEPARATOR . 'GenerateXml.php' );

class DBTeam extends ModuleAdminController
{
    private $domtree;
    private $protocol; // 'https' or 'http'
    private $langID;

    public function __construct() 
    {
        $this->name = 'cocotefeed';
        $this->domtree = new DOMDocument('1.0', 'UTF-8');
        $this->protocol = $this->checkHTTPS();
        $this->langID = Configuration::get('PS_LANG_DEFAULT');

        parent::__construct();
    }
    
    /* SELECT OPTIONS */
    
    public static function getPaymentOptions()
    {
        $payment = array(
            array('key' => 'bitcoin', 'name' => 'Bitcoin'),
            array('key' => 'paypal', 'name' => 'PayPal'),
            array('key' => 'cbplusieursfois', 'name' => 'CB plusieurs fois sans frais'),
            array('key' => 'credit', 'name' => 'Credit'),
            array('key' => 'cheque', 'name' => 'Chèque'),
            array('key' => 'virement', 'name' => 'Virement'),
            array('key' => 'gift_payment', 'name' => 'Carte/Chèque cadeaux'),
            array('key' => 'prelevement', 'name' => 'Prelevement bancaire'),
            array('key' => 'cb', 'name' => 'Carte Bancaire'),
            array('key' => 'especes', 'name' => 'Espèces'),
            array('key' => 'crypto-monnaies', 'name' => 'Crypto-monnaies'),
            array('key' => 'ethereum', 'name' => 'Ethereum'),
            array('key' => 'ripple', 'name' => 'Ripple'),
            array('key' => 'litecoin', 'name' => 'Litecoin'),
            array('key' => 'nem', 'name' => 'NEM'),
            array('key' => 'dash', 'name' => 'Dash'),
            array('key' => 'ethereumclassic', 'name' => 'EthereumClassic'),
            array('key' => 'monero', 'name' => 'Monero'),
            array('key' => 'stellarlumens', 'name' => 'StellarLumens'),
            array('key' => 'steem', 'name' => 'Steem'),
            array('key' => 'golem', 'name' => 'Golem'),
            array('key' => 'augur', 'name' => 'Augur'),
            array('key' => 'dogecoin', 'name' => 'Dogecoin'),
            array('key' => 'maidsafecoin', 'name' => 'MaidSafeCoin'),
            array('key' => 'stratis', 'name' => 'Stratis'),
            array('key' => 'zcash', 'name' => 'Zcash'),
            array('key' => 'gnosis', 'name' => 'Gnosis'),
            array('key' => 'bitshares', 'name' => 'BitShares'),
            array('key' => 'waves', 'name' => 'Waves'),
            array('key' => 'bytecoin', 'name' => 'Bytecoin'),
            array('key' => 'digixdao', 'name' => 'DigixDAO'),
            array('key' => 'factom', 'name' => 'Factom'),
            array('key' => 'decred', 'name' => 'Decred'),
            array('key' => 'singulardtv', 'name' => 'SingularDTV'),
            array('key' => 'ardor', 'name' => 'Ardor'),
            array('key' => 'pivx', 'name' => 'PIVX'),
            array('key' => 'siacoin', 'name' => 'Siacoin'),
            array('key' => 'tether', 'name' => 'Tether'),
            array('key' => 'gamecredits', 'name' => 'GameCredits'),
            array('key' => 'lisk', 'name' => 'Lisk'),
            array('key' => 'amazon-pay', 'name' => 'Amazon Pay'),
        );
        return $payment;
    }
    
    public static function getPeopleTargetOptions()
    {
        $targets = array(
            array('key' => 'homme', 'name' => 'Homme'),
            array('key' => 'femme', 'name' => 'Femme'),
            array('key' => 'enfant', 'name' => 'Enfant'),
            array('key' => 'garcon', 'name' => 'Garçon'),
            array('key' => 'fille', 'name' => 'Fille'),
            array('key' => 'adolescente', 'name' => 'Adolescente'),
            array('key' => 'femme-enceinte', 'name' => 'Femme enceinte'),
            array('key' => 'jeune-maman', 'name' => 'Jeune maman'),
            array('key' => 'femme-agee', 'name' => 'Femme âgée'),
            array('key' => 'bebe-fille', 'name' => 'Bébé fille'),
            array('key' => 'bebe-garcon', 'name' => 'Bébé garçon'),
            array('key' => 'adolescent', 'name' => 'Adolescent'),
            array('key' => 'homme-age', 'name' => 'Homme âgé'),
        );
        return $targets;
    }
    
    /* CONFIGURATION */
    
    public static function getFormSubmitConfigValue()
    {
        $values['allowedDistance'] = Tools::getValue('COCOTE_ALLOWED_DISTANCE');

        $values['depositLat'] = Tools::getValue('COCOTE_DEPOSIT_LAT');
        $values['depositLon'] = Tools::getValue('COCOTE_DEPOSIT_LON');
        $values['depositRoad'] = Tools::getValue('COCOTE_DEPOSIT_ROAD');
        $values['depositZipcode'] = Tools::getValue('COCOTE_DEPOSIT_ZIPCODE');
        $values['depositCity'] = Tools::getValue('COCOTE_DEPOSIT_CITY');

        $values['pointLat'] = Tools::getValue('COCOTE_POINT_LAT');
        $values['pointLon'] = Tools::getValue('COCOTE_POINT_LON');
        $values['pointRoad'] = Tools::getValue('COCOTE_POINT_ROAD');
        $values['pointZipcode'] = Tools::getValue('COCOTE_POINT_ZIPCODE');
        $values['pointCity'] = Tools::getValue('COCOTE_POINT_CITY');
        
        $values['COCOTE_GTIN'] = Tools::getValue('COCOTE_GTIN');
        $values['COCOTE_MPN'] = Tools::getValue('COCOTE_MPN');
        $values['COCOTE_PRODUCER'] = Tools::getValue('COCOTE_PRODUCER');
        
        $values['targets'] = Tools::getValue('target_people_keys');
        $values['tags'] = Tools::getValue('tag_keys');
        $values['saleType'] = Tools::getValue('sale_type_keys');
        $values['paymentOnline'] = Tools::getValue('payment_online');
        $values['paymentOnsite'] = Tools::getValue('payment_onsite');

        $values['COCOTE_EXPORTED_SHOP_ID'] = Tools::getValue('COCOTE_EXPORTED_SHOP_ID');
        $values['COCOTE_EXPORTED_PRIVATE_KEY'] = Tools::getValue('COCOTE_EXPORTED_PRIVATE_KEY');
        $values['COCOTE_EXPORTED_GODFATHER_ADVANTAGES'] = Tools::getValue('COCOTE_EXPORTED_GODFATHER_ADVANTAGES');
        $values['COCOTE_EXPORTED_GODSON_ADVANTAGES'] = Tools::getValue('COCOTE_EXPORTED_GODSON_ADVANTAGES');
        $values['COCOTE_EXPORTED_SPONSOR_DETAILS'] = Tools::getValue('COCOTE_EXPORTED_SPONSOR_DETAILS');
        $values['COCOTE_STATUS_STOCK'] = Tools::getValue('COCOTE_STATUS_STOCK');

        return $values;
    }
    
    public static function saveFormSubmitConfigValue($values)
    {
        Configuration::updateValue('COCOTE_ALLOWED_DISTANCE', $values['allowedDistance']);

        Configuration::updateValue('COCOTE_DEPOSIT_LAT', $values['depositLat']);
        Configuration::updateValue('COCOTE_DEPOSIT_LON', $values['depositLon']);
        Configuration::updateValue('COCOTE_DEPOSIT_ROAD', $values['depositRoad']);
        Configuration::updateValue('COCOTE_DEPOSIT_ZIPCODE', $values['depositZipcode']);
        Configuration::updateValue('COCOTE_DEPOSIT_CITY', $values['depositCity']);

        Configuration::updateValue('COCOTE_POINT_LAT', $values['pointLat']);
        Configuration::updateValue('COCOTE_POINT_LON', $values['pointLon']);
        Configuration::updateValue('COCOTE_POINT_ROAD', $values['pointRoad']);
        Configuration::updateValue('COCOTE_POINT_ZIPCODE', $values['pointZipcode']);
        Configuration::updateValue('COCOTE_POINT_CITY', $values['pointCity']);
        
        Configuration::updateValue('COCOTE_GTIN', $values['COCOTE_GTIN']);
        Configuration::updateValue('COCOTE_MPN', $values['COCOTE_MPN']);
        Configuration::updateValue('COCOTE_PRODUCER', $values['COCOTE_PRODUCER']);
        

        Configuration::updateValue('COCOTE_TARGETS', serialize($values['targets']));
        Configuration::updateValue('COCOTE_TAGS', serialize($values['tags']));
        Configuration::updateValue('COCOTE_SALE_TYPE', serialize($values['saleType']));
        Configuration::updateValue('COCOTE_PAYMENT_ONLINE', serialize($values['paymentOnline']));
        Configuration::updateValue('COCOTE_PAYMENT_ONSITE', serialize($values['paymentOnsite']));

        Configuration::updateValue('COCOTE_EXPORTED_SHOP_ID', $values['COCOTE_EXPORTED_SHOP_ID']);
        Configuration::updateValue('COCOTE_EXPORTED_PRIVATE_KEY', $values['COCOTE_EXPORTED_PRIVATE_KEY']);
        Configuration::updateValue('COCOTE_EXPORTED_GODFATHER_ADVANTAGES', $values['COCOTE_EXPORTED_GODFATHER_ADVANTAGES']);
        Configuration::updateValue('COCOTE_EXPORTED_GODSON_ADVANTAGES', $values['COCOTE_EXPORTED_GODSON_ADVANTAGES']);
        Configuration::updateValue('COCOTE_EXPORTED_SPONSOR_DETAILS', $values['COCOTE_EXPORTED_SPONSOR_DETAILS']);
        Configuration::updateValue('COCOTE_STATUS_STOCK', $values['COCOTE_STATUS_STOCK']);


        return true;
    }
    
    public static function deleteConfiguration()
    {
        Configuration::deleteByName('COCOTE_ALLOWED_DISTANCE');

        Configuration::deleteByName('COCOTE_DEPOSIT_LAT');
        Configuration::deleteByName('COCOTE_DEPOSIT_LON');
        Configuration::deleteByName('COCOTE_DEPOSIT_ROAD');
        Configuration::deleteByName('COCOTE_DEPOSIT_ZIPCODE');
        Configuration::deleteByName('COCOTE_DEPOSIT_CITY');

        Configuration::deleteByName('COCOTE_POINT_LAT');
        Configuration::deleteByName('COCOTE_POINT_LON');
        Configuration::deleteByName('COCOTE_POINT_ROAD');
        Configuration::deleteByName('COCOTE_POINT_ZIPCODE');
        Configuration::deleteByName('COCOTE_POINT_CITY');
        
        Configuration::deleteByName('COCOTE_GTIN');
        Configuration::deleteByName('COCOTE_MPN');
        Configuration::deleteByName('COCOTE_PRODUCER');
        
        Configuration::deleteByName('COCOTE_TARGETS');
        Configuration::deleteByName('COCOTE_TAGS');
        Configuration::deleteByName('COCOTE_SALE_TYPE');
        Configuration::deleteByName('COCOTE_PAYMENT_ONLINE');
        Configuration::deleteByName('COCOTE_PAYMENT_ONSITE');
        
        Configuration::deleteByName('COCOTE_EXPORTED_SHOP_ID');
        Configuration::deleteByName('COCOTE_EXPORTED_PRIVATE_KEY');
        Configuration::deleteByName('COCOTE_EXPORTED_GODFATHER_ADVANTAGES');
        Configuration::deleteByName('COCOTE_EXPORTED_GODSON_ADVANTAGES');
        Configuration::deleteByName('COCOTE_EXPORTED_SPONSOR_DETAILS');
        Configuration::deleteByName('COCOTE_STATUS_STOCK');
        return true;
    }
    
    public static function validateFormConfigValue(&$values)
    {
        $errors = false;

        /* Shop ID */
        if(empty($values['COCOTE_EXPORTED_SHOP_ID'])){
            $errors[] = 'Shop ID est requis!';
        }

        if(empty($values['COCOTE_EXPORTED_PRIVATE_KEY'])){
            $errors[] = 'Private key est requis!';
        }
            
        if($errors != false){
            return $errors;
        } 
        else{
            return true;
        }
    }
    
    /* STATUS */
    
    public static function checkConfigurationStatus()
    {
        $status = 'ACTIVE';

        if(DBTeam::checkHowManyProduct()==0){
            $status = 'INACTIVE';
        }

        return $status;
    }
    
    public static function checkProductExportStatus($productID)
    {
        return 'ACTIVE';
        $sqlCategories = "SELECT categories,labels FROM cocote_export WHERE product_id = ".(int)$productID;
        $product = Db::getInstance()->getRow($sqlCategories);
        
        
        if(!empty($product['labels']) && !is_null($product['labels']) && !empty($product['categories']) && !is_null($product['categories'])){
            return 'ACTIVE';
        }
        else {
            return 'INACTIVE';
        }
    }
    
    public static function checkHowManyProductIsConfigured()
    {
        $sql = "SELECT count(*) as total FROM `cocote_export` WHERE labels != '' AND categories != '' ";
        if($row = Db::getInstance()->getRow($sql)){
            return $row['total'];
        }
        else{
            return 0;
        }  
    }

    public static function checkHowManyProduct($statusStock = true)
    {
        if(!$statusStock) {
            $productObj = new Product();
            $products = $productObj->getProducts(Configuration::get('PS_LANG_DEFAULT'), 0, 0, 'id_product', 'ASC');

            if ($products) {
                return count($products);
            } else {
                return 0;
            }
        }else{
            $sql = "SELECT count(*) as total FROM "._DB_PREFIX_."product p INNER JOIN "._DB_PREFIX_."stock_available sa ON p.id_product = sa.id_product AND id_product_attribute = 0 AND sa.quantity>0";
            if($row = Db::getInstance()->getRow($sql)){
                return $row['total'];
            }
            else{
                return 0;
            }
        }
    }

    public static function insertCocoteExport($urlShopFinal)
    {
        $shop_id = Tools::getValue('COCOTE_EXPORTED_SHOP_ID');
        $private_key = Tools::getValue('COCOTE_EXPORTED_PRIVATE_KEY');
        $export_status = Tools::getValue('COCOTE_STATUS_STOCK');

        if ($shop_id !='' && $private_key!=''){
            Db::getInstance()->Execute("INSERT INTO `cocote_export` (shop_id, private_key, export_xml,export_status)
                                        VALUES ('". $shop_id."',
                                                '". $private_key."',
                                                '". $urlShopFinal."',
                                                '". $export_status ."'
                                                )"
                                    );
        }
    }

    public static function updateCocoteExport($urlShopFinal)
    {
        $shop_id = Tools::getValue('COCOTE_EXPORTED_SHOP_ID');
        $private_key = Tools::getValue('COCOTE_EXPORTED_PRIVATE_KEY');
        $export_status = Tools::getValue('COCOTE_STATUS_STOCK');

        if ($shop_id != '' && $private_key != '') {
            Db::getInstance()->Execute("UPDATE `cocote_export` 
                                        SET shop_id='" . $shop_id . "',
                                            private_key='" . $private_key . "',
                                            export_xml='" . $urlShopFinal . "',
                                            export_status='".$export_status."'"
            );
        }
    }

    public static function checkCocoteFeed($urlShopFinal)
    {
        $sql = "SELECT shop_id, private_key, export_xml FROM `cocote_export` ORDER BY id_export DESC";
        if($row = Db::getInstance()->getRow($sql)){
            // update 1 data
            DBTeam::updateCocoteExport($urlShopFinal);
            if($row = Db::getInstance()->getRow($sql)){
                return $row;
            }
        }
        else{
            // insert 1 data
            DBTeam::insertCocoteExport($urlShopFinal);
            return 0;
        }
    }

    public static function checkStatusCocoteFeed()
    {
        $sql = "SELECT shop_id, private_key, export_xml FROM `cocote_export` ORDER BY id_export DESC";
        if($row = Db::getInstance()->getRow($sql)){
            return $row;
        }
        else{
            return 0;
        }
    }

    public static function generateCocoteXml($statusStock)
    {
        $link = new Link;
        $url_shop = $link->getBaseLink();
        $urlShopFinal = '';

        /* Generate XML */
        $generateXml = new GenerateXml($statusStock);
        $xmlName = $generateXml->initContent();

        $urlShopFinal = $url_shop.'feed'. DIRECTORY_SEPARATOR .$xmlName;
        // insert DataBase
        DBTeam::checkCocoteFeed($urlShopFinal);

        return $urlShopFinal;
    }

    public static function checkCustomerByIdOrder($id_order)
    {
        $sql = "SELECT o.total_paid, c.email FROM "
            ._DB_PREFIX_ ."orders o INNER JOIN "
            ._DB_PREFIX_ ."customer c ON o.id_customer=c.id_customer 
            WHERE o.id_order=".$id_order;
        if($row = Db::getInstance()->getRow($sql)){
            return $row;
        }
        else{
            return 0;
        }
    }

    public static function checkCocoteExport()
    {
        $sql = "SELECT * FROM cocote_export";
        if($row = Db::getInstance()->getRow($sql)){
            return $row;
        }
        else{
            return 0;
        }
    }


}
