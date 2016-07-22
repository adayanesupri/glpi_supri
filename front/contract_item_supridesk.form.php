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

$ci  = new Contract_Item_Supridesk();
$cip = new Contract_Item_Printer_Supridesk();

if (!isset($_GET["id"])) {
   $_GET["id"] = "";
}

if (isset($_POST["add"]))
{
	$ci->check(-1,'w',$_POST);
	$ciID = $ci->add($_POST);

	//grava histórico de inclusão de item no contrato
	$ci->getFromDB($ciID);
	$changes[0] = '0';
	$changes[1] = '';
	$changes[2] = $LANG['tarifacao'][34] . " ({$ciID})";
	Log::history( $_POST['contracts_id'], 'Contract', $changes, 'Contract_Item_Supridesk', Log::HISTORY_LOG_SUPRISERVICE_MESSAGE);
	
	//grava histórico de criação ne item do contrato
	$changes[2] = $LANG['tarifacao'][39] . " ({$_POST['contracts_id']})";
	Log::history( $ciID, 'Contract_Item_Supridesk', $changes, 'Contract', Log::HISTORY_LOG_SUPRISERVICE_MESSAGE);

	Html::redirect($CFG_GLPI["root_doc"].'/front/contract.form.php?id='.$_POST['contracts_id']);
}
else if (isset($_POST["delete"]))
{
   $ci->check($_POST['id'],'d');

	//verifica se existem equipamentos associados a este item e mostra mensagem caso exista.
	$contractItemPrinter = new Contract_Item_Printer_Supridesk();
	if ( $contractItemPrinter->getFromIDs( $_POST['id'] ) )
		Html::displayErrorAndDie( "Este item de contrato possui equipamentos associados, exclua-os antes de remover o item." );

        $ci->delete($_POST, 0, false);
	//grava histórico de exclusão de item no contrato
	$ci->getFromDB($_POST["id"]);
	$changes[0] = '0';
	$changes[1] = '';
	$changes[2] = $LANG['tarifacao'][35] . " ({$_POST["id"]})";
	Log::history( $ci->fields['contracts_id'], 'Contract', $changes, 'Contract_Item_Supridesk', Log::HISTORY_LOG_SUPRISERVICE_MESSAGE);
	
	$contract = new Contract();
	if ( $contract->getFromDB( $ci->fields['contracts_id'] ) )
		Html::redirect($contract->getFormURL().'?id='.$contract->fields['id']);

   Html::redirect($CFG_GLPI["root_doc"]."/front/central.php");

}
else if (isset($_POST["update"]))
{
   $ci->check($_POST['id'],'w');

   $ci->update($_POST);
   Event::log($_POST["id"], "contractitem", 4, "inventory", $_SESSION["glpiname"]." ".$LANG['log'][21]);
   Html::back();

}
else if (isset($_POST['assign_printer']))
{
    $ci->check(-1,'w',$_POST);
    $itemOK = (isset($_POST["items_id"]) && $_POST["items_id"] >0);
    $dateOK = (isset($_POST["date"]) && $_POST["date"] != null);
   if ( $itemOK && $dateOK) {
       
        $itemtype = $_POST['0'];

        $used = $cip->getPrinterAlreadyUsed( $_POST["items_id"],$contract, $itemtype );
        
        if ( $used )
        {
            $cip->getFromIDs( null, $_POST["items_id"] );			
            $ci->getFromDB( $cip->fields['contracts_items_id'] );
            $linkContractItem = "<a href='".$CFG_GLPI["root_doc"]."/front/contract_item_supridesk.form.php?id={$ci->fields['id']}'>".$ci->fields["nome"] . "</a>";

            $contract = new Contract();
            $contract->getFromDB($ci->fields['contracts_id']);
            $linkContract = "<a href='".$CFG_GLPI["root_doc"]."/front/contract.form.php?id={$contract->fields['id']}'>".$contract->fields["name"] . "</a>";
            Html::displayErrorAndDie( "Impressora já vinculada ao item {$linkContractItem} do contrato {$linkContract}." );
        }
        else if ( !isset($_POST["agrupamentos_id"]) || $_POST["agrupamentos_id"] <= 0 )
        {
            Html::displayErrorAndDie( "Escolha um agrupamento para o equipamento." );
        }

	//adiciona contador
        $contadorID = $cip->addContador($_POST["date"],
                                        intval($_POST["impressao_mono"]),
                                        intval($_POST["impressao_color"]),
                                        intval($_POST["copia_mono"]),
                                        intval($_POST["copia_color"]),
                                        intval($_POST["digitalizacao_copia"]),
                                        intval($_POST["digitalizacao_fax"]),
                                        intval($_POST["digitalizacao_rede"]),
                                        intval($_POST["digitalizacao_usb"]) );
        
        //var_export($used);
       // die();

	//atribui impressora
        $cipID = $cip->assignPrinter($_POST["contracts_items_id"], $_POST["items_id"], $_POST["agrupamentos_id"], $contadorID, intval($_POST["replaced_printers_id"]), $_POST['0'] );

          //grava histórico no contrato
        $changes[0] = '0';
        $changes[1] = '';
        $printer = new Printer();
        $printer->getFromDB($_POST["items_id"]);
        $changes[2] = "Equipamento associado: ID: {$printer->fields['id']}, Serial: {$printer->fields['serial']}, Patrimônio: {$printer->fields['otherserial']}, Agrupamento: {$_POST['agrupamentos_id']}";
        Log::history($_POST["contracts_items_id"], 'Contract_Item_Supridesk', $changes, 'Printer', Log::HISTORY_LOG_SUPRISERVICE_MESSAGE);

        //grava histórico na impressora
        $ci->getFromDB($_POST['contracts_items_id']);
        $changes[0] = '0';
        $changes[1] = '';
        $changes[2] = "Associada ao item ({$_POST['contracts_items_id']}) do contrato ({$ci->fields['contracts_id']}) no agrupamento ({$_POST['agrupamentos_id']})";
        Log::history( $_POST["items_id"], 'Printer', $changes, 'Contract_Item_Supridesk', Log::HISTORY_LOG_SUPRISERVICE_MESSAGE);

   }
   Html::back();

}
else if (isset($_POST['unassign_printer']))
{
   $itemtype = $_POST['0'];
   
   $ci->check(-1,'w',$_POST);
	$itemOK = (isset($_POST["items_id"]) && $_POST["items_id"] >0);
	$dateOK = (isset($_POST["date"]) && $_POST["date"] != null);
   if ( $itemOK && $dateOK) {

		$used = $cip->getPrinterAlreadyUsed( $_POST["items_id"], $_POST["contracts_items_id"], $itemtype );
                
		if ( !$used )
		{
                    $contractItem = new Contract_Item_Supridesk();
                    $contractItem->getFromDB( $_POST["contracts_items_id"] );
                    $linkContractItem = "<a href='".$CFG_GLPI["root_doc"]."/front/contract_item_supridesk.form.php?id={$contractItem->fields['id']}'>".$contractItem->fields["nome"] . "</a>";
                    $contract = new Contract();
                    $contract->getFromDB($contractItem->fields['contracts_id']);
                    $linkContract = "<a href='".$CFG_GLPI["root_doc"]."/front/contract.form.php?id={$contract->fields['id']}'>".$contract->fields["name"] . "</a>";
                    Html::displayErrorAndDie( "O equipamento informado não está associado a este item de contrato." );
		}
	
		$cip->getFromIDs( $_POST["contracts_items_id"], $_POST["items_id"], true );
		$ci->getFromDB( $_POST["contracts_items_id"] );
		$printer = new Printer();
		$printer->getFromDB( $_POST["items_id"] );

		//adiciona contador
                $contadorID = $cip->addContador($_POST["date"],
                                                intval($_POST["impressao_mono"]),
                                                intval($_POST["impressao_color"]),
                                                intval($_POST["copia_mono"]),
                                                intval($_POST["copia_color"]),
                                                intval($_POST["digitalizacao_copia"]),
                                                intval($_POST["digitalizacao_fax"]),
                                                intval($_POST["digitalizacao_rede"]),
                                                intval($_POST["digitalizacao_usb"]) );
		//desassocia impressora
                $cipID = $cip->unassignPrinter(	$_POST["contracts_items_id"], $_POST["items_id"], $contadorID, $itemtype );

		//grava histórico na impressora
		$changes[0] = '0';
		$changes[1] = '';
		$changes[2] = "Desassociada do item ({$cip->fields['contracts_items_id']}) do contrato ({$ci->fields['contracts_id']})";
		Log::history( $cip->fields['printers_id'], 'Printer', $changes, 'Contract_Item_Supridesk', Log::HISTORY_LOG_SUPRISERVICE_MESSAGE);

		//grava histórico no item de contrato
		$changes[0] = '0';
		$changes[1] = '';
		$changes[2] = "Equipamento desassociado: ID: {$printer->fields['id']}, Serial: {$printer->fields['serial']}, Patrimônio: {$printer->fields['otherserial']}";
		Log::history($cip->fields["contracts_items_id"], 'Contract_Item_Supridesk', $changes, 'Printer', Log::HISTORY_LOG_SUPRISERVICE_MESSAGE );

	}
   Html::back();

} else if (isset($_POST["action"]) && $_POST["action"] == 'massive_move_to_group') {
   Session::checkRight("contract", "w");

   if (isset($_POST["move_to_group"]) && count($_POST["move_to_group"])) {
      foreach ($_POST["move_to_group"] as $contractItemPrinterID => $val) {
			$cip->moveToGroup( $contractItemPrinterID, $_POST["agrupamentoID"] );
      }
   }
   //Event::log(0, "networkport", 5, "inventory", $_SESSION["glpiname"]." ".$LANG['log'][74]);
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
   Html::header($LANG['tarifacao'][17],$_SERVER['PHP_SELF'],"inventory");

   $ci->showForm($_GET["id"], $_GET);
   Html::footer();
}
?>