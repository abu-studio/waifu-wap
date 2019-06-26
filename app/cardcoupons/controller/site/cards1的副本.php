<?php
class cardcoupons_ctl_site_cards1 extends b2c_frontpage{
	function __construct($app){
        parent::__construct($app);
		$this->ctl_name = $this->_request->get_ctl_name();
		$this->pagedata['ctl_name'] = $this->ctl_name;
        $this->_response->set_header('Cache-Control', 'no-store');
    }

    /**
     * 用户验证
     * @return bool
     */
    function verify_member(){
        kernel::single('base_session')->start();
        $this->member_id = $_SESSION['account'][pam_account::get_account_type('b2c')];
        if(app::get('b2c')->member_id = $_SESSION['account'][pam_account::get_account_type(app::get('b2c')->app_id)]){
            $obj_member = app::get('b2c')->model('members');
            $data = $obj_member->select()->columns('member_id')->where('member_id = ?',app::get('b2c')->member_id)->instance()->fetch_one();
            if($data){
                //登陆受限检测
                $res = $this->loginlimit(app::get('b2c')->member_id,$redirect);
                if($res){
                    $this->redirect($redirect);
                    return false;
                }else{
                    return true;
                }
            }else{
                $this->redirect(array('app'=>'b2c', 'ctl'=>'site_passport', 'act'=>'error'));
                return false;
            }
        }else{
            $this->redirect(array('app'=>'b2c', 'ctl'=>'site_passport', 'act'=>'error'));
            return false;
        }

    }

    /**
     * 获取当前用户信息
     * @return array
     */
    public function get_current_member()
    {
        if($this->member) return $this->member;
        $obj_members = app::get('b2c')->model('members');
		$this->member = $obj_members->get_current_member();
        //登陆受限检测
        if(is_array($this->member)){
            $minfo = $this->member;
            $mid = $minfo['member_id'];
            $res = $this->loginlimit($mid,$redirect);
            if($res){
                $this->redirect($redirect);
            }
        }
        return $this->member;
    }

    /**
     * 通过卡号 卡密进行卡券领取
     */
    function login(){

        $obj_filter = kernel::single('b2c_site_filter');
        $_POST = $obj_filter->check_input($_POST);

        $card_num = trim($obj_filter->check_input($_POST['card_num']));
        $password = trim($obj_filter->check_input($_POST['password']));

        if(strlen($card_num) > 0 || $password > 0){
            if(!preg_match("/^\d{12}$/",$card_num)){
                echo json_encode(array('status'=>'failed', 'url'=>'back', 'msg'=>"卡号格式有误，请重新输入！"));
                exit();
            }
        }

        $cards_pass_object = app::get('cardcoupons')->model('cards_pass');
        $cards_pass_data =  $cards_pass_object->getList('*', array('card_no' => $card_num,'card_pass'=>$password,'disabled'=>"false",'source'=>'internal'));

        //卡号，卡密都正确的话 需要检验用户名
        if(!empty($cards_pass_data)){
            if($cards_pass_data[0]['status'] == '0' || $cards_pass_data[0]['status'] == '-1'){
                echo json_encode(array('status'=>'failed', 'url'=>'back', 'msg'=>"此卡号尚未启用或者被冻结！"));
                exit();
            }

			if(in_array($cards_pass_data[0]['status'],array(3,4))){
				$url = $this->gen_url(array('app'=>'b2c','ctl'=>'site_member','act'=>'orders'));
				echo json_encode(array('status'=>'success','url'=>$url,'msg'=>''));
				exit();
			}
			$last_url = $this->gen_url(array('app'=>'cardcoupons','ctl'=>'site_cards1','act'=>'showcard','arg'=>base64_encode($card_num)));
			echo json_encode(array('status'=>'success','url'=>$last_url,'msg'=>''));
			exit();
			
        }else{
			echo json_encode(array('status'=>'failed', 'url'=>'back', 'msg'=>"卡号或卡密填写错误，请重新填写！"));
			exit();
		}
    }


    function showcard($card_no){
		if(!$this->verify_member()){
            exit();
        }

        $card_no = base64_decode($card_no);
        if(strlen($card_no) <= 0){
            $url = $this->gen_url( array('app'=>'b2c','ctl'=>'site_member','act'=>'condolences'));
            $this->splash('failed', $url, app::get('physical')->_('暂无此卡号，请重新输入!'));
            exit();
        }

        $cards_object = app::get('cardcoupons')->model('cards');
        //获取卡券信息
        $cards_info = $cards_object->getcardinfo($card_no);

        $cards_pass_object = kernel::single("cardcoupons_mdl_cards_pass");
        $cards_pass_data = $cards_pass_object->getList('*',array('card_no'=>$card_no));
        if(in_array($cards_pass_data[0]['status'],array(3,4))){
            $this->redirect(array('app'=>'b2c','ctl'=>'site_member','act'=>'orders','full'=>1));
            die();
        }

        if($cards_info){
            $this->pagedata['cardinfo'] = $cards_info;
        }else{
            $url = $this->gen_url( array('app'=>'b2c','ctl'=>'site_member','act'=>'condolences'));
            $this->splash('failed', $url, app::get('physical')->_('暂无此卡号，请重新输入!'));
            exit();
        }

        //根据用户是否登陆，来显示不同的卡券展示页面
        $this->set_tmpl("card_main1");
		
		if($cards_info['card_type'] == "02"){
			$url = $this->gen_url(array('app'=>'physical','ctl'=>'site_exchange','act'=>'index','args'=>array('1','1','0',$cards_info['goods_id'],$card_no,base64_encode($cards_pass_data[0]['card_pass']))));
			header('Content-Type: text/html; charset=utf-8');
			header('Location:'.$url);
			die();
		}


		if($cards_info['change_way'] != "num"){
            $change_page = "site/showmembercard_price.html";
        }else{
            $change_page = "site/showmembercard.html";
        }
        $time = time();
        $sessObj = kernel::single('base_session');
        $sessObj->start();
        $kvstoreKeyname = 'showcard1' . $sessObj->sess_id();
        base_kvstore::instance('cardcouponsSiteCards')->store($kvstoreKeyname, $time , 3600);
        $this->page($change_page);
    }


