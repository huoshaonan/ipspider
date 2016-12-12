<?php

class spider{
	
	private $curl = null;
	public $urlArr = array();
	public $params = array();
	public $opts = array();

	public function __construct()
	{
		$this->curl = curl::getIns();
	}

	/**
	*启动
	**/
	public function run()
	{
		if (empty($this->urlArr) || !is_array($this->urlArr)) {
			die('Invalid urlArr!');
		}

		$curl_params = array();

		foreach ($this->urlArr as $key => $url) {
			//组装url
			$curl_params[$key]['url'] = $url;
			//组装params
			if (isset($this->params[$key])) {
				$curl_params[$key]['params'] = $this->params[$key];
			}
			//组装opts
			if (isset($this->opts[$key])) {
				$curl_params[$key]['opts'] = $this->opts[$key];
			}
		}

		$itemCounts = count($curl_params);

		if ($itemCounts > 1) {//并发请求

			$result = $this->curl->multiCurl($curl_params)->body();
			$result = arrayToString($result);
		} elseif ($itemCounts == 1) {//单个请求

			$curl_params = $curl_params['0'];
			//根据params是否为空判断get/post请求
			if (empty($curl_params['params'])) {

				$result = $this->curl->get($curl_params)->body();
			} else {

				$result = $this->curl->post($curl_params)->body();
			}
		} else {

			$result = '';
		}


		return $result;
	}

	/**
	*设置url集合
     *$urlArr string|array
	**/
	public function setUrlArr($urlArr)
	{
		if (!empty($urlArr)) {

		    if (is_array($urlArr)) {

		        $this->urlArr = $urlArr;
            } else {

                $this->urlArr = array($urlArr);
            }
		}
	}

	/**
	*设置参数集合
	**/
	public function setParams($params = array())
	{
		if (!empty($params)) {
			$this->params = $params;
		}
	}

	/**
	*设置header头信息集合
	**/
	public function setOpts($opts)
	{
		if (!empty($opts)) {
			$this->opts = $opts;
		}
	}
}