<?php
/*
 * @version $Id: ticketiteminformation.php 17152 2012-01-24 11:22:16Z moyo $
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
// Original Author of file: Nelly Mahu-Lasson
// Purpose of file:
// ----------------------------------------------------------------------

// Direct access to file
if (strpos($_SERVER['PHP_SELF'],"ticketiteminformation.php")) {
   $AJAX_INCLUDE = 1;
   define('GLPI_ROOT','..');
   include (GLPI_ROOT."/inc/includes.php");
   header("Content-Type: text/html; charset=UTF-8");
   Html::header_nocache();
}

Session::checkLoginUser();

if (isset($_REQUEST["my_items"]) && !empty($_REQUEST["my_items"])) {
   $splitter = explode("_",$_REQUEST["my_items"]);
   if (count($splitter) == 2) {
      $_REQUEST["itemtype"] = $splitter[0];
      $_REQUEST["items_id"] = $splitter[1];
   }
}

if (isset($_REQUEST['itemtype']) && isset($_REQUEST['items_id']) && $_REQUEST['items_id'] > 0) {
   // Security
   if (!class_exists($_REQUEST['itemtype']) ) {
      exit();
   }

   $days   = 3;
   $ticket = new Ticket();
   $data   = $ticket->getActiveOrSolvedLastDaysTicketsForItem($_REQUEST['itemtype'],
                                                              $_REQUEST['items_id'], $days);


	/*//SUPRISERVICE*/
   if (count($data)) {
		echo "<br /><b>" . count($data).'&nbsp;'.$LANG['job'][36] . " Seguem os detalhes abaixo: </b><br /><br />";
		echo "
			<table class='tab_cadre'>
				<tr>
					<th>Chamado</th>
					<th>Status</th>
					<th width='100%'>Descrição</th>
				</tr>
				";

		for($i = 0; $i < count($data); $i++){
			$data_ticket = $data[$i];
			$data_status = "<img src='".$CFG_GLPI["root_doc"]."/pics/".$data_ticket["status"].".png'
								alt=\"". Ticket::getStatus($data_ticket["status"])."\" title=\"".
								Ticket::getStatus($data_ticket["status"])."\">";
			$linkTicket = "<a href='".$CFG_GLPI["root_doc"]."/front/ticket.form.php?id=".$data_ticket['id']."' target='_blank'>".$data_ticket['id']."</a>";
			?>
			<tr>
				<td align='center'><?=$linkTicket?></td>
				<td><?=$data_status?></td>
				<td><?=$data_ticket['name']?></td>
			</tr>
		<?
		}
		echo "</table>";
	}
	else
		echo count($data).'&nbsp;'.$LANG['job'][36];

	
	/*//SUPRISERVICE*/
	if ( $_REQUEST['itemtype'] == 'Printer')
	{
		$printer = new Printer();
		$printer->getFromDB($_REQUEST['items_id']);
		print "<br><br><b>Selecione o consumível a ser utilizado:</b><br>";
		CartridgeItem::dropdownForTicket($printer, true);
	}
	
   $warranty  = $ticket->checkWarrantyForItem($_REQUEST['itemtype'], $_REQUEST['items_id']);
	if (!$warranty)
		echo "<span class='red' style='font-size: 12px; font-weight: bold;'><br>".$LANG['mailing'][40].".</span>";

	/*//SUPRISERVICE*/
	$expiracao = $ticket->checkContractTimeForItem($_REQUEST['items_id']);
	if ($expiracao !== true)
		echo $expiracao;
}
?>