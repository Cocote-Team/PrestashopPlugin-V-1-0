<?php
class cocotefeedGenerateModuleFrontController extends ModuleFrontController
{
    private $domtree;
    private $protocol; // 'https' or 'http'
    private $langID;

    public function __construct()
    {
        $this->domtree = new DOMDocument('1.0', 'UTF-8');
        $this->protocol = $this->checkHTTPS();
        $this->langID = Configuration::get('PS_LANG_DEFAULT');
        require_once( _PS_MODULE_DIR_  . 'cocotefeed' . DIRECTORY_SEPARATOR . 'helpers' . DIRECTORY_SEPARATOR . 'DBTeam.php' );
        parent::__construct();
    }
    
    public function initContent()
    {
        if(DBTeam::checkConfigurationStatus() !== 'ACTIVE'){
            echo 'Status inactive. Cocote export is not configured!';
            die();
        }
        
        $productObj = new Product();
        $products = $productObj->getProducts($this->langID, 0, 0, 'id_product', 'DESC' );
        
        $domtree = new DOMDocument('1.0', 'UTF-8');
        
        $xmlRootTemponary = $domtree->createElement("offers");
        $xmlRoot = $domtree->appendChild($xmlRootTemponary);
        foreach($products as $product){
            if(DBTeam::checkProductExportStatus((int)$product['id_product']) !== 'ACTIVE'){
                continue;
            }
            $elements = $this->getItemInnerXmlElements($product);
            if(!$elements){
                continue;
            }
            $importedPlaceOnline = $domtree->importNode($this->getPlaceOnlineElement());
            $importedPlaceOnsite = $domtree->importNode($this->getPlaceOnsiteElement(),true);
            $currentprodTemponary = $domtree->createElement("item");
            $currentprod = $xmlRoot->appendChild($currentprodTemponary);
            
            foreach($elements as $element){
                $currentprod->appendChild($element);
            }
            $currentprod->appendChild($importedPlaceOnline);
            $currentprod->appendChild($importedPlaceOnsite);
        }
        $domtree->save('feed/cocote.xml');
        echo 'ok';
    }
    
    private function getItemInnerXmlElements($product)
    {
//        $links = $this->getProductLinks($product['id_product']);
        $productDetails = $this->getProductDetails($product['id_product']);
        if(!$productDetails){
            return false;
        }
        
        $response = array();
        $response[] = new DOMElement('id', $product['id_product']); /* CUSTOM */
        $response[] = new DOMElement('title', $product['name']); /* REQUIS */
        $response[] = new DOMElement('description', strip_tags($product['description'])); /* REQUIS */
        $response[] = new DOMElement('gtin', $this->getGTIN($product['id_product'])); /* REQUIS 1/2 */
        $response[] = new DOMElement('mpn', $this->getMPN($product['id_product'])); /* REQUIS 1/2 */ 
        $response[] = new DOMElement('labels', $productDetails['labels']); /* REQUIS */
        $response[] = new DOMElement('category', $productDetails['categories']); /* REQUIS */
//        $response[] = new DOMElement('link', $links['product']); /* REQUIS */
//        $response[] = new DOMElement('image_link', $links['image1']); /* REQUIS */
//        
//        if(!is_null($links['image2'])){
//           $response[] = new DOMElement('image_link2', $links['image2']); /* OPTIONNEL */
//        }
        
        $response[] = new DOMElement('price', $product['price']); /* REQUIS */
        
        if(Configuration::get('COCOTE_SALE_TYPE') != 'b:0;'){
            $response[] = new DOMElement('sale_type', $this->prepareTargetValues(Configuration::get('COCOTE_SALE_TYPE'))); /* REQUIS */
        }
        if(Configuration::get('COCOTE_PAYMENT_ONLINE') != 'b:0;'){
            $response[] = new DOMElement('payment_online', $this->prepareTargetValues(Configuration::get('COCOTE_PAYMENT_ONLINE')));
        }
        if(Configuration::get('COCOTE_PAYMENT_ONSITE') != 'b:0;'){
            $response[] = new DOMElement('payment_onsite', $this->prepareTargetValues(Configuration::get('COCOTE_PAYMENT_ONSITE')));
        }
        
        $response[] = new DOMElement('producer', Configuration::get('COCOTE_PRODUCER')); /* OPTIONNEL */
        $response[] = new DOMElement('state', $this->checkProductState($product['condition'])); /* OPTIONNEL */
        
        if(Configuration::get('COCOTE_TARGETS') != 'b:0;'){
            $response[] = new DOMElement('targets', $this->prepareTargetValues(Configuration::get('COCOTE_TARGETS'))); /* OPTIONNEL */
        }
        if(Configuration::get('COCOTE_TAGS') != 'b:0;'){
            $response[] = new DOMElement('tags', $this->prepareTargetValues(Configuration::get('COCOTE_TAGS'))); /* OPTIONNEL */
        }
        if(Configuration::get('COCOTE_ALLOWED_DISTANCE')){
            $response[] = new DOMElement('distance', Configuration::get('COCOTE_ALLOWED_DISTANCE')); /* OPTIONNEL */
        }
        
        return $response;
    }
    
