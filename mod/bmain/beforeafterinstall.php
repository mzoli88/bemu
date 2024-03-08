<?php

//csomag telepítésekor futtatott kód
class beforeafterinstall
{

	public function before($oldversion = '', $newversion = '')
	{
	}

	public function after($oldversion = '', $newversion = '')
	{
	}

	public function __destruct()
	{
		header('Location: ../mod/bmain/init.php');
		exit;
	}
}