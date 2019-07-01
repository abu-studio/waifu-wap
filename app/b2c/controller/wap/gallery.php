<?php
/**
ShopEx licence
*
* @copyright  Copyright (c) 2005-2013 ShopEx Technologies Inc. (http://www.shopex.cn)
* @license  http://ecos.shopex.cn/ ShopEx License
 */

class b2c_ctl_wap_gallery extends wap_frontpage{

    var $_call = 'index';
    var $type='goodsCat';
    var $seoTag=array('shopname','search_numgoods_amount','goods_cat','goods_cat_p','goods_type','brand','sort_path');

    public function __construct(&$app) {
        parent::__construct($app);
        $shopname = $app->getConf('system.shopname');
        $this->shopname = $shopname;
        kernel::single('base_session')->start();
        if ( isset($shopname) )
        {
            $this->title = app::get('b2c')->_('商品分类').'_'.$shopname;
            $this->keywords = app::get('b2c')->_('商品分类_').'_'.$shopname;
            $this->description = app::get('b2c')->_('商品分类_').'_'.$shopname;
        }
        $this->_response->set_header('Cache-Control', 'no-store, no-cache');
        $this->pagedata['request_url'] = $this->gen_url( array('app'=>'b2c','ctl'=>'wap_product','act'=>'get_goods_spec') );
        $this->mdl_store=app::get('business')->model('storemanger');
        $this->db=kernel::database();
        $this->mdl_brand=$this->app->model('brand');
        $this->arr_cat=array();
    }


    public function cat($cat_id = 0)
    {
        $objCat = app::get('b2c')->model('goods_cat');
        $catlist = $objCat->getList('*', array('parent_id' => $cat_id), $offset=0, $limit=-1, 'p_order ASC');

        $this->pagedata['catlist'] = $catlist;

        $this->pagedata['cur_cat'] = $objCat->getRow('*',array('cat_id'=>$cat_id));
        $this->pagedata['pre_cat'] = $objCat->getRow('cat_id, cat_name',array('cat_id'=>$this->pagedata['cur_cat']['parent_id']));
        $this->page('wap/gallery/cat.html');
    }

    public function index($cat_id='',$urlFilter=null,$orderBy=0,$page=1,$virtual_cat_id=null,$showtype=null) {
        $this->pagedata['commentListListnum'] = $this->app->getConf('gallery.comment.time');
        $request_params = $this->_request->get_params();
        $request_params = utils::filter_input_XSS($request_params);
        $urlFilter = utils::filter_input_XSS($urlFilter);
        //频道页判断，如果是第一级分类并且设置了模板则为频道页。
        //如果是虚拟分类跳转则不判断是不是频道页。
        if( empty($cat_type) )
        {
            $is_chanel=$this->is_channel($cat_id);
            if($is_chanel){
                return;
            }
        }else{
            //$cat_id='';
        }
        if(in_array('gallery-index', $this->weixin_share_page)){
            $this->pagedata['from_weixin'] = $this->from_weixin;
            $this->pagedata['weixin']['appid'] = $this->weixin_a_appid;
            $this->pagedata['weixin']['imgUrl'] = base_storager::image_path(app::get('weixin')->getConf('weixin_basic_setting.weixin_logo'));
            $this->pagedata['weixin']['linelink'] = app::get('wap')->router()->gen_url(array('app'=>'b2c','ctl'=>'wap_gallery','act'=>'index','arg0'=>$cat_id, 'full'=>1));
            $this->pagedata['weixin']['shareTitle'] = $this->title;
            $this->pagedata['weixin']['descContent'] = $this->description;

            //微信内置js调用
            $wechat = kernel::single('weixin_wechat');
            $signPackage = $wechat->getSignPackage();
            $this->pagedata['signPackage'] = $signPackage;
            //end
        }

        $this->set_tmpl('gallery');

        $this->page('wap/gallery/index.html');
    }


    /**
     * @Author: panbiao <panbiaophp@163.com>
     * @DateTime: 2019-06-30 23:38
     * @Desc: 频道页
     * @Parms: $cat_id
     * @Return:
     */

