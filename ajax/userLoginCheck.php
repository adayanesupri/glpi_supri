<?php
/*//SUPRISERVICE*/
//Checa se um login já existe
$AJAX_INCLUDE = 1;

define('GLPI_ROOT','..');
include (GLPI_ROOT."/inc/includes.php");
header("Content-Type: text/html; charset=UTF-8");
Html::header_nocache();

Session::checkLoginUser();

$pass = "&nbsp;&nbsp;<span style='color:green;font-weight=bold;'>login válido</span>";
$fail = "&nbsp;&nbsp;<span style='color:red;font-weight=bold;'>login já existente</span>";

if (isset($_POST['login']) && User::getIdByName($_POST['login']) !== false )
	echo $fail;
else
	echo $pass;
?>