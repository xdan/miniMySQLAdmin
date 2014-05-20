<?php
include 'functions.php';
$mysqladmin = file_get_contents('index.php');
$mysqladmin = preg_replace_callback('#include \'(.*)\';#Uui',function($match){
	$file = file_get_contents($match[1]);
	$file = preg_replace('#^<\?php#','',$file);
	return $file;
},$mysqladmin);

$mysqladmin = preg_replace_callback('#<link[^>]*href="([^"]+)"[^>]*>#Uui',function($match){
	$file = file_get_contents($match[1]);
	$path = dirname(realpath($match[1]));
	if( preg_match('#\.png$#',$match[1]) ){
		return preg_replace('#href="([^"]+)"#','href="'.base64_encode_image($match[1],'png').'"',$match[0]);
	}else{
		$file = preg_replace_callback('#url\(([^\)]+)\)#Uui',function($match) use ($path){
			$image = realpath($path.$match[1]);
			if( realpath($image) and is_file($image) )
				return 'url('.base64_encode_image($image,'png').')';
			else
				return $match[0];
		},$file);
		return '<style>'.$file.'</style>';
	}
},$mysqladmin);

$mysqladmin = preg_replace_callback('#<script[^>]*src="([^"]+)"[^>]*>#Uui',function($match){
	$file = file_get_contents($match[1]);
	return '<script type="text/javascript">'.$file;
},$mysqladmin);

file_put_contents('mysqladmin.php',$mysqladmin);
echo 'Complite : <a href="mysqladmin.php">mysqladmin.php</a>';