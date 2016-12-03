<?php
class testIp
{
	protected $api = 'http://1212.ip138.com/ic.asp';

	public function index()
	{
		header('Content-type:text/html;charset=utf-8;');
		$agentList = $this->getIps();
		
		$matchRes = array();
		foreach ($agentList as $agent) {

			$data  = array(
				'url' => $this->api,
				'opts'=> array(
					'proxy' => array(
						'ip'   => $agent['ip'],
						'port' => $agent['port']
						),
					)
				);
			$ipRes = curl::getIns()->get($data)->body();
			$patt = '/<center>(.*?)<\/center>/';
			if (preg_match($patt,$ipRes,$match)) {

				$matchRes[] = array(
					mb_convert_encoding($match['1'], 'UTF-8','GBK'),
					$agent['ip'].':'.$agent['port']
					);
			}
		}
		echo '<pre>';print_r($matchRes);
	}

	public function getIps()
	{
		$map    = '`succ_times` = 0';
		$field  = 'id,ip,port'; 
		$agentList = ipmodel::getIns()->table('agent_ip')->where($map)->field($field)->limit(10)->select();
		
		return $agentList;	
	}
}