<?php

/**
 * 福员外通用接口
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2017/4/18
 * Time: 11:08
 */
class b2c_fuyuanwai_api_base {

    const KEY_ALGORITHM = "RSA";
    const SIGNATURE_ALGORITHM = OPENSSL_ALGO_SHA1; //"SHA1withRSA";
    const CIPHER_ALGORITHM = OPENSSL_PKCS1_PADDING; //"RSA/ECB/PKCS1Padding"; //加密block需要预留11字节
    const KEYBIT = 2048;
    const RESERVEBYTES = 11;

    const USER_SYNC_BATCH_NUM = 5;

    /** 15位发起机构号  :保留域:今后可能出现多家商户,需要通过商家号找到商家公钥 */
    //protected static $merId='100000001000009';

    //private $shwfPrivateKey = "MIIEvQIBADANBgkqhkiG9w0BAQEFAASCBKcwggSjAgEAAoIBAQCnsqqdv+EwqginBM6+Noj69H9n61p31oJ/njQHRFMFu1A7+Dvg50roFax1UsqVJznpRFFvAcyDpyh4+S0bOt0nFYprtje4/MUzolcKj/0OJrNWE/eZBJLYfaU/gRJxcIhjVBnAb43FlkEWfLQBmXlN4NMgF7yd/+iEw4/qZiLYSPbohrp6uZ1i0+C5LT6nNnltjvSaEPeXebohGw8ZFd43rQ7pxIghnRB2SnUy1tWUviUa2Z9UGlemHEapMH9cRpCx2uqrZcmzaC9xolaGHukydhdgvR4rHLhy63tgZekCx4IsfHf0ZQigoyVCbDuRf+kQItVzrGkq2zI6/PnZSHoDAgMBAAECggEAGXpuq5rsdr1OpTl4w7N7ak1dUgeS7iQcHZGGrBf4WSWVUhcr2caqPcqVMhoLB1A3XzZu/OOUE+iAoKiS7tEpqnd+WVEM9EELIQYoHUW1F8JPeuN7ubbVLKkiQxy0FaIYRqcPZopMStnCN81od9m+cVOYIAcbNBIeXJVttxofbyafzfY16z/+VeMORQdWFpR8v5tN/xylokfZjiILGeH/sEE631Lkcis/ZXJtKgqMFwBhkUaYUO0sayIZWXzXgx5lCKizpHbwNFCRFsNdu5b5adhpsAqhE5N/lKib2IfuLBz9vmxjnqP6iyZ/LYF2joMUvrptOvVAyJ5d9UODl1JOWQKBgQDRqG6JBjxPFOYGb9VkiSJFt3YWPNtfI8CPIsMv93vaB8y34DeVw5M9x+AIZIhwVerNajUUUeCQ4QBMKL17gWKQkXd2Jl/YthecYWH2XXIGlE060VXMnGtxz5SfPTo/HQbwNdvXm+/IumM05hkosFn6fQvVt5/8bqPMRSuPkvUDVwKBgQDMw+mBgYBTHYgjQDne2bMi3lUyHk4G7qIO8Znj6lJE62/aYNgNnnOBKTbBvdVLn6OvriucbVLCG8QqINo+MXx1zWbQw8sfxrYe66DAcjB4xafOfBzObaO/hmj5DuQEFscuc+/xOu1JlscDg9M9QElavmhfulbWdde9fQsdBMrfNQKBgHB2rEx+ds1pBXcAeHSEh0jkf8iv/nELiZh+ajuJwvsS4gLkmIySq6IhXJDD9Nhljh2AUlbDEPZGa9VuqS3eGtQ34+AR96oVC5dMObNhLvBOjxr0/dRTN+OGGVBOFLeUR4uFKJeAw2Bmcx8GKwrDhpCykS2kYYKUivLhS9upVhrjAoGAYcz5DJgT+J7UVTHp8hy8yNy0iHmc/wafdM/Elu1mWfCxvfYfe3HA7WIH+0V2SOZ4wgJIZjB5JKkqaozCcI4mSgXPI8tAi27XsbENWJ2xtR2C5sa044vOeD30iXCpS6Ktg+xwICHrEAjqCS2/iTPZVXQ4WfCgZVJntuOwmS2e0DkCgYEAyUE7ogW+WgPX6lmhbsSYqclLlECcQJ8THGasqu/Ujkj8BjHlPYW/q24lVgXy5Ah2TC03e7dzG7rdbWYDF/d4yPSs43dMp5Enzm6kz6iwMeYde40B25bqDmMeUcea+seYZXBrOiNWGGFssCr/o9e67rNjbz6rU3qdIyW6XlYKyzk=";

