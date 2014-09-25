<?php
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