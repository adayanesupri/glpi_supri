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

$ic  = new ITILCategory();
$icst = new ITILCategory_SolutionTypes();

if (!isset($_GET["id"])) {
   $_GET["id"] = "";
}

if (isset($_POST["vincular"]))
{
	$ic->check(-1,'w',$_POST);
	$icstID = $icst->add($_POST);

	Html::redirect($CFG_GLPI["root_doc"].'/front/itilcategory.form.php?id='.$_POST['itilcategories_id']);

}else if (isset($_POST["action"]) && $_POST["action"] == 'desvincular_diagnostico') {
	$ic->check(-1,'w',$_POST);

	foreach ($_POST["del_ICTS"] as $ICST_ID => $val) {
		$input = Array();
		$input['id'] = $ICST_ID;
		$icst->delete($input);
	}
   $icst->redirectToList();
}
else
{
   if (empty($_GET["itilcategories_id"])) {
      $_GET["itilcategories_id"] = "";
   }
   if (empty($_GET["itemtype"])) {
      $_GET["itemtype"] = "";
   }
   if (empty($_GET["several"])) {
      $_GET["several"] = "";
   }

   Session::checkRight("itilcategories", "w");
   Html::header($LANG['custom_chamado'][3],$_SERVER['PHP_SELF'],"inventory");

   $ic->showForm($_GET["id"], $_GET);
   Html::footer();
}
?>