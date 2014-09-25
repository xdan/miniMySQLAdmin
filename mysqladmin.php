<?php
session_start();

$config = array(
	'cookie'=>'key_man',
	'max_str_len'=>'100',
	'count_on_page'=>'20',
	'ssd_deob'=>'hjkhjssre4',
	//'charset'=>'utf-8',
	//'host'=>'localhost',
	//'username'=>'root',
	//'password'=>'parol',
);


class db{
	private $connid = null;
	private $error = '';
	private $last_query = '';
	public $separator = ';';
	public $charset = false;
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
		if( $this->charset )
			 $charset = $this->charset;
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
	function connect($host,$user,$password,$charset=false){
		if( $charset )
			 $this->charset = $charset;
		if( $this->connid = @mysql_connect($host, $user, $password) ){
			return true;
		}
		$this->error = 'No connect';
		return false;
	}
	function q($sql){
		$sqls = explode($this->separator,$sql);
		$this->error = '';
		$inq = false;
		foreach($sqls as $query){
			if( trim($query) ){
				$inq = mysql_query($query,$this->connid);
				if(!$inq)
					$this->error.=mysql_error()."\n";
			}
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
			return ( $only === false )?$row: isset($row[$only])?$row[$only]:false;
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
			return mysql_insert_id($this->connid);
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


function ssd_deob($String, $Password='dgfdfg234'){
    $String = $String.'';
	$Salt='GKJ76DL2dsgDP'; 
    $StrLen = strlen($String);
    $Seq = $Password;
    $Gamma = '';
    while (strlen($Gamma)<$StrLen){
        $Seq = pack("H*",sha1($Gamma.$Seq.$Salt)); 
        $Gamma.=substr($Seq,0,8);
    }
    return $String^$Gamma;
}

function stripslashesall(&$array) {
    reset($array);
    while (list($key, $val) = each($array)) {
        if (is_string($val)) {
        	$array[$key] = stripslashes($val);
        } elseif (is_array($val)) {
        	$array[$key] = stripslashesall($val);
        }
    }
    return $array;
}

function _trim($value){
	return preg_replace(array('#^[\n\r\t\s]+#u','#[\n\r\t\s]+$#u'),'',$value);
}
function getPHPCharset( $value,$charset="utf-8" ){
	$ch = mb_detect_encoding($value);
	return $ch?$ch:$charset;
}
function _encode($host,$user,$password,$charcode){
	global $config; 
	return base64_encode(ssd_deob($host.'&|&'.$user.'&|&'.$password.'&|&'.$charcode,$config['ssd_deob']));
}
function _decode($val){
	global $config; 
	return explode('&|&',ssd_deob(base64_decode($val),$config['ssd_deob']));
}
function __($sql){
	return addslashes($sql);
}

function analize($sql,$value,$key,$primary,$table){
	global $config;
	if( preg_match('#^show#iu',_trim($sql)) ){
		if(preg_match('#databases#iu',_trim($sql))){
			return '<a href="?dbname='.$value.'"><i class="icon icon_db"></i> '.$value.'</a>';
		}elseif(preg_match('#tables#iu',_trim($sql))){
			return '<a class="col-sm-4" href="?table='.$value.'&sql='.__('select * from `'.$value.'`').'"><i class="icon icon_table"></i> '.$value.'</a>  <a class="col-sm-1" href="?table='.$value.'&sql='.__('SHOW COLUMNS FROM `'.$value.'`').'">structure</a>  <a href="?table='.$value.'&sql='.__('SHOW CREATE TABLE `'.$value.'`').'">SQL</a>';
		}elseif( preg_match('#CREATE[\s\t\n]+TABLE#iu',_trim($sql)) ){
			return htmlspecialchars(_trim($value));
		}
		return $value;
	}elseif( preg_match('#^select#iu',_trim($sql)) ){
		return ($table&&$primary?'<input type="checkbox" name="value" value="'.$value.'"> <a style="" onclick="return confirm(\'Are you shure?\')" href="?table='.$table.'&action=delete&key='.__($key).'&value='.__($value).'"><i class="icon icon_delete"></i></a> <a href="?table='.$table.'&action=edit&key='.$key.'&value='.$value.'"><i class="icon icon_edit"></i></a> ':'').htmlspecialchars(mb_substr($value,0,$config['max_str_len'],getPHPCharset($value)));
	}
	return $value;
}
function analize_header($sql,$value,$primary,$table){
	if( is_select($sql) ){
		return ($primary&&$table?'<input onclick="toggleAll.call();" type="checkbox" name="header_value" value="'.__($value).'"> ':'').'<a href="?sql='.rawurlencode(add_to_sql($sql,'order',$value)).'">'.htmlspecialchars($value).'</a>';
	}
	return $value;
}
function is_select($sql){
	return preg_match('#^[\(]?select#iu',_trim($sql));
}
function is_limited($sql){
	return preg_match('#[\s\n\r\t]*limit[\s\n\r\t]+#ui',$sql);
}
function is_ordered($sql){
	return preg_match('#[\s\n\r\t]*order[\s\n\r\t]+by[\s\n\r\t]+([^\s\n\r\t]+)([\s\n\r\t]+|$)#ui',$sql,$list)?preg_replace('#[\'"\s\n\t`]#','',$list[1]):false;
}
function is_enum($inq, $i){
	return strpos(mysql_field_flags($inq, $i), 'enum') !== false;
}

function get_enum_values( $table, $field ){
   global $db;
   $type = $db->row( "SHOW COLUMNS FROM `{$table}` WHERE Field = '{$field}'" ,'Type');
    preg_match('/^enum\((.*)\)$/', $type, $matches);
    foreach( explode(',', $matches[1]) as $value ){
         $enum[] = trim( $value, "'" );
    }
    return $enum;
}

function is_primary($inq, $i){
	return strpos(mysql_field_flags($inq, $i), 'primary_key') !== false;
}
function add_to_sql($sql,$name,$value){
	switch($name){
		case 'limit':
			if( is_limited($sql) ){
				$sql = preg_replace('#[\s\t\n\r]*limit[\s\t\n\r]+[0-9\s]+#ui',' limit `'.$value.'` ',$sql);
			}else{
				$sql.=' limit '.$value.' ';
			}
		break;
		case 'order':
			if( $orderby = is_ordered($sql) ){
				$sql = preg_replace('#[\s\n\r\t]*order[\s\n\r\t]+by[\s\n\r\t]+[^\s\n\r\t;]+[\s\n\r\t]*#ui',' order by `'.$value.'` ',$sql);
				if( $value==$orderby ){
					if( preg_match('#[\s\n\r\t`]+order[\s\n\r\t`]+by[\s\n\r\t`]+`'.$value.'`[\s\n\r\t`]+(desc|asc)#ui',$sql,$list) ){
						$sql = preg_replace('#( order by `'.$value.'`[\s\n\r\t`]+)(desc|asc)#ui','$1'.(mb_strtolower($list[1])=='desc'?'asc':'desc'),$sql);
					}else
						$sql = preg_replace('#( order by `'.$value.'`[\s\n\r\t`]+)#ui','$1desc ',$sql);
				}
			}else{
				if( is_limited($sql) ){
					$sql=preg_replace('#[\s\n\r\t]*limit[\s\n\r\t]+#ui',' order by `'.$value.'` asc  limit ',$sql);
				}else{
					$sql.='order by '.$value.' ';
				}
			}
		break;
	}
	return $sql;
}

function base64_encode_image ($filename,$filetype) {
    if ($filename) {
        $imgbinary = fread(fopen($filename, "r"), filesize($filename));
        return 'data:image/' . $filetype . ';base64,' . base64_encode($imgbinary);
    }
}

function variant_connect_finded(){
	$options = array();
	if( file_exists('configuration.php') ){
		$options[] = 'Joomla';
	}
	if( file_exists('protected/config/main.php') ){
		$options[] = 'Yii';
	}
	if( file_exists('base/danneo.setting.php') ){
		$options[] = 'Danneo';
	}
	if( file_exists('application/config.php') ){
		$options[] = 'DatingScript';
	}
	$out = '';
	foreach($options as $option){
		$out.='<option value="'.mb_strtolower($option).'">'.$option.'</option>';
	}
	return $out;
}
function get_input($inq,$table,$type,$key,$value){
	switch( $type ){
		case 'real':
		case 'int':
			return '<input type="number" class="form-control" name="key['.__($key).']" id="key_'.__($key).'" placeholder="'.__($key).'" value="'.__($value).'">';
		break;
		case 'string':
			if( is_enum($inq,$i) ){
				$enum_variants = get_enum_values($table,$key);
				$out = '<select class="form-control" name="key['.__($key).']" id="key_'.__($key).'">';
				foreach($enum_variants as $name){
					$out.='<option '.($value==$name?'selected':'').' value="'.$name.'">'.$name.'</option>';
				}
				$out.='</select>';
			}else{
				$out='<input type="text" class="form-control" name="key['.__($key).']" id="key_'.__($key).'" placeholder="'.__($key).'" value="'.__($value).'">';
			}
			return $out;
		break;
		case 'timestamp':
		case 'year':
		case 'date':
		case 'time':
		case 'datetime':
			return '<input type="text" class="form-control" name="key['.$key.']" id="key_'.$key.'" placeholder="'.$key.'" value="'.addslashes($value).'">';
		break;
		default:
			return '<textarea class="form-control" name="key['.$key.']" id="key_'.$key.'" placeholder="'.$key.'">'.htmlspecialchars($value).'</textarea>';
		break;
	}
}
function try_connect_through($system){
	global $db;
	switch($system){
		case 'joomla':
			if( file_exists('configuration.php') ){
				$data = file_get_contents('configuration.php');
				if( 
					preg_match('#host[\s]*=[\s]*(\'|")([^\'"]+)(\'|")#Uusi',$data,$host) and
					preg_match('#user[\s]*=[\s]*(\'|")([^\'"]+)(\'|")#Uusi',$data,$user) and
					preg_match('#password[\s]*=[\s]*(\'|")([^\'"]+)(\'|")#Uusi',$data,$password)
				){
					if($db->connect($host[2],$user[2],$password[2])){
						return array('host'=>$host[2],'username'=>$user[2],'password'=>$password[2]);
					}else 
						return false;
				}else 
					return false;
			}else 
				return false;
		break;
		case 'yii':
			if( file_exists('protected/config/main.php') ){
				$data = file_get_contents('protected/config/main.php');
			
				if( 
					preg_match('#connectionString.*mysql:host=(.*);#Uusi',$data,$host) and
					preg_match('#username(\'|")[\s]*=>[\s]*(\'|")([^\'"]+)(\'|")#Uusi',$data,$user) and
					preg_match('#password(\'|")[\s]*=>[\s]*(\'|")([^\'"]+)(\'|")#Uusi',$data,$password)
				){
					if($db->connect($host[1],$user[3],$password[3])){
						return array('host'=>$host[1],'username'=>$user[3],'password'=>$password[3]);
					}else 
						return false;
				}else 
					return false;
			}else 
				return false;
		break;
	}
	return false;
}
function ekran($value){
	global $db;
	return '\''.$db->_($value).'\'';
}

function get_primary_field( $table ){
	global $db;
	return $db->row("SHOW KEYS FROM `".$db->_($table)."` WHERE Key_name = 'PRIMARY'",'Column_name');
}


/**
 * SQL Formatter is a collection of utilities for debugging SQL queries.
 * It includes methods for formatting, syntax highlighting, removing comments, etc.
 *
 * @package    SqlFormatter
 * @author     Jeremy Dorn <jeremy@jeremydorn.com>
 * @author     Florin Patan <florinpatan@gmail.com>
 * @copyright  2013 Jeremy Dorn
 * @license    http://opensource.org/licenses/MIT
 * @link       http://github.com/jdorn/sql-formatter
 * @version    1.2.18
 */
class SqlFormatter
{
    // Constants for token types
    const TOKEN_TYPE_WHITESPACE = 0;
    const TOKEN_TYPE_WORD = 1;
    const TOKEN_TYPE_QUOTE = 2;
    const TOKEN_TYPE_BACKTICK_QUOTE = 3;
    const TOKEN_TYPE_RESERVED = 4;
    const TOKEN_TYPE_RESERVED_TOPLEVEL = 5;
    const TOKEN_TYPE_RESERVED_NEWLINE = 6;
    const TOKEN_TYPE_BOUNDARY = 7;
    const TOKEN_TYPE_COMMENT = 8;
    const TOKEN_TYPE_BLOCK_COMMENT = 9;
    const TOKEN_TYPE_NUMBER = 10;
    const TOKEN_TYPE_ERROR = 11;
    const TOKEN_TYPE_VARIABLE = 12;

    // Constants for different components of a token
    const TOKEN_TYPE = 0;
    const TOKEN_VALUE = 1;

    // Reserved words (for syntax highlighting)
    protected static $reserved = array(
        'ACCESSIBLE', 'ACTION', 'AGAINST', 'AGGREGATE', 'ALGORITHM', 'ALL', 'ALTER', 'ANALYSE', 'ANALYZE', 'AS', 'ASC',
        'AUTOCOMMIT', 'AUTO_INCREMENT', 'BACKUP', 'BEGIN', 'BETWEEN', 'BINLOG', 'BOTH', 'CASCADE', 'CASE', 'CHANGE', 'CHANGED', 'CHARACTER SET',
        'CHARSET', 'CHECK', 'CHECKSUM', 'COLLATE', 'COLLATION', 'COLUMN', 'COLUMNS', 'COMMENT', 'COMMIT', 'COMMITTED', 'COMPRESSED', 'CONCURRENT',
        'CONSTRAINT', 'CONTAINS', 'CONVERT', 'CREATE', 'CROSS', 'CURRENT_TIMESTAMP', 'DATABASE', 'DATABASES', 'DAY', 'DAY_HOUR', 'DAY_MINUTE',
        'DAY_SECOND', 'DEFAULT', 'DEFINER', 'DELAYED', 'DELETE', 'DESC', 'DESCRIBE', 'DETERMINISTIC', 'DISTINCT', 'DISTINCTROW', 'DIV',
        'DO', 'DUMPFILE', 'DUPLICATE', 'DYNAMIC', 'ELSE', 'ENCLOSED', 'END', 'ENGINE', 'ENGINE_TYPE', 'ENGINES', 'ESCAPE', 'ESCAPED', 'EVENTS', 'EXEC', 
        'EXECUTE', 'EXISTS', 'EXPLAIN', 'EXTENDED', 'FAST', 'FIELDS', 'FILE', 'FIRST', 'FIXED', 'FLUSH', 'FOR', 'FORCE', 'FOREIGN', 'FULL', 'FULLTEXT',
        'FUNCTION', 'GLOBAL', 'GRANT', 'GRANTS', 'GROUP_CONCAT', 'HEAP', 'HIGH_PRIORITY', 'HOSTS', 'HOUR', 'HOUR_MINUTE',
        'HOUR_SECOND', 'IDENTIFIED', 'IF', 'IFNULL', 'IGNORE', 'IN', 'INDEX', 'INDEXES', 'INFILE', 'INSERT', 'INSERT_ID', 'INSERT_METHOD', 'INTERVAL',
        'INTO', 'INVOKER', 'IS', 'ISOLATION', 'KEY', 'KEYS', 'KILL', 'LAST_INSERT_ID', 'LEADING', 'LEVEL', 'LIKE', 'LINEAR',
        'LINES', 'LOAD', 'LOCAL', 'LOCK', 'LOCKS', 'LOGS', 'LOW_PRIORITY', 'MARIA', 'MASTER', 'MASTER_CONNECT_RETRY', 'MASTER_HOST', 'MASTER_LOG_FILE',
        'MATCH','MAX_CONNECTIONS_PER_HOUR', 'MAX_QUERIES_PER_HOUR', 'MAX_ROWS', 'MAX_UPDATES_PER_HOUR', 'MAX_USER_CONNECTIONS',
        'MEDIUM', 'MERGE', 'MINUTE', 'MINUTE_SECOND', 'MIN_ROWS', 'MODE', 'MODIFY',
        'MONTH', 'MRG_MYISAM', 'MYISAM', 'NAMES', 'NATURAL', 'NOT', 'NOW()','NULL', 'OFFSET', 'ON', 'OPEN', 'OPTIMIZE', 'OPTION', 'OPTIONALLY',
        'ON UPDATE', 'ON DELETE', 'OUTFILE', 'PACK_KEYS', 'PAGE', 'PARTIAL', 'PARTITION', 'PARTITIONS', 'PASSWORD', 'PRIMARY', 'PRIVILEGES', 'PROCEDURE',
        'PROCESS', 'PROCESSLIST', 'PURGE', 'QUICK', 'RANGE', 'RAID0', 'RAID_CHUNKS', 'RAID_CHUNKSIZE','RAID_TYPE', 'READ', 'READ_ONLY',
        'READ_WRITE', 'REFERENCES', 'REGEXP', 'RELOAD', 'RENAME', 'REPAIR', 'REPEATABLE', 'REPLACE', 'REPLICATION', 'RESET', 'RESTORE', 'RESTRICT',
        'RETURN', 'RETURNS', 'REVOKE', 'RLIKE', 'ROLLBACK', 'ROW', 'ROWS', 'ROW_FORMAT', 'SECOND', 'SECURITY', 'SEPARATOR',
        'SERIALIZABLE', 'SESSION', 'SHARE', 'SHOW', 'SHUTDOWN', 'SLAVE', 'SONAME', 'SOUNDS', 'SQL',  'SQL_AUTO_IS_NULL', 'SQL_BIG_RESULT',
        'SQL_BIG_SELECTS', 'SQL_BIG_TABLES', 'SQL_BUFFER_RESULT', 'SQL_CALC_FOUND_ROWS', 'SQL_LOG_BIN', 'SQL_LOG_OFF', 'SQL_LOG_UPDATE',
        'SQL_LOW_PRIORITY_UPDATES', 'SQL_MAX_JOIN_SIZE', 'SQL_QUOTE_SHOW_CREATE', 'SQL_SAFE_UPDATES', 'SQL_SELECT_LIMIT', 'SQL_SLAVE_SKIP_COUNTER',
        'SQL_SMALL_RESULT', 'SQL_WARNINGS', 'SQL_CACHE', 'SQL_NO_CACHE', 'START', 'STARTING', 'STATUS', 'STOP', 'STORAGE',
        'STRAIGHT_JOIN', 'STRING', 'STRIPED', 'SUPER', 'TABLE', 'TABLES', 'TEMPORARY', 'TERMINATED', 'THEN', 'TO', 'TRAILING', 'TRANSACTIONAL', 'TRUE',
        'TRUNCATE', 'TYPE', 'TYPES', 'UNCOMMITTED', 'UNIQUE', 'UNLOCK', 'UNSIGNED', 'USAGE', 'USE', 'USING', 'VARIABLES',
        'VIEW', 'WHEN', 'WITH', 'WORK', 'WRITE', 'YEAR_MONTH'
    );

    // For SQL formatting
    // These keywords will all be on their own line
    protected static $reserved_toplevel = array(
        'SELECT', 'FROM', 'WHERE', 'SET', 'ORDER BY', 'GROUP BY', 'LIMIT', 'DROP',
        'VALUES', 'UPDATE', 'HAVING', 'ADD', 'AFTER', 'ALTER TABLE', 'DELETE FROM', 'UNION ALL', 'UNION', 'EXCEPT', 'INTERSECT'
    );

    protected static $reserved_newline = array(
        'LEFT OUTER JOIN', 'RIGHT OUTER JOIN', 'LEFT JOIN', 'RIGHT JOIN', 'OUTER JOIN', 'INNER JOIN', 'JOIN', 'XOR', 'OR', 'AND'
    );

    protected static $functions = array (
        'ABS', 'ACOS', 'ADDDATE', 'ADDTIME', 'AES_DECRYPT', 'AES_ENCRYPT', 'AREA', 'ASBINARY', 'ASCII', 'ASIN', 'ASTEXT', 'ATAN', 'ATAN2',
        'AVG', 'BDMPOLYFROMTEXT',  'BDMPOLYFROMWKB', 'BDPOLYFROMTEXT', 'BDPOLYFROMWKB', 'BENCHMARK', 'BIN', 'BIT_AND', 'BIT_COUNT', 'BIT_LENGTH',
        'BIT_OR', 'BIT_XOR', 'BOUNDARY',  'BUFFER',  'CAST', 'CEIL', 'CEILING', 'CENTROID',  'CHAR', 'CHARACTER_LENGTH', 'CHARSET', 'CHAR_LENGTH',
        'COALESCE', 'COERCIBILITY', 'COLLATION',  'COMPRESS', 'CONCAT', 'CONCAT_WS', 'CONNECTION_ID', 'CONTAINS', 'CONV', 'CONVERT', 'CONVERT_TZ',
        'CONVEXHULL',  'COS', 'COT', 'COUNT', 'CRC32', 'CROSSES', 'CURDATE', 'CURRENT_DATE', 'CURRENT_TIME', 'CURRENT_TIMESTAMP', 'CURRENT_USER',
        'CURTIME', 'DATABASE', 'DATE', 'DATEDIFF', 'DATE_ADD', 'DATE_DIFF', 'DATE_FORMAT', 'DATE_SUB', 'DAY', 'DAYNAME', 'DAYOFMONTH', 'DAYOFWEEK',
        'DAYOFYEAR', 'DECODE', 'DEFAULT', 'DEGREES', 'DES_DECRYPT', 'DES_ENCRYPT', 'DIFFERENCE', 'DIMENSION', 'DISJOINT', 'DISTANCE', 'ELT', 'ENCODE',
        'ENCRYPT', 'ENDPOINT', 'ENVELOPE', 'EQUALS', 'EXP', 'EXPORT_SET', 'EXTERIORRING', 'EXTRACT', 'EXTRACTVALUE', 'FIELD', 'FIND_IN_SET', 'FLOOR',
        'FORMAT', 'FOUND_ROWS', 'FROM_DAYS', 'FROM_UNIXTIME', 'GEOMCOLLFROMTEXT', 'GEOMCOLLFROMWKB', 'GEOMETRYCOLLECTION', 'GEOMETRYCOLLECTIONFROMTEXT',
        'GEOMETRYCOLLECTIONFROMWKB', 'GEOMETRYFROMTEXT', 'GEOMETRYFROMWKB', 'GEOMETRYN', 'GEOMETRYTYPE', 'GEOMFROMTEXT', 'GEOMFROMWKB', 'GET_FORMAT',
        'GET_LOCK', 'GLENGTH', 'GREATEST', 'GROUP_CONCAT', 'GROUP_UNIQUE_USERS', 'HEX', 'HOUR', 'IF', 'IFNULL', 'INET_ATON', 'INET_NTOA', 'INSERT', 'INSTR',
        'INTERIORRINGN', 'INTERSECTION', 'INTERSECTS',  'INTERVAL', 'ISCLOSED', 'ISEMPTY', 'ISNULL', 'ISRING', 'ISSIMPLE', 'IS_FREE_LOCK', 'IS_USED_LOCK',
        'LAST_DAY', 'LAST_INSERT_ID', 'LCASE', 'LEAST', 'LEFT', 'LENGTH', 'LINEFROMTEXT', 'LINEFROMWKB', 'LINESTRING', 'LINESTRINGFROMTEXT', 'LINESTRINGFROMWKB',
        'LN', 'LOAD_FILE', 'LOCALTIME', 'LOCALTIMESTAMP', 'LOCATE', 'LOG', 'LOG10', 'LOG2', 'LOWER', 'LPAD', 'LTRIM', 'MAKEDATE', 'MAKETIME', 'MAKE_SET',
        'MASTER_POS_WAIT', 'MAX', 'MBRCONTAINS', 'MBRDISJOINT', 'MBREQUAL', 'MBRINTERSECTS', 'MBROVERLAPS', 'MBRTOUCHES', 'MBRWITHIN', 'MD5', 'MICROSECOND',
        'MID', 'MIN', 'MINUTE', 'MLINEFROMTEXT', 'MLINEFROMWKB', 'MOD', 'MONTH', 'MONTHNAME', 'MPOINTFROMTEXT', 'MPOINTFROMWKB', 'MPOLYFROMTEXT', 'MPOLYFROMWKB',
        'MULTILINESTRING', 'MULTILINESTRINGFROMTEXT', 'MULTILINESTRINGFROMWKB', 'MULTIPOINT',  'MULTIPOINTFROMTEXT', 'MULTIPOINTFROMWKB', 'MULTIPOLYGON',
        'MULTIPOLYGONFROMTEXT', 'MULTIPOLYGONFROMWKB', 'NAME_CONST', 'NULLIF', 'NUMGEOMETRIES', 'NUMINTERIORRINGS',  'NUMPOINTS', 'OCT', 'OCTET_LENGTH',
        'OLD_PASSWORD', 'ORD', 'OVERLAPS', 'PASSWORD', 'PERIOD_ADD', 'PERIOD_DIFF', 'PI', 'POINT', 'POINTFROMTEXT', 'POINTFROMWKB', 'POINTN', 'POINTONSURFACE',
        'POLYFROMTEXT', 'POLYFROMWKB', 'POLYGON', 'POLYGONFROMTEXT', 'POLYGONFROMWKB', 'POSITION', 'POW', 'POWER', 'QUARTER', 'QUOTE', 'RADIANS', 'RAND',
        'RELATED', 'RELEASE_LOCK', 'REPEAT', 'REPLACE', 'REVERSE', 'RIGHT', 'ROUND', 'ROW_COUNT', 'RPAD', 'RTRIM', 'SCHEMA', 'SECOND', 'SEC_TO_TIME',
        'SESSION_USER', 'SHA', 'SHA1', 'SIGN', 'SIN', 'SLEEP', 'SOUNDEX', 'SPACE', 'SQRT', 'SRID', 'STARTPOINT', 'STD', 'STDDEV', 'STDDEV_POP', 'STDDEV_SAMP',
        'STRCMP', 'STR_TO_DATE', 'SUBDATE', 'SUBSTR', 'SUBSTRING', 'SUBSTRING_INDEX', 'SUBTIME', 'SUM', 'SYMDIFFERENCE', 'SYSDATE', 'SYSTEM_USER', 'TAN',
        'TIME', 'TIMEDIFF', 'TIMESTAMP', 'TIMESTAMPADD', 'TIMESTAMPDIFF', 'TIME_FORMAT', 'TIME_TO_SEC', 'TOUCHES', 'TO_DAYS', 'TRIM', 'TRUNCATE', 'UCASE',
        'UNCOMPRESS', 'UNCOMPRESSED_LENGTH', 'UNHEX', 'UNIQUE_USERS', 'UNIX_TIMESTAMP', 'UPDATEXML', 'UPPER', 'USER', 'UTC_DATE', 'UTC_TIME', 'UTC_TIMESTAMP',
        'UUID', 'VARIANCE', 'VAR_POP', 'VAR_SAMP', 'VERSION', 'WEEK', 'WEEKDAY', 'WEEKOFYEAR', 'WITHIN', 'X', 'Y', 'YEAR', 'YEARWEEK'
    );

    // Punctuation that can be used as a boundary between other tokens
    protected static $boundaries = array(',', ';',':', ')', '(', '.', '=', '<', '>', '+', '-', '*', '/', '!', '^', '%', '|', '&', '#');

    // For HTML syntax highlighting
    // Styles applied to different token types
    public static $quote_attributes = 'style="color: blue;"';
    public static $backtick_quote_attributes = 'style="color: purple;"';
    public static $reserved_attributes = 'style="font-weight:bold;"';
    public static $boundary_attributes = '';
    public static $number_attributes = 'style="color: green;"';
    public static $word_attributes = 'style="color: #333;"';
    public static $error_attributes = 'style="background-color: red;"';
    public static $comment_attributes = 'style="color: #aaa;"';
    public static $variable_attributes = 'style="color: orange;"';
    public static $pre_attributes = 'style="color: black; background-color: white;"';

    // Boolean - whether or not the current environment is the CLI
    // This affects the type of syntax highlighting
    // If not defined, it will be determined automatically
    public static $cli;

    // For CLI syntax highlighting
    public static $cli_quote = "\x1b[34;1m";
    public static $cli_backtick_quote = "\x1b[35;1m";
    public static $cli_reserved = "\x1b[37m";
    public static $cli_boundary = "";
    public static $cli_number = "\x1b[32;1m";
    public static $cli_word = "";
    public static $cli_error = "\x1b[31;1;7m";
    public static $cli_comment = "\x1b[30;1m";
    public static $cli_functions = "\x1b[37m";
    public static $cli_variable = "\x1b[36;1m";

    // The tab character to use when formatting SQL
    public static $tab = '  ';

    // This flag tells us if queries need to be enclosed in <pre> tags
    public static $use_pre = true;

    // This flag tells us if SqlFormatted has been initialized
    protected static $init;

    // Regular expressions for tokenizing
    protected static $regex_boundaries;
    protected static $regex_reserved;
    protected static $regex_reserved_newline;
    protected static $regex_reserved_toplevel;
    protected static $regex_function;

    // Cache variables
    // Only tokens shorter than this size will be cached.  Somewhere between 10 and 20 seems to work well for most cases.
    public static $max_cachekey_size = 15;
    protected static $token_cache = array();
    protected static $cache_hits = 0;
    protected static $cache_misses = 0;

    /**
     * Get stats about the token cache
     * @return Array An array containing the keys 'hits', 'misses', 'entries', and 'size' in bytes
     */
    public static function getCacheStats()
    {
        return array(
            'hits'=>self::$cache_hits,
            'misses'=>self::$cache_misses,
            'entries'=>count(self::$token_cache),
            'size'=>strlen(serialize(self::$token_cache))
        );
    }

    /**
     * Stuff that only needs to be done once.  Builds regular expressions and sorts the reserved words.
     */
    protected static function init()
    {
        if (self::$init) return;

        // Sort reserved word list from longest word to shortest, 3x faster than usort
        $reservedMap = array_combine(self::$reserved, array_map('strlen', self::$reserved));
        arsort($reservedMap);
        self::$reserved = array_keys($reservedMap);

        // Set up regular expressions
        self::$regex_boundaries = '('.implode('|',array_map(array(__CLASS__, 'quote_regex'),self::$boundaries)).')';
        self::$regex_reserved = '('.implode('|',array_map(array(__CLASS__, 'quote_regex'),self::$reserved)).')';
        self::$regex_reserved_toplevel = str_replace(' ','\\s+','('.implode('|',array_map(array(__CLASS__, 'quote_regex'),self::$reserved_toplevel)).')');
        self::$regex_reserved_newline = str_replace(' ','\\s+','('.implode('|',array_map(array(__CLASS__, 'quote_regex'),self::$reserved_newline)).')');

        self::$regex_function = '('.implode('|',array_map(array(__CLASS__, 'quote_regex'),self::$functions)).')';

        self::$init = true;
    }

    /**
     * Return the next token and token type in a SQL string.
     * Quoted strings, comments, reserved words, whitespace, and punctuation are all their own tokens.
     *
     * @param String $string   The SQL string
     * @param array  $previous The result of the previous getNextToken() call
     *
     * @return Array An associative array containing the type and value of the token.
     */
    protected static function getNextToken($string, $previous = null)
    {
        // Whitespace
        if (preg_match('/^\s+/',$string,$matches)) {
            return array(
                self::TOKEN_VALUE => $matches[0],
                self::TOKEN_TYPE=>self::TOKEN_TYPE_WHITESPACE
            );
        }

        // Comment
        if ($string[0] === '#' || (isset($string[1])&&($string[0]==='-'&&$string[1]==='-') || ($string[0]==='/'&&$string[1]==='*'))) {
            // Comment until end of line
            if ($string[0] === '-' || $string[0] === '#') {
                $last = strpos($string, "\n");
                $type = self::TOKEN_TYPE_COMMENT;
            } else { // Comment until closing comment tag
                $last = strpos($string, "*/", 2) + 2;
                $type = self::TOKEN_TYPE_BLOCK_COMMENT;
            }

            if ($last === false) {
                $last = strlen($string);
            }

            return array(
                self::TOKEN_VALUE => substr($string, 0, $last),
                self::TOKEN_TYPE  => $type
            );
        }

        // Quoted String
        if ($string[0]==='"' || $string[0]==='\'' || $string[0]==='`' || $string[0]==='[') {
            $return = array(
                self::TOKEN_TYPE => (($string[0]==='`' || $string[0]==='[')? self::TOKEN_TYPE_BACKTICK_QUOTE : self::TOKEN_TYPE_QUOTE),
                self::TOKEN_VALUE => self::getQuotedString($string)
            );

            return $return;
        }

        // User-defined Variable
        if ($string[0] === '@' && isset($string[1])) {
            $ret = array(
                self::TOKEN_VALUE => null,
                self::TOKEN_TYPE => self::TOKEN_TYPE_VARIABLE
            );
            
            // If the variable name is quoted
            if ($string[1]==='"' || $string[1]==='\'' || $string[1]==='`') {
                $ret[self::TOKEN_VALUE] = '@'.self::getQuotedString(substr($string,1));
            }
            // Non-quoted variable name
            else {
                preg_match('/^(@[a-zA-Z0-9\._\$]+)/',$string,$matches);
                if ($matches) {
                    $ret[self::TOKEN_VALUE] = $matches[1];
                }
            }
            
            if($ret[self::TOKEN_VALUE] !== null) return $ret;
        }

        // Number (decimal, binary, or hex)
        if (preg_match('/^([0-9]+(\.[0-9]+)?|0x[0-9a-fA-F]+|0b[01]+)($|\s|"\'`|'.self::$regex_boundaries.')/',$string,$matches)) {
            return array(
                self::TOKEN_VALUE => $matches[1],
                self::TOKEN_TYPE=>self::TOKEN_TYPE_NUMBER
            );
        }

        // Boundary Character (punctuation and symbols)
        if (preg_match('/^('.self::$regex_boundaries.')/',$string,$matches)) {
            return array(
                self::TOKEN_VALUE => $matches[1],
                self::TOKEN_TYPE  => self::TOKEN_TYPE_BOUNDARY
            );
        }

        // A reserved word cannot be preceded by a '.'
        // this makes it so in "mytable.from", "from" is not considered a reserved word
        if (!$previous || !isset($previous[self::TOKEN_VALUE]) || $previous[self::TOKEN_VALUE] !== '.') {
            $upper = strtoupper($string);
            // Top Level Reserved Word
            if (preg_match('/^('.self::$regex_reserved_toplevel.')($|\s|'.self::$regex_boundaries.')/', $upper,$matches)) {
                return array(
                    self::TOKEN_TYPE=>self::TOKEN_TYPE_RESERVED_TOPLEVEL,
                    self::TOKEN_VALUE=>substr($string,0,strlen($matches[1]))
                );
            }
            // Newline Reserved Word
            if (preg_match('/^('.self::$regex_reserved_newline.')($|\s|'.self::$regex_boundaries.')/', $upper,$matches)) {
                return array(
                    self::TOKEN_TYPE=>self::TOKEN_TYPE_RESERVED_NEWLINE,
                    self::TOKEN_VALUE=>substr($string,0,strlen($matches[1]))
                );
            }
            // Other Reserved Word
            if (preg_match('/^('.self::$regex_reserved.')($|\s|'.self::$regex_boundaries.')/', $upper,$matches)) {
                return array(
                    self::TOKEN_TYPE=>self::TOKEN_TYPE_RESERVED,
                    self::TOKEN_VALUE=>substr($string,0,strlen($matches[1]))
                );
            }
        }

        // A function must be suceeded by '('
        // this makes it so "count(" is considered a function, but "count" alone is not
        $upper = strtoupper($string);
        // function
        if (preg_match('/^('.self::$regex_function.'[(]|\s|[)])/', $upper,$matches)) {
            return array(
                self::TOKEN_TYPE=>self::TOKEN_TYPE_RESERVED,
                self::TOKEN_VALUE=>substr($string,0,strlen($matches[1])-1)
            );
        }

        // Non reserved word
        preg_match('/^(.*?)($|\s|["\'`]|'.self::$regex_boundaries.')/',$string,$matches);

        return array(
            self::TOKEN_VALUE => $matches[1],
            self::TOKEN_TYPE  => self::TOKEN_TYPE_WORD
        );
    }

    protected static function getQuotedString($string)
    {
        $ret = null;
        
        // This checks for the following patterns:
        // 1. backtick quoted string using `` to escape
        // 2. square bracket quoted string (SQL Server) using ]] to escape
        // 3. double quoted string using "" or \" to escape
        // 4. single quoted string using '' or \' to escape
        if ( preg_match('/^(((`[^`]*($|`))+)|((\[[^\]]*($|\]))(\][^\]]*($|\]))*)|(("[^"\\\\]*(?:\\\\.[^"\\\\]*)*("|$))+)|((\'[^\'\\\\]*(?:\\\\.[^\'\\\\]*)*(\'|$))+))/s', $string, $matches)) {
            $ret = $matches[1];
        }
        
        return $ret;
    }

    /**
     * Takes a SQL string and breaks it into tokens.
     * Each token is an associative array with type and value.
     *
     * @param String $string The SQL string
     *
     * @return Array An array of tokens.
     */
    protected static function tokenize($string)
    {
        self::init();

        $tokens = array();

        // Used for debugging if there is an error while tokenizing the string
        $original_length = strlen($string);

        // Used to make sure the string keeps shrinking on each iteration
        $old_string_len = strlen($string) + 1;

        $token = null;

        $current_length = strlen($string);

        // Keep processing the string until it is empty
        while ($current_length) {
            // If the string stopped shrinking, there was a problem
            if ($old_string_len <= $current_length) {
                $tokens[] = array(
                    self::TOKEN_VALUE=>$string,
                    self::TOKEN_TYPE=>self::TOKEN_TYPE_ERROR
                );

                return $tokens;
            }
            $old_string_len =  $current_length;

            // Determine if we can use caching
            if ($current_length >= self::$max_cachekey_size) {
                $cacheKey = substr($string,0,self::$max_cachekey_size);
            } else {
                $cacheKey = false;
            }

            // See if the token is already cached
            if ($cacheKey && isset(self::$token_cache[$cacheKey])) {
                // Retrieve from cache
                $token = self::$token_cache[$cacheKey];
                $token_length = strlen($token[self::TOKEN_VALUE]);
                self::$cache_hits++;
            } else {
                // Get the next token and the token type
                $token = self::getNextToken($string, $token);
                $token_length = strlen($token[self::TOKEN_VALUE]);
                self::$cache_misses++;

                // If the token is shorter than the max length, store it in cache
                if ($cacheKey && $token_length < self::$max_cachekey_size) {
                    self::$token_cache[$cacheKey] = $token;
                }
            }

            $tokens[] = $token;

            // Advance the string
            $string = substr($string, $token_length);

            $current_length -= $token_length;
        }

        return $tokens;
    }

    /**
     * Format the whitespace in a SQL string to make it easier to read.
     *
     * @param String  $string    The SQL string
     * @param boolean $highlight If true, syntax highlighting will also be performed
     *
     * @return String The SQL string with HTML styles and formatting wrapped in a <pre> tag
     */
    public static function format($string, $highlight=true)
    {
        // This variable will be populated with formatted html
        $return = '';

        // Use an actual tab while formatting and then switch out with self::$tab at the end
        $tab = "\t";

        $indent_level = 0;
        $newline = false;
        $inline_parentheses = false;
        $increase_special_indent = false;
        $increase_block_indent = false;
        $indent_types = array();
        $added_newline = false;
        $inline_count = 0;
        $inline_indented = false;
        $clause_limit = false;

        // Tokenize String
        $original_tokens = self::tokenize($string);

        // Remove existing whitespace
        $tokens = array();
        foreach ($original_tokens as $i=>$token) {
            if ($token[self::TOKEN_TYPE] !== self::TOKEN_TYPE_WHITESPACE) {
                $token['i'] = $i;
                $tokens[] = $token;
            }
        }

        // Format token by token
        foreach ($tokens as $i=>$token) {
            // Get highlighted token if doing syntax highlighting
            if ($highlight) {
                $highlighted = self::highlightToken($token);
            } else { // If returning raw text
                $highlighted = $token[self::TOKEN_VALUE];
            }

            // If we are increasing the special indent level now
            if ($increase_special_indent) {
                $indent_level++;
                $increase_special_indent = false;
                array_unshift($indent_types,'special');
            }
            // If we are increasing the block indent level now
            if ($increase_block_indent) {
                $indent_level++;
                $increase_block_indent = false;
                array_unshift($indent_types,'block');
            }

            // If we need a new line before the token
            if ($newline) {
                $return .= "\n" . str_repeat($tab, $indent_level);
                $newline = false;
                $added_newline = true;
            } else {
                $added_newline = false;
            }

            // Display comments directly where they appear in the source
            if ($token[self::TOKEN_TYPE] === self::TOKEN_TYPE_COMMENT || $token[self::TOKEN_TYPE] === self::TOKEN_TYPE_BLOCK_COMMENT) {
                if ($token[self::TOKEN_TYPE] === self::TOKEN_TYPE_BLOCK_COMMENT) {
                    $indent = str_repeat($tab,$indent_level);
                    $return .= "\n" . $indent;
                    $highlighted = str_replace("\n","\n".$indent,$highlighted);
                }

                $return .= $highlighted;
                $newline = true;
                continue;
            }

            if ($inline_parentheses) {
                // End of inline parentheses
                if ($token[self::TOKEN_VALUE] === ')') {
                    $return = rtrim($return,' ');

                    if ($inline_indented) {
                        array_shift($indent_types);
                        $indent_level --;
                        $return .= "\n" . str_repeat($tab, $indent_level);
                    }

                    $inline_parentheses = false;

                    $return .= $highlighted . ' ';
                    continue;
                }

                if ($token[self::TOKEN_VALUE] === ',') {
                    if ($inline_count >= 30) {
                        $inline_count = 0;
                        $newline = true;
                    }
                }

                $inline_count += strlen($token[self::TOKEN_VALUE]);
            }

            // Opening parentheses increase the block indent level and start a new line
            if ($token[self::TOKEN_VALUE] === '(') {
                // First check if this should be an inline parentheses block
                // Examples are "NOW()", "COUNT(*)", "int(10)", key(`somecolumn`), DECIMAL(7,2)
                // Allow up to 3 non-whitespace tokens inside inline parentheses
                $length = 0;
                for ($j=1;$j<=250;$j++) {
                    // Reached end of string
                    if (!isset($tokens[$i+$j])) break;

                    $next = $tokens[$i+$j];

                    // Reached closing parentheses, able to inline it
                    if ($next[self::TOKEN_VALUE] === ')') {
                        $inline_parentheses = true;
                        $inline_count = 0;
                        $inline_indented = false;
                        break;
                    }

                    // Reached an invalid token for inline parentheses
                    if ($next[self::TOKEN_VALUE]===';' || $next[self::TOKEN_VALUE]==='(') {
                        break;
                    }

                    // Reached an invalid token type for inline parentheses
                    if ($next[self::TOKEN_TYPE]===self::TOKEN_TYPE_RESERVED_TOPLEVEL || $next[self::TOKEN_TYPE]===self::TOKEN_TYPE_RESERVED_NEWLINE || $next[self::TOKEN_TYPE]===self::TOKEN_TYPE_COMMENT || $next[self::TOKEN_TYPE]===self::TOKEN_TYPE_BLOCK_COMMENT) {
                        break;
                    }

                    $length += strlen($next[self::TOKEN_VALUE]);
                }

                if ($inline_parentheses && $length > 30) {
                    $increase_block_indent = true;
                    $inline_indented = true;
                    $newline = true;
                }

                // Take out the preceding space unless there was whitespace there in the original query
                if (isset($original_tokens[$token['i']-1]) && $original_tokens[$token['i']-1][self::TOKEN_TYPE] !== self::TOKEN_TYPE_WHITESPACE) {
                    $return = rtrim($return,' ');
                }

                if (!$inline_parentheses) {
                    $increase_block_indent = true;
                    // Add a newline after the parentheses
                    $newline = true;
                }

            }

            // Closing parentheses decrease the block indent level
            elseif ($token[self::TOKEN_VALUE] === ')') {
                // Remove whitespace before the closing parentheses
                $return = rtrim($return,' ');

                $indent_level--;

                // Reset indent level
                while ($j=array_shift($indent_types)) {
                    if ($j==='special') {
                        $indent_level--;
                    } else {
                        break;
                    }
                }

                if ($indent_level < 0) {
                    // This is an error
                    $indent_level = 0;

                    if ($highlight) {
                        $return .= "\n".self::highlightError($token[self::TOKEN_VALUE]);
                        continue;
                    }
                }

                // Add a newline before the closing parentheses (if not already added)
                if (!$added_newline) {
                    $return .= "\n" . str_repeat($tab, $indent_level);
                }
            }

            // Top level reserved words start a new line and increase the special indent level
            elseif ($token[self::TOKEN_TYPE] === self::TOKEN_TYPE_RESERVED_TOPLEVEL) {
                $increase_special_indent = true;

                // If the last indent type was 'special', decrease the special indent for this round
                reset($indent_types);
                if (current($indent_types)==='special') {
                    $indent_level--;
                    array_shift($indent_types);
                }

                // Add a newline after the top level reserved word
                $newline = true;
                // Add a newline before the top level reserved word (if not already added)
                if (!$added_newline) {
                    $return .= "\n" . str_repeat($tab, $indent_level);
                }
                // If we already added a newline, redo the indentation since it may be different now
                else {
                    $return = rtrim($return,$tab).str_repeat($tab, $indent_level);
                }

                // If the token may have extra whitespace
                if (strpos($token[self::TOKEN_VALUE],' ')!==false || strpos($token[self::TOKEN_VALUE],"\n")!==false || strpos($token[self::TOKEN_VALUE],"\t")!==false) {
                    $highlighted = preg_replace('/\s+/',' ',$highlighted);
                }
                //if SQL 'LIMIT' clause, start variable to reset newline
                if ($token[self::TOKEN_VALUE] === 'LIMIT' && !$inline_parentheses) {
                    $clause_limit = true;
                }
            }

            // Checks if we are out of the limit clause
            elseif ($clause_limit && $token[self::TOKEN_VALUE] !== "," && $token[self::TOKEN_TYPE] !== self::TOKEN_TYPE_NUMBER && $token[self::TOKEN_TYPE] !== self::TOKEN_TYPE_WHITESPACE) {
                $clause_limit = false;
            }

            // Commas start a new line (unless within inline parentheses or SQL 'LIMIT' clause)
            elseif ($token[self::TOKEN_VALUE] === ',' && !$inline_parentheses) {
                //If the previous TOKEN_VALUE is 'LIMIT', resets new line
                if ($clause_limit === true) {
                    $newline = false;
                    $clause_limit = false;
                }
                // All other cases of commas
                else {
                    $newline = true;
                }
            }

            // Newline reserved words start a new line
            elseif ($token[self::TOKEN_TYPE] === self::TOKEN_TYPE_RESERVED_NEWLINE) {
                // Add a newline before the reserved word (if not already added)
                if (!$added_newline) {
                    $return .= "\n" . str_repeat($tab, $indent_level);
                }

                // If the token may have extra whitespace
                if (strpos($token[self::TOKEN_VALUE],' ')!==false || strpos($token[self::TOKEN_VALUE],"\n")!==false || strpos($token[self::TOKEN_VALUE],"\t")!==false) {
                    $highlighted = preg_replace('/\s+/',' ',$highlighted);
                }
            }

            // Multiple boundary characters in a row should not have spaces between them (not including parentheses)
            elseif ($token[self::TOKEN_TYPE] === self::TOKEN_TYPE_BOUNDARY) {
                if (isset($tokens[$i-1]) && $tokens[$i-1][self::TOKEN_TYPE] === self::TOKEN_TYPE_BOUNDARY) {
                    if (isset($original_tokens[$token['i']-1]) && $original_tokens[$token['i']-1][self::TOKEN_TYPE] !== self::TOKEN_TYPE_WHITESPACE) {
                        $return = rtrim($return,' ');
                    }
                }
            }

            // If the token shouldn't have a space before it
            if ($token[self::TOKEN_VALUE] === '.' || $token[self::TOKEN_VALUE] === ',' || $token[self::TOKEN_VALUE] === ';') {
                $return = rtrim($return, ' ');
            }

            $return .= $highlighted.' ';

            // If the token shouldn't have a space after it
            if ($token[self::TOKEN_VALUE] === '(' || $token[self::TOKEN_VALUE] === '.') {
                $return = rtrim($return,' ');
            }
            
            // If this is the "-" of a negative number, it shouldn't have a space after it
            if($token[self::TOKEN_VALUE] === '-' && isset($tokens[$i+1]) && $tokens[$i+1][self::TOKEN_TYPE] === self::TOKEN_TYPE_NUMBER && isset($tokens[$i-1])) {
                $prev = $tokens[$i-1][self::TOKEN_TYPE];
                if($prev !== self::TOKEN_TYPE_QUOTE && $prev !== self::TOKEN_TYPE_BACKTICK_QUOTE && $prev !== self::TOKEN_TYPE_WORD && $prev !== self::TOKEN_TYPE_NUMBER) {
                    $return = rtrim($return,' ');
                }
            } 
        }

        // If there are unmatched parentheses
        if ($highlight && array_search('block',$indent_types) !== false) {
            $return .= "\n".self::highlightError("WARNING: unclosed parentheses or section");
        }

        // Replace tab characters with the configuration tab character
        $return = trim(str_replace("\t",self::$tab,$return));

        if ($highlight) {
            $return = self::output($return);
        }

        return $return;
    }

    /**
     * Add syntax highlighting to a SQL string
     *
     * @param String $string The SQL string
     *
     * @return String The SQL string with HTML styles applied
     */
    public static function highlight($string)
    {
        $tokens = self::tokenize($string);

        $return = '';

        foreach ($tokens as $token) {
            $return .= self::highlightToken($token);
        }

        return self::output($return);
    }

    /**
     * Split a SQL string into multiple queries.
     * Uses ";" as a query delimiter.
     *
     * @param String $string The SQL string
     *
     * @return Array An array of individual query strings without trailing semicolons
     */
    public static function splitQuery($string)
    {
        $queries = array();
        $current_query = '';
        $empty = true;

        $tokens = self::tokenize($string);

        foreach ($tokens as $token) {
            // If this is a query separator
            if ($token[self::TOKEN_VALUE] === ';') {
                if (!$empty) {
                    $queries[] = $current_query.';';
                }
                $current_query = '';
                $empty = true;
                continue;
            }

            // If this is a non-empty character
            if ($token[self::TOKEN_TYPE] !== self::TOKEN_TYPE_WHITESPACE && $token[self::TOKEN_TYPE] !== self::TOKEN_TYPE_COMMENT && $token[self::TOKEN_TYPE] !== self::TOKEN_TYPE_BLOCK_COMMENT) {
                $empty = false;
            }

            $current_query .= $token[self::TOKEN_VALUE];
        }

        if (!$empty) {
            $queries[] = trim($current_query);
        }

        return $queries;
    }

    /**
     * Remove all comments from a SQL string
     *
     * @param String $string The SQL string
     *
     * @return String The SQL string without comments
     */
    public static function removeComments($string)
    {
        $result = '';

        $tokens = self::tokenize($string);

        foreach ($tokens as $token) {
            // Skip comment tokens
            if ($token[self::TOKEN_TYPE] === self::TOKEN_TYPE_COMMENT || $token[self::TOKEN_TYPE] === self::TOKEN_TYPE_BLOCK_COMMENT) {
                continue;
            }

            $result .= $token[self::TOKEN_VALUE];
        }
        $result = self::format( $result,false);

        return $result;
    }

    /**
     * Compress a query by collapsing white space and removing comments
     *
     * @param String $string The SQL string
     *
     * @return String The SQL string without comments
     */
    public static function compress($string)
    {
        $result = '';

        $tokens = self::tokenize($string);

        $whitespace = true;
        foreach ($tokens as $token) {
            // Skip comment tokens
            if ($token[self::TOKEN_TYPE] === self::TOKEN_TYPE_COMMENT || $token[self::TOKEN_TYPE] === self::TOKEN_TYPE_BLOCK_COMMENT) {
                continue;
            }
            // Remove extra whitespace in reserved words (e.g "OUTER     JOIN" becomes "OUTER JOIN")
            elseif ($token[self::TOKEN_TYPE] === self::TOKEN_TYPE_RESERVED || $token[self::TOKEN_TYPE] === self::TOKEN_TYPE_RESERVED_NEWLINE || $token[self::TOKEN_TYPE] === self::TOKEN_TYPE_RESERVED_TOPLEVEL) {
                $token[self::TOKEN_VALUE] = preg_replace('/\s+/',' ',$token[self::TOKEN_VALUE]);
            }

            if ($token[self::TOKEN_TYPE] === self::TOKEN_TYPE_WHITESPACE) {
                // If the last token was whitespace, don't add another one
                if ($whitespace) {
                    continue;
                } else {
                    $whitespace = true;
                    // Convert all whitespace to a single space
                    $token[self::TOKEN_VALUE] = ' ';
                }
            } else {
                $whitespace = false;
            }

            $result .= $token[self::TOKEN_VALUE];
        }

        return rtrim($result);
    }

    /**
     * Highlights a token depending on its type.
     *
     * @param Array $token An associative array containing type and value.
     *
     * @return String HTML code of the highlighted token.
     */
    protected static function highlightToken($token)
    {
        $type = $token[self::TOKEN_TYPE];

        if (self::is_cli()) {
            $token = $token[self::TOKEN_VALUE];
        } else {
            if (defined('ENT_IGNORE')) {
              $token = htmlentities($token[self::TOKEN_VALUE],ENT_COMPAT | ENT_IGNORE ,'UTF-8');
            } else {
              $token = htmlentities($token[self::TOKEN_VALUE],ENT_COMPAT,'UTF-8');
            }
        }

        if ($type===self::TOKEN_TYPE_BOUNDARY) {
            return self::highlightBoundary($token);
        } elseif ($type===self::TOKEN_TYPE_WORD) {
            return self::highlightWord($token);
        } elseif ($type===self::TOKEN_TYPE_BACKTICK_QUOTE) {
            return self::highlightBacktickQuote($token);
        } elseif ($type===self::TOKEN_TYPE_QUOTE) {
            return self::highlightQuote($token);
        } elseif ($type===self::TOKEN_TYPE_RESERVED) {
            return self::highlightReservedWord($token);
        } elseif ($type===self::TOKEN_TYPE_RESERVED_TOPLEVEL) {
            return self::highlightReservedWord($token);
        } elseif ($type===self::TOKEN_TYPE_RESERVED_NEWLINE) {
            return self::highlightReservedWord($token);
        } elseif ($type===self::TOKEN_TYPE_NUMBER) {
            return self::highlightNumber($token);
        } elseif ($type===self::TOKEN_TYPE_VARIABLE) {
            return self::highlightVariable($token);
        } elseif ($type===self::TOKEN_TYPE_COMMENT || $type===self::TOKEN_TYPE_BLOCK_COMMENT) {
            return self::highlightComment($token);
        }

        return $token;
    }

    /**
     * Highlights a quoted string
     *
     * @param String $value The token's value
     *
     * @return String HTML code of the highlighted token.
     */
    protected static function highlightQuote($value)
    {
        if (self::is_cli()) {
            return self::$cli_quote . $value . "\x1b[0m";
        } else {
            return '<span ' . self::$quote_attributes . '>' . $value . '</span>';
        }
    }

    /**
     * Highlights a backtick quoted string
     *
     * @param String $value The token's value
     *
     * @return String HTML code of the highlighted token.
     */
    protected static function highlightBacktickQuote($value)
    {
        if (self::is_cli()) {
            return self::$cli_backtick_quote . $value . "\x1b[0m";
        } else {
            return '<span ' . self::$backtick_quote_attributes . '>' . $value . '</span>';
        }
    }

    /**
     * Highlights a reserved word
     *
     * @param String $value The token's value
     *
     * @return String HTML code of the highlighted token.
     */
    protected static function highlightReservedWord($value)
    {
        if (self::is_cli()) {
            return self::$cli_reserved . $value . "\x1b[0m";
        } else {
            return '<span ' . self::$reserved_attributes . '>' . $value . '</span>';
        }
    }

    /**
     * Highlights a boundary token
     *
     * @param String $value The token's value
     *
     * @return String HTML code of the highlighted token.
     */
    protected static function highlightBoundary($value)
    {
        if ($value==='(' || $value===')') return $value;

        if (self::is_cli()) {
            return self::$cli_boundary . $value . "\x1b[0m";
        } else {
            return '<span ' . self::$boundary_attributes . '>' . $value . '</span>';
        }
    }

    /**
     * Highlights a number
     *
     * @param String $value The token's value
     *
     * @return String HTML code of the highlighted token.
     */
    protected static function highlightNumber($value)
    {
        if (self::is_cli()) {
            return self::$cli_number . $value . "\x1b[0m";
        } else {
            return '<span ' . self::$number_attributes . '>' . $value . '</span>';
        }
    }

    /**
     * Highlights an error
     *
     * @param String $value The token's value
     *
     * @return String HTML code of the highlighted token.
     */
    protected static function highlightError($value)
    {
        if (self::is_cli()) {
            return self::$cli_error . $value . "\x1b[0m";
        } else {
            return '<span ' . self::$error_attributes . '>' . $value . '</span>';
        }
    }

    /**
     * Highlights a comment
     *
     * @param String $value The token's value
     *
     * @return String HTML code of the highlighted token.
     */
    protected static function highlightComment($value)
    {
        if (self::is_cli()) {
            return self::$cli_comment . $value . "\x1b[0m";
        } else {
            return '<span ' . self::$comment_attributes . '>' . $value . '</span>';
        }
    }

    /**
     * Highlights a word token
     *
     * @param String $value The token's value
     *
     * @return String HTML code of the highlighted token.
     */
    protected static function highlightWord($value)
    {
        if (self::is_cli()) {
            return self::$cli_word . $value . "\x1b[0m";
        } else {
            return '<span ' . self::$word_attributes . '>' . $value . '</span>';
        }
    }

    /**
     * Highlights a variable token
     *
     * @param String $value The token's value
     *
     * @return String HTML code of the highlighted token.
     */
    protected static function highlightVariable($value)
    {
        if (self::is_cli()) {
            return self::$cli_variable . $value . "\x1b[0m";
        } else {
            return '<span ' . self::$variable_attributes . '>' . $value . '</span>';
        }
    }

    /**
     * Helper function for building regular expressions for reserved words and boundary characters
     *
     * @param String $a The string to be quoted
     *
     * @return String The quoted string
     */
    private static function quote_regex($a)
    {
        return preg_quote($a,'/');
    }

    /**
     * Helper function for building string output
     *
     * @param String $string The string to be quoted
     *
     * @return String The quoted string
     */
    private static function output($string)
    {
        if (self::is_cli()) {
            return $string."\n";
        } else {
            $string=trim($string);
            if (!self::$use_pre) {
                return $string;
            }

            return '<pre '.self::$pre_attributes.'>' . $string . '</pre>';
        }
    }

    private static function is_cli()
    {
        if (isset(self::$cli)) return self::$cli;
        else return php_sapi_name() === 'cli';
    }

}


if( get_magic_quotes_gpc() ){
    if ($_POST){
        $_POST = stripslashesall($_POST);
    }
    if ($_GET){
    	$_GET = stripslashesall($_GET);
    }
    if ($_COOKIE){
    	$_COOKIE = stripslashesall($_COOKIE);
    }
    if ($_REQUEST){
    	$_REQUEST = stripslashesall($_REQUEST);
    }
}


$db = new db();
$action = (isset($_REQUEST['action']) and in_array($_REQUEST['action'],array('index','connect','login','add','edit','save','delete')))?$_REQUEST['action']:'index';

$data = array('error'=>'','connect'=>'');

$_REQUEST = array_merge($_SESSION,$_REQUEST,$config);

$sql = '';
$database_selected  = false;
$connected  = false;

if( isset($_COOKIE[$config['cookie']]) ){
	list($config['host'],$config['username'],$config['password'],$config['charset']) = _decode($_COOKIE[$config['cookie']]);
	if( $db->connect($config['host'],$config['username'],$config['password'],$config['charset']) ){
		$connected = true;
	}else{
		$data['error'] = $db->error();
	}
}else{
	if( isset($_SESSION['host']) and isset($_SESSION['username']) and isset($_SESSION['password']) ){
		if( $db->connect($_SESSION['host'],$_SESSION['username'],$_SESSION['password'],$_SESSION['charset']) ){
			$connected = true;
		}else{
			$data['error'] = $db->error();
		}
	}
}

switch( $action ){
	case 'save':
		if( $connected ){
			
			if( $db->selectdb($_REQUEST['dbname']) ){
				if( isset($_POST['key']) and is_array($_POST['key']) and count($_POST['key']) ){
					if( isset($_REQUEST['table']) and isset($_REQUEST['primary_key']) and isset($_REQUEST['primary_value']) ){
						
						$inq = $db->update($_GET['table'],$_POST['key'],'`'.$db->_($_REQUEST['primary_key']).'`=\''.$db->_($_REQUEST['primary_value']).'\'');
						if(!$inq){
							$data['error'] = $db->error();
							$action = 'edit';
							$inq = $db->q($q = 'select * from `'.$db->_($_GET['table']).'` where `'.$db->_($_REQUEST['primary_key']).'`=\''.$db->_($_REQUEST['primary_value']).'\'');
						}
					}elseif( isset($_REQUEST['table']) ){
						$inq = $db->insert($_GET['table'],$_POST['key']);
						if( $inq ){
							$_REQUEST['primary_key'] = get_primary_field($_GET['table']);
							if( $_REQUEST['primary_key'] )
								$_REQUEST['primary_value'] = $inq;
						}else{
							$data['error'] = $db->error();
							$action = 'add';
							$inq = $db->q($q = 'select * from `'.$db->_($_GET['table']).'` limit 1');
						}
					}
				}
				if( $inq && !$data['error'] ){
					if( isset($_REQUEST['close']) or !isset($_REQUEST['save']) ){
						$sql = 'select * FROM `'.$db->_($_REQUEST['table'].'`');
						
						$action = 'index';
						if( is_select($sql) and !is_limited($sql) )
							$sql = add_to_sql($sql,'limit',$config['count_on_page']);
						
						$time = microtime(true);
							
						$inq = $db->q( $sql );
						
						$endtime = microtime(true) - $time;
						
						if( $inq and is_select($sql) and mysql_field_table($inq,0) )
							$_GET['table'] = $_SESSION['table'] = $_REQUEST['table'] = mysql_field_table($inq,0);
					}else{
						$action = 'edit';
						$inq = $db->q($q = 'select * from `'.$db->_($_GET['table']).'` where `'.$db->_($_REQUEST['primary_key']).'`=\''.$db->_($_REQUEST['primary_value']).'\'');
					}
				}
				$database_selected = true;
			}
		}else{
			$data['error'] = $db->error();
			$action = 'login';
		}
	break;
	case 'delete':
		if( $connected ){
			if( $db->selectdb($_REQUEST['dbname']) ){
				if( isset($_GET['table']) and isset($_GET['key']) and (!empty($_GET['value']) or (!empty($_GET['values']) and is_array($_GET['values']) and count($_GET['values']))) ){
					if( !empty($_GET['value'] ) ){
						$inq = $db->delete($_GET['table'],'`'.$db->_($_GET['key']).'`=\''.$db->_($_GET['value']).'\'');
					}else{
						$inq = $db->delete($_GET['table'],'`'.$db->_($_GET['key']).'` in ('.implode(',',array_map(ekran,$_GET['values'])).')');
					}
					if($inq){
						$action = 'index';
						$sql = 'select * FROM `'.$db->_($_REQUEST['table']).'`';
						$database_selected = true;
						$action = 'index';
						if( is_select($sql) and !is_limited($sql) )
							$sql = add_to_sql($sql,'limit',$config['count_on_page']);
					}else{
						$data['error'] = $db->error();
					}
				}
				
				$database_selected = true;
			}
		}
	break;
	case 'add':
		if( $connected ){
			if( $db->selectdb($_REQUEST['dbname']) ){
				if( isset($_GET['table']) ){
					$inq = $db->q($q = 'select * from `'.$db->_($_GET['table']).'` limit 1');
				}
				$database_selected = true;
			}
		}else{
			$data['error'] = $db->error();
			$action = 'login';
		}
	break;
	case 'edit':
		if( $connected ){
			if( $db->selectdb($_REQUEST['dbname']) ){
				if( isset($_GET['table']) and isset($_GET['key']) and isset($_GET['value']) ){
					$inq = $db->q($q = 'select * from `'.$db->_($_GET['table']).'` where `'.$db->_($_GET['key']).'`=\''.$db->_($_GET['value']).'\'');
				}
				
				$database_selected = true;
			}
		}else{
			$data['error'] = $db->error();
			$action = 'login';
		}
	break;
	case 'index':
		if( $connected ){
			if( isset($_REQUEST['dbname']) ){
				if( $db->selectdb($_REQUEST['dbname']) ){
					if( isset($_GET['table']) ){
						$_SESSION['table'] = $_REQUEST['table'];
						$sql = $_REQUEST['sql']? $_REQUEST['sql']:'select * FROM `'.$db->_($_REQUEST['table']).'`';
					}else
						$sql = $_REQUEST['sql']? $_REQUEST['sql']:'SHOW TABLES FROM `'.$db->_($_REQUEST['dbname']).'`';
					
					$_SESSION['dbname'] = $_REQUEST['dbname'];
					
					if( is_select($sql) and !is_limited($sql) )
						$sql = add_to_sql($sql,'limit',$config['count_on_page']);
					$database_selected = true;
				}else{
					$data['error'] = $db->error();
					$sql = $_REQUEST['sql']? $_REQUEST['sql']:'SHOW DATABASES;';
				}
			}else{
				$sql = $_REQUEST['sql']? $_REQUEST['sql']:'SHOW DATABASES;';
			}
			
			$action = 'index';
			
			$time = microtime(true);	
			$inq = $db->q( $sql );
			$endtime = microtime(true) - $time;
			
			if( $inq and is_select($sql) and mysql_field_table($inq,0) )
				$_GET['table'] = $_SESSION['table'] = $_REQUEST['table'] = mysql_field_table($inq,0);
		}else{
			$data['error'] = $db->error();
			$action = 'login';
		}
	break;
	case 'connect':
		$db->disconnect();
		
		if( $db->connect($_REQUEST['host'],$_REQUEST['username'],$_REQUEST['password'],$_REQUEST['charset']) ){
			if( !empty($_REQUEST['remember']) ){
				setcookie($config['cookie'],_encode($q = $_REQUEST['host'],$_REQUEST['username'],$_REQUEST['password'],$_REQUEST['charset']));
			}else{
				$_SESSION['host'] = $_REQUEST['host'];
				$_SESSION['username'] = $_REQUEST['username'];
				$_SESSION['password'] = $_REQUEST['password'];
				$_SESSION['charset'] = $_REQUEST['charset'];
			}
			$action = 'index';
			header('location:?action=index');
			exit();
		}else{
			if( !empty($_REQUEST['through']) ){
				$connect_data = try_connect_through($_REQUEST['through']);
				if( $connect_data ){
					if( !empty($_REQUEST['remember']) ){
						setcookie($config['cookie'],_encode($connect_data['host'],$connect_data['username'],$connect_data['password']));
					}else{
						$_SESSION['host'] = $connect_data['host'];
						$_SESSION['username'] = $connect_data['username'];
						$_SESSION['password'] = $connect_data['password'];
					}
					header('location:?action=index');
					exit();
				}
			}
			$_SESSION['host'] = $_SESSION['username'] = $_SESSION['password'] = '';
			setcookie($config['cookie'],'');
			$data['error'] = $db->error();
			$action = 'login';
		}
	break;
}	



?><!DOCTYPE html>
<html lang="ru">
<head>
<meta http-equiv="content-type" content="text/html; charset=utf-8"/>
<title>Mini MySQL Admin v<? 
echo '1.1.3';?></title>
<meta name="keywords" content="" /> 
<meta name="description" content=""/>
<link href="data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAABAAAAAQCAIAAACQkWg2AAAAGXRFWHRTb2Z0d2FyZQBBZG9iZSBJbWFnZVJlYWR5ccllPAAAAyJpVFh0WE1MOmNvbS5hZG9iZS54bXAAAAAAADw/eHBhY2tldCBiZWdpbj0i77u/IiBpZD0iVzVNME1wQ2VoaUh6cmVTek5UY3prYzlkIj8+IDx4OnhtcG1ldGEgeG1sbnM6eD0iYWRvYmU6bnM6bWV0YS8iIHg6eG1wdGs9IkFkb2JlIFhNUCBDb3JlIDUuMy1jMDExIDY2LjE0NTY2MSwgMjAxMi8wMi8wNi0xNDo1NjoyNyAgICAgICAgIj4gPHJkZjpSREYgeG1sbnM6cmRmPSJodHRwOi8vd3d3LnczLm9yZy8xOTk5LzAyLzIyLXJkZi1zeW50YXgtbnMjIj4gPHJkZjpEZXNjcmlwdGlvbiByZGY6YWJvdXQ9IiIgeG1sbnM6eG1wPSJodHRwOi8vbnMuYWRvYmUuY29tL3hhcC8xLjAvIiB4bWxuczp4bXBNTT0iaHR0cDovL25zLmFkb2JlLmNvbS94YXAvMS4wL21tLyIgeG1sbnM6c3RSZWY9Imh0dHA6Ly9ucy5hZG9iZS5jb20veGFwLzEuMC9zVHlwZS9SZXNvdXJjZVJlZiMiIHhtcDpDcmVhdG9yVG9vbD0iQWRvYmUgUGhvdG9zaG9wIENTNiAoV2luZG93cykiIHhtcE1NOkluc3RhbmNlSUQ9InhtcC5paWQ6OEI3QjNGRDNFMDE1MTFFMzk5NURCNkU3OEQyRTE2QjMiIHhtcE1NOkRvY3VtZW50SUQ9InhtcC5kaWQ6OEI3QjNGRDRFMDE1MTFFMzk5NURCNkU3OEQyRTE2QjMiPiA8eG1wTU06RGVyaXZlZEZyb20gc3RSZWY6aW5zdGFuY2VJRD0ieG1wLmlpZDo4QjdCM0ZEMUUwMTUxMUUzOTk1REI2RTc4RDJFMTZCMyIgc3RSZWY6ZG9jdW1lbnRJRD0ieG1wLmRpZDo4QjdCM0ZEMkUwMTUxMUUzOTk1REI2RTc4RDJFMTZCMyIvPiA8L3JkZjpEZXNjcmlwdGlvbj4gPC9yZGY6UkRGPiA8L3g6eG1wbWV0YT4gPD94cGFja2V0IGVuZD0iciI/PtZnflQAAAHmSURBVHjaTFJLq3FhFHYOIxORSBJl5DJhgigpycRlxkzKyP+QsZ/BzIAiEyLKnZTcDVwnErkk9vcc63z7WLXXfvZa61nrfda7v+LxuMVi4bwtnU4T8Pl8BKrV6n6/53wYTywW2+12+litVvV6XSKRsJHxeLzb7T4J358fer0e3mAwsBGNRkNk+9t+CK/XCy+aq1Qqn8+n0WgEvt/v8AzD+P1+Pp/vcDhkMhmyvxNutxs4AoEAQ3AkVNNJptMpsMfjQapQKID/jYc4jUYDPhAIwA+HQ7YRsNlsRrvNZvNHgKcidILvdrtsHJjaMW/7Ez2ZTI7HI+HBYEBAp9O53W6cqlgsEoF3Pp+Xy+V6vYb6ZrOpVquBwYQHYTQaPR4PDGd7cVUqlVAolMvlCoWiVCpls9lOp+N0OiG93++32+1Wq4WFcrnc7Xb7c85kMokXoofDYT6fRyIRJK7XKzA8MCIowIoib+OxGsrl8mKxwLKlUmkqlcrn81arNRwOI0LqaQ28XC4nEolsNhsuGLvPZDKIzmYzpHu9Hummjr+EYDAIAdFoFDfq9XpPpxOiWq3W5XJRHf0E7AROIpEAggDyoVAIu2L+G2TEYjFKzd/2hQpMMJlMl8ulUqnQBIqgN9ZVq9U+//B/AgwAFeVfm4b+8bQAAAAASUVORK5CYII=" rel="shortcut icon" type="image/x-icon">
<style>/*!
 * Bootstrap v3.1.1 (http://getbootstrap.com)
 * Copyright 2011-2014 Twitter, Inc.
 * Licensed under MIT (https://github.com/twbs/bootstrap/blob/master/LICENSE)
 */

/*! normalize.css v3.0.0 | MIT License | git.io/normalize */html{font-family:sans-serif;-ms-text-size-adjust:100%;-webkit-text-size-adjust:100%}body{margin:0}article,aside,details,figcaption,figure,footer,header,hgroup,main,nav,section,summary{display:block}audio,canvas,progress,video{display:inline-block;vertical-align:baseline}audio:not([controls]){display:none;height:0}[hidden],template{display:none}a{background:0 0}a:active,a:hover{outline:0}abbr[title]{border-bottom:1px dotted}b,strong{font-weight:700}dfn{font-style:italic}h1{font-size:2em;margin:.67em 0}mark{background:#ff0;color:#000}small{font-size:80%}sub,sup{font-size:75%;line-height:0;position:relative;vertical-align:baseline}sup{top:-.5em}sub{bottom:-.25em}img{border:0}svg:not(:root){overflow:hidden}figure{margin:1em 40px}hr{-moz-box-sizing:content-box;box-sizing:content-box;height:0}pre{overflow:auto}code,kbd,pre,samp{font-family:monospace,monospace;font-size:1em}button,input,optgroup,select,textarea{color:inherit;font:inherit;margin:0}button{overflow:visible}button,select{text-transform:none}button,html input[type=button],input[type=reset],input[type=submit]{-webkit-appearance:button;cursor:pointer}button[disabled],html input[disabled]{cursor:default}button::-moz-focus-inner,input::-moz-focus-inner{border:0;padding:0}input{line-height:normal}input[type=checkbox],input[type=radio]{box-sizing:border-box;padding:0}input[type=number]::-webkit-inner-spin-button,input[type=number]::-webkit-outer-spin-button{height:auto}input[type=search]{-webkit-appearance:textfield;-moz-box-sizing:content-box;-webkit-box-sizing:content-box;box-sizing:content-box}input[type=search]::-webkit-search-cancel-button,input[type=search]::-webkit-search-decoration{-webkit-appearance:none}fieldset{border:1px solid silver;margin:0 2px;padding:.35em .625em .75em}legend{border:0;padding:0}textarea{overflow:auto}optgroup{font-weight:700}table{border-collapse:collapse;border-spacing:0}td,th{padding:0}@media print{*{text-shadow:none!important;color:#000!important;background:transparent!important;box-shadow:none!important}a,a:visited{text-decoration:underline}a[href]:after{content:" (" attr(href) ")"}abbr[title]:after{content:" (" attr(title) ")"}a[href^="javascript:"]:after,a[href^="#"]:after{content:""}pre,blockquote{border:1px solid #999;page-break-inside:avoid}thead{display:table-header-group}tr,img{page-break-inside:avoid}img{max-width:100%!important}p,h2,h3{orphans:3;widows:3}h2,h3{page-break-after:avoid}select{background:#fff!important}.navbar{display:none}.table td,.table th{background-color:#fff!important}.btn>.caret,.dropup>.btn>.caret{border-top-color:#000!important}.label{border:1px solid #000}.table{border-collapse:collapse!important}.table-bordered th,.table-bordered td{border:1px solid #ddd!important}}*{-webkit-box-sizing:border-box;-moz-box-sizing:border-box;box-sizing:border-box}:before,:after{-webkit-box-sizing:border-box;-moz-box-sizing:border-box;box-sizing:border-box}html{font-size:62.5%;-webkit-tap-highlight-color:rgba(0,0,0,0)}body{font-family:"Helvetica Neue",Helvetica,Arial,sans-serif;font-size:14px;line-height:1.42857143;color:#333;background-color:#fff}input,button,select,textarea{font-family:inherit;font-size:inherit;line-height:inherit}a{color:#428bca;text-decoration:none}a:hover,a:focus{color:#2a6496;text-decoration:underline}a:focus{outline:thin dotted;outline:5px auto -webkit-focus-ring-color;outline-offset:-2px}figure{margin:0}img{vertical-align:middle}.img-responsive,.thumbnail>img,.thumbnail a>img,.carousel-inner>.item>img,.carousel-inner>.item>a>img{display:block;max-width:100%;height:auto}.img-rounded{border-radius:6px}.img-thumbnail{padding:4px;line-height:1.42857143;background-color:#fff;border:1px solid #ddd;border-radius:4px;-webkit-transition:all .2s ease-in-out;transition:all .2s ease-in-out;display:inline-block;max-width:100%;height:auto}.img-circle{border-radius:50%}hr{margin-top:20px;margin-bottom:20px;border:0;border-top:1px solid #eee}.sr-only{position:absolute;width:1px;height:1px;margin:-1px;padding:0;overflow:hidden;clip:rect(0,0,0,0);border:0}h1,h2,h3,h4,h5,h6,.h1,.h2,.h3,.h4,.h5,.h6{font-family:inherit;font-weight:500;line-height:1.1;color:inherit}h1 small,h2 small,h3 small,h4 small,h5 small,h6 small,.h1 small,.h2 small,.h3 small,.h4 small,.h5 small,.h6 small,h1 .small,h2 .small,h3 .small,h4 .small,h5 .small,h6 .small,.h1 .small,.h2 .small,.h3 .small,.h4 .small,.h5 .small,.h6 .small{font-weight:400;line-height:1;color:#999}h1,.h1,h2,.h2,h3,.h3{margin-top:20px;margin-bottom:10px}h1 small,.h1 small,h2 small,.h2 small,h3 small,.h3 small,h1 .small,.h1 .small,h2 .small,.h2 .small,h3 .small,.h3 .small{font-size:65%}h4,.h4,h5,.h5,h6,.h6{margin-top:10px;margin-bottom:10px}h4 small,.h4 small,h5 small,.h5 small,h6 small,.h6 small,h4 .small,.h4 .small,h5 .small,.h5 .small,h6 .small,.h6 .small{font-size:75%}h1,.h1{font-size:36px}h2,.h2{font-size:30px}h3,.h3{font-size:24px}h4,.h4{font-size:18px}h5,.h5{font-size:14px}h6,.h6{font-size:12px}p{margin:0 0 10px}.lead{margin-bottom:20px;font-size:16px;font-weight:200;line-height:1.4}@media (min-width:768px){.lead{font-size:21px}}small,.small{font-size:85%}cite{font-style:normal}.text-left{text-align:left}.text-right{text-align:right}.text-center{text-align:center}.text-justify{text-align:justify}.text-muted{color:#999}.text-primary{color:#428bca}a.text-primary:hover{color:#3071a9}.text-success{color:#3c763d}a.text-success:hover{color:#2b542c}.text-info{color:#31708f}a.text-info:hover{color:#245269}.text-warning{color:#8a6d3b}a.text-warning:hover{color:#66512c}.text-danger{color:#a94442}a.text-danger:hover{color:#843534}.bg-primary{color:#fff;background-color:#428bca}a.bg-primary:hover{background-color:#3071a9}.bg-success{background-color:#dff0d8}a.bg-success:hover{background-color:#c1e2b3}.bg-info{background-color:#d9edf7}a.bg-info:hover{background-color:#afd9ee}.bg-warning{background-color:#fcf8e3}a.bg-warning:hover{background-color:#f7ecb5}.bg-danger{background-color:#f2dede}a.bg-danger:hover{background-color:#e4b9b9}.page-header{padding-bottom:9px;margin:40px 0 20px;border-bottom:1px solid #eee}ul,ol{margin-top:0;margin-bottom:10px}ul ul,ol ul,ul ol,ol ol{margin-bottom:0}.list-unstyled{padding-left:0;list-style:none}.list-inline{padding-left:0;list-style:none;margin-left:-5px}.list-inline>li{display:inline-block;padding-left:5px;padding-right:5px}dl{margin-top:0;margin-bottom:20px}dt,dd{line-height:1.42857143}dt{font-weight:700}dd{margin-left:0}@media (min-width:768px){.dl-horizontal dt{float:left;width:160px;clear:left;text-align:right;overflow:hidden;text-overflow:ellipsis;white-space:nowrap}.dl-horizontal dd{margin-left:180px}}abbr[title],abbr[data-original-title]{cursor:help;border-bottom:1px dotted #999}.initialism{font-size:90%;text-transform:uppercase}blockquote{padding:10px 20px;margin:0 0 20px;font-size:17.5px;border-left:5px solid #eee}blockquote p:last-child,blockquote ul:last-child,blockquote ol:last-child{margin-bottom:0}blockquote footer,blockquote small,blockquote .small{display:block;font-size:80%;line-height:1.42857143;color:#999}blockquote footer:before,blockquote small:before,blockquote .small:before{content:'\2014 \00A0'}.blockquote-reverse,blockquote.pull-right{padding-right:15px;padding-left:0;border-right:5px solid #eee;border-left:0;text-align:right}.blockquote-reverse footer:before,blockquote.pull-right footer:before,.blockquote-reverse small:before,blockquote.pull-right small:before,.blockquote-reverse .small:before,blockquote.pull-right .small:before{content:''}.blockquote-reverse footer:after,blockquote.pull-right footer:after,.blockquote-reverse small:after,blockquote.pull-right small:after,.blockquote-reverse .small:after,blockquote.pull-right .small:after{content:'\00A0 \2014'}blockquote:before,blockquote:after{content:""}address{margin-bottom:20px;font-style:normal;line-height:1.42857143}code,kbd,pre,samp{font-family:Menlo,Monaco,Consolas,"Courier New",monospace}code{padding:2px 4px;font-size:90%;color:#c7254e;background-color:#f9f2f4;white-space:nowrap;border-radius:4px}kbd{padding:2px 4px;font-size:90%;color:#fff;background-color:#333;border-radius:3px;box-shadow:inset 0 -1px 0 rgba(0,0,0,.25)}pre{display:block;padding:9.5px;margin:0 0 10px;font-size:13px;line-height:1.42857143;word-break:break-all;word-wrap:break-word;color:#333;background-color:#f5f5f5;border:1px solid #ccc;border-radius:4px}pre code{padding:0;font-size:inherit;color:inherit;white-space:pre-wrap;background-color:transparent;border-radius:0}.pre-scrollable{max-height:340px;overflow-y:scroll}.container{margin-right:auto;margin-left:auto;padding-left:15px;padding-right:15px}@media (min-width:768px){.container{width:750px}}@media (min-width:992px){.container{width:970px}}@media (min-width:1200px){.container{width:1170px}}.container-fluid{margin-right:auto;margin-left:auto;padding-left:15px;padding-right:15px}.row{margin-left:-15px;margin-right:-15px}.col-xs-1,.col-sm-1,.col-md-1,.col-lg-1,.col-xs-2,.col-sm-2,.col-md-2,.col-lg-2,.col-xs-3,.col-sm-3,.col-md-3,.col-lg-3,.col-xs-4,.col-sm-4,.col-md-4,.col-lg-4,.col-xs-5,.col-sm-5,.col-md-5,.col-lg-5,.col-xs-6,.col-sm-6,.col-md-6,.col-lg-6,.col-xs-7,.col-sm-7,.col-md-7,.col-lg-7,.col-xs-8,.col-sm-8,.col-md-8,.col-lg-8,.col-xs-9,.col-sm-9,.col-md-9,.col-lg-9,.col-xs-10,.col-sm-10,.col-md-10,.col-lg-10,.col-xs-11,.col-sm-11,.col-md-11,.col-lg-11,.col-xs-12,.col-sm-12,.col-md-12,.col-lg-12{position:relative;min-height:1px;padding-left:15px;padding-right:15px}.col-xs-1,.col-xs-2,.col-xs-3,.col-xs-4,.col-xs-5,.col-xs-6,.col-xs-7,.col-xs-8,.col-xs-9,.col-xs-10,.col-xs-11,.col-xs-12{float:left}.col-xs-12{width:100%}.col-xs-11{width:91.66666667%}.col-xs-10{width:83.33333333%}.col-xs-9{width:75%}.col-xs-8{width:66.66666667%}.col-xs-7{width:58.33333333%}.col-xs-6{width:50%}.col-xs-5{width:41.66666667%}.col-xs-4{width:33.33333333%}.col-xs-3{width:25%}.col-xs-2{width:16.66666667%}.col-xs-1{width:8.33333333%}.col-xs-pull-12{right:100%}.col-xs-pull-11{right:91.66666667%}.col-xs-pull-10{right:83.33333333%}.col-xs-pull-9{right:75%}.col-xs-pull-8{right:66.66666667%}.col-xs-pull-7{right:58.33333333%}.col-xs-pull-6{right:50%}.col-xs-pull-5{right:41.66666667%}.col-xs-pull-4{right:33.33333333%}.col-xs-pull-3{right:25%}.col-xs-pull-2{right:16.66666667%}.col-xs-pull-1{right:8.33333333%}.col-xs-pull-0{right:0}.col-xs-push-12{left:100%}.col-xs-push-11{left:91.66666667%}.col-xs-push-10{left:83.33333333%}.col-xs-push-9{left:75%}.col-xs-push-8{left:66.66666667%}.col-xs-push-7{left:58.33333333%}.col-xs-push-6{left:50%}.col-xs-push-5{left:41.66666667%}.col-xs-push-4{left:33.33333333%}.col-xs-push-3{left:25%}.col-xs-push-2{left:16.66666667%}.col-xs-push-1{left:8.33333333%}.col-xs-push-0{left:0}.col-xs-offset-12{margin-left:100%}.col-xs-offset-11{margin-left:91.66666667%}.col-xs-offset-10{margin-left:83.33333333%}.col-xs-offset-9{margin-left:75%}.col-xs-offset-8{margin-left:66.66666667%}.col-xs-offset-7{margin-left:58.33333333%}.col-xs-offset-6{margin-left:50%}.col-xs-offset-5{margin-left:41.66666667%}.col-xs-offset-4{margin-left:33.33333333%}.col-xs-offset-3{margin-left:25%}.col-xs-offset-2{margin-left:16.66666667%}.col-xs-offset-1{margin-left:8.33333333%}.col-xs-offset-0{margin-left:0}@media (min-width:768px){.col-sm-1,.col-sm-2,.col-sm-3,.col-sm-4,.col-sm-5,.col-sm-6,.col-sm-7,.col-sm-8,.col-sm-9,.col-sm-10,.col-sm-11,.col-sm-12{float:left}.col-sm-12{width:100%}.col-sm-11{width:91.66666667%}.col-sm-10{width:83.33333333%}.col-sm-9{width:75%}.col-sm-8{width:66.66666667%}.col-sm-7{width:58.33333333%}.col-sm-6{width:50%}.col-sm-5{width:41.66666667%}.col-sm-4{width:33.33333333%}.col-sm-3{width:25%}.col-sm-2{width:16.66666667%}.col-sm-1{width:8.33333333%}.col-sm-pull-12{right:100%}.col-sm-pull-11{right:91.66666667%}.col-sm-pull-10{right:83.33333333%}.col-sm-pull-9{right:75%}.col-sm-pull-8{right:66.66666667%}.col-sm-pull-7{right:58.33333333%}.col-sm-pull-6{right:50%}.col-sm-pull-5{right:41.66666667%}.col-sm-pull-4{right:33.33333333%}.col-sm-pull-3{right:25%}.col-sm-pull-2{right:16.66666667%}.col-sm-pull-1{right:8.33333333%}.col-sm-pull-0{right:0}.col-sm-push-12{left:100%}.col-sm-push-11{left:91.66666667%}.col-sm-push-10{left:83.33333333%}.col-sm-push-9{left:75%}.col-sm-push-8{left:66.66666667%}.col-sm-push-7{left:58.33333333%}.col-sm-push-6{left:50%}.col-sm-push-5{left:41.66666667%}.col-sm-push-4{left:33.33333333%}.col-sm-push-3{left:25%}.col-sm-push-2{left:16.66666667%}.col-sm-push-1{left:8.33333333%}.col-sm-push-0{left:0}.col-sm-offset-12{margin-left:100%}.col-sm-offset-11{margin-left:91.66666667%}.col-sm-offset-10{margin-left:83.33333333%}.col-sm-offset-9{margin-left:75%}.col-sm-offset-8{margin-left:66.66666667%}.col-sm-offset-7{margin-left:58.33333333%}.col-sm-offset-6{margin-left:50%}.col-sm-offset-5{margin-left:41.66666667%}.col-sm-offset-4{margin-left:33.33333333%}.col-sm-offset-3{margin-left:25%}.col-sm-offset-2{margin-left:16.66666667%}.col-sm-offset-1{margin-left:8.33333333%}.col-sm-offset-0{margin-left:0}}@media (min-width:992px){.col-md-1,.col-md-2,.col-md-3,.col-md-4,.col-md-5,.col-md-6,.col-md-7,.col-md-8,.col-md-9,.col-md-10,.col-md-11,.col-md-12{float:left}.col-md-12{width:100%}.col-md-11{width:91.66666667%}.col-md-10{width:83.33333333%}.col-md-9{width:75%}.col-md-8{width:66.66666667%}.col-md-7{width:58.33333333%}.col-md-6{width:50%}.col-md-5{width:41.66666667%}.col-md-4{width:33.33333333%}.col-md-3{width:25%}.col-md-2{width:16.66666667%}.col-md-1{width:8.33333333%}.col-md-pull-12{right:100%}.col-md-pull-11{right:91.66666667%}.col-md-pull-10{right:83.33333333%}.col-md-pull-9{right:75%}.col-md-pull-8{right:66.66666667%}.col-md-pull-7{right:58.33333333%}.col-md-pull-6{right:50%}.col-md-pull-5{right:41.66666667%}.col-md-pull-4{right:33.33333333%}.col-md-pull-3{right:25%}.col-md-pull-2{right:16.66666667%}.col-md-pull-1{right:8.33333333%}.col-md-pull-0{right:0}.col-md-push-12{left:100%}.col-md-push-11{left:91.66666667%}.col-md-push-10{left:83.33333333%}.col-md-push-9{left:75%}.col-md-push-8{left:66.66666667%}.col-md-push-7{left:58.33333333%}.col-md-push-6{left:50%}.col-md-push-5{left:41.66666667%}.col-md-push-4{left:33.33333333%}.col-md-push-3{left:25%}.col-md-push-2{left:16.66666667%}.col-md-push-1{left:8.33333333%}.col-md-push-0{left:0}.col-md-offset-12{margin-left:100%}.col-md-offset-11{margin-left:91.66666667%}.col-md-offset-10{margin-left:83.33333333%}.col-md-offset-9{margin-left:75%}.col-md-offset-8{margin-left:66.66666667%}.col-md-offset-7{margin-left:58.33333333%}.col-md-offset-6{margin-left:50%}.col-md-offset-5{margin-left:41.66666667%}.col-md-offset-4{margin-left:33.33333333%}.col-md-offset-3{margin-left:25%}.col-md-offset-2{margin-left:16.66666667%}.col-md-offset-1{margin-left:8.33333333%}.col-md-offset-0{margin-left:0}}@media (min-width:1200px){.col-lg-1,.col-lg-2,.col-lg-3,.col-lg-4,.col-lg-5,.col-lg-6,.col-lg-7,.col-lg-8,.col-lg-9,.col-lg-10,.col-lg-11,.col-lg-12{float:left}.col-lg-12{width:100%}.col-lg-11{width:91.66666667%}.col-lg-10{width:83.33333333%}.col-lg-9{width:75%}.col-lg-8{width:66.66666667%}.col-lg-7{width:58.33333333%}.col-lg-6{width:50%}.col-lg-5{width:41.66666667%}.col-lg-4{width:33.33333333%}.col-lg-3{width:25%}.col-lg-2{width:16.66666667%}.col-lg-1{width:8.33333333%}.col-lg-pull-12{right:100%}.col-lg-pull-11{right:91.66666667%}.col-lg-pull-10{right:83.33333333%}.col-lg-pull-9{right:75%}.col-lg-pull-8{right:66.66666667%}.col-lg-pull-7{right:58.33333333%}.col-lg-pull-6{right:50%}.col-lg-pull-5{right:41.66666667%}.col-lg-pull-4{right:33.33333333%}.col-lg-pull-3{right:25%}.col-lg-pull-2{right:16.66666667%}.col-lg-pull-1{right:8.33333333%}.col-lg-pull-0{right:0}.col-lg-push-12{left:100%}.col-lg-push-11{left:91.66666667%}.col-lg-push-10{left:83.33333333%}.col-lg-push-9{left:75%}.col-lg-push-8{left:66.66666667%}.col-lg-push-7{left:58.33333333%}.col-lg-push-6{left:50%}.col-lg-push-5{left:41.66666667%}.col-lg-push-4{left:33.33333333%}.col-lg-push-3{left:25%}.col-lg-push-2{left:16.66666667%}.col-lg-push-1{left:8.33333333%}.col-lg-push-0{left:0}.col-lg-offset-12{margin-left:100%}.col-lg-offset-11{margin-left:91.66666667%}.col-lg-offset-10{margin-left:83.33333333%}.col-lg-offset-9{margin-left:75%}.col-lg-offset-8{margin-left:66.66666667%}.col-lg-offset-7{margin-left:58.33333333%}.col-lg-offset-6{margin-left:50%}.col-lg-offset-5{margin-left:41.66666667%}.col-lg-offset-4{margin-left:33.33333333%}.col-lg-offset-3{margin-left:25%}.col-lg-offset-2{margin-left:16.66666667%}.col-lg-offset-1{margin-left:8.33333333%}.col-lg-offset-0{margin-left:0}}table{max-width:100%;background-color:transparent}th{text-align:left}.table{width:100%;margin-bottom:20px}.table>thead>tr>th,.table>tbody>tr>th,.table>tfoot>tr>th,.table>thead>tr>td,.table>tbody>tr>td,.table>tfoot>tr>td{padding:8px;line-height:1.42857143;vertical-align:top;border-top:1px solid #ddd}.table>thead>tr>th{vertical-align:bottom;border-bottom:2px solid #ddd}.table>caption+thead>tr:first-child>th,.table>colgroup+thead>tr:first-child>th,.table>thead:first-child>tr:first-child>th,.table>caption+thead>tr:first-child>td,.table>colgroup+thead>tr:first-child>td,.table>thead:first-child>tr:first-child>td{border-top:0}.table>tbody+tbody{border-top:2px solid #ddd}.table .table{background-color:#fff}.table-condensed>thead>tr>th,.table-condensed>tbody>tr>th,.table-condensed>tfoot>tr>th,.table-condensed>thead>tr>td,.table-condensed>tbody>tr>td,.table-condensed>tfoot>tr>td{padding:5px}.table-bordered{border:1px solid #ddd}.table-bordered>thead>tr>th,.table-bordered>tbody>tr>th,.table-bordered>tfoot>tr>th,.table-bordered>thead>tr>td,.table-bordered>tbody>tr>td,.table-bordered>tfoot>tr>td{border:1px solid #ddd}.table-bordered>thead>tr>th,.table-bordered>thead>tr>td{border-bottom-width:2px}.table-striped>tbody>tr:nth-child(odd)>td,.table-striped>tbody>tr:nth-child(odd)>th{background-color:#f9f9f9}.table-hover>tbody>tr:hover>td,.table-hover>tbody>tr:hover>th{background-color:#f5f5f5}table col[class*=col-]{position:static;float:none;display:table-column}table td[class*=col-],table th[class*=col-]{position:static;float:none;display:table-cell}.table>thead>tr>td.active,.table>tbody>tr>td.active,.table>tfoot>tr>td.active,.table>thead>tr>th.active,.table>tbody>tr>th.active,.table>tfoot>tr>th.active,.table>thead>tr.active>td,.table>tbody>tr.active>td,.table>tfoot>tr.active>td,.table>thead>tr.active>th,.table>tbody>tr.active>th,.table>tfoot>tr.active>th{background-color:#f5f5f5}.table-hover>tbody>tr>td.active:hover,.table-hover>tbody>tr>th.active:hover,.table-hover>tbody>tr.active:hover>td,.table-hover>tbody>tr.active:hover>th{background-color:#e8e8e8}.table>thead>tr>td.success,.table>tbody>tr>td.success,.table>tfoot>tr>td.success,.table>thead>tr>th.success,.table>tbody>tr>th.success,.table>tfoot>tr>th.success,.table>thead>tr.success>td,.table>tbody>tr.success>td,.table>tfoot>tr.success>td,.table>thead>tr.success>th,.table>tbody>tr.success>th,.table>tfoot>tr.success>th{background-color:#dff0d8}.table-hover>tbody>tr>td.success:hover,.table-hover>tbody>tr>th.success:hover,.table-hover>tbody>tr.success:hover>td,.table-hover>tbody>tr.success:hover>th{background-color:#d0e9c6}.table>thead>tr>td.info,.table>tbody>tr>td.info,.table>tfoot>tr>td.info,.table>thead>tr>th.info,.table>tbody>tr>th.info,.table>tfoot>tr>th.info,.table>thead>tr.info>td,.table>tbody>tr.info>td,.table>tfoot>tr.info>td,.table>thead>tr.info>th,.table>tbody>tr.info>th,.table>tfoot>tr.info>th{background-color:#d9edf7}.table-hover>tbody>tr>td.info:hover,.table-hover>tbody>tr>th.info:hover,.table-hover>tbody>tr.info:hover>td,.table-hover>tbody>tr.info:hover>th{background-color:#c4e3f3}.table>thead>tr>td.warning,.table>tbody>tr>td.warning,.table>tfoot>tr>td.warning,.table>thead>tr>th.warning,.table>tbody>tr>th.warning,.table>tfoot>tr>th.warning,.table>thead>tr.warning>td,.table>tbody>tr.warning>td,.table>tfoot>tr.warning>td,.table>thead>tr.warning>th,.table>tbody>tr.warning>th,.table>tfoot>tr.warning>th{background-color:#fcf8e3}.table-hover>tbody>tr>td.warning:hover,.table-hover>tbody>tr>th.warning:hover,.table-hover>tbody>tr.warning:hover>td,.table-hover>tbody>tr.warning:hover>th{background-color:#faf2cc}.table>thead>tr>td.danger,.table>tbody>tr>td.danger,.table>tfoot>tr>td.danger,.table>thead>tr>th.danger,.table>tbody>tr>th.danger,.table>tfoot>tr>th.danger,.table>thead>tr.danger>td,.table>tbody>tr.danger>td,.table>tfoot>tr.danger>td,.table>thead>tr.danger>th,.table>tbody>tr.danger>th,.table>tfoot>tr.danger>th{background-color:#f2dede}.table-hover>tbody>tr>td.danger:hover,.table-hover>tbody>tr>th.danger:hover,.table-hover>tbody>tr.danger:hover>td,.table-hover>tbody>tr.danger:hover>th{background-color:#ebcccc}@media (max-width:767px){.table-responsive{width:100%;margin-bottom:15px;overflow-y:hidden;overflow-x:scroll;-ms-overflow-style:-ms-autohiding-scrollbar;border:1px solid #ddd;-webkit-overflow-scrolling:touch}.table-responsive>.table{margin-bottom:0}.table-responsive>.table>thead>tr>th,.table-responsive>.table>tbody>tr>th,.table-responsive>.table>tfoot>tr>th,.table-responsive>.table>thead>tr>td,.table-responsive>.table>tbody>tr>td,.table-responsive>.table>tfoot>tr>td{white-space:nowrap}.table-responsive>.table-bordered{border:0}.table-responsive>.table-bordered>thead>tr>th:first-child,.table-responsive>.table-bordered>tbody>tr>th:first-child,.table-responsive>.table-bordered>tfoot>tr>th:first-child,.table-responsive>.table-bordered>thead>tr>td:first-child,.table-responsive>.table-bordered>tbody>tr>td:first-child,.table-responsive>.table-bordered>tfoot>tr>td:first-child{border-left:0}.table-responsive>.table-bordered>thead>tr>th:last-child,.table-responsive>.table-bordered>tbody>tr>th:last-child,.table-responsive>.table-bordered>tfoot>tr>th:last-child,.table-responsive>.table-bordered>thead>tr>td:last-child,.table-responsive>.table-bordered>tbody>tr>td:last-child,.table-responsive>.table-bordered>tfoot>tr>td:last-child{border-right:0}.table-responsive>.table-bordered>tbody>tr:last-child>th,.table-responsive>.table-bordered>tfoot>tr:last-child>th,.table-responsive>.table-bordered>tbody>tr:last-child>td,.table-responsive>.table-bordered>tfoot>tr:last-child>td{border-bottom:0}}fieldset{padding:0;margin:0;border:0;min-width:0}legend{display:block;width:100%;padding:0;margin-bottom:20px;font-size:21px;line-height:inherit;color:#333;border:0;border-bottom:1px solid #e5e5e5}label{display:inline-block;margin-bottom:5px;font-weight:700}input[type=search]{-webkit-box-sizing:border-box;-moz-box-sizing:border-box;box-sizing:border-box}input[type=radio],input[type=checkbox]{margin:4px 0 0;margin-top:1px \9;line-height:normal}input[type=file]{display:block}input[type=range]{display:block;width:100%}select[multiple],select[size]{height:auto}input[type=file]:focus,input[type=radio]:focus,input[type=checkbox]:focus{outline:thin dotted;outline:5px auto -webkit-focus-ring-color;outline-offset:-2px}output{display:block;padding-top:7px;font-size:14px;line-height:1.42857143;color:#555}.form-control{display:block;width:100%;height:34px;padding:6px 12px;font-size:14px;line-height:1.42857143;color:#555;background-color:#fff;background-image:none;border:1px solid #ccc;border-radius:4px;-webkit-box-shadow:inset 0 1px 1px rgba(0,0,0,.075);box-shadow:inset 0 1px 1px rgba(0,0,0,.075);-webkit-transition:border-color ease-in-out .15s,box-shadow ease-in-out .15s;transition:border-color ease-in-out .15s,box-shadow ease-in-out .15s}.form-control:focus{border-color:#66afe9;outline:0;-webkit-box-shadow:inset 0 1px 1px rgba(0,0,0,.075),0 0 8px rgba(102,175,233,.6);box-shadow:inset 0 1px 1px rgba(0,0,0,.075),0 0 8px rgba(102,175,233,.6)}.form-control::-moz-placeholder{color:#999;opacity:1}.form-control:-ms-input-placeholder{color:#999}.form-control::-webkit-input-placeholder{color:#999}.form-control[disabled],.form-control[readonly],fieldset[disabled] .form-control{cursor:not-allowed;background-color:#eee;opacity:1}textarea.form-control{height:auto}input[type=search]{-webkit-appearance:none}input[type=date]{line-height:34px}.form-group{margin-bottom:15px}.radio,.checkbox{display:block;min-height:20px;margin-top:10px;margin-bottom:10px;padding-left:20px}.radio label,.checkbox label{display:inline;font-weight:400;cursor:pointer}.radio input[type=radio],.radio-inline input[type=radio],.checkbox input[type=checkbox],.checkbox-inline input[type=checkbox]{float:left;margin-left:-20px}.radio+.radio,.checkbox+.checkbox{margin-top:-5px}.radio-inline,.checkbox-inline{display:inline-block;padding-left:20px;margin-bottom:0;vertical-align:middle;font-weight:400;cursor:pointer}.radio-inline+.radio-inline,.checkbox-inline+.checkbox-inline{margin-top:0;margin-left:10px}input[type=radio][disabled],input[type=checkbox][disabled],.radio[disabled],.radio-inline[disabled],.checkbox[disabled],.checkbox-inline[disabled],fieldset[disabled] input[type=radio],fieldset[disabled] input[type=checkbox],fieldset[disabled] .radio,fieldset[disabled] .radio-inline,fieldset[disabled] .checkbox,fieldset[disabled] .checkbox-inline{cursor:not-allowed}.input-sm{height:30px;padding:5px 10px;font-size:12px;line-height:1.5;border-radius:3px}select.input-sm{height:30px;line-height:30px}textarea.input-sm,select[multiple].input-sm{height:auto}.input-lg{height:46px;padding:10px 16px;font-size:18px;line-height:1.33;border-radius:6px}select.input-lg{height:46px;line-height:46px}textarea.input-lg,select[multiple].input-lg{height:auto}.has-feedback{position:relative}.has-feedback .form-control{padding-right:42.5px}.has-feedback .form-control-feedback{position:absolute;top:25px;right:0;display:block;width:34px;height:34px;line-height:34px;text-align:center}.has-success .help-block,.has-success .control-label,.has-success .radio,.has-success .checkbox,.has-success .radio-inline,.has-success .checkbox-inline{color:#3c763d}.has-success .form-control{border-color:#3c763d;-webkit-box-shadow:inset 0 1px 1px rgba(0,0,0,.075);box-shadow:inset 0 1px 1px rgba(0,0,0,.075)}.has-success .form-control:focus{border-color:#2b542c;-webkit-box-shadow:inset 0 1px 1px rgba(0,0,0,.075),0 0 6px #67b168;box-shadow:inset 0 1px 1px rgba(0,0,0,.075),0 0 6px #67b168}.has-success .input-group-addon{color:#3c763d;border-color:#3c763d;background-color:#dff0d8}.has-success .form-control-feedback{color:#3c763d}.has-warning .help-block,.has-warning .control-label,.has-warning .radio,.has-warning .checkbox,.has-warning .radio-inline,.has-warning .checkbox-inline{color:#8a6d3b}.has-warning .form-control{border-color:#8a6d3b;-webkit-box-shadow:inset 0 1px 1px rgba(0,0,0,.075);box-shadow:inset 0 1px 1px rgba(0,0,0,.075)}.has-warning .form-control:focus{border-color:#66512c;-webkit-box-shadow:inset 0 1px 1px rgba(0,0,0,.075),0 0 6px #c0a16b;box-shadow:inset 0 1px 1px rgba(0,0,0,.075),0 0 6px #c0a16b}.has-warning .input-group-addon{color:#8a6d3b;border-color:#8a6d3b;background-color:#fcf8e3}.has-warning .form-control-feedback{color:#8a6d3b}.has-error .help-block,.has-error .control-label,.has-error .radio,.has-error .checkbox,.has-error .radio-inline,.has-error .checkbox-inline{color:#a94442}.has-error .form-control{border-color:#a94442;-webkit-box-shadow:inset 0 1px 1px rgba(0,0,0,.075);box-shadow:inset 0 1px 1px rgba(0,0,0,.075)}.has-error .form-control:focus{border-color:#843534;-webkit-box-shadow:inset 0 1px 1px rgba(0,0,0,.075),0 0 6px #ce8483;box-shadow:inset 0 1px 1px rgba(0,0,0,.075),0 0 6px #ce8483}.has-error .input-group-addon{color:#a94442;border-color:#a94442;background-color:#f2dede}.has-error .form-control-feedback{color:#a94442}.form-control-static{margin-bottom:0}.help-block{display:block;margin-top:5px;margin-bottom:10px;color:#737373}@media (min-width:768px){.form-inline .form-group{display:inline-block;margin-bottom:0;vertical-align:middle}.form-inline .form-control{display:inline-block;width:auto;vertical-align:middle}.form-inline .input-group>.form-control{width:100%}.form-inline .control-label{margin-bottom:0;vertical-align:middle}.form-inline .radio,.form-inline .checkbox{display:inline-block;margin-top:0;margin-bottom:0;padding-left:0;vertical-align:middle}.form-inline .radio input[type=radio],.form-inline .checkbox input[type=checkbox]{float:none;margin-left:0}.form-inline .has-feedback .form-control-feedback{top:0}}.form-horizontal .control-label,.form-horizontal .radio,.form-horizontal .checkbox,.form-horizontal .radio-inline,.form-horizontal .checkbox-inline{margin-top:0;margin-bottom:0;padding-top:7px}.form-horizontal .radio,.form-horizontal .checkbox{min-height:27px}.form-horizontal .form-group{margin-left:-15px;margin-right:-15px}.form-horizontal .form-control-static{padding-top:7px}@media (min-width:768px){.form-horizontal .control-label{text-align:right}}.form-horizontal .has-feedback .form-control-feedback{top:0;right:15px}.btn{display:inline-block;margin-bottom:0;font-weight:400;text-align:center;vertical-align:middle;cursor:pointer;background-image:none;border:1px solid transparent;white-space:nowrap;padding:6px 12px;font-size:14px;line-height:1.42857143;border-radius:4px;-webkit-user-select:none;-moz-user-select:none;-ms-user-select:none;user-select:none}.btn:focus,.btn:active:focus,.btn.active:focus{outline:thin dotted;outline:5px auto -webkit-focus-ring-color;outline-offset:-2px}.btn:hover,.btn:focus{color:#333;text-decoration:none}.btn:active,.btn.active{outline:0;background-image:none;-webkit-box-shadow:inset 0 3px 5px rgba(0,0,0,.125);box-shadow:inset 0 3px 5px rgba(0,0,0,.125)}.btn.disabled,.btn[disabled],fieldset[disabled] .btn{cursor:not-allowed;pointer-events:none;opacity:.65;filter:alpha(opacity=65);-webkit-box-shadow:none;box-shadow:none}.btn-default{color:#333;background-color:#fff;border-color:#ccc}.btn-default:hover,.btn-default:focus,.btn-default:active,.btn-default.active,.open .dropdown-toggle.btn-default{color:#333;background-color:#ebebeb;border-color:#adadad}.btn-default:active,.btn-default.active,.open .dropdown-toggle.btn-default{background-image:none}.btn-default.disabled,.btn-default[disabled],fieldset[disabled] .btn-default,.btn-default.disabled:hover,.btn-default[disabled]:hover,fieldset[disabled] .btn-default:hover,.btn-default.disabled:focus,.btn-default[disabled]:focus,fieldset[disabled] .btn-default:focus,.btn-default.disabled:active,.btn-default[disabled]:active,fieldset[disabled] .btn-default:active,.btn-default.disabled.active,.btn-default[disabled].active,fieldset[disabled] .btn-default.active{background-color:#fff;border-color:#ccc}.btn-default .badge{color:#fff;background-color:#333}.btn-primary{color:#fff;background-color:#428bca;border-color:#357ebd}.btn-primary:hover,.btn-primary:focus,.btn-primary:active,.btn-primary.active,.open .dropdown-toggle.btn-primary{color:#fff;background-color:#3276b1;border-color:#285e8e}.btn-primary:active,.btn-primary.active,.open .dropdown-toggle.btn-primary{background-image:none}.btn-primary.disabled,.btn-primary[disabled],fieldset[disabled] .btn-primary,.btn-primary.disabled:hover,.btn-primary[disabled]:hover,fieldset[disabled] .btn-primary:hover,.btn-primary.disabled:focus,.btn-primary[disabled]:focus,fieldset[disabled] .btn-primary:focus,.btn-primary.disabled:active,.btn-primary[disabled]:active,fieldset[disabled] .btn-primary:active,.btn-primary.disabled.active,.btn-primary[disabled].active,fieldset[disabled] .btn-primary.active{background-color:#428bca;border-color:#357ebd}.btn-primary .badge{color:#428bca;background-color:#fff}.btn-success{color:#fff;background-color:#5cb85c;border-color:#4cae4c}.btn-success:hover,.btn-success:focus,.btn-success:active,.btn-success.active,.open .dropdown-toggle.btn-success{color:#fff;background-color:#47a447;border-color:#398439}.btn-success:active,.btn-success.active,.open .dropdown-toggle.btn-success{background-image:none}.btn-success.disabled,.btn-success[disabled],fieldset[disabled] .btn-success,.btn-success.disabled:hover,.btn-success[disabled]:hover,fieldset[disabled] .btn-success:hover,.btn-success.disabled:focus,.btn-success[disabled]:focus,fieldset[disabled] .btn-success:focus,.btn-success.disabled:active,.btn-success[disabled]:active,fieldset[disabled] .btn-success:active,.btn-success.disabled.active,.btn-success[disabled].active,fieldset[disabled] .btn-success.active{background-color:#5cb85c;border-color:#4cae4c}.btn-success .badge{color:#5cb85c;background-color:#fff}.btn-info{color:#fff;background-color:#5bc0de;border-color:#46b8da}.btn-info:hover,.btn-info:focus,.btn-info:active,.btn-info.active,.open .dropdown-toggle.btn-info{color:#fff;background-color:#39b3d7;border-color:#269abc}.btn-info:active,.btn-info.active,.open .dropdown-toggle.btn-info{background-image:none}.btn-info.disabled,.btn-info[disabled],fieldset[disabled] .btn-info,.btn-info.disabled:hover,.btn-info[disabled]:hover,fieldset[disabled] .btn-info:hover,.btn-info.disabled:focus,.btn-info[disabled]:focus,fieldset[disabled] .btn-info:focus,.btn-info.disabled:active,.btn-info[disabled]:active,fieldset[disabled] .btn-info:active,.btn-info.disabled.active,.btn-info[disabled].active,fieldset[disabled] .btn-info.active{background-color:#5bc0de;border-color:#46b8da}.btn-info .badge{color:#5bc0de;background-color:#fff}.btn-warning{color:#fff;background-color:#f0ad4e;border-color:#eea236}.btn-warning:hover,.btn-warning:focus,.btn-warning:active,.btn-warning.active,.open .dropdown-toggle.btn-warning{color:#fff;background-color:#ed9c28;border-color:#d58512}.btn-warning:active,.btn-warning.active,.open .dropdown-toggle.btn-warning{background-image:none}.btn-warning.disabled,.btn-warning[disabled],fieldset[disabled] .btn-warning,.btn-warning.disabled:hover,.btn-warning[disabled]:hover,fieldset[disabled] .btn-warning:hover,.btn-warning.disabled:focus,.btn-warning[disabled]:focus,fieldset[disabled] .btn-warning:focus,.btn-warning.disabled:active,.btn-warning[disabled]:active,fieldset[disabled] .btn-warning:active,.btn-warning.disabled.active,.btn-warning[disabled].active,fieldset[disabled] .btn-warning.active{background-color:#f0ad4e;border-color:#eea236}.btn-warning .badge{color:#f0ad4e;background-color:#fff}.btn-danger{color:#fff;background-color:#d9534f;border-color:#d43f3a}.btn-danger:hover,.btn-danger:focus,.btn-danger:active,.btn-danger.active,.open .dropdown-toggle.btn-danger{color:#fff;background-color:#d2322d;border-color:#ac2925}.btn-danger:active,.btn-danger.active,.open .dropdown-toggle.btn-danger{background-image:none}.btn-danger.disabled,.btn-danger[disabled],fieldset[disabled] .btn-danger,.btn-danger.disabled:hover,.btn-danger[disabled]:hover,fieldset[disabled] .btn-danger:hover,.btn-danger.disabled:focus,.btn-danger[disabled]:focus,fieldset[disabled] .btn-danger:focus,.btn-danger.disabled:active,.btn-danger[disabled]:active,fieldset[disabled] .btn-danger:active,.btn-danger.disabled.active,.btn-danger[disabled].active,fieldset[disabled] .btn-danger.active{background-color:#d9534f;border-color:#d43f3a}.btn-danger .badge{color:#d9534f;background-color:#fff}.btn-link{color:#428bca;font-weight:400;cursor:pointer;border-radius:0}.btn-link,.btn-link:active,.btn-link[disabled],fieldset[disabled] .btn-link{background-color:transparent;-webkit-box-shadow:none;box-shadow:none}.btn-link,.btn-link:hover,.btn-link:focus,.btn-link:active{border-color:transparent}.btn-link:hover,.btn-link:focus{color:#2a6496;text-decoration:underline;background-color:transparent}.btn-link[disabled]:hover,fieldset[disabled] .btn-link:hover,.btn-link[disabled]:focus,fieldset[disabled] .btn-link:focus{color:#999;text-decoration:none}.btn-lg,.btn-group-lg>.btn{padding:10px 16px;font-size:18px;line-height:1.33;border-radius:6px}.btn-sm,.btn-group-sm>.btn{padding:5px 10px;font-size:12px;line-height:1.5;border-radius:3px}.btn-xs,.btn-group-xs>.btn{padding:1px 5px;font-size:12px;line-height:1.5;border-radius:3px}.btn-block{display:block;width:100%;padding-left:0;padding-right:0}.btn-block+.btn-block{margin-top:5px}input[type=submit].btn-block,input[type=reset].btn-block,input[type=button].btn-block{width:100%}.fade{opacity:0;-webkit-transition:opacity .15s linear;transition:opacity .15s linear}.fade.in{opacity:1}.collapse{display:none}.collapse.in{display:block}.collapsing{position:relative;height:0;overflow:hidden;-webkit-transition:height .35s ease;transition:height .35s ease}@font-face{font-family:'Glyphicons Halflings';src:url(../fonts/glyphicons-halflings-regular.eot);src:url(../fonts/glyphicons-halflings-regular.eot?#iefix) format('embedded-opentype'),url(../fonts/glyphicons-halflings-regular.woff) format('woff'),url(../fonts/glyphicons-halflings-regular.ttf) format('truetype'),url(../fonts/glyphicons-halflings-regular.svg#glyphicons_halflingsregular) format('svg')}.glyphicon{position:relative;top:1px;display:inline-block;font-family:'Glyphicons Halflings';font-style:normal;font-weight:400;line-height:1;-webkit-font-smoothing:antialiased;-moz-osx-font-smoothing:grayscale}.glyphicon-asterisk:before{content:"\2a"}.glyphicon-plus:before{content:"\2b"}.glyphicon-euro:before{content:"\20ac"}.glyphicon-minus:before{content:"\2212"}.glyphicon-cloud:before{content:"\2601"}.glyphicon-envelope:before{content:"\2709"}.glyphicon-pencil:before{content:"\270f"}.glyphicon-glass:before{content:"\e001"}.glyphicon-music:before{content:"\e002"}.glyphicon-search:before{content:"\e003"}.glyphicon-heart:before{content:"\e005"}.glyphicon-star:before{content:"\e006"}.glyphicon-star-empty:before{content:"\e007"}.glyphicon-user:before{content:"\e008"}.glyphicon-film:before{content:"\e009"}.glyphicon-th-large:before{content:"\e010"}.glyphicon-th:before{content:"\e011"}.glyphicon-th-list:before{content:"\e012"}.glyphicon-ok:before{content:"\e013"}.glyphicon-remove:before{content:"\e014"}.glyphicon-zoom-in:before{content:"\e015"}.glyphicon-zoom-out:before{content:"\e016"}.glyphicon-off:before{content:"\e017"}.glyphicon-signal:before{content:"\e018"}.glyphicon-cog:before{content:"\e019"}.glyphicon-trash:before{content:"\e020"}.glyphicon-home:before{content:"\e021"}.glyphicon-file:before{content:"\e022"}.glyphicon-time:before{content:"\e023"}.glyphicon-road:before{content:"\e024"}.glyphicon-download-alt:before{content:"\e025"}.glyphicon-download:before{content:"\e026"}.glyphicon-upload:before{content:"\e027"}.glyphicon-inbox:before{content:"\e028"}.glyphicon-play-circle:before{content:"\e029"}.glyphicon-repeat:before{content:"\e030"}.glyphicon-refresh:before{content:"\e031"}.glyphicon-list-alt:before{content:"\e032"}.glyphicon-lock:before{content:"\e033"}.glyphicon-flag:before{content:"\e034"}.glyphicon-headphones:before{content:"\e035"}.glyphicon-volume-off:before{content:"\e036"}.glyphicon-volume-down:before{content:"\e037"}.glyphicon-volume-up:before{content:"\e038"}.glyphicon-qrcode:before{content:"\e039"}.glyphicon-barcode:before{content:"\e040"}.glyphicon-tag:before{content:"\e041"}.glyphicon-tags:before{content:"\e042"}.glyphicon-book:before{content:"\e043"}.glyphicon-bookmark:before{content:"\e044"}.glyphicon-print:before{content:"\e045"}.glyphicon-camera:before{content:"\e046"}.glyphicon-font:before{content:"\e047"}.glyphicon-bold:before{content:"\e048"}.glyphicon-italic:before{content:"\e049"}.glyphicon-text-height:before{content:"\e050"}.glyphicon-text-width:before{content:"\e051"}.glyphicon-align-left:before{content:"\e052"}.glyphicon-align-center:before{content:"\e053"}.glyphicon-align-right:before{content:"\e054"}.glyphicon-align-justify:before{content:"\e055"}.glyphicon-list:before{content:"\e056"}.glyphicon-indent-left:before{content:"\e057"}.glyphicon-indent-right:before{content:"\e058"}.glyphicon-facetime-video:before{content:"\e059"}.glyphicon-picture:before{content:"\e060"}.glyphicon-map-marker:before{content:"\e062"}.glyphicon-adjust:before{content:"\e063"}.glyphicon-tint:before{content:"\e064"}.glyphicon-edit:before{content:"\e065"}.glyphicon-share:before{content:"\e066"}.glyphicon-check:before{content:"\e067"}.glyphicon-move:before{content:"\e068"}.glyphicon-step-backward:before{content:"\e069"}.glyphicon-fast-backward:before{content:"\e070"}.glyphicon-backward:before{content:"\e071"}.glyphicon-play:before{content:"\e072"}.glyphicon-pause:before{content:"\e073"}.glyphicon-stop:before{content:"\e074"}.glyphicon-forward:before{content:"\e075"}.glyphicon-fast-forward:before{content:"\e076"}.glyphicon-step-forward:before{content:"\e077"}.glyphicon-eject:before{content:"\e078"}.glyphicon-chevron-left:before{content:"\e079"}.glyphicon-chevron-right:before{content:"\e080"}.glyphicon-plus-sign:before{content:"\e081"}.glyphicon-minus-sign:before{content:"\e082"}.glyphicon-remove-sign:before{content:"\e083"}.glyphicon-ok-sign:before{content:"\e084"}.glyphicon-question-sign:before{content:"\e085"}.glyphicon-info-sign:before{content:"\e086"}.glyphicon-screenshot:before{content:"\e087"}.glyphicon-remove-circle:before{content:"\e088"}.glyphicon-ok-circle:before{content:"\e089"}.glyphicon-ban-circle:before{content:"\e090"}.glyphicon-arrow-left:before{content:"\e091"}.glyphicon-arrow-right:before{content:"\e092"}.glyphicon-arrow-up:before{content:"\e093"}.glyphicon-arrow-down:before{content:"\e094"}.glyphicon-share-alt:before{content:"\e095"}.glyphicon-resize-full:before{content:"\e096"}.glyphicon-resize-small:before{content:"\e097"}.glyphicon-exclamation-sign:before{content:"\e101"}.glyphicon-gift:before{content:"\e102"}.glyphicon-leaf:before{content:"\e103"}.glyphicon-fire:before{content:"\e104"}.glyphicon-eye-open:before{content:"\e105"}.glyphicon-eye-close:before{content:"\e106"}.glyphicon-warning-sign:before{content:"\e107"}.glyphicon-plane:before{content:"\e108"}.glyphicon-calendar:before{content:"\e109"}.glyphicon-random:before{content:"\e110"}.glyphicon-comment:before{content:"\e111"}.glyphicon-magnet:before{content:"\e112"}.glyphicon-chevron-up:before{content:"\e113"}.glyphicon-chevron-down:before{content:"\e114"}.glyphicon-retweet:before{content:"\e115"}.glyphicon-shopping-cart:before{content:"\e116"}.glyphicon-folder-close:before{content:"\e117"}.glyphicon-folder-open:before{content:"\e118"}.glyphicon-resize-vertical:before{content:"\e119"}.glyphicon-resize-horizontal:before{content:"\e120"}.glyphicon-hdd:before{content:"\e121"}.glyphicon-bullhorn:before{content:"\e122"}.glyphicon-bell:before{content:"\e123"}.glyphicon-certificate:before{content:"\e124"}.glyphicon-thumbs-up:before{content:"\e125"}.glyphicon-thumbs-down:before{content:"\e126"}.glyphicon-hand-right:before{content:"\e127"}.glyphicon-hand-left:before{content:"\e128"}.glyphicon-hand-up:before{content:"\e129"}.glyphicon-hand-down:before{content:"\e130"}.glyphicon-circle-arrow-right:before{content:"\e131"}.glyphicon-circle-arrow-left:before{content:"\e132"}.glyphicon-circle-arrow-up:before{content:"\e133"}.glyphicon-circle-arrow-down:before{content:"\e134"}.glyphicon-globe:before{content:"\e135"}.glyphicon-wrench:before{content:"\e136"}.glyphicon-tasks:before{content:"\e137"}.glyphicon-filter:before{content:"\e138"}.glyphicon-briefcase:before{content:"\e139"}.glyphicon-fullscreen:before{content:"\e140"}.glyphicon-dashboard:before{content:"\e141"}.glyphicon-paperclip:before{content:"\e142"}.glyphicon-heart-empty:before{content:"\e143"}.glyphicon-link:before{content:"\e144"}.glyphicon-phone:before{content:"\e145"}.glyphicon-pushpin:before{content:"\e146"}.glyphicon-usd:before{content:"\e148"}.glyphicon-gbp:before{content:"\e149"}.glyphicon-sort:before{content:"\e150"}.glyphicon-sort-by-alphabet:before{content:"\e151"}.glyphicon-sort-by-alphabet-alt:before{content:"\e152"}.glyphicon-sort-by-order:before{content:"\e153"}.glyphicon-sort-by-order-alt:before{content:"\e154"}.glyphicon-sort-by-attributes:before{content:"\e155"}.glyphicon-sort-by-attributes-alt:before{content:"\e156"}.glyphicon-unchecked:before{content:"\e157"}.glyphicon-expand:before{content:"\e158"}.glyphicon-collapse-down:before{content:"\e159"}.glyphicon-collapse-up:before{content:"\e160"}.glyphicon-log-in:before{content:"\e161"}.glyphicon-flash:before{content:"\e162"}.glyphicon-log-out:before{content:"\e163"}.glyphicon-new-window:before{content:"\e164"}.glyphicon-record:before{content:"\e165"}.glyphicon-save:before{content:"\e166"}.glyphicon-open:before{content:"\e167"}.glyphicon-saved:before{content:"\e168"}.glyphicon-import:before{content:"\e169"}.glyphicon-export:before{content:"\e170"}.glyphicon-send:before{content:"\e171"}.glyphicon-floppy-disk:before{content:"\e172"}.glyphicon-floppy-saved:before{content:"\e173"}.glyphicon-floppy-remove:before{content:"\e174"}.glyphicon-floppy-save:before{content:"\e175"}.glyphicon-floppy-open:before{content:"\e176"}.glyphicon-credit-card:before{content:"\e177"}.glyphicon-transfer:before{content:"\e178"}.glyphicon-cutlery:before{content:"\e179"}.glyphicon-header:before{content:"\e180"}.glyphicon-compressed:before{content:"\e181"}.glyphicon-earphone:before{content:"\e182"}.glyphicon-phone-alt:before{content:"\e183"}.glyphicon-tower:before{content:"\e184"}.glyphicon-stats:before{content:"\e185"}.glyphicon-sd-video:before{content:"\e186"}.glyphicon-hd-video:before{content:"\e187"}.glyphicon-subtitles:before{content:"\e188"}.glyphicon-sound-stereo:before{content:"\e189"}.glyphicon-sound-dolby:before{content:"\e190"}.glyphicon-sound-5-1:before{content:"\e191"}.glyphicon-sound-6-1:before{content:"\e192"}.glyphicon-sound-7-1:before{content:"\e193"}.glyphicon-copyright-mark:before{content:"\e194"}.glyphicon-registration-mark:before{content:"\e195"}.glyphicon-cloud-download:before{content:"\e197"}.glyphicon-cloud-upload:before{content:"\e198"}.glyphicon-tree-conifer:before{content:"\e199"}.glyphicon-tree-deciduous:before{content:"\e200"}.caret{display:inline-block;width:0;height:0;margin-left:2px;vertical-align:middle;border-top:4px solid;border-right:4px solid transparent;border-left:4px solid transparent}.dropdown{position:relative}.dropdown-toggle:focus{outline:0}.dropdown-menu{position:absolute;top:100%;left:0;z-index:1000;display:none;float:left;min-width:160px;padding:5px 0;margin:2px 0 0;list-style:none;font-size:14px;background-color:#fff;border:1px solid #ccc;border:1px solid rgba(0,0,0,.15);border-radius:4px;-webkit-box-shadow:0 6px 12px rgba(0,0,0,.175);box-shadow:0 6px 12px rgba(0,0,0,.175);background-clip:padding-box}.dropdown-menu.pull-right{right:0;left:auto}.dropdown-menu .divider{height:1px;margin:9px 0;overflow:hidden;background-color:#e5e5e5}.dropdown-menu>li>a{display:block;padding:3px 20px;clear:both;font-weight:400;line-height:1.42857143;color:#333;white-space:nowrap}.dropdown-menu>li>a:hover,.dropdown-menu>li>a:focus{text-decoration:none;color:#262626;background-color:#f5f5f5}.dropdown-menu>.active>a,.dropdown-menu>.active>a:hover,.dropdown-menu>.active>a:focus{color:#fff;text-decoration:none;outline:0;background-color:#428bca}.dropdown-menu>.disabled>a,.dropdown-menu>.disabled>a:hover,.dropdown-menu>.disabled>a:focus{color:#999}.dropdown-menu>.disabled>a:hover,.dropdown-menu>.disabled>a:focus{text-decoration:none;background-color:transparent;background-image:none;filter:progid:DXImageTransform.Microsoft.gradient(enabled=false);cursor:not-allowed}.open>.dropdown-menu{display:block}.open>a{outline:0}.dropdown-menu-right{left:auto;right:0}.dropdown-menu-left{left:0;right:auto}.dropdown-header{display:block;padding:3px 20px;font-size:12px;line-height:1.42857143;color:#999}.dropdown-backdrop{position:fixed;left:0;right:0;bottom:0;top:0;z-index:990}.pull-right>.dropdown-menu{right:0;left:auto}.dropup .caret,.navbar-fixed-bottom .dropdown .caret{border-top:0;border-bottom:4px solid;content:""}.dropup .dropdown-menu,.navbar-fixed-bottom .dropdown .dropdown-menu{top:auto;bottom:100%;margin-bottom:1px}@media (min-width:768px){.navbar-right .dropdown-menu{left:auto;right:0}.navbar-right .dropdown-menu-left{left:0;right:auto}}.btn-group,.btn-group-vertical{position:relative;display:inline-block;vertical-align:middle}.btn-group>.btn,.btn-group-vertical>.btn{position:relative;float:left}.btn-group>.btn:hover,.btn-group-vertical>.btn:hover,.btn-group>.btn:focus,.btn-group-vertical>.btn:focus,.btn-group>.btn:active,.btn-group-vertical>.btn:active,.btn-group>.btn.active,.btn-group-vertical>.btn.active{z-index:2}.btn-group>.btn:focus,.btn-group-vertical>.btn:focus{outline:0}.btn-group .btn+.btn,.btn-group .btn+.btn-group,.btn-group .btn-group+.btn,.btn-group .btn-group+.btn-group{margin-left:-1px}.btn-toolbar{margin-left:-5px}.btn-toolbar .btn-group,.btn-toolbar .input-group{float:left}.btn-toolbar>.btn,.btn-toolbar>.btn-group,.btn-toolbar>.input-group{margin-left:5px}.btn-group>.btn:not(:first-child):not(:last-child):not(.dropdown-toggle){border-radius:0}.btn-group>.btn:first-child{margin-left:0}.btn-group>.btn:first-child:not(:last-child):not(.dropdown-toggle){border-bottom-right-radius:0;border-top-right-radius:0}.btn-group>.btn:last-child:not(:first-child),.btn-group>.dropdown-toggle:not(:first-child){border-bottom-left-radius:0;border-top-left-radius:0}.btn-group>.btn-group{float:left}.btn-group>.btn-group:not(:first-child):not(:last-child)>.btn{border-radius:0}.btn-group>.btn-group:first-child>.btn:last-child,.btn-group>.btn-group:first-child>.dropdown-toggle{border-bottom-right-radius:0;border-top-right-radius:0}.btn-group>.btn-group:last-child>.btn:first-child{border-bottom-left-radius:0;border-top-left-radius:0}.btn-group .dropdown-toggle:active,.btn-group.open .dropdown-toggle{outline:0}.btn-group>.btn+.dropdown-toggle{padding-left:8px;padding-right:8px}.btn-group>.btn-lg+.dropdown-toggle{padding-left:12px;padding-right:12px}.btn-group.open .dropdown-toggle{-webkit-box-shadow:inset 0 3px 5px rgba(0,0,0,.125);box-shadow:inset 0 3px 5px rgba(0,0,0,.125)}.btn-group.open .dropdown-toggle.btn-link{-webkit-box-shadow:none;box-shadow:none}.btn .caret{margin-left:0}.btn-lg .caret{border-width:5px 5px 0;border-bottom-width:0}.dropup .btn-lg .caret{border-width:0 5px 5px}.btn-group-vertical>.btn,.btn-group-vertical>.btn-group,.btn-group-vertical>.btn-group>.btn{display:block;float:none;width:100%;max-width:100%}.btn-group-vertical>.btn-group>.btn{float:none}.btn-group-vertical>.btn+.btn,.btn-group-vertical>.btn+.btn-group,.btn-group-vertical>.btn-group+.btn,.btn-group-vertical>.btn-group+.btn-group{margin-top:-1px;margin-left:0}.btn-group-vertical>.btn:not(:first-child):not(:last-child){border-radius:0}.btn-group-vertical>.btn:first-child:not(:last-child){border-top-right-radius:4px;border-bottom-right-radius:0;border-bottom-left-radius:0}.btn-group-vertical>.btn:last-child:not(:first-child){border-bottom-left-radius:4px;border-top-right-radius:0;border-top-left-radius:0}.btn-group-vertical>.btn-group:not(:first-child):not(:last-child)>.btn{border-radius:0}.btn-group-vertical>.btn-group:first-child:not(:last-child)>.btn:last-child,.btn-group-vertical>.btn-group:first-child:not(:last-child)>.dropdown-toggle{border-bottom-right-radius:0;border-bottom-left-radius:0}.btn-group-vertical>.btn-group:last-child:not(:first-child)>.btn:first-child{border-top-right-radius:0;border-top-left-radius:0}.btn-group-justified{display:table;width:100%;table-layout:fixed;border-collapse:separate}.btn-group-justified>.btn,.btn-group-justified>.btn-group{float:none;display:table-cell;width:1%}.btn-group-justified>.btn-group .btn{width:100%}[data-toggle=buttons]>.btn>input[type=radio],[data-toggle=buttons]>.btn>input[type=checkbox]{display:none}.input-group{position:relative;display:table;border-collapse:separate}.input-group[class*=col-]{float:none;padding-left:0;padding-right:0}.input-group .form-control{position:relative;z-index:2;float:left;width:100%;margin-bottom:0}.input-group-lg>.form-control,.input-group-lg>.input-group-addon,.input-group-lg>.input-group-btn>.btn{height:46px;padding:10px 16px;font-size:18px;line-height:1.33;border-radius:6px}select.input-group-lg>.form-control,select.input-group-lg>.input-group-addon,select.input-group-lg>.input-group-btn>.btn{height:46px;line-height:46px}textarea.input-group-lg>.form-control,textarea.input-group-lg>.input-group-addon,textarea.input-group-lg>.input-group-btn>.btn,select[multiple].input-group-lg>.form-control,select[multiple].input-group-lg>.input-group-addon,select[multiple].input-group-lg>.input-group-btn>.btn{height:auto}.input-group-sm>.form-control,.input-group-sm>.input-group-addon,.input-group-sm>.input-group-btn>.btn{height:30px;padding:5px 10px;font-size:12px;line-height:1.5;border-radius:3px}select.input-group-sm>.form-control,select.input-group-sm>.input-group-addon,select.input-group-sm>.input-group-btn>.btn{height:30px;line-height:30px}textarea.input-group-sm>.form-control,textarea.input-group-sm>.input-group-addon,textarea.input-group-sm>.input-group-btn>.btn,select[multiple].input-group-sm>.form-control,select[multiple].input-group-sm>.input-group-addon,select[multiple].input-group-sm>.input-group-btn>.btn{height:auto}.input-group-addon,.input-group-btn,.input-group .form-control{display:table-cell}.input-group-addon:not(:first-child):not(:last-child),.input-group-btn:not(:first-child):not(:last-child),.input-group .form-control:not(:first-child):not(:last-child){border-radius:0}.input-group-addon,.input-group-btn{width:1%;white-space:nowrap;vertical-align:middle}.input-group-addon{padding:6px 12px;font-size:14px;font-weight:400;line-height:1;color:#555;text-align:center;background-color:#eee;border:1px solid #ccc;border-radius:4px}.input-group-addon.input-sm{padding:5px 10px;font-size:12px;border-radius:3px}.input-group-addon.input-lg{padding:10px 16px;font-size:18px;border-radius:6px}.input-group-addon input[type=radio],.input-group-addon input[type=checkbox]{margin-top:0}.input-group .form-control:first-child,.input-group-addon:first-child,.input-group-btn:first-child>.btn,.input-group-btn:first-child>.btn-group>.btn,.input-group-btn:first-child>.dropdown-toggle,.input-group-btn:last-child>.btn:not(:last-child):not(.dropdown-toggle),.input-group-btn:last-child>.btn-group:not(:last-child)>.btn{border-bottom-right-radius:0;border-top-right-radius:0}.input-group-addon:first-child{border-right:0}.input-group .form-control:last-child,.input-group-addon:last-child,.input-group-btn:last-child>.btn,.input-group-btn:last-child>.btn-group>.btn,.input-group-btn:last-child>.dropdown-toggle,.input-group-btn:first-child>.btn:not(:first-child),.input-group-btn:first-child>.btn-group:not(:first-child)>.btn{border-bottom-left-radius:0;border-top-left-radius:0}.input-group-addon:last-child{border-left:0}.input-group-btn{position:relative;font-size:0;white-space:nowrap}.input-group-btn>.btn{position:relative}.input-group-btn>.btn+.btn{margin-left:-1px}.input-group-btn>.btn:hover,.input-group-btn>.btn:focus,.input-group-btn>.btn:active{z-index:2}.input-group-btn:first-child>.btn,.input-group-btn:first-child>.btn-group{margin-right:-1px}.input-group-btn:last-child>.btn,.input-group-btn:last-child>.btn-group{margin-left:-1px}.nav{margin-bottom:0;padding-left:0;list-style:none}.nav>li{position:relative;display:block}.nav>li>a{position:relative;display:block;padding:10px 15px}.nav>li>a:hover,.nav>li>a:focus{text-decoration:none;background-color:#eee}.nav>li.disabled>a{color:#999}.nav>li.disabled>a:hover,.nav>li.disabled>a:focus{color:#999;text-decoration:none;background-color:transparent;cursor:not-allowed}.nav .open>a,.nav .open>a:hover,.nav .open>a:focus{background-color:#eee;border-color:#428bca}.nav .nav-divider{height:1px;margin:9px 0;overflow:hidden;background-color:#e5e5e5}.nav>li>a>img{max-width:none}.nav-tabs{border-bottom:1px solid #ddd}.nav-tabs>li{float:left;margin-bottom:-1px}.nav-tabs>li>a{margin-right:2px;line-height:1.42857143;border:1px solid transparent;border-radius:4px 4px 0 0}.nav-tabs>li>a:hover{border-color:#eee #eee #ddd}.nav-tabs>li.active>a,.nav-tabs>li.active>a:hover,.nav-tabs>li.active>a:focus{color:#555;background-color:#fff;border:1px solid #ddd;border-bottom-color:transparent;cursor:default}.nav-tabs.nav-justified{width:100%;border-bottom:0}.nav-tabs.nav-justified>li{float:none}.nav-tabs.nav-justified>li>a{text-align:center;margin-bottom:5px}.nav-tabs.nav-justified>.dropdown .dropdown-menu{top:auto;left:auto}@media (min-width:768px){.nav-tabs.nav-justified>li{display:table-cell;width:1%}.nav-tabs.nav-justified>li>a{margin-bottom:0}}.nav-tabs.nav-justified>li>a{margin-right:0;border-radius:4px}.nav-tabs.nav-justified>.active>a,.nav-tabs.nav-justified>.active>a:hover,.nav-tabs.nav-justified>.active>a:focus{border:1px solid #ddd}@media (min-width:768px){.nav-tabs.nav-justified>li>a{border-bottom:1px solid #ddd;border-radius:4px 4px 0 0}.nav-tabs.nav-justified>.active>a,.nav-tabs.nav-justified>.active>a:hover,.nav-tabs.nav-justified>.active>a:focus{border-bottom-color:#fff}}.nav-pills>li{float:left}.nav-pills>li>a{border-radius:4px}.nav-pills>li+li{margin-left:2px}.nav-pills>li.active>a,.nav-pills>li.active>a:hover,.nav-pills>li.active>a:focus{color:#fff;background-color:#428bca}.nav-stacked>li{float:none}.nav-stacked>li+li{margin-top:2px;margin-left:0}.nav-justified{width:100%}.nav-justified>li{float:none}.nav-justified>li>a{text-align:center;margin-bottom:5px}.nav-justified>.dropdown .dropdown-menu{top:auto;left:auto}@media (min-width:768px){.nav-justified>li{display:table-cell;width:1%}.nav-justified>li>a{margin-bottom:0}}.nav-tabs-justified{border-bottom:0}.nav-tabs-justified>li>a{margin-right:0;border-radius:4px}.nav-tabs-justified>.active>a,.nav-tabs-justified>.active>a:hover,.nav-tabs-justified>.active>a:focus{border:1px solid #ddd}@media (min-width:768px){.nav-tabs-justified>li>a{border-bottom:1px solid #ddd;border-radius:4px 4px 0 0}.nav-tabs-justified>.active>a,.nav-tabs-justified>.active>a:hover,.nav-tabs-justified>.active>a:focus{border-bottom-color:#fff}}.tab-content>.tab-pane{display:none}.tab-content>.active{display:block}.nav-tabs .dropdown-menu{margin-top:-1px;border-top-right-radius:0;border-top-left-radius:0}.navbar{position:relative;min-height:50px;margin-bottom:20px;border:1px solid transparent}@media (min-width:768px){.navbar{border-radius:4px}}@media (min-width:768px){.navbar-header{float:left}}.navbar-collapse{max-height:340px;overflow-x:visible;padding-right:15px;padding-left:15px;border-top:1px solid transparent;box-shadow:inset 0 1px 0 rgba(255,255,255,.1);-webkit-overflow-scrolling:touch}.navbar-collapse.in{overflow-y:auto}@media (min-width:768px){.navbar-collapse{width:auto;border-top:0;box-shadow:none}.navbar-collapse.collapse{display:block!important;height:auto!important;padding-bottom:0;overflow:visible!important}.navbar-collapse.in{overflow-y:visible}.navbar-fixed-top .navbar-collapse,.navbar-static-top .navbar-collapse,.navbar-fixed-bottom .navbar-collapse{padding-left:0;padding-right:0}}.container>.navbar-header,.container-fluid>.navbar-header,.container>.navbar-collapse,.container-fluid>.navbar-collapse{margin-right:-15px;margin-left:-15px}@media (min-width:768px){.container>.navbar-header,.container-fluid>.navbar-header,.container>.navbar-collapse,.container-fluid>.navbar-collapse{margin-right:0;margin-left:0}}.navbar-static-top{z-index:1000;border-width:0 0 1px}@media (min-width:768px){.navbar-static-top{border-radius:0}}.navbar-fixed-top,.navbar-fixed-bottom{position:fixed;right:0;left:0;z-index:1030}@media (min-width:768px){.navbar-fixed-top,.navbar-fixed-bottom{border-radius:0}}.navbar-fixed-top{top:0;border-width:0 0 1px}.navbar-fixed-bottom{bottom:0;margin-bottom:0;border-width:1px 0 0}.navbar-brand{float:left;padding:15px;font-size:18px;line-height:20px;height:50px}.navbar-brand:hover,.navbar-brand:focus{text-decoration:none}@media (min-width:768px){.navbar>.container .navbar-brand,.navbar>.container-fluid .navbar-brand{margin-left:-15px}}.navbar-toggle{position:relative;float:right;margin-right:15px;padding:9px 10px;margin-top:8px;margin-bottom:8px;background-color:transparent;background-image:none;border:1px solid transparent;border-radius:4px}.navbar-toggle:focus{outline:0}.navbar-toggle .icon-bar{display:block;width:22px;height:2px;border-radius:1px}.navbar-toggle .icon-bar+.icon-bar{margin-top:4px}@media (min-width:768px){.navbar-toggle{display:none}}.navbar-nav{margin:7.5px -15px}.navbar-nav>li>a{padding-top:10px;padding-bottom:10px;line-height:20px}@media (max-width:767px){.navbar-nav .open .dropdown-menu{position:static;float:none;width:auto;margin-top:0;background-color:transparent;border:0;box-shadow:none}.navbar-nav .open .dropdown-menu>li>a,.navbar-nav .open .dropdown-menu .dropdown-header{padding:5px 15px 5px 25px}.navbar-nav .open .dropdown-menu>li>a{line-height:20px}.navbar-nav .open .dropdown-menu>li>a:hover,.navbar-nav .open .dropdown-menu>li>a:focus{background-image:none}}@media (min-width:768px){.navbar-nav{float:left;margin:0}.navbar-nav>li{float:left}.navbar-nav>li>a{padding-top:15px;padding-bottom:15px}.navbar-nav.navbar-right:last-child{margin-right:-15px}}@media (min-width:768px){.navbar-left{float:left!important}.navbar-right{float:right!important}}.navbar-form{margin-left:-15px;margin-right:-15px;padding:10px 15px;border-top:1px solid transparent;border-bottom:1px solid transparent;-webkit-box-shadow:inset 0 1px 0 rgba(255,255,255,.1),0 1px 0 rgba(255,255,255,.1);box-shadow:inset 0 1px 0 rgba(255,255,255,.1),0 1px 0 rgba(255,255,255,.1);margin-top:8px;margin-bottom:8px}@media (min-width:768px){.navbar-form .form-group{display:inline-block;margin-bottom:0;vertical-align:middle}.navbar-form .form-control{display:inline-block;width:auto;vertical-align:middle}.navbar-form .input-group>.form-control{width:100%}.navbar-form .control-label{margin-bottom:0;vertical-align:middle}.navbar-form .radio,.navbar-form .checkbox{display:inline-block;margin-top:0;margin-bottom:0;padding-left:0;vertical-align:middle}.navbar-form .radio input[type=radio],.navbar-form .checkbox input[type=checkbox]{float:none;margin-left:0}.navbar-form .has-feedback .form-control-feedback{top:0}}@media (max-width:767px){.navbar-form .form-group{margin-bottom:5px}}@media (min-width:768px){.navbar-form{width:auto;border:0;margin-left:0;margin-right:0;padding-top:0;padding-bottom:0;-webkit-box-shadow:none;box-shadow:none}.navbar-form.navbar-right:last-child{margin-right:-15px}}.navbar-nav>li>.dropdown-menu{margin-top:0;border-top-right-radius:0;border-top-left-radius:0}.navbar-fixed-bottom .navbar-nav>li>.dropdown-menu{border-bottom-right-radius:0;border-bottom-left-radius:0}.navbar-btn{margin-top:8px;margin-bottom:8px}.navbar-btn.btn-sm{margin-top:10px;margin-bottom:10px}.navbar-btn.btn-xs{margin-top:14px;margin-bottom:14px}.navbar-text{margin-top:15px;margin-bottom:15px}@media (min-width:768px){.navbar-text{float:left;margin-left:15px;margin-right:15px}.navbar-text.navbar-right:last-child{margin-right:0}}.navbar-default{background-color:#f8f8f8;border-color:#e7e7e7}.navbar-default .navbar-brand{color:#777}.navbar-default .navbar-brand:hover,.navbar-default .navbar-brand:focus{color:#5e5e5e;background-color:transparent}.navbar-default .navbar-text{color:#777}.navbar-default .navbar-nav>li>a{color:#777}.navbar-default .navbar-nav>li>a:hover,.navbar-default .navbar-nav>li>a:focus{color:#333;background-color:transparent}.navbar-default .navbar-nav>.active>a,.navbar-default .navbar-nav>.active>a:hover,.navbar-default .navbar-nav>.active>a:focus{color:#555;background-color:#e7e7e7}.navbar-default .navbar-nav>.disabled>a,.navbar-default .navbar-nav>.disabled>a:hover,.navbar-default .navbar-nav>.disabled>a:focus{color:#ccc;background-color:transparent}.navbar-default .navbar-toggle{border-color:#ddd}.navbar-default .navbar-toggle:hover,.navbar-default .navbar-toggle:focus{background-color:#ddd}.navbar-default .navbar-toggle .icon-bar{background-color:#888}.navbar-default .navbar-collapse,.navbar-default .navbar-form{border-color:#e7e7e7}.navbar-default .navbar-nav>.open>a,.navbar-default .navbar-nav>.open>a:hover,.navbar-default .navbar-nav>.open>a:focus{background-color:#e7e7e7;color:#555}@media (max-width:767px){.navbar-default .navbar-nav .open .dropdown-menu>li>a{color:#777}.navbar-default .navbar-nav .open .dropdown-menu>li>a:hover,.navbar-default .navbar-nav .open .dropdown-menu>li>a:focus{color:#333;background-color:transparent}.navbar-default .navbar-nav .open .dropdown-menu>.active>a,.navbar-default .navbar-nav .open .dropdown-menu>.active>a:hover,.navbar-default .navbar-nav .open .dropdown-menu>.active>a:focus{color:#555;background-color:#e7e7e7}.navbar-default .navbar-nav .open .dropdown-menu>.disabled>a,.navbar-default .navbar-nav .open .dropdown-menu>.disabled>a:hover,.navbar-default .navbar-nav .open .dropdown-menu>.disabled>a:focus{color:#ccc;background-color:transparent}}.navbar-default .navbar-link{color:#777}.navbar-default .navbar-link:hover{color:#333}.navbar-inverse{background-color:#222;border-color:#080808}.navbar-inverse .navbar-brand{color:#999}.navbar-inverse .navbar-brand:hover,.navbar-inverse .navbar-brand:focus{color:#fff;background-color:transparent}.navbar-inverse .navbar-text{color:#999}.navbar-inverse .navbar-nav>li>a{color:#999}.navbar-inverse .navbar-nav>li>a:hover,.navbar-inverse .navbar-nav>li>a:focus{color:#fff;background-color:transparent}.navbar-inverse .navbar-nav>.active>a,.navbar-inverse .navbar-nav>.active>a:hover,.navbar-inverse .navbar-nav>.active>a:focus{color:#fff;background-color:#080808}.navbar-inverse .navbar-nav>.disabled>a,.navbar-inverse .navbar-nav>.disabled>a:hover,.navbar-inverse .navbar-nav>.disabled>a:focus{color:#444;background-color:transparent}.navbar-inverse .navbar-toggle{border-color:#333}.navbar-inverse .navbar-toggle:hover,.navbar-inverse .navbar-toggle:focus{background-color:#333}.navbar-inverse .navbar-toggle .icon-bar{background-color:#fff}.navbar-inverse .navbar-collapse,.navbar-inverse .navbar-form{border-color:#101010}.navbar-inverse .navbar-nav>.open>a,.navbar-inverse .navbar-nav>.open>a:hover,.navbar-inverse .navbar-nav>.open>a:focus{background-color:#080808;color:#fff}@media (max-width:767px){.navbar-inverse .navbar-nav .open .dropdown-menu>.dropdown-header{border-color:#080808}.navbar-inverse .navbar-nav .open .dropdown-menu .divider{background-color:#080808}.navbar-inverse .navbar-nav .open .dropdown-menu>li>a{color:#999}.navbar-inverse .navbar-nav .open .dropdown-menu>li>a:hover,.navbar-inverse .navbar-nav .open .dropdown-menu>li>a:focus{color:#fff;background-color:transparent}.navbar-inverse .navbar-nav .open .dropdown-menu>.active>a,.navbar-inverse .navbar-nav .open .dropdown-menu>.active>a:hover,.navbar-inverse .navbar-nav .open .dropdown-menu>.active>a:focus{color:#fff;background-color:#080808}.navbar-inverse .navbar-nav .open .dropdown-menu>.disabled>a,.navbar-inverse .navbar-nav .open .dropdown-menu>.disabled>a:hover,.navbar-inverse .navbar-nav .open .dropdown-menu>.disabled>a:focus{color:#444;background-color:transparent}}.navbar-inverse .navbar-link{color:#999}.navbar-inverse .navbar-link:hover{color:#fff}.breadcrumb{padding:8px 15px;margin-bottom:20px;list-style:none;background-color:#f5f5f5;border-radius:4px}.breadcrumb>li{display:inline-block}.breadcrumb>li+li:before{content:"/\00a0";padding:0 5px;color:#ccc}.breadcrumb>.active{color:#999}.pagination{display:inline-block;padding-left:0;margin:20px 0;border-radius:4px}.pagination>li{display:inline}.pagination>li>a,.pagination>li>span{position:relative;float:left;padding:6px 12px;line-height:1.42857143;text-decoration:none;color:#428bca;background-color:#fff;border:1px solid #ddd;margin-left:-1px}.pagination>li:first-child>a,.pagination>li:first-child>span{margin-left:0;border-bottom-left-radius:4px;border-top-left-radius:4px}.pagination>li:last-child>a,.pagination>li:last-child>span{border-bottom-right-radius:4px;border-top-right-radius:4px}.pagination>li>a:hover,.pagination>li>span:hover,.pagination>li>a:focus,.pagination>li>span:focus{color:#2a6496;background-color:#eee;border-color:#ddd}.pagination>.active>a,.pagination>.active>span,.pagination>.active>a:hover,.pagination>.active>span:hover,.pagination>.active>a:focus,.pagination>.active>span:focus{z-index:2;color:#fff;background-color:#428bca;border-color:#428bca;cursor:default}.pagination>.disabled>span,.pagination>.disabled>span:hover,.pagination>.disabled>span:focus,.pagination>.disabled>a,.pagination>.disabled>a:hover,.pagination>.disabled>a:focus{color:#999;background-color:#fff;border-color:#ddd;cursor:not-allowed}.pagination-lg>li>a,.pagination-lg>li>span{padding:10px 16px;font-size:18px}.pagination-lg>li:first-child>a,.pagination-lg>li:first-child>span{border-bottom-left-radius:6px;border-top-left-radius:6px}.pagination-lg>li:last-child>a,.pagination-lg>li:last-child>span{border-bottom-right-radius:6px;border-top-right-radius:6px}.pagination-sm>li>a,.pagination-sm>li>span{padding:5px 10px;font-size:12px}.pagination-sm>li:first-child>a,.pagination-sm>li:first-child>span{border-bottom-left-radius:3px;border-top-left-radius:3px}.pagination-sm>li:last-child>a,.pagination-sm>li:last-child>span{border-bottom-right-radius:3px;border-top-right-radius:3px}.pager{padding-left:0;margin:20px 0;list-style:none;text-align:center}.pager li{display:inline}.pager li>a,.pager li>span{display:inline-block;padding:5px 14px;background-color:#fff;border:1px solid #ddd;border-radius:15px}.pager li>a:hover,.pager li>a:focus{text-decoration:none;background-color:#eee}.pager .next>a,.pager .next>span{float:right}.pager .previous>a,.pager .previous>span{float:left}.pager .disabled>a,.pager .disabled>a:hover,.pager .disabled>a:focus,.pager .disabled>span{color:#999;background-color:#fff;cursor:not-allowed}.label{display:inline;padding:.2em .6em .3em;font-size:75%;font-weight:700;line-height:1;color:#fff;text-align:center;white-space:nowrap;vertical-align:baseline;border-radius:.25em}.label[href]:hover,.label[href]:focus{color:#fff;text-decoration:none;cursor:pointer}.label:empty{display:none}.btn .label{position:relative;top:-1px}.label-default{background-color:#999}.label-default[href]:hover,.label-default[href]:focus{background-color:gray}.label-primary{background-color:#428bca}.label-primary[href]:hover,.label-primary[href]:focus{background-color:#3071a9}.label-success{background-color:#5cb85c}.label-success[href]:hover,.label-success[href]:focus{background-color:#449d44}.label-info{background-color:#5bc0de}.label-info[href]:hover,.label-info[href]:focus{background-color:#31b0d5}.label-warning{background-color:#f0ad4e}.label-warning[href]:hover,.label-warning[href]:focus{background-color:#ec971f}.label-danger{background-color:#d9534f}.label-danger[href]:hover,.label-danger[href]:focus{background-color:#c9302c}.badge{display:inline-block;min-width:10px;padding:3px 7px;font-size:12px;font-weight:700;color:#fff;line-height:1;vertical-align:baseline;white-space:nowrap;text-align:center;background-color:#999;border-radius:10px}.badge:empty{display:none}.btn .badge{position:relative;top:-1px}.btn-xs .badge{top:0;padding:1px 5px}a.badge:hover,a.badge:focus{color:#fff;text-decoration:none;cursor:pointer}a.list-group-item.active>.badge,.nav-pills>.active>a>.badge{color:#428bca;background-color:#fff}.nav-pills>li>a>.badge{margin-left:3px}.jumbotron{padding:30px;margin-bottom:30px;color:inherit;background-color:#eee}.jumbotron h1,.jumbotron .h1{color:inherit}.jumbotron p{margin-bottom:15px;font-size:21px;font-weight:200}.container .jumbotron{border-radius:6px}.jumbotron .container{max-width:100%}@media screen and (min-width:768px){.jumbotron{padding-top:48px;padding-bottom:48px}.container .jumbotron{padding-left:60px;padding-right:60px}.jumbotron h1,.jumbotron .h1{font-size:63px}}.thumbnail{display:block;padding:4px;margin-bottom:20px;line-height:1.42857143;background-color:#fff;border:1px solid #ddd;border-radius:4px;-webkit-transition:all .2s ease-in-out;transition:all .2s ease-in-out}.thumbnail>img,.thumbnail a>img{margin-left:auto;margin-right:auto}a.thumbnail:hover,a.thumbnail:focus,a.thumbnail.active{border-color:#428bca}.thumbnail .caption{padding:9px;color:#333}.alert{padding:15px;margin-bottom:20px;border:1px solid transparent;border-radius:4px}.alert h4{margin-top:0;color:inherit}.alert .alert-link{font-weight:700}.alert>p,.alert>ul{margin-bottom:0}.alert>p+p{margin-top:5px}.alert-dismissable{padding-right:35px}.alert-dismissable .close{position:relative;top:-2px;right:-21px;color:inherit}.alert-success{background-color:#dff0d8;border-color:#d6e9c6;color:#3c763d}.alert-success hr{border-top-color:#c9e2b3}.alert-success .alert-link{color:#2b542c}.alert-info{background-color:#d9edf7;border-color:#bce8f1;color:#31708f}.alert-info hr{border-top-color:#a6e1ec}.alert-info .alert-link{color:#245269}.alert-warning{background-color:#fcf8e3;border-color:#faebcc;color:#8a6d3b}.alert-warning hr{border-top-color:#f7e1b5}.alert-warning .alert-link{color:#66512c}.alert-danger{background-color:#f2dede;border-color:#ebccd1;color:#a94442}.alert-danger hr{border-top-color:#e4b9c0}.alert-danger .alert-link{color:#843534}@-webkit-keyframes progress-bar-stripes{from{background-position:40px 0}to{background-position:0 0}}@keyframes progress-bar-stripes{from{background-position:40px 0}to{background-position:0 0}}.progress{overflow:hidden;height:20px;margin-bottom:20px;background-color:#f5f5f5;border-radius:4px;-webkit-box-shadow:inset 0 1px 2px rgba(0,0,0,.1);box-shadow:inset 0 1px 2px rgba(0,0,0,.1)}.progress-bar{float:left;width:0;height:100%;font-size:12px;line-height:20px;color:#fff;text-align:center;background-color:#428bca;-webkit-box-shadow:inset 0 -1px 0 rgba(0,0,0,.15);box-shadow:inset 0 -1px 0 rgba(0,0,0,.15);-webkit-transition:width .6s ease;transition:width .6s ease}.progress-striped .progress-bar{background-image:-webkit-linear-gradient(45deg,rgba(255,255,255,.15) 25%,transparent 25%,transparent 50%,rgba(255,255,255,.15) 50%,rgba(255,255,255,.15) 75%,transparent 75%,transparent);background-image:linear-gradient(45deg,rgba(255,255,255,.15) 25%,transparent 25%,transparent 50%,rgba(255,255,255,.15) 50%,rgba(255,255,255,.15) 75%,transparent 75%,transparent);background-size:40px 40px}.progress.active .progress-bar{-webkit-animation:progress-bar-stripes 2s linear infinite;animation:progress-bar-stripes 2s linear infinite}.progress-bar-success{background-color:#5cb85c}.progress-striped .progress-bar-success{background-image:-webkit-linear-gradient(45deg,rgba(255,255,255,.15) 25%,transparent 25%,transparent 50%,rgba(255,255,255,.15) 50%,rgba(255,255,255,.15) 75%,transparent 75%,transparent);background-image:linear-gradient(45deg,rgba(255,255,255,.15) 25%,transparent 25%,transparent 50%,rgba(255,255,255,.15) 50%,rgba(255,255,255,.15) 75%,transparent 75%,transparent)}.progress-bar-info{background-color:#5bc0de}.progress-striped .progress-bar-info{background-image:-webkit-linear-gradient(45deg,rgba(255,255,255,.15) 25%,transparent 25%,transparent 50%,rgba(255,255,255,.15) 50%,rgba(255,255,255,.15) 75%,transparent 75%,transparent);background-image:linear-gradient(45deg,rgba(255,255,255,.15) 25%,transparent 25%,transparent 50%,rgba(255,255,255,.15) 50%,rgba(255,255,255,.15) 75%,transparent 75%,transparent)}.progress-bar-warning{background-color:#f0ad4e}.progress-striped .progress-bar-warning{background-image:-webkit-linear-gradient(45deg,rgba(255,255,255,.15) 25%,transparent 25%,transparent 50%,rgba(255,255,255,.15) 50%,rgba(255,255,255,.15) 75%,transparent 75%,transparent);background-image:linear-gradient(45deg,rgba(255,255,255,.15) 25%,transparent 25%,transparent 50%,rgba(255,255,255,.15) 50%,rgba(255,255,255,.15) 75%,transparent 75%,transparent)}.progress-bar-danger{background-color:#d9534f}.progress-striped .progress-bar-danger{background-image:-webkit-linear-gradient(45deg,rgba(255,255,255,.15) 25%,transparent 25%,transparent 50%,rgba(255,255,255,.15) 50%,rgba(255,255,255,.15) 75%,transparent 75%,transparent);background-image:linear-gradient(45deg,rgba(255,255,255,.15) 25%,transparent 25%,transparent 50%,rgba(255,255,255,.15) 50%,rgba(255,255,255,.15) 75%,transparent 75%,transparent)}.media,.media-body{overflow:hidden;zoom:1}.media,.media .media{margin-top:15px}.media:first-child{margin-top:0}.media-object{display:block}.media-heading{margin:0 0 5px}.media>.pull-left{margin-right:10px}.media>.pull-right{margin-left:10px}.media-list{padding-left:0;list-style:none}.list-group{margin-bottom:20px;padding-left:0}.list-group-item{position:relative;display:block;padding:10px 15px;margin-bottom:-1px;background-color:#fff;border:1px solid #ddd}.list-group-item:first-child{border-top-right-radius:4px;border-top-left-radius:4px}.list-group-item:last-child{margin-bottom:0;border-bottom-right-radius:4px;border-bottom-left-radius:4px}.list-group-item>.badge{float:right}.list-group-item>.badge+.badge{margin-right:5px}a.list-group-item{color:#555}a.list-group-item .list-group-item-heading{color:#333}a.list-group-item:hover,a.list-group-item:focus{text-decoration:none;background-color:#f5f5f5}a.list-group-item.active,a.list-group-item.active:hover,a.list-group-item.active:focus{z-index:2;color:#fff;background-color:#428bca;border-color:#428bca}a.list-group-item.active .list-group-item-heading,a.list-group-item.active:hover .list-group-item-heading,a.list-group-item.active:focus .list-group-item-heading{color:inherit}a.list-group-item.active .list-group-item-text,a.list-group-item.active:hover .list-group-item-text,a.list-group-item.active:focus .list-group-item-text{color:#e1edf7}.list-group-item-success{color:#3c763d;background-color:#dff0d8}a.list-group-item-success{color:#3c763d}a.list-group-item-success .list-group-item-heading{color:inherit}a.list-group-item-success:hover,a.list-group-item-success:focus{color:#3c763d;background-color:#d0e9c6}a.list-group-item-success.active,a.list-group-item-success.active:hover,a.list-group-item-success.active:focus{color:#fff;background-color:#3c763d;border-color:#3c763d}.list-group-item-info{color:#31708f;background-color:#d9edf7}a.list-group-item-info{color:#31708f}a.list-group-item-info .list-group-item-heading{color:inherit}a.list-group-item-info:hover,a.list-group-item-info:focus{color:#31708f;background-color:#c4e3f3}a.list-group-item-info.active,a.list-group-item-info.active:hover,a.list-group-item-info.active:focus{color:#fff;background-color:#31708f;border-color:#31708f}.list-group-item-warning{color:#8a6d3b;background-color:#fcf8e3}a.list-group-item-warning{color:#8a6d3b}a.list-group-item-warning .list-group-item-heading{color:inherit}a.list-group-item-warning:hover,a.list-group-item-warning:focus{color:#8a6d3b;background-color:#faf2cc}a.list-group-item-warning.active,a.list-group-item-warning.active:hover,a.list-group-item-warning.active:focus{color:#fff;background-color:#8a6d3b;border-color:#8a6d3b}.list-group-item-danger{color:#a94442;background-color:#f2dede}a.list-group-item-danger{color:#a94442}a.list-group-item-danger .list-group-item-heading{color:inherit}a.list-group-item-danger:hover,a.list-group-item-danger:focus{color:#a94442;background-color:#ebcccc}a.list-group-item-danger.active,a.list-group-item-danger.active:hover,a.list-group-item-danger.active:focus{color:#fff;background-color:#a94442;border-color:#a94442}.list-group-item-heading{margin-top:0;margin-bottom:5px}.list-group-item-text{margin-bottom:0;line-height:1.3}.panel{margin-bottom:20px;background-color:#fff;border:1px solid transparent;border-radius:4px;-webkit-box-shadow:0 1px 1px rgba(0,0,0,.05);box-shadow:0 1px 1px rgba(0,0,0,.05)}.panel-body{padding:15px}.panel-heading{padding:10px 15px;border-bottom:1px solid transparent;border-top-right-radius:3px;border-top-left-radius:3px}.panel-heading>.dropdown .dropdown-toggle{color:inherit}.panel-title{margin-top:0;margin-bottom:0;font-size:16px;color:inherit}.panel-title>a{color:inherit}.panel-footer{padding:10px 15px;background-color:#f5f5f5;border-top:1px solid #ddd;border-bottom-right-radius:3px;border-bottom-left-radius:3px}.panel>.list-group{margin-bottom:0}.panel>.list-group .list-group-item{border-width:1px 0;border-radius:0}.panel>.list-group:first-child .list-group-item:first-child{border-top:0;border-top-right-radius:3px;border-top-left-radius:3px}.panel>.list-group:last-child .list-group-item:last-child{border-bottom:0;border-bottom-right-radius:3px;border-bottom-left-radius:3px}.panel-heading+.list-group .list-group-item:first-child{border-top-width:0}.panel>.table,.panel>.table-responsive>.table{margin-bottom:0}.panel>.table:first-child,.panel>.table-responsive:first-child>.table:first-child{border-top-right-radius:3px;border-top-left-radius:3px}.panel>.table:first-child>thead:first-child>tr:first-child td:first-child,.panel>.table-responsive:first-child>.table:first-child>thead:first-child>tr:first-child td:first-child,.panel>.table:first-child>tbody:first-child>tr:first-child td:first-child,.panel>.table-responsive:first-child>.table:first-child>tbody:first-child>tr:first-child td:first-child,.panel>.table:first-child>thead:first-child>tr:first-child th:first-child,.panel>.table-responsive:first-child>.table:first-child>thead:first-child>tr:first-child th:first-child,.panel>.table:first-child>tbody:first-child>tr:first-child th:first-child,.panel>.table-responsive:first-child>.table:first-child>tbody:first-child>tr:first-child th:first-child{border-top-left-radius:3px}.panel>.table:first-child>thead:first-child>tr:first-child td:last-child,.panel>.table-responsive:first-child>.table:first-child>thead:first-child>tr:first-child td:last-child,.panel>.table:first-child>tbody:first-child>tr:first-child td:last-child,.panel>.table-responsive:first-child>.table:first-child>tbody:first-child>tr:first-child td:last-child,.panel>.table:first-child>thead:first-child>tr:first-child th:last-child,.panel>.table-responsive:first-child>.table:first-child>thead:first-child>tr:first-child th:last-child,.panel>.table:first-child>tbody:first-child>tr:first-child th:last-child,.panel>.table-responsive:first-child>.table:first-child>tbody:first-child>tr:first-child th:last-child{border-top-right-radius:3px}.panel>.table:last-child,.panel>.table-responsive:last-child>.table:last-child{border-bottom-right-radius:3px;border-bottom-left-radius:3px}.panel>.table:last-child>tbody:last-child>tr:last-child td:first-child,.panel>.table-responsive:last-child>.table:last-child>tbody:last-child>tr:last-child td:first-child,.panel>.table:last-child>tfoot:last-child>tr:last-child td:first-child,.panel>.table-responsive:last-child>.table:last-child>tfoot:last-child>tr:last-child td:first-child,.panel>.table:last-child>tbody:last-child>tr:last-child th:first-child,.panel>.table-responsive:last-child>.table:last-child>tbody:last-child>tr:last-child th:first-child,.panel>.table:last-child>tfoot:last-child>tr:last-child th:first-child,.panel>.table-responsive:last-child>.table:last-child>tfoot:last-child>tr:last-child th:first-child{border-bottom-left-radius:3px}.panel>.table:last-child>tbody:last-child>tr:last-child td:last-child,.panel>.table-responsive:last-child>.table:last-child>tbody:last-child>tr:last-child td:last-child,.panel>.table:last-child>tfoot:last-child>tr:last-child td:last-child,.panel>.table-responsive:last-child>.table:last-child>tfoot:last-child>tr:last-child td:last-child,.panel>.table:last-child>tbody:last-child>tr:last-child th:last-child,.panel>.table-responsive:last-child>.table:last-child>tbody:last-child>tr:last-child th:last-child,.panel>.table:last-child>tfoot:last-child>tr:last-child th:last-child,.panel>.table-responsive:last-child>.table:last-child>tfoot:last-child>tr:last-child th:last-child{border-bottom-right-radius:3px}.panel>.panel-body+.table,.panel>.panel-body+.table-responsive{border-top:1px solid #ddd}.panel>.table>tbody:first-child>tr:first-child th,.panel>.table>tbody:first-child>tr:first-child td{border-top:0}.panel>.table-bordered,.panel>.table-responsive>.table-bordered{border:0}.panel>.table-bordered>thead>tr>th:first-child,.panel>.table-responsive>.table-bordered>thead>tr>th:first-child,.panel>.table-bordered>tbody>tr>th:first-child,.panel>.table-responsive>.table-bordered>tbody>tr>th:first-child,.panel>.table-bordered>tfoot>tr>th:first-child,.panel>.table-responsive>.table-bordered>tfoot>tr>th:first-child,.panel>.table-bordered>thead>tr>td:first-child,.panel>.table-responsive>.table-bordered>thead>tr>td:first-child,.panel>.table-bordered>tbody>tr>td:first-child,.panel>.table-responsive>.table-bordered>tbody>tr>td:first-child,.panel>.table-bordered>tfoot>tr>td:first-child,.panel>.table-responsive>.table-bordered>tfoot>tr>td:first-child{border-left:0}.panel>.table-bordered>thead>tr>th:last-child,.panel>.table-responsive>.table-bordered>thead>tr>th:last-child,.panel>.table-bordered>tbody>tr>th:last-child,.panel>.table-responsive>.table-bordered>tbody>tr>th:last-child,.panel>.table-bordered>tfoot>tr>th:last-child,.panel>.table-responsive>.table-bordered>tfoot>tr>th:last-child,.panel>.table-bordered>thead>tr>td:last-child,.panel>.table-responsive>.table-bordered>thead>tr>td:last-child,.panel>.table-bordered>tbody>tr>td:last-child,.panel>.table-responsive>.table-bordered>tbody>tr>td:last-child,.panel>.table-bordered>tfoot>tr>td:last-child,.panel>.table-responsive>.table-bordered>tfoot>tr>td:last-child{border-right:0}.panel>.table-bordered>thead>tr:first-child>td,.panel>.table-responsive>.table-bordered>thead>tr:first-child>td,.panel>.table-bordered>tbody>tr:first-child>td,.panel>.table-responsive>.table-bordered>tbody>tr:first-child>td,.panel>.table-bordered>thead>tr:first-child>th,.panel>.table-responsive>.table-bordered>thead>tr:first-child>th,.panel>.table-bordered>tbody>tr:first-child>th,.panel>.table-responsive>.table-bordered>tbody>tr:first-child>th{border-bottom:0}.panel>.table-bordered>tbody>tr:last-child>td,.panel>.table-responsive>.table-bordered>tbody>tr:last-child>td,.panel>.table-bordered>tfoot>tr:last-child>td,.panel>.table-responsive>.table-bordered>tfoot>tr:last-child>td,.panel>.table-bordered>tbody>tr:last-child>th,.panel>.table-responsive>.table-bordered>tbody>tr:last-child>th,.panel>.table-bordered>tfoot>tr:last-child>th,.panel>.table-responsive>.table-bordered>tfoot>tr:last-child>th{border-bottom:0}.panel>.table-responsive{border:0;margin-bottom:0}.panel-group{margin-bottom:20px}.panel-group .panel{margin-bottom:0;border-radius:4px;overflow:hidden}.panel-group .panel+.panel{margin-top:5px}.panel-group .panel-heading{border-bottom:0}.panel-group .panel-heading+.panel-collapse .panel-body{border-top:1px solid #ddd}.panel-group .panel-footer{border-top:0}.panel-group .panel-footer+.panel-collapse .panel-body{border-bottom:1px solid #ddd}.panel-default{border-color:#ddd}.panel-default>.panel-heading{color:#333;background-color:#f5f5f5;border-color:#ddd}.panel-default>.panel-heading+.panel-collapse .panel-body{border-top-color:#ddd}.panel-default>.panel-footer+.panel-collapse .panel-body{border-bottom-color:#ddd}.panel-primary{border-color:#428bca}.panel-primary>.panel-heading{color:#fff;background-color:#428bca;border-color:#428bca}.panel-primary>.panel-heading+.panel-collapse .panel-body{border-top-color:#428bca}.panel-primary>.panel-footer+.panel-collapse .panel-body{border-bottom-color:#428bca}.panel-success{border-color:#d6e9c6}.panel-success>.panel-heading{color:#3c763d;background-color:#dff0d8;border-color:#d6e9c6}.panel-success>.panel-heading+.panel-collapse .panel-body{border-top-color:#d6e9c6}.panel-success>.panel-footer+.panel-collapse .panel-body{border-bottom-color:#d6e9c6}.panel-info{border-color:#bce8f1}.panel-info>.panel-heading{color:#31708f;background-color:#d9edf7;border-color:#bce8f1}.panel-info>.panel-heading+.panel-collapse .panel-body{border-top-color:#bce8f1}.panel-info>.panel-footer+.panel-collapse .panel-body{border-bottom-color:#bce8f1}.panel-warning{border-color:#faebcc}.panel-warning>.panel-heading{color:#8a6d3b;background-color:#fcf8e3;border-color:#faebcc}.panel-warning>.panel-heading+.panel-collapse .panel-body{border-top-color:#faebcc}.panel-warning>.panel-footer+.panel-collapse .panel-body{border-bottom-color:#faebcc}.panel-danger{border-color:#ebccd1}.panel-danger>.panel-heading{color:#a94442;background-color:#f2dede;border-color:#ebccd1}.panel-danger>.panel-heading+.panel-collapse .panel-body{border-top-color:#ebccd1}.panel-danger>.panel-footer+.panel-collapse .panel-body{border-bottom-color:#ebccd1}.well{min-height:20px;padding:19px;margin-bottom:20px;background-color:#f5f5f5;border:1px solid #e3e3e3;border-radius:4px;-webkit-box-shadow:inset 0 1px 1px rgba(0,0,0,.05);box-shadow:inset 0 1px 1px rgba(0,0,0,.05)}.well blockquote{border-color:#ddd;border-color:rgba(0,0,0,.15)}.well-lg{padding:24px;border-radius:6px}.well-sm{padding:9px;border-radius:3px}.close{float:right;font-size:21px;font-weight:700;line-height:1;color:#000;text-shadow:0 1px 0 #fff;opacity:.2;filter:alpha(opacity=20)}.close:hover,.close:focus{color:#000;text-decoration:none;cursor:pointer;opacity:.5;filter:alpha(opacity=50)}button.close{padding:0;cursor:pointer;background:0 0;border:0;-webkit-appearance:none}.modal-open{overflow:hidden}.modal{display:none;overflow:auto;overflow-y:scroll;position:fixed;top:0;right:0;bottom:0;left:0;z-index:1050;-webkit-overflow-scrolling:touch;outline:0}.modal.fade .modal-dialog{-webkit-transform:translate(0,-25%);-ms-transform:translate(0,-25%);transform:translate(0,-25%);-webkit-transition:-webkit-transform .3s ease-out;-moz-transition:-moz-transform .3s ease-out;-o-transition:-o-transform .3s ease-out;transition:transform .3s ease-out}.modal.in .modal-dialog{-webkit-transform:translate(0,0);-ms-transform:translate(0,0);transform:translate(0,0)}.modal-dialog{position:relative;width:auto;margin:10px}.modal-content{position:relative;background-color:#fff;border:1px solid #999;border:1px solid rgba(0,0,0,.2);border-radius:6px;-webkit-box-shadow:0 3px 9px rgba(0,0,0,.5);box-shadow:0 3px 9px rgba(0,0,0,.5);background-clip:padding-box;outline:0}.modal-backdrop{position:fixed;top:0;right:0;bottom:0;left:0;z-index:1040;background-color:#000}.modal-backdrop.fade{opacity:0;filter:alpha(opacity=0)}.modal-backdrop.in{opacity:.5;filter:alpha(opacity=50)}.modal-header{padding:15px;border-bottom:1px solid #e5e5e5;min-height:16.42857143px}.modal-header .close{margin-top:-2px}.modal-title{margin:0;line-height:1.42857143}.modal-body{position:relative;padding:20px}.modal-footer{margin-top:15px;padding:19px 20px 20px;text-align:right;border-top:1px solid #e5e5e5}.modal-footer .btn+.btn{margin-left:5px;margin-bottom:0}.modal-footer .btn-group .btn+.btn{margin-left:-1px}.modal-footer .btn-block+.btn-block{margin-left:0}@media (min-width:768px){.modal-dialog{width:600px;margin:30px auto}.modal-content{-webkit-box-shadow:0 5px 15px rgba(0,0,0,.5);box-shadow:0 5px 15px rgba(0,0,0,.5)}.modal-sm{width:300px}}@media (min-width:992px){.modal-lg{width:900px}}.tooltip{position:absolute;z-index:1030;display:block;visibility:visible;font-size:12px;line-height:1.4;opacity:0;filter:alpha(opacity=0)}.tooltip.in{opacity:.9;filter:alpha(opacity=90)}.tooltip.top{margin-top:-3px;padding:5px 0}.tooltip.right{margin-left:3px;padding:0 5px}.tooltip.bottom{margin-top:3px;padding:5px 0}.tooltip.left{margin-left:-3px;padding:0 5px}.tooltip-inner{max-width:200px;padding:3px 8px;color:#fff;text-align:center;text-decoration:none;background-color:#000;border-radius:4px}.tooltip-arrow{position:absolute;width:0;height:0;border-color:transparent;border-style:solid}.tooltip.top .tooltip-arrow{bottom:0;left:50%;margin-left:-5px;border-width:5px 5px 0;border-top-color:#000}.tooltip.top-left .tooltip-arrow{bottom:0;left:5px;border-width:5px 5px 0;border-top-color:#000}.tooltip.top-right .tooltip-arrow{bottom:0;right:5px;border-width:5px 5px 0;border-top-color:#000}.tooltip.right .tooltip-arrow{top:50%;left:0;margin-top:-5px;border-width:5px 5px 5px 0;border-right-color:#000}.tooltip.left .tooltip-arrow{top:50%;right:0;margin-top:-5px;border-width:5px 0 5px 5px;border-left-color:#000}.tooltip.bottom .tooltip-arrow{top:0;left:50%;margin-left:-5px;border-width:0 5px 5px;border-bottom-color:#000}.tooltip.bottom-left .tooltip-arrow{top:0;left:5px;border-width:0 5px 5px;border-bottom-color:#000}.tooltip.bottom-right .tooltip-arrow{top:0;right:5px;border-width:0 5px 5px;border-bottom-color:#000}.popover{position:absolute;top:0;left:0;z-index:1010;display:none;max-width:276px;padding:1px;text-align:left;background-color:#fff;background-clip:padding-box;border:1px solid #ccc;border:1px solid rgba(0,0,0,.2);border-radius:6px;-webkit-box-shadow:0 5px 10px rgba(0,0,0,.2);box-shadow:0 5px 10px rgba(0,0,0,.2);white-space:normal}.popover.top{margin-top:-10px}.popover.right{margin-left:10px}.popover.bottom{margin-top:10px}.popover.left{margin-left:-10px}.popover-title{margin:0;padding:8px 14px;font-size:14px;font-weight:400;line-height:18px;background-color:#f7f7f7;border-bottom:1px solid #ebebeb;border-radius:5px 5px 0 0}.popover-content{padding:9px 14px}.popover>.arrow,.popover>.arrow:after{position:absolute;display:block;width:0;height:0;border-color:transparent;border-style:solid}.popover>.arrow{border-width:11px}.popover>.arrow:after{border-width:10px;content:""}.popover.top>.arrow{left:50%;margin-left:-11px;border-bottom-width:0;border-top-color:#999;border-top-color:rgba(0,0,0,.25);bottom:-11px}.popover.top>.arrow:after{content:" ";bottom:1px;margin-left:-10px;border-bottom-width:0;border-top-color:#fff}.popover.right>.arrow{top:50%;left:-11px;margin-top:-11px;border-left-width:0;border-right-color:#999;border-right-color:rgba(0,0,0,.25)}.popover.right>.arrow:after{content:" ";left:1px;bottom:-10px;border-left-width:0;border-right-color:#fff}.popover.bottom>.arrow{left:50%;margin-left:-11px;border-top-width:0;border-bottom-color:#999;border-bottom-color:rgba(0,0,0,.25);top:-11px}.popover.bottom>.arrow:after{content:" ";top:1px;margin-left:-10px;border-top-width:0;border-bottom-color:#fff}.popover.left>.arrow{top:50%;right:-11px;margin-top:-11px;border-right-width:0;border-left-color:#999;border-left-color:rgba(0,0,0,.25)}.popover.left>.arrow:after{content:" ";right:1px;border-right-width:0;border-left-color:#fff;bottom:-10px}.carousel{position:relative}.carousel-inner{position:relative;overflow:hidden;width:100%}.carousel-inner>.item{display:none;position:relative;-webkit-transition:.6s ease-in-out left;transition:.6s ease-in-out left}.carousel-inner>.item>img,.carousel-inner>.item>a>img{line-height:1}.carousel-inner>.active,.carousel-inner>.next,.carousel-inner>.prev{display:block}.carousel-inner>.active{left:0}.carousel-inner>.next,.carousel-inner>.prev{position:absolute;top:0;width:100%}.carousel-inner>.next{left:100%}.carousel-inner>.prev{left:-100%}.carousel-inner>.next.left,.carousel-inner>.prev.right{left:0}.carousel-inner>.active.left{left:-100%}.carousel-inner>.active.right{left:100%}.carousel-control{position:absolute;top:0;left:0;bottom:0;width:15%;opacity:.5;filter:alpha(opacity=50);font-size:20px;color:#fff;text-align:center;text-shadow:0 1px 2px rgba(0,0,0,.6)}.carousel-control.left{background-image:-webkit-linear-gradient(left,color-stop(rgba(0,0,0,.5) 0),color-stop(rgba(0,0,0,.0001) 100%));background-image:linear-gradient(to right,rgba(0,0,0,.5) 0,rgba(0,0,0,.0001) 100%);background-repeat:repeat-x;filter:progid:DXImageTransform.Microsoft.gradient(startColorstr='#80000000', endColorstr='#00000000', GradientType=1)}.carousel-control.right{left:auto;right:0;background-image:-webkit-linear-gradient(left,color-stop(rgba(0,0,0,.0001) 0),color-stop(rgba(0,0,0,.5) 100%));background-image:linear-gradient(to right,rgba(0,0,0,.0001) 0,rgba(0,0,0,.5) 100%);background-repeat:repeat-x;filter:progid:DXImageTransform.Microsoft.gradient(startColorstr='#00000000', endColorstr='#80000000', GradientType=1)}.carousel-control:hover,.carousel-control:focus{outline:0;color:#fff;text-decoration:none;opacity:.9;filter:alpha(opacity=90)}.carousel-control .icon-prev,.carousel-control .icon-next,.carousel-control .glyphicon-chevron-left,.carousel-control .glyphicon-chevron-right{position:absolute;top:50%;z-index:5;display:inline-block}.carousel-control .icon-prev,.carousel-control .glyphicon-chevron-left{left:50%}.carousel-control .icon-next,.carousel-control .glyphicon-chevron-right{right:50%}.carousel-control .icon-prev,.carousel-control .icon-next{width:20px;height:20px;margin-top:-10px;margin-left:-10px;font-family:serif}.carousel-control .icon-prev:before{content:'\2039'}.carousel-control .icon-next:before{content:'\203a'}.carousel-indicators{position:absolute;bottom:10px;left:50%;z-index:15;width:60%;margin-left:-30%;padding-left:0;list-style:none;text-align:center}.carousel-indicators li{display:inline-block;width:10px;height:10px;margin:1px;text-indent:-999px;border:1px solid #fff;border-radius:10px;cursor:pointer;background-color:#000 \9;background-color:rgba(0,0,0,0)}.carousel-indicators .active{margin:0;width:12px;height:12px;background-color:#fff}.carousel-caption{position:absolute;left:15%;right:15%;bottom:20px;z-index:10;padding-top:20px;padding-bottom:20px;color:#fff;text-align:center;text-shadow:0 1px 2px rgba(0,0,0,.6)}.carousel-caption .btn{text-shadow:none}@media screen and (min-width:768px){.carousel-control .glyphicon-chevron-left,.carousel-control .glyphicon-chevron-right,.carousel-control .icon-prev,.carousel-control .icon-next{width:30px;height:30px;margin-top:-15px;margin-left:-15px;font-size:30px}.carousel-caption{left:20%;right:20%;padding-bottom:30px}.carousel-indicators{bottom:20px}}.clearfix:before,.clearfix:after,.container:before,.container:after,.container-fluid:before,.container-fluid:after,.row:before,.row:after,.form-horizontal .form-group:before,.form-horizontal .form-group:after,.btn-toolbar:before,.btn-toolbar:after,.btn-group-vertical>.btn-group:before,.btn-group-vertical>.btn-group:after,.nav:before,.nav:after,.navbar:before,.navbar:after,.navbar-header:before,.navbar-header:after,.navbar-collapse:before,.navbar-collapse:after,.pager:before,.pager:after,.panel-body:before,.panel-body:after,.modal-footer:before,.modal-footer:after{content:" ";display:table}.clearfix:after,.container:after,.container-fluid:after,.row:after,.form-horizontal .form-group:after,.btn-toolbar:after,.btn-group-vertical>.btn-group:after,.nav:after,.navbar:after,.navbar-header:after,.navbar-collapse:after,.pager:after,.panel-body:after,.modal-footer:after{clear:both}.center-block{display:block;margin-left:auto;margin-right:auto}.pull-right{float:right!important}.pull-left{float:left!important}.hide{display:none!important}.show{display:block!important}.invisible{visibility:hidden}.text-hide{font:0/0 a;color:transparent;text-shadow:none;background-color:transparent;border:0}.hidden{display:none!important;visibility:hidden!important}.affix{position:fixed}@-ms-viewport{width:device-width}.visible-xs,.visible-sm,.visible-md,.visible-lg{display:none!important}@media (max-width:767px){.visible-xs{display:block!important}table.visible-xs{display:table}tr.visible-xs{display:table-row!important}th.visible-xs,td.visible-xs{display:table-cell!important}}@media (min-width:768px) and (max-width:991px){.visible-sm{display:block!important}table.visible-sm{display:table}tr.visible-sm{display:table-row!important}th.visible-sm,td.visible-sm{display:table-cell!important}}@media (min-width:992px) and (max-width:1199px){.visible-md{display:block!important}table.visible-md{display:table}tr.visible-md{display:table-row!important}th.visible-md,td.visible-md{display:table-cell!important}}@media (min-width:1200px){.visible-lg{display:block!important}table.visible-lg{display:table}tr.visible-lg{display:table-row!important}th.visible-lg,td.visible-lg{display:table-cell!important}}@media (max-width:767px){.hidden-xs{display:none!important}}@media (min-width:768px) and (max-width:991px){.hidden-sm{display:none!important}}@media (min-width:992px) and (max-width:1199px){.hidden-md{display:none!important}}@media (min-width:1200px){.hidden-lg{display:none!important}}.visible-print{display:none!important}@media print{.visible-print{display:block!important}table.visible-print{display:table}tr.visible-print{display:table-row!important}th.visible-print,td.visible-print{display:table-cell!important}}@media print{.hidden-print{display:none!important}}</style>
<style>/*!
 * Bootstrap v3.1.1 (http://getbootstrap.com)
 * Copyright 2011-2014 Twitter, Inc.
 * Licensed under MIT (https://github.com/twbs/bootstrap/blob/master/LICENSE)
 */

.btn-default,.btn-primary,.btn-success,.btn-info,.btn-warning,.btn-danger{text-shadow:0 -1px 0 rgba(0,0,0,.2);-webkit-box-shadow:inset 0 1px 0 rgba(255,255,255,.15),0 1px 1px rgba(0,0,0,.075);box-shadow:inset 0 1px 0 rgba(255,255,255,.15),0 1px 1px rgba(0,0,0,.075)}.btn-default:active,.btn-primary:active,.btn-success:active,.btn-info:active,.btn-warning:active,.btn-danger:active,.btn-default.active,.btn-primary.active,.btn-success.active,.btn-info.active,.btn-warning.active,.btn-danger.active{-webkit-box-shadow:inset 0 3px 5px rgba(0,0,0,.125);box-shadow:inset 0 3px 5px rgba(0,0,0,.125)}.btn:active,.btn.active{background-image:none}.btn-default{background-image:-webkit-linear-gradient(top,#fff 0,#e0e0e0 100%);background-image:linear-gradient(to bottom,#fff 0,#e0e0e0 100%);filter:progid:DXImageTransform.Microsoft.gradient(startColorstr='#ffffffff', endColorstr='#ffe0e0e0', GradientType=0);filter:progid:DXImageTransform.Microsoft.gradient(enabled=false);background-repeat:repeat-x;border-color:#dbdbdb;text-shadow:0 1px 0 #fff;border-color:#ccc}.btn-default:hover,.btn-default:focus{background-color:#e0e0e0;background-position:0 -15px}.btn-default:active,.btn-default.active{background-color:#e0e0e0;border-color:#dbdbdb}.btn-primary{background-image:-webkit-linear-gradient(top,#428bca 0,#2d6ca2 100%);background-image:linear-gradient(to bottom,#428bca 0,#2d6ca2 100%);filter:progid:DXImageTransform.Microsoft.gradient(startColorstr='#ff428bca', endColorstr='#ff2d6ca2', GradientType=0);filter:progid:DXImageTransform.Microsoft.gradient(enabled=false);background-repeat:repeat-x;border-color:#2b669a}.btn-primary:hover,.btn-primary:focus{background-color:#2d6ca2;background-position:0 -15px}.btn-primary:active,.btn-primary.active{background-color:#2d6ca2;border-color:#2b669a}.btn-success{background-image:-webkit-linear-gradient(top,#5cb85c 0,#419641 100%);background-image:linear-gradient(to bottom,#5cb85c 0,#419641 100%);filter:progid:DXImageTransform.Microsoft.gradient(startColorstr='#ff5cb85c', endColorstr='#ff419641', GradientType=0);filter:progid:DXImageTransform.Microsoft.gradient(enabled=false);background-repeat:repeat-x;border-color:#3e8f3e}.btn-success:hover,.btn-success:focus{background-color:#419641;background-position:0 -15px}.btn-success:active,.btn-success.active{background-color:#419641;border-color:#3e8f3e}.btn-info{background-image:-webkit-linear-gradient(top,#5bc0de 0,#2aabd2 100%);background-image:linear-gradient(to bottom,#5bc0de 0,#2aabd2 100%);filter:progid:DXImageTransform.Microsoft.gradient(startColorstr='#ff5bc0de', endColorstr='#ff2aabd2', GradientType=0);filter:progid:DXImageTransform.Microsoft.gradient(enabled=false);background-repeat:repeat-x;border-color:#28a4c9}.btn-info:hover,.btn-info:focus{background-color:#2aabd2;background-position:0 -15px}.btn-info:active,.btn-info.active{background-color:#2aabd2;border-color:#28a4c9}.btn-warning{background-image:-webkit-linear-gradient(top,#f0ad4e 0,#eb9316 100%);background-image:linear-gradient(to bottom,#f0ad4e 0,#eb9316 100%);filter:progid:DXImageTransform.Microsoft.gradient(startColorstr='#fff0ad4e', endColorstr='#ffeb9316', GradientType=0);filter:progid:DXImageTransform.Microsoft.gradient(enabled=false);background-repeat:repeat-x;border-color:#e38d13}.btn-warning:hover,.btn-warning:focus{background-color:#eb9316;background-position:0 -15px}.btn-warning:active,.btn-warning.active{background-color:#eb9316;border-color:#e38d13}.btn-danger{background-image:-webkit-linear-gradient(top,#d9534f 0,#c12e2a 100%);background-image:linear-gradient(to bottom,#d9534f 0,#c12e2a 100%);filter:progid:DXImageTransform.Microsoft.gradient(startColorstr='#ffd9534f', endColorstr='#ffc12e2a', GradientType=0);filter:progid:DXImageTransform.Microsoft.gradient(enabled=false);background-repeat:repeat-x;border-color:#b92c28}.btn-danger:hover,.btn-danger:focus{background-color:#c12e2a;background-position:0 -15px}.btn-danger:active,.btn-danger.active{background-color:#c12e2a;border-color:#b92c28}.thumbnail,.img-thumbnail{-webkit-box-shadow:0 1px 2px rgba(0,0,0,.075);box-shadow:0 1px 2px rgba(0,0,0,.075)}.dropdown-menu>li>a:hover,.dropdown-menu>li>a:focus{background-image:-webkit-linear-gradient(top,#f5f5f5 0,#e8e8e8 100%);background-image:linear-gradient(to bottom,#f5f5f5 0,#e8e8e8 100%);background-repeat:repeat-x;filter:progid:DXImageTransform.Microsoft.gradient(startColorstr='#fff5f5f5', endColorstr='#ffe8e8e8', GradientType=0);background-color:#e8e8e8}.dropdown-menu>.active>a,.dropdown-menu>.active>a:hover,.dropdown-menu>.active>a:focus{background-image:-webkit-linear-gradient(top,#428bca 0,#357ebd 100%);background-image:linear-gradient(to bottom,#428bca 0,#357ebd 100%);background-repeat:repeat-x;filter:progid:DXImageTransform.Microsoft.gradient(startColorstr='#ff428bca', endColorstr='#ff357ebd', GradientType=0);background-color:#357ebd}.navbar-default{background-image:-webkit-linear-gradient(top,#fff 0,#f8f8f8 100%);background-image:linear-gradient(to bottom,#fff 0,#f8f8f8 100%);background-repeat:repeat-x;filter:progid:DXImageTransform.Microsoft.gradient(startColorstr='#ffffffff', endColorstr='#fff8f8f8', GradientType=0);filter:progid:DXImageTransform.Microsoft.gradient(enabled=false);border-radius:4px;-webkit-box-shadow:inset 0 1px 0 rgba(255,255,255,.15),0 1px 5px rgba(0,0,0,.075);box-shadow:inset 0 1px 0 rgba(255,255,255,.15),0 1px 5px rgba(0,0,0,.075)}.navbar-default .navbar-nav>.active>a{background-image:-webkit-linear-gradient(top,#ebebeb 0,#f3f3f3 100%);background-image:linear-gradient(to bottom,#ebebeb 0,#f3f3f3 100%);background-repeat:repeat-x;filter:progid:DXImageTransform.Microsoft.gradient(startColorstr='#ffebebeb', endColorstr='#fff3f3f3', GradientType=0);-webkit-box-shadow:inset 0 3px 9px rgba(0,0,0,.075);box-shadow:inset 0 3px 9px rgba(0,0,0,.075)}.navbar-brand,.navbar-nav>li>a{text-shadow:0 1px 0 rgba(255,255,255,.25)}.navbar-inverse{background-image:-webkit-linear-gradient(top,#3c3c3c 0,#222 100%);background-image:linear-gradient(to bottom,#3c3c3c 0,#222 100%);background-repeat:repeat-x;filter:progid:DXImageTransform.Microsoft.gradient(startColorstr='#ff3c3c3c', endColorstr='#ff222222', GradientType=0);filter:progid:DXImageTransform.Microsoft.gradient(enabled=false)}.navbar-inverse .navbar-nav>.active>a{background-image:-webkit-linear-gradient(top,#222 0,#282828 100%);background-image:linear-gradient(to bottom,#222 0,#282828 100%);background-repeat:repeat-x;filter:progid:DXImageTransform.Microsoft.gradient(startColorstr='#ff222222', endColorstr='#ff282828', GradientType=0);-webkit-box-shadow:inset 0 3px 9px rgba(0,0,0,.25);box-shadow:inset 0 3px 9px rgba(0,0,0,.25)}.navbar-inverse .navbar-brand,.navbar-inverse .navbar-nav>li>a{text-shadow:0 -1px 0 rgba(0,0,0,.25)}.navbar-static-top,.navbar-fixed-top,.navbar-fixed-bottom{border-radius:0}.alert{text-shadow:0 1px 0 rgba(255,255,255,.2);-webkit-box-shadow:inset 0 1px 0 rgba(255,255,255,.25),0 1px 2px rgba(0,0,0,.05);box-shadow:inset 0 1px 0 rgba(255,255,255,.25),0 1px 2px rgba(0,0,0,.05)}.alert-success{background-image:-webkit-linear-gradient(top,#dff0d8 0,#c8e5bc 100%);background-image:linear-gradient(to bottom,#dff0d8 0,#c8e5bc 100%);background-repeat:repeat-x;filter:progid:DXImageTransform.Microsoft.gradient(startColorstr='#ffdff0d8', endColorstr='#ffc8e5bc', GradientType=0);border-color:#b2dba1}.alert-info{background-image:-webkit-linear-gradient(top,#d9edf7 0,#b9def0 100%);background-image:linear-gradient(to bottom,#d9edf7 0,#b9def0 100%);background-repeat:repeat-x;filter:progid:DXImageTransform.Microsoft.gradient(startColorstr='#ffd9edf7', endColorstr='#ffb9def0', GradientType=0);border-color:#9acfea}.alert-warning{background-image:-webkit-linear-gradient(top,#fcf8e3 0,#f8efc0 100%);background-image:linear-gradient(to bottom,#fcf8e3 0,#f8efc0 100%);background-repeat:repeat-x;filter:progid:DXImageTransform.Microsoft.gradient(startColorstr='#fffcf8e3', endColorstr='#fff8efc0', GradientType=0);border-color:#f5e79e}.alert-danger{background-image:-webkit-linear-gradient(top,#f2dede 0,#e7c3c3 100%);background-image:linear-gradient(to bottom,#f2dede 0,#e7c3c3 100%);background-repeat:repeat-x;filter:progid:DXImageTransform.Microsoft.gradient(startColorstr='#fff2dede', endColorstr='#ffe7c3c3', GradientType=0);border-color:#dca7a7}.progress{background-image:-webkit-linear-gradient(top,#ebebeb 0,#f5f5f5 100%);background-image:linear-gradient(to bottom,#ebebeb 0,#f5f5f5 100%);background-repeat:repeat-x;filter:progid:DXImageTransform.Microsoft.gradient(startColorstr='#ffebebeb', endColorstr='#fff5f5f5', GradientType=0)}.progress-bar{background-image:-webkit-linear-gradient(top,#428bca 0,#3071a9 100%);background-image:linear-gradient(to bottom,#428bca 0,#3071a9 100%);background-repeat:repeat-x;filter:progid:DXImageTransform.Microsoft.gradient(startColorstr='#ff428bca', endColorstr='#ff3071a9', GradientType=0)}.progress-bar-success{background-image:-webkit-linear-gradient(top,#5cb85c 0,#449d44 100%);background-image:linear-gradient(to bottom,#5cb85c 0,#449d44 100%);background-repeat:repeat-x;filter:progid:DXImageTransform.Microsoft.gradient(startColorstr='#ff5cb85c', endColorstr='#ff449d44', GradientType=0)}.progress-bar-info{background-image:-webkit-linear-gradient(top,#5bc0de 0,#31b0d5 100%);background-image:linear-gradient(to bottom,#5bc0de 0,#31b0d5 100%);background-repeat:repeat-x;filter:progid:DXImageTransform.Microsoft.gradient(startColorstr='#ff5bc0de', endColorstr='#ff31b0d5', GradientType=0)}.progress-bar-warning{background-image:-webkit-linear-gradient(top,#f0ad4e 0,#ec971f 100%);background-image:linear-gradient(to bottom,#f0ad4e 0,#ec971f 100%);background-repeat:repeat-x;filter:progid:DXImageTransform.Microsoft.gradient(startColorstr='#fff0ad4e', endColorstr='#ffec971f', GradientType=0)}.progress-bar-danger{background-image:-webkit-linear-gradient(top,#d9534f 0,#c9302c 100%);background-image:linear-gradient(to bottom,#d9534f 0,#c9302c 100%);background-repeat:repeat-x;filter:progid:DXImageTransform.Microsoft.gradient(startColorstr='#ffd9534f', endColorstr='#ffc9302c', GradientType=0)}.list-group{border-radius:4px;-webkit-box-shadow:0 1px 2px rgba(0,0,0,.075);box-shadow:0 1px 2px rgba(0,0,0,.075)}.list-group-item.active,.list-group-item.active:hover,.list-group-item.active:focus{text-shadow:0 -1px 0 #3071a9;background-image:-webkit-linear-gradient(top,#428bca 0,#3278b3 100%);background-image:linear-gradient(to bottom,#428bca 0,#3278b3 100%);background-repeat:repeat-x;filter:progid:DXImageTransform.Microsoft.gradient(startColorstr='#ff428bca', endColorstr='#ff3278b3', GradientType=0);border-color:#3278b3}.panel{-webkit-box-shadow:0 1px 2px rgba(0,0,0,.05);box-shadow:0 1px 2px rgba(0,0,0,.05)}.panel-default>.panel-heading{background-image:-webkit-linear-gradient(top,#f5f5f5 0,#e8e8e8 100%);background-image:linear-gradient(to bottom,#f5f5f5 0,#e8e8e8 100%);background-repeat:repeat-x;filter:progid:DXImageTransform.Microsoft.gradient(startColorstr='#fff5f5f5', endColorstr='#ffe8e8e8', GradientType=0)}.panel-primary>.panel-heading{background-image:-webkit-linear-gradient(top,#428bca 0,#357ebd 100%);background-image:linear-gradient(to bottom,#428bca 0,#357ebd 100%);background-repeat:repeat-x;filter:progid:DXImageTransform.Microsoft.gradient(startColorstr='#ff428bca', endColorstr='#ff357ebd', GradientType=0)}.panel-success>.panel-heading{background-image:-webkit-linear-gradient(top,#dff0d8 0,#d0e9c6 100%);background-image:linear-gradient(to bottom,#dff0d8 0,#d0e9c6 100%);background-repeat:repeat-x;filter:progid:DXImageTransform.Microsoft.gradient(startColorstr='#ffdff0d8', endColorstr='#ffd0e9c6', GradientType=0)}.panel-info>.panel-heading{background-image:-webkit-linear-gradient(top,#d9edf7 0,#c4e3f3 100%);background-image:linear-gradient(to bottom,#d9edf7 0,#c4e3f3 100%);background-repeat:repeat-x;filter:progid:DXImageTransform.Microsoft.gradient(startColorstr='#ffd9edf7', endColorstr='#ffc4e3f3', GradientType=0)}.panel-warning>.panel-heading{background-image:-webkit-linear-gradient(top,#fcf8e3 0,#faf2cc 100%);background-image:linear-gradient(to bottom,#fcf8e3 0,#faf2cc 100%);background-repeat:repeat-x;filter:progid:DXImageTransform.Microsoft.gradient(startColorstr='#fffcf8e3', endColorstr='#fffaf2cc', GradientType=0)}.panel-danger>.panel-heading{background-image:-webkit-linear-gradient(top,#f2dede 0,#ebcccc 100%);background-image:linear-gradient(to bottom,#f2dede 0,#ebcccc 100%);background-repeat:repeat-x;filter:progid:DXImageTransform.Microsoft.gradient(startColorstr='#fff2dede', endColorstr='#ffebcccc', GradientType=0)}.well{background-image:-webkit-linear-gradient(top,#e8e8e8 0,#f5f5f5 100%);background-image:linear-gradient(to bottom,#e8e8e8 0,#f5f5f5 100%);background-repeat:repeat-x;filter:progid:DXImageTransform.Microsoft.gradient(startColorstr='#ffe8e8e8', endColorstr='#fff5f5f5', GradientType=0);border-color:#dcdcdc;-webkit-box-shadow:inset 0 1px 3px rgba(0,0,0,.05),0 1px 0 rgba(255,255,255,.1);box-shadow:inset 0 1px 3px rgba(0,0,0,.05),0 1px 0 rgba(255,255,255,.1)}</style>
<style>/* BASICS */

.CodeMirror {
  /* Set height, width, borders, and global font properties here */
  font-family: monospace;
  height: 300px;
}
.CodeMirror-scroll {
  /* Set scrolling behaviour here */
  overflow: auto;
}

/* PADDING */

.CodeMirror-lines {
  padding: 4px 0; /* Vertical padding around content */
}
.CodeMirror pre {
  padding: 0 4px; /* Horizontal padding of content */
}

.CodeMirror-scrollbar-filler, .CodeMirror-gutter-filler {
  background-color: white; /* The little square between H and V scrollbars */
}

/* GUTTER */

.CodeMirror-gutters {
  border-right: 1px solid #ddd;
  background-color: #f7f7f7;
  white-space: nowrap;
}
.CodeMirror-linenumbers {}
.CodeMirror-linenumber {
  padding: 0 3px 0 5px;
  min-width: 20px;
  text-align: right;
  color: #999;
  -moz-box-sizing: content-box;
  box-sizing: content-box;
}

/* CURSOR */

.CodeMirror div.CodeMirror-cursor {
  border-left: 1px solid black;
}
/* Shown when moving in bi-directional text */
.CodeMirror div.CodeMirror-secondarycursor {
  border-left: 1px solid silver;
}
.CodeMirror.cm-keymap-fat-cursor div.CodeMirror-cursor {
  width: auto;
  border: 0;
  background: #7e7;
}
/* Can style cursor different in overwrite (non-insert) mode */
div.CodeMirror-overwrite div.CodeMirror-cursor {}

.cm-tab { display: inline-block; }

.CodeMirror-ruler {
  border-left: 1px solid #ccc;
  position: absolute;
}

/* DEFAULT THEME */

.cm-s-default .cm-keyword {color: #708;}
.cm-s-default .cm-atom {color: #219;}
.cm-s-default .cm-number {color: #164;}
.cm-s-default .cm-def {color: #00f;}
.cm-s-default .cm-variable,
.cm-s-default .cm-punctuation,
.cm-s-default .cm-property,
.cm-s-default .cm-operator {}
.cm-s-default .cm-variable-2 {color: #05a;}
.cm-s-default .cm-variable-3 {color: #085;}
.cm-s-default .cm-comment {color: #a50;}
.cm-s-default .cm-string {color: #a11;}
.cm-s-default .cm-string-2 {color: #f50;}
.cm-s-default .cm-meta {color: #555;}
.cm-s-default .cm-qualifier {color: #555;}
.cm-s-default .cm-builtin {color: #30a;}
.cm-s-default .cm-bracket {color: #997;}
.cm-s-default .cm-tag {color: #170;}
.cm-s-default .cm-attribute {color: #00c;}
.cm-s-default .cm-header {color: blue;}
.cm-s-default .cm-quote {color: #090;}
.cm-s-default .cm-hr {color: #999;}
.cm-s-default .cm-link {color: #00c;}

.cm-negative {color: #d44;}
.cm-positive {color: #292;}
.cm-header, .cm-strong {font-weight: bold;}
.cm-em {font-style: italic;}
.cm-link {text-decoration: underline;}

.cm-s-default .cm-error {color: #f00;}
.cm-invalidchar {color: #f00;}

div.CodeMirror span.CodeMirror-matchingbracket {color: #0f0;}
div.CodeMirror span.CodeMirror-nonmatchingbracket {color: #f22;}
.CodeMirror-activeline-background {background: #e8f2ff;}

/* STOP */

/* The rest of this file contains styles related to the mechanics of
   the editor. You probably shouldn't touch them. */

.CodeMirror {
  line-height: 1;
  position: relative;
  overflow: hidden;
  background: white;
  color: black;
}

.CodeMirror-scroll {
  /* 30px is the magic margin used to hide the element's real scrollbars */
  /* See overflow: hidden in .CodeMirror */
  margin-bottom: -30px; margin-right: -30px;
  padding-bottom: 30px;
  height: 100%;
  outline: none; /* Prevent dragging from highlighting the element */
  position: relative;
  -moz-box-sizing: content-box;
  box-sizing: content-box;
}
.CodeMirror-sizer {
  position: relative;
  border-right: 30px solid transparent;
  -moz-box-sizing: content-box;
  box-sizing: content-box;
}

/* The fake, visible scrollbars. Used to force redraw during scrolling
   before actuall scrolling happens, thus preventing shaking and
   flickering artifacts. */
.CodeMirror-vscrollbar, .CodeMirror-hscrollbar, .CodeMirror-scrollbar-filler, .CodeMirror-gutter-filler {
  position: absolute;
  z-index: 6;
  display: none;
}
.CodeMirror-vscrollbar {
  right: 0; top: 0;
  overflow-x: hidden;
  overflow-y: scroll;
}
.CodeMirror-hscrollbar {
  bottom: 0; left: 0;
  overflow-y: hidden;
  overflow-x: scroll;
}
.CodeMirror-scrollbar-filler {
  right: 0; bottom: 0;
}
.CodeMirror-gutter-filler {
  left: 0; bottom: 0;
}

.CodeMirror-gutters {
  position: absolute; left: 0; top: 0;
  padding-bottom: 30px;
  z-index: 3;
}
.CodeMirror-gutter {
  white-space: normal;
  height: 100%;
  -moz-box-sizing: content-box;
  box-sizing: content-box;
  padding-bottom: 30px;
  margin-bottom: -32px;
  display: inline-block;
  /* Hack to make IE7 behave */
  *zoom:1;
  *display:inline;
}
.CodeMirror-gutter-elt {
  position: absolute;
  cursor: default;
  z-index: 4;
}

.CodeMirror-lines {
  cursor: text;
}
.CodeMirror pre {
  /* Reset some styles that the rest of the page might have set */
  -moz-border-radius: 0; -webkit-border-radius: 0; border-radius: 0;
  border-width: 0;
  background: transparent;
  font-family: inherit;
  font-size: inherit;
  margin: 0;
  white-space: pre;
  word-wrap: normal;
  line-height: inherit;
  color: inherit;
  z-index: 2;
  position: relative;
  overflow: visible;
}
.CodeMirror-wrap pre {
  word-wrap: break-word;
  white-space: pre-wrap;
  word-break: normal;
}

.CodeMirror-linebackground {
  position: absolute;
  left: 0; right: 0; top: 0; bottom: 0;
  z-index: 0;
}

.CodeMirror-linewidget {
  position: relative;
  z-index: 2;
  overflow: auto;
}

.CodeMirror-widget {}

.CodeMirror-wrap .CodeMirror-scroll {
  overflow-x: hidden;
}

.CodeMirror-measure {
  position: absolute;
  width: 100%;
  height: 0;
  overflow: hidden;
  visibility: hidden;
}
.CodeMirror-measure pre { position: static; }

.CodeMirror div.CodeMirror-cursor {
  position: absolute;
  border-right: none;
  width: 0;
}

div.CodeMirror-cursors {
  visibility: hidden;
  position: relative;
  z-index: 1;
}
.CodeMirror-focused div.CodeMirror-cursors {
  visibility: visible;
}

.CodeMirror-selected { background: #d9d9d9; }
.CodeMirror-focused .CodeMirror-selected { background: #d7d4f0; }
.CodeMirror-crosshair { cursor: crosshair; }

.cm-searching {
  background: #ffa;
  background: rgba(255, 255, 0, .4);
}

/* IE7 hack to prevent it from returning funny offsetTops on the spans */
.CodeMirror span { *vertical-align: text-bottom; }

/* Used to force a border model for a node */
.cm-force-border { padding-right: .1px; }

@media print {
  /* Hide the cursor when printing */
  .CodeMirror div.CodeMirror-cursors {
    visibility: hidden;
  }
}
</style>
<style>html{
overflow-y:scroll;
}
a{
color: #5F6D79;
}
.login,.edit{
margin:50px auto;
width:500px;
}
.edit{
	width:800px;
}
.header{
line-height:34px;
height:34px;
background-image: -webkit-linear-gradient(top,#d9edf7 0,#c4e3f3 100%);
background-image: linear-gradient(to bottom,#d9edf7 0,#c4e3f3 100%);
background-repeat: repeat-x;
filter: progid:DXImageTransform.Microsoft.gradient(startColorstr='#ffd9edf7', endColorstr='#ffc4e3f3', GradientType=0);
padding-left:10px;
}
.icon{
background:no-repeat 0px 0px transparent;
display:inline-block;
width:16px;
height:16px;
vertical-align:middle;
background-image: url(data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAJAAAAAQCAYAAAD59vZgAAAAGXRFWHRTb2Z0d2FyZQBBZG9iZSBJbWFnZVJlYWR5ccllPAAAA2ZpVFh0WE1MOmNvbS5hZG9iZS54bXAAAAAAADw/eHBhY2tldCBiZWdpbj0i77u/IiBpZD0iVzVNME1wQ2VoaUh6cmVTek5UY3prYzlkIj8+IDx4OnhtcG1ldGEgeG1sbnM6eD0iYWRvYmU6bnM6bWV0YS8iIHg6eG1wdGs9IkFkb2JlIFhNUCBDb3JlIDUuMy1jMDExIDY2LjE0NTY2MSwgMjAxMi8wMi8wNi0xNDo1NjoyNyAgICAgICAgIj4gPHJkZjpSREYgeG1sbnM6cmRmPSJodHRwOi8vd3d3LnczLm9yZy8xOTk5LzAyLzIyLXJkZi1zeW50YXgtbnMjIj4gPHJkZjpEZXNjcmlwdGlvbiByZGY6YWJvdXQ9IiIgeG1sbnM6eG1wTU09Imh0dHA6Ly9ucy5hZG9iZS5jb20veGFwLzEuMC9tbS8iIHhtbG5zOnN0UmVmPSJodHRwOi8vbnMuYWRvYmUuY29tL3hhcC8xLjAvc1R5cGUvUmVzb3VyY2VSZWYjIiB4bWxuczp4bXA9Imh0dHA6Ly9ucy5hZG9iZS5jb20veGFwLzEuMC8iIHhtcE1NOk9yaWdpbmFsRG9jdW1lbnRJRD0ieG1wLmRpZDozMjg4RUUyREU3REZFMzExQURBNDkwODg5MjBFREZCRiIgeG1wTU06RG9jdW1lbnRJRD0ieG1wLmRpZDpEQUU5N0IzOURGRkYxMUUzQjZGRkYwQzcwQUMyMTIxRiIgeG1wTU06SW5zdGFuY2VJRD0ieG1wLmlpZDpEQUU5N0IzOERGRkYxMUUzQjZGRkYwQzcwQUMyMTIxRiIgeG1wOkNyZWF0b3JUb29sPSJBZG9iZSBQaG90b3Nob3AgQ1M2IChXaW5kb3dzKSI+IDx4bXBNTTpEZXJpdmVkRnJvbSBzdFJlZjppbnN0YW5jZUlEPSJ4bXAuaWlkOjM3ODhFRTJERTdERkUzMTFBREE0OTA4ODkyMEVERkJGIiBzdFJlZjpkb2N1bWVudElEPSJ4bXAuZGlkOjMyODhFRTJERTdERkUzMTFBREE0OTA4ODkyMEVERkJGIi8+IDwvcmRmOkRlc2NyaXB0aW9uPiA8L3JkZjpSREY+IDwveDp4bXBtZXRhPiA8P3hwYWNrZXQgZW5kPSJyIj8+gNJxFgAAB7RJREFUeNq0Wn1QFVUUv8hDBJQQHRxshkggxMiADDSMsgxKkCTKUKTJhLJy+hizf8B/amqaxnKaZszJpgTTjCww5SNwsrK0oommr6kBSiJLim/oKYhs5yznPg+X3X1v4XFmfvPu7t6ze+/e3z33d88+H03TxGTMx8fH7NJlgN+ofCWgz+I2iwGxFtePAbqpHAUIBnwH0Kaw/U5AAJVXA46a1LPTgHEPw/ZTG+IA9wFSABGAUKVqF+APwJeAMsAv6FtWVja5FyBEIqBxor4O4T3zBQQxoixmLwHLn1MZB38AMELH11IHzEbyRcB7jJRfAOYRqTLwPjAA2I9SQAjgbsA5k3sFAg4BzgAeggEYsehPgEl5nG3fukucPdtp+XL27N9udflJ6qfVeMymybMCsA3wFOAVvFBQUCDsTiQk7b59+7DYuH79+gkN+IEDBxodFjPQzKYBdgEiAXfRTEXyVAKWUudwoLYyn72Al2hgdwBOAe4kEl1F5PmTznP7FvACla8HXAHIBBwGrARcB2gAvAWQb+Ew3fucAXmOAG5hkeNBb8ycZ3Y8LFpbW4Wvr6+YNm2aC/qsonNuCPScG/KohnWflwRC8vT19dlqc3BwsKs8MjIizp8/b8t/xowZrobYJc/rgE10XEFRAO+WDJhLg6laFJFOWjL5ONk5JM9ak+diWP+IItBOQDYtKb10PZzVvY2urWb3V8mDNt+L0VfU1dXpsxrJkpWVJWpqalwzPTs72507RrhhG+MxTH1yLYPNzc1icHDQI2d/f3+RmJg4Zhm9ePGirf7KiOewSZ49gAfYuTP0+x+F4f0e3usJhTxWhlGtlsgjw/0pilwDTKN8SFFJEFHqAXdQpMHRTGX3xLr3eJNAmZmZrsiDUUeSRpLKQi/JJSCGNNDNpIHmsj7jROkgDXQcgGvP7zyCLFmyxFZ7L1y4MCYCDQ8P6+XCwkJRVFQkUlJSLP3Rxw6BsN4B5aVjRNnCo6KdCOphvRto8Hn9fsBfjDyClqscQDXgRuZ7jGbrMkWQ5wGGvEmg2tpaF1mQPFVVVaOzjo7RivKftdJFp3E1JNjWM9XV1WJoaEh/Hi4vckkyKjscDpGenm4YgRoaGkRTU5OIj48XGzZsEMnJyZYRSIbPt5nWMBLHpTR7JErpvBR3ywFNSp0jtPtaAKhSrjWRz2wiJZ4rV56bSoKc+/UpkWS2Ih4DKPJoJsDIM115CU6L+u6QRTtBrb29Xevo6NC6urq07u5urbe3V0d/f7/mdDr1+liH12O7yInspHTf0tJSDYijcauvr3dbRh/0RWAb29radERERGgJCQk6Fi1apOXm5moVFRWu6xLog74OmuE3sWXqaYU8qGkKFEG8ie2ikAxzDDr4CKCNlU+za9GAE4B/AY8a+K4kMRzIznWSvpFbTtRCJ9kOjUeiT0hgcztpEnkCJhF4XL4YcXD2S81z9OilXX9OTo7+a1MX2TJ8Hi5Lfn5+IjQ01PV8s3JGRsaYaCKXMG7Tp08XLS0toqSkRMTGxor8/HyRlJQ0TgP9zAi0jV5wCYs8+eyeSKZCRh7LZdKk7IndopCnj3SOJM9jpKNOeppvockxZYaEkATiy5bchck6Brpo0oaDicKdC3p5bFbG5U4aLmtWIjomJkakpaXpOzdZTy6LDtIxwYwoxYCZgMsppyLtTVzGDciA4u9qIlc0O78bcy00mLsVn2bARsBPTPhK+4rI0Up+KCBvpwSatOUWuRLUOEkmYryOhHW/QlZfg/r1rIzi5TODOo1qBDLSQGvWrHHVUXWRt6y8vFwnBUaNkJAQ/RjNrCzbpIpobnFxcboGio6O1kmkb/+oHhfRI6T+cUqsI9/HlXu9SueM1upuShJinuc1dj6L7dJU28ESi6ol005jBQ1cFZHKnc2kZY+T5wT1cRnTVR9QGkDmiY57cO8fiJhuI5DchckB4jkhSRpZx1uGg7l27aUMyMGDB0VeXp5lmed90F9GFmxXVFSUSE1N1X/nzx/NdoDmEYGBgaa7sBGKCKhl0pX2vUERwZ3Q67fRZ3d1/Ym0nu5NgylPtJSdwxzVvRQBD9ESKPVVvUEkmorPIeN0iqqLvGFIyMrKSpcGCgsL04/RzMqrVq0yjECYH8LMdHh4+LjtPo9SRtv4QRKgPOGGeZ/NHmgYzFe8bKPPOylz7fRQv7izSoU8NRRNZe/zlH6lUlpitTf4Y7ZD6uzsHKOL1Oy0twzvHxkZOeYcCmarMic9Rh9JjuLiYjEwMDCGOEYEkhFLzQM56aWWkwbZ4uEWE+Phj2L0mxf/lLGArrconzK+Jh9uaYrusLJrlGO+VNbSJ5ZBg34dpaUR7W8xxeaJLmLmxwjvznyNotBkRLgkBC5VZtbT0yNmzZo1dhdm8BHOSfrFTvjGwbpVuYSE+pTK9zPN8y6RR2NpACzPI3jcb8CvVN5Iv7Mo2hh92JH9ep/SC5unmkCe6CKyd8ToN729pPfaxejX9x66HkQaL5404jo1679w4cIJtxPJM2fOaCYmKCjIsq78BiYJ5zOFf4cIZuSIcaM3MHKF2Xz0P4Dvp7D9K9hMb6Q8lB1yezr7fagNOAFzSffhuwhhnzJ6aDeKxPqGJsHHXvo7R4IY/WvMhHz/F2AAX1VZ2r8/WO0AAAAASUVORK5CYII=);
}

.icon.icon_ok{background-position:-0px 0px;}
.icon.icon_setting{background-position:-16px 0px;}
.icon.icon_edit{background-position:-32px 0px;}
.icon.icon_delete{background-position:-48px 0px;}
.icon.icon_add{background-position:-64px 0px;}
.icon.icon_tables{background-position:-80px 0px;}
.icon.icon_db{background-position:-96px 0px;}
.icon.icon_table{background-position:-112px 0px;}
.icon.icon_play{background-position:-128px 0px;}

.header a{
	text-decoration:underline;
	margin-right:10px;
	color:#5F6D79;
}
.workbox{
	padding:10px;
}
.cm-s-default.CodeMirror,textarea{
	display: block;
	font-size: 14px;
	color: #555;
	background-color: #fff;
	background-image: none;
	border: 1px solid #ccc;
	border-radius: 0px;
	-webkit-box-shadow: inset 0 1px 1px rgba(0,0,0,.075);
	box-shadow: inset 0 1px 1px rgba(0,0,0,.075);
	-webkit-transition: border-color ease-in-out .15s,box-shadow ease-in-out .15s;
	transition: border-color ease-in-out .15s,box-shadow ease-in-out .15s;
	height: auto;
	min-height:100px;
	padding-bottom:20px;
}
.btn{border-radius: 0px;}
.time_for_query{
border:1px dotted #ccc;
border-width:1px 0px;
margin:10px 0px;
}
.CodeMirror-hscrollbar >div{

}
th{
	background:#f5f5f5;
}
.result .alert{
	margin:10px;
}
.result{
	
}
pre {
overflow: inherit; 
display: block;
padding: 0px;
margin: 0;
font-size: 1em;
line-height: 1;
word-break: break-all;
word-wrap: break-word;
color: inherit;
background-color: transparent;
border: 0;
border-radius: 0;
font-family: "Helvetica Neue",Helvetica,Arial,sans-serif;
}
.already_executed{
padding: 5px;
margin: 10px 0px;
}
.already_executed,.already_executed pre{
	padding: 5px;
	border:1px solid #ccc;
	background:rgba(127,127,127,0.1) !important;
}
#result_table{
margin:0px;
}
#result_table input[type=checkbox]{
vertical-align:middle;
margin:0px;
}

.table,.panel,.form-control,.alert {
	border-radius:0px !important;
}
.footer{
padding:5px 10px;
background:#f5f5f5;
border:1px solid #ccc;
border-width:1px 0px;
text-align:right;
}
</style>
</head>
<body>
<div class="header panel-success ">
	<a href="http://xdsoft.net/miniMySQLAdmin/"><strong>miniMySQLAdmin</strong> <span style="opacity:0.7;">(version:<? 
echo '1.1.3';?>)</span></a>
	<?php if( $connected ){ ?>
	<a href="?sql=<?php echo __('show databases;'); ?>">Databases</a>
	<?php } ?>
	<?php if( $database_selected ){ ?>
		<i class="icon icon_db"></i> <a href="?sql=<?php echo __('show tables;');?>"><?php echo $_SESSION['dbname']?></a>
		<a href="?sql=<?php echo __('show variables;');?>">Variables</a>
		<?php if( $_GET['table'] ){ ?>
			<a href="?table=<?php echo $_SESSION['table']?>&sql=<?php echo __('SHOW COLUMNS FROM `'.$db->_($_SESSION['table']).'`;');?>">Structure</a>
			<i class="icon icon_table"></i> <a href="?table=<?php echo $_SESSION['table']?>">Data</a>
			<a href="?table=<?php echo $_SESSION['table']?>&sql=<?php echo __('SHOW INDEX FROM `'.$db->_($_SESSION['table']).'`;');?>">Indexes</a>
		<?php }?>
	<?php } ?>

	<a style="float:right" href="?action=login" title="Settings"><i class="icon icon_setting"></i></a>
</div>
<?php
switch( $action ){
case 'edit':?>
<div class="edit panel panel-info">
  <div class="panel-heading">Edit Row</div>
  <div class="panel-body">
	<?php if( $data['error'] ){ ?>
		<div class="alert alert-danger"><?php echo $data['error']?></div>
	<?php } ?>
	<form class="form-horizontal" role="form" method="post" onsubmit="return confirm('Are you shure?')">
		<input type="hidden" name="action" value="save">
		<input type="hidden" name="table" value="<?php echo mysql_field_table($inq, 0);?>">
		<?php
		
		$row = $db->__($inq);
		$i=0;
		$primary_finded = false;
		
		foreach($row as $key=>$value){?>
		<div class="form-group">
			<label for="key_<?php echo $key?>" class="col-sm-3 control-label"><?php echo $key?></label>
			<div class="col-sm-9">
				<?php 
				echo get_input($inq,mysql_field_table($inq, 0),mysql_field_type ($inq, $i),$key,$value);
				
				if(is_primary($inq,$i) && !$primary_finded){
					$primary_finded  = true;
				?>
					<input type="hidden" name="primary_key" value="<?php echo htmlspecialchars($key);?>">
					<input type="hidden" name="primary_value" value="<?php echo htmlspecialchars($value);?>"><?}
				?>
			</div>
		</div>
		<? $i++;
		}?>
		<div class="form-group">
			<div class="col-sm-offset-3 col-sm-10">
			  <button type="submit" name="save" class="btn btn-primary">&nbsp;&nbsp;&nbsp;Save&nbsp;&nbsp;&nbsp;</button>
			  <button type="submit" name="close" class="btn btn-success">&nbsp;&nbsp;&nbsp;Save&amp;Close&nbsp;&nbsp;&nbsp;</button>
			  <button type="reset" class="btn btn-default">&nbsp;&nbsp;&nbsp;Reset&nbsp;&nbsp;&nbsp;</button>
			  <button type="button" onclick="document.location='?table=<?php echo __($_GET['table']);?>';" class="btn btn-danger">&nbsp;&nbsp;&nbsp;Cancel&nbsp;&nbsp;&nbsp;</button>
			</div>
		</div>
	</form>
  </div>
</div>
<?php
break;
case 'add':?>
<div class="edit panel panel-info">
  <div class="panel-heading">Add Record</div>
  <div class="panel-body">
	<?php if( $data['error'] ){ ?>
		<div class="alert alert-danger"><?php echo $data['error']?></div>
	<?php } ?>
	<form class="form-horizontal" role="form" method="post" onsubmit="return confirm('Are you shure?')">
		<input type="hidden" name="action" value="save">
		<input type="hidden" name="table" value="<?php echo mysql_field_table($inq, 0);?>">
		<?php
		$row = $db->__($inq);
		$i=0;
		$primary_finded = false;
		
		foreach($row as $key=>$value){?>
		<div class="form-group">
			<label for="key_<?php echo $key?>" class="col-sm-3 control-label"><?php echo $key?></label>
			<div class="col-sm-9">
				<?php 
				echo get_input($inq,mysql_field_table($inq, 0),mysql_field_type ($inq, $i),$key,'');
				if(is_primary($inq,$i) && !$primary_finded){
					$primary_finded  = true;
					?>
					<input type="hidden" name="primary_key" value="<?php echo htmlspecialchars($key);?>">
					<?
				}
				?>
			</div>
		</div>
		<?php 
		$i++;
		}?>
		<div class="form-group">
			<div class="col-sm-offset-3 col-sm-10">
			  <button type="submit" name="save" class="btn btn-primary">&nbsp;&nbsp;&nbsp;Save&nbsp;&nbsp;&nbsp;</button>
			  <button type="submit" name="close" class="btn btn-success">&nbsp;&nbsp;&nbsp;Save&amp;Close&nbsp;&nbsp;&nbsp;</button>
			  <button type="reset" class="btn btn-default">&nbsp;&nbsp;&nbsp;Reset&nbsp;&nbsp;&nbsp;</button>
			  <button type="button" onclick="document.location='?table=<?php echo __($_GET['table']);?>';" class="btn btn-danger">&nbsp;&nbsp;&nbsp;Cancel&nbsp;&nbsp;&nbsp;</button>
			</div>
		</div>
	</form>
  </div>
</div>
<?php
break;
case 'login':?>
<div class="login panel panel-info">
  <div class="panel-heading">DB Connection Settings</div>
  <div class="panel-body">
	<?if($data['error']){?>
	<div class="alert alert-danger"><?php echo $data['error']?></div>
	<?}?>
	<form class="form-horizontal" role="form" method="post">
		<input type="hidden" name="action" value="connect">
		<div class="form-group">
			<label for="inputusername3" class="col-sm-4 control-label">Username</label>
			<div class="col-sm-7">
				<input type="text" class="form-control" name="username" id="inputusername3" placeholder="DB user name" value="<?php echo $_REQUEST['username']?htmlspecialchars($_REQUEST['username']):'root'?>">
			</div>
		</div>
		<div class="form-group">
			<label for="inputPassword3" class="col-sm-4 control-label">Password</label>
			<div class="col-sm-7">
			  <input type="password" class="form-control" name="password" id="inputPassword3" placeholder="Password" value="<?php echo $_REQUEST['password']?htmlspecialchars($_REQUEST['password']):''?>">
			</div>
		</div>
		<div class="form-group">
			<label for="host" class="col-sm-4 control-label">Host</label>
			<div class="col-sm-7">
				<input type="text" class="form-control" id="host" name="host" placeholder="MySQL host" value="<?php echo $_REQUEST['host']?htmlspecialchars($_REQUEST['host']):'localhost'?>">
			</div>
		</div>
		<div class="form-group">
			<label for="charset" class="col-sm-4 control-label">Charset</label>
			<div class="col-sm-7">
				<select id="charset" class="form-control" name="charset">
				  <option value="">- default -</option>
				  <option value="utf8" selected="">utf8</option>
				  <option value="cp1251">cp1251</option>
				</select>
			</div>
		</div>
		<hr>
		<div class="form-group ">
			<label for="charset" class="col-sm-4 control-label">or try to connect through</label>
			<div class="col-sm-7">
				<select id="through" class="form-control "  <?php echo variant_connect_finded()==''?'disabled':''?> name="through">
					<option>Select available connection</option>
					<?php echo variant_connect_finded();?>
				</select>
			</div>
		</div>
		<div class="form-group">
			<div class="col-sm-offset-4 col-sm-8">
				<div class="checkbox">
					<label>
						<input name="remember" value="yes" type="checkbox"> Remember in cookies
					</label>
				</div>
			</div>
		</div>
		<div class="form-group">
			<div class="col-sm-offset-4 col-sm-8">
				<button type="submit" class="btn btn-primary">&nbsp;&nbsp;&nbsp;Try to connect&nbsp;&nbsp;&nbsp;</button>
				<?php if( $connected ){ ?>
				<button type="submit" name="action" value="index" class="btn btn-danger">&nbsp;&nbsp;&nbsp;Cancel&nbsp;&nbsp;&nbsp;</button>
				<?php } ?>
			</div>
		</div>
	</form>
  </div>
</div>
<?break;
case 'index':?>
<div class="workbox">
	<form role="form" method="post">
		<input type="hidden" name="action" value="index">
		<pre class="already_executed"><?php echo SqlFormatter::format(_trim($db->last()));?></pre>
		<div class="form-group">
			<textarea id="sql" name="sql" placeholder="SQL..." class="form-control" rows="5"><?php echo htmlspecialchars(_trim($sql));?></textarea>
		</div>
		<div class="form-group">
			<div>
			  <button type="submit" class="btn btn-default"><i class="icon icon_play"></i> Exec</button>
			</div>
		</div>
	</form>
	<?php if($data['error']){?>
	<div class="alert alert-danger"><?php echo $data['error']?></div>
	<?php }else{ ?>
		<div class="time_for_query">time:<?php echo round($endtime,5);?> sec, affect:<?php echo $db->affect();?></div>
		<?php
		if( $inq and $inq!==true ){
			$numfields =  mysql_num_fields($inq);
			?>
			<table id="result_table" class="table table-hover table-condensed table-bordered table-striped">
				<tr>
					<?php
					$primary_key = '';
					for ($i=0; $i < $numfields; $i++){
						$primary_key=='' and is_primary($inq,$i) and $primary_key = mysql_field_name($inq, $i);
						echo '<th>'.analize_header($sql,mysql_field_name($inq, $i),$primary_key==mysql_field_name($inq, $i),mysql_field_table($inq, 0)).'</th>';
					}
					?>
				</tr>
				<?php
				while ($row = $db->__($inq)){ 
					?><tr><?
						$i=0;
						foreach($row as $key=>$value){
							echo '<td><pre>'.analize($sql,$value,$key,$primary_key==$key,mysql_field_table($inq, 0)).'</pre></td>';
							$i++;
						}
					?></tr><? 
				}
				?>
			</table>
			<?php if($_GET['table']){?>
			<div style="margin:5px 0px;">
			<?php if( $primary_key ){?>
				<a class="btn btn-default" onclick="return deleteAllSelected.call(this)" href="?table=<?php echo mysql_field_table($inq, 0)?>&action=delete&key=<?php echo __($primary_key)?>"><i class="icon icon_delete"></i></a>
			<?php } ?>
				<a class="btn btn-default"  href="?table=<?php echo mysql_field_table($inq, 0)?>&action=add"><i class="icon icon_add"></i></a>
			</div>
			<?php } ?>
		<?php }else{ ?>
			<?php if($inq!==true){ ?>
				<div class="alert alert-danger"><?php echo $db->error();?></div>
			<?php }else{ ?>
				<div class="alert alert-success">Success</div>
			<?php } ?>
		<?php }
	}?>
	</div>
</div>
<?break;
}
?>
<div class="footer">author: <a href="http://xdsoft.net"><strong>Chupurnov Valeriy</strong></a> mail:<a href="mailto:chupurnov@gmail.com"><strong>chupurnov@gmail.com</strong></a> Donate: paypal <strong>skoder@ya.ru</strong>, Yandex Money <strong>41001437985378</strong>, WMR <strong>R292055463147</strong>, WMZ <strong>Z326592306965</strong></div>
<script type="text/javascript">!function(a){if("object"==typeof exports&&"object"==typeof module)module.exports=a();else{if("function"==typeof define&&define.amd)return define([],a);this.CodeMirror=a()}}(function(){"use strict";function y(a,c){if(!(this instanceof y))return new y(a,c);this.options=c=c||{},Eg(Zd,c,!1),M(c);var d=c.value;"string"==typeof d&&(d=new yf(d,c.mode)),this.doc=d;var e=this.display=new z(a,d);e.wrapper.CodeMirror=this,I(this),G(this),c.lineWrapping&&(this.display.wrapper.className+=" CodeMirror-wrap"),c.autofocus&&!q&&Qc(this),this.state={keyMaps:[],overlays:[],modeGen:0,overwrite:!1,focused:!1,suppressEdits:!1,pasteIncoming:!1,cutIncoming:!1,draggingText:!1,highlight:new ug},b&&setTimeout(Fg(Pc,this,!0),20),Tc(this),Yg();var f=this;zc(this,function(){f.curOp.forceUpdate=!0,Cf(f,d),c.autofocus&&!q||Rg()==e.input?setTimeout(Fg(vd,f),20):wd(f);for(var a in $d)$d.hasOwnProperty(a)&&$d[a](f,c[a],ae);for(var b=0;b<ee.length;++b)ee[b](f)})}function z(a,b){var d=this,e=d.input=Mg("textarea",null,null,"position: absolute; padding: 0; width: 1px; height: 1em; outline: none");h?e.style.width="1000px":e.setAttribute("wrap","off"),p&&(e.style.border="1px solid black"),e.setAttribute("autocorrect","off"),e.setAttribute("autocapitalize","off"),e.setAttribute("spellcheck","false"),d.inputDiv=Mg("div",[e],null,"overflow: hidden; position: relative; width: 3px; height: 0px;"),d.scrollbarH=Mg("div",[Mg("div",null,null,"height: 100%; min-height: 1px")],"CodeMirror-hscrollbar"),d.scrollbarV=Mg("div",[Mg("div",null,null,"min-width: 1px")],"CodeMirror-vscrollbar"),d.scrollbarFiller=Mg("div",null,"CodeMirror-scrollbar-filler"),d.gutterFiller=Mg("div",null,"CodeMirror-gutter-filler"),d.lineDiv=Mg("div",null,"CodeMirror-code"),d.selectionDiv=Mg("div",null,null,"position: relative; z-index: 1"),d.cursorDiv=Mg("div",null,"CodeMirror-cursors"),d.measure=Mg("div",null,"CodeMirror-measure"),d.lineMeasure=Mg("div",null,"CodeMirror-measure"),d.lineSpace=Mg("div",[d.measure,d.lineMeasure,d.selectionDiv,d.cursorDiv,d.lineDiv],null,"position: relative; outline: none"),d.mover=Mg("div",[Mg("div",[d.lineSpace],"CodeMirror-lines")],null,"position: relative"),d.sizer=Mg("div",[d.mover],"CodeMirror-sizer"),d.heightForcer=Mg("div",null,null,"position: absolute; height: "+pg+"px; width: 1px;"),d.gutters=Mg("div",null,"CodeMirror-gutters"),d.lineGutter=null,d.scroller=Mg("div",[d.sizer,d.heightForcer,d.gutters],"CodeMirror-scroll"),d.scroller.setAttribute("tabIndex","-1"),d.wrapper=Mg("div",[d.inputDiv,d.scrollbarH,d.scrollbarV,d.scrollbarFiller,d.gutterFiller,d.scroller],"CodeMirror"),c&&(d.gutters.style.zIndex=-1,d.scroller.style.paddingRight=0),p&&(e.style.width="0px"),h||(d.scroller.draggable=!0),m&&(d.inputDiv.style.height="1px",d.inputDiv.style.position="absolute"),c&&(d.scrollbarH.style.minHeight=d.scrollbarV.style.minWidth="18px"),a.appendChild?a.appendChild(d.wrapper):a(d.wrapper),d.viewFrom=d.viewTo=b.first,d.view=[],d.externalMeasured=null,d.viewOffset=0,d.lastSizeC=0,d.updateLineNumbers=null,d.lineNumWidth=d.lineNumInnerWidth=d.lineNumChars=null,d.prevInput="",d.alignWidgets=!1,d.pollingFast=!1,d.poll=new ug,d.cachedCharWidth=d.cachedTextHeight=d.cachedPaddingH=null,d.inaccurateSelection=!1,d.maxLine=null,d.maxLineLength=0,d.maxLineChanged=!1,d.wheelDX=d.wheelDY=d.wheelStartX=d.wheelStartY=null,d.shift=!1,d.selForContextMenu=null}function A(a){a.doc.mode=y.getMode(a.options,a.doc.modeOption),B(a)}function B(a){a.doc.iter(function(a){a.stateAfter&&(a.stateAfter=null),a.styles&&(a.styles=null)}),a.doc.frontier=a.doc.first,Sb(a,100),a.state.modeGen++,a.curOp&&Fc(a)}function C(a){a.options.lineWrapping?(Ug(a.display.wrapper,"CodeMirror-wrap"),a.display.sizer.style.minWidth=""):(Tg(a.display.wrapper,"CodeMirror-wrap"),L(a)),E(a),Fc(a),ic(a),setTimeout(function(){O(a)},100)}function D(a){var b=uc(a.display),c=a.options.lineWrapping,d=c&&Math.max(5,a.display.scroller.clientWidth/vc(a.display)-3);return function(e){if(Ue(a.doc,e))return 0;var f=0;if(e.widgets)for(var g=0;g<e.widgets.length;g++)e.widgets[g].height&&(f+=e.widgets[g].height);return c?f+(Math.ceil(e.text.length/d)||1)*b:f+b}}function E(a){var b=a.doc,c=D(a);b.iter(function(a){var b=c(a);b!=a.height&&Gf(a,b)})}function F(a){var b=je[a.options.keyMap],c=b.style;a.display.wrapper.className=a.display.wrapper.className.replace(/\s*cm-keymap-\S+/g,"")+(c?" cm-keymap-"+c:"")}function G(a){a.display.wrapper.className=a.display.wrapper.className.replace(/\s*cm-s-\S+/g,"")+a.options.theme.replace(/(^|\s)\s*/g," cm-s-"),ic(a)}function H(a){I(a),Fc(a),setTimeout(function(){Q(a)},20)}function I(a){var b=a.display.gutters,c=a.options.gutters;Og(b);for(var d=0;d<c.length;++d){var e=c[d],f=b.appendChild(Mg("div",null,"CodeMirror-gutter "+e));"CodeMirror-linenumbers"==e&&(a.display.lineGutter=f,f.style.width=(a.display.lineNumWidth||1)+"px")}b.style.display=d?"":"none",J(a)}function J(a){var b=a.display.gutters.offsetWidth;a.display.sizer.style.marginLeft=b+"px",a.display.scrollbarH.style.left=a.options.fixedGutter?b+"px":0}function K(a){if(0==a.height)return 0;for(var c,b=a.text.length,d=a;c=Ne(d);){var e=c.find(0,!0);d=e.from.line,b+=e.from.ch-e.to.ch}for(d=a;c=Oe(d);){var e=c.find(0,!0);b-=d.text.length-e.from.ch,d=e.to.line,b+=d.text.length-e.to.ch}return b}function L(a){var b=a.display,c=a.doc;b.maxLine=Df(c,c.first),b.maxLineLength=K(b.maxLine),b.maxLineChanged=!0,c.iter(function(a){var c=K(a);c>b.maxLineLength&&(b.maxLineLength=c,b.maxLine=a)})}function M(a){var b=Bg(a.gutters,"CodeMirror-linenumbers");-1==b&&a.lineNumbers?a.gutters=a.gutters.concat(["CodeMirror-linenumbers"]):b>-1&&!a.lineNumbers&&(a.gutters=a.gutters.slice(0),a.gutters.splice(b,1))}function N(a){var b=a.display.scroller;return{clientHeight:b.clientHeight,barHeight:a.display.scrollbarV.clientHeight,scrollWidth:b.scrollWidth,clientWidth:b.clientWidth,barWidth:a.display.scrollbarH.clientWidth,docHeight:Math.round(a.doc.height+Xb(a.display))}}function O(a,b){b||(b=N(a));var c=a.display,d=b.docHeight+pg,e=b.scrollWidth>b.clientWidth,f=d>b.clientHeight;if(f?(c.scrollbarV.style.display="block",c.scrollbarV.style.bottom=e?ah(c.measure)+"px":"0",c.scrollbarV.firstChild.style.height=Math.max(0,d-b.clientHeight+(b.barHeight||c.scrollbarV.clientHeight))+"px"):(c.scrollbarV.style.display="",c.scrollbarV.firstChild.style.height="0"),e?(c.scrollbarH.style.display="block",c.scrollbarH.style.right=f?ah(c.measure)+"px":"0",c.scrollbarH.firstChild.style.width=b.scrollWidth-b.clientWidth+(b.barWidth||c.scrollbarH.clientWidth)+"px"):(c.scrollbarH.style.display="",c.scrollbarH.firstChild.style.width="0"),e&&f?(c.scrollbarFiller.style.display="block",c.scrollbarFiller.style.height=c.scrollbarFiller.style.width=ah(c.measure)+"px"):c.scrollbarFiller.style.display="",e&&a.options.coverGutterNextToScrollbar&&a.options.fixedGutter?(c.gutterFiller.style.display="block",c.gutterFiller.style.height=ah(c.measure)+"px",c.gutterFiller.style.width=c.gutters.offsetWidth+"px"):c.gutterFiller.style.display="",!a.state.checkedOverlayScrollbar&&b.clientHeight>0){if(0===ah(c.measure)){var g=r&&!n?"12px":"18px";c.scrollbarV.style.minWidth=c.scrollbarH.style.minHeight=g;var h=function(b){cg(b)!=c.scrollbarV&&cg(b)!=c.scrollbarH&&Ac(a,Xc)(b)};eg(c.scrollbarV,"mousedown",h),eg(c.scrollbarH,"mousedown",h)}a.state.checkedOverlayScrollbar=!0}}function P(a,b,c){var d=c&&null!=c.top?Math.max(0,c.top):a.scroller.scrollTop;d=Math.floor(d-Wb(a));var e=c&&null!=c.bottom?c.bottom:d+a.wrapper.clientHeight,f=If(b,d),g=If(b,e);if(c&&c.ensure){var h=c.ensure.from.line,i=c.ensure.to.line;if(f>h)return{from:h,to:If(b,Jf(Df(b,h))+a.wrapper.clientHeight)};if(Math.min(i,b.lastLine())>=g)return{from:If(b,Jf(Df(b,i))-a.wrapper.clientHeight),to:i}}return{from:f,to:Math.max(g,f+1)}}function Q(a){var b=a.display,c=b.view;if(b.alignWidgets||b.gutters.firstChild&&a.options.fixedGutter){for(var d=T(b)-b.scroller.scrollLeft+a.doc.scrollLeft,e=b.gutters.offsetWidth,f=d+"px",g=0;g<c.length;g++)if(!c[g].hidden){a.options.fixedGutter&&c[g].gutter&&(c[g].gutter.style.left=f);var h=c[g].alignable;if(h)for(var i=0;i<h.length;i++)h[i].style.left=f}a.options.fixedGutter&&(b.gutters.style.left=d+e+"px")}}function R(a){if(!a.options.lineNumbers)return!1;var b=a.doc,c=S(a.options,b.first+b.size-1),d=a.display;if(c.length!=d.lineNumChars){var e=d.measure.appendChild(Mg("div",[Mg("div",c)],"CodeMirror-linenumber CodeMirror-gutter-elt")),f=e.firstChild.offsetWidth,g=e.offsetWidth-f;return d.lineGutter.style.width="",d.lineNumInnerWidth=Math.max(f,d.lineGutter.offsetWidth-g),d.lineNumWidth=d.lineNumInnerWidth+g,d.lineNumChars=d.lineNumInnerWidth?c.length:-1,d.lineGutter.style.width=d.lineNumWidth+"px",J(a),!0}return!1}function S(a,b){return String(a.lineNumberFormatter(b+a.firstLineNumber))}function T(a){return a.scroller.getBoundingClientRect().left-a.sizer.getBoundingClientRect().left}function U(a,b,c){for(var f,d=a.display.viewFrom,e=a.display.viewTo,g=P(a.display,a.doc,b),i=!0;;i=!1){var j=a.display.scroller.clientWidth;if(!V(a,g,c))break;f=!0,a.display.maxLineChanged&&!a.options.lineWrapping&&W(a);var k=N(a);if(Ob(a),X(a,k),O(a,k),h&&a.options.lineWrapping&&Y(a,k),i&&a.options.lineWrapping&&j!=a.display.scroller.clientWidth)c=!0;else if(c=!1,b&&null!=b.top&&(b={top:Math.min(k.docHeight-pg-k.clientHeight,b.top)}),g=P(a.display,a.doc,b),g.from>=a.display.viewFrom&&g.to<=a.display.viewTo)break}return a.display.updateLineNumbers=null,f&&(jg(a,"update",a),(a.display.viewFrom!=d||a.display.viewTo!=e)&&jg(a,"viewportChange",a,a.display.viewFrom,a.display.viewTo)),f}function V(a,b,c){var d=a.display,e=a.doc;if(!d.wrapper.offsetWidth)return Hc(a),void 0;if(!(!c&&b.from>=d.viewFrom&&b.to<=d.viewTo&&0==Lc(a))){R(a)&&Hc(a);var f=_(a),g=e.first+e.size,h=Math.max(b.from-a.options.viewportMargin,e.first),i=Math.min(g,b.to+a.options.viewportMargin);d.viewFrom<h&&h-d.viewFrom<20&&(h=Math.max(e.first,d.viewFrom)),d.viewTo>i&&d.viewTo-i<20&&(i=Math.min(g,d.viewTo)),x&&(h=Se(a.doc,h),i=Te(a.doc,i));var j=h!=d.viewFrom||i!=d.viewTo||d.lastSizeC!=d.wrapper.clientHeight;Kc(a,h,i),d.viewOffset=Jf(Df(a.doc,d.viewFrom)),a.display.mover.style.top=d.viewOffset+"px";var k=Lc(a);if(j||0!=k||c){var l=Rg();return k>4&&(d.lineDiv.style.display="none"),ab(a,d.updateLineNumbers,f),k>4&&(d.lineDiv.style.display=""),l&&Rg()!=l&&l.offsetHeight&&l.focus(),Og(d.cursorDiv),Og(d.selectionDiv),j&&(d.lastSizeC=d.wrapper.clientHeight,Sb(a,400)),Z(a),!0}}}function W(a){var b=a.display,c=ac(a,b.maxLine,b.maxLine.text.length).left;b.maxLineChanged=!1;var d=Math.max(0,c+3),e=Math.max(0,b.sizer.offsetLeft+d+pg-b.scroller.clientWidth);b.sizer.style.minWidth=d+"px",e<a.doc.scrollLeft&&hd(a,Math.min(b.scroller.scrollLeft,e),!0)}function X(a,b){a.display.sizer.style.minHeight=a.display.heightForcer.style.top=b.docHeight+"px",a.display.gutters.style.height=Math.max(b.docHeight,b.clientHeight-pg)+"px"}function Y(a,b){a.display.sizer.offsetWidth+a.display.gutters.offsetWidth<a.display.scroller.clientWidth-1&&(a.display.sizer.style.minHeight=a.display.heightForcer.style.top="0px",a.display.gutters.style.height=b.docHeight+"px")}function Z(a){for(var b=a.display,d=b.lineDiv.offsetTop,e=0;e<b.view.length;e++){var g,f=b.view[e];if(!f.hidden){if(c){var h=f.node.offsetTop+f.node.offsetHeight;g=h-d,d=h}else{var i=f.node.getBoundingClientRect();g=i.bottom-i.top}var j=f.line.height-g;if(2>g&&(g=uc(b)),(j>.001||-.001>j)&&(Gf(f.line,g),$(f.line),f.rest))for(var k=0;k<f.rest.length;k++)$(f.rest[k])}}}function $(a){if(a.widgets)for(var b=0;b<a.widgets.length;++b)a.widgets[b].height=a.widgets[b].node.offsetHeight}function _(a){for(var b=a.display,c={},d={},e=b.gutters.firstChild,f=0;e;e=e.nextSibling,++f)c[a.options.gutters[f]]=e.offsetLeft,d[a.options.gutters[f]]=e.offsetWidth;return{fixedPos:T(b),gutterTotalWidth:b.gutters.offsetWidth,gutterLeft:c,gutterWidth:d,wrapperWidth:b.wrapper.clientWidth}}function ab(a,b,c){function i(b){var c=b.nextSibling;return h&&r&&a.display.currentWheelTarget==b?b.style.display="none":b.parentNode.removeChild(b),c}for(var d=a.display,e=a.options.lineNumbers,f=d.lineDiv,g=f.firstChild,j=d.view,k=d.viewFrom,l=0;l<j.length;l++){var m=j[l];if(m.hidden);else if(m.node){for(;g!=m.node;)g=i(g);var o=e&&null!=b&&k>=b&&m.lineNumber;m.changes&&(Bg(m.changes,"gutter")>-1&&(o=!1),bb(a,m,k,c)),o&&(Og(m.lineNumber),m.lineNumber.appendChild(document.createTextNode(S(a.options,k)))),g=m.node.nextSibling}else{var n=jb(a,m,k,c);f.insertBefore(n,g)}k+=m.size}for(;g;)g=i(g)}function bb(a,b,c,d){for(var e=0;e<b.changes.length;e++){var f=b.changes[e];"text"==f?fb(a,b):"gutter"==f?hb(a,b,c,d):"class"==f?gb(b):"widget"==f&&ib(b,d)}b.changes=null}function cb(a){return a.node==a.text&&(a.node=Mg("div",null,null,"position: relative"),a.text.parentNode&&a.text.parentNode.replaceChild(a.node,a.text),a.node.appendChild(a.text),c&&(a.node.style.zIndex=2)),a.node}function db(a){var b=a.bgClass?a.bgClass+" "+(a.line.bgClass||""):a.line.bgClass;if(b&&(b+=" CodeMirror-linebackground"),a.background)b?a.background.className=b:(a.background.parentNode.removeChild(a.background),a.background=null);else if(b){var c=cb(a);a.background=c.insertBefore(Mg("div",null,b),c.firstChild)}}function eb(a,b){var c=a.display.externalMeasured;return c&&c.line==b.line?(a.display.externalMeasured=null,b.measure=c.measure,c.built):mf(a,b)}function fb(a,b){var c=b.text.className,d=eb(a,b);b.text==b.node&&(b.node=d.pre),b.text.parentNode.replaceChild(d.pre,b.text),b.text=d.pre,d.bgClass!=b.bgClass||d.textClass!=b.textClass?(b.bgClass=d.bgClass,b.textClass=d.textClass,gb(b)):c&&(b.text.className=c)}function gb(a){db(a),a.line.wrapClass?cb(a).className=a.line.wrapClass:a.node!=a.text&&(a.node.className="");var b=a.textClass?a.textClass+" "+(a.line.textClass||""):a.line.textClass;a.text.className=b||""}function hb(a,b,c,d){b.gutter&&(b.node.removeChild(b.gutter),b.gutter=null);var e=b.line.gutterMarkers;if(a.options.lineNumbers||e){var f=cb(b),g=b.gutter=f.insertBefore(Mg("div",null,"CodeMirror-gutter-wrapper","position: absolute; left: "+(a.options.fixedGutter?d.fixedPos:-d.gutterTotalWidth)+"px"),b.text);if(!a.options.lineNumbers||e&&e["CodeMirror-linenumbers"]||(b.lineNumber=g.appendChild(Mg("div",S(a.options,c),"CodeMirror-linenumber CodeMirror-gutter-elt","left: "+d.gutterLeft["CodeMirror-linenumbers"]+"px; width: "+a.display.lineNumInnerWidth+"px"))),e)for(var h=0;h<a.options.gutters.length;++h){var i=a.options.gutters[h],j=e.hasOwnProperty(i)&&e[i];j&&g.appendChild(Mg("div",[j],"CodeMirror-gutter-elt","left: "+d.gutterLeft[i]+"px; width: "+d.gutterWidth[i]+"px"))}}}function ib(a,b){a.alignable&&(a.alignable=null);for(var d,c=a.node.firstChild;c;c=d){var d=c.nextSibling;"CodeMirror-linewidget"==c.className&&a.node.removeChild(c)}kb(a,b)}function jb(a,b,c,d){var e=eb(a,b);return b.text=b.node=e.pre,e.bgClass&&(b.bgClass=e.bgClass),e.textClass&&(b.textClass=e.textClass),gb(b),hb(a,b,c,d),kb(b,d),b.node}function kb(a,b){if(lb(a.line,a,b,!0),a.rest)for(var c=0;c<a.rest.length;c++)lb(a.rest[c],a,b,!1)}function lb(a,b,c,d){if(a.widgets)for(var e=cb(b),f=0,g=a.widgets;f<g.length;++f){var h=g[f],i=Mg("div",[h.node],"CodeMirror-linewidget");h.handleMouseEvents||(i.ignoreEvents=!0),mb(h,i,b,c),d&&h.above?e.insertBefore(i,b.gutter||b.text):e.appendChild(i),jg(h,"redraw")}}function mb(a,b,c,d){if(a.noHScroll){(c.alignable||(c.alignable=[])).push(b);var e=d.wrapperWidth;b.style.left=d.fixedPos+"px",a.coverGutter||(e-=d.gutterTotalWidth,b.style.paddingLeft=d.gutterTotalWidth+"px"),b.style.width=e+"px"}a.coverGutter&&(b.style.zIndex=5,b.style.position="relative",a.noHScroll||(b.style.marginLeft=-d.gutterTotalWidth+"px"))}function pb(a){return nb(a.line,a.ch)}function qb(a,b){return ob(a,b)<0?b:a}function rb(a,b){return ob(a,b)<0?a:b}function sb(a,b){this.ranges=a,this.primIndex=b}function tb(a,b){this.anchor=a,this.head=b}function ub(a,b){var c=a[b];a.sort(function(a,b){return ob(a.from(),b.from())}),b=Bg(a,c);for(var d=1;d<a.length;d++){var e=a[d],f=a[d-1];if(ob(f.to(),e.from())>=0){var g=rb(f.from(),e.from()),h=qb(f.to(),e.to()),i=f.empty()?e.from()==e.head:f.from()==f.head;b>=d&&--b,a.splice(--d,2,new tb(i?h:g,i?g:h))}}return new sb(a,b)}function vb(a,b){return new sb([new tb(a,b||a)],0)}function wb(a,b){return Math.max(a.first,Math.min(b,a.first+a.size-1))}function xb(a,b){if(b.line<a.first)return nb(a.first,0);var c=a.first+a.size-1;return b.line>c?nb(c,Df(a,c).text.length):yb(b,Df(a,b.line).text.length)}function yb(a,b){var c=a.ch;return null==c||c>b?nb(a.line,b):0>c?nb(a.line,0):a}function zb(a,b){return b>=a.first&&b<a.first+a.size}function Ab(a,b){for(var c=[],d=0;d<b.length;d++)c[d]=xb(a,b[d]);return c}function Bb(a,b,c,d){if(a.cm&&a.cm.display.shift||a.extend){var e=b.anchor;if(d){var f=ob(c,e)<0;f!=ob(d,e)<0?(e=c,c=d):f!=ob(c,d)<0&&(c=d)}return new tb(e,c)}return new tb(d||c,c)}function Cb(a,b,c,d){Ib(a,new sb([Bb(a,a.sel.primary(),b,c)],0),d)}function Db(a,b,c){for(var d=[],e=0;e<a.sel.ranges.length;e++)d[e]=Bb(a,a.sel.ranges[e],b[e],null);var f=ub(d,a.sel.primIndex);Ib(a,f,c)}function Eb(a,b,c,d){var e=a.sel.ranges.slice(0);e[b]=c,Ib(a,ub(e,a.sel.primIndex),d)}function Fb(a,b,c,d){Ib(a,vb(b,c),d)}function Gb(a,b){var c={ranges:b.ranges,update:function(b){this.ranges=[];for(var c=0;c<b.length;c++)this.ranges[c]=new tb(xb(a,b[c].anchor),xb(a,b[c].head))}};return gg(a,"beforeSelectionChange",a,c),a.cm&&gg(a.cm,"beforeSelectionChange",a.cm,c),c.ranges!=b.ranges?ub(c.ranges,c.ranges.length-1):b}function Hb(a,b,c){var d=a.history.done,e=zg(d);e&&e.ranges?(d[d.length-1]=b,Jb(a,b,c)):Ib(a,b,c)}function Ib(a,b,c){Jb(a,b,c),Rf(a,a.sel,a.cm?a.cm.curOp.id:0/0,c)}function Jb(a,b,c){(ng(a,"beforeSelectionChange")||a.cm&&ng(a.cm,"beforeSelectionChange"))&&(b=Gb(a,b));var d=c&&c.bias||(ob(b.primary().head,a.sel.primary().head)<0?-1:1);Kb(a,Mb(a,b,d,!0)),c&&c.scroll===!1||!a.cm||Rd(a.cm)}function Kb(a,b){b.equals(a.sel)||(a.sel=b,a.cm&&(a.cm.curOp.updateInput=a.cm.curOp.selectionChanged=!0,mg(a.cm)),jg(a,"cursorActivity",a))}function Lb(a){Kb(a,Mb(a,a.sel,null,!1),rg)}function Mb(a,b,c,d){for(var e,f=0;f<b.ranges.length;f++){var g=b.ranges[f],h=Nb(a,g.anchor,c,d),i=Nb(a,g.head,c,d);(e||h!=g.anchor||i!=g.head)&&(e||(e=b.ranges.slice(0,f)),e[f]=new tb(h,i))}return e?ub(e,b.primIndex):b}function Nb(a,b,c,d){var e=!1,f=b,g=c||1;a.cantEdit=!1;a:for(;;){var h=Df(a,f.line);if(h.markedSpans)for(var i=0;i<h.markedSpans.length;++i){var j=h.markedSpans[i],k=j.marker;if((null==j.from||(k.inclusiveLeft?j.from<=f.ch:j.from<f.ch))&&(null==j.to||(k.inclusiveRight?j.to>=f.ch:j.to>f.ch))){if(d&&(gg(k,"beforeCursorEnter"),k.explicitlyCleared)){if(h.markedSpans){--i;continue}break}if(!k.atomic)continue;var l=k.find(0>g?-1:1);if(0==ob(l,f)&&(l.ch+=g,l.ch<0?l=l.line>a.first?xb(a,nb(l.line-1)):null:l.ch>h.text.length&&(l=l.line<a.first+a.size-1?nb(l.line+1,0):null),!l)){if(e)return d?(a.cantEdit=!0,nb(a.first,0)):Nb(a,b,c,!0);e=!0,l=b,g=-g}f=l;continue a}}return f}}function Ob(a){for(var b=a.display,c=a.doc,d=document.createDocumentFragment(),e=document.createDocumentFragment(),f=0;f<c.sel.ranges.length;f++){var g=c.sel.ranges[f],h=g.empty();(h||a.options.showCursorWhenSelecting)&&Pb(a,g,d),h||Qb(a,g,e)}if(a.options.moveInputWithCursor){var i=oc(a,c.sel.primary().head,"div"),j=b.wrapper.getBoundingClientRect(),k=b.lineDiv.getBoundingClientRect(),l=Math.max(0,Math.min(b.wrapper.clientHeight-10,i.top+k.top-j.top)),m=Math.max(0,Math.min(b.wrapper.clientWidth-10,i.left+k.left-j.left));b.inputDiv.style.top=l+"px",b.inputDiv.style.left=m+"px"}Pg(b.cursorDiv,d),Pg(b.selectionDiv,e)}function Pb(a,b,c){var d=oc(a,b.head,"div"),e=c.appendChild(Mg("div","\xa0","CodeMirror-cursor"));if(e.style.left=d.left+"px",e.style.top=d.top+"px",e.style.height=Math.max(0,d.bottom-d.top)*a.options.cursorHeight+"px",d.other){var f=c.appendChild(Mg("div","\xa0","CodeMirror-cursor CodeMirror-secondarycursor"));f.style.display="",f.style.left=d.other.left+"px",f.style.top=d.other.top+"px",f.style.height=.85*(d.other.bottom-d.other.top)+"px"}}function Qb(a,b,c){function j(a,b,c,d){0>b&&(b=0),b=Math.round(b),d=Math.round(d),f.appendChild(Mg("div",null,"CodeMirror-selected","position: absolute; left: "+a+"px; top: "+b+"px; width: "+(null==c?i-a:c)+"px; height: "+(d-b)+"px"))}function k(b,c,d){function m(c,d){return nc(a,nb(b,c),"div",f,d)}var k,l,f=Df(e,b),g=f.text.length;return jh(Kf(f),c||0,null==d?g:d,function(a,b,e){var n,o,p,f=m(a,"left");if(a==b)n=f,o=p=f.left;else{if(n=m(b-1,"right"),"rtl"==e){var q=f;f=n,n=q}o=f.left,p=n.right}null==c&&0==a&&(o=h),n.top-f.top>3&&(j(o,f.top,null,f.bottom),o=h,f.bottom<n.top&&j(o,f.bottom,null,n.top)),null==d&&b==g&&(p=i),(!k||f.top<k.top||f.top==k.top&&f.left<k.left)&&(k=f),(!l||n.bottom>l.bottom||n.bottom==l.bottom&&n.right>l.right)&&(l=n),h+1>o&&(o=h),j(o,n.top,p-o,n.bottom)}),{start:k,end:l}}var d=a.display,e=a.doc,f=document.createDocumentFragment(),g=Yb(a.display),h=g.left,i=d.lineSpace.offsetWidth-g.right,l=b.from(),m=b.to();if(l.line==m.line)k(l.line,l.ch,m.ch);else{var n=Df(e,l.line),o=Df(e,m.line),p=Qe(n)==Qe(o),q=k(l.line,l.ch,p?n.text.length+1:null).end,r=k(m.line,p?0:null,m.ch).start;p&&(q.top<r.top-2?(j(q.right,q.top,null,q.bottom),j(h,r.top,r.left,r.bottom)):j(q.right,q.top,r.left-q.right,q.bottom)),q.bottom<r.top&&j(h,q.bottom,null,r.top)}c.appendChild(f)}function Rb(a){if(a.state.focused){var b=a.display;clearInterval(b.blinker);var c=!0;b.cursorDiv.style.visibility="",a.options.cursorBlinkRate>0&&(b.blinker=setInterval(function(){b.cursorDiv.style.visibility=(c=!c)?"":"hidden"},a.options.cursorBlinkRate))}}function Sb(a,b){a.doc.mode.startState&&a.doc.frontier<a.display.viewTo&&a.state.highlight.set(b,Fg(Tb,a))}function Tb(a){var b=a.doc;if(b.frontier<b.first&&(b.frontier=b.first),!(b.frontier>=a.display.viewTo)){var c=+new Date+a.options.workTime,d=ge(b.mode,Vb(a,b.frontier));zc(a,function(){b.iter(b.frontier,Math.min(b.first+b.size,a.display.viewTo+500),function(e){if(b.frontier>=a.display.viewFrom){var f=e.styles,g=ff(a,e,d,!0);e.styles=g.styles,g.classes?e.styleClasses=g.classes:e.styleClasses&&(e.styleClasses=null);for(var h=!f||f.length!=e.styles.length,i=0;!h&&i<f.length;++i)h=f[i]!=e.styles[i];h&&Gc(a,b.frontier,"text"),e.stateAfter=ge(b.mode,d)}else hf(a,e.text,d),e.stateAfter=0==b.frontier%5?ge(b.mode,d):null;return++b.frontier,+new Date>c?(Sb(a,a.options.workDelay),!0):void 0})})}}function Ub(a,b,c){for(var d,e,f=a.doc,g=c?-1:b-(a.doc.mode.innerMode?1e3:100),h=b;h>g;--h){if(h<=f.first)return f.first;var i=Df(f,h-1);if(i.stateAfter&&(!c||h<=f.frontier))return h;var j=vg(i.text,null,a.options.tabSize);(null==e||d>j)&&(e=h-1,d=j)}return e}function Vb(a,b,c){var d=a.doc,e=a.display;if(!d.mode.startState)return!0;var f=Ub(a,b,c),g=f>d.first&&Df(d,f-1).stateAfter;return g=g?ge(d.mode,g):he(d.mode),d.iter(f,b,function(c){hf(a,c.text,g);var h=f==b-1||0==f%5||f>=e.viewFrom&&f<e.viewTo;c.stateAfter=h?ge(d.mode,g):null,++f}),c&&(d.frontier=f),g}function Wb(a){return a.lineSpace.offsetTop}function Xb(a){return a.mover.offsetHeight-a.lineSpace.offsetHeight}function Yb(a){if(a.cachedPaddingH)return a.cachedPaddingH;var b=Pg(a.measure,Mg("pre","x")),c=window.getComputedStyle?window.getComputedStyle(b):b.currentStyle,d={left:parseInt(c.paddingLeft),right:parseInt(c.paddingRight)};return isNaN(d.left)||isNaN(d.right)||(a.cachedPaddingH=d),d}function Zb(a,b,c){var d=a.options.lineWrapping,e=d&&a.display.scroller.clientWidth;if(!b.measure.heights||d&&b.measure.width!=e){var f=b.measure.heights=[];if(d){b.measure.width=e;for(var g=b.text.firstChild.getClientRects(),h=0;h<g.length-1;h++){var i=g[h],j=g[h+1];Math.abs(i.bottom-j.bottom)>2&&f.push((i.bottom+j.top)/2-c.top)}}f.push(c.bottom-c.top)}}function $b(a,b,c){if(a.line==b)return{map:a.measure.map,cache:a.measure.cache};for(var d=0;d<a.rest.length;d++)if(a.rest[d]==b)return{map:a.measure.maps[d],cache:a.measure.caches[d]};for(var d=0;d<a.rest.length;d++)if(Hf(a.rest[d])>c)return{map:a.measure.maps[d],cache:a.measure.caches[d],before:!0}}function _b(a,b){b=Qe(b);var c=Hf(b),d=a.display.externalMeasured=new Dc(a.doc,b,c);d.lineN=c;var e=d.built=mf(a,d);return d.text=e.pre,Pg(a.display.lineMeasure,e.pre),d}function ac(a,b,c,d){return dc(a,cc(a,b),c,d)}function bc(a,b){if(b>=a.display.viewFrom&&b<a.display.viewTo)return a.display.view[Ic(a,b)];var c=a.display.externalMeasured;return c&&b>=c.lineN&&b<c.lineN+c.size?c:void 0}function cc(a,b){var c=Hf(b),d=bc(a,c);d&&!d.text?d=null:d&&d.changes&&bb(a,d,c,_(a)),d||(d=_b(a,b));var e=$b(d,b,c);return{line:b,view:d,rect:null,map:e.map,cache:e.cache,before:e.before,hasHeights:!1}}function dc(a,b,c,d){b.before&&(c=-1);var f,e=c+(d||"");return b.cache.hasOwnProperty(e)?f=b.cache[e]:(b.rect||(b.rect=b.view.text.getBoundingClientRect()),b.hasHeights||(Zb(a,b.view,b.rect),b.hasHeights=!0),f=fc(a,b,c,d),f.bogus||(b.cache[e]=f)),{left:f.left,right:f.right,top:f.top,bottom:f.bottom}}function fc(a,b,c,e){for(var h,i,j,k,f=b.map,l=0;l<f.length;l+=3){var m=f[l],n=f[l+1];if(m>c?(i=0,j=1,k="left"):n>c?(i=c-m,j=i+1):(l==f.length-3||c==n&&f[l+3]>c)&&(j=n-m,i=j-1,c>=n&&(k="right")),null!=i){if(h=f[l+2],m==n&&e==(h.insertLeft?"left":"right")&&(k=e),"left"==e&&0==i)for(;l&&f[l-2]==f[l-3]&&f[l-1].insertLeft;)h=f[(l-=3)+2],k="left";if("right"==e&&i==n-m)for(;l<f.length-3&&f[l+3]==f[l+4]&&!f[l+5].insertLeft;)h=f[(l+=3)+2],k="right";break}}var o;if(3==h.nodeType){for(;i&&Lg(b.line.text.charAt(m+i));)--i;for(;n>m+j&&Lg(b.line.text.charAt(m+j));)++j;if(d&&0==i&&j==n-m)o=h.parentNode.getBoundingClientRect();else if(g&&a.options.lineWrapping){var p=Ng(h,i,j).getClientRects();o=p.length?p["right"==e?p.length-1:0]:ec}else o=Ng(h,i,j).getBoundingClientRect()||ec}else{i>0&&(k=e="right");var p;o=a.options.lineWrapping&&(p=h.getClientRects()).length>1?p["right"==e?p.length-1:0]:h.getBoundingClientRect()}if(d&&!i&&(!o||!o.left&&!o.right)){var q=h.parentNode.getClientRects()[0];o=q?{left:q.left,right:q.left+vc(a.display),top:q.top,bottom:q.bottom}:ec}for(var r,s=(o.bottom+o.top)/2-b.rect.top,t=b.view.measure.heights,l=0;l<t.length-1&&!(s<t[l]);l++);r=l?t[l-1]:0,s=t[l];var u={left:("right"==k?o.right:o.left)-b.rect.left,right:("left"==k?o.left:o.right)-b.rect.left,top:r,bottom:s};return o.left||o.right||(u.bogus=!0),u}function gc(a){if(a.measure&&(a.measure.cache={},a.measure.heights=null,a.rest))for(var b=0;b<a.rest.length;b++)a.measure.caches[b]={}}function hc(a){a.display.externalMeasure=null,Og(a.display.lineMeasure);for(var b=0;b<a.display.view.length;b++)gc(a.display.view[b])}function ic(a){hc(a),a.display.cachedCharWidth=a.display.cachedTextHeight=a.display.cachedPaddingH=null,a.options.lineWrapping||(a.display.maxLineChanged=!0),a.display.lineNumChars=null}function jc(){return window.pageXOffset||(document.documentElement||document.body).scrollLeft}function kc(){return window.pageYOffset||(document.documentElement||document.body).scrollTop}function lc(a,b,c,d){if(b.widgets)for(var e=0;e<b.widgets.length;++e)if(b.widgets[e].above){var f=Ye(b.widgets[e]);c.top+=f,c.bottom+=f}if("line"==d)return c;d||(d="local");var g=Jf(b);if("local"==d?g+=Wb(a.display):g-=a.display.viewOffset,"page"==d||"window"==d){var h=a.display.lineSpace.getBoundingClientRect();g+=h.top+("window"==d?0:kc());var i=h.left+("window"==d?0:jc());c.left+=i,c.right+=i}return c.top+=g,c.bottom+=g,c}function mc(a,b,c){if("div"==c)return b;var d=b.left,e=b.top;if("page"==c)d-=jc(),e-=kc();else if("local"==c||!c){var f=a.display.sizer.getBoundingClientRect();d+=f.left,e+=f.top}var g=a.display.lineSpace.getBoundingClientRect();return{left:d-g.left,top:e-g.top}}function nc(a,b,c,d,e){return d||(d=Df(a.doc,b.line)),lc(a,d,ac(a,d,b.ch,e),c)}function oc(a,b,c,d,e){function f(b,f){var g=dc(a,e,b,f?"right":"left");return f?g.left=g.right:g.right=g.left,lc(a,d,g,c)}function g(a,b){var c=h[b],d=c.level%2;return a==kh(c)&&b&&c.level<h[b-1].level?(c=h[--b],a=lh(c)-(c.level%2?0:1),d=!0):a==lh(c)&&b<h.length-1&&c.level<h[b+1].level&&(c=h[++b],a=kh(c)-c.level%2,d=!1),d&&a==c.to&&a>c.from?f(a-1):f(a,d)}d=d||Df(a.doc,b.line),e||(e=cc(a,d));var h=Kf(d),i=b.ch;if(!h)return f(i);var j=sh(h,i),k=g(i,j);return null!=rh&&(k.other=g(i,rh)),k}function pc(a,b){var c=0,b=xb(a.doc,b);a.options.lineWrapping||(c=vc(a.display)*b.ch);var d=Df(a.doc,b.line),e=Jf(d)+Wb(a.display);return{left:c,right:c,top:e,bottom:e+d.height}}function qc(a,b,c,d){var e=nb(a,b);return e.xRel=d,c&&(e.outside=!0),e}function rc(a,b,c){var d=a.doc;if(c+=a.display.viewOffset,0>c)return qc(d.first,0,!0,-1);var e=If(d,c),f=d.first+d.size-1;if(e>f)return qc(d.first+d.size-1,Df(d,f).text.length,!0,1);0>b&&(b=0);for(var g=Df(d,e);;){var h=sc(a,g,e,b,c),i=Oe(g),j=i&&i.find(0,!0);if(!i||!(h.ch>j.from.ch||h.ch==j.from.ch&&h.xRel>0))return h;e=Hf(g=j.to.line)}}function sc(a,b,c,d,e){function j(d){var e=oc(a,nb(c,d),"line",b,i);return g=!0,f>e.bottom?e.left-h:f<e.top?e.left+h:(g=!1,e.left)}var f=e-Jf(b),g=!1,h=2*a.display.wrapper.clientWidth,i=cc(a,b),k=Kf(b),l=b.text.length,m=mh(b),n=nh(b),o=j(m),p=g,q=j(n),r=g;if(d>q)return qc(c,n,r,1);for(;;){if(k?n==m||n==uh(b,m,1):1>=n-m){for(var s=o>d||q-d>=d-o?m:n,t=d-(s==m?o:q);Lg(b.text.charAt(s));)++s;var u=qc(c,s,s==m?p:r,-1>t?-1:t>1?1:0);return u}var v=Math.ceil(l/2),w=m+v;if(k){w=m;for(var x=0;v>x;++x)w=uh(b,w,1)}var y=j(w);y>d?(n=w,q=y,(r=g)&&(q+=1e3),l=v):(m=w,o=y,p=g,l-=v)}}function uc(a){if(null!=a.cachedTextHeight)return a.cachedTextHeight;if(null==tc){tc=Mg("pre");for(var b=0;49>b;++b)tc.appendChild(document.createTextNode("x")),tc.appendChild(Mg("br"));tc.appendChild(document.createTextNode("x"))}Pg(a.measure,tc);var c=tc.offsetHeight/50;return c>3&&(a.cachedTextHeight=c),Og(a.measure),c||1}function vc(a){if(null!=a.cachedCharWidth)return a.cachedCharWidth;var b=Mg("span","xxxxxxxxxx"),c=Mg("pre",[b]);Pg(a.measure,c);var d=b.getBoundingClientRect(),e=(d.right-d.left)/10;return e>2&&(a.cachedCharWidth=e),e||10}function xc(a){a.curOp={viewChanged:!1,startHeight:a.doc.height,forceUpdate:!1,updateInput:null,typing:!1,changeObjs:null,cursorActivityHandlers:null,selectionChanged:!1,updateMaxLine:!1,scrollLeft:null,scrollTop:null,scrollToPos:null,id:++wc},ig++||(hg=[])}function yc(a){var b=a.curOp,c=a.doc,d=a.display;if(a.curOp=null,b.updateMaxLine&&L(a),b.viewChanged||b.forceUpdate||null!=b.scrollTop||b.scrollToPos&&(b.scrollToPos.from.line<d.viewFrom||b.scrollToPos.to.line>=d.viewTo)||d.maxLineChanged&&a.options.lineWrapping){var e=U(a,{top:b.scrollTop,ensure:b.scrollToPos},b.forceUpdate);a.display.scroller.offsetHeight&&(a.doc.scrollTop=a.display.scroller.scrollTop)}if(!e&&b.selectionChanged&&Ob(a),e||b.startHeight==a.doc.height||O(a),null==d.wheelStartX||null==b.scrollTop&&null==b.scrollLeft&&!b.scrollToPos||(d.wheelStartX=d.wheelStartY=null),null!=b.scrollTop&&d.scroller.scrollTop!=b.scrollTop){var f=Math.max(0,Math.min(d.scroller.scrollHeight-d.scroller.clientHeight,b.scrollTop));d.scroller.scrollTop=d.scrollbarV.scrollTop=c.scrollTop=f}if(null!=b.scrollLeft&&d.scroller.scrollLeft!=b.scrollLeft){var g=Math.max(0,Math.min(d.scroller.scrollWidth-d.scroller.clientWidth,b.scrollLeft));d.scroller.scrollLeft=d.scrollbarH.scrollLeft=c.scrollLeft=g,Q(a)}if(b.scrollToPos){var h=Nd(a,xb(a.doc,b.scrollToPos.from),xb(a.doc,b.scrollToPos.to),b.scrollToPos.margin);b.scrollToPos.isCursor&&a.state.focused&&Md(a,h)}b.selectionChanged&&Rb(a),a.state.focused&&b.updateInput&&Pc(a,b.typing);var i=b.maybeHiddenMarkers,j=b.maybeUnhiddenMarkers;if(i)for(var k=0;k<i.length;++k)i[k].lines.length||gg(i[k],"hide");if(j)for(var k=0;k<j.length;++k)j[k].lines.length&&gg(j[k],"unhide");var l;if(--ig||(l=hg,hg=null),b.changeObjs&&gg(a,"changes",a,b.changeObjs),l)for(var k=0;k<l.length;++k)l[k]();if(b.cursorActivityHandlers)for(var k=0;k<b.cursorActivityHandlers.length;k++)b.cursorActivityHandlers[k](a)}function zc(a,b){if(a.curOp)return b();xc(a);try{return b()}finally{yc(a)}}function Ac(a,b){return function(){if(a.curOp)return b.apply(a,arguments);xc(a);try{return b.apply(a,arguments)}finally{yc(a)}}}function Bc(a){return function(){if(this.curOp)return a.apply(this,arguments);xc(this);try{return a.apply(this,arguments)}finally{yc(this)
}}}function Cc(a){return function(){var b=this.cm;if(!b||b.curOp)return a.apply(this,arguments);xc(b);try{return a.apply(this,arguments)}finally{yc(b)}}}function Dc(a,b,c){this.line=b,this.rest=Re(b),this.size=this.rest?Hf(zg(this.rest))-c+1:1,this.node=this.text=null,this.hidden=Ue(a,b)}function Ec(a,b,c){for(var e,d=[],f=b;c>f;f=e){var g=new Dc(a.doc,Df(a.doc,f),f);e=f+g.size,d.push(g)}return d}function Fc(a,b,c,d){null==b&&(b=a.doc.first),null==c&&(c=a.doc.first+a.doc.size),d||(d=0);var e=a.display;if(d&&c<e.viewTo&&(null==e.updateLineNumbers||e.updateLineNumbers>b)&&(e.updateLineNumbers=b),a.curOp.viewChanged=!0,b>=e.viewTo)x&&Se(a.doc,b)<e.viewTo&&Hc(a);else if(c<=e.viewFrom)x&&Te(a.doc,c+d)>e.viewFrom?Hc(a):(e.viewFrom+=d,e.viewTo+=d);else if(b<=e.viewFrom&&c>=e.viewTo)Hc(a);else if(b<=e.viewFrom){var f=Jc(a,c,c+d,1);f?(e.view=e.view.slice(f.index),e.viewFrom=f.lineN,e.viewTo+=d):Hc(a)}else if(c>=e.viewTo){var f=Jc(a,b,b,-1);f?(e.view=e.view.slice(0,f.index),e.viewTo=f.lineN):Hc(a)}else{var g=Jc(a,b,b,-1),h=Jc(a,c,c+d,1);g&&h?(e.view=e.view.slice(0,g.index).concat(Ec(a,g.lineN,h.lineN)).concat(e.view.slice(h.index)),e.viewTo+=d):Hc(a)}var i=e.externalMeasured;i&&(c<i.lineN?i.lineN+=d:b<i.lineN+i.size&&(e.externalMeasured=null))}function Gc(a,b,c){a.curOp.viewChanged=!0;var d=a.display,e=a.display.externalMeasured;if(e&&b>=e.lineN&&b<e.lineN+e.size&&(d.externalMeasured=null),!(b<d.viewFrom||b>=d.viewTo)){var f=d.view[Ic(a,b)];if(null!=f.node){var g=f.changes||(f.changes=[]);-1==Bg(g,c)&&g.push(c)}}}function Hc(a){a.display.viewFrom=a.display.viewTo=a.doc.first,a.display.view=[],a.display.viewOffset=0}function Ic(a,b){if(b>=a.display.viewTo)return null;if(b-=a.display.viewFrom,0>b)return null;for(var c=a.display.view,d=0;d<c.length;d++)if(b-=c[d].size,0>b)return d}function Jc(a,b,c,d){var f,e=Ic(a,b),g=a.display.view;if(!x||c==a.doc.first+a.doc.size)return{index:e,lineN:c};for(var h=0,i=a.display.viewFrom;e>h;h++)i+=g[h].size;if(i!=b){if(d>0){if(e==g.length-1)return null;f=i+g[e].size-b,e++}else f=i-b;b+=f,c+=f}for(;Se(a.doc,c)!=c;){if(e==(0>d?0:g.length-1))return null;c+=d*g[e-(0>d?1:0)].size,e+=d}return{index:e,lineN:c}}function Kc(a,b,c){var d=a.display,e=d.view;0==e.length||b>=d.viewTo||c<=d.viewFrom?(d.view=Ec(a,b,c),d.viewFrom=b):(d.viewFrom>b?d.view=Ec(a,b,d.viewFrom).concat(d.view):d.viewFrom<b&&(d.view=d.view.slice(Ic(a,b))),d.viewFrom=b,d.viewTo<c?d.view=d.view.concat(Ec(a,d.viewTo,c)):d.viewTo>c&&(d.view=d.view.slice(0,Ic(a,c)))),d.viewTo=c}function Lc(a){for(var b=a.display.view,c=0,d=0;d<b.length;d++){var e=b[d];e.hidden||e.node&&!e.changes||++c}return c}function Mc(a){a.display.pollingFast||a.display.poll.set(a.options.pollInterval,function(){Oc(a),a.state.focused&&Mc(a)})}function Nc(a){function c(){var d=Oc(a);d||b?(a.display.pollingFast=!1,Mc(a)):(b=!0,a.display.poll.set(60,c))}var b=!1;a.display.pollingFast=!0,a.display.poll.set(20,c)}function Oc(a){var b=a.display.input,c=a.display.prevInput,e=a.doc;if(!a.state.focused||gh(b)&&!c||Sc(a)||a.options.disableInput)return!1;a.state.pasteIncoming&&a.state.fakedLastChar&&(b.value=b.value.substring(0,b.value.length-1),a.state.fakedLastChar=!1);var f=b.value;if(f==c&&!a.somethingSelected())return!1;if(g&&!d&&a.display.inputHasSelection===f)return Pc(a),!1;var h=!a.curOp;h&&xc(a),a.display.shift=!1,8203!=f.charCodeAt(0)||e.sel!=a.display.selForContextMenu||c||(c="\u200b");for(var i=0,j=Math.min(c.length,f.length);j>i&&c.charCodeAt(i)==f.charCodeAt(i);)++i;for(var k=f.slice(i),l=fh(k),m=a.state.pasteIncoming&&l.length>1&&e.sel.ranges.length==l.length,n=e.sel.ranges.length-1;n>=0;n--){var o=e.sel.ranges[n],p=o.from(),q=o.to();i<c.length?p=nb(p.line,p.ch-(c.length-i)):a.state.overwrite&&o.empty()&&!a.state.pasteIncoming&&(q=nb(q.line,Math.min(Df(e,q.line).text.length,q.ch+zg(l).length)));var r=a.curOp.updateInput,s={from:p,to:q,text:m?[l[n]]:l,origin:a.state.pasteIncoming?"paste":a.state.cutIncoming?"cut":"+input"};if(Fd(a.doc,s),jg(a,"inputRead",a,s),k&&!a.state.pasteIncoming&&a.options.electricChars&&a.options.smartIndent&&o.head.ch<100&&(!n||e.sel.ranges[n-1].head.line!=o.head.line)){var t=a.getModeAt(o.head);if(t.electricChars){for(var u=0;u<t.electricChars.length;u++)if(k.indexOf(t.electricChars.charAt(u))>-1){Td(a,o.head.line,"smart");break}}else if(t.electricInput){var v=zd(s);t.electricInput.test(Df(e,v.line).text.slice(0,v.ch))&&Td(a,o.head.line,"smart")}}}return Rd(a),a.curOp.updateInput=r,a.curOp.typing=!0,f.length>1e3||f.indexOf("\n")>-1?b.value=a.display.prevInput="":a.display.prevInput=f,h&&yc(a),a.state.pasteIncoming=a.state.cutIncoming=!1,!0}function Pc(a,b){var c,e,f=a.doc;if(a.somethingSelected()){a.display.prevInput="";var h=f.sel.primary();c=hh&&(h.to().line-h.from().line>100||(e=a.getSelection()).length>1e3);var i=c?"-":e||a.getSelection();a.display.input.value=i,a.state.focused&&Ag(a.display.input),g&&!d&&(a.display.inputHasSelection=i)}else b||(a.display.prevInput=a.display.input.value="",g&&!d&&(a.display.inputHasSelection=null));a.display.inaccurateSelection=c}function Qc(a){"nocursor"==a.options.readOnly||q&&Rg()==a.display.input||a.display.input.focus()}function Rc(a){a.state.focused||(Qc(a),vd(a))}function Sc(a){return a.options.readOnly||a.doc.cantEdit}function Tc(a){function e(){a.state.focused&&setTimeout(Fg(Qc,a),0)}function f(b){lg(a,b)||bg(b)}function i(b){if(a.somethingSelected())c.inaccurateSelection&&(c.prevInput="",c.inaccurateSelection=!1,c.input.value=a.getSelection(),Ag(c.input));else{for(var d="",e=[],f=0;f<a.doc.sel.ranges.length;f++){var g=a.doc.sel.ranges[f].head.line,h={anchor:nb(g,0),head:nb(g+1,0)};e.push(h),d+=a.getRange(h.anchor,h.head)}"cut"==b.type?a.setSelections(e,null,rg):(c.prevInput="",c.input.value=d,Ag(c.input))}"cut"==b.type&&(a.state.cutIncoming=!0)}var c=a.display;eg(c.scroller,"mousedown",Ac(a,Xc)),b?eg(c.scroller,"dblclick",Ac(a,function(b){if(!lg(a,b)){var c=Wc(a,b);if(c&&!cd(a,b)&&!Vc(a.display,b)){$f(b);var d=Yd(a,c);Cb(a.doc,d.anchor,d.head)}}})):eg(c.scroller,"dblclick",function(b){lg(a,b)||$f(b)}),eg(c.lineSpace,"selectstart",function(a){Vc(c,a)||$f(a)}),v||eg(c.scroller,"contextmenu",function(b){xd(a,b)}),eg(c.scroller,"scroll",function(){c.scroller.clientHeight&&(gd(a,c.scroller.scrollTop),hd(a,c.scroller.scrollLeft,!0),gg(a,"scroll",a))}),eg(c.scrollbarV,"scroll",function(){c.scroller.clientHeight&&gd(a,c.scrollbarV.scrollTop)}),eg(c.scrollbarH,"scroll",function(){c.scroller.clientHeight&&hd(a,c.scrollbarH.scrollLeft)}),eg(c.scroller,"mousewheel",function(b){kd(a,b)}),eg(c.scroller,"DOMMouseScroll",function(b){kd(a,b)}),eg(c.scrollbarH,"mousedown",e),eg(c.scrollbarV,"mousedown",e),eg(c.wrapper,"scroll",function(){c.wrapper.scrollTop=c.wrapper.scrollLeft=0}),eg(c.input,"keyup",Ac(a,td)),eg(c.input,"input",function(){g&&!d&&a.display.inputHasSelection&&(a.display.inputHasSelection=null),Nc(a)}),eg(c.input,"keydown",Ac(a,rd)),eg(c.input,"keypress",Ac(a,ud)),eg(c.input,"focus",Fg(vd,a)),eg(c.input,"blur",Fg(wd,a)),a.options.dragDrop&&(eg(c.scroller,"dragstart",function(b){fd(a,b)}),eg(c.scroller,"dragenter",f),eg(c.scroller,"dragover",f),eg(c.scroller,"drop",Ac(a,ed))),eg(c.scroller,"paste",function(b){Vc(c,b)||(a.state.pasteIncoming=!0,Qc(a),Nc(a))}),eg(c.input,"paste",function(){if(h&&!a.state.fakedLastChar&&!(new Date-a.state.lastMiddleDown<200)){var b=c.input.selectionStart,d=c.input.selectionEnd;c.input.value+="$",c.input.selectionStart=b,c.input.selectionEnd=d,a.state.fakedLastChar=!0}a.state.pasteIncoming=!0,Nc(a)}),eg(c.input,"cut",i),eg(c.input,"copy",i),m&&eg(c.sizer,"mouseup",function(){Rg()==c.input&&c.input.blur(),Qc(a)})}function Uc(a){var b=a.display;b.cachedCharWidth=b.cachedTextHeight=b.cachedPaddingH=null,a.setSize()}function Vc(a,b){for(var c=cg(b);c!=a.wrapper;c=c.parentNode)if(!c||c.ignoreEvents||c.parentNode==a.sizer&&c!=a.mover)return!0}function Wc(a,b,c,d){var e=a.display;if(!c){var f=cg(b);if(f==e.scrollbarH||f==e.scrollbarV||f==e.scrollbarFiller||f==e.gutterFiller)return null}var g,h,i=e.lineSpace.getBoundingClientRect();try{g=b.clientX-i.left,h=b.clientY-i.top}catch(b){return null}var k,j=rc(a,g,h);if(d&&1==j.xRel&&(k=Df(a.doc,j.line).text).length==j.ch){var l=vg(k,k.length,a.options.tabSize)-k.length;j=nb(j.line,Math.max(0,Math.round((g-Yb(a.display).left)/vc(a.display))-l))}return j}function Xc(a){if(!lg(this,a)){var b=this,c=b.display;if(c.shift=a.shiftKey,Vc(c,a))return h||(c.scroller.draggable=!1,setTimeout(function(){c.scroller.draggable=!0},100)),void 0;if(!cd(b,a)){var d=Wc(b,a);switch(window.focus(),dg(a)){case 1:d?$c(b,a,d):cg(a)==c.scroller&&$f(a);break;case 2:h&&(b.state.lastMiddleDown=+new Date),d&&Cb(b.doc,d),setTimeout(Fg(Qc,b),20),$f(a);break;case 3:v&&xd(b,a)}}}}function $c(a,b,c){setTimeout(Fg(Rc,a),0);var e,d=+new Date;Zc&&Zc.time>d-400&&0==ob(Zc.pos,c)?e="triple":Yc&&Yc.time>d-400&&0==ob(Yc.pos,c)?(e="double",Zc={time:d,pos:c}):(e="single",Yc={time:d,pos:c});var f=a.doc.sel,g=r?b.metaKey:b.ctrlKey;a.options.dragDrop&&$g&&!Sc(a)&&"single"==e&&f.contains(c)>-1&&f.somethingSelected()?_c(a,b,c,g):ad(a,b,c,e,g)}function _c(a,c,e,f){var g=a.display,i=Ac(a,function(j){h&&(g.scroller.draggable=!1),a.state.draggingText=!1,fg(document,"mouseup",i),fg(g.scroller,"drop",i),Math.abs(c.clientX-j.clientX)+Math.abs(c.clientY-j.clientY)<10&&($f(j),f||Cb(a.doc,e),Qc(a),b&&!d&&setTimeout(function(){document.body.focus(),Qc(a)},20))});h&&(g.scroller.draggable=!0),a.state.draggingText=i,g.scroller.dragDrop&&g.scroller.dragDrop(),eg(document,"mouseup",i),eg(g.scroller,"drop",i)}function ad(a,b,c,d,f){function p(b){if(0!=ob(o,b))if(o=b,"rect"==d){for(var e=[],f=a.options.tabSize,g=vg(Df(i,c.line).text,c.ch,f),h=vg(Df(i,b.line).text,b.ch,f),m=Math.min(g,h),n=Math.max(g,h),p=Math.min(c.line,b.line),q=Math.min(a.lastLine(),Math.max(c.line,b.line));q>=p;p++){var r=Df(i,p).text,s=wg(r,m,f);m==n?e.push(new tb(nb(p,s),nb(p,s))):r.length>s&&e.push(new tb(nb(p,s),nb(p,wg(r,n,f))))}e.length||e.push(new tb(c,c)),Ib(i,ub(l.ranges.slice(0,k).concat(e),k),{origin:"*mouse",scroll:!1}),a.scrollIntoView(b)}else{var t=j,u=t.anchor,v=b;if("single"!=d){if("double"==d)var w=Yd(a,b);else var w=new tb(nb(b.line,0),xb(i,nb(b.line+1,0)));ob(w.anchor,u)>0?(v=w.head,u=rb(t.from(),w.anchor)):(v=w.anchor,u=qb(t.to(),w.head))}var e=l.ranges.slice(0);e[k]=new tb(xb(i,u),v),Ib(i,ub(e,k),sg)}}function s(b){var c=++r,e=Wc(a,b,!0,"rect"==d);if(e)if(0!=ob(e,o)){Rc(a),p(e);var f=P(h,i);(e.line>=f.to||e.line<f.from)&&setTimeout(Ac(a,function(){r==c&&s(b)}),150)}else{var g=b.clientY<q.top?-20:b.clientY>q.bottom?20:0;g&&setTimeout(Ac(a,function(){r==c&&(h.scroller.scrollTop+=g,s(b))}),50)}}function t(b){r=1/0,$f(b),Qc(a),fg(document,"mousemove",u),fg(document,"mouseup",v),i.history.lastSelOrigin=null}var h=a.display,i=a.doc;$f(b);var j,k,l=i.sel;if(f&&!b.shiftKey?(k=i.sel.contains(c),j=k>-1?i.sel.ranges[k]:new tb(c,c)):j=i.sel.primary(),b.altKey)d="rect",f||(j=new tb(c,c)),c=Wc(a,b,!0,!0),k=-1;else if("double"==d){var m=Yd(a,c);j=a.display.shift||i.extend?Bb(i,j,m.anchor,m.head):m}else if("triple"==d){var n=new tb(nb(c.line,0),xb(i,nb(c.line+1,0)));j=a.display.shift||i.extend?Bb(i,j,n.anchor,n.head):n}else j=Bb(i,j,c);f?k>-1?Eb(i,k,j,sg):(k=i.sel.ranges.length,Ib(i,ub(i.sel.ranges.concat([j]),k),{scroll:!1,origin:"*mouse"})):(k=0,Ib(i,new sb([j],0),sg),l=i.sel);var o=c,q=h.wrapper.getBoundingClientRect(),r=0,u=Ac(a,function(a){(g&&!e?a.buttons:dg(a))?s(a):t(a)}),v=Ac(a,t);eg(document,"mousemove",u),eg(document,"mouseup",v)}function bd(a,b,c,d,e){try{var f=b.clientX,g=b.clientY}catch(b){return!1}if(f>=Math.floor(a.display.gutters.getBoundingClientRect().right))return!1;d&&$f(b);var h=a.display,i=h.lineDiv.getBoundingClientRect();if(g>i.bottom||!ng(a,c))return ag(b);g-=i.top-h.viewOffset;for(var j=0;j<a.options.gutters.length;++j){var k=h.gutters.childNodes[j];if(k&&k.getBoundingClientRect().right>=f){var l=If(a.doc,g),m=a.options.gutters[j];return e(a,c,a,l,m,b),ag(b)}}}function cd(a,b){return bd(a,b,"gutterClick",!0,jg)}function ed(a){var b=this;if(!lg(b,a)&&!Vc(b.display,a)){$f(a),g&&(dd=+new Date);var c=Wc(b,a,!0),d=a.dataTransfer.files;if(c&&!Sc(b))if(d&&d.length&&window.FileReader&&window.File)for(var e=d.length,f=Array(e),h=0,i=function(a,d){var g=new FileReader;g.onload=Ac(b,function(){if(f[d]=g.result,++h==e){c=xb(b.doc,c);var a={from:c,to:c,text:fh(f.join("\n")),origin:"paste"};Fd(b.doc,a),Hb(b.doc,vb(c,zd(a)))}}),g.readAsText(a)},j=0;e>j;++j)i(d[j],j);else{if(b.state.draggingText&&b.doc.sel.contains(c)>-1)return b.state.draggingText(a),setTimeout(Fg(Qc,b),20),void 0;try{var f=a.dataTransfer.getData("Text");if(f){if(b.state.draggingText&&!(r?a.metaKey:a.ctrlKey))var k=b.listSelections();if(Jb(b.doc,vb(c,c)),k)for(var j=0;j<k.length;++j)Ld(b.doc,"",k[j].anchor,k[j].head,"drag");b.replaceSelection(f,"around","paste"),Qc(b)}}catch(a){}}}}function fd(a,b){if(g&&(!a.state.draggingText||+new Date-dd<100))return bg(b),void 0;if(!lg(a,b)&&!Vc(a.display,b)&&(b.dataTransfer.setData("Text",a.getSelection()),b.dataTransfer.setDragImage&&!l)){var c=Mg("img",null,null,"position: fixed; left: 0; top: 0;");c.src="data:image/gif;base64,R0lGODlhAQABAAAAACH5BAEKAAEALAAAAAABAAEAAAICTAEAOw==",k&&(c.width=c.height=1,a.display.wrapper.appendChild(c),c._top=c.offsetTop),b.dataTransfer.setDragImage(c,0,0),k&&c.parentNode.removeChild(c)}}function gd(b,c){Math.abs(b.doc.scrollTop-c)<2||(b.doc.scrollTop=c,a||U(b,{top:c}),b.display.scroller.scrollTop!=c&&(b.display.scroller.scrollTop=c),b.display.scrollbarV.scrollTop!=c&&(b.display.scrollbarV.scrollTop=c),a&&U(b),Sb(b,100))}function hd(a,b,c){(c?b==a.doc.scrollLeft:Math.abs(a.doc.scrollLeft-b)<2)||(b=Math.min(b,a.display.scroller.scrollWidth-a.display.scroller.clientWidth),a.doc.scrollLeft=b,Q(a),a.display.scroller.scrollLeft!=b&&(a.display.scroller.scrollLeft=b),a.display.scrollbarH.scrollLeft!=b&&(a.display.scrollbarH.scrollLeft=b))}function kd(b,c){var d=c.wheelDeltaX,e=c.wheelDeltaY;null==d&&c.detail&&c.axis==c.HORIZONTAL_AXIS&&(d=c.detail),null==e&&c.detail&&c.axis==c.VERTICAL_AXIS?e=c.detail:null==e&&(e=c.wheelDelta);var f=b.display,g=f.scroller;if(d&&g.scrollWidth>g.clientWidth||e&&g.scrollHeight>g.clientHeight){if(e&&r&&h)a:for(var i=c.target,j=f.view;i!=g;i=i.parentNode)for(var l=0;l<j.length;l++)if(j[l].node==i){b.display.currentWheelTarget=i;break a}if(d&&!a&&!k&&null!=jd)return e&&gd(b,Math.max(0,Math.min(g.scrollTop+e*jd,g.scrollHeight-g.clientHeight))),hd(b,Math.max(0,Math.min(g.scrollLeft+d*jd,g.scrollWidth-g.clientWidth))),$f(c),f.wheelStartX=null,void 0;if(e&&null!=jd){var m=e*jd,n=b.doc.scrollTop,o=n+f.wrapper.clientHeight;0>m?n=Math.max(0,n+m-50):o=Math.min(b.doc.height,o+m+50),U(b,{top:n,bottom:o})}20>id&&(null==f.wheelStartX?(f.wheelStartX=g.scrollLeft,f.wheelStartY=g.scrollTop,f.wheelDX=d,f.wheelDY=e,setTimeout(function(){if(null!=f.wheelStartX){var a=g.scrollLeft-f.wheelStartX,b=g.scrollTop-f.wheelStartY,c=b&&f.wheelDY&&b/f.wheelDY||a&&f.wheelDX&&a/f.wheelDX;f.wheelStartX=f.wheelStartY=null,c&&(jd=(jd*id+c)/(id+1),++id)}},200)):(f.wheelDX+=d,f.wheelDY+=e))}}function ld(a,b,c){if("string"==typeof b&&(b=ie[b],!b))return!1;a.display.pollingFast&&Oc(a)&&(a.display.pollingFast=!1);var d=a.display.shift,e=!1;try{Sc(a)&&(a.state.suppressEdits=!0),c&&(a.display.shift=!1),e=b(a)!=qg}finally{a.display.shift=d,a.state.suppressEdits=!1}return e}function md(a){var b=a.state.keyMaps.slice(0);return a.options.extraKeys&&b.push(a.options.extraKeys),b.push(a.options.keyMap),b}function od(a,b){var c=ke(a.options.keyMap),d=c.auto;clearTimeout(nd),d&&!me(b)&&(nd=setTimeout(function(){ke(a.options.keyMap)==c&&(a.options.keyMap=d.call?d.call(null,a):d,F(a))},50));var e=ne(b,!0),f=!1;if(!e)return!1;var g=md(a);return f=b.shiftKey?le("Shift-"+e,g,function(b){return ld(a,b,!0)})||le(e,g,function(b){return("string"==typeof b?/^go[A-Z]/.test(b):b.motion)?ld(a,b):void 0}):le(e,g,function(b){return ld(a,b)}),f&&($f(b),Rb(a),jg(a,"keyHandled",a,e,b)),f}function pd(a,b,c){var d=le("'"+c+"'",md(a),function(b){return ld(a,b,!0)});return d&&($f(b),Rb(a),jg(a,"keyHandled",a,"'"+c+"'",b)),d}function rd(a){var c=this;if(Rc(c),!lg(c,a)){b&&27==a.keyCode&&(a.returnValue=!1);var d=a.keyCode;c.display.shift=16==d||a.shiftKey;var e=od(c,a);k&&(qd=e?d:null,!e&&88==d&&!hh&&(r?a.metaKey:a.ctrlKey)&&c.replaceSelection("",null,"cut")),18!=d||/\bCodeMirror-crosshair\b/.test(c.display.lineDiv.className)||sd(c)}}function sd(a){function c(a){18!=a.keyCode&&a.altKey||(Tg(b,"CodeMirror-crosshair"),fg(document,"keyup",c),fg(document,"mouseover",c))}var b=a.display.lineDiv;Ug(b,"CodeMirror-crosshair"),eg(document,"keyup",c),eg(document,"mouseover",c)}function td(a){lg(this,a)||16==a.keyCode&&(this.doc.sel.shift=!1)}function ud(a){var b=this;if(!lg(b,a)){var c=a.keyCode,e=a.charCode;if(k&&c==qd)return qd=null,$f(a),void 0;if(!(k&&(!a.which||a.which<10)||m)||!od(b,a)){var f=String.fromCharCode(null==e?c:e);pd(b,a,f)||(g&&!d&&(b.display.inputHasSelection=null),Nc(b))}}}function vd(a){"nocursor"!=a.options.readOnly&&(a.state.focused||(gg(a,"focus",a),a.state.focused=!0,Ug(a.display.wrapper,"CodeMirror-focused"),a.curOp||a.display.selForContextMenu==a.doc.sel||(Pc(a),h&&setTimeout(Fg(Pc,a,!0),0))),Mc(a),Rb(a))}function wd(a){a.state.focused&&(gg(a,"blur",a),a.state.focused=!1,Tg(a.display.wrapper,"CodeMirror-focused")),clearInterval(a.display.blinker),setTimeout(function(){a.state.focused||(a.display.shift=!1)},150)}function xd(a,b){function j(){if(null!=c.input.selectionStart){var b=a.somethingSelected(),d=c.input.value="\u200b"+(b?c.input.value:"");c.prevInput=b?"":"\u200b",c.input.selectionStart=1,c.input.selectionEnd=d.length,c.selForContextMenu=a.doc.sel}}function l(){if(c.inputDiv.style.position="relative",c.input.style.cssText=i,d&&(c.scrollbarV.scrollTop=c.scroller.scrollTop=f),Mc(a),null!=c.input.selectionStart){(!g||d)&&j();var b=0,e=function(){c.selForContextMenu==a.doc.sel&&0==c.input.selectionStart?Ac(a,ie.selectAll)(a):b++<10?c.detectingSelectAll=setTimeout(e,500):Pc(a)};c.detectingSelectAll=setTimeout(e,200)}}if(!lg(a,b,"contextmenu")){var c=a.display;if(!Vc(c,b)&&!yd(a,b)){var e=Wc(a,b),f=c.scroller.scrollTop;if(e&&!k){var h=a.options.resetSelectionOnContextMenu;h&&-1==a.doc.sel.contains(e)&&Ac(a,Ib)(a.doc,vb(e),rg);var i=c.input.style.cssText;if(c.inputDiv.style.position="absolute",c.input.style.cssText="position: fixed; width: 30px; height: 30px; top: "+(b.clientY-5)+"px; left: "+(b.clientX-5)+"px; z-index: 1000; background: "+(g?"rgba(255, 255, 255, .05)":"transparent")+"; outline: none; border-width: 0; outline: none; overflow: hidden; opacity: .05; filter: alpha(opacity=5);",Qc(a),Pc(a),a.somethingSelected()||(c.input.value=c.prevInput=" "),c.selForContextMenu=a.doc.sel,clearTimeout(c.detectingSelectAll),g&&!d&&j(),v){bg(b);var m=function(){fg(window,"mouseup",m),setTimeout(l,20)};eg(window,"mouseup",m)}else setTimeout(l,50)}}}}function yd(a,b){return ng(a,"gutterContextMenu")?bd(a,b,"gutterContextMenu",!1,gg):!1}function Ad(a,b){if(ob(a,b.from)<0)return a;if(ob(a,b.to)<=0)return zd(b);var c=a.line+b.text.length-(b.to.line-b.from.line)-1,d=a.ch;return a.line==b.to.line&&(d+=zd(b).ch-b.to.ch),nb(c,d)}function Bd(a,b){for(var c=[],d=0;d<a.sel.ranges.length;d++){var e=a.sel.ranges[d];c.push(new tb(Ad(e.anchor,b),Ad(e.head,b)))}return ub(c,a.sel.primIndex)}function Cd(a,b,c){return a.line==b.line?nb(c.line,a.ch-b.ch+c.ch):nb(c.line+(a.line-b.line),a.ch)}function Dd(a,b,c){for(var d=[],e=nb(a.first,0),f=e,g=0;g<b.length;g++){var h=b[g],i=Cd(h.from,e,f),j=Cd(zd(h),e,f);if(e=h.to,f=j,"around"==c){var k=a.sel.ranges[g],l=ob(k.head,k.anchor)<0;d[g]=new tb(l?j:i,l?i:j)}else d[g]=new tb(i,i)}return new sb(d,a.sel.primIndex)}function Ed(a,b,c){var d={canceled:!1,from:b.from,to:b.to,text:b.text,origin:b.origin,cancel:function(){this.canceled=!0}};return c&&(d.update=function(b,c,d,e){b&&(this.from=xb(a,b)),c&&(this.to=xb(a,c)),d&&(this.text=d),void 0!==e&&(this.origin=e)}),gg(a,"beforeChange",a,d),a.cm&&gg(a.cm,"beforeChange",a.cm,d),d.canceled?null:{from:d.from,to:d.to,text:d.text,origin:d.origin}}function Fd(a,b,c){if(a.cm){if(!a.cm.curOp)return Ac(a.cm,Fd)(a,b,c);if(a.cm.state.suppressEdits)return}if(!(ng(a,"beforeChange")||a.cm&&ng(a.cm,"beforeChange"))||(b=Ed(a,b,!0))){var d=w&&!c&&Ge(a,b.from,b.to);if(d)for(var e=d.length-1;e>=0;--e)Gd(a,{from:d[e].from,to:d[e].to,text:e?[""]:b.text});else Gd(a,b)}}function Gd(a,b){if(1!=b.text.length||""!=b.text[0]||0!=ob(b.from,b.to)){var c=Bd(a,b);Pf(a,b,c,a.cm?a.cm.curOp.id:0/0),Jd(a,b,c,De(a,b));var d=[];Bf(a,function(a,c){c||-1!=Bg(d,a.history)||(Zf(a.history,b),d.push(a.history)),Jd(a,b,null,De(a,b))})}}function Hd(a,b,c){if(!a.cm||!a.cm.state.suppressEdits){for(var e,d=a.history,f=a.sel,g="undo"==b?d.done:d.undone,h="undo"==b?d.undone:d.done,i=0;i<g.length&&(e=g[i],c?!e.ranges||e.equals(a.sel):e.ranges);i++);if(i!=g.length){for(d.lastOrigin=d.lastSelOrigin=null;e=g.pop(),e.ranges;){if(Sf(e,h),c&&!e.equals(a.sel))return Ib(a,e,{clearRedo:!1}),void 0;f=e}var j=[];Sf(f,h),h.push({changes:j,generation:d.generation}),d.generation=e.generation||++d.maxGeneration;for(var k=ng(a,"beforeChange")||a.cm&&ng(a.cm,"beforeChange"),i=e.changes.length-1;i>=0;--i){var l=e.changes[i];if(l.origin=b,k&&!Ed(a,l,!1))return g.length=0,void 0;j.push(Mf(a,l));var m=i?Bd(a,l,null):zg(g);Jd(a,l,m,Fe(a,l)),!i&&a.cm&&a.cm.scrollIntoView(l);var n=[];Bf(a,function(a,b){b||-1!=Bg(n,a.history)||(Zf(a.history,l),n.push(a.history)),Jd(a,l,null,Fe(a,l))})}}}}function Id(a,b){if(0!=b&&(a.first+=b,a.sel=new sb(Cg(a.sel.ranges,function(a){return new tb(nb(a.anchor.line+b,a.anchor.ch),nb(a.head.line+b,a.head.ch))}),a.sel.primIndex),a.cm)){Fc(a.cm,a.first,a.first-b,b);for(var c=a.cm.display,d=c.viewFrom;d<c.viewTo;d++)Gc(a.cm,d,"gutter")}}function Jd(a,b,c,d){if(a.cm&&!a.cm.curOp)return Ac(a.cm,Jd)(a,b,c,d);if(b.to.line<a.first)return Id(a,b.text.length-1-(b.to.line-b.from.line)),void 0;if(!(b.from.line>a.lastLine())){if(b.from.line<a.first){var e=b.text.length-1-(a.first-b.from.line);Id(a,e),b={from:nb(a.first,0),to:nb(b.to.line+e,b.to.ch),text:[zg(b.text)],origin:b.origin}}var f=a.lastLine();b.to.line>f&&(b={from:b.from,to:nb(f,Df(a,f).text.length),text:[b.text[0]],origin:b.origin}),b.removed=Ef(a,b.from,b.to),c||(c=Bd(a,b,null)),a.cm?Kd(a.cm,b,d):uf(a,b,d),Jb(a,c,rg)}}function Kd(a,b,c){var d=a.doc,e=a.display,f=b.from,g=b.to,h=!1,i=f.line;a.options.lineWrapping||(i=Hf(Qe(Df(d,f.line))),d.iter(i,g.line+1,function(a){return a==e.maxLine?(h=!0,!0):void 0})),d.sel.contains(b.from,b.to)>-1&&mg(a),uf(d,b,c,D(a)),a.options.lineWrapping||(d.iter(i,f.line+b.text.length,function(a){var b=K(a);b>e.maxLineLength&&(e.maxLine=a,e.maxLineLength=b,e.maxLineChanged=!0,h=!1)}),h&&(a.curOp.updateMaxLine=!0)),d.frontier=Math.min(d.frontier,f.line),Sb(a,400);var j=b.text.length-(g.line-f.line)-1;f.line!=g.line||1!=b.text.length||tf(a.doc,b)?Fc(a,f.line,g.line+1,j):Gc(a,f.line,"text");var k=ng(a,"changes"),l=ng(a,"change");if(l||k){var m={from:f,to:g,text:b.text,removed:b.removed,origin:b.origin};l&&jg(a,"change",a,m),k&&(a.curOp.changeObjs||(a.curOp.changeObjs=[])).push(m)}a.display.selForContextMenu=null}function Ld(a,b,c,d,e){if(d||(d=c),ob(d,c)<0){var f=d;d=c,c=f}"string"==typeof b&&(b=fh(b)),Fd(a,{from:c,to:d,text:b,origin:e})}function Md(a,b){var c=a.display,d=c.sizer.getBoundingClientRect(),e=null;if(b.top+d.top<0?e=!0:b.bottom+d.top>(window.innerHeight||document.documentElement.clientHeight)&&(e=!1),null!=e&&!o){var f=Mg("div","\u200b",null,"position: absolute; top: "+(b.top-c.viewOffset-Wb(a.display))+"px; height: "+(b.bottom-b.top+pg)+"px; left: "+b.left+"px; width: 2px;");a.display.lineSpace.appendChild(f),f.scrollIntoView(e),a.display.lineSpace.removeChild(f)}}function Nd(a,b,c,d){for(null==d&&(d=0);;){var e=!1,f=oc(a,b),g=c&&c!=b?oc(a,c):f,h=Pd(a,Math.min(f.left,g.left),Math.min(f.top,g.top)-d,Math.max(f.left,g.left),Math.max(f.bottom,g.bottom)+d),i=a.doc.scrollTop,j=a.doc.scrollLeft;if(null!=h.scrollTop&&(gd(a,h.scrollTop),Math.abs(a.doc.scrollTop-i)>1&&(e=!0)),null!=h.scrollLeft&&(hd(a,h.scrollLeft),Math.abs(a.doc.scrollLeft-j)>1&&(e=!0)),!e)return f}}function Od(a,b,c,d,e){var f=Pd(a,b,c,d,e);null!=f.scrollTop&&gd(a,f.scrollTop),null!=f.scrollLeft&&hd(a,f.scrollLeft)}function Pd(a,b,c,d,e){var f=a.display,g=uc(a.display);0>c&&(c=0);var h=a.curOp&&null!=a.curOp.scrollTop?a.curOp.scrollTop:f.scroller.scrollTop,i=f.scroller.clientHeight-pg,j={},k=a.doc.height+Xb(f),l=g>c,m=e>k-g;if(h>c)j.scrollTop=l?0:c;else if(e>h+i){var n=Math.min(c,(m?k:e)-i);n!=h&&(j.scrollTop=n)}var o=a.curOp&&null!=a.curOp.scrollLeft?a.curOp.scrollLeft:f.scroller.scrollLeft,p=f.scroller.clientWidth-pg;b+=f.gutters.offsetWidth,d+=f.gutters.offsetWidth;var q=f.gutters.offsetWidth,r=q+10>b;return o+q>b||r?(r&&(b=0),j.scrollLeft=Math.max(0,b-10-q)):d>p+o-3&&(j.scrollLeft=d+10-p),j}function Qd(a,b,c){(null!=b||null!=c)&&Sd(a),null!=b&&(a.curOp.scrollLeft=(null==a.curOp.scrollLeft?a.doc.scrollLeft:a.curOp.scrollLeft)+b),null!=c&&(a.curOp.scrollTop=(null==a.curOp.scrollTop?a.doc.scrollTop:a.curOp.scrollTop)+c)}function Rd(a){Sd(a);var b=a.getCursor(),c=b,d=b;a.options.lineWrapping||(c=b.ch?nb(b.line,b.ch-1):b,d=nb(b.line,b.ch+1)),a.curOp.scrollToPos={from:c,to:d,margin:a.options.cursorScrollMargin,isCursor:!0}}function Sd(a){var b=a.curOp.scrollToPos;if(b){a.curOp.scrollToPos=null;var c=pc(a,b.from),d=pc(a,b.to),e=Pd(a,Math.min(c.left,d.left),Math.min(c.top,d.top)-b.margin,Math.max(c.right,d.right),Math.max(c.bottom,d.bottom)+b.margin);a.scrollTo(e.scrollLeft,e.scrollTop)}}function Td(a,b,c,d){var f,e=a.doc;null==c&&(c="add"),"smart"==c&&(a.doc.mode.indent?f=Vb(a,b):c="prev");var g=a.options.tabSize,h=Df(e,b),i=vg(h.text,null,g);h.stateAfter&&(h.stateAfter=null);var k,j=h.text.match(/^\s*/)[0];if(d||/\S/.test(h.text)){if("smart"==c&&(k=a.doc.mode.indent(f,h.text.slice(j.length),h.text),k==qg)){if(!d)return;c="prev"}}else k=0,c="not";"prev"==c?k=b>e.first?vg(Df(e,b-1).text,null,g):0:"add"==c?k=i+a.options.indentUnit:"subtract"==c?k=i-a.options.indentUnit:"number"==typeof c&&(k=i+c),k=Math.max(0,k);var l="",m=0;if(a.options.indentWithTabs)for(var n=Math.floor(k/g);n;--n)m+=g,l+="	";if(k>m&&(l+=yg(k-m)),l!=j)Ld(a.doc,l,nb(b,0),nb(b,j.length),"+input");else for(var n=0;n<e.sel.ranges.length;n++){var o=e.sel.ranges[n];if(o.head.line==b&&o.head.ch<j.length){var m=nb(b,j.length);Eb(e,n,new tb(m,m));break}}h.stateAfter=null}function Ud(a,b,c,d){var e=b,f=b,g=a.doc;return"number"==typeof b?f=Df(g,wb(g,b)):e=Hf(b),null==e?null:(d(f,e)&&Gc(a,e,c),f)}function Vd(a,b){for(var c=a.doc.sel.ranges,d=[],e=0;e<c.length;e++){for(var f=b(c[e]);d.length&&ob(f.from,zg(d).to)<=0;){var g=d.pop();if(ob(g.from,f.from)<0){f.from=g.from;break}}d.push(f)}zc(a,function(){for(var b=d.length-1;b>=0;b--)Ld(a.doc,"",d[b].from,d[b].to,"+delete");Rd(a)})}function Wd(a,b,c,d,e){function k(){var b=f+c;return b<a.first||b>=a.first+a.size?j=!1:(f=b,i=Df(a,b))}function l(a){var b=(e?uh:vh)(i,g,c,!0);if(null==b){if(a||!k())return j=!1;g=e?(0>c?nh:mh)(i):0>c?i.text.length:0}else g=b;return!0}var f=b.line,g=b.ch,h=c,i=Df(a,f),j=!0;if("char"==d)l();else if("column"==d)l(!0);else if("word"==d||"group"==d)for(var m=null,n="group"==d,o=a.cm&&a.cm.getHelper(b,"wordChars"),p=!0;!(0>c)||l(!p);p=!1){var q=i.text.charAt(g)||"\n",r=Ig(q,o)?"w":n&&"\n"==q?"n":!n||/\s/.test(q)?null:"p";if(!n||p||r||(r="s"),m&&m!=r){0>c&&(c=1,l());break}if(r&&(m=r),c>0&&!l(!p))break}var s=Nb(a,nb(f,g),h,!0);return j||(s.hitSide=!0),s}function Xd(a,b,c,d){var g,e=a.doc,f=b.left;if("page"==d){var h=Math.min(a.display.wrapper.clientHeight,window.innerHeight||document.documentElement.clientHeight);g=b.top+c*(h-(0>c?1.5:.5)*uc(a.display))}else"line"==d&&(g=c>0?b.bottom+3:b.top-3);for(;;){var i=rc(a,f,g);if(!i.outside)break;if(0>c?0>=g:g>=e.height){i.hitSide=!0;break}g+=5*c}return i}function Yd(a,b){var c=a.doc,d=Df(c,b.line).text,e=b.ch,f=b.ch;if(d){var g=a.getHelper(b,"wordChars");(b.xRel<0||f==d.length)&&e?--e:++f;for(var h=d.charAt(e),i=Ig(h,g)?function(a){return Ig(a,g)}:/\s/.test(h)?function(a){return/\s/.test(a)}:function(a){return!/\s/.test(a)&&!Ig(a)};e>0&&i(d.charAt(e-1));)--e;for(;f<d.length&&i(d.charAt(f));)++f}return new tb(nb(b.line,e),nb(b.line,f))}function _d(a,b,c,d){y.defaults[a]=b,c&&($d[a]=d?function(a,b,d){d!=ae&&c(a,b,d)}:c)}function ke(a){return"string"==typeof a?je[a]:a}function re(a,b,c,d,e){if(d&&d.shared)return te(a,b,c,d,e);if(a.cm&&!a.cm.curOp)return Ac(a.cm,re)(a,b,c,d,e);var f=new pe(a,e),g=ob(b,c);if(d&&Eg(d,f,!1),g>0||0==g&&f.clearWhenEmpty!==!1)return f;if(f.replacedWith&&(f.collapsed=!0,f.widgetNode=Mg("span",[f.replacedWith],"CodeMirror-widget"),d.handleMouseEvents||(f.widgetNode.ignoreEvents=!0),d.insertLeft&&(f.widgetNode.insertLeft=!0)),f.collapsed){if(Pe(a,b.line,b,c,f)||b.line!=c.line&&Pe(a,c.line,b,c,f))throw new Error("Inserting collapsed marker partially overlapping an existing one");x=!0}f.addToHistory&&Pf(a,{from:b,to:c,origin:"markText"},a.sel,0/0);var j,h=b.line,i=a.cm;if(a.iter(h,c.line+1,function(a){i&&f.collapsed&&!i.options.lineWrapping&&Qe(a)==i.display.maxLine&&(j=!0),f.collapsed&&h!=b.line&&Gf(a,0),Ae(a,new xe(f,h==b.line?b.ch:null,h==c.line?c.ch:null)),++h}),f.collapsed&&a.iter(b.line,c.line+1,function(b){Ue(a,b)&&Gf(b,0)}),f.clearOnEnter&&eg(f,"beforeCursorEnter",function(){f.clear()}),f.readOnly&&(w=!0,(a.history.done.length||a.history.undone.length)&&a.clearHistory()),f.collapsed&&(f.id=++qe,f.atomic=!0),i){if(j&&(i.curOp.updateMaxLine=!0),f.collapsed)Fc(i,b.line,c.line+1);else if(f.className||f.title||f.startStyle||f.endStyle)for(var k=b.line;k<=c.line;k++)Gc(i,k,"text");f.atomic&&Lb(i.doc),jg(i,"markerAdded",i,f)}return f}function te(a,b,c,d,e){d=Eg(d),d.shared=!1;var f=[re(a,b,c,d,e)],g=f[0],h=d.widgetNode;return Bf(a,function(a){h&&(d.widgetNode=h.cloneNode(!0)),f.push(re(a,xb(a,b),xb(a,c),d,e));for(var i=0;i<a.linked.length;++i)if(a.linked[i].isParent)return;g=zg(f)}),new se(f,g)}function ue(a){return a.findMarks(nb(a.first,0),a.clipPos(nb(a.lastLine())),function(a){return a.parent})}function ve(a,b){for(var c=0;c<b.length;c++){var d=b[c],e=d.find(),f=a.clipPos(e.from),g=a.clipPos(e.to);if(ob(f,g)){var h=re(a,f,g,d.primary,d.primary.type);d.markers.push(h),h.parent=d}}}function we(a){for(var b=0;b<a.length;b++){var c=a[b],d=[c.primary.doc];Bf(c.primary.doc,function(a){d.push(a)});for(var e=0;e<c.markers.length;e++){var f=c.markers[e];-1==Bg(d,f.doc)&&(f.parent=null,c.markers.splice(e--,1))}}}function xe(a,b,c){this.marker=a,this.from=b,this.to=c}function ye(a,b){if(a)for(var c=0;c<a.length;++c){var d=a[c];if(d.marker==b)return d}}function ze(a,b){for(var c,d=0;d<a.length;++d)a[d]!=b&&(c||(c=[])).push(a[d]);return c}function Ae(a,b){a.markedSpans=a.markedSpans?a.markedSpans.concat([b]):[b],b.marker.attachLine(a)}function Be(a,b,c){if(a)for(var e,d=0;d<a.length;++d){var f=a[d],g=f.marker,h=null==f.from||(g.inclusiveLeft?f.from<=b:f.from<b);if(h||f.from==b&&"bookmark"==g.type&&(!c||!f.marker.insertLeft)){var i=null==f.to||(g.inclusiveRight?f.to>=b:f.to>b);(e||(e=[])).push(new xe(g,f.from,i?null:f.to))}}return e}function Ce(a,b,c){if(a)for(var e,d=0;d<a.length;++d){var f=a[d],g=f.marker,h=null==f.to||(g.inclusiveRight?f.to>=b:f.to>b);if(h||f.from==b&&"bookmark"==g.type&&(!c||f.marker.insertLeft)){var i=null==f.from||(g.inclusiveLeft?f.from<=b:f.from<b);(e||(e=[])).push(new xe(g,i?null:f.from-b,null==f.to?null:f.to-b))}}return e}function De(a,b){var c=zb(a,b.from.line)&&Df(a,b.from.line).markedSpans,d=zb(a,b.to.line)&&Df(a,b.to.line).markedSpans;if(!c&&!d)return null;var e=b.from.ch,f=b.to.ch,g=0==ob(b.from,b.to),h=Be(c,e,g),i=Ce(d,f,g),j=1==b.text.length,k=zg(b.text).length+(j?e:0);if(h)for(var l=0;l<h.length;++l){var m=h[l];if(null==m.to){var n=ye(i,m.marker);n?j&&(m.to=null==n.to?null:n.to+k):m.to=e}}if(i)for(var l=0;l<i.length;++l){var m=i[l];if(null!=m.to&&(m.to+=k),null==m.from){var n=ye(h,m.marker);n||(m.from=k,j&&(h||(h=[])).push(m))}else m.from+=k,j&&(h||(h=[])).push(m)}h&&(h=Ee(h)),i&&i!=h&&(i=Ee(i));var o=[h];if(!j){var q,p=b.text.length-2;if(p>0&&h)for(var l=0;l<h.length;++l)null==h[l].to&&(q||(q=[])).push(new xe(h[l].marker,null,null));
for(var l=0;p>l;++l)o.push(q);o.push(i)}return o}function Ee(a){for(var b=0;b<a.length;++b){var c=a[b];null!=c.from&&c.from==c.to&&c.marker.clearWhenEmpty!==!1&&a.splice(b--,1)}return a.length?a:null}function Fe(a,b){var c=Vf(a,b),d=De(a,b);if(!c)return d;if(!d)return c;for(var e=0;e<c.length;++e){var f=c[e],g=d[e];if(f&&g)a:for(var h=0;h<g.length;++h){for(var i=g[h],j=0;j<f.length;++j)if(f[j].marker==i.marker)continue a;f.push(i)}else g&&(c[e]=g)}return c}function Ge(a,b,c){var d=null;if(a.iter(b.line,c.line+1,function(a){if(a.markedSpans)for(var b=0;b<a.markedSpans.length;++b){var c=a.markedSpans[b].marker;!c.readOnly||d&&-1!=Bg(d,c)||(d||(d=[])).push(c)}}),!d)return null;for(var e=[{from:b,to:c}],f=0;f<d.length;++f)for(var g=d[f],h=g.find(0),i=0;i<e.length;++i){var j=e[i];if(!(ob(j.to,h.from)<0||ob(j.from,h.to)>0)){var k=[i,1],l=ob(j.from,h.from),m=ob(j.to,h.to);(0>l||!g.inclusiveLeft&&!l)&&k.push({from:j.from,to:h.from}),(m>0||!g.inclusiveRight&&!m)&&k.push({from:h.to,to:j.to}),e.splice.apply(e,k),i+=k.length-1}}return e}function He(a){var b=a.markedSpans;if(b){for(var c=0;c<b.length;++c)b[c].marker.detachLine(a);a.markedSpans=null}}function Ie(a,b){if(b){for(var c=0;c<b.length;++c)b[c].marker.attachLine(a);a.markedSpans=b}}function Je(a){return a.inclusiveLeft?-1:0}function Ke(a){return a.inclusiveRight?1:0}function Le(a,b){var c=a.lines.length-b.lines.length;if(0!=c)return c;var d=a.find(),e=b.find(),f=ob(d.from,e.from)||Je(a)-Je(b);if(f)return-f;var g=ob(d.to,e.to)||Ke(a)-Ke(b);return g?g:b.id-a.id}function Me(a,b){var d,c=x&&a.markedSpans;if(c)for(var e,f=0;f<c.length;++f)e=c[f],e.marker.collapsed&&null==(b?e.from:e.to)&&(!d||Le(d,e.marker)<0)&&(d=e.marker);return d}function Ne(a){return Me(a,!0)}function Oe(a){return Me(a,!1)}function Pe(a,b,c,d,e){var f=Df(a,b),g=x&&f.markedSpans;if(g)for(var h=0;h<g.length;++h){var i=g[h];if(i.marker.collapsed){var j=i.marker.find(0),k=ob(j.from,c)||Je(i.marker)-Je(e),l=ob(j.to,d)||Ke(i.marker)-Ke(e);if(!(k>=0&&0>=l||0>=k&&l>=0)&&(0>=k&&(ob(j.to,c)||Ke(i.marker)-Je(e))>0||k>=0&&(ob(j.from,d)||Je(i.marker)-Ke(e))<0))return!0}}}function Qe(a){for(var b;b=Ne(a);)a=b.find(-1,!0).line;return a}function Re(a){for(var b,c;b=Oe(a);)a=b.find(1,!0).line,(c||(c=[])).push(a);return c}function Se(a,b){var c=Df(a,b),d=Qe(c);return c==d?b:Hf(d)}function Te(a,b){if(b>a.lastLine())return b;var d,c=Df(a,b);if(!Ue(a,c))return b;for(;d=Oe(c);)c=d.find(1,!0).line;return Hf(c)+1}function Ue(a,b){var c=x&&b.markedSpans;if(c)for(var d,e=0;e<c.length;++e)if(d=c[e],d.marker.collapsed){if(null==d.from)return!0;if(!d.marker.widgetNode&&0==d.from&&d.marker.inclusiveLeft&&Ve(a,b,d))return!0}}function Ve(a,b,c){if(null==c.to){var d=c.marker.find(1,!0);return Ve(a,d.line,ye(d.line.markedSpans,c.marker))}if(c.marker.inclusiveRight&&c.to==b.text.length)return!0;for(var e,f=0;f<b.markedSpans.length;++f)if(e=b.markedSpans[f],e.marker.collapsed&&!e.marker.widgetNode&&e.from==c.to&&(null==e.to||e.to!=c.from)&&(e.marker.inclusiveLeft||c.marker.inclusiveRight)&&Ve(a,b,e))return!0}function Xe(a,b,c){Jf(b)<(a.curOp&&a.curOp.scrollTop||a.doc.scrollTop)&&Qd(a,null,c)}function Ye(a){return null!=a.height?a.height:(Qg(document.body,a.node)||Pg(a.cm.display.measure,Mg("div",[a.node],null,"position: relative")),a.height=a.node.offsetHeight)}function Ze(a,b,c,d){var e=new We(a,c,d);return e.noHScroll&&(a.display.alignWidgets=!0),Ud(a,b,"widget",function(b){var c=b.widgets||(b.widgets=[]);if(null==e.insertAt?c.push(e):c.splice(Math.min(c.length-1,Math.max(0,e.insertAt)),0,e),e.line=b,!Ue(a.doc,b)){var d=Jf(b)<a.doc.scrollTop;Gf(b,b.height+Ye(e)),d&&Qd(a,null,e.height),a.curOp.forceUpdate=!0}return!0}),e}function _e(a,b,c,d){a.text=b,a.stateAfter&&(a.stateAfter=null),a.styles&&(a.styles=null),null!=a.order&&(a.order=null),He(a),Ie(a,c);var e=d?d(a):1;e!=a.height&&Gf(a,e)}function af(a){a.parent=null,He(a)}function bf(a,b){if(a)for(;;){var c=a.match(/(?:^|\s+)line-(background-)?(\S+)/);if(!c)break;a=a.slice(0,c.index)+a.slice(c.index+c[0].length);var d=c[1]?"bgClass":"textClass";null==b[d]?b[d]=c[2]:new RegExp("(?:^|s)"+c[2]+"(?:$|s)").test(b[d])||(b[d]+=" "+c[2])}return a}function cf(a,b){if(a.blankLine)return a.blankLine(b);if(a.innerMode){var c=y.innerMode(a,b);return c.mode.blankLine?c.mode.blankLine(c.state):void 0}}function df(a,b,c){for(var d=0;10>d;d++){var e=a.token(b,c);if(b.pos>b.start)return e}throw new Error("Mode "+a.name+" failed to advance stream.")}function ef(a,b,c,d,e,f,g){var h=c.flattenSpans;null==h&&(h=a.options.flattenSpans);var l,i=0,j=null,k=new oe(b,a.options.tabSize);for(""==b&&bf(cf(c,d),f);!k.eol();){if(k.pos>a.options.maxHighlightLength?(h=!1,g&&hf(a,b,d,k.pos),k.pos=b.length,l=null):l=bf(df(c,k,d),f),a.options.addModeClass){var m=y.innerMode(c,d).mode.name;m&&(l="m-"+(l?m+" "+l:m))}h&&j==l||(i<k.start&&e(k.start,j),i=k.start,j=l),k.start=k.pos}for(;i<k.pos;){var n=Math.min(k.pos,i+5e4);e(n,j),i=n}}function ff(a,b,c,d){var e=[a.state.modeGen],f={};ef(a,b.text,a.doc.mode,c,function(a,b){e.push(a,b)},f,d);for(var g=0;g<a.state.overlays.length;++g){var h=a.state.overlays[g],i=1,j=0;ef(a,b.text,h.mode,!0,function(a,b){for(var c=i;a>j;){var d=e[i];d>a&&e.splice(i,1,a,e[i+1],d),i+=2,j=Math.min(a,d)}if(b)if(h.opaque)e.splice(c,i-c,a,"cm-overlay "+b),i=c+2;else for(;i>c;c+=2){var f=e[c+1];e[c+1]=(f?f+" ":"")+"cm-overlay "+b}},f)}return{styles:e,classes:f.bgClass||f.textClass?f:null}}function gf(a,b){if(!b.styles||b.styles[0]!=a.state.modeGen){var c=ff(a,b,b.stateAfter=Vb(a,Hf(b)));b.styles=c.styles,c.classes?b.styleClasses=c.classes:b.styleClasses&&(b.styleClasses=null)}return b.styles}function hf(a,b,c,d){var e=a.doc.mode,f=new oe(b,a.options.tabSize);for(f.start=f.pos=d||0,""==b&&cf(e,c);!f.eol()&&f.pos<=a.options.maxHighlightLength;)df(e,f,c),f.start=f.pos}function lf(a,b){if(!a||/^\s*$/.test(a))return null;var c=b.addModeClass?kf:jf;return c[a]||(c[a]=a.replace(/\S+/g,"cm-$&"))}function mf(a,b){var c=Mg("span",null,null,h?"padding-right: .1px":null),d={pre:Mg("pre",[c]),content:c,col:0,pos:0,cm:a};b.measure={};for(var e=0;e<=(b.rest?b.rest.length:0);e++){var i,f=e?b.rest[e-1]:b.line;d.pos=0,d.addToken=of,(g||h)&&a.getOption("lineWrapping")&&(d.addToken=pf(d.addToken)),eh(a.display.measure)&&(i=Kf(f))&&(d.addToken=qf(d.addToken,i)),d.map=[],sf(f,d,gf(a,f)),f.styleClasses&&(f.styleClasses.bgClass&&(d.bgClass=Vg(f.styleClasses.bgClass,d.bgClass||"")),f.styleClasses.textClass&&(d.textClass=Vg(f.styleClasses.textClass,d.textClass||""))),0==d.map.length&&d.map.push(0,0,d.content.appendChild(ch(a.display.measure))),0==e?(b.measure.map=d.map,b.measure.cache={}):((b.measure.maps||(b.measure.maps=[])).push(d.map),(b.measure.caches||(b.measure.caches=[])).push({}))}return gg(a,"renderLine",a,b.line,d.pre),d.pre.className&&(d.textClass=Vg(d.pre.className,d.textClass||"")),d}function nf(a){var b=Mg("span","\u2022","cm-invalidchar");return b.title="\\u"+a.charCodeAt(0).toString(16),b}function of(a,b,c,e,f,g){if(b){var h=a.cm.options.specialChars,i=!1;if(h.test(b))for(var j=document.createDocumentFragment(),k=0;;){h.lastIndex=k;var l=h.exec(b),m=l?l.index-k:b.length-k;if(m){var n=document.createTextNode(b.slice(k,k+m));d?j.appendChild(Mg("span",[n])):j.appendChild(n),a.map.push(a.pos,a.pos+m,n),a.col+=m,a.pos+=m}if(!l)break;if(k+=m+1,"	"==l[0]){var o=a.cm.options.tabSize,p=o-a.col%o,n=j.appendChild(Mg("span",yg(p),"cm-tab"));a.col+=p}else{var n=a.cm.options.specialCharPlaceholder(l[0]);d?j.appendChild(Mg("span",[n])):j.appendChild(n),a.col+=1}a.map.push(a.pos,a.pos+1,n),a.pos++}else{a.col+=b.length;var j=document.createTextNode(b);a.map.push(a.pos,a.pos+b.length,j),d&&(i=!0),a.pos+=b.length}if(c||e||f||i){var q=c||"";e&&(q+=e),f&&(q+=f);var r=Mg("span",[j],q);return g&&(r.title=g),a.content.appendChild(r)}a.content.appendChild(j)}}function pf(a){function b(a){for(var b=" ",c=0;c<a.length-2;++c)b+=c%2?" ":"\xa0";return b+=" "}return function(c,d,e,f,g,h){a(c,d.replace(/ {3,}/g,b),e,f,g,h)}}function qf(a,b){return function(c,d,e,f,g,h){e=e?e+" cm-force-border":"cm-force-border";for(var i=c.pos,j=i+d.length;;){for(var k=0;k<b.length;k++){var l=b[k];if(l.to>i&&l.from<=i)break}if(l.to>=j)return a(c,d,e,f,g,h);a(c,d.slice(0,l.to-i),e,f,null,h),f=null,d=d.slice(l.to-i),i=l.to}}}function rf(a,b,c,d){var e=!d&&c.widgetNode;e&&(a.map.push(a.pos,a.pos+b,e),a.content.appendChild(e)),a.pos+=b}function sf(a,b,c){var d=a.markedSpans,e=a.text,f=0;if(d)for(var k,m,n,o,p,q,h=e.length,i=0,g=1,j="",l=0;;){if(l==i){m=n=o=p="",q=null,l=1/0;for(var r=[],s=0;s<d.length;++s){var t=d[s],u=t.marker;t.from<=i&&(null==t.to||t.to>i)?(null!=t.to&&l>t.to&&(l=t.to,n=""),u.className&&(m+=" "+u.className),u.startStyle&&t.from==i&&(o+=" "+u.startStyle),u.endStyle&&t.to==l&&(n+=" "+u.endStyle),u.title&&!p&&(p=u.title),u.collapsed&&(!q||Le(q.marker,u)<0)&&(q=t)):t.from>i&&l>t.from&&(l=t.from),"bookmark"==u.type&&t.from==i&&u.widgetNode&&r.push(u)}if(q&&(q.from||0)==i&&(rf(b,(null==q.to?h+1:q.to)-i,q.marker,null==q.from),null==q.to))return;if(!q&&r.length)for(var s=0;s<r.length;++s)rf(b,0,r[s])}if(i>=h)break;for(var v=Math.min(h,l);;){if(j){var w=i+j.length;if(!q){var x=w>v?j.slice(0,v-i):j;b.addToken(b,x,k?k+m:m,o,i+x.length==l?n:"",p)}if(w>=v){j=j.slice(v-i),i=v;break}i=w,o=""}j=e.slice(f,f=c[g++]),k=lf(c[g++],b.cm.options)}}else for(var g=1;g<c.length;g+=2)b.addToken(b,e.slice(f,f=c[g]),lf(c[g+1],b.cm.options))}function tf(a,b){return 0==b.from.ch&&0==b.to.ch&&""==zg(b.text)&&(!a.cm||a.cm.options.wholeLineUpdateBefore)}function uf(a,b,c,d){function e(a){return c?c[a]:null}function f(a,c,e){_e(a,c,e,d),jg(a,"change",a,b)}var g=b.from,h=b.to,i=b.text,j=Df(a,g.line),k=Df(a,h.line),l=zg(i),m=e(i.length-1),n=h.line-g.line;if(tf(a,b)){for(var o=0,p=[];o<i.length-1;++o)p.push(new $e(i[o],e(o),d));f(k,k.text,m),n&&a.remove(g.line,n),p.length&&a.insert(g.line,p)}else if(j==k)if(1==i.length)f(j,j.text.slice(0,g.ch)+l+j.text.slice(h.ch),m);else{for(var p=[],o=1;o<i.length-1;++o)p.push(new $e(i[o],e(o),d));p.push(new $e(l+j.text.slice(h.ch),m,d)),f(j,j.text.slice(0,g.ch)+i[0],e(0)),a.insert(g.line+1,p)}else if(1==i.length)f(j,j.text.slice(0,g.ch)+i[0]+k.text.slice(h.ch),e(0)),a.remove(g.line+1,n);else{f(j,j.text.slice(0,g.ch)+i[0],e(0)),f(k,l+k.text.slice(h.ch),m);for(var o=1,p=[];o<i.length-1;++o)p.push(new $e(i[o],e(o),d));n>1&&a.remove(g.line+1,n-1),a.insert(g.line+1,p)}jg(a,"change",a,b)}function vf(a){this.lines=a,this.parent=null;for(var b=0,c=0;b<a.length;++b)a[b].parent=this,c+=a[b].height;this.height=c}function wf(a){this.children=a;for(var b=0,c=0,d=0;d<a.length;++d){var e=a[d];b+=e.chunkSize(),c+=e.height,e.parent=this}this.size=b,this.height=c,this.parent=null}function Bf(a,b,c){function d(a,e,f){if(a.linked)for(var g=0;g<a.linked.length;++g){var h=a.linked[g];if(h.doc!=e){var i=f&&h.sharedHist;(!c||i)&&(b(h.doc,i),d(h.doc,a,i))}}}d(a,null,!0)}function Cf(a,b){if(b.cm)throw new Error("This document is already in use.");a.doc=b,b.cm=a,E(a),A(a),a.options.lineWrapping||L(a),a.options.mode=b.modeOption,Fc(a)}function Df(a,b){if(b-=a.first,0>b||b>=a.size)throw new Error("There is no line "+(b+a.first)+" in the document.");for(var c=a;!c.lines;)for(var d=0;;++d){var e=c.children[d],f=e.chunkSize();if(f>b){c=e;break}b-=f}return c.lines[b]}function Ef(a,b,c){var d=[],e=b.line;return a.iter(b.line,c.line+1,function(a){var f=a.text;e==c.line&&(f=f.slice(0,c.ch)),e==b.line&&(f=f.slice(b.ch)),d.push(f),++e}),d}function Ff(a,b,c){var d=[];return a.iter(b,c,function(a){d.push(a.text)}),d}function Gf(a,b){var c=b-a.height;if(c)for(var d=a;d;d=d.parent)d.height+=c}function Hf(a){if(null==a.parent)return null;for(var b=a.parent,c=Bg(b.lines,a),d=b.parent;d;b=d,d=d.parent)for(var e=0;d.children[e]!=b;++e)c+=d.children[e].chunkSize();return c+b.first}function If(a,b){var c=a.first;a:do{for(var d=0;d<a.children.length;++d){var e=a.children[d],f=e.height;if(f>b){a=e;continue a}b-=f,c+=e.chunkSize()}return c}while(!a.lines);for(var d=0;d<a.lines.length;++d){var g=a.lines[d],h=g.height;if(h>b)break;b-=h}return c+d}function Jf(a){a=Qe(a);for(var b=0,c=a.parent,d=0;d<c.lines.length;++d){var e=c.lines[d];if(e==a)break;b+=e.height}for(var f=c.parent;f;c=f,f=c.parent)for(var d=0;d<f.children.length;++d){var g=f.children[d];if(g==c)break;b+=g.height}return b}function Kf(a){var b=a.order;return null==b&&(b=a.order=wh(a.text)),b}function Lf(a){this.done=[],this.undone=[],this.undoDepth=1/0,this.lastModTime=this.lastSelTime=0,this.lastOp=null,this.lastOrigin=this.lastSelOrigin=null,this.generation=this.maxGeneration=a||1}function Mf(a,b){var c={from:pb(b.from),to:zd(b),text:Ef(a,b.from,b.to)};return Tf(a,c,b.from.line,b.to.line+1),Bf(a,function(a){Tf(a,c,b.from.line,b.to.line+1)},!0),c}function Nf(a){for(;a.length;){var b=zg(a);if(!b.ranges)break;a.pop()}}function Of(a,b){return b?(Nf(a.done),zg(a.done)):a.done.length&&!zg(a.done).ranges?zg(a.done):a.done.length>1&&!a.done[a.done.length-2].ranges?(a.done.pop(),zg(a.done)):void 0}function Pf(a,b,c,d){var e=a.history;e.undone.length=0;var g,f=+new Date;if((e.lastOp==d||e.lastOrigin==b.origin&&b.origin&&("+"==b.origin.charAt(0)&&a.cm&&e.lastModTime>f-a.cm.options.historyEventDelay||"*"==b.origin.charAt(0)))&&(g=Of(e,e.lastOp==d))){var h=zg(g.changes);0==ob(b.from,b.to)&&0==ob(b.from,h.to)?h.to=zd(b):g.changes.push(Mf(a,b))}else{var i=zg(e.done);for(i&&i.ranges||Sf(a.sel,e.done),g={changes:[Mf(a,b)],generation:e.generation},e.done.push(g);e.done.length>e.undoDepth;)e.done.shift(),e.done[0].ranges||e.done.shift()}e.done.push(c),e.generation=++e.maxGeneration,e.lastModTime=e.lastSelTime=f,e.lastOp=d,e.lastOrigin=e.lastSelOrigin=b.origin,h||gg(a,"historyAdded")}function Qf(a,b,c,d){var e=b.charAt(0);return"*"==e||"+"==e&&c.ranges.length==d.ranges.length&&c.somethingSelected()==d.somethingSelected()&&new Date-a.history.lastSelTime<=(a.cm?a.cm.options.historyEventDelay:500)}function Rf(a,b,c,d){var e=a.history,f=d&&d.origin;c==e.lastOp||f&&e.lastSelOrigin==f&&(e.lastModTime==e.lastSelTime&&e.lastOrigin==f||Qf(a,f,zg(e.done),b))?e.done[e.done.length-1]=b:Sf(b,e.done),e.lastSelTime=+new Date,e.lastSelOrigin=f,e.lastOp=c,d&&d.clearRedo!==!1&&Nf(e.undone)}function Sf(a,b){var c=zg(b);c&&c.ranges&&c.equals(a)||b.push(a)}function Tf(a,b,c,d){var e=b["spans_"+a.id],f=0;a.iter(Math.max(a.first,c),Math.min(a.first+a.size,d),function(c){c.markedSpans&&((e||(e=b["spans_"+a.id]={}))[f]=c.markedSpans),++f})}function Uf(a){if(!a)return null;for(var c,b=0;b<a.length;++b)a[b].marker.explicitlyCleared?c||(c=a.slice(0,b)):c&&c.push(a[b]);return c?c.length?c:null:a}function Vf(a,b){var c=b["spans_"+a.id];if(!c)return null;for(var d=0,e=[];d<b.text.length;++d)e.push(Uf(c[d]));return e}function Wf(a,b,c){for(var d=0,e=[];d<a.length;++d){var f=a[d];if(f.ranges)e.push(c?sb.prototype.deepCopy.call(f):f);else{var g=f.changes,h=[];e.push({changes:h});for(var i=0;i<g.length;++i){var k,j=g[i];if(h.push({from:j.from,to:j.to,text:j.text}),b)for(var l in j)(k=l.match(/^spans_(\d+)$/))&&Bg(b,Number(k[1]))>-1&&(zg(h)[l]=j[l],delete j[l])}}}return e}function Xf(a,b,c,d){c<a.line?a.line+=d:b<a.line&&(a.line=b,a.ch=0)}function Yf(a,b,c,d){for(var e=0;e<a.length;++e){var f=a[e],g=!0;if(f.ranges){f.copied||(f=a[e]=f.deepCopy(),f.copied=!0);for(var h=0;h<f.ranges.length;h++)Xf(f.ranges[h].anchor,b,c,d),Xf(f.ranges[h].head,b,c,d)}else{for(var h=0;h<f.changes.length;++h){var i=f.changes[h];if(c<i.from.line)i.from=nb(i.from.line+d,i.from.ch),i.to=nb(i.to.line+d,i.to.ch);else if(b<=i.to.line){g=!1;break}}g||(a.splice(0,e+1),e=0)}}}function Zf(a,b){var c=b.from.line,d=b.to.line,e=b.text.length-(d-c)-1;Yf(a.done,c,d,e),Yf(a.undone,c,d,e)}function ag(a){return null!=a.defaultPrevented?a.defaultPrevented:0==a.returnValue}function cg(a){return a.target||a.srcElement}function dg(a){var b=a.which;return null==b&&(1&a.button?b=1:2&a.button?b=3:4&a.button&&(b=2)),r&&a.ctrlKey&&1==b&&(b=3),b}function jg(a,b){function e(a){return function(){a.apply(null,d)}}var c=a._handlers&&a._handlers[b];if(c){var d=Array.prototype.slice.call(arguments,2);hg||(++ig,hg=[],setTimeout(kg,0));for(var f=0;f<c.length;++f)hg.push(e(c[f]))}}function kg(){--ig;var a=hg;hg=null;for(var b=0;b<a.length;++b)a[b]()}function lg(a,b,c){return gg(a,c||b.type,a,b),ag(b)||b.codemirrorIgnore}function mg(a){var b=a._handlers&&a._handlers.cursorActivity;if(b)for(var c=a.curOp.cursorActivityHandlers||(a.curOp.cursorActivityHandlers=[]),d=0;d<b.length;++d)-1==Bg(c,b[d])&&c.push(b[d])}function ng(a,b){var c=a._handlers&&a._handlers[b];return c&&c.length>0}function og(a){a.prototype.on=function(a,b){eg(this,a,b)},a.prototype.off=function(a,b){fg(this,a,b)}}function ug(){this.id=null}function wg(a,b,c){for(var d=0,e=0;;){var f=a.indexOf("	",d);-1==f&&(f=a.length);var g=f-d;if(f==a.length||e+g>=b)return d+Math.min(g,b-e);if(e+=f-d,e+=c-e%c,d=f+1,e>=b)return d}}function yg(a){for(;xg.length<=a;)xg.push(zg(xg)+" ");return xg[a]}function zg(a){return a[a.length-1]}function Bg(a,b){for(var c=0;c<a.length;++c)if(a[c]==b)return c;return-1}function Cg(a,b){for(var c=[],d=0;d<a.length;d++)c[d]=b(a[d],d);return c}function Dg(a,b){var c;if(Object.create)c=Object.create(a);else{var d=function(){};d.prototype=a,c=new d}return b&&Eg(b,c),c}function Eg(a,b,c){b||(b={});for(var d in a)!a.hasOwnProperty(d)||c===!1&&b.hasOwnProperty(d)||(b[d]=a[d]);return b}function Fg(a){var b=Array.prototype.slice.call(arguments,1);return function(){return a.apply(null,b)}}function Ig(a,b){return b?b.source.indexOf("\\w")>-1&&Hg(a)?!0:b.test(a):Hg(a)}function Jg(a){for(var b in a)if(a.hasOwnProperty(b)&&a[b])return!1;return!0}function Lg(a){return a.charCodeAt(0)>=768&&Kg.test(a)}function Mg(a,b,c,d){var e=document.createElement(a);if(c&&(e.className=c),d&&(e.style.cssText=d),"string"==typeof b)e.appendChild(document.createTextNode(b));else if(b)for(var f=0;f<b.length;++f)e.appendChild(b[f]);return e}function Og(a){for(var b=a.childNodes.length;b>0;--b)a.removeChild(a.firstChild);return a}function Pg(a,b){return Og(a).appendChild(b)}function Qg(a,b){if(a.contains)return a.contains(b);for(;b=b.parentNode;)if(b==a)return!0}function Rg(){return document.activeElement}function Sg(a){return new RegExp("\\b"+a+"\\b\\s*")}function Tg(a,b){var c=Sg(b);c.test(a.className)&&(a.className=a.className.replace(c,""))}function Ug(a,b){Sg(b).test(a.className)||(a.className+=" "+b)}function Vg(a,b){for(var c=a.split(" "),d=0;d<c.length;d++)c[d]&&!Sg(c[d]).test(b)&&(b+=" "+c[d]);return b}function Wg(a){if(document.body.getElementsByClassName)for(var b=document.body.getElementsByClassName("CodeMirror"),c=0;c<b.length;c++){var d=b[c].CodeMirror;d&&a(d)}}function Yg(){Xg||(Zg(),Xg=!0)}function Zg(){var a;eg(window,"resize",function(){null==a&&(a=setTimeout(function(){a=null,_g=null,Wg(Uc)},100))}),eg(window,"blur",function(){Wg(wd)})}function ah(a){if(null!=_g)return _g;var b=Mg("div",null,null,"width: 50px; height: 50px; overflow-x: scroll");return Pg(a,b),b.offsetWidth&&(_g=b.offsetHeight-b.clientHeight),_g||0}function ch(a){if(null==bh){var b=Mg("span","\u200b");Pg(a,Mg("span",[b,document.createTextNode("x")])),0!=a.firstChild.offsetHeight&&(bh=b.offsetWidth<=1&&b.offsetHeight>2&&!c)}return bh?Mg("span","\u200b"):Mg("span","\xa0",null,"display: inline-block; width: 1px; margin-right: -1px")}function eh(a){if(null!=dh)return dh;var b=Pg(a,document.createTextNode("A\u062eA")),c=Ng(b,0,1).getBoundingClientRect();if(c.left==c.right)return!1;var d=Ng(b,1,2).getBoundingClientRect();return dh=d.right-c.right<3}function jh(a,b,c,d){if(!a)return d(b,c,"ltr");for(var e=!1,f=0;f<a.length;++f){var g=a[f];(g.from<c&&g.to>b||b==c&&g.to==b)&&(d(Math.max(g.from,b),Math.min(g.to,c),1==g.level?"rtl":"ltr"),e=!0)}e||d(b,c,"ltr")}function kh(a){return a.level%2?a.to:a.from}function lh(a){return a.level%2?a.from:a.to}function mh(a){var b=Kf(a);return b?kh(b[0]):0}function nh(a){var b=Kf(a);return b?lh(zg(b)):a.text.length}function oh(a,b){var c=Df(a.doc,b),d=Qe(c);d!=c&&(b=Hf(d));var e=Kf(d),f=e?e[0].level%2?nh(d):mh(d):0;return nb(b,f)}function ph(a,b){for(var c,d=Df(a.doc,b);c=Oe(d);)d=c.find(1,!0).line,b=null;var e=Kf(d),f=e?e[0].level%2?mh(d):nh(d):d.text.length;return nb(null==b?Hf(d):b,f)}function qh(a,b,c){var d=a[0].level;return b==d?!0:c==d?!1:c>b}function sh(a,b){rh=null;for(var d,c=0;c<a.length;++c){var e=a[c];if(e.from<b&&e.to>b)return c;if(e.from==b||e.to==b){if(null!=d)return qh(a,e.level,a[d].level)?(e.from!=e.to&&(rh=d),c):(e.from!=e.to&&(rh=c),d);d=c}}return d}function th(a,b,c,d){if(!d)return b+c;do b+=c;while(b>0&&Lg(a.text.charAt(b)));return b}function uh(a,b,c,d){var e=Kf(a);if(!e)return vh(a,b,c,d);for(var f=sh(e,b),g=e[f],h=th(a,b,g.level%2?-c:c,d);;){if(h>g.from&&h<g.to)return h;if(h==g.from||h==g.to)return sh(e,h)==f?h:(g=e[f+=c],c>0==g.level%2?g.to:g.from);if(g=e[f+=c],!g)return null;h=c>0==g.level%2?th(a,g.to,-1,d):th(a,g.from,1,d)}}function vh(a,b,c,d){var e=b+c;if(d)for(;e>0&&Lg(a.text.charAt(e));)e+=c;return 0>e||e>a.text.length?null:e}var a=/gecko\/\d/i.test(navigator.userAgent),b=/MSIE \d/.test(navigator.userAgent),c=b&&(null==document.documentMode||document.documentMode<8),d=b&&(null==document.documentMode||document.documentMode<9),e=b&&(null==document.documentMode||document.documentMode<10),f=/Trident\/([7-9]|\d{2,})\./.test(navigator.userAgent),g=b||f,h=/WebKit\//.test(navigator.userAgent),i=h&&/Qt\/\d+\.\d+/.test(navigator.userAgent),j=/Chrome\//.test(navigator.userAgent),k=/Opera\//.test(navigator.userAgent),l=/Apple Computer/.test(navigator.vendor),m=/KHTML\//.test(navigator.userAgent),n=/Mac OS X 1\d\D([8-9]|\d\d)\D/.test(navigator.userAgent),o=/PhantomJS/.test(navigator.userAgent),p=/AppleWebKit/.test(navigator.userAgent)&&/Mobile\/\w+/.test(navigator.userAgent),q=p||/Android|webOS|BlackBerry|Opera Mini|Opera Mobi|IEMobile/i.test(navigator.userAgent),r=p||/Mac/.test(navigator.platform),s=/win/i.test(navigator.platform),t=k&&navigator.userAgent.match(/Version\/(\d*\.\d*)/);t&&(t=Number(t[1])),t&&t>=15&&(k=!1,h=!0);var u=r&&(i||k&&(null==t||12.11>t)),v=a||g&&!d,w=!1,x=!1,nb=y.Pos=function(a,b){return this instanceof nb?(this.line=a,this.ch=b,void 0):new nb(a,b)},ob=y.cmpPos=function(a,b){return a.line-b.line||a.ch-b.ch};sb.prototype={primary:function(){return this.ranges[this.primIndex]},equals:function(a){if(a==this)return!0;if(a.primIndex!=this.primIndex||a.ranges.length!=this.ranges.length)return!1;for(var b=0;b<this.ranges.length;b++){var c=this.ranges[b],d=a.ranges[b];if(0!=ob(c.anchor,d.anchor)||0!=ob(c.head,d.head))return!1}return!0},deepCopy:function(){for(var a=[],b=0;b<this.ranges.length;b++)a[b]=new tb(pb(this.ranges[b].anchor),pb(this.ranges[b].head));return new sb(a,this.primIndex)},somethingSelected:function(){for(var a=0;a<this.ranges.length;a++)if(!this.ranges[a].empty())return!0;return!1},contains:function(a,b){b||(b=a);for(var c=0;c<this.ranges.length;c++){var d=this.ranges[c];if(ob(b,d.from())>=0&&ob(a,d.to())<=0)return c}return-1}},tb.prototype={from:function(){return rb(this.anchor,this.head)},to:function(){return qb(this.anchor,this.head)},empty:function(){return this.head.line==this.anchor.line&&this.head.ch==this.anchor.ch}};var tc,Yc,Zc,ec={left:0,right:0,top:0,bottom:0},wc=0,dd=0,id=0,jd=null;g?jd=-.53:a?jd=15:j?jd=-.7:l&&(jd=-1/3);var nd,qd=null,zd=y.changeEnd=function(a){return a.text?nb(a.from.line+a.text.length-1,zg(a.text).length+(1==a.text.length?a.from.ch:0)):a.to};y.prototype={constructor:y,focus:function(){window.focus(),Qc(this),Nc(this)},setOption:function(a,b){var c=this.options,d=c[a];(c[a]!=b||"mode"==a)&&(c[a]=b,$d.hasOwnProperty(a)&&Ac(this,$d[a])(this,b,d))},getOption:function(a){return this.options[a]},getDoc:function(){return this.doc},addKeyMap:function(a,b){this.state.keyMaps[b?"push":"unshift"](a)},removeKeyMap:function(a){for(var b=this.state.keyMaps,c=0;c<b.length;++c)if(b[c]==a||"string"!=typeof b[c]&&b[c].name==a)return b.splice(c,1),!0},addOverlay:Bc(function(a,b){var c=a.token?a:y.getMode(this.options,a);if(c.startState)throw new Error("Overlays may not be stateful.");this.state.overlays.push({mode:c,modeSpec:a,opaque:b&&b.opaque}),this.state.modeGen++,Fc(this)}),removeOverlay:Bc(function(a){for(var b=this.state.overlays,c=0;c<b.length;++c){var d=b[c].modeSpec;if(d==a||"string"==typeof a&&d.name==a)return b.splice(c,1),this.state.modeGen++,Fc(this),void 0}}),indentLine:Bc(function(a,b,c){"string"!=typeof b&&"number"!=typeof b&&(b=null==b?this.options.smartIndent?"smart":"prev":b?"add":"subtract"),zb(this.doc,a)&&Td(this,a,b,c)}),indentSelection:Bc(function(a){for(var b=this.doc.sel.ranges,c=-1,d=0;d<b.length;d++){var e=b[d];if(e.empty())e.head.line>c&&(Td(this,e.head.line,a,!0),c=e.head.line,d==this.doc.sel.primIndex&&Rd(this));else{var f=Math.max(c,e.from().line),g=e.to();c=Math.min(this.lastLine(),g.line-(g.ch?0:1))+1;for(var h=f;c>h;++h)Td(this,h,a)}}}),getTokenAt:function(a,b){var c=this.doc;a=xb(c,a);for(var d=Vb(this,a.line,b),e=this.doc.mode,f=Df(c,a.line),g=new oe(f.text,this.options.tabSize);g.pos<a.ch&&!g.eol();){g.start=g.pos;var h=df(e,g,d)}return{start:g.start,end:g.pos,string:g.current(),type:h||null,state:d}},getTokenTypeAt:function(a){a=xb(this.doc,a);var f,b=gf(this,Df(this.doc,a.line)),c=0,d=(b.length-1)/2,e=a.ch;if(0==e)f=b[2];else for(;;){var g=c+d>>1;if((g?b[2*g-1]:0)>=e)d=g;else{if(!(b[2*g+1]<e)){f=b[2*g+2];break}c=g+1}}var h=f?f.indexOf("cm-overlay "):-1;return 0>h?f:0==h?null:f.slice(0,h-1)},getModeAt:function(a){var b=this.doc.mode;return b.innerMode?y.innerMode(b,this.getTokenAt(a).state).mode:b},getHelper:function(a,b){return this.getHelpers(a,b)[0]},getHelpers:function(a,b){var c=[];if(!fe.hasOwnProperty(b))return fe;var d=fe[b],e=this.getModeAt(a);if("string"==typeof e[b])d[e[b]]&&c.push(d[e[b]]);else if(e[b])for(var f=0;f<e[b].length;f++){var g=d[e[b][f]];g&&c.push(g)}else e.helperType&&d[e.helperType]?c.push(d[e.helperType]):d[e.name]&&c.push(d[e.name]);for(var f=0;f<d._global.length;f++){var h=d._global[f];h.pred(e,this)&&-1==Bg(c,h.val)&&c.push(h.val)}return c},getStateAfter:function(a,b){var c=this.doc;return a=wb(c,null==a?c.first+c.size-1:a),Vb(this,a+1,b)},cursorCoords:function(a,b){var c,d=this.doc.sel.primary();return c=null==a?d.head:"object"==typeof a?xb(this.doc,a):a?d.from():d.to(),oc(this,c,b||"page")},charCoords:function(a,b){return nc(this,xb(this.doc,a),b||"page")},coordsChar:function(a,b){return a=mc(this,a,b||"page"),rc(this,a.left,a.top)},lineAtHeight:function(a,b){return a=mc(this,{top:a,left:0},b||"page").top,If(this.doc,a+this.display.viewOffset)},heightAtLine:function(a,b){var c=!1,d=this.doc.first+this.doc.size-1;a<this.doc.first?a=this.doc.first:a>d&&(a=d,c=!0);var e=Df(this.doc,a);return lc(this,e,{top:0,left:0},b||"page").top+(c?this.doc.height-Jf(e):0)},defaultTextHeight:function(){return uc(this.display)},defaultCharWidth:function(){return vc(this.display)},setGutterMarker:Bc(function(a,b,c){return Ud(this,a,"gutter",function(a){var d=a.gutterMarkers||(a.gutterMarkers={});return d[b]=c,!c&&Jg(d)&&(a.gutterMarkers=null),!0})}),clearGutter:Bc(function(a){var b=this,c=b.doc,d=c.first;c.iter(function(c){c.gutterMarkers&&c.gutterMarkers[a]&&(c.gutterMarkers[a]=null,Gc(b,d,"gutter"),Jg(c.gutterMarkers)&&(c.gutterMarkers=null)),++d})}),addLineClass:Bc(function(a,b,c){return Ud(this,a,"class",function(a){var d="text"==b?"textClass":"background"==b?"bgClass":"wrapClass";if(a[d]){if(new RegExp("(?:^|\\s)"+c+"(?:$|\\s)").test(a[d]))return!1;a[d]+=" "+c}else a[d]=c;return!0})}),removeLineClass:Bc(function(a,b,c){return Ud(this,a,"class",function(a){var d="text"==b?"textClass":"background"==b?"bgClass":"wrapClass",e=a[d];if(!e)return!1;if(null==c)a[d]=null;else{var f=e.match(new RegExp("(?:^|\\s+)"+c+"(?:$|\\s+)"));if(!f)return!1;var g=f.index+f[0].length;a[d]=e.slice(0,f.index)+(f.index&&g!=e.length?" ":"")+e.slice(g)||null}return!0})}),addLineWidget:Bc(function(a,b,c){return Ze(this,a,b,c)}),removeLineWidget:function(a){a.clear()},lineInfo:function(a){if("number"==typeof a){if(!zb(this.doc,a))return null;var b=a;if(a=Df(this.doc,a),!a)return null}else{var b=Hf(a);if(null==b)return null}return{line:b,handle:a,text:a.text,gutterMarkers:a.gutterMarkers,textClass:a.textClass,bgClass:a.bgClass,wrapClass:a.wrapClass,widgets:a.widgets}},getViewport:function(){return{from:this.display.viewFrom,to:this.display.viewTo}},addWidget:function(a,b,c,d,e){var f=this.display;a=oc(this,xb(this.doc,a));var g=a.bottom,h=a.left;if(b.style.position="absolute",f.sizer.appendChild(b),"over"==d)g=a.top;else if("above"==d||"near"==d){var i=Math.max(f.wrapper.clientHeight,this.doc.height),j=Math.max(f.sizer.clientWidth,f.lineSpace.clientWidth);("above"==d||a.bottom+b.offsetHeight>i)&&a.top>b.offsetHeight?g=a.top-b.offsetHeight:a.bottom+b.offsetHeight<=i&&(g=a.bottom),h+b.offsetWidth>j&&(h=j-b.offsetWidth)}b.style.top=g+"px",b.style.left=b.style.right="","right"==e?(h=f.sizer.clientWidth-b.offsetWidth,b.style.right="0px"):("left"==e?h=0:"middle"==e&&(h=(f.sizer.clientWidth-b.offsetWidth)/2),b.style.left=h+"px"),c&&Od(this,h,g,h+b.offsetWidth,g+b.offsetHeight)},triggerOnKeyDown:Bc(rd),triggerOnKeyPress:Bc(ud),triggerOnKeyUp:Bc(td),execCommand:function(a){return ie.hasOwnProperty(a)?ie[a](this):void 0},findPosH:function(a,b,c,d){var e=1;0>b&&(e=-1,b=-b);for(var f=0,g=xb(this.doc,a);b>f&&(g=Wd(this.doc,g,e,c,d),!g.hitSide);++f);return g},moveH:Bc(function(a,b){var c=this;c.extendSelectionsBy(function(d){return c.display.shift||c.doc.extend||d.empty()?Wd(c.doc,d.head,a,b,c.options.rtlMoveVisually):0>a?d.from():d.to()},tg)}),deleteH:Bc(function(a,b){var c=this.doc.sel,d=this.doc;c.somethingSelected()?d.replaceSelection("",null,"+delete"):Vd(this,function(c){var e=Wd(d,c.head,a,b,!1);return 0>a?{from:e,to:c.head}:{from:c.head,to:e}})}),findPosV:function(a,b,c,d){var e=1,f=d;0>b&&(e=-1,b=-b);for(var g=0,h=xb(this.doc,a);b>g;++g){var i=oc(this,h,"div");if(null==f?f=i.left:i.left=f,h=Xd(this,i,e,c),h.hitSide)break}return h},moveV:Bc(function(a,b){var c=this,d=this.doc,e=[],f=!c.display.shift&&!d.extend&&d.sel.somethingSelected();if(d.extendSelectionsBy(function(g){if(f)return 0>a?g.from():g.to();var h=oc(c,g.head,"div");null!=g.goalColumn&&(h.left=g.goalColumn),e.push(h.left);var i=Xd(c,h,a,b);return"page"==b&&g==d.sel.primary()&&Qd(c,null,nc(c,i,"div").top-h.top),i},tg),e.length)for(var g=0;g<d.sel.ranges.length;g++)d.sel.ranges[g].goalColumn=e[g]}),toggleOverwrite:function(a){(null==a||a!=this.state.overwrite)&&((this.state.overwrite=!this.state.overwrite)?Ug(this.display.cursorDiv,"CodeMirror-overwrite"):Tg(this.display.cursorDiv,"CodeMirror-overwrite"),gg(this,"overwriteToggle",this,this.state.overwrite))},hasFocus:function(){return Rg()==this.display.input},scrollTo:Bc(function(a,b){(null!=a||null!=b)&&Sd(this),null!=a&&(this.curOp.scrollLeft=a),null!=b&&(this.curOp.scrollTop=b)}),getScrollInfo:function(){var a=this.display.scroller,b=pg;return{left:a.scrollLeft,top:a.scrollTop,height:a.scrollHeight-b,width:a.scrollWidth-b,clientHeight:a.clientHeight-b,clientWidth:a.clientWidth-b}},scrollIntoView:Bc(function(a,b){if(null==a?(a={from:this.doc.sel.primary().head,to:null},null==b&&(b=this.options.cursorScrollMargin)):"number"==typeof a?a={from:nb(a,0),to:null}:null==a.from&&(a={from:a,to:null}),a.to||(a.to=a.from),a.margin=b||0,null!=a.from.line)Sd(this),this.curOp.scrollToPos=a;else{var c=Pd(this,Math.min(a.from.left,a.to.left),Math.min(a.from.top,a.to.top)-a.margin,Math.max(a.from.right,a.to.right),Math.max(a.from.bottom,a.to.bottom)+a.margin);this.scrollTo(c.scrollLeft,c.scrollTop)}}),setSize:Bc(function(a,b){function c(a){return"number"==typeof a||/^\d+$/.test(String(a))?a+"px":a}null!=a&&(this.display.wrapper.style.width=c(a)),null!=b&&(this.display.wrapper.style.height=c(b)),this.options.lineWrapping&&hc(this),this.curOp.forceUpdate=!0,gg(this,"refresh",this)}),operation:function(a){return zc(this,a)},refresh:Bc(function(){var a=this.display.cachedTextHeight;Fc(this),this.curOp.forceUpdate=!0,ic(this),this.scrollTo(this.doc.scrollLeft,this.doc.scrollTop),J(this),(null==a||Math.abs(a-uc(this.display))>.5)&&E(this),gg(this,"refresh",this)}),swapDoc:Bc(function(a){var b=this.doc;
return b.cm=null,Cf(this,a),ic(this),Pc(this),this.scrollTo(a.scrollLeft,a.scrollTop),jg(this,"swapDoc",this,b),b}),getInputField:function(){return this.display.input},getWrapperElement:function(){return this.display.wrapper},getScrollerElement:function(){return this.display.scroller},getGutterElement:function(){return this.display.gutters}},og(y);var Zd=y.defaults={},$d=y.optionHandlers={},ae=y.Init={toString:function(){return"CodeMirror.Init"}};_d("value","",function(a,b){a.setValue(b)},!0),_d("mode",null,function(a,b){a.doc.modeOption=b,A(a)},!0),_d("indentUnit",2,A,!0),_d("indentWithTabs",!1),_d("smartIndent",!0),_d("tabSize",4,function(a){B(a),ic(a),Fc(a)},!0),_d("specialChars",/[\t\u0000-\u0019\u00ad\u200b\u2028\u2029\ufeff]/g,function(a,b){a.options.specialChars=new RegExp(b.source+(b.test("	")?"":"|	"),"g"),a.refresh()},!0),_d("specialCharPlaceholder",nf,function(a){a.refresh()},!0),_d("electricChars",!0),_d("rtlMoveVisually",!s),_d("wholeLineUpdateBefore",!0),_d("theme","default",function(a){G(a),H(a)},!0),_d("keyMap","default",F),_d("extraKeys",null),_d("lineWrapping",!1,C,!0),_d("gutters",[],function(a){M(a.options),H(a)},!0),_d("fixedGutter",!0,function(a,b){a.display.gutters.style.left=b?T(a.display)+"px":"0",a.refresh()},!0),_d("coverGutterNextToScrollbar",!1,O,!0),_d("lineNumbers",!1,function(a){M(a.options),H(a)},!0),_d("firstLineNumber",1,H,!0),_d("lineNumberFormatter",function(a){return a},H,!0),_d("showCursorWhenSelecting",!1,Ob,!0),_d("resetSelectionOnContextMenu",!0),_d("readOnly",!1,function(a,b){"nocursor"==b?(wd(a),a.display.input.blur(),a.display.disabled=!0):(a.display.disabled=!1,b||Pc(a))}),_d("disableInput",!1,function(a,b){b||Pc(a)},!0),_d("dragDrop",!0),_d("cursorBlinkRate",530),_d("cursorScrollMargin",0),_d("cursorHeight",1),_d("workTime",100),_d("workDelay",100),_d("flattenSpans",!0,B,!0),_d("addModeClass",!1,B,!0),_d("pollInterval",100),_d("undoDepth",200,function(a,b){a.doc.history.undoDepth=b}),_d("historyEventDelay",1250),_d("viewportMargin",10,function(a){a.refresh()},!0),_d("maxHighlightLength",1e4,B,!0),_d("moveInputWithCursor",!0,function(a,b){b||(a.display.inputDiv.style.top=a.display.inputDiv.style.left=0)}),_d("tabindex",null,function(a,b){a.display.input.tabIndex=b||""}),_d("autofocus",null);var be=y.modes={},ce=y.mimeModes={};y.defineMode=function(a,b){if(y.defaults.mode||"null"==a||(y.defaults.mode=a),arguments.length>2){b.dependencies=[];for(var c=2;c<arguments.length;++c)b.dependencies.push(arguments[c])}be[a]=b},y.defineMIME=function(a,b){ce[a]=b},y.resolveMode=function(a){if("string"==typeof a&&ce.hasOwnProperty(a))a=ce[a];else if(a&&"string"==typeof a.name&&ce.hasOwnProperty(a.name)){var b=ce[a.name];"string"==typeof b&&(b={name:b}),a=Dg(b,a),a.name=b.name}else if("string"==typeof a&&/^[\w\-]+\/[\w\-]+\+xml$/.test(a))return y.resolveMode("application/xml");return"string"==typeof a?{name:a}:a||{name:"null"}},y.getMode=function(a,b){var b=y.resolveMode(b),c=be[b.name];if(!c)return y.getMode(a,"text/plain");var d=c(a,b);if(de.hasOwnProperty(b.name)){var e=de[b.name];for(var f in e)e.hasOwnProperty(f)&&(d.hasOwnProperty(f)&&(d["_"+f]=d[f]),d[f]=e[f])}if(d.name=b.name,b.helperType&&(d.helperType=b.helperType),b.modeProps)for(var f in b.modeProps)d[f]=b.modeProps[f];return d},y.defineMode("null",function(){return{token:function(a){a.skipToEnd()}}}),y.defineMIME("text/plain","null");var de=y.modeExtensions={};y.extendMode=function(a,b){var c=de.hasOwnProperty(a)?de[a]:de[a]={};Eg(b,c)},y.defineExtension=function(a,b){y.prototype[a]=b},y.defineDocExtension=function(a,b){yf.prototype[a]=b},y.defineOption=_d;var ee=[];y.defineInitHook=function(a){ee.push(a)};var fe=y.helpers={};y.registerHelper=function(a,b,c){fe.hasOwnProperty(a)||(fe[a]=y[a]={_global:[]}),fe[a][b]=c},y.registerGlobalHelper=function(a,b,c,d){y.registerHelper(a,b,d),fe[a]._global.push({pred:c,val:d})};var ge=y.copyState=function(a,b){if(b===!0)return b;if(a.copyState)return a.copyState(b);var c={};for(var d in b){var e=b[d];e instanceof Array&&(e=e.concat([])),c[d]=e}return c},he=y.startState=function(a,b,c){return a.startState?a.startState(b,c):!0};y.innerMode=function(a,b){for(;a.innerMode;){var c=a.innerMode(b);if(!c||c.mode==a)break;b=c.state,a=c.mode}return c||{mode:a,state:b}};var ie=y.commands={selectAll:function(a){a.setSelection(nb(a.firstLine(),0),nb(a.lastLine()),rg)},singleSelection:function(a){a.setSelection(a.getCursor("anchor"),a.getCursor("head"),rg)},killLine:function(a){Vd(a,function(b){if(b.empty()){var c=Df(a.doc,b.head.line).text.length;return b.head.ch==c&&b.head.line<a.lastLine()?{from:b.head,to:nb(b.head.line+1,0)}:{from:b.head,to:nb(b.head.line,c)}}return{from:b.from(),to:b.to()}})},deleteLine:function(a){Vd(a,function(b){return{from:nb(b.from().line,0),to:xb(a.doc,nb(b.to().line+1,0))}})},delLineLeft:function(a){Vd(a,function(a){return{from:nb(a.from().line,0),to:a.from()}})},undo:function(a){a.undo()},redo:function(a){a.redo()},undoSelection:function(a){a.undoSelection()},redoSelection:function(a){a.redoSelection()},goDocStart:function(a){a.extendSelection(nb(a.firstLine(),0))},goDocEnd:function(a){a.extendSelection(nb(a.lastLine()))},goLineStart:function(a){a.extendSelectionsBy(function(b){return oh(a,b.head.line)},tg)},goLineStartSmart:function(a){a.extendSelectionsBy(function(b){var c=oh(a,b.head.line),d=a.getLineHandle(c.line),e=Kf(d);if(!e||0==e[0].level){var f=Math.max(0,d.text.search(/\S/)),g=b.head.line==c.line&&b.head.ch<=f&&b.head.ch;return nb(c.line,g?0:f)}return c},tg)},goLineEnd:function(a){a.extendSelectionsBy(function(b){return ph(a,b.head.line)},tg)},goLineRight:function(a){a.extendSelectionsBy(function(b){var c=a.charCoords(b.head,"div").top+5;return a.coordsChar({left:a.display.lineDiv.offsetWidth+100,top:c},"div")},tg)},goLineLeft:function(a){a.extendSelectionsBy(function(b){var c=a.charCoords(b.head,"div").top+5;return a.coordsChar({left:0,top:c},"div")},tg)},goLineUp:function(a){a.moveV(-1,"line")},goLineDown:function(a){a.moveV(1,"line")},goPageUp:function(a){a.moveV(-1,"page")},goPageDown:function(a){a.moveV(1,"page")},goCharLeft:function(a){a.moveH(-1,"char")},goCharRight:function(a){a.moveH(1,"char")},goColumnLeft:function(a){a.moveH(-1,"column")},goColumnRight:function(a){a.moveH(1,"column")},goWordLeft:function(a){a.moveH(-1,"word")},goGroupRight:function(a){a.moveH(1,"group")},goGroupLeft:function(a){a.moveH(-1,"group")},goWordRight:function(a){a.moveH(1,"word")},delCharBefore:function(a){a.deleteH(-1,"char")},delCharAfter:function(a){a.deleteH(1,"char")},delWordBefore:function(a){a.deleteH(-1,"word")},delWordAfter:function(a){a.deleteH(1,"word")},delGroupBefore:function(a){a.deleteH(-1,"group")},delGroupAfter:function(a){a.deleteH(1,"group")},indentAuto:function(a){a.indentSelection("smart")},indentMore:function(a){a.indentSelection("add")},indentLess:function(a){a.indentSelection("subtract")},insertTab:function(a){a.replaceSelection("	")},insertSoftTab:function(a){for(var b=[],c=a.listSelections(),d=a.options.tabSize,e=0;e<c.length;e++){var f=c[e].from(),g=vg(a.getLine(f.line),f.ch,d);b.push(new Array(d-g%d+1).join(" "))}a.replaceSelections(b)},defaultTab:function(a){a.somethingSelected()?a.indentSelection("add"):a.execCommand("insertTab")},transposeChars:function(a){zc(a,function(){for(var b=a.listSelections(),c=[],d=0;d<b.length;d++){var e=b[d].head,f=Df(a.doc,e.line).text;if(f)if(e.ch==f.length&&(e=new nb(e.line,e.ch-1)),e.ch>0)e=new nb(e.line,e.ch+1),a.replaceRange(f.charAt(e.ch-1)+f.charAt(e.ch-2),nb(e.line,e.ch-2),e,"+transpose");else if(e.line>a.doc.first){var g=Df(a.doc,e.line-1).text;g&&a.replaceRange(f.charAt(0)+"\n"+g.charAt(g.length-1),nb(e.line-1,g.length-1),nb(e.line,1),"+transpose")}c.push(new tb(e,e))}a.setSelections(c)})},newlineAndIndent:function(a){zc(a,function(){for(var b=a.listSelections().length,c=0;b>c;c++){var d=a.listSelections()[c];a.replaceRange("\n",d.anchor,d.head,"+input"),a.indentLine(d.from().line+1,null,!0),Rd(a)}})},toggleOverwrite:function(a){a.toggleOverwrite()}},je=y.keyMap={};je.basic={Left:"goCharLeft",Right:"goCharRight",Up:"goLineUp",Down:"goLineDown",End:"goLineEnd",Home:"goLineStartSmart",PageUp:"goPageUp",PageDown:"goPageDown",Delete:"delCharAfter",Backspace:"delCharBefore","Shift-Backspace":"delCharBefore",Tab:"defaultTab","Shift-Tab":"indentAuto",Enter:"newlineAndIndent",Insert:"toggleOverwrite",Esc:"singleSelection"},je.pcDefault={"Ctrl-A":"selectAll","Ctrl-D":"deleteLine","Ctrl-Z":"undo","Shift-Ctrl-Z":"redo","Ctrl-Y":"redo","Ctrl-Home":"goDocStart","Ctrl-Up":"goDocStart","Ctrl-End":"goDocEnd","Ctrl-Down":"goDocEnd","Ctrl-Left":"goGroupLeft","Ctrl-Right":"goGroupRight","Alt-Left":"goLineStart","Alt-Right":"goLineEnd","Ctrl-Backspace":"delGroupBefore","Ctrl-Delete":"delGroupAfter","Ctrl-S":"save","Ctrl-F":"find","Ctrl-G":"findNext","Shift-Ctrl-G":"findPrev","Shift-Ctrl-F":"replace","Shift-Ctrl-R":"replaceAll","Ctrl-[":"indentLess","Ctrl-]":"indentMore","Ctrl-U":"undoSelection","Shift-Ctrl-U":"redoSelection","Alt-U":"redoSelection",fallthrough:"basic"},je.macDefault={"Cmd-A":"selectAll","Cmd-D":"deleteLine","Cmd-Z":"undo","Shift-Cmd-Z":"redo","Cmd-Y":"redo","Cmd-Up":"goDocStart","Cmd-End":"goDocEnd","Cmd-Down":"goDocEnd","Alt-Left":"goGroupLeft","Alt-Right":"goGroupRight","Cmd-Left":"goLineStart","Cmd-Right":"goLineEnd","Alt-Backspace":"delGroupBefore","Ctrl-Alt-Backspace":"delGroupAfter","Alt-Delete":"delGroupAfter","Cmd-S":"save","Cmd-F":"find","Cmd-G":"findNext","Shift-Cmd-G":"findPrev","Cmd-Alt-F":"replace","Shift-Cmd-Alt-F":"replaceAll","Cmd-[":"indentLess","Cmd-]":"indentMore","Cmd-Backspace":"delLineLeft","Cmd-U":"undoSelection","Shift-Cmd-U":"redoSelection",fallthrough:["basic","emacsy"]},je.emacsy={"Ctrl-F":"goCharRight","Ctrl-B":"goCharLeft","Ctrl-P":"goLineUp","Ctrl-N":"goLineDown","Alt-F":"goWordRight","Alt-B":"goWordLeft","Ctrl-A":"goLineStart","Ctrl-E":"goLineEnd","Ctrl-V":"goPageDown","Shift-Ctrl-V":"goPageUp","Ctrl-D":"delCharAfter","Ctrl-H":"delCharBefore","Alt-D":"delWordAfter","Alt-Backspace":"delWordBefore","Ctrl-K":"killLine","Ctrl-T":"transposeChars"},je["default"]=r?je.macDefault:je.pcDefault;var le=y.lookupKey=function(a,b,c){function d(b){b=ke(b);var e=b[a];if(e===!1)return"stop";if(null!=e&&c(e))return!0;if(b.nofallthrough)return"stop";var f=b.fallthrough;if(null==f)return!1;if("[object Array]"!=Object.prototype.toString.call(f))return d(f);for(var g=0;g<f.length;++g){var h=d(f[g]);if(h)return h}return!1}for(var e=0;e<b.length;++e){var f=d(b[e]);if(f)return"stop"!=f}},me=y.isModifierKey=function(a){var b=ih[a.keyCode];return"Ctrl"==b||"Alt"==b||"Shift"==b||"Mod"==b},ne=y.keyName=function(a,b){if(k&&34==a.keyCode&&a["char"])return!1;var c=ih[a.keyCode];return null==c||a.altGraphKey?!1:(a.altKey&&(c="Alt-"+c),(u?a.metaKey:a.ctrlKey)&&(c="Ctrl-"+c),(u?a.ctrlKey:a.metaKey)&&(c="Cmd-"+c),!b&&a.shiftKey&&(c="Shift-"+c),c)};y.fromTextArea=function(a,b){function d(){a.value=i.getValue()}if(b||(b={}),b.value=a.value,!b.tabindex&&a.tabindex&&(b.tabindex=a.tabindex),!b.placeholder&&a.placeholder&&(b.placeholder=a.placeholder),null==b.autofocus){var c=Rg();b.autofocus=c==a||null!=a.getAttribute("autofocus")&&c==document.body}if(a.form&&(eg(a.form,"submit",d),!b.leaveSubmitMethodAlone)){var e=a.form,f=e.submit;try{var g=e.submit=function(){d(),e.submit=f,e.submit(),e.submit=g}}catch(h){}}a.style.display="none";var i=y(function(b){a.parentNode.insertBefore(b,a.nextSibling)},b);return i.save=d,i.getTextArea=function(){return a},i.toTextArea=function(){d(),a.parentNode.removeChild(i.getWrapperElement()),a.style.display="",a.form&&(fg(a.form,"submit",d),"function"==typeof a.form.submit&&(a.form.submit=f))},i};var oe=y.StringStream=function(a,b){this.pos=this.start=0,this.string=a,this.tabSize=b||8,this.lastColumnPos=this.lastColumnValue=0,this.lineStart=0};oe.prototype={eol:function(){return this.pos>=this.string.length},sol:function(){return this.pos==this.lineStart},peek:function(){return this.string.charAt(this.pos)||void 0},next:function(){return this.pos<this.string.length?this.string.charAt(this.pos++):void 0},eat:function(a){var b=this.string.charAt(this.pos);if("string"==typeof a)var c=b==a;else var c=b&&(a.test?a.test(b):a(b));return c?(++this.pos,b):void 0},eatWhile:function(a){for(var b=this.pos;this.eat(a););return this.pos>b},eatSpace:function(){for(var a=this.pos;/[\s\u00a0]/.test(this.string.charAt(this.pos));)++this.pos;return this.pos>a},skipToEnd:function(){this.pos=this.string.length},skipTo:function(a){var b=this.string.indexOf(a,this.pos);return b>-1?(this.pos=b,!0):void 0},backUp:function(a){this.pos-=a},column:function(){return this.lastColumnPos<this.start&&(this.lastColumnValue=vg(this.string,this.start,this.tabSize,this.lastColumnPos,this.lastColumnValue),this.lastColumnPos=this.start),this.lastColumnValue-(this.lineStart?vg(this.string,this.lineStart,this.tabSize):0)},indentation:function(){return vg(this.string,null,this.tabSize)-(this.lineStart?vg(this.string,this.lineStart,this.tabSize):0)},match:function(a,b,c){if("string"!=typeof a){var f=this.string.slice(this.pos).match(a);return f&&f.index>0?null:(f&&b!==!1&&(this.pos+=f[0].length),f)}var d=function(a){return c?a.toLowerCase():a},e=this.string.substr(this.pos,a.length);return d(e)==d(a)?(b!==!1&&(this.pos+=a.length),!0):void 0},current:function(){return this.string.slice(this.start,this.pos)},hideFirstChars:function(a,b){this.lineStart+=a;try{return b()}finally{this.lineStart-=a}}};var pe=y.TextMarker=function(a,b){this.lines=[],this.type=b,this.doc=a};og(pe),pe.prototype.clear=function(){if(!this.explicitlyCleared){var a=this.doc.cm,b=a&&!a.curOp;if(b&&xc(a),ng(this,"clear")){var c=this.find();c&&jg(this,"clear",c.from,c.to)}for(var d=null,e=null,f=0;f<this.lines.length;++f){var g=this.lines[f],h=ye(g.markedSpans,this);a&&!this.collapsed?Gc(a,Hf(g),"text"):a&&(null!=h.to&&(e=Hf(g)),null!=h.from&&(d=Hf(g))),g.markedSpans=ze(g.markedSpans,h),null==h.from&&this.collapsed&&!Ue(this.doc,g)&&a&&Gf(g,uc(a.display))}if(a&&this.collapsed&&!a.options.lineWrapping)for(var f=0;f<this.lines.length;++f){var i=Qe(this.lines[f]),j=K(i);j>a.display.maxLineLength&&(a.display.maxLine=i,a.display.maxLineLength=j,a.display.maxLineChanged=!0)}null!=d&&a&&this.collapsed&&Fc(a,d,e+1),this.lines.length=0,this.explicitlyCleared=!0,this.atomic&&this.doc.cantEdit&&(this.doc.cantEdit=!1,a&&Lb(a.doc)),a&&jg(a,"markerCleared",a,this),b&&yc(a),this.parent&&this.parent.clear()}},pe.prototype.find=function(a,b){null==a&&"bookmark"==this.type&&(a=1);for(var c,d,e=0;e<this.lines.length;++e){var f=this.lines[e],g=ye(f.markedSpans,this);if(null!=g.from&&(c=nb(b?f:Hf(f),g.from),-1==a))return c;if(null!=g.to&&(d=nb(b?f:Hf(f),g.to),1==a))return d}return c&&{from:c,to:d}},pe.prototype.changed=function(){var a=this.find(-1,!0),b=this,c=this.doc.cm;a&&c&&zc(c,function(){var d=a.line,e=Hf(a.line),f=bc(c,e);if(f&&(gc(f),c.curOp.selectionChanged=c.curOp.forceUpdate=!0),c.curOp.updateMaxLine=!0,!Ue(b.doc,d)&&null!=b.height){var g=b.height;b.height=null;var h=Ye(b)-g;h&&Gf(d,d.height+h)}})},pe.prototype.attachLine=function(a){if(!this.lines.length&&this.doc.cm){var b=this.doc.cm.curOp;b.maybeHiddenMarkers&&-1!=Bg(b.maybeHiddenMarkers,this)||(b.maybeUnhiddenMarkers||(b.maybeUnhiddenMarkers=[])).push(this)}this.lines.push(a)},pe.prototype.detachLine=function(a){if(this.lines.splice(Bg(this.lines,a),1),!this.lines.length&&this.doc.cm){var b=this.doc.cm.curOp;(b.maybeHiddenMarkers||(b.maybeHiddenMarkers=[])).push(this)}};var qe=0,se=y.SharedTextMarker=function(a,b){this.markers=a,this.primary=b;for(var c=0;c<a.length;++c)a[c].parent=this};og(se),se.prototype.clear=function(){if(!this.explicitlyCleared){this.explicitlyCleared=!0;for(var a=0;a<this.markers.length;++a)this.markers[a].clear();jg(this,"clear")}},se.prototype.find=function(a,b){return this.primary.find(a,b)};var We=y.LineWidget=function(a,b,c){if(c)for(var d in c)c.hasOwnProperty(d)&&(this[d]=c[d]);this.cm=a,this.node=b};og(We),We.prototype.clear=function(){var a=this.cm,b=this.line.widgets,c=this.line,d=Hf(c);if(null!=d&&b){for(var e=0;e<b.length;++e)b[e]==this&&b.splice(e--,1);b.length||(c.widgets=null);var f=Ye(this);zc(a,function(){Xe(a,c,-f),Gc(a,d,"widget"),Gf(c,Math.max(0,c.height-f))})}},We.prototype.changed=function(){var a=this.height,b=this.cm,c=this.line;this.height=null;var d=Ye(this)-a;d&&zc(b,function(){b.curOp.forceUpdate=!0,Xe(b,c,d),Gf(c,c.height+d)})};var $e=y.Line=function(a,b,c){this.text=a,Ie(this,b),this.height=c?c(this):1};og($e),$e.prototype.lineNo=function(){return Hf(this)};var jf={},kf={};vf.prototype={chunkSize:function(){return this.lines.length},removeInner:function(a,b){for(var c=a,d=a+b;d>c;++c){var e=this.lines[c];this.height-=e.height,af(e),jg(e,"delete")}this.lines.splice(a,b)},collapse:function(a){a.push.apply(a,this.lines)},insertInner:function(a,b,c){this.height+=c,this.lines=this.lines.slice(0,a).concat(b).concat(this.lines.slice(a));for(var d=0;d<b.length;++d)b[d].parent=this},iterN:function(a,b,c){for(var d=a+b;d>a;++a)if(c(this.lines[a]))return!0}},wf.prototype={chunkSize:function(){return this.size},removeInner:function(a,b){this.size-=b;for(var c=0;c<this.children.length;++c){var d=this.children[c],e=d.chunkSize();if(e>a){var f=Math.min(b,e-a),g=d.height;if(d.removeInner(a,f),this.height-=g-d.height,e==f&&(this.children.splice(c--,1),d.parent=null),0==(b-=f))break;a=0}else a-=e}if(this.size-b<25&&(this.children.length>1||!(this.children[0]instanceof vf))){var h=[];this.collapse(h),this.children=[new vf(h)],this.children[0].parent=this}},collapse:function(a){for(var b=0;b<this.children.length;++b)this.children[b].collapse(a)},insertInner:function(a,b,c){this.size+=b.length,this.height+=c;for(var d=0;d<this.children.length;++d){var e=this.children[d],f=e.chunkSize();if(f>=a){if(e.insertInner(a,b,c),e.lines&&e.lines.length>50){for(;e.lines.length>50;){var g=e.lines.splice(e.lines.length-25,25),h=new vf(g);e.height-=h.height,this.children.splice(d+1,0,h),h.parent=this}this.maybeSpill()}break}a-=f}},maybeSpill:function(){if(!(this.children.length<=10)){var a=this;do{var b=a.children.splice(a.children.length-5,5),c=new wf(b);if(a.parent){a.size-=c.size,a.height-=c.height;var e=Bg(a.parent.children,a);a.parent.children.splice(e+1,0,c)}else{var d=new wf(a.children);d.parent=a,a.children=[d,c],a=d}c.parent=a.parent}while(a.children.length>10);a.parent.maybeSpill()}},iterN:function(a,b,c){for(var d=0;d<this.children.length;++d){var e=this.children[d],f=e.chunkSize();if(f>a){var g=Math.min(b,f-a);if(e.iterN(a,g,c))return!0;if(0==(b-=g))break;a=0}else a-=f}}};var xf=0,yf=y.Doc=function(a,b,c){if(!(this instanceof yf))return new yf(a,b,c);null==c&&(c=0),wf.call(this,[new vf([new $e("",null)])]),this.first=c,this.scrollTop=this.scrollLeft=0,this.cantEdit=!1,this.cleanGeneration=1,this.frontier=c;var d=nb(c,0);this.sel=vb(d),this.history=new Lf(null),this.id=++xf,this.modeOption=b,"string"==typeof a&&(a=fh(a)),uf(this,{from:d,to:d,text:a}),Ib(this,vb(d),rg)};yf.prototype=Dg(wf.prototype,{constructor:yf,iter:function(a,b,c){c?this.iterN(a-this.first,b-a,c):this.iterN(this.first,this.first+this.size,a)},insert:function(a,b){for(var c=0,d=0;d<b.length;++d)c+=b[d].height;this.insertInner(a-this.first,b,c)},remove:function(a,b){this.removeInner(a-this.first,b)},getValue:function(a){var b=Ff(this,this.first,this.first+this.size);return a===!1?b:b.join(a||"\n")},setValue:Cc(function(a){var b=nb(this.first,0),c=this.first+this.size-1;Fd(this,{from:b,to:nb(c,Df(this,c).text.length),text:fh(a),origin:"setValue"},!0),Ib(this,vb(b))}),replaceRange:function(a,b,c,d){b=xb(this,b),c=c?xb(this,c):b,Ld(this,a,b,c,d)},getRange:function(a,b,c){var d=Ef(this,xb(this,a),xb(this,b));return c===!1?d:d.join(c||"\n")},getLine:function(a){var b=this.getLineHandle(a);return b&&b.text},getLineHandle:function(a){return zb(this,a)?Df(this,a):void 0},getLineNumber:function(a){return Hf(a)},getLineHandleVisualStart:function(a){return"number"==typeof a&&(a=Df(this,a)),Qe(a)},lineCount:function(){return this.size},firstLine:function(){return this.first},lastLine:function(){return this.first+this.size-1},clipPos:function(a){return xb(this,a)},getCursor:function(a){var c,b=this.sel.primary();return c=null==a||"head"==a?b.head:"anchor"==a?b.anchor:"end"==a||"to"==a||a===!1?b.to():b.from()},listSelections:function(){return this.sel.ranges},somethingSelected:function(){return this.sel.somethingSelected()},setCursor:Cc(function(a,b,c){Fb(this,xb(this,"number"==typeof a?nb(a,b||0):a),null,c)}),setSelection:Cc(function(a,b,c){Fb(this,xb(this,a),xb(this,b||a),c)}),extendSelection:Cc(function(a,b,c){Cb(this,xb(this,a),b&&xb(this,b),c)}),extendSelections:Cc(function(a,b){Db(this,Ab(this,a,b))}),extendSelectionsBy:Cc(function(a,b){Db(this,Cg(this.sel.ranges,a),b)}),setSelections:Cc(function(a,b,c){if(a.length){for(var d=0,e=[];d<a.length;d++)e[d]=new tb(xb(this,a[d].anchor),xb(this,a[d].head));null==b&&(b=Math.min(a.length-1,this.sel.primIndex)),Ib(this,ub(e,b),c)}}),addSelection:Cc(function(a,b,c){var d=this.sel.ranges.slice(0);d.push(new tb(xb(this,a),xb(this,b||a))),Ib(this,ub(d,d.length-1),c)}),getSelection:function(a){for(var c,b=this.sel.ranges,d=0;d<b.length;d++){var e=Ef(this,b[d].from(),b[d].to());c=c?c.concat(e):e}return a===!1?c:c.join(a||"\n")},getSelections:function(a){for(var b=[],c=this.sel.ranges,d=0;d<c.length;d++){var e=Ef(this,c[d].from(),c[d].to());a!==!1&&(e=e.join(a||"\n")),b[d]=e}return b},replaceSelection:function(a,b,c){for(var d=[],e=0;e<this.sel.ranges.length;e++)d[e]=a;this.replaceSelections(d,b,c||"+input")},replaceSelections:Cc(function(a,b,c){for(var d=[],e=this.sel,f=0;f<e.ranges.length;f++){var g=e.ranges[f];d[f]={from:g.from(),to:g.to(),text:fh(a[f]),origin:c}}for(var h=b&&"end"!=b&&Dd(this,d,b),f=d.length-1;f>=0;f--)Fd(this,d[f]);h?Hb(this,h):this.cm&&Rd(this.cm)}),undo:Cc(function(){Hd(this,"undo")}),redo:Cc(function(){Hd(this,"redo")}),undoSelection:Cc(function(){Hd(this,"undo",!0)}),redoSelection:Cc(function(){Hd(this,"redo",!0)}),setExtending:function(a){this.extend=a},getExtending:function(){return this.extend},historySize:function(){for(var a=this.history,b=0,c=0,d=0;d<a.done.length;d++)a.done[d].ranges||++b;for(var d=0;d<a.undone.length;d++)a.undone[d].ranges||++c;return{undo:b,redo:c}},clearHistory:function(){this.history=new Lf(this.history.maxGeneration)},markClean:function(){this.cleanGeneration=this.changeGeneration(!0)},changeGeneration:function(a){return a&&(this.history.lastOp=this.history.lastOrigin=null),this.history.generation},isClean:function(a){return this.history.generation==(a||this.cleanGeneration)},getHistory:function(){return{done:Wf(this.history.done),undone:Wf(this.history.undone)}},setHistory:function(a){var b=this.history=new Lf(this.history.maxGeneration);b.done=Wf(a.done.slice(0),null,!0),b.undone=Wf(a.undone.slice(0),null,!0)},markText:function(a,b,c){return re(this,xb(this,a),xb(this,b),c,"range")},setBookmark:function(a,b){var c={replacedWith:b&&(null==b.nodeType?b.widget:b),insertLeft:b&&b.insertLeft,clearWhenEmpty:!1,shared:b&&b.shared};return a=xb(this,a),re(this,a,a,c,"bookmark")},findMarksAt:function(a){a=xb(this,a);var b=[],c=Df(this,a.line).markedSpans;if(c)for(var d=0;d<c.length;++d){var e=c[d];(null==e.from||e.from<=a.ch)&&(null==e.to||e.to>=a.ch)&&b.push(e.marker.parent||e.marker)}return b},findMarks:function(a,b,c){a=xb(this,a),b=xb(this,b);var d=[],e=a.line;return this.iter(a.line,b.line+1,function(f){var g=f.markedSpans;if(g)for(var h=0;h<g.length;h++){var i=g[h];e==a.line&&a.ch>i.to||null==i.from&&e!=a.line||e==b.line&&i.from>b.ch||c&&!c(i.marker)||d.push(i.marker.parent||i.marker)}++e}),d},getAllMarks:function(){var a=[];return this.iter(function(b){var c=b.markedSpans;if(c)for(var d=0;d<c.length;++d)null!=c[d].from&&a.push(c[d].marker)}),a},posFromIndex:function(a){var b,c=this.first;return this.iter(function(d){var e=d.text.length+1;return e>a?(b=a,!0):(a-=e,++c,void 0)}),xb(this,nb(c,b))},indexFromPos:function(a){a=xb(this,a);var b=a.ch;return a.line<this.first||a.ch<0?0:(this.iter(this.first,a.line,function(a){b+=a.text.length+1}),b)},copy:function(a){var b=new yf(Ff(this,this.first,this.first+this.size),this.modeOption,this.first);return b.scrollTop=this.scrollTop,b.scrollLeft=this.scrollLeft,b.sel=this.sel,b.extend=!1,a&&(b.history.undoDepth=this.history.undoDepth,b.setHistory(this.getHistory())),b},linkedDoc:function(a){a||(a={});var b=this.first,c=this.first+this.size;null!=a.from&&a.from>b&&(b=a.from),null!=a.to&&a.to<c&&(c=a.to);var d=new yf(Ff(this,b,c),a.mode||this.modeOption,b);return a.sharedHist&&(d.history=this.history),(this.linked||(this.linked=[])).push({doc:d,sharedHist:a.sharedHist}),d.linked=[{doc:this,isParent:!0,sharedHist:a.sharedHist}],ve(d,ue(this)),d},unlinkDoc:function(a){if(a instanceof y&&(a=a.doc),this.linked)for(var b=0;b<this.linked.length;++b){var c=this.linked[b];if(c.doc==a){this.linked.splice(b,1),a.unlinkDoc(this),we(ue(this));break}}if(a.history==this.history){var d=[a.id];Bf(a,function(a){d.push(a.id)},!0),a.history=new Lf(null),a.history.done=Wf(this.history.done,d),a.history.undone=Wf(this.history.undone,d)}},iterLinkedDocs:function(a){Bf(this,a)},getMode:function(){return this.mode},getEditor:function(){return this.cm}}),yf.prototype.eachLine=yf.prototype.iter;var zf="iter insert remove copy getEditor".split(" ");for(var Af in yf.prototype)yf.prototype.hasOwnProperty(Af)&&Bg(zf,Af)<0&&(y.prototype[Af]=function(a){return function(){return a.apply(this.doc,arguments)}}(yf.prototype[Af]));og(yf);var hg,$f=y.e_preventDefault=function(a){a.preventDefault?a.preventDefault():a.returnValue=!1},_f=y.e_stopPropagation=function(a){a.stopPropagation?a.stopPropagation():a.cancelBubble=!0},bg=y.e_stop=function(a){$f(a),_f(a)},eg=y.on=function(a,b,c){if(a.addEventListener)a.addEventListener(b,c,!1);else if(a.attachEvent)a.attachEvent("on"+b,c);else{var d=a._handlers||(a._handlers={}),e=d[b]||(d[b]=[]);e.push(c)}},fg=y.off=function(a,b,c){if(a.removeEventListener)a.removeEventListener(b,c,!1);else if(a.detachEvent)a.detachEvent("on"+b,c);else{var d=a._handlers&&a._handlers[b];if(!d)return;for(var e=0;e<d.length;++e)if(d[e]==c){d.splice(e,1);break}}},gg=y.signal=function(a,b){var c=a._handlers&&a._handlers[b];if(c)for(var d=Array.prototype.slice.call(arguments,2),e=0;e<c.length;++e)c[e].apply(null,d)},ig=0,pg=30,qg=y.Pass={toString:function(){return"CodeMirror.Pass"}},rg={scroll:!1},sg={origin:"*mouse"},tg={origin:"+move"};ug.prototype.set=function(a,b){clearTimeout(this.id),this.id=setTimeout(b,a)};var vg=y.countColumn=function(a,b,c,d,e){null==b&&(b=a.search(/[^\s\u00a0]/),-1==b&&(b=a.length));for(var f=d||0,g=e||0;;){var h=a.indexOf("	",f);if(0>h||h>=b)return g+(b-f);g+=h-f,g+=c-g%c,f=h+1}},xg=[""],Ag=function(a){a.select()};p?Ag=function(a){a.selectionStart=0,a.selectionEnd=a.value.length}:g&&(Ag=function(a){try{a.select()}catch(b){}}),[].indexOf&&(Bg=function(a,b){return a.indexOf(b)}),[].map&&(Cg=function(a,b){return a.map(b)});var Ng,Gg=/[\u00df\u3040-\u309f\u30a0-\u30ff\u3400-\u4db5\u4e00-\u9fcc\uac00-\ud7af]/,Hg=y.isWordChar=function(a){return/\w/.test(a)||a>"\x80"&&(a.toUpperCase()!=a.toLowerCase()||Gg.test(a))},Kg=/[\u0300-\u036f\u0483-\u0489\u0591-\u05bd\u05bf\u05c1\u05c2\u05c4\u05c5\u05c7\u0610-\u061a\u064b-\u065e\u0670\u06d6-\u06dc\u06de-\u06e4\u06e7\u06e8\u06ea-\u06ed\u0711\u0730-\u074a\u07a6-\u07b0\u07eb-\u07f3\u0816-\u0819\u081b-\u0823\u0825-\u0827\u0829-\u082d\u0900-\u0902\u093c\u0941-\u0948\u094d\u0951-\u0955\u0962\u0963\u0981\u09bc\u09be\u09c1-\u09c4\u09cd\u09d7\u09e2\u09e3\u0a01\u0a02\u0a3c\u0a41\u0a42\u0a47\u0a48\u0a4b-\u0a4d\u0a51\u0a70\u0a71\u0a75\u0a81\u0a82\u0abc\u0ac1-\u0ac5\u0ac7\u0ac8\u0acd\u0ae2\u0ae3\u0b01\u0b3c\u0b3e\u0b3f\u0b41-\u0b44\u0b4d\u0b56\u0b57\u0b62\u0b63\u0b82\u0bbe\u0bc0\u0bcd\u0bd7\u0c3e-\u0c40\u0c46-\u0c48\u0c4a-\u0c4d\u0c55\u0c56\u0c62\u0c63\u0cbc\u0cbf\u0cc2\u0cc6\u0ccc\u0ccd\u0cd5\u0cd6\u0ce2\u0ce3\u0d3e\u0d41-\u0d44\u0d4d\u0d57\u0d62\u0d63\u0dca\u0dcf\u0dd2-\u0dd4\u0dd6\u0ddf\u0e31\u0e34-\u0e3a\u0e47-\u0e4e\u0eb1\u0eb4-\u0eb9\u0ebb\u0ebc\u0ec8-\u0ecd\u0f18\u0f19\u0f35\u0f37\u0f39\u0f71-\u0f7e\u0f80-\u0f84\u0f86\u0f87\u0f90-\u0f97\u0f99-\u0fbc\u0fc6\u102d-\u1030\u1032-\u1037\u1039\u103a\u103d\u103e\u1058\u1059\u105e-\u1060\u1071-\u1074\u1082\u1085\u1086\u108d\u109d\u135f\u1712-\u1714\u1732-\u1734\u1752\u1753\u1772\u1773\u17b7-\u17bd\u17c6\u17c9-\u17d3\u17dd\u180b-\u180d\u18a9\u1920-\u1922\u1927\u1928\u1932\u1939-\u193b\u1a17\u1a18\u1a56\u1a58-\u1a5e\u1a60\u1a62\u1a65-\u1a6c\u1a73-\u1a7c\u1a7f\u1b00-\u1b03\u1b34\u1b36-\u1b3a\u1b3c\u1b42\u1b6b-\u1b73\u1b80\u1b81\u1ba2-\u1ba5\u1ba8\u1ba9\u1c2c-\u1c33\u1c36\u1c37\u1cd0-\u1cd2\u1cd4-\u1ce0\u1ce2-\u1ce8\u1ced\u1dc0-\u1de6\u1dfd-\u1dff\u200c\u200d\u20d0-\u20f0\u2cef-\u2cf1\u2de0-\u2dff\u302a-\u302f\u3099\u309a\ua66f-\ua672\ua67c\ua67d\ua6f0\ua6f1\ua802\ua806\ua80b\ua825\ua826\ua8c4\ua8e0-\ua8f1\ua926-\ua92d\ua947-\ua951\ua980-\ua982\ua9b3\ua9b6-\ua9b9\ua9bc\uaa29-\uaa2e\uaa31\uaa32\uaa35\uaa36\uaa43\uaa4c\uaab0\uaab2-\uaab4\uaab7\uaab8\uaabe\uaabf\uaac1\uabe5\uabe8\uabed\udc00-\udfff\ufb1e\ufe00-\ufe0f\ufe20-\ufe26\uff9e\uff9f]/;Ng=document.createRange?function(a,b,c){var d=document.createRange();return d.setEnd(a,c),d.setStart(a,b),d}:function(a,b,c){var d=document.body.createTextRange();return d.moveToElementText(a.parentNode),d.collapse(!0),d.moveEnd("character",c),d.moveStart("character",b),d},b&&(Rg=function(){try{return document.activeElement}catch(a){return document.body}});var _g,bh,dh,Xg=!1,$g=function(){if(d)return!1;var a=Mg("div");return"draggable"in a||"dragDrop"in a}(),fh=y.splitLines=3!="\n\nb".split(/\n/).length?function(a){for(var b=0,c=[],d=a.length;d>=b;){var e=a.indexOf("\n",b);-1==e&&(e=a.length);var f=a.slice(b,"\r"==a.charAt(e-1)?e-1:e),g=f.indexOf("\r");-1!=g?(c.push(f.slice(0,g)),b+=g+1):(c.push(f),b=e+1)}return c}:function(a){return a.split(/\r\n?|\n/)},gh=window.getSelection?function(a){try{return a.selectionStart!=a.selectionEnd}catch(b){return!1}}:function(a){try{var b=a.ownerDocument.selection.createRange()}catch(c){}return b&&b.parentElement()==a?0!=b.compareEndPoints("StartToEnd",b):!1},hh=function(){var a=Mg("div");return"oncopy"in a?!0:(a.setAttribute("oncopy","return;"),"function"==typeof a.oncopy)}(),ih={3:"Enter",8:"Backspace",9:"Tab",13:"Enter",16:"Shift",17:"Ctrl",18:"Alt",19:"Pause",20:"CapsLock",27:"Esc",32:"Space",33:"PageUp",34:"PageDown",35:"End",36:"Home",37:"Left",38:"Up",39:"Right",40:"Down",44:"PrintScrn",45:"Insert",46:"Delete",59:";",61:"=",91:"Mod",92:"Mod",93:"Mod",107:"=",109:"-",127:"Delete",173:"-",186:";",187:"=",188:",",189:"-",190:".",191:"/",192:"`",219:"[",220:"\\",221:"]",222:"'",63232:"Up",63233:"Down",63234:"Left",63235:"Right",63272:"Delete",63273:"Home",63275:"End",63276:"PageUp",63277:"PageDown",63302:"Insert"};y.keyNames=ih,function(){for(var a=0;10>a;a++)ih[a+48]=ih[a+96]=String(a);for(var a=65;90>=a;a++)ih[a]=String.fromCharCode(a);for(var a=1;12>=a;a++)ih[a+111]=ih[a+63235]="F"+a}();var rh,wh=function(){function c(c){return 247>=c?a.charAt(c):c>=1424&&1524>=c?"R":c>=1536&&1773>=c?b.charAt(c-1536):c>=1774&&2220>=c?"r":c>=8192&&8203>=c?"w":8204==c?"b":"L"}function j(a,b,c){this.level=a,this.from=b,this.to=c}var a="bbbbbbbbbtstwsbbbbbbbbbbbbbbssstwNN%%%NNNNNN,N,N1111111111NNNNNNNLLLLLLLLLLLLLLLLLLLLLLLLLLNNNNNNLLLLLLLLLLLLLLLLLLLLLLLLLLNNNNbbbbbbsbbbbbbbbbbbbbbbbbbbbbbbbbb,N%%%%NNNNLNNNNN%%11NLNNN1LNNNNNLLLLLLLLLLLLLLLLLLLLLLLNLLLLLLLLLLLLLLLLLLLLLLLLLLLLLLLN",b="rrrrrrrrrrrr,rNNmmmmmmrrrrrrrrrrrrrrrrrrrrrrrrrrrrrrrrrrrrrrrrrrrrrrrrrrrrrmmmmmmmmmmmmmmrrrrrrrnnnnnnnnnn%nnrrrmrrrrrrrrrrrrrrrrrrrrrrrrrrrrrrrrrrrrrrrrrrrrrrrrrrrrrrrrrrrrrrrrrrrrrrrrrrrrrrrrrrrrrrrrrrrrrrrrrrrrrmmmmmmmmmmmmmmmmmmmNmmmm",d=/[\u0590-\u05f4\u0600-\u06ff\u0700-\u08ac]/,e=/[stwN]/,f=/[LRr]/,g=/[Lb1n]/,h=/[1n]/,i="L";return function(a){if(!d.test(a))return!1;for(var m,b=a.length,k=[],l=0;b>l;++l)k.push(m=c(a.charCodeAt(l)));for(var l=0,n=i;b>l;++l){var m=k[l];"m"==m?k[l]=n:n=m}for(var l=0,o=i;b>l;++l){var m=k[l];"1"==m&&"r"==o?k[l]="n":f.test(m)&&(o=m,"r"==m&&(k[l]="R"))
}for(var l=1,n=k[0];b-1>l;++l){var m=k[l];"+"==m&&"1"==n&&"1"==k[l+1]?k[l]="1":","!=m||n!=k[l+1]||"1"!=n&&"n"!=n||(k[l]=n),n=m}for(var l=0;b>l;++l){var m=k[l];if(","==m)k[l]="N";else if("%"==m){for(var p=l+1;b>p&&"%"==k[p];++p);for(var q=l&&"!"==k[l-1]||b>p&&"1"==k[p]?"1":"N",r=l;p>r;++r)k[r]=q;l=p-1}}for(var l=0,o=i;b>l;++l){var m=k[l];"L"==o&&"1"==m?k[l]="L":f.test(m)&&(o=m)}for(var l=0;b>l;++l)if(e.test(k[l])){for(var p=l+1;b>p&&e.test(k[p]);++p);for(var s="L"==(l?k[l-1]:i),t="L"==(b>p?k[p]:i),q=s||t?"L":"R",r=l;p>r;++r)k[r]=q;l=p-1}for(var v,u=[],l=0;b>l;)if(g.test(k[l])){var w=l;for(++l;b>l&&g.test(k[l]);++l);u.push(new j(0,w,l))}else{var x=l,y=u.length;for(++l;b>l&&"L"!=k[l];++l);for(var r=x;l>r;)if(h.test(k[r])){r>x&&u.splice(y,0,new j(1,x,r));var z=r;for(++r;l>r&&h.test(k[r]);++r);u.splice(y,0,new j(2,z,r)),x=r}else++r;l>x&&u.splice(y,0,new j(1,x,l))}return 1==u[0].level&&(v=a.match(/^\s+/))&&(u[0].from=v[0].length,u.unshift(new j(0,0,v[0].length))),1==zg(u).level&&(v=a.match(/\s+$/))&&(zg(u).to-=v[0].length,u.push(new j(0,b-v[0].length,b))),u[0].level!=zg(u).level&&u.push(new j(u[0].level,b,b)),u}}();return y.version="4.1.1",y}),function(a){"object"==typeof exports&&"object"==typeof module?a(require("../../lib/codemirror")):"function"==typeof define&&define.amd?define(["../../lib/codemirror"],a):a(CodeMirror)}(function(a){"use strict";a.defineMode("sql",function(a,b){function k(a,b){var k=a.next();if(i[k]){var n=i[k](a,b);if(n!==!1)return n}if(1==h.hexNumber&&("0"==k&&a.match(/^[xX][0-9a-fA-F]+/)||("x"==k||"X"==k)&&a.match(/^'[0-9a-fA-F]+'/)))return"number";if(1==h.binaryNumber&&(("b"==k||"B"==k)&&a.match(/^'[01]+'/)||"0"==k&&a.match(/^b[01]+/)))return"number";if(k.charCodeAt(0)>47&&k.charCodeAt(0)<58)return a.match(/^[0-9]*\.?[0-9]+([eE][-+]?[0-9]+)?/),1==h.decimallessFloat&&a.eat("."),"number";if("?"==k&&(a.eatSpace()||a.eol()||a.eat(";")))return"variable-3";if("'"==k||'"'==k&&h.doubleQuote)return b.tokenize=l(k),b.tokenize(a,b);if((1==h.nCharCast&&("n"==k||"N"==k)||1==h.charsetCast&&"_"==k&&a.match(/[a-z][a-z0-9]*/i))&&("'"==a.peek()||'"'==a.peek()))return"keyword";if(/^[\(\),\;\[\]]/.test(k))return null;if(h.commentSlashSlash&&"/"==k&&a.eat("/"))return a.skipToEnd(),"comment";if(h.commentHash&&"#"==k||"-"==k&&a.eat("-")&&(!h.commentSpaceRequired||a.eat(" ")))return a.skipToEnd(),"comment";if("/"==k&&a.eat("*"))return b.tokenize=m,b.tokenize(a,b);if("."!=k){if(g.test(k))return a.eatWhile(g),null;if("{"==k&&(a.match(/^( )*(d|D|t|T|ts|TS)( )*'[^']*'( )*}/)||a.match(/^( )*(d|D|t|T|ts|TS)( )*"[^"]*"( )*}/)))return"number";a.eatWhile(/^[_\w\d]/);var o=a.current().toLowerCase();return j.hasOwnProperty(o)&&(a.match(/^( )+'[^']*'/)||a.match(/^( )+"[^"]*"/))?"number":d.hasOwnProperty(o)?"atom":e.hasOwnProperty(o)?"builtin":f.hasOwnProperty(o)?"keyword":c.hasOwnProperty(o)?"string-2":null}return 1==h.zerolessFloat&&a.match(/^(?:\d+(?:e[+-]?\d+)?)/i)?"number":1==h.ODBCdotTable&&a.match(/^[a-zA-Z_]+/)?"variable-2":void 0}function l(a){return function(b,c){for(var e,d=!1;null!=(e=b.next());){if(e==a&&!d){c.tokenize=k;break}d=!d&&"\\"==e}return"string"}}function m(a,b){for(;;){if(!a.skipTo("*")){a.skipToEnd();break}if(a.next(),a.eat("/")){b.tokenize=k;break}}return"comment"}function n(a,b,c){b.context={prev:b.context,indent:a.indentation(),col:a.column(),type:c}}function o(a){a.indent=a.context.indent,a.context=a.context.prev}var c=b.client||{},d=b.atoms||{"false":!0,"true":!0,"null":!0},e=b.builtin||{},f=b.keywords||{},g=b.operatorChars||/^[*+\-%<>!=&|~^]/,h=b.support||{},i=b.hooks||{},j=b.dateSQL||{date:!0,time:!0,timestamp:!0};return{startState:function(){return{tokenize:k,context:null}},token:function(a,b){if(a.sol()&&b.context&&null==b.context.align&&(b.context.align=!1),a.eatSpace())return null;var c=b.tokenize(a,b);if("comment"==c)return c;b.context&&null==b.context.align&&(b.context.align=!0);var d=a.current();return"("==d?n(a,b,")"):"["==d?n(a,b,"]"):b.context&&b.context.type==d&&o(b),c},indent:function(b,c){var d=b.context;if(!d)return 0;var e=c.charAt(0)==d.type;return d.align?d.col+(e?0:1):d.indent+(e?0:a.indentUnit)},blockCommentStart:"/*",blockCommentEnd:"*/",lineComment:h.commentSlashSlash?"//":h.commentHash?"#":null}}),function(){function b(a){for(var b;null!=(b=a.next());)if("`"==b&&!a.eat("`"))return"variable-2";return null}function c(a){return a.eat("@")&&(a.match(/^session\./),a.match(/^local\./),a.match(/^global\./)),a.eat("'")?(a.match(/^.*'/),"variable-2"):a.eat('"')?(a.match(/^.*"/),"variable-2"):a.eat("`")?(a.match(/^.*`/),"variable-2"):a.match(/^[0-9a-zA-Z$\.\_]+/)?"variable-2":null}function d(a){return a.eat("N")?"atom":a.match(/^[a-zA-Z.#!?]/)?"variable-2":null}function f(a){for(var b={},c=a.split(" "),d=0;d<c.length;++d)b[c[d]]=!0;return b}var e="alter and as asc between by count create delete desc distinct drop from having in insert into is join like not on or order select set table union update values where ";a.defineMIME("text/x-sql",{name:"sql",keywords:f(e+"begin"),builtin:f("bool boolean bit blob enum long longblob longtext medium mediumblob mediumint mediumtext time timestamp tinyblob tinyint tinytext text bigint int int1 int2 int3 int4 int8 integer float float4 float8 double char varbinary varchar varcharacter precision real date datetime year unsigned signed decimal numeric"),atoms:f("false true null unknown"),operatorChars:/^[*+\-%<>!=]/,dateSQL:f("date time timestamp"),support:f("ODBCdotTable doubleQuote binaryNumber hexNumber")}),a.defineMIME("text/x-mssql",{name:"sql",client:f("charset clear connect edit ego exit go help nopager notee nowarning pager print prompt quit rehash source status system tee"),keywords:f(e+"begin trigger proc view index for add constraint key primary foreign collate clustered nonclustered"),builtin:f("bigint numeric bit smallint decimal smallmoney int tinyint money float real char varchar text nchar nvarchar ntext binary varbinary image cursor timestamp hierarchyid uniqueidentifier sql_variant xml table "),atoms:f("false true null unknown"),operatorChars:/^[*+\-%<>!=]/,dateSQL:f("date datetimeoffset datetime2 smalldatetime datetime time"),hooks:{"@":c}}),a.defineMIME("text/x-mysql",{name:"sql",client:f("charset clear connect edit ego exit go help nopager notee nowarning pager print prompt quit rehash source status system tee"),keywords:f(e+"accessible action add after algorithm all analyze asensitive at authors auto_increment autocommit avg avg_row_length before binary binlog both btree cache call cascade cascaded case catalog_name chain change changed character check checkpoint checksum class_origin client_statistics close coalesce code collate collation collations column columns comment commit committed completion concurrent condition connection consistent constraint contains continue contributors convert cross current current_date current_time current_timestamp current_user cursor data database databases day_hour day_microsecond day_minute day_second deallocate dec declare default delay_key_write delayed delimiter des_key_file describe deterministic dev_pop dev_samp deviance diagnostics directory disable discard distinctrow div dual dumpfile each elseif enable enclosed end ends engine engines enum errors escape escaped even event events every execute exists exit explain extended fast fetch field fields first flush for force foreign found_rows full fulltext function general get global grant grants group groupby_concat handler hash help high_priority hosts hour_microsecond hour_minute hour_second if ignore ignore_server_ids import index index_statistics infile inner innodb inout insensitive insert_method install interval invoker isolation iterate key keys kill language last leading leave left level limit linear lines list load local localtime localtimestamp lock logs low_priority master master_heartbeat_period master_ssl_verify_server_cert masters match max max_rows maxvalue message_text middleint migrate min min_rows minute_microsecond minute_second mod mode modifies modify mutex mysql_errno natural next no no_write_to_binlog offline offset one online open optimize option optionally out outer outfile pack_keys parser partition partitions password phase plugin plugins prepare preserve prev primary privileges procedure processlist profile profiles purge query quick range read read_write reads real rebuild recover references regexp relaylog release remove rename reorganize repair repeatable replace require resignal restrict resume return returns revoke right rlike rollback rollup row row_format rtree savepoint schedule schema schema_name schemas second_microsecond security sensitive separator serializable server session share show signal slave slow smallint snapshot soname spatial specific sql sql_big_result sql_buffer_result sql_cache sql_calc_found_rows sql_no_cache sql_small_result sqlexception sqlstate sqlwarning ssl start starting starts status std stddev stddev_pop stddev_samp storage straight_join subclass_origin sum suspend table_name table_statistics tables tablespace temporary terminated to trailing transaction trigger triggers truncate uncommitted undo uninstall unique unlock upgrade usage use use_frm user user_resources user_statistics using utc_date utc_time utc_timestamp value variables varying view views warnings when while with work write xa xor year_month zerofill begin do then else loop repeat"),builtin:f("bool boolean bit blob decimal double float long longblob longtext medium mediumblob mediumint mediumtext time timestamp tinyblob tinyint tinytext text bigint int int1 int2 int3 int4 int8 integer float float4 float8 double char varbinary varchar varcharacter precision date datetime year unsigned signed numeric"),atoms:f("false true null unknown"),operatorChars:/^[*+\-%<>!=&|^]/,dateSQL:f("date time timestamp"),support:f("ODBCdotTable decimallessFloat zerolessFloat binaryNumber hexNumber doubleQuote nCharCast charsetCast commentHash commentSpaceRequired"),hooks:{"@":c,"`":b,"\\":d}}),a.defineMIME("text/x-mariadb",{name:"sql",client:f("charset clear connect edit ego exit go help nopager notee nowarning pager print prompt quit rehash source status system tee"),keywords:f(e+"accessible action add after algorithm all always analyze asensitive at authors auto_increment autocommit avg avg_row_length before binary binlog both btree cache call cascade cascaded case catalog_name chain change changed character check checkpoint checksum class_origin client_statistics close coalesce code collate collation collations column columns comment commit committed completion concurrent condition connection consistent constraint contains continue contributors convert cross current current_date current_time current_timestamp current_user cursor data database databases day_hour day_microsecond day_minute day_second deallocate dec declare default delay_key_write delayed delimiter des_key_file describe deterministic dev_pop dev_samp deviance diagnostics directory disable discard distinctrow div dual dumpfile each elseif enable enclosed end ends engine engines enum errors escape escaped even event events every execute exists exit explain extended fast fetch field fields first flush for force foreign found_rows full fulltext function general generated get global grant grants group groupby_concat handler hard hash help high_priority hosts hour_microsecond hour_minute hour_second if ignore ignore_server_ids import index index_statistics infile inner innodb inout insensitive insert_method install interval invoker isolation iterate key keys kill language last leading leave left level limit linear lines list load local localtime localtimestamp lock logs low_priority master master_heartbeat_period master_ssl_verify_server_cert masters match max max_rows maxvalue message_text middleint migrate min min_rows minute_microsecond minute_second mod mode modifies modify mutex mysql_errno natural next no no_write_to_binlog offline offset one online open optimize option optionally out outer outfile pack_keys parser partition partitions password persistent phase plugin plugins prepare preserve prev primary privileges procedure processlist profile profiles purge query quick range read read_write reads real rebuild recover references regexp relaylog release remove rename reorganize repair repeatable replace require resignal restrict resume return returns revoke right rlike rollback rollup row row_format rtree savepoint schedule schema schema_name schemas second_microsecond security sensitive separator serializable server session share show shutdown signal slave slow smallint snapshot soft soname spatial specific sql sql_big_result sql_buffer_result sql_cache sql_calc_found_rows sql_no_cache sql_small_result sqlexception sqlstate sqlwarning ssl start starting starts status std stddev stddev_pop stddev_samp storage straight_join subclass_origin sum suspend table_name table_statistics tables tablespace temporary terminated to trailing transaction trigger triggers truncate uncommitted undo uninstall unique unlock upgrade usage use use_frm user user_resources user_statistics using utc_date utc_time utc_timestamp value variables varying view views virtual warnings when while with work write xa xor year_month zerofill begin do then else loop repeat"),builtin:f("bool boolean bit blob decimal double float long longblob longtext medium mediumblob mediumint mediumtext time timestamp tinyblob tinyint tinytext text bigint int int1 int2 int3 int4 int8 integer float float4 float8 double char varbinary varchar varcharacter precision date datetime year unsigned signed numeric"),atoms:f("false true null unknown"),operatorChars:/^[*+\-%<>!=&|^]/,dateSQL:f("date time timestamp"),support:f("ODBCdotTable decimallessFloat zerolessFloat binaryNumber hexNumber doubleQuote nCharCast charsetCast commentHash commentSpaceRequired"),hooks:{"@":c,"`":b,"\\":d}}),a.defineMIME("text/x-cassandra",{name:"sql",client:{},keywords:f("use select from using consistency where limit first reversed first and in insert into values using consistency ttl update set delete truncate begin batch apply create keyspace with columnfamily primary key index on drop alter type add any one quorum all local_quorum each_quorum"),builtin:f("ascii bigint blob boolean counter decimal double float int text timestamp uuid varchar varint"),atoms:f("false true"),operatorChars:/^[<>=]/,dateSQL:{},support:f("commentSlashSlash decimallessFloat"),hooks:{}}),a.defineMIME("text/x-plsql",{name:"sql",client:f("appinfo arraysize autocommit autoprint autorecovery autotrace blockterminator break btitle cmdsep colsep compatibility compute concat copycommit copytypecheck define describe echo editfile embedded escape exec execute feedback flagger flush heading headsep instance linesize lno loboffset logsource long longchunksize markup native newpage numformat numwidth pagesize pause pno recsep recsepchar release repfooter repheader serveroutput shiftinout show showmode size spool sqlblanklines sqlcase sqlcode sqlcontinue sqlnumber sqlpluscompatibility sqlprefix sqlprompt sqlterminator suffix tab term termout time timing trimout trimspool ttitle underline verify version wrap"),keywords:f("abort accept access add all alter and any array arraylen as asc assert assign at attributes audit authorization avg base_table begin between binary_integer body boolean by case cast char char_base check close cluster clusters colauth column comment commit compress connect connected constant constraint crash create current currval cursor data_base database date dba deallocate debugoff debugon decimal declare default definition delay delete desc digits dispose distinct do drop else elseif elsif enable end entry escape exception exception_init exchange exclusive exists exit external fast fetch file for force form from function generic goto grant group having identified if immediate in increment index indexes indicator initial initrans insert interface intersect into is key level library like limited local lock log logging long loop master maxextents maxtrans member minextents minus mislabel mode modify multiset new next no noaudit nocompress nologging noparallel not nowait number_base object of off offline on online only open option or order out package parallel partition pctfree pctincrease pctused pls_integer positive positiven pragma primary prior private privileges procedure public raise range raw read rebuild record ref references refresh release rename replace resource restrict return returning returns reverse revoke rollback row rowid rowlabel rownum rows run savepoint schema segment select separate session set share snapshot some space split sql start statement storage subtype successful synonym tabauth table tables tablespace task terminate then to trigger truncate type union unique unlimited unrecoverable unusable update use using validate value values variable view views when whenever where while with work"),builtin:f("abs acos add_months ascii asin atan atan2 average bfile bfilename bigserial bit blob ceil character chartorowid chr clob concat convert cos cosh count dec decode deref dual dump dup_val_on_index empty error exp false float floor found glb greatest hextoraw initcap instr instrb int integer isopen last_day least lenght lenghtb ln lower lpad ltrim lub make_ref max min mlslabel mod months_between natural naturaln nchar nclob new_time next_day nextval nls_charset_decl_len nls_charset_id nls_charset_name nls_initcap nls_lower nls_sort nls_upper nlssort no_data_found notfound null number numeric nvarchar2 nvl others power rawtohex real reftohex round rowcount rowidtochar rowtype rpad rtrim serial sign signtype sin sinh smallint soundex sqlcode sqlerrm sqrt stddev string substr substrb sum sysdate tan tanh to_char text to_date to_label to_multi_byte to_number to_single_byte translate true trunc uid unlogged upper user userenv varchar varchar2 variance varying vsize xml"),operatorChars:/^[*+\-%<>!=~]/,dateSQL:f("date time timestamp"),support:f("doubleQuote nCharCast zerolessFloat binaryNumber hexNumber")}),a.defineMIME("text/x-hive",{name:"sql",keywords:f("select alter $elem$ $key$ $value$ add after all analyze and archive as asc before between binary both bucket buckets by cascade case cast change cluster clustered clusterstatus collection column columns comment compute concatenate continue create cross cursor data database databases dbproperties deferred delete delimited desc describe directory disable distinct distribute drop else enable end escaped exclusive exists explain export extended external false fetch fields fileformat first format formatted from full function functions grant group having hold_ddltime idxproperties if import in index indexes inpath inputdriver inputformat insert intersect into is items join keys lateral left like limit lines load local location lock locks mapjoin materialized minus msck no_drop nocompress not of offline on option or order out outer outputdriver outputformat overwrite partition partitioned partitions percent plus preserve procedure purge range rcfile read readonly reads rebuild recordreader recordwriter recover reduce regexp rename repair replace restrict revoke right rlike row schema schemas semi sequencefile serde serdeproperties set shared show show_database sort sorted ssl statistics stored streamtable table tables tablesample tblproperties temporary terminated textfile then tmp to touch transform trigger true unarchive undo union uniquejoin unlock update use using utc utc_tmestamp view when where while with"),builtin:f("bool boolean long timestamp tinyint smallint bigint int float double date datetime unsigned string array struct map uniontype"),atoms:f("false true null unknown"),operatorChars:/^[*+\-%<>!=]/,dateSQL:f("date timestamp"),support:f("ODBCdotTable doubleQuote binaryNumber hexNumber")})}()});
</script>
<script type="text/javascript">if( document.getElementsByTagName('textarea').length ){	var areas = document.getElementsByTagName('textarea');		arrau = Array.prototype.slice.call( areas, 0 );	for(var i=0;i<arrau.length;i++){		if( arrau[i].getAttribute && arrau[i].getAttribute('id')!='sql' )			CodeMirror.fromTextArea(arrau[i]);		}}if( document.getElementById('sql') ){	CodeMirror.fromTextArea(document.getElementById('sql'), {		mode: "text/x-mysql"	});}function toggleAll(){	var table = document.getElementById('result_table'),		inputs = table.getElementsByTagName('input'),		i = 0;	for(;i<inputs.length;i++){		if( inputs[i].getAttribute('type') && inputs[i].getAttribute('type').toLowerCase()=='checkbox' && inputs[i].getAttribute('name')!='header_value' ){			inputs[i].checked = !inputs[i].checked;		}	}		return false;}function deleteAllSelected(){	var table = document.getElementById('result_table'),		inputs = table.getElementsByTagName('input'),		i = 0;	var selected = [];	for(;i<inputs.length;i++){		if( inputs[i].getAttribute('type') && inputs[i].getAttribute('type').toLowerCase()=='checkbox' && inputs[i].getAttribute('name')!='header_value' &&inputs[i].checked ){			selected.push('values[]='+inputs[i].value);		}	}		if( selected.length){		if( confirm('Are you sure you want to delete '+selected.length+' records?') ){			document.location = this.getAttribute('href')+'&'+selected.join('&');;		}	}else{		alert('No row in the table is selected');	};	return false;}</script>
</body>
</html>
