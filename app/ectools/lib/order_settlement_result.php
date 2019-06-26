<?php
class order_settlement_result{
    static $localDir;
    static $filenameToday;
    static $ftpDir;

    static function register_autoload($load=array('kernel', 'autoload'))
    {
        if(function_exists('spl_autoload_register')){
            return spl_autoload_register($load);
        }else{
            return false;
        }
    }

    static function boot(){
        if(!self::register_autoload()){
            require(ROOT_DIR . '/app/base/autoload.php');
        }

        self::$localDir = ROOT_DIR . "/data/orderSettlementResult/";
        if(! file_exists(self::$localDir)){
            $rs = mkdir(self::$localDir ,0777,true);
            if(false == $rs){
                exit("error: ".self::$localDir."这个目录无法被创建,请检查是否没有权限");
            }
        }

        self::$filenameToday = date('Ymd');
        self::$ftpDir = '/biz/DataDockingPlatformCSV/';

        $times = 9;
        do{
            $logData = self::ftp_read_orderlog();
            $resBool = self::order_settlement($logData);
            if($resBool){
                self::rename_local_file();
            }
            $isResolved = self::is_resolved();
            $times--;
            if(0 == $times){
                $isResolved = true;
            }
        }while(! $isResolved);

    }



    //解析文件内容,并在数据库里做标记
    static function order_settlement($orderIdList){
        if(empty($orderIdList)){
            return false;
        }

        $db = kernel::database();
        $newOrderIdList=array();
        foreach ($orderIdList as $orderId){
            $orderId = intval(trim($orderId[0]));
            if(empty($orderId)){
                continue;
            }

            array_push($newOrderIdList , $orderId);
        }

        if(empty($newOrderIdList)){
            return false;
        }

        $newOrderIdStr = implode("," , $newOrderIdList);
        $sql="update sdb_b2c_orders set settlement_result='finish' where order_id in ($newOrderIdStr)";
        $rs = $db->exec($sql);
        $db->commit(true);

        if(false == $rs){
            return false;
        }

        return true;
    }


    //从ftp服务器 下载文件,并解析
    static function ftp_read_orderlog(){
        $connId = ftp_connect('172.16.96.231' , '21') ;
        if(false == $connId){
            exit('error: ftp文件服务器连接不上');
        }
        $loginResult = ftp_login($connId , 'Qggys' , 'K4cj2Khh');
        if(false == $loginResult){
            exit('error: ftp文件服务可以连接, 但是器登录失败');
        }
        ftp_pasv($connId , 1);
        //$pwd = ftp_pwd($connId);
        //$fileList = ftp_nlist($connId , self::$ftpDir);

        $fileName = self::$filenameToday . '.xls';
        $localFileName = self::$localDir . $fileName;
        $result = ftp_get($connId , $localFileName , self::$ftpDir . $fileName , FTP_BINARY);
        if(false == $result){
            exit("errro: 没有获取到文件 {$fileName}");
        }

        $contents = self::read_xls($localFileName);

        return $contents;
    }

    //做标记, 表示该文件已处理
    static function rename_local_file(){
        $fileName = self::$filenameToday . '.xls';
        $localFileName = self::$localDir . $fileName;

        $newFilename = self::$filenameToday . '.log';
        $newLocalFilename = self::$localDir . $newFilename;
        if(file_exists($localFileName)){
            file_put_contents($newLocalFilename , 'done');
            return true;
        }

        return false;
    }

    //检查下本地文件是否已经解析过了
    static function is_resolved(){
        $newFilename = self::$filenameToday . '.log';
        $newLocalFilename = self::$localDir . $newFilename;
        $contents = file_get_contents($newLocalFilename);
        if(!empty($contents) && 'done' == $contents){
            return true;
        }

        return false;
    }

    static function inc_excel(){
        date_default_timezone_set('Asia/ShangHai');

        if(file_exists(ROOT_DIR.'/excellibs/PHPExcel.php')){
            require_once ROOT_DIR.'/excellibs/PHPExcel.php';
        }
    }

    static function read_xls($filename){
        self::inc_excel();

        $objReader = PHPExcel_IOFactory::createReader('Excel5');
        $objReader->setReadDataOnly(true);
        $objPHPExcel = $objReader->load($filename);
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

}

