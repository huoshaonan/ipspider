<?php
/**
 * 抓取有代理(http://www.youdaili.net/)网站的ip
 * @time 2016年11月19日20:57:09
 * @author huoshaonan <[huoshaonan@qq.com]>
 */
class youdailiSpider
{
	//抓取结果集合
	protected $result = array();
	protected $curl;

	/**
	*抓取
	**/
	public function index($baseurl = null) {
		$spider = new spider();
		$baseurlArr = ipmodel::getIns()->table('youdaili')->field('url,num')->select();

		foreach ($baseurlArr as $arr) {
			$baseurl = $arr['url'];
			$total_num = $arr['num'];

			$succNum = 0;//成功入库数
			$urlArr = array();
			for ($i=0;$i<$total_num;$i++) {

				if ($i > 0){
					$page = $i+1;
					$url = str_replace('.html', '_'.$page, $baseurl).'.html';
				} else {
					$url = $baseurl;
				}

				$urlArr[] = $url;
				//每次并行抓取五页
				if (count($urlArr) >= $total_num) {
					$spider->setUrlArr($urlArr);
					$headers = array(
						'Upgrade-Insecure-Requests: 1',
						'Cookie: Hm_lvt_f8bdd88d72441a9ad0f8c82db3113a84=1479557994,1480240791; Hm_lpvt_f8bdd88d72441a9ad0f8c82db3113a84=1480242952',
						);
					$spider->setOpts(array('headers' => $headers));
					$result = $spider->run();

					$result = $this->preg_parse($result);

					$succNum += ipmodel::getIns()->table('agent_ip')->add($result);
					unset($result);
					$urlArr = array();
				}
			}
			echo date('H:i:s',time()).'  抓取完成:'.$succNum.PHP_EOL;
			sleep(2);
		}
	}

	/**
	*解析字段
	**/
	protected function preg_parse(&$content)
	{
		$each_patt = '/<p>\d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3}:\d{1,5}@.*?#.*?(<\/p>|<br \/>)/is';
		preg_match_all($each_patt, $content, $matches);
		$content = null;

		$result = array();
		if (!empty($matches['0'])) {

			foreach ($matches['0'] as $block) {
				$tmpResult = array();

				//匹配IP
				$ipPatt = '/(\d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3})/is';
				if (preg_match($ipPatt,$block,$ipMatch)) {
					$tmpResult['ip'] = $ipMatch['1'];
				} else {
					continue;
				}

				//匹配端口
				$portPatt = '/:(\d{1,5})@/is';
				if (preg_match($portPatt,$block,$portMatch)) {
					$tmpResult['port'] = $portMatch['1'];
				} else {
					$tmpResult['port'] = '';
				}

				//匹配地区
				$regionPatt = '/#(.*?)<\/p>/is';
				if (preg_match($regionPatt, $block, $regionMatch)) {
					$tmpResult['region'] = $regionMatch['1'];
				} else {
					$tmpResult['region'] = '';
				}

				//匹配类型，HTTP || HTTPS
				$protocolPatt = '/@(.*?)#/is';
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

	/**
	 * 抓取每日列表基本url及每日页数
	 * @return [type] [description]
	 */
	public function getListUrl()
	{
		//http://www.youdaili.net/Daili/guonei/list_1.html
		$baseurl = 'http://www.youdaili.net/Daili/guonei';

		$this->curl = curl::getIns();

		for ($i=12;$i<=500;$i++) {

			$url = $baseurl.'/list_'.$i.'.html';
			$data = array(
				'url' => $url
				);
			$content = $this->curl->get($data)->body();
			$urlInfo = $this->getUrlInfo($content);
			unset($content);
			$res = ipmodel::getIns()->table('youdaili')->add($urlInfo);
			unset($urlInfo);
			echo date('H:i:s',time()).' 第'.$i.'页 共抓取：'.$res.PHP_EOL;
			sleep(2);
		}
		echo date('H:i:s',time()).' 抓取完毕';
	}

	/**
	 * 根据网页源码获取url列表
	 */
	protected function getUrlInfo(&$content)
	{
		$patt = '/<div class=\"chunlist\">.*?<\/div>/is';
		$result = array();
		if (preg_match($patt,$content,$matche)) {

			$div = $matche['0'];
			if ($div) {

				$each_patt = '/href=\"(.*?)\"/is';
				preg_match_all($each_patt, $div, $matches);
				foreach ($matches['1'] as $url) {
					$tmpResult['url'] = $url;
					$data = array(
						'url' => $url
						);
					$urlRes = $this->curl->get($data)->body();
					//匹配页数
					$page_patt = '/<div class=\"pagebreak\">.*共(\d*)页.*?<\/div>/is';
					preg_match($page_patt,$urlRes,$urlMatch);
					if (!empty($urlMatch['1'])) {
						$tmpResult['num'] = $urlMatch['1'];
					} else {
						$tmpResult['num'] = 1;
					}

					$result[] = $tmpResult;
				}
			}
		}
		
		return $result;
	}
}