    //private $yflPublicKey = 'MIIBIjANBgkqhkiG9w0BAQEFAAOCAQ8AMIIBCgKCAQEApSa2U1CsXMNrek0d6XodouoqYVNvjFwyLr6kFbJyUuU5Aakk8NHdvKlD7OEaIVkXbakqrTAkL7eIX67DF3dpWm16PuFkOC6CVaKaELrctmjBDl9NIZqz8U33TpCM/lhqhjhD6aAKK3IVBWbvMS0Cokc1EucGYRVGnLSWpaLTi3oW9xdIUZ1h8FT30CvsUQAjANILo5gkxaM7xPPLxMwXSofHIIuMS82tBGXY92+cYRvgRgECjudE7Td5b/6iBrP1suCF4E9zcIkqJLrRBCDExlTW5mBZQrdcnBlnq3HNAz5rtY6yJFnDuO4QRk/amzmraJpDFwczrGy7oZ28GnEhYQIDAQAB';

    //protected $YFL_USER_SYNC_URL = 'http://120.55.93.82:9071/yfl/third/channel/api';

    /** 8位交易码  : 保留域*/
    private $serverCode;

        /**交易请求报文体*/
    private $reqText;

        /** 加解密对象*/
    //private RSAHelper cipher;

        /** 报文主体明文*/
    private $plaintext;

    private $charSet = "utf-8" ;

    private $encryptBlock;
    private $decryptBlock;

    public function __construct(){
        $this->signature_alg = self::SIGNATURE_ALGORITHM;
        $this->padding = self::CIPHER_ALGORITHM;

        $this->localPrivKey = null;
        $this->peerPubKey = null;
        $this->decryptBlock = self::KEYBIT / 8; //256 bytes
        $this->encryptBlock = $this->decryptBlock - self::RESERVEBYTES; //245 bytes

        $this->merId = SHWF_MERID;
        $this->shwfPrivateKey = SHWF_PRIVATE_KEY;
        $this->yflPublicKey = YFL_PUBLIC_KEY;
        $this->yflUserSyncUrl = YFL_USER_SYNC_URL;

    }

    public function leftZero ($inputStr,$len=0){
        return str_pad(''.$inputStr, $len, "0", STR_PAD_LEFT);
    }

    /**
     * encrypt with public key
     * @param $data;
     * @param $rsakeypath
     */
    public static function encryptPublic($data, $rsakeypath) {
        $content = self::getContent($rsakeypath);
        if ($content) {
            $pem = self::transJavaRsaKeyToPhpOpenSSL($content);
            $pem = self::appendFlags($pem, true);
            $res = openssl_pkey_get_public($pem);
            if ($res) {
                $opt = openssl_public_encrypt($data, $result, $res);
                if ($opt) {
                    return $result;
                }
            }
        }
        return false;
    }

    /**
     * decrypt with private key
     * @param $data
     * @param $rsakeypath
     */
    public static function decryptPrivate($data, $rsakeypath) {
        $content = self::getContent($rsakeypath);
        if ($content) {
            $pem = self::transJavaRsaKeyToPhpOpenSSL($content);
            $pem = self::appendFlags($pem, false);
            $res = openssl_pkey_get_private($pem);
            if ($res) {
                $opt = openssl_private_decrypt($data, $result, $res);
                if ($opt) {
                    return $result;
                }
            }
        }
        return false;
    }

    /**
     * get content forom file
     * @param $filepath
     * @return $content
     */
    private static function getContent($filepath) {
        if (is_file($filepath)) {
            $content = file_get_contents($filepath);
            return strtr($content, array(
                "\r\n" => "",
                "\r" => "",
                "\n" => "",
            ));
        }
        return false;
    }

    /**
     * trans java's rsa key format to php openssl can read
     * @param $content
     * @return string
     */
    private static function transJavaRsaKeyToPhpOpenSSL($content) {
        if ($content) {
            return trim(chunk_split($content, 64, "\n"));
        }
        return false;
    }

    /**
     * append Falgs to content
     * @param $content
     * @param $isPublic
     * @return string
     */
    private static function appendFlags($content, $isPublic = true) {
        if ($isPublic) {
            return "-----BEGIN PUBLIC KEY-----\n" . $content . "\n-----END PUBLIC KEY-----\n";
        }
        else {
            return "-----BEGIN PRIVATE KEY-----\n" . $content . "\n-----END PRIVATE KEY-----\n";
        }
    }

