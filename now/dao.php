<?php
namespace now;

use now\err as err;
use now\cache as cache;
use now\mysql as mysql;
use \Exception as Exception;

/**
 * 数据访问接口，提供了多库的封装和缓存的处理
 * @author      欧远宁
 * @version     1.0.0
 * @copyright   CopyRight By 欧远宁
 * @package     now
 */
final class dao {
    
    /**
     * 表结构定义
     * @var array
     */
    public $schema = null;
    
    /**
     * 当前模块下的sql配置
     * @var array
     */
    public $sql = null;
    
    /**
     * 当前模块
     * @var string
     */
    public $mdl = '';
    
    /**
     * 当前表
     * @var string
     */
    public $tbl = '';
    
    /**
     * 版本号
     * @var string
     */
    private $ver = '0';
    
    /**
     * 数据库访问类
     * @var class
     */
    private $db = null;
    
    /**
     * 缓存类
     * @var class
     */
    private $cache = null;
    
    /**
     * 主键名
     * @var string
     */
    private $key = '';
    
    /**
     * 结果集名
     * @var string
     */
    private $key_list = '';
    
    /**
     * dao的构造函数
     * @author 欧远宁
     * @param string $mdl 应用名
     * @param string $tbl 表对象名
     */
    public function __construct($mdl, $tbl){
        $this->schema = $GLOBALS['cfg'][$mdl]['schema'][$tbl];
        $this->sql = $GLOBALS['cfg'][$mdl]['sql'];
        
        $suf = inject::split($mdl, $tbl);
        
        //我们用版本号作为缓存key的前缀，以便当数据结构变更的时候，将对象缓存自动过期
        $this->ver = $this->schema['ver'].'.';
        
        $this->mdl = $mdl.$suf['db'];
        $this->tbl = $tbl.$suf['tbl'];
        $this->key = $tbl.'_id';
        $this->key_list = $tbl.'_list';
        
        $this->db = mysql::get_ins($mdl,$suf['db']);
        if ( $GLOBALS['cfg']['cfg']['dao_cache'] ){
            $this->cache = cache::get_ins($mdl);
        } else {
            $this->schema['cache'] = -1;
        }
    }

    /**
     * 得到一个uuid
     * @author 欧远宁
     * @param string $prefix 前缀
     * @return string 如果没有前缀的话，是16位。大概是每秒1000并发的时候。有1/3839000 的概率重复
     */
    public function uuid($prefix=''){
        return uniqid($prefix).sprintf('%x',mt_rand(256, 4095));
    }
    
    /**
     * 新增数据
     * @author 欧远宁
     * @param array $data 需要新增的数据 
     * @example
     * //自动生成id<br/>
     * $user_dao->add(array(<br/>
     *     'name'=>'aaa','age'=>12<br/>
     * ));<br/><br/>
     * 
     * //手动赋id<br/>
     * $user_dao = new dao('blog', 'user');<br/>
     * $user_dao->add(array(<br/>
     *     'user_id'=>$user_dao->uuid(), 'name'=>'aaa','age'=>12<br/>
     * ));<br/><br/>
     * 
     * //批量新增<br/>
     * $user_dao = new dao('blog', 'user');<br/>
     * $user_dao->add(array('name'=>'名字1'),array('name'=>'名字2'));<br/>
     */
    public function add($data = array()){
        $len = count($data);
        
        if(empty($data) || $len == 0){
            return 0;
        }
        
        if ($len == count($data,COUNT_RECURSIVE)){
            $data = array($data);
        }
        
        $bsql = 'INSERT INTO `' .$this->tbl . '` (';
        try{
        	$result = 0;
            foreach($data as $para){
                if (!key_exists($this->key, $para)){
                    $para[$this->key] = $this->uuid();
                }
                
                $sql = $bsql.'`'.implode('`,`', array_keys($para)).'`';
                $para = $this->pre_para($para);
                $keys = array_keys($para);
                
                $sql .= ') VALUES (';
                $sql .= implode(',', $keys) . ')';
                $result += $this->db->execute($sql, $para);
            }
            
            //清除查询缓存
            if ($this->schema['cache'] > -1){//使用缓存
                $this->cache->inc('qc.'.$this->mdl.'.'.$this->tbl);
            }
            return $result;
        }catch (Exception $e){
            throw new err($e->getMessage(), 100);
        }
    }
        
