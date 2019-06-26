<?php
class physical_ctl_site_index extends site_controller{

	function __construct($app){
        parent::__construct($app);
		$this->pagedata['res_url']=$this->app->res_url;
    }

    function index(){
		$this->pagedata['show_rb'] = 1;
		$this->set_tmpl("physical");
		$this->page('');
    }

	function goodslist($order=0,$page=1){
		$pageLimit = 5;

		$where='';
		//产品名称
		$name = trim(@utils::filter_input($_GET['name']));
		//防止xss
		$name = @utils::filter_input($name);
		if($name){
			$where .= " AND a.name like '%{$name}%' ";
		}
		$this->pagedata['name'] = $name;

		//价格区间
		$price1 = trim($_GET['price1']);
		//防止xss
		$price1 = @utils::filter_input($price1);
		if($price1 && is_numeric($price1) ){
			$where .= " AND a.price >= {$price1} ";
		}
		$this->pagedata['price1'] = $price1;

		$price2 = trim($_GET['price2']);
		//防止xss
		$price2 = @utils::filter_input($price2);
		if($price2 && is_numeric($price2) ){
			$where .= " AND a.price <= {$price2} ";
		}
		$this->pagedata['price2'] = $price2;

		//排序
		$this->pagedata['order'] = $order;
		$orderby="";
		if($order == 1){
			$orderby = " a.price DESC ";
		}elseif($order == 2){
			$orderby = " a.price ASC ";
		}


		//体检产品列表
		$obj_package = $this->app->model('package');
		$this->pagedata['goods_list'] = $obj_package->get_goods_list($where,$orderby,$pageLimit*($page-1),$pageLimit);

		$count = $obj_package->get_goods_list_num($where);
		$this->pagedata['count'] = $count;

		$tmp =time();

		$this->pagedata['pager'] = array(
            'current'=>$page,
            'total'=>ceil($count/$pageLimit),
            'link'=>  $this->gen_url( array( 'app'=>'physical', 'ctl'=>'site_index','full'=>1,'act'=>'goodslist','args'=>array( $order,$tmp ) ) )."?name={$name}&price1={$price1}&price2={$price2}",
            'token'=>$tmp,
			);

		$this->set_tmpl("physical_public");
		$this->page('site/goods/list.html');
    }

