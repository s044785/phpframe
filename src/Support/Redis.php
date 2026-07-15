<?php
declare(strict_types=1);

namespace PHPFrame\Support;

// Redis 连接管理（单例），封装常用缓存操作
final class Redis
{
    private static ?self $instance = null;
    private \Redis $redis;

    public function __construct(\Redis $redis)
    {
        $this->redis = $redis;
    }

    // 从环境变量创建 Redis 连接
    public static function fromEnv(): self
    {
        $host = Env::get('REDIS_HOST', '127.0.0.1') ?? '127.0.0.1';
        $port = (int)(Env::get('REDIS_PORT', '6379') ?? '6379');
        $pass = Env::get('REDIS_PASS', '');
        $db = (int)(Env::get('REDIS_DB', '0') ?? '0');

        $redis = new \Redis();
        $redis->connect($host, $port);
        if ($pass !== null && $pass !== '') {
            $redis->auth($pass);
        }
        $redis->select($db);
        
        return new self($redis);
    }

    // 设置全局 Redis 实例
    public static function setInstance(self $instance): void
    {
        self::$instance = $instance;
    }

    // 获取全局 Redis 实例
    public static function getInstance(): self
    {
        if (self::$instance === null) {
            self::$instance = self::fromEnv();
        }
        return self::$instance;
    }

    // 初始化（在 bootstrap 中调用）
    public static function init(): void
    {
        self::$instance = null; // 重置以便延迟连接
    }

    // ── 缓存操作 ──────────────────────────────────────────────

    /**
	 * 写缓存
	 *
	 * @param string $key 组存KEY
	 * @param string $value 缓存值
	 * @param array|int $option 选项 例如：['nx', 'ex' => 10] 或['xx', 'px' => 10] 或 10
	 */
	public function set($key, $value, $option = [])
	{
		// 永不超时
		if (empty($option)) {
			return $this->redis->set($key, $value);
		} else {
			return $this->redis->set($key, $value, $option);
		}
	}

	/**
	 * 读缓存
	 *
	 * @param string|array $key 缓存KEY,支持一次取多个 $key = ['key1','key2']
	 * @return string|bool  失败返回 false, 成功返回字符串
	 */
	public function get($key)
	{
		// 是否一次取多个值
		$func = is_array($key) ? 'mGet' : 'get';
		return $this->redis->{$func}($key);
	}

   /**
	 * 条件形式设置缓存，如果 key 不存时就设置，存在时设置失败
	 *
	 * @param string $key 缓存KEY
	 * @param string $value 缓存值
	 * @return bool 操作结果
	 */
	public function setnx($key, $value)
	{
		return $this->redis->setnx($key, $value);
	}

	/**
	 * 删除缓存
	 *
	 * @param string|array $key 缓存KEY，支持单个健:"key1" 或多个健:array('key1','key2')
	 * @return int 删除的健的数量
	 */
	public function remove($key)
	{
		// $key => "key1" || array('key1','key2')
		return $this->redis->del($key);
	}

	/**
	 * 删除缓存
	 *
	 * @param string|array $key 缓存KEY，支持单个健:"key1" 或多个健:array('key1','key2')
	 * @return int 删除的健的数量
	 */
	public function del($key)
	{
		// $key => "key1" || array('key1','key2')
		return $this->redis->del($key);
	}

	/**
	 * 值加加操作,类似 ++$i ,如果 key 不存在时自动设置为 0 后进行加加操作
	 *
	 * @param string $key 缓存KEY
	 * @param int $step 操作时的默认值
	 * @return int 操作后的值
	 */
	public function incr($key, $step = 1)
	{
		if ($step == 1) {
			return $this->redis->incr($key);
		} else {
			return $this->redis->incrBy($key, $step);
		}
	}

	/**
	 * 值减减操作,类似 --$i ,如果 key 不存在时自动设置为 0 后进行减减操作
	 *
	 * @param string $key 缓存KEY
	 * @param int $step 操作时的默认值
	 * @return int 操作后的值
	 */
	public function decr($key, $step = 1)
	{
		if ($step == 1) {
			return $this->redis->decr($key);
		} else {
			return $this->redis->decrBy($key, $step);
		}
	}

	/**
	 * 设定一个key的活动时间（s）
	 *
	 * @param string $key 缓存KEY
	 * @param int $time 过期时间秒
	 * @return bool 操作结果
	 */
	public function expire($key, $time)
	{
		return $this->redis->expire($key, $time);
	}

	/**
	 * 清理当前redis数据库所有数据
	 *
	 * @return bool 返回操作结果
	 */
	public function clean()
	{
		return $this->redis->flushDB();
	}

	/**
	 * hash字段的存储
	 *
	 * @param string $key 键名
	 * @param array $data 设置内容
	 * @param int $expire 设置过期时长（s）
	 * @return bool 返回操作结果
	 */
	public function setData($key, $data, $expire = 0)
	{
		if (self::checkLock($key)) {
			$data = empty($data) ? [] : $data;
			$this->redis->hMSet($key, $data);
			if ($expire > 0) {
				$this->redis->expireAt($key, time() + $expire);
			}
			self::freeLock($key);
			return true;
		} else {
			return false;
		}
	}

