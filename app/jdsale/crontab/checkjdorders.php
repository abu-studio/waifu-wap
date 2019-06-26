<?php
class sync {

    public function init(){
        $this->_do_sync();
    }

    private function _do_sync(){
		ini_set('date.timezone','Asia/Shanghai');
        error_log("测试时间 ".date('Y-m-d H:i:s')."\n\r",3,ROOT_DIR.'/duizhang.txt');
        kernel::single('jdsale_checkjdorders')->checkOrders();
        kernel::single('jdsale_checkjdbookorders')->checkOrders();
    }
}
require_once(dirname(__FILE__) . '/../lib/config/config.php');
$sync = new sync();
$sync->init();

?>