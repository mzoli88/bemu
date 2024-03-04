<?php
require_once( __DIR__ . '/../config.php');

define('BORDER_PATH_BORDER', preg_replace('/^[A-Z]\:/','',realpath( __DIR__ . DIRECTORY_SEPARATOR . '..')  . DIRECTORY_SEPARATOR ));
define('BORDER_PATH_BORDERLIB', BORDER_PATH_BORDER . 'lib' . DIRECTORY_SEPARATOR);
define('BORDER_PATH_BORDERDOC', BORDER_PATH_BORDER . 'doc' . DIRECTORY_SEPARATOR);
define('BORDER_PREFIX', 'bemu_');
define('BORDER_EMU', true);
define('BORDER_PATH_JAVA', 'java');


function emu_user_init(){
	
	if (PHP_SAPI === 'cli')return;

	if(isset($_COOKIE['SESS_'.BORDER_PREFIX.'ID'])){
		if(file_exists(session_save_path().'\sess_'.$_COOKIE['SESS_'.BORDER_PREFIX.'ID'])){
			if(!empty(file_get_contents(session_save_path().'\sess_'.$_COOKIE['SESS_'.BORDER_PREFIX.'ID'])))return;
		}
	}
	
	if(session_status() == PHP_SESSION_NONE){
		session_name('SESS_'.BORDER_PREFIX.'ID');
		session_start();
	}
	
	$db = new mysqli(BORDER_DB_HOST, BORDER_DB_USER, BORDER_DB_PASSWORD);
	if ($db->connect_error){
		echo '<!DOCTYPE html><html><body>Lehet nincs adatbazis? <a href="lib/createtables.php">Telepites futtatása</a><br>' . $db->connect_error . '</body></html>';
		die;
	}
	
	try {
		$db->select_db(BORDER_DB_DATABASE);
	} catch (\Throwable $th) {
		echo '<!DOCTYPE html><html><body>'.$th->getMessage().'<br>Lehet nincs adatbazis? <a href="lib/createtables.php">Telepites futtatása</a><br></body></html>';
		die;
	}

	$result = $db->query("SELECT v FROM b_emu_param WHERE p = 'active_user'");
	if($result){
		$id = $result->fetch_object()->v;
		$result->close();
	}else{
		echo '<!DOCTYPE html><html><body>Nincs b_emu_param tabla! <a href="lib/createtables.php">Telepites futtatása</a></body></html>';
		die;
	}

	$result = $db->query("SELECT * FROM nevek WHERE id = ".$id);
	$userdata = $result->fetch_object();
	$result->close();

	$_SESSION['id']=$userdata->id;
	$_SESSION['nev']=mb_convert_encoding($userdata->nev,'ISO-8859-1');
	$_SESSION['teljesnev']=mb_convert_encoding($userdata->teljesnev,'ISO-8859-1');
	$_SESSION['telefon']=mb_convert_encoding($userdata->telefon,'ISO-8859-1');
	$_SESSION['email']=mb_convert_encoding($userdata->email,'ISO-8859-1');
	$_SESSION['p_nev']="Hitelintézet neve";
	$_SESSION['g_check_naja'] = crypt($_SESSION['id'],session_id());
	session_commit();
	
}

emu_user_init();