
<?php
/**
 * ShopEx licence
 *
 * @copyright  Copyright (c) 2005-2010 ShopEx Technologies Inc. (http://www.shopex.cn)
 * @license  http://ecos.shopex.cn/ ShopEx License
 */
class cellphone_base_auction_info extends cellphone_cellphone{
	var $pageLimit = 5;
	var $page=1;
	var $picSize = 'cs';
	
    public function __construct($app){
        parent::__construct();
        $this->app = $app;
    }


   //获取拍品详情
    function getauction(){
		$params = $this->params;

	   if(!isset($params['picSize']) || empty($params['picSize'])){
		  $params['picSize'] = $this->picSize;
	   }
	   if($params['picSize'] != 'cs' && $params['picSize'] != 'cl'){
		  $params['picSize'] = $this->picSize;
	   }
	   $picSize=$params['picSize'];
	   $apply_id=$params['apply_id'];
	   $oActivity=app::get('auction')->model('activity');
	   $oImage_attach = app::get('image')->model('image_attach');
	   
		$curtime=time();
		$is_price=false;
		$data=array();
	    $sql='select  apply.gid,apply.start_price,apply.amplitude,apply.fixed_price,apply.last_price,apply.delayed_time,apply.auction_end_time,'
				.'apply.log_count,store.store_name,store.area,act.start_time,goods.comments_count,goods.name,goods.freight_bear,apply.pledge_price,  '
				.'dly.dt_expressions,goods.weight,goods.price,dly.firstprice,dly.continueprice,dly.dt_name '
				.'from sdb_auction_groupapply as apply     '
				.'inner join sdb_auction_activity as act on apply.aid=act.act_id  '
				.'inner join sdb_b2c_goods as goods on apply.gid=goods.goods_id '
				.'left join sdb_b2c_goods_dly as gdly on gdly.goods_id=goods.goods_id '
				.'left join sdb_b2c_dlytype as dly on dly.dt_id=gdly.dly_id  '
				.'left join sdb_business_storemanger as store on apply.member_id=store.account_id  where apply.id='.$apply_id;
		 $data=$oActivity->db->selectrow($sql);
		 $fee=@utils::cal_fee($data['dt_expressions'],$data['weight'],$data['price'],$data['firstprice'],$data['continueprice']);
		 unset($data['dt_expressions'],$data['weight'],$data['price'],$data['firstprice'],$data['continueprice']);
		 $data['cost_fee']=$fee;
		$start_time=$data['start_time'];
		$delayed_time=$data['delayed_time']?$data['delayed_time']:$data['auction_end_time'];
		$diff_time=abs($start_time-$delayed_time);
		//竞价周期
		if($diff_time/86400>1){
				$data['auc_days']=round($diff_time/86400).'天';
		}else if($diff_time/3600>1){
				$data['auc_days']=round($diff_time/3600).'小时';
		}else if($diff_time/60>1){
				$data['auc_days']=round($diff_time/60).'分';
		}else{
				$data['auc_days']=round($diff_time).'秒';
		}
		$data['delayed_period']=5;//延时周期
		 $images=array();
		 $images=$oImage_attach->getList('image_id',array('target_type'=>'goods','target_id'=>$data['gid']));
		 if(!empty($data)){
			 foreach($images as $key=>&$val){
					 $val['image_id']=$this->get_img_url($val['image_id'],$picSize);
			 }
			$area=explode(':',$data['area']);
			$area=explode('/',$area[1]);
			$data['area']=$area[0].$area[1];
			$data['images']=$images;
			$this->send(true,$data,app::get('cellphone')->_('拍品详情'));
		 }else{
			$this->send(true,null,app::get('cellphone')->_('没有数据'));
		 }
		 
   }

