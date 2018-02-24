<?php
/**
 * User: Yirius
 * Date: 2018/2/24
 * Time: 14:08
 */

namespace iceshttp;


use think\facade\Env;

class Http
{
    /**
     * cURL的资源句柄
     * @type resource
     */
    protected $curl;

    /**
     * 返回信息出现错误
     * @type bool
     */
    protected $error = false;
    /**
     * 记录错误码,当错误码不为0,就是错误
     * @type int
     */
    protected $errorCode = 0;

    /**
     * 记录错误信息
     * @type null
     */
    protected $errorMessage = null;

    /**
     * 记录curl的错误
     * @type bool
     */
    protected $curlError = false;

    /**
     * curl错误码
     * @type int
     */
    protected $curlErrorCode = 0;

    /**
     * curl错误信息
     * @type null
     */
    protected $curlErrorMessage = null;

    /**
     * http返回信息
     * @type bool
     */
    protected $httpError = false;

    /**
     *  http返回信息是否错误
     * @type int
     */
    protected $httpStatusCode = 0;

    /**
     * http错误的发挥信息
     * @type null
     */
    protected $httpErrorMessage = null;

    /**
     * @type
     */
    protected $options;

    /**
     * @type
     */
    protected $headers;

    /**
     * @type
     */
    protected $rawResponse;

    /**
     * 设置正式发送之前的前置操作
     * @type \Closure
     */
    protected $beforeSendCallback = null;

    /**
     * 如果是下载,当前在完成之后会执行这个操作
     * @type \Closure
     */
    protected $downloadCompleteCallback = null;

    /**
     * 当操作执行完成之后,无论成功还是失败,都会调用这个方法
     * @type \Closure
     */
    protected $completeCallback = null;

    /**
     * @type null
     */
    protected $fileHandle = null;

    /**
     * @type null
     */
    protected $dataCallback = null;

    /**
     * @type array
     */
    protected $opts = [
        CURLOPT_URL,
        CURLOPT_RETURNTRANSFER,
        CURLOPT_FOLLOWLOCATION,
        CURLOPT_AUTOREFERER,
        CURLOPT_USERAGENT,
        CURLOPT_TIMEOUT,
        CURLOPT_HTTP_VERSION,
        CURLOPT_HTTPHEADER,
        CURLOPT_IPRESOLVE,
        CURLOPT_REFERER,
        CURLOPT_SSL_VERIFYHOST,
        CURLOPT_SSL_VERIFYPEER,
        CURLOPT_POST,
        CURLOPT_POSTFIELDS,
        CURLOPT_CUSTOMREQUEST,
        CURLOPT_HTTPAUTH,
        CURLOPT_USERPWD
    ];

    /**
     * @type string
     */
    private $jsonPattern = '/^(?:application|text)\/(?:[a-z]+(?:[\.-][0-9a-z]+){0,}[\+\.]|x-)?json(?:-[a-z]+)?/i';

    /**
     * @type string
     */
    private $xmlPattern = '~^(?:text/|application/(?:atom\+|rss\+)?)xml~i';

    function __construct()
    {
        if (!extension_loaded('curl')) {
            throw new \ErrorException('cURL library is not loaded');
        }

        //初始化curl
        $this->curl = curl_init();
        //首先设置可以returntransfer
        $this->setOpt(CURLOPT_RETURNTRANSFER, true);
        //直接关闭SSL校验
        $this->setOpt(CURLOPT_SSL_VERIFYPEER, false);
        $this->setOpt(CURLOPT_SSL_VERIFYHOST, false);
    }

    /**
     * @title 设置参数
     * @description 设置参数项
     * @createtime: 2018/2/24 14:45
     * @param $key
     * @param $value
     * @return $this
     */
    public function setOpt($key, $value){
        if(in_array($key, $this->opts)){
            $this->options[$key] = $value;
            curl_setopt($this->curl, $key, $value);
        }
        return $this;
    }

    /**
     * @title 设置需要提交的网址
     * @description 设置需要提交的网址
     * @createtime: 2018/2/24 14:46
     * @param string $url
     * @return \iceshttp\Http
     */
    public function setUrl($url){
        return $this->setOpt(CURLOPT_URL, $url);
    }

    /**
     * @title 设置数据发送的头部
     * @description 设置数据发送的头部
     * @createtime: 2018/2/24 15:00
     * @param $key
     * @param $value
     * @return $this
     */
    public function setHeader($key, $value = null){
        //首先记录头部
        $this->headers[$key] = $value;
        $headers = [];
        foreach ($this->headers as $key => $value) {
            $headers[] = $key . ': ' . $value;
        }
        $this->setOpt(CURLOPT_HTTPHEADER, $headers);
        return $this;
    }

