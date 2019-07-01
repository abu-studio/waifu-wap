<?php
/**
 * ShopEx licence
 *
 * @copyright  Copyright (c) 2005-2013 ShopEx Technologies Inc. (http://www.shopex.cn)
 * @license  http://ecos.shopex.cn/ ShopEx License
 */

class b2c_ctl_wap_member extends wap_frontpage{

    function __construct(&$app){
        parent::__construct($app);
        $shopname = app::get('wap')->getConf('wap.name');
        if(isset($shopname)){
            $this->title = app::get('b2c')->_('会员中心').'_'.$shopname;
            $this->keywords = app::get('b2c')->_('会员中心_').'_'.$shopname;
            $this->description = app::get('b2c')->_('会员中心_').'_'.$shopname;
        }
        $this->header .= '<meta name="robots" content="noindex,noarchive,nofollow" />';
        $this->_response->set_header('Cache-Control', 'no-store');
        $this->verify_member();
        $this->pagesize = 10;
        $this->action = $this->_request->get_act_name();
        if(!$this->action) $this->action = 'index';
        $this->action_view = $this->action.".html";
        $this->member = $this->get_current_member();
        /** end **/
    }

    /*
     *本控制器公共分页函数
     * */
    function pagination($current,$totalPage,$act,$arg='',$app_id='b2c',$ctl='wap_member'){ //本控制器公共分页函数
        if (!$arg){
            $this->pagedata['pager'] = array(
                'current'=>$current,
                'total'=>$totalPage,
                'link' =>$this->gen_url(array('app'=>$app_id, 'ctl'=>$ctl,'act'=>$act,'args'=>array(($tmp = time())))),
                'token'=>$tmp,
                );
        }else{
            $arg = array_merge($arg, array(($tmp = time())));
            $this->pagedata['pager'] = array(
                'current'=>$current,
                'total'=>$totalPage,
                'link' =>$this->gen_url(array('app'=>$app_id, 'ctl'=>$ctl,'act'=>$act,'args'=>$arg)),
                'token'=>$tmp,
                );
        }
    }

    function get_start($nPage,$count){
        $maxPage = ceil($count / $this->pagesize);
        if($nPage > $maxPage) $nPage = $maxPage;
        $start = ($nPage-1) * $this->pagesize;
        $start = $start<0 ? 0 : $start;
        $aPage['start'] = $start;
        $aPage['maxPage'] = $maxPage;
        return $aPage;
    }

    public function index()
    {
        //判断商社号是否在列表中
        $acount_object = app::get('pam')->model('account');
        $account_data = $acount_object->getRow('company_no',array('account_id'=>$this->member['member_id']));
        $companys = app::get('site')->getConf('sand.company') ? app::get('site')->getConf('sand.company'): array();
        if(in_array(strtoupper($account_data['company_no']),$companys))
        {
            $this->pagedata['sandstatus'] = true;
        }else{
            $this->pagedata['sandstatus'] = false;
        }
        //新增福员外订单的菜单
        $mdl_member_fyw = app::get('b2c')->model('member_fyw');
        $fywRecord = $mdl_member_fyw->getRow('*',array('member_id'=>$this->member['member_id']));
        if(empty($fywRecord))
        {
            $this->pagedata['hasfyw'] = false;
        }else{
            $this->pagedata['hasfyw'] = true;
        }
        //进入页面是需要调用订单操作脚本
        //面包屑
        $this->path[] = array('title'=>app::get('b2c')->_('会员中心'),'link'=>$this->gen_url(array('app'=>'b2c', 'ctl'=>'wap_member', 'act'=>'index','full'=>1)));
        $GLOBALS['runtime']['path'] = $this->path;
        $oMem = &$this->app->model('members');
        $oRder = &$this->app->model('orders');
        $oMem_lv = $this->app->model('member_lv');
        $this->pagedata['switch_lv'] = $oMem_lv->get_member_lv_switch($this->member['member_lv']);
        //获取所有的订单信息
        $orders = $oRder->getList('*',array('member_id' => $this->member['member_id']));
        $order_total = count($orders);
        $aInfo = $oMem->dump($this->member['member_id']);
        $oGoods = $this->app->model("goods");
        $count_tmp = $oRder->get_search_order_main_ids($this->member['member_id']);
        //会员主页接口
        $MemberInfoArr = SFSC_HttpClient::doMemberMain($this->member['uname']);

        $oMsg = kernel::single('b2c_message_msg');
        $no_read = $oMsg->getList('*',array('to_id' => $this->member['member_id'],'has_sent' => 'true','for_comment_id' => 'all','inbox' => 'true','mem_read_status' => 'false'));
        $no_read = count($no_read);
        $this->pagedata['member_name'] = $MemberInfoArr['RESULT_DATA']['NAME'] ? $MemberInfoArr['RESULT_DATA']['NAME'] : $this->member['uname']; //会员名称
        //存入session 会员名称
        if(!empty($MemberInfoArr['RESULT_DATA']['CUSTOMER_EXT_LIST']))
        {
            foreach($MemberInfoArr['RESULT_DATA']['CUSTOMER_EXT_LIST'] as $k=>$v)
            {
                if($v['BIZ_ID'] == "customer.name")
                {
                    $this->pagedata['company_name'] = $v['VALUE'] ? $v['VALUE'] : "";
                }
                if($v['BIZ_ID'] == "banner.url")
                {
                    $_SESSION['sfsc']['banner_url'] = $v['VALUE'] ? kernel::base_url().'/themes/simple/images/'.$v['VALUE'] : kernel::base_url()."/themes/simple/images/grzx0601.png";
                }
                if($v['BIZ_ID'] == "log.url")
                {
                    $_SESSION['sfsc']['log_url'] = $v['VALUE'] ? kernel::base_url().'/themes/simple/images/'.$v['VALUE'] : kernel::base_url()."/themes/simple/images/yoofuu_default_logo.png";
                }
            }
        }else{
            $_SESSION['sfsc']['banner_url'] =  kernel::base_url()."/themes/simple/images/grzx0601.png";
            $_SESSION['sfsc']['log_url'] = kernel::base_url()."/themes/simple/images/yoofuu_default_logo.png";
        }

        $this->pagedata['company_name'] = $this->pagedata['company_name'] ? $this->pagedata['company_name'] : $MemberInfoArr['RESULT_DATA']['COMPANY_NAME'];        //公称名称
        $this->pagedata['top_pic'] = $MemberInfoArr['COMPANY_NAME']['TOP_PITURE_NAME'];
        $this->pagedata['bottom_pic'] = $MemberInfoArr['RESULT_DATA']['BOTTOM_PITURE_NAME'];
        $this->pagedata['headerdata']['my_fd'] = $MemberInfoArr['RESULT_DATA']['SUM'] ?$MemberInfoArr['RESULT_DATA']['SUM']:0;   //我的福点
        $this->pagedata['headerdata']['message'] = $no_read;
        $this->pagedata['headerdata']['my_jf'] = 0;                   //我的积分
        $this->pagedata['headerdata']['my_dd'] = $count_tmp ?$count_tmp :0;   //我的定单
        $this->pagedata['uname_en'] = $_SESSION['sfsc']['NAME_EN'];  //java端获取的en名称
        //获取频道信息 CHANNEL_LIST
        //1 商城  2 团购 3 便生活 4卡劵 5 体检 6 理财  8.京东频道
/*      $channel_list_array = array(
            array(
                'channel_id'=>'1',
                'app'=>'site',
                'ctl'=>'default',
                'act'=>'index',
                'pic'=>'grzx0601_14.png',
                'describe'=>'购物配送一站式'
            ),
            array(
                'channel_id'=>'2',
                'app'=>'groupbuy',
                'ctl'=>'site_grouplist',
                'act'=>'index',
                'pic'=>'grzx0601_26.png',
                'describe'=>'惊爆单品 诚意推荐'
            ),
            array(
                'channel_id'=>'3',
                'app'=>'b2c',
                'ctl'=>'site_lifecost',
                'act'=>'index',
                'pic'=>'grzx0601_16.png',
                'describe'=>'生活缴费一站式'
            ),
            array(
                'channel_id'=>'4',
                'app'=>'cardcoupons',
                'ctl'=>'site_card_channel',
                'act'=>'index',
                'pic'=>'grzx0601_24.png',
                'describe'=>'节日礼包 商务馈赠'
            ),
            array(
                'channel_id'=>'5',
                'app'=>'physical',
                'ctl'=>'site_index',
                'act'=>'index',
                'pic'=>'grzx0601_27.png',
                'describe'=>'关爱您和家人健康'
            ),
            array(
                'channel_id'=>'6',
                'app'=>'b2c',
                'ctl'=>'site_member',
                'act'=>'index',
                'pic'=>'grzx0601_25.png',
                'describe'=>'一站式购物'
            ),
        array(
                'channel_id'=>'7',
                'app'=>'b2c',
                'ctl'=>'site_product',
                'act'=>'Japan',
                'pic'=>'grzx0601_28.png',
                'describe'=>'日本馆'
            ),
        array(
                'channel_id'=>'8',
                'app'=>'jdsale',
                'ctl'=>'site_gallery',
                'act'=>'index',
                'pic'=>'grzx0601_28.png',
                'describe'=>'京东特卖'
            ),
        );
*/
        $this->pagedata['channellist'] = $MemberInfoArr['RESULT_DATA']['CHANNEL_LIST'];
        $this->pagedata['channellist_count'] = count($this->pagedata['channellist']);
        #获取默认的货币
        $obj_currency = app::get('ectools')->model('currency');
        $arr_def_cur = $obj_currency->getDefault();
        $this->pagedata['def_cur_sign'] = $arr_def_cur['cur_sign'];
        #获取咨询评论回复
        $obj_mem_msg = kernel::single('b2c_message_disask');
        $this->member['unreadmsg'] = $obj_mem_msg->calc_unread_disask($this->member['member_id']);
        //额外的会员的信息 - 冻结积分、将要获得的积分
        $obj_extend_point = kernel::servicelist('b2c.member_extend_point_info');
        if ($obj_extend_point)
        {
            foreach ($obj_extend_point as $obj)
            {
                $this->pagedata['extend_point_html'] = $obj->gen_extend_point($this->member['member_id']);
            }
        }
        //获取java礼包信息----start
        $card_pass_model = app::get('cardcoupons')->model("cards_pass");
        $tmp2_libao = SFSC_HttpClient::getJavaLibao($this->member['uname']);
        if(!empty($tmp2_libao))
        {
            foreach($tmp2_libao['RESULT_DATA'] as $libao_k=>$libao_v){
                if(!empty($libao_v['CARD_NUMBER']))
                {
                    $libao_time = $card_pass_model->getlist("from_time,to_time",array("card_no"=>$libao_v['CARD_NUMBER']));
                    if(!empty($libao_time))
                    {
                        $tmp2_libao['RESULT_DATA'][$libao_k]['OVERDUE_TIME1'] = date("Y/m/d",$libao_time[0]['from_time'])."-".date("Y/m/d",$libao_time[0]['to_time']);
                    }
                }
            }
        }
        $this->pagedata['member_java_libao'] = $tmp2_libao;
        //获取java礼包信息----end
        // 判断是否开启预存款
        $_mdl_payment_cfgs = app::get('ectools')->model('payment_cfgs');
        $_payment_info = $_mdl_payment_cfgs->getPaymentInfo('deposit');
        if($_payment_info['app_staus'] == app::get('ectools')->_('开启'))
        {
            $this->pagedata['deposit_status'] = 'true';
        }
        $this->pagedata['member'] = $this->member;
        $this->pagedata['total_order'] = $order_total;
        $this->pagedata['aNum']=$aInfo['advance']['total'];
        $this->set_tmpl('member');
        $obj_member = &$this->app->model('member_goods');
        $aData_fav = $obj_member->get_favorite($this->app->member_id,$this->member['member_lv']);
        $this->pagedata['favorite'] = $aData_fav['data'];
        $this->pagedata['fav_num'] = count($aData_fav['data']);
        //默认图片
        $imageDefault = app::get('image')->getConf('image.set');
        $this->pagedata['defaultImage'] = $imageDefault['S']['default_image'];
        $rule = kernel::single('b2c_member_solution');
        $this->pagedata['wel'] = $rule->get_all_to_array($this->member['member_lv']);
        $this->pagedata['res_url'] = $this->app->res_url;
        //优惠券的数量
        $oCoupon = kernel::single('b2c_coupon_mem');
        $aCoupon = $oCoupon->get_list_m($this->member['member_id']);
        $this->pagedata['coupon_num'] = count($aCoupon);
        //待评价的商品
        $oRder_items = &$this->app->model('order_items');
        $omember_comments = &$this->app->model('member_comments');
        $evaluate_filter['member_id'] = $this->member['member_id'];
        $evaluate_filter['status'] = 'finish';
        $evaluate_filter['comments_count'] = 0;
        $orders = $oRder->getList('order_id,createtime,comments_count',$evaluate_filter);
        $day_1 = app::get('b2c')->getConf('site.comment_original_time');
        $day_2 = app::get('b2c')->getConf('site.comment_additional_time');
        $day_1 = intval($day_1)?intval($day_1):30;
        $day_2 = intval($day_2)?intval($day_2):90;
        $order_ids = array();
        foreach($orders as &$v)
        {
            if(intval($v['comments_count']) > 1 || intval($v['createtime']) < strtotime("-{$day_2} day")) continue;
            if(intval($v['comments_count']) == 0 && intval($v['createtime']) < strtotime("-{$day_1} day")) continue;
            if(intval($v['comments_count']) == 0 && intval($v['createtime']) >= strtotime("-{$day_1} day")){
                $order_ids[] = $v['order_id'];
            }
        }
        $goods = array();
        if(!empty($order_ids))
        {
            $goods = $oRder_items->getList('order_id,goods_id,name',array('order_id|in'=>$order_ids));
        }
        foreach($goods as $k=>&$v)
        {
            $orders1 = $oRder->getList('createtime',array('order_id'=>$v['order_id']));
            $ogoods1 = $oGoods->getList('image_default_id,comments_count',array('goods_id'=>$v['goods_id']));
            $v['createtime'] = $orders1[0]['createtime'];
            $v['image_default_id'] = $ogoods1[0]['image_default_id'];
            $v['comments_count'] = $ogoods1[0]['comments_count'];
            $is_comment= $omember_comments->getList('comment_id',array('type_id'=>$v['goods_id'],'order_id'=>$v['order_id']));
            if(count($is_comment)>0){
                $v['is_comment'] = true;
            }else{
                $v['is_comment'] = false;
            }
        }
        $this->pagedata['discuss_num'] = count($order_ids);
        $this->pagedata['good'] = $goods;

        //已买到的宝贝
        $buy_filter['member_id'] = $this->member['member_id'];
        $buy_filter['status|noequal'] = 'dead';
        $buy_filter['comments_count'] = 0;
        $buy_orders = $oRder->getList('*',$buy_filter);
        foreach($buy_orders as &$v)
        {
            $buy_order_ids[] = $v['order_id'];
        }
        $buy_good = $oRder_items->getList('order_id,goods_id',array('order_id|in'=>$buy_order_ids),0,-1,'item_id desc');
        foreach($buy_good as $k=>&$v)
        {
            $orders2 = $oRder->getList('ship_status,status,confirm,pay_status,comments_count,createtime',array('order_id'=>$v['order_id']));
            $ogoods2 = $oGoods->getList('image_default_id',array('goods_id'=>$v['goods_id']));
            $v['ship_status'] = $orders2[0]['ship_status'];
            $v['status'] = $orders2[0]['status'];
            $v['pay_status'] = $orders2[0]['pay_status'];

            if(intval($orders2[0]['comments_count']) > 1 || intval($orders2[0]['createtime']) < strtotime("-{$day_2} day")){
                $v['comments_count'] = -1;
            }else if(intval($orders2[0]['comments_count']) == 0 && intval($orders2[0]['createtime']) < strtotime("-{$day_1} day")){
                $v['comments_count'] = -1;
            }else{
                $v['comments_count'] = $orders2[0]['comments_count'];
            }
            $v['confirm'] = $orders2[0]['confirm'];
            $v['image_default_id'] = $ogoods2[0]['image_default_id'];
        }
        $this->pagedata['buy_good'] = $buy_good;
        //待确认订单的数目
        $confirm_filter['member_id'] = $this->member['member_id'];
        $confirm_filter['confirm'] = 'N';
        $confirm_filter['pay_status'] = '1';
        $confirm_filter['ship_status'] = '1';
        $confirm_filter['status'] = 'active';
        $confirm_orders = $oRder->getList('order_id',$confirm_filter);
        $this->pagedata['confirm_num'] = count($confirm_orders);
        //降价商品的数量
        $Mgoods = $this->app->model('member_goods');
        $goods_price = $Mgoods->getList('gnotify_id',array('type'=>'fav','is_change'=>'down','member_id'=>$this->app->member_id));
        $this->pagedata['goods_price_down_num'] = count($goods_price);
        //促销商品
        $pmt_goods = $Mgoods->getList('goods_id',array('type'=>'fav','member_id'=>$this->app->member_id));
        foreach($pmt_goods as $v)
        {
            $p_goods[] = $v['goods_id'];
        }
        $pmt_good = $oGoods->getList('goods_id',array('act_type|noequal'=>'normal','goods_id|in'=>$p_goods));
        $this->pagedata['pmt_good_num'] = count($pmt_good);
        //判断手机，邮箱，支付密码
        $is_pass = $oMem->getList('mobile,email',array('member_id'=>$this->member['member_id']));
        $is_pass_num = 0;
        if($is_pass[0]['mobile']){
            $is_pass_num++;
            $is_mobile = 1;
        }else{
            $is_mobile = 0;
        }
        if($is_pass[0]['email']){
            $is_pass_num++;
            $is_email = 1;
        }else{
            $is_email = 0;
        }
        //获取提醒信息
        $mem_msg = $this->app->model('member_comments');
        $sql = " SELECT * FROM `sdb_b2c_member_comments` WHERE  `to_id`='".$this->member['member_id']."' AND `for_comment_id`='0' AND `object_type`='msg'  AND `has_sent`='true' AND `inbox`='true' AND `mem_read_status`='false' AND `display`='true'";
        $msg_arr =  $mem_msg->db->select($sql);
        $remind_info_count = count($msg_arr);
        $this->pagedata['remind_info_count'] = $remind_info_count;

        $this->pagedata['is_mobile'] = $is_mobile;
        $this->pagedata['is_email'] = $is_email;
        $this->pagedata['is_pass_num']= $is_pass_num;

        $site_get_policy_method = $this->app->getConf('site.get_policy.method');
        $this->pagedata['site_point_usage'] = $site_get_policy_method != '1' ? 'true' : 'false';
        //获取用户头像信息
        $member_object = kernel::single("b2c_mdl_members");
        $this->pagedata['Head_portrait'] = $aInfo['Head_portrait'];
        $member_data = $member_object->dump(array('member_id'=>$this->member['member_id']),"*");
        if($this->member['member_lv'] == 1){
            $this->pagedata['member_lv_pic'] = kernel::base_url().'/themes/simple/images/member_lv01.png';
        }elseif($this->member['member_lv'] == 2){
            $this->pagedata['member_lv_pic'] = kernel::base_url().'/themes/simple/images/member_lv02.png';
        }elseif($this->member['member_lv'] == 3){
            $this->pagedata['member_lv_pic'] = kernel::base_url().'/themes/simple/images/member_lv03.png';
        }else{
            $this->pagedata['member_lv_pic'] = kernel::base_url().'/themes/simple/images/member_lv01.png';
        }
        $this->pagedata['productcount'] = $MemberInfoArr['RESULT_DATA']['PRODUCT_COUNT'] ? $MemberInfoArr['RESULT_DATA']['PRODUCT_COUNT'] : 0;
        $this->pagedata['levelname'] = $this->member['levelname'] ? $this->member['levelname'] : '普通会员';
        if($this->pagedata['levelname']){
            $system_array = array("普通会员"=>"Regular Member","黄金会员"=>"Gold Member","钻石会员"=>"Diamond Member");
            foreach($system_array as $k_name=>$v_name){
                if($k_name == $this->pagedata['levelname']){
                    $this->pagedata['levelname_en'] = $v_name;
                }
            }
        }
        $this->pagedata['member_data'] = $member_data['member_avatar'] ? kernel::base_url().'/public/memberavatar/'.$member_data['member_avatar'] : kernel::base_url().'/themes/simple/images/grzx0601_01.png';
        $this->pagedata['current_url'] =  app::get('business')->res_url;
        $this->page('wap/member/index.html');
    }