    function card_data(){
        if(!$this->verify_member()){
            exit();
        }
        $obj_filter = kernel::single('b2c_site_filter');
        $_POST = $obj_filter->check_input($_POST);

        $card_id = $obj_filter->check_input($_POST['card_id']);
        $card_pass_no = $obj_filter->check_input($_POST['card_pass_no']);

        $sessObj = kernel::single('base_session');
        $sessObj->start();
        $kvstoreKeyname = 'showcard1' . $sessObj->sess_id();
        $showcard = null;
        base_kvstore::instance('cardcouponsSiteCards')->fetch($kvstoreKeyname, $showcard);
        if($showcard > 0){
            base_kvstore::instance('cardcouponsSiteCards')->delete($kvstoreKeyname);
        }else{
            $url = $this->gen_url(array('app'=>'cardcoupons','ctl'=>'site_cards1','act'=>'showcard','arg'=>base64_encode($card_pass_no)));
            $this->splash('failed', $url, app::get('physical')->_('页面已失效!'));
        }
       if(!empty($card_id) && !empty($card_pass_no)){
           $card_pass_object = kernel::single("cardcoupons_mdl_cards_pass");
           $card_object = kernel::single("cardcoupons_mdl_cards");
           $card_solution = kernel::single("cardcoupons_mdl_cards_solution");
           $card_pass_data = $card_pass_object->getList("*",array("card_no"=>$card_pass_no,"status"=>array('1','2','3','9'),"disabled"=>'false'));

           if(empty($card_pass_data)){
               $url = $this->gen_url(array('app'=>'cardcoupons','ctl'=>'site_cards1','act'=>'showcard','arg'=>base64_encode($card_pass_no)));
               $this->splash('failed', $url, app::get('physical')->_('卡号已经被使用了!'));
               exit();
           }

           $card_data = $card_object->getList("*",array("card_id"=>$card_id));
           if(empty($card_data)){
               $url = $this->gen_url( array('app'=>'cardcoupons', 'ctl'=>'site_cards1', 'act'=>'showcard','arg'=>base64_encode($card_pass_no)));
               $this->splash('failed', $url, app::get('physical')->_('卡原型错误，请重新请求数据'));
               exit();
           }

           //检测用户选择卡内物品的总和 和数据库中总和是否相同
           //总数
           $num = 0;
           //实物券
           $goods_card_num = 0;
           //电子券
           $virtual_card_num = 0;
           //实物商品
           $goods_num = 0;
           if(!empty($_POST['goods'])){
              foreach($obj_filter->check_input($_POST['goods']) as $k=>$v){
                  //todo 如果一个商品选择了电子券有选择了实物券 提示保存
                  if($v['goods_card'] !=0 && $v['virtual_card'] != 0){
                      $url = $this->gen_url( array('app'=>'cardcoupons', 'ctl'=>'site_cards1', 'act'=>'showcard','arg'=>base64_encode($card_pass_no)));
                      $this->splash('failed', $url, app::get('physical')->_('同一商品不能既选择电子券，又选择实物券！'));
                      die();
                  }
                  if(($v['goods_card'] +  $v['virtual_card'] + $v['goods']) != 0){
                      $num += $v['goods_card'] +  $v['virtual_card'] + $v['goods'];
                      $goods_info[$k]['num'] = $v['goods_card'] +  $v['virtual_card'] + $v['goods'];
                      $goods_card_num += $v['goods_card'];
                      $virtual_card_num += $v['virtual_card'];
                      $goods_num += $v['goods'];
                      $goods_id[] = $k;
                  }
              }
           }


           if($card_data[0]['type_id'] == "01" || ($card_data[0]['type_id'] == "03" && empty($_POST['cardgoods']))){
               if($num <= 0){
                   $url = $this->gen_url( array('app'=>'cardcoupons', 'ctl'=>'site_cards1', 'act'=>'showcard','arg'=>base64_encode($card_pass_no)));
                   $this->splash('failed', $url, app::get('physical')->_('你还没有选择，请重新选择！'));
                   exit();
               }

               if(!empty($card_data[0]['rules']) || !empty($num)){
                   if($num > $card_data[0]['rules']){
                       $url = $this->gen_url( array('app'=>'cardcoupons', 'ctl'=>'site_cards1', 'act'=>'showcard','arg'=>base64_encode($card_pass_no)));
                       $this->splash('failed', $url, app::get('physical')->_('选择数量超过了,卡内数量！'));
                       exit();
                   }
               }
           }

           //如果会员已登录，查询会员的信息
           kernel::single('base_session')->start();
           $arrMember = $this->get_current_member();
           $this->pagedata['member_id'] = $arrMember['member_id'];

            //实物卡兑换信息
            if($card_data[0]['type_id'] == "01"){
                $goods_object = kernel::single("b2c_mdl_goods");
                $goods_data = $goods_object->getList("store_id,goods_state,buy_m_count,fav_count,freight_bear,comments_count,avg_point,goods_id,name,bn,price,cost,mktprice,marketable,store,store_freeze,notify_num,score,weight,unit,brief,image_default_id,udfimg,thumbnail_pic,small_pic,big_pic,min_buy,package_scale,package_unit,package_use,score_setting,nostore_sell,goods_setting,disabled,spec_desc,adjunct,brand_id,type_id,cat_id,seo_info,act_type",array("goods_id"=>$goods_id));
                $storemanger_object = kernel::single("business_mdl_storemanger");
                foreach($obj_filter->check_input($_POST['goods']) as $k=>$v){
                    foreach($goods_data as $key=>$val){
                        if($k == $val['goods_id']){
                            $goods_data[$key]['virtual_card'] = $v['virtual_card']?$v['virtual_card']:0;
                            $goods_data[$key]['goods'] = $v['goods']?$v['goods']:0;
                            $goods_data[$key]['goods_card'] = $v['goods_card']?$v['goods_card']:0;
                            $goods_data[$key]['goods_nums'] = $goods_data[$key]['virtual_card']+$goods_data[$key]['goods']+$goods_data[$key]['goods_card'];
                            $storemanger_data = $storemanger_object->dump(array("store_id"=>$goods_data[$key]['store_id']),"store_name");
                            $goods_data[$key]['store_name'] = $storemanger_data['store_name'];
                        }
                    }
                }
                $list2dump = kernel::single("b2c_goods_list2dump");
                foreach($goods_data as $key=>$val){
                    $product = $list2dump->get_goods($goods_data[$key],1);
                    $goods_tmp = array(
                        'goods' => Array (
                            'num' => $val['goods_nums'],
                            'goods_id' => $val['goods_id'],
                            'pmt_id' =>'',
                            'product_id' =>key($product['product']),
                            'virtual_card' => $val['virtual_card'],
                            'goods' => $val['goods'],
                            'goods_card' => $val['goods_card']
                        ),
                        '0' => 'goods'
                    );
                    $aGoods[] = $goods_tmp;
                }

                //模拟像购物车添加信息
                if(empty($aGoods)){
                    $url = $this->gen_url( array('app'=>'cardcoupons', 'ctl'=>'site_cards1', 'act'=>'showcard','arg'=>base64_encode($card_pass_no)));
                    $this->splash('failed', $url, app::get('physical')->_('商品无数据!'));
                    exit();
                }
                $this->_common($aGoods);

                $this->pagedata['cards']['card_id'] = $card_id;
                $this->pagedata['cards']['card_pass_no'] = $card_pass_no;
                $this->pagedata['gooddata'] = $goods_data;
                $this->pagedata['good_data'] =  $aGoods;
                if($goods_card_num != 0 || $goods_num != 0){
                    $cardhtml = "distribution.html";
                }else{
                    $cardhtml = "receiver.html";
                }
            }elseif($card_data[0]['type_id'] == "02"){
                $url = $this->gen_url(array('app'=>'physical','ctl'=>'site_exchange','act'=>'index','args'=>array('1','1','0',$obj_filter->check_input($_POST['cardgoods']),$obj_filter->check_input($_POST['card_pass_no']),base64_encode($card_pass_data[0]['card_pass']))));
                header('Content-Type: text/html; charset=utf-8');
                header('Location:'.$url);
                die();
            }elseif($card_data[0]['type_id'] == "03"){
                //选择体检卡 跳到服务兑换页面
                if(!empty($_POST['cardgoods'])){
                    $url = $this->gen_url(array('app'=>'physical','ctl'=>'site_exchange','act'=>'index','args'=>array('1','1','0',$obj_filter->check_input($_POST['cardgoods']),$obj_filter->check_input($_POST['card_pass_no']),base64_encode($card_pass_data[0]['card_pass']))));
                    header('Content-Type: text/html; charset=utf-8');
                    header('Location:'.$url);
                    die();
                }

                /**在复合卡中，选择实物类信息  那就需要走 实物类判断 start**/
                if($num <= 0){
                    $url = $this->gen_url( array('app'=>'cardcoupons', 'ctl'=>'site_cards1', 'act'=>'showcard','arg'=>base64_encode($card_pass_no)));
                    $this->splash('failed', $url, app::get('physical')->_('你还没有选择，请重新选择！'));
                    exit();
                }

                if(!empty($card_data[0]['rules']) || !empty($num)){
                    if($num > $card_data[0]['rules']){
                        $url = $this->gen_url( array('app'=>'cardcoupons', 'ctl'=>'site_cards1', 'act'=>'showcard','arg'=>base64_encode($card_pass_no)));
                        $this->splash('failed', $url, app::get('physical')->_('选择数量超过了,卡内数量！'));
                        exit();
                    }
                }

                $goods_object = kernel::single("b2c_mdl_goods");
                $goods_data = $goods_object->getList("store_id,goods_state,buy_m_count,fav_count,freight_bear,comments_count,avg_point,goods_id,name,bn,price,cost,mktprice,marketable,store,store_freeze,notify_num,score,weight,unit,brief,image_default_id,udfimg,thumbnail_pic,small_pic,big_pic,min_buy,package_scale,package_unit,package_use,score_setting,nostore_sell,goods_setting,disabled,spec_desc,adjunct,brand_id,type_id,cat_id,seo_info,act_type",array("goods_id"=>$goods_id));
                $storemanger_object = kernel::single("business_mdl_storemanger");
                foreach($obj_filter->check_input($_POST['goods']) as $k=>$v){
                    foreach($goods_data as $key=>$val){
                        if($k == $val['goods_id']){
                            $goods_data[$key]['virtual_card'] = $v['virtual_card']?$v['virtual_card']:0;
                            $goods_data[$key]['goods'] = $v['goods']?$v['goods']:0;
                            $goods_data[$key]['goods_card'] = $v['goods_card']?$v['goods_card']:0;
                            $goods_data[$key]['goods_nums'] = $goods_data[$key]['virtual_card']+$goods_data[$key]['goods']+$goods_data[$key]['goods_card'];
                            $storemanger_data = $storemanger_object->dump(array("store_id"=>$goods_data[$key]['store_id']),"store_name");
                            $goods_data[$key]['store_name'] = $storemanger_data['store_name'];
                        }
                    }
                }

                $list2dump = kernel::single("b2c_goods_list2dump");
                foreach($goods_data as $key=>$val){
                    $product = $list2dump->get_goods($goods_data[$key],1);
                    $goods_tmp = array(
                        'goods' => Array (
                            'num' => $val['goods_nums'],
                            'goods_id' => $val['goods_id'],
                            'pmt_id' =>'',
                            'product_id' =>key($product['product']),
                            'virtual_card' => $val['virtual_card'],
                            'goods' => $val['goods'],
                            'goods_card' => $val['goods_card']
                        ),
                        '0' => 'goods'
                    );
                    $aGoods[] = $goods_tmp;
                }
                //模拟像购物车添加信息
                if(empty($aGoods)){
                    $url = $this->gen_url( array('app'=>'cardcoupons', 'ctl'=>'site_cards1', 'act'=>'showcard','arg'=>base64_encode($card_pass_no)));
                    $this->splash('failed', $url, app::get('physical')->_('商品无数据！'));
                    exit();
                }

                $this->_common($aGoods);
                $this->pagedata['cards']['card_id'] = $card_id;
                $this->pagedata['cards']['card_pass_no'] = $card_pass_no;
                $this->pagedata['gooddata'] = $goods_data;
                $this->pagedata['good_data'] =  $aGoods;
                if($goods_card_num != 0 || $goods_num != 0){
                    $cardhtml = "distribution.html";
                }else{
                    $cardhtml = "receiver.html";
                }
                /**在复合卡中，选择实物类信息  那就需要走 实物类判断 end**/
            }else{
                $url = $this->gen_url( array('app'=>'cardcoupons', 'ctl'=>'site_cards1', 'act'=>'showcard','arg'=>base64_encode($card_pass_no)));
                $this->splash('failed', $url, app::get('physical')->_('卡券类型错误！'));
                exit();
            }
            $this->set_tmpl("card_default1");
			//防止重复提交
			$time = time();
            $sessObj = kernel::single('base_session');
            $sessObj->start();
            $kvstoreKeyname = 'card_time1' . $sessObj->sess_id();
            base_kvstore::instance('cardcouponsSiteCards')->store($kvstoreKeyname , $time , 3600);
			$this->pagedata['card_time'] = $time;
            $this->page('site/'.$cardhtml);

       }
    }


