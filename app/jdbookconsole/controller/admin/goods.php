<?php
 

class jdbookconsole_ctl_admin_goods extends desktop_controller{

     var $workground = 'jdbookconsole.workground.order';

	 public function index(){
         app::get('jdsale')->setConf('goodsbookdata.isinitial','0');
         if($_GET['action'] == 'export') $this->_end_message = '导出商品';


         //lpc 增加京东图书数据
         $custom_actions[] = array(
                 'label'  => app::get('b2c')->_('同步京东图书数据'),
                 'icon'   => 'download.gif',
                 'href' => 'index.php?app=jdbookconsole&ctl=admin_goods&act=importBook',
                 'target' => ''
             );


         $group = array();
         if($this->has_permission('jdeditmarketable')){
             $group =array(
                 array(
                     'label'  => app::get('b2c')->_('商品上架'),
                     'icon'   => 'download.gif',
                     'submit' => 'index.php?app=jdbookconsole&ctl=admin_goods&act=singleBatchEdit&p[0]=enable',
                     'target' => 'dialog'
                 ),
                 array('label'=>'_SPLIT_'),
                 array(
                     'label'  => app::get('b2c')->_('商品下架'),
                     'icon'   => 'download.gif',
                     'submit' => 'index.php?app=jdbookconsole&ctl=admin_goods&act=singleBatchEdit&p[0]=disable',
                     'target' => 'dialog'
                 )
             );

             if($_GET['view'] == '1') {
                 $group ='';
                 $group[]          = array(
                     'label'  => app::get('b2c')->_('商品上架'),
                     'icon'   => 'download.gif',
                     'submit' => 'index.php?app=jdbookconsole&ctl=admin_goods&act=singleBatchEdit&p[0]=enable',
                     'target' => 'dialog'
                 );
             }

             if($_GET['view'] == '7') {
                 $group ='';
                 $group[]          = array(
                     'label'  => app::get('b2c')->_('商品下架'),
                     'icon'   => 'download.gif',
                     'submit' => 'index.php?app=jdbookconsole&ctl=admin_goods&act=singleBatchEdit&p[0]=disable',
                     'target' => 'dialog'
                 );
             }
         }



         
		 $custom_actions[] = array(
			 'label' => app::get('b2c')->_('批量操作'),
			 'icon'  => 'batch.gif',
			 'group' => $group,
		 );
         
         $this->finder('b2c_mdl_goods',array(
            'title'=>'商品列表',
            'allow_detail_popup'=>true,
            'base_filter'=>array('disabled'=>'false','goods_kind'=>'jdbook','cat_id'=>array('0')),
            'use_buildin_export'=>false,
			'force_view_tab'=>true,
			'use_buildin_set_tag'=>true,
			'use_buildin_recycle'=>false,
			'use_buildin_filter'=>true,
			'use_view_tab'=>true,
            'actions'=>$custom_actions
          ));
    }

    //lpc 导入京东图书数据
    public function importBook(){
        app::get('jdsale')->setConf('goodsbookdata.isinitial',0);
        $shopId = app::get('site')->getConf('jdbook.shopId') ? app::get('site')->getConf('jdbook.shopId'): '';
        if (empty($shopId)){
            $this->splash('success','','未设置京东图书自营商铺！');
        }else{
            app::get('jdsale')->setConf('goodsbookdata.isinitial',0);
            $is_initial = app::get('jdsale')->getConf('goodsbookdata.isinitial');
            if ($is_initial != 1) {
                set_time_limit(0);
                $jdsale_goods_import = kernel::single('jdsale_goods_importbook');
                if ($jdsale_goods_import->initialData($shopId)){
                    app::get('jdsale')->setConf('goodsbookdata.isinitial',1);
                    $this->splash('success','','京东图书数据导入成功！');
                }else{
                    $this->splash('success','','京东图书数据导入失败！');
                }
            }else{
                $this->splash('success','','京东图书数据已经导入！');
            }
        }

        $this->index();

    }