    /*
     *会员中心首页交易提醒 (未付款订单,到货通知，未读的评论咨询回复)
     * */
    private function msgAlert(){
        //获取待付款订单数
        $oRder = $this->app->model('orders');//--11sql
        $un_pay_orders = $oRder->count(array('member_id' => $this->member['member_id'],'pay_status' => 0,'status'=>'active'));
        $member['un_pay_orders'] = $un_pay_orders;
         //获取预售订单数
        $prepare_pay_orders = $oRder->count(array('member_id' => $this->member['member_id'],'promotion_type' => 'prepare'));
        $member['prepare_pay_orders'] = $prepare_pay_orders;
        //到货通知
        $member_goods = $this->app->model('member_goods');
        $member['sto_goods_num'] = $member_goods->get_goods($this->app->member_id);

        //评论咨询回复
        $mem_msg = $this->app->model('member_comments');
        $object_type = array('discuss','ask');
        $aData = $mem_msg->getList('*',array('to_id' => $this->app->member_id,'object_type'=> $object_type,'mem_read_status' => 'false','display' => 'true'));
        $un_readAskMsg = 0;
        $un_readDiscussMsg = 0;
        foreach($aData as $val){
            if($val['object_type'] == 'ask'){
                $un_readAskMsg += 1;
            }else{
                $un_readDiscussMsg += 1;
            }
        }
        $member['un_readAskMsg'] = $un_readAskMsg;
        $member['un_readDiscussMsg'] = $un_readDiscussMsg;
        return $member;
    }

    //积分历史
    function point_history($nPage=1){
        $this->path[] = array('title'=>app::get('b2c')->_('会员中心'),'link'=>$this->gen_url(array('app'=>'b2c', 'ctl'=>'wap_member', 'act'=>'index','full'=>1)));
        $this->path[] = array('title'=>app::get('b2c')->_('积分历史'),'link'=>'#');
        $GLOBALS['runtime']['path'] = $this->path;
        $member = app::get('pointprofessional')->model('members');
        $member_point = app::get('pointprofessional')->model('member_point');
        $obj_gift_link = kernel::service('b2c.exchange_gift');
        if ($obj_gift_link)
        {
            $this->pagedata['exchange_gift_link'] = $obj_gift_link->gen_exchange_link();
        }
        // 额外的会员的信息 - 冻结积分、将要获得的积分
        $obj_extend_point = kernel::servicelist('b2c.member_extend_point_info');
        if ($obj_extend_point)
        {
            foreach ($obj_extend_point as $obj)
            {
                $this->pagedata['extend_point_html'] = $obj->gen_extend_detail_point($this->app->member_id);
            }
        }
        $nodes_obj = $this->app->model('shop');
        $nodes = $nodes_obj->count( array('node_type'=>'ecos.taocrm','status'=>'bind'));

        if($nodes > 0){
            $getlog_params = array('member_id'=>$this->app->member_id,'page'=>$nPage,'page_size'=>$this->pagesize);
            $pointlog = kernel::single('b2c_member_point_contact_crm')->getPointLog($getlog_params);

            $count = $pointlog['total'];
            $aPage = $this->get_start($nPage,$count);
            $this->pagedata['total'] = $member->get_real_point($this->app->member_id,'1');
            $this->pagedata['historys'] = $pointlog['historys'];
        }else{
            $count = $member_point->count(array('member_id'=>$this->app->member_id));
            $aPage = $this->get_start($nPage,$count);
            $params['data'] = $member_point->get_all_list('*',array('member_id' => $this->app->member_id,'status'=>'false'),$aPage['start'],$this->pagesize);
            $this->pagedata['total'] = $member->get_real_point($this->app->member_id,'1');
            $this->pagedata['historys'] = $params['data'];
        }
        $params['page'] = $aPage['maxPage'];
        $this->pagination($nPage,$params['page'],'point_history');
        $this->page('wap/member/point_history.html');
    }


    /**
     * @Author: panbiao <panbiaophp@163.com>
     * @DateTime: 2019-07-01 15:09
     * @Desc: 订单列表
     */
    public function orders($type='',$order_id='',$goods_name='',$goods_bn='',$time='',$pay_status='',$nPage=1)
    {
        //进入页面是需要调用订单操作脚本
        $obj_filter = kernel::single('b2c_site_filter');
        $type = mysql_real_escape_string($obj_filter->check_input($type));
        $order_id = mysql_real_escape_string($obj_filter->check_input($order_id));
        $goods_name = mysql_real_escape_string($obj_filter->check_input($goods_name));
        $goods_bn = mysql_real_escape_string($obj_filter->check_input($goods_bn));
        $time = mysql_real_escape_string($obj_filter->check_input($time));
        $pay_status = mysql_real_escape_string($obj_filter->check_input($pay_status));
        $nPage = mysql_real_escape_string($obj_filter->check_input($nPage));

        $this->path[] = array('title'=>app::get('b2c')->_('会员中心'),'link'=>$this->gen_url(array('app'=>'b2c', 'ctl'=>'site_member', 'act'=>'index','full'=>1)));
        $this->path[] = array('title'=>app::get('b2c')->_('我的订单'),'link'=>'#');
        $GLOBALS['runtime']['path'] = $this->path;
        $order = &$this->app->model('orders');
        $order_id = trim($order_id);
        $goods_name = trim($goods_name);
        $goods_bn = trim($goods_bn);
        //给会员中心订单列表标签增加数量显示begin此种方法需要与数据库交互5次但是不需要循环
        /*$types=array('','nopayed','ship','shiped','finish','dead');
        $type_orders_count=array();
        foreach($types as $type_key=>$type_value){
            if($type!=$type_value){
                $sql = $this->get_search_order_ids($type_value,$time);
                $arrayorser = $order->db->select($sql);
                if($type_value==''){
                    $type_orders_count['all']=count($arrayorser);
                }else{
                    $type_orders_count[$type_value]=count($arrayorser);
                }

            }
        }*/
        /**
         *以下方法只需与数据库交互1次，但是需要循环所有该会员下的订单
         **/
        $member_orders_all=$order->db->select("select pay_status,status,ship_status,confirm,comments_count from ".kernel::database()->prefix ."b2c_orders where member_id='{$this->member['member_id']}' and order_type <> 'sand'");
        $type_orders_count=array ('all' =>0,'shiped' =>0,'dead' =>0,'ship' =>0,'comment' =>0,'finish' =>0,'nopayed'=>0,'confirm'=>0);
        $type_orders_count['all']=count($member_orders_all);
        foreach($member_orders_all as $moa_key=>$moa_value)
        {
            if($moa_value['pay_status']==0 &&$moa_value['status']=='active')
            {
                $type_orders_count['nopayed']=$type_orders_count['nopayed']+1;//待付款
            }else if($moa_value['pay_status']==1 &&$moa_value['ship_status']==0&&$moa_value['status']=='active')
            {
                $type_orders_count['ship']=$type_orders_count['ship']+1;//待发货
            }else if($moa_value['pay_status']==1 &&$moa_value['ship_status']==1&&$moa_value['status']=='active')
            {
                $type_orders_count['shiped']=$type_orders_count['shiped']+1;//待收货
            }else if($moa_value['status']=='finish')
            {
                if($moa_value['comments_count']==0){
                    $type_orders_count['comment']=$type_orders_count['comment']+1;//未评论
                }
                $type_orders_count['finish']=$type_orders_count['finish']+1;//已完成
            }else if($moa_value['pay_status']==1 &&$moa_value['ship_status']==1&&$moa_value['status']=='active' &&$moa_value['confirm']=='N' )
            {
                $type_orders_count['confirm']=$type_orders_count['confirm']+1;//待确认
            }else if($moa_value['status']=='dead'){
                $type_orders_count['dead']=$type_orders_count['dead']+1;//作废
            }
        }
        $sql = $order->get_search_order_ids($type,$time,$this->member['member_id']);
        $arrayorser = $order->db->select($sql);
        if($type==''){
            $type_orders_count['all']=count($arrayorser);
        }else{
            $type_orders_count[$type]=count($arrayorser);
        }
        //给会员中心订单列表标签增加数量显示end
        $search_order=$order->search_order($order_id,$goods_name,$goods_bn,$this->member['member_id']);
        foreach($arrayorser as $key=>$value){
            foreach($search_order as $k=>$v){
                if($value['order_id']==$v['order_id']){
                    $arr[]=$value;
                }
            }
        }
        $arrayorser=$arr;
        if(empty($arrayorser)){
            $msg='没有找到相应的订单！';
        }else{
            $aData = $order->fetchByMember($this->member['member_id'],$nPage-1,'','',$arrayorser);
        }
        $this->get_order_details($aData,'member_orders');
        $oImage = app::get('image')->model('image');
        $imageDefault = app::get('image')->getConf('image.set');
        $applySObj = app::get('spike')->model('spikeapply');
        $applyGObj = app::get('groupbuy')->model('groupapply');
        $applyScoreObj = app::get('scorebuy')->model('scoreapply');

        foreach($aData['data'] as $k=>$v)
        {
            //获取订单支付时间
            $obj_payment = app::get('ectools')->model('refunds');
            $payment_id = $obj_payment->get_payment($v['order_id']);
            $pay_time = app::get('ectools')->model('payments')->getRow('t_payed',array('payment_id'=>$payment_id['bill_id']));
            $aData['data'][$k]['pay_time'] = $pay_time['t_payed'];
            $obj_aftersales = app::get('aftersales')->model('return_product');
            $ord_id = $obj_aftersales->getRow('return_id',array('order_id'=>$v['order_id'],'status'=>'3','refund_type'=>'2'));
            if($ord_id){
                $aData['data'][$k]['need_send'] = 1;
            }else{
                $aData['data'][$k]['need_send'] = 0;
            }
            $ord_id = $obj_aftersales->getRow('return_id',array('order_id'=>$v['order_id'],'status'=>'11','refund_type'=>'2'));
            if($ord_id){
                $aData['data'][$k]['need_edit'] = 1;
            }else{
                $aData['data'][$k]['need_edit'] = 0;
            }
            //end
            foreach($v['goods_items'] as $k2=>$v2)
            {
                if( !$v2['product']['thumbnail_pic'] && !$oImage->getList("image_id",array('image_id'=>$v['image_default_id']))){
                    $aData['data'][$k]['goods_items'][$k2]['product']['thumbnail_pic'] = $imageDefault['S']['default_image'];
                }
                $act_id = '';
                //秒杀详细页参数
                switch($v['order_type'])
                {
                    case 'spike':
                        $act_id = $applySObj->getOnActIdByGoodsId($v2['product']['goods_id']);
                        break;
                    case 'group':
                        $act_id = $applyGObj->getOnActIdByGoodsId($v2['product']['goods_id']);
                        break;
                    case 'score':
                        $act_id = $applyScoreObj->getOnActIdByGoodsId($v2['product']['goods_id']);
                        break;
                    case 'normal':
                        break;
                }
                if($act_id)
                {
                    $aData['data'][$k]['goods_items'][$k2]['product']['args'] = array($v2['product']['goods_id'],'','',$act_id);
                }
            }

            //获取买家/卖家
            $obj_members = app::get('pam')->model('account');
            $buy_name = $obj_members->getRow('login_name',array('account'=>$v['member_id']));
            $aData['data'][$k]['buy_name'] = $buy_name['login_name'];

            $obj_strman = app::get('business')->model('storemanger');
            $seller_id = $obj_strman->getRow('account_id,store_idcardname',array('store_id'=>$v['store_id']));
            $seller_name = $obj_members->getRow('login_name',array('account_id'=>$seller_id['account_id']));
            $aData['data'][$k]['seller_name'] = $seller_name['login_name'];
            $aData['data'][$k]['seller_real_name'] = $seller_id['store_idcardname'];
        }

        //添加订单html埋点
        foreach($aData['data'] as $k=>$v)
        {
            foreach(kernel::servicelist('business.member_orders') as $service){
                if(is_object($service)){
                    if(method_exists($service,'get_orders_html')){
                        $aData['data'][$k]['html'] .= $service->get_orders_html($v);
                    }
                }
            }
            if($aData['data'][$k]['order_kind']=='b2c_card'&&$aData['data'][$k]['pay_status']=='1'&&$aData['data'][$k]['ship_status']=='1'){
                //短信重发功能
                $url = $this->gen_url(array('app' => 'b2c', 'ctl' => 'wap_member', 'act' => 'send_message', 'arg0' => $aData['data'][$k]['order_id']));
                $aData['data'][$k]['html'] = $aData['data'][$k]['html']."
                <a href=".$url." class='font-blue operate-btn'>重发</a>";
            }
        }
        $this->pagedata['type_orders_count']=$type_orders_count;
        $this->pagedata['msg']=$msg;
        $this->pagedata['orders'] = $aData['data'];
        $this->pagedata['orders_nums'] = count($aData['data']);

        //下拉框数据 --start
        $this->pagedata['select']['time']['options'] = $this->get_select_date();
        $this->pagedata['select']['time']['value'] = $time;
        //下拉框数据 --end

        //获取传过来的参数
        $this->pagedata['type'] =$type;
        $this->pagedata['order_id'] = $order_id;
        $this->pagedata['goods_name'] = $goods_name;
        $this->pagedata['goods_bn'] = $goods_bn;
        $this->pagedata['time'] = $time;

        //修改分页链接参数 --start

        $arr_args = array($type,$order_id,$goods_name,$goods_bn,$time,$pay_status);

        //--end
        $this->pagination($nPage,$aData['pager']['total'],'orders',$arr_args);
        $this->pagedata['res_url'] = $this->app->res_url;

        $this->page('wap/member/orders.html');
    }

    /**
     * 动态获取选择的时间
     * @return array
     */
    private function get_select_date(){

        $year = date('Y',time());
        $options = array();

        $options['all'] = "全部时间";
        $options['3th'] = "三个月内";
        $options['6th'] = "半年内";
        $options[$year] = "今年内";
        $options['1'] = "1年以前";

        return $options;
    }

    /**
     * 得到订单列表详细
     * @param array 订单详细信息
     * @param string tpl
     * @return null
     */
    protected function get_order_details(&$aData,$tml='member_orders')
    {
        if (isset($aData['data']) && $aData['data'])
        {
            $objMath = kernel::single('ectools_math');
            // 所有的goods type 处理的服务的初始化.
            $arr_service_goods_type_obj = array();
            $arr_service_goods_type = kernel::servicelist('order_goodstype_operation');
            foreach ($arr_service_goods_type as $obj_service_goods_type)
            {
                $goods_types = $obj_service_goods_type->get_goods_type();
                $arr_service_goods_type_obj[$goods_types] = $obj_service_goods_type;
            }

            foreach ($aData['data'] as &$arr_data_item)
            {
                $this->get_order_detail_item($arr_data_item,$tml);
            }
        }
    }

