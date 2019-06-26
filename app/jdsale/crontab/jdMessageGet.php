<?php
/**
 * Created by PhpStorm.
 * User: shaojun
 * Date: 2016/12/6
 * Time: 15:28
 */
class sync {
    public function init(){
        $this->_do_sync();
    }

    private function _do_sync(){
		ini_set('date.timezone','Asia/Shanghai');        
        kernel::single('jdsale_automessageget')->auto_getmessage();
    }
}
require_once(dirname(__FILE__) . '/../lib/config/config.php');
$sync = new sync();
$sync->init();

?>