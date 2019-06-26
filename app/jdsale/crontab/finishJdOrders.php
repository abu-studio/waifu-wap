<?php
class sync {

    public function init(){
        $this->_do_sync();
    }

    private function _do_sync(){		
		ini_set('date.timezone','Asia/Shanghai');       
        kernel::single('jdsale_autofinshjdorders')->orders();           
        kernel::single('jdsale_autofinshjdbookorders')->orders();
    }
}
require_once(dirname(__FILE__) . '/../lib/config/config.php');
$sync = new sync();
$sync->init();

?>