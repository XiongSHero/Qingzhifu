<?php

// +----------------------------------------------------------------------
// | easy pay [ pay to easy ]
// +----------------------------------------------------------------------
// | Copyright (c) 2016-2017 All rights reserved.
// +----------------------------------------------------------------------
// | Author: fengxing <QQ:51125330>
// +----------------------------------------------------------------------
global $publicData; //存储通用数据
/**
 * 通用函数库，该项目下所有通用函数
 */
$sqltrace = array();

//记录sql执行语句
function setSqlTrace($sql) {
    global $sqltrace;
    $sqltrace[] = $sql;
}

//返回sql执行语句
function getSqlTrace() {
    global $sqltrace;
    return implode('<br>', $sqltrace);
}

//查看是否移动端登陆
function isMobile() {
    // 如果有HTTP_X_WAP_PROFILE则一定是移动设备
    if (isset($_SERVER['HTTP_X_WAP_PROFILE']))
        return true;

    //此条摘自TPM智能切换模板引擎，适合TPM开发
    if (isset($_SERVER['HTTP_CLIENT']) && 'PhoneClient' == $_SERVER['HTTP_CLIENT'])
        return true;
    //如果via信息含有wap则一定是移动设备,部分服务商会屏蔽该信息
    if (isset($_SERVER['HTTP_VIA']))
    //找不到为flase,否则为true
        return stristr($_SERVER['HTTP_VIA'], 'wap') ? true : false;
    //判断手机发送的客户端标志,兼容性有待提高
    if (isset($_SERVER['HTTP_USER_AGENT'])) {
        $clientkeywords = array(
            'nokia',
            'sony',
            'ericsson',
            'mot',
            'samsung',
            'htc',
            'sgh',
            'lg',
            'sharp',
            'sie-',
            'philips',
            'panasonic',
            'alcatel',
            'lenovo',
            'iphone',
            'ipod',
            'blackberry',
            'meizu',
            'android',
            'netfront',
            'symbian',
            'ucweb',
            'windowsce',
            'palm',
            'operamini',
            'operamobi',
            'openwave',
            'nexusone',
            'cldc',
            'midp',
            'wap',
            'mobile'
        );
        //从HTTP_USER_AGENT中查找手机浏览器的关键字
        if (preg_match("/(" . implode('|', $clientkeywords) . ")/i", strtolower($_SERVER['HTTP_USER_AGENT']))) {
            return true;
        }
    }
    //协议法，因为有可能不准确，放到最后判断
    if (isset($_SERVER['HTTP_ACCEPT'])) {
        // 如果只支持wml并且不支持html那一定是移动设备
        // 如果支持wml和html但是wml在html之前则是移动设备
        if ((strpos($_SERVER['HTTP_ACCEPT'], 'vnd.wap.wml') !== false) && (strpos($_SERVER['HTTP_ACCEPT'], 'text/html') === false || (strpos($_SERVER['HTTP_ACCEPT'], 'vnd.wap.wml') < strpos($_SERVER['HTTP_ACCEPT'], 'text/html')))) {
            return true;
        }
    }
    return false;
}

//对传入的变量进行转义
if (phpversion() < '5.3.0') {
    set_magic_quotes_runtime(0);
    @ ini_set('magic_quotes_sybase', 0);
}
if (get_magic_quotes_gpc()) {
    $_GET = stripslashes_deep($_GET);
    $_POST = stripslashes_deep($_POST);
    $_COOKIE = stripslashes_deep($_COOKIE);
} else {
    $_GET = add_magic_quotes($_GET);
    $_POST = add_magic_quotes($_POST);
    $_COOKIE = add_magic_quotes($_COOKIE);
    $_SERVER = add_magic_quotes($_SERVER);
    $_REQUEST = array_merge($_GET, $_POST);
}

/**
 * 去除转义字符；
 * @param array $value 待转义数组
 * @return array 转义后的数组
 * @author fengxing
 */
