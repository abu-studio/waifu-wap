<?php

class b2c_ctl_site_oracletest extends b2c_frontpage
{
    public function lpc()
    {
        echo 123;exit;
        //获取该库的人才号
        $db                = kernel::single('base_db_oracle_connections');
        $bindPara          = array(":user_name" => $name);
        $sql               = "select * from uup.v_yoofuu_login where (USER_NAME=:user_name or (PHONE=:user_name and MB_LOGIN_STATUS=1) or (EMAIL=:user_name and MAIL_LOGIN_STATUS=1)) and DELETED = 'N'";
        $yoofuu_login_data = $db->bindSelect($sql, $bindPara);
        echo $password . "<pre>";
        print_r($yoofuu_login_data);exit;
        if ($yoofuu_login_data[0]['PWD'] != $password) {
            $_SESSION['login_msg'] = app::get('b2c')->_('云平台:用户名或密码错误');
            $this->splash('failed', $this->gen_url(array('app' => 'b2c', 'ctl' => 'site_passport', 'act' => 'elogin')), app::get('b2c')->_('用户名或密码错误'), '', '', true);
        }
    }
}
