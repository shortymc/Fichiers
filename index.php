<?php 
/*
   +-----------------+------------------------------------------------------------+
   |  Script         | CuteViewer                                                 |
   |  Author         | Ernesto Villarreal                                         |
   |  Modified by    | Mahel Plasson                                              |
   |  Last Modified  | September 2023                                             |
   +-----------------+------------------------------------------------------------+
   |  This program is free software; you can redistribute it and/or               |
   |  modify it under the terms of the GNU General Public License                 |
   |  as published by the Free Software Foundation; either version 2              |
   |  of the License, or (at your option) any later version.                      |
   |                                                                              |
   |  This program is distributed in the hope that it will be useful,             |
   |  but WITHOUT ANY WARRANTY; without even the implied warranty of              |
   |  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the               |
   |  GNU General Public License for more details.                                |
   |                                                                              |
   |  You should have received a copy of the GNU General Public License           |
   |  along with this program; if not, write to the Free Software                 |
   |  Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA  02111-1307, USA. |
   +------------------------------------------------------------------------------+
*/
	
	//	Configurable stuff
	$pass = sha1('votremotdepasse');// Change the password.
	$hiddenDirs = array('.', '..', '.htaccess', 'resources', 'fviewer.php', 'README.md', 'index.php', 'index-en.php', 'LICENSE'); // Add your own hidden directories
	$fileDir = 'resources/'; // If you the additional resources to another directory, change this variable
	
	session_start();
	ini_set('max_execution_time', 0);
	ini_set('upload_max_filesize', '200M');
	$tformat = "Y-m-d H:i:s";
	$copyd = '2009'.(date("Y", time())>2009?' - '.date("Y", time()):'');
	$out = $title = $ptitle = $js = '';
	$backUrl = '';
	
	$colors = array(
		'afa' => 'Vert',
		'faf' => 'Violet',
		'aff' => 'Bleu ciel',
		'aaf' => 'Bleu',
		'faa' => 'Rouge',
		'ffa' => 'Or',
		'fff' => 'Argent'
	);

	$select = '
			<select id="change-color">';
	
	$mdir = end(explode('/', dirname(__file__)));
	
	$dir = isset($_GET['d'])?stripslashes(trim($_GET['d'])):'.';
	
	$dir = preg_replace('%\.+%', '', $dir);
	$dir = $opendir = preg_replace('%/{2,}%', '', $dir);
	$dir = $opendir = preg_replace('%^/%', '', $dir);
	
	$dir = $opendir = $dir?$dir:'.';
	
	if(in_array(substr(str_replace('/', '', $dir), 0, strlen($mdir)), $hiddenDirs))
		$dir = $opendir = '.';
	
	$mode = isset($_GET['m'])?stripslashes($_GET['m']):'view';
	
	if(isset($_SESSION['pass'])){
		$l = $_SESSION['pass']==$pass;
	}else{
		$l = false;
	}
	
	$c = isset($_GET['c'])?$_GET['c']:'afa';
	$color = isset($_COOKIE['cl'])?$_COOKIE['cl']:$c;
	
	if(isset($_GET['c'])){
		$color = $c;
	}
	
	foreach($colors as $n => $c)
	{
		$select .=	'
				<option value="'.$n.'"'.($color==$n?'selected="selected"':'').'>'.$c.'</option>';
	}
	
	$select .= '			
	'.	'		</select>';
	
	$log =		'<div class="left pr pl imp">
	'.		'		'.($l?'Vous êtes connecté !':'Vous n\'êtes pas connecté!
	'.		'		<form action="?m=login&d='.$dir.'" method="post">
	'.		'			Mot de passe : <input type="password" name="pass" />&nbsp;
	'.		'			<input type="submit" value="Connexion !" />
	'.		'		</form>').'
	'.		'	</div>
	'.		'	<div class="right pl pr imp">
	'.		'		Couleur : '.$select.'
	'.		'	</div>';
	
	function dirsort($a, $b)
	{
		global $opendir;
		
		if(is_dir($opendir.'/'.$a))
			return -1;
		elseif(is_dir($opendir.'/'.$b))
			return 1;
		else
			return filemtime($opendir.'/'.$a)>filemtime($opendir.'/'.$b)?-1:1;
	}
	
	function mkname($m)
	{
		global $dir;
		return '<a href="'.$_SERVER['PHP_SELF'].'?d='.substr($dir, 0, strpos($dir, $m[1])+strlen($m[1])).'">'.$m[1].'</a>/';
	}
	
	function mkdirname($link)
	{
		global $mdir, $dir;
		
		$name = '/'.($link?'<a href='.$_SERVER['PHP_SELF'].'>'.$mdir.'</a>':$mdir).'/'.($dir=='.'?'':($link?preg_replace_callback('%(([0-9]|[A-Za-z]|[_\./-])+?)\/%', 'mkname', $dir.'/'):($dir.'/')));
		
		return $name;
	}
	
	function show_thumbnail($filename){
		$imgfiles = array('.gif', 'jpeg', '.jpg', '.png', 'apng', '.svg', '.bmp');
		
		return in_array(strtolower(substr($filename, -4)), $imgfiles)
			&& filesize($filename) < 1024000;
	}
	
	function display_filesize($bytes)
	{
		$units =	array(
						'<abbr title="bytes">bytes',
						'<abbr title="kilobytes">KB',
						'<abbr title="megabytes">MB',
						'<abbr title="gigabytes">GB',
						'<abbr title="terabytes">TB',
						'<abbr title="petabyte">PB'
					);
		
		$bytes = max($bytes, 0);
		$pow = floor(($bytes?log($bytes):0)/log(1024));
		$pow = min($pow, count($units)-1);

		$bytes /= pow(1024, $pow);

		return round($bytes, 2).' '.$units[$pow].'</abbr>';
	}
	
	function remove_dir($current_dir)
	{
		$current_dir = $current_dir.'/';
		
		if($dir = @opendir($current_dir))
		{
			while (($f = readdir($dir)) !== false)
			{
				if($f > '0' && filetype($current_dir.$f) == 'file')
					unlink($current_dir.$f);
				elseif($f > '0' && filetype($current_dir.$f) == 'dir')
					remove_dir($current_dir.$f.'\\');
			}
		}
		closedir($dir);
		rmdir($current_dir);
		return true;
	}
	
	function safe_filename($str)
	{
		$str = str_replace(' ', '_', $str);

		$result = '';
		for ($i=0; $i<strlen($str); ++$i)
			if(preg_match('([0-9]|[A-Za-z]|[_\./-])', $str{$i}))
				$result .= $str{$i};

		return $result;
	}
	
	if($mode == 'login')
	{
		$passhash = sha1($_POST['pass']);
		if($passhash==$pass)
		{
			$_SESSION['pass'] = $passhash;
			$log = 'Vous êtes connecté!';
			$l = true;
		}else{
			$js = '$("#showlog").click();';
		}
		
		$mode = 'view';
	}
	
	if($mode == 'color')
	{
		$ap = '(color changed)';
		$c = isset($_GET['c'])?$_GET['c']:'Blue';
		setcookie('cl', $c, time()+31556926);
		$color = $c;
		
		$mode = 'view';
	}
	
	switch($mode)
	{
		case 'view':
		default:
		
			$fcount = $dcount = $i = 0;
			$out = '';
			if(file_exists($opendir))
			{
				$noback = true;
				$files = array_diff(scandir($opendir), $hiddenDirs);
				usort($files, 'dirsort');
				
				foreach($files as $file)
				{
					$furl = str_replace('./', '', $dir.'/'.$file);
					$fname = !is_dir($furl)?$file:'/'.$file.'/';
					$out .=	'<tr class="d'.($i++%2).'">
			'.					'	<td>
			'.					'		<div class="left">
			'.					'			'.(show_thumbnail($furl)?'
			'.					'			<img src="'.$furl.'" alt="'.$fname.'" width="16px" height="16px" /> ':'').'
			'.					'			<a href="'.(is_dir($furl)?'?d='.$furl:$furl).'">'.$fname.'</a>
			'.					'		</div>'.($l?'<div class="right opts">
			'.					'			<a href="#" class="rename" id="'.$file.'" title="Rename '.$fname.'...">renommer</a>
			'.					'			- <a class="delete" title="Delete '.$fname.'..." href="?m=del&amp;f='.($furl).'">supprimer</a>
			'.					'		</div>':'').'
			/* '.					'	</td><td style="text-align: center;"> */
			'.					'		'.date($tformat, filemtime($furl)).'
			'.					'	</td><td>
			'.					'		'.(!is_dir($furl)?display_filesize(filesize($furl)):'&nbsp;').'
			'.					'	</td>
			'.					'</tr>';
					if(is_dir($furl)) ++$dcount; else ++$fcount;
				}
			
				$title = ' ('.$fcount.' fichier'.($fcount==1?'':'s').', '.$dcount.' dossier'.($dcount==1?'':'s').')';
				$ptitle =  mkdirname(false).$title;
				$title = mkdirname(true).$title;
				$opt = $l?'<a href="?m=upload&amp;d='.$dir.'">Envoyer des fichiers dans '.mkdirname(false).'</a><br /><a class="create-dir" href="#">Créer un répertoire</a>':'';
				$out =	'
	'.					'	'.($out?('<table id="list" cellpadding="0"><thead>
	'.					'		<tr class="d2">
	'.					'			<th class="h" title="Trier par nom de fichier/repertoire...">
	'.					'				Nom de dossier/fichier
	'.					'			</th><th class="h" width="200px" title="Trier par date...">
	'.					'				Dernière modification
	'.					'			</th><th width="70px" class="h" title="Trier par taille de fichier...">
	'.					'				Taille</th>
	'.					'		</tr>
	'.					'	</thead>
	'.					'	<tbody>
	'.					'		'.$out.'
	'.					'	</tbody></table>'):'
	'.					'	<div class="msg">
	'.					'			Il n\'y a pas aucun fichier dans ce repertoire.
	'.					'		</div>
	'.					'	').'
	'.					'';
				$js .=	'$("a.delete").click(function(){
	'.					'	return confirm("Etes vous sur ?\nCette action est irreversible !");
	'.					'});
	'.					'$("td").filter(":not(td.h)").hover(function(){
	'.					'	$(this).addClass("highlight").siblings().addClass("highlight2");
	'.					'}, function(){
	'.					'	$(this).removeClass("highlight").siblings().removeClass("highlight2");
	'.					'});
	'.					'$("a.rename").click(function(){
	'.					'	oldname = $(this).attr("id");
	'.					'	newname = prompt("Nouveau nom de fichier :", oldname);
	'.					'	if(newname && newname != oldname)
	'.					'		top.location="?m=rename&new='.$opendir.'/"+newname+"&old='.$opendir.'/"+oldname;
	'.					'	else
	'.					'		return false;
	'.					'});
	'.					'$("a.create-dir").click(function(){
	'.					'	dirname = prompt("Le nouveau dossier sera créé dans ce dossier.\nNom du dossier:", "");
	'.					'	if(dirname)
	'.					'		top.location="?m=createdir&new='.$opendir.'/"+dirname;
	'.					'	else
	'.					'		return false;
	'.					'});
	'.					'$("table#list").tablesorter(); ';
			}
			else
			{
				$title = $ptitle = 'Erreur: introuvable';
				$out = '<div class="msg">Le dossier n\'a pu être trouvé.</div>';
				$backUrl = 'javascript:history.back();';
			}
			
			break;
			
		case 'upload':
		
			$title = $ptitle = 'Envoyer les fichiers dans '.mkdirname(false);
			
			if(!$l)
				$out = 'Vous devez être connecté pour envoyer des fichiers.';
			elseif(!is_dir($dir))
			{
				$title = $ptitle = 'Erreur: Le dossier n\'a pu être trouvé';
				$out = '<div class="msg">Dossier non trouvé.</div>';
			}
			else
			{
				$js .=	'$("a#more").click(function(){
	'.					'	if(files<10){
	'.					'		$("input#submit").before("<input style=\'display: none;\' type=\'file\' id=\'file_"+(++files)+"\' name=\'file_"+files+"\' />");
	'.					'		$("input#file_"+files).fadeIn(100);
	'.					'		$("input#file_num").attr("value", files);
	'.					'		return false;
	'.					'	}
	'.					'});
	'.					'$("a#less").click(function(){
	'.					'	if(files>1){
	'.					'		$("input#file_"+(files--)).stop().
	'.					'		fadeOut(100, function(){
	'.					'			$(this).remove();
	'.					'		});
	'.					'		$("input#file_num").attr("value", files);
	'.					'		return false;
	'.					'	}
	'.					'});
	'.					'$("input#submit").click(function(){
	'.					'	$("span#info").hide().
	'.					'	html("Merci de patienter pendant l\'envoi de "+(files==1?"votre fichier":"vos fichiers")+" <span id=\'dotdotdot\'></span>").stop().
	'.					'	fadeIn(200);
	'.					'	ell(0);
	'.					'});';
				$out =	'<form id="upload_form" enctype="multipart/form-data" action="?m=uploading&amp;d='.$dir.'" method="post">
	'.					'	<div class="imp">
	'.					'		<a href="#" id="more" title="Plus de fichiers...">+</a>&nbsp;<a href="#" id="less" title="Moins de fichiers...">-</a>
	'.					'	</div>
	'.					'	<input type="hidden" name="file_num" id="file_num" value="1" />
	'.					'	<input type="file" name="file_1" id="file_1" />
	'.					'	<input id="submit" type="submit" value="Envoyer un ou des fichier(s)" />
	'.					'</form>
	'.					'<span id="info">&nbsp;</span>';
			}
			
			$backUrl = '?d='.$dir;
			$out = '<div class="msg">'.$out.'</div>';

			break;
			
		case 'Uploading':
		
			$title = $ptitle = 'Envoyer des fichiers vers '.mkdirname(false);
			
			if(!$l)
				$out = 'Vous devez être connecté pour envoyer des fichiers.';
			else
			{
				$filenum = $filenum?intval($_POST['file_num']):1;
				
				if(!$filenum)
					$out = 'Fichiers invalides.';
				else
				{
					$out = '';
					foreach($_FILES as $file)
					{
						$dir = $dir!=$mdir?$dir:'.';
						$path = $dir.'/'.basename(stripslashes(safe_filename($file['name']))); 
						if(!file_exists($path))
						{
							if(move_uploaded_file(stripslashes($file['tmp_name']), $path))
							    $out .= 'Le fichier "'.$path.'" à été envoyé avec succès !<br />';
							else
							    $out .= 'Une erreur est survenue pendant l\'envoi de ce fichier "'.$path.'"!<br />';
						}
						else
							$out .= 'Le fichier "'.$path.'" est déjà présent !<br />';
					}
				}		
			}
			
			$out = '<div class="msg">'.$out.'</div>';
			$backUrl = '?d='.$dir;
			
			break;
		
		case 'del':
			
			$title = $ptitle = 'Supprimer le fichier/repertoire';
			
			if(!$l)
				$out = 'Vous devez être connecté pour envoyer des fichiers.';
			else
			{
				$file = stripslashes($_GET['f']);
			
				if(!$d || strstr($file, 'index.php'))
					$out = 'Ce nom de fichier est invalide !';
				else
				{
					if(is_dir($file))
					{
						if(remove_dir($file))
							$out = 'Le repertoire "'.$file.'" à été supprimé avec succès !!';
						else
							$out = 'Une erreur est survenue pendant la tentative de suppression de ce dossier "'.$file.'"!';
					}
					else
					{
						if(file_exists($file))
						{
							if(unlink($file))
								$out = 'Le fichier "'.$file.' à été supprimé avec succès !';
							else
								$out = 'Une erreur est survenue pendant la tentative de suppression de ce dossier "'.$file.'"!';
						}
					}
				}
			}
			
			$out = '<div class="msg">'.$out.'</div>';
			$backUrl = 'javascript:history.back();';

			break;
		
		case 'rename':
			
			$oldname = stripslashes($_GET['old']);
			$newname = safe_filename(stripslashes($_GET['new']));
			$title = $ptitle = 'Renomer le fichier/repertoire';
			
			if(!$l)
				$out = 'Vous devez être connecté pour renommer des fichiers/repertoires.';
			elseif(!$newname)
				$out = 'Ce nom de fichier est invalide !';
			elseif(is_dir($oldname))
			{
				if(!is_dir($newname))
				{
					if(rename($oldname, $newname))
						$out = 'Le dossier "'.$oldname.'" à été rennommé en "'.$newname.'" avec succès !';
				}
				else
				{
					$title = $ptitle = 'Erreur : ce dossier existe déjà';
					$out = 'Un dossier nommé "'.$newname.'" est déjà présent.';
				}
			}
			elseif(file_exists($oldname))
			{
				if(!file_exists($newname))
				{
					if(rename($oldname, $newname))
						$out = 'Le fichier "'.$oldname.'" à été renommé "'.$newname.'" avec succès !';
					else
						$out = 'Une erreur est survenue pour renommer le fichier "'.$oldname.'" en "'.$newname.'"!';
				}
				else
				{
					$title = $ptitle = 'Erreur: le fichier existe';
					$out = 'Un fichier nommé "'.$newname.'" existe déjà.';
				}
			}
			else
				$out = 'Fichier non trouvé.';
			
			$backUrl = '?d='.$dir;
			
			break;
		
		case 'createdir':
			
			$name = safe_filename(stripslashes($_GET['new']));
			$title = $ptitle = 'Créer un répertoire';
			
			if(!$l)
				$out = 'Vous devez être connecter pour créer des dossiers.';
			elseif(!$name)
				$out = 'Ce nom de dossier/fichier est invalide!';
			elseif(is_dir($name))
			{
				$title = $ptitle = 'Erreur: ce dossier existe';
				$out = 'Un dossier nommé "'.$name.'" existe déjà.';
			}
			else
			{
				if(mkdir($name))
					$out = 'Le répertoire "'.$name.'" à été créé avec succès !';
				else
					$out = 'Une erreur est survenue en essayant de créer le dossier "'.$name.'"!';
			}
			
			$backUrl = '?d='.$name;
			$out = '<div class="msg">'.$out.'</div>';
			
			break;
	}
	
	$out =	'
	'.$out.(isset($noback)?'':'<br /><a href="'.$backUrl.'">Go back.</a>').'