function stripslashes_deep($value) {
    if (is_array($value)) {
        $value = array_map('stripslashes_deep', $value);
    } elseif (is_object($value)) {
        $vars = get_object_vars($value);
        foreach ($vars as $key => $data) {
            $value->{$key} = stripslashes_deep($data);
        }
    } else {
        $value = stripslashes($value);
    }
    return $value;
}

/**
 * 添加转义字符；
 * @param array $array 待转义数组
 * @return array 转义后的数组
 * @author fengxing
 */
function add_magic_quotes($array) {
    foreach ((array) $array as $k => $v) {
        if (is_array($v)) {
            $array[$k] = add_magic_quotes($v);
        } else {
            $array[$k] = addslashes($v);
        }
    }
    return $array;
}

/**
 * 提取分页信息
 * @param int $count 数据总数
 * @param int $page 当前页数
 * @param int $limit 限定数量
 * @return int
 * @author fengxing
 */
function page($count, $page, $limit = 10) {
    if ($page == '' || !is_numeric($page) || $page <= 1) {
        return 1;
    }
    $n = ceil($count / $limit);
    if ($page > $n) {
        return $n;
    }
    return $page;
}

/**
 * 根据参数判断并获取缓存
 * @param string $cacheName 缓存名称
 * @param string|array $value 缓存内容
 * @return array
 * @author fengxing
 */
function SS($cacheName, $value = '') {
    $path = APP_PATH . 'Runtime/Temp/';
    if (!file_exists($path))
        mkdir($path, 0755, true);
    if ($value === '') {
        return unserialize(file_get_contents($path . md5($cacheName)));
    }
    file_put_contents($path . md5($cacheName), serialize($value));
}

/**
 * 获取带参数固定缓存数据集
 * @param string $param 数组
 * @return array
 * @author fengxing
 */
function SD($param = array()) {
    $apiCache = D('ApiCache');
    $style = $param['style'] . 'Cache';
    return $apiCache->$style($param);
}

/**
 * 描述：使用tool文件夹下的方法集合
 * @param string $className 类名或者第三方工具类的路径
 * @param string $functionName 方法名
 * @param array $param 参数集合
 * @author fengxing
 */
function useToolFunction($className, $functionName = '', $param = array()) {
    $thisClassName = explode('/', $className);
    $thisClassName = $thisClassName[count($thisClassName) - 1];
    if (!class_exists($thisClassName)) {
        $importClassName = str_replace('/', '.', $className);
        import('Common.Tool.' . $importClassName);
    }
    $model = new $thisClassName(); // 实例化
    if ($functionName) {
        return call_user_func_array(array(
            $model,
            $functionName), $param);
    }
    return $model;
}

/**
 * 描述：格式化字符串通用方法，调用Tool/String.class.php中方法
 * @param string $functionName 函数名
 * @author fengxing
 */
function stringChange($functionName) {
    $param = func_get_args();
    $param = array_splice($param, 1);
    return useToolFunction('StringChange', $functionName, $param); //支持3个参数超出用数组
}

/**
 * 描述：检测字符串通用方法，调用Tool/StringCheck.class.php中方法
 * @param string $functionName 函数名
 * @author fengxing
 */
function checkString($functionName) {
    $param = func_get_args();
    $param = array_splice($param, 1);
    return useToolFunction('StringCheck', $functionName, $param); //支持3个参数超出用数组
}

/**
 * 日期相关处理 调用Tool/DateHandle.class.php中方法
 * @param string $functionName 函数名
 * @notice 以后对应日期处理相关函数 请封装至Tool/DateHandle.class.php类中
 * @author fengxing
 */
function handleDate($functionName) {
    $param = func_get_args();
    $param = array_splice($param, 1);
    return useToolFunction('DateHandle', $functionName, $param); //支持3个参数超出用数组
}

/**
 * 分页相关处理
 * @param $functionName
 * 请统一此方法处理分页相关处理
 * @author fengxing
 */
function thisPage($functionName) {
    //@notice 调用Page类相关方法,请先调用init方法
    $param = func_get_args();
    $param = array_splice($param, 1);
    return useToolFunction('ThisPage', $functionName, $param); //支持3个参数超出用数组
}

