<?php

class AdminCocoteFeedConfigureController extends AdminController {

    public function __construct() {
        $module_name = "cocotefeed";
        Tools::redirectAdmin('index.php?controller=AdminModules&configure=' . $module_name . '&token=' . Tools::getAdminTokenLite('AdminModules'));
    }

}