    /**
     * 处理请求
     * @param $inMessage
     * @return mixed
     */
    //function getReqText($inMessage){
    //
    //    $pem = trim(chunk_split($this->shwfPrivateKey,64,"\n"));//转换为pem格式的公钥
    //    $pem = "-----BEGIN PRIVATE KEY-----\n".$pem."\n-----END PRIVATE KEY-----\n";
    //
    //    $getShwfPrivateKey = openssl_pkey_get_private($pem);
    //    //$this->log_rsa($getShwfPrivateKey);
    //
    //    openssl_private_decrypt(base64_decode($inMessage),$decrypted,$getShwfPrivateKey);
    //    openssl_free_key($getShwfPrivateKey);
    //    $this->log_rsa($decrypted);
    //    return $decrypted;
    //}

    /**
     * 处理返回
     * @param $outMessage
     * @return mixed
     */
    function getResponse($outMessage,$tradeCode){
        $result = $this->leftZero($this->merId,15);
        $result.=$this->leftZero($tradeCode,8);

        $signature = $this->sign($outMessage);
        $result.=$this->leftZero(strlen($signature),4);
        $result.=base64_encode($signature);

        $encrypted =$this->encryptOutMessage($outMessage);
        $result.=$encrypted;

        $gramaLen = strlen($result);
        $result = $this->leftZero($gramaLen,8).$result;

        //$this->log_rsa($result);
        return $result;
    }

    /**
     * 处理请求
     * @param $inMessage
     * @return mixed
     */
    function decryptInMessage($inMessage){

        //$this->log_rsa($inMessage);

        $ciphertext = base64_decode($inMessage);


        $pem = trim(chunk_split($this->shwfPrivateKey,64,"\n"));//转换为pem格式的公钥
        $pem = self::appendFlags($pem,false);
        //$pem = "-----BEGIN PRIVATE KEY-----\n".$pem."\n-----END PRIVATE KEY-----\n";

        $getShwfPrivateKey = openssl_pkey_get_private($pem);

        //计算分段解密的block数 (理论上应该能整除)
        $dataLength = strlen($ciphertext);
        //$nBlock = ($dataLength / $this->decryptBlock);
        //for debug.
        //$this->log_rsa(' '.$dataLength.' '.$decryptBlock);//.' '.$nBlock);

        //输出buffer, , 大小为nBlock个encryptBlock
        $outBuf = '';

        //分段解密
        for ($offset = 0; $offset < $dataLength; $offset += $this->decryptBlock)
        {
            //block大小: decryptBlock 或 剩余字节数
            $data = substr($ciphertext, $offset,$this->decryptBlock);

            //$this->log_rsa($data);
            //得到分段解密结果
            $decryptedBlock = '';
            if (!openssl_private_decrypt($data, $decryptedBlock, $getShwfPrivateKey, $this->padding))
            {
                return ''; //出错返回空
            }
            else
            {
                //追加结果到输出buffer中
                $outBuf .= $decryptedBlock;
            }
        }
        openssl_free_key($getShwfPrivateKey);
        //$this->log_rsa('decryptInMessage:'.$outBuf);
        return $outBuf;
    }

