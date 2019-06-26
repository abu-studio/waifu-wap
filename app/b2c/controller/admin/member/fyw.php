<?php
/**
 *
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2017/4/30
 * Time: 13:26
 */
class b2c_ctl_admin_member_fyw extends desktop_controller{

    var $workground = 'b2c_ctl_admin_member';

    public function __construct($app)
    {
        parent::__construct($app);
        header("cache-control: no-store, no-cache, must-revalidate");
    }

    function index(){
        $group[] = array('label'=>app::get('b2c')->_('用户状态更新'),'icon'=>'download.gif','submit'=>'index.php?app=b2c&ctl=admin_member_fyw&act=batch_update','target'=>'_self');

        $actions_base['title']=app::get('b2c')->_('福员外会员列表');
        $actions_base['actions'] = array(
            array(
            'label' => "导入会员",
            'href' => 'index.php?app=b2c&ctl=admin_member_fyw&act=import',
            'target' => 'dialog::{title:\'' . app::get('b2c')->_('导入会员') . '\',width:500,height:200}'
            ),
            //array(
            //    'label' => "批量更新",
            //    'icon'=>'batch.gif',
            //    'group'=>$group,
            //),
            );
        $actions_base['use_buildin_filter']=true;
        $this->finder('b2c_mdl_member_fyw',$actions_base);
    }

    function import(){

        $this->display('admin/member/fyw/import.html');
    }

    function create(){

        $this->begin('index.php?app=b2c&ctl=admin_member_fyw&act=index');
        $members_array=$this->read_xls($_FILES['fyw_members']);
        if(!is_array($members_array) || empty($members_array)){
            $this->end(false,app::get('b2c')->_('无会员数据'));
        }
        $mdl_member_fyw = app::get('b2c')->model('member_fyw');
        $mdl_members = app::get('b2c')->model('members');
        $errorMemberNo = false;
        foreach($members_array as $item=>$value){
            $member_id = $mdl_members->get_id_by_uname($value[0]);
            if (empty($member_id)){
                $member_id = 0;
            }
            // 2017-07-10 需求调整
            //if(empty($member_id)){
            //    $errmsg = app::get('b2c')->_('存在无效会员（人才号）');
            //    $errorMemberNo = true;
            //    continue;
            //}
            $member_fyw = array(
                'member_no' => $value[0],
                'certificate_type' => $value[1],
                'certificate_no' => $value[2],
                'member_name' => $value[3],
                'mobile_no'=>$value[4],
                'company_name' => $value[5],
                'company_code' => $value[6],
                'region' => $value[7],
                'status' => $value[8],
                'member_id' => $member_id ,
                'issync'=>'0',
                'createtime' =>time(),
            );
            //$this->log_rsa(var_export($member_fyw,1));
            $fiter_member_no = array('member_no'=>$value[0]);
            $hasFyw = $mdl_member_fyw->count($fiter_member_no);
            if ($hasFyw>0){
                $member_fyw['sync_type'] = 'UPDATE';
                if(!$mdl_member_fyw->update($member_fyw,$fiter_member_no)){
                    $this->end(false,app::get('b2c')->_('数据保存失败'));
                }
            }else{
                if(!$mdl_member_fyw->save($member_fyw)){
                    $this->end(false,app::get('b2c')->_('数据保存失败'));
                }
            }

        }
        if ($errorMemberNo){
            $this->end(false,$errmsg);
        }else{
            $this->end(true,app::get('b2c')->_('数据导入成功'));
        }
    }

    private function read_xls($file){
        $file_type = substr(strrchr($file['name'],'.'),1);
        if(empty($file['tmp_name'])){
            $this->end(false,app::get('b2c')->_('文件不能为空'));
        }
        // 检查文件格式
        if ($file_type != 'xls'){
            $this->end(false,app::get('b2c')->_('文件格式不对,请重新上传'));
        }
        $this->inc_excel();

        $objReader = PHPExcel_IOFactory::createReader('Excel5');
        $objReader->setReadDataOnly(true);
        $objPHPExcel = $objReader->load($file['tmp_name']);
        $objWorksheet = $objPHPExcel->getActiveSheet();
        $highestRow = $objWorksheet->getHighestRow();
        $highestColumn = $objWorksheet->getHighestColumn();
        $highestColumnIndex = PHPExcel_Cell::columnIndexFromString($highestColumn);
        $excelData = array();
        for ($row = 2; $row <= $highestRow; $row++) {
            $excelData_row = array();
            for ($col = 0; $col < $highestColumnIndex; $col++) {
                $excelData_row[] =trim((string)$objWorksheet->getCellByColumnAndRow($col, $row)->getValue());
            }
            $excelData[]=$excelData_row;
        }
        //$this->log_rsa(var_export($excelData,1));

        return $excelData;
    }