	function storelist($goods_id=0,$order=0,$page=1){
        $obj_filter = kernel::single('b2c_site_filter');
        $goods_id = $obj_filter->check_input($goods_id);
        $order = $obj_filter->check_input($order);
        $page = $obj_filter->check_input($page);
		//机构列表
		$obj_organization = &$this->app->model('organization');
		$list = $obj_organization->getList("organization_id,organization_name");
		$organization_list[0] = "请选择";
        foreach($list as $key=>$val){
            $organization_list[$val['organization_id']]=$val['organization_name'];
        }
        $this->pagedata['organization_list'] = $organization_list;

		$pageLimit = 5;

		$where='';
		$package_name = '';
		//体检产品使用门店
		$this->pagedata['goods_id'] = $goods_id;
		if($goods_id){
			//$where .= " AND e.goods_id ={$goods_id} ";
			$db=kernel::database();
			$sql="select package_name from sdb_physical_package as a join sdb_cardcoupons_cards_solution as b on b.services_id=a.package_id join sdb_cardcoupons_cards as c on c.card_id=b.card_id where c.goods_id= {$goods_id} ";
			$package_info=$db->selectRow($sql);
			$package_name = $package_info['package_name'];
		}
		//地区
		//防止xss
		$_GET['area'] = $obj_filter->check_input($_GET['area']);
		$area_arr = explode(":",$_GET['area']);
		$area = $area_arr[1];
		if($area){
			$where .= " AND a.area like '%{$area}%' ";
		}
		$this->pagedata['area'] = $_GET['area'];

		//机构
		$organization_id = $obj_filter->check_input($_GET['organization_id']);
		//防止xss
		$organization_id = $obj_filter->check_input($organization_id);
		if($organization_id){
			$where .= " AND a.organization_id = {$organization_id} ";
		}
		$this->pagedata['organization_id'] = $organization_id;

		//套餐名称
		$package_name = $obj_filter->check_input($_GET['package_name'])?trim($obj_filter->check_input($_GET['package_name'])):$package_name;
		//防止xss
		$package_name = $obj_filter->check_input($package_name);
		if($package_name){
			$where .= " AND c.package_name like '%{$package_name}%' ";
		}
		$this->pagedata['package_name'] = $package_name;

		//门店名称
		$store_name = trim($_GET['store_name']);
		//防止xss
		$store_name = $obj_filter->check_input($store_name);
		if($store_name){
			$where .= " AND a.store_name like '%{$store_name}%' ";
		}
		$this->pagedata['store_name'] = $store_name;

		//排序
		$this->pagedata['order'] = $order;
		$orderby="";


		//体检产品列表
		$obj_package = $this->app->model('package');
		$store_list = $obj_package->get_store_list($where,$orderby,$pageLimit*($page-1),$pageLimit);

		$week=array(
			"1"=>"周一",
			"2"=>"周二",
			"3"=>"周三",
			"4"=>"周四",
			"5"=>"周五",
			"6"=>"周六",
			"0"=>"周日",
		);
		$week_arr=array("1","2","3","4","5","6","0");
		foreach($store_list as $key => $val){
			$restday_html="";
			$weekday_arr = explode(",",$val['weekday']);
			$restday_arr = array_diff($week_arr,$weekday_arr);
			foreach($restday_arr as $v){
				$restday_html .= ",".$week[$v];
			}
			$store_list[$key]["restday_html"] = trim($restday_html,",");
		}

		$this->pagedata['store_list'] = $store_list;

		$count = $obj_package->get_store_list_num($where);
		$this->pagedata['count'] = $count;

		$tmp =time();

		$this->pagedata['pager'] = array(
            'current'=>$page,
            'total'=>ceil($count/$pageLimit),
            'link'=>  $this->gen_url( array( 'app'=>'physical', 'ctl'=>'site_index','full'=>1,'act'=>'storelist','args'=>array( $goods_id,$order,$tmp ) ) )."?area={$_GET['area']}&organization_id={$organization_id}&package_name={$package_name}&store_name={$store_name}",
            'token'=>$tmp,
			);

		$this->set_tmpl("physical_public");
		$this->page('site/store/list.html');
    }

	function goodsdetail($goods_id=0){
		$obj_goods = app::get('b2c')->model('goods');

        if(!$goods_id){
            $this->splash('failed', 'back', app::get('physical')->_('无效体检产品！'));
        }else{
            $info = $obj_goods->dump($goods_id,'*','default');
            if(!$info || empty($info)){
                $this->splash('failed', 'back', app::get('physical')->_('无效体检产品！'));
            }
        }
		$this->pagedata['info'] = $info;

		//体检项目
		$obj_package = $this->app->model('package');
		$projects = $obj_package->get_all_project($goods_id);
		$this->pagedata['projects'] = $projects;

		$this->set_tmpl("physical_public");
		$this->page('site/goods/detail.html');
	}

	function storedetail($store_id=0){
		$obj_store = &$this->app->model('store');

        if(!$store_id){
            $this->splash('failed', 'back', app::get('physical')->_('无效体检产品！'));
        }else{
            $info = $obj_store->getInfobyid($store_id);
            if(!$info || empty($info)){
                $this->splash('failed', 'back', app::get('physical')->_('无效体检产品！'));
            }
        }
		$week=array(
			"1"=>"周一",
			"2"=>"周二",
			"3"=>"周三",
			"4"=>"周四",
			"5"=>"周五",
			"6"=>"周六",
			"0"=>"周日",
		);
		$week_arr=array("1","2","3","4","5","6","0");
		$restday_html="";
		$weekday_arr = explode(",",$info['weekday']);
		$restday_arr = array_diff($week_arr,$weekday_arr);
		foreach($restday_arr as $v){
			$restday_html .= ",".$week[$v];
		}
		$info["restday_html"] = trim($restday_html,",");

		$this->pagedata['info'] = $info;

		//关联套餐
		$package_list = app::get('physical') -> model('store') -> get_attach_list($store_id);
		$this->pagedata['package_list'] = $package_list;

		$this->set_tmpl("physical_public");
		$this->page('site/store/detail.html');
	}
}