    /**
     * 删除数据
     * @author 欧远宁
     * @param any $filter    筛选条件，如果是字符串，则使用Id进行删除，否则需拼sql
     * @param any $del_one    需要删除的一对一表信息, 格式为：array('mdl1.table1','mdl2.table2');   //模块名.表名
     * @param any $del_many   需要删除的一对多表信息,格式为：array('mdl1.table1','mdl2.table2');  //模块名.表名
     * @example
     * //删除帖子表及一对一的帖子统计表，一对多的回复表<br/>
     * $topic_dao = new dao('blog','topic');<br/>
     * $topic_dao->del('topicid', array('blog.topic_stat'),array('blog.reply'));<br/>
     */
    public function del($filter='',$del_one=null, $del_many=null){
        try{
            if (is_array($filter) && count($filter)== 0){
                throw new err("不能一次性进行全部数据的删除");
            } else if ($filter == ''){
                throw new err("不能一次性进行全部数据的删除");
            }

            if (is_array($filter)){
            	$flen = count($filter);
                if ($flen == 1 && key_exists($this->key, $filter)){
                    return $this->del($filter[$this->key], $del_one, $del_many);
                }
                
                //有值的普通数组
                if ($flen > 0 && !fun::is_assoc($filter)){
                	foreach($filter as $fkey){
                		$this->del($fkey, $del_one, $del_many);
                	}
                	return;
                }

                $lst = $this->get_rec($filter, array('all'=>'y'));
                foreach($lst['list'] as $rec){
                    $this->del($rec[$this->key], $del_one, $del_many);
                }
            } else {//根据ID删除
                //先删除hasMany
                $this->del_many(array($this->key=>$filter), $del_many);
                                
                $sql = 'DELETE FROM `'.$this->tbl.'` WHERE '.$this->key.'=:'.$this->key;
                $para = array(':'.$this->key=>$filter);
                $re = $this->db->execute($sql, $para);

                $this->mv_obj_cache($filter);
                $this->del_one($filter, $del_one);
            }
        } catch (Exception $e){
            throw new err($e->getMessage(),10000);
        }
    }
        
    /**
     * 一次更新多笔记录
     * @author 欧远宁
     * @param array $para 是一个数组，其每个项的格式为 array(filter,  $data)。与mdf($filter, $data)要求一样
     * @example
     * $user_dao = new dao('base', 'user');<br/>
     * $user_dao->mdf(<br/>
     *    array('user_id1', array('age'=>18),<br/>
     *    array(array('sex'=>'male'), array('name'=>'i am male'))<br/>
     * );<br/>
     */
    public function muti_mdf($paras){
    	$ret = 0;
    	foreach($paras as $para){
    		$ret+=$this->mdf($para[0],  $para[1]);
    	}
    	return $ret;
    }
    
