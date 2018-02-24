<?php
/**
 * User: Yirius
 * Date: 2018/2/24
 * Time: 16:01
 */

namespace iceshttp;


class Encrypt
{
    /**
     * 公钥记录
     * @resource
     */
    protected $pu_key = null;
    /**
     * 私钥记录
     * @resource
     */
    protected $pr_key = null;

    protected $splitChar = "&";

    /**
     * @title 设置默认分隔符
     * @description 设置默认分隔符
     * @createtime: 2018/2/24 16:45
     * @param $splitChar
     * @return $this;
     */
    public function setSplitChar($splitChar)
    {
        $this->splitChar = $splitChar;
        return $this;
    }

    /**
     * @title 对一个数组进行排序,然后把它的值序列化
     * @description 对一个数组进行排序,然后把它的值序列化
     * @createtime: 2018/2/24 16:43
     * @param array $data
     * @return string
     */
    public function SortToString($data){
        ksort($data);
        $temp = [];
        foreach($data as $i => $v){
            if(!empty($v)){
                $temp[] = $i . "=" . $v;
            }
        }
        return join($this->splitChar, $temp);
    }

    /**
     * @title 返回排序后附加signKey上的值
     * @description 返回排序后附加signKey上的值
     * @createtime: 2018/2/24 16:42
     * @param array $data
     * @param string $signKey
     * @return string
     */
    public function AppendKey($data, $signKey){
        return $this->SortToString($data).$signKey;
    }

    /**
     * @title 获取到md5加密之后的净值,大写
     * @description 获取到md5加密之后的净值,大写
     * @createtime: 2018/2/24 16:43
     * @param array $data
     * @param string $signKey
     * @return string
     */
    public function UpperMd5($data, $signKey){
        return strtoupper(md5($this->AppendKey($data, $signKey)));
    }

    /**
     * @title 在一定情况下,json的值会携带\\,需要去除
     * @description 去掉json中携带的双重反斜杠
     * @createtime: 2018/2/24 16:45
     * @param array $data
     * @return mixed
     */
    public function json($data){
        return str_replace("\\", "", json_encode($data, JSON_UNESCAPED_UNICODE));
    }

    /**
     * @title 对md5加密数据进行验证
     * @description 对md5加密的数据进行验证,判断返回数据是否真实
     * @createtime: 2018/2/24 16:46
     * @param string $verfiy
     * @param array $data
     * @param null $signKey
     * @return bool
     */
    public function Md5KeyVerfiy($verfiy, $data, $signKey = null){
        if($signKey){
            $getPrivate = $this->AppendKey($data, $signKey);
        }else{
            $getPrivate = $this->SortToString($data);
        }
        if(strtoupper($verfiy) == strtoupper(md5($getPrivate))){
            return true;
        }else{
            return false;
        }
    }

    /**
     * @title 设置公钥
     * @description 设置公钥
     * @createtime: 2018/2/24 16:47
     * @param string $publicKey
     * @return bool
     */
    public function setPublicKey($publicKey){
        $resource = openssl_pkey_get_public($publicKey);
        if($resource){
            $this->pu_key = $resource;
            return true;
        }else{
            return false;
        }
    }

    /**
     * @title 设置私钥
     * @description 设置私钥
     * @createtime: 2018/2/24 16:47
     * @param string $privateKey
     * @return bool
     */
    public function setPrivateKey($privateKey){
        $resource = openssl_pkey_get_private($privateKey);
        if($resource){
            $this->pr_key = $resource;
            return true;
        }else{
            return false;
        }
    }

    /**
     * @title 利用公钥进行117位为分割的加密
     * @description 待加密字符串过大,117位分割一次进行加密后串联
     * @createtime: 2018/2/24 16:49
     * @param string $str
     * @return bool|string
     */
    public function puKey117Encrypt($str)
    {
        if(empty($this->pu_key)){
            return false;
        }
        $encryptData = '';
        foreach (str_split($str, 117) as $value){
            openssl_public_encrypt($value, $crypted, $this->pu_key);
            $encryptData .= $crypted;
        }
        return base64_encode($encryptData);
    }