	/**
	 * hash字段的读取
	 *
	 * @param string $key \键名
	 * @return array|bool 返回值
	 */
	public function getData($key)
	{
		$data = $this->redis->hGetAll($key);
		return empty($data) ? false : $data;
	}

	/**
	 * 检查操作锁
	 *
	 * @param string $key 设置锁名称
	 * @param int $expire 设置过期时长（s）
	 * @return bool 返回检查结果
	 */
	public function checkLock($key, $expire = 30)
	{
		$time = time();
		$key .= ':lock';
		if (self::set($key, $time + $expire, ['nx', 'ex' => $expire])) {
			$lock = true;
		} else {
			$last = self::get($key);
			if ($time > $last) {
				$lock = true;
				self::remove($key);
			} else {
				$lock = false;
			}
		}
		return $lock;
	}

	/**
	 * 释放操作锁
	 * @param string $key 设置锁名称
	 * @return void
	 */
	public function freeLock($key)
	{
		$key .= ':lock';
		self::remove($key);
	}

	/**
	 * 批量删除key
	 *
	 * @param string $match 匹配KEY正则值
	 */
	public function delKeys($match)
	{
		$count = 300;
		$index = 0;
		$this->redis->setOption(\Redis::OPT_SCAN, \Redis::SCAN_RETRY);
		$iterator = null;
		while ($keys = call_user_func_array(array($this->redis, 'scan'), array(&$iterator, $match, $count))) {
			self::remove($keys);
		}
	}

	/**
	 * 列表的存储
	 *
	 * @param string $key 键名
	 * @param array $list 列表
	 * @param int $expire 过期时间（s）
	 * @return bool 是否存储成功
	 */
	public function setList($key, $list, $expire = 0)
	{
		if (self::checkLock($key)) {
			$data = json_encode($list, JSON_UNESCAPED_UNICODE);
			if ($expire > 0) {
				self::set($key, $data, $expire);
			} else {
				self::set($key, $data);
			}
			self::freeLock($key);
			return true;
		} else {
			return false;
		}
	}

	/**
	 * 列表的读取
	 *
	 * @param string $key 键名
	 * @return array|bool 返回列表值，失败则返回false
	 */
	public function getList($key)
	{
		$data = $this->redis->get($key);
		if ($data !== false) {
			$list = json_decode($data, true);
			return $list;
		}
		return false;
	}

	/** 检查给定 key 是否存在
	 * @param string $key 监测键
	 * @param mixed $keys 更多缓存值
	 * @return int 返回0或1或n(多个key)
	 */
	public function exists($key, ...$keys)
	{
		return $this->redis->exists($key, ...$keys);
	}

	/**
	 * 在列表中添加一个或多个值
	 * @param string $key 列表键
	 * @param mixed $value 缓存值
	 * @param mixed $args 更多缓存值
	 * @return int|false 返回当前长度或失败
	 */
	public function rPush($key, $value, ...$args)
	{
		return $this->redis->rPush($key, $value, ...$args);
	}

	/**
	 * 移出并获取列表的第一个元素
	 * @param string $key 列表键
	 * @return mixed 返回缓存值
	 */
	public function lPop($key)
	{
		return $this->redis->lPop($key);
	}

	/**
	 * 将一个值插入到已存在的列表头部
	 * @param string $key 列表键
	 * @param mixed $value 缓存值
	 * @param mixed $args 更多缓存值
	 * @return int|false 返回当前长度或失败
	 */
	public function lPush($key, $value, ...$args)
	{
		return $this->redis->lPush($key, $value, ...$args);
	}

	/**
	 * 移出并获取列表的最后一个元素
	 * @param string $key 列表键
	 * @return mixed 返回缓存值
	 */
	public function rPop($key)
	{
		return $this->redis->rPop($key);
	}

	/**
	 * 获取列表指定范围内的元素
	 * @param string $key 列表键
	 * @param int $start 开始
	 * @param int $stop 结束值
	 * @return array 返回列表
	 */
	public function lRange($key, $start, $stop)
	{
		return $this->redis->lRange($key, $start, $stop);
	}

	/**
	 * 获取指定键名的生命周期
	 * 
	 * @param string $key 键名
	 * @return int 返回剩余秒数
	 */
	public function ttl($key)
	{
		return $this->redis->ttl($key);
	}

	/**
	 * 设置指定键名的基数
	 * 
	 * @param string $key 键名
	 * @param array $elements 成员数组
	 * @return int 返回存入值,如果存在返回0
	 */
	public function pfAdd($key, $elements)
	{
		return $this->redis->pfAdd($key, $elements);
	}

	/**
	 * 获取指定键名的基数估算值
	 * 
	 * @param string|array $key 键名或键名的数组
	 * @return int 返回统计数
	 */
	public function pfCount($key)
	{
		return $this->redis->pfCount($key);
	}

    // 获取原始 phpredis 对象
    public function raw(): \Redis
    {
        return $this->redis;
    }

    // 禁止克隆
    private function __clone() {}
}
