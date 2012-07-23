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
	 * 得到语言配置信息<br/>
	 * 如果有格式，请参考php函数sprintf中的格式描述信息。
	 * @author 欧远宁
	 * @param string $key 语言配置的key
	 * @param array $para 格式的参数
	 */
	public static function lang($key, $para=null){
		if (!key_exists($key, $GLOBALS['cfg']['lang'])){
			return 'unknown error key='.$key;
		}
		if (is_null($para)){
			return $GLOBALS['cfg']['lang'][$key];
		} else {
			return vsprintf($GLOBALS['cfg']['lang'][$key], $para);
		}
	}
	
    /**
     * 得到配置文件的信息
     * 新增的配置文件，请放在/cfg/目录下。
     * @author 欧远宁
     * @param string $name 配置名
     * @param string $key  key
     */
    public static function get_cfg($name, $key='', $def=null){
        require_once(OP.'cfg'.DS.$name.'.php');
        if ($key == ''){
        	if (key_exists($name, $GLOBALS['cfg'])){
        		return $GLOBALS['cfg'][$name];
        	} else {
        		return $def;
        	}
        } else {
        	if (key_exists($key, $GLOBALS['cfg'][$name])){
        		return $GLOBALS['cfg'][$name][$key];
        	} else {
        		return $def;
        	}
        }
    }
    
    /**
     * 暴露出来的一个设置缓存接口，使用sys模块的缓存设置
     * @author 欧远宁
     * @param string $key cache的key
     * @param any $val    cache的值
     * @param int $time   过期时间，单位秒
     */
    public static function set_sys_cache($key, $val, $time=300) {
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
     * 内置的字段检验的方法，包含了以下的验证方式
     * uuid		检查uuid<br/>
     * ip		检查IP<br/>
     * mail		检查email<br/>
     * url      检查URL<br/>
     * idcard   检查身份证<br/>
     * zip      检查邮编<br/>
     * passwod  检查密码<br/>
     * phone    电话号码<br/>
     * mobile   手机号<br/>
     * date     日期，格式为yyyy-mm-dd<br/>
     * datetime 时间，格式为yyyy-mm-dd hh:ii:ss<br/>
     * timestamp 时间戳类型<br/>
     * qq       QQ号<br/>
     * chinese-min-[max]   都是汉字<br/>
     * alpha-min-[max]     都是字母<br/>
     * alnum-min-[max]     字母和数字<br/>
     * num-min-[max]       数字<br/>
     * int-min-[max]       整数<br/>
     * float-精度-int部分最小-int部分最大    浮点类型验证
     * str-min-[max]       字符串，一个汉字当作一个字符串<br/>
     * str2-min-[max]      字符串，一个汉字根据gbk编码计算字符串长度，一般是2个<br/>
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
                if (!ctype_digit($str.'')){
                    return false;
                }
                $min = (isset($arr[1])) ? $arr[1]-1 : -1;
                if (isset($arr[2])){
                	return ($str > $min && $str <= $arr[2]);
                } else {
                	return ($str > $min);
                }
                break;
            case 'float': //float-精度-最小-最大
            	if(!preg_match('/^[0-9]+\.?[0-9]{0,'.$arr[1].'}/', $str)){
            		return false;
            	}
            	$str = $str + 0;
            	return ( ($str >= (int)$arr[2]) && ($str <= (int)$arr[3]) );
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
                if (!ctype_digit($str.'')){
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
            case 'timestamp': //验证timestamp类型
            	if (!ctype_digit($str.'')){
            		return false;
            	}else{
					return true;
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
                return preg_match('/^\d{6}$/', $str);
                break;
            case 'idcard':
            	return preg_match('/^(\d{14}|\d{17})(\d|[xX])$/', $str);
            	break;
            case 'qq':
                return preg_match('/^\d{5,15}$/', $str);
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
     * @param array $target 目标数组
     * @param array $arr    来源数组
     * @param array $keys   需要从$arr中移出的字段列表
     * @param string $pre   放到$target的时候，所使用的前缀
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
    
    /**
     * 得到用户的IP
     * @author 欧远宁
     */
    public static function get_ip(){
	   	$ip = $_SERVER['REMOTE_ADDR'];
	   	if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
	   		$ip = $_SERVER['HTTP_CLIENT_IP'];
	   	} elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
	   		$ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
	   	}
    	if(false !== strpos($ip, ',')){
    		$ip = reset(explode(',', $ip));
    	}
    	return $ip;
    }
    
    /**
     * 检查一个数组是否是关联数组
     * 如果是空数组，则返回$empty，默认为false
     * @author 欧远宁
     * @param array $arr
     * @param bool $def
     */
    public static function is_assoc($arr, $empty=false){
    	foreach($arr as $k=>$v){
    		return (gettype($k) !== 'integer');
    	}
    	return $empty;
    }
}