<?php
namespace now;

/**
 * 简单的session封装
 * @author      欧远宁
 * @version     1.0.0
 * @copyright   CopyRight By 欧远宁
 * @package     now
 */
final class session {
    
    /**
     * 密钥，使用前请修改
     * @var string
     */
    public static $secret = '';
    
    /**
     * 是否将session信息放到cookie中，如果是OnePageOneApplication的方式，
     * 建议不要放到cookie中，而是每次放到请求参数中
     * @var bool
     */
    public static $to_cookie = FALSE;
    
    /**
     * 存放到cookie的时候，这个cookie的名字
     * @var string
     */
    private static $cookie_name = '_nowsess';
    
    /**
     * session中保存的数据内容
     * @var array
     */
    private static $sessions = array();
    
    /**
     * 根据玩家id。获取一个session的哈希
     * 以后每次请求都需要传递此字符串，以便确认用户身份
     * @static
     * @author 欧远宁
     * @param string $uid 用户id
     */
    public static function make_session($para=array()) {
        $secret = self::$secret;
        $rand = microtime(TRUE).'';
        
        $data = serialize($para);
        $ck = md5($rand.$data.$secret);
        
        $str = $rand.'|'.$ck.'|'.$data;
        $str = base64_encode(xxtea::encrypt($str, md5($secret)));
        
        if (self::$to_cookie){
            $cfg = $GLOBALS['cfg']['cfg']['session']['cookie'];
            setcookie(self::$cookie_name, $str,  $cfg['expire'], $cfg['path'], $cfg['domain'], $cfg['secure'], $cfg['httponly']);
        }
        return $str;
    }
    
    /**
     * 获取当前登录玩家
     * 根据玩家传过来的字符串确定玩家身份
     * @static
     * @author 欧远宁
     * @param string $session session字符串
     */
    public static function get_session($session='') {
        if (self::$to_cookie){
            if(isset($_COOKIE[self::$cookie_name])){
                $session = $_COOKIE[self::$cookie_name];
                unset($_COOKIE[self::$cookie_name]);
            } else{
                $session = '';
            }
        }
        $secret = self::$secret;
        
        $sess = xxtea::decrypt(base64_decode($session), md5($secret));
        $arr = explode('|', $sess, 3);
        
        if (count($arr) != 3) {
            return;
        }
        
        $str = md5($arr[0].$arr[2].$secret);
        if ($str == $arr[1]) {
            self::$sessions = unserialize($arr[2]);
        }
    }
    
    /**
     * 得到session的值
     * @static
     * @author 欧远宁
     * @param string $key
     */
    public static function get($key){
        if(key_exists($key, self::$sessions)){
            self::$sessions[$key];
        } else {
            return null;
        }
    }
    
}