<?php

class AdminCocoteFeedController extends ModuleAdminController
{
    public function init()
    {
        parent::init();
        $this->bootstrap = true;
    }

    public function initContent()
    {
        parent::initContent();
        $this->setTemplate('cocotefeed.tpl');
    }
}