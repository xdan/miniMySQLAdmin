<?php
class db{
	private $connid = null;
	private $error = '';
	private $last_query = '';
	public $separator = ';';
	function error(){
		return $this->error;
	}
	function affect(){
		return mysql_affected_rows($this->connid);
	}
	function last(){
		return $this->last_query;
	}
	function selectdb( $dbname,$charset="utf8" ){
		if( mysql_select_db($dbname, $this->connid) ){
			return $this->q("SET NAMES '".$charset."'") && $this->q("SET CHARSET '".$charset."'") && $this->q("SET CHARACTER SET '".$charset."'") && $this->q("SET SESSION collation_connection = '{$charset}_general_ci'");
		}else{
			$this->error = mysql_error();
			return false;
		}
	}
	function disconnect(){
		if( $this->connid )
			mysql_close($this->connid);
	}
	function connect($host,$user,$password){
		if( $this->connid = @mysql_connect($host, $user, $password) ){
			return true;
		}
		$this->error = 'No connect';
		return false;
	}
	function q($sql){
		$sqls = explode($this->separator,$sql);
		$this->error = '';
		foreach($sqls as $sql1){
			$inq = mysql_query($sql1,$this->connid);
			if(!$inq)
				$this->error.=mysql_error()."\n";
		}
		$this->last_query = $sql;
		return $inq;
	}
	
	function __($inq){
		return  mysql_fetch_assoc($inq);
	}
	
	function row($sql,$only = false){
		$inq = $this->q($sql);
		if( $inq ){
			$row = $this->__($inq);
			return ($only === false or !isset($row[$only]))?$row:$row[$only];
		}else{
			return false;
		}
	}
	
	function rows($sql,$field=false,$only = false){
		$inq = $this->q($sql);
		if( $inq ){
			$items = array();
			while( $row =  $this->__($inq) ){
				$item = ($only === false or !isset($row[$only]))?$row:$row[$only];
				if( $field === false or !isset($row[$field]) )
					$items[] = $item;
				else
					$items[$field] = $item;
			}
			return $items;
		}else{
			return false;
		}
	}
	
	function _($val){
		return mysql_real_escape_string($val,$this->connid);
	}
	
	private function arrayToSet($values){
		$ret = array();
		if( is_array($values) ){
			foreach($values as $key=>$value){
				$ret[]=is_int($key)?$value:"`$key`='".$this->_($value)."'";
			}
			return implode(',',$ret);
		}else 
			return $values;
	}
	
	private function condition($values){
		$ret = array();
		if( is_array($values) ){
			foreach($values as $key=>$value){
				$ret[]=is_int($key)?$value:"`$key`='".$this->_($value)."'";
			}
			return implode(' and ',$ret);
		}else 
			return $values;
	}
	
	function insert($table,$array){
		$sql = 'insert into '.$table.' set '.$this->arrayToSet($array);
		$inq = $this->q($sql);
		if( $inq )
			return mysql_insert_id();
		else
			return false;
	}
	
	function delete($table,$condition){
		$sql = 'delete from '.$table.' where '.$this->condition($condition);
		return $this->q($sql);
	}
	
	function update($table,$array,$condition){
		$sql = 'update '.$table.' set '.$this->arrayToSet($array).' where '.$this->condition($condition);
		return $this->q($sql);
	}
	function exists($table,$conditions,$id='id',$order = false,$offset = 0){
		$sql = 'select * from '.$table.' where '.$this->condition($condition).' '.($order?' order by '.$order:'').' '.($offset?' offset '.$offset:'');
		return $this->row($sql,$id);
	}
}