    /**
     * @title 取消设置header头部
     * @description 取消设置header头部
     * @createtime: 2018/2/24 15:02
     * @param $key
     * @return $this
     */
    public function unsetHeader($key){
        unset($this->headers[$key]);
        $headers = [];
        foreach ($this->headers as $key => $value) {
            $headers[] = $key . ': ' . $value;
        }
        $this->setOpt(CURLOPT_HTTPHEADER, $headers);
        return $this;
    }

    /**
     * @title 设置Auth权限
     * @description 设置Auth权限
     * @createtime: 2018/2/24 15:09
     * @param $username
     * @param string $password
     */
    public function setDigestAuthentication($username, $password = '')
    {
        $this->setOpt(CURLOPT_HTTPAUTH, CURLAUTH_DIGEST);
        $this->setOpt(CURLOPT_USERPWD, $username . ':' . $password);
    }

    /**
     * @title 设置连接端口
     * @description 设置连接端口
     * @createtime: 2018/2/24 15:10
     * @param int $port
     */
    public function setPort($port)
    {
        $this->setOpt(CURLOPT_PORT, intval($port));
    }

    /**
     * @title 设置来源链接
     * @description 设置来源链接
     * @createtime: 2018/2/24 15:11
     * @param $referer
     */
    public function setReferer($referer)
    {
        $this->setOpt(CURLOPT_REFERER, $referer);
    }

    /**
     * @title 设置User-Agent
     * @description 设置User-Agent
     * @createtime: 2018/2/24 15:12
     * @param $user_agent
     */
    public function setUserAgent($user_agent)
    {
        $this->setOpt(CURLOPT_USERAGENT, $user_agent);
    }

    /**
     * @title 创建post的数据
     * @description 创建post的数据
     * @createtime: 2018/2/24 14:58
     * @param $data
     * @return array|string
     */
    public function buildPostData($data)
    {
        if($this->dataCallback != null && $this->dataCallback instanceof \Closure){
            return $this->dataCallback($data, $this);
        }
        $binary_data = false;
        if (is_array($data)) {
            // Return JSON-encoded string when the request's content-type is JSON.
            if (isset($this->headers['Content-Type']) && preg_match($this->jsonPattern, $this->headers['Content-Type'])) {
                $json_str = json_encode($data);
                if (!($json_str === false)) {
                    $data = $json_str;
                }
            } else {
                foreach ($data as $key => $value) {
                    //如果使用@开头,并且是一个字符串,同时存在这个文件
                    if (is_string($value) &&
                        strpos($value, '@') === 0 &&
                        is_file(substr($value, 1))
                    ) {
                        $binary_data = true;
                        if (class_exists('CURLFile')) {
                            $data[$key] = new \CURLFile(substr($value, 1));
                        }
                    } elseif ($value instanceof \CURLFile) {
                        $binary_data = true;
                    }
                }
            }
        }
        if (!$binary_data && (is_array($data) || is_object($data))) {
            $data = http_build_query($data);
        }
        return $data;
    }

    /**
     * @title 用get方法获取
     * @description 用get方法获取
     * @createtime: 2018/2/24 15:42
     * @param $url
     * @param array $data
     * @return $this
     */
    public function get($url, $data = [])
    {
        if(!empty($data)){
            $this->setUrl($url . "?" . http_build_query($data));
        }else{
            $this->setUrl($url);
        }
        $this
            ->setOpt(CURLOPT_CUSTOMREQUEST, 'GET')
            ->setOpt(CURLOPT_HTTPGET, true)
            ->exec();
        return $this;
    }

    /**
     * @title post
     * @description
     * @createtime: 2018/2/24 14:53
     * @param $url
     * @param array $data
     * @return $this
     */
    public function post($url, $data = []){
        //首先设置类型是POST
        $this->setOpt(CURLOPT_CUSTOMREQUEST, 'POST');
        //设置提交网址
        $this->setUrl($url);
        //设置提交参数
        $this->setOpt(CURLOPT_POST, true);
        $this->setOpt(CURLOPT_POSTFIELDS, $this->buildPostData($data));
        //执行
        $this->exec();
        return $this;
    }

    /**
     * @title 文件下载
     * @description 文件下载
     * @createtime: 2018/2/24 15:51
     * @param $url
     * @param $filename
     * @param string $path
     * @return $this
     */
    public function download($url, $filename, $path = 'public/uploads/')
    {
        $this->fileHandle = true;
        $this->get($url)->getResponse(function($raw) use ($filename,$path){
            $resource = fopen(Env::get("root_path") . $path . $filename, 'a');
            fwrite($resource, $raw);
            fclose($resource);
        });
        //触发完成回调
        $this->call($this->downloadCompleteCallback);
        return $this;
    }

