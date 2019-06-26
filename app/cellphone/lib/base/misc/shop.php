<?php
class cellphone_base_misc_shop extends cellphone_cellphone
{
    public function __construct($app){
        parent::__construct();
        $this->app = $app;
    }
    //店铺信息
    public function getstoreinfo(){
         $params = $this->params;

         $must_params = array(
            'store_id'=>'店铺标识',
        );
        $this->check_params($must_params);
        $store_id = intval($params['store_id']);
        $picSize = in_array(strtolower($params['picSize']), array('cl', 'cs'))? strtolower($params['picSize']):'cl';
        $mdl_storemanger = app::get('business')->model('storemanger');
        $sInfo= $mdl_storemanger->getRow('store_id,shop_name,store_name,area,image,fav_count,tel,zip,company_ctel,store_region,approved_time',array('store_id'=>$store_id));
        if(empty($sInfo)){
        $this->send(false,null,'没有找到该店铺');
        }
        $area = $sInfo['area'];
        $aryAre = split('/', $area);
        $stemp['pro'] = substr($aryAre[0],strpos($aryAre[0], ':')+1);
        $stemp['city'] =  $aryAre[1];
        $stemp['district'] = substr($aryAre[2], 0, strpos($aryAre[2], ':'));
        $sInfo['area'] =$stemp['pro'].$stemp['city'].$stemp['district'];
        $sInfo['img_url']=$this->get_img_url($sInfo['image'],$picSize);
		unset($sInfo['image']);
        $region_ids_str = substr($sInfo['store_region'],1,strrpos(trim($sInfo['store_region']),',')-1);
	    $region_data = $mdl_storemanger->db->select('select a.cat_id,a.cat_name from sdb_b2c_goods_cat as a where a.cat_id in('.$region_ids_str.') and a.disabled="false" ');
	    $str ='';
	    foreach($region_data as $v){
	      $str = $str.$v['cat_name'].'/';
	    }
	    $sInfo['store_region'] = rtrim($str, "/") ;
        $objComment = app::get('business')->model('comment_stores_point');
        $store_info = $objComment->getStoreInfo($store_id);
        foreach((array)$store_info['store_point'] as $row){
            $sInfo['store_point'][] = array(
                'name' => $row['name'],
                'avg_point' => $row['avg_point'],
                'avg_percent' => $row['avg_percent'],
            );
        }
        // 添加店铺内商品数量
        $filter = array('store_id'=>$store_id,'marketable'=>'true');
        $sInfo['goods_count'] =  app::get('b2c')->model('goods')->count($filter);
        // end 
        $member = $this->get_current_member();
        $member_id = $member['member_id'];
        if($member_id > 0){
            $result = app::get('business')->model('member_stores')->getRow('*',array('store_id'=>$store_id,'member_id'=>$member_id));
            if(empty($result)){
                    $sInfo['is_fav']= false;
                }else{
                    $sInfo['is_fav']= true;
                }
            @app :: get('business')->model('store_view_history')->add_history($member_id,$store_id);

            }
        $this->send(true,$sInfo,'店铺信息');

		

    }