    /**
     * 得到订单列表详细
     * @param array 订单详细信息
     * @param string 模版名称
     * @return null
     */
    protected function get_order_detail_item(&$arr_data_item,$tpl='member_order_detail')
    {
        if (isset($arr_data_item) && $arr_data_item)
        {
            $objMath = kernel::single('ectools_math');
            // 所有的goods type 处理的服务的初始化.
            $arr_service_goods_type_obj = array();
            $arr_service_goods_type = kernel::servicelist('order_goodstype_operation');
            foreach ($arr_service_goods_type as $obj_service_goods_type)
            {
                $goods_types = $obj_service_goods_type->get_goods_type();
                $arr_service_goods_type_obj[$goods_types] = $obj_service_goods_type;
            }


            $arr_data_item['goods_items'] = array();
            $obj_specification = $this->app->model('specification');
            $obj_spec_values = $this->app->model('spec_values');
            $obj_goods = $this->app->model('goods');
            $oImage = app::get('image')->model('image');
            if (isset($arr_data_item['order_objects']) && $arr_data_item['order_objects'])
            {
                foreach ($arr_data_item['order_objects'] as $k=>$arr_objects)
                {
                    $index = 0;
                    $index_adj = 0;
                    $index_gift = 0;
                    $image_set = app::get('image')->getConf('image.set');
                    if ($arr_objects['obj_type'] == 'goods')
                    {
                        foreach ($arr_objects['order_items'] as $arr_items)
                        {
                            if (!$arr_items['products'])
                            {
                                $o = $this->app->model('order_items');
                                $tmp = $o->getList('*', array('item_id'=>$arr_items['item_id']));
                                $arr_items['products']['product_id'] = $tmp[0]['product_id'];
                            }

                            if ($arr_items['item_type'] == 'product')
                            {
                                if ($arr_data_item['goods_items'][$k]['product'])
                                    $arr_data_item['goods_items'][$k]['product']['quantity'] = $objMath->number_plus(array($arr_items['quantity'], $arr_data_item['goods_items'][$k]['product']['quantity']));
                                else
                                    $arr_data_item['goods_items'][$k]['product']['quantity'] = $arr_items['quantity'];

                                $arr_data_item['goods_items'][$k]['product'] = $arr_items;
                                $arr_data_item['goods_items'][$k]['product']['name'] = $arr_items['name'];
                                $arr_data_item['goods_items'][$k]['product']['goods_id'] = $arr_items['goods_id'];
                                $arr_data_item['goods_items'][$k]['product']['price'] = $arr_items['price'];
                                $arr_data_item['goods_items'][$k]['product']['score'] = intval($arr_items['score']*$arr_data_item['goods_items'][$k]['product']['quantity']);
                                $arr_data_item['goods_items'][$k]['product']['amount'] = $arr_items['amount'];
                                $arr_goods_list = $obj_goods->getList('image_default_id,spec_desc', array('goods_id' => $arr_items['goods_id']));

                                $arr_goods = $arr_goods_list[0];
                                // 获取货品关联第一张图片
                                $select_spec_private_value_id = reset($arr_items['products']['spec_desc']['spec_private_value_id']);
                                $spec_desc_goods = reset($arr_goods['spec_desc']);
                                if($spec_desc_goods[$select_spec_private_value_id]['spec_goods_images']){
                                    list($default_product_image) = explode(',', $spec_desc_goods[$select_spec_private_value_id]['spec_goods_images']);
                                    $arr_goods['image_default_id'] = $default_product_image;
                                }else{
                                    if( !$arr_goods['image_default_id'] && !$oImage->getList("image_id",array('image_id'=>$arr_goods['image_default_id']))){
                                        $arr_goods['image_default_id'] = $image_set['S']['default_image'];
                                    }
                                }

                                $arr_data_item['goods_items'][$k]['product']['thumbnail_pic'] = $arr_goods['image_default_id'];
                                $arr_data_item['goods_items'][$k]['product']['link_url'] = $this->gen_url(array('app'=>'b2c','ctl'=>'wap_product','act'=>'index','arg0'=>$arr_items['products']['product_id']));
                                if ($arr_items['addon'])
                                {
                                    $arrAddon = $arr_addon = unserialize($arr_items['addon']);
                                    if ($arr_addon['product_attr'])
                                        unset($arr_addon['product_attr']);
                                    $arr_data_item['goods_items'][$k]['product']['minfo'] = $arr_addon;
                                }else{
                                    unset($arrAddon,$arr_addon);
                                }
                                if ($arrAddon['product_attr'])
                                {
                                    foreach ($arrAddon['product_attr'] as $arr_product_attr)
                                    {
                                        $arr_data_item['goods_items'][$k]['product']['attr'] .= $arr_product_attr['label'] . $this->app->_(":") . $arr_product_attr['value'] . $this->app->_(" ");
                                    }
                                }

                                if (isset($arr_data_item['goods_items'][$k]['product']['attr']) && $arr_data_item['goods_items'][$k]['product']['attr'])
                                {
                                    if (strpos($arr_data_item['goods_items'][$k]['product']['attr'], $this->app->_(" ")) !== false)
                                    {
                                        $arr_data_item['goods_items'][$k]['product']['attr'] = substr($arr_data_item['goods_items'][$k]['product']['attr'], 0, strrpos($arr_data_item['goods_items'][$k]['product']['attr'], $this->app->_(" ")));
                                    }
                                }
                            }
                            elseif ($arr_items['item_type'] == 'adjunct')
                            {
                                $str_service_goods_type_obj = $arr_service_goods_type_obj[$arr_items['item_type']];
                                $str_service_goods_type_obj->get_order_object(array('goods_id' => $arr_items['goods_id'], 'product_id'=>$arr_items['products']['product_id']), $arrGoods,$tpl);


                                if ($arr_data_item['goods_items'][$k][$arr_items['item_type'].'_items'][$index_adj])
                                    $arr_data_item['goods_items'][$k][$arr_items['item_type'].'_items'][$index_adj]['quantity'] = $objMath->number_plus(array($arr_items['quantity'], $arr_data_item['goods_items'][$k][$arr_items['item_type'].'_items'][$index_adj]['quantity']));
                                else
                                    $arr_data_item['goods_items'][$k][$arr_items['item_type'].'_items'][$index_adj]['quantity'] = $arr_items['quantity'];

                                if (!$arrGoods['image_default_id'])
                                {
                                    $arrGoods['image_default_id'] = $image_set['S']['default_image'];
                                }
                                $arr_data_item['goods_items'][$k][$arr_items['item_type'].'_items'][$index_adj] = $arr_items;
                                $arr_data_item['goods_items'][$k][$arr_items['item_type'].'_items'][$index_adj]['name'] = $arr_items['name'];
                                $arr_data_item['goods_items'][$k][$arr_items['item_type'].'_items'][$index_adj]['score'] = intval($arr_items['score']*$arr_data_item['goods_items'][$k][$arr_items['item_type'].'_items'][$index_adj]['quantity']);
                                $arr_data_item['goods_items'][$k][$arr_items['item_type'].'_items'][$index_adj]['goods_id'] = $arr_items['goods_id'];
                                $arr_data_item['goods_items'][$k][$arr_items['item_type'].'_items'][$index_adj]['price'] = $arr_items['price'];
                                $arr_data_item['goods_items'][$k][$arr_items['item_type'].'_items'][$index_adj]['thumbnail_pic'] = $arrGoods['image_default_id'];
                                $arr_data_item['goods_items'][$k][$arr_items['item_type'].'_items'][$index_adj]['link_url'] = $this->gen_url(array('app'=>'b2c','ctl'=>'wap_product','act'=>'index','arg0'=>$arr_items['products']['product_id']));
                                $arr_data_item['goods_items'][$k][$arr_items['item_type'].'_items'][$index_adj]['amount'] = $arr_items['amount'];

                                if ($arr_items['addon'])
                                {
                                    $arr_addon = unserialize($arr_items['addon']);

                                    if ($arr_addon['product_attr'])
                                    {
                                        foreach ($arr_addon['product_attr'] as $arr_product_attr)
                                        {
                                            $arr_data_item['goods_items'][$k][$arr_items['item_type'].'_items'][$index_adj]['attr'] .= $arr_product_attr['label'] . $this->app->_(":") . $arr_product_attr['value'] . $this->app->_(" ");
                                        }
                                    }
                                }

                                if (isset($arr_data_item['goods_items'][$k][$arr_items['item_type'].'_items'][$index_adj]['attr']) && $arr_data_item['goods_items'][$k][$arr_items['item_type'].'_items'][$index_adj]['attr'])
                                {
                                    if (strpos($arr_data_item['goods_items'][$k][$arr_items['item_type'].'_items'][$index_adj]['attr'], $this->app->_(" ")) !== false)
                                    {
                                        $arr_data_item['goods_items'][$k][$arr_items['item_type'].'_items'][$index_adj]['attr'] = substr($arr_data_item['goods_items'][$k][$arr_items['item_type'].'_items'][$index_adj]['attr'], 0, strrpos($arr_data_item['goods_items'][$k][$arr_items['item_type'].'_items'][$index_adj]['attr'], $this->app->_(" ")));
                                    }
                                }

                                $index_adj++;
                            }
                            else
                            {
                                // product gift.
                                if ($arr_service_goods_type_obj[$arr_objects['obj_type']])
                                {
                                    $str_service_goods_type_obj = $arr_service_goods_type_obj[$arr_items['item_type']];
                                    $str_service_goods_type_obj->get_order_object(array('goods_id' => $arr_items['goods_id'], 'product_id'=>$arr_items['products']['product_id']), $arrGoods,$tpl);

                                    if ($arr_data_item['goods_items'][$k][$arr_items['item_type'].'_items'][$index_gift])
                                        $arr_data_item['goods_items'][$k][$arr_items['item_type'].'_items'][$index_gift]['quantity'] = $objMath->number_plus(array($arr_items['quantity'], $arr_data_item['goods_items'][$k][$arr_items['item_type'].'_items'][$arr_items['products']['product_id']]['quantity']));
                                    else
                                        $arr_data_item['goods_items'][$k][$arr_items['item_type'].'_items'][$index_gift]['quantity'] = $arr_items['quantity'];

                                    if (!$arrGoods['image_default_id'])
                                    {
                                        $arrGoods['image_default_id'] = $image_set['S']['default_image'];
                                    }
                                    $arr_data_item['goods_items'][$k][$arr_items['item_type'].'_items'][$index_gift] = $arr_items;
                                    $arr_data_item['goods_items'][$k][$arr_items['item_type'].'_items'][$index_gift]['name'] = $arr_items['name'];
                                    $arr_data_item['goods_items'][$k][$arr_items['item_type'].'_items'][$index_gift]['goods_id'] = $arr_items['goods_id'];
                                    $arr_data_item['goods_items'][$k][$arr_items['item_type'].'_items'][$index_gift]['price'] = $arr_items['price'];
                                    $arr_data_item['goods_items'][$k][$arr_items['item_type'].'_items'][$index_gift]['thumbnail_pic'] = $arrGoods['image_default_id'];
                                    $arr_data_item['goods_items'][$k][$arr_items['item_type'].'_items'][$index_gift]['score'] = intval($arr_items['score']*$arr_data_item['goods_items'][$k][$arr_items['item_type'].'_items'][$index_gift]['quantity']);
                                    $arr_data_item['goods_items'][$k][$arr_items['item_type'].'_items'][$index_gift]['link_url'] = $this->gen_url(array('app'=>'b2c','ctl'=>'wap_product','act'=>'index','arg0'=>$arr_items['products']['product_id']));
                                    $arr_data_item['goods_items'][$k][$arr_items['item_type'].'_items'][$index_gift]['amount'] = $arr_items['amount'];

                                    if ($arr_items['addon'])
                                    {
                                        $arr_addon = unserialize($arr_items['addon']);

                                        if ($arr_addon['product_attr'])
                                        {
                                            foreach ($arr_addon['product_attr'] as $arr_product_attr)
                                            {
                                                $arr_data_item['goods_items'][$k][$arr_items['item_type'].'_items'][$index_gift]['attr'] .= $arr_product_attr['label'] . $this->app->_(":") . $arr_product_attr['value'] . $this->app->_(" ");
                                            }
                                        }
                                    }

                                    if (isset($arr_data_item['goods_items'][$k][$arr_items['item_type'].'_items'][$index_gift]['attr']) && $arr_data_item['goods_items'][$k][$arr_items['item_type'].'_items'][$index_gift]['attr'])
                                    {
                                        if (strpos($arr_data_item['goods_items'][$k][$arr_items['item_type'].'_items'][$index_gift]['attr'], $this->app->_(" ")) !== false)
                                        {
                                            $arr_data_item['goods_items'][$k][$arr_items['item_type'].'_items'][$index_gift]['attr'] = substr($arr_data_item['goods_items'][$k][$arr_items['item_type'].'_items'][$index_gift]['attr'], 0, strrpos($arr_data_item['goods_items'][$k][$arr_items['item_type'].'_items'][$index_gift]['attr'], $this->app->_(" ")));
                                        }
                                    }
                                }
                                $index_gift++;
                            }
                        }
                    }
                    else
                    {
                        if ($arr_objects['obj_type'] == 'gift')
                        {
                            if ($arr_service_goods_type_obj[$arr_objects['obj_type']])
                            {
                                foreach ($arr_objects['order_items'] as $arr_items)
                                {
                                    if (!$arr_items['products'])
                                    {
                                        $o = $this->app->model('order_items');
                                        $tmp = $o->getList('*', array('item_id'=>$arr_items['item_id']));
                                        $arr_items['products']['product_id'] = $tmp[0]['product_id'];
                                    }

                                    $str_service_goods_type_obj = $arr_service_goods_type_obj[$arr_objects['obj_type']];
                                    $str_service_goods_type_obj->get_order_object(array('goods_id' => $arr_items['goods_id'], 'product_id'=>$arr_items['products']['product_id']), $arrGoods,$tpl);

                                    if (!isset($arr_items['products']['product_id']) || !$arr_items['products']['product_id'])
                                        $arr_items['products']['product_id'] = $arr_items['goods_id'];

                                    if ($arr_data_item[$arr_items['item_type'].'_items'][$arr_items['products']['product_id']])
                                        $arr_data_item[$arr_items['item_type'].'_items'][$arr_items['products']['product_id']]['quantity'] = $objMath->number_plus(array($arr_items['quantity'], $arr_data_item[$arr_items['item_type'].'_items'][$arr_items['products']['product_id']]['quantity']));
                                    else
                                        $arr_data_item[$arr_items['item_type'].'_items'][$arr_items['products']['product_id']]['quantity'] = $arr_items['quantity'];

                                    if (!$arrGoods['image_default_id'])
                                    {
                                        $arrGoods['image_default_id'] = $image_set['S']['default_image'];
                                    }

                                    $arr_data_item[$arr_items['item_type'].'_items'][$arr_items['products']['product_id']]['name'] = $arr_items['name'];
                                    $arr_data_item[$arr_items['item_type'].'_items'][$arr_items['products']['product_id']]['goods_id'] = $arr_items['goods_id'];
                                    $arr_data_item[$arr_items['item_type'].'_items'][$arr_items['products']['product_id']]['price'] = $arr_items['price'];
                                    $arr_data_item[$arr_items['item_type'].'_items'][$arr_items['products']['product_id']]['thumbnail_pic'] = $arrGoods['image_default_id'];
                                    $arr_data_item[$arr_items['item_type'].'_items'][$arr_items['products']['product_id']]['point'] = intval($arr_items['score']*$arr_data_item[$arr_items['item_type'].'_items'][$arr_items['products']['product_id']]['quantity']);
                                    $arr_data_item[$arr_items['item_type'].'_items'][$arr_items['products']['product_id']]['nums'] = $arr_items['quantity'];
                                    $arr_data_item[$arr_items['item_type'].'_items'][$arr_items['products']['product_id']]['link_url'] = $this->gen_url(array('app'=>'b2c','ctl'=>'wap_product','act'=>'index','arg0'=>$arr_items['products']['product_id']));
                                    $arr_data_item[$arr_items['item_type'].'_items'][$arr_items['products']['product_id']]['amount'] = $arr_items['amount'];

                                    if ($arr_items['addon'])
                                    {
                                        $arr_addon = unserialize($arr_items['addon']);

                                        if ($arr_addon['product_attr'])
                                        {
                                            foreach ($arr_addon['product_attr'] as $arr_product_attr)
                                            {
                                                $arr_data_item[$arr_items['item_type'].'_items'][$arr_items['products']['product_id']]['attr'] .= $arr_product_attr['label'] . $this->app->_(":") . $arr_product_attr['value'] . $this->app->_(" ");
                                            }
                                        }
                                    }

                                    if (isset($arr_data_item[$arr_items['item_type'].'_items'][$arr_items['products']['product_id']]['attr']) && $arr_data_item[$arr_items['item_type'].'_items'][$arr_items['products']['product_id']]['attr'])
                                    {
                                        if (strpos($arr_data_item[$arr_items['item_type'].'_items'][$arr_items['products']['product_id']]['attr'], $this->app->_(" ")) !== false)
                                        {
                                            $arr_data_item[$arr_items['item_type'].'_items'][$arr_items['products']['product_id']]['attr'] = substr($arr_data_item[$arr_items['item_type'].'_items'][$arr_items['products']['product_id']]['attr'], 0, strrpos($arr_data_item[$arr_items['item_type'].'_items'][$arr_items['products']['product_id']]['attr'], $this->app->_(" ")));
                                        }
                                    }
                                }
                            }
                        }
                        else
                        {
                            if ($arr_service_goods_type_obj[$arr_objects['obj_type']])
                            {

                                $str_service_goods_type_obj = $arr_service_goods_type_obj[$arr_objects['obj_type']];
                                $arr_data_item['extends_items'][] = $str_service_goods_type_obj->get_order_object($arr_objects, $arr_Goods,$tpl);
                            }
                        }
                    }
                }
            }

        }
    }


