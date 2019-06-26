<?php
class auto_settlement {
    public function init(){
        $this->_do_exec();
    }

    private function _do_exec(){
        echo "自动周期结算开始...\n";
        kernel::single('business_settlement_autosettlement')->autosettlement();
        echo '自动周期结算结束...';
    }
}
$root_dir = realpath(dirname(__FILE__).'/../../../');
require_once($root_dir . '/config/config.php');
define('APP_DIR',ROOT_DIR."/app/");

require_once(APP_DIR.'/base/kernel.php');
if(!kernel::register_autoload()){
    require(APP_DIR.'/base/autoload.php');
}

require_once(APP_DIR.'/base/defined.php');

cachemgr::init(false);
set_time_limit(0);
header("cache-control:no-cache,must-revalidate");
$violation = new auto_settlement();
$violation->init();

