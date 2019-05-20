<?php

/**
 * Class GenerateXml
 */
class GenerateXml extends ObjectModel
{
    private $domtree;
    private $protocol; // 'https' or 'http'
    private $langID;
    private $xmlFile;
    private $cms;
    private $stock;

    /**
     * GenerateXml constructor.
     * @param $stock
     */
    public function __construct($stock)
    {
        $this->domtree = new DOMDocument('1.0', 'UTF-8');
        $this->protocol = $this->checkHTTPS();
        $this->langID = Configuration::get('PS_LANG_DEFAULT');
        $this->xmlFile = hash('crc32',__FILE__).'.xml';
        $this->cms = 'prestashop';
        $this->stock = (boolean)$stock;
        require_once( _PS_MODULE_DIR_  . 'cocotefeed' . DIRECTORY_SEPARATOR . 'helpers' . DIRECTORY_SEPARATOR . 'DBTeam.php' );
    }

    /**
     * @return string
     */
    public function initContent()
    {
        if (DBTeam::checkConfigurationStatus() !== 'ACTIVE') {
            echo 'Status inactive. Cocote export is not configured!';
            die();
        }

        $module = Module::getInstanceByName('cocotefeed');

        $productObj = new Product();

        $products = $productObj->getProducts($this->langID, 0, 0, 'id_product', 'DESC');

        $domtree = new DOMDocument('1.0', 'UTF-8');

        $root= $domtree->createElement("shop");
        $xmlRoot = $domtree->appendChild($root);

        $generated = $domtree->createElement('generated');
        $attr = $domtree->createAttribute('cms');
        $attr->value = $this->cms;
        $generated->appendChild($attr);

        $attr2 = $domtree->createAttribute('plugin_version');
        $attr2->value = $module->version;
        $generated->appendChild($attr2);

        $attr2 = $domtree->createAttribute('prestashop_version');
        $attr2->value = _PS_VERSION_;
        $generated->appendChild($attr2);

        $domtree->appendChild($generated);
        $generated = $root->appendChild($generated);
        $text = $domtree->createTextNode(date('Y-m-d H:i:s'));
        $text = $generated->appendChild($text);

        $xmlRootTemponary = $domtree->createElement("offers");
        $xmlRoot = $xmlRoot->appendChild($xmlRootTemponary);

        foreach($products as $product){
            $product['quantity'] = Product::getQuantity($product['id_product']);
            $this->getItemInnerXmlElements($product, $domtree);
        }

        $domtree->save($this->xmlFile);
        $this->directoryXml(getcwd(). DIRECTORY_SEPARATOR .$this->xmlFile);
        return $this->xmlFile;
    }

    /**
     * @param $product
     * @param DOMDocument $domtree
     * @return array
     */
    private function getItemInnerXmlElements($product, $domtree)
    {
        $offers = $domtree->getElementsByTagName('offers')->item(0);

        if(($this->stock && $product['quantity']>0) || !$this->stock) {
            $links = $this->getProductLinks($product['id_product']);

            $currentprod = $domtree->createElement('item');
            $offers->appendChild($currentprod);

            $currentprod->appendChild($domtree->createElement('identifier', $product['id_product']));
            $currentprod->appendChild($domtree->createElement('link', $links['product']));
            $currentprod->appendChild($domtree->createElement('keywords', strip_tags($this->getProductCategories($product['id_product']))));
            $currentprod->appendChild($domtree->createElement('brand', strip_tags($product['manufacturer_name'])));

            $descTitle = $domtree->createElement('title');
            $descTitle->appendChild($domtree->createCDATASection($product['name']));
            $currentprod->appendChild($descTitle);

            $descTag = $domtree->createElement('description');
            $descTag->appendChild($domtree->createCDATASection(strip_tags($product['description'])));
            $currentprod->appendChild($descTag);

            $currentprod->appendChild($domtree->createElement('image_link', $links['image1']));

            if (!is_null($links['image2'])) {
                $currentprod->appendChild($domtree->createElement('image_link2', $links['image2']));
            }

            $npm = $product['upc'];
            if ($product['upc'] == '') {
                $npm = $product['id_product'];
            }
            $currentprod->appendChild($domtree->createElement('mpn', $npm));

            $categoriesAll = $this->getProductBaliseCategory($product['id_product']);
            $currentprod->appendChild($domtree->createElement('category', $categoriesAll));

            $priceTTC = Product::getPriceStatic($product['id_product']);
            $currentprod->appendChild($domtree->createElement('price', number_format(round($priceTTC, 2), 2, '.', ' ')));

            $gtin = $product['ean13'];
            if ($gtin == '0') {
                $gtin = '';
            }

            $currentprod->appendChild($domtree->createElement('gtin', $gtin));
        }
    }

