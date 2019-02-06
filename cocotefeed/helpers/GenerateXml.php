<?php
class GenerateXml extends ObjectModel
{
    private $domtree;
    private $protocol; // 'https' or 'http'
    private $langID;
    private $xmlFile;
    private $cms;
    private $stock;

    public function __construct($stock)
    {
        $this->domtree = new DOMDocument('1.0', 'UTF-8');
        $this->protocol = $this->checkHTTPS();
        $this->langID = Configuration::get('PS_LANG_DEFAULT');
        $this->xmlFile = hash('crc32',__FILE__).'.xml';
        $this->cms = 'prestashop';
        $this->stock = $stock;
        require_once( _PS_MODULE_DIR_  . 'cocotefeed' . DIRECTORY_SEPARATOR . 'helpers' . DIRECTORY_SEPARATOR . 'DBTeam.php' );
    }
    
    public function initContent()
    {
        if (DBTeam::checkConfigurationStatus() !== 'ACTIVE') {
            //echo 'Status inactive. Cocote export is not configured!';
            Form::msgConfirm($this->l('Status inactive. Cocote n\'est pas configuré!'), array('module' => 'cocotefeed', 'form' => 'default'));
            die();
        }

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
                $elements = $this->getItemInnerXmlElements($product);
                if (!$elements) {
                    continue;
                }

            $currentprodTemponary = $domtree->createElement("item");
            $currentprod = $xmlRoot->appendChild($currentprodTemponary);
            
            foreach($elements as $element){
                $currentprod->appendChild($element);
            }
        }

        $domtree->save($this->xmlFile);
        $this->directoryXml(getcwd(). DIRECTORY_SEPARATOR .$this->xmlFile);
        return $this->xmlFile;
    }
    
    private function getItemInnerXmlElements($product)
    {
        $links = $this->getProductLinks($product['id_product']);

        $response = array();
        // stock avalaible
        if($product['quantity']>0 && $this->stock) {
            $response[] = new DOMElement('identifier', $product['id_product']); /* CUSTOM */
            $response[] = new DOMElement('title', $product['name']); /* REQUIS */

            $response[] = new DOMElement('keywords', strip_tags($this->getProductCategories($product['id_product']))); /* REQUIS */

            $response[] = new DOMElement('brand', strip_tags($product['manufacturer_name'])); /* REQUIS */
            $response[] = new DOMElement('description', strip_tags($product['description'])); /* REQUIS */

            $gtin = $product['ean13'];
            if ($gtin == '0') {
                $gtin = '';
            }
            $response[] = new DOMElement('gtin', $gtin);

            $npm = $product['upc'];
            if ($product['upc'] == '') {
                $npm = $product['id_product'];
            }
            $response[] = new DOMElement('mpn', $npm); /* REQUIS 1/2 */

            $categoriesAll = $this->getProductBaliseCategory($product['id_product']);
            $response[] = new DOMElement('category', $categoriesAll);

            $response[] = new DOMElement('link', htmlentities($links['product']));
            $response[] = new DOMElement('image_link', $links['image1']); /* REQUIS */

            if (!is_null($links['image2'])) {
                $response[] = new DOMElement('image_link2', $links['image2']); /* OPTIONNEL */
            }

            $priceTTC = Product::getPriceStatic($product['id_product']);
            $response[] = new DOMElement('price', number_format(round($priceTTC, 2), 2, '.', ' '));
        }
        // all stock 
        if(!$this->stock) {
            $response[] = new DOMElement('identifier', $product['id_product']); /* CUSTOM */
            $response[] = new DOMElement('title', $product['name']); /* REQUIS */

            $response[] = new DOMElement('keywords', strip_tags($this->getProductCategories($product['id_product']))); /* REQUIS */

            $response[] = new DOMElement('brand', strip_tags($product['manufacturer_name'])); /* REQUIS */
            $response[] = new DOMElement('description', strip_tags($product['description'])); /* REQUIS */

            $gtin = $product['ean13'];
            if ($gtin == '0') {
                $gtin = '';
            }
            $response[] = new DOMElement('gtin', $gtin);

            $npm = $product['upc'];
            if ($product['upc'] == '') {
                $npm = $product['id_product'];
            }
            $response[] = new DOMElement('mpn', $npm); /* REQUIS 1/2 */

            $categoriesAll = $this->getProductBaliseCategory($product['id_product']);
            $response[] = new DOMElement('category', $categoriesAll);

            $response[] = new DOMElement('link', htmlentities($links['product']));
            $response[] = new DOMElement('image_link', $links['image1']); /* REQUIS */

            if (!is_null($links['image2'])) {
                $response[] = new DOMElement('image_link2', $links['image2']); /* OPTIONNEL */
            }

            $priceTTC = Product::getPriceStatic($product['id_product']);
            $response[] = new DOMElement('price', number_format(round($priceTTC, 2), 2, '.', ' '));//
        }
        return $response;
    }
   
    private function getProductLinks($productID)
    {
        $link = new Link();
        $product = new Product($productID);
        $response['product'] = $link->getProductLink($product);
        $images = $product->getImages($this->langID);
        
        if(isset($images[0])){
            $response['image1'] = $this->protocol.'://'.$link->getImageLink($product->link_rewrite[1],$images[0]['id_image']);
        } 
        
        if(isset($images[1])){
            $response['image2'] = $this->protocol.'://'.$link->getImageLink($product->link_rewrite[1],$images[1]['id_image']);
        } else {
            $response['image2'] = null;
        }
        
        return $response;
    }
    
    private function checkHTTPS()
    {
        if(isset($_SERVER['HTTPS'])){
            return 'https';
        } else {
            return 'http';
        }
    }
    
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

    public function enleverCaracteresSpeciaux($text) {
        return str_replace( array('à','á','â','ã','ä', 'ç', 'è','é','ê','ë', 'ì','í','î','ï', 'ñ', 'ò','ó','ô','õ','ö', 'ù','ú','û','ü', 'ý','ÿ', 'À','Á','Â','Ã','Ä', 'Ç', 'È','É','Ê','Ë', 'Ì','Í','Î','Ï', 'Ñ', 'Ò','Ó','Ô','Õ','Ö', 'Ù','Ú','Û','Ü', 'Ý'),
            array('a','a','a','a','a', 'c', 'e','e','e','e', 'i','i','i','i', 'n', 'o','o','o','o','o', 'u','u','u','u', 'y','y', 'A','A','A','A','A', 'C', 'E','E','E','E', 'I','I','I','I', 'N', 'O','O','O','O','O', 'U','U','U','U', 'Y'),
            $text);
    }
}