    function is_channel($cat_id='') {

        //如果为空则不是频道页
        if(empty($cat_id)){
            return false;
        }
        $cat_id=explode(',',$cat_id);
        //如果多ID也不是频道页
        if(count($cat_id)>1){
            return false;
        }
        $productCat = &$this->app->model('goods_cat');
        $cat=$productCat->dump($cat_id[0]);

        if(empty($cat)){
            return false;
        }
        //如果不是顶级分类也不是频道页
        if($cat['parent_id']){
            return false;
        }
        //如果如果不可用则也不是频道页
        if($cat['disabled']=='true'){
            return false;
        }
        if($cat['gallery_setting']['gallery_template'])
        {
            $this->set_tmpl_file($cat['gallery_setting']['gallery_template']);
        }else{
            $theme=kernel::single('site_theme_base')->get_default();
            $obj_themes_tmpl = app::get('site')->model('themes_tmpl');
            $tmpl_rows = $obj_themes_tmpl->getList('tmpl_path',array('theme'=>$theme,'tmpl_type'=>'gallery','type'=>$cat['cat_id']));
            if(empty($tmpl_rows)){
                return false;
            }
            $this->set_tmpl_file($tmpl_rows[0]['tmpl_path']);
        }

        $this->set_tmpl('gallery');
        $seo=$cat['seo_info'];
        if(!empty($seo['seo_title'])|| !empty($seo['seo_keywords'])|| !empty($seo['seo_description'])){
            $this->title = $seo['seo_title'];
            $this->keywords = $seo['seo_keywords'];
            $this->description = $seo['seo_description'];
        }
        $this->page('wap/gallery/empty.html');
        return true;
    }






    /*
     * 面包屑数据设置
     *
     * @params int $cat_id 分类ID
     * */
    private function runtime_path($cat_id,$search_keywords,$virtual_cat_id){
        //set 面包屑
        $global_runtime_path = "";
        if($search_keywords){//搜索的时候
            $GLOBALS['runtime']['search_key'] = $search_keywords;
            $keywords = app::get('b2c')->_('搜索').'"'.$search_keywords.'"';
            $url = app::get('wap')->router()->gen_url(array('app'=>'b2c','ctl'=>'wap_gallery','act'=>'index')).'?scontent=n,'.$search_keywords;
            $global_runtime_path = array(
                array('type'=>'goodsCat','title'=>app::get('wap')->_('首页'),'link'=>kernel::base_url(1)),
                array('type'=>'goodsCat','title'=>$keywords,'link'=>$url),
            );
            if($cat_id){
                $global_runtime_path[] = array('type'=>'goodsCat','title'=>$this->goods_cat,'link'=>kernel::base_url(1));
            }
        }elseif($virtual_cat_id){//虚拟分类的时候
            $virtualCat = app::get('b2c')->model('goods_virtual_cat')->getList('virtual_cat_name,url',array('virtual_cat_id'=>intval($virtual_cat_id)));
            $global_runtime_path = array(
                array('type'=>'goodsCat','title'=>app::get('wap')->_('首页'),'link'=>kernel::base_url(1)),
                array('type'=>'goodsCat','title'=>$virtualCat[0]['virtual_cat_name'],'link'=>$virtualCat[0]['url']),
            );
            if($cat_id){
                $global_runtime_path[] = array('type'=>'goodsCat','title'=>$this->goods_cat,'link'=>kernel::base_url(1));
            }
        }else{
            $catModel = $this->app->model('goods_cat');
            if($cat_id && !cachemgr::get('global_runtime_path' . $cat_id, $global_runtime_path)){
                cachemgr::co_start();
                $global_runtime_path = $catModel->getPath($cat_id,'');
                cachemgr::set('global_runtime_path', $global_runtime_path, cachemgr::co_end());
            }
        }
        return $global_runtime_path;
    }

    /*
     * 设置列表页SEO
     *
     * @params array $seo_info
     * */
    private function _set_seo($seo_info){
        if(!empty($seo_info['seo_title']) || !empty($seo_info['seo_keywords']) || !empty($seo_info['seo_description'])){
            $this->title = $seo_info['seo_title'];
            $this->keywords = $seo_info['seo_keywords'];
            $this->description = $seo_info['seo_description'];
        }else{
            $this->setSeo('wap_gallery','index',$this->prepareSeoData($this->pagedata));
        }
    }

