<?php

class sand_mdl_sandorder extends dbeav_model{

	var $defaultOrder = array('createtime','DESC');    

    function __construct($app){
        parent::__construct($app);
        //$this->use_meta();  //member中的扩展属性将通过meta系统进行存储
    }

    

   
  
    /* 通过用户名获取会员信息  */
    function getMemberByUname($uname){
        $account = app::get('pam')->model('account');
        $member = $account->getList('account_id', array('login_name' => $uname));
        if($ret = $this->getList('*', array('member_id' => $member[0]['account_id']))){
            return $ret[0];
        }
        return false;
    }
    
       /**
     * 重写会员导出方法
     * @param array $data
     * @param array $filter
     * @param int $offset
     * @param int $exportType
    
    public function fgetlist_csv( &$data,$filter,$offset,$exportType =1 ){
       
        $cols = $this->_columns();
        if(!$data['title']){
            $this->title = array();
			array_push($this->title,'序号','成员姓名','证件类型（01:身份证 02:军官证 03:护照 04:其他 06:营业执照 08:台胞证 11:员工证）','成员用户登录名','成员用户手机号','成员用户邮箱','成员用户登录密码','绑定银行卡卡号','绑定银行卡对应真实姓名','绑定银行卡所属银行联行行号','绑定银行卡开户行','所属机构','备注','转账金额');
			
            // service for add title when export
            $data['title'] = '"'.implode('","',$this->title).'"';
        }
		
        if(!$list = $this->getList(implode(',',array_keys($cols)),$filter))return false;
		
        // $data['contents'] = array();
			
        foreach( $list as $line => $row ){
			$contdata = array();
			$info = app::get('b2c')->model('members')->getRow('*',array('member_id'=>$row['member_id']));
			error_log(var_export($info,1)."\n",3,ROOT_DIR.'/test.txt');
			array_push($contdata,$row['log_id'],'小白','01'."\t",$row['member_name'],$info['mobile'],$info['email'],'123456','123','xiaobai','nanjing','nanjing','nanjing','nanjing',$row['amount']);

            $rowVal = array();
            foreach( $contdata as $col => $val ){
                
                if( in_array( $cols[$col]['type'],array('time','last_modify') ) && $val ){
                   $val = date('Y-m-d H:i',$val)."\t";
                }
                if ($cols[$col]['type'] == 'longtext'){
                    if (strpos($val, "\n") !== false){
                        $val = str_replace("\n", " ", $val);
                    }
                }
				
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

                if( array_key_exists( $col, $this->title ) )
                    $rowVal[] = addslashes(  (is_array($cols[$col]['type'])?$cols[$col]['type'][$val]:$val ) );
            }
			
            $data['contents'][] = '"'.implode('","',$rowVal).'"';
        }
    }


    function getTitle(&$cols){
        $title = array();
        foreach( $cols as $col => $val ){
            $title[$col] = $val['label'].'('.$col.')';
        }
        return $title;
    }

	 */
}