    function encryptOutMessage($outMessage){
        //$yflPublicKey = 'MIIBIjANBgkqhkiG9w0BAQEFAAOCAQ8AMIIBCgKCAQEApSa2U1CsXMNrek0d6XodouoqYVNvjFwyLr6kFbJyUuU5Aakk8NHdvKlD7OEaIVkXbakqrTAkL7eIX67DF3dpWm16PuFkOC6CVaKaELrctmjBDl9NIZqz8U33TpCM/lhqhjhD6aAKK3IVBWbvMS0Cokc1EucGYRVGnLSWpaLTi3oW9xdIUZ1h8FT30CvsUQAjANILo5gkxaM7xPPLxMwXSofHIIuMS82tBGXY92+cYRvgRgECjudE7Td5b/6iBrP1suCF4E9zcIkqJLrRBCDExlTW5mBZQrdcnBlnq3HNAz5rtY6yJFnDuO4QRk/amzmraJpDFwczrGy7oZ28GnEhYQIDAQAB';

        $pem = trim(chunk_split($this->yflPublicKey,64,"\n"));//转换为pem格式的公钥
        $pem = self::appendFlags($pem,true);
        //$pem = "-----BEGIN PUBLIC KEY-----\n".$pem."\n-----END PUBLIC KEY-----\n";
        $getyflPublicKey = openssl_pkey_get_public($pem);
        //$this->log_rsa($getyflPublicKey);


        $dataLength = strlen($outMessage);
        $nBlock = ($dataLength / $this->encryptBlock);
        if (($dataLength % $this->encryptBlock) != 0)  {
            //余数非0block数再加1
            $nBlock = $nBlock + 1;
        }

        //输出buffer, 大小为nBlock个decryptBlock
        $outBuf = '';

        //分段加密
        for ($offset = 0; $offset < $dataLength; $offset += $this->encryptBlock){
            //block大小: encryptBlock 或 剩余字节数
            $data = substr($outMessage, $offset, $this->encryptBlock);

            //得到分段加密结果
            $encryptedBlock = '';
            if (!openssl_public_encrypt($data, $encryptedBlock, $getyflPublicKey, $this->padding)){
                return ''; //出错返回空
            }
            else{
                //追加结果到输出buffer中
                $outBuf .= $encryptedBlock;
            }
        }
        openssl_free_key($getyflPublicKey);
        return base64_encode($outBuf);

    }

    function sign($outMessage){
        //shwf的privatekey 数字签名
        //$shwfPrivateKey = "MIIEvQIBADANBgkqhkiG9w0BAQEFAASCBKcwggSjAgEAAoIBAQCnsqqdv+EwqginBM6+Noj69H9n61p31oJ/njQHRFMFu1A7+Dvg50roFax1UsqVJznpRFFvAcyDpyh4+S0bOt0nFYprtje4/MUzolcKj/0OJrNWE/eZBJLYfaU/gRJxcIhjVBnAb43FlkEWfLQBmXlN4NMgF7yd/+iEw4/qZiLYSPbohrp6uZ1i0+C5LT6nNnltjvSaEPeXebohGw8ZFd43rQ7pxIghnRB2SnUy1tWUviUa2Z9UGlemHEapMH9cRpCx2uqrZcmzaC9xolaGHukydhdgvR4rHLhy63tgZekCx4IsfHf0ZQigoyVCbDuRf+kQItVzrGkq2zI6/PnZSHoDAgMBAAECggEAGXpuq5rsdr1OpTl4w7N7ak1dUgeS7iQcHZGGrBf4WSWVUhcr2caqPcqVMhoLB1A3XzZu/OOUE+iAoKiS7tEpqnd+WVEM9EELIQYoHUW1F8JPeuN7ubbVLKkiQxy0FaIYRqcPZopMStnCN81od9m+cVOYIAcbNBIeXJVttxofbyafzfY16z/+VeMORQdWFpR8v5tN/xylokfZjiILGeH/sEE631Lkcis/ZXJtKgqMFwBhkUaYUO0sayIZWXzXgx5lCKizpHbwNFCRFsNdu5b5adhpsAqhE5N/lKib2IfuLBz9vmxjnqP6iyZ/LYF2joMUvrptOvVAyJ5d9UODl1JOWQKBgQDRqG6JBjxPFOYGb9VkiSJFt3YWPNtfI8CPIsMv93vaB8y34DeVw5M9x+AIZIhwVerNajUUUeCQ4QBMKL17gWKQkXd2Jl/YthecYWH2XXIGlE060VXMnGtxz5SfPTo/HQbwNdvXm+/IumM05hkosFn6fQvVt5/8bqPMRSuPkvUDVwKBgQDMw+mBgYBTHYgjQDne2bMi3lUyHk4G7qIO8Znj6lJE62/aYNgNnnOBKTbBvdVLn6OvriucbVLCG8QqINo+MXx1zWbQw8sfxrYe66DAcjB4xafOfBzObaO/hmj5DuQEFscuc+/xOu1JlscDg9M9QElavmhfulbWdde9fQsdBMrfNQKBgHB2rEx+ds1pBXcAeHSEh0jkf8iv/nELiZh+ajuJwvsS4gLkmIySq6IhXJDD9Nhljh2AUlbDEPZGa9VuqS3eGtQ34+AR96oVC5dMObNhLvBOjxr0/dRTN+OGGVBOFLeUR4uFKJeAw2Bmcx8GKwrDhpCykS2kYYKUivLhS9upVhrjAoGAYcz5DJgT+J7UVTHp8hy8yNy0iHmc/wafdM/Elu1mWfCxvfYfe3HA7WIH+0V2SOZ4wgJIZjB5JKkqaozCcI4mSgXPI8tAi27XsbENWJ2xtR2C5sa044vOeD30iXCpS6Ktg+xwICHrEAjqCS2/iTPZVXQ4WfCgZVJntuOwmS2e0DkCgYEAyUE7ogW+WgPX6lmhbsSYqclLlECcQJ8THGasqu/Ujkj8BjHlPYW/q24lVgXy5Ah2TC03e7dzG7rdbWYDF/d4yPSs43dMp5Enzm6kz6iwMeYde40B25bqDmMeUcea+seYZXBrOiNWGGFssCr/o9e67rNjbz6rU3qdIyW6XlYKyzk=";

        $pem = trim(chunk_split($this->shwfPrivateKey,64,"\n"));//转换为pem格式的公钥
        $pem = self::appendFlags($pem,false);
        //$pem = "-----BEGIN PRIVATE KEY-----\n".$pem."\n-----END PRIVATE KEY-----\n";

        $getShwfPrivateKey = openssl_pkey_get_private($pem);

        //$this->log_rsa($getShwfPrivateKey);

        openssl_sign($outMessage, $signature, $getShwfPrivateKey);
        openssl_free_key($getShwfPrivateKey);

        return $signature;
    }

