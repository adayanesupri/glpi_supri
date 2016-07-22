<?php
/*
 * @version $Id: computer.form.php 17307 2012-01-31 13:10:42Z yllen $
 -------------------------------------------------------------------------
 GLPI - Gestionnaire Libre de Parc Informatique
 Copyright (C) 2003-2012 by the INDEPNET Development Team.

 http://indepnet.net/   http://glpi-project.org
 -------------------------------------------------------------------------

 LICENSE

 This file is part of GLPI.

 GLPI is free software; you can redistribute it and/or modify
 it under the terms of the GNU General Public License as published by
 the Free Software Foundation; either version 2 of the License, or
 (at your option) any later version.

 GLPI is distributed in the hope that it will be useful,
 but WITHOUT ANY WARRANTY; without even the implied warranty of
 MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 GNU General Public License for more details.

 You should have received a copy of the GNU General Public License
 along with GLPI. If not, see <http://www.gnu.org/licenses/>.
 --------------------------------------------------------------------------
 */

// ----------------------------------------------------------------------
// Original Author of file:
// Purpose of file:
// ----------------------------------------------------------------------

define('GLPI_ROOT', '..');
include (GLPI_ROOT . "/inc/includes.php");

//Session::checkRight("computer", "r");

if (!isset($_GET["id"])) {
   $_GET["id"] = "";
}

if (!isset($_GET["sort"])) {
   $_GET["sort"] = "";
}

if (!isset($_GET["order"])) {
   $_GET["order"] = "";
}

if (!isset($_GET["withtemplate"])) {
   $_GET["withtemplate"] = "";
}

$carkit = new Cartridge_Kit_Supridesk();

//Add a new computer
if (isset($_POST["add"])) {
   //$computer->check(-1, 'w', $_POST);
   if ($newID = $carkit->add($_POST)) {
      //Event::log($newID, "computers", 4, "inventory",
        //         $_SESSION["glpiname"]." ".$LANG['log'][20]." ".$_POST["name"].".");
   }
   Html::back();

// delete a computer
} else if (isset($_POST["delete"])) {
   $okItems = $carkit->delCartuchoKit($_POST["id"]);
   if ($okItems) {
      $ok = $carkit->delete($_POST);
   }
   $carkit->redirectToList();

} else if (isset($_POST["update"])) {
   //$computer->check($_POST['id'], 'w');
   $carkit->update($_POST);
   //Event::log($_POST["id"], "computers", 4, "inventory",
   //           $_SESSION["glpiname"]." ".$LANG['log'][21]);*/
   Html::back();

// Disconnect a computer from a printer/monitor/phone/peripheral
} else if ($_POST['rmvT']==1) {//isset($_POST["removerTodos"])) {die("removerTodos");
	//TODO: checar se pode editar usuário
   //$cartype->check($_POST["cartridgeitems_id"],'w');
   $carkit->delCartuchoKit( $_POST["uID"] );
   Html::back();

} else if ($_POST['rmvT']==2) {//isset($_POST["removerSelecionados"])) {die("removerSelecionados");
	//TODO: checar se pode editar usuário
   //$cartype->check($_POST["cartridgeitems_id"],'w');

	foreach($_POST as $k => $v) {
		if (strpos($k, 'sRemove_') !== false)
		{
			$_cartridge_ids = substr($k, strlen("sRemove_"));
         
			if ($v > 0 && $carkit->delCartuchoKit( $_POST["uID"], $_cartridge_ids, $v ) )
			{
				//Event::log($_POST["tID"], "cartridges", 4, "inventory", $_SESSION["glpiname"]." ".$LANG['log'][30]);
			}
		}
   }
   Html::back();

} else if (isset($_POST["addCartucho"])) {
   //$carkit->check($_POST["cartridgeitems_id"],'w');

   if ($carkit->addCartuchoKit($_POST["kitAddCartridge"], $_POST["cartridgeitems_id"], $_POST["uID"])) {
      //Event::log($_POST["tID"], "cartridges", 4, "inventory", $_SESSION["glpiname"]." ".$LANG['log'][30]);
   }
   Html::back();

// Disconnect a computer from a printer/monitor/phone/peripheral
} else {//print computer informations
   Html::header($LANG['Menu'][103], $_SERVER['PHP_SELF'], "inventory", "cartridge_kit");
   //show computer form to add
   $carkit->showForm($_GET["id"]);
   Html::footer();
}
?>
