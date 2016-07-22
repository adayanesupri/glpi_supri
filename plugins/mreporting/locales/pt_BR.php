<?php
/*
 * @version $Id: HEADER 15930 2011-10-30 15:47:55Z tsmr $
 -------------------------------------------------------------------------
 Mreporting plugin for GLPI
 Copyright (C) 2003-2011 by the mreporting Development Team.

 https://forge.indepnet.net/projects/mreporting
 -------------------------------------------------------------------------

 LICENSE

 This file is part of mreporting.

 mreporting is free software; you can redistribute it and/or modify
 it under the terms of the GNU General Public License as published by
 the Free Software Foundation; either version 2 of the License, or
 (at your option) any later version.

 mreporting is distributed in the hope that it will be useful,
 but WITHOUT ANY WARRANTY; without even the implied warranty of
 MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 GNU General Public License for more details.

 You should have received a copy of the GNU General Public License
 along with mreporting. If not, see <http://www.gnu.org/licenses/>.
 --------------------------------------------------------------------------
 */
 
$LANG['plugin_mreporting']["name"] = "Indicadores";

$LANG['plugin_mreporting']["error"][0] = "Nenhum relatório disponível !";
$LANG['plugin_mreporting']["error"][1] = "Não há dados para esse intervalo de data !";
$LANG['plugin_mreporting']["error"][2] = "Não definido";

$LANG['plugin_mreporting']['Helpdesk']['title'] = "Reporting Helpdesk";
$LANG['plugin_mreporting']['Helpdesk']['reportHbarTicketNumberByEntity']['title'] = "Número de tickets por entidade";
$LANG['plugin_mreporting']['Helpdesk']['reportHbarTrocaTonerPorEntidade']['title'] = "Troca de Toner Por Entidade";
$LANG['plugin_mreporting']['Helpdesk']['reportPieTicketNumberByEntity']['title'] = "Número de tickets por entidade";
$LANG['plugin_mreporting']['Helpdesk']['reportHgbarTicketNumberByCatAndEntity']['title'] = "Número de tickets por categorias e entidades";
$LANG['plugin_mreporting']['Helpdesk']['reportPieTicketOpenedAndClosed']['title'] = "Número de tickets abertos e fechados";
$LANG['plugin_mreporting']['Helpdesk']['reportPieTicketOpenedbyStatus']['title'] = "Número de tickets abertos por status";
$LANG['plugin_mreporting']['Helpdesk']['reportHgbarOpenTicketNumberByCategoryAndByType']['title'] = "Número de tickets abertos por categorias e tipos";
$LANG['plugin_mreporting']['Helpdesk']['reportHgbarCloseTicketNumberByCategoryAndByType']['title'] = "Número de tickets fechados por categorias e tipos";
$LANG['plugin_mreporting']['Helpdesk']['reportHgbarTicketNumberByService']['title'] = "Número de tickets abertos e fechados por serviço";
$LANG['plugin_mreporting']['Helpdesk']['reportHgbarOpenedTicketNumberByCategory']['title'] = "Número de tickets abertos por categorias e por status";
$LANG['plugin_mreporting']['Helpdesk']['reportAreaNbTicket']['title'] = "Evolução do número de tickets no período";
$LANG['plugin_mreporting']['Helpdesk']['reportLineNbTicket']['title'] = "Evolução do número de tickets no período por Localidades";
$LANG['plugin_mreporting']['Helpdesk']['reportGlineNbTicket']['title'] = "Evolução do número de tickets no período (por status)";
$LANG['plugin_mreporting']['Helpdesk']['reportGareaNbTicket']['title'] = "Evolução do número de tickets no período (por status)";

$LANG['plugin_mreporting']['Helpdesk']['reportLineFluxoChamadosPorPeriodo']['title'] = "Média de Fluxo de tickets por dia / hora";
$LANG['plugin_mreporting']['Helpdesk']['reportLineFluxoChamadosPorMes']['title'] = "Fluxo de tickets por Mês";
$LANG['plugin_mreporting']['Helpdesk']['reportLineFluxoChamadosPorDiaSemana']['title'] = "Média de Fluxo de tickets por Dia da Semana";
$LANG['plugin_mreporting']['Helpdesk']['reportLineFluxoChamadosPorHora']['title'] = "Média de Fluxo de tickets por Hora";