    function creat_order(){
		if(!$this->verify_member()){
            exit();
        }


        $obj_filter = kernel::single('b2c_site_filter');
        $_POST = $obj_filter->check_input($_POST);

        //session 数据开始
        kernel::single('base_session')->start();
        //url
        $this->begin(array('app'=>'cardcoupons','ctl'=>'site_cards1','act'=>'card_data'));
        

        //获取原型卡信息
        $cards_object = kernel::single("cardcoupons_mdl_cards");
        $obj_card = $cards_object->dump($_POST['cards']['card_id']);

        //获取会员信息
        $arrMember = $this->get_current_member();

        $cards_pass_object = kernel::single("cardcoupons_mdl_cards_pass");

        $cards_top_info = $cards_pass_object->getList("*",array('card_no'=>$obj_filter->check_input($_POST['cards']['card_pass_no'])));

        $card_status = $cards_top_info[0]['status'];
        //获取可兑金额
        $card_change_num = $obj_card['rules']  - $cards_top_info[0]['exchange_num'];

        if(in_array($cards_top_info[0]['status'],array(0,3,4))){
            $this->end(true, app::get('cardcoupons')->_("该卡券已经兑换！"), $this->gen_url(array('app'=>'b2c','ctl'=>'site_member','act'=>'condolences')),'',true);
            die();
        }

        //事物处理开始
        $db = kernel::database();
        $transaction_status = $db->beginTransaction();

        $msg = "";
        if($_POST['cards_noadd'] == 1){
            if(!$_POST['delivery']['ship_name'] || !$_POST['delivery']['ship_mobile']){
                if (!$_POST['delivery']['ship_name'])
                {
                    $msg .= app::get('b2c')->_("收货人姓名不能为空！")."<br />";
                }

                if (!$_POST['delivery']['ship_mobile'])
                {
                    $msg .= app::get('b2c')->_("手机不能为空！")."<br />";
                }

                if (strpos($msg, '<br />') !== false)
                {
                    $msg = substr($msg, 0, strlen($msg) - 6);
                }
                eval("\$msg = \"$msg\";");
                $this->end(false, $msg, '',true,true);
            }

        }else{
            if (!$_POST['delivery']['ship_area'] || !$_POST['delivery']['ship_addr_area'] || !$_POST['delivery']['ship_name'] || (!$_POST['delivery']['ship_email'] && !$arrMember['member_id']) || (!$_POST['delivery']['ship_mobile'] && !$_POST['delivery']['ship_tel']) || $_POST['delivery']['shipping_id'] || !$_POST['payment']['pay_app_id'])
            {
                if (!$_POST['delivery']['ship_area'] || !$_POST['delivery']['ship_addr_area'])
                {
                    $msg .= app::get('b2c')->_("收货地区不能为空！")."<br />";
                }

                if (!$_POST['delivery']['ship_name'])
                {
                    $msg .= app::get('b2c')->_("收货人姓名不能为空！")."<br />";
                }

                if (!$_POST['delivery']['ship_email'] && !$arrMember['member_id'])
                {
                    $msg .= app::get('b2c')->_("Email不能为空！")."<br />";
                }

                if (!$_POST['delivery']['ship_mobile'] && !$_POST['delivery']['ship_tel'])
                {
                    $msg .= app::get('b2c')->_("手机或电话必填其一！")."<br />";
                }

                if (!$_POST['payment']['pay_app_id'])
                {
                    $msg .= app::get('b2c')->_("支付方式不能为空！")."<br />";
                }

                if (strpos($msg, '<br />') !== false)
                {
                    $msg = substr($msg, 0, strlen($msg) - 6);
                }
                eval("\$msg = \"$msg\";");

                $this->end(false, $msg, '',true,true);
            }
        }

        //模拟购物车数据
        if(empty($_POST['goodsinfo'])){
            $this->end(true, app::get('cardcoupons')->_("无商品信息！"), $this->gen_url(array('app'=>'b2c','ctl'=>'site_member','act'=>'condolences')),'',true);
            die();
        }
        $allCart = array();
        foreach($obj_filter->check_input($_POST['goodsinfo']) as $k=>$v){
            //验证卡号是否充足
            //加入购物车

            if($v['goods']['goods_card'] != '0' || $v['goods']['virtual_card'] != '0'){
                if(!$this->check_card_num($v['goods'])){
                    $this->end(true, app::get('cardcoupons')->_("无库存，请重新选择商品！"), "",'',true);
                    die();
                }
            }

            kernel::single('fastbuy_cart_fastbuy_goods')->get_fastbuy_arr(
                $v,
                array(),
                $allCart
            );
        }

        //验证所选产品是否都符合规定值
        if(count($_POST['goodsinfo']) != count($allCart['object']['goods'])){
            $this->end(true, app::get('cardcoupons')->_("所选产品无库存或者没有上架，请重新选择产品！"), "",'',true);
            die();
        }
        $area_array = explode(":",$_POST['delivery']['ship_area']); 
        $area_id = array_pop($area_array);
        $sum_itemnum = 0;
        $sum_total_amount = 0;

        //获取分单信息 主要的
        $temp_split_order=kernel::single('b2c_cart_object_split')->split_order(kernel::single("b2c_ctl_site_cart"),$area_id,$allCart);
        foreach($temp_split_order as $store_id=>$sgoods){
             foreach($sgoods['slips'] as $order_sp=>$sorder){
                //判断是否支持的配送方式
                if(!$sorder['shipping']){
                    $this->end(true, $this->app->_("存在不支持配送到所选地区的商品，请重新选择！"), "",'',true);
                }
             }
        }
        //防止重复提交
        $sessObj = kernel::single('base_session');
        $sessObj->start();
        $kvstoreKeyname = 'card_time1' . $sessObj->sess_id();
        $cachedCardTime = null;
        base_kvstore::instance('cardcouponsSiteCards')->fetch($kvstoreKeyname, $cachedCardTime);
        if($cachedCardTime == $_POST['card_time']){
            base_kvstore::instance('cardcouponsSiteCards')->delete($kvstoreKeyname);
        }else{
            $this->end(true, $this->app->_("请勿重复提交！"), $this->gen_url(array('app'=>'cardcoupons','ctl'=>'site_cards','act'=>'index')),'',true);die;
		}
        foreach($temp_split_order as $store_id=>$sgoods){
            foreach($sgoods['slips'] as $order_sp=>$sorder){
                //post数据
                $order = &kernel::single("b2c_mdl_orders");
                //判断是否支持的配送方式
                if(!$sorder['shipping']){
                    $this->end(true, $this->app->_("存在不支持配送到所选地区的商品，请重新选择！"), "",'',true);
                }
                //分单后重新构建购物车数据.
                $aCart=$this->get_split_cart($allCart,$sorder,$obj_filter->check_input($_POST),$obj_filter->check_input($_POST['shipping'][$store_id][$order_sp]),$store_id);
                $postData=$this->get_post_cart($aCart,$sorder,$obj_filter->check_input($_POST),$obj_filter->check_input($_POST['shipping'][$store_id][$order_sp]));
                //取得购物车数据对应的订单数据。
                $order_data=$this->get_order_data($aCart,$postData,$store_id,$msg,$arrMember);
                $obj_order_create = kernel::single("b2c_order_create");
                $order_id=$order_data['order_id'];
                $order_data['memo'] = $obj_filter->check_input($_POST['cardRemark']) ? $obj_filter->check_input($_POST['cardRemark']) : "";
                $order_data['order_kind'] = "card";


                $sum_itemnum += $order_data['itemnum'];
                foreach($order_data['order_objects'] as $objkey => $objval){
                    $sum_total_amount += $objval['price'] * $objval['quantity'];
                }

                if($obj_card['change_way'] == 'num'){
                    if($sum_itemnum > $card_change_num){
                        $this->end(true, app::get('cardcoupons')->_("您选择的产品数量，超过可兑换数量，卡券兑换失败！"), $this->gen_url(array('app'=>'b2c','ctl'=>'site_member','act'=>'orders')),'',true);
                    }
                }elseif($obj_card['change_way'] == 'price'){
                    if($sum_total_amount > $card_change_num){
                        $this->end(true, app::get('cardcoupons')->_("您选择的产品总价，超过可兑换金额，卡券兑换失败！"), $this->gen_url(array('app'=>'b2c','ctl'=>'site_member','act'=>'orders')),'',true);
                    }
                }else{
                    //暂无其他模式
                    die;
                }

            if(empty($cards_top_info[0]['exchange_num'])){
                $exchange_num = 0;
            }else{
                $exchange_num = $cards_top_info[0]['exchange_num'];
            }
            if($obj_card['change_way'] == 'num'){
                $change_num = $obj_card['rules'] - $exchange_num - $sum_itemnum;
                $exchange_num += $sum_itemnum;
            }elseif($obj_card['change_way'] == 'price'){
                $change_num = $obj_card['rules'] - $exchange_num - $sum_total_amount;
                $exchange_num += $sum_total_amount;
            }else{
                //暂无其他模式
                die;
            }
            $cards_pass_object->update(array('exchange_num'=> $exchange_num),array('card_no'=>$obj_filter->check_input($_POST['cards']['card_pass_no'])));
            
            
            
                $result = $obj_order_create->save($order_data, $msg);
                if ($result)
                {
                    // 发票高级配置埋点
                    foreach(kernel::servicelist('invoice_setting') as $services ) {
                        if (is_object($services) ) {
                            if (method_exists($services, 'saveInvoiceData') ) {
                                $services->saveInvoiceData($postData['order_id'],$postData['payment']);
                            }
                        }
                    }
                }

                // 取到日志模块
                if ($arrMember['member_id']){
                    $obj_members = kernel::single("b2c_mdl_members");
                    $arrPams = $obj_members->dump($arrMember['member_id'], '*', array(':account@pam' => array('*')));
                }

                // remark create
                $obj_order_create = kernel::single("b2c_order_remark");
                $arr_remark = array(
                    'order_bn' => $order_id,
                    'mark_text' => $postData['memo'],
                    'op_name' => (!$arrMember['member_id']) ? app::get('b2c')->_('顾客') : $arrPams['pam_account']['login_name'],
                    'mark_type' => 'b0',
                );

                $log_text = "";
                if ($result){
                    $log_text[] = array(
                        'txt_key'=>'订单创建成功！',
                        'data'=>array(
                        ),
                    );
                    $log_text = serialize($log_text);
                }else{
                    $log_text[] = array(
                        'txt_key'=>'订单创建失败！',
                        'data'=>array(
                        ),
                    );
                    $log_text = serialize($log_text);
                }

                $orderLog = kernel::single("b2c_mdl_order_log");
                $sdf_order_log = array(
                    'rel_id' => $order_id,
                    'op_id' => $arrMember['member_id'],
                    'op_name' => (!$arrMember['member_id']) ? app::get('b2c')->_('顾客') : $arrPams['pam_account']['login_name'],
                    'alttime' => time(),
                    'bill_type' => 'order',
                    'behavior' => 'creates',
                    'result' => 'SUCCESS',
                    'log_text' => $log_text,
                );

                $log_id = $orderLog->save($sdf_order_log);

                if ($result){
                    foreach(kernel::servicelist('b2c_save_post_om') as $object){
                        $object->set_arr($order_id, 'order');
                    }
                    // 得到物流公司名称
                    if ($order_data['order_objects']){
                        $itemNum = 0;
                        $good_id = "";
                        $goods_name = "";
                        foreach ($order_data['order_objects'] as $arr_objects){
                            if ($arr_objects['order_items']){
                                if ($arr_objects['obj_type'] == 'goods'){
                                    $obj_goods = kernel::single("b2c_mdl_goods");
                                    $good_id = $arr_objects['order_items'][0]['goods_id'];
                                    $obj_goods->updateRank($good_id, 'buy_count',$arr_objects['order_items'][0]['quantity']);
                                    $arr_goods = $obj_goods->dump($good_id);
                                }

                                foreach ($arr_objects['order_items'] as $arr_items){
                                    $itemNum = kernel::single("ectools_math")->number_plus(array($itemNum, $arr_items['quantity']));
                                    if ($arr_objects['obj_type'] == 'goods'){
                                        if ($arr_items['item_type'] == 'product')
                                            $goods_name .= $arr_items['name'] . ($arr_items['products']['spec_info'] ? '(' . $arr_items['products']['spec_info'] . ')' : '') . '(' . $arr_items['quantity'] . ')';
                                    }
                                }
                            }
                        }
                        $obj_dlytype = kernel::single("b2c_mdl_dlytype");
                        $arr_dlytype = $obj_dlytype->dump($order_data['shipping']['shipping_id'], 'dt_name');
                        $arr_updates = array(
                            'order_id' => $order_id,
                            'total_amount' => $order_data['total_amount'],
                            'shipping_id' => $arr_dlytype['dt_name'],
                            'ship_mobile' => $order_data['consignee']['mobile'],
                            'ship_tel' => $order_data['consignee']['telephone'],
                            'ship_addr' => $order_data['consignee']['addr'],
                            'ship_email' => $order_data['consignee']['email'] ? $order_data['consignee']['email'] : '',
                            'ship_zip' => $order_data['consignee']['zip'],
                            'ship_name' => $order_data['consignee']['name'],
                            'member_id' => $order_data['member_id'] ? $order_data['member_id'] : 0,
                            'uname' => (!$order_data['member_id']) ? app::get('b2c')->_('顾客') : $arrPams['pam_account']['login_name'],
                            'itemnum' => count($order_data['order_objects']),
                            'goods_id' => $good_id,
                            'goods_url' => kernel::base_url(1).kernel::url_prefix().$this->gen_url(array('app'=>'b2c','ctl'=>'site_product','act'=>'index','arg0'=>$good_id)),
                            'thumbnail_pic' => base_storager::image_path($arr_goods['image_default_id']),
                            'goods_name' => $goods_name,
                            'ship_status' => '',
                            'pay_status' => 'Nopay',
                            'is_frontend' => true,
                        );
                        //$order->fireEvent('create', $arr_updates, $order_data['member_id']);
                    }


                    /** 订单创建结束后执行的方法 **/
                    $odr_create_service = kernel::servicelist('b2c_order.create');
                    $arr_order_create_after = array();
                    if ($odr_create_service){
                        foreach ($odr_create_service as $odr_ser){
                            if(!is_object($odr_ser)) continue;
                            if( method_exists($odr_ser,'get_order') )
                                $index = $odr_ser->get_order();
                            else $index = 10;
                            while(true) {
                                if( !isset($arr_order_create_after[$index]) )break;
                                $index++;
                            }
                            $arr_order_create_after[$index] = $odr_ser;
                        }
                    }
                    ksort($arr_order_create_after);
                    if ($arr_order_create_after){
                        foreach ($arr_order_create_after as $obj){
                            $obj->generate($order_data);
                        }
                    }
                    /** end **/
                }else{
                    $db->rollback();
                }

                if ($result){
                    $order_num = $order->count(array('member_id' => $order_data['member_id']));
                    $obj_mem = kernel::single("b2c_mdl_members");
                    $obj_mem->update(array('order_num'=>$order_num), array('member_id'=>$order_data['member_id']));

                    /** 兑换卡券订单处理  start**/
                    if ($order_data){
                        // 模拟支付流程
                        $objPay = kernel::single("ectools_pay");
                        $sdf = array(
                            'payment_id' => $objPay->get_payment_id(),
                            'order_id' => $order_data['order_id'],
                            'rel_id' => $order_data['order_id'],
                            'op_id' => $order_data['member_id'],
                            'pay_app_id' => $order_data['payinfo']['pay_app_id'],
                            'currency' => $order_data['currency'],
                            'payinfo' => array(
                                'cost_payment' => $order_data['payinfo']['cost_payment'],
                            ),
                            'pay_object' => 'order',
                            'member_id' => $order_data['member_id'],
                            'op_name' => $this->user->user_data['account']['login_name'],
                            'status' => 'ready',
                            'cur_money' => $order_data['cur_amount'],
                            'money' => $order_data['total_amount'],
                        );
                        $is_payed = $objPay->gopay($sdf, $msg);
                        if (!$is_payed){
                            $msg = app::get('b2c')->_('订单自动支付失败！');
                            $this->end(false, $msg, $this->gen_url(array('app'=>'b2c','ctl'=>'site_member','act'=>'condolences')));
                        }

                        $obj_pay_lists = kernel::servicelist("order.pay_finish");

                        $is_payed = false;

                        foreach ($obj_pay_lists as $order_pay_service_object)
                        {
							$sdf['is_card']=true;
                            $is_payed = $order_pay_service_object->order_pay_finish($sdf, 'succ', 'font',$msg);
                        }
                    }

                    /** 兑换卡券订单处理  end**/

                    // 与中心交互
                    $is_need_rpc = false;
                    $obj_rpc_obj_rpc_request_service = kernel::servicelist('b2c.rpc_notify_request');
                    foreach ($obj_rpc_obj_rpc_request_service as $obj){
                        if ($obj && method_exists($obj, 'rpc_judge_send')){
                            if ($obj instanceof b2c_api_rpc_notify_interface)
                                $is_need_rpc = $obj->rpc_judge_send($order_data);
                        }
                        if ($is_need_rpc) break;
                    }

                    if ($is_need_rpc){
                        //新的版本控制api
                        $obj_apiv = kernel::single('b2c_apiv_exchanges_request');
                        $obj_apiv->rpc_caller_request($order_data, 'ordercreate');
                    }
                    $flag = true;
                    $aOrders[] = $order_id;
                }else{
                    $flag = false;
                }
            }
        }


        if($flag){
			//订单都执行成功以后 执行commit  修改update 状态
            //判断卡券领取状态  初始-已领取-本次领取
            //部分领取合并上次 订单信息
            if($card_status =='9'){
                $aOrders = array_merge($cards_top_info[0]['exchange_order_id'],$aOrders);
            }

            $card_status = '3';  //已领取
            if($change_num > 0){
                $card_status = '9';  //部分领取
            }

            $cards_pass_object->update(array('status'=>$card_status,'use_time'=>time(),'exchange_order_id'=>$aOrders),array('card_no'=>$obj_filter->check_input($_POST['cards']['card_pass_no'])));
            $db->commit($transaction_status);
            $orderStr = implode(',', $aOrders);


            if($card_status == '3'){
                $_sjson = array(
                    'METHOD'=>'updateDocItemStatus',
                    'CARD_NUMBER'=>$obj_filter->check_input($_POST['cards']['card_pass_no']),
                    'REC_ORDER_ID'=>$orderStr,
                    'REC_STATUS'=>'I01102'
                );
                $post_data = array('serviceNo'=>'DocumentItemService',"inputParam"=>json_encode($_sjson));
                $tmpdata = SFSC_HttpClient::doPost(DO_SERVER_URL,$post_data);
            }
            if(count($aOrders)>1){
                $this->end(true, app::get('cardcoupons')->_("卡券兑换成功！"), $this->gen_url(array('app'=>'b2c','ctl'=>'site_member','act'=>'orders')),'',true);
            }else{
                $this->end(true, app::get('cardcoupons')->_("卡券兑换成功！"), $this->gen_url(array('app'=>'b2c','ctl'=>'site_member','act'=>'orders')),'',true);
            }
        }else{
            $this->end(false, $msg, $this->gen_url(array('app'=>'b2c','ctl'=>'site_member','act'=>'condolences')),true,true);
        }

    }


