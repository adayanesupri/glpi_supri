<?php
/*
 * @version $Id: networkport.form.php 18718 2012-06-26 06:51:50Z moyo $
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

$fipl  = new Fusioninventory_Printerlogs();

if (!isset($_GET["id"])) {
   $_GET["id"] = "";
}

if (isset($_POST["add"]))
{
	//$fipl->check(-1,'w',$_POST);
	$_POST["import_mode"] = "MANUAL";
	$_POST["pages_total"] = $_POST["pages_n_b"] + $_POST["pages_color"];
	$fiplID = $fipl->add($_POST);

	Html::redirect($CFG_GLPI["root_doc"].'/front/printer.form.php?id='.$_POST['printers_id']);
} else if (isset($_POST["update"]))
{
   $fipl->check($_POST['id'],'w');

	$_POST["pages_total"] = $_POST["pages_n_b"] + $_POST["pages_color"];

   $fipl->update($_POST);
   //Event::log($_POST["id"], "contractitem", 4, "inventory", $_SESSION["glpiname"]." ".$LANG['log'][21]);
   Html::back();

} else if (isset($_POST["action"]) && $_POST["action"] == 'delete') {
   Session::checkRight("printer", "w");

   if (isset($_POST["printerlogs"]) && count($_POST["printerlogs"])) {
      foreach ($_POST["printerlogs"] as $printerlogsID => $val) {
			$fipl->getFromDB($printerlogsID);
			//contadores automáticos não podem ser deletados
			if ( $fipl->fields["import_mode"] == "FM")
				continue;
			$fipl->delete($fipl->fields, 0, true);
      }
   }
   //Event::log(0, "networkport", 5, "inventory", $_SESSION["glpiname"]." ".$LANG['log'][74]);
   Html::back();
}
else
{
   if (empty($_GET["printers_id"])) {
      $_GET["printers_id"] = "";
   }
   if (empty($_GET["itemtype"])) {
      $_GET["itemtype"] = "";
   }

   Session::checkRight("contract", "w");
   Html::header($LANG['tarifacao'][17],$_SERVER['PHP_SELF'],"billing","contract");

   $fipl->showForm($_GET["id"], $_GET);
   Html::footer();
}
?>