    /**
     * @Author: panbiao <panbiaophp@163.com>
     * @DateTime: 2019-07-01 15:17
     * @Desc: 订单详情
     * @Parms:string order_id
     * @Return:array
     */
    public function orderdetail($order_id=0)
    {
        //过滤xss 和 sql注入
        $order_id = kernel::single('b2c_site_filter')->check_input($order_id);
        if (!isset($order_id) || !$order_id)
        {
            $this->begin(array('app' => 'b2c','ctl' => 'wap_member', 'act'=>'index'));
            $this->end(false, app::get('b2c')->_('订单编号不能为空！'));
        }

        $objOrder = $this->app->model('orders');
        $subsdf = array('order_objects'=>array('*',array('order_items'=>array('*',array(':products'=>'*')))), 'order_pmt'=>array('*'));
        $sdf_order = $objOrder->dump($order_id, '*', $subsdf);
        $objMath = kernel::single("ectools_math");
        if(!$sdf_order||$this->app->member_id!=$sdf_order['member_id']){
            $this->_response->set_http_response_code(404);
            $this->_response->set_body(app::get('b2c')->_('订单号：') . $order_id . app::get('b2c')->_('不存在！'));
            return;
        }
        if($sdf_order['member_id']){
            $member = $this->app->model('members');
            $aMember = $member->dump($sdf_order['member_id'], 'email');
            $sdf_order['receiver']['email'] = $aMember['contact']['email'];
        }

        // 处理收货人地区
        $arr_consignee_area = array();
        $arr_consignee_regions = array();
        if (strpos($sdf_order['consignee']['area'], ':') !== false)
        {
            $arr_consignee_area = explode(':', $sdf_order['consignee']['area']);
            if ($arr_consignee_area[1])
            {
                if (strpos($arr_consignee_area[1], '/') !== false)
                {
                    $arr_consignee_regions = explode('/', $arr_consignee_area[1]);
                }
            }

            $sdf_order['consignee']['area'] = (is_array($arr_consignee_regions) && $arr_consignee_regions) ? $arr_consignee_regions[0] . $arr_consignee_regions[1] . $arr_consignee_regions[2] : $sdf_order['consignee']['area'];
        }

        // 订单的相关信息的修改
        $obj_other_info = kernel::servicelist('b2c.order_other_infomation');
        if ($obj_other_info)
        {
            foreach ($obj_other_info as $obj)
            {
                $this->pagedata['discount_html'] = $obj->gen_point_discount($sdf_order);
            }
        }
        $order_payed = kernel::single('b2c_order_pay')->check_payed($sdf_order['order_id']);
        if($order_payed==$sdf_order['total_amount']){
            $sdf_order['is_pay'] = 1;
        }else{
            $sdf_order['is_pay'] = 0;
        }
        //判断预售 promotion_type
        if($sdf_order['promotion_type']=='prepare'){
            $prepare_order=kernel::service('prepare_order');
            if($prepare_order)
            {
                $prepare=$prepare_order->get_order_info($sdf_order['order_id']);
                $order_payed = kernel::single('b2c_order_pay')->check_payed($sdf_order['order_id']);
                if($order_payed>0 ){
                    if( $prepare['begin_time'] < time() && time() < $prepare['end_time'] ){
                        if($order_payed==$prepare['preparesell_price']){
                            $sdf_order['is_pay']=1;
                        }
                    }
                }
            }
        }
        //显示是否有必填项
        $minfo = $objOrder->minfo($sdf_order);
        if(!empty($minfo)){
            $this->pagedata['is_minfo'] = 1;
            $this->pagedata['minfo'] = $minfo;
        }else{
            $this->pagedata['is_minfo'] = 0;
        }
        $this->pagedata['order'] = $sdf_order;

        $order_items = array();
        $gift_items = array();
        $this->get_order_detail_item($sdf_order,'member_order_detail');
        $this->pagedata['order'] = $sdf_order;
        /** 将商品促销单独剥离出来 **/
        if ($this->pagedata['order']['order_pmt'])
        {
            foreach ($this->pagedata['order']['order_pmt'] as $key=>$arr_pmt)
            {
                if ($arr_pmt['pmt_type'] == 'goods')
                {
                    $this->pagedata['order']['goods_pmt'][$arr_pmt['product_id']][$key] =  $this->pagedata['order']['order_pmt'][$key];
                    unset($this->pagedata['order']['order_pmt'][$key]);
                }
            }
        }
        /** end **/

        // 得到订单留言.
        $oMsg = kernel::single("b2c_message_order");
        $arrOrderMsg = $oMsg->getList('*', array('order_id' => $order_id, 'object_type' => 'order'), $offset=0, $limit=-1, 'time DESC');

        $this->pagedata['ordermsg'] = $arrOrderMsg;
        $this->pagedata['res_url'] = $this->app->res_url;

        //我已付款
        $$timeHours = array();
        for($i=0;$i<24;$i++){
            $v = ($i<10)?'0'.$i:$i;
            $timeHours[$v] = $v;
        }
        $timeMins = array();
        for($i=0;$i<60;$i++){
            $v = ($i<10)?'0'.$i:$i;
            $timeMins[$v] = $v;
        }
        $this->pagedata['timeHours'] = $timeHours;
        $this->pagedata['timeMins'] = $timeMins;

        // 生成订单日志明细
        //$oLogs =$this->app->model('order_log');
        //$arr_order_logs = $oLogs->getList('*', array('rel_id' => $order_id));
        $arr_order_logs = $objOrder->getOrderLogList($order_id);
        $this->pagedata['orderlogs'] = $arr_order_logs['data'];
        $logi = app::get('logisticstrack')->is_actived();
        $this->pagedata['logi'] = $logi;

        // 取到支付单信息
        $obj_payments = app::get('ectools')->model('payments');
        $this->pagedata['paymentlists'] = $obj_payments->get_payments_by_order_id($order_id);

        // 支付方式的解析变化
        $obj_payments_cfgs = app::get('ectools')->model('payment_cfgs');
        $arr_payments_cfg = $obj_payments_cfgs->getPaymentInfo($this->pagedata['order']['payinfo']['pay_app_id']);
        $this->pagedata['order']['payment'] = $arr_payments_cfg;

        #//物流跟踪安装并且开启
        #$logisticst = app::get('b2c')->getConf('system.order.tracking');
        #$logisticst_service = kernel::service('b2c_change_orderloglist');
        #if(isset($logisticst) && $logisticst == 'true' && $logisticst_service){
        #    $this->pagedata['services']['logisticstack'] = $logisticst_service;
        #}
        $this->pagedata['orderlogs'] = $arr_order_logs['data'];
        // 添加html埋点
        foreach( kernel::servicelist('b2c.order_add_html') as $services ) {
            if ( is_object($services) ) {
                if ( method_exists($services, 'fetchHtml') ) {
                    $services->fetchHtml($this,$order_id,'site/invoice_detail.html');
                }
            }
        }
        $this->pagedata['controller'] = "orders";

        //添加体检频道html嵌入页面
        $physical_flag = false;
        if($this->pagedata['order']['order_kind'] == 'card' || $this->pagedata['order']['order_kind'] == 'b2c_card')
        {
            $physical_orders_object = kernel::single("physical_mdl_orders");
            $physical_orders_data = $physical_orders_object->dump(array("order_id"=>$this->pagedata['order']['order_id']),"*");
            if(!empty($physical_orders_data)){
                $physical_flag = true;
                $physical_store_object = kernel::single("physical_mdl_store");
                $physical_store_data = $physical_store_object->dump(array('store_id'=>$physical_orders_data['store_id']),"*");
                $physical_package_object = kernel::single("physical_mdl_package");
                $physical_package_data = $physical_package_object->dump(array('package_id'=>$physical_orders_data['package_id']),"*");
                if($physical_orders_data['c_type'] == '1'){
                    $physical_orders_data['c_type'] = "身份证";
                }elseif($physical_orders_data['c_type'] == '2'){
                    $physical_orders_data['c_type'] = "军官证";
                }elseif($physical_orders_data['c_type'] == '3'){
                    $physical_orders_data['c_type'] = "团员证";
                }else{
                    $physical_orders_data['c_type'] = "身份证";
                }
                if($physical_orders_data['marry'] == "1"){
                    $physical_orders_data['marry'] = '是';
                }elseif($physical_orders_data['marry'] == "2"){
                    $physical_orders_data['marry'] = '否';
                }else{
                    $physical_orders_data['marry'] = '否';
                }
                if($physical_orders_data['sex'] == "1"){
                    $physical_orders_data['sex'] = '男';
                }elseif($physical_orders_data['sex'] == "2"){
                    $physical_orders_data['sex'] = '女';
                }else{
                    $physical_orders_data['sex'] = '男';
                }
                $physical_orders_data['package_info'] = $physical_package_data;
                $physical_orders_data['store_info'] = $physical_store_data;
                $this->pagedata['physical_data'] = $physical_orders_data;
                $this->pagedata['physical_flag'] = $physical_flag;
            }
        }
        // 预售订单信息
        $prepare_order=kernel::service('prepare_order');
        if($prepare_order)
        {
            $pre_order=$prepare_order->get_order_info($order_id);
            if(!empty($pre_order))
            {
                $pre_order['prepare_type']='prepare';
                $this->pagedata['prepare']=$pre_order;
            }
        }
        $this->page('wap/member/orderdetail.html');
    }


    //物流信息查询
    function logistic($deliveryid){
        $deliveryMdl = app::get('b2c')->model('delivery');
        $delivery = $deliveryMdl->getList('logi_id,logi_name,logi_no',array('delivery_id'=>$deliveryid,'disabled'=>'false'),0,1);
        $this->pagedata['delivery'] = $delivery;
        $this->pagedata['logisticsurl'] = $this->gen_url(array('app'=>'logisticstrack','ctl'=>'wap_tracker','act'=>'pull','arg0'=>$deliveryid));
        $this->page('wap/member/logistic.html');
    }

    function favorite($nPage=1){
        $this->path[] = array('title'=>app::get('b2c')->_('会员中心'),'link'=>$this->gen_url(array('app'=>'b2c', 'ctl'=>'wap_member', 'act'=>'index','full'=>1)));
        $this->path[] = array('title'=>app::get('b2c')->_('商品收藏'),'link'=>'#');
        $GLOBALS['runtime']['path'] = $this->path;
        $aData = kernel::single('b2c_member_fav')->get_favorite($this->app->member_id,$this->member['member_lv'],$nPage);
        $imageDefault = app::get('image')->getConf('image.set');
        $aProduct = $aData['data'];
        foreach($aProduct as $k=>$v){
            if($v['nostore_sell']){
                $aProduct[$k]['store'] = 999999;
                $aProduct[$k]['product_id'] = $v['spec_desc_info'][0]['product_id'];
            }else{
                foreach((array)$v['spec_desc_info'] as $value){
                    $aProduct[$k]['product_id'] = $value['product_id'];
                    $aProduct[$k]['spec_info'] = $value['spec_info'];
                    $aProduct[$k]['price'] = $value['price'];
                    if(is_null($value['store']) ){
                        $aProduct[$k]['store'] = 999999;
                        break;
                    }elseif( ($value['store']-$value['freez']) > 0 ){
                        $aProduct[$k]['store'] = $value['store']-$value['freez'];
                        break;
                    }else{
                        $aProduct[$k]['store'] = false;
                    }
                }
            }
        }
        $this->pagedata['favorite'] = $aProduct;
        $this->pagination($nPage,$aData['page'],'favorite');
        $this->pagedata['defaultImage'] = $imageDefault['S']['default_image'];
        $setting['buytarget'] = $this->app->getConf('site.buy.target');
        $this->pagedata['setting'] = $setting;
        $this->pagedata['current_page'] = $nPage;
        /** 接触收藏的页面地址 **/
        $this->pagedata['fav_ajax_del_goods_url'] = $this->gen_url(array('app'=>'b2c','ctl'=>'wap_member','act'=>'ajax_del_fav','args'=>array('goods')));
        $this->page('wap/member/favorite.html');
    }

    /*
     *删除商品收藏
     * */
    function ajax_del_fav($gid=null,$object_type='goods'){
        if(!$gid){
            $this->splash('error',null,app::get('b2c')->_('参数错误！'));
        }
        if (!kernel::single('b2c_member_fav')->del_fav($this->app->member_id,$object_type,$gid,$maxPage)){
            $this->splash('error',null,app::get('b2c')->_('移除失败！'));
        }else{
            $this->set_cookie('S[GFAV]'.'['.$this->app->member_id.']',$this->get_member_fav($this->app->member_id),false);

            $current_page = $_POST['current'];
            if ($current_page > $maxPage){
                $current_page = $maxPage;
                $reload_url = $this->gen_url(array('app'=>'b2c','ctl'=>'wap_member','act'=>'favorite','args'=>array($current_page)));
                $this->splash('success',$reload_url,app::get('b2c')->_('成功移除！'));
            }
            $aData = kernel::single('b2c_member_fav')->get_favorite($this->app->member_id,$this->member['member_lv'],$current_page);
            $aProduct = $aData['data'];

            $oImage = app::get('image')->model('image');
            $imageDefault = app::get('image')->getConf('image.set');
            foreach($aProduct as $k=>$v) {
                if(!$oImage->getList("image_id",array('image_id'=>$v['image_default_id']))){
                    $aProduct[$k]['image_default_id'] = $imageDefault['S']['default_image'];
                }
            }
            $this->pagedata['favorite'] = $aProduct;
            $this->pagedata['defaultImage'] = $imageDefault['S']['default_image'];
            $reload_url = $this->gen_url(array('app'=>'b2c','ctl'=>'wap_member','act'=>'favorite'));
            $this->splash('success',$reload_url,app::get('b2c')->_('成功移除！'));
        }
    }

    function ajax_fav() {
        $object_type = $_POST['type'];
        $nGid = $_POST['gid'];
        if (!kernel::single('b2c_member_fav')->add_fav($this->app->member_id,$object_type,$nGid)){
            $this->splash('failed', app::get('b2c')->_('商品收藏添加失败！'), '', '', true);
        }else{
            $this->set_cookie('S[GFAV]'.'['.$this->app->member_id.']',$this->get_member_fav($this->app->member_id),false);
            $this->splash('success',$url,app::get('b2c')->_('商品收藏添加成功'));
        }
    }

    //收获地址
    function receiver(){
        $this->path[] = array('title'=>app::get('b2c')->_('会员中心'),'link'=>$this->gen_url(array('app'=>'b2c', 'ctl'=>'wap_member', 'act'=>'index','full'=>1)));
        $this->path[] = array('title'=>app::get('b2c')->_('收货地址'),'link'=>'#');
        $GLOBALS['runtime']['path'] = $this->path;
        $objMem = $this->app->model('members');
        $this->pagedata['receiver'] = $objMem->getMemberAddr($this->app->member_id);
        $this->pagedata['is_allow'] = (count($this->pagedata['receiver'])<10 ? 1 : 0);
        $this->pagedata['num'] = count($this->pagedata['receiver']);
        $this->pagedata['res_url'] = $this->app->res_url;
        $this->page('wap/member/receiver.html');
    }


    /**
     * @Author: panbiao <panbiaophp@163.com>
     * @DateTime: 2019-07-01 10:28
     * @Desc: 设置和取消默认地址，$disabled 2为设置默认1为取消默认
     */
    function set_default()
    {
        $addrId = $_POST['addr_id'];
        $disabled = $_POST['disabled'];
        if(empty($addrId))
        {
            echo json_encode(array('status'=>'failed','msg'=>'参数错误'));exit;
        }
        $obj_member = &$this->app->model('members');
        $member_id = $this->app->member_id;
        if($obj_member->check_addr($addrId,$member_id ))
        {
            if($obj_member->set_to_def($addrId,$member_id,$message,$disabled))
            {
                echo json_encode(array('status'=>'success','msg'=>$message));exit;
            }
            else
            {
                echo json_encode(array('status'=>'failed','msg'=>$message));exit;
            }
        }
        else
        {
            echo json_encode(array('status'=>'failed','msg'=>'参数错误'));exit;
        }
    }

    //删除收货地址
    function del_rec()
    {
        $addrId = $_POST['addr_id'];
        if(empty($addrId))
        {
            echo json_encode(array('status'=>'failed','msg'=>'参数错误'));exit;
        }
        $obj_member = &$this->app->model('members');
        if($obj_member->check_addr($addrId,$this->app->member_id))
        {
            if($obj_member->del_rec($addrId,$message,$this->app->member_id))
            {
                echo json_encode(array('status'=>'success','msg'=>$message));exit;
            }
            else
            {
                echo json_encode(array('status'=>'failed','msg'=>$message));exit;
            }
        }
        else
        {
            echo json_encode(array('status'=>'failed','msg'=>'操作失败'));exit;
        }
    }

    /*
     *添加、修改收货地址
     * */
    function modify_receiver($addrId=null){
        if(!$addrId){
            echo  app::get('b2c')->_("参数错误");exit;
        }
        $obj_member = $this->app->model('members');
        if($obj_member->check_addr($addrId,$this->app->member_id)){
            if($aRet = $obj_member->getAddrById($addrId)){
                $aRet['defOpt'] = array('0'=>app::get('b2c')->_('否'), '1'=>app::get('b2c')->_('是'));
                 $this->pagedata = $aRet;
            }else{
                $this->_response->set_http_response_code(404);
                $this->_response->set_body(app::get('b2c')->_('修改的收货地址不存在！'));
                exit;
            }
            $this->page('wap/member/modify_receiver.html');
        }else{
            echo  app::get('b2c')->_("参数错误");exit;
        }
    }