   //按钮状态
   function checkstatus(){
	    $params = $this->params;

        //检查应用级必填参数
        $must_params = array(
            'session'=>'会员ID'
        );
        $this->check_params($must_params);
        //$member_id=$params['member_id'];
        //$obj_members = app :: get('b2c') -> model('members');
        //$member=$obj_members->get_member_info($member_id);
        $member=$this->get_current_member();
        $member_id=$member['member_id'];

		$apply_id=$params['apply_id'];
		$curtime = time();
		$status;
		$oPricelog=app::get('auction')->model('pricelog');
		$sql='select sum(price) as _count from sdb_auction_pricelog where member_id='.$member_id.' and 
		 apply_id='.$apply_id.' and status <>\'1\' having _count>='.$data['pledge_price'];
		$price_log=$oPricelog->db->selectrow($sql);
		if(!$price_log['_count']){
			$status=1;
		}else{
			$sql='select act.start_time,apply.delayed_time from sdb_auction_activity as act '
					.' inner join sdb_auction_groupapply as apply on act.act_id=apply.aid where apply.id='.$apply_id;
			$apply=$oPricelog->db->select($sql);
			if($apply['start_time'] && $curtime<$apply['start_time']){
				$status=2;
			}else if(($apply['start_time'] && $curtime>$apply['start_time']) && ($apply['delayed_time'] && $curtime<=$apply['delayed_time'])){
				$status=3;
			}else if($apply['delayed_time'] && $curtime>$apply['delayed_time']){
				$status=4;
			}
		}
		$data['status']=$status;
		$this->send(true,$data,app::get('cellphone')->_('按钮状态'));
   }

	
	//所有出价
	  function getlogprice(){
			$params = $this->params;
			$apply_id=$params['apply_id'];
			$price_log=array();
			$oActivitylog=app::get('auction')->model('activitylog');
			$filter=array('act_id'=>$apply_id);
			$price_log=$oActivitylog->db->select('SELECT a.bid_user,a.is_name,b.login_name,a.bid_price,a.bid_time from sdb_auction_activitylog  as a inner join  sdb_pam_account as b on  a.bid_user=b.account_id  where a.act_id='.$apply_id.' order by a.bid_price desc ');
			$aData['total']=$oActivitylog->count($filter);
			$res_is_name=array();
			$res_is_name=$oActivitylog->db->select('SELECT a.bid_user from sdb_auction_activitylog  as a inner join  sdb_pam_account as b on  a.bid_user=b.account_id  where a.act_id='.$apply_id.'  group by a.bid_user,a.is_name having a.is_name=\'true\' order by a.bid_price desc');
			foreach($res_is_name as $n_k=>$n_v){
				foreach($price_log as $r_k=>&$r_v){
					if($n_v['bid_user']==$r_v['bid_user']){
						$r_v['login_name']=substr($r_v['login_name'],0,1).'**'.substr($r_v['login_name'],-1,1);
					}
				}
			}
			$aData['data']=$price_log;
			if(!empty($aData['data'])){
					$this->send(true,$aData,app::get('cellphone')->_('出价记录'));
			}else{
					$this->send(true,null,app::get('cellphone')->_('没有数据'));
			}

			
	 }

	 //出价
	 function toprice(){
			$Oactivity= app::get('auction')->model('activitylog');
			$params = $this->params;

			//检查应用级必填参数
			$must_params = array(
				'session'=>'会员ID'
			);
			$this->check_params($must_params);
			//$member_id=$params['member_id'];
			//$obj_members = app :: get('b2c') -> model('members');
			//$member=$obj_members->get_member_info($member_id);
			$member=$this->get_current_member();
			$member_id=$member['member_id'];
			$data['act_id']=$params['apply_id'];
			$data['is_name']=$params['is_name'];
			$data['member_id']=$member_id;
			$data['bid_price']=$params['bid_price'];
			$response=$Oactivity->add_actlog($data);
			$this->send(true,$response,app::get('cellphone')->_('出价'));
	 }


	//拍卖活动列表
	function getactlist(){
			$params = $this->params;
			$Oact=app::get('auction')->model('activity');
		   if(!isset($params['picSize']) || empty($params['picSize'])){
			  $params['picSize'] = $this->picSize;
		   }
		   if($params['picSize'] != 'cs' && $params['picSize'] != 'cl'){
			  $params['picSize'] = $this->picSize;
		   }
		   $picSize=$params['picSize'];
			$aData=array();
			$sql='select act.name,act.description,act.start_time,act.end_time,act.act_pic,apply.goods_count from sdb_auction_activity as act '
					 .'inner join (select aid,sum(gid) as goods_count from sdb_auction_groupapply group by aid) as apply on apply.aid=act.act_id   ';
			if($params['date']){
				$date=strtotime($params['date']);
				$sql.=' where act.start_time <'.$date.' and act.end_time >='.$date;
			}
			$aData=$Oact->db->select($sql);
			if(!empty($aData)){
				foreach($aData as $key=>&$val){
						$val['act_pic']=$this->get_img_url($val['act_pic'],$picSize);
				}
				$this->send(true,$aData,app::get('cellphone')->_('拍卖活动列表'));
			}else{
				$this->send(true,null,app::get('cellphone')->_('没有数据'));
			}
	}

	//拍卖活动详情
	function getact(){
			$params=$this->params;
			$aData=array();
		   if(!isset($params['picSize']) || empty($params['picSize'])){
			  $params['picSize'] = $this->picSize;
		   }
		   if($params['picSize'] != 'cs' && $params['picSize'] != 'cl'){
			  $params['picSize'] = $this->picSize;
		   }
			if($params['act_id']){
				$act_id=explode(',',$params['act_id']);
				$Oact=app::get('auction')->model('activity');
				
				$aData=$Oact->getList('act_id,name,description,start_time,end_time,act_pic,delayed_period_max,delayed_duration_max',array('act_id'=>$act_id));	
			}
			if(!empty($aData)){
					foreach($aData as $key=>&$val){
						$val['act_pic']=$this->get_img_url($val['act_pic'],$picSize);
					}
					$this->send(true,$aData,app::get('cellphone')->_('拍卖活动详情'));
			}else{
					$this->send(true,null,app::get('cellphone')->_('没有数据'));
			}
	}
	
