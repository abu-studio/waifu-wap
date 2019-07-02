<?php
 

class jdsale_ctl_wap_product extends jdsale_frontpage{

    var $_call = 'call';
    var $type = 'goods';
    var $seoTag = array('shopname','brand','goods_name','goods_cat','goods_intro','goods_brief','brand_kw','goods_kw','goods_price','update_time','goods_bn');
    function __construct($app){
	
        parent::__construct($app);
        $shopname = app::get('b2c')->getConf('system.shopname');
        kernel::single('base_session')->start();
	
        if(isset($shopname)){
            $this->title = app::get('b2c')->_('京东商品页').'_'.$shopname;
            $this->keywords = app::get('b2c')->_('京东商品页').'_'.$shopname;
            $this->description = app::get('b2c')->_('京东商品页').'_'.$shopname;
        }

        if(!empty($_SESSION['sfsc']['vcat'])){
            $this->set_tmpl('hfjdproduct');
        }else{
            $this->set_tmpl("jdproduct");
        }
    }


    public function index() {

		 $objProduct = app::get('b2c')->model('products');
		 $objGoods = app::get('b2c')->model('goods');
        //获取参数
        $_getParams = $this->_request->get_params();

        $gid = $_getParams[0];
        $this->id = $gid;

        if(!$this->id){
            $this->splash('failed', 'back', app::get('b2c')->_('无效商品！<br>可能是商品未上架'));
        }else{
            $rs = $objGoods->dump(array('goods_id'=>$this->id),'goods_id');
            if(!$rs || empty($rs)){
                $this->splash('failed', 'back', app::get('b2c')->_('无效商品！<br>可能是商品未上架'));
            }
        }

        if (isset($_getParams[1])){
            $vcat_id = $_getParams[1];
            if (intval($vcat_id) > 0) {
                $this->set_tmpl("hfjdproduct");
                $virCatObj                = app::get('b2c')->model('goods_hfvirtual_cat');
                $vcatList                 = $virCatObj->getList('cat_id,cat_path,virtual_cat_id,filter,virtual_cat_name as cat_name,goods_type,url',
                    array('virtual_cat_id' => intval($vcat_id)));
                $vcat                     = $vcatList[0];
                $_SESSION['sfsc']['vcat'] = $vcat;
            }else{
                if (!empty($_SESSION['sfsc']['vcat'])){
                    unset($_SESSION['sfsc']['vcat']);
                    $this->set_tmpl("jdproduct");
                }
            }
        }

        if(!empty($_SESSION['sfsc']['vcat'])){
            $GLOBALS['runtime']['jdpath'] = $objGoods->jdgetHfPath($gid,'');
        }else{
            $GLOBALS['runtime']['jdpath'] = $objGoods->jdgetPath($gid,'');
        }

        $GLOBALS['runtime']['goods_id'] = $gid;

        //当前登陆用户信息
        $siteMember = $this->get_current_member();
        //当前登陆用户等级
        $this->site_member_lv_id = $siteMember['member_lv'];
        $this->pagedata['this_member_lv_id'] = $this->site_member_lv_id;

        if($this->site_member_lv_id){
            $member_lv_object = kernel::single("b2c_mdl_member_lv");
            $member_lv_data = $member_lv_object->dump(array('member_lv_id'=>$this->site_member_lv_id),"*");
            $this->pagedata['this_member_lv_name'] = $member_lv_data['name'];
            if($this->site_member_lv_id == 1){
                $this->pagedata['this_member_lv_pic'] = kernel::base_url().'/themes/simple/images/member_lv01.png';
            }elseif($this->site_member_lv_id == 2){
                $this->pagedata['this_member_lv_pic'] = kernel::base_url().'/themes/simple/images/member_lv02.png';
            }elseif($this->site_member_lv_id == 3){
                $this->pagedata['this_member_lv_pic'] = kernel::base_url().'/themes/simple/images/member_lv03.png';
            }else{
                $this->pagedata['this_member_lv_pic'] = kernel::base_url().'/themes/simple/images/member_lv01.png';
            }
        }
		
        $aGoods_list = $objGoods->getList("store_id,goods_state,buy_m_count,fav_count,freight_bear,comments_count,avg_point,goods_id,name,bn,price,cost,mktprice,marketable,store,store_freeze,notify_num,score,weight,unit,brief,image_default_id,udfimg,thumbnail_pic,small_pic,big_pic,min_buy,package_scale,package_unit,package_use,score_setting,nostore_sell,goods_setting,disabled,spec_desc,adjunct,brand_id,cat_id,seo_info,act_type,intro,wareQD,param",array('goods_id'=>$gid,'store_id|than'=>0));
        
        //获取详细的商品数据
        $list2dump = kernel::single("b2c_goods_list2dump");
        $aGoods = $list2dump->get_jdgoods($aGoods_list[0],$this->site_member_lv_id);
		
		$aGoods['buy_m_count'] = $aGoods_list[0]['buy_m_count'];
		$aGoods['current_price'] = $aGoods['price'];
		
        //lpc 获取类型（goods、book）
        $jdGoods = app::get('b2c')->model('goods')->dump(array('bn|tequal'=>$aGoods['bn']),'goods_kind');
        $jdgoodsKind = "normal";
        if ($jdGoods['goods_kind'] == "jdbook")
            $jdgoodsKind = "book";

		//获取好评度
		$rated = kernel::single('jdsale_api_goods')->queryProductComment(array('sku'=>$aGoods['bn']),$jdgoodsKind);
		$aGoods['rated'] = $rated['result'][0]['averageScore'];
		
		//获取默认地址数据
		$region_obj = app::get('jdsale')->model('regions');
		$Rdata = $region_obj->getRow('*',array('local_name'=>'上海'));
		$provinceDate = $region_obj->getList('*',array('region_grade'=>1));
		$this->pagedata['regionDate'] = $Rdata;
		$this->pagedata['provinceDate'] = $provinceDate;
		
		$this->pagedata['async_request_list'] = json_encode($this->get_body_async_url($aGoods));
		$this->pagedata['seelist'] = kernel::single("b2c_goods_description_see2see")->jdshowlist($gid,$this->site_member_lv_id);
		
		
		$tTitle=$aGoods['name'];
        $this->title = $tTitle;
		
		$this->pagedata['goods'] = $aGoods;
		
		
        //检查买家是否是店家
        $checkSeller = kernel::service('business_check_goods_isMy');
        if($checkSeller){
            if($checkSeller->check_isSeller($msg)){
                $this->pagedata['isSeller'] = 'true';
            }else{
                $this->pagedata['isSeller'] = 'false';
            }
        }

        $this->page('site/product/index.html');
    }
	
	
	
