<?php
  
class cellphone_base_misc_activities extends cellphone_cellphone{
    var $position = 'activity';
    public function __construct($app){
        parent::__construct();
        $this->app = $app;
    }
    
    // 获取活动首页信息
    public function get_activity(){
        $params = $this->params;
        if(!isset($params['position']) || empty($params['position'])){
          $params['position'] = $this->position;
        }
        $picSize = in_array(strtolower($params['pic_size']), array('cl', 'cs'))?strtolower($params['pic_size']):'cl';
        $limit = $params['pageLimit']?intval($params['pageLimit']):4;
        $offset = $params['nPage']?(intval($params['nPage'])-1)*$limit:0;
        
        $objAct = app::get('cellphone')->model('activity');
        $filter = array('position'=>$params['position']);
        $aData = $objAct->getList('act_id,act_name,position,layout,banner,logo',$filter,$offset,$limit,'p_order ASC');
        if(!empty($aData)){
            
               foreach((array)$aData as $key => $value){
                    $temp = $objAct->get_detail($value['act_id']);
                   //act_type该活动所属的活动app : groupbuy timedbuy add by cuiqw
                    $aData[$key]['act_type'] = $temp['source']['app'];
                    $aData[$key]['start_time'] = $temp['start_time'];
                    $aData[$key]['end_time'] = $temp['end_time'];
                    $aData[$key]['banner'] = $this->get_img_url($value['banner'],'s');
                    $aData[$key]['logo'] = $this->get_img_url($value['logo'],$picSize);
                  }
               $this->send(true,$aData,app::get('cellphone')->_('活动列表'));
                             
        }else{
           $this->send(true,null,app::get('cellphone')->_('暂无活动'));
        }


    }
    
    // 获取活动列表页信息
    public function get_gallery(){
        $params = $this->params;
        $must_params = array(
            'act_id'=>'活动标识',
        );
        $this->check_params($must_params);
        $picSize = in_array(strtolower($params['pic_size']), array('cl', 'cs'))?strtolower($params['pic_size']):'cl';
        $limit = $params['pageLimit']?intval($params['pageLimit']):10;
        $offset = $params['nPage']?(intval($params['nPage'])-1)*$limit:0;
        
        $objAct = app::get('cellphone')->model('activity');
        $aData = $objAct->dump(array('act_id'=>$params['act_id']),'act_name,banner,source,original_id','default');
        if(empty($aData)){
           $this->send(false,null,app::get('cellphone')->_('该活动不存在'));
        }
        $aData['banner'] = $this->get_img_url($aData['banner'],'s');
        $aData['source'] = unserialize($aData['source']);
        $aData['object_type'] = $aData['source']['app'];//活动类型
        if($aData['source'] && is_array($aData['source'])){
            $act_id = $aData['original_id']?$aData['original_id']:'-1';
            $time = time();
            $app = @app::get($aData['source']['app']);
            $activity = @$app->model($aData['source']['m1']);
            $actapply = @$app->model($aData['source']['m2']);
            if($app && $activity && $actapply){
                $aData['gallery'] = array();
                $objGoods = app::get('b2c')->model('goods');
                $aAct = $activity->getRow('*',array('act_id'=>$aData['original_id']));
                cellphone_misc_exec::get_change($aAct);
                $aAct['act_id'] = $aAct['act_id']?intval($aAct['act_id']):-1;
                $aApply = $actapply->getList('*',array('aid'=>$aAct['act_id'],'status'=>'2'),$offset,$limit);
                cellphone_misc_exec::get_change($aApply);
                if($aAct['start_time'] && $aAct['end_time'] && $aAct['start_time']<=$time && $aAct['end_time']>$time){
                    $goods_id = array();
                    foreach((array)$aApply as $row){
                        $temp = explode(',', $row['gid']);
                        $temp = array_filter($temp);
                        $temp = !empty($temp)?$temp:array(-1);
                        
                        if($app->app_id == 'package'){
                            $price = 0;
                            foreach((array)$objGoods->getList('price',array('goods_id'=>$temp)) as $item){
                                $price += floatval($item['price']);
                            } 
                            $aData['gallery'][] = array(
                                'object_type' => $aData['source']['app'],
                                'goods_id' => $row['act_id'],
                                'image' => $this->get_img_url($row['image'],$picSize),
                                'name' => $row[($actapply->textColumn?$actapply->textColumn:'name')],
                                'real_price' => isset($row['last_price'])?$row['last_price']:$row['price'],
                                'price' => $price,
                                'freight_bear' => $row['freight_bear'],
                            );
                        }else{
                            $tData = $objGoods->getRow('goods_id,name,freight_bear,price,udfimg,thumbnail_pic,image_default_id',array('goods_id'=>$temp));
                            $aData['gallery'][] = array(
                                'object_type' => $aData['source']['app'],
                                'goods_id' => $tData['goods_id'],
                                'image' => $tData['udfimg']=='true'?$this->get_img_url($tData['thumbnail_pic'],$picSize):$this->get_img_url($tData['image_default_id'],$picSize),
                                'name' => $tData['name'],
                                'real_price' => isset($row['last_price'])?$row['last_price']:$row['price'],
                                'price' => $tData['price'],
                                'freight_bear' => $tData['freight_bear'],
                                'start_time'=> $aAct['start_time'],//活动开始时间
                                'end_time'=> $aAct['end_time'],// 活动的结束时间
                                'store'=>$row['store'],       //原始库存 added by cuiqw
                                'buy_count'=>$row['store']-$row['remainnums'],//已购买数量
                                'remainnums'=>$row['remainnums'],  // 剩余库存
                            );
                        }
                    }
                }
            }
        }
        unset($aData['source'], $aData['original_id']);
        $this->send(true,$aData,app::get('cellphone')->_('活动下的商品'));
    }

