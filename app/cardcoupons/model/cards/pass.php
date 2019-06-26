<?php

 
class cardcoupons_mdl_cards_pass extends dbeav_model{
	var $defaultOrder = array('createtime','DESC');

    function __construct($app){
        parent::__construct($app);
        $this->use_meta();
        $this->mySqlKeyLib = kernel::single('cardcoupons_mysqlkey');
        // $this->mySqlKeyLib->storeKey();
        // $this->currentKey = $this->mySqlKeyLib->getCurrentMysqlKey();
        // $this->keyHistoryList = $this->mySqlKeyLib->getMysqlKeyList();
    }

   function get_batch_id(){
		$i = rand(0,99999);
        do{
            if(99999==$i){
                $i=0;
            }
            $i++;
            $batch = date('ymdH').str_pad($i,5,'0',STR_PAD_LEFT);
            $row = $this->db->selectrow('SELECT batch from sdb_cardcoupons_cards_pass where batch ='.$batch);
        }while($row);
        return $batch;
	}
	//卡根据库存自动生成卡号，卡密
	/**
	*params card  array(from_time,to_time,card_id,num);
	**/
	function auto_pass($cards,&$msg){
		$pass=array();
		$card_info=kernel::single('cardcoupons_mdl_cards')->dump(array('card_id'=>$cards['card_id']));
		if($card_info){
			$card_service=kernel::single('cardcoupons_mdl_cards_service')->dump(array('card_service_id'=>$card_info['service_id']));
			if($card_service && $card_service['handle']){
				$card_no_like = $card_info['type_id'].$card_service['handle'];
			}else{
				$card_no_like = $card_info['type_id'].$card_info['type_id'];
			}
			$max_pass=$this->db->select("select * from sdb_cardcoupons_cards_pass where card_no like '".$card_no_like."%' order by card_pass_id desc limit 0,1");
			if($max_pass[0]){
				$old_card_no=$max_pass[0]['card_no'];
				$_four=substr($old_card_no,0,4);
				$_eight=substr($old_card_no,4);
			}else{
				$_four=$card_no_like;
				$_eight=0;
			}
			$batch=$this->get_batch_id();
			for($i=1;$i<=$cards['num'];$i++){
				if($cards['is_issued']=='true'){
					$pass[$i]['status'] =1;
				}
				$code=rand(100000,999999);
				$pass[$i]['from_time']=$card_info['from_time'];
				$pass[$i]['to_time']=$card_info['to_time'];
				$pass[$i]['card_no']=$_four.str_pad($_eight+$i,8,'0',STR_PAD_LEFT);
				$pass[$i]['card_pass']=$code;
				$pass[$i]['passset']='auto';
				$pass[$i]['source']=$card_info['source'];
				$pass[$i]['createtime']=time();
				$pass[$i]['batch']=$batch;
				$pass[$i]['card_id']=$cards['card_id'];
				$pass[$i]['card_name']=$card_info['name'];
			}
		}else{
			$msg="卡券不存在";
			return false;
		}
		return $pass;
		
		
	}

    //大订单 客户自动生成卡号卡密方法
    function sfscauto_pass($cards,&$msg){
        $pass=array();
        $card_info=kernel::single('cardcoupons_mdl_cards')->dump(array('card_id'=>$cards['card_id']));
        if($card_info){
            $card_service=kernel::single('cardcoupons_mdl_cards_service')->dump(array('card_service_id'=>$card_info['service_id']));
            if($card_service && $card_service['handle']){
                $card_no_like = $card_info['type_id'].$card_service['handle'];
            }else{
                $card_no_like = $card_info['type_id'].$card_info['type_id'];
            }
            $max_pass=$this->db->select("select * from sdb_cardcoupons_cards_pass where card_no like '".$card_no_like."%' order by card_pass_id desc limit 0,1");
            if($max_pass[0]){
                $old_card_no=$max_pass[0]['card_no'];
                $_four=substr($old_card_no,0,4);
                $_eight=substr($old_card_no,4);
            }else{
                $_four=$card_no_like;
                $_eight=0;
            }

            $batch=$this->get_batch_id();
            for($i=1;$i<=$cards['num'];$i++){

                $code=rand(100000,999999);
                $pass[$i]['from_time']=$card_info['from_time'];
                $pass[$i]['to_time']=$card_info['to_time'];
                $pass[$i]['card_no']=$_four.str_pad($_eight+$i,8,'0',STR_PAD_LEFT);
                $pass[$i]['card_pass']=$code;
                $pass[$i]['passset']='auto';
                $pass[$i]['source']="internal";
                $pass[$i]['createtime']=time();
                $pass[$i]['batch']=$batch;
                $pass[$i]['status']='1';
                $pass[$i]['ex_status'] = 'true';
                $pass[$i]['disabled'] = "false";
                $pass[$i]['is_send'] = "false";
                $pass[$i]['lasttime']=time();
                $pass[$i]['order_id']=$cards['order_id'];
                $pass[$i]['type']='virtual';
                $pass[$i]['card_id']=$cards['card_id'];
                $pass[$i]['card_name']=$card_info['name'];
            }
        }else{
            $msg="卡券不存在";
            return false;
        }
        return $pass;
    }

