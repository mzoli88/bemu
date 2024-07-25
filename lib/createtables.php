<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once(__DIR__ . '/../config.php');

// Create connection
$conn = new mysqli(BORDER_DB_HOST, BORDER_DB_USER, BORDER_DB_PASSWORD);
// Check connection
if ($conn->connect_error) die("Connection failed: " . $conn->connect_error);
// Create database
$conn->execute_query("CREATE DATABASE IF NOT EXISTS " . BORDER_DB_DATABASE);

$conn->select_db(BORDER_DB_DATABASE);

//ha nincs b_emu_param tábla akor létrehzza
$conn->execute_query("
CREATE TABLE IF NOT EXISTS `b_emu_param` (
  `p` varchar(255) COLLATE utf8_hungarian_ci NOT NULL,
  `v` longtext COLLATE utf8_hungarian_ci NOT NULL,
  UNIQUE KEY `p` (`p`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_hungarian_ci;");

$ct = $conn->query("select count(*) from b_emu_param")->fetch_row()[0];
if ($ct == 0) $conn->execute_query("INSERT INTO `b_emu_param` (`p`, `v`)VALUES ('active_user', '2');");

//ha nincs b_nagycsoport_nevek tábla akkor létrehzza
$conn->execute_query("
CREATE TABLE IF NOT EXISTS `b_nagycsoport_nevek` (
  `b_nagycsoport_nevek_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `b_nagycsoport_id` int(10) unsigned DEFAULT NULL,
  `nevek_id` int(10) unsigned DEFAULT NULL,
  PRIMARY KEY (`b_nagycsoport_nevek_id`),
  KEY `b_nagycsoport_id` (`b_nagycsoport_id`,`nevek_id`),
  KEY `nevek_id` (`nevek_id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;");

//ha nincs jogok tábla akkor létrehzza
$conn->execute_query("
CREATE TABLE IF NOT EXISTS `nevek_csoport` (
	`csoport_id` int(11) unsigned NOT NULL AUTO_INCREMENT,
	`nev` varchar(100) NOT NULL DEFAULT '',
	`ugyfeladmin` tinyint(1) NOT NULL DEFAULT '0',
	`modul_azon` varchar(30) DEFAULT NULL,
	PRIMARY KEY (`csoport_id`),
	KEY `modul_azon` (`modul_azon`),
	KEY `nev` (`nev`),
	KEY `ugyfeladmin` (`ugyfeladmin`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1 PACK_KEYS=0;");

$ct = $conn->query("select count(*) from nevek_csoport")->fetch_row()[0];
if ($ct == 0) $conn->execute_query("INSERT INTO `nevek_csoport` (`csoport_id`, `nev`)VALUES (2, 'Border admin');");

//ha nincs b_nagycsoport tábla akkor létrehzza
$conn->execute_query("
CREATE TABLE IF NOT EXISTS `b_nagycsoport` (
	`b_nagycsoport_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
	`nev` varchar(100) DEFAULT NULL,
	`b_nagycsoport_tipus_id` int(11) DEFAULT NULL,
	PRIMARY KEY (`b_nagycsoport_id`),
	KEY `nev` (`nev`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;");

//ha nincs b_nagycsoport_modul tábla akkor létrehzza
$conn->execute_query("
CREATE TABLE IF NOT EXISTS `b_nagycsoport_modul` (
	`b_nagycsoport_modul_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
	`b_nagycsoport_id` int(10) unsigned DEFAULT NULL,
	`modul_azon` varchar(30) DEFAULT NULL,
	PRIMARY KEY (`b_nagycsoport_modul_id`),
	KEY `b_nagycsoport_id` (`b_nagycsoport_id`,`modul_azon`),
	KEY `modul_azon` (`modul_azon`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;");

//ha nincs nevek tábla létrehzza
$conn->execute_query("
CREATE TABLE IF NOT EXISTS `nevek` (
	`id` int(11) unsigned NOT NULL AUTO_INCREMENT,
	`nev` varchar(150) NOT NULL,
	`teljesnev` varchar(150) DEFAULT NULL,
	`jelszo` varchar(100) DEFAULT '#',
	`valtoztass` tinyint(1) unsigned DEFAULT NULL,
	`email` varchar(150) DEFAULT NULL,
	`telefon` varchar(11) DEFAULT NULL,
	`beosztas` varchar(100) DEFAULT NULL,
	`csopid` int(11) DEFAULT '1',
	`belephet` tinyint(1) DEFAULT '1',
	`alias` varchar(150) DEFAULT NULL,
	`ldap` varchar(150) DEFAULT NULL,
	`noleiras` tinyint(1) DEFAULT '0',
	`tavoliip` varchar(50) DEFAULT NULL,
	`sip` varchar(10) DEFAULT NULL,
	`email_pw` varchar(50) DEFAULT NULL,
	`uazon` varchar(8) DEFAULT NULL,
	`sikertelen` int(11) DEFAULT '0',
	`extfelulet` tinyint(1) DEFAULT '1',
	`rss` tinyint(1) DEFAULT '1',
	`upin` varchar(60) DEFAULT NULL,
	`tavolinev` varchar(150) DEFAULT NULL,
	`parhuzam` tinyint(1) DEFAULT '0',
	`partner_id` int(11) DEFAULT NULL,
	`internetrol` tinyint(1) DEFAULT '0',
	`hash` tinyint(1) DEFAULT '0',
	`telefon2` varchar(50) DEFAULT NULL,
	`b_nevek_statusz_id` int(11) DEFAULT NULL,
	`torolheto` tinyint(1) DEFAULT '0',
	PRIMARY KEY (`id`),
	KEY `nev` (`nev`),
	KEY `teljesnev` (`teljesnev`),
	KEY `belephet` (`belephet`),
	KEY `partner_id` (`partner_id`),
	KEY `internetrol` (`internetrol`),
	KEY `email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1 PACK_KEYS=0;");

$ct = $conn->query("select count(*) from nevek")->fetch_row()[0];
if ($ct == 0) $conn->execute_query("
INSERT INTO `nevek` (`id`, `nev`, `teljesnev`, `email`, `telefon`)
VALUES ('2', 'admin', 'Rendszer - Admin', 'Border@Admin.hu', '1234');");

$conn->execute_query("
	CREATE TABLE IF NOT EXISTS `nevek_csoportosit` (
	  `nevek_csoportosit_id` int(11) unsigned NOT NULL AUTO_INCREMENT,
	  `csoport_id` int(11) NOT NULL DEFAULT '0',
	  `nevek_id` int(11) NOT NULL DEFAULT '0',
	  PRIMARY KEY (`nevek_csoportosit_id`),
	  KEY `csoport_id` (`csoport_id`),
	  KEY `nevek_id` (`nevek_id`)
	) ENGINE=InnoDB DEFAULT CHARSET=latin1 PACK_KEYS=0;");

$ct = $conn->query("select count(*) from nevek_csoportosit")->fetch_row()[0];
if ($ct == 0) $conn->execute_query("INSERT INTO `nevek_csoportosit` (`csoport_id`, `nevek_id`) VALUES (2, 2);");

$conn->execute_query("
CREATE TABLE IF NOT EXISTS `b_nagycsoport_tipus` (
	`b_nagycsoport_tipus_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
	`tipusnev` varchar(50) DEFAULT NULL,
	PRIMARY KEY (`b_nagycsoport_tipus_id`),
	KEY `tipusnev` (`tipusnev`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;");

$ct = $conn->query("select count(*) from b_nagycsoport_tipus")->fetch_row()[0];
if ($ct == 0) $conn->execute_query("
INSERT INTO `b_nagycsoport_tipus` (`b_nagycsoport_tipus_id`, `tipusnev`) VALUES
	(1,	'Szervezeti egység'),
	(2,	'Jogosultság csoport');");

$conn->execute_query("
	CREATE TABLE IF NOT EXISTS `b_menu` (
	  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
	  `id_csoport` int(11) NOT NULL DEFAULT '0',
	  `item` tinyint(4) NOT NULL DEFAULT '0',
	  `menu` varchar(100) NOT NULL DEFAULT '',
	  `link` varchar(100) DEFAULT NULL,
	  `feltime` int(11) DEFAULT NULL,
	  `feltolto` int(11) unsigned DEFAULT NULL,
	  PRIMARY KEY (`id`),
	  KEY `id_csoport` (`id_csoport`),
	  KEY `menu` (`menu`),
	  KEY `link` (`link`),
	  KEY `item` (`item`)
	) ENGINE=InnoDB DEFAULT CHARSET=latin1;");

$conn->execute_query("
	CREATE TABLE IF NOT EXISTS `b_menucsop` (
	  `b_menucsop_id` int(11) unsigned NOT NULL AUTO_INCREMENT,
	  `menu_id` int(11) NOT NULL DEFAULT '0',
	  `csoport_id` int(11) NOT NULL DEFAULT '1',
	  `csop_e` tinyint(1) unsigned DEFAULT NULL,
	  `feltolthet` tinyint(1) NOT NULL DEFAULT '0',
	  PRIMARY KEY (`b_menucsop_id`),
	  KEY `menu_id` (`menu_id`),
	  KEY `csoport_id` (`csoport_id`)
	) ENGINE=InnoDB DEFAULT CHARSET=latin1;");


$conn->execute_query("
	CREATE TABLE IF NOT EXISTS `b_nagycsoport_nevekcsop` (
		`b_nagycsoport_nevekcsop_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
		`b_nagycsoport_id` int(10) unsigned DEFAULT NULL,
		`nevek_csoport_id` int(10) unsigned DEFAULT NULL,
		PRIMARY KEY (`b_nagycsoport_nevekcsop_id`),
		KEY `b_nagycsoport_id` (`b_nagycsoport_id`,`nevek_csoport_id`),
		KEY `nevek_csoport_id` (`nevek_csoport_id`)
	) ENGINE=InnoDB DEFAULT CHARSET=latin1;");

$conn->execute_query("
	CREATE TABLE IF NOT EXISTS `b_modulok` (
	`b_modulok_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
	`azon` varchar(30) NOT NULL DEFAULT '',
	`verzio` varchar(20) NOT NULL DEFAULT '',
	`b_menu_id` int(11) NOT NULL DEFAULT 0,
	`friss_datum` date DEFAULT NULL,
	`friss_ido` time DEFAULT NULL,
	`modulnev` varchar(100) DEFAULT NULL,
	`rejtve` tinyint(1) NOT NULL DEFAULT 0,
	`csomag` varchar(100) DEFAULT NULL,
	`last_changed` datetime DEFAULT NULL,
	PRIMARY KEY (`b_modulok_id`),
	KEY `azon` (`azon`),
	KEY `rejtve` (`rejtve`)
	) ENGINE=MyISAM DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;");

$conn->close();
header("Location: bmain/install.php");
