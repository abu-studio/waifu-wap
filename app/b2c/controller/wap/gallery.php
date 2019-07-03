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

    //有效店铺
    function _filterStore(){
        $str="`sdb_b2c_goods`.store_id not in(select store_id from sdb_business_storemanger";
        $str.=" where approved <>'1' or status<>'1' or (last_time is not null and last_time <=".mktime(0, 0, 0, date("m")  , date("d"), date("Y"))."))";
        return $str;
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

    public function indexOld($cat_id='',$urlFilter=null,$orderBy=0,$page=1,$virtual_cat_id=null,$showtype=null) {
        $this->pagedata['commentListListnum'] = $this->app->getConf('gallery.comment.time');
        $request_params = $this->_request->get_params();
        $request_params = utils::filter_input_XSS($request_params);
        $urlFilter = utils::filter_input_XSS($urlFilter);
        //频道页判断，如果是第一级分类并且设置了模板则为频道页。
        //如果是虚拟分类跳转则不判断是不是频道页。
        if( empty($cat_type) )
        {
            $is_chanel=$this->is_channel($cat_id);
            if($is_chanel)
            {
                return;
            }
        }else{
            //$cat_id='';
        }
        if (!empty($_SESSION['sfsc']['vcat']))
        {
            unset($_SESSION['sfsc']['vcat']);
        }
        $tab = intval($tab);


        if(in_array('gallery-index', $this->weixin_share_page))
        {
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

    public function index($cat_id='',$urlFilter=null,$orderBy=0,$tab=null,$page=1,$cat_type=null,$view='grid',$st='g',$tmpl=null)
    {
        $GLOBALS['runtime']['time']=microtime();
        //防止xss
        $view = @utils::filter_input_XSS($view);
        $st = @utils::filter_input_XSS($st);
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
        if (!empty($_SESSION['sfsc']['vcat'])){
            unset($_SESSION['sfsc']['vcat']);
        }
        $tab = intval($tab);
        //防止逗号被过滤
        $searchtools = &$this->app->model('search');
        $map =$searchtools->map;
        foreach($map as $k=>$v){
            $urlFilter = str_replace($v.',',$v.'{}',$urlFilter);
        }
        $urlFilter = utils::filter_input($urlFilter);
        foreach($map as $k=>$v){
            $urlFilter = str_replace($v.'{}',$v.',',$urlFilter);
        }
        $urlFilter=htmlspecialchars(urldecode($urlFilter));

        //当前位置栏中的搜索
        if($_GET['pscontent']){
            $_GET['scontent']= 'n,'.htmlspecialchars($_GET['pscontent']);
        }

        if($_GET['loc']){
            $_GET['loc']=utils::filter_input($_GET['loc']);
        }
        //查询内容。
        $_GET['scontent'] = htmlspecialchars($_GET['scontent']);
        if(!empty($urlFilter) && $urlFilter != $_GET['scontent']){

            $urlFilter .= '_'.$_GET['scontent'];
        }else{
            $urlFilter = $_GET['scontent'];
        }
        //如果从虚拟分类跳转过来则运行。
        $virCatObj = &$this->app->model('goods_virtual_cat');
        if( !empty($cat_type) )
        {
            $vcatid = $cat_type;
            $oSearch = &$this->app->model('search');

            /** 颗粒缓存商品虚拟分类 **/
            if(!cachemgr::get('goods_virtual_cat_'.intval($vcatid), $vcat))
            {
                cachemgr::co_start();
                $vcat = $virCatObj->getList('cat_id,cat_path,virtual_cat_id,filter,virtual_cat_name as cat_name',array('virtual_cat_id'=>intval($vcatid)));
                cachemgr::set('goods_virtual_cat_'.intval($vcatid), $vcat, cachemgr::co_end());
            }

            $vcat = current( $vcat );
            $vcatFilters = $virCatObj->_mkFilter($vcat['filter']);
            $vcatFilters = $virCatObj->getFilter($vcatFilters);
            $old_cat_id = $cat_id;
            $old_urlFilter = $urlFilter;
            if(!empty($vcatFilters['cat_id'])){
                $cat_id = $cat_id ? $cat_id.','.implode(",",$vcatFilters['cat_id']):implode(",",$vcatFilters['cat_id']);
            }
            if(strpos($cat_id,',')!==false){
                $cat_id=implode(',',array_unique(explode(',',$cat_id)));
            }

            $urlFilter = $urlFilter?$urlFilter:$oSearch->encode($vcatFilters);
            //$st='g';//搜索商品
            //$view='grid';//显示大图。
            if(empty($st)){
                $st='g';
            }
            if(empty($view)){
                $view='grid';
            }
        }
        //============end==============
        //解析搜索条件
        $path =array();
        $cat=array();
        $searchtools = &$this->app->model('search');
        $propargs = $searchtools->decode($urlFilter,$path,$cat);
        //如果选择的店铺列表页
        $this->pagedata['show_filter']='true';
        $store_id=array('_ANY_');
        if(isset($_GET['sid']) && $_GET['sid'] && empty($propargs['store_id'][0]))
        {
            $propargs['store_id'][0]=intval($_GET['sid']);
            $urlFilter=$searchtools->encode($propargs);
        }
        //品牌
        $brand_id=array();
        //扩展属性对应的goods中的字段。
        $prot=array();
        //扩展字段对应的值。
        $proparg=array();
        //搜索内容
        $search_key='';
        $default_view=$view;
        $default_searchType=$st;
        if(is_array($propargs))
        {
            foreach($propargs as $rk=>$rv){
                $pos = strpos($rk,'p_');
                if($pos === 0){//扩展属性
                    $propz[$rk] = $rv[0];
                    $rk =substr($rk,2);
                    $proparg[$rk] = $rv;
                    $prot[] = $rk;
                }else if($rk=='brand_id'){//取得搜索的品牌选择
                    $brand_id=$rv;
                }else if($rk=='name'){//搜索内容
                    $search_key=$rv[0];
                }else if($rk=='store_id'){
                    $store_id=$rv;
                    $this->store_id = implode(",",$rv);
                    $view='grid';
                    $this->pagedata['show_filter']='false';
                    //$st='s';
                }
            }
        }
        //搜索内容
        if(!empty($search_key)){
            $GLOBALS['runtime']['search_key'] = $search_key;
        }
        $this->pagedata['scon']['scontent']=$search_key;

        //搜索类型s:店铺 g:商品
        $GLOBALS['runtime']['st']=$st;

        //判断是否启用索引查询
        if(app::get('base')->getConf('server.search_server.search_goods')){
            //print_r(app::get('base')->getConf('server.search_server.search_goods'));
            $searchApp = search_core::instance('search_goods');
        }
        $GLOBALS['runtime']['view']=$view;
        //如果显示的是店铺列表页则在头部显示店铺信息。
        if($this->pagedata['show_filter']=='false')
        {
            $aStore=$this->mdl_store->getList_Search('store_id,store_name,image,area',array('store_id'=>$store_id),0,1);
            $aStore=$aStore[0];
            //取得店铺品牌
            $business_brand=app::get('business')->model('brand')->getList('brand_name',array('store_id'=>$aStore['store_id']));
            $b_brand=array();
            foreach($business_brand as $bkey=>$b){
                $b_brand[]=$b['brand_name'];
            }
            $area=$aStore['area'];
            $area_arr=explode(':',$area);
            $area_arr=explode('/',$area_arr[1]);
            $aStore['area']=$area_arr[0]."&nbsp;&nbsp;".$area_arr[1];
            $aStore['b_brand']=implode("$nbsp;$nbsp;", $b_brand);
            $this->pagedata['singleStore']=$aStore;
            //$GLOBALS['runtime']['st']='s';
        }

        //如果分类是任何分类。则删除也就是不搜索。
        if($cat_id == '_ANY_')
        {
            unset($cat_id);
        }
        if($propargs['cat_id'])
        {
            $cat_id=implode(",",$propargs['cat_id']);
        }
        //如果设置了分类。
        if($cat_id)
        {
            $cat_id=explode(",",$cat_id);
            foreach($cat_id as $k=>$v){
                if($v) $cat_id[$k]=intval($v);
            }
            $this->id = implode(",",$cat_id);
        }else{
            $cat_id = array('');
            $this->id = '';
        }
        //分页当前页。
        $page = ($page > 1) ? intval($page) : 1;

//每页显示商品数。
        $pageLimit = $this->app->getConf('gallery.display.listnum');//大图
        $spageLimit = $this->app->getConf('gallery.display.slistnum');//小图
        $shoppageLimit = $this->app->getConf('gallery.display.shoplistnum');//店铺
        $pageLimit = ($pageLimit ? $pageLimit : 20);
        if($view=='sgrid'){
            $pageLimit=($spageLimit ? $spageLimit : 80);
        }
        if($view=='index'){
            $pageLimit=($shoppageLimit ? $shoppageLimit : 20);
        }
//$pageLimit=5;
        //图片显示尺寸。
        $this->pagedata['pdtPic']=array('width'=>100,'heigth'=>100);

        $productCat = &$this->app->model('goods_cat');
        $objGoods = $this->app->model('goods');

        //当前位置
        $global_runtime_path = "";

        if($cat_type){//虚拟分类

            if(!cachemgr::get('global_runtime_path_cat_type_'.md5(serialize($cat_type)), $global_runtime_path)){
                cachemgr::co_start();
                $global_runtime_path = $virCatObj->getPath($cat_type,'');
                $co_end=cachemgr::co_end();
                $co_end['expires']=time()+300;
                cachemgr::set('global_runtime_path_cat_type_'.md5(serialize($cat_type)), $global_runtime_path, $co_end);
            }
        }else{
            if(!cachemgr::get('global_runtime_path_cat_id'.md5(serialize($cat_id[0])), $global_runtime_path)){
                cachemgr::co_start();
                $global_runtime_path = $productCat->getPath($cat_id[0],'');
                $co_end=cachemgr::co_end();
                $co_end['expires']=time()+300;
                cachemgr::set('global_runtime_path_cat_id'.md5(serialize($cat_id[0])), $global_runtime_path, $co_end);
            }

        }

        //未设置虚拟分类
        if(empty($cat_type)){
            array_shift($global_runtime_path);
            array_unshift($global_runtime_path,array('type'=>'goodsCat',
                'title'=>app::get('site')->_('全部'),
                'link'=>$this->gen_url( array('app'=>'b2c','ctl'=>'site_gallery')).'?scontent='.$search_key));
        }


        // ajx 以下是为了当无分类和搜索条件时 显示所有商品
        if( count($global_runtime_path) < 2 ){
            $global_runtime_path = array(
                array('type'=>'goodsCat',
                    'title'=>app::get('site')->_('全部'),
                    'link'=>$this->gen_url( array('app'=>'b2c','ctl'=>'site_gallery')).'?scontent='.$search_key)
            );
        }

        $GLOBALS['runtime']['path'] = $global_runtime_path;

        //设置品牌搜索条件到当前位置。
        if(empty($brand_id[0])==false && $brand_id[0]!=='_ANY_'){
            /** 颗粒缓存商品品牌 **/
            if(!cachemgr::get('gallery_brand_'.md5(serialize($brand_id)), $brand_options)){
                cachemgr::co_start();
                $bfilter['brand_id']=$brand_id;
                $arr_brand=$this->mdl_brand->getList('brand_id,brand_name',$bfilter,0,-1,'ordernum');
                foreach($arr_brand as $bv){
                    $brand_options[$bv['brand_id']]=$bv['brand_name'];
                }
                $co_end=cachemgr::co_end();
                $co_end['expires']=time()+300;
                cachemgr::set('gallery_brand_'.md5(serialize($brand_id)), $brand_options, $co_end);
            }
            $GLOBALS['runtime']['brand']['name']='品牌';
            $GLOBALS['runtime']['brand']['options']=$brand_options;

        }
        //如果存在分类则取出分类信息。
        if ($cat_id[0]){
            if(!cachemgr::get('goods_cat_'.$cat_id[0], $this->cat_result)){
                cachemgr::co_start();
                $this->cat_result = $productCat->getList('cat_name,gallery_setting,type_id',array('cat_id|in'=>$cat_id),0,1);
                cachemgr::set('goods_cat_'.$cat_id[0], $this->cat_result, cachemgr::co_end());
            }
            $type_filter['type_id'] = $this->cat_result[0]['type_id'];
        }
        //如果分类存在模板。则调用分类模板。  为什么取第一个的模板？
        //为了防止顶级分类外的分类选择模板。把模板干掉。让系统取默认。
        /*if( isset($this->cat_result[0]['gallery_setting']['gallery_template'])
            &&$this->cat_result[0]['gallery_setting']['gallery_template'] ){
            $this->set_tmpl_file($this->cat_result[0]['gallery_setting']['gallery_template']);                 //添加模板
        }*/


        //如果显示视图未设定则取系统设定，如果系统未设定则默认为index。
        if(empty($view)){
            $view = $this->app->getConf('gallery.default_view')?$this->app->getConf('gallery.default_view'):'index';
        }

        //是否缓存了类型数据。如果为缓存则取出并且缓存。前台没用到以后删除
        if(!cachemgr::get('goods_cat_childnode_'.$cat_id[0], $this->pagedata['childnode'])){
            cachemgr::co_start();
            $this->pagedata['childnode'] = $productCat->getCatParentById($cat_id,$view);
            cachemgr::set('goods_cat_childnode_'.$cat_id[0], $this->pagedata['childnode'], cachemgr::co_end());
        }


        //echo microtime(),'<br>';
        //取出分类。
        $cache_key='gallery_cat_'.md5(serialize(array($cat_id,$view,$type_filter['type_id'],$cat_type)));
        if(!cachemgr::get($cache_key, $cat)){
            cachemgr::co_start();
            $cat = kernel::service('b2c_site_goods_list_viewer_apps')->get_view($cat_id,$view,$type_filter['type_id'],$cat_type,'wap');
            $co_end=cachemgr::co_end();
            $co_end['expires']=time()+300;
            cachemgr::set($cache_key, $cat, $co_end);
        }

        //$cat = kernel::service('b2c_site_goods_list_viewer_apps')->get_view($cat_id,$view,$type_filter['type_id'],$cat_type);

        //echo microtime(),'<br>';
        //构建前台用参数列表。
        $args = array( ( $cat_type?$old_cat_id:$this->id),($cat_type?$old_urlFilter:urlencode($urlFilter)),$orderBy,$tab,$page,$cat_type,$default_view,$default_searchType,$tmpl);

        $this->pagedata['args'] = $args;
        $this->pagedata['args1'] = $args[1];
        $args[1] = null;
        $this->pagedata['args2'] = $args;

        if($this->app->getConf('system.seo.noindex_catalog'))
            $this->header .= '<meta name="robots" content="noindex,noarchive,follow" />';



        $filter = $propargs;

        if(is_array($filter)){
            $filter=array_merge(array('cat_id'=>$cat_id,'marketable'=>'true'),$filter);
            if( ($filter['cat_id'][0] === '' || $filter['cat_id'][0] === null ) && !isset( $filter['cat_id'][1] ) )
                unset($filter['cat_id']);
            if( ($filter['brand_id'][0] ==='' || $filter['brand_id'][0] === null) && !isset( $filter['brand_id'][1] ))
                unset($filter['brand_id']);
            if( ($filter['store_id'][0] ==='' || $filter['store_id'][0] === null) && !isset( $filter['store_id'][1] ))
                unset($filter['store_id']);
        }else{
            $filter = array('cat_id'=>$cat_id,'marketable'=>'true');
        }

        //--------获取类型关联的规格
        $type_id = $type_filter['type_id'];

        $gType = &$this->app->model('goods_type');

        //本地化服务地区
        if(!isset($_GET['loc'])){
            if(isset($_COOKIE['CITY_ID'])){
                $mdl_regions = app::get('ectools')->model('regions');
                $cityname = $mdl_regions->dump(array('region_id'=>$_COOKIE['CITY_ID']),'local_name');
                $_GET['loc'] = $cityname['local_name'];
            }
        }
        //end

        /*发货地区*/
        if(isset($_GET['loc']) && $_GET['loc']){
            $this->pagedata['scon']['loc']=$_GET['loc'];
            if(is_object($searchApp)){
                $filter['loc']=array($_GET['loc']);
            }else{
                $filter['loc']=$_GET['loc'];
            }
        }
        /*发货地区*/
        $filter['store_id']=$store_id;
        //如果Store搜索全部，则删除该条件
        if($filter['store_id'][0] == '_ANY_'){
            unset($filter['store_id']);
        }

        $filter['cat_id'] = $cat_id;
        $filter['goods_type'] = 'normal';
        $filter['marketable'] = 'true';
        //-----查找当前类别子类别的关联类型ID
        if ($urlFilter){
            if($vcat['type_id']){
                $filter['type_id']=null;
            }
        }

        foreach($path as $p){
            $arg = unserialize(serialize($this->pagedata['args']));
            $arg[1] = $p['str'];
            $title = array();
            if($p['type']=='brand_id'){
                $brand = array();

                foreach($cat['brand'] as $b){
                    $brand[$b['brand_id']] = $b['brand_name'];
                }
                foreach($p['data'] as $i){
                    $title[] = $brand[$i];
                    $tip = __("品牌");
                }
                unset($brand);
            }
        }
        $this->pagedata['tabs'] = $cat['tabs'];
        $this->pagedata['cat_id'] = implode(",",$cat_id);
        $views = $cat['setting']['list_tpl'];

        //显示视图对应的连接参数。
        foreach($views as $key=>$val){
            $this->pagedata['views'][$key] = array( ( $cat_type?$old_cat_id:$this->id ),'',$orderBy,$tab,1,$cat_type,$val,$st);
        }
        if($cat['tabs'][$tab])
        {
            parse_str($cat['tabs'][$tab]['filter'],$_filter);
            $filter = array_merge($filter,$_filter);
        }
        if(isset($this->pagedata['orderBy'][$orderBy]))
        {
            $orderby = $this->pagedata['orderBy'][$orderBy]['sql'];
        }

        /***********************/
        $this->pagedata['orderBy'] = $objGoods->orderBy();//排序方式
        //如果未指定排序方式则取默认。
        if(empty($orderBy)){
            $orderBy = 1;
        }

        //如果排序中没有找到排序规则则报错。
        if(!isset($this->pagedata['orderBy'][$orderBy])){
            $this->_response->set_http_response_code(404);
        }else{
            $orderby = $this->pagedata['orderBy'][$orderBy]['sql'];

        }
        $res = false;   //初始化
        //下面是基于索引分词器。基于zend_lucence开发的文本搜索引擎
        //echo '<pre>';
        if(is_object($searchApp))
        {
            $sfilter['filter'] = $filter;
            $sfilter['from'] = $pageLimit*($page-1);     //分页
            $sfilter['to'] = $pageLimit;
            $sfilter['order'] =str_replace('sdb_b2c_goods.', '', $orderby);//$orderby;
            $sfilter['view'] = $view;
            $sfilter['st'] = $st;
            $sfilter['scount'] = count($cat['props']);
            self::_print($sfilter);
            $sphinxstart = true;
            $this->pagedata['order'] = $orderby;
            $this->pagedata['filter'] = $filter;
            //分词用于前台热卖商品搜索。
            if($filter['name'][0]){
                $split_word=$searchApp->split_words($filter['name'][0]);
                $this->pagedata['filter']['name'][0]=implode(' ',$split_word);
            }

            if($view=='index'){
                $queryRes = $searchApp->query_store($sfilter);
            }else{
                $queryRes = $searchApp->query($sfilter);
            }
            if($queryRes){
                $res = $searchApp->commit();
                self::_print($res);
                $nprop = $res['prop'];           //属性搜索
                $cbrand = $res['brand'];
                $resCat = $res['cat'];
                $this->pagedata['search_store_id']=array_keys($res['all_store']);
                if(is_array($res['result'])||is_array($res['all_store'])){
                    $all_goods_id=$res['result'];
                    if($view=='index'){
                        //总数量
                        $count =count($res['all_store']);
                        //print_r($res['all_store']);
                        $sfilter['filter']['store_id']=array_keys($res['store']);
                        //$arr_store_id=
                        $store_filter['store_id']=array_keys($res['store']);
                        //查询店铺对应的商品
                        //echo microtime(),'<br>';
                        $searchApp->query_store_goods($sfilter);
                        $res_goods = $searchApp->commit();
                        //echo microtime(),'<br>';
                        $resStore=$res_goods['store_id'];
                        //print_r($resStore);

                        //$limit_store_id=array_slice($arr_store_id,$pageLimit*($page-1),$pageLimit);
                        //$limit_store_id;
                        $temp_goods_id=array();
                        foreach($resStore as $key=>$goods){
                            //if(in_array($key,$limit_store_id)){
                            $temp_goods_id=array_merge($temp_goods_id,array_slice(array_values($goods),0,4));
                            $temp_store_goods_count[$key]=count($goods);
                            //}
                        }
                        $aStore=$this->mdl_store->getList('store_id,store_name,image,area',$store_filter);
                        $aStore=utils::array_change_key($aStore,'store_id', 0);

                        $rfilter['store_id']=$store_filter['store_id'];
                        $rfilter['goods_id']=$temp_goods_id;
                        $search_data =$objGoods->getList_1($this->getGoodsCol(),$rfilter);
                        $tmp_search_data=array();
                        foreach($search_data AS $tmp_data){
                            $tmp_search_data[$tmp_data['goods_id']] = $tmp_data;
                        }
                        foreach($temp_goods_id AS $v){
                            $aProduct[] = $tmp_search_data[$v];
                        }
                    }else{
                        $rfilter['goods_id'] = $res['result'];
                        $count = $res['total'];
                        $tmp_filter['str_where'] = $objGoods->_filter($rfilter);
                        $search_data =$objGoods->getList_1($this->getGoodsCol(),$tmp_filter);
                        foreach($search_data AS $tmp_data){
                            $tmp_search_data[$tmp_data['goods_id']] = $tmp_data;
                        }
                        foreach($res['result'] as $v){
                            if(!isset($tmp_search_data[$v])) continue;
                            $aProduct[] = $tmp_search_data[$v];  //产品
                        }
                    }
                    unset($search_data);
                    unset($tmp_search_data);
                }else{
                    $count = 0;
                    $aProduct = array();
                }
            }
        }
        //分词结束。基于zend_lucence开发的文本搜索引擎
        /*搜索商品*/
        if($res === false)
        {
            $arr_cat_id=array();
            //默认分类要考虑分类相关性，鸡肋呀。
            if(empty($orderby)){
                $torderby=' dorder desc';
                $temp=0;
                if($propargs['name'][0]){
                    $_catfilter = array(
                        'cat_name|has'=>$propargs['name'][0],
                        'disabled'=>'false',
                    );
                    $arr_cat_id=$productCat->getList('cat_id',$_catfilter);
                    $arr_cat_id=utils::array_change_key($arr_cat_id,'cat_id', 0);
                    $arr_cat_id=array_keys($arr_cat_id);
                }
            }
            if (isset($filter['tag'][0])&&!$filter['tag'][0])
                unset($filter['tag']);
            if(app::get('base')->getConf('server.search_server.search_goods')){
                if(!empty($filter['name'][0])){
                    $segmentObj = search_core::segment();
                    $segmentObj->pre_filter(new search_service_filter_cjk);
                    $segmentObj->token_filter(new search_service_filter_lowercase);
                    $segmentObj->set($filter['name'][0], 'utf8');
                    while($row = $segmentObj->next()){
                        $res[] = $row['text'];
                    }
                    $GLOBALS['search_array']=$res;
                    $filter['name'][0]=implode(' ',$res);
                }
            }
            if($default_searchType=='s'){
                $filter['store_name']=$filter['name'][0];
            }
            //分词用于前台热卖商品搜索。
            $this->pagedata['filter'] = $filter;

            $tmp_filter['str_where'] = $objGoods->_extend_filter($filter);
            //过滤掉店铺未审核、或者状态为不可用或者是过期的店铺对应的商品。
            $tmp_filter['str_where']=$tmp_filter['str_where'].' and '.$this->_filterStore();

            if($view=='index'){
                //搜索出所有符合条件的商品对应的店铺。

                if(empty($orderby)){
                    $torderby=' dorder desc';
                }//else{
                //$temp_product=$objGoods->getList('store_id',$tmp_filter,0,-1,$orderby);
                //}
                //print_r($tmp_filter);
                $temp=0;
                $temp_product =$objGoods->getSearchGoods('sdb_b2c_goods.goods_id,sdb_b2c_goods.store_id,sdb_b2c_goods.cat_id,sdb_b2c_goods.brand_id',$tmp_filter,0,-1,$torderby,$arr_cat_id,$temp);
                $all_goods_id=array_map('current',$temp_product);
                $temp_store_array=utils::array_change_key($temp_product,'store_id', 1);
                $no_indexCat=utils::array_change_key($temp_product,'cat_id', 1);
                $no_indexBrand=utils::array_change_key($temp_product,'brand_id', 1);

                $arr_store_id=array_keys($temp_store_array);
                //用于推荐商铺的检索ID
                $this->pagedata['search_store_id']=$arr_store_id;//array_keys($temp_store_array);

                $limit_store_id=array_slice($arr_store_id,$pageLimit*($page-1),$pageLimit);
                $temp_store_goods_count=array();
                $temp_goods_id=array();
                foreach($temp_store_array as $key=>$v){
                    if(in_array($key,$limit_store_id)){
                        $temp_goods_id=array_merge($temp_goods_id,array_slice(array_map('current',$v),0,4));
                        $temp_store_goods_count[$key]=count($v);
                    }
                }
                //echo '<pre>';print_r($no_indexCat);print_r($temp_goods_id);print_r($temp_store_array);echo '</pre>';
                $store_filter['store_id']=$limit_store_id;
                //$store_filter['store_id']=array_keys($temp_store_array);
                //其他条件$store_filter['']
                $aStore=$this->mdl_store->getList('store_id,store_name,image,area',$store_filter);
                $aStore=utils::array_change_key($aStore,'store_id', 0);
                $count =count($arr_store_id);// $this->mdl_store->count($store_filter);
                $rfilter['store_id']=$store_filter['store_id'];
                $rfilter['goods_id']=$temp_goods_id;
                $search_data =$objGoods->getList_1($this->getGoodsCol(),$rfilter);
                $tmp_search_data=array();
                foreach($search_data AS $tmp_data){
                    $tmp_search_data[$tmp_data['goods_id']] = $tmp_data;
                }
                //$ttmp_search_data=array();
                foreach($temp_goods_id AS $v){
                    $aProduct[] = $tmp_search_data[$v];
                }
                unset($temp_product);
                unset($search_data);
                unset($tmp_search_data);
                unset($temp_store_array);
            }else{
                $ttemp_product=$objGoods->getSearchGoods('sdb_b2c_goods.goods_id,sdb_b2c_goods.store_id,sdb_b2c_goods.cat_id,sdb_b2c_goods.brand_id',$tmp_filter,0,-1,$orderby,$arr_cat_id,$temp);//$objGoods->getList('store_id,cat_id',$tmp_filter,0,-1);
                $all_goods_id=array_map('current',$ttemp_product);
                $ttemp_store_array=utils::array_change_key($ttemp_product,'store_id', 1);
                $no_indexCat=utils::array_change_key($ttemp_product,'cat_id', 1);
                $no_indexBrand=utils::array_change_key($ttemp_product,'brand_id', 1);
                //用于推荐商铺的检索ID
                $this->pagedata['search_store_id']=array_keys($ttemp_store_array);
                if(empty($orderby)){
                    $orderby=' dorder desc';

                }
                $temp=0;
                $aProduct =$objGoods->getSearchGoods($this->getGoodsCol(),$tmp_filter,$pageLimit*($page-1),$pageLimit,$orderby,$arr_cat_id,$temp);
                $count=$temp;
            }
        }
        //扩展属性中的排序 不知道干吗用。
        $selector = array();
        $selector['ordernum'] = $cat['ordernum'];

        /*********分类中存在品牌。********/
        //self::_print($cat['brand']);
        if (is_array($cat['brand'])){

            //查找当前条件下的品牌对应当前条件下的商品数量。
            if($sphinxstart){//来自索引
                $bCount = $cbrand;
            }else{//品牌对应的商品数量
                foreach($no_indexBrand as $key=>$goods){
                    $bCount[]=array('brand_id'=>$key,'_count'=>count($goods));
                }
                //$bCount =$no_indexBrand;// $objGoods->countBrandGoods($tmp_filter,$cat['brand']);
            }
            //搜索条件下的品牌ID
            if(is_array($filter['brand_id'])){
                $bid = array_flip($filter['brand_id']);
            }
            //该分类下的品牌。
            foreach($cat['brand'] as $bk => $bv){
                $brand = array('name'=>app::get('b2c')->_('品牌'),'value'=>$bid);
                $brandArray[$bv['brand_id']] = $bv['brand_name'];
            }
            //搜索的商品结果如果包含品牌
            foreach((array)$bCount as $sk => $sv){
                if(isset($brandArray[$bCount[$sk]['brand_id']])){
                    $tmpOp[$bCount[$sk]['brand_id']]=$brandArray[$bCount[$sk]['brand_id']];//."<span class='num'>(".$bCount[$sk]['_count'].")</span>";
                }
            }
            $brand['options'] = $tmpOp;
            $selector['brand_id'] = $brand;
        }

        //分类选择项
        $plist=array();
        $has_select_cat=false;
        if(isset($cat_id[0]) && $cat_id[0] && count($cat_id)==1){
            if(!cachemgr::get('gallery_goods_cat_parent_id'.md5(serialize($cat_id)), $cat_list)){
                cachemgr::co_start();
                $cat_list=$productCat->getList('cat_name,cat_id,parent_id,cat_path,child_count',array('parent_id|in'=>$cat_id,'hidden'=>'false'),0,-1,'p_order');
                $co_end=cachemgr::co_end();
                $co_end['expires']=time()+300;
                cachemgr::set('gallery_goods_cat_parent_id'.md5(serialize($cat_id)), $cat_list, $co_end);
            }
            //$cat_list=$productCat->getList('cat_name,cat_id,parent_id,cat_path,child_count',array('parent_id|in'=>$cat_id),0,-1,'p_order');
            if(empty($cat_list)==false){
                $plist=utils::array_change_key($cat_list,'parent_id', 1);
                if($sphinxstart){
                    $catList=$resCat;
                }else{
                    $catList=$no_indexCat;
                }
                $has_select_cat=true;
            }
        }else{
            if($sphinxstart){
                $catList=$resCat;
            }else{
                $catList=$no_indexCat;
            }
            if(!cachemgr::get('gallery_goods_cat_id'.md5(serialize(array_keys($catList))), $cat_list)){
                cachemgr::co_start();
                $cat_list=$productCat->getList('cat_name,cat_id,parent_id,cat_path,child_count',array('cat_id|in'=>array_keys($catList),'hidden'=>'false'),0,-1);
                $co_end=cachemgr::co_end();
                $co_end['expires']=time()+300;
                cachemgr::set('gallery_goods_cat_id'.md5(serialize(array_keys($catList))), $cat_list, $co_end);
            }
            //$cat_list=$productCat->getList('cat_name,cat_id,parent_id,cat_path,child_count',array('cat_id|in'=>array_keys($catList)),0,-1);
            $plist=utils::array_change_key($cat_list,'parent_id', 1);
        }

        //self::_print($cat_list);
        if(empty($plist)==false){
            $catchild=array();
            foreach($plist as $pid=>$cl){
                //最顶级分类不显示
                if($pid==0){
                    continue;
                }
                $item=array();
                $item['value']='';
                $item['has']=false;
                if($has_select_cat){
                    $item['name']='分类';
                }else{
                    if($pid==0){//顶级分类
                        $item['name']='其他分类';
                    }else{
                        if(!in_array($pid,$this->arr_cat)){
                            if(!cachemgr::get('gallery_goods_cat_id'.intval($pid), $row)){
                                cachemgr::co_start();
                                $cat_list=$productCat->getList('cat_name,cat_id,parent_id,cat_path,child_count',array('cat_id|in'=>array_keys($catList),'hidden'=>'false'),0,-1);
                                $row=$this->db->selectrow('select cat_name,parent_id from sdb_b2c_goods_cat where cat_id='.$pid);
                                $co_end=cachemgr::co_end();
                                $co_end['expires']=time()+300;
                                cachemgr::set('gallery_goods_cat_id'.intval($pid), $row, $co_end);
                            }
                            $this->arr_cat[$pid]=$row;
                        }
                        $scat=$this->arr_cat[$pid];
                        $item['name']=$scat['cat_name'];
                        if($scat['parent_id']){
                            $item['has']=true;
                        }else{
                            $item['has']=false;
                        }
                    }
                }
                foreach($cl as $key=>$c){
                    if($catList[$c['cat_id']]){
                        $item['options'][$c['cat_id']]=$c['cat_name'];
                    }
                }
                if($item['options']){
                    $catchild[$pid]=$item;
                }
            }
            krsort($catchild);
            $selector['cat_id'] =$catchild;
        }
        //属性选择项
        $goods_relate = array();
        if((!is_array($cat_id) && $cat_id) || $cat_id[0] || $cat_type){//为什么再选择一遍 huoxh
            $_pfilter['goods_id']=$all_goods_id;
            $goods_relate=$objGoods->getList("p_1,p_2,p_3,p_4,p_5,p_6,p_7,p_8,p_9,p_10,p_11,p_12,p_13,p_14,p_15,p_16,p_17,p_18,p_19,p_20",$_pfilter,0,-1);
        }

        foreach((array)$cat['props'] as $prop_id=>$prop){
            if($prop['search']=='nav' ||$prop['search']=='select'){

                if(is_array($filter['brand_id'])&&isset($filter['p_'.$prop_id])){//为什么判断品牌 huoxh
                    $prop['value'] = array_flip($filter['p_'.$prop_id]);
                }
                $plugadd=array();
                if(is_array($goods_relate)){
                    foreach($goods_relate as $k=>$v){//统计属性对应的商品数量

                        if($v["p_".$prop_id]!=null){

                            if($plugadd[$v["p_".$prop_id]]){
                                $plugadd[$v["p_".$prop_id]]=$plugadd[$v["p_".$prop_id]]+1;
                            }else{
                                $plugadd[$v["p_".$prop_id]]=1;
                            }
                        }
                    }
                }
                $navselector=0;
                if(is_array($prop['options'])){
                    foreach($prop['options'] as $q=>$e){
                        if($plugadd[$q]){
                            //$prop['options'][$q]=$prop['options'][$q];//."<span class='num'>(".$plugadd[$q].")</span>";
                            if (!$navselector)
                                $navselector=1;
                        }else{
                            unset($prop['options'][$q]);
                        }
                    }
                }

                $selector[$prop_id] = $prop;
            }
        }

        if ($navselector){
            $nsvcount=0;
            $noshow=0;
            foreach($selector as $sk => $sv){
                if ($sv['value']){
                    $nsvcount++;
                }
                if (is_numeric($sk)&&!$sv['show']){
                    $noshow++;
                }
            }
            if ($nsvcount==intval(count($selector)-$noshow))
                $navselector=0;
        }
        foreach((array)$cat['spec'] as $spec_id=>$spec_name){
            $sId['spec_id'][] = $spec_id;
        }

        $cat['ordernum'] = $cat['ordernum']?$cat['ordernum']:array(''=>2);
        if ($cat['ordernum']){
            if ($selector)
            {
                foreach($selector as $key => $val)
                {
                    if(!in_array($key,$cat['ordernum'])&&$val){
                        $selectorExd[$key]=$val;
                    }
                }
            }
        }
        if(is_array($aProduct))
        {
            foreach($aProduct as $apk=>$apv)
            {
                $rfilter[] = $apv['goods_id'];
            }
        }

        //end
        //对商品进行预处理支持ie8的webslice特性
        $this->pagedata['mask_webslice'] = $this->app->getConf('system.ui.webslice')?' hslice':null;

        //屏蔽输入方式查询 huoxh
        //$this->pagedata['searchInput'] = &$searchInput;
        $this->pagedata['selectorExd'] = $selectorExd;
        $this->cat_id = $cat_id;
        $this->_plugins['function']['selector'] = array(&$this,'_selector');
        //分页开始，前台将调用gimage标签 显示分页。
        $this->pagedata['pager'] = array(
            'current'=>$page,
            'scontent'=>$p['str'],
            'urlFilter'=>$cat_type?$old_urlFilter:urlencode($urlFilter),
            'formAction'=>$this->gen_url(array('app'=>'b2c', 'ctl'=>'wap_search','full'=>1,'act'=>'result')),
            'args'=>array( 'cat_id'=>($cat_type?$old_cat_id:implode(',',$cat_id) ),'filter'=>$filter,'orderby'=>$orderBy,'tab'=>$tab,'cat_type'=>$cat_type,'view'=>$view,'st'=>$st,'tmpl'=>$tmpl,'store_id'=>$_GET['sid'],'loc'=>$_GET['loc']),
            'total'=>ceil($count/$pageLimit),
            'link'=>  $this->gen_url(array('app'=>'b2c', 'ctl'=>'wap_gallery','full'=>1,'act'=>'index','args'=>array( ($cat_type?$old_cat_id:implode(',',$cat_id) ),urlencode($p['str']),$orderBy,$tab,($tmp=time()),$cat_type,$view,$st,$tmpl))),//.'?scontent='.urlencode($p['str']),
            'token'=>$tmp);

        //如果当前页大于总页数。则报错。
        if($page != 1 && $page > $this->pagedata['pager']['total']){
            $this->_response->set_http_response_code(404);
        }

        //如果未搜索到则显示报错页面。
        if(!$count){
            $this->pagedata['emtpy_info'] = kernel::single('site_errorpage_get')->getConf('errorpage.search');

        }
        //商品数量
        $GLOBALS['runtime']['goods_count']=$count;
        $this->pagedata['searchtotal']=$count;

        $aData = $this->get_current_member();
        if(is_array($aProduct) && count($aProduct) > 0)
        {
            $objProduct = $this->app->model('products');
            //显示市场价
            if($this->app->getConf('site.show_mark_price')=='true'){
                $setting['mktprice'] = $this->app->getConf('site.show_mark_price');
                if(isset($aProduct)){
                    foreach($aProduct as $pk=>$pv){
                        if(empty($aProduct[$pk]['mktprice']))
                            $aProduct[$pk]['mktprice'] = $objProduct->getRealMkt($pv['price']);
                    }
                }
            }else{
                $setting['mktprice'] = 0;
            }
            //商品页是否显示节省金额
            $setting['saveprice'] = $this->app->getConf('site.save_price');
            //顾客点击商品购买按钮后
            $setting['buytarget'] = $this->app->getConf('site.buy.target');
            $this->pagedata['setting'] = $setting;
            //spec_desc

            //会员等级
            $this->site_member_lv_id = $aData['member_lv'];
            /** 获取所有商品对应的货品 **/
            //取得所有商品对应商店
            $store_products = utils::array_change_key($aProduct,'store_id', 1);
            $store_ids=array_keys($store_products);
            $store_info=$this->mdl_store->getlist('store_id,store_name',array('store_id'=>$store_ids));
            $store_info=utils::array_change_key($store_info,'store_id', 0);



            /** 促销处理 **/
            $promotion=kernel::single('business_goods_promotion');
            foreach ($aProduct as $key=>&$val)
            {
                //$aProduct[$key]['goods_url']=$promotion->gen_site_url($val['goods_id'],$val['promotion_id'],$val['act_type']);
                $aProduct[$key]['goods_url'] = app::get('wap')->router()->gen_url(array('app' => 'b2c','ctl' => 'wap_product', 'act'=>'index','arg0'=>$val['goods_id']));;
                // add storeINfo and sepcl;
                $val['store_name']=$store_info[$val['store_id']]['store_name'];
                $this->specFomart(&$val);
            }
            // show tag on goods image
            foreach( kernel::servicelist('tags_special.add') as $services )
            {
                if ( is_object($services)) {
                    if ( method_exists( $services, 'add') ) {
                        //$services->add( $rfilter, $aProduct );
                    }
                }
            }
            // end of add

            // 分店铺和商品
            if($view=='index')
            {
                $taStore=array();
                $store_product=utils::array_change_key($aProduct,'store_id', 1);
                $business_brand=app::get('business')->model('brand')->getList('store_id,brand_name',array('store_id'=>array_keys($aStore)));
                $b_brand=array();
                foreach($business_brand as $bkey=>$b){
                    $b_brand[$b['store_id']][]=$b['brand_name'];
                }
                foreach($store_product as $key=>$store){
                    if(array_key_exists($key,$aStore)){

                        $area=$aStore[$key]['area'];
                        $area_arr=explode(':',$area);
                        $area_arr=explode('/',$area_arr[1]);
                        $aStore[$key]['area']=$area_arr[0]."&nbsp;&nbsp;".$area_arr[1];
                        $taStore[$key]=$aStore[$key];
                        $taStore[$key]['b_brand']=implode("/", $b_brand[$key]);

                        $taStore[$key]['gcount']=$temp_store_goods_count[$key];
                        $taStore[$key]['goods_list']=array_slice($store,0,4);
                    }
                }
                unset($store_product);
                $this->pagedata['products']=$taStore;

            }else{
                $this->pagedata['products'] = &$aProduct;
            }
        }
        //没有商品的情况下不显示店铺信息。
        if(empty($aProduct[0])){
            $this->pagedata['show_filter']='true';
        }
        if(!$aData['member_id']){
            $this->pagedata['login'] = 'nologin';
        }
        /* if($searchSelect){
             $this->pagedata['searchSelect'] = &$searchSelect;
         }*/

        //当前显示样式：店铺、大图、小图
        //$this->pagedata['curView'] = $view;
        $this->pagedata['curView'] = $default_view;
        //如果选择了分类
        $this->pagedata['selector_cat']=&$selector['cat_id'];
        $GLOBALS['runtime']['cat']=$cat_id;

        unset($selector['cat_id']);
        //如果选择了品牌则列表中不显示品牌
        if(empty($propargs['brand_id'])){
            $this->pagedata['selector_brand']=&$selector['brand_id'];
        }
        unset($selector['brand_id']);
        unset($selector['ordernum']);

        //删除已经选择的属性。
        foreach($propargs as $key=>$p){
            if(is_string($key)&&substr($key,0,2)=='p_'){
                $GLOBALS['runtime']['props'][]=$selector[intval(substr($key,2))];
                unset($selector[intval(substr($key,2))]);
            }
        }


        $this->pagedata['selector'] = &$selector;



        //商品类型 好像是虚拟分类。
        $this->pagedata['cat_type'] = $cat_type;

        if($GLOBALS['search_array']&&is_array($GLOBALS['search_array'])){
            $this->pagedata['search_array'] = implode("+",$GLOBALS['search_array']);
        }
        //前台显示模板样式：店铺列表、大图、小图。  店铺列表 已经屏蔽
        $this->pagedata['_PDT_LST_TPL'] = $cat['tpl'];

        unset($filter['name']);
        if($GLOBALS['runtime']['search_key']){
            $filter['name']=array($GLOBALS['runtime']['search_key']);
        }
        $this->pagedata['bfilter'] = $filter;

        if($tmpl)
        {
            $this->set_tmpl_file('gallery-'.$tmpl.'.html');
            $this->pagedata['tmpl'] = true;
        }else{
            $this->set_tmpl('gallery');//设定模板类型为列表页。
        }
        $this->pagedata['gallery_display'] = $this->app->getConf('gallery.display.grid.colnum');

        //是否显示销量
        $this->pagedata['showBuyCount'] = $this->app->getConf('gallery.display.buyCount');

        //是否启用分类搜索。
        $this->pagedata['show_cat'] = $this->app->getConf('site.cat.select');

        if($count < $this->pagedata['gallery_display']){
            $this->pagedata['gwidth'] = $count * (100/$this->pagedata['gallery_display']);
        }else{
            $this->pagedata['gwidth'] = 100;
        }



        //是否启用属性搜索-----好像没有这个设定。
        //$this->pagedata['property_select'] = $this->app->getConf('site.property.select');

        //默认图片
        $imageDefault = app::get('image')->getConf('image.set');
        $this->pagedata['image_set'] = $imageDefault;
        $this->pagedata['defaultImage'] = $imageDefault['S']['default_image'];

        //已经选择的扩展属性。
        $this->pagedata['proparg'] = $proparg;

        /** 获取商品表相关meta **/
        if ($cat_id)
        {
            $obj_meta_register = app::get('dbeav')->model('meta_register');
            $arr_meta_register = $obj_meta_register->getList('*',array('tbl_name'=>$productCat->table_name(1),'col_name'=>'seo_info'));
            if (!$arr_meta_register){
                $productCat->cat_meta_register();
            }

            $meta_desc = $arr_meta_register[0]['col_desc'];
            $col_type = $arr_meta_register[0]['col_type'];
            $obj_meta_value = app::get('dbeav')->model('meta_value_'.$col_type);
            $seo_info = $obj_meta_value->select($arr_meta_register[0]['mr_id'],$cat_id);

            if(is_array($seo_info) && count($seo_info) == 1){
                $seo_info = $seo_info[$cat_id[0]];
            }elseif(is_string($seo_info)){
                $seo_info = unserialize($seo_info[$cat_id[0]]);
            }elseif(!$seo_info){
                $seo_info="";
            }
        }
        if(!empty($seo_info['seo_info']['seo_title']) || !empty($seo_info['seo_info']['seo_keywords']) || !empty($seo_info['seo_info']['seo_description'])){
            $this->title = $seo_info['seo_info']['seo_title'];
            $this->keywords = $seo_info['seo_info']['seo_keywords'];
            $this->description = $seo_info['seo_info']['seo_description'];
        }else{
            $this->setSeo('wap_gallery','index',$this->prepareSeoData($this->pagedata));
        }
        if($_GET['scontent'])
        {
            $title = explode(",",$_GET['scontent']);
            $this->title = $title[1].'_'.$this->shopname;
        }

        $this->page('wap/gallery/index.html');
    }

    public function getGoodsCol(){
        $col=array();
        $col[]='sdb_b2c_goods.goods_id';
        $col[]='sdb_b2c_goods.name';
        $col[]='IFNULL(pp.p_price,`sdb_b2c_goods`.price) as price';
        $col[]='sdb_b2c_goods.type_id';
        $col[]='sdb_b2c_goods.cat_id';
        $col[]='sdb_b2c_goods.brand_id';
        $col[]='sdb_b2c_goods.mktprice';
        $col[]='sdb_b2c_goods.image_default_id';
        $col[]='sdb_b2c_goods.udfimg';
        $col[]='sdb_b2c_goods.thumbnail_pic';
        $col[]='sdb_b2c_goods.spec_desc';
        $col[]='sdb_b2c_goods.comments_count';
        $col[]='sdb_b2c_goods.store_id';
        $col[]='sdb_b2c_goods.buy_m_count';
        $col[]='sdb_b2c_goods.act_type';
        $col[]='IFNULL(pp.ref_id,0) as promotion_id';
        return implode(',',$col);
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


    var $spec_list=array();
    function specFomart(&$listgoods){
        $oSpec = &$this->app->model('specification');
        if( $listgoods['spec_desc'] ){
            if(!is_array( $listgoods['spec_desc'] )){
                $listgoods['spec_desc']=unserialize($listgoods['spec_desc']);
            }

            foreach( $listgoods['spec_desc'] as $specId => $spec )
            {
                if(!isset($this->spec_list[$specId])){
                    $aRow = $oSpec->getList("*",array('spec_id'=>$specId));
                    $this->spec_list[$specId]=$aRow[0];
                }
                $listgoods['spec'][$specId] = $this->spec_list[$specId];
                foreach( $spec as $pSpecId => $specValue )
                {
                    $specValue['spec_goods_images']=explode(',', $specValue['spec_goods_images']);
                    $listgoods['spec'][$specId]['option'][$pSpecId] = array_merge( array('private_spec_value_id'=>$pSpecId), $specValue );
                }
            }
        }
    }

    function prepareSeoData($data)
    {
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
            'search_num'=>$data['searchtotal'],
            'goods_cat'=>$this->cat_result[0]['cat_name']?$this->cat_result[0]['cat_name']:'',
            'goods_cat_p'=>$cat_path,
            'goods_type'=>$this->spec_goods?$this->spec_goods['name']:'',
        );
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