    /**
      *根据app名 获取相应类型的活动
      *
      *
      */
    function getactbyapp(){
        $params = $this->params;
        $must_params = array(
            'app'=>'活动类型',
            
        );
        $this->check_params($must_params);
        if(!in_array($params['app'],array('groupbuy','scorebuy','timedbuy','spike'))){
            $this->send(false,null,'参数错误');
        }
        if($params['pagelimit']){
            $pagelimit = $params['pagelimit'];
        }else{
            $pagelimit = 10;
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
        $objAct = app::get('cellphone')->model('activity');
        $phone_act_list = $objAct->getList('act_id,act_name,banner,logo,source',array(),($nPage-1)*$pagelimit,$pagelimit);
        $filter_phone_act_list = array();
        if($phone_act_list){
           foreach($phone_act_list as $key=>$item){
               $source = unserialize($item['source']);
             
               if($source['app']==$params['app']){
                  $filter_phone_act_list[] = $item ;
                }
              } 
            }
         if($filter_phone_act_list){
              foreach($filter_phone_act_list as $k=>$v){
                   $temp = $objAct->get_detail($v['act_id']);
                   //act_type该活动所属的活动app : groupbuy timedbuy add by cuiqw
                    $filter_phone_act_list[$key]['act_type'] = $temp['source']['app'];
                    $filter_phone_act_list[$key]['start_time'] = $temp['start_time'];
                    $filter_phone_act_list[$key]['end_time'] = $temp['end_time'];
                    $filter_phone_act_list[$key]['banner'] = $this->get_img_url($v['banner'],'s');
                    $filter_phone_act_list[$key]['logo'] = $this->get_img_url($v['logo'],$picSize);
                    unset($filter_phone_act_list[$key]['source']);
              }
         
          $this->send(true,$filter_phone_act_list,$params['app'].'类型活动列表');  
        } else{
           $this->send(true,null,'暂无此类型的活动');
        
        }

   
    }
    /**
      *根据app名 获取相应类型的活动下的商品
      *
      *
      */
      function getgoodsbyapp(){
      
       $params = $this->params;
        $must_params = array(
            'app'=>'活动类型',
            
        );
        $this->check_params($must_params);
        if(!in_array($params['app'],array('groupbuy','scorebuy','timedbuy','spike'))){
            $this->send(false,null,'参数错误');
        }
       /* if($params['pagelimit']){
            $pagelimit = $params['pagelimit'];
        }else{
            $pagelimit = 10;
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
        */
        $objAct = app::get('cellphone')->model('activity');
        $phone_act_list = $objAct->getList('act_id,source',array(),0,-1);
        $filter_phone_act_list = array();
        if($phone_act_list){
           foreach($phone_act_list as $key=>$item){
               $source = unserialize($item['source']);
             
               if($source['app']==$params['app']){
                  $filter_phone_act_list[] = $item['act_id'] ;
                }
              } 
            }
         // 开始根据所有的app 类型活动id 取商品
        $data = array();
       
        foreach( $filter_phone_act_list as $val){
        $aData = $objAct->dump(array('act_id'=>$val),'act_name,banner,source,original_id','default');
        $aData['banner'] = $this->get_img_url($aData['banner'],'s');
        $aData['source'] = unserialize($aData['source']);
        $aData['object_type'] = $aData['source']['app'];//活动类型
        if($aData['source'] && is_array($aData['source'])){
            $act_id = $aData['original_id']?$aData['original_id']:'-1';
            $time = time();
            $app = @app::get($aData['source']['app']);
            $activity = @$app->model($aData['source']['m1']);
            $actapply = @$app->model($aData['source']['m2']);
            if($app && $activity && $actapply){
                //$aData['gallery'] = array();
                $objGoods = app::get('b2c')->model('goods');
                $aAct = $activity->getRow('*',array('act_id'=>$aData['original_id']));
                cellphone_misc_exec::get_change($aAct);
                $aAct['act_id'] = $aAct['act_id']?intval($aAct['act_id']):-1;
                $aApply = $actapply->getList('*',array('aid'=>$aAct['act_id'],'status'=>'2'),0,-1);
                cellphone_misc_exec::get_change($aApply);
                if($aAct['start_time'] && $aAct['end_time'] && $aAct['start_time']<=$time && $aAct['end_time']>$time){
                    $goods_id = array();
                    foreach((array)$aApply as $row){
                        $temp = explode(',', $row['gid']);
                        $temp = array_filter($temp);
                        $temp = !empty($temp)?$temp:array(-1);
                        
                        if($app->app_id == 'package'){
                            $price = 0;
                            foreach((array)$objGoods->getList('price',array('goods_id'=>$temp)) as $item){
                                $price += floatval($item['price']);
                            } 
                            $data[] = array(
                                'object_type' => $aData['source']['app'],
                                'goods_id' => $row['act_id'],
                                'image' => $this->get_img_url($row['image'],$picSize),
                                'name' => $row[($actapply->textColumn?$actapply->textColumn:'name')],
                                'real_price' => isset($row['last_price'])?$row['last_price']:$row['price'],
                                'price' => $price,
                                'freight_bear' => $row['freight_bear'],
                            );
                        }else{
                            $tData = $objGoods->getRow('goods_id,name,freight_bear,price,udfimg,thumbnail_pic,image_default_id',array('goods_id'=>$temp));
                            $data[] = array(
                                'object_type' => $aData['source']['app'],
                                'goods_id' => $tData['goods_id'],
                                'image' => $tData['udfimg']=='true'?$this->get_img_url($tData['thumbnail_pic'],$picSize):$this->get_img_url($tData['image_default_id'],$picSize),
                                'name' => $tData['name'],
                                'real_price' => isset($row['last_price'])?$row['last_price']:$row['price'],
                                'price' => $tData['price'],
                                'freight_bear' => $tData['freight_bear'],
                                'start_time'=> $aAct['start_time'],//活动开始时间
                                'end_time'=> $aAct['end_time'],// 活动的结束时间
                                'store'=>$row['store'],       //原始库存 added by cuiqw
                                'buy_count'=>$row['store']-$row['remainnums'],//已购买数量
                                'remainnums'=>$row['remainnums'],  // 剩余库存
                            );
                        }
                    }
                }
            }
        }

     }
     if($data){
       $this->send(true,$data,$params['app'].'类型活动下的商品');
     
     }else{
       $this->send(true,null,'暂无'.$params['app'].'类型活动下的商品');
     }


    }



}