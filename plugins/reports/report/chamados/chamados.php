<?php
/*
 * @version $Id: printers.php 203 2011-10-27 13:56:38Z remi $
 -------------------------------------------------------------------------
 reports - Additional reports plugin for GLPI
 Copyright (C) 2003-2011 by the reports Development Team.

 https://forge.indepnet.net/projects/reports
 -------------------------------------------------------------------------

 LICENSE

 This file is part of reports.

 reports is free software; you can redistribute it and/or modify
 it under the terms of the GNU General Public License as published by
 the Free Software Foundation; either version 2 of the License, or
 (at your option) any later version.

 reports is distributed in the hope that it will be useful,
 but WITHOUT ANY WARRANTY; without even the implied warranty of
 MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 GNU General Public License for more details.

 You should have received a copy of the GNU General Public License
 along with reports. If not, see <http://www.gnu.org/licenses/>.
 --------------------------------------------------------------------------
 */

/*
 * ----------------------------------------------------------------------
 * Original Author of file: Remi Collet
 *
 * Purpose of file:
 *    Big UNION to have a report including all inventory
 *
 * ----------------------------------------------------------------------
 */

//Options for GLPI 0.71 and newer : need slave db to access the report
$USEDBREPLICATE         = 1;
$DBCONNECTION_REQUIRED  = 0;

define('GLPI_ROOT', '../../../..');
include (GLPI_ROOT . "/inc/includes.php");

$ignored = array('Cartridge', 'CartridgeItem', 'Consumable', 'ConsumableItem', 'Software');

$report = new PluginReportsAutoReport();

//Report's search criterias
$opendate = new PluginReportsDateIntervalCriteria($report, 'data_abertura', 'Abertura');
$closedate = new PluginReportsDateIntervalCriteria($report, 'data_fechamento', 'Fechamento');
$analista = new PluginReportsDropdownCriteria($report, 'users_id', 'glpi_analistas', $LANG['reportchamados'][2]);
$type = new PluginReportsItemTypeCriteria($report, 'itemtype', '', 'infocom_types', $ignored);

// Status
 $status = new PluginReportsTicketStatusCriteria($report, 'status', $LANG['state'][0], 0);

//Display criterias form is needed
$report->displayCriteriasForm();

$display_type = HTML_OUTPUT;

//If criterias have been validated
if ($report->criteriasValidated()) {   

	$report->setSubNameAuto();	

	$cols = array(    
		new PluginReportsColumn('id', $LANG['reportchamados'][1]),				
		new PluginReportsColumn('analista', $LANG['reportchamados'][2]),
		new PluginReportsColumn('entidade', $LANG['reportchamados'][3]),
		new PluginReportsColumnType('itemtype', $LANG['reportchamados'][18]),
		new PluginReportsColumn('status', $LANG['state'][0]),		
		new PluginReportsColumn('modelo', $LANG['reportchamados'][4]),
		new PluginReportsColumn('equipamento', $LANG['reportchamados'][5]),
		new PluginReportsColumnDate('data_abertura', $LANG['reportchamados'][6]),
		new PluginReportsColumnDate('data_solucao', $LANG['reportchamados'][7]),
		new PluginReportsColumnDate('data_fechamento', $LANG['reportchamados'][8]), 
		new PluginReportsColumn('tempo_solucao', $LANG['reportchamados'][9]),
		new PluginReportsColumn('tempo_fechamento', $LANG['reportchamados'][10]),
		new PluginReportsColumnDate('vencimento_sla', $LANG['reportchamados'][11]),
		new PluginReportsColumn('tempo_excedente', $LANG['reportchamados'][12]),
		new PluginReportsColumn('categoria', $LANG['reportchamados'][13]),
		new PluginReportsColumn('descricao_problema', $LANG['reportchamados'][14]),
		new PluginReportsColumn('descricao_solucao', $LANG['reportchamados'][15]),
		new PluginReportsColumn('chamado_lenovo', $LANG['reportchamados'][16]),
		new PluginReportsColumn('fornecedor', $LANG['reportchamados'][17])
	);

	$report->setColumns($cols);
	   
	$sql = "SELECT
			report_tickets.id,
			report_tickets.users_id,
			report_tickets.analista,
			report_tickets.entities_id,
			report_tickets.entidade,
			report_tickets.status,
			report_tickets.itemtype,
			report_tickets.modelo,
			report_tickets.equipamento,
			report_tickets.data_abertura,
			report_tickets.data_solucao,
			report_tickets.data_fechamento,
			report_tickets.tempo_solucao,
			report_tickets.tempo_fechamento,
			report_tickets.vencimento_sla,
			report_tickets.tempo_excedente,
			report_tickets.categoria,
			report_tickets.descricao_problema,
			report_tickets.descricao_solucao,
			report_tickets.chamado_lenovo,
			report_tickets.fornecedor
			FROM
			report_tickets
		   ".
	
	getEntitiesRestrictRequest('WHERE', 'report_tickets').
	$report->addSqlCriteriasRestriction();	  
	       
	$report->setSqlRequest($sql);
	
	$report->execute();
	
}else{
   Html::footer();
}