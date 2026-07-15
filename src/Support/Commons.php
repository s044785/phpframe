<?php

declare(strict_types=1);

namespace PHPFrame\Support;

use PHPFrame\Support\Redis;

//公共对象类
class Commons
{

	/**
	 * 安全字符
	 *
	 * @param string $str 输入字符
	 * @param boolean $hs 是否特殊HTML编码，默认是
	 * @return string 返回编码后字符
	 */
	public static function safeStr($str, $hs = true)
	{
		if (function_exists('htmlspecialchars') && $hs == true) {
			$str = htmlspecialchars($str);
		}
		$str = addslashes($str);
		return $str;
	}

	/**
	 * 安全数组
	 *
	 * @param array $array 输入数组
	 * @return array 返回编码后数组
	 */
	public static function safeArray($array)
	{
		$result = [];
		foreach ($array as $key => $value) {
			$result[$key] = is_array($value) ? self::safeArray($value) : self::safeStr($value);
		}
		return $result;
	}

	/**
	 * 安全对象
	 *
	 * @param object $object 输入数组
	 * @return object 返回编码后数组
	 */
	public static function safeObject($object)
	{
		$result = (object)[];
		foreach ($object as $key => $value) {
			$result->$key = is_object($value) ? self::safeObject($value) : self::safeStr($value);
		}
		return $result;
	}

	/**
	 * 安全HTML
	 *
	 * @param string $str 输入字符
	 * @return string 返回编码后字符
	 */
	public static function safeHtml($str)
	{
		if (function_exists('htmlspecialchars')) {
			$str = htmlspecialchars($str);
		}
		return $str;
	}

	/**
	 * 警告
	 *
	 * @param string $info 警告内容
	 * @param string $url 返回的URL
	 * @return string
	 */
	public static function alert($info, $url)
	{
		echo '<meta charset="utf-8">';
		if (substr($url, 0, 11) == 'javascript:') {
			$url = substr($url, 11);
			echo '<script>alert("' . $info . '");' . $url . '</script>';
		} else {
			echo '<script>alert("' . $info . '");location="' . $url . '";</script>';
		}
		exit;
	}

	/**
	 * 字符导零
	 *
	 * @param string $str 输入字符
	 * @param int $len 需要长度
	 * @return string
	 */
	public static function fillStr($str, $len = 2)
	{
		$fill = '0000000000000';
		return strlen($str) < $len ? substr($fill . $str, -$len) : $str;
	}

	/**
	 * 返回时间信息
	 *
	 * @param int $input 输入内容
	 * @param string $format 需要的时间格式
	 * @param bool $diff 返回时间差，默认返回
	 * @return string
	 */
	public static function getTimeInfo($input, $format = 'Y-m-d H:i:s', $diff = true)
	{
		$time = empty($input) ? 0 : (is_numeric($input) ? $input : strtotime($input));
		if ($time <= 0 && $diff) {
			return '--';
		}
		if ($diff) {
			$t = time() - $time;
			if ($t < 60) {
				return '刚刚';
			} elseif ($t >= 60 && $t < 3600) {
				return (int) ($t / 60) . '分钟前';
			} elseif ($t >= 3600 && $t < 86400) {
				return (int) ($t / 3600) . '小时前';
			} elseif ($t >= 86400 && $t < 86400 * 2) {
				return '1天前';
			} else {
				return date($format, $time);
			}
		} else {
			return date($format, $time);
		}
	}

	/**
	 * 返回剩余时间
	 *
	 * @param int $seconds 秒数
	 * @return array [天,小时,分钟,秒]
	 */
	public static function getTimeRest($seconds)
	{
		if ($seconds < 0) {
			return false;
		}
		$day = floor($seconds / (60 * 60 * 24));
		$hour = self::fillStr(floor($seconds / (60 * 60) - ($day * 24)));
		$minute = self::fillStr(floor(fmod($seconds / 60, 60)));
		$second = self::fillStr(floor(fmod($seconds, 60)));
		return [$day, $hour, $minute, $second];
	}

	/**
	 * 返回数字格式
	 *
	 * @param int $number 数值
	 * @param string $lan 显示语言可选en,zh
	 * @return string 数字格式
	 */
	public static function getNumberInfo($number, $lan = 'en')
	{
		$lanstr = ['en' => ['K', 'W', 'W+'], 'zh' => ['千', '万', '万+']];
		if ($number < 1000) {
			return $number;
		} elseif ($number >= 1000 && $number < 10000) {
			return floor($number / 1000) . $lanstr[$lan][0];
		} elseif ($number >= 10000 && $number < 100000) {
			return floor($number / 10000) . $lanstr[$lan][1];
		} elseif ($number >= 100000) {
			return floor($number / 10000) . $lanstr[$lan][2];
		}
	}

	/**
	 * 返回域名
	 *
	 * @param string $url 需要解析的URL
	 * @return string 域名
	 */
	public static function getDomain($url)
	{
		return parse_url($url, PHP_URL_HOST);
	}

	/**
	 * 返回URL参数
	 *
	 * @param string $url 需要解析的URL
	 * @return array 参数数组
	 */
	public static function getQuery($url)
	{
		$query = parse_url($url, PHP_URL_QUERY);
		$params = [];
		if (!empty($query)) {
			$queryParts = explode('&', $query);
			foreach ($queryParts as $param) {
				if (!empty($param)) {
					$item = explode('=', $param);
					if (count($item) > 1) {
						$params[$item[0]] = urldecode(substr($param, strlen($item[0] . '=')));
					}
				}
			}
		}
		return $params;
	}

