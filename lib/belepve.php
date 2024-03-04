<?php

@require_once('config.class.php');
if(isset($no_permisson_uri) && in_array($_SERVER['REQUEST_URI'],$no_permisson_uri)){
	return;
}

if (empty(session_id())) {
	session_name('SESS_' . BORDER_PREFIX . 'ID');
	session_start();
}

if (empty($_SESSION['id'])){
	header("HTTP/1.1 401 Unauthorized");
    exit;
}