    function log_rsa($message,$arrInfo=array()) {
        file_put_contents(DATA_DIR . '/api_rsa.log', date('Y-m-d H:i:s',time())."\n\r", FILE_APPEND);
        file_put_contents(DATA_DIR . '/api_rsa.log', $message."\n\r", FILE_APPEND);
        if (!empty($arrInfo)){
            file_put_contents(DATA_DIR . '/api_rsa.log', var_export($arrInfo,1)."\n\r", FILE_APPEND);
        }
    }


    function doPostYfl($post_data){
        $post_json = json_encode($post_data);
        //$this->log_rsa($post_json);

        $result = $this->leftZero($this->merId);
        $result .= $this->leftZero('YFLUSYN1');
        $signature = $this->sign($post_json);
        $result.=$this->leftZero(strlen($signature),4);
        $result.=base64_encode($signature);

        $result.=$this->encryptOutMessage($post_json);
        $gramaLen = strlen($result);
        $result = $this->leftZero($gramaLen,8).$result;
        //$this->log_rsa($result);

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $this->yflUserSyncUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_TIMEOUT,50);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $result);
        curl_setopt($ch, CURLOPT_VERBOSE, false);
        $curl_errno = curl_errno($ch);
        $curl_error = curl_error($ch);
        $output = curl_exec($ch);
        curl_close($ch);
        if($curl_errno >0){
            return false;
        }else{
            return $output;
        }

    }

    /**
     * 功能：报文验签
     * @param data	验签报文
     * @return
     * @throws Exception
     */
    function verifySign($plaintext,$signBase64Str){


        $pem = trim(chunk_split($this->yflPublicKey,64,"\n"));//转换为pem格式的公钥
        $pem = self::appendFlags($pem,false);
        //$pem = "-----BEGIN PRIVATE KEY-----\n".$pem."\n-----END PRIVATE KEY-----\n";

        $getyflPublicKey = openssl_pkey_get_public($pem);

        $signature = base64_decode($signBase64Str);

        $this->log_rsa('plaintext'.$plaintext);
        $this->log_rsa('signBase64Str'.$signature);


        $ret = openssl_verify($plaintext, $signature, $getyflPublicKey, $this->signature_alg);
        openssl_free_key($getyflPublicKey);

        if ($ret === 1)
        {
            return true;
        }
        else
        {
            //($ret == -1)表示出错
            return false;
        }
    }


    /**
     * 初始化加密对象
     * @param merKeyPath	商户私钥
     * @param pubKeyPath	系统公钥
     * 备注： 系统公钥->加密 	商户私钥->签名
     * @throws Exception
     */
    private function initKey($localPrivKeyBase64Str, $peerPubKeyBase64Str) {

    }

    /**
     * 功能：报文解密
     * @return
     */
    function decrypt($InMessage){

        openssl_public_decrypt(base64_decode($InMessage),$decrypted,$this->getShwfPublicKey());
        return $decrypted;

    }

    /**
     * 功能：报文加密
     * @return
     */
    function encrypt(){

    }
}