<?php
/**
*数据库模型
*@time 2016年11月17日11:05:49
*@author huoshaonan
*
*支持链式操作，例：ipmodel::getIns()->table($tableName)->add($data);
**/
class ipmodel
{

	public static $ins = null;
	protected $pdo = null;
	protected $table = null;
	protected $limit = 0;
	protected $field = '*';
	protected $where = '';
	protected $offset= 0;

	private final function __construct()
	{
		try{
			$this->pdo = new PDO('mysql:host=192.168.71.130;dbname=test','slaveuser1','root');
		} catch (Exception $e) {
			exit('mysql connect error');
		}
		$this->pdo->exec('set names utf8');
	}

	private function __clone(){}

	public static function getIns()
	{
		if (!self::$ins) {
			self::$ins = new self();
		}

		return self::$ins;
	}

	public function table($table)
	{
		if ($table) {

			$this->table = $table;
			return self::$ins;
		} else {

			return false;
		}

	}

	public function add($data = array())
	{
		if (empty($data)) {
			return false;
		}

		$keyAndValues = $this->parse_sql($data);
		$sql = 'insert into '.$this->table.' ('.$keyAndValues['key'].')values('.$keyAndValues['values'].')';

		$result = $this->pdo->exec($sql);

		return $result;
	}

	protected function parse_sql(&$data)
	{
		//字段
		$keys = '';
		//对应的值
		$values = '';

		foreach ($data as $keyData => $valueData) {

			if (!is_array($valueData)) {

				$keys .= '`'.$keyData.'`,';
				$values .= "'".htmlspecialchars($valueData)."',";
			} else {
				$tmpValue = '';
				foreach ($valueData as $key => $value) {

					if ($keyData == 0) {
						$keys .= '`'.$key.'`,';
					}
					$tmpValue .= "'".htmlspecialchars($value)."',";
				}

				$values .= "(".trim($tmpValue,',')."),";
			}
			
		}

		$keys = trim($keys,',');
		$values = trim($values,',');
		$values = trim($values,'(');
		$values = trim($values,')');

		return array('key' => $keys,'values' => $values);
	}

	/**
	 * 指定取数据的数目
	 * @param  string $limit [description]
	 * @return [type]         [description]
	 */
	public function limit($limit = '')
	{
		if (trim($limit) != '') {

			//判断是否传入了offset数值
			if (strpos($limit, ',') !== false) {

				list($offset,$limit) = explode(',', $limit);

				if ($offset>0) {
					$this->offset = $offset;
				}
			}

			if ($limit > 0) {
				$this->limit = $limit;
			}
		}

		return self::$ins;
	}

	/**
	 * 指定返回的字段
	 * @param  array  $field [description]
	 * @return [type]        [description]
	 */
	public function field($field = array())
	{
		if (!empty($field)) {

			if (!is_array($field)) {
				$this->field = $field;
			} else {
				$fields = implode('`,`', $field);
				$this->field = '`'.$fields.'`';
			}
		}

		return self::$ins;
	}

	/**
	 * 查询条件
	 * @param  array|string  $map [description]
	 * @return [type]      [description]
	 */
	public function where($map)
	{
		if (!empty($map)) {

			if (is_array($map)) {

				$where = '';
				foreach ($map as $field => $exp) {
					//$exp第一个元素是比较符，gt(e)大于(等于)，lt(e)小于等于
					if (is_array($exp) and count($exp) == 2) {

						switch ($exp['0']) {
							case 'gt':
								$symbol = '>';
							break;
							case 'gte':
								$symbol = '>=';
							break;
							case 'lt':
								$symbol = '<';
							break;
							case 'lte':
								$symbol = '<=';
							break;
							default:
								$symbol = '=';
							break;
						}
						$value = $exp['1'];
					} else {

						$symbol = '=';
						$value  = $exp;
					}

					$where .= '`'.$field.'`'.$symbol.'"'.$value.'" and ';
				}
				//字符串型的查询条件
				$where = trim($where,' and ');
			} else {

				$where = $map;
			}

			$this->where = $where;
		}

		return self::$ins;
	}

	public function select()
	{
		$sql = 'select '.$this->field.' from '.$this->table.' ';

		if ($this->where != '') {

			$sql .= 'where '.$this->where.' ';
		}

		if ($this->limit > 0) {

			if ($this->offset > 0) {

				$sql .= 'limit '.$this->offset.','.$this->limit;				
			} else {

				$sql .= 'limit '.$this->limit;
			}
		}

		$query_res = $this->pdo->query($sql,PDO::FETCH_ASSOC);

		$result = array();
		if ($query_res) {
			
			foreach ($query_res as $value) {
				$result[] = $value;
			}
		}

		return $result;
	}

	/**更新操作
	 * [update description]
	 * @param  array $data [description]
	 * @return int       [description]
	 */
	public function update($data = array())
	{
		if (!$this->where) {
			die('Where is needed');
		}

		if (empty($data)) {
			die('Data is empty');
		}

		$fields = '';
		foreach ($data as $key => $value) {

			$fields .= '`'.$key.'`="'.$value.'",';
		}
		$fields = trim($fields,',');

		$sql = 'update '.$this->table.' set '.$fields.' where '.$this->where;

		$result = $this->pdo->exec($sql);

		return $result;
	}
}