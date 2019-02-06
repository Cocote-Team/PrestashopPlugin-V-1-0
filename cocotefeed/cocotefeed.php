<?php
if (!defined('_PS_VERSION_'))
{
  exit;
}

class CocoteFeed extends Module
{
    public function __construct()
    {
        $this->name = 'cocotefeed'; //like folder name
        $this->tab = 'front_office_features';
        $this->version = '1.0.2';
        $this->author = 'Vang KU';
        $this->need_instance = 0;
        $this->ps_versions_compliancy = array('min' => '1.6', 'max' => _PS_VERSION_);
        $this->bootstrap = true;
        $this->stock = true;

        parent::__construct();
        
        require_once( _PS_MODULE_DIR_ . DIRECTORY_SEPARATOR . $this->name . DIRECTORY_SEPARATOR . 'helpers' . DIRECTORY_SEPARATOR . 'DBTeam.php' );
        
        $this->displayName = $this->l('Cocotefeed');
        $this->description = $this->l('Cocote export for Prestashop version 1.6.* to '._PS_VERSION_);
        $this->confirmUninstall = $this->l('Are you sure you want to uninstall?');
    }
    
    /* --- INSTALL --- */
    
    public function install()
    {
        if (Shop::isFeatureActive()){
            Shop::setContext(Shop::CONTEXT_ALL);
        }
        if (!parent::install() ||
                !$this->registerHook('actionOrderStatusUpdate')
                || !$this->registerHook('actionCronJob')
                || !$this->registerHook('header')
            ){
            return false;
        }

        if(!$this->createProductsTable()){
            return false;
        }

        return true ;
    }
    
    private function createProductsTable()
    {
        $table = "CREATE TABLE `cocote_export` (
                `id_export` int(10) NOT NULL,
                `shop_id` varchar(10) NOT NULL,
                `private_key` varchar(255) NOT NULL,
                `export_status` int(1) NOT NULL DEFAULT '1',
                `export_xml` varchar(255) NOT NULL
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8;";

        $primaryKey = "ALTER TABLE `cocote_export` ADD UNIQUE KEY `id_export` (`id_export`);";
        $autoIncremente = "ALTER TABLE `cocote_export` MODIFY `id_export` int(10) NOT NULL AUTO_INCREMENT;";
        
        $db = Db::getInstance();
        if(!$db->execute($table) || !$db->execute($primaryKey) || !$db->execute($autoIncremente)){
            return false;
        }
        return true;
    }

    /* --- UNINSTALL --- */
    
    public function uninstall()
    {
        if (!parent::uninstall() || !Configuration::deleteByName('MYMODULE_NAME')){
            return false;
        }
        if(!$this->deleteProductsTable()){
            return false;
        }
        DBTeam::deleteConfiguration();
        return true;
    }
    
    private function deleteProductsTable()
    {
        $db = Db::getInstance();
        if(!$db->execute('DROP TABLE `cocote_export`;')){
            return false;
        }
        return true;
    }
    
    /* --- HOOKS --- */

    public function hookActionOrderStatusUpdate($params)
    {
        if(!empty($params['newOrderStatus'])) {
            // status = Paiement accepté OR Livré OR Expédié
            if ($params['newOrderStatus']->id == Configuration::get('PS_OS_WS_PAYMENT') ||
                $params['newOrderStatus']->id == Configuration::get('PS_OS_PAYMENT') ||
                $params['newOrderStatus']->id == Configuration::get('PS_OS_DELIVERED') ||
                $params['newOrderStatus']->id == Configuration::get('PS_OS_SHIPPING') ||
                $params['newOrderStatus']->id == Configuration::get('PS_OS_CANCELED')
            ) {
                $rowCustomer = DBTeam::checkCustomerByIdOrder($params['id_order']);
                $rowCocoteExport = DBTeam::checkCocoteExport();
                if (count($rowCustomer) > 0 AND count($rowCocoteExport) > 0) {

                    if (!file_exists(_PS_MODULE_DIR_ . $this->name . DIRECTORY_SEPARATOR . 'log')) {
                        mkdir(_PS_MODULE_DIR_ . $this->name . DIRECTORY_SEPARATOR . 'log');
                    }
                    $fp = fopen(_PS_MODULE_DIR_ . $this->name . DIRECTORY_SEPARATOR . 'log' . DIRECTORY_SEPARATOR . 'log_' . date('Ymd') . '.log', 'a+');
                    $observer = '[LOG ' . date('Y-m-d H:i:s') . '] newOrderStatus = ' . $params['newOrderStatus']->id;
                    fwrite($fp, $observer . "\n");

                    fclose($fp);

                    $status = 'completed';
                    if ($params['newOrderStatus']->id == Configuration::get('PS_OS_CANCELED'))
                        $status = 'cancelled';

                    $orderDetails = OrderDetail::getList((int)$params['id_order']);
                    $i = 0;
                    $skus = '';
                    foreach ($orderDetails as $orderDetail) {
                        if ($i == 0) {
                            $skus .= $orderDetail['product_id'];
                        } else {
                            $skus .= ',' . $orderDetail['product_id'];
                        }
                        $i++;
                    }

                    exec('php ' . _PS_MODULE_DIR_ . 'cocotefeed' . DIRECTORY_SEPARATOR . 'CashbackCocote.php' .
                        ' ' . $rowCocoteExport['shop_id'] .
                        ' ' . $rowCocoteExport['private_key'] .
                        ' ' . $rowCustomer['email'] .
                        ' ' . $params['id_order'] .
                        ' ' . $rowCustomer['total_paid'] .
                        ' ' . $status .
                        ' ' . $skus
                    );
                }
            } else {
                $rowCustomer = DBTeam::checkCustomerByIdOrder($params['id_order']);
                $rowCocoteExport = DBTeam::checkCocoteExport();
                if (count($rowCocoteExport) > 0) {
                    $this->context->smarty->assign(
                        array(
                            'mSiteId' => $rowCocoteExport['shop_id'],
                            'amount' => $rowCustomer['total_paid'],
                            'orderId' => $params['id_order']
                        ));
                }
                
                return $this->display(__FILE__, 'views/templates/front/analytics_confirm.tpl');
            }
        }
    }