    /**
     * @Author: panbiao <panbiaophp@163.com>
     * @DateTime: 2019-07-01 09:23
     * @Desc: 新增收货地址
     */
    function insertRec($data,&$msg)
    {
        $obj_member = &$this->app->model('members');
        if(!$obj_member->isAllowAddr($this->app->member_id))
        {
            $msg = '不能新增收货地址';
            return false;
        }
        $aData = $obj_member->checkRecInput($data);
        $status = $obj_member->insertRec($aData,$this->app->member_id,$msg);
        return $status?true:false;
    }

    /**
     * @Author: panbiao <panbiaophp@163.com>
     * @DateTime: 2019-07-01 09:25
     * @Desc: 修改收货地址
     */
    function modifyReceiver($data,&$msg)
    {
        if(empty($data['addr_id']))
        {
            $msg = "参数错误";
            return false;
        }
        $obj_member = &$this->app->model('members');
        if($obj_member->check_addr($data['addr_id'],$this->member['member_id']))
        {
            $aData = $obj_member->checkRecInput($data);
            if($obj_member->save_rec($aData,$this->app->member_id,$msg))
            {
                return true;
            }
            else{
                return false;
            }
        }
        else
        {
            $msg = "操作失败";
            return false;
        }
    }



    /**
     * @Author: panbiao <panbiaophp@163.com>
     * @DateTime: 2019-07-01 08:39
     * @Desc: 保存收货地址
     */
    function save_rec()
    {
        if(!$_POST['default'])
        {
            $_POST['default'] = 0;
        }
        $_POST['member_id'] = $this->app->member_id;
        if (empty($_POST['addr_id']))
        {
            if(!$this->insertRec($_POST,$msg))
            {
                $this->splash('failed',null,$msg,'','',true);
            }
        }
        else
        {
            if(!$this->modifyReceiver($_POST,$msg))
            {
                $this->splash('failed',null,$msg,'','',true);
            }
        }
        $this->splash('success',$this->gen_url(array('app'=>'b2c','ctl'=>'wap_member','act'=>'receiver')),app::get('b2c')->_('保存成功'),'','',true);
    }

    //添加收货地址
    function add_receiver(){
        $obj_member = $this->app->model('members');
        if($obj_member->isAllowAddr($this->app->member_id)){
            $this->page('wap/member/modify_receiver.html');
        }else{
            $this->splash('failed',$this->gen_url(array('app'=>'b2c','ctl'=>'wap_member','act'=>'receiver')),app::get('b2c')->_('最多添加10个收货地址'),'','',true);
        }
    }

    /*
     * 获取评论咨询的数据
     *
     * */
    public function getComment($nPage=1,$type='discuss'){
        //获取评论咨询基本数据
        $comment = kernel::single('b2c_message_disask');
        $aData = $comment->get_member_disask($this->app->member_id,$nPage,$type);
        $gids = array();
        $productGids = array();
        foreach((array)$aData['data'] as $k => $v){
            if($v['type_id'] && !in_array($v['type_id'],$gids) ){
                $gids[] = $v['type_id'];
            }
            if(!$v['product_id'] && !in_array($v['type_id'],$productGids) ){
                $productGids[] = $v['type_id'];
            }

            if($v['items']){//统计回复未读的数量
                $unReadNum = 0;
                foreach($v['items'] as $val){
                    if($val['mem_read_status'] == 'false' ){
                        $unReadNum += 1;
                    }
                }
            }
            $aData['data'][$k]['unReadNum'] = $unReadNum;
        }

        //获取货品ID
        $productData = $productGids ? $this->app->model('products')->getList('goods_id,product_id',array('goods_id'=>$productGids,'is_default'=>'true')) : array();
        foreach((array) $productData as $p_row){
            $productList[$p_row['goods_id']] = $p_row['product_id'];
        }
        $this->pagedata['productList'] = $productList;

        //评论咨询商品信息
        $goodsData = $gids ? $this->app->model('goods')->getList('goods_id,name,price,thumbnail_pic,udfimg,image_default_id',array('goods_id'=>$gids)) : null;
        if($goodsData){
            foreach($goodsData as $row){
                $goodsList[$row['goods_id']] = $row;
            }
        }
        $this->pagedata['goodsList'] = $goodsList;

        //评论咨询私有的数据
        if($type == 'discuss'){
            $this->pagedata['point_status'] = app::get('b2c')->getConf('goods.point.status') ? app::get('b2c')->getConf('goods.point.status'): 'on';
            if($this->pagedata['point_status'] == 'on'){//如果开启评分则获取评论评分
                $objPoint = $this->app->model('comment_goods_point');
                $goods_point = $objPoint->get_single_point_arr($gids);
                $this->pagedata['goods_point'] = $goods_point;
            }
        }else{
            $gask_type = unserialize($this->app->getConf('gask_type'));//咨询类型
            foreach((array)$gask_type as $row){
                $gask_type_list[$row['type_id']] = $row['name'];
            }
            $this->pagedata['gask_type'] = $gask_type_list;
        }
        return $aData;
    }

    function comment($nPage=1){
        //面包屑
        $this->path[] = array('title'=>app::get('b2c')->_('会员中心'),'link'=>$this->gen_url(array('app'=>'b2c', 'ctl'=>'wap_member', 'act'=>'index','full'=>1)));
        $this->path[] = array('title'=>app::get('b2c')->_('评论与咨询'),'link'=>'#');
        $GLOBALS['runtime']['path'] = $this->path;

        $comment = $this->getComment($nPage,'discuss');
        $this->pagedata['commentList'] = $comment['data'];
        $this->pagination($nPage,$comment['page'],'comment');
        $this->output();
    }

    function ask($nPage=1){
        //面包屑
        $this->path[] = array('title'=>app::get('b2c')->_('会员中心'),'link'=>$this->gen_url(array('app'=>'b2c', 'ctl'=>'wap_member', 'act'=>'index','full'=>1)));
        $this->path[] = array('title'=>app::get('b2c')->_('评论与咨询'),'link'=>'#');
        $GLOBALS['runtime']['path'] = $this->path;

        $this->pagedata['controller'] = "comment";
        $comment = $this->getComment($nPage,'ask');
        $this->pagedata['commentList'] = $comment['data'];
        $this->pagedata['commentType'] = 'ask';
        $this->pagination($nPage,$comment['page'],'ask');
        $this->action_view = 'comment.html';
        $this->output();
    }

    /**
     * @Author: panbiao <panbiaophp@163.com>
     * @DateTime: 2019-07-01 21:04
     * @Desc: 未评论商品
     */
    public function nodiscuss($nPage=1)
    {
        //面包屑
        $this->path[] = array('title'=>app::get('b2c')->_('会员中心'),'link'=>$this->gen_url(array('app'=>'b2c', 'ctl'=>'wap_member', 'act'=>'index','full'=>1)));
        $this->path[] = array('title'=>app::get('b2c')->_('未评论商品'),'link'=>'#');
        $GLOBALS['runtime']['path'] = $this->path;

        //获取会员已发货的商品日志
        $sell_logs = $this->app->model('sell_logs')->getList('order_id,product_id,goods_id',array('member_id'=>$this->app->member_id));
        //获取会员已评论的商品
        $comments = $this->app->model('member_comments')->getList('order_id,product_id',array('author_id'=>$this->app->member_id,'object_type'=>'discuss','for_comment_id'=>'0'));
        $data = array();
        if($comments){
            foreach((array)$comments as $row){
                if($row['order_id'] && $row['product_id']){
                    $data[$row['order_id']][$row['product_id']] = $row['product_id'];
                }
            }
        }

        foreach((array)$sell_logs as $key=>$log_row){
            if($data && $data[$log_row['order_id']][$log_row['product_id']] == $log_row['product_id']){
                unset($sell_logs[$key]);
            }else{
                $filter['order_id'][] = $log_row['order_id'];
                $filter['product_id'][] = $log_row['product_id'];
                $filter['item_type|noequal'] = 'gift';
            }
        }

        $orderItemModel = app::get('b2c')->model('order_items');
        $limit = $this->pagesize;
        $start = ($nPage-1)*$limit;
        $i = 0;
        $nogift = $orderItemModel->getList('order_id,product_id',$filter);
        if($nogift){
            foreach($nogift as $row){
                $tmp_nogift_order_id[] = $row['order_id'];
                $tmp_nogift_product_id[] = $row['product_id'];
            }
        }
        foreach((array)$sell_logs as $key=>$log_row){
            if(in_array($log_row['order_id'],$tmp_nogift_order_id) && in_array($log_row['product_id'],$tmp_nogift_product_id) ){//剔除赠品,赠品不需要评论
                if($i >= $start && $i < ($nPage*$limit) ){
                    $sell_logs_data[] = $log_row;
                    $gids[] = $log_row['goods_id'];
                }
                if($nogift){
                    $i += 1;
                }
            }
        }
        $totalPage = ceil($i/$limit);
        if($nPage > $totalPage) $nPage = $totalPage;

        $this->pagedata['list'] = $sell_logs_data;
        $this->pagination($nPage,$totalPage,'nodiscuss');

        if($gids){
            //获取商品信息
            $goodsData = $this->app->model('goods')->getList('goods_id,name,image_default_id',array('goods_id'=>$gids));
            $goodsList = array();
            foreach((array)$goodsData as $goods_row){
                $goodsList[$goods_row['goods_id']]['name'] = $goods_row['name'];
                $goodsList[$goods_row['goods_id']]['image_default_id'] = $goods_row['image_default_id'];
            }
            $this->pagedata['goods'] = $goodsList;

            $this->pagedata['point_status'] = app::get('b2c')->getConf('goods.point.status') ? app::get('b2c')->getConf('goods.point.status'): 'on';
            $this->pagedata['verifyCode'] = $this->app->getConf('comment.verifyCode');
            if($this->pagedata['point_status'] == 'on'){
                //评分类型
                $comment_goods_type = $this->app->model('comment_goods_type');
                $this->pagedata['comment_goods_type'] = $comment_goods_type->getList('*');
                if(!$this->pagedata['comment_goods_type']){
                    $sdf['type_id'] = 1;
                    $sdf['name'] = app::get('b2c')->_('商品评分');
                    $addon['is_total_point'] = 'on';
                    $sdf['addon'] = serialize($addon);
                    $comment_goods_type->insert($sdf);
                    $this->pagedata['comment_goods_type'] = $comment_goods_type->getList('*');
                }
            }

        $this->pagedata['submit_comment_notice'] = $this->app->getConf('comment.submit_comment_notice.discuss');
        }
        $this->page('wap/member/nodiscuss.html');
    }

    //我的优惠券
    function coupon($nPage=1) {
        $member_center_url = $this->gen_url(array('app'=>'b2c', 'ctl'=>'wap_member', 'act'=>'index','full'=>1));
        $this->path[] = array('title'=>app::get('b2c')->_('会员中心'),'link'=>$member_center_url);
        $this->path[] = array('title'=>app::get('b2c')->_('我的优惠券'),'link'=>'#');
        $GLOBALS['runtime']['path'] = $this->path;
        $oCoupon = kernel::single('b2c_coupon_mem');
        $aData = $oCoupon->get_list_m($this->app->member_id,$nPage);
        if ($aData) {
            foreach ($aData as $k => $item) {
                if ($item['coupons_info']['cpns_status'] !=1) {
                    $aData[$k]['coupons_info']['cpns_status'] = false;
                    $aData[$k]['memc_status'] = app::get('b2c')->_('此种优惠券已取消');
                    continue;
                }

                $member_lvs = explode(',',$item['time']['member_lv_ids']);
                if (!in_array($this->member['member_lv'],(array)$member_lvs)) {
                    $aData[$k]['coupons_info']['cpns_status'] = false;
                    $aData[$k]['memc_status'] = app::get('b2c')->_('本级别不准使用');
                    continue;
                }

                $curTime = time();
                if ($curTime>=$item['time']['from_time'] && $curTime<$item['time']['to_time']) {
                    if ($item['memc_used_times']<$this->app->getConf('coupon.mc.use_times')){
                        if ($item['coupons_info']['cpns_status']){
                            $aData[$k]['memc_status'] = app::get('b2c')->_('可使用');
                        }else{
                            $aData[$k]['memc_status'] = app::get('b2c')->_('本优惠券已作废');
                        }
                    }else{
                        $aData[$k]['coupons_info']['cpns_status'] = false;
                        $aData[$k]['memc_status'] = app::get('b2c')->_('本优惠券次数已用完');
                    }
                }else{
                    $aData[$k]['coupons_info']['cpns_status'] = false;
                    $aData[$k]['memc_status'] = app::get('b2c')->_('还未开始或已过期');
                }
            }
        }

        $total = $oCoupon->get_list_m($this->app->member_id);
        $this->pagination($nPage,ceil(count($total)/$this->pagesize),'coupon');
        $this->pagedata['coupons'] = $aData;
        $this->pagedata['member_center_url'] = $member_center_url;
        $this->page('wap/member/coupon.html');
    }


    /**
     * 添加留言
     * @params string order_id
     * @params string message type
     */
    public function add_order_msg( $order_id , $msgType = 0 ){
        $timeHours = array();
        for($i=0;$i<24;$i++){
            $v = ($i<10)?'0'.$i:$i;
            $timeHours[$v] = $v;
        }
        $timeMins = array();
        for($i=0;$i<60;$i++){
            $v = ($i<10)?'0'.$i:$i;
            $timeMins[$v] = $v;
        }
        $this->pagedata['orderId'] = $order_id;
        $this->pagedata['msgType'] = $msgType;
        $this->pagedata['timeHours'] = $timeHours;
        $this->pagedata['timeMins'] = $timeMins;

        $this->page('wap/member/add_order_msg.html');
    }

    /**
     * 订单留言提交
     * @params null
     * @return null
     */
    public function toadd_order_msg()
    {
        if(!$_POST['msg']['orderid']){
            $this->splash(false,app::get('b2c')->_('参数错误'),true);
        }

        $obj_filter = kernel::single('b2c_site_filter');
        $_POST = $obj_filter->check_input($_POST);

        $_POST['to_type'] = 'admin';
        $_POST['author_id'] = $this->app->member_id;
        $_POST['author'] = $this->member['uname'];
        $is_save = true;
        $obj_order_message = kernel::single("b2c_order_message");
        if ($obj_order_message->create($_POST)){
            $url = $this->gen_url(array('app'=>'b2c','ctl'=>'wap_member','act'=>'orderdetail','arg0'=>$_POST['msg']['orderid']));
            $this->splash('success',$url,app::get('b2c')->_('留言成功'),'','',true);
        }else{
            $url = $this->gen_url(array('app'=>'b2c','ctl'=>'wap_member','act'=>'add_order_msg','arg0'=>$_POST['msg']['orderid'],'arg1'=>1));
            $this->splash(false,$url,app::get('b2c')->_('留言失败'),'','',true);
        }
    }

    /*
     *会员中心 修改密码页面
     * */
    function security($type = ''){
        $member = $this->member;
        $obj_pam_members = app::get('pam')->model('members');
        $this->pagedata['is_nopassword'] = $obj_pam_members->is_nopassword($member['member_id']);
        $this->path[] = array('title'=>app::get('b2c')->_('会员中心'),'link'=>$this->gen_url(array('app'=>'b2c', 'ctl'=>'wap_member', 'act'=>'index','full'=>1)));
        $this->path[] = array('title'=>app::get('b2c')->_('修改密码'),'link'=>'#');
        $GLOBALS['runtime']['path'] = $this->path;
        $this->page('wap/member/modify_password.html');
    }

    /*
     *保存修改密码
     * */
    function save_security(){
        $member = $this->member;
        $obj_pam_members = app::get('pam')->model('members');
        $passport_login = $this->gen_url(array('app'=>'b2c','ctl'=>'wap_passport','act'=>'login'));
        $url = $this->gen_url(array('app'=>'b2c','ctl'=>'wap_passport','act'=>'logout','arg0'=>$passport_login));
        $userPassport = kernel::single('b2c_user_passport');
        if($obj_pam_members->is_nopassword($member['member_id']) == 'true')
        {
            $result = $userPassport->reset_passport($this->app->member_id,$_POST['passwd']);
            if($result)
            {
                $this->splash('success', $url, app::get('b2c')->_('修改成功'), true);
            }else{
                $this->splash('failed', null, app::get('b2c')->_('修改失败'), true);
            }
        }
        $result = $userPassport->save_security($this->app->member_id,$_POST,$msg);
        if($result){
            $this->splash('success',$url,$msg,'','',true);
        }else{
            $this->splash('failed',null,$msg,'','',true);
        }
    }

    function cancel($order_id)
    {
        $this->pagedata['cancel_order_id'] = $order_id;
        $this->page('wap/member/order_cancel_reason.html');
    }


