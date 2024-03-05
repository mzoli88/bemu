<?php
header('Content-Type: text/html; charset=UTF-8');
?>
<!DOCTYPE html>
<html>
  <head>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
	<style>
		body{
			display: flex;
			justify-content: flex-start;
			flex-wrap: wrap;
			background-color: #2a6d9e29;
		}
		
		.box{
			position:relative;
			padding:0;
			margin: 4px;
			color: #17517b;
			min-width: 300px;
			border-radius: 6px;
		}
		
		.box_title{
			background-color: #fff;
			border-radius: 6px;
			color: #157fcc;
			background-color: #fff;
			padding:4px;
			cursor: pointer;
			background-color: #fff;
			border: 1px solid #aaa;
		}
		
		.box_content{
			background-color: #fff;
			overflow: auto;
			display:none;
			z-index: 1000;
			border: 1px solid #aaa;
			min-width: 300px;
			max-height: 300px;
			position: absolute;
			top: 35px;
		}

		.box:focus .box_content
		{
			display: block;
		}

		.box h3{
			margin: 0;
		}

		.box ul{
			padding:0 20px;
			margin: 6px;
		}
		
		.box li{
			margin-top: 5px;
		}
		
		a{
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
		a.helplink{
			position: absolute;
			top: 6px;
			right: 10px;
		}
	</style>
	<script>
window.onload = function() {
	var boxes = document.querySelectorAll('.box');
	
	for(var i in boxes){
		if(boxes[i].classList){
			boxes[i].addEventListener('blur', function(e){
				if(e.target.contains(e.relatedTarget)){
					e.target.focus();
				}
			});
		}
	}
};
	</script>
    <title>Menü</title>
	</head>
  <body>
	
	<?php
		
		if(isset($_SERVER['REQUEST_URI'])){
			$tmp = preg_split('/(lib\/)|(mod\/)/',$_SERVER['REQUEST_URI']);
			define('BORDER_PATH_URL', array_shift($tmp));
			unset($tmp);
		}

		require_once('config.class.php');
		emu_user_init();
	
		$path = BORDER_PATH_BORDER."mod" .DIRECTORY_SEPARATOR;
		
		function to_utf8($str){
			$str = iconv(mb_detect_encoding($str, mb_detect_order(), true), "UTF-8", $str);
			return str_replace(['õ','Õ','û','Û'],['ő','Ő','ű','Ű'],$str);
		}
		
		$folders = scandir($path);
		if($folders){
			$files=[];
			
			foreach ($folders as $result) {
				if ($result === '.' or $result === '..') continue;
				$result = to_utf8($result);
				if (is_dir($path . DIRECTORY_SEPARATOR . $result)) {
	
					echo '<div class="box" tabindex="0"><div class="box_title"><h3><a target="_BLANK" href="'. BORDER_PATH_URL .'mod/' . $result.'/index.php">'.$result.'</a></h3></div></div>';
					
				}else{
					$files[] = '<a href="'.BORDER_PATH_URL.'mod/'.$result."\">".$result.'</a>';
				}
			}
			if(!empty($files)){
				echo '<div class="box" tabindex="0"><div class="box_title"><h3>Fájlok</h3></div><div class="box_content"><ul>';
				foreach ($files as $row) {
					echo '<li>'.$row."</li>";
				}
				echo '</ul></div></div>';
			}
		}
		exit();
	?>
  </body>
	
</html>