    /* --- CONFIG --- */
    
    public function getContent()
    {
        $output = null;

        if (Tools::isSubmit('submit'.$this->name)){
            $values = DBTeam::getFormSubmitConfigValue();
            $validate = DBTeam::validateFormConfigValue($values);
            
            DBTeam::saveFormSubmitConfigValue($values);
            $output .= $this->displayConfirmation($this->l('Paramètres enregistrés'));

            $this->stock = $values['COCOTE_STATUS_STOCK'];

            if($validate !== true){
                foreach($validate as $error){
                    $output .= $this->displayError($this->l($error));
                }
            }
        }

        $this->context->controller->addJS($this->_path.'js/config.js',false);
        $this->context->controller->addCSS($this->_path.'css/mymodule.css', 'all');
        return $output.$this->displayForm();
    }
    
    public function displayForm()
    {
        // Get default language
        $default_lang = (int)Configuration::get('PS_LANG_DEFAULT');

        // Init Fields form array
        $fields_form[0]['form'] = array(
            'legend' => array(
                'title' => $this->l('Settings'),
            ),
            'input' => array(

                array(
                    'type' => 'text',
                    'style' => 'text-align: center',
                    'label' => $this->l('Status'),
                    'readonly' => 'readonly',
                    'name' => 'COCOTE_STATUS'
                ),
                array(
                    'type' => 'text',
                    'style' => 'text-align: center',
                    'label' => $this->l('Nombre de produit(s) à exporter'),
                    'readonly' => 'readonly',
                    'name' => 'COCOTE_EXPORTED_PRODUCT_NUMBER'
                ),

                array(
                    'type' => 'text',
                    'label' => $this->l('Shop ID'),
                    'name' => 'COCOTE_EXPORTED_SHOP_ID',
                    'size' => 100,
                    'hint' => 'Retrouvez votre identifiant depuis votre compte marchand Cocote',
                    'required' => true,
                ),

                array(
                    'type' => 'text',
                    'label' => $this->l('Private Key'),
                    'name' => 'COCOTE_EXPORTED_PRIVATE_KEY',
                    'size' => 100,
                    'hint' => 'Retrouvez votre clé privée depuis votre compte marchand Cocote',
                    'required' => true,
                ),

                array(
                    'type' => 'checkbox',
                    'label' => $this->l('Exportez uniquemment les produits en stock'),
                    'name' => 'COCOTE_STATUS',
                    'values' => array(
                        'query' => array(
                            array(
                                'id' => 'STOCK',
                                'name' => $this->l(''),
                                'val' => '1',
                            ),
                        ),
                        'id' => 'id',
                        'name' => 'name'
                    )
                ),

                array(
                    'type' => 'text',
                    'style' => 'text-align: center',
                    'label' => $this->l('Lien vers le flux XML'),
                    'hint' => 'Votre flux sera réactualisé automatiquement chaque jour vers 3 heures (matin)',
                    'readonly' => 'readonly',
                    'name' => 'COCOTE_FLUX'
                ),
            ),

            'submit' => array(
                'title' => $this->l('Save'),
                'class' => 'btn btn-default pull-right'
            ),

            'buttons' => array(
                array(
                    'href' => DBTeam::generateCocoteXml(Configuration::get('COCOTE_STATUS_STOCK')),
                    'class' => '_blank pull-right',
                    'target' => '_blank',
                    'title' => $this->l('Export XML'),
                    'icon' => 'process-icon-export',
                ),
            ),
        );

        $helper = new HelperForm();

        // Module, token and currentIndex
        $helper->module = $this;
        $helper->name_controller = $this->name;
        $helper->token = Tools::getAdminTokenLite('AdminModules');
        $helper->currentIndex = AdminController::$currentIndex . '&configure=' . $this->name;

        // Language
        $helper->default_form_language = $default_lang;
        $helper->allow_employee_form_lang = $default_lang;

        // Title and toolbar
        $helper->title = $this->displayName;
        $helper->show_toolbar = true;        // false -> remove toolbar
        $helper->toolbar_scroll = true;      // yes - > Toolbar is always visible on the top of the screen.
        $helper->submit_action = 'submit' . $this->name;
        $helper->toolbar_btn = array(
            'save' =>
                array(
                    'desc' => $this->l('Save'),
                    'href' => AdminController::$currentIndex . '&configure=' . $this->name . '&save' . $this->name .
                        '&token=' . Tools::getAdminTokenLite('AdminModules'),
                ),
            'back' => array(
                'href' => AdminController::$currentIndex . '&token=' . Tools::getAdminTokenLite('AdminModules'),
                'desc' => $this->l('Back to list')
            )
        );

        $helper->fields_value['COCOTE_STATUS'] = DBTeam::checkConfigurationStatus();
        $helper->fields_value['COCOTE_EXPORTED_PRODUCT_NUMBER'] = DBTeam::checkHowManyProduct($this->stock);
        $helper->fields_value['COCOTE_EXPORTED_SHOP_ID'] = Configuration::get('COCOTE_EXPORTED_SHOP_ID');
        $helper->fields_value['COCOTE_EXPORTED_PRIVATE_KEY'] = Configuration::get('COCOTE_EXPORTED_PRIVATE_KEY');
        $helper->fields_value['COCOTE_FLUX'] = DBTeam::generateCocoteXml(Configuration::get('COCOTE_STATUS_STOCK'));

        $status_stock = Configuration::get('COCOTE_STATUS_STOCK');
        if(Configuration::get('COCOTE_STATUS_STOCK') == ''){
            $status_stock = $this->stock;
        }
        $helper->fields_value['COCOTE_STATUS_STOCK'] = $status_stock;

        return $helper->generateForm($fields_form);
    }