    /**
     * @title 获取到错误信息
     * @description 如果存在错误,那就直接返回错误语句,如果不存在,就返回false,所以应该用===false来判断
     * @createtime: 2018/2/24 14:49
     * @return bool|string
     */
    public function getError(){
        if($this->error){
            return $this->errorMessage;
        }else{
            return false;
        }
    }

    /**
     * @title 参数回调
     * @description 参数回调
     * @createtime: 2018/2/24 15:17
     * @param $func
     * @param $opts
     * @return $this
     */
    public function call($func, $opts = null){
        if($func instanceof \Closure){
            $func($this, $opts);
        }
        return $this;
    }

    /**
     * @title 真正的执行方法
     * @description
     * @createtime: 2018/2/24 15:34
     */
    private function exec(){
        $this->call($this->beforeSendCallback);
        $this->rawResponse = curl_exec($this->curl);
        $this->curlErrorCode = curl_errno($this->curl);
        $this->curlErrorMessage = curl_error($this->curl);

        $this->curlError = !($this->curlErrorCode === 0);

        if ($this->curlError && function_exists('curl_strerror')) {
            $this->curlErrorMessage =
                curl_strerror($this->curlErrorCode) . (
                empty($this->curlErrorMessage) ? '' : ': ' . $this->curlErrorMessage
                );
        }

        $this->httpStatusCode = $this->getInfo(CURLINFO_HTTP_CODE);
        $this->httpError = in_array(floor($this->httpStatusCode / 100), array(4, 5));

        //设置完成触发
        $this->execDone();

        return $this;
    }

    /**
     * @title 完成执行之后的惭怍
     * @description 完成执行之后的惭怍
     * @createtime: 2018/2/24 15:20
     */
    private function execDone()
    {
        if ($this->error) {
            $this->call($this->completeCallback, false);
        } else {
            $this->call($this->completeCallback, true);
        }
    }

    /**
     * @title getResponse
     * @description
     * @createtime: 2018/2/24 15:35
     * @param \Closure|null $decode
     */
    public function getResponse(\Closure $decode = null){
        if(!empty($decode) && $decode instanceof \Closure){
            return $decode($this->rawResponse, $this);
        }
        return $this->rawResponse;
    }

    /**
     * @title xml_decode
     * @description xml_decode
     * @createtime: 2018/2/24 16:00
     * @param $xml
     * @return mixed
     */
    function xml_decode($xml)
    {
        //禁止引用外部xml实体
        libxml_disable_entity_loader(true);
        $values = json_decode(json_encode(simplexml_load_string($xml, 'SimpleXMLElement', LIBXML_NOCDATA)), true);
        return $values;
    }

    /**
     * @title 获取到信息参数
     * @description 利用指定信息获取到参数
     * @createtime: 2018/2/24 15:20
     * @param null $opt
     * @return mixed
     */
    public function getInfo($opt = null)
    {
        $args = array();
        $args[] = $this->curl;
        if (func_num_args()) {
            $args[] = $opt;
        }
        return call_user_func_array('curl_getinfo', $args);
    }

    /**
     * @title 设置提交之前的回调
     * @description 设置提交之前的回调
     * @createtime: 2018/2/24 15:53
     * @param \Closure $beforeSendCallback
     * @return $this
     */
    public function setBeforeSendCallback(\Closure $beforeSendCallback)
    {
        $this->beforeSendCallback = $beforeSendCallback;
        return $this;
    }

    /**
     * @title 设置完成的回调
     * @description 设置完成的回调
     * @createtime: 2018/2/24 15:53
     * @param \Closure $completeCallback
     * @return $this
     */
    public function setCompleteCallback(\Closure $completeCallback)
    {
        $this->completeCallback = $completeCallback;
        return $this;
    }

    /**
     * @title 设置下载完成的回调
     * @description 设置下载完成的回调
     * @createtime: 2018/2/24 15:53
     * @param $downloadCompleteCallback
     * @return $this
     */
    public function setDownloadCompleteCallback($downloadCompleteCallback)
    {
        $this->downloadCompleteCallback = $downloadCompleteCallback;
        return $this;
    }

    /**
     * @title 设置JSON的编辑措施
     * @description 设置JSON的编辑措施
     * @createtime: 2018/2/24 15:53
     * @param $jsonCall
     * @return $this
     */
    public function setDataCall($jsonCall)
    {
        $this->dataCallback = $jsonCall;
        return $this;
    }
}