    function docancel()
    {
        $arrMember = kernel::single('b2c_user_object')->get_current_member(); //member_id,uname
        //开启事务处理
        $db = kernel::database();
        $transaction_status = $db->beginTransaction();

        $order_cancel_reason = $_POST['order_cancel_reason'];

        $error_url = $this->gen_url(array('app'=>'b2c','ctl'=>'wap_member','act'=>'cancel','arg0'=>$order_cancel_reason['order_id']));
        if($order_cancel_reason['reason_type'] == 7 && !$order_cancel_reason['reason_desc'])
        {
            $this->splash('failed',$error_url,'请输入详细原因',true);
        }
        if(strlen($order_cancel_reason['reason_desc'])>150)
        {
            $this->splash('failed',$error_url,'详细原因过长，请输入50个字以内',true);
        }
        if($order_cancel_reason['reason_type'] != 7 && strlen($order_cancel_reason['reason_desc']) > 0)
        {
            $order_cancel_reason['reason_desc'] = '';
        }
        $order_cancel_reason = utils::_filter_input($order_cancel_reason);
        $order_cancel_reason['cancel_time'] = time();
        $mdl_order = app::get('b2c')->model('orders');
        $sdf_order = $mdl_order->getRow('member_id,status,pay_status,ship_status', array('order_id'=>$order_cancel_reason['order_id']));
        if($sdf_order['member_id'] != $arrMember['member_id'])
        {
            $db->rollback();
            $this->splash('failed',$error_url,"请勿取消别人的订单",true);
            return;
        }
        $order_payed = kernel::single('b2c_order_pay')->check_payed($order_cancel_reason['order_id']);
        if($order_payed>0){
            $this->splash('failed',$error_url,"支付过的订单，无法取消订单",true);
        }

        $mdl_order_cancel_reason = app::get('b2c')->model('order_cancel_reason');
        $result = $mdl_order_cancel_reason->save($order_cancel_reason);
        if(!$result)
        {
            $db->rollback();
            $this->splash('failed',$error_url,"订单取消原因记录失败",true);
        }
        $obj_checkorder = kernel::service('b2c_order_apps', array('content_path'=>'b2c_order_checkorder'));
        if (!$obj_checkorder->check_order_cancel($order_cancel_reason['order_id'],'',$message))
        {
            $db->rollback();
            $this->splash('failed',$error_url,$message,true);
        }
        //活动订单，未支付，未发货的可以取消
        if ($sdf_order['status'] !='active')
        {
            $db->rollback();
            $this->splash('failed',$error_url,'订单状态错误',true);
        }
        if ($sdf_order['pay_status'] !='0'  || $sdf_order['ship_status']!='0')
        {
            $db->rollback();
            $this->splash('failed',$error_url,'订单不能取消',true);
        }
        $sdf['order_id'] = $order_cancel_reason['order_id'];
        $sdf['op_id'] = $arrMember['member_id'];
        $sdf['opname'] = $arrMember['uname'];
        $sdf['account_type'] = 'member';
        $b2c_order_cancel = kernel::single('b2c_order_cancel');
        if ($b2c_order_cancel->generate($sdf, $this, $message))
        {
            if($order_object = kernel::service('b2c_order_rpc_async')){
                $order_object->modifyActive($sdf['order_id']);
            }
            $db->commit($transaction_status);
            $url = $this->gen_url(array('app'=>'b2c','ctl'=>'wap_member','act'=>'index'));
            $obj_coupon = kernel::single("b2c_coupon_order");
            $obj_coupon->use_c($sdf['order_id'],'cancel');
            $this->splash('success',$url,"订单取消成功",true);
        }
        else
        {
            $db->rollback();
            $this->splash('failed',$error_url,"订单取消失败",true);
        }
    }


    function receive($order_id){
        $arrMember = kernel::single('b2c_user_object')->get_current_member();
        $mdl_order = app::get('b2c')->model('orders');
        $sdf_order_member_id = $mdl_order->getRow('member_id', array('order_id'=>$order_id));
        $sdf_order_member_id['member_id'] = (int) $sdf_order_member_id['member_id'];
        if($sdf_order_member_id['member_id'] != $arrMember['member_id'])
        {
            return '请勿操作别人的收货';
        }else{
            $arr_updates = array('order_id'=>$order_id,'received_status' =>'1','received_time'=>time());
            $mdl_order->save($arr_updates);
            $delivery_mdl = app::get('b2c')->model('order_delivery_time');
            $delivery_mdl->delete(array('order_id' => $order_id));
            $orderLog = $this->app->model("order_log");
            $log_text = serialize($log_text);
            $sdf_order_log = array(
                'rel_id' => $order_id,
                'op_id' => $arrMember['member_id'],
                'op_name' => (!$arrMember['member_id']) ? app::get('b2c')->_('顾客') : $arrMember['uname'],
                'alttime' => time(),
                'bill_type' => 'order',
                'behavior' => 'receive',
                'result' => 'SUCCESS',
                'log_text' => '用户已确认收货！',
            );
            if($orderLog->save($sdf_order_log)){
                $this->splash('success',null,'已完成收货',true);exit;
            }else{
                $this->splash('error',null,'收货失败',true);exit;
            }
        }
    }

    public function coupon_receive(){
        if( isset($_POST['cpns_id']) ){
            $cpnsId = $_POST['cpns_id'];

            $concurrent_cpns=kernel::single("base_concurrent_file");
            $cpns_group_id = 'cpns_group_'.intval($cpnsId%1000);
            $concurrent_cpns->status($cpns_group_id);
            if(!$concurrent_cpns->check_flock()){
                $concurrent_cpns->close_lock();
                echo json_encode(array('status'=>'fail',msg=>"网络异常，请重试"));exit();
            }

            $oExchangeCoupon = kernel::single('b2c_coupon_mem');
            $memberId = intval($this->app->member_id);//会员id号
            $obj_widget_coupons = kernel::single('wap_widgets_coupons');
            if($memberId){
                $msgArr = array(
                    '2'=>'cpns_id为空',
                    '3'=>'优惠券已经领光',
                    '4'=>'会员等级不符',
                    '5'=>'活动未开始',
                    '6'=>'活动已结束',
                );
                if( $obj_widget_coupons->getReceiveStatus($cpnsId) ){
                    $concurrent_cpns->close_lock();
                    echo json_encode(array('status'=>'fail',msg=>"不可重复领取"));exit();
                }

                $verify_status = $obj_widget_coupons->verify($cpnsId);
                if( $verify_status != 1 ){
                    $concurrent_cpns->close_lock();
                    echo json_encode(array('status'=>'fail','msg'=>$msgArr[$verify_status]));exit();
                }

                $coupons = $this->app->model('coupons');
                $cur_coupon = $coupons->dump($cpnsId);

                if( $oExchangeCoupon->obtain($cpnsId,$memberId,$params) ){
                    $concurrent_cpns->unlock();
                    echo json_encode(array('status'=>'success',msg=>"领取成功"));exit();
                }else
                {
                    $concurrent_cpns->close_lock();
                    echo json_encode(array('status'=>'fail',msg=>"领取失败"));exit();
                }
            }else{
                $concurrent_cpns->close_lock();
                echo json_encode(array('status'=>'fail',msg=>"没有登录"));exit();
            }
        }

        echo json_encode(array('status'=>'fail',msg=>"参数异常"));exit();
    }


    /**
     * 优惠券列表挂件内优惠券状态查询
     * @params $cpns_id string 优惠券id
     * @return json array('status'=>'success/fail',coupon=>array('1'=>array('receiveStatus'=>true,'url'=>)),msg=>'tips')
     */
    public function coupon_status($cpns_id){
        if( !$cpns_id ){
            echo json_encode(array('status'=>'fail','coupon'=>array(),'msg'=>'参数异常'));
            exit();
        }
        $cpns_id = explode(',',$cpns_id);

        $data = array();
        $filter = array('cpns_id'=>$cpns_id);

        $data = kernel::single('wap_widgets_coupons')->getPromotionCoupons($filter);

        echo json_encode(array('status'=>'success','coupon'=>$data,'msg'=>''));exit();
    }

    function afterlist($nPage=1){
        $nPage =intval($nPage);
        $this->path[] = array('title'=>app::get('b2c')->_('会员中心'),'link'=>$this->gen_url(array('app'=>'b2c', 'ctl'=>'wap_member', 'act'=>'index','full'=>1)));
        $GLOBALS['runtime']['path'] = $this->path;
        $obj_return_policy = kernel::service("aftersales.return_policy");
        $arr_settings = array();
        if (!isset($obj_return_policy) || !is_object($obj_return_policy))
        {
            $this->afterlist_msg('fail','售后服务应用不存在！',$url='');
            return '';
        }

        if (!$obj_return_policy->get_conf_data($arr_settings))
        {
            $this->afterlist_msg('fail','售后服务信息没有取到！',$url='');
            return '';
        }
        $order = $this->app->model('orders');
        $order_status['pay_status'] = 1;
        $order_status['ship_status'] = array(1,2,3);
        $order_status['status'] ='active';
        $aData = $order->fetchByMember($this->app->member_id,$nPage,$order_status);
        $this->get_order_details($aData,'member_orders');
        $oImage = app::get('image')->model('image');
        $oGoods = app::get('b2c')->model('goods');
        $imageDefault = app::get('image')->getConf('image.set');
        foreach($aData['data'] as $k => &$v) {
            foreach($v['goods_items'] as $k2 => &$v2) {
                $spec_desc_goods = $oGoods->getList('spec_desc,image_default_id',array('goods_id'=>$v2['product']['goods_id']));
                if($v2['product']['products']['spec_desc']['spec_private_value_id']){
                    $select_spec_private_value_id = reset($v2['product']['products']['spec_desc']['spec_private_value_id']);
                    $spec_desc_goods = reset($spec_desc_goods[0]['spec_desc']);
                }
                if($spec_desc_goods[$select_spec_private_value_id]['spec_goods_images']){
                    list($default_product_image) = explode(',', $spec_desc_goods[$select_spec_private_value_id]['spec_goods_images']);
                    $v2['product']['thumbnail_pic'] = $default_product_image;
                }elseif($spec_desc_goods[0]['image_default_id']){
                    if( !$v2['product']['thumbnail_pic'] && !$oImage->getList("image_id",array('image_id'=>$spec_desc_goods[0]['image_default_id']))){
                        $v2['product']['thumbnail_pic'] = $imageDefault['S']['default_image'];
                    }else{
                        $v2['product']['thumbnail_pic'] = $spec_desc_goods[0]['image_default_id'];
                    }
                }
            }
            $v['is_afterrec'] = $obj_return_policy->is_order_aftersales($v['order_id']);
        }
        $this->pagedata['orders'] = $aData['data'];

        $arr_args = array();
        $this->pagination($nPage,$aData['pager']['total'],'afterlist',$arr_args);
        $this->page('wap/afterlist/afterlist.html');

    }

    public function add_aftersales($order_id)
    {
        $this->path[] = array('title'=>app::get('b2c')->_('会员中心'),'link'=>$this->gen_url(array('app'=>'b2c', 'ctl'=>'wap_member', 'act'=>'index','full'=>1)));
        $GLOBALS['runtime']['path'] = $this->path;
        $obj_return_policy = kernel::service("aftersales.return_policy");
        $arr_settings = array();

        if (!isset($obj_return_policy) || !is_object($obj_return_policy))
        {
            $this->afterlist_msg('fail','售后服务应用不存在！',$url='');
            return '';
        }

        if (!$obj_return_policy->get_conf_data($arr_settings))
        {
            $this->afterlist_msg('fail','售后服务信息没有取到！',$url='');
            return '';
        }

        if(!$obj_return_policy->is_order_aftersales($order_id)){
            $this->afterlist_msg('fail','该订单您已经申请过退换货，无退换商品',$url='');
            return '';
        }

        $products = app::get('b2c')->model('products');
        $this->pagedata['order_id'] = $order_id;
        $objOrder =  $this->app->model('orders');
        $subsdf = array('order_objects'=>array('*',array('order_items'=>array('*',array(':products'=>'*')))));
        $this->pagedata['order'] = $objOrder->dump($order_id, '*', $subsdf);

        // 校验订单的会员有效性.
        $is_verified = ($this->_check_verify_member($this->pagedata['order']['member_id'])) ? $this->_check_verify_member($this->pagedata['order']['member_id']) : false;

        // 校验订单的有效性.
        if ($_COOKIE['ST_ShopEx-Order-Buy'] != md5($this->app->getConf('certificate.token').$order_id) && !$is_verified)
        {
            $this->afterlist_msg('fail','订单无效！',$url='');
            return '';
        }

        $this->pagedata['orderlogs'] = $objOrder->getOrderLogList($order_id);

        if(!$this->pagedata['order'])
        {
            $this->afterlist_msg('fail','订单无效！',$url='');
            return '';
        }

        $order_items = array();
        // 所有的goods type 处理的服务的初始化.
        $arr_service_goods_type_obj = array();
        $arr_service_goods_type = kernel::servicelist('order_goodstype_operation');
        foreach ($arr_service_goods_type as $obj_service_goods_type)
        {
            $goods_types = $obj_service_goods_type->get_goods_type();
            $arr_service_goods_type_obj[$goods_types] = $obj_service_goods_type;
        }

        $objMath = kernel::single("ectools_math");
        $oImage = app::get('image')->model('image');
        $oGoods = app::get('b2c')->model('goods');
        $imageDefault = app::get('image')->getConf('image.set');
        foreach ($this->pagedata['order']['order_objects'] as $k=>$arrOdr_object)
        {
            $index = 0;
            $index_adj = 0;
            $index_gift = 0;
            $tmp_array = array();
            if($arrOdr_object['obj_type'] == 'timedbuy'){
                $arrOdr_object['obj_type'] = 'goods';
            }
            if ($arrOdr_object['obj_type'] == 'goods')
            {
                $order_aftersales_products_quantity=$obj_return_policy->order_products_quantity($order_id);
                foreach($arrOdr_object['order_items'] as $key => $item)
                {
                    if ($item['item_type'] == 'product')
                        $item['item_type'] = 'goods';
                    if ($tmp_array = $arr_service_goods_type_obj[$item['item_type']]->get_aftersales_order_info($item)){
                        $tmp_array = (array)$tmp_array;
                        if (!$tmp_array) continue;
                        $product_id = $tmp_array['products']['product_id'];
                        $item['quantity']=$order_aftersales_products_quantity[$product_id];
                        $tmp_array['quantity']=$order_aftersales_products_quantity[$product_id];
                        if(empty($item['quantity'])){
                            continue;
                        }
                        $tmp_array['quantity'] =intval($tmp_array['quantity']);
                        if (!$order_items[$product_id]){
                            $tmp_array['arrNum'] = $this->intArray($tmp_array['quantity']);
                            $order_items[$product_id] = $tmp_array;
                        }else{
                            $order_items[$product_id]['sendnum'] = floatval($objMath->number_plus(array($order_items[$product_id]['sendnum'],$tmp_array['sendnum'])));
                            $order_items[$product_id]['quantity'] = intval(floatval($objMath->number_plus(array($order_items[$product_id]['quantity'],$tmp_array['quantity']))));
                            $order_items[$product_id]['arrNum'] = $this->intArray($order_items[$product_id]['quantity']);
                        }
                        // 货品图片
                        $spec_desc_goods = $oGoods->getList('spec_desc,image_default_id',array('goods_id'=>$item['goods_id']));

                        if($item['products']['spec_desc']['spec_private_value_id']){
                            $select_spec_private_value_id = reset($item['products']['spec_desc']['spec_private_value_id']);
                            $spec_desc_goods = reset($spec_desc_goods[0]['spec_desc']);
                        }
                        if($spec_desc_goods[$select_spec_private_value_id]['spec_goods_images']){
                            list($default_product_image) = explode(',', $spec_desc_goods[$select_spec_private_value_id]['spec_goods_images']);
                            $order_items[$product_id]['thumbnail_pic'] = $default_product_image;
                        }elseif($spec_desc_goods[0]['image_default_id']){
                            if( !$order_items[$product_id]['thumbnail_pic'] && !$oImage->getList("image_id",array('image_id'=>$spec_desc_goods[0]['image_default_id']))){
                                $order_items[$product_id]['thumbnail_pic'] = $imageDefault['S']['default_image'];

                            }else{
                                $order_items[$product_id]['thumbnail_pic'] = $spec_desc_goods[0]['image_default_id'];

                            }
                        }else{
                            $result = $products ->getRow('goods_id,spec_desc',array('product_id'=>$product_id));
                            $default_image=$oGoods->getRow('image_default_id',array('goods_id'=>$result['goods_id']));
                            $order_items[$product_id]['thumbnail_pic'] = $default_image['image_default_id'];
                        }
                    }
                }
            }
            else
            {
                if ($tmp_array = $arr_service_goods_type_obj[$arrOdr_object['obj_type']]->get_aftersales_order_info($arrOdr_object))
                {
                    $tmp_array = (array)$tmp_array;
                    if (!$tmp_array) continue;
                    foreach ($tmp_array as $tmp){
                        if (!$order_items[$tmp['product_id']]){
                            $tmp['arrNum'] = $this->intArray($tmp['quantity']);
                            $order_items[$tmp['product_id']] = $tmp;
                        }else{

                            $order_items[$tmp['product_id']]['sendnum'] = floatval($objMath->number_plus(array($order_items[$tmp['product_id']]['sendnum'],$tmp['sendnum'])));
                            $order_items[$tmp['product_id']]['nums'] = floatval($objMath->number_plus(array($order_items[$tmp['product_id']]['nums'],$tmp['nums'])));
                            $order_items[$tmp['product_id']]['quantity'] = floatval($objMath->number_plus(array($order_items[$tmp['product_id']]['quantity'],$tmp['quantity'])));
                            $order_items[$tmp['product_id']]['arrNum'] = $this->intArray($order_items[$tmp['product_id']]['quantity']);
                        }
                    }
                }
                //$order_items = array_merge($order_items, $tmp_array);
            }
        }

        $this->pagedata['order_id'] = $order_id;
        $this->pagedata['order']['items'] = $order_items;
        $this->pagedata['controller'] = 'afterlist';
        // echo "<pre>";print_r($this->pagedata);exit;
        $this->page('wap/afterlist/afterinfo.html');
    }



