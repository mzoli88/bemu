<?php
require_once('config.class.php');
if(!function_exists('mysql_connect'))require_once('mysql_old.php');
require_once("gdbtools.php");

class b_alap{
	
	static $db;
	
	// public function b_alap(){}
	
	static function belepve() {
		session_name('SESS_'.BORDER_PREFIX.'ID');
		if(session_status() == PHP_SESSION_NONE)session_start();
	}
	
	static function get_csoport_only($csoportkeres) {
		$db = new mysqli(BORDER_DB_HOST, BORDER_DB_USER, BORDER_DB_PASSWORD, BORDER_DB_DATABASE);
		$result = $db->query("SELECT * FROM nevek_csoport WHERE nev = '".$csoportkeres."'");
		$vanjog = $result->fetch_object();
		$result->close();
		if(!$vanjog)$result = $db->query("INSERT INTO nevek_csoport (nev) VALUES ('".$csoportkeres."')");
		$result = $db->query("SELECT * FROM nevek_csoportosit INNER JOIN nevek_csoport ON nevek_csoport.csoport_id = nevek_csoportosit.csoport_id WHERE nevek_csoport.nev = '".$csoportkeres."' AND nevek_csoportosit.nevek_id = ".$_SESSION['id']);
		$vanjog = $result->fetch_object();
		if($vanjog)return true;else false;
	}
	
	static function rendszergazda_e(){
		$db = new mysqli(BORDER_DB_HOST, BORDER_DB_USER, BORDER_DB_PASSWORD, BORDER_DB_DATABASE);
		$result = $db->query('SELECT nevek_id from nevek_csoportosit where csoport_id=2 and nevek_id='.$_SESSION['id']);
		$id = $result->fetch_object();
		$result->close();
		if($id)return true;else false;
	}
	
	static function uzenet() {
		return true;
	}

	function utf($string) {
		return iconv('ISO-8859-2', 'UTF-8', $string);
	}

	function iso($string) {
		return iconv('UTF-8', 'ISO-8859-2', $string);
	}
	
	static function naplo($uzenet='Nincs szöveg', $forras='border', $db=false, $tipus=B_LOG_INFO, $forward_allowed=true, $partner='', $csoportok='') {
		// print_r('ITT');
	}

}