    //购物车
    private function _common($goods=array()){

        //获取会员信息
        $arrMember = $this->get_current_member();

        //模拟购物车数据 (默认商品的规格和分类已经做好了)
        $aCart = array();
        foreach($goods as $k=>$v){
            kernel::single('fastbuy_cart_fastbuy_goods')->get_fastbuy_arr(
                $v,
                array(),
                $aCart
            );
        }
        //判断购物车有没有自己的商品
        foreach($aCart['object']['goods'] as $k=>$v){
            $check_objects = kernel::servicelist('business_check_goods_isMy');
            $sign = true;
            if($check_objects){
                foreach($check_objects as $check_object){
                    $check_object->check_goods_isMy($v['params']['goods_id'],$msg,$sign);
                }
                if(!$sign){
                    $this->splash('failed',$this->gen_url(array('app'=>'b2c','act'=>'index','ctl'=>'site_product','arg1'=>$aCart['object']['goods'][0]['params']['goods_id']) ) , app::get('b2c')->_('不能购买自己的商品'));
                }
            }
        }

        $this->pagedata['aCart'] = $aCart;

        if($this->ajax_update === true){
            foreach(kernel::servicelist('b2c_cart_object_apps') as $object){
                if(!is_object($object)) continue;
                //应该判断是否实现了接口
                if(!method_exists($object,'get_update_num')) continue;
                if(!method_exists($object,'get_type')) continue;
                $this->pagedata['edit_ajax_data'] = $object->get_update_num($aCart['object'][$object->get_type()],$this->update_obj_ident);
                if($this->pagedata['edit_ajax_data']){
                    $this->pagedata['edit_ajax_data'] = json_encode( $this->pagedata['edit_ajax_data'] );
                    if($object->get_type()=='goods'){
                        $this->pagedata['update_cart_type_godos'] = true;
                        if(!method_exists( $object,'get_error_html')) continue;
                        $this->pagedata['error_msg'] = $object->get_error_html($aCart['object']['goods'],$this->update_obj_ident);
                    }
                    break;
                }
            }
        }

        /**
         * 额外设置的地址checkbox是否显示
         */
        $is_recsave_display = 'true';
        $is_rec_addr_edit = 'true';
        $app_id = 'b2c';
        $obj_recsave_checkbox = kernel::servicelist('b2c.checkout_recsave_checkbox');
        $arr_extends_checkout = array();
        if ($obj_recsave_checkbox)
        {
            foreach($obj_recsave_checkbox as $object)
            {
                if(!is_object($object)) continue;

                if( method_exists($object,'get_order') )
                    $index = $object->get_order();
                else $index = 10;

                while(true) {
                    if( !isset($arr_extends_checkout[$index]) )break;
                    $index++;
                }
                $arr_extends_checkout[$index] = $object;
            }
            ksort($arr_extends_checkout);
        }
        if ($arr_extends_checkout)
        {
            foreach ($arr_extends_checkout as $obj)
            {
                if ( method_exists($obj,'check_display') )
                    $obj->check_display($is_recsave_display);
                if ( method_exists($obj,'check_edit') )
                    $obj->check_edit($is_rec_addr_edit);
                if ( method_exists($obj,'check_app_id') )
                    $obj->check_app_id($app_id);
            }
        }
        $this->pagedata['is_recsave_display'] = $is_recsave_display;
        $this->pagedata['is_rec_addr_edit'] = $is_rec_addr_edit;
        $this->pagedata['app_id'] = $app_id;



        $obj_member_addrs = kernel::single("b2c_mdl_member_addrs");
        $obj_dltype = kernel::single("b2c_mdl_dlytype");
        $addr = array();
        $member_point = 0;
        $shipping_method = '';
        $shipping_id = 0;
        $arr_shipping_method = array();
        $payment_method = 0;
        $def_addr = 0;
        $arr_def_addr = array();
        $str_def_currency = $arrMember['member_cur'] ? $arrMember['member_cur'] : "";

        if ($arrMember['member_id'])
        {
            // 得到当前会员的积分
            $obj_members = kernel::single("b2c_mdl_members");
            $arr_member = $obj_members->dump($arrMember['member_id'], 'point,addon');
            $member_point = $arr_member['point'];
            if (isset($arr_member['addon']) && $arr_member['addon'])
            {
                $arr_addon = unserialize(stripslashes($arr_member['addon']));
                if ($arr_addon)
                {
                    $obj_session = kernel::single('base_session');
                    $obj_session->start();
                    if ($arr_addon['def_addr']['usable'])
                    {
                        if (!isset($_COOKIE['purchase']['addr']['usable']))
                        {
                            $arr_addon['def_addr']['usable'] = '';
                            $str_addon = serialize($arr_addon);
                            $obj_members->update(array('addon'=>$str_addon), array('member_id'=>$arrMember['member_id']));
                        }
                        elseif ($_COOKIE['purchase']['addr']['usable'] != md5($obj_session->sess_id().$arrMember['member_id']))
                        {
                            $arr_addon['def_addr']['usable'] = '';
                            $str_addon = serialize($arr_addon);
                            $obj_members->update(array('addon'=>$str_addon), array('member_id'=>$arrMember['member_id']));
                        }
                    }
                    $tmp_cnt = $obj_member_addrs->count(array('member_id'=>$arrMember['member_id'],'def_addr'=>'1'));
                    if ($arr_addon['def_addr']  && ((isset($arr_addon['def_addr']['usable']) && $arr_addon['def_addr']['usable'] == md5($obj_session->sess_id().$arrMember['member_id'])) || $tmp_cnt == 0))
                    {
                        $def_addr = $arr_addon['def_addr']['addr_id'] ? $arr_addon['def_addr']['addr_id'] : 0;
                        $arr_area = explode(':', $arr_addon['def_addr']['area']);
                        $def_area = $arr_area[2];
                        $arr_def_addr = $arr_addon['def_addr'];
                        $arr_def_addr['addr_id'] = $arr_addon['def_addr']['addr_id'];
                        $arr_def_addr['def_addr'] = $arr_addon['def_addr']['def_addr'];
                        $arr_def_addr['addr_region'] = $arr_addon['def_addr']['area'];
                        $arr_def_addr['addr'] = $arr_addon['def_addr']['addr'];
                        $arr_def_addr['zip'] = $arr_addon['def_addr']['zip'];
                        $arr_def_addr['name'] = $arr_addon['def_addr']['name'];
                        $arr_def_addr['mobile'] = $arr_addon['def_addr']['mobile'];
                        $arr_def_addr['tel'] = $arr_addon['def_addr']['tel'] ? $arr_addon['def_addr']['tel'] : '';
                        $arr_def_addr['day'] = $arr_addon['def_addr']['day'] ? $arr_addon['def_addr']['day'] : '';
                        $arr_def_addr['specal_day'] = $arr_addon['def_addr']['specal_day'] ? $arr_addon['def_addr']['specal_day'] : '';
                        $arr_def_addr['time'] = $arr_addon['def_addr']['time'] ? $arr_addon['def_addr']['time'] : '';
                        if ($arr_def_addr['day'] == app::get('b2c')->_('任意日期') && $arr_def_addr['time'] == app::get('b2c')->_('任意时间段'))
                        {
                            unset($arr_def_addr['day']);
                            unset($arr_def_addr['time']);
                        }
                    }
                }
            }

            $addrMember = array(
                'member_id' => $arrMember['member_id'],
            );
            $addrlist = $obj_member_addrs->getList('*',array('member_id'=>$arrMember['member_id']));
            $is_checked = false;
            $is_def = false;
            foreach($addrlist as $key=>$rows)
            {
                if(empty($rows['tel'])){
                    $str_tel = app::get('b2c')->_('手机：').$rows['mobile'];
                }else{
                    $str_tel = app::get('b2c')->_('电话：').$rows['tel'];
                }
                if ((isset($arr_def_addr['addr_id']) && $rows['addr_id'] == $arr_def_addr['addr_id']) || (!$arr_def_addr && $rows['def_addr']))
                {
                    $is_def = true;
                    $is_checked = true;
                }
                $addr[] = array('addr_id'=> $rows['addr_id'],'def_addr'=>$is_def ? 1 : 0,'addr_region'=> $rows['area'],
                    'addr_label'=> $rows['addr'].app::get('b2c')->_(' (收货人：').$rows['name'].' '.$str_tel.app::get('b2c')->_(' 邮编：').$rows['zip'].')');
                if ($rows['def_addr'])
                {
                    $def_addr = $rows['addr_id'];
                    $arr_area = explode(':', $rows['area']);
                    $def_area = $arr_area[2];
                    $arr_def_addr_member = array(
                        'addr_id'=> $rows['addr_id'],
                        'def_addr'=>$rows['def_addr'],
                        'addr_region'=> $rows['area'],
                        'addr'=> $rows['addr'],
                        'zip' => $rows['zip'],
                        'name' => $rows['name'],
                        'mobile' => $rows['mobile'],
                        'tel' => $rows['tel'],
                    );
                }
                else
                {
                    if ($key == 0 && !$def_area)
                    {
                        $arr_area = explode(':', $rows['area']);
                        $def_area = $arr_area[2];
                    }
                }
            }
            if ($arr_def_addr && !$is_checked)
                $this->pagedata['other_addr_checked'] = 'true';
            if ($addrlist && !$arr_def_addr && !$is_checked)
            {
                $def_addr = $addrlist[0]['addr_id'];
                $arr_area = explode(':', $addrlist[0]['area']);
                $def_area = $arr_area[2];
                $arr_def_addr_member = $addrlist[0];
                $arr_def_addr_member['addr_id'] = $addrlist[0]['addr_id'];
                $arr_def_addr_member['def_addr'] = 1;
                $arr_def_addr_member['addr_region'] = $addrlist[0]['area'];
                $arr_def_addr_member['addr'] = $addrlist[0]['addr'];
                $arr_def_addr_member['zip'] = $addrlist[0]['zip'];
                $arr_def_addr_member['name'] = $addrlist[0]['name'];
                $arr_def_addr_member['mobile'] = $addrlist[0]['mobile'];
                $arr_def_addr_member['tel'] = $addrlist[0]['tel'];
                $addr[0]['def_addr'] = 1;
            }
        }

        // 会员没有默认的地址 默认的货币
        if ((!$def_addr || !$str_def_currency) && !$arrMember['member_id'])
        {
            if ($_COOKIE['purchase']['addon'])
            {
                $arr_addon = unserialize(stripslashes($_COOKIE['purchase']['addon']));
                if (!$def_addr)
                {
                    if (isset($arr_addon['member']['ship_area']) && $arr_addon['member']['ship_area'])
                    {
                        $def_addr = 0;
                        $arr_area = explode(':', $arr_addon['member']['ship_area']);
                        $def_area = $arr_area[2];
                        $arr_def_addr = $arr_addon['member'];
                        $arr_def_addr['addr_region'] = $arr_addon['member']['ship_area'];
                        $arr_def_addr['addr'] = $arr_addon['member']['ship_addr'];
                        $arr_def_addr['zip'] = $arr_addon['member']['ship_zip'] ? $arr_addon['member']['ship_zip'] : '';
                        $arr_def_addr['name'] = $arr_addon['member']['ship_name'];
                        $arr_def_addr['email'] = $arr_addon['member']['ship_email'];
                        $arr_def_addr['mobile'] = $arr_addon['member']['ship_mobile'];
                        $arr_def_addr['tel'] = $arr_addon['member']['ship_tel'] ? $arr_addon['member']['ship_tel'] : '';
                        $arr_def_addr['day'] = $arr_addon['member']['day'] ? $arr_addon['member']['day'] : '';
                        $arr_def_addr['specal_day'] = $arr_addon['member']['specal_day'] ? $arr_addon['member']['specal_day'] : '';
                        $arr_def_addr['time'] = $arr_addon['member']['time'] ? $arr_addon['member']['time'] : '';
                        if ($arr_def_addr['day'] == app::get('b2c')->_('任意日期') && $arr_def_addr['time'] == app::get('b2c')->_('任意时间段'))
                        {
                            unset($arr_def_addr['day']);
                            unset($arr_def_addr['time']);
                        }
                        $this->pagedata['addr'] = array(
                            'area'=> $arr_addon['member']['ship_area'],
                            'addr'=> $arr_addon['member']['ship_addr'],
                            'zipcode' => $arr_addon['member']['ship_zip'] ? $arr_addon['member']['ship_zip'] : '',
                            'name' => $arr_addon['member']['ship_name'],
                            'email' => $arr_addon['member']['ship_email'],
                            'phone' => array(
                                'mobile'=>$arr_addon['member']['ship_mobile'],
                                'telephone' => $arr_addon['member']['ship_tel'] ? $arr_addon['member']['ship_tel'] : ''
                            ),
                        );
                    }
                }
            }
        }

        $obj_dlytype = kernel::single("b2c_mdl_dlytype");
        $arr_shipping_info = $obj_dlytype->get_shiping_info($shipping_id, $this->pagedata['aCart']["subtotal"]);
        $this->pagedata['def_addr'] = $def_addr ? $def_addr : 0;
        $this->pagedata['def_area'] = $def_area ? $def_area : 0;

        foreach($addrlist as $k=>$v){
            $area = array();
            $area = explode(':',$v['area']);
            $addrlist[$k]['_area'] = $area[2];
            $area = explode('/',$area[1]);

            if(in_array($area[0],array('北京','天津','上海','重庆'))){
                $area[0] = '';
            }

            $addrlist[$k]['area_arr'] = $area;
            if($v['def_addr']==1){
                $addr_default_addr = $addrlist[$k];
            }
        }
        if($addrlist){
            if(!$addr_default_addr){
                $addrlist[0]['def_addr'] = 1;
                $addr_default_addr = $addrlist[0];
            }
        }

        $this->pagedata['addrlist'] = $addrlist;
        $this->pagedata['default_addr'] = $addr_default_addr;
        $defaule_area_id = explode(':',$addr_default_addr['area']);
        $this->pagedata['area_id'] = $defaule_area_id[2];
        $this->pagedata['address']['member_id'] = $arrMember['member_id'];
        $this->pagedata['def_arr_addr'] = $arr_def_addr ? $arr_def_addr : $arr_def_addr_member;
        $this->pagedata['def_arr_addr_member'] = $arr_def_addr_member;
        $this->pagedata['def_arr_addr_other'] = $arr_def_addr;
        $this->pagedata['site_checkout_zipcode_required_open'] = app::get('cardcoupons')->getConf('site.checkout.zipcode.required.open');

        $currency = app::get('ectools')->model('currency');
        $this->objMath = kernel::single("ectools_math");
        //订单总和
        $total_amount = $this->objMath->number_minus(array($this->pagedata['aCart']["subtotal"], $this->pagedata['aCart']['discount_amount']));
        $split_order=kernel::single('b2c_cart_object_split')->split_order(kernel::single("b2c_ctl_site_cart"),$this->sssspagedata['area_id'],$this->pagedata['aCart']);
    }
    //取得订单数据。
    function get_order_data($aCart,$postData,$store_id,&$msg,$arrMember){
        $obj_filter = kernel::single('b2c_site_filter');
        $postData = $obj_filter->check_input($postData);
        $order = &kernel::single("b2c_mdl_orders");
        $postData['order_id'] = $order_id = $order->gen_id();
        $postData['member_id'] = $arrMember['member_id'] ? $arrMember['member_id'] : 0;
        $order_data = array();

        // 加入订单能否生成的判断
        /*
       $obj_checkorder = kernel::service('b2c_order_apps', array('content_path'=>'b2c_order_checkorder'));
       if ($obj_checkorder)
       {
           if (!$obj_checkorder->check_create($aCart, $postData['delivery']['ship_area'], $message)){
               $this->end(false, $message);
           }

       }
       */
        $obj_order_create = kernel::single("b2c_order_create");
        $order_data = $obj_order_create->generate($postData,'',$msg,$aCart);
        $order_data['store_id'] = $store_id;
        $obj_checkproducts = kernel::servicelist('b2c_order_check_products');
        if ($obj_checkproducts)
        {
            foreach($obj_checkproducts as $obj_check){
                if (!$obj_check->check_products($order_data, $messages)){
                    $this->end(false, $messages,'',true,true);
                }
            }
        }

        if (!$order_data || !$order_data['order_objects'])
        {
            $msg = app::get('b2c')->_("订单生成失败！");
            $this->end(false, $msg, '',true,true);
        }
        /*
        if($order_data['shipping']['shipping_id'] == null || $order_data['shipping']['shipping_id'] == ''){
            $msg = app::get('b2c')->_("请选择店铺配送方式！");
            $this->end(false, $msg, '',true,true);
        }
        */
        return $order_data;
    }
    //为分单准备post数据。
    function get_post_cart($aCart,$sOrder,$postData,$oShipping){
        $post=array();
        if(isset($postData['purchase'])){
            $post['purchase']=$postData['purchase'];
        }
        if(isset($postData['split_order'])){
            $post['split_order']=$postData['purchase'];
        }
        if(isset($postData['extends_args'])){
            $post['extends_args']=$postData['extends_args'];
        }
        if(isset($postData['delivery'])){
            $post['delivery']=$postData['delivery'];
        }
        if(isset($postData['payment'])){
            $post['payment']=$postData['payment'];
        }
        if(isset($postData['fromCart'])){
            $post['fromCart']=$postData['fromCart'];
        }
        if(isset($postData['split_order'])){
            $post['split_order']=$postData['split_order'];
        }
        if(isset($postData['minfo'])){
            $post['minfo']=$postData['minfo'];
        }
        $shipping_id = $oShipping['shipping_id'];
        $post['delivery']['shipping_id'] = $shipping_id;
        $post['delivery']['is_protect'][$shipping_id] = $oShipping['is_protect'];
        $post['is_protect'][]= $oShipping['is_protect'];
        $post['shipping_id'][]= $oShipping['shipping_id'];
        $post['memo'] = $oShipping['memo'];
        $post['payment']['dis_point']=0;
        return $post;
    }