 // 取到当前分类下的下一级分类
    public function getnextcat(){
         $params = $this->params;

         $must_params = array(
            'store_id'=>'店铺标识',
            
        );
        $this->check_params($must_params);
        $store_id = intval($params['store_id']);
		$arr = app::get('business')->model('storemanger')->getRow('store_id,shop_name',array('store_id'=>$store_id));
        if(empty($arr)){
	    $this->send(false,null,app::get('cellphone')->_('没有该店铺'));
	    } 
		$catlist = array();
		$mdl_goods_cat = app::get('business')->model('goods_cat');
		if($params['custom_cat_id']){

        $custom_cat_id = intval($params['custom_cat_id']);
		$filter = array('store_id'=>$store_id,'parent_id'=>$custom_cat_id);
		$catlist['data'] = $mdl_goods_cat->getList('store_id,custom_cat_id,cat_name,parent_id',$filter);
		}
	 // 获得店铺的一级分类
	  else{
        $filter = array('store_id'=>$store_id,'parent_id'=>0);
        $catlist['data'] = $mdl_goods_cat->getList('store_id,custom_cat_id,cat_name,parent_id',$filter);
		foreach($catlist['data'] as &$val){
		    $childcat = $mdl_goods_cat->getList('custom_cat_id,cat_name,parent_id',array('store_id'=>$store_id,'parent_id'=>$val['custom_cat_id']),0,-1);
            $val['child']= $childcat;
		  //foreach( $childcat as $v){
		  //$cat[] = $v['cat_name'];
		  //$val['child']
		  //}
		  //$val['child'] = implode('/',$cat);
		    unset($cat);
		}
	  
	  }
        if(!$catlist||empty($catlist)){
		$this->send(true,null,app::get('cellphone')->_('无下级分类'));
		}
	    $this->send(true,$catlist,app::get('cellphone')->_('分类列表'));
    }
   //根据分类获取商品列表 此时传入的分类ID 可以是一级分类或者是二级分类
    public  function getgoodsbycat(){

         $params = $this->params;

         $must_params = array(
            'store_id'=>'店铺标识',
            'custom_cat_id'=>'分类标识',
        );
        $this->check_params($must_params);
        $store_id = intval($params['store_id']);
        $custom_cat_id = intval($params['custom_cat_id']);
		$page = $params['nPage']? $params['nPage']: 1;
		$pageLimit=$params['pagelimit']? $params['pagelimit']:10;
		$picSize = $params['picSize']? $params['picSize']:'cs';
		$orderby = 'view_count desc';
		if($params['orderby']==7){
	     $orderby = 'view_count desc';// 按照人气降序排序
		}
	    if($params['orderby']==9){
         $orderby = 'buy_count desc';//按照销量 降序排序
		}
	    if($params['orderby']==4){  
		 $orderby = 'price desc';    // 按照价格降序排序
		}
		if($params['orderby']==5){
		 $orderby = 'price asc';    //按照价格升序排序
		}
		$storearr = app::get('business')->model('storemanger')->getRow('store_id,shop_name',array('store_id'=>$store_id));
        if(empty($storearr)){
	    $this->send(false,null,app::get('cellphone')->_('没有该店铺'));
	    } 
        // 此处判断 custom_cat_id=-99 的情况
		if($custom_cat_id==-99){
		
		   $list = $this->getgoods($store_id,$page,$pageLimit,$picSize,$orderby);
		   if($list['data']){
		   $this->send(true,$list,app::get('cellphone')->_('商品列表'));
		   }
		   $this->send(true,null,app::get('cellphone')->_('没有商品'));
		   
		}
	    $mdl_goods_cat = app::get('business')->model('goods_cat');
		$parent_id = $mdl_goods_cat->getRow('parent_id,store_id',array('custom_cat_id'=>$custom_cat_id,'store_id'=>$store_id));
		//判断 是一级分类的ID 还是二级分类的ID
		if(!empty($parent_id)&&empty($parent_id['parent_id'])){
	 
		$list = $this->gettopcatlist($store_id,$custom_cat_id,$picSize,$page,$pageLimit,$orderby);
		
		}
		
	    if(!empty($parent_id)&&!empty($parent_id['parent_id'])){
		
		$list = $this->getsecondcatlist($custom_cat_id,$picSize,$page,$pageLimit,$orderby);
		
		}
		if($list['data']){
		$this->send(true,$list,app::get('cellphone')->_('商品列表'));
		}
		$this->send(true,null,app::get('cellphone')->_('没有商品'));
	        
    }
	//获得店铺某个一级分类下的商品列表
	private function gettopcatlist($store_id,$cat_id,$picSize,$page,$pageLimit,$orderby='view_count desc'){
	
	//先要取到二级分类id 再找到所有的id下的商品 最后再找到该ID下直属的商品 最后返回所有的商品
	$mdl_goods_cat = app::get('business')->model('goods_cat');
	$secondcatlist = $mdl_goods_cat->getList('custom_cat_id',array('store_id'=>$store_id,'parent_id'=>$cat_id));
	$cat_ids[] = $cat_id;
	
	//有二级分类的时候
	if(!empty($secondcatlist)){
	foreach($secondcatlist as $val){
	$cat_ids[] = $val['custom_cat_id'];
	   }
	}
	$filter = implode(',',$cat_ids);
	$countsql = 'select count(a.goods_id) from sdb_b2c_goods as a  left join sdb_business_goods_cat_conn as b on a.goods_id=b.goods_id left join sdb_business_goods_cat as c on b.cat_id=c.custom_cat_id where c.custom_cat_id in ('.$filter.') ';
	$cout = $mdl_goods_cat->db->select($countsql);
	$count = $cout[0]['count(a.goods_id)'];
	
    $sql = 'select a.goods_id,a.name,a.price ,a.mktprice,a.buy_m_count,a.act_type,a.freight_bear,a.image_default_id,c.store_id from sdb_b2c_goods as a  left join sdb_business_goods_cat_conn as b on a.goods_id=b.goods_id left join sdb_business_goods_cat as c on b.cat_id=c.custom_cat_id where c.custom_cat_id in ('.$filter.') order by a.'.$orderby.' limit '.($page-1)*$pageLimit.','.$pageLimit;
   // echo '$filter='.$filter."</br>";
	//echo '$sql='.$sql;
	//exit;
	$data = $mdl_goods_cat->db->select($sql);
	if(!empty($data)){
    foreach( $data as &$val){
    $val['image_default_id'] = $this->get_img_url($val['image_default_id'],$picSize);
	$val['act_type'] = $val['act_type']=='package'?'normal':$val['act_type'];
	   }
	 }
	
	 return $this->get_response_result($count,$page,$pageLimit,$data);


	}
    //获得店铺某个二级分类下的商品列表
	private function getsecondcatlist($cat_id,$picSize,$page,$pageLimit,$orderby='view_count desc'){
	 
      $mdl_goods_cat_conn = app::get('business')->model('goods_cat_conn');
	  $count = $mdl_goods_cat_conn->count(array('cat_id'=>$cat_id));
      $goods_ids = $mdl_goods_cat_conn->getList('goods_id',array('cat_id'=>$cat_id));
	  if(!empty($goods_ids)){
	   $mdl_goods = app::get('b2c')->model('goods');
	   $goodslist = array();
	   $ids = array();
	   foreach( $goods_ids as $v){
	   $ids[] = $v['goods_id'];
	   }
	   $goodslist = $mdl_goods->getList('goods_id,store_id,name,price,mktprice,buy_m_count,image_default_id',array('goods_id'=>$ids),($page-1)*$pageLimit,$pageLimit,$orderby);
	   if(!empty($goodslist)){
           foreach( $goodslist as &$val){
             $val['image_default_id'] = $this->get_img_url($val['image_default_id'],$picSize);
             $val['act_type'] = $val['act_type']=='package'?'normal':$val['act_type'];
	          }
	     }
	   return $this->get_response_result($count,$page,$pageLimit,$goodslist);
	  }
	  return $this->get_response_result($count,$page,$pageLimit,$goods_ids);;
	
	
	}
	 private  function get_response_result($count,$page,$pagelimit,$data){
         $pager['limit']= $pagelimit;
         $pager['tPage']= ceil($count/intval($pagelimit));
         $pager['cPage']= $page;
         $pager['count']= $count;
         return array('page'=>$pager,'data'=>$data);
         
    }
    //获得店铺顶级分类下的商品列表包括店铺的一级分类的多维数组
    public function gettopcatgoods(){
         $params = $this->params;

         $must_params = array(
            'store_id'=>'店铺标识',
            
        );
        $this->check_params($must_params);
        $store_id = intval($params['store_id']);
		$page = $params['nPage']? $params['nPage']: 1;
		$pageLimit= $params['pagelimit']? $params['pagelimit']:10;
		$picSize = $params['picSize']? $params['picSize']:'cs';
		$arr = app::get('business')->model('storemanger')->getRow('store_id,shop_name',array('store_id'=>$store_id));
        if(empty($arr)){
	    $this->send(false,null,app::get('cellphone')->_('没有该店铺'));
	    } 
		$mdl_goods_cat  = app::get('business')->model('goods_cat');
		$topcatlist = $mdl_goods_cat->getList('store_id,custom_cat_id,cat_name',array('store_id'=>$store_id,'parent_id'=>0));
		if(empty($topcatlist)){
		$this->send(true,null,app::get('cellphone')->_('该店铺没有自定义分类'));
		}
		foreach( $topcatlist as &$val){
		$val['goods']= $this->gettopcatlist($store_id,intval($val['custom_cat_id']),$picSize,$page,$pageLimit);
		}
	    if($topcatlist){
		 $add = array();
		 $add['store_id'] = $store_id;
         $add['custom_cat_id'] = -99;
         $add['cat_name'] = '未分类商品';
	     $add['goods']=$this->getgoods($store_id,$page,$pageLimit,$picSize);
		 $topcatlist = array_merge($topcatlist,array($add));
	    $this->send(true,$topcatlist,app::get('cellphone')->_('分类商品列表'));
		}
	    $this->send(true,null,app::get('cellphone')->_('没有数据'));
       
    }
   