    private function getPlaceOnlineElement() 
    {
        try{
            $domtree = new DOMDocument('1.0', 'UTF-8');
            $placeOnline = $domtree->createElement("place_online");
            $placeOnline->setAttribute('lat', Configuration::get('COCOTE_DEPOSIT_LAT')); /* REQUIS */
            $placeOnline->setAttribute('lon', Configuration::get('COCOTE_DEPOSIT_LON')); /* REQUIS */
            $placeOnline->setAttribute('road', Configuration::get('COCOTE_DEPOSIT_ROAD')); /* REQUIS */
            $placeOnline->setAttribute('zipcode', Configuration::get('COCOTE_DEPOSIT_ZIPCODE')); /* REQUIS */
            $placeOnline->setAttribute('city', Configuration::get('COCOTE_DEPOSIT_CITY')); /* REQUIS */
            return $placeOnline->cloneNode();
        } 
        catch (Exception $ex) {
            echo $ex->getMessage();
            die();
        }
    }
    
    private function getPlaceOnsiteElement()
    {
        $domtree = new DOMDocument('1.0', 'UTF-8');
        $placeOnsiteTemponary = $domtree->createElement("place_onsite");
        $placeOnsite = $domtree->appendChild($placeOnsiteTemponary);
        
        $place = $domtree->createElement('place');
        
        if(Configuration::get('COCOTE_POINT_LAT')){
            $place->setAttribute('lat', Configuration::get('COCOTE_POINT_LAT')); /* OPTIONNEL */
        }
        if(Configuration::get('COCOTE_POINT_LON')){
            $place->setAttribute('lon', Configuration::get('COCOTE_POINT_LON')); /* OPTIONNEL */
        }
        
        $place->setAttribute('road', Configuration::get('COCOTE_POINT_ROAD')); /* REQUIS */
        $place->setAttribute('zipcode', Configuration::get('COCOTE_POINT_ZIPCODE')); /* REQUIS */
        $place->setAttribute('city', Configuration::get('COCOTE_POINT_CITY')); /* REQUIS */
        $placeOnsite->appendChild($place);
        
        return $placeOnsite->cloneNode(true);
    }
    
    private function getProductDetails($productID)
    {
        $sql = "SELECT labels,categories FROM cocote_export WHERE product_id = ".$productID;
        if($row = Db::getInstance()->getRow($sql)){
            $row['labels'] = $this->prepareTargetValues($row['labels']);
            $row['categories'] = $this->prepareTargetValues($row['categories']);
            return $row;
        }
        else{
            return false;
        }
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
    
    private function checkProductState($productState)
    {
        switch($productState){
            case 'new':
                $state = 'new';
                break;
            case 'used': case 'refurbished':
                $state = 'second_hand';
                break;
            default:
                $state = 'aucune';
                break;
        }
        return $state;
    }
    
    private function checkHTTPS()
    {
        if($_SERVER['HTTPS'] == "on"){
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
}