	private function get_body_async_url($aGoods) {
        foreach( kernel::servicelist("b2c_product_index_async") as $object ) {
            if( !$object ) continue;
            $index = null;
            if( !method_exists($object,'getAsyncInfo') ) {
                continue;
            }

            if( method_exists($object,'get_order') )
                $index = $object->get_order();

            while(true) {
                if( !isset($list[$index]) ) break;
                $index++;
            }

            $asyncinfo = $object->getAsyncInfo($aGoods);
            if(!$asyncinfo) continue;
            $list[key($asyncinfo)] = ($asyncinfo[key($asyncinfo)]);

        }
        krsort($list);
        return $list;
    }

    //lpc 判断京东商品类型
    public function jdGoodsType($param)
    {
        $jdGoods = app::get('b2c')->model('goods')->dump(array('bn|tequal'=>$param),"goods_kind");
        if ($jdGoods['goods_kind']=="jdbook") {
            return "book";
        }
        return "normal";
    }
	
	//判断是否有库存
	function getStore(){
		$num = $_POST['num']?$_POST['num']:1;
		$params=array(
		  "skuNums"=>array(
			   array('skuId'=>$_POST['bn'],'num'=>$num),
		  ),
		 'area'=>$_POST['region']
	   );
       //lpc
       $jdgoodsKind = $this->jdGoodsType($_POST['bn']);
	   $result = kernel::single('jdsale_api_area')->getFororder($params,$jdgoodsKind);
	  
	   echo $result['result'][0]['stockStateDesc'];
	}


	function viewpic($goodsid, $selected='def'){
        $objGoods = app::get('b2c')->model('goods');
        $aGoods = $objGoods->dump($goodsid,'name');
        $this->pagedata['goods_name'] = urlencode(htmlspecialchars($aGoods['name'],ENT_QUOTES));
        $this->pagedata['goods_name_show'] = $aGoods['name'];
        $this->pagedata['company_name'] = str_replace("'","&apos;",htmlspecialchars($this->app->getConf('system.shopname')));
		$image = app::get("jdsale")->model("image");

        $image_data = $image->getList("goods_id,image_path",array("goods_id"=>intval($goodsid)),0,-1,"order_sort asc");
        foreach($image_data as $img_k=>$img_v)
        {
            $json_image[] = "'".jdsale_goods_import::$image_url_max.$img_v['image_path']."'";
        }

		if(count($json_image>0)){
            $this->pagedata['json_image'] = implode(',',$json_image);
        }
        
        $this->pagedata['image_file'] = $json_image;
        $this->pagedata['image_file_total'] = count($image_data);
        if(count($json_image>0)){
            $this->pagedata['json_image'] = implode(',',$json_image);
        }
        $this->pagedata['goods_id'] = $goodsid;
        $this->page('site/product/viewpic.html',true);

    }
}