    /**
     * Hook d'exécution de la crontab
     */
    public function hookActionCronJob() {

        //Exemple basique on va créer un fichier de log et insérer un contenu dès que la tache cron est appellée
        if (!file_exists(_PS_MODULE_DIR_ . $this->name . DIRECTORY_SEPARATOR . 'log')) {
            mkdir(_PS_MODULE_DIR_ . $this->name . DIRECTORY_SEPARATOR . 'log');
        }
        $fp = fopen(dirname(__FILE__) . DIRECTORY_SEPARATOR .'log'. DIRECTORY_SEPARATOR .'log_' . date('Ymd') . '.log', 'a+');
        fputs($fp, 'CALLED at ' . date('Y-m-d H:i:s') .' ');


        //Exemple plus avancé, on souhaite effectuer des taches différentes en fonction de l'heure
        $hour = date('H');

        switch ($hour) {
            case 03:
                //Lancement des actions du matin
                fputs($fp, 'generateCocoteXml()'."\n");
                DBTeam::generateCocoteXml($this->stock);
                break;

            case 18:
                //Lancement des actions du soir
                break;

            default:
                //Action par défaut
                break;
        }

        fclose($fp);
    }

    /**
     * Information sur la fréquence des taches cron du module
     * Granularité maximume à l'heure
     */
    public function getCronFrequency() {
        return array(
            'hour' => 3, // -1 equivalent à * en cron normal
            'day' => -1,
            'month' => -1,
            'day_of_week' => -1
        );
    }

    public function hookDisplayHeader()
    {
        // script de suivi en JS
        $rowCocoteExport = DBTeam::checkCocoteExport();
        if(count($rowCocoteExport)>0) {
            $this->context->smarty->assign(
                array(
                    'mSiteId' => $rowCocoteExport['shop_id'],
                )
            );
        }
        return $this->display(__FILE__, 'views/templates/front/analytics.tpl');
    }
}