	//拍卖下拍品
	function getactapply(){
			$Oact=app::get('auction')->model('activity');
		   if(!isset($params['picSize']) || empty($params['picSize'])){
			  $params['picSize'] = $this->picSize;
		   }
		   if($params['picSize'] != 'cs' && $params['picSize'] != 'cl'){
			  $params['picSize'] = $this->picSize;
		   }
			$picSize=$params['picSize'];			
			$params=$this->params;
			$act_id=$params['act_id'];
			$aData=array();
			$sql='select  act.act_id,act.name,act.description,act.start_time,act.end_time,act.act_pic,ifnull(store.store_name,\'平台发起\') as store_name from sdb_auction_activity as act '
					.'left join  sdb_business_storemanger as store on act.act_type=store.store_id  where act.act_id='.$act_id;
			$act=$Oact->db->selectrow($sql);
			$act['act_pic']=$this->get_img_url($act['act_pic'],$picSize);
			$aData['act']=$act;
			$sql='select apply.id,goods.name,apply.last_price as price,act.start_time,apply.delayed_time,apply.gid,goods.image_default_id from sdb_auction_activity as act '
					.'inner join sdb_auction_groupapply as apply on act.act_id=apply.aid '
					.'inner join sdb_b2c_goods as goods on apply.gid=goods.goods_id ';
			$goods=array();
			$goods=$Oact->db->select($sql);
			foreach($goods as $key=>&$val){
				$val['image_default_id']=$this->get_img_url($val['image_default_id'],$picSize);
			}
			$aData['goods']=$goods;
			if(!empty($aData['act']) && !empty($aData['goods'])){
				$this->send(true,$aData,app::get('cellphone')->_('拍卖活动下拍品'));
			}else{
				$this->send(true,null,app::get('cellphone')->_('没有数据'));
			}
			
	}





	//获取拍卖活动列表
	function getattend(){
		$params = $this->params;
		$Oact=app::get('auction')->model('activity');
        //检查应用级必填参数
        $must_params = array(
            'session'=>'会员ID'
        );
        $this->check_params($must_params);
        //$member_id=$params['member_id'];
        //$obj_members = app :: get('b2c') -> model('members');
        //$member=$obj_members->get_member_info($member_id);
        $member=$this->get_current_member();
        $member_id=$member['member_id'];
		$sto= kernel::single("business_memberstore",$member_id);
		$store=$sto->storeinfo;
	
		$store_region=array_keys($store['store_region']);
		$store_cat=$store['issue_type'];
		$store_grade=$store['store_grade'];
        $oActivity = app::get('auction')->model('activity');
		 $Ostore=app::get('business')->model('storemanger');
        $nowTime = time();


		$sql='select act.act_id,act.name,act.start_time,act.end_time,act.act_open,act.business_type,act.act_type,act.store_type,'
				.' act.store_lv,act.sell_price,plog.log_price from sdb_auction_activity as act '
				.' left join (select member_id,sum(price) as log_price,act_id  from sdb_auction_spricelog where status<>\'1\'  and member_id ='.$member_id.' group by member_id,act_id ) as ' 
				.' plog  on act.act_id=plog.act_id  where  act.act_open=\'true\'  '
				.' and act.apply_start_time<='.$nowTime
				.' and act.apply_end_time>='.$nowTime;
        $activityInfo = $oActivity->db->select($sql);
		
		$strore_ids=array();
        $now = time();
        foreach($activityInfo as $k=>$v){
            if($now > $v['end_time']){
                unset($activityInfo[$k]);
            }
        }
		$price_log=kernel::service('price_log');
        if($price_log){
		    $price_arr=$price_log->check_sell_price($member_id);
        }
	
        foreach($activityInfo as $k=>$v){
            if($store_region){
                $businee_type = array();
                $businee_type =array_filter(explode(',',$v['business_type']));
                $ret = array_intersect($businee_type,$store_region);
				
                if(!$ret){
                    unset($activityInfo[$k]);
                }

            }

            $act_store_cat = explode(',',$v['store_type']);
            if(!in_array($store_cat,$act_store_cat)){
                unset($activityInfo[$k]);
            }

            $act_store_grade = array_filter(explode(',',$v['store_lv']));
            if(!in_array($store_grade,$act_store_grade)){
                unset($activityInfo[$k]);
            }
			$store_ids[]=$v['act_type'];

        }

		$shopInfo=$Ostore->getList('store_id,store_name',array('shop_id'=>$store_ids));
		foreach($activityInfo as $k=>$v){
			
			$activityInfo[$k]['is_price']=0;
			foreach($price_arr as $pk=>$pv){
				if($v['act_id']==$pv['act_id'] && $pv['_count']>=$v['sell_price'] ){
					$activityInfo[$k]['is_price']=1;
				}
			}
			foreach($shopInfo as $sk=>$sv){
					if($v['act_type']!=0 && $sv['store_id']==$v['act_type'] ){
							$activityInfo[$k]['store_name']=$sv['store_name'];
					}else if($v['act_type'] ==0){
							$activityInfo[$k]['store_name']='平台发起';
					}
			}
            $activityInfo[$k]['start_time'] = date('Y-m-d',$v['start_time']);
            $activityInfo[$k]['end_time'] = date('Y-m-d',$v['end_time']);
			$activityInfo[$k]['surplus_price']=$activityInfo[$k]['sell_price']-$activityInfo[$k]['log_price'];
			unset($activityInfo[$k]['business_type'],$activityInfo[$k]['act_type'],$activityInfo[$k]['store_type'],$activityInfo[$k]['store_lv']);
		}
	

		$this->send(true,$activityInfo,app::get('cellphone')->_('拍卖活动列表'));
		
	}