    /**
     * 进行更新操作
     * @author 欧远宁
     * @param any $filter 筛选条件
     * @param array $data 需要更改的值
     * @example
     * //单笔修改<br/>
     * $topic_dao = new dao('blog','topic');<br/>
     * $topic_dao->mdf('topicId', array('title'=>'新的标题'));<br/><br/>
     * 
     * //批量修改<br/>
     * $topic_dao = new dao('blog', 'topic');<br/>
     * $topic_dao->mdf(array('forum_id'=>'板块ID'), array('title'=>'标题'));<br/>
     */
    public function mdf($filter='',$data){
        if (!is_array($data) || empty($data)){
            throw new err("修改的数据为空");
        }
        
        if (is_array($filter) && count($filter) == 0) {
            throw new err("不能一次性进行全部数据的修改");
        } else if ($filter == ''){
            throw new err("不能一次性进行全部数据的修改");
        }
        
        try{
            $result = 0;
            if (is_array($filter)){//筛选式更新
            	$flen = count($filter);
                if ($flen && key_exists($this->key, $filter)){
                    return $this->mdf($filter[$this->key], $data);
                }
                
                //有值的普通数组
                if ($flen > 0 && !fun::is_assoc($filter)){
                	foreach($filter as $fkey){
                		$result+= $this->mdf($fkey, $data);
                	}
                	return $result;
                }
                
                if ($this->schema['cache'] > -1) {
                    $res = $this->get_rec($filter, array('all'=>'y'));
                    foreach ($res['list'] as $obj){
                        $result += $this->mdf($obj[$this->key], $data);
                    }
                } else {
                    $sql = 'UPDATE `'. $this->tbl. '` SET ';
                    foreach ($data as $k => $v) {
                        $sql .= ' `'.$k.'` = :v_'.$k.',';
                    }
                    $sql = substr($sql, 0, -1) . ' WHERE 1=1';
                    foreach ($filter as $k => $v) {
                        $sql .= ' AND `'.$k.'` = :f_'.$k;
                    }
                    $param = array_merge( $this->pre_para($data, 'v_'), $this->pre_para($filter, 'f_') );
                    $result = $this->db->execute($sql, $param);
                }
            } else {//根据ID更新
                $sql = 'UPDATE `'.$this->tbl.'` SET ';
                foreach ($data as $k => $v) {
                    $sql .= ' `'.$k.'` = :'.$k.',';
                }
                $sql = substr($sql, 0, -1);
                $param = $this->pre_para($data);
                $param[':'.$this->key] = $filter;
                $sql.= ' WHERE '.$this->key.' = :'.$this->key;
                $result = $this->db->execute($sql, $param);
                //移除缓存
                $this->mv_obj_cache($filter);
            }
            return $result;
        } catch (Exception $e){
            throw new err($e->getMessage(),10000);
        }
    }
    
    /**
     * 得到一定条件下数据的总量
     * 一般而言，直接使用get()和find()函数，配置里面的$page参数即可得到数据总量
     * 提供count()方法的目的是为了满足只需要总量，而不需要明细的需求。
     * @param any $filter 如果是array，则使用AND的等号方式，进行查询，如果是string，则使用类似find的方式进行查询
     * @example
     * $user_dao = new dao('base', 'user');<br/>
     * $man_num = $user_dao->count(array('sex'=>'male'));<br/>
     * 
     * $child_num = $user_dao->count('age < :age', array('age'=>12));<br/>
     */
    public function count($filter, $para=array()){
        $sql = 'SELECT COUNT('.$this->key.') as ttl FROM `'.$this->tbl.'` WHERE';
        if( is_array($filter) ) {
            $sql.=' 1=1';
            foreach ($filter as $k => $v) {
                $sql.= ' AND `'.$k.'` = :'.$k;
            }
            $filter = $this->pre_para($filter);
            $plist = $this->db->query($sql, $filter);
        } else {
            $sql.=$filter;
            $para = $this->pre_para($para);
            $plist = $this->db->query($sql, $para);
        }
        return $plist[0]['ttl'];
    }
    
    /**
     * 进行多字段的统计
     * @author 欧远宁
     * @param array $fields 	统计的字段
     * @param string $where     where字句。可以包含group by 和order by等。
     * @param array $para       where中使用到的参数列表
     * @param array $group_by   group by字句
     * @example
     * $pro_dao = new dao('base', 'pro');<br/>
     * $rec = $pro_dao->fstat(array('pre_degree'=>'SUM','field2'=>''));<br/>
     * $rec = $pro_dao->fstat(array('pre_degree'=>'SUM'), 'pro_id=:pro_id', array('pro_id'=>'aaa'));<br/>
     * 
     * $rec = $pro_dao->fstat(array('pre_degree'=>'SUM','score'=>'AVG', 'kind'=>''),null,null,'kind');
     * 类似于select SUM(pre_degree) as SUM_pre_degree, AVG(score) as AVG_score, kind FROM pro GROUP BY kind;
     */
    public function fstat($fields, $where, $para=null, $group_by=''){
    	if (!is_array($fields) || empty($fields)){
    		throw new err('错误的字段统计参数');
    	}
    	$sql = 'SELECT';
    	foreach($fields as $k=>$v){
    		if ($v === ''){
    			$sql.= ' `'.$k.'`,';
    		} else {
    			$sql.= ' '.$v.'(`'.$k.'`) AS '.$v.'_'.$k.',';
    		}
    	}
    	$sql = substr($sql, 0, -1);
    	$sql.= ' FROM `'.$this->tbl.'`';
    	if ($where != ''){
    		$sql.= ' WHERE '.$where;
    		$para = $this->pre_para($para);
    	}
    	if ($group_by != ''){
    		$sql.= ' GROUP BY '.$group_by;
    	}
    	$lst = $this->db->query($sql, $para, array('all'=>'y'));
    	return $lst;
    }
    