/**
 * 描述：控制器扩展对空模型进行提示
 * @author fengxing
 */
function __hack_module() {
    emptyUrl();
}

/**
 * 描述：控制器扩展对空方法进行提示
 * @author fengxing
 */
function __hack_action() {
    emptyUrl();
}

/**
 * 描述：不存在的链接进行错误提示
 * @author fengxing
 */
function emptyUrl() {
    $map = C('LOSE_URL_JUMP');
    if (isset($map[CONTROLLER_NAME])) {
        header('Location:' . $map[CONTROLLER_NAME]);
        exit;
    }
    if (IS_AJAX) {
        R('Common/SystemLayer/ajaxSetError', array(
            '00139',
            1));
    }
    R('Common/SystemLayer/jumpError');
    exit();
}

/**
 * 上传数据到远程
 * @param string $url 提交地址
 * @param array  $data 提交数据
 * @return String
 * @author fengxing
 */
function CURL($url, $data = '', $style = '') {
    $headers = array();
    $headers[] = 'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8';
    //$headers[] = 'Accept-Encoding: gzip, deflate';
    $headers[] = 'Accept-Language: zh-CN,zh;q=0.8,en-US;q=0.5,en;q=0.3';
    $headers[] = 'Cache-Control: no-cache';
    if($style=='json'){
        $headers[] = 'Content-Type: application/json; charset=utf-8';
        $data=json_encode($data,JSON_UNESCAPED_UNICODE);
    }else{
        $headers[] = 'Content-Type: multipart/form-data; charset=utf-8';
    }
    $headers[] = 'User-Agent: Mozilla/5.0 (X11; Ubuntu; Linux i686; rv:28.0) Gecko/20100101 Firefox/55.0';
    $headers[] = 'X-MicrosoftAjax: Delta=true';

    $curl = curl_init();
    if (!empty($data)) {
        curl_setopt($curl, CURLOPT_POST, 1);
        curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
    }
    curl_setopt($curl, CURLOPT_HEADER, 0);
    curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($curl, CURLOPT_URL, $url);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($curl, CURLOPT_USERAGENT, "Mozilla/5.0");
    if (strstr($url, 'https://')) {
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, FALSE); // https请求 不验证证书和hosts
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, FALSE);
    }

    $result = curl_exec($curl);
    $error = curl_error($curl);
    $output = $error ? $error : $result;
    return $output;
}

/**
 * Token检查
 * @return bool
 * @author fengxing
 */
function checkToken($data) {
    if (C('TOKEN_ON')) {
        $name = C('TOKEN_NAME');
        if (!isset($data[$name]) || !isset($_SESSION[$name])) { // 令牌数据无效
            return false;
        }
        // 令牌验证
        list($key, $value) = explode('_', $data[$name]);

        if (isset($_SESSION[$name][$key]) && $value && $_SESSION[$name][$key] === $value) { // 防止重复提交
            if (!IS_AJAX) {//Ajax重复提交兼容 否则前台所有Ajax都要追加设置变更Token
                unset($_SESSION[$name][$key]); // 验证完成销毁session
            }
            return true;
        }
        // 开启TOKEN重置
        if (C('TOKEN_RESET'))
            unset($_SESSION[$name][$key]);
        return false;
    }
    //Toekn未开启直接返回true
    return true;
}

/**
 * 描述：获取静态方法
 * @return string
 * @author fengxing
 */
function getStaticFunction($modelName, $functionName) {
    $modell = D($modelName);
    return $modell::$functionName;
}

/**
 * 通用获取API方法 用于__call
 * @param string $functionName 当前调用的方法名称
 * @param string $args 参数数组
 * @return mixed
 * @author fengxing
 */
function getApi($functionName, $args) {

    $module = str_replace('getApi', '', $functionName);

    //处理$args[0]目前结构为Grade/grade
    $urlArray = explode('/', $args[0]);

    $model = A($module . '/' . $urlArray[0], 'Api');
    if ($model === false)
        return '';
    $param = $args;
    $param = array_slice($param, 1);
    return call_user_func_array(array(
        $model,
        $urlArray[1]), $param);
}