    /*
     * 返回seo设置的数据
     *
     * */
    private function prepareSeoData($data){
        $catpath = $GLOBALS['runtime']['path'];
        if(is_array($catpath)){
            $cat_path = $catpath[0]['title'];
            unset($catpath[0]);
            foreach($catpath as $ck=>$cv){
                $cat_path .= ','.$cv['title'];
            }
        }
        return array(
            'shop_name'=>$this->shopname,
            'search_num'=>$data['total'],
            'goods_cat'=>$this->goods_cat?$this->goods_cat:'',
            'goods_cat_p'=>$cat_path,
            'goods_type'=>$this->goods_type ? $this->goods_type:'',
        );
    }

    /*
     * 根据分类ID提供筛选条件，并且返回已选择的条件数据
     *
     * @params int $cat_id 分类ID
     * @params array $filter 已选择的条件
     * */
    private function screen($cat_id,$filter){
        if ( empty($cat_id) ) {
            $screen = array();
        }
        $screen['cat_id'] = $cat_id;
        $cat_id = $cat_id ?  $cat_id : $this->pagedata['show_cat_id'];
        //搜索时的分类
        if(!$screen['cat_id'] && count($this->pagedata['catArr']) > 1){
            $searchCat = app::get('b2c')->model('goods_cat')->getList('cat_id,cat_name',array('cat_id'=>$this->pagedata['catArr']));
            $i=0;
            foreach($this->catCount as $catid=>$num){
                $sort[$catid] = $i;
                if($i == 9) break;
                $i++;
            }
            foreach($searchCat as $row){
                $screen['search_cat'][$sort[$row['cat_id']]] = $row;
            }
            ksort($screen['search_cat']);
        }

        //商品子分类
        $show_cat = $this->app->getConf('site.cat.select');
        if($show_cat == 'true'){
            $sCatData = app::get('b2c')->model('goods_cat')->getList('cat_id,cat_name',array('parent_id'=>$cat_id));
            $screen['cat'] = $sCatData;
        }

        cachemgr::co_start();
        if(!cachemgr::get("goodsObjectCat".$cat_id, $catInfo)){
            $goodsInfoCat = app::get("b2c")->model("goods_cat")->getList('*',array('cat_id'=>$cat_id) );
            $catInfo = $goodsInfoCat[0];
            cachemgr::set("goodsObjectCat".$cat_id, $catInfo, cachemgr::co_end());
        }
        $this->goods_cat = $catInfo['cat_name'];//seo

        cachemgr::co_start();
        if(!cachemgr::get("goodsObjectType".$catInfo['type_id'], $typeInfo)){
            $typeInfo = app::get("b2c")->model("goods_type")->dump(array('type_id'=>$catInfo['type_id']) );
            cachemgr::set("goodsObjectType".$catInfo['type_id'], $typeInfo, cachemgr::co_end());
        }
        $this->goods_type = $typeInfo['name'];//seo

        if($typeInfo['price'] && $filter['price'][0]){
            $active_filter['price']['title'] = $this->app->_('价格');
            $active_filter['price']['label'] = 'price';
            $active_filter['price']['options'][0]['data'] =  $filter['price'][0];
            foreach($typeInfo['price'] as $key=>$price){
                $price_filter = implode('~',$price);
                if($filter['price'][0] == $price_filter){
                    $typeInfo['price'][$key]['active'] = 'active';
                    $active_arr['price'] = 'active';
                }
                $active_filter['price']['options'][0]['name'] = $filter['price'][0];
            }
        }
        $screen['price'] = $typeInfo['price'];

        if ( $typeInfo['setting']['use_brand'] ){
            $type_brand = app::get('b2c')->model('type_brand')->getList('brand_id',array('type_id'=>$catInfo['type_id']));
            if ( $type_brand ) {
                foreach ( $type_brand as $brand_k=>$brand_row ) {
                    $brand_ids[$brand_k] = $brand_row['brand_id'];
                }
            }
            $brands = app::get('b2c')->model('brand')->getList('brand_id,brand_name',array('brand_id'=>$brand_ids,'disabled'=>'false'));
            //是否已选择
            foreach($brands as $b_k=>$row){
                if(in_array($row['brand_id'],$filter['brand_id'])){
                    $brands[$b_k]['active'] = 'active';
                    $active_arr['brand'] = 'active';
                    $active_filter['brand']['title'] = $this->app->_('品牌');;
                    $active_filter['brand']['label'] = 'brand_id';
                    $active_filter['brand']['options'][$row['brand_id']]['data'] =  $row['brand_id'];
                    $active_filter['brand']['options'][$row['brand_id']]['name'] = $row['brand_name'];
                }
            }
            $screen['brand'] = $brands;
        }

        //扩展属性
        if ( $typeInfo['setting']['use_props'] && $typeInfo['props'] ){
            foreach ( $typeInfo['props'] as $p_k => $p_v){
                if ( $p_v['search'] != 'disabled' ) {
                    $props[$p_k]['name'] = $p_v['name'];
                    $props[$p_k]['goods_p'] = $p_v['goods_p'];
                    $props[$p_k]['type'] = $p_v['type'];
                    $props[$p_k]['search'] = $p_v['search'];
                    $props[$p_k]['show'] = $p_v['show'];
                    $propsActive = array();
                    if($p_v['options']){
                        foreach($p_v['options'] as $propItemKey=>$propItemValue){
                            $activeKey = 'p_'.$p_v['goods_p'];
                            if($filter[$activeKey] && in_array($propItemKey,$filter[$activeKey])){
                                $active_filter[$activeKey]['title'] = $p_v['name'];
                                $active_filter[$activeKey]['label'] = $activeKey;
                                $active_filter[$activeKey]['options'][$propItemKey]['data'] =  $propItemKey;
                                $active_filter[$activeKey]['options'][$propItemKey]['name'] = $propItemValue;
                                $propsActive[$propItemKey] = 'active';
                            }
                        }
                    }
                    $props[$p_k]['options'] = $p_v['options'];
                    $props[$p_k]['active'] = $propsActive;
                }
            }

            $screen['props'] = $props;
        }

        //规格
        $gType = $this->app->model('goods_type');
        $SpecList = $gType->getSpec($catInfo['type_id'],1);//获取关联的规格
        if($SpecList){
            foreach($SpecList as $speck=>$spec_value){
                if($spec_value['spec_value']){
                    foreach($spec_value['spec_value'] as $specKey=>$SpecValue){
                        $activeKey = 's_'.$speck;
                        if($filter[$activeKey] && in_array($specKey,$filter[$activeKey])){
                            $active_filter[$activeKey]['title'] = $spec_value['name'];
                            $active_filter[$activeKey]['label'] = $activeKey;
                            $active_filter[$activeKey]['options'][$specKey]['data'] =  $specKey;
                            $active_filter[$activeKey]['options'][$specKey]['name'] = $SpecValue['spec_value'];
                            $specActive[$specKey] = 'active';
                        }
                    }
                }
                $SpecList[$speck]['active'] = $specActive;
            }
        }
        $screen['spec'] = $SpecList;

        //排序
        $orderBy = $this->app->model('goods')->orderBy();
        $screen['orderBy'] = $orderBy;

        //标签
        $tagFilter['app_id'][] = 'b2c';
        $giftAppActive = app::get('gift')->is_actived();
        if($giftAppActive){
            $tagFilter['app_id'][] = 'gift';
        }
        $progetcouponAppActive = app::get('progetcoupon')->is_actived();
        if($progetcouponAppActive){
            $progetcouponAppActive['app_id'][] = 'progetcoupon';
        }
        $tags = app::get('desktop')->model('tag')->getList('*',$tagFilter);
        if($filter['pTag']){
            $active_arr['pTags'] = 'active';
        }
        foreach($tags as $tag_key=>$tag_row){
            if($tag_row['tag_type'] == 'goods'){//商品标签
                if(in_array($tag_row['tag_id'],$filter['gTag'])){
                    $screen['tags']['goods'][$tag_key]['active'] = 'checked';
                }
                $screen['tags']['goods'][$tag_key]['tag_id'] = $tag_row['tag_id'];
                $screen['tags']['goods'][$tag_key]['tag_name'] = $tag_row['tag_name'];
            }elseif($tag_row['tag_type'] == 'promotion'){//促销标签
                if(in_array($tag_row['tag_id'],$filter['pTag'])){
                    $screen['tags']['promotion'][$tag_key]['active'] = 'active';
                    $active_filter['pTag']['title'] = $this->app->_('促销商品');;
                    $active_filter['pTag']['label'] = 'pTag';
                    $active_filter['pTag']['options'][$tag_row['tag_id']]['data'] =  $tag_row['tag_id'];
                    $active_filter['pTag']['options'][$tag_row['tag_id']]['name'] = $tag_row['tag_name'];
                }
                $screen['tags']['promotion'][$tag_key]['tag_id'] = $tag_row['tag_id'];
                $screen['tags']['promotion'][$tag_key]['tag_name'] = $tag_row['tag_name'];
            }
        }
        $this->pagedata['active_arr'] = $active_arr;
        $return['screen'] = $screen;
        $return['active_filter'] = $active_filter;
        $return['seo_info'] = $catInfo['seo_info'];
        return $return;
    }

