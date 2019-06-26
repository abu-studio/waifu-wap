<?php
 

class cardcoupons_ctl_admin_cards extends desktop_controller{

    var $workground = 'cardcoupons.wrokground.card';

    function index(){
               if($_GET['action'] == 'export') $this->_end_message = '导出卡券';

        // if($this->has_permission('batcheditmarketable')){
            // $group[] = array('label'=>app::get('b2c')->_('商品上架'),'icon'=>'download.gif','submit'=>'index.php?app=b2c&ctl=admin_goods&act=enable','target'=>'refresh');
            // $group[] = array('label'=>app::get('b2c')->_('商品下架'),'icon'=>'download.gif','submit'=>'index.php?app=b2c&ctl=admin_goods&act=disable','target'=>'refresh');
            // $group[] = array('label'=>'_SPLIT_');
        // }
        if($this->has_permission('cardeditmarketable')){
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
             $group[] = array('label'=>app::get('b2c')->_('重新生成图片'),'icon'=>'download.gif','submit'=>'index.php?app=b2c&ctl=admin_goods&act=batchImage','target'=>'dialog');
        if($this->has_permission('addcard')){
             $custom_actions[] = array(
                 'label'=>app::get('b2c')->_('添加卡券'),
                 'icon'=>'add.gif',
                 //'disabled'=>'false',
                 'href'=>'index.php?app=cardcoupons&ctl=admin_cards_editor&act=add',
                 'target'=>'_blank'
             );
        }
		if($this->has_permission('deletecard')){
             $custom_actions[] = array(
                 'label'=>app::get('b2c')->_('删除'),
				 'confirm'=>app::get('desktop')->_('确定删除选中项？卡券删除后无法恢复'),
                 'submit'=>'index.php?app=cardcoupons&ctl=admin_cards&act=delete',
             );
        }
        // $custom_actions[] = array(
            // 'label'=>app::get('b2c')->_('批量操作'),
            // 'icon'=>'batch.gif',
            // 'group'=>$group,
        // );
        $actions_base['title'] = app::get('cardcoupons')->_('卡券列表');
        $actions_base['actions'] = $custom_actions;
        $actions_base['use_buildin_set_tag'] = false;
        $actions_base['use_buildin_filter'] = true;
        if($this->has_permission('exportcard')){
            $actions_base['use_buildin_export'] = true;
        }
        /*if($this->has_permission('importcard')){
            $actions_base['use_buildin_import'] = true;
        }*/

		$actions_base['use_buildin_recycle'] = false;
        $actions_base['allow_detail_popup'] = true;
        $actions_base['use_view_tab'] = true;
        $actions_base['use_buildin_tagedit'] = false;
		
        $actions_base['base_filter'] = array('source'=>'internal');

        $this->finder('cardcoupons_mdl_cards',$actions_base);

    }

	function cardType(){


	}


    function _views(){

        $sub_menu = array();
       // $filter = array("source"=>"source='internal'","cardkind"=>"cardkind='01'");
        $sub_menu[1] = array('label'=>app::get('b2c')->_('实体卡券'),'optional'=>true,'href'=>'index.php?app=b2c&ctl=admin_goods&act=index&view=1&view_from=dashboard');

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


    function get_type(){
		$card_type = $_POST['card_type'];
        if(empty($_POST['card_type'])){
            $card_type = "01";
        }
		$card_id=$_POST['card_id'];
		if($card_id){
			$cards=kernel::single('cardcoupons_mdl_cards')->dump(array('card_id'=>$card_id),'*','default');
			$this->pagedata['cards'] = $cards;
            $this->pagedata['change_way'] = $cards['change_way'];
            $this->pagedata['change_mode'] = $cards['change_mode'];
		}else{
            $this->pagedata['change_way'] = "num";
            $this->pagedata['change_mode'] = "once";
        }

        $card_service_object = kernel::single("cardcoupons_mdl_cards_service");

        $card_service_data = $card_service_object->getList("*",array("cardkind"=>$card_type));
        $this->pagedata['card_service_type'] = $card_type;
        $this->pagedata['card_service_data'] = $card_service_data;
		$this->pagedata['goodsselect_filter'] = array('marketable'=>'true','goods_kind|in'=>array('virtual','entity','card'));
        $this->display('admin/cards/input_card.html');

    }
	   function get_service(){
		$card_type = $_POST['card_type'];
        if(empty($_POST['card_type'])){
            $card_type = "01";
        }
		$card_id=$_POST['card_id'];
		if($card_id){
			$cards=kernel::single('cardcoupons_mdl_cards')->dump(array('card_id'=>$card_id),'*','default');
			$this->pagedata['cards'] = $cards;
		}
		/*
        $card_service_object = kernel::single("cardcoupons_mdl_cards_service");

        $card_service_data = $card_service_object->getList("*",array("cardkind"=>$card_type));
        $this->pagedata['card_service_type'] = $card_type;
        $this->pagedata['card_service_data'] = $card_service_data;
		*/
		$this->pagedata['filter'] = array("cardkind"=>$card_type);
        $this->display('admin/cards/input_cardService.html');

    }

	 function get_dlytypes(){
		$store_id=$_POST['store_id'];
		$this->pagedata['filter'] = array("store_id"=>$store_id);
        $this->display('admin/cards/input_cardDlytypes.html');
    }
	//卡券设置密码
	function setpass(){
		if($_POST['setpass']=='setpass'){
			$this->begin('index.php?app=cardcoupons&ctl=admin_cards&act=index');
			$db=kernel::database();
			$sql="select p.bn,p.store,p.goods_id from ".DB_PREFIX ."cardcoupons_cards as c join ".DB_PREFIX ."b2c_products as p on c.goods_id =p.goods_id where c.card_id='".$_POST['cards']['card_id']."'";
			$products=$db->select($sql);
			$product=$products[0];
			unset($products[0]);
			if($product){
				//事物处理开始
				$transaction_status = $db->beginTransaction();
				$result=kernel::single('cardcoupons_ctl_admin_cards_editor')->dispose_pass($_POST['cards']);
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
					$this->end(false,app::get('b2c')->_('操作失败'),null,$result);
				}
				
			}
			$this->end(false,app::get('b2c')->_('卡券货品不存在'));
			
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
			$this->display('admin/cards/setpass.html');
		}
	}
	//下载卡密上传模板
	function downpass(){
		$csv_data[0]=array('卡券编号(card_no)','卡密(card_pass)','类型(type)','开始时间(from_time)','结束时间(to_time)');
		$csv_data[1]=array('','','entity',date('Y-m-d',time()),'');
		$csv_string = null;
        $csv_row    = array();
        foreach( $csv_data as $key => $csv_item )
        {
            /*
            if( $key === 0 )
            {
                $csv_row[]    = implode( "," , $csv_item );
                continue;
            }
            */
            $current    = array();
            foreach( $csv_item AS $item )
            {
			/****************************************************************************************************************************
			*很关键。 默认csv文件字符串需要 ‘ " ’ 环绕,否则导入导出操作时可能发生异常。
			****************************************************************************************************************************/
            $current[] = is_numeric( $item ) ? $item : '"' . str_replace( '"', '""', $item ) . '"';
            //$current[] ='"' . str_replace( '"', '""', $item ) . '"';
            }
            $csv_row[]    = implode( "," , $current );
        }

        $csv_string = implode( "\r\n", $csv_row );
        /****************************************************************************************************************************
         * 输出
        ****************************************************************************************************************************/

        header("Content-type:text/csv");
        header("Content-Type: application/force-download");
        header("Content-Disposition: attachment; filename=pass".$_GET['card_id'].".csv");
        header('Expires:0');
        header('Pragma:public');
        echo mb_convert_encoding($csv_string, 'GBK', 'UTF-8');
        //echo "\xFF\xFE".mb_convert_encoding( $csv_string, 'UCS-2LE', 'UTF-8' );
	}
	
	//由于卡券包含商品，货品所以卡券无法使用系统自带删除。此处另写删除方法
	function delete($rows){
		$obj_cards=kernel::single('cardcoupons_mdl_cards');
		$so_filter['card_id']=$_POST['card_id'];
		if($_POST['isSelectAll']=='_ALL_'){
			$so_filter=array();
		}
		$this->begin('index.php?app=cardcoupons&ctl=admin_cards&act=index');
		$result=$obj_cards->delete($so_filter);
		if($result['result']){
			$this->end(true,'删除成功');
		}else{
			$this->end(false,$result['error']);
		}
		
	}



}