$LANG['plugin_mreporting']['Helpdesk']['reportHbarInstalacaoTonerPorEntidade']['title'] = "Instalação de Toner Por Entidade";
$LANG['plugin_mreporting']['Helpdesk']['reportHgbarMediaImpressaoTrocaTonerImpressora']['title'] = "Média de impressão por troca de toner por modelo impressora por entidade";

$LANG['plugin_mreporting']['Helpdesk']['reportHgbarChamadosPorAnalista']['title'] = "Tickets abertos, solucionados e follow ups por Grupo de Usuário";

$LANG['plugin_mreporting']['Helpdesk']['reportGlineTicketRequestTypePeriod']['title'] = "Tickets abertos por tipo de requisição por período";
$LANG['plugin_mreporting']['Helpdesk']['reportGlineTicketRequestTypePeriodEvolution']['title'] = "Evolução de tickets abertos por tipo de requisição por período";

$LANG['plugin_mreporting']['Helpdesk']['reportGlineEstoque']['title'] = "Progressão de Troca de Consumíveis";

$LANG['plugin_mreporting']['Helpdesk']['reportHbarProgressaoTotalTrocaDeConsumiveis']['title'] = "Progressão e Totais de Troca de Consumíveis";

$LANG['plugin_mreporting']['Helpdesk']['reportGlineBIDTroca']['title'] = "Relatório de BID por troca de suprimento (que é retirado da impressora)";

$LANG['plugin_mreporting']['Helpdesk']['reportGlineBIDInstalacao']['title'] = "Relatório de BID por instalação de suprimento (que é instalado na impressora)";

//$LANG['plugin_mreporting']['Helpdesk']['reportGlinePrevisaoCusto']['title'] = "Linhas - Previsão de Custos";

$LANG['plugin_mreporting']['Helpdesk']['reportHgbarVolumetriaMensalImpressora']['title'] = "Volumetria mensal por modelo impressora por entidade";

$LANG['plugin_mreporting']['Helpdesk']['reportGlineCloseTicketNumberByType']['title'] = "Número de tickets fechados por tipo";

$LANG['plugin_mreporting']['Helpdesk']['reportHbarPecas']['title'] = "Número de peças trocadas";

//$LANG['plugin_mreporting']['Helpdesk']['reportGlineInvComputador']['title'] = "Inventário de Computadores a Venda";

//$LANG['plugin_mreporting']['Helpdesk']['reportGlineInvMonitor']['title'] = "Inventário de Monitores a Venda";

//$LANG['plugin_mreporting']['Helpdesk']['reportGlineInvPrinter']['title'] = "Inventário de Impressoras a Venda";

$LANG['plugin_mreporting']['Helpdesk']['reportHgbarChamadosPorCategoriaAnalista']['title'] = "Quantidade de tickets por Analista";

$LANG['plugin_mreporting']['Helpdesk']['reportGlineTicketAnalista']['title'] = "Quantidade de tickets por Tipo";

$LANG['plugin_mreporting']['Helpdesk']['reportGlineInventarioPecas']['title'] = "Inventário de Peças";

$LANG['plugin_mreporting']['Helpdesk']['reportGlineEquipamentosBackup']['title'] = "Inventário equipamentos de Backup";

$LANG['plugin_mreporting']['Helpdesk']['reportGlineFaturamento']['title'] = "Relatório de Faturamento";

$LANG['plugin_mreporting']['Helpdesk']['reportPieTopTenAuthor']['title'] = "Top 10 Demandadores";
$LANG['plugin_mreporting']['Helpdesk']['reportGlineTelefoniaIP']['title'] = "Telefonia IP";
$LANG['plugin_mreporting']['Helpdesk']['reportGlineInventarioPecasVenda']['title'] = "Inventário de Equipamentos/Softwares";
?>