	//获取商户经营范围
	function getbusinesstype(){
			$Ocat=app::get('b2c')->model('goods_cat');
			$cats=$Ocat->getList('cat_id,cat_name',array('parent_id'=>0));
			if(!empty($cats)){
				$this->send(true,$cats,app::get('cellphone')->_('商户经营范围'));
			}else{
				$this->send(true,null,app::get('cellphone')->_('没有数据'));
			}
	}

	//获取店铺类型
	function getstoretype(){
      $store_type=array (
        0 => app::get('b2c')->_('卖场型旗舰店'),
        1 => app::get('b2c')->_('专卖店'),
        2 => app::get('b2c')->_('专营店'),
        3 => app::get('b2c')->_('品牌旗舰店'),
      );
	$this->send(true,$store_type,app::get('cellphone')->_('店铺类型'));
	}
	
	//获取店铺等级
	function getstorelv(){
		$obj_storegrade = app :: get('business') -> model('storegrade');
        $storegrade =  $obj_storegrade->getList('grade_id,grade_name', array('disabled'=>'false','default_lv'=>'1'));
        $this->send(true,$storegrade,app::get('cellphone')->_('店铺等级'));
	}
	
	//获取我参加的活动列表
	function getmyapply(){
		$params = $this->params;
		//检查应用级必填参数
		$must_params = array(
			'session'=>'会员ID'
		);
		$this->check_params($must_params);

		//$member_id=$params['member_id'];
		//$obj_members = app :: get('b2c') -> model('members');
		//$member=$obj_members->get_member_info($member_id);
		$member=$this->get_current_member();
		   if($params['picSize'] != 'cs' && $params['picSize'] != 'cl'){
			  $params['picSize'] = $this->picSize;
		   }
		$picSize=$params['picSize'];
		$member_id=$member['member_id'];
		$is_platform=$params['is_platform']?$params['is_platform']:0;
		$storemember = app::get('business')->model('storemember');
		$storemanger=app::get('business')->model('storemanger');
		$businessActivity =app::get('auction')->model('groupapply');
		$aGood=app::get('b2c')->model('goods');
		$aActivity=app::get('auction')->model('activity');
        $store_id = $storemember->getList('store_id',array('member_id'=>$member_id));
        if(!$store_id){
            $store_id = $storemanger->getList('store_id',array('account_id'=>$member_id));
        }
		$store_id=$store_id[0]['store_id'];
		$now_time=time();
		if($is_platform==0){
		$sql='select act.act_id,act.name as act_name,goods.goods_id,apply.id as apply_id,goods.name as goods_name,ifnull(store.store_name,\'平台发起\') as store_name,apply.status,apply.remark,act.start_time,act.end_time,apply.delayed_time,apply.id as apply_id,apply.last_price,'
				.' case when apply.delayed_time<'.$now_time.' then goods.price else apply.last_price end as	cur_price, '
				.' case when apply.delayed_time<'.$now_time.' and ifnull(apply.last_price,0)-ifnull(apply.fixed_price,0)<0 then \'流拍\'  '
				.' when apply.delayed_time<'.$now_time.' and ifnull(apply.last_price,0)-ifnull(apply.fixed_price,0)>0 then \'成功\' '
				.' when '.$now_time.'>act.start_time and '.$now_time.'<apply.delayed_time then \'拍卖中\' end as act_open, '
				.' ifnull(apply.image_codeid,goods.image_default_id) as img '
				.'from sdb_auction_groupapply as apply '
				.'inner join sdb_auction_activity as act on apply.aid=act.act_id '
				.'inner join sdb_b2c_goods as goods on apply.gid=goods.goods_id '
				.'left join sdb_business_storemanger as store on store.store_id=apply.act_type  '
				.' where apply.store_id='.$store_id;
		}else if($is_platform==1){
		$sql='select act.act_id,act.name as act_name,goods.goods_id,apply.id as apply_id,goods.name as goods_name,ifnull(store.store_name,\'平台发起\') as store_name,apply.status,apply.remark,act.start_time,act.end_time,apply.delayed_time,apply.id as apply_id,apply.last_price, '
				.' case when apply.delayed_time<'.$now_time.' then goods.price else apply.last_price end as	cur_price, '
				.' case when apply.delayed_time<'.$now_time.' and ifnull(apply.last_price,0)-ifnull(apply.fixed_price,0)<0 then \'流拍\'  '
				.' when apply.delayed_time<'.$now_time.' and ifnull(apply.last_price,0)-ifnull(apply.fixed_price,0)>0 then \'成功\' '
				.' when '.$now_time.'>act.start_time and '.$now_time.'<apply.delayed_time then \'拍卖中\' end as act_open, '	
				.' ifnull(apply.image_codeid,goods.image_default_id) as img '
				.'from sdb_auction_groupapply as apply '
				.'inner join sdb_auction_activity as act on apply.aid=act.act_id '
				.'inner join sdb_b2c_goods as goods on apply.gid=goods.goods_id '
				.'left join sdb_business_storemanger as store on store.store_id=apply.act_type  '
				.' where  act.act_type=0 and  apply.store_id='.$store_id;			
		}else if($is_platform==2){
			$sql='select act.act_id,act.name as act_name,goods.goods_id,apply.id as apply_id,goods.name as goods_name,ifnull(store.store_name,\'平台发起\') as store_name,apply.status,apply.remark,act.start_time,act.end_time,apply.delayed_time,apply.id as apply_id,apply.last_price ,'
				.' case when apply.delayed_time<'.$now_time.' then goods.price else apply.last_price end as	cur_price, '
				.' case when apply.delayed_time<'.$now_time.' and ifnull(apply.last_price,0)-ifnull(apply.fixed_price,0)<0 then \'流拍\'  '
				.' when apply.delayed_time<'.$now_time.' and ifnull(apply.last_price,0)-ifnull(apply.fixed_price,0)>0 then \'成功\' '
				.' when '.$now_time.'>act.start_time and '.$now_time.'<apply.delayed_time then \'拍卖中\' end as act_open, '		
				.' ifnull(apply.image_codeid,goods.image_default_id) as img '
				.'from sdb_auction_groupapply as apply '
				.'inner join sdb_auction_activity as act on apply.aid=act.act_id '
				.'inner join sdb_b2c_goods as goods on apply.gid=goods.goods_id '
				.'left join sdb_business_storemanger as store on store.store_id=apply.act_type  '
				.' where  act.act_type<>0  and apply.store_id='.$store_id;						
		}
		$busineeActivityInfo = $businessActivity->db->select($sql);
		$store_ids=array();
		$status_arr=array(
                1=>__('待审核'),
                2=>__('审核通过'),
                3=>__('审核不通过'),
				4=>__('已结束'),
				5=>__('已下单')
            );
		$image_default=$this->app->getConf('image.set');
		$image_default=$this->get_img_url($image_default[strtoupper($picSize)]['default_image'],$picSize);
        foreach($busineeActivityInfo as $k=>&$v){
            if($v['status'] == '2'){
                $args = array($v['goods_id'],'','',$v['apply_id']);
                $v['goodsLink'] = app::get('site')->router()->gen_url( array('app'=>'auction','ctl'=>'site_product','act'=>'index','args'=>$args) );
				
            }else{
                $v['goodsLink'] = app::get('site')->router()->gen_url( array('app'=>'b2c','ctl'=>'site_product','act'=>'index','arg0'=>$v['gid']));
            }
			//$v['status']=$status_arr[$v['status']];
			$v['img']=$this->get_img_url($v['img'],$picSize)!=''?$this->get_img_url($v['img'],$picSize):$image_default;
			
			//$v['act_open']=$v['act_open']=='true'?'开启':'未开启';
			
        }
		$platform_count=$businessActivity->count(array('act_type'=>0,'store_id'=>$store_id));
		$person_count=$businessActivity->count(array('act_type|noequal'=>0,'store_id'=>$store_id));
		$data['act']=$busineeActivityInfo;
		$data['count']=array('platform_count'=>$platform_count,'person_count'=>$person_count);
		if(!empty($data['act'])){
			$this->send(true,$data,app::get('cellphone')->_('我参加的活动列表'));
		}else{
			$this->send(true,null,app::get('cellphone')->_('没有数据'));
		}
	}