    /*
     * 前台筛选商品ajax调用
     * */
    public function ajax_get_goods(){
        $tmp_params = $this->filter_decode($_POST);
        $params = $tmp_params['filter'];
        $orderby = $tmp_params['orderby'];
        $showtype = $tmp_params['showtype'];
        $page = $tmp_params['page'] ? $tmp_params['page'] : 1;
        $goodsData = $this->get_goods($params,$page,$orderby);
       // if($goodsData){
            $this->pagedata['goodsData'] = $goodsData;
            $view = 'wap/gallery/type/'.$showtype.'.html';
            echo $this->fetch($view);
        //}else{
            //后台站点设置搜索为空页面
        //    echo app::get('site')->getConf('errorpage.search');
        //}
    }

    /*
     * 返回搜索条件
     *
     * @params array $params 已有条件
     * @params int   $cat_id 分类ID
     * @params nit   $virtual_cat_id 虚拟分类ID
     * @return array
     */
    public function filter_decode($params=null,$cat_id,$virtual_cat_id=null){
        //获取cookie中的条件
        if(!$params){
            $cookie_filter = $_COOKIE['S']['GALLERY']['FILTER'];
            if($cookie_filter){
                $tmp_params = explode('&',$cookie_filter);
                foreach($tmp_params as $k=>$v){
                    $arrfilter = explode('=',$v);
                    $f_k = str_replace('[]','',$arrfilter[0]);
                    if($f_k == 'cat_id' || $f_k == 'orderBy' || $f_k == 'showtype' || $f_k == 'is_store' || $f_k == 'page'){
                        $params[$f_k] = $arrfilter[1];
                    }else{
                        $params[$f_k][] = $arrfilter[1];
                    }
                }
            }
            if($params['cat_id'] != $cat_id){
                unset($params);
                $this->set_cookie('S[GALLERY][FILTER]','nofilter');
            }
        }//end if
        if($virtual_cat_id){
            $params['virtual_cat_id']  = $virtual_cat_id;
        }
        $filter['params'] = $params;
        #分类
        $params['cat_id'] = $cat_id ? $cat_id : $params['cat_id'];
        if(!$params['cat_id']) unset($params['cat_id']);

        if($params['search_keywords'][0]){
            $params['orderBy'] = $params['orderBy'] ? $params['orderBy'] : 'view_count desc';
        }elseif($params['scontent']){
            $oSearch = $this->app->model('search');
            $decode = $oSearch->decode($params['scontent']);
            $params['search_keywords'] = $decode['search_keywords'];
            unset($params['scontent']);
        }

        if($params['search_keywords']){
            $params['search_keywords']= str_replace('%xia%','_',$params['search_keywords']);
        }

        #排序
        $orderby = $params['orderBy'];unset($params['orderBy']);

        #分页,页码
        $page= $params['page'];unset($params['page']);

        #商品显示方式
        if($params['showtype']){
            $showtype = $params['showtype'];unset($params['showtype']);
        }else{
            $showtype = app::get('b2c')->getConf('gallery.default_view');
        }

        $params['marketable'] = 'true';
        $params['is_buildexcerpts'] = 'true';
        $tmp_filter = $params;

        #价格区间筛选
        if($tmp_filter['price']){
            $tmp_filter['price'] = explode('~',$tmp_filter['price'][0]);
        }
        #商品标签筛选条件
        if($tmp_filter['gTag']){
            $tmp_filter['tag'] = $tmp_filter['gTag'];unset($tmp_filter['gTag']);
        }

        // if($tmp_filter['is_store'] == 'on' || app::get('b2c')->getConf('gallery.display.stock_goods') != 'true'){
        //     #是否有货
        //     $is_store = $params['is_store'];
        // }

        if(app::get('b2c')->getConf('gallery.display.stock_goods')!='true'){
            $tmp_filter['is_store']='on';
        }


        if($tmp_filter['virtual_cat_id']){
            $tmp_filter = $this->_merge_vcat_filter($tmp_filter['virtual_cat_id'],$tmp_filter);//合并虚拟分类条件
        }

        if($tmp_filter['pTag']){//促销优惠
            $time = time();
            $pTagGoods = app::get('b2c')->model('goods_promotion_ref')->getList('goods_id,apply_platform',array('tag_id'=>$tmp_filter['pTag'],'from_time|sthan'=>$time, 'to_time|bthan'=>$time,'status'=>'true'));
            if($pTagGoods){
                foreach($pTagGoods as $gids){
                    if(strpos($gids['apply_platform'],'2') === false){
                        continue;
                    }
                    $tmp_filter['goods_id'][] = $gids['goods_id'];
                }
            }
            if(empty($tmp_filter['goods_id']) ){
                $tmp_filter['goods_id'] = array(-1);
            }
            unset($tmp_filter['pTag']);
        }

        $filter['filter'] = $tmp_filter;
        $filter['orderby'] = $orderby;
        $filter['showtype'] = $showtype;
        $filter['is_store'] = $is_store;
        $filter['page'] = $page;
        return $filter;
    }

