<?php

/**
 * 福员外用户同步接口
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2017/4/26
 * Time: 14:09
 */
class  b2c_fuyuanwai_api_sync extends b2c_fuyuanwai_api_base {

    const USER_SYNC_BATCH_NUM = 5;


    function __construct() {
        parent::__construct();
        $this->mdl_member_fyw_log_sync = app::get('b2c')->model('member_fyw_log_sync');
        $this->mdl_member_fyw = app::get('b2c')->model('member_fyw');
        $this->user_sync_batch_num = intval(USER_SYNC_BATCH_NUM);
        if (empty($this->user_sync_batch_num)){
            $this->user_sync_batch_num =10;
        }

    }

    function auto_sync_users(){
        set_time_limit(0);
        $this->log_rsa('start api_sync');
        $this->YFLUSYN1();
        $this->log_rsa('end api_sync');
    }

    function YFLUSYN1($onlyOne=0){

        while($this->hasSyncMember() > 0 ){
            $reqText = $this->getMembers($onlyOne);
            $total_num = count($reqText['data']);
            $params = json_encode($reqText);
            $params2 = str_replace(","," ",$params);

            $log_sync = array('serial_no'=>$reqText['serialNo'],
                              'params'=>json_encode($params2),
                              'createtime'=>time());
            $log_id = $this->mdl_member_fyw_log_sync->insert($log_sync);
            $log_filter = array('log_id'=>$log_id);

            $respText = $this->doPostYfl($reqText);
            if($respText == false){
                //log
                $log_result=array('success'=>false,
                                  'total_num'=>$total_num,
                                  'success_num'=>0,
                                  'failure_num'=>$total_num,
                                  'failure_log'=>app::get('b2c')->_('网络原因'));
                $this->mdl_member_fyw_log_sync->update($log_result,$log_filter);
                break;
            }

            //$this->log_rsa($respText);
            $result_json =  $this->decryptInMessage(substr($respText,379));
            $result = json_decode($result_json,true);

            $log_result= array('result'=>$result,
                               'result_code'=>$result['head']['code'],
                               'result_message'=>$result['head']['msg'],
            );

            //$this->log_rsa(var_export($result,1));

            if (!empty($result) && $result['head']['code'] === '0000'){
                $log_result['success']= true;
                $log_result['total_num'] = $total_num;
                $log_result['success_num'] = $total_num;
                $log_result['failure_num'] = 0;
                $this->updateMembersSync($this->member_fyw_ids);
                $this->mdl_member_fyw_log_sync->update($log_result,$log_filter);
            }else{
                if (empty($result)){
                    $failure_log  = app::get('b2c')->_('无法解析返回数据');
                }else{
                    $failure_log  = $result['head']['msg'];
                }
                $log_result['success']= false;
                $log_result['total_num'] = $total_num;
                $log_result['success_num'] = 0;
                $log_result['failure_num'] = $total_num;
                $log_result['failure_log'] = $failure_log;
                $this->mdl_member_fyw_log_sync->update($log_result,$log_filter);
                break;

            }
            if($onlyOne>0){
                //只执行一次
                break;
            }
        }

        return $result;
    }

    private function hasSyncMember(){

        $num = $this->mdl_member_fyw->count(array('issync'=>0));
        return $num;
    }

    private function getMembers($onlyOne=0){

        if ($onlyOne === 0){
            $members = $this->mdl_member_fyw->getList('*',array('issync'=>0),0,$this->user_sync_batch_num);
        }else{
            $members = $this->mdl_member_fyw->getList('*',array('issync'=>0,'member_fyw_id'=>$onlyOne));
        }

        $member_fyw_ids = array();
        $member_data = array();
        foreach($members as $k=>$v){
            $data['memberNo']=$v['member_no'];
            $data['certType']=$v['certificate_type'];
            $data['certNo']=$v['certificate_no'];
            $data['name']=$v['member_name'];
            $data['comName']=$v['company_name'];
            $data['status']=$v['status'];
            $data['synType']=$v['sync_type'];
            $data['updateTime']=date('Y-m-d H:i:s',$v['last_modified']);
            $member_data[]=$data;
            $member_fyw_ids[]=$v['member_fyw_id'];
        }
        $reqText = array('serialNo'=>$this->getSerialNo(),
                         'data'=>$member_data);
        $this->member_fyw_ids=$member_fyw_ids;

        //$this->log_rsa(var_export($reqText,1));
        return $reqText;
    }

    private function updateMembersSync($member_fyw_ids){
        $this->mdl_member_fyw->update(array('issync'=>'1'),array('member_fyw_id' =>$member_fyw_ids));
    }

    private function getSerialNo(){
        $today= date('Ymd',time());
        $today_int = strtotime(date('Y-m-d'));
        $log_sync = $this->mdl_member_fyw_log_sync->getList('*',array('createtime|than'=>$today_int));
        if($log_sync){
            $no = count($log_sync)+1;
        }else{
            $no =1;
        }
        $serialNo = $today.$this->leftZero($no,8);
        return $serialNo;
    }

}