   //获得当前店铺下的没有参与自定义分类的所有商品
     private function getgoods($store_id,$page=1,$pageLimit=10,$picSize='cs',$orderby='view_count desc'){
     
		$mdl_store = app::get('business')->model('storemanger');
		$countsql = 'select count(a.goods_id) from sdb_b2c_goods as a where a.store_id='.$store_id.' and  a.goods_id not in( select b.goods_id from sdb_business_goods_cat_conn as b ) ';
		$cout = $mdl_store->db->select($countsql);
		$count = $cout[0]['count(a.goods_id)'];

		$sql = 'select a.goods_id,a.name,a.price ,a.mktprice,a.buy_m_count,a.act_type,a.freight_bear,a.image_default_id,a.store_id  from sdb_b2c_goods as a where a.store_id='.$store_id.' and  a.goods_id not in( select b.goods_id from sdb_business_goods_cat_conn as b ) order by a.'.$orderby.' limit '.($page-1)*$pageLimit.','.$pageLimit;
		
		$data = $mdl_store->db->select($sql);
 
		if(!empty($data)){
           foreach( $data as &$val){
             $val['image_default_id'] = $this->get_img_url($val['image_default_id'],$picSize);
	         $val['act_type'] = $val['act_type']=='package'?'normal':$val['act_type'];
	          }
	    }
        $list = $this->get_response_result($count,$page,$pageLimit,$data);
		return  $list;
     }