    public function _views(){

        $sub_menu = array();
        $mdl_goods = app::get('b2c')->model('goods');
        $sub_menu[0] = array('label'=>app::get('b2c')->_('全部'),'optional'=>true,'filter'=>'','addon'=>$mdl_goods->count(array('goods_kind'=>'jdbook','cat_id'=>array('0'))));

        //已下架商品
        $filter = array('marketable'=>'false','goods_type'=>'normal','goods_kind'=>'jdbook','cat_id'=>array('0'));
        $market_count = $mdl_goods->count($filter);
        if($market_count >0){
            $sub_menu[1] = array('label'=>app::get('b2c')->_('已下架商品'),'optional'=>true,'filter'=>$filter,'addon'=>$market_count,'href'=>'index.php?app=jdbookconsole&ctl=admin_goods&act=index&view=1&view_from=dashboard');
        }

        //上架商品
        $markettrue_filter=array('marketable'=>'true','goods_type'=>'normal','goods_kind'=>'jdbook','cat_id'=>array('0'));
        $markettrue_count = $mdl_goods->count($markettrue_filter);
        if($markettrue_count>0){
            $sub_menu[7] = array('label'=>app::get('b2c')->_('已上架'),'optional'=>true,'filter'=>$markettrue_filter,'addon'=>$markettrue_count,'href'=>'index.php?app=jdbookconsole&ctl=admin_goods&act=index&view=7&view_from=dashboard');
        }

        ksort($sub_menu);
        $show_menu = $sub_menu;
        foreach($show_menu as $k=>$v){
            if($v['optional']==false){
            }elseif(($_GET['view_from']=='dashboard')&&$k==$_GET['view']){
                $show_menu[$k] = $v;
            }
            if (!$v['addon']) {unset($show_menu[$k]);}
        }

        return $show_menu;
    }



    /**
     * 批量操作（上架和下架）,并需记录操作日志和操作明细日志
     * @param string $editType
     */
    function singleBatchEdit($editType=''){
        if(!$this->has_permission('jdeditmarketable')){
            $this->begin('');
            $this->end(false, app::get('jdsale')->_('您无权批量操作商品上架'));
        }

        $objGoods = app::get('b2c')->model('goods');
        $newFilter = $_POST;
        unset($newFilter['app']);
        unset($newFilter['ctl']);
        unset($newFilter['act']);
        unset($newFilter['_finder']);
        unset($newFilter['marketable']);
        unset($newFilter['_DTYPE_BOOL']);

        if($_POST['isSelectedAll'] == '_ALL_'){
			$_POST['goods_id'][0] = '_ALL_';
			$_POST['goods_kind'] = 'jdbook';
            $_POST['cat_id'] = array('0');
		}
		
		if($_POST['view'] == 1){		
			$_POST['marketable'] = 'false';
			$_POST['goods_type'] = 'normal';
		}elseif($_POST['view'] == 7){
			$_POST['marketable'] = 'true';
			$_POST['goods_type'] = 'normal';
		}


        if(count($_POST['goods_id']) == 0 && $_POST['_finder']['select'] != 'multi' && !$_POST['_finder']['id'] && !$_POST['filter']){
            echo __('请选择商品记录');
            exit;
        }
        if($_POST['filter']){
            $_POST['_finder'] = unserialize($_POST['filter']);

            if(is_array($_POST['filter'])){
                $_POST['_finder']=$_POST['filter'];
                $_POST['cat_id'] = array($_POST['filter']['cat_id']);
                $_POST['jd_cat_id'] = array($_POST['filter']['jd_cat_id']);
            }

            $newFilter = $_POST['_finder'];

            if($_POST['updateAct']){
                $editType = $_POST['updateAct'];
            }
        }
        if($_GET['cat_id']){
            $_POST['cat_id']=array($_GET['cat_id']);
        }else{
			 $_POST['cat_id']=array('0');
		}


        if($_GET['jd_cat_id']){
            $_POST['jd_cat_id']=array($_GET['jd_cat_id']);
        }

        $this->pagedata['editInfo'] = $objGoods->getBatchEditInfo($_POST);

        unset($_POST['finder']);
        $this->pagedata['filter'] = htmlspecialchars(serialize($newFilter));
        $this->pagedata['finder'] = $_GET['finder'];
        $this->display('admin/goods/batch/batchEdit'.ucfirst($editType).'.html');
    }