';
	$log =	'
	'.(!empty($opt)?'<div class="imp left pl">
	'.		'	'.$opt.'
	'.		'</div>
	'.		'<div class="imp right pr">
	'.		'	'.$log.'
	'.		'</div>':$log).'
';
	$js =	'<script type="text/javascript"><!--
	'.		'var files = 1, fadeOut = 0;
	'.		'function ell(n){
	'.		'	switch(n){
	'.		'		case 0: case 1: n++; break;
	'.		'		case 2: n = 0;
	'.		'	}
	'.		'	dots = "";
	'.		'	for(i=0;i-1<n;i++)
	'.		'		dots=dots+".";
	'.		'	$("#dotdotdot").text(dots);
	'.		'	setTimeout("ell("+n+")", 500);
	'.		'}
	'.		'$(function(){
	'.		'	$(document).mouseup(function(e){
	'.		'	if($(e.target).parents("#log").length==0&&$(e.target).filter("#log").length==0)
	'.		'		$("#log").slideUp("fast");
	'.		'	});
	'.		'	$("#showlog").click(function(){
	'.		'		$("#log").slideDown("fast");
	'.		'		return false;
	'.		'	});
	'.		'	$("#change-color").change(function(){
	'.		'		top.location="?d='.$dir.'&m=color&c="+$("#change-color option:selected").val();
	'.		'	});
	'.		'	'.$js.'
	'.		'}); //-->
	'.		'</script>
