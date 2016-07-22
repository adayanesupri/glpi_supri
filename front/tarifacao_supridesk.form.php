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

$c = new Contract();
$t = new Tarifacao_Supridesk();

if (!isset($_GET["id"])) {
   $_GET["id"] = "";
}

if (isset($_POST["action"]) && $_POST["action"] == 'delete') {
   Session::checkRight("contract", "w");

	$hasError = false;
	$countError = 0;
   if (isset($_POST["del_relatorio"]) && count($_POST["del_relatorio"])) {
      foreach ($_POST["del_relatorio"] as $relatorio_id => $val) {
         if ($t->can($relatorio_id,'d')) {
            $t->delete(array("id" => $relatorio_id));
         }
			else
			{
				$hasError = true;
				$countError++;
			}
      }
   }

	if ( $hasError )
	{
		$msg = "$countError relatório não pôde ser excluído devido às regras de exclusão.";
		if ( $countError > 1 )
			$msg = "$countError relatórios não puderam ser excluídos devido às regras de exclusão.";
		Html::displayErrorAndDie( $msg );
	}
}else if (isset($_POST["action"]) && $_POST["action"] == 'faturar') {
   Session::checkRight("contract", "w");

	$hasError = false;
	$countError = 0;
   if (isset($_POST["del_relatorio"]) && count($_POST["del_relatorio"])) {
      foreach ($_POST["del_relatorio"] as $relatorio_id => $val)
		{
			$t->getFromDB($relatorio_id);
         if ($t->canFaturar()) {
            $t->faturar();
         }
			else
			{
				$hasError = true;
				$countError++;
			}
      }
   }

	if ( $hasError )
	{
		$msg = "$countError relatório não pôde ser faturado devido às regras de faturamento.";
		if ( $countError > 1 )
			$msg = "$countError relatórios não puderam ser faturados devido às regras de faturamento.";
		Html::displayErrorAndDie( $msg );
	}
}
Html::back();
?>