<?php
namespace now;

use now\cache as cache;

/**
 * 一些内置的静态方法
 * @author      欧远宁
 * @version     1.0.0
 * @copyright   CopyRight By 欧远宁
 * @package     now
 */
final class fun {
    
    /**
     * 得到配置文件的信息
     * 新增的配置文件，请放在/cfg/目录下。
     * @author 欧远宁
     * @param string $name 配置名
     * @param string $key  key
     */
    public static function get_cfg($name, $key=''){
        require_once(OP.'cfg'.DS.$name.'.php');
        if ($key == ''){
            return $GLOBALS['cfg'][$name];
        } else {
            return $GLOBALS['cfg'][$name][$key];
        }
    }
    
    /**
     * 暴露出来的一个设置缓存接口，使用sys模块的缓存设置
     * @author 欧远宁
     * @param string $key cache的key
     * @param any $val    cache的值
     * @param int $time   过期时间，单位秒
     */
    public static function set_sys_cache($key, $val, $time) {
        $cache = cache::get_ins('sys');
        $pre = 'user_';        //给一个前缀，避免跟其他系统cache重名
        $cache->set($pre.$key, $val, $time);
    }
    
    /**
     * 获取缓存
     * @author 欧远宁
     * @param string $key cache的key
     * @return  缓存内容
     */
    public static function get_sys_cache($key) {
        $cache = cache::getIns('sys');
        $pre = 'user_';        //给一个前缀，避免跟其他系统cache重名
        return $cache->get($pre.$key);
    }
    
    /**
     * 删除缓存
     * @author 欧远宁
     * @param string $key cache的key
     */
    public static function del_sys_cache($key) {
        $cache = cache::getIns('sys');
        $pre = 'user_';        //给一个前缀，避免跟其他系统cache重名
        return $cache->del($pre.$key);
    }
    
    /**
     * 得到一笔配置好的SQL
     * @author 欧远宁
     * @param string $mdl
     * @param string $key
     */
    public static function get_sql($mdl, $key){
        return $GLOBALS['cfg'][$mdl]['sql'][$key];
    }
    
    /**
     * 内置的字段检验的方法，包含了一下的验证方式
     * guid
     * ip
     * mail
     * url
     * zip
     * passwod
     * phone
     * mobile
     * date
     * datetime
     * qq
     * chinese-[min]-[max]
     * alpha-[min]-[max]
     * alnum-[min]-[max]
     * int-[min]-[max]
     * str-[min]-[max]
     * str2-[min]-[max]
     * @author 欧远宁
     * @param string $str    需要验证的字符串
     * @param string $val    验证的格式
     */
    public static function val_str($str, $val){
        if ($val == '') {
            return TRUE;
        }
    
        $arr = explode('-',$val);
        switch($arr[0]){
            case 'uuid'://是否正常的uuid格式
                return preg_match('/^[a-f0-9]{16}$/', $str);
                break;
            case 'str'://str-min-max 一个汉字当作一个字符处理
                $len = mb_strlen($str);
                if (count($arr) == 3){//说明是一个区间
                    return ($len > ($arr[1] - 1 ) && $len < ($arr[2] + 1));
                } else {//说明是一个固定长度
                    return ($len == $arr[1]);
                }
                break;
            case 'str2'://一个汉字当作2个字符处理
                $len = strlen(iconv('utf-8', 'gbk', $str));
                if (count($arr) == 3){//说明是一个区间
                    return ($len > ($arr[1] - 1 ) && $len < ($arr[2] + 1));
                } else {//说明是一个固定长度
                    return ($len == $arr[1]);
                }
                break;
            case 'int'://int-min-max
                $min = (isset($arr[1])) ? $arr[1]-1 : -1;
                $max = (isset($arr[2])) ? $arr[2]+1 : 210000000;
                return ($str > $min && $str < $max);
                break;
            case 'alpha'://验证字母 alpha-min-max
                if (!ctype_alpha($str)){
                    return false;
                }
                $len = mb_strlen($str);
                if (count($arr) == 3){//说明是一个区间
                    return ($len > ($arr[1] - 1 ) && $len < ($arr[2] + 1));
                } else {//说明是一个固定长度
                    return ($len == $arr[1]);
                }
                break;
            case 'num'://验证数字
                if (!ctype_digit($str)){
                    return false;
                }
                $len = mb_strlen($str);
                if (count($arr) == 3){//说明是一个区间
                    return ($len > ($arr[1] - 1 ) && $len < ($arr[2] + 1));
                } else {//说明是一个固定长度
                    return ($len == $arr[1]);
                }
            case 'alnum'://验证字母+数字
                if (!ctype_alnum($str)){
                    return false;
                }
                $len = mb_strlen($str);
                if (count($arr) == 3){//说明是一个区间
                    return ($len > ($arr[1] - 1 ) && $len < ($arr[2] + 1));
                } else {//说明是一个固定长度
                    return ($len == $arr[1]);
                }
                break;
            case 'password':
                return preg_match('/^[\w]{5,30}$/',$str);
                break;
            case 'date'://yyyy-mm-dd
                list($year, $month, $day) = sscanf($str, '%d-%d-%d');
                return checkdate($month, $day, $year);
                break;
            case 'datetime': //yyyy-mm-dd hh:ii:ss
                list($year, $month, $day, $h, $m, $s) = sscanf($str, '%d-%d-%d %d:%d:%d');
                if(!checkdate($month, $day, $year)) {
                    return false;
                }
                return ($h>-1 && $h<25) && ($m>-1 && $m<61) && ($s>-1 && $s<61);
                break;
            case 'ip':
                return ip2long($str);
                break;
            case 'mail':
                return preg_match('/^[\w]{1,}[@][\w\-]{1,}([.]([\w\-]{1,})){1,3}$/', $str);
                break;
            case 'url':
                return preg_match('/^[\w]{1,}[@][\w\-]{1,}([.]([\w\-]{1,})){1,3}$/', $str);
                break;
            case 'zip':
                return preg_match('/^[0-9]\d{6}$/', $str);
                break;
            case 'qq':
                return preg_match('/^[0-9]\d{5,15}$/', $str);
                break;
            case 'phone':
                return preg_match('/^((0[1-9]{3})?(0[12][0-9])?[-])?\d{6,8}$/', $str);
                break;
            case 'mobile':
                return preg_match('/(^0?[1][3-9][0-9]{9}$)/', $str);
                break;
            case 'chinese': //chinese-min-max
                if (count($arr) == 3){
                    return preg_match('/^[\xB0-\xF7][\xA1-\xFE]{'.$arr[1].','.$arr[2].'}$/', $str);
                } else {
                    return preg_match('/^[\xB0-\xF7][\xA1-\xFE]{'.$arr[1].'}$/', $str);
                }
                break;
        }
    }
    
