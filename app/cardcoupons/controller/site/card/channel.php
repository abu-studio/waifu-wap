<?php
class cardcoupons_ctl_site_card_channel extends site_controller{
	function __construct($app){
        parent::__construct($app);
		$this->pagedata['res_url']=$this->app->res_url;
    }
    function index(){
		$this->set_tmpl("card_channel");
		$this->page("");
    }
}