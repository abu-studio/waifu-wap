<?php
 

class cardcoupons_ctl_admin_excards extends desktop_controller{

    var $workground = 'cardcoupons.wrokground.card';


    function index(){
               if($_GET['action'] == 'export') $this->_end_message = '导出卡券';

       
        // if($this->has_permission('batcheditmarketable')){
            // $group[] = array('label'=>app::get('b2c')->_('商品上架'),'icon'=>'download.gif','submit'=>'index.php?app=b2c&ctl=admin_goods&act=enable','target'=>'refresh');
            // $group[] = array('label'=>app::get('b2c')->_('商品下架'),'icon'=>'download.gif','submit'=>'index.php?app=b2c&ctl=admin_goods&act=disable','target'=>'refresh');
            // $group[] = array('label'=>'_SPLIT_');
        // }
        if($this->has_permission('excardeditmarketable')){
            $group[] = array('label'=>app::get('b2c')->_('卡券上架'),'icon'=>'download.gif','submit'=>'index.php?app=b2c&ctl=admin_goods&act=singleBatchEdit&p[0]=enable','target'=>'dialog');
            $group[] = array('label'=>app::get('b2c')->_('卡券下架'),'icon'=>'download.gif','submit'=>'index.php?app=b2c&ctl=admin_goods&act=singleBatchEdit&p[0]=disable','target'=>'dialog');
            $group[] = array('label'=>'_SPLIT_');
        }      
        // if($this->has_permission('batcheditprice')){
        //     $group[] = array('label'=>app::get('b2c')->_('统一调价'),'icon'=>'download.gif','submit'=>'index.php?app=b2c&ctl=admin_goods&act=singleBatchEdit&p[0]=uniformPrice','target'=>'dialog');
        //     $group[] = array('label'=>app::get('b2c')->_('分别调价'),'icon'=>'download.gif','submit'=>'index.php?app=b2c&ctl=admin_goods&act=singleBatchEdit&p[0]=differencePrice','target'=>'dialog');
        // }
        // if($this->has_permission('batcheditstore')){
        //     $group[] = array('label'=>app::get('b2c')->_('统一调库存'),'icon'=>'download.gif','submit'=>'index.php?app=b2c&ctl=admin_goods&act=singleBatchEdit&p[0]=uniformStore','target'=>'dialog');
        //     $group[] = array('label'=>app::get('b2c')->_('分别调库存'),'icon'=>'download.gif','submit'=>'index.php?app=b2c&ctl=admin_goods&act=singleBatchEdit&p[0]=differenceStore','target'=>'dialog');
        //     $group[] = array('label'=>'_SPLIT_');
        // }
        //     $group[] = array('label'=>app::get('b2c')->_('商品名称'),'icon'=>'download.gif','submit'=>'index.php?app=b2c&ctl=admin_goods&act=singleBatchEdit&p[0]=name','target'=>'dialog');
        //     $group[] = array('label'=>app::get('b2c')->_('商品简介'),'icon'=>'download.gif','submit'=>'index.php?app=b2c&ctl=admin_goods&act=singleBatchEdit&p[0]=brief','target'=>'dialog');
        // if($this->has_permission('brandgoods')){
        //     $group[] = array('label'=>app::get('b2c')->_('商品品牌'),'icon'=>'download.gif','submit'=>'index.php?app=b2c&ctl=admin_goods&act=singleBatchEdit&p[0]=brand','target'=>'dialog');
        // }
        //     $group[] = array('label'=>app::get('b2c')->_('商品排序'),'icon'=>'download.gif','submit'=>'index.php?app=b2c&ctl=admin_goods&act=singleBatchEdit&p[0]=dorder','target'=>'dialog');
        //     $group[] = array('label'=>app::get('b2c')->_('商品重量'),'icon'=>'download.gif','submit'=>'index.php?app=b2c&ctl=admin_goods&act=singleBatchEdit&p[0]=weight','target'=>'dialog');
        // if($this->has_permission('catgoods')){
        //     $group[] = array('label'=>app::get('b2c')->_('分类转换'),'icon'=>'download.gif','submit'=>'index.php?app=b2c&ctl=admin_goods&act=singleBatchEdit&p[0]=cat','target'=>'dialog');
        //     $group[] = array('label'=>'_SPLIT_');
        // }
       //$group[] = array('label'=>app::get('b2c')->_('重新生成图片'),'icon'=>'download.gif','submit'=>'index.php?app=b2c&ctl=admin_goods&act=batchImage','target'=>'dialog');
        if($this->has_permission('addexcard')){
             $custom_actions[] = array(
                 'label'=>app::get('b2c')->_('添加卡券'),
                 'icon'=>'add.gif',
                 //'disabled'=>'false',
                 'href'=>'index.php?app=cardcoupons&ctl=admin_excards_editor&act=add',
                 'target'=>'_blank'
            );
        }
		if($this->has_permission('deleteexcard')){
             $custom_actions[] = array(
                 'label'=>app::get('b2c')->_('删除'),
				 'confirm'=>app::get('desktop')->_('确定删除选中项？卡券删除后无法恢复'),
                 'submit'=>'index.php?app=cardcoupons&ctl=admin_excards&act=delete',
             );
        }
		/*if($this->has_permission('cardpass')){
             $custom_actions[] = array(
                 'label'=>app::get('b2c')->_('批量卡密'),
                 'icon'=>'add.gif',
                 //'disabled'=>'false',
                 'submit'=>'index.php?app=cardcoupons&ctl=admin_excards&act=setpass',
                 'target'=>'dialog'
            );
        }*/
        /*$custom_actions[] = array(
            'label'=>app::get('b2c')->_('批量操作'),
            'icon'=>'batch.gif',
            'group'=>$group,
        );*/
		$actions_base['use_buildin_recycle'] = false;
        $actions_base['title'] = app::get('cardcoupons')->_('卡券列表');
        $actions_base['actions'] = $custom_actions;
        $actions_base['use_buildin_set_tag'] = true;
        $actions_base['use_buildin_filter'] = true;
        if($this->has_permission('exportcard')){
            $actions_base['use_buildin_export'] = true;
        }
        if($this->has_permission('importexcard')){
            $actions_base['use_buildin_import'] = false;
        }
        $actions_base['allow_detail_popup'] = true;
        $actions_base['use_view_tab'] = true;
        $actions_base['base_filter'] = array('source'=>'external');
        $this->finder('cardcoupons_mdl_cards',$actions_base);
    }
	