    /**
     * 从数组中剔除某些KEY的值
     * @author 欧远宁
     * @param array $target 目标数组
     * @param array $keys   会被删除的key列表
     */
    public static function remove_from_array(& $target, $keys){
        if(!is_array($target)) {
            return;
        }
        
        if (count($target) != count($target, COUNT_RECURSIVE)){
            foreach($target as & $arr){
                self::remove_from_array($arr, $keys);
            }
        } else {
            foreach($keys as $key){
                unset($target[$key]);
            }
        }
    }
    
    /**
     * 从数组中提取某些KEY的值
     * @author 欧远宁
     * @param array $target  目标数组
     * @param array $keys      需要提取的key。只有这些key才会保留
     */
    public static function fetch_from_array(& $target, $keys){
        if(!is_array($target)) {
            return;
        }
        
        if (count($target) != count($target, COUNT_RECURSIVE)){
            foreach($target as & $arr){
                self::fetch_from_array($arr, $keys);
            }
        } else {
            foreach($target as $k=>$v){
                if(!in_array($k,$keys)){
                    unset($target[$k]);
                }
            }
        }
    }
    
    /**
     * 将$arr数组中的这些$keys，合并入$target数组中，合并时，可以选择$pre这个前缀
     * @static
     * @author 欧远宁
     * @param array $target
     * @param array $arr
     * @param array $keys
     * @param string $pre
     */
    public static function merge_to_array($target, $arr, $keys, $pre=''){
        $ret = array();
        $len = count($target);
        for($i = 0; $i < $len; $i++){
            $to_arr = $target[$i];
            $fr_arr = $arr[$i];
            foreach($keys as $k){
                $to_arr[$pre.$k] = $fr_arr[$k];
            }
            $ret[] = $to_arr;
        }
        return $ret;
    }
    
    /**
     * 移除敏感的script串信息
     * @author 欧远宁
     * @param string $str
     * @return string 替换后的字符串
     */
    public static function remove_script($str){
        $f = array(
                "/<(?:link|script|frame|iframe|frameset)[^>]*>.*?<\/(?:link|script|frame|iframe|frameset)\s*>/is",
                "/<(?:frameset|script|iframe|frame|link)[^>]*>/is",
                "/<a\s*href=\s*['|\"|j|v].*?script:[^>]*>.*?<\/a>/is",
                "/<[a-z]+\s*(?:onerror|onload|onunload|onresize|onblur|onchange|onclick|ondblclick|onfocus|onkeydown|onkeypress|onkeyup|onmousemove|onmousedown|onmouseout|onmouseover|onmouseup|onselect)[^>]*>/is"
        );
        return preg_replace($f, '', $str);
    }
}