    /**
     * 进行单表的，基于等号筛选的查询
     * @author 欧远宁
     * @param array $filter  筛选条件如：array('uid'=>'outrace')。如果是字符串，则根据ID查找
     * @param array $page    分页参数    array('cur'=>当前页数,'size'=>每页数据量, start=开始笔数,不予cur同时使用,<br/>
     *                                     'all'=>是否全部取回来y/n,'ttl'=是否返回总数)
     * @param string $order  排序信息如：'time desc,name asc'，默认不排序
     * @param array $get_one 所需要获取的一对一表如：array('base.user.user_id','forum.forum_stat')
     * @example 
     * $dao = new dao('blog','article');<br/>
     * $filter = array('uid'=>'ouyuanning');<br/>
     * $page = array('cur'=>1,'size'=>40,'ttl'=>'y');    //取第一页。每页40笔，返回总量<br/>
     * $order = 'addtime Desc';                //根据时间降序排列<br/><br/>
     * 
     * //获取1对1表内容，根据原则是article_id = articleStat_id<br/>
     *  //多对1则根据最后一个的字段名的值 = 多对一表的ID值<br/>
     * $get_one = array('blog.article_stat','user.upoint.user_id');<br/><br/>
     * 
     * //得到结果为：$res['article_list']  $res['article_stat_list'] $res['upoint_list']<br/>         
     * $res = $dao->get($filter, $page, $order, $get_one);
     * @return array
     */
    public function get($filter='', $page=null, $order=null, $get_one=null){
        $result[$this->key_list] = array();
        if ($filter == '') $filter = array();
        
        if (is_array($get_one)) {
        	$one_arr = array();
        	foreach ($get_one as $obj){
        		$arr = explode('.',$obj);
        		$one_arr[] = $arr;
        		if (count($arr) == 4){
        			$result[$arr[3].'_list'] = array();
        		} else {
        			$result[$arr[1].'_list'] = array();
        		}
        	}
        }
        
        if (is_array($filter)){//并非根据ID获取
        	$flen = count($filter);
            if ($flen == 1 && key_exists($this->key, $filter)){
                return $this->get($filter[$this->key], null, null, $get_one);
            }
            
            //有值的普通数组
        	if ($flen > 0 && !fun::is_assoc($filter)){
        		foreach($filter as $fkey){
        			$tmp = $this->get($fkey, null, null, $get_one);
        			foreach($result as $k=>$v){
        				$result[$k] = array_merge($v, $tmp[$k]);
        			}
        		}
        		return $result;
        	}

            $klist = $this->get_rec($filter, $page, $order);
            if(!is_null($klist['ttl'])){
                $result['ttl'] = $klist['ttl'];
            }
            
            foreach ($klist['list'] as $obj){
                $tmp = $this->get($obj[$this->key], null, null, $get_one); //一次返回多笔记录的时候，不支持$getMany
                foreach ($tmp as $k=>$v){
                	if (count($v) > 0){
                		$result[$k][] = $v[0];
                	} else {
                		$result[$k][] = array();
                	}
                }
            }
        } else {//根据ID获取
            $id = $filter;
            
            if ($id == '') {
                return array($this->key_list=>array());
            }

            $filter_para = array();
            $filter_para[':'.$this->key] = $filter;
            $where = ' WHERE '.$this->key.' = :'.$this->key;
            if ($this->schema['cache'] > -1){//说明有使用缓存
                $cid = $this->ver.$this->mdl.$this->tbl.$id;
                $re = $this->cache->get($cid);

                if ($re==''){    //未命中缓存
                    $sql = 'SELECT '.$this->schema['fields'].' FROM `'.$this->tbl.'`'.$where;
                    $tmp = $this->db->query($sql, $filter_para);
                    if (count($tmp) > 0){
                        $result[$this->key_list] = $tmp;
                        $this->cache->set($cid, $tmp, $this->schema['cache']);
                    }
                } else {
                    $result[$this->key_list] = $re;
                }
            } else {//未使用缓存
                $sql = 'SELECT '.$this->schema['fields'].' FROM `'.$this->tbl.'`'. $where;
                $result[$this->key_list] = $this->db->query($sql, $filter_para);
            }
                
            $cLen = count($result[$this->key_list]);

            //得到1对1表数据
            if ($cLen > 0 && is_array($get_one)){
                foreach($one_arr as $one){
                    $c = new dao($one[0], $one[1]);
                    $len = count($one);
                    if ($len < 3){
                        $tmp = $c->get($id);
                    } else {
                        $tmp = $c->get($result[$this->key_list][0][$one[2]]);
                    }
                    if ($len == 4){
                    	$tmp[$one[3].'_list'] = $tmp[$one[1].'_list'];
                    	unset($tmp[$one[1].'_list']);
                    }
                    $result = array_merge($result, $tmp);
                }
            }
        }
        return $result;
    }
    