     /**  返回当前店铺内的所有商品  added by cuiqw */
     function getallgoods(){
        $params = $this->params;

        $must_params = array(
            'store_id'=>'店铺标识',
        );
        $this->check_params($must_params);
        $store_id = intval($params['store_id']);
        $page = $params['nPage']? $params['nPage']: 1;
        $pageLimit = $params['pagelimit']? $params['pagelimit']:10;
        $picSize = $params['picSize']? $params['picSize']:'cs';
        $arr = app::get('business')->model('storemanger')->getRow('store_id',array('store_id'=>$store_id));
        if(empty($arr)){
	       $this->send(false,null,app::get('cellphone')->_('没有该店铺'));
	    } 
        $orderby = 'view_count desc';
		if($params['orderby']==7){
	     $orderby = 'view_count desc';// 按照人气降序排序
		}
	    if($params['orderby']==9){
         $orderby = 'buy_count desc';//按照销量 降序排序
		}
	    if($params['orderby']==4){  
		 $orderby = 'price desc';    // 按照价格降序排序
		}
		if($params['orderby']==5){
		 $orderby = 'price asc';    //按照价格升序排序
		}
        if($params['orderby']==13){
         $orderby = 'uptime DESC';   //按照上架的时间降序排序 最新上架的在前
        }
        $filter = array('store_id'=>$store_id,'marketable'=>'true');
        $mdl_goods = app::get('b2c')->model('goods');
        $count = $mdl_goods->count($filter);
        $goods_list = $mdl_goods->getList('goods_id,store_id,name,price,mktprice,buy_m_count,buy_count,image_default_id,uptime',$filter,($page-1)*$pageLimit,$pageLimit,$orderby);
        if(empty($goods_list)){
           $this->send(true,null,app::get('cellphone')->_('店铺内暂无商品出售'));
        }else{
            foreach ($goods_list as &$val){
            $val['image_default_id'] =  $this->get_img_url($val['image_default_id'],$picSize);
            }
           $goods_list = $this->get_response_result($count,$page,$pageLimit,$goods_list);
           $this->send(true,$goods_list,app::get('cellphone')->_('店铺内商品'));
        }


     }
     