	/**
	 * 模拟提交数据函数
	 *
	 * @param string $url 提交URL
	 * @param mixed $data 提交的数据
	 * @param array $options 参数选项
	 * @param bool $debug 是否调试
	 * @return string 返回服务器响应结果
	 */
	public static function vpost($url, $data = '', $options = [], $debug = false)
	{
		$curl = curl_init(); // 启动一个CURL会话
		curl_setopt($curl, CURLOPT_URL, $url); // 要访问的地址
		curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, 0); // 对认证证书来源的检查
		curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 2); // 从证书中检查SSL加密算法是否存在
		curl_setopt($curl, CURLOPT_FOLLOWLOCATION, 1); // 使用自动跳转
		curl_setopt($curl, CURLOPT_AUTOREFERER, 1); // 自动设置Referer
		if (!empty($data)) {
			curl_setopt($curl, CURLOPT_POST, 1); // 发送一个常规的Post请求
			curl_setopt($curl, CURLOPT_POSTFIELDS, $data); // Post提交的数据包
		}
		curl_setopt($curl, CURLOPT_TIMEOUT, 30); // 设置超时限制防止死循环
		curl_setopt($curl, CURLOPT_HEADER, 0); // 显示返回的Header区域内容
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1); // 获取的信息以文件流的形式返回
		if (isset($_SERVER['HTTP_USER_AGENT'])) {
			curl_setopt($curl, CURLOPT_USERAGENT, $_SERVER['HTTP_USER_AGENT']); // 模拟用户使用的浏览器
		}
		foreach ($options as $item) {
			curl_setopt($curl, $item[0], $item[1]);
		}
		$res = curl_exec($curl); // 执行操作
		if ($debug) {
			$option = json_encode($options, JSON_UNESCAPED_UNICODE);
			$data = is_array($data) || is_object($data) ? json_encode($data, JSON_UNESCAPED_UNICODE) : $data;
			Commons::errorLogs("\n请求地址：{$url}\n请求数据：{$data}\n请求选项：{$option}\n返回数据：{$res}\n\n");
			if (curl_errno($curl)) {
				Commons::errorLogs(curl_error($curl)); //捕抓异常
			}
		}
		curl_close($curl); // 关闭CURL会话
		return $res; // 返回数据
	}

	/**
	 * 从服务器获取函数
	 *
	 * @param string $url 提交URL
	 * @param array $options 参数选项
	 * @param bool $debug 是否调试
	 * @return string 返回服务器响应结果
	 */
	public static function httpGet($url, $options = [], $debug = false)
	{
		$curl = curl_init();
		curl_setopt($curl, CURLOPT_URL, $url);
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
		foreach ($options as $item) {
			curl_setopt($curl, $item[0], $item[1]);
		}
		$res = curl_exec($curl);
		curl_close($curl);
		if ($debug) {
			$option = json_encode($options, JSON_UNESCAPED_UNICODE);
			Commons::errorLogs("\n请求地址：{$url}\n请求选项：{$option}\n返回数据：{$res}\n\n");
		}
		return $res;
	}

	/**
	 * 格式化字段
	 *
	 * @param array $fields 需要的字段
	 * @param boolean $tolower 是否转为小写
	 * @param array $except 需要忽略的字段
	 * @return array 返回格式化结果
	 */
	public static function formatFields($fields, $tolower = true, $except = [])
	{
		$data = [];
		if (empty($fields)) {
			return $fields;
		}
		foreach ($fields as $key => $value) {
			if (is_array($value)) {
				$value = self::formatFields($value, $tolower, $except);
			} else {
				if (!in_array($key, $except)) {
					if (in_array(substr($key, 0, 2), ['i_', 'c_', 'd_'])) {
						$key = substr($key, 2);
						if ($tolower) {
							$key = strtolower($key);
						}
					}
				}
			}
			$data[$key] = $value;
		}
		return $data;
	}

	/**
	 * 生成交易单号
	 *
	 * @param string $pre 交易单号前缀
	 * @return string 返回交易单号
	 */
	public static function getTradeNO($pre = '')
	{
		return $pre . substr(date('Y'), 2) . date('mdHis') . mt_rand(10000, 99999);
	}

	/**
	 * 获取用户IP
	 *
	 * @return string 返回用户IP
	 */
	public static function getIP()
	{
		// $userIP = (@$_SERVER['HTTP_VIA']) ? @$_SERVER['HTTP_X_FORWARDED_FOR'] : @$_SERVER['REMOTE_ADDR'];
		// $userIP = !empty($userIP) ? $userIP : @$_SERVER['REMOTE_ADDR'];
		if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
			// 检查 IP 是否来自共享互联网访问点
			$userIP = $_SERVER['HTTP_CLIENT_IP'];
		} elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
			// 检查 IP 是否通过代理
			$userIP = $_SERVER['HTTP_X_FORWARDED_FOR'];
		} elseif (!empty($_SERVER['HTTP_X_REAL_IP'])) {
			// 检查 IP 是否通过 Nginx 代理
			$userIP = $_SERVER['HTTP_X_REAL_IP'];
		} else {
			// 如果没有通过代理，则直接获取 IP
			$userIP = @$_SERVER['REMOTE_ADDR'];
		}

		return is_null($userIP) ? '' : trim(explode(',', $userIP)[0]);
	}

	/**
	 * 使用JS字符串
	 *
	 * @param string $script JS内容
	 * @return string JS内容
	 */
	public static function script($script)
	{
		return '<script>' . $script . '</script>';
	}

	/**
	 * 分页字符串
	 *
	 * @param int $pageIndex 页码
	 * @param int $totalNum 总数据条数
	 * @param int $pageSize 分页大小
	 * @param string $urlPath 路径
	 * @param string $pageStr 页码参数
	 * @param boolean $showNum 是否显示总数
	 * @param string $class 分页自定义样式名称
	 * @param bool $showSkip 显示转跳
	 * @return string 分页HTML
	 */
	public static function showPages($pageIndex, $totalNum, $pageSize = 15, $urlPath = '', $pageStr = '?pageIndex=', $showNum = false, $class = 'pages', $showSkip = false)
	{
		$maxPage = ceil($totalNum / $pageSize);
		$pageIndex = min(max(1, $pageIndex), $maxPage);
		$pagination = '<div class="' . $class . '">' . PHP_EOL;
		$pagination .= '        <ul>' . PHP_EOL;
		$a = 0;
		if ($pageIndex > 1) {
			$pagination .= '          <li><a href="' . $pageStr . '1' . $urlPath . '" title="第一页"><i class="icon-skip-back"></i></a></li>' . PHP_EOL;
			$pagination .= '          <li><a href="' . $pageStr . ($pageIndex - 1) . $urlPath . '" title="上一页"><i class="icon-rewind"></i></a></li>' . PHP_EOL;
		}
		if ($pageIndex > 1) {
			$a = 1;
		}
		if ($pageIndex > 2) {
			$a = 2;
		}
		if ($pageIndex > 3) {
			$a = 3;
		}
		for ($p1 = $pageIndex - $a; $p1 < $pageIndex; $p1++) {
			$pagination .= '          <li><a href="' . $pageStr . $p1 . $urlPath . '">' . $p1 . '</a></li>' . PHP_EOL;
		}
		if ($maxPage <> 1) {
			$pagination .= '          <li><a class="selected">' . $pageIndex . '</a></li>' . PHP_EOL;
		}
		for ($p2 = $pageIndex + 1; $p2 < $pageIndex + 5; $p2++) {
			if ($p2 > $maxPage) {
				break;
			}
			$pagination .= '          <li><a href="' . $pageStr . $p2 . $urlPath . '">' . $p2 . '</a></li>' . PHP_EOL;
		}
		if ($showSkip && $pageIndex < $maxPage - 5) {
			$pagination .= '          <li><span>···</span></li>';
			$pagination .= '          <li><a href="' . $pageStr . ($maxPage - 1) . $urlPath . '">' . ($maxPage - 1) . '</a></li>' . PHP_EOL;
		}
		if ($pageIndex < $maxPage) {
			$pagination .= '          <li><a href="' . $pageStr . ($pageIndex + 1) . $urlPath . '" title="下一页"><i class="icon-fast-forward"></i></a></li>' . PHP_EOL;
		}
		if ($showNum && !$showSkip) {
			if ($pageIndex < $maxPage) {
				$pagination .= '          <li><a href="' . $pageStr . ($maxPage) . $urlPath . '" title="最后页"><i class="icon-skip-forward"></i></a></li>' . PHP_EOL;
			}
		}
		$pagination .= '        </ul>' . PHP_EOL;
		if ($showNum && !$showSkip) {
			$pagination .= '      <div class="page-detail">共' . $totalNum . '条</div>' . PHP_EOL;
		}
		$pagination .= '    </div>' . PHP_EOL;
		return $pagination;
	}

	/**
	 * 搜索字符串
	 *
	 * @param string $word 关键词
	 * @param string $content 内容
	 * @param int $left 关键词前长度
	 * @param int $length 需要长度
	 * @param string $style 关键词样式
	 * @return string 搜索结果HTML
	 */
	public static function strSearch($word, $content, $left = 16, $length = 60, $style = '')
	{
		if (mb_strlen($content) > $length && !empty($word)) {
			$first = mb_stripos($content, $word, 0, 'utf-8');
			$wordlen = mb_strlen($word, 'utf-8');
			if ($first !== false) {
				if ($first < $left) {
					$content = mb_substr($content, 0, $length, 'utf-8') . '...';
				} else {
					$content = '...' . mb_substr($content, $first - $left, $length, 'utf-8') . '...';
				}
				$content = str_ireplace($word, '<em style="' . $style . '">' . $word . '</em>', $content);
			} else {
				$content = mb_substr($content, 0, $length, 'utf-8') . '...';
			}
		} else {
			$content = str_ireplace($word, '<em style="' . $style . '">' . $word . '</em>', $content);
		}
		return $content;
	}

	/**
	 * 错误日志记录
	 *
	 * @param mixed $content 记录内容
	 * @param string $prefix 文件前缀
	 */
	public static function errorLogs($content, $prefix = 'error_')
	{
		$file = __DIR__ . '/../../upload/logs/' . $prefix . date('Y-m-d') . '.txt';
		if (!is_dir(dirname($file)) && !self::mkdirs(dirname($file), 0777)) {
			return;
		}
		$handle = fopen($file, 'a');
		$content = is_array($content) || is_object($content) ? json_encode($content, JSON_UNESCAPED_UNICODE) : $content;
		//file_put_contents($file, $log, LOCK_EX);
		if ($handle) {
			$log = date('Y-m-d H:i:s') . ' ' . $_SERVER['SCRIPT_FILENAME'] . ' ' . $content . PHP_EOL;
			fwrite($handle, $log);
			fclose($handle);
		}
	}

	/**
	 * 创建多层级目录
	 *
	 * @param $dirs 目录路径
	 * @param $mode 权限编码
	 * @return boolean 创建结果
	 */
	public static function mkdirs($dirs = '', $mode = 0777)
	{
		if (!is_dir($dirs)) {
			self::mkdirs(dirname($dirs), $mode);
			$ret = @mkdir($dirs, $mode);
			chmod($dirs, $mode);
			return $ret;
		}
		return true;
	}

	/**
	 * 获取平台类型
	 *
	 * @param string $userAgent 用户代理
	 * @return string 平台类型
	 */
	public static function getPlatform($userAgent = '')
	{
		$userAgent = empty($userAgent) ? $_SERVER['HTTP_USER_AGENT'] : $userAgent;
		$userAgent = @strtolower($userAgent);
		if (stripos($userAgent, 'iphone') !== false) {
			return 'ios';
		} elseif (stripos($userAgent, 'android') !== false) {
			return 'android';
		} elseif (stripos($userAgent, 'windows') !== false) {
			return 'windows';
		} elseif (stripos($userAgent, 'ipad') !== false) {
			return 'ipad';
		} elseif (stripos($userAgent, 'macintosh') !== false) {
			return 'mac';
		} else {
			return 'other';
		}
	}

	/**
	 * 组合成URL
	 *
	 * @param array $array 数组
	 * @return string 组合的URL
	 */
	public static function toUrlParams($array)
	{
		$buff = '';
		foreach ($array as $k => $v) {
			if (is_array($v)) {
				$v = json_encode($v, JSON_UNESCAPED_UNICODE);
			}
			$v = str_replace('+', ' ', $v);
			$buff .= $k . '=' . $v . '&';
		}
		$buff = trim($buff, '&');
		return $buff;
	}

	/**
	 * 递归对数组按照键名排序
	 * @param array $array 输入的数组
	 * @return array
	 */
	public static function arrayKsort($array)
	{
		ksort($array);
		foreach ($array as $k => $v) {
			if (is_array($v)) {
				$array[$k] = self::arrayKsort($v);
			}
		}
		return $array;
	}

	/**
	 * 获取签名
	 *
	 * @param array $data 数组
	 * @param string $key 加入密钥
	 * @return string 签名
	 */
	public static function makeSign($data, $key = '')
	{
		$data = self::arrayKsort($data);
		$string = self::toUrlParams($data) . $key;
		$string = md5($string);
		return $string;
	}

	/**
	 * 验证签名
	 *
	 * @param array $data 数组
	 * @param string $key 加入密钥
	 * @return boolean 验证结果
	 */
	public static function checkSign($data, $key = '')
	{
		if (!is_array($data)) {
			$data = self::objectToArray($data);
		}
		if (!array_key_exists('sign', $data)) {
			self::errorLogs($data);
		}
		return array_key_exists('sign', $data) && $data['sign'] == self::makeSign(array_diff_key($data, ['sign' => '']), $key) ? true : false;
	}

	/**
	 * 对象转为数组
	 *
	 * @param object $obj 对象
	 * @return array 转换结果
	 */
	public static function objectToArray($obj)
	{
		$obj = (array) $obj;
		foreach ($obj as $k => $v) {
			if (gettype($v) == 'resource') {
				return;
			}
			if (gettype($v) == 'object' || gettype($v) == 'array') {
				$obj[$k] = (array) self::objectToArray($v);
			}
		}

		return $obj;
	}

	/**
	 * 返回指定值的数据
	 *
	 * @param array $data 数据
	 * @param array $fields 需要的字段，$field 为数组时表示可忽略参数不会露出
	 * @param array $default 默认值，键名必须包含在$fields中才会露出
	 * @return array 返回结果
	 */
	public static function resultFields($data, $fields = [], $default = [])
	{
		if (!empty($fields)) {
			$result = [];
			if (empty($data)) {
				foreach ($fields as $field) {
					if (!is_array($field)) {
						$result[$field] = array_key_exists($field, $default) ? $default[$field] : '';
					}
				}
			} else {
				foreach ($fields as $field) {
					if (!is_array($field)) {
						$value = array_key_exists($field, $data) ? $data[$field] : '';
						$result[$field] = $value !== '' ? $value : (array_key_exists($field, $default) ? $default[$field] : '');
					} else {
						$key = $field[0];
						if (array_key_exists($key, $data)) {
							$value = $data[$key];
							$result[$key] = $value !== '' ? $value : (array_key_exists($key, $default) ? $default[$key] : '');
						}
					}
				}
			}
		} else {
			$result = $data;
		}

		return $result;
	}

	/**
	 * 多维数组按某一个键值进行排序
	 *
	 * @param array $array 数组
	 * @param string $keys 需要排序键名
	 * @param string $type 排序类型
	 * @return array 排序后结果集
	 */
	public static function arraySort($array, $keys, $type = 'asc')
	{
		//$array为要排序的数组,$keys为要用来排序的键名,$type默认为升序排序
		$keysvalue = $new_array = [];
		foreach ($array as $k => $v) {
			$keysvalue[$k] = $v[$keys];
		}
		if ($type == 'asc') {
			asort($keysvalue);
		} else {
			arsort($keysvalue);
		}
		reset($keysvalue);
		foreach ($keysvalue as $k => $v) {
			$new_array[$k] = $array[$k];
		}
		return $new_array;
	}

	/**
	 * 接口错误返回
	 *
	 * @param string $message 消息内容
	 * @param string $errcode 错误编码
	 * @param string $function 返回类型
	 * @param bool $debug 是否调试
	 * @return string 返回json
	 */
	public static function apiErrcode($message = '', $errcode = '', $function = '', $debug = false)
	{
		$data['status'] = 'FAILED';
		$data['message'] = $message;
		if (!empty($errcode)) {
			$data['errcode'] = $errcode;
		}
		if ($debug) {
			self::errorLogs($data, 'api');
		}
		if ($function == 'return') {
			return $data;
		} else {
			echo json_encode($data, JSON_UNESCAPED_UNICODE);
			exit;
		}
	}

	/**
	 * 接口正确返回
	 *
	 * @param array $data 数据内容
	 * @param string $function 返回类型
	 * @param bool $debug 是否调试
	 * @return string 返回json
	 */
	public static function apiSuccess($data = [], $function = '', $debug = false)
	{
		$data['status'] = 'SUCCESS';
		$data['errcode'] = 0;
		if ($debug) {
			self::errorLogs($data, 'api');
		}
		if ($function == 'return') {
			return $data;
		} else {
			echo json_encode($data, JSON_UNESCAPED_UNICODE);
			exit;
		}
	}

	/**
	 * 标签格式化
	 *
	 * @param string $tags 标签内容
	 * @return array 返回标签集
	 */
	public static function formatTags($tags)
	{
		if (empty($tags)) {
			return [];
		}
		if (is_array($tags)) {
			return $tags;
		}
		$tags = str_replace('|', ' ', $tags);
		$tags = str_replace(',', ' ', $tags);
		$tags = str_replace('、', ' ', $tags);
		$tags = str_replace('，', ' ', $tags);
		$tags = str_replace(';', ' ', $tags);
		$tags = str_replace('；', ' ', $tags);
		$tags = str_replace('。', ' ', $tags);
		$tags = str_replace('  ', ' ', $tags);
		$tags = trim($tags);
		$list = empty($tags) ? [] : array_unique(explode(' ', $tags));
		$tagList = [];
		foreach ($list as $item) {
			$strlen = mb_strlen($item, 'utf-8');
			$strlen >= 1 && $strlen < 12 ? $tagList[] = $item : '';
		}
		return $tagList;
	}

	/**
	 * 重设图片大小
	 *
	 * @param string $source 原始的
	 * @param int $width 需要设置的宽度
	 * @param int $height 需要设置的高度
	 * @param string $cdn CDN前缀地址，设置后需要添加到图片前
	 */
	public static function imageResize($source, $width = 0, $height = 0, $cdn = '')
	{
		$url = explode('?', $source)[0];
		if (empty($url)) {
			return $cdn . '/images/nopic.png';
		}
		if (!empty($cdn)) {
			$url = substr($url, 0, 1) == '/' && substr($url, 0, 2) != '//' ? $cdn . $url : $url;
		}
		if ($width > 0 && $height > 0) {
			$url .= "?x-oss-process=image/resize,w_$width,h_$height";
		} elseif ($width > 0 && $height == 0) {
			$url .= "?x-oss-process=image/resize,w_$width";
		} elseif ($width == 0 && $height > 0) {
			$url .= "?x-oss-process=image/resize,h_$height";
		}
		return $url;
	}

	/**
	 * 获取URL路径
	 *
	 * @param array $filter 需要过滤参数集
	 * @param array $inter 需要返回参数集
	 * @param string $url URL
	 * @return string 组合的URL路径
	 */
	public static function urlPath($filter = [], $inter = [], $url = '')
	{
		$url = empty($url) ? $_SERVER['REQUEST_URI'] : $url;
		$urlArray = parse_url($url);
		$urlQuery = array_key_exists('query', $urlArray) ? $urlArray['query'] : '';
		parse_str($urlQuery, $params);
		if (!empty($filter) & !empty($params)) {
			$params = array_diff_key($params, array_fill_keys($filter, ''));
		}
		if (!empty($inter)) {
			$params = array_intersect_key($params, array_fill_keys($inter, ''));
		}
		$urlQuery = http_build_query($params);
		return empty($urlQuery) ? '' : '&' . $urlQuery;
	}

	/**
	 * 获取生命周期
	 *
	 * @param array $datetimeArray 时间数组
	 * @return int 获取最小的时间戳
	 */
	public static function getLifeTime($datetimeArray)
	{
		sort($datetimeArray);
		$timestamp = time();
		foreach ($datetimeArray as $datetime) {
			if (strtotime($datetime) > $timestamp) {
				return strtotime($datetime) - $timestamp;
			}
		}
		return 1;
	}

	/**
	 * 下划线转驼峰
	 *
	 * 思路:
	 * step1.原字符串转小写,原字符串中的分隔符用空格替换,在字符串开头加上分隔符
	 * step2.将字符串中每个单词的首字母转换为大写,再去空格,去字符串首部附加的分隔符.
	 * @param string $uncamelizedWords 需要转换的字符
	 * @param string $separator 分隔符
	 * @return string 返回转换结果
	 */
	public static function camelize($uncamelizedWords, $separator = '_')
	{
		$uncamelizedWords = $separator . str_replace($separator, ' ', strtolower($uncamelizedWords));
		return ltrim(str_replace(' ', '', ucwords($uncamelizedWords)), $separator);
	}

	/**
	 * 数组下划线转驼峰
	 *
	 * @param array $array 转换数组
	 * @return array 返回转换结果
	 */
	public static function arrayCamelize($array)
	{
		if (empty($array)) {
			return $array;
		}
		$data = [];
		foreach ($array as $key => $value) {
			$newKey = self::camelize($key);
			if (is_array($value)) {
				$data[$newKey] = self::arrayCamelize($value);
			} else {
				$data[$newKey] = $value;
			}
		}
		return $data;
	}

	/**
	 * 驼峰命名转下划线命名
	 *
	 * 思路:
	 * 小写和大写紧挨一起的地方,加上分隔符,然后全部转小写
	 * @param string $uncamelized_words 需要转换的字符
	 * @param string $separator 分隔符
	 * @return string 返回转换结果
	 */
	public static function uncamelize($camelCaps, $separator = '_')
	{
		return strtolower(preg_replace('/([a-z])([A-Z])/', "$1" . $separator . "$2", $camelCaps));
	}

	/**
	 * 数组驼峰命名转下划线命名
	 *
	 * @param array $array 转换数组
	 * @param array $unval 是否转换值，默认转换
	 * @return array 返回转换结果
	 */
	public static function arrayUncamelize($array, $unval = true)
	{
		$data = [];
		foreach ($array as $key => $value) {
			$newKey = self::uncamelize($key);
			if ($unval && is_array($value)) {
				$data[$newKey] = self::arrayUncamelize($value);
			} else {
				$data[$newKey] = $value;
			}
		}
		return $data;
	}

	/**
	 * 隐藏手机号码中间4位
	 *
	 * @param string $mobile 手机号
	 * @return string
	 */
	public static function privateMobile($mobile)
	{
		return substr($mobile, 0, 3) . '****' . substr($mobile, -4);
	}

	/**
	 * 隐藏电子邮件中间几位
	 *
	 * @param string $email 电子邮件
	 * @return string
	 */
	public static function privateEmail($email)
	{
		$arrayEmail = explode('@', $email);
		return substr($email, 0, 2) . '***' . substr($arrayEmail[0], -1) . '@' . $arrayEmail[1];
	}

	/**
	 * 隐藏姓名中间字
	 *
	 * @param string $name 姓名
	 * @return string
	 */
	public static function privateName($name)
	{
		$lengthName = mb_strlen($name, 'utf-8');
		return $lengthName <= 2 ? mb_substr($name, 0, 1, 'utf-8') . '*' : mb_substr($name, 0, 1, 'utf-8') . '**';
	}

	/**
	 * 隐藏身份证中间字符
	 *
	 * @param string $idCard 身份证号码
	 * @return string
	 */
	public static function privateIdCard($idCard)
	{
		return  mb_substr($idCard, 0, 3) . '****************' . mb_substr($idCard, -2);
	}

	/**
	 * 检查用户名是否合法
	 *
	 * @param string $userName 用户名
	 * @return boolean
	 */
	public static function chkUserName($userName)
	{
		$pattern = "/^[a-zA-Z][0-9A-Za-z_]{3,15}$/";
		return preg_match($pattern, $userName) ? true : false;
	}

	/**
	 * 检查手机号是否合法
	 *
	 * @param string $mobile 手机号
	 * @return boolean
	 */
	public static function chkMobile($mobile)
	{
		$pattern = "/^1[3456789]\d{9}$/";
		return preg_match($pattern, $mobile) ? true : false;
	}

	/**
	 * 检查电子邮箱是否合法
	 *
	 * @param string $email 电子邮箱
	 * @return boolean
	 */
	public static function chkEmail($email)
	{
		$pattern = "/^([0-9A-Za-z\\-_\\.]+)@([0-9A-Za-z\\-_\\.]+\\.[0-9a-z]+(\\.[a-z]+)?)$/";
		return preg_match($pattern, $email) ? true : false;
	}

	/**
	 * 检查身份证号是否合法
	 *
	 * @param string $idCard 身份证号
	 * @return boolean
	 */
	public static function chkIdCard($idCard)
	{
		$result = false;
		$idCard = strtoupper($idCard);
		$pattern = "/^[1-9]\d{5}(18|19|([23]\d))\d{2}((0[1-9])|(10|11|12))(([0-2][1-9])|10|20|30|31)\d{3}[0-9Xx]$/";
		if (preg_match($pattern, $idCard)) {

			$map = [1, 0, 'X', 9, 8, 7, 6, 5, 4, 3, 2];
			$sum = 0;
			for ($i = 17; $i > 0; $i--) {
				$s = pow(2, $i) % 11;
				$sum += $s * $idCard[17 - $i];
			}
			$code = $map[$sum % 11]; //这里显示最后一位校验码
			$result = $code == $idCard[17] ? true : false;
		}
		return $result;
	}

	/**
	 * 判断是否移动设备
	 *
	 * @return boolean
	 */
	public static function isMobile()
	{
		$UserAgent = '';
		if (isset($_SERVER['HTTP_X_WAP_PROFILE'])) {
			return true;
		}
		if (isset($_SERVER['HTTP_USER_AGENT'])) {
			$UserAgent = $_SERVER['HTTP_USER_AGENT'];
		}
		if ($UserAgent != '') {
			if (preg_match('/(blackberry|configuration\/cldc|hp |hp-|htc |htc_|htc-|iemobile|kindle|midp|mmp|motorola|mobile|nokia|opera mini|opera |Googlebot-Mobile|YahooSeeker\/M1A1-R2D2|android|iphone|ipod|mobi|palm|palmos|pocket|portalmmm|ppc;|smartphone|sonyericsson|sqh|spv|symbian|treo|up.browser|up.link|vodafone|windows ce|xda |xda_)/i', $UserAgent)) { //检查USER_AGENT
				return true;
			} else {
				return false;
			}
		} else {
			return false;
		}
	}

	/**
	 * 来源校验
	 *
	 * @return boolean 返回校验结果
	 */
	public static function hostMatch()
	{
		$referer = parse_url($_SERVER['HTTP_REFERER']);
		if (@stripos($_SERVER['HTTP_HOST'], $referer['host']) === false) {
			return false;
		} else {
			return true;
		}
	}

	/**
	 * 获取SessionId
	 * @return string sessionId
	 */
	public static function getSessionId()
	{
		if (session_status() !== PHP_SESSION_ACTIVE) {
			session_start();
		}
		return session_id();
	}

	/**
	 * 判断是否含电话号码
	 * @param string $str 需要验证的字符
	 * @return bool 验证结果
	 */
	public static function havePhone($str)
	{
		$str = str_replace('零', '0', $str);
		$str = str_replace('一', '1', $str);
		$str = str_replace('二', '2', $str);
		$str = str_replace('三', '3', $str);
		$str = str_replace('四', '4', $str);
		$str = str_replace('五', '5', $str);
		$str = str_replace('六', '6', $str);
		$str = str_replace('七', '7', $str);
		$str = str_replace('八', '8', $str);
		$str = str_replace('九', '9', $str);
		$str = str_replace('〇', '0', $str);
		$str = str_replace('壹', '1', $str);
		$str = str_replace('贰', '2', $str);
		$str = str_replace('叁', '3', $str);
		$str = str_replace('肆', '4', $str);
		$str = str_replace('伍', '5', $str);
		$str = str_replace('陆', '6', $str);
		$str = str_replace('柒', '7', $str);
		$str = str_replace('捌', '8', $str);
		$str = str_replace('玖', '9', $str);
		$str = str_replace('①', '1', $str);
		$str = str_replace('②', '2', $str);
		$str = str_replace('③', '3', $str);
		$str = str_replace('④', '4', $str);
		$str = str_replace('⑤', '5', $str);
		$str = str_replace('⑥', '6', $str);
		$str = str_replace('⑦', '7', $str);
		$str = str_replace('⑧', '8', $str);
		$str = str_replace('⑨', '9', $str);
		$str = str_replace('-', '', $str);
		$str = str_replace('.', '', $str);
		$str = str_replace('/', '', $str);
		$str = str_replace('~', '', $str);
		$str = str_replace(' ', '', $str);
		$str = str_replace('微', '', $str);
		$str = str_replace('信', '', $str);
		$str = str_replace('电', '', $str);
		$str = str_replace('话', '', $str);
		$str = str_replace('Q', '', $str);
		$pattern = "/\d{7,12}/";
		if (preg_match($pattern, $str)) {
			return true;
		} else {
			return false;
		}
	}

	/**
	 * 获取审核信息
	 * @param int $status 状态码
	 * @return string 审核结果
	 */
	public static function getPassInfo($status)
	{
		$statusList = [-1 => '未通过', 0 => '待审核', 1 => '已通过'];
		return array_key_exists($status, $statusList) ? $statusList[$status] : '';
	}

	/**
	 * 获取星级
	 * @param int $rate 评星
	 * @return string 返回评星的HTML
	 */
	public static function getRate($rate)
	{
		switch ($rate) {
			case 0:
				return '<i class="icon-star-outline2"></i>';
				break;
			case 1:
				return '<i class="icon-star3"></i>';
				break;
			case 2:
				return '<i class="icon-star3"></i><i class="icon-star3"></i>';
				break;
			case 3:
				return '<i class="icon-star3"></i><i class="icon-star3"></i><i class="icon-star3"></i>';
				break;
			case 4:
				return '<i class="icon-star3"></i><i class="icon-star3"></i><i class="icon-star3"></i><i class="icon-star3"></i>';
				break;
			case 5:
				return '<i class="icon-star3"></i><i class="icon-star3"></i><i class="icon-star3"></i><i class="icon-star3"></i><i class="icon-star3"></i>';
				break;
			default:
				return '未评级';
		}
	}

	/**
	 * 获取简介内容
	 * @param string $intro 详细介绍
	 * @return string 返回获取后的简介内容
	 */
	public static function getDescription($intro)
	{
		$description = str_replace(PHP_EOL, '', strip_tags($intro));
		if ($rightStr = strrpos($description, '。')) {
			if ($rightStr > 240 || $rightStr < 90) {
				$description = mb_substr($description, 0, 120, 'utf-8');
				if ($slipStr = strrpos($description, '，')) {
					$description = substr($description, 0, $slipStr);
				}
			} else {
				$description = substr($description, 0, $rightStr + 3);
			}
		} else {
			$description = mb_substr($description, 0, 120, 'utf-8');
			if ($slipStr = strrpos($description, '，')) {
				$description = substr($description, 0, $slipStr);
			}
		}
		return $description;
	}

	/**
	 * 随机生成码
	 * @param int $length 返回长度
	 * @return string 返回符合长度的码
	 */
	public static function randCode($length = 6)
	{
		$rand = mt_rand(0, 999999);
		return self::fillStr($rand, $length);
	}

	/**
	 * URL添加HTTP头
	 * @param string $url 原始URL
	 * @param string $http http头
	 * @return string 返回URL
	 */
	public static function httpUrl($url, $http = 'https://')
	{
		if (substr($url, 0, 2) == '//') {
			$url = $http . substr($url, 2);
		}
		return $url;
	}

	/**
	 * URL Base64加密
	 * @param string $data 原始URL
	 * @return string 返回URL
	 */
	public static function base64EncodeUrl($data)
	{
		return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
	}

	/**
	 * URL Base64解密
	 * @param string $data URL
	 * @return string 返回原始URL
	 */
	public static function base64DecodeUrl($data)
	{
		return base64_decode(str_pad(strtr($data, '-_', '+/'), strlen($data) % 4, '=', STR_PAD_RIGHT));
	}

	/**
	 * 判断是否微信浏览器
	 * @return bool
	 */
	public static function isWeixin()
	{
		@$httpUserAgent = $_SERVER['HTTP_USER_AGENT'];
		if (stripos($httpUserAgent, 'MicroMessenger') !== false) {
			return true;
		} else {
			return false;
		}
	}

	/**
	 * 判断是否微信小程序
	 * @return bool
	 */
	public static function isMiniProgram()
	{
		@$httpUserAgent = $_SERVER['HTTP_USER_AGENT'];
		if (stripos($httpUserAgent, 'miniProgram') !== false) {
			return true;
		} else {
			return false;
		}
	}

	/**
	 * 判断是否机器人
	 * @return bool
	 */
	public static function isBot()
	{
		@$httpUserAgent = $_SERVER['HTTP_USER_AGENT'];
		if (stripos($httpUserAgent, 'http') !== false) {
			return true;
		} else {
			return false;
		}
	}

	/**
	 * 判断是否iOS
	 * @return bool
	 */
	public static function isiOS()
	{
		@$httpUserAgent = $_SERVER['HTTP_USER_AGENT'];
		if (preg_match('/\(i[^;]+;( U;)? CPU.+Mac OS X/i', $httpUserAgent)) {
			return true;
		} else {
			return false;
		}
	}

	/**
	 * 获取百分比
	 * @param float $value 值
	 * @param float $total 总数
	 * @param int $decimals 保留小数
	 * @return float
	 */
	public static function percent(float $value, float $total, int $decimals = 2)
	{
		if ($value == 0 || $total == 0) {
			return 0;
		}
		return (float)number_format($value / $total * 100, $decimals, '.', '');
	}

	/**
	 * 获取表格HTML中的数据
	 * @param string $table 表格HTML
	 * @return array 表格数据
	 */
	public static function getTdArray($table)
	{
		$tdArray = [];
		$table = preg_replace("'<table[^>]*?>'si", '', $table);
		$table = preg_replace("'<tr[^>]*?>'si", '', $table);
		$table = preg_replace("'<td[^>]*?>'si", '', $table);
		$table = str_replace("</tr>", "{tr}", $table);
		$table = str_replace("</td>", "{td}", $table);
		//去掉 HTML 标记
		$table = preg_replace("'<[/!]*?[^<>]*?>'si", '', $table);
		//去掉空白字符
		$table = preg_replace("'([rn])[s]+'", '', $table);
		$table = str_replace(" ", '', $table);
		$table = str_replace(" ", '', $table);
		$table = str_replace("&nbsp;", '', $table);
		$table = str_replace("\t", '', $table);
		$table = str_replace(PHP_EOL, '', $table);
		$tableArray = explode('{tr}', $table);
		array_pop($tableArray);
		foreach ($tableArray as $key => $tr) {
			$td = explode('{td}', $tr);
			array_pop($td);
			$tdArray[] = $td;
		}
		return $tdArray;
	}

	/**
	 * 获取IP地址的主机名称
	 * @param string $ip
	 * @param bool $cache 是否缓存
	 * @return string
	 */
	public static function getHostName($ip, $cache = true)
	{
		if ($cache) {
			if (self::isIP($ip) === false) {
				return 'Unknown';
			}

			$key = __CLASS__ . ':ip_host:' . $ip;
			$hostName = Redis::getInstance()->get($key);
			if (empty($hostName)) {
				$hostName = gethostbyaddr($ip);
				if ($hostName === false || $ip == $hostName) {
					$hostName = 'Unknown';
				}
				Redis::getInstance()->set($key, $hostName);
				Redis::getInstance()->expire($key, 3600);
			}
		} else {
			$hostName = gethostbyaddr($ip);
			if ($hostName === false || $ip == $hostName) {
				$hostName = 'Unknown';
			}
		}
		return $hostName;
	}

	/**
	 * 判断是否合法IP地址
	 * @param string $ip ip地址
	 * @return bool 判断结果
	 */
	public static function isIP($ip)
	{
		if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
			return 'v4';
		} elseif (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {
			return 'v6';
		} else {
			return false;
		}
	}
}