    /*
     * 将列表页中的搜索条件和虚拟分类条件合并
     *
     * @params int $virtual_cat_id 虚拟分类ID
     * @params array $filter  列表页搜索条件
     * */
    private function _merge_vcat_filter($virtual_cat_id,$filter){
        $virCatObj = $this->app->model('goods_virtual_cat');
        /** 颗粒缓存商品虚拟分类 **/
        if(!cachemgr::get('goods_virtual_cat_'.intval($virtual_cat_id), $vcat)){
            cachemgr::co_start();
            $vcat = $virCatObj->getList('cat_id,cat_path,virtual_cat_id,filter,virtual_cat_name as cat_name',array('virtual_cat_id'=>intval($virtual_cat_id)));
            cachemgr::set('goods_virtual_cat_'.intval($virtual_cat_id), $vcat, cachemgr::co_end());
        }
        $vcat = current( $vcat );
        parse_str($vcat['filter'],$vcatFilters);

        if($filter['cat_id'] && $vcatFilters['cat_id']){
            unset($vcatFilters['cat_id']);
        }
        $filter = array_merge_recursive($filter,$vcatFilters);
        return $filter;
    }

     /* 根据条件返回搜索到的商品
     * @params array $filter 搜索条件
     * @params int   $page   页码
     * @params string $orderby 排序
     * @return array
     * */
    public function get_goods($filter,$page=1,$orderby,&$pagedata=false){
        $goodsObject = kernel::single('b2c_goods_object');
        $goodsModel = app::get('b2c')->model('goods');
        $siteMember = $this->get_current_member();
        if( empty($siteMember['member_id']) ){
            $this->pagedata['login'] = 'nologin';
        }

        $page = $page ? $page : 1;
        $pageLimit = $this->app->getConf('gallery.display.listnum');
        $pageLimit = ($pageLimit ? $pageLimit : 20);
        $this->pagedata['pageLimit'] = $pageLimit;
        $goodsData = $goodsModel->getList('*',$filter,$pageLimit*($page-1),$pageLimit,$orderby,$total=false);
        if($goodsData && $total ===false){
           $total = $goodsModel->count($filter);
        }
        $this->pagedata['total'] =  $total;
        $pagetotal= $this->pagedata['total'] ? ceil($this->pagedata['total']/$pageLimit) : 1;
        $max_pagetotal = $this->app->getConf('gallery.display.pagenum');
        $max_pagetotal = $max_pagetotal ? $max_pagetotal : 100;
        $this->pagedata['pagetotal'] = $pagetotal > $max_pagetotal ? $max_pagetotal : $pagetotal;
        $this->pagedata['page'] = $page;
        //分页
        $this->pagedata['pager'] = array(
            'current'=>$page,
            'total'=>$this->pagedata['pagetotal'],
            'link' =>$this->gen_url(array('app'=>'b2c', 'ctl'=>'wap_gallery','act'=>'ajax_get_goods')),
        );
        $gfav = explode(',',$_COOKIE['S']['GFAV'][$siteMember['member_id']]);
        foreach($goodsData as $key=>$goods_row){
            if(in_array($goods_row['goods_id'],$gfav)){
                $goodsData[$key]['is_fav'] = 'true';
            }
            if($goods_row['udfimg'] == 'true' && $goods_row['thumbnail_pic']){
                $goodsData[$key]['image_default_id'] = $goods_row['thumbnail_pic'];
            }
            $gids[$key] = $goods_row['goods_id'];
        }

        if($filter['search_keywords'] || $filter['virtual_cat_id']){
            if(kernel::service('server.search_type.b2c_goods') && $searchrule = searchrule_search::instance('b2c_goods') ){
                if($searchrule){
                    $catCount = $searchrule->get_cat($filter);
                }
            }else{
                $sfilter = 'select cat_id,count(cat_id) as count from sdb_b2c_goods WHERE ';
                $sfilter .= $goodsModel->_filter($filter);
                $sfilter .= ' group by cat_id';
                $cats = $goodsModel->db->select($sfilter);
                if($cats){
                    foreach($cats as $cat_row){
                        $catCount[$cat_row['cat_id']] = $cat_row['count'];
                    }
                }
            }
        }
        //搜索时的分类
        if(!empty($catCount) && count($catCount) != 1){
            arsort($catCount);
            $this->pagedata['show_cat_id'] = key($catCount);
            $this->pagedata['catArr'] = array_keys($catCount);
            $this->catCount = $catCount;
        }else{
            $this->pagedata['show_cat_id'] = key($catCount);
        }

        //货品
        $goodsData = $this->get_product($gids,$goodsData);

        //促销标签
        $goodsData = $this->get_goods_promotion($gids,$goodsData);

        //商品标签信息
        foreach( kernel::servicelist('tags_special.add') as $services ) {
            if ( is_object($services)) {
                if ( method_exists( $services, 'add') ) {
                    $services->add( $gids, $goodsData);
                }
            }
        }
         //给商品附加预售信息
        $preparesell_is_actived = app::get('preparesell')->getConf('app_is_actived');
        if($preparesell_is_actived == 'true'){
            $prepare=app::get('preparesell')->model('preparesell_goods');
            if(is_object($prepare))
            {
                foreach ($goodsData as $key => $value) {
                    $prepare_goods = $prepare->getRow('*',array('product_id'=>$value['products']['product_id']));
                    if(!empty($prepare_goods))
                    {
                        $goodsData[$key]['prepare']=$prepare_goods;
                    }
                }
            }
        }
        if($filter['is_store'] == 'on'){
            $productsModel = app::get('b2c')->model('products');
            foreach($goodsData as $key => $value){
                $products = $productsModel->getlist('product_id,store,freez',array('goods_id'=>$value['goods_id']));
                $is_store = false;
                foreach($products as $value){
                    if($value['store']>$value['freez']){
                        $is_store = ture;
                        break;
                    }
                }
                if(!$is_store){
                    unset($goodsData[$key]);
                }
            }
        }
        if(!empty($pagedata)){
            $pagedata=$this->pagedata;
        }
        $goodsData = $this->get_goods_point($gids,$goodsData);
        return $goodsData;
    }