     function gettags(){
        $params = $this->params;
        $must_params = array(
            'store_id'=>'店铺标识',
        );
        $this->check_params($must_params);
        $store_id = intval($params['store_id']);
        $page = $params['nPage']? $params['nPage']: 1;
        $pageLimit= $params['pagelimit']? $params['pagelimit']:10;
        $picSize = $params['picSize']? $params['picSize']:'cs';
        $filter = array(
            'store_id'=>$params['store_id'],
            'tag_mode'=>'normal',
            'app_id'=>'cellphone',
        );
        $filter['tag_type'] = (!isset($params['type'])||!in_array($params['type'],array('goods','activity','all')))?'goods':$params['type'];
        if($filter['tag_type'] == 'all')unset($filter['tag_type']);
        if(isset($params['tag_id']) && !empty($params['tag_id'])){
            $filter['tag_id'] = $params['tag_id'];
        }
        
        $data = array();
        $this->rec_tags($data, $filter, ($page-1)*$pageLimit, 2, $picSize);

        if(empty($data)){
            $this->send(true,null,app::get('cellphone')->_('没有数据'));
        }
        $this->send(true,$data,app::get('cellphone')->_('标签数据'));
    }
    
    private function rec_tags(&$data, $filter, $offset, $limit, $picSize){
        $obj_tag = &app::get('cellphone')->model('tag');
        $count = $obj_tag->count($filter);
        if(!$count)return;
        $obj_tag_rel = &app::get('cellphone')->model('tag_rel');
        $aObject = array();
        foreach($obj_tag->getList('tag_id,tag_name',$filter,0,-1) as $row){
            if(!$row['tag_id'])continue;
            $data[$row['tag_id']]['tag_id'] = $row['tag_id'];
            $data[$row['tag_id']]['tag_name'] = $row['tag_name'];
            foreach($obj_tag_rel->getList('*',array('tag_id'=>$row['tag_id']),$offset,$limit) as $item){
                if($item['app_id']!='b2c' && !$this->app_return($item['app_id']))continue;
                //if(array_key_exists($item['rel_id'].'_'.$item['object_type'],$data[$row['tag_id']]['item']))continue;
                $data[$row['tag_id']]['item'][$item['rel_id'].'_'.$item['object_type']] = array(
                    'goods_id' => $item['rel_id'],
                    'object_type' => $item['app_id']=='b2c'?'normal':$item['app_id'],
                );
                $aObject[$item['app_id']][] = $item['rel_id'];
            }
        }
        $obj_goods = &app::get('b2c')->model('goods');
        $obj_data = array();
        foreach($aObject as $key => $value){
            $temp = $this->app_return($key);
            if(!$temp && $key!='b2c')continue;
            if($key == 'package'){
                $obj_app = app::get($key);
                if($obj_app->is_installed()){
                    $obj_model = $obj_app->model($temp['m2']);
                    foreach($obj_model->getList('id,amount as price,name,image',array('id'=>$value),0,-1) as $item){
                        $obj_data[$item['id'].'_'.$key] = array(
                            'price' => $item['price'],
                            'name' => $item['name'],
                            'image' => $this->get_img_url($item['image'],$picSize),
                            'bid_count'=> 0,
                        );
                    }
                }
            }else{
                foreach($obj_goods->getList('goods_id,price,name,image_default_id as image',array('goods_id'=>$value),0,-1) as $item){
                    $obj_data[$item['goods_id'].'_'.$key] = array(
                        'price' => $item['price'],
                        'name' => $item['name'],
                        'image' => $this->get_img_url($item['image'],$picSize),
                        'bid_count'=> 0,
                    );
                }
                if($temp){
                    $obj_app = app::get($key);
                    if($obj_app->is_installed()){
                        $obj_model = $obj_app->model($temp['m2']);
                        if($key == 'auction'){
                            foreach($obj_model->getList("gid as goods_id,last_price as price,(select count(1) from sdb_auction_activitylog where act_id={$obj_model->table_name(1)}.id) as bid_count",array('gid'=>$value),0,-1) as $item){
                                $obj_data[$item['goods_id'].'_'.$key]['price'] = $item['price'];
                                $obj_data[$item['goods_id'].'_'.$key]['bid_count'] = $item['bid_count'];
                            }
                        }else{
                            foreach($obj_model->getList('gid as goods_id,last_price as price',array('gid'=>$value),0,-1) as $item){
                                $obj_data[$item['goods_id'].'_'.$key]['price'] = $item['price'];
                            }
                        }
                    }
                }
            }
        }
        $filter['tag_id'] = array();
        foreach($data as $key => &$value){
            if(empty($value['item']))continue;
            foreach($value['item'] as $ckey => &$cvalue){
                $temp_key = $cvalue['goods_id'].'_'.($cvalue['object_type']=='normal'?'b2c':$cvalue['object_type']);
                if(array_key_exists($temp_key,$obj_data)){
                    $cvalue = array_merge($cvalue,$obj_data[$temp_key]);
                }else{
                    $obj_tag_rel->delete(array('tag_id'=>$value['tag_id'],'rel_id'=>$cvalue['goods_id'],'app_id'=>($cvalue['object_type']=='normal'?'b2c':$cvalue['object_type']),'tag_type'=>($cvalue['object_type']=='normal'?'goods':'activity')));
                    unset($data[$key]['item'][$ckey]);
                    if(count($value['item']) < $limit){
                        $filter['tag_id'][] = $value['tag_id'];
                    }
                }
            }
        }
        if(!empty($filter['tag_id'])){
            $this->rec_tags(&$data, $filter, $offset, $limit, $picSize);
        }else{
            foreach($data as $key => &$value){
                if(empty($value['item']))unset($data[$key]);
                $value['item'] = array_values($value['item']);
            }
            $data = array_values($data);
        }
    }
    
