<?php
class myPthreads extends Thread
{
    public $data;
    public $url;
    public $opts;

    /**
     * myPthreads constructor.
     * @param $url string 待抓取的链接
     * @param $opts array curlopt_set参数
     */
	public function __construct($url,$opts)
	{
		$this->url  = $url;
        $this->opts = $opts;
	}

	public function run()
	{
        $spider = new spider();

        $spider->setUrlArr($this->url);

        $spider->setOpts($this->opts);

        $this->data = $spider->run();
	}
}