    function saveBatchEdit(){
        if(!$this->has_permission('jdeditmarketable')){
            $this->begin('');
            $this->end(false, app::get('jdsale')->_('您无权批量操作商品上架'));
        }
        $objGoods = app::get('b2c')->model('goods');
        $this->begin('');
        $filter = unserialize($_POST['filter']);

		if($filter['view'] == 1){		
			$filter['marketable'] = 'false';
			$filter['goods_type'] = 'normal';
		}elseif($filter['view'] == 7){
			$filter['marketable'] = 'true';
			$filter['goods_type'] = 'normal';
		}



        if(empty($filter['cat_id'])){
            $filter['cat_id'] = array('0');
        }
		$filter['goods_kind'] = array('jdbook');
        $filter['goods_id'] = $objGoods->getGoodsIdByFilter($filter);

        $haserror = false;

        switch( $_POST['updateAct'] ){
            case 'enable':
                if(!$this->has_permission('batcheditmarketable')){
                    $this->end(false, app::get('b2c')->_('您无权批量操作商品上架'));
                }
                //过滤京东已经下架商品
                $this->getJdProductState($filter);

                if (empty($filter['goods_id'])){
                    $this->end(true, app::get('b2c')->_('选中商品在京东已经全部下架，未完成上架'));
                    break;
                }

                $glist = $objGoods->setEnabled(array('goods_id'=>$filter['goods_id']),'true');
                $data['marketable_allow'] = 'true';
                $data['marketable_content'] = $_POST['set']['marketable_content'];
                if($filter['goods_id'][0] == '_ALL_')  unset($filter);
                $goods_id = $objGoods->getList('goods_id',$filter);
                $date = time();
                $obj_apply = app::get('b2c')->model('goods_marketable_application');
                foreach($goods_id as $pk => $pv){
                    $result['goods_id'][] = $pv['goods_id'];
                    $apply_data = array();
                    $apply_data['goods_id'] = $pv['goods_id'];
                    $apply_data['status'] = '1';
                    $apply_data['restore'] = $data['marketable_content'];
                    $apply_data['audit_time'] = $date;
                    $apply_data['audit_user'] = $this->user->user_id;
                    $apply_data['last_modify'] = $date;
                    $obj_apply->insert($apply_data);
                }
                $objGoods->update($data, $result);
                $this->end(true, app::get('b2c')->_('选中商品上架完成'));
                break;

            case 'disable':
                if(!$this->has_permission('batcheditmarketable')){
                    $this->end(false, app::get('b2c')->_('您无权批量操作商品下架'));
                }
                $glist = $objGoods->setEnabled(array('goods_id'=>$filter['goods_id']),'false');
                $data['marketable_allow'] = 'false';
                $data['marketable_content'] = $_POST['set']['marketable_content'];
                if($filter['goods_id'][0] == '_ALL_')  unset($filter);
                $goods_id = $objGoods->getList('goods_id',$filter);
                $date = time();
                $obj_apply = app::get('b2c')->model('goods_marketable_application');
                foreach($goods_id as $pk => $pv){
                    $result['goods_id'][] = $pv['goods_id'];
                    $apply_data = array();
                    $apply_data['goods_id'] = $pv['goods_id'];
                    $apply_data['status'] = '2';
                    $apply_data['restore'] = $data['marketable_content'];
                    $apply_data['audit_time'] = $date;
                    $apply_data['audit_user'] = $this->user->user_id;
                    $apply_data['last_modify'] = $date;
                    $obj_apply->insert($apply_data);
                }
                $objGoods->update($data, $result);
                $this->end(true, app::get('b2c')->_('选中商品下架完成'));
                break;

        }

        ini_set('track_errors','1');
        restore_error_handler();
        if(!$haserror){
            $this->end(true, app::get('b2c')->_('保存成功'));
        }else{
            echo $GLOBALS['php_errormsg'];
        }
    }

