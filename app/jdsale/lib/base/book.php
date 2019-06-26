<?php

/**
 * Created by PhpStorm.
 * User: yuanshaofeng
 * Date: 2018/4/10
 * Time: 下午5:11
 */
class jdsale_base_book
{
    public function getAppkey(){
        //临时返回
        $app_key = constant('JDBK_APPKEY');
        return $app_key;
    }

    public function getAppSecret(){
        //临时返回
        $app_secret = constant('JDBK_SECRET');
        return $app_secret;
    }

    public function getUserName(){
        //临时返回
        $username = constant('JDBK_USERNAME');
        return $username;
    }

    public function getPassword(){
        //临时返回
        $password = md5(constant('JDBK_PASSWD'));
        return $password;
    }
}