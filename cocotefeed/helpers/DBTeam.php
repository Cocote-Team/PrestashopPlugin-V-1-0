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
    
    /* CONFIGURATION */
    
    public static function getFormSubmitConfigValue()
    {
        $values['COCOTE_EXPORTED_SHOP_ID'] = Tools::getValue('COCOTE_EXPORTED_SHOP_ID');
        $values['COCOTE_EXPORTED_PRIVATE_KEY'] = Tools::getValue('COCOTE_EXPORTED_PRIVATE_KEY');
        $values['COCOTE_STATUS_STOCK'] = Tools::getValue('COCOTE_STATUS_STOCK');

        return $values;
    }
    
    public static function saveFormSubmitConfigValue($values)
    {

        Configuration::updateValue('COCOTE_EXPORTED_SHOP_ID', $values['COCOTE_EXPORTED_SHOP_ID']);
        Configuration::updateValue('COCOTE_EXPORTED_PRIVATE_KEY', $values['COCOTE_EXPORTED_PRIVATE_KEY']);
        Configuration::updateValue('COCOTE_STATUS_STOCK', $values['COCOTE_STATUS_STOCK']);


        return true;
    }
    
    public static function deleteConfiguration()
    {
        
        Configuration::deleteByName('COCOTE_EXPORTED_SHOP_ID');
        Configuration::deleteByName('COCOTE_EXPORTED_PRIVATE_KEY');
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
