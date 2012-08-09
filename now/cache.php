<?php
namespace now;

use now\err as err;

/**
 * 缓存处理类，只支持memcached
 * @author        欧远宁
 * @version       1.0.0
 * @copyright     CopyRight By 欧远宁
 * @package       now
 */
final class cache {
    /**
     * 模块名
     * @var string
     */
    private $mdl = null;
    
    /**
     * memcache事例
     * @var class
     */
    private $mem_cls = null;
    
    /**
     * 是否已经打开
     * @var boolean
     */
    private $close = TRUE;
    
    /**
     * 针对此次请求的缓存，我们保存一份在内存中
     * @var array
     */
    private $buf_list = array();
    
    /**
     * 已经删除的key
     * @var array
     */
    private $del_list = array();
    
    /**
     * 所有的cache示例
     * @var array
     */
    private static $ins = array();
    
    
    /**
     * 构造函数，new一个缓存对象
     * @author 欧远宁
     * @param string $mdl 模块名称
     * @param string $ver 版本信息
     */
    private function __construct($mdl){
        $this->mdl = $mdl;
    }
    
    /**
     * 打开缓存
     * @author 欧远宁
     * @throws err
     */
    private function open(){
        if($this->close){
            $this->close = false;
            $cfg = $GLOBALS['cfg']['db'][$this->mdl]['cache'];
            if (extension_loaded('memcached')) {//优先使用memcached扩展，据说更快一些。
                $this->mem_cls = new \Memcached();
            } else if (extension_loaded('memcache')){//不过memcache扩展也够用了
                $this->mem_cls = new \Memcache();
            } else {
                throw new err('Need memcached or memcache ext');
            }
            try{
                $this->mem_cls->addServer($cfg['host'], $cfg['port']);
            } catch (Exception $e){
                throw new err($e->getMessage(),10000);
            }
        }
    }
    
    /**
     * 析构函数，清空资源
     * @author 欧远宁
     */
    public function __destruct(){
    }
    
    /**
     * 获取一个Cache实例
     * @author 欧远宁
     * @param string $mdl 应用名
     * @return    一个类实例
     */
    public static function get_ins($mdl){
        if (!key_exists($mdl, self::$ins)){
            self::$ins[$mdl] = new cache($mdl);
        }
        return self::$ins[$mdl];
    }
    
    /**
     * 提交cache的变更
     * 将变更的数据写入到cache中
     * @author 欧远宁
     */
    public static function commit(){
        foreach(self::$ins as $cache){
            $cache->save();
            $cache->mem_cls = null;
            $cache->close = TRUE;
        }
    }
    
    /**
     * 进行cache的回滚操作
     * 由于默认的是cache未被提交，所以rollback操作实际没有太多操作
     * @author 欧远宁
     */
    public static function rollback(){
        foreach(self::$ins as $cache){
            $cache->unsave();
            $cache->mem_cls = null;
            $cache->close = TRUE;
        }
    }
    
    /**
     * 将更新的数据保存到缓存中
     * @author 欧远宁
     */
    public function save(){
        $this->open();
        foreach($this->buf_list as $k=>$v){
            if ($v[1] !== -99){
                $this->mem_cls->set($k, $v[0], $v[1]);
            }
        }
        foreach($this->del_list as $k=>$v){
            $this->mem_cls->delete($k);
        }
    }
    
    /**
     * 回滚的时候，需要处理一下更新的cache。
     * 进行set操作的，我们就删除原cache，下次取的时候直接从db取
     * 进行inc操作的话，进行一次回滚
     * @author 欧远宁
     */
    public function unsave(){
        $this->buf_list = array();
        $this->del_list = array();
    }
    
    /**
     * 获取一笔缓存数据
     * @author 欧远宁
     * @param string $id    缓存的key
     * @return 缓存数据内容
     */
    public function get($id, $default=''){
        if (key_exists($id, $this->buf_list)){
            return $this->buf_list[$id][0];
        }
        if (key_exists($id, $this->del_list)){
            return '';
        }
        try{
            $this->open();
            $ret = $this->mem_cls->get($id);
            $this->buf_list[$id] = array($ret, -99);
            return $ret;
        } catch (Exception $e){
            return $default;
        }
    }
    
    
    /**
     * 设置一个缓存值
     * 如果数据有回滚的话，那么我们把cache删除掉，下次不再从缓存读
     * @author 欧远宁
     * @param string $id     缓存的key
     * @param any $data      需要缓存的内容
     * @param int $lifeTime  缓存时间，单位为秒，默认0
     * @param bool $force    是否强制更新，强制更新的缓存不回滚
     */
    public function set($id, $data, $lifeTime=0, $force=false){
        if (key_exists($id, $this->del_list)){
            unset($this->del_list[$id]);
        }
        if ($force) {//强制更新
            if (key_exists($id, $this->buf_list)){
                unset($this->buf_list[$id]);
            }
            $this->open();
            $this->mem_cls->set($id, $data, $lifeTime);
        } else {
            $this->buf_list[$id] = array($data, $lifeTime);
        }
    }
    
    /**
     * 进行自增操作，
     * @author 欧远宁
     * @param string $id   cache的key
     * @param int $num     自增数值，可以为负数
     * @param int $default 没有值的时候的默认值
     */
    public function inc($id, $num=1, $default=0){
        if (key_exists($id, $this->buf_list)){
        	if ($this->buf_list[$id][0] == ''){
        		$this->buf_list[$id][0] = 0;
        	}
            $this->buf_list[$id][0] = $this->buf_list[$id][0] + $num;
            $this->buf_list[$id][1] = 0;
        } else {
            if (key_exists($id, $this->del_list)){
                unset($this->del_list[$id]);
            }
            $this->open();
            try {
            	$old = $this->mem_cls->get($id);
            } catch (Exception $e){
            	$old = '';
        	}
            if($old === ''){
                $this->buf_list[$id] = array($default + $num, 0);
            } else {
                $this->buf_list[$id] = array($old + $num, 0);
            }
        }
    }
    
    /**
     * 删除缓存中的一笔记录
     * @author 欧远宁
     * @param string $id
     */
    public function del($id){
        if (key_exists($id, $this->buf_list)){
            unset($this->buf_list[$id]);
        }
        $this->del_list[$id] = '';
    }
    
    /**
     * 清空缓存
     * @author 欧远宁
     */
    public function clear(){
        $this->buf = array();
        $this->incList = array();
        $this->open();
        $this->mem_cls->flush();
    }
}