    public function return_save()
    {
        $this->path[] = array('title'=>app::get('b2c')->_('会员中心'),'link'=>$this->gen_url(array('app'=>'b2c', 'ctl'=>'wap_member', 'act'=>'index','full'=>1)));
        $GLOBALS['runtime']['path'] = $this->path;
        $obj_return_policy = kernel::service("aftersales.return_policy");
        $arr_settings = array();
        //var_dump($obj_return_policy);

        if (!isset($obj_return_policy) || !is_object($obj_return_policy))
        {
            $this->afterlist_msg('fail','售后服务应用不存在！',$url='');
            return '';
        }

        if (!$obj_return_policy->get_conf_data($arr_settings))
        {
            $this->afterlist_msg('fail','售后服务信息没有取到！',$url='');
            return '';
        }

        if (!$_POST['product_bn'])
        {
            $this->afterlist_msg('fail','您没有选择商品，请先选择商品！',$url='');
            return '';
        }

        if (!$_POST['title'])
        {
            $this->afterlist_msg('fail','请填写退货理由',$url='');
            return '';
        }

        $upload_file = "";
        if ( $_FILES['file']['size'] > 314572800 )
        {
            $this->afterlist_msg('fail','上传文件不能超过300M！',$url='');
            return '';
        }

        if ( $_FILES['file']['name'] != "" )
        {
            $type=array("png","jpg","gif","jpeg","rar","zip");

            if(!in_array(strtolower($this->fileext($_FILES['file']['name'])), $type))
            {
                $text = implode(",", $type);
                $this->end(false, app::get('aftersales')->_("您只能上传以下类型文件: ") . $text . "<br>", $com_url,false,$_POST['response_json']);
                $this->ajax_callback('error',app::get('aftersales')->_("您只能上传以下类型文件: ") . $text . "<br>");
            }

            $mdl_img = app::get('image')->model('image');
            $image_name = $_FILES['file']['name'];
            $image_id = $mdl_img->store($_FILES['file']['tmp_name'],null,null,$image_name);
            $mdl_img->rebuild($image_id,array('L','M','S'));

            if (isset($_REQUEST['type']))
            {
                $type = $_REQUEST['type'];
            }
            else
            {
                $type = 's';
            }
            $image_src = base_storager::image_path($image_id, $type);
        }

        if(!$_POST['agree']){
            $this->afterlist_msg('fail','请先查看售后服务须知并且同意',$url='');
            return '';
        }

        $obj_filter = kernel::single('b2c_site_filter');
        $_POST = $obj_filter->check_input($_POST);

        $product_data = array();
        $order_products_quantity=$obj_return_policy->order_products_quantity($_POST['order_id']);
        foreach ((array)$_POST['product_bn'] as $key => $val)
        {
            $item = array();
            $item['bn'] = $val;
            $item['name'] = $_POST['product_name'][$key];
            $item['num'] = intval($_POST['product_nums'][$key]);
            $item['price'] = floatval($_POST['product_price'][$key]);
            $item['product_id'] = intval($key);
            if($order_products_quantity[$key]<$item['num']){
                $is_aftersales_status='product_num_error';
            }
            $product_data[] = $item;
        }

        if(!empty($is_aftersales_status)){
            $this->afterlist_msg('fail','您申请退换货的物品大于可退货换数量',$url='');
            return '';
        }


        $aData['order_id'] = $_POST['order_id'];
        $aData['title'] = $_POST['title'];
        $aData['type'] = $_POST['type']==2 ? 2 : 1;
        $aData['add_time'] = time();
        $aData['image_file'] = $image_id;
        $aData['member_id'] = $this->app->member_id;
        $aData['product_data'] = serialize($product_data);
        $aData['content'] = $_POST['content'];
        $aData['status'] = 2;
        $msg = "";
        $obj_aftersales = kernel::service("api.aftersales.request");
        if ($obj_aftersales && $obj_aftersales->generate($aData, $msg))
        {
            $obj_rpc_request_service = kernel::service('b2c.rpc.send.request');
            if ($obj_rpc_request_service && method_exists($obj_rpc_request_service, 'rpc_caller_request'))
            {
                if ($obj_rpc_request_service instanceof b2c_api_rpc_request_interface)
                    $obj_rpc_request_service->rpc_caller_request($aData,'aftersales');
            }
            else
            {
                $obj_aftersales->rpc_caller_request($aData);
            }
            $this->afterlist_msg('success','提交成功',$url='');
            return '';
        }
        else
        {
            $this->afterlist_msg('fail','error',$url='');
            return '';
        }
    }




    function afterrec($type='noarchive', $nPage=1){
        $this->path[] = array('title'=>app::get('b2c')->_('会员中心'),'link'=>$this->gen_url(array('app'=>'b2c', 'ctl'=>'wap_member', 'act'=>'index','full'=>1)));
        $GLOBALS['runtime']['path'] = $this->path;
        $obj_return_policy = kernel::service("aftersales.return_policy");
        $arr_settings = array();

        $filter = array();
        $filter["member_id"] =$this->app->member_id;
        $obj_return_policy = kernel::service("aftersales.return_policy");
        $arr_settings = array();
        if (!isset($obj_return_policy) || !is_object($obj_return_policy))
        {
            $this->afterlist_msg('fail','售后服务应用不存在！',$url='');
            return '';
        }

        if (!$obj_return_policy->get_conf_data($arr_settings))
        {
            $this->afterlist_msg('fail','售后服务信息没有取到！',$url='');
            return '';
        }


        if($type == 'archive'){
            $this->pagedata['type']='archive';
            $aData = $this->get_return_product_list('*', $filter, $nPage);
        }else{
            $this->pagedata['type']='noarchive';
            $aData = $obj_return_policy->get_return_product_list('*', $filter, $nPage);
        }

        $oImage = app::get('image')->model('image');
        $oGoods = app::get('b2c')->model('goods');
        $products = app::get('b2c')->model('products');
        $imageDefault = app::get('image')->getConf('image.set');

        foreach($aData['data'] as $key=>$val){
            $aData['data'][$key]['product_data'] = unserialize($val['product_data']);
            foreach($aData['data'][$key]['product_data'] as $gkey => $gval ){
                $result = $products ->getRow('goods_id,spec_desc',array('bn'=>$gval['bn']));
                if($result['spec_desc']['spec_private_value_id']){
                    $select_spec_private_value_id = reset($result['spec_desc']['spec_private_value_id']);
                    $spec_desc_goods = reset($result['spec_desc']);
                }
                if($spec_desc_goods[$select_spec_private_value_id]['spec_goods_images']){
                    list($default_product_image) = explode(',', $spec_desc_goods[$select_spec_private_value_id]['spec_goods_images']);
                    $aData['data'][$key]['product_data'][$gkey]['thumbnail_pic'] = $default_product_image;
                }elseif($spec_desc_goods[0]['image_default_id']){
                    if(!$oImage->getList("image_id",array('image_id'=>$spec_desc_goods[0]['image_default_id']))){
                        $aData['data'][$key]['product_data'][$gkey]['thumbnail_pic'] = $imageDefault['S']['default_image'];
                    }else{
                        $aData['data'][$key]['product_data'][$gkey]['thumbnail_pic'] = $spec_desc_goods[0]['image_default_id'];
                    }
                }else{
                    $default_image=$oGoods->getRow('image_default_id',array('goods_id'=>$result['goods_id']));
                    $aData['data'][$key]['product_data'][$gkey]['thumbnail_pic'] = $default_image['image_default_id'];

                }
                $aData['data'][$key]['product_data'][$gkey]['link_url']= $this->gen_url(array('app'=>'b2c','ctl'=>'wap_product','act'=>'index','arg0'=>$aData['data'][$key]['product_data']['0']['product_id']));
            }
            $aData['data'][$key]['comment'] = unserialize($val['comment']);
        }



        if (isset($aData['data']) && $aData['data'])
        {
            $this->pagedata['return_list'] = $aData['data'];
        }


        $imageDefault = app::get('image')->getConf('image.set');
        $this->pagedata['defaultImage'] = $imageDefault['S']['default_image'];
        $arrPager = $this->get_start($nPage, $aData['total']);
        $this->pagination($nPage,$aData['pager']['total'],'afterrec',$arr_args);
        $this->pagedata['controller'] = 'afterrec';
        //获取物流公司表数据
        $mdl_b2c_dlycorp = $this->app->model("dlycorp");
        $this->pagedata["dlycorps"] = $mdl_b2c_dlycorp->getList();
        
        $this->page('wap/afterlist/afterrec.html');

    }



    function afterrec_info($return_order_id){
        $this->path[] = array('title'=>app::get('b2c')->_('会员中心'),'link'=>$this->gen_url(array('app'=>'b2c', 'ctl'=>'wap_member', 'act'=>'index','full'=>1)));
        $GLOBALS['runtime']['path'] = $this->path;
        $obj_return_policy = kernel::service("aftersales.return_policy");
        $arr_settings = array();

        $filter = array();
        $filter["member_id"] =$this->app->member_id;
        $obj_return_policy = kernel::service("aftersales.return_policy");
        $arr_settings = array();
        if (!isset($obj_return_policy) || !is_object($obj_return_policy))
        {
            $this->afterlist_msg('fail','售后服务应用不存在！',$url='');
            return '';
        }

        if (!$obj_return_policy->get_conf_data($arr_settings))
        {
            $this->afterlist_msg('fail','售后服务信息没有取到！',$url='');
            return '';
        }
        $filter['return_id'] = $return_order_id;

        if($type == 'archive'){
            $this->pagedata['type']='archive';
            $aData = $this->get_return_product_list('*', $filter, $nPage);
        }else{
            $this->pagedata['type']='noarchive';
            $aData = $obj_return_policy->get_return_product_list('*', $filter, $nPage);
        }

        $oImage = app::get('image')->model('image');
        $oGoods = app::get('b2c')->model('goods');
        $products = app::get('b2c')->model('products');
        $imageDefault = app::get('image')->getConf('image.set');

        if (isset($aData['data']) && $aData['data'])
        {
            foreach($aData['data'] as $key=>$val){
                $aData['data'][$key]['product_data'] = unserialize($val['product_data']);
                foreach($aData['data'][$key]['product_data'] as $gkey => $gval ){
                    $result = $products ->getRow('goods_id,spec_desc',array('bn'=>$gval['bn']));
                    if($result['spec_desc']['spec_private_value_id']){
                        $select_spec_private_value_id = reset($result['spec_desc']['spec_private_value_id']);
                        $spec_desc_goods = reset($result['spec_desc']);
                    }

                    if($spec_desc_goods[$select_spec_private_value_id]['spec_goods_images']){
                        list($default_product_image) = explode(',', $spec_desc_goods[$select_spec_private_value_id]['spec_goods_images']);
                        $aData['data'][$key]['product_data'][$gkey]['thumbnail_pic'] = $default_product_image;
                    }elseif($spec_desc_goods[0]['image_default_id']){
                        if(!$oImage->getList("image_id",array('image_id'=>$spec_desc_goods[0]['image_default_id']))){
                            $aData['data'][$key]['product_data'][$gkey]['thumbnail_pic'] = $imageDefault['S']['default_image'];
                        }else{
                            $aData['data'][$key]['product_data'][$gkey]['thumbnail_pic'] = $spec_desc_goods[0]['image_default_id'];
                        }
                    }else{
                        $default_image=$oGoods->getRow('image_default_id',array('goods_id'=>$result['goods_id']));
                        $aData['data'][$key]['product_data'][$gkey]['thumbnail_pic'] = $default_image['image_default_id'];

                    }
                    $aData['data'][$key]['product_data'][$gkey]['link_url']= $this->gen_url(array('app'=>'b2c','ctl'=>'wap_product','act'=>'index','arg0'=>$aData['data'][$key]['product_data']['0']['product_id']));
                }
                $aData['data'][$key]['comment'] = unserialize($val['comment']);
            }
            $this->pagedata['order'] = $aData['data'][0];
        }

        if($this->pagedata['order']["customer_logistics"]){
            //获取顾客前台填写的物流信息
            $mdl_aftersales_return_product = app::get('aftersales')->model('return_product');
            $this->pagedata['customer_logistics_text'] = $mdl_aftersales_return_product->customer_logistics_text($this->pagedata['order']["customer_logistics"]);
        }
        
        $imageDefault = app::get('image')->getConf('image.set');
        $this->pagedata['defaultImage'] = $imageDefault['S']['default_image'];
        $arrPager = $this->get_start($nPage, $aData['total']);
        $this->pagination($nPage,$aData['pager']['total'],'afterrec',$arr_args);
        $this->pagedata['controller'] = 'afterrec';
        $this->page('wap/afterlist/afterinfree.html');

    }

    public  function read(){
        $this->pagedata['comment'] = app::get('aftersales')->getConf('site.return_product_comment');
        $this->page('wap/afterlist/afterpact.html');

    }

    private function afterlist_msg($status,$msg,$url=''){
        if(empty($url)){
            $url = $this->gen_url(array('app'=>'b2c', 'ctl'=>'wap_member', 'act'=>'index','full'=>1));
        }
        $this->pagedata['status'] =$status; 
        $this->pagedata['msg'] =$msg;
        $this->pagedata['url'] =$url;
        $this->page('wap/afterlist/afterwin.html');
    }


    private function intArray($int=1){
        for($i=1;$i<=$int;$i++){
            $return[$i] = $i;
        }
        return $return;
    }

    //积分兑换优惠券页面展示
    function coupon_exchange() {
        $member_center_url = $this->gen_url(array('app'=>'b2c', 'ctl'=>'wap_member', 'act'=>'index','full'=>1));
        $this->path[] = array('title'=>app::get('b2c')->_('会员中心'),'link'=>$member_center_url);
        $this->path[] = array('title'=>app::get('b2c')->_('积分兑换优惠卷'),'link'=>'#');
        $GLOBALS['runtime']['path'] = $this->path;
        //获取兑换券列表
        $oExchangeCoupon = kernel::single('b2c_coupon_mem');
        $site_point_usage = $this->app->getConf('site.point_usage');
        $this->pagedata['site_point_usage'] = ($site_point_usage == '1') ? true : false;
        if ($aExchange = $oExchangeCoupon->get_list()) {
            foreach ($aExchange as $k => $item) {
                $member_lvs = explode(',',$item['time']['member_lv_ids']);
                if (!in_array($this->member['member_lv'],(array)$member_lvs)) {
                    unset($aExchange[$k]);
                    continue;
                }
            }
            $this->pagedata['couponList'] = $aExchange;
        }
        $this->pagedata['member_center_url'] = $member_center_url;
        $this->page('wap/member/coupon_exchange.html');
    }
    
    //积分兑换优惠券 兑换操作
    function exchange() {
        $cpnsId = $_POST["cpns_id"];
        $arr_json_data = array("success"=>false);
        $coupon_exchange_url = $this->gen_url(array('app'=>'b2c','ctl'=>'wap_member','act'=>'coupon_exchange'));
        //积分设置的用途
        $site_point_usage = app::get('b2c')->getConf('site.point_usage');
        if($site_point_usage != '1'){
            $arr_json_data["message"] = '积分只用于抵扣，不能兑换';
            echo json_encode($arr_json_data);exit;
        }
        if(!$cpnsId){
            $arr_json_data["message"] = '参数错误';
            echo json_encode($arr_json_data);exit;
        }
        $oExchangeCoupon = kernel::single('b2c_coupon_mem');
        $memberId = intval($this->app->member_id);//会员id号
        if($memberId){
            $cur_coupon_nums = $this->app->model('member_coupon')->count(array('cpns_id'=>$cpnsId,'member_id'=>$memberId,'source'=>1));
            $coupons = $this->app->model('coupons');
            $cur_coupon = $coupons->dump($cpnsId);
            if($cur_coupon['cpns_max_num'] > 0 ){  //兼容老数据处理老数据还是无限制兑换
                if($cur_coupon_nums >= $cur_coupon['cpns_max_num']){
                    $arr_json_data["message"] = '您的兑换次数已达上限！';
                    echo json_encode($arr_json_data);exit;
                }
            }
            
            $obj_point_common = kernel::single('pointprofessional_point_common');
            $real_point_get = $obj_point_common->check_used_point_get($memberId);
            if (!$real_point_get["rs"]){
                $arr_json_data["message"] = '获取可用积分失败！';
                echo json_encode($arr_json_data);exit;
            }
            
            if($real_point_get['real_point'] < $cur_coupon['cpns_point']){
                $arr_json_data["message"] = '您的积分不足！';
                echo json_encode($arr_json_data);exit;
            }
            
            if ($oExchangeCoupon->exchange($cpnsId,$memberId,$real_point_get['real_point'],$params)){
                $cpns_point = $params['cpns_point'];
                $member_point = $this->app->model('member_point');
                if($obj_point_common->point_change_action($this->member['member_id'],-$cpns_point,$msg,'exchange_coupon',2,$memberId,$memberId,'exchange',false)){
                    $change_nums = $cur_coupon['cpns_max_num'] - $cur_coupon_nums -1;
                    $coupon_url = $this->gen_url(array('app'=>'b2c','ctl'=>'wap_member','act'=>'coupon'));
                    if($cur_coupon['cpns_max_num'] > 0 ){
                        $arr_json_data["message"] = '兑换成功,您还可以兑换'.$change_nums.'张';
                    }else{
                        $arr_json_data["message"] = '兑换成功';
                    }
                    $arr_json_data['success'] = true;
                    echo json_encode($arr_json_data);exit;
                }else{
                    $oExchangeCoupon->exchange_delete($params);
                    $arr_json_data["message"] = $msg;
                    echo json_encode($arr_json_data);exit;
                }
            }
        }else{
            $arr_json_data["message"] = '没有登录';
            echo json_encode($arr_json_data);exit;
        }
        $arr_json_data["message"] = '积分不足或兑换购物券无效';
        echo json_encode($arr_json_data);exit;
    }
    
