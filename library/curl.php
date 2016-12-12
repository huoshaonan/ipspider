<?php
/**
*爬虫类
*@time 2016年11月11日13:10:57
*@author 霍少楠
**/
class curl{
	
	private static $_ins = null;
	private $result = null;

	private final function __construct(){}

	private function __clone(){}

	public static function getIns()
	{
		if (!self::$_ins) {
			self::$_ins = new self();
		}

		return self::$_ins;
	}

	/**
	*$method string post|get
	*
	*$data array 
	*     Array
	*	     (
	*	         [url] => 			 //string 要抓取的连接地址
	*	         [params] => Array   //array 如果为Post,则为参数，get时留空
	*	             (
	*	             )
	*
	*	         [opts] => Array 	 //array 额外要添加的curlopt_set参数
	*	             (
	*	             )
	*
	*	     )
	**/
	public function __call($method,$data)
	{

		$method_arr = array(
			'get',
			'post'
			);

		if (!in_array($method, $method_arr)) {
			throw new Exception("Error Method Request", 1);
		}

		$ch = $this->get_handle($method,$data['0']);
		$result = curl_exec($ch);
		curl_close($ch);

		$this->result = $result;
		return self::$_ins;
	}

	/**
	*多句柄处理,默认GET方法，当params不为空时自动转为POST
	*$data array()
	**/
	public function multiCurl($data = array())
	{
		if (empty($data)) return false;

		$mh = curl_multi_init();

		//加入句柄
		foreach ($data as $singleData) {
			$ch = $this->get_handle('get',$singleData);
			curl_multi_add_handle($mh,$ch);
		}

		$running = null;
		//执行批处理句柄
		do {
			usleep(5000);
			curl_multi_exec($mh, $running);
		} while ($running > 0);

		$result = array();
		//读取结果
		foreach ($chArr as $ch) {
			$result[] = curl_multi_getcontent($ch);
			//销毁句柄
			curl_multi_remove_handle($mh, $ch);
		}

		//关闭句柄
		curl_multi_close($mh);

		$this->result = $result;
		return self::$_ins;
	}

	/**
	*获取curl常规句柄
	**/
	private function get_handle($method,$data)
	{
		$ch = curl_init();

		curl_setopt($ch, CURLOPT_URL, $data['url']);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_HEADER, 0);

		//连接等待时间,默认3秒
		if (!empty($data['opts']['conntimeout'])) {

			curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $data['opts']['conntimeout']);
		} elseif (!empty($data['opts']['conntimeout_ms'])) {

			curl_setopt($ch, CURLOPT_CONNECTTIMEOUT_MS, $data['opts']['conntimeout_ms']);
		} else {

			curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 3);
		}

		//连接运行时间
		if (!empty($data['opts']['timeout'])) {

			curl_setopt($ch, CURLOPT_TIMEOUT, $data['opts']['timeout']);
		} elseif (!empty($data['opts']['timeout_ms'])) {

			curl_setopt($ch, CURLOPT_TIMEOUT_MS, $data['opts']['timeout_ms']);
		} else {

			curl_setopt($ch, CURLOPT_TIMEOUT, 5);
		}

		//构造post请求参数
		if ($method == 'post' || !empty($data['params'])) {

			curl_setopt($ch, CURLOPT_POST, 1);

			if (!empty($data['params'])) {

				curl_setopt($ch, CURLOPT_POSTFIELDS, $data['params']);
			}
		}

		//构造代理
		if (!empty($data['opts']['userAgent'])) {

			$userAgent = $data['opts']['userAgent'];
		} else {

			$userAgent = 'Mozilla/5.0 (Windows NT 6.1; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/54.0.2840.71 Safari/537.36';
		}
		curl_setopt($ch, CURLOPT_USERAGENT, $userAgent);

		//构造referer
		if (!empty($data['opts']['referer'])) {
			curl_setopt($ch, CURLOPT_REFERER, $data['opts']['referer']);
		}

		//跳过https证书检查
		if (strpos($data['url'], 'https') !== false) {
			curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
		}

		curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
		
		//配置代理服务器
		if (!empty($data['opts']['proxy'])) {

			$proxyInfo = $data['opts']['proxy'];

			curl_setopt($ch, CURLOPT_PROXYAUTH, CURLAUTH_BASIC); //代理认证模式
			curl_setopt($ch, CURLOPT_PROXY, $proxyInfo['ip']); //代理服务器地址
			curl_setopt($ch, CURLOPT_PROXYPORT, $proxyInfo['port']); //代理服务器端口
			if (!empty($proxyInfo['pwd']) && !empty($proxyInfo['user'])) {
				//http代理认证帐号，username:password的格式
				curl_setopt($ch, CURLOPT_PROXYUSERPWD, $proxyInfo['user'].":".$proxyInfo['pwd']); 
			}
			curl_setopt($ch, CURLOPT_PROXYTYPE, CURLPROXY_HTTP); //使用http代理模式
		}

		//配置额外头信息
		if (!empty($data['opts']['headers'])) {
			curl_setopt($ch, CURLOPT_HTTPHEADER, $data['opts']['headers']);
		}

		return $ch;
	}

	public function body()
	{
		return $this->result;
	}
}