    private function app_return($data){
        $app = array(
            'score',
            'group',
            'spike',
            'package',
            'auction',
        );
        if(!in_array($data,$app))return;
        switch($data){
            case 'scorebuy':
                $app_list = array(
                    'app' => 'scorebuy',
                    'm1' => 'activity',
                    'm2' => 'scoreapply',
                );
                break;
            case 'groupbuy':
                $app_list = array(
                    'app' => 'groupbuy',
                    'm1' => 'activity',
                    'm2' => 'groupapply',
                );
                break;
            case 'spike':
                $app_list = array(
                    'app' => 'spike',
                    'm1' => 'activity',
                    'm2' => 'spikeapply',
                );
                break;
            case 'package':
                $app_list = array(
                    'app' => 'package',
                    'm1' => 'activity',
                    'm2' => 'attendactivity',
                );
                break;
            case 'auction':
                $app_list = array(
                    'app' => 'auction',
                    'm1' => 'activity',
                    'm2' => 'groupapply',
                );
                break;
        }
        return $app_list;
    }
    
    function gethot(){
        $params = $this->params;
        $limit = $params['pageLimit']?intval($params['pageLimit']):10;
        $offset = $params['nPage']?(intval($params['nPage'])-1)*$limit:0;
        $picSize = in_array(strtolower($params['pic_size']), array('cl', 'cs'))?strtolower($params['pic_size']):'cl';
        
        $obj_store = app::get('business')->model('storemanger');
        $data=$obj_store->getList("store_id,store_name,image,area,store_grade,(select count(view_count) from sdb_b2c_goods where store_id={$obj_store->table_name(1)}.store_id) as view_count,(select count(1) from sdb_b2c_goods where store_id={$obj_store->table_name(1)}.store_id) as goods_count",null,$offset,$limit,'view_count desc'); 
        $temp_store_array=utils::array_change_key($data,'store_id', 1);
        $store_filter['store_id']=array_keys($temp_store_array['store']);
        $business_brand=app::get('business')->model('brand')->getList('brand_name,store_id',$store_filter);
        $storegrade=app::get('business')->model('storegrade')->getlist('grade_id,issue_type');
        $storegrade=utils::array_change_key($storegrade,'grade_id', 0);
        $brands=utils::array_change_key($business_brand,'store_id', 1);
        foreach($data as $key=>&$store){
            $store['logo']=base_storager::image_path($store['image'],'s');
            unset($store['image']);
            $store['issue_type']=$storegrade[$store['store_grade']]['issue_type'];
            $store['brand']=array_map('current',$brands[$store['store_id']]);
            unset($store['store_grade']);
        }
        $data = empty($data)?null:$data;
        $this->send(true, $data, app::get('b2c')->_('success'));
    }

