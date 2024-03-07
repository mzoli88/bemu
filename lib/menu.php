<?php
header('Content-Type: text/html; charset=UTF-8');
?>
<!DOCTYPE html>
<html>

<head>
	<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
	<style>
		body {
			display: flex;
			justify-content: flex-start;
			flex-wrap: wrap;
			background-color: #2a6d9e29;
		}

		.box {
			position: relative;
			padding: 0;
			margin: 4px;
			color: #17517b;
			min-width: 300px;
			border-radius: 6px;
		}

		.box_title {
			background-color: #fff;
			border-radius: 6px;
			color: #157fcc;
			background-color: #fff;
			padding: 4px;
			cursor: pointer;
			background-color: #fff;
			border: 1px solid #aaa;
		}

		.box h3 {
			margin: 0;
		}

		a {
			color: #17517b;
		}

		a:link {
			text-decoration: none;
		}

		a:visited {
			text-decoration: none;
		}

		a:hover {
			text-decoration: underline;
		}

		a:active {
			text-decoration: underline;
		}

		a.helplink {
			position: absolute;
			top: 6px;
			right: 10px;
		}
	</style>
	<title>Menü</title>
</head>

<body>
	<?php

	use App\Border3;
	use Illuminate\Support\Facades\DB;

	function refreshModulData()
	{
		$badmin = DB::table('nevek_csoport')->where('csoport_id', 2)->first();
		if (!$badmin) {
			DB::table('nevek_csoport')->insert([
				'csoport_id' => 2,
				'nev' => 'Rendszer - Admin',
				'modul_azon' => 'admin',
			]);
		}

		collect(config('mods'))->each(function ($modul, $modul_azon) {
			$modulData = DB::table('b_modulok')->where('azon', $modul_azon)->first();
			if (!$modulData) {
				DB::table('b_modulok')->insert([
					'azon' => $modul_azon,
					'modulnev' => $modul['name'],
					'verzio' => 'v' . $modul['version'],
				]);
			}

			// $modul = config('mods')[getModulAzon()];
			$perms = [];

			if (array_key_exists('perms', $modul)) {
				foreach ($modul['perms'] as $key => $value) $perms[$key] = $modul['name'] . ' - ' . $value;
			}

			if (array_key_exists('menu', $modul)) {
				foreach ($modul['menu'] as $key => $value) $perms[$key] = $modul['name'] . ' - ' . $value['name'] . ' (menüpont)';
			}

			collect($perms)->each(function ($jog) use ($modul_azon) {
				$ellenorzendo = conv($jog);
				$equery = DB::table('nevek_csoport')->where('nev', $ellenorzendo);
				$result = $equery->first();
				if (!$result) {
					DB::table('nevek_csoport')->insert([
						'nev' => $ellenorzendo,
						'modul_azon' => $modul_azon,
					]);
				}
			});
		});


		$jog_id = Border3::getJogId('Rendszer - Felhasználó kezelés (menüpont)');
		$result = DB::table('nevek_csoportosit')->where('nevek_id', getUserId())->where('csoport_id', $jog_id)->first();
		if (!$result) DB::table('nevek_csoportosit')->insert(['nevek_id' => getUserId(), 'csoport_id' => $jog_id,]);

		$jog_id = Border3::getJogId('Rendszer - Szerepkör kezelés (menüpont)');
		$result = DB::table('nevek_csoportosit')->where('nevek_id', getUserId())->where('csoport_id', $jog_id)->first();
		if (!$result) DB::table('nevek_csoportosit')->insert(['nevek_id' => getUserId(), 'csoport_id' => $jog_id,]);

		$jog_id = Border3::getJogId('Rendszer - Modulok (menüpont)');
		$result = DB::table('nevek_csoportosit')->where('nevek_id', getUserId())->where('csoport_id', $jog_id)->first();
		if (!$result) DB::table('nevek_csoportosit')->insert(['nevek_id' => getUserId(), 'csoport_id' => $jog_id,]);

	}

	if (isset($_SERVER['REQUEST_URI'])) {
		$tmp = preg_split('/(lib\/)|(mod\/)/', $_SERVER['REQUEST_URI']);
		define('BORDER_PATH_URL', array_shift($tmp));
		unset($tmp);
	}

	define('LARAVEL_START', microtime(true));
	date_default_timezone_set('Europe/Budapest');
	require 'bmain/vendor/autoload.php';
	require_once('config.class.php');
	// emu_user_init();
	$app = require_once 'bmain/bootstrap/app.php';
	$kernel = $app->make(\Illuminate\Contracts\Http\Kernel::class);
	$response = $kernel->handle(
		$request = \Illuminate\Http\Request::capture()
	);

	refreshModulData();

	collect(config('mods'))->each(function ($mod, $azon) {
		echo '<div class="box" tabindex="0"><div class="box_title"><h3><a target="_BLANK" href="';
		echo BORDER_PATH_URL . 'mod/bmain/public/#' . $azon . '/index.php">';
		echo $mod['name'] . ' (v' . $mod['version'] . ")";
		echo '</a></h3></div></div>';
	});
	?>
</body>

</html>