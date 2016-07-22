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

$st  = new SolutionType();
$stst = new SolutionType_SolutionTemplates();

if (!isset($_GET["id"])) {
   $_GET["id"] = "";
}

if (isset($_POST["vincular"]))
{
	$st->check(-1,'w',$_POST);
	$ststID = $stst->add($_POST);

	Html::redirect($CFG_GLPI["root_doc"].'/front/solutiontype.form.php?id='.$_POST['solutiontypes_id']);

}else if (isset($_POST["action"]) && $_POST["action"] == 'desvincular_solucao') {
	$st->check(-1,'w',$_POST);

	foreach ($_POST["del_STST"] as $STST_ID => $val) {
		$input = Array();
		$input['id'] = $STST_ID;
		$stst->delete($input);
	}
   $stst->redirectToList();
}
else
{
   if (empty($_GET["solutiontypes_id"])) {
      $_GET["solutiontypes_id"] = "";
   }
   if (empty($_GET["itemtype"])) {
      $_GET["itemtype"] = "";
   }
   if (empty($_GET["several"])) {
      $_GET["several"] = "";
   }

   Session::checkRight("solutiontypes", "w");
   Html::header($LANG['custom_chamado'][9],$_SERVER['PHP_SELF'],"inventory");

   $st->showForm($_GET["id"], $_GET);
   Html::footer();
}
?>