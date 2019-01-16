<?php

class AdminProductController extends ModuleAdminController
{
	public $ssl = false;

	public function __construct()
	{
//            parent::__construct();
//            $this->context = Context::getContext();
            $cookie = new Cookie('psAdmin');
            if (!$cookie->id_employee) {
                $response['status'] = 'error';
                $response['feed'] = 'Access denied. Admin is not logged in!';
                echo json_encode($response);
                die();
            }
            
            if($_GET['action'] === 'set'){
                    $this->setProduct();
                    return 1;
            }
            
	}
       
        public function setProduct()
        {
            if($_SERVER['REQUEST_METHOD'] !== 'POST'){
                $response['status'] = 'error';
                $response['feed'] = 'Invalid request method!';
                echo json_encode($response);
                die();
            }
            
            if(!isset($_POST['product_id']) || !isset($_POST['categories']) && !isset($_POST['labels'])){
                $response['status'] = 'error';
                $response['feed'] = 'Fill all required fields!';
                echo json_encode($response);
                die();
            }
            
            $productID = $_POST['product_id'];
            $categories = (empty($_POST['categories'])) ? '' : serialize($_POST['categories']);
            $labels = (empty($_POST['labels'])) ? '' : serialize($_POST['labels']);
            
            if(!empty($_POST['mass_label']) && !empty($_POST['mass_label_method'])){
                $this->labelMassUpdate($productID,$_POST['mass_label'],$_POST['mass_label_method'],$labels);
            }
            
            if(!empty($_POST['mass_category']) && !empty($_POST['mass_category_method'])){
                $this->categoryMassUpdate($productID,$_POST['mass_category'],$_POST['mass_category_method'],$categories);
            }
            
            try{
                $primarySettings = "REPLACE INTO cocote_export(product_id,labels,categories) VALUES(".$productID.",'".$labels."','".$categories."');";
                $db = Db::getInstance();
                $db->executeS($primarySettings);
            } catch (Exception $ex) {
                $response['status'] = 'error';
                $response['feed'] = $ex->getMessage();
                echo json_encode($response);
                die();
            }
            
            $response['status'] = 'ok';
            $response['feed'] = 'ok';
            echo json_encode($response);
            die();
        }
        
        public function labelMassUpdate($productID, $group, $method, $labels)
        {
            $products = $this->getSelectedProductsList($productID,$group);

            $db = Db::getInstance();
            
            if($method === 'hard'){
                foreach($products as $product){
                    $sqlCategories = "SELECT categories FROM cocote_export WHERE product_id = ".$product['id_product'];
                    $row = $db->getRow($sqlCategories);
                    $actualCategories = $row['categories'];
                    
                    $query = "REPLACE INTO cocote_export(product_id,labels,categories) VALUES(".$product['id_product'].",'".$labels."', '".$actualCategories."');";
                    $db->executeS($query);
                }
            }
            else if($method === 'soft'){
                foreach($products as $product){
                    $sqlCategories = "SELECT categories,labels FROM cocote_export WHERE product_id = ".$product['id_product'];
                    $row = $db->getRow($sqlCategories);
                    $actualCategories = $row['categories'];
                    $actualLabels = $row['labels'];
                    if($actualLabels === '' || empty($actualLabels) || is_null($actualLabels)){
                        $query = "REPLACE INTO cocote_export(product_id,labels,categories) VALUES(".$product['id_product'].",'".$labels."', '".$actualCategories."');";
                        $db->executeS($query);
                    }
                }
            }
            return 1;
        }
        
        public function categoryMassUpdate($productID, $group, $method, $categories)
        {
            $products = $this->getSelectedProductsList($productID,$group);
            $db = Db::getInstance();
            
            if($method === 'hard'){
                foreach($products as $product){
                    $sqlCategories = "SELECT labels FROM cocote_export WHERE product_id = ".$product['id_product'];
                    $row = $db->getRow($sqlCategories);
                    $actualLabels = $row['labels'];
                    
                    $query = "REPLACE INTO cocote_export(product_id,categories,labels) VALUES(".$product['id_product'].",'".$categories."', '".$actualLabels."');";
                    $db->executeS($query);
                }
            }
            else if($method === 'soft'){
                foreach($products as $product){
                    $sqlCategories = "SELECT categories,labels FROM cocote_export WHERE product_id = ".$product['id_product'];
                    $row = $db->getRow($sqlCategories);
                    $actualCategories = $row['categories'];
                    $actualLabels = $row['labels'];
                    
                    if($actualCategories === '' || empty($actualCategories) || is_null($actualCategories)){
                        $query = "REPLACE INTO cocote_export(product_id,categories,labels) VALUES(".$product['id_product'].",'".$categories."', '".$actualLabels."');";
                        $db->executeS($query);
                    }
                }
            }
            return 1;
        }
        
        private function getSelectedProductsList($productID,$group)
        {
            if($group === 'all'){
                $productObj = new Product();
                return $productObj->getProducts((int)Configuration::get('PS_LANG_DEFAULT'), 0, 0, 'id_product', 'DESC' );
            } 
            else if($group === 'category') {
                $productObj = new Product($productID);
                $categoryID = (int)$productObj->id_category_default;
                $category = new Category($categoryID,(int)Configuration::get('PS_LANG_DEFAULT'));
                return $category->getProducts((int)Configuration::get('PS_LANG_DEFAULT'), 1 , 9999999);
            }
        }
}