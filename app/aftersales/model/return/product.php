<?php


/**
 * 申请退货记录表模型
 * 
 * @version 0.1
 * @package aftersales.lib
 */ 
class aftersales_mdl_return_product extends dbeav_model{

    function __construct($app){
        parent::__construct($app);
        //使用meta系统进行存储
        $this->use_meta();
    }

	/**
	 * @var 是否使用tag
	 */
	var $has_tag = true;
	var $defaultOrder = array('add_time','DESC');
    /**
     * 得到唯一的编号
     * @params null
     * @return string 售后序号
     */
    public function gen_id()
    {
        $i = rand(0,9999);
        do{
            if(9999==$i){
                $i=0;
            }
            $i++;
            $return_id = date('YmdH').str_pad($i,4,'0',STR_PAD_LEFT);
            $row = $this->db->selectrow('SELECT return_id from sdb_aftersales_return_product where return_id ='.$return_id);
        }while($row);
        return $return_id;
    }
    
    /**
     * 改变售后状态
     * @param mixed 售后信息修改的sdf数据
     * @param string 引用值，修改处理的结果消息
     * @return boolean 成功与否
     */
    public function change_status(&$sdf, &$msg='')
    {
        if ($this->save($sdf))
        {
            /*$row = $this->instance($return_id,"member_id");
            $this->modelName="member/account";
            $this->fireEvent('saleservice',$row,$row['member_id']);*/
            $this->get_status($sdf);
            
            return true;
        }
        else
        {
            $msg = app::get('aftersales')->_("状态保存失败！");
            return false;
        }
    }
    
    /**
     * 得到售后状态的具体描述
     * @param mixed 售后信息数据，引用数据
     * @return null
     */
    public function get_status(&$sdf)
    { 
        //todo: 去掉本函数，合并到schema中
        switch ($sdf['status'])
        {
                case 1:
                    $sdf['status'] = __("退款协议等待卖家确认");
                    break;
                case 2:
                    $sdf['status'] = __("审核中");
                    break;
                case 3:
                    $sdf['status'] = __("接受申请");
                    break;
                case 4:
                    $sdf['status'] = __("完成");
                    break;
                case 5:
                    $sdf['status'] = app::get('aftersales')->_("拒绝");
                    break;
                case 6:
                    $sdf['status'] = __("已收货");
                    break;
                case 7:
                    $sdf['status'] = __("已质检");
                    break;
                case 8:
                    $sdf['status'] = __("补差价");
                    break;
                case 9:
                    $sdf['status'] = __("已拒绝退款");
                    break;
                case 10:
                    $sdf['status'] = app::get('aftersales')->_("已取消");
                    break;
                case 11:
                    $sdf['status'] = app::get('aftersales')->_("卖家不同意协议，等待买家修改");
                    break;
        }
        
        return true;
    }
    
    /**
     * 保存售后描述
     * @param mixed 售后描述的具体结构数组
     * @return boolean 保存的成功与否的标记
     */
    public function send_comment(&$arr_data)
    {
        $info = $this->dump($arr_data['return_id']);
        if ($info['comment'])
            $old_comment = unserialize($info['comment']);
        else
            $old_comment = array();

        $new_comment = array(
            array(
                'time' => time(),
                'content' => $arr_data['comment']
            )
        );

        if(is_array($old_comment))
        {
            $new_comment = array_merge($new_comment,$old_comment);
        }

        $arr_data['comment'] = serialize($new_comment);
        
        return $this->save($arr_data);
    }
	
    /**
     * 修改finder显示的会员ID-变成会员用户名
     * @param array 单条数据数组
     * @return string 会员登录名
     */
	public function modifier_member_id($row)
    {
        if ($row === 0 || $row == '0'){
            return app::get('aftersales')->_('非会员顾客');
        }    
        else{
            $obj_member = app::get('pam')->model('account');
            $sdf = $obj_member->dump($row);
            return $sdf['login_name'];
        }
    }
	
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

				//查出申请人姓名
				if($col=='member_id'){
					$pam_account = app::get('pam')->model('account');
					$pam_info = $pam_account->getList('*',array('account_id'=>$val));
					$val = $pam_info[0]['login_name']."\r";
				}
				if($col=='order_id'){
					$val .= "\r";
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
}