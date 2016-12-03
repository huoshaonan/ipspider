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

		$curl_params['data'] = array();

		foreach ($this->urlArr as $key => $url) {
			//组装url
			$curl_params['data'][$key]['url'] = $url;
			//组装params
			if (isset($this->params[$key])) {
				$curl_params['data'][$key]['params'] = $this->params[$key];
			}
			//组装opts
			if (isset($this->opts[$key])) {
				$curl_params['data'][$key]['opts'] = $this->opts[$key];
			}
		}

		//开启并行抓取
		if (count($curl_params['data']) > 1) {
			$curl_params['multi'] = true;
		}

		$result = $this->curl->get($curl_params)->body();

		return $result;
	}
	
	/**
	*设置url集合
	**/
	public function setUrlArr($urlArr = array())
	{
		if (!empty($urlArr)) {
			$this->urlArr = $urlArr;
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