    /**
     * 对于某几个字段进行自增操作
     * @author 欧远宁
     * @param string or array   $filter   筛选条件eg: 'A'或array('UserId'=>'uname1')
     * @param array             $fileds   更新字段列表 array('field1','field2')
     * @param array             $values   更新值列表 array(20,-1)
     * @param array             $data     非自增的其他字段
     * $dao = dao('yy', 'upoint');<br/>
     * $filter = 'ou';<br/>
     * $fields = array('exp', 'coin');<br/>
     * $values = array(10, -1000);<br/>
     * $data = array('last' => '2222');<br/>
     * $dao->inc($filter, $fields, $values, $data);<br/>
     * //将会执行类似这样的sql:    UPDATE upoint SET exp = exp+10, coin = coin-1000,last='2222' WHERE upoint_id='ou';  同时更新缓存<br/>
     * @return 操作成功数
     */
    public function inc($filter='', $fields=array(), $values=array(), $data=array()){
        try{
            $result = 0;
            
            if ($filter == '') {
                $filter = array();
            }

            if (is_array($filter)){//筛选式更新
                if (count($filter) == 1 && key_exists($this->key, $filter)){
                    return $this->Inc($filter[$this->key], $fields, $values, $data);
                }
                 
                if ($this->schema['cache'] > -1) {
                    $res = $this->get_rec($filter, array('all'=>'y'));
                    foreach ($res['list'] as $obj){
                        $result += $this->inc($obj[$this->key], $fields, $values, $data);
                    }
                } else {
                    $sql = 'UPDATE `'.$this->tbl.'` SET ';
                    $i = 0;
                    foreach($fields as $fld){
                        $sql .= ' `'.$fld.'` = `'.$fld.'` + ('.$values[$i].'),';
                        $i = $i    + 1;
                    }
                    foreach ($data as $k => $v) {
                        $sql .= ' `'.$k.'` = :v_'.$k.',';
                    }
//                     $sql = substr($sql,0,(mb_strlen($sql)-1)) . ' WHERE 1=1';
                    $sql = substr($sql,0,-1) . ' WHERE 1=1';
                    foreach ($filter as $k => $v) {
                        $sql .= ' AND `'.$k.'` = :f_'.$k;
                    }
                    $param = array_merge( $this->pre_para($data,'v_'), $this->pre_para($filter,'f_') );
                    $result = $this->db->execute($sql,$param);
                }
            } else {//根据ID更新
                $sql = 'UPDATE `' . $this->tbl .'` SET ';
                $i = 0;
                foreach($fields as $fld){
                    $sql .= ' `'.$fld.'` = '.$fld.' + ('.$values[$i].'),';
                    $i = $i    + 1;
                }
                foreach ($data as $k => $v) {
                    $sql .= ' `'.$k.'` = :v_'.$k.',';
                }
//                 $sql = substr($sql,0,(mb_strlen($sql)-1));
                $sql = substr($sql, 0, -1);
                $param[':'.$this->key] = $filter;
                $sql .= ' WHERE '.$this->key.' = :'.$this->key;
                $param = array_merge($this->pre_para($data,'v_'), $param);
                $result = $this->db->execute($sql, $param);
                
                //移除缓存
                $this->mv_obj_cache($filter);
                    
                return $result;
            }
        } catch (Exception $e){
            throw new err($e->getMessage(),100);
        }
    }
    