	//查看活动详情
	function getapplydel(){
		$params = $this->params;
		$id=$params['apply_id'];
		 $applyObj = app::get('auction')->model('groupapply');
        $info = $applyObj->loadActInfoById($id);
        if($info){
            $act_open = array('true'=>'开启','false'=>'关闭');
            $status = array('1'=>'未审核','2'=>'审核通过','3'=>'审核未通过');
            $info['start_time'] = date('Y-m-d H:i:s',$info['start_time']);
            $info['end_time'] = date('Y-m-d H:i:s',$info['end_time']);
			$info['delayed_time']=date('Y-m-d H:i:s',$info['delayed_time']);
            $goodsObj = app::get('b2c')->model('goods');
            $goods = $goodsObj->dump(array('goods_id'=>$info['gid']),'name');
            $info['goods_name'] = $goods['name'];
            $info['act_open'] = $act_open[$info['act_open']];
            $info['status'] = $status[$info['status']];
			unset($info['gid'],$info['cat_id'],$info['price'],$info['last_price'],$info['act_store'],$info['remainnums'],$info['personlimit']);
        }
		if(!empty($info)){
			$this->send(true,$info,app::get('cellphone')->_('活动详情'));
		}else{
			$this->send(true,null,app::get('cellphone')->_('没有数据'));
		}
	}