/**
 * 兼容低版本的函数<5.5
 * @author fengxing
 */
if (!function_exists('array_column')) {

    function array_column($input, $columnKey, $indexKey = null) {
        $columnKeyIsNumber = (is_numeric($columnKey)) ? true : false;
        $indexKeyIsNull = (is_null($indexKey)) ? true : false;
        $indexKeyIsNumber = (is_numeric($indexKey)) ? true : false;
        $result = array();
        foreach ((array) $input as $key => $row) {
            if ($columnKeyIsNumber) {
                $tmp = array_slice($row, $columnKey, 1);
                $tmp = (is_array($tmp) && !empty($tmp)) ? current($tmp) : null;
            } else {
                $tmp = isset($row[$columnKey]) ? $row[$columnKey] : null;
            }
            if (!$indexKeyIsNull) {
                if ($indexKeyIsNumber) {
                    $key = array_slice($row, $indexKey, 1);
                    $key = (is_array($key) && !empty($key)) ? current($key) : null;
                    $key = is_null($key) ? 0 : $key;
                } else {
                    $key = isset($row[$indexKey]) ? $row[$indexKey] : 0;
                }
            }
            $result[$key] = $tmp;
        }
        return $result;
    }

}

/**
 * 获取当前路径
 * @param string $dep 路径间隔
 * @return string
 * @author fengxing
 */
function SP($dep = '/') {
    if (empty($dep)) {
        $dep = C('URL_PATHINFO_DEPR');
    }
    return MODULE_NAME . $dep . CONTROLLER_NAME . $dep . ACTION_NAME;
}

/**
 * 获取当前模型对应db类
 * @author fengxing
 */
function SM() {
    //处理$args[0]目前结构为Grade/grade
    $param = func_get_args();

    $urlArray = explode('/', $param[0]);
    $modelName = $urlArray[0];

    if (file_exists(COMMON_PATH . 'Model/' . $modelName . 'Model.class.php')) {
        $model = D($modelName);
    } else {
        $model = D('Base');
        $model->setModelName($modelName);
    }

    if (count($urlArray) == 1)
        return $model;

    $param = array_slice($param, 1);
    return call_user_func_array(array(
        $model,
        $urlArray[1]), $param);
}

/**
 * 获取当前模型对应逻辑类
 * @author fengxing
 */
function SL() {
    //处理$args[0]目前结构为ade/ade
    $param = func_get_args();

    $urlArray = explode('/', $param[0]);
    $modelName = $urlArray[0];

    if (file_exists(COMMON_PATH . 'Logic/' . $modelName . 'Logic.class.php')) {
        $model = D($modelName, 'Logic');
    } else {
        //错误记录
        $data = array();
        $data['description'] = '逻辑层声明失败，模型【' . $modelName . '】不存在';
        D('LogError')->setLine($data);
        E('逻辑层声明失败，模型【' . $modelName . '】不存在');
        exit();
    }

    if (count($urlArray) == 1)
        return $model;

    $param = array_slice($param, 1);
    var_dump($modelName, $param);
    return call_user_func_array(array(
        $model,
        $urlArray[1]), $param);
}

/**
 * 获取接口数据
 * @return null
 * @author fengxing
 */
function SA() {
    //处理$args[0]目前结构为Grade/grade
    $param = func_get_args();
    $params = explode('/', $param[0]);
    $modelName = $params[0];
    if (file_exists(COMMON_PATH . 'Api/' . $modelName . 'Api.class.php')) {
        $model = D($modelName, 'Api');
    } else {
        //错误记录
        $data = array();
        $data['description'] = '接口层声明失败，接口【' . $modelName . '】不存在';
        D('LogError')->setLine($data);
        E('接口层声明失败，接口【' . $modelName . '】不存在');
        exit();
    }

    if (count($params) == 1)
        return $model;

    $param = array_slice($param, 1);
    return call_user_func_array(array(
        $model,
        $params[1]), $param);
}

/**
 * 获取路径参数数据
 * @return null
 * @author fengxing
 */
