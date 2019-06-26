<?php
/**
 * Created by PhpStorm.
 * User: yuanshaofeng
 * Date: 2018/4/10
 * Time: 下午5:09
 */
class jdsale_base_normal
{
    public function getAppkey(){
        //临时返回
        $app_key = constant('JD_APPKEY');
        return $app_key;
    }

    public function getAppSecret(){
        //临时返回
        $app_secret = constant('JD_SECRET');
        return $app_secret;
    }

    public function getUserName(){
        //临时返回
        $username = constant('JD_USERNAME');
        return $username;
    }

    public function getPassword(){
        //临时返回
        $password = md5(constant('JD_PASSWD'));
        return $password;
    }
}