	//校验卡号和卡密
	function check_pass($pass,&$msg){
		if(empty($pass['card_no'])||empty($pass['card_pass'])){
			$msg='卡号或卡密为空';
			return false;
		}
		$pass_info=$this->dump(array('card_no'=>$pass['card_no']));
		if($pass_info){
			if($pass['card_pass']!=$pass['card_pass']){
				$msg='卡密不正确';
				return false;
			}
		}else{
			$msg='卡号不存在';
			return false;
		}
		return true;
	}
	//处理上传文件的卡密卡号
	function ftp_pass(&$pass_error){
		$pass=$this->read_csv($_FILES['cards'],$pass_error);
		return $pass;
	}
	//读取csv文件
	function read_csv($file,&$pass_error){
		$file_type = substr(strrchr($file['name'],'.'),1);
		
		// 检查文件格式
	   if ($file_type != 'csv'){
			$pass_error= '文件格式不对,请重新上传!';
			return false;
		}
		$handle = fopen($file['tmp_name'],"r");
		/*if ($file_encoding != 'UTF-8'){
			echo '文件编码错误,请重新上传!';
			exit;
		}*/
		
		$row = 0;
		$post=array();
		$key=0;
		$m_key=array();
		setlocale(LC_CTYPE, "zh_CN.GBK");//防止以中文开头时读取的内容为空
		while ($data = fgetcsv($handle,1000,',')){
			$row++;
			if ($row == 1){
				foreach($data as $k_key=>$k_value){
					if($k_value){
						$result = array(); 
						preg_match_all("/(?:\()(.*)(?:\))/i",$k_value, $result);
						$k_value=explode(':',$k_value);
						$m_key[$k_key]=$result[1][0];
					}	
				}
			}else{
				$num = count($data);
				// 这里会依次输出每行当中每个单元格的数据
				foreach($data as $v_key=>$v_value){
					$v_value1 = iconv('GBK','UTF-8',$v_value);
					$post[$key][$m_key[$v_key]]=$v_value1;
				}
			}
		   $key++;
		}
		
		fclose($handle);
		//$post = iconv('GB2312','UTF-8',$post);
		return $post;
	
	}
	//处理手动填写的卡密
	function manual_pass($cards){
		$pass=array();
		foreach((array)$cards['pass'] as $value){
			$value['from_time']=$cards['from_time'];
			$value['to_time']=$cards['to_time'];
			$pass[]=$value;
		}
		return $pass;
	}


    function checkuname($login_name){
        $account_type = "card";
        $obj_pam_account = new pam_account($account_type);
        return $obj_pam_account->is_exists($login_name);
    }

    function register($user_name,$password,$email) {
        //模拟注册数据
        $regtime = time();
        $register_data=array(
            'reg_type' =>'username',
            'pam_account' => array(
                'login_name' =>$user_name,
                'login_password' => $password,
                'psw_confirm' => $password,
                'createtime' =>$regtime,
                'account_type' => 'card'
            ),
            'contact' => array(
                'commonlyemail' => $email,
                'email' => $email,
            ),
            'member_lv' => array(
                'member_group_id' => 1,
            ),
            'currency'=>'CNY',
            'reg_ip'=>'127.0.0.1',
            'regtime'=>$regtime,
            'is_subscibe'=>'true',
            'license'=>'agree',
        );
        //调用保存方法
        $member_id = $this->add($register_data);
        if($member_id){
            return true;
        }
        return false;
    }