    //根据分单数据重新构建购物车结构，以便直接生成订单结构。
    function get_split_cart(&$allCart,$sOrder,$postData,$oShipping,$store_id){
        $sCart=array();
        foreach($sOrder['object'] as $obj_type=>$obj){
            foreach($obj['index'] as $index){
                $sCart['object'][$obj_type][]=$allCart['object'][$obj_type][$index];
            }
        }
        $mCart = kernel::single("b2c_mdl_cart");
        $mCart->count_objects($sCart);
        if($allCart['promotion']){
            foreach($allCart['promotion'] as $pkey=> $promotion){
                foreach($promotion as $ptypekey=> $pcoupon){
                    if($allCart['is_free_shipping'][$store_id]){//免运费
                        if($pcoupon['store_id']==$store_id){
                            $sCart['promotion'][$pkey][$ptypekey]=$pcoupon;
                        }
                    }elseif ($pkey == 'coupon'){
                        $sCart['promotion'][$pkey][$ptypekey] = $pcoupon;
                    }else{//折扣或者送优惠券
                        if($pcoupon['store_id']==$store_id){
                            if($pcoupon['discount_amount']>0){
                                if($sCart['subtotal']>$pcoupon['discount_amount']){

                                    $sCart['discount_amount']=$pcoupon['discount_amount'] + $sCart['discount_amount_prefilter'];
                                    $sCart['subtotal_discount']=$pcoupon['discount_amount'] + $sCart['discount_amount_prefilter'];
                                    $sCart['discount_amount_order']=$pcoupon['discount_amount'];
                                    $allCart['promotion'][$pkey][$ptypekey]=$pcoupon['discount_amount']-$sCart['subtotal'];
                                }
                                $sCart['promotion'][$pkey][$ptypekey]=$pcoupon;
                            }else if($pcoupon['discount_amount']==0){//送优惠券。
                                $sCart['promotion'][$pkey][$ptypekey]=$pcoupon;
                                unset($allCart['promotion'][$pkey][$ptypekey]);
                            }
                        }
                    }
                }
            }
        }

        if($allCart['object']['coupon']){
            foreach($allCart['object']['coupon'] as $coupon){
                if($coupon['store_id']==$store_id){
                    $sCart['object']['coupon'][]=$coupon;
                }
            }
        }
        if($allCart['is_free_shipping']){
            $sCart['is_free_shipping']=$allCart['is_free_shipping'][$store_id];
        }
        if($allCart['free_shipping_rule_type']){
            $sCart['free_shipping_rule_type']=$allCart['free_shipping_rule_type'][$store_id];
        }
        if($allCart['free_shipping_rule_id']){
            $sCart['free_shipping_rule_id']=$allCart['free_shipping_rule_id'][$store_id];
        }
        if(isset($allCart['inAct'])){
            $sCart['inAct']=$allCart['inAct'];
        }

        if(isset($allCart['promotion_order_create'])){
            foreach($allCart['promotion_order_create'] as $key=>$promotion_order_create){
                foreach($promotion_order_create as $k=>$val){
                    $objCoupon = app::get('b2c')->model('coupons');
                    $cList = $objCoupon->dump($val['cpns_id'][0]);
                    $cList['store_id'] = explode(',',$cList['store_id']);
                    if($cList['store_id'][1]==$store_id){
                        $sCart['promotion_order_create'][$key][$k] = $val;
                    }
                }
            }
        }
        return $sCart;
    }