function SU($num) {
    $param = $_SERVER['REQUEST_URI'];
    $param = str_replace('.' . C('URL_HTML_SUFFIX'), '', $param); //去掉后缀
    $param = preg_replace('/\?\=/', C('URL_PATHINFO_DEPR'), $param); //替换问号和等号
    $paramArray = explode(C('URL_PATHINFO_DEPR'), $param);
    return $paramArray[$num];
}

//兼容mysql获取方法
if (!function_exists('mysql_get_server_info')) {

    function mysql_get_server_info() {
        return '';
    }

}

/**
 * 获取订单随机数
 */
function getDingdanRand() {
    global $publicData;
    $length = (int) $publicData['peizhi']['ddhlength'] - 17;
    $min = 1;
    $max = 9;
    for ($i = 1; $i < $length; $i++) {
        $min = (string) $min . '0';
        $max = (string) $max . '9';
    }
    return rand($min, $max);
}

//aes ecb加密
function aes_ecb_encrypt($con, $key) {
    $AESed = base64_encode(mcrypt_encrypt(MCRYPT_RIJNDAEL_128, $key, $con, MCRYPT_MODE_ECB)); #加密
    return $AESed;
}

//aes ecb解密
function aes_ecb_decrypt($con, $key) {
    $decrypted = mcrypt_decrypt(MCRYPT_RIJNDAEL_128, $key, base64_decode($con), MCRYPT_MODE_CBC);
    return $decrypted;
}

function is_iphone() {
    $agent = strtolower($_SERVER['HTTP_USER_AGENT']);
    if (strpos($agent, 'iphone') !== false) {
        return true;
    }
    if (strpos($agent, 'ipad') !== false) {
        return true;
    }
    if (strpos($agent, 'ipod') !== false) {
        return true;
    }
    return false;
}

function is_wechat() {
    $agent = strtolower($_SERVER['HTTP_USER_AGENT']);
    if (strpos($agent, 'micromessenger') !== false) {
        return true;
    }
    return false;
}

function is_qq() {
    $agent = strtolower($_SERVER['HTTP_USER_AGENT']);
    if (strpos($agent, 'QQ') !== false) {
        return true;
    }
    return false;
}

function is_android() {
    $agent = strtolower($_SERVER['HTTP_USER_AGENT']);
    if (strpos($agent, 'Android') !== false) {
        return true;
    }
    if (strpos($agent, 'SymbianOS') !== false) {
        return true;
    }
    return false;
}

/**
 * 描述：二维码处理
 * @return array
 * @author fengxing
 */
function getQrcode($url, $width = 200, $height = 200) {
    global $publicData;
    switch($publicData['peizhi']['ewmcreate']){
        case 'baidu':
            $tjurl = 'http://pan.baidu.com/share/qrcode?w=' . $width . '&h=' . $height . '&url=' . urlencode($url);
            break;
        case 'liantu':
            $tjurl = 'http://qr.liantu.com/api.php?text=' . urlencode($url) . '&w=' . $width . '&m=10';
            break;
        case 'lwl12':
            $tjurl = 'https://api.lwl12.com/img/qrcode/get?ct=' . urlencode($url) . '&w=' . $width . '&h=' . $height;
            break;
        case 'topscan':
            $tjurl = 'http://qr.topscan.com/api.php?text=' . urlencode($url) . '&w=' . $width . '&m=10';
            break;
        case 'k780':
            $tjurl = 'https://sapi.k780.com/?data=' . urlencode($url) . '&app=qr.get&level=L&size=6';
            break;
        case 'my':
            $tjurl = $publicData['peizhi']['httpstyle'] . '://' . $_SERVER['HTTP_HOST'] . '/Index/Index/qrcode?url=' . urlencode($url) . '&w=' . $width . '&k=' . md5(C('FX_QRCODE_KEY') . $url);
            break;
        default:
            $tjurl = 'http://qr.topscan.com/api.php?text=' . urlencode($url) . '&w=' . $width . '&m=10';
            break;
    }
    return $tjurl;
}