    function add($register_data){
        $sfsc_post=$register_data;
        foreach($sfsc_post as $key=>$val){
            if(strpos($key,"box:") !== false){
                $aTmp = explode("box:",$key);
                $sfsc_post[$aTmp[1]] = serialize($val);
            }
        }
        $mem_model = kernel::single('b2c_mdl_members');
        $message = "";
        //校验用户注册项 是否正确
        //if($mem_model->validate($sfsc_post,$message)){
            //用户登陆密码加密
            $use_pass_data['login_name'] = $sfsc_post['pam_account']['login_name'];
            $use_pass_data['createtime'] = $sfsc_post['pam_account']['createtime'];
            $sfsc_post['pam_account']['login_password'] = pam_encrypt::get_encrypted_password(trim($sfsc_post['pam_account']['login_password']), pam_account::get_account_type('b2c'), $use_pass_data);
            //事物模拟开始
            $db = kernel::database();
            $db->beginTransaction();
            $msg = "";
            //模拟注册创建
            if($mem_model->save($sfsc_post)){
                $member_id = $sfsc_post['member_id'];
                if(!($this->save_attr($member_id, $sfsc_post))){
                    $db->rollBack();
                    return false;
                }
                $db->commit();
                //
                $data['member_id'] = $member_id;
                $data['uname'] = $sfsc_post['pam_account']['login_name'];
                $data['passwd'] = $sfsc_post['pam_account']['psw_confirm'];
                $data['email'] = $sfsc_post['contact']['email'];
                $data['is_frontend'] = false;
                //增加会员同步
                if( $member_rpc_object = kernel::service("b2c_member_rpc_sync") ) {
                    $member_rpc_object->createActive($member_id);
                }

                $obj_account=kernel::single('b2c_mdl_member_account');
                $obj_account->fireEvent('register',$data,$member_id);

                #↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓记录管理员操作日志↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓
                if($obj_operatorlogs = kernel::service('operatorlog')){
                    if(method_exists($obj_operatorlogs,'inlogs')) {
                        $memo = '添加新会员，会员名为  "'.$data['uname'].'"';
                        $obj_operatorlogs->inlogs($memo, '添加会员', 'members');
                    }
                }
                #↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑记录管理员操作日志↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑
                return $member_id;
            }else{
                return false;
            }
       // }else{
      //      return false;
       // }
    }

    //保存相关的会员信息
    private function save_attr($member_id=null,$aData){

        if(!$member_id){
            return false;
        }

        $member_model = &kernel::single('b2c_mdl_members');
        $aData['pam_account']['account_id'] = $member_id;
        if(!$_POST['profile']['birthday']) unset($aData['profile']['birthday']);
        if($aData['profile']['gender'] == 1){
            $aData['profile']['gender'] = 'male';
        }elseif($aData['profile']['gender'] ===0){
            $aData['profile']['gender'] = 'female';
        }else{
            $aData['profile']['gender'] = 'no';
        }
        foreach($aData as $key=>$val){
            if(strpos($key,"box:") !== false){
                $aTmp = explode("box:",$key);
                $aData[$aTmp[1]] = serialize($val);
            }
        }
        /*
        if($aData['contact']['name']&&!preg_match('/^([@\.]|[^\x00-\x2f^\x3a-\x40]){2,20}$/i', $aData['contact']['name']))
        {
            $msg = app::get('b2c')->_('姓名包含非法字符');
            return false;
        }
        */
        $obj_filter = kernel::single('b2c_site_filter');
        $aData = $obj_filter->check_input($aData);
        if($member_model->save($aData)){
            return true;
        }
        return false;

    }