	function setpass(){
		if($_POST['setpass']=='setpass'){
			$this->begin('index.php?app=cardcoupons&ctl=admin_excards&act=index');
			$db=kernel::database();
			$sql="select p.bn,p.store,p.goods_id from ".DB_PREFIX ."cardcoupons_cards as c join ".DB_PREFIX ."b2c_products as p on c.goods_id =p.goods_id where c.card_id='".$_POST['cards']['card_id']."'";
			$products=$db->select($sql);
			$product=$products[0];
			unset($products[0]);
			if($product){
				//事物处理开始
				$transaction_status = $db->beginTransaction();
				$result=kernel::single('cardcoupons_ctl_admin_excards_editor')->dispose_pass($_POST['cards']);
				if($result['result']){
					$db->commit($transaction_status);
					$this->end(true,app::get('b2c')->_('操作成功'));
					/*
					$store=$product['store']+$result['store'];
					$goods=array('store'=>$store);
					$product['store']=$store;
					$is_save=kernel::single('b2c_mdl_goods')->update($goods,array('goods_id'=>$product['goods_id']));
					if($is_save){
						$is_save=kernel::single('b2c_mdl_products')->update($product,array('bn'=>$product['bn']));
						if($is_save){
							$db->commit($transaction_status);
							$this->end(true,app::get('b2c')->_('操作成功'));
						}else{
							$db->rollback();
							$this->end(false,app::get('b2c')->_('库存更新失败'));
						}
						
					}else{
						$db->rollback();
						$this->end(false,app::get('b2c')->_('库存更新失败'));
					}	
					*/
				}else{
					$db->rollback();
					$this->end(false,app::get('b2c')->_($result['error']),null,$result);
				}
				
			}else{
				$this->end(false,app::get('b2c')->_('卡券货品不存在'));
			}
			
		}else{
			$cards_id=array();
			if($_GET['cards_id']){
				$cards_id[]=$_GET['card_id'];
			}else{
				$cards_id=$_POST['id'];
			}
			$cards=kernel::single('cardcoupons_mdl_cards')->dump(array('card_id'=>$_GET['card_id']));
			$this->pagedata['cards']=$cards;
			$this->pagedata['cards_json']=json_decode($cards_id);
			$this->display('admin/excards/setpass.html');
		}
		
	}
	//由于卡券包含商品，货品所以卡券无法使用系统自带删除。此处另写删除方法
	function delete($rows){
		$obj_cards=kernel::single('cardcoupons_mdl_cards');
		$so_filter['card_id']=$_POST['card_id'];
		if($_POST['isSelectAll']=='_ALL_'){
			$so_filter=array();
		}
		$this->begin('index.php?app=cardcoupons&ctl=admin_excards&act=index');
		$result=$obj_cards->delete($so_filter);
		if($result['result']){
			$this->end(true,'删除成功');
		}else{
			$this->end(false,$result['error']);
		}
		
	}
	


}
