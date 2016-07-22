<?php
// Direct access to file
if (strpos($_SERVER['PHP_SELF'],"activeentity.php")) {
   define('GLPI_ROOT','../../..');
   include (GLPI_ROOT."/inc/includes.php");
   header("Content-Type: text/html; charset=UTF-8");
   Html::header_nocache();
include(GLPI_ROOT."/plugins/mobile/inc/ajax.function.php");
}

if (!defined('GLPI_ROOT')) {
   die("Can not acces directly to this file");
}

Session::checkLoginUser();

// Non define case
//print_r($_POST);

if (isset($_POST["value"]) ) {
	$_SESSION["glpiactive_entity"] = $_POST["value"];
	$entity = new Entity();
	$entity->getFromDB($_SESSION["glpiactive_entity"]);
	echo "Entidade ativa selecionada: " . $entity->fields["completename"];
}
?>