';

; ?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" 
        "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd"> 
<html xmlns="http://www.w3.org/1999/xhtml"> 
<head> 
	<title><?php echo $ptitle; ?></title> 
	<meta http-equiv="Content-Type" content="text/html;charset=utf-8" /> 
	<meta name="Author" content="Kaeru" />
	<link rel="stylesheet" type="text/css" media="handheld" href="<?php echo $fileDir; ?>fstyle.php?c=<?php echo $color; ?>&handheld=1" />
	<link rel="stylesheet" type="text/css" media="screen" href="<?php echo $fileDir; ?>fstyle.php?c=<?php echo $color; ?>" />
	<script type="text/javascript" src="<?php echo $fileDir; ?>jquery-1.6.2.min.js"></script>
	<script type="text/javascript" src="<?php echo $fileDir; ?>tablesorter.js"></script>
	<?php echo $js; ?>
</head>
<body>
<div id="wrapper">
	<div class="box">
		<h1><a id="showlog" href="#" title="Afficher/Cacher les options...">**</a> <?php echo $title; ?></h1>
		<div id="log"><?php echo $log; ?></div>
		<div id="main"><?php echo $out; ?></div>
	</div>
	<div id="push"></div>
</div>
<div id="foot">
	<p><a >CuteViewer 2.0.1</a> &copy; <?php echo $copyd; ?> Ernesto Villarreal ~ adapted by shorty - <a align="center" href="https://www.tradezone.fr">Accueil</a></p>
</div>
</body>
</html>
