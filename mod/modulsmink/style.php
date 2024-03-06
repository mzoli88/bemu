<?php
header("Content-type: text/css");
require_once('config.class.php');
if(defined('MODULSMINK') && !empty(MODULSMINK) ){
	echo file_get_contents(__DIR__ . DIRECTORY_SEPARATOR  . MODULSMINK . ".css");
}else{
	echo file_get_contents(__DIR__ . DIRECTORY_SEPARATOR  . "style2.css");
}