       /*  获取站内通知*/
    function getnotice(){
        $params = $this->params;
        $must_params = array(
            'session'=>'会员ID',
        );
        $this->check_params($must_params);
        
        $limit = $params['pageLimit']?intval($params['pageLimit']):10;
        $offset = $params['nPage']?(intval($params['nPage'])-1)*$limit:0;
        $member = $this->get_current_member();
        if(empty($member['member_id'])){
        $this->send(false,null,app::get('b2c')->_('session失效或您还未登录'));
        }
        $member_id = $member['member_id']; 
        $oMsg = kernel::single('b2c_message_msg');
        $filter_sql = "(author_id is not null and author_id in (select account_id from sdb_pam_account where account_type='shopadmin'))";
        $data = $oMsg->getList('title,comment,mem_read_status,author',array('to_id' => $member_id,'has_sent' => 'true','for_comment_id' => 'all','inbox' =>'true', 'filter_sql'=>$filter_sql),$offset,$limit);
        $data = empty($data)?null:$data;
        $this->send(true, $data, app::get('b2c')->_('站内通知'));
    }

     /*获取站内信*/
    function getletter(){
        $params = $this->params;
        $must_params = array(
            'session'=>'会员ID',
        );
        $this->check_params($must_params);
        
        $limit = $params['pageLimit']?intval($params['pageLimit']):10;
        $offset = $params['nPage']?(intval($params['nPage'])-1)*$limit:0;
        $member = $this->get_current_member();
        if(empty($member['member_id'])){
        $this->send(false,null,app::get('b2c')->_('session失效或您还未登录'));
        }
        $member_id = $member['member_id'];        
        $oMsg = kernel::single('b2c_message_msg');
        $filter_sql = "(author_id is not null and author_id not in (select account_id from sdb_pam_account where account_type='shopadmin'))";
        $data = $oMsg->getList('title,comment,mem_read_status,author',array('to_id' => $member_id,'has_sent' => 'true','for_comment_id' => 'all','inbox' =>'true', 'filter_sql'=>$filter_sql),$offset,$limit);
        $data = empty($data)?null:$data;
        $this->send(true, $data, app::get('b2c')->_('站内信'));
    }

    /**返回平台开通的实体店铺**/
    function getshoplist(){
    
        $params = $this->params; 
        $limit = $params['pageLimit']?intval($params['pageLimit']):10;
        $offset = $params['nPage']?(intval($params['nPage'])-1)*$limit:0;
        $picSize = in_array(strtolower($params['picSize']), array('cl', 'cs'))? strtolower($params['picSize']):'cs';
        $obj_regions_shop = app::get('ectools')->model('regions_shop');
        $result = $obj_regions_shop->getList('shop_id,shop_name,shop_area,shop_addr,image,add_time',array(),$offset,$limit,'add_time ASC');
        if($result){ 
            foreach($result as &$val){
             $area_arr = explode(':',$val['shop_area']);
             $val['addr'] = str_replace('/','-',$area_arr[1]).$val['shop_addr'] ;
             $val['image'] = $this->get_img_url($val['image'],$picSize);
             unset($val['shop_area'],$val['shop_addr']);
            }
          $this->send(true,$result,app::get('b2c')->_('门店列表'));
        }else{
          $this->send(true,null,app::get('b2c')->_('没有门店'));
        }
        
    }
   
}