	//活动活动信息(活动结束时间,延时时段,延时时长)
	function getactinfo(){
			$params = $this->params;
			$act_id=$params['act_id'];
			$actObj = app::get('auction')->model('activity');
			$act=$actObj->getList('name,start_time,end_time',array('act_id'=>$act_id));
			if(!empty($act)){
				$this->send(true,$act,app::get('cellphone')->_('活动信息'));
			}else{
				$this->send(true,null,app::get('cellphone')->_('没有数据'));
			}
	}

	//保存发起拍卖
	function tosaveact(){
			$params = $this->params;
			$status=true;
			$msg='';
			$data=array();
			$Oact=app::get('auction')->model('activity');
			$params['act_open']='true';//发起拍卖默认开启
			if(!$params['store_id']){
				$msg='店铺id不能为空';
			}else{
				$params['act_type']=$params['store_id'];
			}
			if(!$params['name']){
				$msg='活动名称不能为空';
			}

			if($params['delayed_num']){
				if(!preg_match('/^\+?[1-9][0-9]*$/',$params['delayed_num'])){
					  $msg='延迟次数只能是数字';				
				}
			}else{
				$msg='延迟次数不能为空';
			}

			if($params['delayed_period_max']){
				if(!preg_match('/^\+?[1-9][0-9]*$/',$params['delayed_period_max'])){
					  $msg='最大延迟时段只能是数字';				
				}
			}else{
				$msg='最大延迟时段不能为空';
			}

			if($params['sell_price']){
				if(!preg_match('/^\+?[1-9][0-9]*$/',$params['sell_price'])){
					  $msg='卖家保证金只能是数字';				
				}else if($params['sell_price']<=0){
						$msg='卖家保证金必须大于零';
				}
			}else{
				$msg='卖家保证金不能为空';
			}


			if($params['delayed_duration_max']){
				if(!preg_match('/^\+?[1-9][0-9]*$/',$params['delayed_duration_max'])){
					  $msg='最大延迟时长只能是数字';				
				}
			}else{
				$msg='最大延迟时长不能为空';
			}

			if(!$params['apply_start_time']){
				$msg='活动申请开始时间不能为空';
			}
			
			if(!$params['apply_end_time']){
				$msg='活动申请结束时间不能为空';
			}
			
			if($params['apply_start_time']>$params['apply_end_time']){
				$msg='活动申请开始时间不能大于结束时间';
			}
			
			if(!$params['start_time']){
				$msg='活动开始时间不能为空';
			}
			
			if(!$params['end_time']){
				$msg='活动结束时间不能为空';
			}
			
			if($params['start_time']>$params['end_time']){
				$msg='活动开始时间不能大于结束时间';
			}
			if(!$params['business_type']){
				$msg='商户经营范围不能为空';
			}
			if(!$params['store_type']){
				$msg='店铺类型不能为空';
			}
			if(!$params['store_lv']){
				$msg='店铺等级不能为空';
			}

			if($msg==''){
			  $data['status']=1;
			  $data['msg']='发起拍卖成功';
			  $res=$Oact->save($params);
			  if($res){
				$this->send(true,$data,app::get('cellphone')->_('发起拍卖成功'));
			  }else{
				    $data['status']=0;
					  $data['msg']='发起失败';
					$this->send(true,$data,app::get('cellphone')->_('发起失败'));
			  }
			}else{
				$data['status']=0;
				$data['msg']=$msg;
				$this->send(true,$data,app::get('cellphone')->_($msg));
			}



	}