    /*
     * 获取搜索到的商品的默认货品数据，并且格式化货品数据(货品市场价，库存等)
     *
     * @params array $gids 搜索到到的商品ID集合
     * @params array $goodsData 搜索到的商品数据
     * @return array
     * */
    private function get_product($gids,$goodsData){
        $this->pagedata['imageDefault'] = app::get('image')->getConf('image.set');
        $productModel = $this->app->model('products');
        // $products =  $productModel->getList('*',array('goods_id'=>$gids,'is_default'=>'true','marketable'=>'true'));
        $products =  $productModel->getList('*',array('goods_id'=>$gids,'marketable'=>'true'));
        $temp_product =  array();
        foreach($products as $key=>$row){
            $temp_product[$row['goods_id']][] = $row;                
        }
        unset($products);
        foreach($temp_product as $k=>$v){
            $temp = array();
            foreach($v as $goods_id=>$val){
                if($val['is_default'] == 'true'){
                    $temp = $val;
                }                
            }
            if( !$temp ){
                $temp = $v[0];
            }
            $products[] = $temp;
        }
        $show_mark_price = $this->app->getConf('site.show_mark_price');

        #检测货品是否参与special活动
        if($object_price = kernel::service('sepcial_goods_check')){
            $object_price->check_special_goods_list($products);
        }

        $sdf_product = array();
        foreach($products as $key=>$row){
            $sdf_product[$row['goods_id']] = $row;
        }
        foreach ($goodsData as $gk=>$goods_row){
            $product_row = $sdf_product[$goods_row['goods_id']];
            $goodsData[$gk]['products'] = $product_row;
            //市场价
            if($show_mark_price =='true'){
                if($product_row['mktprice'] == '' || $product_row['mktprice'] == null)
                    $goodsData[$gk]['products']['mktprice'] = $productModel->getRealMkt($product_row['price']);
            }

            //库存
            if($goods_row['nostore_sell'] || $product_row['store'] === null){
                $goodsData[$gk]['products']['store'] = 999999;
            }else{
                $store = $product_row['store'] - $product_row['freez'];
                $goodsData[$gk]['products']['store'] = $store > 0 ? $store : 0;
            }
        }
        return $goodsData;
    }