    function write_card_log($status = false,$card_no,$card_pass,$order_id,$arrMember,$goods_name){
        $orderLog = kernel::single("b2c_mdl_order_log");
        if($status){
            $order_log = array(
                'rel_id' => $order_id,
                'op_id' => 0,
                'op_name' => 'auto',
                'alttime' => time(),
                'bill_type' => 'order',
                'behavior' => 'delivery',
                'result' => 'SUCCESS',
                'log_text' => serialize(array(array('txt_key'=>'电子券<span class="siteparttitle-orage">'.$goods_name.'</span>自动兑换'.$card_no.'成功！','data'=>array()))),
            );
        }else{
            $order_log = array(
                'rel_id' => $order_id,
                'op_id' => 0,
                'op_name' => 'auto',
                'alttime' => time(),
                'bill_type' => 'order',
                'behavior' => 'delivery',
                'result' => 'FAILURE',
                'log_text' => serialize(array(array('txt_key'=>'电子券<span class="siteparttitle-orage">'.$goods_name.'</span>自动兑换失败！','data'=>array()))),
            );
        }

        $orderLog->save($order_log);
    }


    function auto_changeproduct($goods_ids,$sdf_payment,$order_ids,$is_merge=false){
        $objEntityGoods = kernel::single('b2c_mdl_goods_entity_items');
        $objGoodsEntity = kernel::single("b2c_mdl_entity_goods");
        $objOrders = kernel::single("b2c_mdl_orders");
        $objGoods =  kernel::single('b2c_mdl_goods');
        $objProducts = kernel::single('b2c_mdl_products');
        $tag = true;
        foreach($goods_ids as $key=>$val){
            for($i=0;$i<$val['nums'];$i++){
                $goods_info = $objGoods->dump(array('goods_id'=>$val['goods_id']),'store');
                $goods_store = $goods_info['store'] - 1;
                $objGoods->update(array('store'=>$goods_store),array('goods_id'=>$val['goods_id']));

                $product_info = $objProducts->dump(array('product_id'=>$val['product_id']),'store,freez');
                $product_store = $product_info['store'] - 1;
                $product_freez = $product_info['freez'] - 1;
                $objProducts->update(array('store'=>$goods_store,'freez'=>$product_freez),array('product_id'=>$val['product_id']));

            }
        }

        // 更新发货日志结果
        foreach($order_ids as $key=>$val){
            $objorder_log = kernel::single("b2c_mdl_order_log");
            if($tag){
                $sdf_order_log = array(
                    'rel_id' => $val,
                    'op_id' => '0',
                    'op_name' => 'auto',
                    'alttime' => time(),
                    'bill_type' => 'order',
                    'behavior' => 'delivery',
                    'result' => 'SUCCESS',
                    'log_text' => '电子券系统已发货，无需物流',
                    'addon' => $log_addon,
                );
            }else{
                $sdf_order_log = array(
                    'rel_id' => $val,
                    'op_id' => '0',
                    'op_name' => 'auto',
                    'alttime' => time(),
                    'bill_type' => 'order',
                    'behavior' => 'delivery',
                    'result' => 'FAILURE',
                    'log_text' => '发货出错',
                    'addon' => $log_addon,
                );
            }
            $log_id = $objorder_log->save($sdf_order_log);
            if($log_id){
                //ajx crm
                //修改订单状态
                $aUpdate['order_id'] = $val;
                $aUpdate['ship_status'] = '1';
                $objOrders->save($aUpdate);
                $obj_apiv = kernel::single('b2c_apiv_exchanges_request');
                $req_arr['order_id']=$val;
                $obj_apiv->rpc_caller_request($req_arr, 'orderupdatecrm');
                $data['confirm_time'] = time()+(app::get('b2c')->getConf('member.to_finish_XU'))*86400;
                $arr = app::get('business')->model('orders')->update($data,array('order_id' => $val));
            }
        }
    }

