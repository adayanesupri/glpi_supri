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

$a  = new Agrupamento_Supridesk();
$ap = new Agrupamento_Printer_Supridesk();

if (!isset($_GET["id"])) {
   $_GET["id"] = "";
}

if (isset($_POST["add"]))
{
	$a->check(-1,'w',$_POST);
	$aID = $a->add($_POST);

	//grava histórico de inclusão de agrupamento no contrato
	$a->getFromDB($aID);
	$changes[0] = '0';
	$changes[1] = '';
	$changes[2] = $LANG['agrupamento'][10] . " ({$aID})";
	Log::history( $_POST['contracts_id'], 'Contract', $changes, 'Agrupamento_Supridesk', Log::HISTORY_LOG_SUPRISERVICE_MESSAGE);
	
	//grava histórico de criação no agrupamento do contrato
	$changes[2] = $LANG['agrupamento'][11] . " ({$_POST['contracts_id']})";
	Log::history( $aID, 'Agrupamento_Supridesk', $changes, 'Contract', Log::HISTORY_LOG_SUPRISERVICE_MESSAGE);

	Html::redirect($CFG_GLPI["root_doc"].'/front/contract.form.php?id='.$_POST['contracts_id']);
}
else if (isset($_POST["delete"]))
{
   $a->check($_POST['id'],'d');

	//verifica se existem equipamentos associados a este item e mostra mensagem caso exista.
	$agrupamentoPrinter = new Agrupamento_Printer_Supridesk();
	if ( $agrupamentoPrinter->getFromAgrupamentoID( $_POST['id'] ) )
		Html::displayErrorAndDie( "Este agrupamento possui equipamentos associados, exclua-os antes de remover o item." );

   $a->delete($_POST, 0, false);
	//grava histórico de exclusão de agrupamento
	$a->getFromDB($_POST["id"]);
	$changes[0] = '0';
	$changes[1] = '';
	$changes[2] = $LANG['agrupamento'][13] . " ({$_POST["id"]})";
	Log::history( $a->fields['contracts_id'], 'Contract', $changes, 'Agrupamento_Supridesk', Log::HISTORY_LOG_SUPRISERVICE_MESSAGE);
	
	$contract = new Contract();
	if ( $contract->getFromDB( $a->fields['contracts_id'] ) )
		Html::redirect($contract->getFormURL().'?id='.$contract->fields['id']);

   Html::redirect($CFG_GLPI["root_doc"]."/front/central.php");

}
else if (isset($_POST["update"]))
{
   $a->check($_POST['id'],'w');

   $a->update($_POST);
   Event::log($_POST["id"], "agrupamento", 4, "inventory", $_SESSION["glpiname"]." ".$LANG['log'][21]);
   Html::back();

}
else if (isset($_POST['assign_printer']))
{
   $a->check(-1,'w',$_POST);
   if (isset($_POST["items_id"]) && $_POST["items_id"] >0) {
		
		$used = $ap->getPrinterAlreadyUsed( $_POST["items_id"] );
		if ( $used )
		{
			$ap->getFromPrinterID( $_POST["items_id"] );
			$a->getFromDB( $ap->fields["agrupamentos_id"] );
			$linkAgrupamento = "<a href='".$CFG_GLPI["root_doc"]."/front/agrupamento_supridesk.form.php?id={$a->fields['id']}'>".$a->fields["name"] . "</a>";
			$contract = new Contract();
			$contract->getFromDB($a->fields['contracts_id']);
			$linkContract = "<a href='".$CFG_GLPI["root_doc"]."/front/contract.form.php?id={$contract->fields['id']}'>".$contract->fields["name"] . "</a>";
			Html::displayErrorAndDie( "Impressora já agrupada no grupo {$linkAgrupamento} do contrato {$linkContract}." );
		}
      $ap->assignPrinter($_POST["agrupamentos_id"],$_POST["items_id"]);
      $changes[0] = '0';
      $changes[1] = '';
		$printer = new Printer();
		$printer->getFromDB($_POST["items_id"]);
      $changes[2] = "Equipamento agrupado: ID: {$printer->fields['id']}, Serial: {$printer->fields['serial']}, Patrimônio: {$printer->fields['otherserial']}";
		Log::history($_POST["agrupamentos_id"], 'Agrupamento_Supridesk', $changes, 'Printer', Log::HISTORY_LOG_SUPRISERVICE_MESSAGE);

		//grava histórico na impressora
		$a->getFromDB($_POST['agrupamentos_id']);
		$changes[0] = '0';
		$changes[1] = '';
		$changes[2] = "Agrupado no grupo ({$_POST['agrupamentos_id']}) do contrato ({$a->fields['contracts_id']})";
		Log::history( $_POST["items_id"], 'Printer', $changes, 'Agrupamento_Supridesk', Log::HISTORY_LOG_SUPRISERVICE_MESSAGE);

   }
   Html::back();

}
else if (isset($_GET['unassign_printer']))
{
   Session::checkRight("contract", "w");

	$ap->getFromDB($_GET['id']);
	print_r($ap->fields);
	$a->getFromDB($ap->fields['agrupamentos_id']);
	$printer = new Printer();
	$printer->getFromDB( $ap->fields['printers_id'] );

	//grava histórico na impressora
	$changes[0] = '0';
	$changes[1] = '';
	$changes[2] = "Desassociada do item ({$ap->fields['agrupamentos_id']}) do contrato ({$a->fields['contracts_id']})";
	Log::history( $ap->fields['printers_id'], 'Printer', $changes, 'Agrupamento_Supridesk', Log::HISTORY_LOG_SUPRISERVICE_MESSAGE);

	//grava histórico no item de contrato
	$changes[0] = '0';
	$changes[1] = '';
	$changes[2] = "Equipamento desassociado: ID: {$printer->fields['id']}, Serial: {$printer->fields['serial']}, Patrimônio: {$printer->fields['otherserial']}";
	Log::history($ap->fields["agrupamentos_id"], 'Agrupamento_Supridesk', $changes, 'Printer', Log::HISTORY_LOG_SUPRISERVICE_MESSAGE );

   $ap->unassignPrinterbyID($_GET['id']);

	Html::back();
}
else
{
   if (empty($_GET["contracts_id"])) {
      $_GET["contracts_id"] = "";
   }
   if (empty($_GET["itemtype"])) {
      $_GET["itemtype"] = "";
   }
   if (empty($_GET["several"])) {
      $_GET["several"] = "";
   }

   Session::checkRight("contract", "w");
   Html::header($LANG['agrupamento'][2],$_SERVER['PHP_SELF'],"inventory");

   $a->showForm($_GET["id"], $_GET);
   Html::footer();
}
?>