    /**
     * @title 利用私钥进行加密
     * @description 用私钥对一个字符串进行加密,默认是Sha1
     * @createtime: 2018/2/24 16:49
     * @param string $str
     * @param int $type
     * @return bool|string
     */
    public function privateKeyEncrypt($str, $type = OPENSSL_ALGO_SHA1){
        if(empty($this->pr_key)){
            return false;
        }
        $sign = '';
        openssl_sign($str, $sign, $this->pr_key, $type);
        $signData = base64_encode($sign);//最终的签名
        return $signData;
    }

    /**
     * @title 私钥解密
     * @description 私钥解密
     * @createtime: 2018/2/24 16:30
     * @param $encryptKey
     * @return mixed
     */
    public function privateKeyDecrypt($encryptKey){
        openssl_private_decrypt(base64_decode($encryptKey), $decrypted, $this->pr_key);
        return $decrypted;
    }

    /**
     * @title 公钥解密
     * @description 公钥解密
     * @createtime: 2018/2/24 16:30
     * @param $encryptKey
     * @return mixed
     */
    public function publicKeyDecrypt($encryptKey){
        openssl_public_decrypt(base64_decode($encryptKey), $decrypted, $this->pu_key);
        return $decrypted;
    }

    /**
     * @title 利用公钥对返回的字符串进行验证
     * @description 利用公钥对返回的字符串进行验证
     * @createtime: 2018/2/24 16:52
     * @param $jsonStr
     * @param $sign
     * @return bool
     */
    public function publicKeyVerfiy($jsonStr, $sign){
        return (bool)openssl_verify($jsonStr, $sign, $this->pu_key);
    }

    /**
     * @title 3des加密
     * @description 3des加密
     * @createtime: 2018/2/24 16:53
     * @param $strinfo
     * @param $desKey
     * @param string $type
     * @param bool $keyPad
     * @return mixed
     */
    public function tripleDes($strinfo, $desKey, $type = "tripledes", $keyPad = true){//数据加密
        $size = \mcrypt_get_block_size($type);//获取到曲矿内容
        $strinfo = $this->pkcs5_pad($strinfo, $size);//pcks5填充
        if($keyPad){
            $key = str_pad($desKey, 24, '0');//对秘钥进行一次填充
        }else{
            $key = $desKey;
        }
        $td = mcrypt_module_open($type, '', MCRYPT_MODE_ECB, '');
        $iv = @mcrypt_create_iv (mcrypt_enc_get_iv_size($td), MCRYPT_RAND);//获取加密曲面向量
        @mcrypt_generic_init($td, $key, $iv);
        $data = mcrypt_generic($td, $strinfo);
        mcrypt_generic_deinit($td);
        mcrypt_module_close($td);
        //    $data = base64_encode($this->PaddingPKCS7($data));
        $data = base64_encode($data);
        return str_replace("\\", "", $data);
    }

    private function pkcs5_pad ($text, $blocksize) {
        $pad = $blocksize - (strlen($text) % $blocksize);
        return $text . str_repeat(chr($pad), $pad);
    }

    /**
     * @title 对3des加密的数据进行解密
     * @description 对3des加密的数据进行解密
     * @createtime: 2018/2/24 16:54
     * @param $sStr
     * @param $sKey
     * @return string
     */
    public function tripleDesDecrypt($sStr, $sKey) {
        $decrypted= mcrypt_decrypt(
            MCRYPT_RIJNDAEL_128,
            $sKey,
            base64_decode($sStr),
            MCRYPT_MODE_ECB
        );

        $dec_s = strlen($decrypted);
        $padding = ord($decrypted[$dec_s-1]);
        $decrypted = substr($decrypted, 0, -$padding);
        return $decrypted;
    }

    /**
     * @title 用openssl对aes加密
     * @description
     * @createtime: ct
     * @param $strSrc
     * @param $aesKey
     * @return string
     */
    public function aesEncrypt($strSrc, $aesKey)
    {
        $encrypted = openssl_encrypt($strSrc, 'AES-128-ECB', base64_decode($aesKey), OPENSSL_RAW_DATA);
        return base64_encode($encrypted);
    }

    /**
     * @title 对3des加密进行解密
     * @description 对3des加密进行解密
     * @createtime: 2018/2/24 16:55
     * @param $strSrc
     * @param $aesKey
     * @return string
     */
    public function aesDecrypt($strSrc, $aesKey)
    {
        $encrypted = base64_decode($strSrc);
        $decrypted = openssl_decrypt($encrypted, 'AES-128-ECB', base64_decode($aesKey), OPENSSL_RAW_DATA);
        return $decrypted;
    }
}
