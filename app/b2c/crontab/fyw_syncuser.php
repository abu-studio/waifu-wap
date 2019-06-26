<?php
class sync {

    public function init(){
        $this->_do_sync();
    }

    private function _do_sync(){
		ini_set('date.timezone','Asia/Shanghai');
		 error_log("fuyuanwai sync user: ".date('Y-m-d H:i:s')."\n\r",3,ROOT_DIR.'/fywsyncuser.txt');
        kernel::single('b2c_fuyuanwai_api_sync')->auto_sync_users();
    }
}
require_once(dirname(__FILE__) . '/../lib/config/config.php');
require_once(dirname(__FILE__) . '/../../../config/fuyuanwai.php');
$sync = new sync();
$sync->init();

?>