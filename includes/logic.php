<?php
$db = new db();
$action = (isset($_REQUEST['action']) and in_array($_REQUEST['action'],array('index','connect','login','add','edit','save','delete')))?$_REQUEST['action']:'index';

$data = array('error'=>'','connect'=>'');

$_REQUEST = array_merge($config,$_REQUEST,$_SESSION);

$sql = '';
$database_selected  = false;
$connected  = false;

if( isset($_COOKIE['key_man']) ){
	list($config['host'],$config['username'],$config['password']) = decode($_COOKIE['key_man']);
	if( $db->connect($config['host'],$config['username'],$config['password']) ){
		$connected = true;
	}else{
		$data['error'] = $db->error();
	}
}

switch( $action ){
	case 'save':
		if( $connected ){
			if( $db->selectdb($_REQUEST['dbname']) ){
				if( isset($_POST['key']) and is_array($_POST['key']) and count($_POST['key']) ){
					if( isset($_REQUEST['table']) and isset($_REQUEST['primary_key']) and isset($_REQUEST['primary_value']) ){
						$inq = $db->update($_GET['table'],$_POST['key'],'`'.$db->_($_REQUEST['primary_key']).'`=\''.$db->_($_REQUEST['primary_value']).'\'');
						if(!$inq)
							$data['error'] = $db->error();
					}elseif( isset($_REQUEST['table']) ){
						$inq = $db->insert($_GET['table'],$_POST['key']);
					}
				}

				$sql = 'select * FROM '.$db->_($_REQUEST['table']);
				$database_selected = true;
				$action = 'index';
				if( is_select($sql) and !is_limited($sql) )
					$sql = add_to_sql($sql,'limit',$config['count_on_page']);
				
				$time = microtime(true);
					
				$inq = $db->q( $sql );
				
				$endtime = microtime(true) - $time;
				
				if( $inq and is_select($sql) and mysql_field_table($inq,0) )
					$_GET['table'] = $_SESSION['table'] = $_REQUEST['table'] = mysql_field_table($inq,0);
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
						$sql = 'select * FROM '.$db->_($_REQUEST['table']);
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
						$sql = $_REQUEST['sql']? $_REQUEST['sql']:'select * FROM '.$db->_($_REQUEST['table']);
					}else
						$sql = $_REQUEST['sql']? $_REQUEST['sql']:'SHOW TABLES FROM '.$db->_($_REQUEST['dbname']);
					
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
		if( $db->connect($_REQUEST['host'],$_REQUEST['username'],$_REQUEST['password']) ){
			setcookie('key_man',encode($_REQUEST['host'],$_REQUEST['username'],$_REQUEST['password']));
			$action = 'index';
			header('location:?action=index');
			exit();
		}else{
			if( !empty($_REQUEST['through']) ){
				$connect_data = try_connect_through($_REQUEST['through']);
				if( $connect_data ){
					setcookie('key_man',encode($connect_data['host'],$connect_data['username'],$connect_data['password']));
					header('location:?action=index');
					exit();
				}
			}
			setcookie('key_man','');
			$data['error'] = $db->error();
			$action = 'login';
		}
	break;
}	