    function logout(){
        app::get('cardcoupons')->member_id = 0;
        $this->cookie_path = kernel::base_url().'/';
        $this->set_cookie('CARDNAME','',time()-3600);
        kernel::single('base_session')->start();
        unset($_SESSION['account']['card']);
        unset($_SESSION['type']);
        unset($_SESSION['last_error']);
        $this->redirect(array('app'=>'cardcoupons','ctl'=>'site_cards1','act'=>'index','full'=>1));
    }


    function auto_logout(){
        app::get('cardcoupons')->member_id = 0;
        $this->cookie_path = kernel::base_url().'/';
        $this->set_cookie('CARDNAME','',time()-3600);
        kernel::single('base_session')->start();
        unset($_SESSION['account']['card']);
        unset($_SESSION['type']);
        unset($_SESSION['last_error']);
    }


    function check_card_num($card_info = array()){
        if(empty($card_info)){
            return false;
        }
        $cards_object = kernel::single("cardcoupons_mdl_cards");
        $cards_pass_object = kernel::single("cardcoupons_mdl_cards_pass");
        $cards_data = $cards_object->dump(array("goods_id"=>$card_info['goods_id']),"card_id,source");
        if(empty($cards_data)){
            return false;
        }
        if($cards_data['source'] == "internal" && $card_info['goods_card'] != 0){
            $cards_pass_data = $cards_pass_object->getList("*",array("card_id"=>$cards_data['card_id'],"status"=>'0','ex_status'=>'true','source'=>'internal','disable'=>'false','type'=>'entity'),0,$card_info['goods_card'],'createtime asc');
            if(count($cards_pass_data) != $card_info['goods_card']){
                return false;
            }
        }
        if($cards_data['source'] == "external" && $card_info['goods_card'] != 0){
            $cards_pass_data = $cards_pass_object->getList("*",array("card_id"=>$cards_data['card_id'],"status"=>'0','ex_status'=>'true','source'=>'external','disable'=>'false','type'=>'entity'),0,$card_info['goods_card'],'createtime asc');
            if(count($cards_pass_data) != $card_info['goods_card']){
                return false;
            }
        }
        if($cards_data['source'] == "external" && $card_info['virtual_card'] != 0){
            $cards_pass_data = $cards_pass_object->getList("*",array("card_id"=>$cards_data['card_id'],"status"=>'0','ex_status'=>'true','source'=>'external','disable'=>'false','type'=>'virtual'),0,$card_info['virtual_card'],'createtime asc');
            if(count($cards_pass_data) != $card_info['virtual_card']){
                return false;
            }
        }
        return true;
    }

}