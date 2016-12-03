<?php
/**
*公共函数库
*@time 2016年11月17日14:12:53
*@author huoshaonan
**/

if (!function_exists('arrayToString')) {
	/**
	*数组拼接成字符串
	**/
	function arrayToString(&$array)
	{
		$string = '';
		
		foreach ($array as $key => $value) {

			if (is_array($value)) {
				$value = arrayToString($value);
			}

			$string .= $value;
		}

		return $string;
	}
}