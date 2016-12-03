<?php
/**
*抓取西刺代理类
*http://bjweb.xicidaili.com/nn/
*http://bjweb.xicidaili.com/nt/
**/
class xiciSpider
{
	//抓取结果集合
	protected $result = array();

	/**
	*抓取
	**/
	public function index($baseurl = null) {
		$spider = new spider();
		if (!$baseurl) {
			$baseurl = 'http://bjweb.xicidaili.com/nn/';
		}

		$succNum = 0;//成功入库数
		$urlArr = array();
		for ($i=0;$i<5;$i++) {

			$urlArr[] = $baseurl.'/'.($i+1);

			//每次并行抓取五页
			if (count($urlArr) >= 5) {
				$spider->setUrlArr($urlArr);
				$headers = array(
					'Upgrade-Insecure-Requests: 1',
					'Referer: http://bjweb.xicidaili.com/nt/',
					'Cookie: _free_proxy_session=BAh7B0kiD3Nlc3Npb25faWQGOgZFVEkiJTBlZjcyYTMyMWJhOWZjNmU5YWI3MGViODM2OWM5YzdjBjsAVEkiEF9jc3JmX3Rva2VuBjsARkkiMTJuOVZ1TUx3L1dIaGpxdlRYMENCUUNHZHFhOFlZZUJOZ25zK3dRMFZqdkU9BjsARg%3D%3D--e732a2e5d0053503e55de69a28148616017e4674; CNZZDATA1256960793=895916990-1479433787-null%7C1479433787',
					'If-None-Match: W/"61b2999f1436c69593b23a7b1a803c1e"'
					);
				$spider->setOpts(array('headers' => $headers));
				$result = $spider->run();

				$result = arrayToString($result);

				$result = $this->preg_parse($result);

				$succNum += ipmodel::getIns()->table('agent_ip')->add($result);
				unset($result);
				$urlArr = array();
				sleep(2);
			}
		}

		echo date('H:i:s',time()).'  抓取完成:'.$succNum.PHP_EOL;
	}

	/**
	*解析字段
	**/
	protected function preg_parse(&$content)
	{
		$patt = '/<tr class=\".*?\">.*?<\/tr>/is';
		preg_match_all($patt, $content, $matches);
		$content = null;

		$result = array();
		if (!empty($matches['0'])) {

			foreach ($matches['0'] as $block) {
				$tmpResult = array();

				//匹配IP
				$ipPatt = '/<td>(\d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3})<\/td>/is';
				if (preg_match($ipPatt,$block,$ipMatch)) {
					$tmpResult['ip'] = $ipMatch['1'];
				} else {
					$tmpResult['ip'] = '';
				}

				//匹配端口
				$portPatt = '/<td>(\d{1,5})<\/td>/is';
				if (preg_match($portPatt,$block,$portMatch)) {
					$tmpResult['port'] = $portMatch['1'];
				} else {
					$tmpResult['port'] = '';
				}

				//匹配地区
				$regionPatt = '/<a.*?>(.*?)<\/a>/is';
				if (preg_match($regionPatt, $block, $regionMatch)) {
					$tmpResult['region'] = $regionMatch['1'];
				} else {
					$tmpResult['region'] = '';
				}

				//匹配类型，HTTP || HTTPS
				$protocolPatt = '/<td>(HTTP|HTTPS)<\/td>/is';
				if (preg_match($protocolPatt, $block, $protocolMatch)) {
					$tmpResult['protocol'] = $protocolMatch['1'];
				} else {
					$tmpResult['protocol'] = '';
				}

				//入库时间
				$tmpResult['time'] = time();

				echo date('H:i:s',time()).' '.json_encode($tmpResult,JSON_UNESCAPED_UNICODE).PHP_EOL;

				if (!empty($tmpResult)) {
					$result[] = $tmpResult;
				}
			}
		}

		return $result;
	}
}