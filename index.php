<?php
// header('Content-Type: text/html; charset=UTF-8');
if(!is_file( __DIR__ . DIRECTORY_SEPARATOR . 'mod' . DIRECTORY_SEPARATOR . '.htaccess')){
	file_put_contents(__DIR__ . DIRECTORY_SEPARATOR . 'mod' . DIRECTORY_SEPARATOR . '.htaccess',"php_value include_path ".__DIR__ . DIRECTORY_SEPARATOR . "lib\n");
	header("Refresh:0");
	die;
}else{
	file_put_contents(__DIR__ . DIRECTORY_SEPARATOR . 'mod' . DIRECTORY_SEPARATOR . '.htaccess',"php_value include_path ".__DIR__ . DIRECTORY_SEPARATOR . "lib\n");
}

require_once('lib/config.class.php');
emu_user_init();
require_once('lib/belepve.php');

if(isset($_SERVER['REQUEST_URI'])){
	$tmp = preg_split('/(lib\/)|(mod\/)/',$_SERVER['REQUEST_URI']);
	define('BORDER_PATH_URL', array_shift($tmp));
	unset($tmp);
}
?>
<!DOCTYPE html>
<html>
  <head>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
	<link rel="shortcut icon" type="image/png" href="favicon.ico"/>
    <title>BORDER Emulator 2</title>
	<link rel="stylesheet" type="text/css" href="lib/sh.css">
	<script src="lib/jquery-1.12.4.min.js"></script> 
  </head>
  <body>
	<div>
		<div class="main_page noselect">
			<div id="emuheader">
				<div class="page_header">
					BORDER Emulátor 2
					<div class="page_version">
						<span>v1.8.0</span>
						<span>PHP:<?=phpversion()?></span>
						<span class="doselect"><a href="http://<?=getHostByName(getHostName())?>"><?=getHostByName(getHostName())?></a></span>
						<span><?=iconv('ISO-8859-2', 'UTF-8',$_SESSION['teljesnev'])?></span>
					</div>
				</div>
				<div id="tab_menu" class="tab_menu"></div>
			</div>
			<div id="iframes"></div>
		</div>
		<script>
		//tab panel váltogatás
		var Ext = {};
		Ext.idCounter = 1;
		var base_url = "<?=BORDER_PATH_URL?>";
		Ext.menustart = {
			// 'menu_admin': ['Border',base_url+"mod/emu_vue/build/"],
			'menu_adminer': ['Adminer',base_url+"adminer/"],
			// 'menu_naplo': ['Napló',base_url+"mod/naplo_emu/build/"],
			'menu_menu': ['Menü',base_url+"lib/menu.php"]
		};
		top.Ext.enableTabReload = true;
		Ext.menu_admin = $("#menu_admin");
		//aktív iframe magasságának frissítése
		Ext.loadLayout = function(bom){
			Ext.getActiveFrame().height(window.innerHeight - $("#emuheader").height() - 25);
		};
		
		Ext.createNewTab = function(item){
			item = $(item);
			if(item.text() === '+'){
				Ext.setTitle('Menü', item);
				Ext.createTab(base_url+"lib/menu.php",null,'+',true,false,Ext.createNewTab);				
			}
		};
		
		Ext.getActiveBtn = function(){
			return $("#tab_menu .active");
		};
		
		Ext.getActiveFrame = function(){
			return $("#iframes .active");
		};
		
		$(window).on('resize', Ext.loadLayout ).trigger('resize');

		Ext.setTitle = function(newtitle,item){
			if(item){
				item.text(newtitle);
				return;
			}
			$('#tab_menu .tbtn').each(function(i,tbtn){
				var title = document.getElementById(tbtn.id + '_if').contentDocument.title;
				if(title && $(tbtn).text() !== title && $(tbtn).text()!=='Adminer')$(tbtn).text(title);
			});
		};
		//menü plugin tudja extet használni
		Ext.getCmp = function(id){
			return {
				getActiveTab:function(){
					return {
						title: 'asdf',
						setTitle: function(newtitle){
							Ext.setTitle(newtitle);
						}
					};
				}
			};
		};
		
		Ext.setActive = function(dom,new_tab){
			var me = this,
				aframe = $('#'+dom.id+'_if');
			if(new_tab){
				var url = aframe.data('src') || aframe.attr('src');
				window.open(url, '_blank')
				return;
			}
			if($(dom).hasClass('active')){
				Ext.enableTabReload = false;
				Ext.getActiveFrame().get(0).contentWindow.location.reload();
				return false;
			}
			
			Ext.getActiveFrame().removeClass('active');
			aframe.addClass('active');
			
			if (aframe.data('src'))aframe.attr('src', aframe.data('src')).data('src', false);
			
			Ext.getActiveBtn().removeClass('active');
			$(dom).addClass('active');
			Ext.loadLayout();
		};
		
		Ext.createTab = function(url,id,title,nofocus,newWindow,clickFn){
			var newItem = document.createElement("div"),
				newIframe;
			$(newItem).addClass('tbtn');
			if(newWindow){
				newItem.id= id||"mframe_"+Ext.idCounter;
				newItem.innerHTML = '<a href="'+url+'" target="_blank" style="text-decoration: none;color:inherit;">'+title+'</a>';
				document.getElementById("tab_menu").appendChild(newItem);
				return;
			}
			newIframe = document.createElement("iframe");
			newItem.id= id||"mframe_"+Ext.idCounter;
			newIframe.id= id ? id+"_if" : false||"mframe_"+Ext.idCounter+"_if";
			Ext.idCounter++;
			newIframe.setAttribute('data-src',url);
			newItem.textContent = title || "Modul";
			$(newItem).on("mousedown",function(e) {
				if(typeof clickFn === 'function'){
					clickFn(this);
				}
				if(e.which <= 2)Ext.setActive(this,e.ctrlKey || e.which === 2 );
			});
			document.getElementById("tab_menu").appendChild(newItem);
			document.getElementById("iframes").appendChild(newIframe);
			if(!nofocus)Ext.setActive(newItem);
			return newItem;
		};
		var last;
		for(var i in Ext.menustart){
			last = Ext.createTab(Ext.menustart[i]['1'],i,Ext.menustart[i]['0'],true,Ext.menustart[i]['2']);
		}
		Ext.setActive(last);
		Ext.createTab(base_url+"lib/menu.php",null,'+',true,false,Ext.createNewTab);
		
		window.onbeforeunload = function(){
			top.Ext.enableTabReload = false;
		};
		</script>
		</div>
  </body>
</html>