    //申请退款列表
    public function refundlist($nPage=1){
        $mdl_b2c_refund_apply = $this->app->model('refund_apply');
        $aData = $mdl_b2c_refund_apply->fetchByMember($this->app->member_id,$nPage);
        //统一获取相关的order_id 获得订单相关信息
        $arr_order_ids = array();
        foreach ($aData["data"] as $var_data){
            if(!in_array($var_data["order_id"],$arr_order_ids)){
                $arr_order_ids[] = $var_data["order_id"];
            }
        }
        $mdl_b2c_orders = $this->app->model('orders');
        $rs_orders = $mdl_b2c_orders->getList('*', array("order_id|in"=>$arr_order_ids));
        $subsdf = array('order_objects'=>array('*',array('order_items'=>array('*',array(':products'=>'*')))));
        foreach ($rs_orders as &$arr_order){
            $arr_order = $mdl_b2c_orders->dump($arr_order['order_id'], '*', $subsdf);
        }
        $orders["data"] = $rs_orders;
        $this->get_order_details($orders,'member_orders');
        $oImage = app::get('image')->model('image');
        $oGoods = app::get('b2c')->model('goods');
        $imageDefault = app::get('image')->getConf('image.set');
        foreach($orders['data'] as $k => &$v) {
            $order_payed = kernel::single('b2c_order_pay')->check_payed($v['order_id']);
            if($order_payed==$v['total_amount']){
                $v['is_pay']=1;
            }else{
                $v['is_pay']=0;
            }
            foreach($v['goods_items'] as $k2 => &$v2) {
                $spec_desc_goods = $oGoods->getList('spec_desc',array('goods_id'=>$v2['product']['goods_id']));
                $select_spec_private_value_id = reset($v2['product']['products']['spec_desc']['spec_private_value_id']);
                $spec_desc_goods = reset($spec_desc_goods[0]['spec_desc']);
                if($spec_desc_goods[$select_spec_private_value_id]['spec_goods_images']){
                    list($default_product_image) = explode(',', $spec_desc_goods[$select_spec_private_value_id]['spec_goods_images']);
                    $v2['product']['thumbnail_pic'] = $default_product_image;
                }else{
                    if( !$v2['product']['thumbnail_pic'] && !$oImage->getList("image_id",array('image_id'=>$v['image_default_id']))){
                        $v2['product']['thumbnail_pic'] = $imageDefault['S']['default_image'];
                    }
                }
            }
        }
        //做个order_id为key的关系数组
        $rl_order_id_order_info = array();
        foreach ($orders["data"] as $var_order){
            $rl_order_id_order_info[$var_order["order_id"]] = $var_order;
        }
        //压入要order_info用来显示在整体信息下方
        foreach ($aData['data'] as &$var_adata){
            $var_adata["order_info"] = $rl_order_id_order_info[$var_adata["order_id"]];
        }
        unset($var_adata);
        $this->pagedata['refund'] = $aData['data'];
        $this->pagination($nPage,$aData['pager']['total'],'refundlist');
        $this->page('wap/member/refundlist.html');
    }
    
    /*
     * 申请退款详细页
     * @params int $refund_apply_bn 退款申请单号
     * */
    function refund_detail($refund_apply_bn){
        if (!$refund_apply_bn){
            $this->begin(array('app' => 'b2c','ctl' => 'wap_member', 'act'=>'index'));
            $this->end(false, app::get('b2c')->_('退款申请单号不能为空！'));
        }
        //获取退款申请信息
        $mdl_b2c_refund_apply = $this->app->model('refund_apply');
        $this->pagedata["refund_apply_info"] = $mdl_b2c_refund_apply->dump(array("refund_apply_bn"=>$refund_apply_bn));
        //获取refunds_reason_text
        $this->pagedata["refund_apply_info"]["refunds_reason"] = $mdl_b2c_refund_apply->get_refunds_reason_text($this->pagedata["refund_apply_info"]["refunds_reason"]);
        //获取退款申请日志
        $mdl_b2c_order_log = $this->app->model('order_log');
        $this->pagedata["refund_apply_log"] = $mdl_b2c_order_log->getList("*",array("rel_id"=>$refund_apply_bn,"bill_type"=>"refund_apply","behavior"=>"refunds"),0,-1,'log_id asc');
        $this->page('wap/member/refund_detail.html');
    }
    
    //售前申请退款操作
    public function do_refund_apply(){
        //默认不成功
        $arr_json_data = array(
                "success" => false,
        );
        $order_id = $_POST["order_id"];
        if (!$order_id){
            $arr_json_data["err_msg"] = "订单id不存在";
            echo json_encode($arr_json_data);exit;
        }
        $refund_apply_reason = $_POST["refund_apply_reason"];
        if(!$refund_apply_reason){
            $arr_json_data["err_msg"] = "请选择退款理由";
            echo json_encode($arr_json_data);exit;
        }
        //检查订单是否符合要求：未发货 已支付
        $mdl_order = $this->app->model('orders');
        $order_info = $mdl_order->dump(array("order_id"=>$order_id),"pay_status,ship_status,member_id,final_amount");
        if(empty($order_info)){
            $arr_json_data["err_msg"] = "此订单不存在";
            echo json_encode($arr_json_data);exit;
        }
        if($order_info["cur_amount"] == 0){
            $arr_json_data["err_msg"] = "0元订单不能申请退款申请";
            echo json_encode($arr_json_data);exit;
        }
        //是否有售后申请记录
        $mdl_aftersales_return_product = app::get('aftersales')->model('return_product');
        $rs_aftersales_return_product = $mdl_aftersales_return_product->dump(array("order_id"=>$order_id));
        //判断是否有未操作过的此订单的退款申请记录
        $mdl_b2c_refund_apply = app::get('b2c')->model('refund_apply');
        $rs_refund_apply = $mdl_b2c_refund_apply->dump(array("order_id"=>$order_id,"status"=>"0"));
        //满足已支付 未发货 没有售后退换货记录的 做售前退款操作
        if($order_info["pay_status"] == "1" && $order_info["ship_status"] == "0" && empty($rs_aftersales_return_product) && empty($rs_refund_apply)){
            $refund_apply_bn = date("YmdHis",time()).str_pad(rand(1,999),4,'0',STR_PAD_LEFT);
            $current_time = time();
            $request_arr = array(
                    "order_id" => $order_id,
                    "member_id" => $order_info["member_id"],
                    "refund_apply_bn" => $refund_apply_bn,
                    "refunds_reason" => $refund_apply_reason,
                    "money" => $order_info["cur_amount"],
                    "current_time" => $current_time,
            );
            //如绑定把退款申请单打到oms
            $obj_apiv = kernel::single('b2c_apiv_exchanges_request');
            $obj_apiv->rpc_caller_request($request_arr, 'orderrefund');
            //生成售前退款记录
            $insert_arr = array(
                    "refund_apply_bn" => $refund_apply_bn,
                    "order_id" => $order_id,
                    "member_id" => $order_info["member_id"],
                    "money" => $order_info["cur_amount"],
                    "refunds_reason" => $refund_apply_reason,
                    "create_time" => $current_time,
                    "last_modified" => $current_time,
            );
            if($mdl_b2c_refund_apply->insert($insert_arr)){
                //更新订单支付状态为退款申请中
                $mdl_order->update(array('pay_status'=>'6'),array('order_id'=>$order_id));
                $msg = "退款申请成功";
                $arr_json_data["success"] = true;
                $arr_json_data["suc_msg"] = $msg;
            }else{
                $msg = "退款申请失败";
                $arr_json_data["err_msg"] = $msg;
            }
            $mdl_b2c_refund_apply->saveOrderLog($refund_apply_bn,$msg);
            echo json_encode($arr_json_data);exit;
        }else{
            $arr_json_data["err_msg"] = "不满足售前退款的条件";
            echo json_encode($arr_json_data);exit;
        }
    }

    //售后前台退换货申请通过审核后保存填写的物流信息
    public function save_logistics_info(){
        //默认不成功
        $arr_json_data = array(
            "success" => false,
        );
        if (!$_POST["logistics_company_name"]){
            $arr_json_data["err_msg"] = "请选择物流公司";
            echo json_encode($arr_json_data);exit;
        }
        if (!$_POST["logistics_no"]){
            $arr_json_data["err_msg"] = "请填写物流单号";
            echo json_encode($arr_json_data);exit;
        }
        
        if (!$_POST["logistics_mobile"]){
            $arr_json_data["err_msg"] = "请填写收货手机号";
            echo json_encode($arr_json_data);exit;
            exit;
        }
        if (!$_POST["logistics_address"]){
            $arr_json_data["err_msg"] = "请填写收货地址";
            echo json_encode($arr_json_data);exit;
        }
        
        if ($_POST["afterrec_type"] == "archive"){
            $mdl_aftersales_return_product = app::get('aftersales')->model('archive_return_product');
        }else{
            $mdl_aftersales_return_product = app::get('aftersales')->model('return_product');
        }
        
        $return_info = $mdl_aftersales_return_product->dump(array("return_id"=>$_POST["return_id"]));
        if (empty($return_info)){
            $arr_json_data["err_msg"] = "不存在此退换货数据";
            echo json_encode($arr_json_data);exit;
        }
        
        //请求接口处理
        $api_data = array(
                "return_id" => $_POST['return_id'],
                "order_id" => $_POST['order_id'],
                "logistics_company_name" => $_POST['logistics_company_name'],
                "logistics_no" => $_POST['logistics_no']
        );
        $obj_apiv = kernel::single('b2c_apiv_exchanges_request');
        $obj_apiv->rpc_caller_request($api_data, 'aftersalelogisticsupdate');
        
        //本地保存填写的信息 用于后台展示
        $arr_customer_logistics = array(
                "物流公司" => $_POST["logistics_company_name"],
                "物流单号" => $_POST["logistics_no"],
                "收货手机号" => $_POST["logistics_mobile"],
                "收货地址" => $_POST["logistics_address"],
        );
        $result = $mdl_aftersales_return_product->update(array("customer_logistics"=>json_encode($arr_customer_logistics)),array("return_id"=>$_POST["return_id"]));
        if($result){
            $arr_json_data["success"] = true;
            $arr_json_data["suc_msg"] = "保存成功";
            echo json_encode($arr_json_data);exit;
        }else{
            $arr_json_data["err_msg"] = "保存失败";
            echo json_encode($arr_json_data);exit;
        }
    }

    /**
     * @Author: panbiao <panbiaophp@163.com>
     * @DateTime: 2019-07-01 14:20
     * @Desc: java 福利信息
     */
    function condolences($status="",$grant_name="")
    {
        $this->pagedata['status'] = $status;
        $this->pagedata['grant_name'] = $grant_name;
        $tmp = SFSC_HttpClient::getJavaCondolences($this->member['uname'],$status,$grant_name);

        $imageDefault = app::get('image')->getConf('image.set');

        if(!empty($tmp))
        {
            $condolences = $tmp['RESULT_DATA'];
            $db=kernel::database();
            foreach($condolences as $key=>$val)
            {
                $sql="select a.type_id,b.image_default_id,b.name from ".DB_PREFIX ."cardcoupons_cards as a join ".DB_PREFIX ."b2c_goods as b on a.goods_id =b.goods_id where b.goods_id='".$val['PRODUCT_ID']."'";
                $info=$db->selectRow($sql);
                $condolences[$key]['type_id'] = $info['type_id'];
                $condolences[$key]['name'] = $info['name'];
                $condolences[$key]['image_default_id'] = $info['image_default_id']?$info['image_default_id']:$imageDefault['S']['default_image'];
                if($val['OVERDUE_TIME'])
                {
                    $condolences[$key]['OVERDUE_TIME'] = substr($val['OVERDUE_TIME'], 0,10);
                }
            }
        }else{
            $condolences = '';
        }
        $this->pagedata['res_url'] = $this->app->res_url;
        $this->pagedata['condolences'] = $condolences;
        $this->page('wap/member/condolences.html');
    }


    /**
     * @Author: panbiao <panbiaophp@163.com>
     * @DateTime: 2019-07-01 20:36
     * @Desc: 站内信-收件箱
     */
    function inbox($nPage=1)
    {
        $this->get_msg_num();
        $oMsg = kernel::single('b2c_message_msg');
        $row = $oMsg->getList('*',array('to_id' => $this->app->member_id,'has_sent' => 'true','for_comment_id' => 'all','inbox' => 'true','mem_read_status' => 'false'));
        $this->pagedata['inbox_num'] = count($row)?count($row):0;

        $row = $oMsg->getList('*',array('to_id' => $this->app->member_id,'has_sent' => 'true','for_comment_id' => 'all','inbox' => 'true'));
        $aData['data'] = $row;
        $aData['total'] = count($row);
        $count = count($row);
        $aPage = $this->get_start($nPage,$count);
        $params['data'] = $oMsg->getList('*',array('to_id' => $this->app->member_id,'has_sent' => 'true','for_comment_id' => 'all','inbox' =>'true'),$aPage['start'],$this->pagesize);
        $params['page'] = $aPage['maxPage'];
        $this->pagedata['message'] = $params['data'];
        $this->pagedata['total_msg'] = $aData['total'];
        $this->pagination($nPage,$params['page'],'inbox');
        $this->page('wap/member/inbox.html');
    }

    /**
     * @Author: panbiao <panbiaophp@163.com>
     * @DateTime: 2019-07-01 20:45
     * @Desc: 站内信-草稿箱
     */
    function outbox($nPage=1) {
        $this->get_msg_num();
        $oMsg = kernel::single('b2c_message_msg');
        $row = $oMsg->getList('*',array('has_sent' => 'false','author_id' => $this->app->member_id));
        $aData['data'] = $row;
        $aData['total'] = count($row);
        $count = count($row);
        $aPage = $this->get_start($nPage,$count);
        $params['data'] = $oMsg->getList('*',array('has_sent' => 'false','author_id' => $this->app->member_id),$aPage['start'],$this->pagesize);
        $params['page'] = $aPage['maxPage'];
        $this->pagedata['message'] = $params['data'];
        $this->pagedata['total_msg'] = $aData['total'];
        $this->pagination($nPage,$params['page'],'outbox');
        $this->pagedata['controller'] = "inbox";
        $this->page('wap/member/outbox.html');
    }

    /**
     * @Author: panbiao <panbiaophp@163.com>
     * @DateTime: 2019-07-01 20:45
     * @Desc: 站内信-已发送
     */
    function track($nPage=1)
    {
        $this->get_msg_num();
        $oMsg = kernel::single('b2c_message_msg');
        $row = $oMsg->getList('*',array('author_id' => $this->app->member_id,'has_sent' => 'true','track' => 'true'));
        $aData['data'] = $row;
        $aData['total'] = count($row);
        $count = count($row);
        $aPage = $this->get_start($nPage,$count);
        $params['data'] = $oMsg->getList('*',array('author_id' => $this->app->member_id,'has_sent' => 'true','track' => 'true'),$aPage['start'],$this->pagesize);
        $params['page'] = $aPage['maxPage'];
        $this->pagedata['message'] = $params['data'];
        $this->pagedata['total_msg'] = $aData['total'];
        $this->pagination($nPage,$params['page'],'track');
        $this->pagedata['controller'] = "inbox";
        $this->page('wap/member/track.html');
    }
    /**
     * @Author: panbiao <panbiaophp@163.com>
     * @DateTime: 2019-07-01 20:50
     * @Desc: 站内信-已发送
     */
    function view_msg()
    {
        $nMsgId = $_POST['comment_id'];
        $objMsg = kernel::single('b2c_message_msg');
        $aMsg = $objMsg->getList('comment',array('comment_id' => $nMsgId,'for_comment_id' => 'all','to_id'=>$this->app->member_id));
        if($aMsg[0]&&($aMsg[0]['author_id']!=$this->app->member_id&&$aMsg[0]['to_id']!=$this->app->member_id)){
            header('Content-Type:text/html; charset=utf-8');
            echo app::get('b2c')->_('对不起，您没有权限查看这条信息！');exit;
        }
        $objMsg->setReaded($nMsgId);
        $objAjax = kernel::single('b2c_view_ajax');
        echo $objAjax->get_html(htmlspecialchars_decode($aMsg[0]['comment']),'b2c_ctl_site_member','view_msg');
        exit;

    }

    /*
     *获取未读信息数目
     * */
    function get_unreadmsg_num()
    {
        $oMsg = kernel::single('b2c_message_msg');
        $num  = $oMsg->count(array('to_id' => $this->app->member_id,'has_sent' => 'true','inbox' => 'true','mem_read_status' => 'false'));
        $data['inbox_num'] = $num ? $num : 0;
        $this->pagedata['inbox_num'] = $data['inbox_num'];
        // echo json_encode($data);
    }
    /*
     *获取收件箱未读信息数量
     * */
    function get_msg_num(){
        $oMsg = kernel::single('b2c_message_msg');
        $row = $oMsg->getList('*',array('to_id' => $this->app->member_id,'has_sent' => 'true','for_comment_id' => 'all','inbox' => 'true','mem_read_status' => 'false'));
        $this->pagedata['inbox_num'] = count($row)?count($row):0;
        $row = $oMsg->getList('*',array('has_sent' => 'false','author_id' => $this->app->member_id));
        $this->pagedata['outbox_num'] = count($row)?count($row):0;
    }
}