	//保存拍卖申请
	function tosaveapply(){
			$params = $this->params;  
			$status=true;
			$msg='';
			$data=array();
			$Oact=app::get('auction')->model('activity');
			$oGoods=app::get('b2c')->model('goods');
			$Oapply=app::get('auction')->model('groupapply');
			$storemember = app::get('business')->model('storemember');
			$storemanger = app::get('business')->model('storemanger');
			//检查应用级必填参数
			$must_params = array(
				'session'=>'会员ID'
			);
			$this->check_params($must_params);
			//$member_id=$params['member_id'];
			//$obj_members = app :: get('b2c') -> model('members');
			//$member=$obj_members->get_member_info($member_id);
			$member=$this->get_current_member();
			$member_id=$member['member_id'];
			$params['num']=1;//拍卖商品数量默认为1
			if(!$params['aid']){
				$msg='活动id不能为空';
			}
			$act=$Oact->dump(array('act_id'=>$params['aid']),'delayed_period_max,delayed_duration_max,sell_price,start_time,end_time');
			 if(!$act){
              $msg='活动不存在';
            }else{
				$params['act_type']=$act['act_type'];
			}
			if(!$params['gid']){
				$msg='参加活动的商品不能为空';
			}else{
				$goods_arr = $oGoods->dump(array('goods_id'=>$params['gid']),'act_type,goods_id,cat_id,price,store');
				$params['cat_id'] = $goods_arr['category']['cat_id'];	
				if($goods_arr['act_type'] && $goods_arr['act_type']!='normal'){
					$msg='该商品已参加过该活动或其他活动！';
				}
				if($act['nums'] && $goods_arr['store'] < $act['nums']){
					$msg='商品库存不能小于平台设置最低库存';
				}
				if($params['nums']&&$params['nums']>$goods_arr['store']){
					$msg='参加活动的商品数量不能大于库存';
				}
			}
			if(!$member_id){
				$msg='申请商家不能为空';
			}else{
				$params['store_id'] = $storemember->getList('store_id',array('member_id'=>$member_id));
				if(!$params['store_id']){
					$params['store_id'] = $storemanger->getList('store_id',array('account_id'=>$member_id));
				}
				$params['store_id']=$params['store_id'][0]['store_id'];
			}

			if($member_id && $params['aid'] && $act['sell_price']){
				$sql='select sum(price) as s_price from sdb_auction_spricelog where member_id='.$member_id.' and act_id='.$params['aid'];
			
				$spcount=$Oact->db->selectrow($sql);
				$sprice=$spcount['s_price'];
				if($sprice==0 || $sprice<$act['sell_price']){
					$msg='卖家还没交纳足够的保证金';
				}
			}
			if(!$params['start_price']){
				$msg='起拍价不能为空';
			}else{
				if(!preg_match('/^([1-9][\d]{0,7}|0)(\.[\d]+)?$/',$params['start_price'])){
						$msg='起拍价必须是金额';
				}else if($params['start_price']<=0){
					    $msg='起拍价必须大于零';
				}
			}
	
			if(!$params['amplitude']){
				$msg='加价幅度不能为空';
			}else{
				if(!preg_match('/^([1-9][\d]{0,7}|0)(\.[\d]+)?$/',$params['amplitude'])){
						$msg='加价幅度必须是金额';
				}else if($params['amplitude']<=0){
					    $msg='加价幅度必须大于零';
				}
			}
		
			if(!$params['fixed_price']){
				$msg='保留价不能为空';
			}else{
				if(!preg_match('/^([1-9][\d]{0,7}|0)(\.[\d]+)?$/',$params['fixed_price'])){
						$msg='保留价必须是金额';
				}else if($params['fixed_price']<=0){
					    $msg='保留价必须大于零';
				}
			}

			if(!$params['pledge_price']){
				$msg='买家保证金不能为空';
			}else{
				if(!preg_match('/^([1-9][\d]{0,7}|0)(\.[\d]+)?$/',$params['pledge_price'])){
						$msg='买家保证金必须是金额';
				}else if($params['pledge_price']<=0){
					    $msg='买家保证金必须大于零';
				}
			}


			if(!$params['auction_end_time']){
				$msg='拍卖结束时间不能为空';
			}else{
				$params['delayed_time']=$params['auction_end_time'];
			}
			if($act['end_time'] && $params['auction_end_time']>$act['end_time']){
				   $msg='拍卖结束时间不能大于活动结束时间';
			}

			if($act['start_time'] && $params['auction_end_time']<$act['start_time']){
				  $msg='拍卖结束时间不能早于活动开始时间';
			}
			if($act['delayed_period_max'] && $params['delayed_period_time']>$act['delayed_period_max']){
				  $msg='延迟时段不能大于'.$act['delayed_period_max'];
			}
			if($act['delayed_duration_max'] && $params['delayed_duration']>$act['delayed_duration_max']){
				   $msg='延迟时长不能大于'.$act['delayed_duration_max'];
			}
			if(!$params['delayed_period_time']){
					$msg='延迟时段不能为空';
			}else{
					if(!preg_match('/^\+?[1-9][0-9]*$/',$params['delayed_period_time'])){
						$msg='延时时段必须是数字';
					}else if($params['delayed_period_time']<=0){
						$msg='延时时段必须大于零';
					}else if($act['delayed_period_max'] && $params['delayed_period_time']>$act['delayed_period_max']){
						$msg='延时时段不能大于最大延时时段';
					}
			}
			if(!$params['delayed_duration']){
					$msg='延迟时长不能为空';
			}else{
					if(!preg_match('/^\+?[1-9][0-9]*$/',$params['delayed_duration'])){
						$msg='延迟时长必须是数字';
					}else if($params['delayed_duration']<=0){
						$msg='延迟时长必须大于零';
					}else if($act['delayed_duration_max'] && $params['delayed_duration']>$act['delayed_duration_max'] ){
						$msg='延迟时长不能大于最大延时时长';
					}
			}
			if($msg==''){
			  $data['status']=1;
			  $data['msg']='申请拍卖成功';   
			  $res=$Oapply->save($params);
			  if($res){
			   $goods['act_type']='auction';
			   $oGoods->update($goods,array('goods_id'=>$params['gid']));
			   $this->send(true,$data,app::get('cellphone')->_('申请拍卖成功'));
			  }else{
					$data['status']=0;
					$data['msg']='申请拍卖失败';   
					$this->send(true,$data,app::get('cellphone')->_('申请拍卖失败'));
			  }
			}else{
				$data['status']=0;
				$data['msg']=$msg;
				$this->send(true,$data,app::get('cellphone')->_($msg));
			}


	}
	

