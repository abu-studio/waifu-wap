
<?php
/**
 * ShopEx licence
 *
 * @copyright  Copyright (c) 2005-2010 ShopEx Technologies Inc. (http://www.shopex.cn)
 * @license  http://ecos.shopex.cn/ ShopEx License
 */
class  cellphone_base_homepage_channel extends cellphone_cellphone{
    public function __construct($app){
        parent::__construct();
        $this->app = $app;
    }
//  获得频道商品列表
   function getlist(){
        $params = $this->params;
        $must_params = array(
            'channel_type'=>'频道页',
            
        );
        $this->check_params($must_params);
        $channel_name = '';
        $mdl_channeltype = app::get('cellphone')->model('channeltype');
        $channeltype = $mdl_channeltype->getList('type_id,type_name',array('type_id'=>$params['channel_type']));
        $channel_name = $channeltype[0]['type_name'];
        if($params['pagelimit']){
            $pagelimit = $params['pagelimit'];
        }else{
            $pagelimit = 8;
        }
        if($params['nPage']){
            $nPage = $params['nPage'];
        }else{
            $nPage = 1;
        }
        if($params['picSize']){
            $picSize = $params['picSize'];
        }else{
            $picSize = 'cs';
        }
        
        $data = array(
            'act_name'=>$channel_name,
            'object_type'=>'normal',
            'banner'=>null
            
        );
		$mdl_channel = $this->app->model('channel');
        $curtime = time();
        $filter = 
        array(
            'is_active'=>'true',
            'start_time|lthan'=>$curtime,
            'end_time|than'=>$curtime,
            'type_id'=>$params['channel_type']
            ); 
        $aData = $mdl_channel->getList('id,goods_id,d_order,type_id',$filter,$pagelimit*($nPage-1),$pagelimit,'d_order  ASC');
		if($aData){
            $mdl_goods = app::get('b2c')->model('goods');
            foreach($aData as $key=>&$val){
             $arr = $mdl_goods->getRow('name,price,mktprice,image_default_id,freight_bear',array('goods_id'=>intval($val['goods_id'])));
             $val['name'] = $arr['name'];
             $val['real_price'] = $arr['price'];
             $val['price'] = $arr['mktprice'];
             $val['freight_bear'] = $arr['freight_bear'];
             $val['image'] = $this->get_img_url($arr['image_default_id'],$picSize);
             $val['object_type'] = 'normal';
             $val['start_time'] = null;
             $val['end_time'] = null;
            

		    }
        }
        $data['gallery'] = $aData;
	    $this->send(true,$data,app::get('cellphone')->_('频道商品列表'));

   }
   /**
     *获取清仓商品列表
     *
     *
     */
    function getclearance(){

        $params = $this->params;
        
        if($params['pagelimit']){
            $pagelimit = $params['pagelimit'];
        }else{
            $pagelimit = 8;
        }
        if($params['nPage']){
            $nPage = $params['nPage'];
        }else{
            $nPage = 1;
        }
        if($params['picSize']){
            $picSize = $params['picSize'];
        }else{
            $picSize = 'cs';
        }
        $data = array(
            'act_name'=>'换季清仓',
            'object_type'=>'normal',
            'banner'=>null
            
        );
       
		$mdl_clearance = $this->app->model('clearance');
        $curtime = time();
        $filter = 
        array(
            'is_active'=>'true',
            'start_time|lthan'=>$curtime,
            'end_time|than'=>$curtime,
           
            ); 
        $aData = $mdl_clearance->getList('id,goods_id,d_order',$filter,$pagelimit*($nPage-1),$pagelimit,'d_order  ASC');
		if($aData){
            $mdl_goods = app::get('b2c')->model('goods');
            foreach($aData as $key=>&$val){
             $arr = $mdl_goods->getRow('name,price,mktprice,image_default_id,freight_bear',array('goods_id'=>intval($val['goods_id'])));
             $val['name'] = $arr['name'];
             $val['real_price'] = $arr['price'];
             $val['price'] = $arr['mktprice'];
             $val['freight_bear'] = $arr['freight_bear'];
             $val['image'] = $this->get_img_url($arr['image_default_id'],$picSize);
             $val['object_type'] = 'normal';
             $val['start_time'] = null;
             $val['end_time'] = null;
            

		    }
        }
        $data['gallery'] = $aData;
	    $this->send(true,$data,app::get('cellphone')->_('清仓商品列表'));

    
   
   }

    /**
      * 获取频道类型列表
      *
      */
    function gettypelist(){
        $params = $this->params;
       
        if($params['picSize']){
          $picSize = $params['picSize'];
        }else{
          $picSize = 'cs';
        }
        if($params['pagelimit']){
            $pagelimit=$params['pagelimit'];
        }else{
            $pagelimit = 4;
        }
        if($params['nPage']){
            $nPage=$params['nPage'];
        }else{
            $nPage = 1;
        }
        $channeltype = app::get('cellphone')->model('channeltype');
        $typedata = $channeltype->getList('type_id,type_name,image_id,d_order',array(), $pagelimit*($nPage-1),$pagelimit,'d_order asc');
        if(!empty($typedata)){
             foreach($typedata as $key=>&$val){
                $val['image_id'] = $this->get_img_url($val['image_id'],$picSize);
                //$val['goods'] = $this->getgoodslist($val['columntype_id'],$params['picSize']);
             }
        }
        if(!$typedata){
            $this->send(true,null,app::get('cellphone')->_('没有数据'));
        }else{
            $this->send(true,$typedata,app::get('cellphone')->_('频道类型'));
        }
    }
   
}