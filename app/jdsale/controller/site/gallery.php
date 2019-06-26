<?php

class jdsale_ctl_site_gallery extends jdsale_frontpage{

    var $_call = 'index';
    var $type='goodsCat';
    var $seoTag=array('shopname','goods_amount','goods_cat','goods_cat_p','goods_type','brand','sort_path');
    public function __construct(&$app) {
        parent::__construct($app);
        $shopname = app::get('b2c')->getConf('system.shopname');
        $this->shopname = $shopname;
        if(isset($shopname)){
            $this->title = app::get('b2c')->_('京东商品分类').'_'.$shopname;
            $this->keywords = app::get('b2c')->_('京东商品分类_').'_'.$shopname;
            $this->description = app::get('b2c')->_('京东商品分类_').'_'.$shopname;
        }
        //$this->pagedata['request_url'] = $this->gen_url( array('app'=>'b2c','ctl'=>'site_product','act'=>'get_goods_spec') );
        $this->mdl_store=app::get('business')->model('storemanger');
        $this->db=kernel::database();
		
        //$this->mdl_brand=$this->app->model('brand');
        $this->arr_cat=array();
    }
	
	function index($cat_id='',$urlFilter=null,$orderBy=0,$tab=null,$page=1,$cat_type=null,$view='grid',$st='g',$tmpl=null){
		
		$GLOBALS['runtime']['time']=microtime();
        //防止xss
        $view = @utils::filter_input_XSS($view);
        $st = @utils::filter_input_XSS($st);
		
		$cat_object = app::get('jdsale')->model('goods_cat');
		$goods_object = app::get('b2c')->model('goods');
		
		$tab = intval($tab);
		//分类数组
		$this->pagedata['cat_id'] = $cat_id;
		$filters =array();
		$filter = array();

		//解析搜索条件
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
		

		//解析搜索条件
        $path =array();
        $cat=array();
        $searchtools = &$this->app->model('search');
        $propargs = $searchtools->decode($urlFilter,$path,$cat);
		
		$filter = $propargs;
		

		if(is_array($propargs)){
            foreach($propargs as $rk=>$rv){
				$pos = strpos($rk,'p_');
				if($rk=='name'){
					$search_key=$rv[0];
				}else if($rk=='jd_brand_id'){//取得搜索的品牌选择
                   $brand_id=$rv;
				}   
			}
		}
		

		//搜索内容
		if(!empty($search_key)){
            $GLOBALS['runtime']['jdsearch_key'] = $search_key;
        }
        $this->pagedata['scon']['scontent']=$search_key;

		//选择分类
		if($cat_id){
			$catDte = $cat_object->getTreeListCat($cat_id);
		    $this->pagedata['childnode'] = $cat_object ->getList('jd_cat_id,cat_name',array('parent_id'=>$cat_id));
			$selector_cat = $cat_object->getGalleryListCat($cat_id);
			$catDte[] = $cat_id;
			$filter['jd_cat_id|in'] = $catDte;		
		}else{
			$selector_cat = $cat_object->getGalleryListCat();
			$this->pagedata['childnode'] = false;
		}
		
		$tmp_filter['str_where'] = $goods_object->_extend_filter($filter);
		

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
		
		
		if($GLOBALS['search_array']&&is_array($GLOBALS['search_array'])){
            $this->pagedata['search_array'] = implode("+",$GLOBALS['search_array']);
        }
		

		//本地化服务地区 
        if(!isset($_GET['loc'])){
            if(isset($_COOKIE['CITY_ID'])){
                $mdl_regions = app::get('ectools')->model('regions');
                $cityname = $mdl_regions->dump(array('region_id'=>$_COOKIE['CITY_ID']),'local_name');
                $_GET['loc'] = $cityname['local_name'];
            }
        }
		
		
		
		if(isset($_GET['loc']) && $_GET['loc']){
            $this->pagedata['scon']['loc']=$_GET['loc'];
            if(is_object($searchApp)){
                $filter['loc']=array($_GET['loc']);
            }else{
                $filter['loc']=$_GET['loc'];
            }
		}
		
		
		

		$this->pagedata['filter'] = $filter;
		$pageLimit = ($pageLimit ? $pageLimit : 20);
		
		$default_view = 'grid';
		$default_searchType = 'g';
		
		//构建前台用参数列表。
		
	
		$args = array($cat_id,urlencode($urlFilter),$orderBy,$tab,$page,$cat_type,$default_view,$default_searchType,$tmpl);
		$this->pagedata['orderBy'] = $goods_object->jdorderBy();//排序方式

		if(isset($this->pagedata['orderBy'][$orderBy])){
            $orderby = $this->pagedata['orderBy'][$orderBy]['sql'];
        }
		 
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
		
		$this->pagedata['args'] = $args;
        $this->pagedata['args1'] = $args[1];
        $args[1] = null;
        $this->pagedata['args2'] = $args;

        if($this->app->getConf('system.seo.noindex_catalog'))
            $this->header .= '<meta name="robots" content="noindex,noarchive,follow" />';
		
		$total = $goods_object->_getSearchJdgdoodsCount($filter);

		$aProduct = $goods_object->getSearchjdGoods('*',$filter,$pageLimit*($page-1),$pageLimit,$orderby,$catDte);
		

		//当前登陆用户信息
        $siteMember = $this->get_current_member();
        //当前登陆用户等级
        $this->site_member_lv_id = $siteMember['member_lv'];
		if($this->site_member_lv_id){
			$member_lv = app::get('b2c')->model("member_lv");
			$member_lv_data = $member_lv->getRow('jd_discount',array('member_lv_id'=>$this->site_member_lv_id));
        }
		$jd_discount = $member_lv_data['jd_discount']?$member_lv_data['jd_discount']:1;
		

		
		foreach($aProduct as $key=>$val){
			$aProduct[$key]['image_default_id'] = jdsale_goods_import::$image_url_mid.$val['image_default_id'];
			$aProduct[$key]['price'] = $val['price']*$jd_discount;
		}
		
		//所选品牌
		$selector_brand = array('name'=>'品牌','value'=>Null);
		$selector_brand['options'] = $total['brandDate'];
		
		//如果选择了品牌，怎前台不显示品牌
		if($brand_id){
			$selector_brand = '';
		}
		
		$this->pagedata['selector_cat'] = $selector_cat;
		$this->pagedata['selector_brand'] = $selector_brand;
		
		$count = $total['gcount'];
		$this-> pagedata['products'] = $aProduct;
		$this->pagedata['_PDT_LST_TPL'] = '/site/gallery/type/grid.html';
		
		$GLOBALS['jdruntime']['cat'] = $cat_id; 
		$GLOBALS['jdruntime']['goods_count'] = $count;
		$GLOBALS['jdruntime']['brand'] = $brand_id; 
		
		$this->pagedata['pager'] = array(
			'current'=>$page,
			'scontent'=>$path[0]['str'],
			'urlFilter'=>urlencode($urlFilter),
			'formAction'=>$this->gen_url(array('app'=>'b2c', 'ctl'=>'site_search','full'=>1,'act'=>'result_jdgoods')),
			'args'=>array('cat_id'=>$cat_id,'filter'=>$filter,'orderby'=>$orderBy,'tab'=>$tab,'cat_type'=>$cat_type,'view'=>$view,'st'=>$st,'tmpl'=>$tmpl,'store_id'=>$_GET['sid'],'loc'=>$_GET['loc']),
			'total'=>ceil($count/$pageLimit),
			'link'=>  $this->gen_url(array('app'=>'jdsale', 'ctl'=>'site_gallery','full'=>1,'act'=>'index','args'=>array($cat_id,urlencode($path[0]['str']),$orderBy,$tab,($tmp=time()),$cat_type,$view,$st,$tmpl))),
			'token'=>$tmp
		);
        
		
        //如果当前页大于总页数。则报错。
        if($page != 1 && $page > $this->pagedata['pager']['total']){
            $this->_response->set_http_response_code(404);
        }
		
		
		$this->set_tmpl("jdsale");
        $this->page('site/gallery/index.html');
	}
}