    /**
     * 进行单表查询,这里的where应该是包含了非等于的筛选条件
     * 如果都是是用等于进行筛选，请使用本类的Get函数，因为这样才能更有效的使用查询缓存
     * @author 欧远宁
     * @param string $where 查询条件   'last>:last'
     * @param array $para   对应的参数  array('last'=>'2008-01-01')
     * @param array $page   分页参数    array('cur'=>当前页数,'size'=>每页数据量, start=开始笔数,不予cur同时使用,<br/>
     *                                     'all'=>是否全部取回来y/n,'ttl'=是否返回总数)
     * @param string $order 排序信息    'last Desc,uid Asc'
     * @return array 查询到的结果集
     * @example 
     * $dao = new dao('blog','user');<br/>
     * $where = 'uid=:uid Or time>:time';<br/>
     * $para = array('uid'=>'yy','time'=>'2011-01-01');<br/>
     * $res = $dao->find($where, $para);<br/>
     * 将会执行类似这样的sql：  SELECT user_id FROM user Where uid = 'yy' Or time > '2011-02-02'。<br/>
     * 然后根据user_id再取一次数据内容<br/>
     */
    public function find($where='1=1', $para=array(), $page=null, $order=null, $get_one=null){
        try{
            if ($where == '') {
                $where = '1=1';
            }
            $result[$this->key_list] = array();
            
            $one_arr = array();
            if (is_array($get_one)) {
                foreach ($get_one as $obj){
                    $arr = explode('.',$obj);
                    $one_arr[] = $arr;
                    $result[$arr[1].'_list'] = array();
                }
            }
            
            if ($this->schema['cache'] > -1){//使用缓存
                $lst = $this->find_rec($where, $para, $page, $order);
                if (key_exists('ttl',$lst)) {
                    $result['ttl'] = $lst['ttl'];
                }
                foreach($lst['list'] as $obj){
                    $tmp = $this->get($obj[$this->key], null, null, $get_one);
                    foreach($tmp as $k=>$v){
                    	if (count($v) > 0){
                    		$result[$k][] = $v[0];
                    	} else {
                    		$result[$k][] = array();
                    	}
                    }
                }
            } else {//未使用缓存
                if (is_array($page) && key_exists('ttl', $page) && $page['ttl'] == 'y'){    //根据需要返回分页信息
                    $psql = 'SELECT count('.$this->key.') AS ttl FROM `'.$this->tbl. '` WHERE '.$where;
                    $plist = $this->db->query($psql, $para);
                    $result['ttl'] = $plist[0]['ttl'];
                } else {
                    $result['ttl'] = null;
                }
                
                $sql = 'SELECT ' .$this->key. ' FROM `' .$this->tbl. '` WHERE '.$where;
                $para = $this->pre_para($para);
                if (!is_null($order) && $order !== '') {
                    $sql .= ' ORDER BY '.$order;
                }
                $lst = $this->db->query($sql, $para, $page);
                
                foreach($lst as $obj){
                    $tmp = $this->get($obj[$this->key], null, null, $get_one);
                    foreach($tmp as $k=>$v){
                        $result[$k][] = $v[0];
                    }
                }
            }
            
            return $result;
        } catch (Exception $e) {
            throw new err($e->getMessage(),100);
        }
    }
    
    /**
     * 进行批量删除
     * @author 欧远宁
     * @param string $where  查询条件   'last>:last'
     * @param array $para    对应的参数  array('time'=>'2008-01-01')
     * @return int    成功操作的笔数
     * @example
     * $topic_dao = new dao('blog','topic');<br/>
     * $topic_dao->fDel('add_time>:add_time', array('add_time'=>121212323));<br/>
     */
    public function fdel($where='1=1', $para=array()){
        if ($where == '') {
            $where = '1=1';
        }
        $result = 0;
        if ($this->schema['cache'] > -1){//使用缓存
            $lst = $this->find_rec($where, $para, array('all'=>'y'));
            foreach($lst['list'] as $obj){
                $result += $this->del($obj[$this->key]);
            }
        } else {//未使用缓存，直接删除
            $sql = 'DELETE FROM `'.$this->tbl.'` WHERE '.$where;
            $result = $this->db->execute($sql, $this->pre_para($para));
        }
        return $result;
    }