    //自动登陆模块
    function login($params){
        $auth = pam_auth::instance($params['type']);
        $auth->set_appid($params['appid']);
        try{
            class_exists($params['module']);
        }catch (Exception $e){
            kernel::single('site_router')->http_status('p404');
        }
        if($params['module']){
            if(class_exists($params['module']) && ($passport_module = kernel::single($params['module']))){
                if($passport_module instanceof pam_interface_passport){
                    $module_uid = $passport_module->login($auth,$auth_data);
                    if(!$module_uid){
                        return false;
                    }

                    if($module_uid){
                        $auth_data['account_type'] = $params['type'];
                        $auth->account()->update($params['module'], $module_uid, $auth_data);
                    }
                    $log = array(
                        'event_time'=>time(),
                        'event_type'=>$auth->type,
                        'event_data'=>base_request::get_remote_addr().':'.$auth_data['log_data'].':'.$_SERVER['HTTP_REFERER'],

                    );

                    app::get('pam')->model('log')->insert($log);
                    if(!$module_uid)$_SESSION['last_error'] = $auth_data['log_data'];

                    //卡券会员存入session
                    $_SESSION['card']['type'] = $auth->type;
                    $_SESSION['card']['login_time'] = time();
                    $params['member_id'] = $_SESSION['account'][$params['type']];
                    $params['uname'] = $_POST['uname'];

                    foreach(kernel::servicelist('pam_login_listener') as $service)
                    {
                        $service->listener_login($params);
                    }

                    if($params['redirect'] && $module_uid){
                        $service = kernel::service('callback_infomation');
                        if(is_object($service)){
                            if(method_exists($service,'get_callback_infomation') && $module_uid){
                                $data = $service->get_callback_infomation($module_uid,$params['type']);
                                if(!$data) $url = '';
                                else $url = '?'.utils::http_build_query($data);
                            }

                        }
                    }

                    if($_COOKIE['autologin'] > 0){
                        kernel::single('base_session')->set_cookie_expires($_COOKIE['autologin']);
                    }
					if(IS_DOMAIN){
						setcookie('CARDNAME',$params['uname'] ,time()+3600,kernel::base_url().'/',COOKIE_DOMAIN,1);
					}else{
						setcookie('CARDNAME',$params['uname'] ,time()+3600,kernel::base_url().'/');
					}

                    if($_SESSION['callback'] && !$module_uid){
                        $callback_url = $_SESSION['callback'];
                        unset($_SESSION['callback']);
                        header('Location:' .urldecode($callback_url));
                        return false;
                    }else{
                        return true;
                    }

                }
            }else{
                return false;
            }
        }
    }
	
	/**
	*根据订单获取内部卡密和卡号
	**/
	function usePassByOrder($card_info,$num=1,&$msg){
		$db=kernel::database();
		$error='';
		if($card_info){
			$pass_array=$this->auto_pass(array('card_id'=>$card_info[0]['card_id'],'num'=>$num),$error);
			if($pass_array){
				$data=array();
				foreach($pass_array as $key=>$value){
					$value['type']='virtual';
					$value['ex_status']='true';
					$value['lasttime']=time();
					$value['order_id']=$card_info[0]['order_id'];
					$result=$this->save($value);
				}
				//更新商品和货品库存
				$goods_obj=kernel::single('b2c_mdl_goods');
				$goods=$goods_obj->dump(array('goods_id'=>$card_info[0]['goods_id']));
				$goods['store']=$goods['store']?$goods['store']+$num:$num;
				$goods_obj->update(array('store'=>$goods['store']),array('goods_id'=>$card_info[0]['goods_id']));
				kernel::single('b2c_mdl_products')->update(array('store'=>$goods['store']),array('product_id'=>$card_info[0]['product_id']));
				return $pass_array;
			}else{
				$msg=$error;
				return false;
			}
			
		}else{
			$msg='通过订单未找到卡券';
			return true;
		}
	}
    /**
     * 重写卡密导出方法
     * @param array $data
     * @param array $filter
     * @param int $offset
     * @param int $exportType
     */
	 
    public function fgetlist_csv( &$data,$filter,$offset,$exportType =1 ){
        $limit = 100;
        $cols = $this->_columns();
        if(!$data['title']){
            $this->title = array();
            foreach( $this->getTitle($cols) as $titlek => $aTitle ){
                $this->title[$titlek] = $aTitle;
            }
            $data['title'] = '"'.implode('","',$this->title).'"';
        }
        if(!$list = $this->getList(implode(',',array_keys($cols)),$filter,$offset*$limit,$limit))return false;
        
        foreach( $list as $line => $row ){
            $rowVal = array();
            foreach( $row as $col => $val ){
                //时间戳转为时间
                if( in_array( $cols[$col]['type'],array('time','last_modify') ) && $val ){
                   $val = date('Y-m-d H:i',$val)."\t";
                }
				//长文本回车转空格
                if ($cols[$col]['type'] == 'longtext'){
                    if (strpos($val, "\n") !== false){
                        $val = str_replace("\n", " ", $val);
                    }
                }
				//长于8位的数字串加换行符
                if(strlen($val) > 8 && eregi("^[0-9]+$",$val)){
                    $val .= "\r";
                }
                
                if( strpos( (string)$cols[$col]['type'], 'table:')===0 ){
                    $subobj = explode( '@',substr($cols[$col]['type'],6) );
                    if( !$subobj[1] )
                        $subobj[1] = $this->app->app_id;
                    $subobj = &app::get($subobj[1])->model( $subobj[0] );
                    $subVal = $subobj->dump( array( $subobj->schema['idColumn']=> $val ),$subobj->schema['textColumn'] );
                    $val = $subVal[$subobj->schema['textColumn']]?$subVal[$subobj->schema['textColumn']]:$val;
                }

				//定制导出卡券编号&密码前加#号（防止'0'消失问题）
				if($col=='card_no'||$col=='card_pass'){
					if(trim($val))$val = "#".$val;
				}

                if( array_key_exists( $col, $this->title ) )
                    $rowVal[] = addslashes(  (is_array($cols[$col]['type'])?$cols[$col]['type'][$val]:$val ) );
            }

            $data['contents'][] = '"'.implode('","',$rowVal).'"';
        }
        return true;

    }
	