    /*
     * 获取搜索到的商品的促销信息
     *
     * @params array $gids 搜索到到的商品ID集合
     * @params array $goodsData 搜索到的商品数据
     * @return array
     * */
    private function get_goods_promotion($gids,$goodsData){
        //商品促销
        $time = time();
        $order = kernel::single('b2c_cart_prefilter_promotion_goods')->order();
        $goodsPromotion = app::get('b2c')->model('goods_promotion_ref')->getList('*', array('goods_id'=>$gids, 'from_time|sthan'=>$time, 'to_time|bthan'=>$time,'status'=>'true'),0,-1,$order);
        if($goodsPromotion){
            $black_gid = array();
            foreach($goodsPromotion as $row) {
                if(in_array($row['goods_id'],$black_gid)) continue;
                if(strpos($row['apply_platform'],'2') === false){
                    continue;
                }
                $tags[] = $row['tag_id'];
                $promotionData[$row['goods_id']][] = $row['tag_id'];
                if( $row['stop_rules_processing']=='true' ){
                    $black_gid[] = $row['goods_id'];
                }
            }
        }
        $tagModel = app::get('desktop')->model('tag');
        $sdf_tags = $tagModel->getList('tag_id,tag_name',array('tag_id'=>$tags));
        foreach($sdf_tags  as $tag_row){
            $tagsData[$tag_row['tag_id']] = $tag_row;
        }
        foreach($promotionData as $gid=>$p_row){
            foreach($p_row as $k=>$tag_id){
                $promotion_tags[$gid][$k] = $tagsData[$tag_id];
            }
        }
        foreach($goodsData as $key=>$goods_row){
            $goodsData[$key]['promotion_tags'] = $promotion_tags[$goods_row['goods_id']];
        }
        return $goodsData;
    }

    /*
     * 获取搜索到的商品的积分信息
     *
     * @params array $gids 搜索到到的商品ID集合
     * @params array $goodsData 搜索到的商品数据
     * @return array
     * */
    private function get_goods_point($gids,$goodsData)
    {
        $pointModel = $this->app->model('comment_goods_point');
        $goods_point_status = app::get('b2c')->getConf('goods.point.status');
        $this->pagedata['point_status'] = $goods_point_status ? $goods_point_status: 'on';
        if($this->pagedata['point_status'] == 'on'){
            $sdf_point = $pointModel->get_single_point_arr($gids);
            foreach($goodsData as $key=>$row){
                $goodsData[$key]['goods_point'] = $sdf_point[$row['goods_id']];
            }
        }

        return $goodsData;
    }


}
