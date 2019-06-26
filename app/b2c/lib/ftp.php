<?php

/**
 * 作用：FTP操作类( 拷贝、移动、删除文件/创建目录 )
 */
class b2c_ftp
{
    public $off; // 返回操作状态(成功/失败)
    public $conn_id; // FTP连接
    const FTP_HOST = FTP_HOST;
    const FTP_PORT = FTP_PORT;
    const FTP_USER = FTP_USER;
    const FTP_PASS = FTP_PASS;

    /**
     * 方法：FTP连接
     * fTP_HOST -- FTP主机
     * fTP_PORT -- 端口
     * fTP_USER -- 用户名
     * fTP_PASS -- 密码
     */
    function __construct()
    {
        $this->conn_id = ftp_connect(self::FTP_HOST, self::FTP_PORT) ;
        if(!$this->conn_id)return array('status' => false, 'msg' => "FTP服务器连接失败");
        if(!ftp_login($this->conn_id, self::FTP_USER, self::FTP_PASS))return array('status' => false, 'msg' => "FTP服务器登陆失败");
        if(FTP_PASV){
            ftp_pasv($this->conn_id, 1); // 打开被动模拟
        }
    }

    /**
     * 方法：上传文件
     * @path -- 本地路径
     * @newpath -- 上传路径
     * @type -- 若目标目录不存在则新建
     */
    function up_file($path, $newpath, $type = true)
    {
        if ($type) $this->dir_mkdirs($newpath);
        $pathArr = $this->_get_path_name($newpath);
        $fromPathArr = $this->_get_path_name($path);
        $this->off = ftp_put($this->conn_id,  $fromPathArr['file'],$path,FTP_IMAGE);
        if ($this->off) {
            $this->off = ftp_rename($this->conn_id,$fromPathArr['file'],$pathArr['file']);
            if ($this->off) {
                return array('status' => true);
            }else {
                return array('status' => false, 'msg' => "文件上传失败，请检查权限及路径是否正确！");

            }
        } else {
            return array('status' => false, 'msg' => "文件上传失败，请检查权限及路径是否正确！");

        }
    }

    /**
     * 方法：移动文件
     * @path -- 原路径
     * @newpath -- 新路径
     * @type -- 若目标目录不存在则新建
     */
    function move_file($path, $newpath, $type = true)
    {
        if ($type) $this->dir_mkdirs($newpath);
        $this->off = ftp_rename($this->conn_id, $path, $newpath);
        if ($this->off) {
            return array('status' => true);
        } else {
            return array('status' => false, 'msg' => "文件移动失败，请检查权限及原路径是否正确！");

        }
    }

    /**
     * 方法：复制文件
     * 说明：由于FTP无复制命令,本方法变通操作为：下载后再上传到新的路径
     * @path -- 原路径
     * @newpath -- 新路径
     * @type -- 若目标目录不存在则新建
     */
    function copy_file($path, $newpath, $type = true)
    {
        $downpath = "c:/tmp.dat";
        $this->off = ftp_get($this->conn_id, $downpath, $path, FTP_ASCII);// 下载
        if (!$this->off)             return array('status' => false, 'msg' => "文件复制失败，请检查权限及原路径是否正确！");
        $this->up_file($downpath, $newpath, $type);
    }

    /**
     * 方法：删除文件
     * @path -- 路径
     */
    function del_file($path)
    {
        $this->off = ftp_delete($this->conn_id, $path);
        if ($this->off) {
            return array('status' => true);
        } else {
            return array('status' => false, 'msg' => "文件删除失败，请检查权限及原路径是否正确！");

        }
    }

    /**
     * 方法：生成目录
     * @path -- 路径
     */
    function dir_mkdirs($path)
    {
        $path_arr = $this->_get_path_name($path);
        $path_arr = $path_arr['dir']; // 取目录数组
        $path_div = count($path_arr); // 取层数
        foreach ($path_arr as $val) // 创建目录
        {
            if (ftp_chdir($this->conn_id, $val) == FALSE) {
                $tmp = ftp_mkdir($this->conn_id, $val);
                if ($tmp == FALSE) {
                    return array('status' => false, 'msg' =>"目录创建失败，请检查权限及路径是否正确！");
                }
                ftp_chdir($this->conn_id, $val);
            }
        }
        for ($i = 1; $i == $path_div; $i++) // 回退到根
        {
            ftp_cdup($this->conn_id);
        }
        return true;
    }

    private function _get_path_name($path){
        $path_arr = explode('/', $path); // 取目录数组
        $file_name = array_pop($path_arr); // 弹出文件名
        return array('dir'=>$path_arr,'file'=>$file_name);
    }

    /**
     * 方法：关闭FTP连接
     */
    function close()
    {
        ftp_close($this->conn_id);
    }
}// class class_ftp end


/************************************** 测试 ***********************************
 * $ftp = new class_ftp('192.168.100.143',21,'user','pwd'); // 打开FTP连接
 * //$ftp->up_file('aa.txt','a/b/c/cc.txt'); // 上传文件
 * //$ftp->move_file('a/b/c/cc.txt','a/cc.txt'); // 移动文件
 * //$ftp->copy_file('a/cc.txt','a/b/dd.txt'); // 复制文件
 * //$ftp->del_file('a/b/dd.txt'); // 删除文件
 * $ftp->close(); // 关闭FTP连接
 ******************************************************************************/
