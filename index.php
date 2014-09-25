<?php
session_start();
include 'includes/config.php';

include 'includes/connector.php';

include 'includes/functions.php';

include 'includes/SqlFormatter.php';

include 'includes/logic.php';

?><!DOCTYPE html>
<html lang="ru">
<head>
<meta http-equiv="content-type" content="text/html; charset=utf-8"/>
<title>Mini MySQL Admin v<? include 'includes/version.php';?></title>
<meta name="keywords" content="" /> 
<meta name="description" content=""/>
<link href="styles/favicon.png" rel="shortcut icon" type="image/x-icon">
<link href="styles/bootstrap/css/bootstrap.min.css" rel="stylesheet"/>
<link href="styles/bootstrap/css/bootstrap-theme.min.css" rel="stylesheet"/>
<link rel="stylesheet" href="scripts/codemirror.css">
<link rel="stylesheet" type="text/css" href="styles/style.css" />

</head>
<body>
<div class="header panel-success ">
	<a href="http://xdsoft.net/miniMySQLAdmin/"><strong>miniMySQLAdmin</strong> <span style="opacity:0.7;">(version:<? include 'includes/version.php';?>)</span></a>
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
<script type="text/javascript" src="scripts/codemirror.js"></script>
<script type="text/javascript" src="scripts/file.js"></script>
</body>
</html>