    /**
     * 进行批量更新
     * @author 欧远宁
     * @param string $where 查询条件   'last>:last'
     * @param array $para   对应的参数  array('last'=>'2008-01-01')
     * @param array $data   更新的数据 array('name'=>'ou');
     * @return int 成功操作的笔数
     * @example
     * $topic_dao = new dao('blog','topic');<br/>
     * $topic_dao->fmdf('add_time>:add_time', array('add_time'=>1111), array('title'=>'已过期'));<br/>
     */
    public function fmdf($where='1=1', $para=array(), $data=array()){
        if ($where == '') {
            $where = '1=1';
        }
        $result = 0;
        if ($this->schema['cache'] > -1){//使用缓存
            $lst = $this->find_rec($where, $para, array('all'=>'y'));
            foreach($lst['list'] as $obj){
                $result += $this->mdf($obj[$this->key], $data,array('all'=>'y'));
            }
        } else {//未使用缓存
            $sql = 'UPDATE `'. $this->tbl . '` SET ';
            foreach ($data as $k => $v) {
                $sql .= ' '.$k.' = :v_'.$k.',';
            }
//             $sql = substr($sql,0,(mb_strlen($sql)-1)).' WHERE '.$where;
            $sql = substr($sql, 0,-1).' WHERE '.$where;
            $param = array_merge( $this->pre_para($data,'v_'), $this->pre_para($para) );
            $result = $this->db->execute($sql, $param);
        }
        return $result;
    }
    
    /**
     * 保留一个直接使用sql的接口。方便进行统计。该接口不会使用任何缓存。慎用。
     * @author 欧远宁
     * @param string $sql 查询sql   'select * from stat where last>:last'，记住这里不要放分页信息
     * @param array $para 对应的参数  array('lastLogin'=>'2008-01-01')
     * @param array $page 分页参数    array('cur'=>1,'size'=>20','all'=>'n','ttl'='n','start'=0)
     */
    public function sql($sql, $para, $page=null) {
        $ret = $this->db->query($sql, $this->pre_para($para), $page);
        return array('list' => $ret);
    }

    /**
     * 使用where的方式查找数据，与Get函数的主要区别是支持非等于号的筛选
     * @author 欧远宁
     * @param string $where  查询条件   'last>:last'
     * @param array $para    对应的参数  array('lastLogin'=>'2008-01-01')
     * @param array $page    分页参数    array('cur'=>1,'size'=>20','all'=>'n','ttl'='n')
     * @param string $order  排序信息    'last Desc,uid Asc'
     * @return  array
     */
    private function find_rec($where, $para, $page=null, $order=null){
        try{
            $sql = 'SELECT '.$this->key.' FROM `'.$this->tbl.'` WHERE '.$where;
            $psql = '';

            if (isset($page['ttl']) && $page['ttl'] == 'y'){    //根据需要返回分页信息
                $psql = str_replace('SELECT '.$this->key, 'SELECT COUNT('.$this->key.') AS ttl', $sql);
            }
            
            if (!is_null($order) && $order != ''){
                $sql .= ' ORDER BY '.$order;
            }

            $lst['list'] = array();
            
            //每个表对应查询缓存的流水好，当该表的数据变更后，该流水号会递增，以便使其查询缓存失效
            $flow = $this->cache->get('qc.'.$this->mdl.'.'.$this->tbl, '0');
            $flow = ($flow == '') ? '0' : $flow;
            $cid = 'fqc.'.md5($this->mdl.$this->tbl.serialize(array($where, $para, $page, $order)).$flow);
            
            $tmp = $this->cache->get($cid);
            
            if ($tmp==''){    //未命中缓存
                $para = $this->pre_para($para);
                $lst['list'] = $this->db->query($sql, $para, $page);
                if ($psql !== ''){
                    $pList = $this->db->query($psql, $para);
                    $lst['ttl']=$pList[0]['ttl'];
                } else {
                    $lst['ttl'] = null;
                }
                $this->cache->set($cid, $lst);
            } else {
                $lst = $tmp;
            }
            
            return $lst;
        } catch (Exception $e){
            throw new Exception($e->getMessage(), 100);
        }
    }
    
