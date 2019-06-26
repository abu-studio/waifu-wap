<?php

 

class jdbookconsole_mdl_jdorders extends dbeav_model{
    /**
	 * 构造方法
	 * @param object model相应app的对象
	 * @return null
	 */
	var $defaultOrder = array('createtime','DESC');

    public function __construct($app){
        $app = app::get('jdsale');
        parent::__construct($app);
        $this->use_meta();
    }
	

	/**
     * filter字段显示修改
     * @params string 字段的值
     * @return string 修改后的字段的值
     */
	 function modifier_order_id($row){
		$_html = '<a href="index.php?app=jdbookconsole&ctl=admin_order&act=index&action=detail&id='.$row.'&singlepage=true&target=_blank" target="_blank">'.$row.'</a>';
		return $_html;
	 }
	
	
	 function modifier_jd_order_price($row){
		return '￥'.$row;
	 }
	
}
