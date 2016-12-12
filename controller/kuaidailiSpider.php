<?php
/**
*抓取快代理
*http://www.kuaidaili.com/proxylist/1
*http://www.kuaidaili.com/free/inha/1/
**/
class kuaidailiSpider
{
	//抓取结果集合
	protected $result = array();

	/**
	*抓取
	**/
	public function index($baseurl = null) {
		$spider = new spider();

		$baseurlArr = array(
			array(
				'url' => 'http://www.kuaidaili.com/proxylist',
				'num' => 10
				),
			array(
				'url' => 'http://www.kuaidaili.com/free/inha',
				'num' => 500
				),
			array(
				'url' => 'http://www.kuaidaili.com/free/intr',
				'num' => 500
				)
		);
		foreach ($baseurlArr as $arr) {
			$baseurl = $arr['url'];
			$total_num = $arr['num'];

            $thread_array = array();
			for ($i=0;$i<$total_num;$i++) {
				
				$headers = array(
					'Upgrade-Insecure-Requests: 1',
					'Referer: http://www.kuaidaili.com/free/inha/1/',
					'Cookie: _gat=1; channelid=0; sid=1479540480086332; _ga=GA1.2.1601349455.1479541706; Hm_lvt_7ed65b1cc4b810e9fd37959c9bb51b31=1479541706; Hm_lpvt_7ed65b1cc4b810e9fd37959c9bb51b31=1479542324',
					);
                $thread_array[$i] = new myPthreads($baseurl.'/'.($i+1),array('headers' => $headers));
                $thread_array[$i]->start();
				usleep(1);
			}

			foreach ($thread_array as $key => $thread) {

			    while ($thread_array[$key]->isRunning()) {
			        usleep(10);
                }

                if ($thread_array[$key]->join()) {
                    $result = $thread_array[$key]->data;
                    $result = $this->preg_parse($result);
                    $succNum = ipmodel::getIns()->table('agent_ip')->add($result);
                    unset($result);
                    echo date('H:i:s',time()).'  抓取完成:'.$succNum.PHP_EOL;
                }
            }

        }
	}

	/**
	*解析字段
	**/
	protected function preg_parse(&$content)
	{
		$each_patt = '/<tr>.*?<\/tr>/is';
		preg_match_all($each_patt, $content, $matches);
		$content = null;

		$result = array();
		if (!empty($matches['0'])) {

			foreach ($matches['0'] as $block) {
				$tmpResult = array();

				//匹配IP
				$ipPatt = '/<td data\-title=\"IP\">(.*?)<\/td>/is';
				if (preg_match($ipPatt,$block,$ipMatch)) {
					$tmpResult['ip'] = $ipMatch['1'];
				} else {
					continue;
				}

				//匹配端口
				$portPatt = '/<td data\-title=\"PORT\">(\d{1,5})<\/td>/is';
				if (preg_match($portPatt,$block,$portMatch)) {
					$tmpResult['port'] = $portMatch['1'];
				} else {
					$tmpResult['port'] = '';
				}

				//匹配地区
				$regionPatt = '/<td data\-title=\"位置\">(.*?)<\/td>/is';
				if (preg_match($regionPatt, $block, $regionMatch)) {
					$tmpResult['region'] = $regionMatch['1'];
				} else {
					$tmpResult['region'] = '';
				}

				//匹配类型，HTTP || HTTPS
				$protocolPatt = '/<td data\-title=\"类型\">(.*?)<\/td>/is';
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