	//交纳买家保证金
	function topricelog(){
			$params = $this->params;  
			//检查应用级必填参数
			$must_params = array(
				'session'=>'会员ID'
			);
			$this->check_params($must_params);
			//$member_id=$params['member_id'];
			//$obj_members = app :: get('b2c') -> model('members');
			//$member=$obj_members->get_member_info($member_id);
			$member=$this->get_current_member();
			$member_id=$member['member_id'];
			$status=true;
			$msg='';
			$data=array();
			$Opl=app::get('auction')->model('pricelog');		
			$params['status']='0';//默认买家保证金是未退还
			if(!$params['apply_id']){
				$msg='活动申请id不能为空';
			}
			if(!$member_id){
				$msg='会员id不能为空';
			}
			if(!$params['price']){
				$msg='买家保证金不能为空';
			}else{
				if(!preg_match('/^([1-9][\d]{0,7}|0)(\.[\d]+)?$/',$params['price'])){
						$msg='买家保证金必须是金额';
				}else if($params['price']<=0){
					    $msg='买家保证金必须大于零';
				}
			}
			if($msg==''){
	          $sp=kernel::service('price_log');
			  $sp->to_log_price($params);
			  $data['status']=1;
			  $data['msg']='买家保证金交纳成功';
			  $this->send(true,$data,app::get('cellphone')->_('买家保证金交纳成功'));
			}else{
			  $data['status']=0;
			  $data['msg']=$msg;
				$this->send(true,$data,app::get('cellphone')->_($msg));
			}



	}

	//交纳卖家保证金
	function tospricelog(){
			$params = $this->params;  
			$status=true;
			$msg='';
			$data=array();
			$Opl=app::get('auction')->model('pricelog');	
		        //检查应用级必填参数
			$must_params = array(
				'session'=>'会员ID'
			);
			$this->check_params($must_params);
			//$member_id=$params['member_id'];
			//$obj_members = app :: get('b2c') -> model('members');
			//$member=$obj_members->get_member_info($member_id);
			$member=$this->get_current_member();
			$member_id=$member['member_id'];	
			$params['status']='0';//默认卖家保证金是未退还
			if(!$params['act_id']){
				$msg='活动id不能为空';
			}
			if(!$member_id){
				$msg='会员id不能为空';
			}
			if(!$params['price']){
				$msg='卖家保证金不能为空';
			}else{
				if(!preg_match('/^([1-9][\d]{0,7}|0)(\.[\d]+)?$/',$params['price'])){
						$msg='卖家保证金必须是金额';
				}else if($params['price']<=0){
					    $msg='卖家保证金必须大于零';
				}
			}
			if($msg==''){
	          $sp=kernel::service('price_log');
			  $sp->to_log_sprice($params);
			  $data['status']=1;
			  $data['msg']='卖家保证金交纳成功';
			  $this->send(true,$data,app::get('cellphone')->_('卖家保证金交纳成功'));
			}else{
				$data['status']=0;
			    $data['msg']=$msg;
				$this->send(true,$data,app::get('cellphone')->_($msg));
			}



	}



}