    /**
     * @param $productID
     * @return mixed
     */
    private function getProductLinks($productID)
    {
        $link = new Link();
        $product = new Product($productID);
        $response['product'] = $link->getProductLink($product);
        $images = $product->getImages($this->langID);

        if(isset($images[0])){
            $response['image1'] = $this->protocol.'://'.$link->getImageLink($product->link_rewrite[Context::getContext()->language->id],$images[0]['id_image']);
        }

        if(isset($images[1])){
            $response['image2'] = $this->protocol.'://'.$link->getImageLink($product->link_rewrite[Context::getContext()->language->id],$images[1]['id_image']);
        } else {
            $response['image2'] = null;
        }

        return $response;
    }

    /**
     * @return string
     */
    private function checkHTTPS()
    {
        if(isset($_SERVER['HTTPS'])){
            return 'https';
        } else {
            return 'http';
        }
    }

    /**
     * @param $serializeValues
     * @return string
     */
    private function prepareTargetValues($serializeValues)
    {
        $response = '';
        $values = unserialize($serializeValues);
        $i = 0;
        foreach($values as $value){
            if($i == 0){
                $response .= $value;
            } else {
                $response .= '|'.$value;
            }
            $i++;
        }
        return $response;
    }

    /**
     * @param $productID
     * @return string
     */
    private function getMPN($productID)
    {
        $configFeatureID = Configuration::get('COCOTE_MPN');
        $features = Product::getFeaturesStatic($productID);
        foreach($features as $feature){
            if($feature['id_feature'] == $configFeatureID){
                $featureValue = FeatureValueCore::getFeatureValueLang($feature['id_feature_value']);
                return $featureValue[0]['value'];
            }
        }
        return '';
    }

    /**
     * @param $productID
     * @return string
     */
    private function getGTIN($productID)
    {
        $configFeatureID = Configuration::get('COCOTE_GTIN');
        $features = Product::getFeaturesStatic($productID);
        foreach($features as $feature){
            if($feature['id_feature'] == $configFeatureID){
                $featureValue = FeatureValueCore::getFeatureValueLang($feature['id_feature_value']);
                return $featureValue[0]['value'];
            }
        }
        return '';
    }

    /**
     * @param $xmlFileLocal
     * @return string
     */
    private function directoryXml($xmlFileLocal){
        chdir(_PS_ADMIN_DIR_);
        chdir('..');
        if(!file_exists('feed')){
            mkdir ('feed');
        }
        $path = getcwd(). DIRECTORY_SEPARATOR .'feed' . DIRECTORY_SEPARATOR .$this->xmlFile;
        rename($xmlFileLocal, $path);

        return $path;
    }

    /**
     * @param $productID
     * @return string
     */
    public function getProductCategories($productID){
        $keywords = '';
        $i = 0;

        $categories = Product::getProductCategoriesFull($productID);
        foreach($categories as $categorie) {
            if($i == 0) {
                $keywords .= $categorie['name'];
            }else {

                if ($categorie['name'] != '') {
                    $keywords .= '|'.$categorie['name'];
                }
            }
            $i++;
        }

        return $keywords;
    }

    /**
     * @param $productID
     * @return mixed
     */
    public function getProductBaliseCategory($productID)
    {
        $categoriesAll = Product::getProductCategoriesFull($productID);
        $i = 0;
        $category = '';
        foreach ($categoriesAll as $categorie) {
            if ($i == 0) {
                $category .= str_replace(" ", "-", $categorie['name']);
            } else {
                $category .= ' > ' . str_replace(" ", "-", $categorie['name']);
            }
            $i++;
        }
        return $this->enleverCaracteresSpeciaux(strtolower($category));
    }

    /**
     * @param $text
     * @return mixed
     */
    public function enleverCaracteresSpeciaux($text) {
        return str_replace( array('à','á','â','ã','ä', 'ç', 'è','é','ê','ë', 'ì','í','î','ï', 'ñ', 'ò','ó','ô','õ','ö', 'ù','ú','û','ü', 'ý','ÿ', 'À','Á','Â','Ã','Ä', 'Ç', 'È','É','Ê','Ë', 'Ì','Í','Î','Ï', 'Ñ', 'Ò','Ó','Ô','Õ','Ö', 'Ù','Ú','Û','Ü', 'Ý'),
            array('a','a','a','a','a', 'c', 'e','e','e','e', 'i','i','i','i', 'n', 'o','o','o','o','o', 'u','u','u','u', 'y','y', 'A','A','A','A','A', 'C', 'E','E','E','E', 'I','I','I','I', 'N', 'O','O','O','O','O', 'U','U','U','U', 'Y'),
            $text);
    }
}