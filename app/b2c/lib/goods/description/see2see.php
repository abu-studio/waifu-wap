<?php
class b2c_goods_description_see2see{
    function __construct( &$app ) {
        $this->app = $app;
    }

    function show( $gid, &$aGoods=null, $custom_view=""){
        $render = $this->app->render();
        //if( !$aGoods ){
            $objGoods = $this->app->model("goods");
            $aCat = array();
            $aStore = array();
            foreach($objGoods->getList('cat_id,store_id', array('goods_id'=>$gid)) as $rows){
                $aCat[] = $rows['cat_id'];
                $aStore[] = $rows['store_id'];
            }
            $aGoods = array();
            $agid = array();
            $filter = array('cat_id|in'=>$aCat,'store_id|in'=>$aStore,'goods_id|noequal'=>$gid,'marketable'=>'true','disabled'=>'false');
            foreach($objGoods->getList('goods_id,price,thumbnail_pic,image_default_id', $filter,0,10) as $rows){
                //if($gid == $rows['goods_id']) continue;
                $aGoods[$rows['goods_id']] = $rows;
                $agid[] = $rows['goods_id'];
            }
            $render->pagedata['goods']['count'] = $objGoods->count($filter);
            $objComments = $this->app->model("member_comments");
            $filter = array('type_id'=>$agid,'display'=>'true','disabled'=>'false','for_comment_id'=>0,'comments_type'=>'1');
            foreach($objComments->getList('addon,comment,author_id,author,type_id',$filter,0,5,'comment_id desc') as $rows){
                $rows['addon'] = unserialize($rows['addon']);
                $aGoods[$rows['type_id']]['comments'][] = $rows;
            }
        //}
        $imageDefault = app::get('image')->getConf('image.set');
        $render->pagedata['image_set'] = $imageDefault;
        $render->pagedata['defaultImage'] = $imageDefault['S']['default_image'];
        $render->pagedata['setting']['buytarget'] = $this->app->getConf('site.buy.target');
        //$siteMember = kernel::single('b2c_ctl_site_product')->get_current_member();
        if(!$siteMember['member_id']){
            $render->pagedata['login'] = 'nologin';
        }
        $render->pagedata['goods']['list'] = $aGoods;
        $render->pagedata['goods']['goods_id'] = $gid;
		$file = $custom_view?$custom_view:"site/product/description/see2see1.html";
        if($custom_view){
			return $render->fetch($file,'',true);
        }
        return $render->fetch($file);
    }
    function showlist( $gid){
        //$render = $this->app->render();
        $objGoods = $this->app->model("goods");
        $aCat = array();
        $aStore = array();
        foreach($objGoods->getList('cat_id,store_id', array('goods_id'=>$gid)) as $rows){
            $aCat[] = $rows['cat_id'];
            $aStore[] = $rows['store_id'];
        }
        $aGoods = array();
        $filter = array('cat_id|in'=>$aCat,'store_id|in'=>$aStore,'goods_id|noequal'=>$gid,'marketable'=>'true','disabled'=>'false');
        foreach($objGoods->getList('goods_id,price,thumbnail_pic,image_default_id', $filter,0,-1) as $rows){
            //if($gid == $rows['goods_id']) continue;
            $aGoods[$rows['goods_id']] = $rows;
        }
        return $aGoods;
    }
	//京东商品
	function jdshowlist($gid,$member_lv_id=null){
        $objGoods = app::get('b2c')->model("goods");
		if($member_lv_id){
			$member_lv = app::get('b2c')->model("member_lv");
			$memLv = $member_lv->getRow("jd_discount",array('member_lv_id'=>$member_lv_id));
		}
		$jd_discount = $memLv['jd_discount']?$memLv['jd_discount']:1;
        $aCat = array();
        foreach($objGoods->getList('jd_cat_id', array('goods_id'=>$gid)) as $rows){
            $aCat[] = $rows['jd_cat_id'];
        }
        $aGoods = array();
        $filter = array('jd_cat_id|in'=>$aCat,'goods_id|noequal'=>$gid,'disabled'=>'false','goods_kind'=>'jdgoods');
        foreach($objGoods->getList('goods_id,price,image_default_id', $filter,0,-1) as $rows){
            $aGoods[$rows['goods_id']] = $rows;
			$aGoods[$rows['goods_id']]['image_default_id'] = jdsale_goods_import::$image_url_n3.$rows['image_default_id'];
			$aGoods[$rows['goods_id']]['price'] = $rows['price']*$jd_discount;
        }
        return $aGoods;
    }
}

