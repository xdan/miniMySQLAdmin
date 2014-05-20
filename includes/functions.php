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

function _trim($value){
	return preg_replace(array('#^[\n\r\t\s]+#u','#[\n\r\t\s]+$#u'),'',$value);
}

function encode($host,$user,$password){
	global $config; 
	return base64_encode(ssd_deob($host.'&|&'.$user.'&|&'.$password,$config['ssd_deob']));
}
function decode($val){
	global $config; 
	return explode('&|&',ssd_deob(base64_decode($val),$config['ssd_deob']));
}
function _($sql){
	return addslashes($sql);
}

function analize($sql,$value,$key,$primary,$table){
	global $config;
	if( preg_match('#^show#iu',_trim($sql)) ){
		if(preg_match('#databases#iu',_trim($sql))){
			return '<a href="?dbname='.$value.'"><i class="icon icon_db"></i> '.$value.'</a>';
		}elseif(preg_match('#tables#iu',_trim($sql))){
			return '<a class="col-sm-4" href="?table='.$value.'&sql='._('select * from `'.$value.'`').'"><i class="icon icon_table"></i> '.$value.'</a>  <a class="col-sm-1" href="?table='.$value.'&sql='._('SHOW COLUMNS FROM `'.$value.'`').'">structure</a>  <a href="?table='.$value.'&sql='._('SHOW CREATE TABLE `'.$value.'`').'">SQL</a>';
		}elseif( preg_match('#CREATE[\s\t\n]+TABLE#iu',_trim($sql)) ){
			return htmlspecialchars(_trim($value));
		}
		return $value;
	}elseif( preg_match('#^select#iu',_trim($sql)) ){
		return ($table&&$primary?'<input type="checkbox" name="value" value="'.$value.'"> <a style="" onclick="return confirm(\'Are you shure?\')" href="?table='.$table.'&action=delete&key='._($key).'&value='._($value).'"><i class="icon icon_delete"></i></a> <a href="?table='.$table.'&action=edit&key='.$key.'&value='.$value.'"><i class="icon icon_edit"></i></a> ':'').htmlspecialchars(mb_substr($value,0,$config['max_str_len'],$config['charset']));
	}
	return $value;
}
function analize_header($sql,$value,$primary,$table){
	if( is_select($sql) ){
		return ($primary&&$table?'<input onclick="toggleAll.call();" type="checkbox" name="header_value" value="'.$value.'"> ':'').'<a href="?sql='._(add_to_sql($sql,'order',$value)).'">'.$value.'</a>';
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
	return preg_match('#[\s\n\r\t]*order[\s\n\r\t]+by#ui',$sql);
}
function is_enum($inq, $i){
	return strpos(mysql_field_flags($inq, $i), 'enum') !== false;
}

function get_enum_values( $table, $field ){
   global $db;
   $type = $db->row( "SHOW COLUMNS FROM {$table} WHERE Field = '{$field}'" ,'Type');
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
			if( is_ordered($sql) ){
				$sql = preg_replace('#[\s\n\r\t]*order[\s\n\r\t]+by[\s\n\r\t]+[^\s\n\r\t;]+[\s\n\r\t]*#ui',' order by `'.$value.'` ',$sql);
			}else{
				if( is_limited($sql) ){
					$sql=preg_replace('#[\s\n\r\t]*limit[\s\n\r\t]+#ui',' order by `'.$value.'` limit ',$sql);
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
			return '<input type="number" class="form-control" name="key['._($key).']" id="key_'._($key).'" placeholder="'._($key).'" value="'._($value).'">';
		break;
		case 'string':
			if( is_enum($inq,$i) ){
				$enum_variants = get_enum_values($table,$key);
				$out = '<select class="form-control" name="key['._($key).']" id="key_'._($key).'">';
				foreach($enum_variants as $name){
					$out.='<option '.($value==$name?'selected':'').' value="'.$name.'">'.$name.'</option>';
				}
				$out.='</select>';
			}else{
				$out='<input type="text" class="form-control" name="key['._($key).']" id="key_'._($key).'" placeholder="'._($key).'" value="'._($value).'">';
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