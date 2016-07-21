<?php
/*//SUPRISERVICE*/
if (strpos($_SERVER['PHP_SELF'],"dropdownCartridgeKit.php")) {
   define('GLPI_ROOT','..');
   include (GLPI_ROOT."/inc/includes.php");
   header("Content-Type: text/html; charset=UTF-8");
   Html::header_nocache();
}
if (!defined('GLPI_ROOT')) {
   die("Can not acces directly to this file");
}

//$count = Cartridge::getUnusedNumber($_POST['value']);
$count = 20;

echo "<select name='kitAddCartridge' size=1>";
for($k = 1; $k <= $count; $k++)
{
	echo "<option value='$k'>$k</option>";
}
echo "</select>";

?>