    /**
     *
     * 完成批量操作（上架和下架）,并记录操作日志和操作明细日志
     * @param $finderResult
     * @param $status
     * @return bool
     */
    function setEnabled($finderResult,$status){
        $objGoods = app::get('b2c')->model('goods');
        if($finderResult['goods_id'][0] == '_ALL_')  unset($finderResult);
        $data['marketable'] = $status;
        $goods_id = $objGoods->getList('goods_id',$finderResult);
        foreach($goods_id as $pk => $pv){
            $result['goods_id'][] = $pv['goods_id'];
        }
        //没有商品时不做处理，直接返回
        if(empty($result['goods_id'])){
            return true;
        }
        $rs_flag = $objGoods->update($data,$result);

        #↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓记录管理员操作日志@lujy↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓
        if($rs_flag){
            if($obj_operatorlogs = kernel::service('operatorlog')){
                if(method_exists($obj_operatorlogs,'inlogs')){
                    $m_tmp = array('true'=>'上架','false'=>'下架');
                    if(!isset($finderResult)){
                        $memo = $m_tmp[$status].'所有京东商品';
                    }else{
                        if(count($finderResult['goods_id'])>100){
                            $memo = '批量'.$m_tmp[$status].'京东商品 ID('.implode(',',$finderResult['goods_id']).')';
                        }else{
                            $goods_bn = $this->getList('bn',$finderResult);
                            $v2tmp='';
                            foreach($goods_bn as $v2){
                                $v2tmp .=$v2['bn'].',';
                            }
                            $memo = '批量'.$m_tmp[$status].'京东商品编号('.rtrim($v2tmp,',').')';
                        }
                    }
                    $obj_operatorlogs->inlogs($memo, '', 'goods');
                }
            }
        }
        #↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑记录管理员操作日志@lujy↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑

        return $rs_flag;
    }

    /**
     * 获取京东上架的商品ids，返回$filter_goods_id
     * @param $filter_goods_id
     */
    private function getJdProductState(&$filter_goods_id){

        $objGoods = app::get('b2c')->model('goods');

        $goods_id_bn = $objGoods->getList('goods_id,bn,goods_kind', $filter_goods_id);

        //lpc 区分图书上架
        
        foreach ($goods_id_bn as $item) {
            if ($item['goods_kind'] == "jdbook")
                $skuBookArr[] = $item['bn'];
            else
                $skuArr[] = $item['bn'];
            $goods_id_bn_2[$item['goods_id']] = $item['bn'];
        }
        $api_goods = kernel::single('jdsale_api_goods');
        if ($skuArr) {
            $skuIds = implode(",", $skuArr);
            $product_state = $api_goods->queryProductState(array('sku' => $skuIds));

            foreach ($product_state['result'] as $item) {
                //京东已经下架商品
                if ($item['state'] == 0){
                    $sku_disable[] = strval($item['sku']);
                }
            }
        }

        //lpc 区分图书上架
        if ($skuBookArr) {
            $skubookIds = implode(",", $skuBookArr);
            $product_state = $api_goods->queryProductState(array('sku' => $skubookIds),"book");

            foreach ($product_state['result'] as $item) {
                //京东已经下架商品
                if ($item['state'] == 0){
                    $sku_disable[] = strval($item['sku']);
                }
            }
        }

        //京东当前已经下架的商品存入操作明细日志
        if($sku_disable && $obj_operatorlogs = kernel::service('operatorlog')){
            if(method_exists($obj_operatorlogs,'inlogs')){
                $v2tmp='';
                foreach($sku_disable as $v2){
                    $v2tmp .=$v2.',';
                }
                $memo = '商品编号('.rtrim($v2tmp,',').') 目前在京东已经下架,无法进行上架操作';

                $obj_operatorlogs->inlogs($memo, '', 'goods');
            }
        }
        if($sku_disable){
            if(count($sku_disable) == count($filter_goods_id['goods_id'])){
                $filter_goods_id['goods_id'] = array();
                return ;
            }
            //京东已经下架的商品goods_id
            foreach($goods_id_bn_2 as $k => $v){
                if (in_array($v,$sku_disable)){
                    $goods_id_disable[]=$k;
                }
            }

            foreach($filter_goods_id['goods_id'] as $k => $v){
                if (in_array($v,$goods_id_disable)){
                    unset($filter_goods_id['goods_id'][$k]);
                }
            }
        }

        return ;
    }
}