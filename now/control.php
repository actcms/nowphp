<?php
namespace now;

use now\session as session;
use \Exception as Exception;

/**
 * 主流程控制
 * @author      欧远宁
 * @version     1.0.0
 * @copyright   CopyRight By 欧远宁
 * @package     now
 */
final class control {

    /**
     * 文件自动加载
     * @author 欧远宁
     * @param string $cls 类名
     */
    public static function autoload($cls) {
        if ($cls == 'self') return;
        $fname = OP.strtr($cls,'\\', DS).'.php';
        if (file_exists($fname)){
            require_once($fname);
        }
    }

    /**
     * 把要用到的配置文件包含进来。
     * @author 欧远宁
     */
    public static function inc(){
        require_once OP.'cfg'.DS.'lang.php';

        //把schema和sql的文件包含进来
        foreach($GLOBALS['cfg']['db'] as $k=>$v){
            if ($k != 'sys'){
                require_once OP.'cfg'.DS.$k.DS.'schema.php';
                require_once OP.'cfg'.DS.$k.DS.'sql.php';
            }
        }
    }

    /**
     * 得到传递过来的参数信息
     * 我们支持2种方式，，
     * 一种兼容现有OA在线系统，从URL得到需要执行的cmd，从post中得到参数
     * 一种是完全从POST过来的数据中取得参数，我们取_c=所需要执行的cmd，使用.间隔
     *
     * 传入参数中有3个比较特别的。一个是_c表示需要执行的cmd，一个是_s表示session，一个是_r表示返回的类型，支持json,jsonp,amf
     * @author 欧远宁
     */
    private static function get_data(){
        $data = array();
        
        if (!key_exists('_c', $_REQUEST)){//说明要么是首页，要么是json类型的请求
            if ($_SERVER['REQUEST_METHOD'] == 'GET'){//如果是get请求，并且没有_cmd参数，那么
                $data['para'] = $_REQUEST;
                if (!isset($data['cmd'])){
                    $data['cmd'] = explode('.', $GLOBALS['cfg']['cfg']['index']);
                }
            } else {
                $data['para'] = json_decode(file_get_contents('php://input'),TRUE);
                if (!isset($data['cmd'])){
                    $data['cmd'] = explode('.', $data['para']['_c']);
                }
                unset($data['para']['_c']);
            }
        } else {
            $data['cmd'] = explode('.', $_REQUEST['_c']);
            unset($_REQUEST['_c']);
            $data['para'] = $_REQUEST;
        }

        //删除掉一些内置的全局变量，避免全局变量的滥用
        unset($_GET);
        unset($_POST);
        unset($_REQUEST);
        return $data;
    }

    /**
     * 根据参数，执行业务逻辑，返回结果
     * @author 欧远宁
     */
    public static function execute(){
        $data = null;
        try {
            self::inc();
            $data = self::get_data();
            $len = count($data['cmd']);
            if ($len < 2){
                throw new err('cmd error');
            } elseif ($len == 2) {
                $data['cmd'][2] = 'index';
            }

            $mdl = $data['cmd'][0];
            $cls_name = $data['cmd'][1];
            $method = $data['cmd'][2];

            //得到session
            $cfg_sess = $GLOBALS['cfg']['cfg']['session'];
            session::$secret = $cfg_sess['secret'];
            session::$to_cookie = $cfg_sess['to_cookie'];

            if (isset($data['para']['_s'])) {
                session::get_session($data['para']['_s']);
                unset($data['para']['_s']);
            } else if (session::$to_cookie){//保存到cookie中
                session::get_session();
            }
			
            $cls_path = 'cmd\\'.$mdl.'\\'.$cls_name;
            $method_path = $mdl.'.'.$cls_name.'.'.$method;
            $cls = new $cls_path($method_path);
            
            inject::cmd_before($method_path, $data['para']);
            call_user_func(array($cls, $method), $data['para']);

            self::commit();
        } catch (err $e) {//优先捕获自定义异常
            self::rollback();
            if (isset($data['para']['r'])){
                
            } else {
                $ret = array(
                        '_c'=>$e->getCode(),
                        '_m'=>$e->getMessage(),
                		'rows'=>array(),
                		'total'=>0
                        );
                view::json($ret);
            }
        } catch (Exception $ex){//捕获未预估的异常
            self::rollback();
            if (isset($data['para']['r'])){

            } else {
                $ret = array(
                        '_c'=>$ex->getCode(),
                        '_m'=>$ex->getMessage(),
                		'rows'=>array(),
                		'total'=>0
                        );
                view::json($ret);
            }
        }
    }

    /**
     * 提交事务
     * @author 欧远宁
     */
    private static function commit(){
        mysql::commit();
        cache::commit();
    }

    /**
     * 回滚事务
     * @author 欧远宁
     */
    private static function rollback(){
        mysql::rollback();
        cache::rollback();
    }
}