    function down_template(){
        $this->inc_excel();
        // Create new PHPExcel object
        $objPHPExcel = new PHPExcel();
        $fileName= '福员外用户导入模板.xls';
        // Add some data
        $objPHPExcel->setActiveSheetIndex(0)
            ->setCellValue('A1', '会员号（人才号）')
            ->setCellValue('B1', '证件类型')
            ->setCellValue('C1', '证件号')
            ->setCellValue('D1', '姓名')
            ->setCellValue('E1','手机号码')
            ->setCellValue('F1', '公司名')
            ->setCellValue('G1','商社代码')
            ->setCellValue('H1','地区')
            ->setCellValue('I1', '用户状态');

        $objPHPExcel->getActiveSheet()->setTitle('会员');

// Set active sheet index to the first sheet, so Excel opens this as the first sheet
        $objPHPExcel->setActiveSheetIndex(0);

// Redirect output to a client’s web browser (Excel2007)
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment;filename="'.$fileName.'"');
        header('Cache-Control: max-age=0');
// If you're serving to IE 9, then the following may be needed
        header('Cache-Control: max-age=1');

// If you're serving to IE over SSL, then the following may be needed
        header ('Expires: Mon, 26 Jul 1997 05:00:00 GMT'); // Date in the past
        header ('Last-Modified: '.gmdate('D, d M Y H:i:s').' GMT'); // always modified
        header ('Cache-Control: cache, must-revalidate'); // HTTP/1.1
        header ('Pragma: public'); // HTTP/1.0

        $objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel5');
        $objWriter->save('php://output');
        exit;
    }

    function batch_update(){
        $this->begin('index.php?app=b2c&ctl=admin_member_fyw&act=index');
        $batch_ids = $_POST['member_fyw_id'];
        $mdl_member_fyw = app::get('b2c')->model('member_fyw');
        $mdl_member_fyw->update(array('issync'=>'0'),array('member_fyw_id'=>$batch_ids));
        $this->pagedata['finder_id'] = $_GET['_finder']['finder_id'];
        $this->end(true, app::get('b2c')->_('更新完成'));

    }

    function edit($member_fyw_id=null){

        $mdl_member_fyw = app::get('b2c')->model('member_fyw');
        $member_fyw = $mdl_member_fyw->dump($member_fyw_id);

        $member_fyw['certificate_type_options'] = array (
            '0' => app::get('b2c')->_('身份证'),
            '1' => app::get('b2c')->_('护照'),
            '2' => app::get('b2c')->_('军官证'),
            '3' => app::get('b2c')->_('士兵证'),
            '4' => app::get('b2c')->_('回乡证'),
            '5' => app::get('b2c')->_('临时身份证'),
            '6' => app::get('b2c')->_('户口簿'),
            '7' => app::get('b2c')->_('警官证'),
            '8' => app::get('b2c')->_('台胞证'),
            '9' => app::get('b2c')->_('营业执照'),
            '10' => app::get('b2c')->_('其它证件'),
        );

        $member_fyw['status_options'] = array (
            'WORK' => app::get('b2c')->_('正常'),
            'STOP' => app::get('b2c')->_('停用'),
            'LEAVE' => app::get('b2c')->_('离职'),
            'RET' => app::get('b2c')->_('退休'),
        );

        $this->pagedata['member_fyw'] = $member_fyw;
        $this->display('admin/member/fyw/edit.html');
    }

    function save(){

        $member_fyw = $_POST;
        //修改同步状态为未同步
        $member_fyw['sync_type']='UPDATE';
        $member_fyw['issync']='0';
        $this->begin();
        $mdl_member_fyw = app::get('b2c')->model('member_fyw');
        if($mdl_member_fyw->save($member_fyw)){
            $this->end(true,app::get('b2c')->_('保存成功'));
        }else{
            $this->end(false,app::get('b2c')->_('保存失败'));
        }

    }

    private function log_rsa($message) {
        file_put_contents(DATA_DIR . '/api_rsa.log', date('Y-m-d H:i:s',time())."\n\r", FILE_APPEND);
        file_put_contents(DATA_DIR . '/api_rsa.log', $message."\n\r", FILE_APPEND);
    }

    private function inc_excel(){
        date_default_timezone_set('Asia/ShangHai');

        if(file_exists(ROOT_DIR.'/excellibs/PHPExcel.php')){
            require_once ROOT_DIR.'/excellibs/PHPExcel.php';
        }
    }



}