    function getTitle(&$cols){
        $title = array();
        foreach( $cols as $col => $val ){
            if( !$val['deny_export'] )
            $title[$col] = $val['label'].'('.$col.')';
        }
        return $title;
    }


    /** 
     * 重写getList方法，取出数据后对密码字段解密
     */
    public function getList($cols='*', $filter=array(), $offset=0, $limit=-1, $orderType=null)
    {
        //fiter条件中有card_pass时，对card_pass加密
        if (isset($filter['card_pass'])){
            $tmp_filter = $this->mySqlKeyLib->enPwByKeyList($filter['card_pass']);
            $filter['card_pass'] = $tmp_filter;
            unset($tmp_filter);
        }

        $arr_list = parent::getList($cols,$filter,$offset,$limit,$orderType);

        //如果cols中有card_pass字段则需解密
        if ($this->mySqlKeyLib->_hasCardPass($cols)){
            //对取出的密码解密
            foreach ($arr_list as $key => &$value) {
                $tmp_v = $value['card_pass'];
                $value['card_pass_ori'] = $tmp_v;
                $re = $this->mySqlKeyLib->dePwByKey($tmp_v);
                $value['card_pass'] = $re;
                unset($tmp_v,$re);
            }
        }

        return $arr_list;
    }


    /** 
     * 重写save方法，save前对密码加密
     */
    public function save(&$sdf)
    {   
    // error_log("=-=-=-=-=-=-=-=-".PHP_EOL.var_export($sdf,1).PHP_EOL,3,DATA_DIR."/".date('Ymd',time())."xxxxxxxxx.log");
        //对将要存储的密码加密处理
        if (isset($sdf['card_pass'])){
            $tmp_v = $sdf['card_pass'];
            // $ssql="select HEX(AES_ENCRYPT('{$tmp_v}','{$this->currentKey}')) as ep";
            // $re = kernel::database()->select($ssql);
            $sdf['card_pass'] = $this->mySqlKeyLib->enPwByCurrentKey($tmp_v); 
            unset($tmp_v);           
        }

        $result = parent::save($sdf);

        return $result;
    }



    public function getListOri($cols='*', $filter=array(), $offset=0, $limit=-1, $orderType=null)
    {

        $arr_list = parent::getList($cols,$filter,$offset,$limit,$orderType);
        return $arr_list;
    }

    /**
    *改写update方法，条件中有明文card_pass时对其加密
    */
    public function update($data,$filter,$mustUpdate = null)
    {
        // $log = array(
        //         'data'=>$data,
        //         'filter'=>$filter,
        //         'mustupdate'=>$mustUpdate
        // );
        //fiter条件中有card_pass时，对card_pass加密
        if (isset($filter['card_pass'])){
            $tmp_filter = $this->mySqlKeyLib->enPwByKeyList($filter['card_pass']);
            $filter['card_pass'] = $tmp_filter;
            unset($tmp_filter);
        }
        // error_log("=-=-=-=-=-=-=-=-".PHP_EOL.var_export($filter,1).PHP_EOL,3,DATA_DIR."/".date('Ymd',time())."xxxxxxxxx.log");
        return parent::update($data,$filter,$mustUpdate);
    }

    public function count($filter=''){

        //fiter条件中有card_pass时，对card_pass加密
        if (isset($filter['card_pass'])){
            $tmp_filter = $this->mySqlKeyLib->enPwByKeyList($filter['card_pass']);
            $filter['card_pass'] = $tmp_filter;
            unset($tmp_filter);
        }
        return parent::count($filter);
    }

}