    /**
     * 删除hasMany表中的记录
     * @author 欧远宁
     * @param array $filter   筛选信息
     * @param array $del_many  需要删除的表
     */
    private function del_many($filter, $del_many) {
        if (is_array($del_many)){
            foreach ($del_many as $obj){
                $arr = explode('.',$obj);
                $c = new dao($arr[0], $arr[1]);
                $c->del($filter);
            }
        }
    }
    
    /**
     * 删除1对1表的内容
     * @author 欧远宁
     * @param array $filter  筛选信息
     * @param array $del_one  需要删除的表
     */
    private function del_one($filter, $del_one) {
        if (is_array($del_one)){
            foreach ($del_one as $obj){
                $arr = explode('.', $obj);
                $c = new dao($arr[0], $arr[1]);
                $c->del($filter);
            }
        }
    }
    
    /**
     * 根据筛选条件获取所有的id列表
     * @author 欧远宁
     * @param array $filter 筛选条件
     * @param array $page   分页信息
     * @param array $order  排序信息
     * @return array        结果集
     */
    private function get_rec($filter, $page=null, $order=null){
        $rec = array();
        if ($this->schema['cache'] > -1){
            //每个表对应查询缓存的流水好，当该表的数据变更后，该流水号会递增，以便使其查询缓存失效
            $flow = $this->cache->get('qc.'.$this->mdl.'.'.$this->tbl, '0');
            $flow = ($flow == '') ? '0' : $flow;
            $tid = 'gqc.'.md5($this->mdl.$this->tbl.serialize(array($filter, $page, $order)).$flow);
            $rec = $this->cache->get($tid);
            
            if ($rec == ''){    //未缓存命中
                $rec = $this->query($filter, $page, $order);
                $this->cache->set($tid, $rec);
            }
        } else {
            $rec = $this->query($filter, $page, $order);
        }
        return $rec;
    }

    /**
     * 根据条件返回数据库中的查询结果
     * @author 欧远宁
     * @param array $filter 筛选条件
     * @param array $page   分页条件    $page=array('cur'=>1,'size'=>20,'ttl'='n')
     * @param string $order 排序条件
     * @return array
     */
    private function query($filter, $page=null, $order=null){
        $sql = 'SELECT '.$this->key.' FROM `'.$this->tbl.'` WHERE 1=1';
        
        foreach ($filter as $k => $v) {
            $sql.= ' AND '.$k.' = :'.$k;
        }
        
        $filter = $this->pre_para($filter);
        
        //根据需要返回分页信息
        if (is_array($page) && key_exists('ttl',$page) && $page['ttl'] == 'y'){
            $psql = str_replace('SELECT '.$this->key, 'SELECT count('.$this->key.') AS ttl', $sql);
            $plist = $this->db->query($psql, $filter);
            $result['ttl'] = $plist[0]['ttl'];
        } else {
            $result['ttl'] = null;
        }
        
        if (!is_null($order) && $order !== '') {
            $sql.= ' ORDER BY '.$order;
        }
        
        $result['list'] = $this->db->query($sql, $filter, $page);
        return $result;
    }
    
    /**
     * 移除对象缓存
     * @author 欧远宁
     * @param string $id 对象缓存的ID
     */
    private function mv_obj_cache($id){
        try{
            if ($this->schema['cache'] > -1) {
                //改变查询缓存的流水号
                $this->cache->inc('qc.'.$this->mdl.'.'.$this->tbl);
                
                //移除对象缓存
                $this->cache->del($this->ver.$this->mdl.$this->tbl.$id);
            }
        } catch (Exception $e){
            throw new err($e->getMessage(), 100);
        }
    }
    
    /**
     * 协助完成将普通数组变成prepare sql中需要的参数
     * @author 欧远宁
     * @param array $arr  输入的参数
     * @param string $pre 前缀
     * @return array
     */
    private function pre_para($arr, $pre = ''){
        if (is_null($arr) || $arr == '') {
            return $arr;
        }
        
        if (!is_array($arr) || count($arr) == 0) {
            return $arr;
        }

        $re = array();
        foreach ($arr as $k => $v) {
            $re[':'.$pre.$k] = $v;
        }
        return $re;
    }
}