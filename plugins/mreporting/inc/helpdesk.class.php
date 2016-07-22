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

class PluginMreportingHelpdesk Extends PluginMreportingBaseclass {

    private $sql_date, $filters, $where_entities;

    function __construct() {
        global $LANG;

        $this->filters = array(
            'open' => array(
                'label' => $LANG['job'][14],
                'status' => array(
                    'new' => $LANG['joblist'][9],
                    'assign' => $LANG['joblist'][18],
                    'plan' => $LANG['joblist'][19],
                    'waiting' => $LANG['joblist'][26]
                )
            ),
            'close' => array(
                'label' => $LANG['job'][16],
                'status' => array(
                    'solved' => $LANG['joblist'][32],
                    'closed' => $LANG['joblist'][33]
                )
            )
        );
        $this->where_entities = "'" . implode("', '", $_SESSION['glpiactiveentities']) . "'";
        $_SESSION['ENTITIES_SELECT'] = $this->where_entities;
    }

    function reportHbarTicketNumberByEntity() {
        global $DB, $LANG;
        
        $mreporting = new PluginMreportingProfile();
        $permissao = $mreporting->confereIndicadores($_REQUEST['f_name'], $_SESSION["glpiactiveprofile"]["id"]);
                
        if($permissao == false){
            print "<div style='text-align: center;'>Sem permissões de visualização deste indicador.</div>";
            die();
        }
        
        $datas = array();
       

        $delay = 365;
        $this->sql_date = PluginMreportingMisc::getSQLDate("glpi_tickets.date", $delay);

        $query = "SELECT
         COUNT(glpi_tickets.id) as nb,
         glpi_entities.name as name
      FROM glpi_tickets
      LEFT JOIN glpi_entities
         ON glpi_tickets.entities_id = glpi_entities.id
      WHERE " . $this->sql_date . "
      AND glpi_entities.id IN (" . $this->where_entities . ")
      AND glpi_tickets.is_deleted = '0'
      GROUP BY glpi_entities.name
      ORDER BY glpi_entities.name ASC";
        $res = $DB->query($query);
        while ($data = $DB->fetch_assoc($res)) {
            if (empty($data['name']))
                $data['name'] = $LANG['entity'][2];
            $datas[$data['name']] = $data['nb'];
        }
        
        return array('datas' => $datas);
    }

    function reportPieTicketNumberByEntity() {
        return $this->reportHbarTicketNumberByEntity();
    }

    function reportHgbarTicketNumberByCatAndEntity() {
        global $DB, $LANG;
        
        $mreporting = new PluginMreportingProfile();
        $permissao = $mreporting->confereIndicadores($_REQUEST['f_name'], $_SESSION["glpiactiveprofile"]["id"]);
                
        if($permissao == false){
            print "<div style='text-align: center;'>Sem permissões de visualização deste indicador.</div>";
            die();
        }
        
        $datas = array();
        $tmp_datas = array();

        $delay = 365;
        $this->sql_date = PluginMreportingMisc::getSQLDate("glpi_tickets.date", $delay);

        //get categories used in this period
        $query_cat = "SELECT
         DISTINCT(glpi_tickets.itilcategories_id) as itilcategories_id,
         glpi_itilcategories.completename as category
      FROM glpi_tickets
      LEFT JOIN glpi_itilcategories
         ON glpi_tickets.itilcategories_id = glpi_itilcategories.id
      WHERE " . $this->sql_date . "
      AND glpi_tickets.entities_id IN (" . $this->where_entities . ")
      AND glpi_tickets.is_deleted = '0'
      ORDER BY glpi_itilcategories.id ASC";
        $res_cat = $DB->query($query_cat);
        $categories = array();
        while ($data = $DB->fetch_assoc($res_cat)) {
            if (empty($data['category']))
                $data['category'] = $LANG['job'][32];
            $categories[$data['category']] = $data['itilcategories_id'];
        }


        $labels2 = array_keys($categories);
        $tmp_cat = array();
        foreach (array_values($categories) as $id) {
            $tmp_cat[] = "cat_$id";
        }
        $cat_str = "'" . implode("', '", array_values($categories)) . "'";

        //count ticket by entity and categories previously selected
        $query = "SELECT
         COUNT(glpi_tickets.id) as nb,
         glpi_entities.name as entity,
         glpi_tickets.itilcategories_id as cat_id
      FROM glpi_tickets
      LEFT JOIN glpi_entities
         ON glpi_tickets.entities_id = glpi_entities.id
      WHERE glpi_tickets.itilcategories_id IN ($cat_str)
      AND glpi_tickets.entities_id IN (" . $this->where_entities . ")
      AND " . $this->sql_date . "
      AND glpi_tickets.is_deleted = '0'
      GROUP BY glpi_entities.name, glpi_tickets.itilcategories_id
      ORDER BY glpi_entities.name ASC, glpi_tickets.itilcategories_id ASC";
        $res = $DB->query($query);
        while ($data = $DB->fetch_assoc($res)) {
            if (empty($data['entity']))
                $data['entity'] = $LANG['entity'][2];
            $tmp_datas[$data['entity']]["cat_" . $data['cat_id']] = $data['nb'];
        }

        //merge missing datas (0 ticket for a category)
        foreach ($tmp_datas as &$data) {
            $data = array_merge(array_fill_keys($tmp_cat, 0), $data);
        }

        //replace cat_id by labels2
        foreach ($tmp_datas as $entity => &$subdata) {
            $tmp = array();
            $i = 0;
            foreach ($subdata as $value) {
                $cat_label = $labels2[$i];
                $tmp[$cat_label] = $value;
                $i++;
            }
            $subdata = $tmp;
        }

        $datas['datas'] = $tmp_datas;
        $datas['labels2'] = $labels2;

        return $datas;
    }

    function reportPieTicketOpenedAndClosed() {
        global $DB;
        
        $mreporting = new PluginMreportingProfile();
        $permissao = $mreporting->confereIndicadores($_REQUEST['f_name'], $_SESSION["glpiactiveprofile"]["id"]);
                
        if($permissao == false){
            print "<div style='text-align: center;'>Sem permissões de visualização deste indicador.</div>";
            die();
        }       

        $delay = 30;
        $this->sql_date = PluginMreportingMisc::getSQLDate("glpi_tickets.date", $delay);

        $datas = array();
        foreach ($this->filters as $filter) {

            $query = "
            SELECT COUNT(*)
            FROM glpi_tickets
            WHERE " . $this->sql_date . "
            AND glpi_tickets.entities_id IN (" . $this->where_entities . ")
            AND glpi_tickets.is_deleted = '0'
            AND glpi_tickets.status IN('" . implode("', '", array_keys($filter['status'])) . "')
         ";
            $result = $DB->query($query);
            $datas[$filter['label']] = $DB->result($result, 0, 0);
        }

        return array('datas' => $datas);
    }

    function reportPieTicketOpenedbyStatus() {
        global $DB;
        
        $mreporting = new PluginMreportingProfile();
        $permissao = $mreporting->confereIndicadores($_REQUEST['f_name'], $_SESSION["glpiactiveprofile"]["id"]);
                
        if($permissao == false){
            print "<div style='text-align: center;'>Sem permissões de visualização deste indicador.</div>";
            die();
        }

        $delay = 365;
        $this->sql_date = PluginMreportingMisc::getSQLDate("glpi_tickets.date", $delay);

        $datas = array();
        foreach ($this->filters['open']['status'] as $key => $val) {

            $query = "
               SELECT COUNT(glpi_tickets.id) as count
               FROM glpi_tickets
               WHERE " . $this->sql_date . "
               AND glpi_tickets.is_deleted = '0'
               AND glpi_tickets.entities_id IN (" . $this->where_entities . ")
               AND glpi_tickets.status ='" . $key . "'
            ";
            $result = $DB->query($query);

            while ($ticket = $DB->fetch_assoc($result)) {

                $datas['datas'][$val] = $ticket['count'];
            }
        }

        $datas['delay'] = $delay;

        return $datas;
    }

    function reportPieTopTenAuthor() {
        global $DB, $LANG;
        
        $mreporting = new PluginMreportingProfile();
        $permissao = $mreporting->confereIndicadores($_REQUEST['f_name'], $_SESSION["glpiactiveprofile"]["id"]);
                
        if($permissao == false){
            print "<div style='text-align: center;'>Sem permissões de visualização deste indicador.</div>";
            die();
        }
        

        $delay = 30;
        $this->sql_date = PluginMreportingMisc::getSQLDate("glpi_tickets.date", $delay);
        $this->sql_closedate = PluginMreportingMisc::getSQLDate("glpi_tickets.closedate", $delay);

        $datas = array();
        $query = "
         SELECT COUNT(glpi_tickets.id) as count, glpi_tickets_users.users_id as users_id
         FROM glpi_tickets
         LEFT JOIN glpi_tickets_users ON (glpi_tickets_users.tickets_id = glpi_tickets.id AND glpi_tickets_users.type =1)
         WHERE " . $this->sql_date . "
         AND " . $this->sql_closedate . "
         AND glpi_tickets.entities_id IN (" . $this->where_entities . ")
         AND glpi_tickets.is_deleted = '0'
         GROUP BY glpi_tickets_users.users_id
         ORDER BY count DESC
         LIMIT 10
      ";
        $result = $DB->query($query);

        while ($ticket = $DB->fetch_assoc($result)) {
            if ($ticket['users_id'] == 0) {
                $label = $LANG['plugin_mreporting']["error"][2];
            } else {
                $label = getUserName($ticket['users_id']);
            }
            $datas['datas'][$label] = $ticket['count'];
        }

        $datas['delay'] = $delay;

        return $datas;
    }

    function reportHgbarOpenTicketNumberByCategoryAndByType() {
        return $this->reportHgbarTicketNumberByCategoryAndByType('open');
    }

    function reportHgbarCloseTicketNumberByCategoryAndByType() {
        return $this->reportHgbarTicketNumberByCategoryAndByType('close');
    }

    private function reportHgbarTicketNumberByCategoryAndByType($filter) {
        global $DB, $LANG;
        
        $mreporting = new PluginMreportingProfile();
        $permissao = $mreporting->confereIndicadores($_REQUEST['f_name'], $_SESSION["glpiactiveprofile"]["id"]);
                
        if($permissao == false){
            print "<div style='text-align: center;'>Sem permissões de visualização deste indicador.</div>";
            die();
        }
        
        $datas = array();

        $delay = 30;
        $this->sql_date = PluginMreportingMisc::getSQLDate("glpi_tickets.date", $delay);

        $datas['labels2']['type_0'] = $LANG['job'][32];
        $datas['labels2']['type_1'] = $LANG['job'][1];
        $datas['labels2']['type_2'] = $LANG['job'][2];

        $query = "
         SELECT
            glpi_itilcategories.id as category_id,
            glpi_itilcategories.completename as category_name,
            glpi_tickets.type,
            COUNT(glpi_tickets.id) as count
         FROM glpi_tickets
         LEFT JOIN glpi_itilcategories
            ON glpi_itilcategories.id = glpi_tickets.itilcategories_id
         WHERE " . $this->sql_date . "
         AND glpi_tickets.entities_id IN (" . $this->where_entities . ")
         AND glpi_tickets.status IN('" . implode("', '", array_keys($this->filters[$filter]['status'])) . "')
         AND glpi_tickets.is_deleted = '0'
         GROUP BY glpi_itilcategories.id, glpi_tickets.type
         ORDER BY glpi_itilcategories.name
      ";
        $result = $DB->query($query);

        $datas['datas'] = array();
        while ($ticket = $DB->fetch_assoc($result)) {
            if (is_null($ticket['category_id'])) {
                $ticket['category_id'] = 0;
                $ticket['category_name'] = $LANG['job'][32];
            }
            $datas['datas'][$ticket['category_name']]['type_' . $ticket['type']] = $ticket['count'];
        }

        return $datas;
    }

    function reportHgbarTicketNumberByService() {
        global $DB, $LANG;
        
        $mreporting = new PluginMreportingProfile();
        $permissao = $mreporting->confereIndicadores($_REQUEST['f_name'], $_SESSION["glpiactiveprofile"]["id"]);
                
        if($permissao == false){
            print "<div style='text-align: center;'>Sem permissões de visualização deste indicador.</div>";
            die();
        }
        
        $datas = array();

        $delay = 30;
        $this->sql_date = PluginMreportingMisc::getSQLDate("glpi_tickets.date", $delay);

        foreach ($this->filters as $class => $filter) {

            $datas['labels2'][$class] = $filter['label'];

            $query = "
            SELECT COUNT(*)
            FROM glpi_tickets
            WHERE id NOT IN (
               SELECT tickets_id
               FROM glpi_groups_tickets
               WHERE glpi_groups_tickets.type = 1
            )
            AND glpi_tickets.entities_id IN (" . $this->where_entities . ")
            AND " . $this->sql_date . "
            AND status IN('" . implode("', '", array_keys($filter['status'])) . "')
         ";
            $result = $DB->query($query);

            $datas['datas'][$LANG['common'][49]][$class] = $DB->result($result, 0, 0);

            $query = "
            SELECT
               glpi_groups.name as group_name,
               COUNT(glpi_tickets.id) as count
            FROM glpi_tickets, glpi_groups_tickets, glpi_groups
            WHERE glpi_tickets.id = glpi_groups_tickets.tickets_id
            AND glpi_tickets.entities_id IN (" . $this->where_entities . ")
            AND glpi_groups_tickets.groups_id = glpi_groups.id
            AND glpi_groups_tickets.type = 1
            AND glpi_tickets.is_deleted = '0'
            AND " . $this->sql_date . "
            AND glpi_tickets.status IN('" . implode("', '", array_keys($filter['status'])) . "')
            GROUP BY glpi_groups.id
            ORDER BY glpi_groups.name
         ";
            $result = $DB->query($query);

            while ($ticket = $DB->fetch_assoc($result)) {
                $datas['datas'][$ticket['group_name']][$class] = $ticket['count'];
            }
        }

        return $datas;
    }

    function reportHgbarOpenedTicketNumberByCategory() {
        global $DB, $LANG;
        
        $mreporting = new PluginMreportingProfile();
        $permissao = $mreporting->confereIndicadores($_REQUEST['f_name'], $_SESSION["glpiactiveprofile"]["id"]);
                
        if($permissao == false){
            print "<div style='text-align: center;'>Sem permissões de visualização deste indicador.</div>";
            die();
        }
        
        $datas = array();

        $delay = 30;
        $this->sql_date = PluginMreportingMisc::getSQLDate("glpi_tickets.date", $delay);

        $status = array_merge(
                $this->filters['open']['status'], $this->filters['close']['status']
        );
        $status_keys = array_keys($status);



        $query = "
         SELECT
            glpi_tickets.status,
            glpi_itilcategories.completename as category_name,
            COUNT(glpi_tickets.id) as count
         FROM glpi_tickets
         LEFT JOIN glpi_itilcategories
            ON glpi_itilcategories.id = glpi_tickets.itilcategories_id
         WHERE " . $this->sql_date . "
         AND glpi_tickets.entities_id IN (" . $this->where_entities . ")
         AND glpi_tickets.status IN('" . implode("', '", $status_keys) . "')
         AND glpi_tickets.is_deleted = '0'
         GROUP BY glpi_itilcategories.id, glpi_tickets.status
         ORDER BY glpi_itilcategories.name
      ";
        $result = $DB->query($query);

        while ($ticket = $DB->fetch_assoc($result)) {
            if (is_null($ticket['category_name'])) {
                $ticket['category_name'] = $LANG['job'][32];
            }
            $datas['labels2'][$ticket['status']] = $status[$ticket['status']];
            $datas['datas'][$ticket['category_name']][$ticket['status']] = $ticket['count'];
        }

        return $datas;
    }

    function reportAreaNbTicket() {
        global $DB, $LANG;
        
        $mreporting = new PluginMreportingProfile();
        $permissao = $mreporting->confereIndicadores($_REQUEST['f_name'], $_SESSION["glpiactiveprofile"]["id"]);
                
        if($permissao == false){
            print "<div style='text-align: center;'>Sem permissões de visualização deste indicador.</div>";
            die();
        }
        
        $datas = array();

        $delay = 365;
        $this->sql_date = PluginMreportingMisc::getSQLDate("glpi_tickets.date", $delay);

        $query = "SELECT
         DISTINCT DATE_FORMAT(date, '%y%m') as month,
         DATE_FORMAT(date, '%b%y') as month_l,
         COUNT(id) as nb
      FROM glpi_tickets
      WHERE " . $this->sql_date . "
      AND glpi_tickets.entities_id IN (" . $this->where_entities . ")
      AND glpi_tickets.is_deleted = '0'
      GROUP BY month
      ORDER BY month";
        $res = $DB->query($query);
        while ($data = $DB->fetch_assoc($res)) {
            $datas['datas'][$data['month_l']] = $data['nb'];
        }

        //curve lines
        $datas['spline'] = true;

        return $datas;
    }

    function reportLineNbTicket() {

        global $DB, $LANG;
        
        $mreporting = new PluginMreportingProfile();
        $permissao = $mreporting->confereIndicadores($_REQUEST['f_name'], $_SESSION["glpiactiveprofile"]["id"]);
                
        if($permissao == false){
            print "<div style='text-align: center;'>Sem permissões de visualização deste indicador.</div>";
            die();
        }
        
        $datas = array();

        $delay = 365;
        $this->sql_date = PluginMreportingMisc::getSQLDate("glpi_tickets.date", $delay);

        if ($_POST['locations_id'] == 0) {
			$this->location = "";
            //$_POST['locations_id'] = 0;
        }else{
			$this->location = "AND glpi_tickets.locations_id = {$_POST['locations_id']}";
		}
        
        
        
        if (isset($_POST['status'])) {
            switch ($_POST['status']) {
                case 'new':
                    $this->status = "AND ( `glpi_tickets`.`status` IN ('new') )";
                    break;
                case 'assign':
                    $this->status = "AND ( `glpi_tickets`.`status` IN ('assign') )";
                    break;
                case 'atendimento':
                    $this->status = "AND ( `glpi_tickets`.`status` IN ('atendimento') )";
                    break;
                case 'waiting':
                    $this->status = "AND ( `glpi_tickets`.`status` IN ('waiting') )";
                    break;
                case 'waiting1':
                    $this->status = "AND ( `glpi_tickets`.`status` IN ('waiting1') )";
                    break;
                case 'waiting4':
                    $this->status = "AND ( `glpi_tickets`.`status` IN ('waiting4') )";
                    break;
                case 'solved':
                    $this->status = "AND ( `glpi_tickets`.`status` IN ('solved') )";
                    break;
                case 'closed':
                    $this->status = "AND ( `glpi_tickets`.`status` IN ('closed') )";
                    break;
                case 'approbation':
                    $this->status = "AND ( `glpi_tickets`.`status` IN ('approbation') )";
                    break;
                case 'approbation1':
                    $this->status = "AND ( `glpi_tickets`.`status` IN ('approbation1') )";
                    break;
                case 'canceled':
                    $this->status = "AND ( `glpi_tickets`.`status` IN ('canceled') )";
                    break;
                case 'plan':
                    $this->status = "AND ( `glpi_tickets`.`status` IN ('plan') )";
                    break;
                case 'notold':
                    $this->status = "AND ( `glpi_tickets`.`status` IN ('new','assign','atendimento','waiting','waiting1','waiting4','approbation','approbation1','canceled','plan') )";
                    break;
                case 'notclosed':
                    $this->status = "AND ( `glpi_tickets`.`status` IN ('new','assign','atendimento','waiting','waiting1','waiting4','solved','approbation','approbation1','canceled','plan') )";
                    break;
                case 'process':
                    $this->status = "AND ( `glpi_tickets`.`status` IN ('assign','plan') )";
                    break;
                case 'old':
                    $this->status = "AND ( `glpi_tickets`.`status` IN ('solved','closed') )";
                    break;
                case 'oldest':
                    $this->status = "AND ( `glpi_tickets`.`status` IN ('solved','closed','canceled') )";
                    break;
                case 'all':
                    $this->status = "";
                    break;
            }
            
            //var_export($_POST['status']);
           // die();
        }

        

        $query = "SELECT
                    DISTINCT DATE_FORMAT(date, '%y%m') as month,
                    DATE_FORMAT(date, '%b%y') as month_l,
                    COUNT(id) as nb
                 FROM glpi_tickets
                 WHERE " . $this->sql_date . "
                 AND glpi_tickets.entities_id IN (" . $this->where_entities . ")
                 " . $this->location . "
                 AND glpi_tickets.is_deleted = '0'
                 " . $this->status . "
                 GROUP BY month
                 ORDER BY month";
        //var_export($query);
        $res = $DB->query($query);
        while ($data = $DB->fetch_assoc($res)) {
            $datas['datas'][$data['month_l']] = $data['nb'];
        }

        //curve lines
        $datas['spline'] = true;

        $datas['options']['showLocations'] = true;       
        $datas['options']['force_show_label'] = true;
        $datas['options']['showStatus'] = true;

        return $datas;
        //return $this->reportAreaNbTicket();
    }

    function reportGlineNbTicket() {
        global $DB, $LANG;
        
        $mreporting = new PluginMreportingProfile();
        $permissao = $mreporting->confereIndicadores($_REQUEST['f_name'], $_SESSION["glpiactiveprofile"]["id"]);
                
        if($permissao == false){
            print "<div style='text-align: center;'>Sem permissões de visualização deste indicador.</div>";
            die();
        }
        
        $datas = array();

        $delay = 365;
        $this->sql_date = PluginMreportingMisc::getSQLDate("glpi_tickets.date", $delay);
        
        $query = "SELECT DISTINCT
         DATE_FORMAT(date, '%y%m') as month,
         DATE_FORMAT(date, '%b%y') as month_l,
         status,
         COUNT(id) as nb
      FROM glpi_tickets
      WHERE " . $this->sql_date . "
      AND glpi_tickets.entities_id IN (" . $this->where_entities . ")
      AND glpi_tickets.is_deleted = '0'
      GROUP BY month, status
      ORDER BY month, status";
        $res = $DB->query($query);
        while ($data = $DB->fetch_assoc($res)) {
            $status = Ticket::getStatus($data['status']);
            $datas['labels2'][$data['month_l']] = $data['month_l'];
            $datas['datas'][$status][$data['month_l']] = $data['nb'];
        }
        
        //var_export($datas);

        //curve lines
        $datas['spline'] = true;

        $datas['options']['showPrinterModels'] = true;
        $datas['options']['force_show_label'] = true;

        return $datas;
    }

    function reportGareaNbTicket() {
        return $this->reportGlineNbTicket();
    }

    function reportLineFluxoChamadosPorPeriodo() {
        global $DB, $LANG;
        
        $mreporting = new PluginMreportingProfile();
        $permissao = $mreporting->confereIndicadores($_REQUEST['f_name'], $_SESSION["glpiactiveprofile"]["id"]);
                
        if($permissao == false){
            print "<div style='text-align: center;'>Sem permissões de visualização deste indicador.</div>";
            die();
        }
        $graph = new PluginMreportingGraph();
        $delay = 30;
        $this->sql_date = PluginMreportingMisc::getSQLDate("glpi_tickets.date", $delay);

        //Gráfico por hora
        $datas = array();
        $query = "	select count(hour(glpi_tickets.date)) as count, hour(glpi_tickets.date) as hora
						from glpi_tickets
						WHERE " . $this->sql_date . "
						group by hour(glpi_tickets.date) ";
        $res = $DB->query($query);
        $date1 = $_REQUEST["date1"] . " 00:00:00";
        $date2 = $_REQUEST["date2"] . " 23:59:59";
        $diff = abs(strtotime($date2) - strtotime($date1));
        $dias = floor($diff / (60 * 60 * 24));
        //adiciona 1 dia para ajustar o range
        $dias++;

        while ($data = $DB->fetch_assoc($res)) {
            $datas['datas'][$data['hora'] . "h"] = number_format($data['count'] / $dias, 2);
        }
        $graph->showArea($datas, $LANG['plugin_mreporting']['Helpdesk']['reportLineFluxoChamadosPorHora']['title'], '');

        //Gráfico por dia da semana
        $datas = array();
        $date_where1 = PluginMreportingMisc::getSQLDate("t2.date", $delay);
        $date_where2 = PluginMreportingMisc::getSQLDate("glpi_tickets.date", $delay);
        $query = "	select count(hour(glpi_tickets.date)),
							hour(glpi_tickets.date),
							case WEEKDAY(glpi_tickets.date)
							when 0 then 'Seg'
							when 1 then 'Ter'
							when 2 then 'Qua'
							when 3 then 'Qui'
							when 4 then 'Sex'
							when 5 then 'Sáb'
							when 6 then 'Dom'
							end as weekday,
							(select count(hour(t2.date))
							from glpi_tickets as t2
							where " . $date_where1 . "
							and WEEKDAY(t2.date) = WEEKDAY(glpi_tickets.date)
							group by WEEKDAY(t2.date)
							order by WEEKDAY(t2.date) ) as weektotal

						from glpi_tickets
						where " . $date_where2 . "
						group by WEEKDAY(glpi_tickets.date)
						order by WEEKDAY(glpi_tickets.date) ";
        $result = $DB->query($query);

        $datas['datas']['Dom'] = 0;
        $datas['datas']['Seg'] = 0;
        $datas['datas']['Ter'] = 0;
        $datas['datas']['Qua'] = 0;
        $datas['datas']['Qui'] = 0;
        $datas['datas']['Sex'] = 0;
        $datas['datas']['Sáb'] = 0;

        while ($data = $DB->fetch_assoc($result)) {
            $datas['datas'][$data['weekday']] = $data['weektotal'];
        }

        $start = strtotime($_REQUEST["date1"] . " 00:00:00");
        $end = strtotime($_REQUEST["date2"] . " 23:59:59");

        $datas['datas']['Dom'] = $datas['datas']['Dom'] / $this->number_of_days(0, $start, $end); // SUNDAY
        $datas['datas']['Seg'] = $datas['datas']['Seg'] / $this->number_of_days(1, $start, $end); // MONDAY
        $datas['datas']['Ter'] = $datas['datas']['Ter'] / $this->number_of_days(2, $start, $end); // TUESDAY
        $datas['datas']['Qua'] = $datas['datas']['Qua'] / $this->number_of_days(3, $start, $end); // WEDNESDAY
        $datas['datas']['Qui'] = $datas['datas']['Qui'] / $this->number_of_days(4, $start, $end); // THURSDAY
        $datas['datas']['Sex'] = $datas['datas']['Sex'] / $this->number_of_days(5, $start, $end); // FRIDAY
        $datas['datas']['Sáb'] = $datas['datas']['Sáb'] / $this->number_of_days(6, $start, $end); // SATURDAY

        $graph->showArea($datas, $LANG['plugin_mreporting']['Helpdesk']['reportLineFluxoChamadosPorDiaSemana']['title'], '');

        return null;
    }

    function number_of_days($day, $start, $end) {
        $ONE_WEEK = 604800; // 7 * 24 * 60 * 60
        $w = array(date('w', $start), date('w', $end));

        $return = floor(( $end - $start ) / $ONE_WEEK) + ( $w[0] > $w[1] ? $w[0] <= $day || $day <= $w[1] : $w[0] <= $day && $day <= $w[1] );
        return $return == 0 ? 1 : $return;
    }

    function reportHbarTrocaTonerPorEntidade() {
        global $DB, $LANG;
        
        $mreporting = new PluginMreportingProfile();
        $permissao = $mreporting->confereIndicadores($_REQUEST['f_name'], $_SESSION["glpiactiveprofile"]["id"]);
                
        if($permissao == false){
            print "<div style='text-align: center;'>Sem permissões de visualização deste indicador.</div>";
            die();
        }

        $graph = new PluginMreportingGraph();

        $datas = array();
        $delay = 365;
        $this->sql_date = PluginMreportingMisc::getSQLDateSupri("glpi_cartridges.date_out", $delay);
        $where_printermodel = "`glpi_printers`.`printermodels_id` = " . (isset($_REQUEST["printermodels_id"]) && $_REQUEST["printermodels_id"] > 0 ? $_REQUEST["printermodels_id"] : "`glpi_printers`.`printermodels_id`");

        $query = "SELECT count(*) as nb,
                        glpi_cartridges.id,
                        glpi_cartridges.printers_id,
                        glpi_cartridges.date_out,
                        CONCAT(`glpi_cartridgeitems`.`name`, ' - ', `glpi_cartridgeitemtypes`.`name`) AS tipo,
                        DATE_FORMAT(glpi_cartridges.date_out, '%y%m') as ordem,
                        DATE_FORMAT(glpi_cartridges.date_out, '%b%y') as month_l,
                        (
                                SELECT entity_id_to
                                FROM supridesk_log_printer_entity l
                                WHERE l.printers_id = `glpi_cartridges`.printers_id
                                  AND l.date <= `glpi_cartridges`.date_out
                                order by date desc
                                LIMIT 1
                        ) as id_entity_pre_troca,
                        (
                                SELECT entity_id_from
                                FROM supridesk_log_printer_entity l
                                WHERE l.printers_id = `glpi_cartridges`.printers_id
                                  AND l.date >= `glpi_cartridges`.date_out
                                order by date asc
                                LIMIT 1
                        ) as id_entity_first_2012,
                        (
                                SELECT id
                                FROM glpi_entities
                                WHERE id = (SELECT entities_id FROM glpi_printers where id = `glpi_cartridges`.printers_id)
                        ) as id_entity_atual,
                        glpi_infocoms.value as valor,
                        glpi_printermodels.name as printer_model

                FROM `glpi_cartridges`,
                     `glpi_cartridgeitems_printermodels`,
                     `glpi_printers`,
                     `glpi_printermodels`,
                     `glpi_cartridgeitemtypes`,
                     `glpi_cartridgeitems` LEFT JOIN `glpi_infocoms` ON ( glpi_cartridgeitems.id = glpi_infocoms.items_id and glpi_infocoms.itemtype = 'CartridgeItem')

                WHERE `glpi_cartridgeitemtypes`.`id` = `glpi_cartridgeitems`.`cartridgeitemtypes_id`
                AND `glpi_cartridges`.`cartridgeitems_id` = `glpi_cartridgeitems`.`id`
                AND `glpi_cartridges`.`printers_id` = `glpi_printers`.`id`
                AND `glpi_printers`.`printermodels_id` = `glpi_cartridgeitems_printermodels`.`printermodels_id`
                AND `glpi_cartridgeitems_printermodels`.`cartridgeitems_id` = `glpi_cartridgeitems`.`id`
                AND " . $where_printermodel . "
                AND " . $this->sql_date . "
                and glpi_printers.printermodels_id = glpi_printermodels.id

                GROUP BY tipo, ordem, month_l, id_entity_pre_troca, id_entity_first_2012, id_entity_atual
                HAVING
                        (id_entity_pre_troca is not null and id_entity_pre_troca IN (" . $this->where_entities . ")  )
                        or
                        ( id_entity_pre_troca is null and id_entity_first_2012 is not null and id_entity_first_2012 IN (" . $this->where_entities . ") )
                        or
                        ( id_entity_pre_troca is null and id_entity_first_2012 is null and id_entity_atual IN (" . $this->where_entities . ") )
                ORDER BY tipo, ordem";
        $res = $DB->query($query);

        $meses = Array();
        $startDate = strtotime($_REQUEST["date1"]);
        $endDate = strtotime($_REQUEST["date2"]);
        $currentDate = $startDate;
        while ($endDate >= $currentDate) {
            $formattedDate = date('My', $currentDate);
            $meses[] = $formattedDate;
            $datas['labels2'][$formattedDate] = $formattedDate;
            $currentDate = strtotime(date('Y/m/01/', $currentDate) . ' +1 month');
        }

        //zera o array de cada item
        if (mysql_num_rows($res) > 0)
            mysql_data_seek($res, 0);
        while ($data = $DB->fetch_assoc($res)) {
            foreach ($meses as $mes) {
                $datas['datas'][$data['tipo']][$mes] = 0;
            }
        }

        if (mysql_num_rows($res) > 0)
            mysql_data_seek($res, 0);
        while ($data = $DB->fetch_assoc($res)) {
            $sumTipo = $datas['datas'][$data['tipo']][$data['month_l']] + $data['nb'];
            $datas['datas'][$data['tipo']][$data['month_l']] = $sumTipo;
            $sum_array = array_sum($datas['datas'][$data['tipo']]);
            $total[$data['tipo']] = $sum_array;
        }

        if (mysql_num_rows($res) > 0)
            mysql_data_seek($res, 0);
        while ($data = $DB->fetch_assoc($res)) {
            $datas['datas'][$data['tipo']] = $total[$data['tipo']];
        }

        $datas['options']['showPrinterModels'] = true;
        $datas['options']['force_show_label'] = true;

        return $datas;
    }

    function reportHgbarMediaImpressaoTrocaTonerImpressora() {
        global $DB, $LANG;
        
        $mreporting = new PluginMreportingProfile();
        $permissao = $mreporting->confereIndicadores($_REQUEST['f_name'], $_SESSION["glpiactiveprofile"]["id"]);
                
        if($permissao == false){
            print "<div style='text-align: center;'>Sem permissões de visualização deste indicador.</div>";
            die();
        }
        
        $datas = array();

        $graph = new PluginMreportingGraph();
        $delay = 30;
        $this->sql_date = PluginMreportingMisc::getSQLDateSupri("glpi_cartridges.date_out", $delay);

        $where_printermodel = "`glpi_printers`.`printermodels_id` = " . (isset($_REQUEST["printermodels_id"]) && $_REQUEST["printermodels_id"] > 0 ? $_REQUEST["printermodels_id"] : "`glpi_printers`.`printermodels_id`");
        $where_printermodel2 = "`glpi_printers2`.`printermodels_id` = " . (isset($_REQUEST["printermodels_id"]) && $_REQUEST["printermodels_id"] > 0 ? $_REQUEST["printermodels_id"] : "`glpi_printers2`.`printermodels_id`");

        $query = "
				SELECT
					`glpi_cartridges`.`id`,
					`glpi_cartridgeitems`.`name` AS nome,
					`glpi_cartridgeitemtypes`.`id` AS idtipo,
					`glpi_cartridgeitemtypes`.`name` AS tipo,
					`glpi_cartridges`.`date_out`,
					`glpi_cartridges`.`pages`,
					`glpi_printers`.`printermodels_id`,
					`glpi_cartridges`.`printers_id`,
					`glpi_printermodels`.`name`,
					CEIL(AVG( `glpi_cartridges`.`pages` - COALESCE((
						SELECT
							`glpi_cartridges2`.`pages`
						FROM
							`glpi_cartridges` as glpi_cartridges2,
							`glpi_cartridgeitems` as glpi_cartridgeitems2,
							`glpi_cartridgeitems_printermodels` as glpi_cartridgeitems_printermodels2,
							`glpi_printers` as glpi_printers2,
							`glpi_cartridgeitemtypes` as glpi_cartridgeitemtypes2
						WHERE `glpi_cartridgeitemtypes2`.`id` = `glpi_cartridgeitems2`.`cartridgeitemtypes_id`
							AND `glpi_cartridges2`.`cartridgeitems_id` = `glpi_cartridgeitems2`.`id`
							AND `glpi_cartridges2`.`printers_id` = `glpi_printers2`.`id`
							AND `glpi_printers2`.`printermodels_id` = `glpi_cartridgeitems_printermodels2`.`printermodels_id`
							AND " . $where_printermodel2 . "
							AND `glpi_cartridgeitems_printermodels2`.`cartridgeitems_id` = `glpi_cartridgeitems2`.`id`
							AND glpi_printers.entities_id IN (" . $this->where_entities . ")
							AND ( `glpi_cartridges2`.`date_out` IS NOT NULL AND glpi_cartridges2.date_out < `glpi_cartridges`.`date_out` )
							AND `glpi_cartridges2`.`printers_id` = `glpi_cartridges`.`printers_id`
							AND `glpi_cartridgeitems2`.`cartridgeitemtypes_id` in (SELECT `glpi_cartridgeitemtypes`.`id` UNION SELECT cartridgeitemtypes_id2 FROM glpi_cartridgeitemtypes_cartridgeitemtypes citcit where citcit.cartridgeitemtypes_id = `glpi_cartridgeitemtypes`.`id`)
							and glpi_cartridgeitems.is_deleted = 0
						ORDER BY
							`glpi_printers2`.`printermodels_id`,
							`glpi_cartridges2`.`date_out` DESC,
							`glpi_cartridgeitems2`.`name`,
							`glpi_cartridgeitemtypes2`.`name`
						LIMIT 1
					), 0))) as Saldo
				FROM
					`glpi_cartridges`,
					`glpi_cartridgeitems`,
					`glpi_cartridgeitems_printermodels`,
					`glpi_printermodels`,
					`glpi_printers`,
					`glpi_cartridgeitemtypes`
				WHERE `glpi_cartridgeitemtypes`.`id` = `glpi_cartridgeitems`.`cartridgeitemtypes_id`
					AND `glpi_cartridges`.`cartridgeitems_id` = `glpi_cartridgeitems`.`id`
					AND `glpi_cartridges`.`printers_id` = `glpi_printers`.`id`
					AND `glpi_printers`.`printermodels_id` = `glpi_cartridgeitems_printermodels`.`printermodels_id`
					AND `glpi_printermodels`.`id` = `glpi_cartridgeitems_printermodels`.`printermodels_id`
					AND " . $where_printermodel . "
					and glpi_cartridgeitems.is_deleted = 0
					AND `glpi_cartridgeitems_printermodels`.`cartridgeitems_id` = `glpi_cartridgeitems`.`id`
					AND glpi_printers.entities_id IN (" . $this->where_entities . ")
					AND " . $this->sql_date . "
				GROUP BY
					`glpi_cartridgeitems`.`name`,
					`glpi_cartridgeitemtypes`.`id`,
					`glpi_cartridgeitemtypes`.`name`,
					`glpi_printers`.`printermodels_id`
				ORDER BY
					`glpi_cartridgeitemtypes`.`name`,
					`glpi_printers`.`printermodels_id`,
					`glpi_cartridgeitems`.`name`,
					`glpi_cartridges`.`date_out`,
					`glpi_cartridgeitemtypes`.`name`
      ";
        $result = $DB->query($query);

        while ($data = $DB->fetch_assoc($result)) {
            $datas['labels2']['type_' . $data['idtipo']] = $data['tipo'];
        }

        //zera o array de cada impressora
        if (mysql_num_rows($result) > 0)
            mysql_data_seek($result, 0);
        while ($data = $DB->fetch_assoc($result)) {
            foreach (array_keys($datas['labels2']) as $key) {
                if (array_key_exists('datas', $datas) && !array_key_exists($key, $datas['datas'][$data['name']]))
                    $datas['datas'][$data['name']][$key] = 0;
            }
        }

        //seto o valor do tipo para esta impressora
        if (mysql_num_rows($result) > 0)
            mysql_data_seek($result, 0);
        while ($data = $DB->fetch_assoc($result)) {
            $datas['datas'][$data['name']]['type_' . $data['idtipo']] = $data['Saldo'];
        }

        //opções do gráfico
        $datas['options']['showPrinterModels'] = true;
        $datas['options']['force_show_label'] = true;

        return $datas;
    }

    function reportHgbarChamadosPorAnalista() {
        global $DB, $LANG;
        
        $mreporting = new PluginMreportingProfile();
        $permissao = $mreporting->confereIndicadores($_REQUEST['f_name'], $_SESSION["glpiactiveprofile"]["id"]);
                
        if($permissao == false){
            print "<div style='text-align: center;'>Sem permissões de visualização deste indicador.</div>";
            die();
        }
        
        $datas = array();

        $graph = new PluginMreportingGraph();
        $delay = 30;
        $tDate = PluginMreportingMisc::getSQLDateSupri("t.date", $delay);
        $tSolveDate = PluginMreportingMisc::getSQLDateSupri("t.solvedate", $delay);
        $tfDate = PluginMreportingMisc::getSQLDateSupri("tf.date", $delay);

        if (isset($_REQUEST["usercategories_id"]) && $_REQUEST["usercategories_id"] > 0)
            $where_users_id = "`u`.`usercategories_id` = " . $_REQUEST["usercategories_id"];
        else
            $where_users_id = "u.usercategories_id in (1,2,3,4)";

        $query = "	SELECT u.id, concat( u.firstname, ' ',  u.realname) as usuario
						FROM glpi_users u, glpi_usercategories uc
						WHERE u.usercategories_id = uc.id
						  AND u.is_active = 1
						  AND u.is_deleted = 0
						  AND " . $where_users_id . "
						ORDER BY u.firstname ";
        $result = $DB->query($query);

        $datas['labels2']['chamados_abertos'] = "Chamados abertos para o analista no Período";
        $datas['labels2']['chamados_abertos_pelo_usuario'] = "Chamados abertos pelo analista no Período";
        $datas['labels2']['chamados_solucionados'] = "Chamados solucionados no Período";
        $datas['labels2']['iteracoes_chamados'] = "Iterações em chamados no Período";
        $datas['labels2']['chamados_em_abertos'] = "Chamados em aberto até a data final do Período";

        //zera o array de cada impressora
        if (mysql_num_rows($result) > 0)
            mysql_data_seek($result, 0);
        while ($data = $DB->fetch_assoc($result)) {            
            foreach (array_keys($datas['labels2']) as $key) {
                //print 'teste';
                if (is_array($datas) && array_key_exists('datas', $datas))
                    //print $data['usuario'] . " - ". $data['usuario'] . "<br>";
                    //echo 'entrou';
                    if (is_array($datas['datas']) && !array_key_exists($key, $datas['datas'][$data['usuario']]))
                        $datas['datas'][$data['usuario']][$key] = 0;
            }
        }
        
        //seto o valor do tipo para esta impressora
        if (mysql_num_rows($result) > 0)
            mysql_data_seek($result, 0);
        while ($data = $DB->fetch_assoc($result)) {
            $sqlCounter = "SELECT COUNT(*) as count
								FROM
									glpi_tickets t,
									glpi_tickets_users tu
									left join glpi_users u ON u.id = tu.users_id
								WHERE t.id = tu.tickets_id
								  AND t.entities_id IN (" . $this->where_entities . ")
								  AND tu.type = 2
								  AND tu.users_id = " . $data['id'] . "
								  AND " . $tDate;

            $resCounter = $DB->query($sqlCounter);
            $datas['datas'][$data['usuario']]['chamados_abertos'] = $DB->result($resCounter, 0, 'count');

            $sqlCounter = "SELECT COUNT(*) as count
								FROM
									glpi_tickets t
									left join glpi_users u ON u.id = t.users_id_recipient
								WHERE t.entities_id IN (" . $this->where_entities . ")
								  AND t.requesttypes_id <> 7
								  AND t.users_id_recipient = " . $data['id'] . "
								  AND " . $tDate;

            $resCounter = $DB->query($sqlCounter);
            $datas['datas'][$data['usuario']]['chamados_abertos_pelo_usuario'] = $DB->result($resCounter, 0, 'count');

            $sqlCounter = "
				select count(*) as count
				from
					glpi_tickets t,
					glpi_tickets_users tu
					left join glpi_users u ON u.id = tu.users_id
				where t.id = tu.tickets_id
					AND t.entities_id IN (" . $this->where_entities . ")
					and tu.type = 2
					and tu.users_id = " . $data['id'] . "
					and " . $tSolveDate;
            $resCounter = $DB->query($sqlCounter);
            $datas['datas'][$data['usuario']]['chamados_solucionados'] = $DB->result($resCounter, 0, 'count');

            $sqlCounter = "
				select count(*) as count
				from
					glpi_tickets t
					LEFT JOIN glpi_ticketfollowups tf ON t.id = tf.tickets_id
				where tf.users_id = " . $data['id'] . "
					AND t.entities_id IN (" . $this->where_entities . ")
					and " . $tfDate;

            $resCounter = $DB->query($sqlCounter);
            $datas['datas'][$data['usuario']]['iteracoes_chamados'] = $DB->result($resCounter, 0, 'count');

            $date2 = $_REQUEST["date2"] . " 23:59:59";
            $sqlCounter = "
				select count(*) as count
				from
					glpi_tickets t,
					glpi_tickets_users tu
					left join glpi_users u ON u.id = tu.users_id
				where t.id = tu.tickets_id
					AND t.entities_id IN (" . $this->where_entities . ")
					and tu.type = 2
					and tu.users_id = " . $data['id'] . "
					and t.status != 'closed' and t.status != 'solved' and t.status != 'canceled'
					and t.date <= '" . $date2 . "'";
            $resCounter = $DB->query($sqlCounter);
            $datas['datas'][$data['usuario']]['chamados_em_abertos'] = $DB->result($resCounter, 0, 'count');
        }

        //opções do gráfico
        if (!isset($_REQUEST["usercategories_id"]) || intval($_REQUEST["usercategories_id"]) == 0)
            $_REQUEST["usercategories_id"] = true;
        $datas['options']['showAnalistas'] = $_REQUEST["usercategories_id"];
        $datas['options']['force_show_label'] = true;

        return $datas;
    }

    function reportGlineTicketRequestTypePeriod() {
        global $DB, $LANG;
        
        $mreporting = new PluginMreportingProfile();
        $permissao = $mreporting->confereIndicadores($_REQUEST['f_name'], $_SESSION["glpiactiveprofile"]["id"]);
                
        if($permissao == false){
            print "<div style='text-align: center;'>Sem permissões de visualização deste indicador.</div>";
            die();
        }
        $datas = array();

        $graph = new PluginMreportingGraph();
        $delay = 0;
        $this->sql_date = PluginMreportingMisc::getSQLDateSupri("t.date", $delay);

        $query = "SELECT DISTINCT
         rq.name as status,
         COUNT(t.id) as nb
		from glpi_tickets t, glpi_requesttypes rq
      WHERE " . $this->sql_date . "
		AND t.requesttypes_id = rq.id
      AND t.is_deleted = '0'
      GROUP BY rq.name
      ORDER BY rq.name";
        $res = $DB->query($query);
        while ($data = $DB->fetch_assoc($res)) {
            $datas['datas'][$data['status']] = $data['nb'];
        }
        $graph->showHbar($datas, $LANG['plugin_mreporting']['Helpdesk']['reportGlineTicketRequestTypePeriod']['title'], '', 'always');


        $datas = array();
        $delay = 365;
        $this->sql_date = PluginMreportingMisc::getSQLDateSupri("t.date", $delay, true);

        $query = "SELECT DISTINCT
         DATE_FORMAT(date, '%y%m') as month,
         DATE_FORMAT(date, '%b%y') as month_l,
         rq.name as status,
         COUNT(t.id) as nb
		from glpi_tickets t, glpi_requesttypes rq
      WHERE " . $this->sql_date . "
		AND t.requesttypes_id = rq.id
      AND t.is_deleted = '0'
      GROUP BY month, rq.name
      ORDER BY month, rq.name";
        $res = $DB->query($query);

        while ($data = $DB->fetch_assoc($res)) {
            $status = $data['status'];
            $datas['labels2'][$data['month_l']] = $data['month_l'];
            $datas['datas'][$status][$data['month_l']] = $data['nb'];
        }

        //curve lines
        $datas['spline'] = true;

        $graph->showGline($datas, $LANG['plugin_mreporting']['Helpdesk']['reportGlineTicketRequestTypePeriodEvolution']['title'], '');

        return null;
    }

    function reportGlineEstoque() {
        global $DB, $LANG;
        
        $mreporting = new PluginMreportingProfile();
        $permissao = $mreporting->confereIndicadores($_REQUEST['f_name'], $_SESSION["glpiactiveprofile"]["id"]);
                
        if($permissao == false){
            print "<div style='text-align: center;'>Sem permissões de visualização deste indicador.</div>";
            die();
        }

        $graph = new PluginMreportingGraph();

        $datas = array();
        $delay = 365;
        $this->sql_date = PluginMreportingMisc::getSQLDateSupri("glpi_cartridges.date_out", $delay);
        $where_printermodel = "`glpi_printers`.`printermodels_id` = " . (isset($_REQUEST["printermodels_id"]) && $_REQUEST["printermodels_id"] > 0 ? $_REQUEST["printermodels_id"] : "`glpi_printers`.`printermodels_id`");

        $query = "	SELECT count(*) as nb,
						glpi_cartridges.id,
						glpi_cartridges.printers_id,
						glpi_cartridges.date_out,
						`glpi_cartridgeitemtypes`.`name` AS tipo,
						DATE_FORMAT(glpi_cartridges.date_out, '%y%m') as ordem,
						DATE_FORMAT(glpi_cartridges.date_out, '%b%y') as month_l,
							(
								SELECT entity_id_to
								FROM supridesk_log_printer_entity l
								WHERE l.printers_id = `glpi_cartridges`.printers_id
								  AND l.date <= `glpi_cartridges`.date_out
								order by date desc
								LIMIT 1
							) as id_entity_pre_troca,
							(
								SELECT entity_id_from
								FROM supridesk_log_printer_entity l
								WHERE l.printers_id = `glpi_cartridges`.printers_id
								  AND l.date >= `glpi_cartridges`.date_out
								order by date asc
								LIMIT 1
							) as id_entity_first_2012,
							(
								SELECT id
								FROM glpi_entities
								WHERE id = (SELECT entities_id FROM glpi_printers where id = `glpi_cartridges`.printers_id)
							) as id_entity_atual
						FROM `glpi_cartridges`,
						`glpi_cartridgeitems`,
						`glpi_cartridgeitems_printermodels`,
						`glpi_printers`,
						`glpi_cartridgeitemtypes`
						WHERE `glpi_cartridgeitemtypes`.`id` = `glpi_cartridgeitems`.`cartridgeitemtypes_id`
						AND `glpi_cartridges`.`cartridgeitems_id` = `glpi_cartridgeitems`.`id`
						AND `glpi_cartridges`.`printers_id` = `glpi_printers`.`id`
						AND `glpi_printers`.`printermodels_id` = `glpi_cartridgeitems_printermodels`.`printermodels_id`
						AND `glpi_cartridgeitems_printermodels`.`cartridgeitems_id` = `glpi_cartridgeitems`.`id`
						AND " . $where_printermodel . "
						AND " . $this->sql_date . "
						GROUP BY tipo, ordem, month_l, id_entity_pre_troca, id_entity_first_2012, id_entity_atual
						HAVING
							(id_entity_pre_troca is not null and id_entity_pre_troca IN (" . $this->where_entities . ")  )
							or
							( id_entity_pre_troca is null and id_entity_first_2012 is not null and id_entity_first_2012 IN (" . $this->where_entities . ") )
							or
							( id_entity_pre_troca is null and id_entity_first_2012 is null and id_entity_atual IN (" . $this->where_entities . ") )
						ORDER BY tipo, ordem";
        $res = $DB->query($query);

        $meses = Array();
        //preenche labels2
        /*
          while ($data = $DB->fetch_assoc($res2))
          {
          $meses[] = $data['month_l'];
          $datas['labels2'][$data['month_l']] = $data['month_l'];
          }
          $datas['labels2']["Feb12"] = $data["Feb12"];
         */

        $startDate = strtotime($_REQUEST["date1"]);
        $endDate = strtotime($_REQUEST["date2"]);
        $currentDate = $startDate;
        while ($endDate >= $currentDate) {
            $formattedDate = date('My', $currentDate);
            $meses[] = $formattedDate;
            $datas['labels2'][$formattedDate] = $formattedDate;
            //echo "<br>" . date('My',$currentDate);
            //$currentDate = strtotime( date('Y/m/01/',$currentDate).' -1 month');
            $currentDate = strtotime(date('Y/m/01/', $currentDate) . ' +1 month');
        }

        //zera o array de cada item
        if (mysql_num_rows($res) > 0)
            mysql_data_seek($res, 0);
        while ($data = $DB->fetch_assoc($res)) {
            foreach ($meses as $mes) {
                $datas['datas'][$data['tipo']][$mes] = 0;
            }
        }
        //var_export($datas);
        if (mysql_num_rows($res) > 0)
            mysql_data_seek($res, 0);
        //$lastTipo = "";
        //$sumTipo = 0;
        while ($data = $DB->fetch_assoc($res)) {
            /*
              $sumTipo++;
              if ($lastTipo == "")
              {
              $lastTipo = $data['tipo'];
              }
              else
              {
              if ($lastTipo != $data['tipo'])
              {
              $datas['datas'][$data['tipo']][$data['month_l']] = $sumTipo;
              $sumTipo = 0;
              }
              }
             */

            $sumTipo = $datas['datas'][$data['tipo']][$data['month_l']] + $data['nb'];
            $datas['datas'][$data['tipo']][$data['month_l']] = $sumTipo;
        }

        //curve lines
        $datas['spline'] = true;
        $datas['options']['showPrinterModels'] = true;
        $datas['options']['force_show_label'] = true;

        $graph->showGline($datas, $LANG['plugin_mreporting']['Helpdesk']['reportGlineEstoque']['title'], '');

        return null;
    }

    function reportGlineTelefoniaIP() {
        global $DB, $LANG;
        
        $mreporting = new PluginMreportingProfile();
        $permissao = $mreporting->confereIndicadores($_REQUEST['f_name'], $_SESSION["glpiactiveprofile"]["id"]);
                
        if($permissao == false){
            print "<div style='text-align: center;'>Sem permissões de visualização deste indicador.</div>";
            die();
        }

        $graph = new PluginMreportingGraph();

        $datas = array();
        $delay = 365;
        $this->sql_date = PluginMreportingMisc::getSQLDateSupri("calldate", $delay);
        $meses = Array();
        $startDate = strtotime($_REQUEST["date1"]);
        $endDate = strtotime($_REQUEST["date2"]);
        $currentDate = $startDate;
        while ($endDate >= $currentDate) {
            $formattedDate = date('My', $currentDate);
            $meses[] = $formattedDate;
            $datas['labels2'][$formattedDate] = $formattedDate;
            //echo "<br>" . date('My',$currentDate);
            //$currentDate = strtotime( date('Y/m/01/',$currentDate).' -1 month');
            $currentDate = strtotime(date('Y/m/01/', $currentDate) . ' +1 month');
        }


        // Conexão com ASTERISK
        $conn = mysql_connect("10.10.10.76", "fdazzi", "frdazzi1980") or
                die("Não foi possível conectar ao banco do Asterisk");
        mysql_select_db("asteriskcdrdb");


        // TEMPOS MÉDIOS
        $tipos = array("TOTAL", "ATENDIMENTO", "RESOLUÇÃO", "ESPERA_ANTES", "TME-AT", "TME-NAT");
        foreach ($tipos as $tipo) {
            // Tempo Médio de Chamadas Totais Entrantes
            if ($tipo == "TOTAL") {
                //echo "<b>$tipo</b><br>";
                foreach ($meses as $mes) {
                    $query = "
                        SELECT
                            avg(duration) AS MEDIA
                        FROM
                            cdr
                        WHERE
                            DATE_FORMAT(calldate, '%b%y') = '$mes'
                            and char_length(src) > 4
                            and ((dst like '110%' or dst = '7700')
                                or dcontext = 'from-did-direct'
                                )
                            and disposition = 'ANSWERED'";
                    //echo "$query<br>";
                    $res = mysql_query($query);
                    $val = mysql_fetch_assoc($res);
                    $datas['datas'][$tipo][$mes] = number_format($val['MEDIA'], 2);
                    //$datas['datas'][$tipo][$mes] = '02:34';
                }
            }
            // Tempo Médio Total de Chamadas Entrantes para atendimento
            if ($tipo == "ATENDIMENTO") {
                //echo "<b>$tipo</b><br>";
                foreach ($meses as $mes) {
                    $query = "
                        SELECT
                            avg(duration) AS MEDIA
                        FROM
                            cdr
                        WHERE
                            DATE_FORMAT(calldate, '%b%y') = '$mes'
                            and char_length(src) > 4
                            and (dst like '110%' or dst = '7700')
                            and disposition = 'ANSWERED'";
                    //echo "$query<br>";
                    $res = mysql_query($query);
                    $val = mysql_fetch_assoc($res);
                    $texto = "CALL CENTER";
                    $datas['datas'][$texto][$mes] = number_format($val['MEDIA'], 2);
                    //$datas['datas'][$tipo][$mes] = '02:34';
                }
            }
            // Tempo Médio Total de Chamadas Entrantes para atendimento
            if ($tipo == "RESOLUÇÃO") {
                //echo "<b>$tipo</b><br>";
                foreach ($meses as $mes) {
                    $query = "
                        SELECT
                            avg(billsec) AS MEDIA
                        FROM
                            cdr
                        WHERE
                            DATE_FORMAT(calldate, '%b%y') = '$mes'
                            and char_length(src) > 4
                            #and dst in ('6660','6661','6618','6624')
                            and disposition = 'ANSWERED'";
                    //echo "$query<br>";
                    $res = mysql_query($query);
                    $val = mysql_fetch_assoc($res);
                    $datas['datas'][$tipo][$mes] = number_format($val['MEDIA'], 2);
                    //$datas['datas'][$tipo][$mes] = '02:34';
                }
            }

            // Tempo Médio Total de Antes da Chamada ser Atendida
            if ($tipo == "TME-AT") {
                //echo "<b>$tipo</b><br>";
                foreach ($meses as $mes) {
                    $query = "
                        SELECT
                            avg(duration-billsec) AS MEDIA
                        FROM
                            cdr
                        WHERE
                            DATE_FORMAT(calldate, '%b%y') = '$mes'
                            and char_length(src) > 4
                            and disposition = 'ANSWERED'
                            and dst like '666%'
                            and
                            (
                                dst like '666%'
                                or
                                (
                                        (dst = '6615' or dst = '6620' or dst = '6631') # Thereza, Claudiana ou outro que não foi ligação direta e vindo do call center
                                        and channel like '%666%' and dcontext <> 'from-did-direct'
                                )
                             )";
                    //echo "$query<br>";
                    $res = mysql_query($query);
                    $val = mysql_fetch_assoc($res);
                    $texto = "TME - ATENDIDAS (CALL CENTER)";
                    $datas['datas'][$texto][$mes] = number_format($val['MEDIA'], 2);
                    //$datas['datas'][$tipo][$mes] = '02:34';
                }
            }

            // Tempo Médio Total de Antes da Chamada ser Abandonada
            if ($tipo == "TME-NAT") {
                //echo "<b>$tipo</b><br>";
                foreach ($meses as $mes) {
                    $query = "
                        SELECT
                            avg(duration-billsec) AS MEDIA
                        FROM
                            cdr
                        WHERE
                            DATE_FORMAT(calldate, '%b%y') = '$mes'
                            and char_length(src) > 4
                            and disposition = 'NO ANSWER'
                            and dst like '666%'
                            and
                            (
                                dst like '666%'
                                or
                                (
                                        (dst = '6615' or dst = '6620' or dst = '6631') # Thereza, Claudiana ou outro que não foi ligação direta e vindo do call center
                                        and channel like '%666%' and dcontext <> 'from-did-direct'
                                )
                             )";
                    //echo "$query<br>";
                    $res = mysql_query($query);
                    $val = mysql_fetch_assoc($res);
                    $texto = "TME - ABANDONADAS (CALL CENTER)";
                    $datas['datas'][$texto][$mes] = number_format($val['MEDIA'], 2);
                    //$datas['datas'][$tipo][$mes] = '02:34';
                }
            }
        }

        //curve lines
        //$datas['spline'] = true;
        //$datas['options']['showPrinterModels'] = true;
        $datas['options']['showRamalSrc'] = true;
        //$datas['options']['force_show_label'] = true;
        //$graph->showGline($datas, $LANG['plugin_mreporting']['Helpdesk']['reportGlineTeste']['title'], '');
        $title = 'Tempo Médio de Chamadas Atendidas - Externas (segundos)';
        $desc = '';
        $graph->showGline($datas, $title, $desc);




        // QUANTIDADE DE LIGAÇÕES
        $graph2 = new PluginMreportingGraph();

        $datas = null;
        $delay = 365;
        $this->sql_date = PluginMreportingMisc::getSQLDateSupri("calldate", $delay);
        $meses = Array();
        $startDate = strtotime($_REQUEST["date1"]);
        $endDate = strtotime($_REQUEST["date2"]);
        $currentDate = $startDate;
        while ($endDate >= $currentDate) {
            $formattedDate = date('My', $currentDate);
            $meses[] = $formattedDate;
            $datas['labels2'][$formattedDate] = $formattedDate;
            //echo "<br>" . date('My',$currentDate);
            //$currentDate = strtotime( date('Y/m/01/',$currentDate).' -1 month');
            $currentDate = strtotime(date('Y/m/01/', $currentDate) . ' +1 month');
        }

        $tipos = null;
        $tipos = array("ENTRANTES", "EFETUADAS", "ABANDONO");
        foreach ($tipos as $tipo) {
            // Quantidade de Chamadas Entrantes
            if ($tipo == "ENTRANTES") {
                //echo "<b>$tipo</b><br>";
                foreach ($meses as $mes) {
                    $query = "
                        SELECT
                            count(*) AS QTD
                        FROM
                            cdr
                        WHERE
                            DATE_FORMAT(calldate, '%b%y') = '$mes'
                            and char_length(src) > 4
                            and (dst like '110%'
                                or dcontext = 'from-did-direct'
                                )
                            and disposition = 'ANSWERED'";
                    //echo "$query<br>";
                    $res = mysql_query($query);
                    $val = mysql_fetch_assoc($res);
                    $datas['datas'][$tipo][$mes] = $val['QTD'];
                    //$datas['datas'][$tipo][$mes] = '02:34';
                }
            }
            // Quantidade de Chamadas Efetuadas
            if ($tipo == "EFETUADAS") {
                //echo "<b>$tipo</b><br>";
                foreach ($meses as $mes) {
                    $query = "
                        SELECT
                            count(*) AS QTD
                        FROM
                            cdr
                        WHERE
                            DATE_FORMAT(calldate, '%b%y') = '$mes'
                            and char_length(src) = 4
                            and char_length(dst) > 4
                            and disposition = 'ANSWERED'";
                    //echo "$query<br>";
                    $res = mysql_query($query);
                    $val = mysql_fetch_assoc($res);
                    $datas['datas'][$tipo][$mes] = $val['QTD'];
                    //$datas['datas'][$tipo][$mes] = '02:34';
                }
            }

            // Quantidade de Chamadas Efetuadas
            if ($tipo == "ABANDONO") {
                //echo "<b>$tipo</b><br>";
                foreach ($meses as $mes) {
                    $query = "
                        SELECT
                            count(*) AS QTD
                        FROM
                            cdr
                        WHERE
                            DATE_FORMAT(calldate, '%b%y') = '$mes'
                            and char_length(src) = 4
                            and char_length(dst) > 4
                            and (dst = 's' or disposition = 'NO ANSWER')";
                    //echo "$query<br>";
                    $res = mysql_query($query);
                    $val = mysql_fetch_assoc($res);
                    $datas['datas'][$tipo][$mes] = $val['QTD'];
                    //$datas['datas'][$tipo][$mes] = '02:34';
                }
            }
        }

        //curve lines
        //$datas['spline'] = true;
        //$datas['options']['showPrinterModels'] = true;
        //$datas['options']['showRamalSrc'] = true;
        //$datas['options']['force_show_label'] = true;
        //$graph->showGline($datas, $LANG['plugin_mreporting']['Helpdesk']['reportGlineTeste']['title'], '');
        $title = 'Quantidade de Chamadas (externas)';
        $desc = '';
        $graph2->showGline($datas, $title, $desc);



        // TAXA DE ABANDONO
        $graph3 = new PluginMreportingGraph();

        $datas = null;
        $delay = 365;
        $this->sql_date = PluginMreportingMisc::getSQLDateSupri("calldate", $delay);
        $meses = Array();
        $startDate = strtotime($_REQUEST["date1"]);
        $endDate = strtotime($_REQUEST["date2"]);
        $currentDate = $startDate;
        while ($endDate >= $currentDate) {
            $formattedDate = date('My', $currentDate);
            $meses[] = $formattedDate;
            $datas['labels2'][$formattedDate] = $formattedDate;
            //echo "<br>" . date('My',$currentDate);
            //$currentDate = strtotime( date('Y/m/01/',$currentDate).' -1 month');
            $currentDate = strtotime(date('Y/m/01/', $currentDate) . ' +1 month');
        }

        $tipos = null;
        $tipos = array("ABANDONO");
        foreach ($tipos as $tipo) {
            // Tempo Médio de Chamadas Totais Entrantes
            if ($tipo == "ABANDONO") {
                //echo "<b>$tipo</b><br>";
                foreach ($meses as $mes) {
                    // Atendidas e abandonadas
                    $query = "
                        SELECT
                            count(*) AS QTD_ATENDIDAS
                        FROM
                            cdr
                        WHERE
                            DATE_FORMAT(calldate, '%b%y') = '$mes'
                            and char_length(src) > 4
                            and (dst like '110%'
                                or dcontext = 'from-did-direct'
                                )";
                    // Abandonadas
                    $query2 = "
                        SELECT
                            count(*) AS QTD_ABANDONO
                        FROM
                            cdr
                        WHERE
                            DATE_FORMAT(calldate, '%b%y') = '$mes'
                            and char_length(src) > 4
                            and (dst = 's' or
                                    ((dcontext = 'from-did-direct' ) and (disposition = 'NO ANSWER'))
                                )";
                    //echo "$query<br>";
                    //echo "$query2<br>";
                    $res = mysql_query($query);
                    $val = mysql_fetch_assoc($res);

                    $res2 = mysql_query($query2);
                    $val2 = mysql_fetch_assoc($res2);
                    $fTotal = $val['QTD_ATENDIDAS'];
                    $fAbandono = $val2['QTD_ABANDONO'];
                    if ($fTotal != 0) {
                        $taxa = ($fAbandono * 100) / $fTotal;
                    } else
                        $taxa = 0;
                    $taxa = number_format($taxa, 2);
                    $datas['datas'][$tipo][$mes] = $taxa;
                    //$datas['datas'][$tipo][$mes] = '02:34';
                }
            }
        }

        //curve lines
        //$datas['spline'] = true;
        //$datas['options']['showPrinterModels'] = true;
        //$datas['options']['showRamalSrc'] = true;
        //$datas['options']['force_show_label'] = true;
        //$graph->showGline($datas, $LANG['plugin_mreporting']['Helpdesk']['reportGlineTeste']['title'], '');
        $title = 'Taxa de Abandono de Chamadas (%)';
        $desc = '';
        $graph3->showGline($datas, $title, $desc);




        // LIGAÇÕES POR HORA
        $graph4 = new PluginMreportingGraph();

        $novoDatas = null;

        // Carregando Horas
        for ($horas = 8; $horas <= 18; $horas++) {
            $novoDatas['labels2'][$horas] = $horas;
        }

        $startDate = $_REQUEST["date1"];
        $endDate = $_REQUEST["date2"];
        //echo "$startDate<br>$endDate<hr>";
        if ($startDate != '')
            $startDate = "and calldate >= '$startDate'";
        else
            $startDate = "";
        if ($endDate != '')
            $endDate = "and calldate <= '$endDate'";
        else
            $endDate = "";

        // Carregando os valores
        $tipos = array('ENTRANTES', 'CALL CENTER');
        foreach ($tipos as $tipo) {
            // Para cada tipo, pega os valores das horas
            if ($tipo == "ENTRANTES") {
                for ($hora = 8; $hora <= 18; $hora++) {
                    $query = "
                        select
                            hour(calldate) as Hora,
                            avg(duration) MEDIA_DURACAO,
                            avg(billsec) MEDIA_FALA,
                            avg(duration - billsec) MEDIA_ESPERA,
                            count(*) as QTD
                        from
                            cdr
                        where
                            hour(calldate) = $hora
                            and char_length(src) > 4
                            $startDate
                            $endDate
                        group by
                            hour(calldate)
                        ";
                    //echo "$query<br>";
                    $res = mysql_query($query);
                    $val = mysql_fetch_assoc($res);
                    //$datas['datas'][$tipo][$mes] = number_format($val['MEDIA']/60, 2);
                    $novoDatas['datas'][$tipo][$hora] = $val['QTD'];
                }
            }
        }


        //$graph->showGline($datas, $LANG['plugin_mreporting']['Helpdesk']['reportGlineTeste']['title'], '');
        $title = 'Ligações por hora';
        $desc = '';



        //echo "<pre>";
        //print_r($novoDatas);
        //echo "</pre>";


        $graph4->showGline($novoDatas, $title, $desc);

        mysql_close($conn);

        return null;
    }

    function reportHbarInstalacaoTonerPorEntidade() {
        global $DB, $LANG;
                
        $mreporting = new PluginMreportingProfile();
        $permissao = $mreporting->confereIndicadores($_REQUEST['f_name'], $_SESSION["glpiactiveprofile"]["id"]);
                
        if($permissao == false){
            print "<div style='text-align: center;'>Sem permissões de visualização deste indicador.</div>";
            die();
        }

        $graph = new PluginMreportingGraph();

        $datas = array();
        $delay = 365;
        $this->sql_date = PluginMreportingMisc::getSQLDateSupri("glpi_cartridges.date_use", $delay);
        $where_printermodel = "`glpi_printers`.`printermodels_id` = " . (isset($_REQUEST["printermodels_id"]) && $_REQUEST["printermodels_id"] > 0 ? $_REQUEST["printermodels_id"] : "`glpi_printers`.`printermodels_id`");

        $query = "SELECT count(*) as nb,
                        glpi_cartridges.id,
                        glpi_cartridges.printers_id,
                        glpi_cartridges.date_out,
                        CONCAT(`glpi_cartridgeitems`.`name`, ' - ', `glpi_cartridgeitemtypes`.`name`) AS tipo,
                        DATE_FORMAT(glpi_cartridges.date_use, '%y%m') as ordem,
                        DATE_FORMAT(glpi_cartridges.date_use, '%b%y') as month_l,
                        (
                                SELECT entity_id_to
                                FROM supridesk_log_printer_entity l
                                WHERE l.printers_id = `glpi_cartridges`.printers_id
                                  AND l.date <= `glpi_cartridges`.date_use
                                order by date desc
                                LIMIT 1
                        ) as id_entity_pre_troca,
                        (
                                SELECT entity_id_from
                                FROM supridesk_log_printer_entity l
                                WHERE l.printers_id = `glpi_cartridges`.printers_id
                                  AND l.date >= `glpi_cartridges`.date_use
                                order by date asc
                                LIMIT 1
                        ) as id_entity_first_2012,
                        (
                                SELECT id
                                FROM glpi_entities
                                WHERE id = (SELECT entities_id FROM glpi_printers where id = `glpi_cartridges`.printers_id)
                        ) as id_entity_atual,
                        glpi_infocoms.value as valor,
                        glpi_printermodels.name as printer_model

                FROM `glpi_cartridges`,
                     `glpi_cartridgeitems_printermodels`,
                     `glpi_printers`,
                     `glpi_printermodels`,
                     `glpi_cartridgeitemtypes`,
                     `glpi_cartridgeitems` LEFT JOIN `glpi_infocoms` ON ( glpi_cartridgeitems.id = glpi_infocoms.items_id and glpi_infocoms.itemtype = 'CartridgeItem')

                WHERE `glpi_cartridgeitemtypes`.`id` = `glpi_cartridgeitems`.`cartridgeitemtypes_id`
                AND `glpi_cartridges`.`cartridgeitems_id` = `glpi_cartridgeitems`.`id`
                AND `glpi_cartridges`.`printers_id` = `glpi_printers`.`id`
                AND `glpi_printers`.`printermodels_id` = `glpi_cartridgeitems_printermodels`.`printermodels_id`
                AND `glpi_cartridgeitems_printermodels`.`cartridgeitems_id` = `glpi_cartridgeitems`.`id`
                AND " . $where_printermodel . "
                AND " . $this->sql_date . "
                and glpi_printers.printermodels_id = glpi_printermodels.id

                GROUP BY tipo, ordem, month_l, id_entity_pre_troca, id_entity_first_2012, id_entity_atual
                HAVING
                        (id_entity_pre_troca is not null and id_entity_pre_troca IN (" . $this->where_entities . ")  )
                        or
                        ( id_entity_pre_troca is null and id_entity_first_2012 is not null and id_entity_first_2012 IN (" . $this->where_entities . ") )
                        or
                        ( id_entity_pre_troca is null and id_entity_first_2012 is null and id_entity_atual IN (" . $this->where_entities . ") )
                ORDER BY tipo, ordem";
        $res = $DB->query($query);

        $meses = Array();
        $startDate = strtotime($_REQUEST["date1"]);
        $endDate = strtotime($_REQUEST["date2"]);
        $currentDate = $startDate;
        while ($endDate >= $currentDate) {
            $formattedDate = date('My', $currentDate);
            $meses[] = $formattedDate;
            $datas['labels2'][$formattedDate] = $formattedDate;
            $currentDate = strtotime(date('Y/m/01/', $currentDate) . ' +1 month');
        }

        //zera o array de cada item
        if (mysql_num_rows($res) > 0)
            mysql_data_seek($res, 0);
        while ($data = $DB->fetch_assoc($res)) {
            foreach ($meses as $mes) {
                $datas['datas'][$data['tipo']][$mes] = 0;
            }
        }

        if (mysql_num_rows($res) > 0)
            mysql_data_seek($res, 0);
        while ($data = $DB->fetch_assoc($res)) {
            $sumTipo = $datas['datas'][$data['tipo']][$data['month_l']] + $data['nb'];
            $datas['datas'][$data['tipo']][$data['month_l']] = $sumTipo;
            $sum_array = array_sum($datas['datas'][$data['tipo']]);
            $total[$data['tipo']] = $sum_array;
        }

        if (mysql_num_rows($res) > 0)
            mysql_data_seek($res, 0);
        while ($data = $DB->fetch_assoc($res)) {
            $datas['datas'][$data['tipo']] = $total[$data['tipo']];
        }

        $datas['options']['showPrinterModels'] = true;
        $datas['options']['force_show_label'] = true;

        return $datas;
    }

    function reportHbarProgressaoTotalTrocaDeConsumiveis() {
        global $DB, $LANG;
        
        $mreporting = new PluginMreportingProfile();
        $permissao = $mreporting->confereIndicadores($_REQUEST['f_name'], $_SESSION["glpiactiveprofile"]["id"]);
                
        if($permissao == false){
            print "<div style='text-align: center;'>Sem permissões de visualização deste indicador.</div>";
            die();
        }

        // LINHAS - PROGRESSÃO DE TROCA DE CONSUMÍVEIS

        $graph = new PluginMreportingGraph();
        $datas = array();
        $delay = 365;
        $this->sql_date = PluginMreportingMisc::getSQLDateSupri("glpi_cartridges.date_out", $delay);
        $where_printermodel = "`glpi_printers`.`printermodels_id` = " . (isset($_REQUEST["printermodels_id"]) && $_REQUEST["printermodels_id"] > 0 ? $_REQUEST["printermodels_id"] : "`glpi_printers`.`printermodels_id`");

        $query = "SELECT count(*) as nb,
                    glpi_cartridges.id,
                    glpi_cartridges.printers_id,
                    glpi_cartridges.date_out,
                    `glpi_cartridgeitemtypes`.`name` AS tipo,
                    DATE_FORMAT(glpi_cartridges.date_out, '%y%m') as ordem,
                    DATE_FORMAT(glpi_cartridges.date_out, '%b%y') as month_l,
                            (
                                    SELECT entity_id_to
                                    FROM supridesk_log_printer_entity l
                                    WHERE l.printers_id = `glpi_cartridges`.printers_id
                                      AND l.date <= `glpi_cartridges`.date_out
                                    order by date desc
                                    LIMIT 1
                            ) as id_entity_pre_troca,
                            (
                                    SELECT entity_id_from
                                    FROM supridesk_log_printer_entity l
                                    WHERE l.printers_id = `glpi_cartridges`.printers_id
                                      AND l.date >= `glpi_cartridges`.date_out
                                    order by date asc
                                    LIMIT 1
                            ) as id_entity_first_2012,
                            (
                                    SELECT id
                                    FROM glpi_entities
                                    WHERE id = (SELECT entities_id FROM glpi_printers where id = `glpi_cartridges`.printers_id)
                            ) as id_entity_atual
                FROM `glpi_cartridges`,
                    `glpi_cartridgeitems`,
                    `glpi_cartridgeitems_printermodels`,
                    `glpi_printers`,
                    `glpi_cartridgeitemtypes`
                WHERE `glpi_cartridgeitemtypes`.`id` = `glpi_cartridgeitems`.`cartridgeitemtypes_id`
                    AND `glpi_cartridges`.`cartridgeitems_id` = `glpi_cartridgeitems`.`id`
                    AND `glpi_cartridges`.`printers_id` = `glpi_printers`.`id`
                    AND `glpi_printers`.`printermodels_id` = `glpi_cartridgeitems_printermodels`.`printermodels_id`
                    AND `glpi_cartridgeitems_printermodels`.`cartridgeitems_id` = `glpi_cartridgeitems`.`id`
                    AND " . $where_printermodel . "
                    AND glpi_printers.entities_id IN (" . $this->where_entities . ")
                    AND " . $this->sql_date . "
                GROUP BY tipo,
                    ordem,
                    month_l,
                    id_entity_pre_troca,
                    id_entity_first_2012,
                    id_entity_atual
                HAVING
                    (id_entity_pre_troca is not null and id_entity_pre_troca IN (" . $this->where_entities . ")  )
                    or
                    ( id_entity_pre_troca is null and id_entity_first_2012 is not null and id_entity_first_2012 IN (" . $this->where_entities . ") )
                    or
                    ( id_entity_pre_troca is null and id_entity_first_2012 is null and id_entity_atual IN (" . $this->where_entities . ") )
                ORDER BY
                    tipo,
                    ordem";
        //echo "<hr>";
        //echo $query;
        $res = $DB->query($query);

        $meses = Array();


        $startDate = strtotime($_REQUEST["date1"]);
        $endDate = strtotime($_REQUEST["date2"]);
        $currentDate = $startDate;
        while ($endDate >= $currentDate) {
            $formattedDate = date('My', $currentDate);
            $meses[] = $formattedDate;
            $datas['labels2'][$formattedDate] = $formattedDate;
            //echo "<br>" . date('My',$currentDate);
            //$currentDate = strtotime( date('Y/m/01/',$currentDate).' -1 month');
            $currentDate = strtotime(date('Y/m/01/', $currentDate) . ' +1 month');
        }

        //zera o array de cada item
        if (mysql_num_rows($res) > 0)
            mysql_data_seek($res, 0);
        while ($data = $DB->fetch_assoc($res)) {
            foreach ($meses as $mes) {
                $datas['datas'][$data['tipo']][$mes] = 0;
            }
        }

        if (mysql_num_rows($res) > 0)
            mysql_data_seek($res, 0);
        //$lastTipo = "";
        //$sumTipo = 0;
        while ($data = $DB->fetch_assoc($res)) {
            $sumTipo = $datas['datas'][$data['tipo']][$data['month_l']] + $data['nb'];
            $datas['datas'][$data['tipo']][$data['month_l']] = $sumTipo;
        }

        //curve lines
        $datas['spline'] = true;
        $datas['options']['showPrinterModels'] = true;
        $datas['options']['force_show_label'] = true;

        $graph->showGline($datas, $LANG['plugin_mreporting']['Helpdesk']['reportGlineEstoque']['title'], '');



        // Barras - Troca de Toner Por Entidade
        $LANG['plugin_mreporting']['Helpdesk']['reportHbarProgressaoTotalTrocaDeConsumiveis']['title'] = "Barras - Troca de Toner Por Entidade";
        $datas = array();
        $graph = new PluginMreportingGraph();
        $delay = 30;
        $this->sql_date = PluginMreportingMisc::getSQLDateSupri("glpi_cartridges.date_out", $delay);
        $where_printermodel = "`glpi_printers`.`printermodels_id` = " . (isset($_REQUEST["printermodels_id"]) && $_REQUEST["printermodels_id"] > 0 ? $_REQUEST["printermodels_id"] : "`glpi_printers`.`printermodels_id`");

        $query = " SELECT
                        count(*) as count,
                        `glpi_cartridgeitems`.`name` AS nome,
                        `glpi_cartridgeitemtypes`.`name` AS tipo
                    FROM
                        `glpi_cartridges`,
                        `glpi_cartridgeitems`,
                        `glpi_cartridgeitems_printermodels`,
                        `glpi_printers`,
                        `glpi_cartridgeitemtypes`
                    WHERE `glpi_cartridgeitemtypes`.`id` = `glpi_cartridgeitems`.`cartridgeitemtypes_id`
                        AND `glpi_cartridges`.`cartridgeitems_id` = `glpi_cartridgeitems`.`id`
                        AND `glpi_cartridges`.`printers_id` = `glpi_printers`.`id`
                        AND `glpi_printers`.`printermodels_id` = `glpi_cartridgeitems_printermodels`.`printermodels_id`
                        AND `glpi_cartridgeitems_printermodels`.`cartridgeitems_id` = `glpi_cartridgeitems`.`id`
                        AND " . $where_printermodel . "
                        AND glpi_printers.entities_id IN (" . $this->where_entities . ")
                        AND " . $this->sql_date . "
                    GROUP BY
                        `glpi_cartridgeitemtypes`.`name`
                    ORDER BY
                        `glpi_cartridgeitemtypes`.`name`,
                        `glpi_cartridgeitems`.`name` ";
        //echo "<hr>";
        //echo $query;
        $res = $DB->query($query);
        while ($data = $DB->fetch_assoc($res)) {
            //$datas['datas'][$data['nome'] . " - " . $data['tipo']] = $data['count'];
            $datas['datas'][$data['tipo']] = $data['count'];
        }



        //opções do gráfico
        $datas['options']['showPrinterModels'] = true;
        $datas['options']['force_show_label'] = true;
        //echo "<pre>";
        //print_r($datas);
        //var_dump($datas);
        //echo "</pre>";
        return $datas;
    }

    function reportGlineBIDTroca() {
        global $DB, $LANG;
        
        $mreporting = new PluginMreportingProfile();
        $permissao = $mreporting->confereIndicadores($_REQUEST['f_name'], $_SESSION["glpiactiveprofile"]["id"]);
                
        if($permissao == false){
            print "<div style='text-align: center;'>Sem permissões de visualização deste indicador.</div>";
            die();
        }
        
        $graph = new PluginMreportingGraph();

        $datas = array();
        $delay = 365;
        $this->sql_date = PluginMreportingMisc::getSQLDateSupri("glpi_cartridges.date_out", $delay);
        $where_printermodel = "`glpi_printers`.`printermodels_id` = " . (isset($_REQUEST["printermodels_id"]) && $_REQUEST["printermodels_id"] > 0 ? $_REQUEST["printermodels_id"] : "`glpi_printers`.`printermodels_id`");

        $query = "	SELECT count(*) as nb,
						glpi_cartridges.id,
						glpi_cartridges.printers_id,
						glpi_cartridges.date_out,
						CONCAT(`glpi_cartridgeitems`.`name`, ' - ', `glpi_cartridgeitemtypes`.`name`) AS tipo,
						DATE_FORMAT(glpi_cartridges.date_out, '%y%m') as ordem,
						DATE_FORMAT(glpi_cartridges.date_out, '%b%y') as month_l,
							(
								SELECT entity_id_to
								FROM supridesk_log_printer_entity l
								WHERE l.printers_id = `glpi_cartridges`.printers_id
								  AND l.date <= `glpi_cartridges`.date_out
								order by date desc
								LIMIT 1
							) as id_entity_pre_troca,
							(
								SELECT entity_id_from
								FROM supridesk_log_printer_entity l
								WHERE l.printers_id = `glpi_cartridges`.printers_id
								  AND l.date >= `glpi_cartridges`.date_out
								order by date asc
								LIMIT 1
							) as id_entity_first_2012,
							(
								SELECT id
								FROM glpi_entities
								WHERE id = (SELECT entities_id FROM glpi_printers where id = `glpi_cartridges`.printers_id)
							) as id_entity_atual,
						glpi_infocoms.value as valor,
						glpi_printermodels.name as printer_model

						FROM `glpi_cartridges`,
						`glpi_cartridgeitems_printermodels`,
						`glpi_printers`,
						`glpi_printermodels`,
						`glpi_cartridgeitemtypes`,
						`glpi_cartridgeitems` LEFT JOIN `glpi_infocoms` ON ( glpi_cartridgeitems.id = glpi_infocoms.items_id and glpi_infocoms.itemtype = 'CartridgeItem')

						WHERE `glpi_cartridgeitemtypes`.`id` = `glpi_cartridgeitems`.`cartridgeitemtypes_id`
						AND `glpi_cartridges`.`cartridgeitems_id` = `glpi_cartridgeitems`.`id`
						AND `glpi_cartridges`.`printers_id` = `glpi_printers`.`id`
						AND `glpi_printers`.`printermodels_id` = `glpi_cartridgeitems_printermodels`.`printermodels_id`
						AND `glpi_cartridgeitems_printermodels`.`cartridgeitems_id` = `glpi_cartridgeitems`.`id`
						AND " . $where_printermodel . "
						AND " . $this->sql_date . "
						and glpi_printers.printermodels_id = glpi_printermodels.id

						GROUP BY tipo, ordem, month_l, id_entity_pre_troca, id_entity_first_2012, id_entity_atual
						HAVING
							(id_entity_pre_troca is not null and id_entity_pre_troca IN (" . $this->where_entities . ")  )
							or
							( id_entity_pre_troca is null and id_entity_first_2012 is not null and id_entity_first_2012 IN (" . $this->where_entities . ") )
							or
							( id_entity_pre_troca is null and id_entity_first_2012 is null and id_entity_atual IN (" . $this->where_entities . ") )
						ORDER BY tipo, ordem";
        $res = $DB->query($query);

        $meses = Array();
        $startDate = strtotime($_REQUEST["date1"]);
        $endDate = strtotime($_REQUEST["date2"]);
        $currentDate = $startDate;
        while ($endDate >= $currentDate) {
            $formattedDate = date('My', $currentDate);
            $meses[] = $formattedDate;
            $datas['labels2'][$formattedDate] = $formattedDate;
            $currentDate = strtotime(date('Y/m/01/', $currentDate) . ' +1 month');
        }

        //zera o array de cada item
        if (mysql_num_rows($res) > 0)
            mysql_data_seek($res, 0);
        while ($data = $DB->fetch_assoc($res)) {
            foreach ($meses as $mes) {
                $datas['datas'][$data['tipo']][$mes] = 0;
            }
            $datas['datas'][$data['tipo']]['valor'] = 0;
            $datas['datas'][$data['tipo']]['printer_model'] = 0;
        }

        if (mysql_num_rows($res) > 0)
            mysql_data_seek($res, 0);
        while ($data = $DB->fetch_assoc($res)) {
            $sumTipo = $datas['datas'][$data['tipo']][$data['month_l']] + $data['nb'];
            $datas['datas'][$data['tipo']][$data['month_l']] = $sumTipo;
            $datas['datas'][$data['tipo']]['valor'] = $data['valor'];
            $datas['datas'][$data['tipo']]['printer_model'] = $data['printer_model'];
        }

        //curve lines
        $datas['spline'] = true;
        $datas['options']['showPrinterModels'] = true;
        $datas['options']['force_show_label'] = true;
        $datas['options']['hide_graph'] = true;

        $graph->showGline($datas, $LANG['plugin_mreporting']['Helpdesk']['reportGlineBIDTroca']['title'], '');

        echo "<table border='1' width='100%' style='text-align:left; border-collapse:collapse;' cellspacing='0' cellspading='0'>";
        echo "<tr style='background-color: #BFC7D1;' height='30px'>";
        echo "<td>&nbsp;Impressora</td>";
        echo "<td>&nbsp;Toner</td>";
        foreach ($datas['labels2'] as $key => $value) {
            echo "<td style='text-align:center;'>{$key}</td>";
        }
        echo "<td style='text-align:center;'>Valor</td>";
        echo "</tr>";

        $count = 0;

        //instancia e inicializa o array
        $totais = array();
        foreach ($datas['labels2'] as $label)
            $totais[$label] = 0;

        foreach ($datas['datas'] as $key => $value) {
            $count++;
            $color = "#DEE1E5";
            if ($count % 2)
                $color = "#F2F2F2";

            echo "<tr style='background-color: {$color};' height='25px'>";
            echo "<td>&nbsp{$datas['datas'][$key]['printer_model']}</td>";
            echo "<td>&nbsp{$key}</td>";
            foreach ($datas['labels2'] as $label) {
                $valor = $datas['datas'][$key][$label];
                echo "<td width='45px' style='text-align:center;'>{$valor}</td>";
                $totais[$label] += $valor * $datas['datas'][$key]['valor'] * 1.0;
            }
            $valor_format = number_format($datas['datas'][$key]['valor'], 2, ',', '.');
            echo "<td style='text-align:right;'>&nbsp{$valor_format}</td>";
            echo "</tr>";
        }
        echo "<tr>";
        echo "<td height='30px' colspan='2' style='text-align:right;'>Totais (R$) &nbsp;</td>";
        $total_geral = 0;
        foreach ($totais as $total) {
            $total_format = number_format($total, 2, ',', '.');
            echo "<td style='text-align:center;'>{$total_format}</td>";
            $total_geral += $total;
        }
        echo "<td>&nbsp;</td>";
        echo "</tr>";

        $colspan = count($datas['labels2']) + 3;

        $total_geral_format = number_format($total_geral, 2, ',', '.');
        echo "<tr>";
        echo "<td style='text-align:center;' height='30px' colspan='{$colspan}'>Total geral (R$): {$total_geral_format}</td>";
        echo "</tr>";

        $custo_medio_format = number_format($total_geral / count($datas['labels2']), 2, ',', '.');
        echo "<tr>";
        echo "<td style='text-align:center;' height='30px' colspan='{$colspan}'>Custo médio mensal (R$): {$custo_medio_format}</td>";
        echo "</tr>";
        echo "</table>";

        return null;
    }

    function reportGlineBIDInstalacao() {
        global $DB, $LANG;
        
        $mreporting = new PluginMreportingProfile();
        $permissao = $mreporting->confereIndicadores($_REQUEST['f_name'], $_SESSION["glpiactiveprofile"]["id"]);
                
        if($permissao == false){
            print "<div style='text-align: center;'>Sem permissões de visualização deste indicador.</div>";
            die();
        }

        /*if ($_SESSION["glpiactiveprofile"]["name"] != "admin" && $_SESSION["glpiactiveprofile"]["name"] != "super-admin") {
            print "<div style='text-align: center;'>Sem permissões de visualização deste indicador.</div>";
            die();
        }*/

        $graph = new PluginMreportingGraph();

        $datas = array();
        $delay = 365;
        $this->sql_date = PluginMreportingMisc::getSQLDateSupri("glpi_cartridges.date_use", $delay);
        $where_printermodel = "`glpi_printers`.`printermodels_id` = " . (isset($_REQUEST["printermodels_id"]) && $_REQUEST["printermodels_id"] > 0 ? $_REQUEST["printermodels_id"] : "`glpi_printers`.`printermodels_id`");

        $query = "	SELECT count(*) as nb,
						glpi_cartridges.id,
						glpi_cartridges.printers_id,
						glpi_cartridges.date_use,
						CONCAT(`glpi_cartridgeitems`.`name`, ' - ', `glpi_cartridgeitemtypes`.`name`) AS tipo,
						DATE_FORMAT(glpi_cartridges.date_use, '%y%m') as ordem,
						DATE_FORMAT(glpi_cartridges.date_use, '%b%y') as month_l,
							(
								SELECT entity_id_to
								FROM supridesk_log_printer_entity l
								WHERE l.printers_id = `glpi_cartridges`.printers_id
								  AND l.date <= `glpi_cartridges`.date_use
								order by date desc
								LIMIT 1
							) as id_entity_pre_troca,
							(
								SELECT entity_id_from
								FROM supridesk_log_printer_entity l
								WHERE l.printers_id = `glpi_cartridges`.printers_id
								  AND l.date >= `glpi_cartridges`.date_use
								order by date asc
								LIMIT 1
							) as id_entity_first_2012,
							(
								SELECT id
								FROM glpi_entities
								WHERE id = (SELECT entities_id FROM glpi_printers where id = `glpi_cartridges`.printers_id)
							) as id_entity_atual,
						glpi_infocoms.value as valor,
						glpi_printermodels.name as printer_model

						FROM `glpi_cartridges`,
						`glpi_cartridgeitems_printermodels`,
						`glpi_printers`,
						`glpi_printermodels`,
						`glpi_cartridgeitemtypes`,
						`glpi_cartridgeitems` LEFT JOIN `glpi_infocoms` ON ( glpi_cartridgeitems.id = glpi_infocoms.items_id and glpi_infocoms.itemtype = 'CartridgeItem')

						WHERE `glpi_cartridgeitemtypes`.`id` = `glpi_cartridgeitems`.`cartridgeitemtypes_id`
						AND `glpi_cartridges`.`cartridgeitems_id` = `glpi_cartridgeitems`.`id`
						AND `glpi_cartridges`.`printers_id` = `glpi_printers`.`id`
						AND `glpi_printers`.`printermodels_id` = `glpi_cartridgeitems_printermodels`.`printermodels_id`
						AND `glpi_cartridgeitems_printermodels`.`cartridgeitems_id` = `glpi_cartridgeitems`.`id`
						AND " . $where_printermodel . "
						AND " . $this->sql_date . "
						and glpi_printers.printermodels_id = glpi_printermodels.id

						GROUP BY tipo, ordem, month_l, id_entity_pre_troca, id_entity_first_2012, id_entity_atual
						HAVING
							(id_entity_pre_troca is not null and id_entity_pre_troca IN (" . $this->where_entities . ")  )
							or
							( id_entity_pre_troca is null and id_entity_first_2012 is not null and id_entity_first_2012 IN (" . $this->where_entities . ") )
							or
							( id_entity_pre_troca is null and id_entity_first_2012 is null and id_entity_atual IN (" . $this->where_entities . ") )
						ORDER BY tipo, ordem";
        $res = $DB->query($query);

        $meses = Array();
        $startDate = strtotime($_REQUEST["date1"]);
        $endDate = strtotime($_REQUEST["date2"]);
        $currentDate = $startDate;
        while ($endDate >= $currentDate) {
            $formattedDate = date('My', $currentDate);
            $meses[] = $formattedDate;
            $datas['labels2'][$formattedDate] = $formattedDate;
            $currentDate = strtotime(date('Y/m/01/', $currentDate) . ' +1 month');
        }

        //zera o array de cada item
        if (mysql_num_rows($res) > 0)
            mysql_data_seek($res, 0);
        while ($data = $DB->fetch_assoc($res)) {
            foreach ($meses as $mes) {
                $datas['datas'][$data['tipo']][$mes] = 0;
            }
            $datas['datas'][$data['tipo']]['valor'] = 0;
            $datas['datas'][$data['tipo']]['printer_model'] = 0;
        }

        if (mysql_num_rows($res) > 0)
            mysql_data_seek($res, 0);
        while ($data = $DB->fetch_assoc($res)) {
            $sumTipo = $datas['datas'][$data['tipo']][$data['month_l']] + $data['nb'];
            $datas['datas'][$data['tipo']][$data['month_l']] = $sumTipo;
            $datas['datas'][$data['tipo']]['valor'] = $data['valor'];
            $datas['datas'][$data['tipo']]['printer_model'] = $data['printer_model'];
        }

        //curve lines
        $datas['spline'] = true;
        $datas['options']['showPrinterModels'] = true;
        $datas['options']['force_show_label'] = true;
        $datas['options']['hide_graph'] = true;

        $graph->showGline($datas, $LANG['plugin_mreporting']['Helpdesk']['reportGlineBIDInstalacao']['title'], '');

        echo "<table border='1' width='100%' style='text-align:left; border-collapse:collapse;' cellspacing='0' cellspading='0'>";
        echo "<tr style='background-color: #BFC7D1;' height='30px'>";
        echo "<td>&nbsp;Impressora</td>";
        echo "<td>&nbsp;Toner</td>";
        foreach ($datas['labels2'] as $key => $value) {
            echo "<td style='text-align:center;'>{$key}</td>";
        }
        echo "<td style='text-align:center;'>Valor</td>";
        echo "</tr>";

        $count = 0;

        //instancia e inicializa o array
        $totais = array();
        foreach ($datas['labels2'] as $label)
            $totais[$label] = 0;

        foreach ($datas['datas'] as $key => $value) {
            $count++;
            $color = "#DEE1E5";
            if ($count % 2)
                $color = "#F2F2F2";

            echo "<tr style='background-color: {$color};' height='25px'>";
            echo "<td>&nbsp{$datas['datas'][$key]['printer_model']}</td>";
            echo "<td>&nbsp{$key}</td>";
            foreach ($datas['labels2'] as $label) {
                $valor = $datas['datas'][$key][$label];
                echo "<td width='45px' style='text-align:center;'>{$valor}</td>";
                $totais[$label] += $valor * $datas['datas'][$key]['valor'] * 1.0;
            }
            $valor_format = number_format($datas['datas'][$key]['valor'], 2, ',', '.');
            echo "<td style='text-align:right;'>&nbsp{$valor_format}</td>";
            echo "</tr>";
        }
        echo "<tr>";
        echo "<td height='30px' colspan='2' style='text-align:right;'>Totais (R$) &nbsp;</td>";
        $total_geral = 0;
        foreach ($totais as $total) {
            $total_format = number_format($total, 2, ',', '.');
            echo "<td style='text-align:center;'>{$total_format}</td>";
            $total_geral += $total;
        }
        echo "<td>&nbsp;</td>";
        echo "</tr>";

        $colspan = count($datas['labels2']) + 3;

        $total_geral_format = number_format($total_geral, 2, ',', '.');
        echo "<tr>";
        echo "<td style='text-align:center;' height='30px' colspan='{$colspan}'>Total geral (R$): {$total_geral_format}</td>";
        echo "</tr>";

        $custo_medio_format = number_format($total_geral / count($datas['labels2']), 2, ',', '.');
        echo "<tr>";
        echo "<td style='text-align:center;' height='30px' colspan='{$colspan}'>Custo médio mensal (R$): {$custo_medio_format}</td>";
        echo "</tr>";
        echo "</table>";

        return null;
    }

    /*
      function reportGlinePrevisaoCusto() {
      global $DB, $LANG;

      if ($_SESSION["glpiactiveprofile"]["name"] != "admin" && $_SESSION["glpiactiveprofile"]["name"] != "super-admin") {
      print "<div style='text-align: center;'>Sem permissões de visualização deste indicador.</div>";
      die();
      }

      $graph = new PluginMreportingGraph();

      $datas = array();
      $delay = 365;
      $this->sql_date = PluginMreportingMisc::getSQLDateSupri("glpi_cartridges.date_out",$delay);
      $where_printermodel = "`glpi_printers`.`printermodels_id` = " . (isset($_REQUEST["printermodels_id"]) && $_REQUEST["printermodels_id"] > 0 ? $_REQUEST["printermodels_id"] : "`glpi_printers`.`printermodels_id`");

      $query = "	SELECT count(*) as nb,
      glpi_cartridges.id,
      glpi_cartridges.printers_id,
      glpi_cartridges.date_out,
      CONCAT(`glpi_cartridgeitems`.`name`, ' - ', `glpi_cartridgeitemtypes`.`name`) AS tipo,
      DATE_FORMAT(glpi_cartridges.date_out, '%y%m') as ordem,
      DATE_FORMAT(glpi_cartridges.date_out, '%b%y') as month_l,
      (
      SELECT entity_id_to
      FROM supridesk_log_printer_entity l
      WHERE l.printers_id = `glpi_cartridges`.printers_id
      AND l.date <= `glpi_cartridges`.date_out
      order by date desc
      LIMIT 1
      ) as id_entity_pre_troca,
      (
      SELECT entity_id_from
      FROM supridesk_log_printer_entity l
      WHERE l.printers_id = `glpi_cartridges`.printers_id
      AND l.date >= `glpi_cartridges`.date_out
      order by date asc
      LIMIT 1
      ) as id_entity_first_2012,
      (
      SELECT id
      FROM glpi_entities
      WHERE id = (SELECT entities_id FROM glpi_printers where id = `glpi_cartridges`.printers_id)
      ) as id_entity_atual,
      glpi_infocoms.value as valor,
      glpi_printermodels.name as printer_model

      FROM `glpi_cartridges`,
      `glpi_cartridgeitems_printermodels`,
      `glpi_printers`,
      `glpi_printermodels`,
      `glpi_cartridgeitemtypes`,
      `glpi_cartridgeitems` LEFT JOIN `glpi_infocoms` ON ( glpi_cartridgeitems.id = glpi_infocoms.items_id and glpi_infocoms.itemtype = 'CartridgeItem')

      WHERE `glpi_cartridgeitemtypes`.`id` = `glpi_cartridgeitems`.`cartridgeitemtypes_id`
      AND `glpi_cartridges`.`cartridgeitems_id` = `glpi_cartridgeitems`.`id`
      AND `glpi_cartridges`.`printers_id` = `glpi_printers`.`id`
      AND `glpi_printers`.`printermodels_id` = `glpi_cartridgeitems_printermodels`.`printermodels_id`
      AND `glpi_cartridgeitems_printermodels`.`cartridgeitems_id` = `glpi_cartridgeitems`.`id`
      AND " . $where_printermodel . "
      AND " . $this->sql_date . "
      and glpi_printers.printermodels_id = glpi_printermodels.id

      GROUP BY tipo, ordem, month_l, id_entity_pre_troca, id_entity_first_2012, id_entity_atual
      HAVING
      (id_entity_pre_troca is not null and id_entity_pre_troca IN (".$this->where_entities.")  )
      or
      ( id_entity_pre_troca is null and id_entity_first_2012 is not null and id_entity_first_2012 IN (".$this->where_entities.") )
      or
      ( id_entity_pre_troca is null and id_entity_first_2012 is null and id_entity_atual IN (".$this->where_entities.") )
      ORDER BY tipo, ordem";
      $res = $DB->query($query);

      $meses = Array();
      $startDate = strtotime($_REQUEST["date1"]);
      $endDate   = strtotime($_REQUEST["date2"]);
      $currentDate = $startDate;
      while ($endDate >= $currentDate)
      {
      $formattedDate = date('My',$currentDate);
      $meses[] = $formattedDate;
      $datas['labels2'][$formattedDate] = $formattedDate;
      $currentDate = strtotime( date('Y/m/01/',$currentDate).' +1 month');
      }

      //zera o array de cada item
      if (mysql_num_rows($res) > 0 ) mysql_data_seek($res, 0);
      while ($data = $DB->fetch_assoc($res))
      {
      foreach( $meses as $mes )
      {
      $datas['datas'][$data['tipo']][$mes] = 0;
      }
      $datas['datas'][$data['tipo']]['valor'] = 0;
      $datas['datas'][$data['tipo']]['printer_model'] = 0;
      }

      if (mysql_num_rows($res) > 0 ) mysql_data_seek($res, 0);
      while ($data = $DB->fetch_assoc($res))
      {
      $sumTipo = $datas['datas'][$data['tipo']][$data['month_l']] + $data['nb'];
      $datas['datas'][$data['tipo']][$data['month_l']] = $sumTipo;
      $datas['datas'][$data['tipo']]['valor'] = $data['valor'];
      $datas['datas'][$data['tipo']]['printer_model'] = $data['printer_model'];
      }

      //curve lines
      $datas['spline'] = true;
      $datas['options']['showPrinterModels'] = true;
      $datas['options']['force_show_label'] = true;
      $datas['options']['hide_graph'] = true;

      $graph->showGline($datas, $LANG['plugin_mreporting']['Helpdesk']['reportGlinePrevisaoCusto']['title'], '');

      echo "<table border='1' width='100%' style='text-align:left; border-collapse:collapse;' cellspacing='0' cellspading='0'>";
      echo "<tr style='background-color: #BFC7D1;' height='30px'>";
      echo "<td>&nbsp;Impressora</td>";
      echo "<td>&nbsp;Toner</td>";
      foreach( $datas['labels2'] as $key => $value )
      {
      echo "<td style='text-align:center;'>{$key}</td>";
      }
      echo "<td style='text-align:center;'>Valor</td>";
      echo "</tr>";

      $count = 0;

      //instancia e inicializa o array
      $totais = array();
      foreach( $datas['labels2'] as $label )
      $totais[$label] = 0;

      foreach( $datas['datas'] as $key => $value )
      {
      $count++;
      $color = "#DEE1E5";
      if ( $count % 2 )
      $color = "#F2F2F2";

      echo "<tr style='background-color: {$color};' height='25px'>";
      echo	"<td>&nbsp{$datas['datas'][$key]['printer_model']}</td>";
      echo	"<td>&nbsp{$key}</td>";
      foreach( $datas['labels2'] as $label )
      {
      $valor = $datas['datas'][$key][$label];
      echo	"<td width='45px' style='text-align:center;'>{$valor}</td>";
      $totais[$label] += $valor * $datas['datas'][$key]['valor'] * 1.0;
      }
      $valor_format = number_format($datas['datas'][$key]['valor'], 2, ',', '.');
      echo	"<td style='text-align:right;'>&nbsp{$valor_format}</td>";
      echo "</tr>";
      }
      echo "<tr>";
      echo "<td height='30px' colspan='2' style='text-align:right;'>Totais (R$) &nbsp;</td>";
      $total_geral = 0;
      foreach( $totais as $total )
      {
      $total_format = number_format($total, 2, ',', '.');
      echo "<td style='text-align:center;'>{$total_format}</td>";
      $total_geral += $total;
      }
      echo "<td>&nbsp;</td>";
      echo "</tr>";

      $colspan = count($datas['labels2']) + 3;

      $total_geral_format = number_format($total_geral, 2, ',', '.');
      echo "<tr>";
      echo "<td style='text-align:center;' height='30px' colspan='{$colspan}'>Total geral (R$): {$total_geral_format}</td>";
      echo "</tr>";

      $custo_medio_format = number_format($total_geral / count($datas['labels2']), 2, ',', '.');
      echo "<tr>";
      echo "<td style='text-align:center;' height='30px' colspan='{$colspan}'>Custo médio mensal (R$): {$custo_medio_format}</td>";
      echo "</tr>";
      echo "</table>";

      return null;
      }
     */

    function reportHgbarVolumetriaMensalImpressora() {
        global $DB, $LANG;
        
        $mreporting = new PluginMreportingProfile();
        $permissao = $mreporting->confereIndicadores($_REQUEST['f_name'], $_SESSION["glpiactiveprofile"]["id"]);
                
        if($permissao == false){
            print "<div style='text-align: center;'>Sem permissões de visualização deste indicador.</div>";
            die();
        }
        
        $datas = array();

        $graph = new PluginMreportingGraph();
        $delay = 30;
        $this->sql_date = PluginMreportingMisc::getSQLDateSupri("glpi_cartridges.date_out", $delay);

        $where_printermodel = "`glpi_printers`.`printermodels_id` = " . (isset($_REQUEST["printermodels_id"]) && $_REQUEST["printermodels_id"] > 0 ? $_REQUEST["printermodels_id"] : "`glpi_printers`.`printermodels_id`");
        $where_printermodel2 = "`glpi_printers2`.`printermodels_id` = " . (isset($_REQUEST["printermodels_id"]) && $_REQUEST["printermodels_id"] > 0 ? $_REQUEST["printermodels_id"] : "`glpi_printers2`.`printermodels_id`");

        $query = "
				SELECT
					`glpi_cartridges`.`id`,
					`glpi_cartridgeitems`.`name` AS nome,
					`glpi_cartridgeitemtypes`.`id` AS idtipo,
					`glpi_cartridgeitemtypes`.`name` AS tipo,
					`glpi_cartridges`.`date_out`,
					`glpi_cartridges`.`pages`,
					`glpi_printers`.`printermodels_id`,
					`glpi_cartridges`.`printers_id`,
					`glpi_printermodels`.`name`,
					CEIL(SUM( `glpi_cartridges`.`pages` - COALESCE((
						SELECT
							`glpi_cartridges2`.`pages`
						FROM
							`glpi_cartridges` as glpi_cartridges2,
							`glpi_cartridgeitems` as glpi_cartridgeitems2,
							`glpi_cartridgeitems_printermodels` as glpi_cartridgeitems_printermodels2,
							`glpi_printers` as glpi_printers2,
							`glpi_cartridgeitemtypes` as glpi_cartridgeitemtypes2
						WHERE `glpi_cartridgeitemtypes2`.`id` = `glpi_cartridgeitems2`.`cartridgeitemtypes_id`
							AND `glpi_cartridges2`.`cartridgeitems_id` = `glpi_cartridgeitems2`.`id`
							AND `glpi_cartridges2`.`printers_id` = `glpi_printers2`.`id`
							AND `glpi_printers2`.`printermodels_id` = `glpi_cartridgeitems_printermodels2`.`printermodels_id`
							AND " . $where_printermodel2 . "
							AND `glpi_cartridgeitems_printermodels2`.`cartridgeitems_id` = `glpi_cartridgeitems2`.`id`
							AND glpi_printers.entities_id IN (" . $this->where_entities . ")
							AND ( `glpi_cartridges2`.`date_out` IS NOT NULL AND glpi_cartridges2.date_out < `glpi_cartridges`.`date_out` )
							AND `glpi_cartridges2`.`printers_id` = `glpi_cartridges`.`printers_id`
							AND `glpi_cartridgeitems2`.`cartridgeitemtypes_id` in (SELECT `glpi_cartridgeitemtypes`.`id` UNION SELECT cartridgeitemtypes_id2 FROM glpi_cartridgeitemtypes_cartridgeitemtypes citcit where citcit.cartridgeitemtypes_id = `glpi_cartridgeitemtypes`.`id`)
							and glpi_cartridgeitems.is_deleted = 0
						ORDER BY
							`glpi_printers2`.`printermodels_id`,
							`glpi_cartridges2`.`date_out` DESC,
							`glpi_cartridgeitems2`.`name`,
							`glpi_cartridgeitemtypes2`.`name`
						LIMIT 1
					), 0))) as Saldo
				FROM
					`glpi_cartridges`,
					`glpi_cartridgeitems`,
					`glpi_cartridgeitems_printermodels`,
					`glpi_printermodels`,
					`glpi_printers`,
					`glpi_cartridgeitemtypes`
				WHERE `glpi_cartridgeitemtypes`.`id` = `glpi_cartridgeitems`.`cartridgeitemtypes_id`
					AND `glpi_cartridges`.`cartridgeitems_id` = `glpi_cartridgeitems`.`id`
					AND `glpi_cartridges`.`printers_id` = `glpi_printers`.`id`
					AND `glpi_printers`.`printermodels_id` = `glpi_cartridgeitems_printermodels`.`printermodels_id`
					AND `glpi_printermodels`.`id` = `glpi_cartridgeitems_printermodels`.`printermodels_id`
					AND " . $where_printermodel . "
					and glpi_cartridgeitems.is_deleted = 0
					AND `glpi_cartridgeitems_printermodels`.`cartridgeitems_id` = `glpi_cartridgeitems`.`id`
					AND glpi_printers.entities_id IN (" . $this->where_entities . ")
					AND " . $this->sql_date . "
				GROUP BY
					`glpi_cartridgeitems`.`name`,
					`glpi_cartridgeitemtypes`.`id`,
					`glpi_cartridgeitemtypes`.`name`,
					`glpi_printers`.`printermodels_id`
				ORDER BY
					`glpi_cartridgeitemtypes`.`name`,
					`glpi_printers`.`printermodels_id`,
					`glpi_cartridgeitems`.`name`,
					`glpi_cartridges`.`date_out`,
					`glpi_cartridgeitemtypes`.`name`
      ";
        $result = $DB->query($query);

        while ($data = $DB->fetch_assoc($result)) {
            $datas['labels2']['type_' . $data['idtipo']] = $data['tipo'];
        }

        //zera o array de cada impressora
        if (mysql_num_rows($result) > 0)
            mysql_data_seek($result, 0);
        while ($data = $DB->fetch_assoc($result)) {
            foreach (array_keys($datas['labels2']) as $key) {
                if (array_key_exists('datas', $datas) && !array_key_exists($key, $datas['datas'][$data['name']]))
                    $datas['datas'][$data['name']][$key] = 0;
            }
        }

        //seto o valor do tipo para esta impressora
        if (mysql_num_rows($result) > 0)
            mysql_data_seek($result, 0);
        while ($data = $DB->fetch_assoc($result)) {
            $datas['datas'][$data['name']]['type_' . $data['idtipo']] = $data['Saldo'];
        }

        //opções do gráfico
        $datas['options']['showPrinterModels'] = true;
        $datas['options']['force_show_label'] = true;

        return $datas;
    }

    function reportHbarPecas() {
        global $DB, $LANG;

        $datas = array();

        $graph = new PluginMreportingGraph();
        $delay = 30;
        $this->sql_date = PluginMreportingMisc::getSQLDateSupri("supridesk_pecas_ticket.data", $delay);
      
        $contracts = "'" . implode("', '", $_POST['contracts']) . "'";       
        
        
        $where = "`glpi_contracts`.`id` IN (".$contracts.")";
        $name = "`glpi_contracts`.`name`";
       

        //$where_printermodel = "`glpi_printers`.`printermodels_id` = " . (isset($_REQUEST["printermodels_id"]) && $_REQUEST["printermodels_id"] > 0 ? $_REQUEST["printermodels_id"] : "`glpi_printers`.`printermodels_id`");
//`supridesk_stocks`.`name`,`supridesk_stocks_ticket`.`tickets_id`, 
        $query = "  SELECT `glpi_tickets`.`entities_id`, 
                           `supridesk_pecas_ticket`.`quantidade`,
                           `supridesk_pecas_ticket`.`peca_id`,
                           `supridesk_pecas`.`value`,
                           `supridesk_pecas_ticket`.`id` AS id,
                           " . $name . "
                    FROM `supridesk_pecas_ticket`
                    LEFT JOIN `glpi_tickets` ON `glpi_tickets`.`id` = `supridesk_pecas_ticket`.`tickets_id`
                    LEFT JOIN `glpi_entities` ON `glpi_tickets`.`entities_id` = `glpi_entities`.`id`
                    LEFT JOIN `supridesk_pecas` ON `supridesk_pecas`.`id` = `supridesk_pecas_ticket`.`peca_id`
                    LEFT JOIN `glpi_contracts` ON `glpi_contracts`.`id` = `glpi_tickets`.`contracts_id`
                    WHERE " . $where . "";
        
       // echo $query;
        //GROUP BY `glpi_entities`.`name`
        $res = $DB->query($query);
        if (mysql_num_rows($res) > 0)
            mysql_data_seek($res, 0);


        while ($data = $DB->fetch_assoc($res)) {
            $valor = $data['quantidade'] * $data['value'];
            $return[$data['name']] [$data['id']] = $valor;
            $sum_array = array_sum($return[$data['name']]);
            //$total[$data['name']] = number_format($sum_array, 2, ',', '.'); 
            $total_name[$data['name']] = number_format($sum_array, 2, ',', '.');
            // unset($datas['datas'][$data['name']] [$data['id']]);   
        }

        
        if (mysql_num_rows($res) > 0)
            mysql_data_seek($res, 0);
        while ($data = $DB->fetch_assoc($res)) {
            $return[$data['name']][$data['id']] = $data['quantidade'];
            //var_export($datas['datas'][$data['name'].$data['quantidade']][$data['id']]);
            //$datas['datas'][$data['name']] ['name'] = $data['name'];
            $sum_array_qtd = array_sum($return[$data['name']]);
            $total_quantidade[$data['name']] = $sum_array_qtd;
            //unset($datas['datas'][$data['name'].$data['quantidade']][$data['id']]);
            //$total['quantidade'] = $data['quantidade'];
        }


        if (mysql_num_rows($res) > 0)
            mysql_data_seek($res, 0);
        while ($data = $DB->fetch_assoc($res)) {
            $datas['datas'][$data['name'] . " - " . $total_name[$data['name']]] = $total_quantidade[$data['name']];
            //$datas['datas'][$data['name']] = $total[$data['name']];
        }
         //var_export($datas);die();
        $datas['options']['showContracts'] = true;
        $datas['options']['force_show_label'] = true;
        

        $graph->showHbar($datas, $LANG['plugin_mreporting']['Helpdesk']['reportHbarPecas']['title']);

        $graph = new PluginMreportingGraph();

        $datas = array();
        $delay = 365;
        $this->sql_date = PluginMreportingMisc::getSQLDateSupri("glpi_cartridges.date_use", $delay);

        $query = "SELECT count(*) as nb,
                        glpi_cartridges.id,
                        glpi_cartridges.printers_id,
                        glpi_cartridges.date_out,
                        CONCAT(`glpi_cartridgeitems`.`name`, ' - ', `glpi_cartridgeitemtypes`.`name`) AS tipo,
                        DATE_FORMAT(glpi_cartridges.date_use, '%y%m') as ordem,
                        DATE_FORMAT(glpi_cartridges.date_use, '%b%y') as month_l,
                        (
                                SELECT entity_id_to
                                FROM supridesk_log_printer_entity l
                                WHERE l.printers_id = `glpi_cartridges`.printers_id
                                  AND l.date <= `glpi_cartridges`.date_use
                                order by date desc
                                LIMIT 1
                        ) as id_entity_pre_troca,
                        (
                                SELECT entity_id_from
                                FROM supridesk_log_printer_entity l
                                WHERE l.printers_id = `glpi_cartridges`.printers_id
                                  AND l.date >= `glpi_cartridges`.date_use
                                order by date asc
                                LIMIT 1
                        ) as id_entity_first_2012,
                        (
                                SELECT id
                                FROM glpi_entities
                                WHERE id = (SELECT entities_id FROM glpi_printers where id = `glpi_cartridges`.printers_id)
                        ) as id_entity_atual,
                        glpi_infocoms.value as valor,
                        glpi_printermodels.name as printer_model

                FROM `glpi_cartridges`,
                     `glpi_cartridgeitems_printermodels`,
                     `glpi_printers`,
                     `glpi_printermodels`,
                     `glpi_cartridgeitemtypes`,
                     `glpi_cartridgeitems` LEFT JOIN `glpi_infocoms` ON ( glpi_cartridgeitems.id = glpi_infocoms.items_id and glpi_infocoms.itemtype = 'CartridgeItem')

                WHERE `glpi_cartridgeitemtypes`.`id` = `glpi_cartridgeitems`.`cartridgeitemtypes_id`
                AND `glpi_cartridges`.`cartridgeitems_id` = `glpi_cartridgeitems`.`id`
                AND `glpi_cartridges`.`printers_id` = `glpi_printers`.`id`
                AND `glpi_printers`.`printermodels_id` = `glpi_cartridgeitems_printermodels`.`printermodels_id`
                AND `glpi_cartridgeitems_printermodels`.`cartridgeitems_id` = `glpi_cartridgeitems`.`id`                
                AND " . $this->sql_date . "
                and glpi_printers.printermodels_id = glpi_printermodels.id

                GROUP BY tipo, ordem, month_l, id_entity_pre_troca, id_entity_first_2012, id_entity_atual
                HAVING
                        (id_entity_pre_troca is not null and id_entity_pre_troca IN (" . $this->where_entities . ")  )
                        or
                        ( id_entity_pre_troca is null and id_entity_first_2012 is not null and id_entity_first_2012 IN (" . $this->where_entities . ") )
                        or
                        ( id_entity_pre_troca is null and id_entity_first_2012 is null and id_entity_atual IN (" . $this->where_entities . ") )
                ORDER BY tipo, ordem";
        $res = $DB->query($query);

        $data = $DB->fetch_assoc($res);
        //var_export($data);
        //die();
    }
    
    
    /*function reportGlineInvComputador() {
        global $DB, $LANG;        
        
        $graph = new PluginMreportingGraph();
        
        $compmodelo = $_REQUEST["computermodels_id"];
        $fabricante = $_REQUEST["manufacturers_id"];
        $type = $_REQUEST["computertypes_id"];

        //filtro para modelo especifico
        if($compmodelo > 0){
            $where_printermodel = "AND `glpi_computermodels`.`id` = " . $compmodelo;
        }else{
           $where_printermodel = ''; 
        }       
        
        //filtro para buscar por fabricante
        if($fabricante > 0){
            $where_fabricante = "AND `glpi_computers`.`manufacturers_id` = " . $fabricante;
        }else{
            $where_fabricante = ''; 
        } 
        
        //filtro por tipo de equipamento
        if($type > 0){
            $where_type = "AND `glpi_computers`.`computertypes_id` = " . $type;
        }else{
            $where_type = ''; 
        } 
        
        $query = "SELECT count(*) AS total, 
                        `glpi_computermodels`.`name` AS modelo, 
                        `glpi_manufacturers`.`name` AS fabricante,
                        `glpi_computers`.`contact` AS nome_alternativo,
                        `glpi_computertypes`.`name` AS tipo,
                        `glpi_computers`.`id` AS id
                    FROM `glpi_computers` 
                    INNER JOIN `glpi_computermodels` ON  `glpi_computermodels`.`id` = `glpi_computers`.`computermodels_id`
                    INNER JOIN `glpi_manufacturers` ON  `glpi_manufacturers`.`id` = `glpi_computers`.`manufacturers_id`
                    INNER JOIN `glpi_computertypes` ON  `glpi_computertypes`.`id` = `glpi_computers`.`computertypes_id`
                    WHERE 1 
                    AND `glpi_computers`.`states_id` = 23
                    ".$where_printermodel."
                    ".$where_fabricante."
                    ".$where_type."
                    GROUP BY `glpi_computermodels`.`id`";

        $res = $DB->query($query);

        //curve lines
        $datas['spline'] = true;
        $datas['options']['showComputerModels'] = true;
        $datas['options']['force_show_label'] = true;
        $datas['options']['hide_graph'] = true;
        $datas['options']['showManufacturer'] = true;
        $datas['options']['showComputerType'] = true;
        $datas['datas'] = true;
        //$datas['options']['showData'] = true;

        $graph->showGline($datas, $LANG['plugin_mreporting']['Helpdesk']['reportGlineInvComputador']['title'], '');

        echo "<table border='1' width='100%' style='text-align:left; border-collapse:collapse;' cellspacing='0' cellspading='0'>";
        echo "<tr style='background-color: #BFC7D1;' height='30px'>";
        echo "<td>&nbsp;TIPO</td>";
        echo "<td>&nbsp;FABRICANTE</td>";        
        echo "<td>&nbsp;MODELO</td>";
        echo "<td>&nbsp;NOME ALTERNATIVO</td>";
        echo "<td style='text-align:center;'>QUANTIDADE</td>";
        echo "</tr>";
        
        $c = array();
        
        while($result = $DB->fetch_assoc($res)){
            $c[] = $result['total'];
            echo "<tr style='background-color: #DEE1E5;' height='25px'>";
            echo "<td>&nbsp{$result['tipo']}</td>";            
            echo "<td>&nbsp{$result['fabricante']}</td>";
            echo "<td>&nbsp{$result['modelo']}</td>";
            echo "<td><a href='../../../front/computer.form.php?id={$result['id']}'>&nbsp{$result['nome_alternativo']}</a></td>"; 
            echo "<td width='45px' style='text-align:center;'>{$result['total']}</td>";
            echo "</tr>";
        }

        $count = array_sum($c);

        echo "<tr>";
        echo "<td height='30px' colspan='4' style='text-align:right;'>TOTAL : &nbsp;</td>";
        echo "<td style='text-align:center;'>{$count}</td>";        
        echo "<td>&nbsp;</td>";
        echo "</tr>";
        echo "</table>";

        return null;
    }*/
    
    /*function reportGlineInvMonitor() {
        global $DB, $LANG;        
        
        $graph = new PluginMreportingGraph();
        
        $monitormodelo = $_REQUEST["monitormodels_id"];
        $fabricante = $_REQUEST["manufacturers_id"];
        $type = $_REQUEST["monitortypes_id"];

        //filtro para modelo especifico
        if($monitormodelo > 0){
            $where_monitormodel = "AND `glpi_monitormodels`.`id` = " . $monitormodelo;
        }else{
            $where_monitormodel = ''; 
        } 
        
        //filtro para buscar por fabricante
        if($fabricante > 0){
            $where_fabricante = "AND `glpi_monitors`.`manufacturers_id` = " . $fabricante;
        }else{
            $where_fabricante = ''; 
        } 
        
        //filtro por tipo de equipamento
        if($type > 0){
            $where_type = "AND `glpi_monitors`.`monitortypes_id` = " . $type;
        }else{
            $where_type = ''; 
        } 
        
        $query = "SELECT count(*) AS total, 
                        `glpi_monitormodels`.`name` AS modelo, 
                        `glpi_manufacturers`.`name` AS fabricante,
                        `glpi_monitors`.`contact` AS nome_alternativo,
                        `glpi_monitortypes`.`name` AS tipo,
                        `glpi_monitors`.`id` AS id
                    FROM `glpi_monitors` 
                    INNER JOIN `glpi_monitormodels` ON  `glpi_monitormodels`.`id` = `glpi_monitors`.`monitormodels_id`
                    INNER JOIN `glpi_manufacturers` ON  `glpi_manufacturers`.`id` = `glpi_monitors`.`manufacturers_id`
                    INNER JOIN `glpi_monitortypes` ON  `glpi_monitortypes`.`id` = `glpi_monitors`.`monitortypes_id`
                    WHERE 1 
                    AND `glpi_monitors`.`states_id` = 23
                    ".$where_monitormodel."
                    ".$where_fabricante."
                    ".$where_type."
                    GROUP BY `glpi_monitormodels`.`id`";
        
       // var_export($query);

        $res = $DB->query($query);

        //curve lines
        $datas['spline'] = true;
        $datas['options']['showMonitorModels'] = true;
        $datas['options']['force_show_label'] = true;
        $datas['options']['hide_graph'] = true;
        $datas['options']['showManufacturer'] = true;
        $datas['options']['showMonitorType'] = true;
        $datas['datas'] = true;
        //$datas['options']['OcultData'] = true;

        $graph->showGline($datas, $LANG['plugin_mreporting']['Helpdesk']['reportGlineInvMonitor']['title'], '');

        echo "<table border='1' width='100%' style='text-align:left; border-collapse:collapse;' cellspacing='0' cellspading='0'>";
        echo "<tr style='background-color: #BFC7D1;' height='30px'>";
        echo "<td>&nbsp;TIPO</td>";
        echo "<td>&nbsp;FABRICANTE</td>";        
        echo "<td>&nbsp;MODELO</td>";
        echo "<td>&nbsp;NOME ALTERNATIVO</td>";
        echo "<td style='text-align:center;'>QUANTIDADE</td>";
        echo "</tr>";
        
        $c = array();

        while($result = $DB->fetch_assoc($res)){
            $c[] = $result['total'];
            echo "<tr style='background-color: #DEE1E5;' height='25px'>";
            echo "<td>&nbsp{$result['tipo']}</td>";            
            echo "<td>&nbsp{$result['fabricante']}</td>";
            echo "<td>&nbsp{$result['modelo']}</td>";
            echo "<td><a href='../../../front/monitor.form.php?id={$result['id']}'>&nbsp{$result['nome_alternativo']}</a></td>"; 
            echo "<td width='45px' style='text-align:center;'>{$result['total']}</td>";
            echo "</tr>";
        }

        $count = array_sum($c);

        echo "<tr>";     
        echo "<td height='30px' colspan='4' style='text-align:right;'>TOTAL : &nbsp;</td>";
        echo "<td style='text-align:center;'>{$count}</td>";        
        echo "<td>&nbsp;</td>";
        echo "</tr>";
        echo "</table>";

        return null;
    }*/
    
    /*function reportGlineInvPrinter() {
        global $DB, $LANG;        
        
        $graph = new PluginMreportingGraph();
        
        $impmodelo = $_REQUEST["printermodels_id"];
        $fabricante = $_REQUEST["manufacturers_id"];
        $type = $_REQUEST["printertypes_id"];

        //filtro para modelo especifico
        if($impmodelo > 0){
            $where_printermodel = "AND `glpi_printermodels`.`id` = " . $impmodelo;
        }else{
           $where_printermodel = ''; 
        }         
        
        //filtro para buscar por fabricante
        if($fabricante > 0){
            $where_fabricante = "AND `glpi_printers`.`manufacturers_id` = " . $fabricante;
        }else{
            $where_fabricante = ''; 
        } 
        
        //filtro por tipo de equipamento
        if($type > 0){
            $where_type = "AND `glpi_printers`.`printertypes_id` = " . $type;
        }else{
            $where_type = ''; 
        } 
        
        $query = "SELECT count(*) AS total, 
                        `glpi_printermodels`.`name` AS modelo, 
                        `glpi_manufacturers`.`name` AS fabricante,
                        `glpi_printers`.`contact` AS nome_alternativo,
                        `glpi_printertypes`.`name` AS tipo,
                        `glpi_printers`.`id` AS id
                    FROM `glpi_printers` 
                    INNER JOIN `glpi_printermodels` ON  `glpi_printermodels`.`id` = `glpi_printers`.`printermodels_id`
                    INNER JOIN `glpi_manufacturers` ON  `glpi_manufacturers`.`id` = `glpi_printers`.`manufacturers_id`
                    INNER JOIN `glpi_printertypes` ON  `glpi_printertypes`.`id` = `glpi_printers`.`printertypes_id`
                    WHERE 1 
                    AND `glpi_printers`.`states_id` = 23
                    ".$where_printermodel."
                    ".$where_fabricante."
                    ".$where_type."
                    GROUP BY `glpi_printermodels`.`id`";

        $res = $DB->query($query);

        //curve lines
        $datas['spline'] = true;
        $datas['options']['showPrinterModels'] = true;
        $datas['options']['force_show_label'] = true;
        $datas['options']['hide_graph'] = true;
        $datas['options']['showManufacturer'] = true;
        $datas['options']['showPrinterType'] = true;
        $datas['datas'] = true;
        //$datas['options']['showData'] = true;

        $graph->showGline($datas, $LANG['plugin_mreporting']['Helpdesk']['reportGlineInvPrinter']['title'], '');

        echo "<table border='1' width='100%' style='text-align:left; border-collapse:collapse;' cellspacing='0' cellspading='0'>";
        echo "<tr style='background-color: #BFC7D1;' height='30px'>";
        echo "<td>&nbsp;TIPO</td>";
        echo "<td>&nbsp;FABRICANTE</td>";        
        echo "<td>&nbsp;MODELO</td>";
        echo "<td>&nbsp;NOME ALTERNATIVO</td>";
        echo "<td style='text-align:center;'>QUANTIDADE</td>";
        echo "</tr>";
        
        $c = array();
        
        while($result = $DB->fetch_assoc($res)){
            $c[] = $result['total'];
            echo "<tr style='background-color: #DEE1E5;' height='25px'>";
            echo "<td>&nbsp{$result['tipo']}</td>";            
            echo "<td>&nbsp{$result['fabricante']}</td>";
            echo "<td>&nbsp{$result['modelo']}</td>";
            echo "<td><a href='../../../front/printer.form.php?id={$result['id']}'>&nbsp{$result['nome_alternativo']}</a></td>"; 
            echo "<td width='45px' style='text-align:center;'>{$result['total']}</td>";
            echo "</tr>";
        }

        $count = array_sum($c);

        echo "<tr>";
        echo "<td height='30px' colspan='4' style='text-align:right;'>TOTAL : &nbsp;</td>";
        echo "<td style='text-align:center;'>{$count}</td>";        
        echo "<td>&nbsp;</td>";
        echo "</tr>";
        echo "</table>";

        return null;
    }*/
    
    function reportHgbarChamadosPorCategoriaAnalista() {
        global $DB, $LANG;
        
        $datas = array();

        $graph = new PluginMreportingGraph();
        
        //opções do gráfico
        if (!isset($_REQUEST["usercategories_id"]) || intval($_REQUEST["usercategories_id"]) == 0)
            $_REQUEST["usercategories_id"] = true;       
        
        $datas['options']['showAnalistas'] = $_REQUEST["usercategories_id"];
        $datas['options']['force_show_label'] = true;
        $datas['datas'] = true;
        
        $categoria = $_REQUEST["usercategories_id"];
        $data1 = $_REQUEST["date1"] . " 00:00:00";
        $data2 = $_REQUEST["date2"] . " 23:59:59";
        //var_export($categoria);
        //die();
        if($_REQUEST['categoriaTemp']){
            if($_REQUEST['usercategories_id'] != -1){
                $analista = $_REQUEST['usercategories_id'];
                //echo $_REQUEST['categoriaTemp']." ".$_REQUEST['usercategories_id'];
                return $this->GrupoAnalista($_REQUEST['categoriaTemp'],$data1,$data2,$analista);
            }else{
                return $this->GrupoAnalista($_REQUEST['categoriaTemp'],$data1,$data2);
            }
        }else{
            if($categoria != -1){
                return $this->GrupoAnalista($categoria,$data1,$data2);
            }
        }
        
        return $datas;
       
    }
    
    function GrupoAnalista($categoria,$data1,$data2,$analista=null) {
        global $DB, $LANG;
        
        session_start();
        $_SESSION['TempCategoria'] = $categoria;  
        
        $datas = array();        
        $datas['options']['showGroup'] = true;
        $datas['options']['ocultaData'] = true;
        
        if($analista != null){
            $where_analista = " AND `u`.`id` = " . $analista. " ";
        }

        $where = " AND `u`.`usercategories_id` = " . $categoria;
        
        $query = "SELECT u.id, concat( u.firstname, ' ',  u.realname) as usuario
				    FROM glpi_users u, glpi_usercategories uc
                    WHERE u.usercategories_id = uc.id
                    AND u.is_active = 1
				    AND u.is_deleted = 0
                    AND `u`.`usercategories_id` = " . $categoria. "
                    ".$where_analista."
                    ORDER BY u.firstname ";
        $result = $DB->query($query);
        
        $datas['labels2']['chamados_abertos'] = "Chamados abertos para o analista no Período";
        $datas['labels2']['chamados_abertos_pelo_usuario'] = "Chamados abertos pelo analista no Período";
        $datas['labels2']['chamados_solucionados'] = "Chamados solucionados no Período";
        $datas['labels2']['iteracoes_chamados'] = "Iterações em chamados no Período";
        $datas['labels2']['chamados_em_abertos'] = "Chamados em aberto até a data final do Período";
        
        if (mysql_num_rows($result) > 0)
            mysql_data_seek($result, 0);
        while ($data = $DB->fetch_assoc($result)) {
            foreach (array_keys($datas['labels2']) as $key) {                
                if (is_array($datas) && array_key_exists('datas', $datas))
                    if (is_array($datas['datas']) && !array_key_exists($key, $datas['datas'][$data['usuario']]))
                        $datas['datas'][$data['usuario']][$key] = 0;
            }
        }
        
        $between = " BETWEEN '".$data1."' AND '".$data2."' "; 
        //seto o valor do tipo para esta impressora
        if (mysql_num_rows($result) > 0)
            mysql_data_seek($result, 0);
        while ($data = $DB->fetch_assoc($result)) {
            $sqlCounter = "SELECT COUNT(*) as count
								FROM
									glpi_tickets t,
									glpi_tickets_users tu
									left join glpi_users u ON u.id = tu.users_id
								WHERE t.id = tu.tickets_id
								  AND t.entities_id IN (" . $this->where_entities . ")
								  AND tu.type = 2
								  AND tu.users_id = " . $data['id'] . "
								  AND t.date " . $between;

            $resCounter = $DB->query($sqlCounter);
            $datas['datas'][$data['usuario']]['chamados_abertos'] = $DB->result($resCounter, 0, 'count');
            
             $sqlCounter = "SELECT COUNT(*) as count
								FROM
									glpi_tickets t
									left join glpi_users u ON u.id = t.users_id_recipient
								WHERE t.entities_id IN (" . $this->where_entities . ")
								  AND t.requesttypes_id <> 7
								  AND t.users_id_recipient = " . $data['id'] . "
								  AND t.date " . $between;

            $resCounter = $DB->query($sqlCounter);
            $datas['datas'][$data['usuario']]['chamados_abertos_pelo_usuario'] = $DB->result($resCounter, 0, 'count');
            
            $sqlCounter = "
				select count(*) as count
				from
					glpi_tickets t,
					glpi_tickets_users tu
					left join glpi_users u ON u.id = tu.users_id
				where t.id = tu.tickets_id
					AND t.entities_id IN (" . $this->where_entities . ")
					and tu.type = 2
					and tu.users_id = " . $data['id'] . "
					and t.solvedate " . $between;
            
            $resCounter = $DB->query($sqlCounter);
            $datas['datas'][$data['usuario']]['chamados_solucionados'] = $DB->result($resCounter, 0, 'count');
            
            $sqlCounter = "
				select count(*) as count
				from
					glpi_tickets t
					LEFT JOIN glpi_ticketfollowups tf ON t.id = tf.tickets_id
				where tf.users_id = " . $data['id'] . "
					AND t.entities_id IN (" . $this->where_entities . ")
					and tf.date " . $between;

            $resCounter = $DB->query($sqlCounter);
            $datas['datas'][$data['usuario']]['iteracoes_chamados'] = $DB->result($resCounter, 0, 'count');
            
            $date2 = $_REQUEST["date2"] . " 23:59:59";
            $sqlCounter = "
				select count(*) as count
				from
					glpi_tickets t,
					glpi_tickets_users tu
					left join glpi_users u ON u.id = tu.users_id
				where t.id = tu.tickets_id
					AND t.entities_id IN (" . $this->where_entities . ")
					and tu.type = 2
					and tu.users_id = " . $data['id'] . "
					and t.status != 'closed' and t.status != 'solved' and t.status != 'canceled'
					and t.date <= '" . $date2 . "'";
            
            $resCounter = $DB->query($sqlCounter);
            $datas['datas'][$data['usuario']]['chamados_em_abertos'] = $DB->result($resCounter, 0, 'count');
        }
        
        return $datas;
         
            
    }
    
    function reportGlineTicketAnalista() {
        global $DB, $LANG;
        
        $mreporting = new PluginMreportingProfile();  
        $analista = $_REQUEST["usercategories_id"];
       
        
        $datas = array();                
        
        $where_analista = " AND `u`.`id` = " . $analista. " ";
                            
        $query = "SELECT u.id, concat( u.firstname, ' ',  u.realname) as usuario
				    FROM glpi_users u, glpi_usercategories uc
                    WHERE u.usercategories_id = uc.id
                    AND u.is_active = 1
				    AND u.is_deleted = 0                    
                    ".$where_analista."
                    ORDER BY u.firstname ";
        $result = $DB->query($query);
        
        $data1 = $_REQUEST["date1"] . " 00:00:00";
        $data2 = $_REQUEST["date2"] . " 23:59:59";
        
        $begin = new DateTime($data1);
        $end = new DateTime($data2);        
        $intervalofinal = date('Y-m-d', strtotime("+1 days",strtotime($data2)));
        $end = new DateTime($intervalofinal);
        
        $interval = DateInterval::createFromDateString('1 day');
        $period = new DatePeriod($begin, $interval, $end);   
        
        $dtbrasil = array();
        
        while($data = $DB->fetch_assoc($result)){
            
            foreach ( $period as $dt ){
                
                $label = $this->buscaNomeDia($dt->format("l"));
                
                $dtfinal = $dt->format("Y-m-d")." 23:59:59";
                $sqlRequisicao = "SELECT DISTINCT t.type, DATE_FORMAT(t.date, '%Y-%m-%d') AS date,
                                            DATE_FORMAT(t.date, '%d/%m') AS datebrasil,   
                                            COUNT(*) as count
                                        FROM
                                            glpi_tickets t,
                                            glpi_tickets_users tu
                                            left join glpi_users u ON u.id = tu.users_id
                                        WHERE t.id = tu.tickets_id
                                          AND t.entities_id IN (" . $this->where_entities . ")                                          
                                          AND tu.users_id = " . $data['id'] . "
                                          AND t.date BETWEEN '".$dt->format("Y-m-d H:i:s")."' 
                                                        AND '".$dtfinal."'
                                        GROUP BY t.type
                                        ORDER BY t.date";

                $resRequisicao = $DB->query($sqlRequisicao);
                
                if (mysql_num_rows($resRequisicao) > 0){                
                    while($data_2 = $DB->fetch_assoc($resRequisicao)){
                         if($data_2['type'] == 1){
                             $contInc = true;
                             $label2 = "Incidentes Abertos";
                         }elseif($data_2['type'] == 2){
                             $contReq = true;
                             $label2 = "Requisições Abertas";
                         }                      

                        $databrasil = (string)$data_2['datebrasil'];
                        $dtbrasil['datas'][$label2][ $data_2['date']] =  $data_2['date'];                    
                        $datas['labels2'][$label." ".$databrasil]= $databrasil;
                        $datas['datas'][$label2][$databrasil] = $data_2['count'];

                    }
                }
                
                if(!isset($contInc)){ $datas['datas']['Incidentes Abertos'][$dt->format("d/Y")] = 0;  }                
                if(!isset($contReq)){ $datas['datas']['Requisições Abertas'][$dt->format("d/Y")] = 0; }
                
                $sqlSolucionados = "SELECT DISTINCT t.type, DATE_FORMAT(t.solvedate, '%Y-%m-%d') AS date,
                                            DATE_FORMAT(t.solvedate, '%d/%m') AS datebrasil,   
                                            COUNT(*) as count
                                        FROM
                                            glpi_tickets t,
                                            glpi_tickets_users tu
                                            left join glpi_users u ON u.id = tu.users_id
                                        WHERE t.id = tu.tickets_id
                                          AND t.entities_id IN (" . $this->where_entities . ")                                          
                                          AND tu.users_id = " . $data['id'] . "
                                          AND t.solvedate  BETWEEN '".$dt->format("Y-m-d H:i:s")."' 
                                                        AND '".$dtfinal."'
                                        GROUP BY t.type
                                        ORDER BY t.solvedate ";
                
                $resSolucionados = $DB->query($sqlSolucionados);
                
                if (mysql_num_rows($resSolucionados) > 0){
                    while($data_3 = $DB->fetch_assoc($resSolucionados)){
                        
                         if($data_3['type'] == 1){
                             $contIncS = true;
                             $label3 = "Incidentes Solucionados";
                         }elseif($data_3['type'] == 2){
                             $contReqS = true;
                             $label3 = "Requisições Solucionadas";
                         }  

                        $databrasil2 = (string)$data_3['datebrasil'];
                        $dtbrasil['datas'][$label3][$data_3['date']] =  $data_3['date'];
                        $datas['labels2'][$label." ".$databrasil2]= $databrasil2;
                        $datas['datas'][$label3][$databrasil2] = $data_3['count'];
                    }
                }
                
                if(!isset($contIncS)){ $datas['datas']['Incidentes Solucionados'][$dt->format("d/Y")] = 0;  }                
                if(!isset($contReqS)){ $datas['datas']['Requisições Solucionadas'][$dt->format("d/Y")] = 0; }
               
                $sqlIteracoes = "SELECT DISTINCT  DATE_FORMAT(tf.date, '%Y-%m-%d') AS date,
                                            DATE_FORMAT(tf.date, '%d/%m') AS datebrasil,   
                                            COUNT(*) as count
                                        FROM
                                            glpi_tickets t
                                            LEFT JOIN glpi_ticketfollowups tf ON t.id = tf.tickets_id  
                                        WHERE t.id = tf.tickets_id
                                          AND t.entities_id IN (" . $this->where_entities . ")                                          
                                          AND tf.users_id = " . $data['id'] . "
                                          AND tf.date  BETWEEN '".$dt->format("Y-m-d H:i:s")."' 
                                                        AND '".$dtfinal."'
                                        
                                        ORDER BY tf.date ";
                
                $resIteracoes = $DB->query($sqlIteracoes);
                
                if (mysql_num_rows($resSolucionados) > 0){
                    while($data4 = $DB->fetch_assoc($resIteracoes)){                     
                        $label4 = "Interações";
                        $iter = true;

                        $databrasil3 = (string)$data4['datebrasil'];
                        $dtbrasil['datas'][$label4][ $data4['date']] =  $data4['date'];
                        $datas['labels2'][$label." ".$databrasil3]= $databrasil3;
                        $datas['datas'][$label4][$databrasil3] = $data4['count'];

                    }
                }
                
                if(!isset($iter)){ $datas['datas']['Interações'][$dt->format("d/Y")] = 0;  }    
                
            }
            
        }
        
        //var_export($datas);
        //////////////////////////////////////////////Incidentes////////////////////////////////////////
        $resArrayIncReq = array_diff_assoc($dtbrasil['datas']['Incidentes Abertos'],$dtbrasil['datas']['Requisições Abertas']);
        if($resArrayIncReq != null){            
            $inc = $this->criaArray($resArrayIncReq);            
            foreach($inc as $rInc){                
                $datas['datas']['Requisições Abertas'][$rInc] = 0;                
            }          
        }
        ksort($datas['datas']['Requisições Abertas']);
        
        
        $resArrayIncReqS = array_diff_assoc($dtbrasil['datas']['Incidentes Abertos'],$dtbrasil['datas']['Requisições Solucionadas']);
        if($resArrayIncReqS != null){            
            $inc = $this->criaArray($resArrayIncReqS);            
            foreach($inc as $rInc){                
                $datas['datas']['Requisições Solucionadas'][$rInc] = 0;                
            }          
        }
        ksort($datas['datas']['Requisições Solucionadas']);
        
        $resArrayIncIncS = array_diff_assoc($dtbrasil['datas']['Incidentes Abertos'],$dtbrasil['datas']['Incidentes Solucionados']);
        if($resArrayIncIncS != null){            
            $inc = $this->criaArray($resArrayIncIncS);            
            foreach($inc as $rInc){                
                $datas['datas']['Incidentes Solucionados'][$rInc] = 0;                
            }          
        }
        ksort($datas['datas']['Incidentes Solucionados']);
        
        $resArrayIncIter = array_diff_assoc($dtbrasil['datas']['Incidentes Abertos'],$dtbrasil['datas']['Interações']);
        if($resArrayIncIter != null){            
            $inc = $this->criaArray($resArrayIncIter);            
            foreach($inc as $rInc){                
                $datas['datas']['Interações'][$rInc] = 0;                
            }          
        }
        ksort($datas['datas']['Interações']);
        
        
        //////////////////////////////////Requisicao ///////////////////////////////////////////        
        $resArrayReqInc = array_diff_assoc($dtbrasil['datas']['Requisições Abertas'],$dtbrasil['datas']['Incidentes Abertos']);
        if($resArrayReqInc != null){            
            $inc = $this->criaArray($resArrayReqInc);            
            foreach($inc as $rInc){                
                $datas['datas']['Incidentes Abertos'][$rInc] = 0;                
            }          
        }
        ksort($datas['datas']['Incidentes Abertos']);
        
        
        $resArrayReqReqS = array_diff_assoc($dtbrasil['datas']['Requisições Abertas'],$dtbrasil['datas']['Requisições Solucionadas']);
        if($resArrayReqReqS != null){            
            $inc = $this->criaArray($resArrayReqReqS);            
            foreach($inc as $rInc){                
                $datas['datas']['Requisições Solucionadas'][$rInc] = 0;                
            }          
        }
        ksort($datas['datas']['Requisições Solucionadas']);
        
        $resArrayReqIncS = array_diff_assoc($dtbrasil['datas']['Requisições Abertas'],$dtbrasil['datas']['Incidentes Solucionados']);
        if($resArrayReqIncS != null){            
            $inc = $this->criaArray($resArrayReqIncS);            
            foreach($inc as $rInc){                
                $datas['datas']['Incidentes Solucionados'][$rInc] = 0;                
            }          
        }
        ksort($datas['datas']['Incidentes Solucionados']);
        
        $resArrayReqIter = array_diff_assoc($dtbrasil['datas']['Requisições Abertas'],$dtbrasil['datas']['Interações']);
        if($resArrayReqIter != null){            
            $inc = $this->criaArray($resArrayReqIter);            
            foreach($inc as $rInc){                
                $datas['datas']['Interações'][$rInc] = 0;                
            }          
        }
        ksort($datas['datas']['Interações']);
        
        
        ///////////////////////////////////////Requisicoes Solucionadas///////////////////////////////////////       
        $resArrayReqSInc = array_diff_assoc($dtbrasil['datas']['Requisições Solucionadas'],$dtbrasil['datas']['Incidentes Abertos']);
        if($resArrayReqSInc != null){            
            $inc = $this->criaArray($resArrayReqSInc);            
            foreach($inc as $rInc){                
                $datas['datas']['Incidentes Abertos'][$rInc] = 0;                
            }          
        }
        ksort($datas['datas']['Incidentes Abertos']);
        
        
        $resArrayReqSReq = array_diff_assoc($dtbrasil['datas']['Requisições Solucionadas'],$dtbrasil['datas']['Requisições Abertas']);
        if($resArrayReqSReq != null){            
            $inc = $this->criaArray($resArrayReqSReq);            
            foreach($inc as $rInc){                
                $datas['datas']['Requisições Abertas'][$rInc] = 0;                
            }          
        }
        ksort($datas['datas']['Requisições Abertas']);
        
        $resArrayReqSIncS = array_diff_assoc($dtbrasil['datas']['Requisições Solucionadas'],$dtbrasil['datas']['Incidentes Solucionados']);
        if($resArrayReqSIncS != null){            
            $inc = $this->criaArray($resArrayReqSIncS);            
            foreach($inc as $rInc){                
                $datas['datas']['Incidentes Solucionados'][$rInc] = 0;                
            }          
        }
        ksort($datas['datas']['Incidentes Solucionados']);
        
        $resArrayReqSIter = array_diff_assoc($dtbrasil['datas']['Requisições Solucionadas'],$dtbrasil['datas']['Interações']);
        if($resArrayReqSIter != null){            
            $inc = $this->criaArray($resArrayReqSIter);            
            foreach($inc as $rInc){                
                $datas['datas']['Interações'][$rInc] = 0;                
            }          
        }
        ksort($datas['datas']['Interações']);
        
        
        
        ///////////////////////////////////////Incidentes Solucionados///////////////////////////////////////       
        $resArrayIncSInc = array_diff_assoc($dtbrasil['datas']['Incidentes Solucionados'],$dtbrasil['datas']['Incidentes Abertos']);
        if($resArrayIncSInc != null){            
            $inc = $this->criaArray($resArrayIncSInc);            
            foreach($inc as $rInc){                
                $datas['datas']['Incidentes Abertos'][$rInc] = 0;                
            }          
        }
        ksort($datas['datas']['Incidentes Abertos']);
        
        
        $resArrayIncSReq = array_diff_assoc($dtbrasil['datas']['Incidentes Solucionados'],$dtbrasil['datas']['Requisições Abertas']);
        if($resArrayIncSReq != null){            
            $inc = $this->criaArray($resArrayIncSReq);            
            foreach($inc as $rInc){                
                $datas['datas']['Requisições Abertas'][$rInc] = 0;                
            }          
        }
        ksort($datas['datas']['Requisições Abertas']);
        
        $resArrayIncSReqS = array_diff_assoc($dtbrasil['datas']['Incidentes Solucionados'],$dtbrasil['datas']['Requisições Solucionadas']);
        if($resArrayIncSReqS != null){            
            $inc = $this->criaArray($resArrayIncSReqS);            
            foreach($inc as $rInc){                
                $datas['datas']['Requisições Solucionadas'][$rInc] = 0;                
            }          
        }
        ksort($datas['datas']['Requisições Solucionadas']);
        
        $resArrayIncSIter = array_diff_assoc($dtbrasil['datas']['Incidentes Solucionados'],$dtbrasil['datas']['Interações']);
        if($resArrayIncSIter != null){            
            $inc = $this->criaArray($resArrayIncSIter);            
            foreach($inc as $rInc){                
                $datas['datas']['Interações'][$rInc] = 0;                
            }          
        }
        ksort($datas['datas']['Interações']);
        
        
         ///////////////////////////////////////Iterações///////////////////////////////////////       
        $resArrayIterInc = array_diff_assoc($dtbrasil['datas']['Interações'],$dtbrasil['datas']['Incidentes Abertos']);
        if($resArrayIterInc != null){            
            $inc = $this->criaArray($resArrayIterInc);            
            foreach($inc as $rInc){                
                $datas['datas']['Incidentes Abertos'][$rInc] = 0;                
            }          
        }
        ksort($datas['datas']['Incidentes Abertos']);
        
        
        $resArrayIterReq = array_diff_assoc($dtbrasil['datas']['Interações'],$dtbrasil['datas']['Requisições Abertas']);
        if($resArrayIterReq != null){            
            $inc = $this->criaArray($resArrayIterReq);            
            foreach($inc as $rInc){                
                $datas['datas']['Requisições Abertas'][$rInc] = 0;                
            }          
        }
        ksort($datas['datas']['Requisições Abertas']);
        
        $resArrayIterReqS = array_diff_assoc($dtbrasil['datas']['Iterações'],$dtbrasil['datas']['Requisições Solucionadas']);
        if($resArrayIterReqS != null){            
            $inc = $this->criaArray($resArrayIterReqS);            
            foreach($inc as $rInc){                
                $datas['datas']['Requisições Solucionadas'][$rInc] = 0;                
            }          
        }
        ksort($datas['datas']['Requisições Solucionadas']);
        
        $resArrayIterIncS = array_diff_assoc($dtbrasil['datas']['Interações'],$dtbrasil['datas']['Incidentes Solucionados']);
        if($resArrayIterIncS != null){            
            $inc = $this->criaArray($resArrayIterIncS);            
            foreach($inc as $rInc){                
                $datas['datas']['Incidentes Solucionados'][$rInc] = 0;                
            }          
        }
        ksort($datas['datas']['Incidentes Solucionados']);
       
        
       // var_export($datas);
        $datas['options']['showGroup'] = true;
        $datas['options']['force_show_label'] = true;
        $datas['spline'] = true;
        
      
        return $datas;
        
    }
    
    
    function buscaNomeDia($data_dt) {
        
        switch($data_dt){
            case "Sunday":
                $label = 'Dom';
                break;
            case "Monday":
                $label = 'Seg';
                break;
            case "Tuesday":
                $label = 'Ter';
                break;
            case "Wednesday":
                $label = 'Qua';
                break;
            case "Thursday":
                $label = 'Qui';
                break;
            case "Friday":
                $label = 'Sex';
                break;
            case "Saturday":
                $label = 'Sáb';
                break;
        }  
        
        return $label;
    
    }
    function criaArray($array) {        
        
        foreach($array as $rInc){                
            $data_dt = date( 'l', strtotime($rInc));
            $label = $this->buscaNomeDia($data_dt);
            $data_dt = date( 'd/m', strtotime($rInc));            
            $dt['datas'][$data_dt] = $data_dt;
        }
        
        return $dt['datas'];  
    }
    
    function reportGlineInventarioPecas() {
        global $DB, $LANG;
        
        $datas = array();
        $graph = new PluginMreportingGraph();   
        
        $datas['options']['showTypeEquipamento'] = true;
        $datas['options']['force_show_label'] = true;  
        $datas['datas'] = false;
            
               
        if($_REQUEST['typeEquip']){  
          
            return $this->InvPecas($_REQUEST['typeEquip']);
            
        }elseif($_REQUEST['tipoEquipamento']){
            
            $fabricante = $_REQUEST["manufacturers_id"];            
                    
            switch($_REQUEST['tipoEquipamento']){
                case 'Computer':
                    $impmodelo = $_REQUEST["computermodels_id"];
                    $type_peca = $_REQUEST["computertypes_id"];                     
                    break;
                case 'Monitor':
                    $impmodelo = $_REQUEST["monitormodels_id"];
                    $type_peca = $_REQUEST["monitortypes_id"];
                    break;
                case 'Printer':
                    $impmodelo = $_REQUEST["printermodels_id"];
                    $type_peca = $_REQUEST["printertypes_id"];
                    break;
                
            }
             return $this->InvPecas($_SESSION['TempTipoEquipamento'],$impmodelo,$type_peca,$fabricante,$states_id);
            //return var_export(array($impmodelo,$type_peca));
            
        }
       
      return $datas;
                
        
       
    }
    
    function InvPecas($type, $impmodelo = 0, $type_peca = 0,$fabricante = 0,$states_id = 0) {
        global $DB, $LANG;       
       // die();
       
        $graph = new PluginMreportingGraph(); 
       // var_export($type);
		//die();
        switch($type){
            case 'Printer':
                $showlabelModel = 'showPrinterModels';
                $showlabelType = 'showPrinterType';
                
                $tabelaModel = 'glpi_printermodels';
                $tabelaType = 'glpi_printertypes';
                
                $datas['options']['showPrinterModels'] = true;
                $datas['options']['showPrinterType'] = true;
                break;
            case 'Monitor':
                $showlabelModel = 'showMonitorModels';
                $showlabelType = 'showMonitorType';
                
                $tabelaModel = 'glpi_monitormodels';
                $tabelaType = 'glpi_monitortypes';
                
                $datas['options']['showMonitorModels'] = true;
                $datas['options']['showMonitorType'] = true;
                break;
            case 'Computer':
                $showlabelModel = 'showComputerModels';
                $showlabelType = 'showComputerType';
                
                $tabelaModel = 'glpi_computermodels';
                $tabelaType = 'glpi_computertypes';
                
                $datas['options']['showComputerModels'] = true;
                $datas['options']['showComputerType'] = true;
                break;
        }
        
        //filtro para modelo especifico
        if($impmodelo > 0){
            $where_printermodel = "AND {$tabelaModel}.`id` = " . $impmodelo;
        }else{
           $where_printermodel = ''; 
        }         
        
        //filtro para buscar por fabricante
        if($fabricante > 0){
            $where_fabricante = "AND `supridesk_pecas`.`manufacturers_id` = " . $fabricante;
        }else{
            $where_fabricante = ''; 
        } 
        
        //filtro por tipo de equipamento
        if($type_peca > 0){
            $where_type = "AND {$tabelaType}.`id` = " . $type_peca;
        }else{
            $where_type = ''; 
        } 
        
        
        $query = "SELECT `supridesk_pecas`.`quantidade` AS total, 
                        {$tabelaModel}.`name`  AS modelo, 
                        `glpi_manufacturers`.`name` AS fabricante,
                        `supridesk_pecas`.`name` AS nome_alternativo,
                        CONCAT(`supridesk_pecas`.`name`,' ',{$tabelaModel}.`name`) AS nome_distinct,
                       # group_concat({$tabelaType}.`name` SEPARATOR ' / ') AS tipo,
                        {$tabelaType}.`name`AS tipo,
                        `supridesk_pecas`.`id` AS id
                    FROM `supridesk_pecas` 
                    INNER JOIN `supridesk_pecas_type_model` ON `supridesk_pecas_type_model`.`peca_id` = supridesk_pecas.`id`
                    INNER JOIN {$tabelaModel} ON  {$tabelaModel}.`id` = `supridesk_pecas_type_model`.`model_id`
                    INNER JOIN `glpi_manufacturers` ON  `glpi_manufacturers`.`id` = `supridesk_pecas`.`manufacturers_id`
                    INNER JOIN {$tabelaType} ON  {$tabelaType}.`id` = `supridesk_pecas_type_model`.`type_id`
                    WHERE 1 
                    ".$where_printermodel."
                    ".$where_fabricante."
                    ".$where_type." ";

        $res = $DB->query($query);
       
        session_start();
        $_SESSION['TempTipoEquipamento'] = $type;

        //curve lines
        $datas['spline'] = true;
        $datas['options'][$showlabel] = true;
        $datas['options']['force_show_label'] = true;
        $datas['options']['hide_graph'] = true;
        $datas['options']['showManufacturer'] = true;
        $datas['options'][$showlabel2] = true;
        $datas['datas'] = true;
        //$datas['options']['showData'] = true;

        $graph->showGline($datas, $LANG['plugin_mreporting']['Helpdesk']['reportGlineInventarioPecas']['title'], '');

        echo "<table border='1' width='100%' style='text-align:left; border-collapse:collapse;' cellspacing='0' cellspading='0'>";
        echo "<tr style='background-color: #BFC7D1;' height='30px'>";
        echo "<td>&nbsp;TIPO</td>";
        echo "<td>&nbsp;FABRICANTE</td>";        
        echo "<td>&nbsp;MODELO</td>";
        echo "<td>&nbsp;NOME ALTERNATIVO</td>";
        echo "<td style='text-align:center;'>QUANTIDADE</td>";
        echo "</tr>";
        
        $c = array();
        
        if (mysql_num_rows($res) > 0)
            mysql_data_seek($res, 0);
            while ($data = $DB->fetch_assoc($res)) {
               
                $array['echo'][$data['nome_alternativo']]['total'][] =  $data['total'];
                $array['echo'][$data['nome_alternativo']]['fabricante'][] = $data['fabricante'];
                $array['echo'][$data['nome_alternativo']]['modelo'][] = $data['modelo'];
                $array['echo'][$data['nome_alternativo']]['tipo'][] = $data['tipo'];              
            }
        
                
        if (mysql_num_rows($res) > 0)
            mysql_data_seek($res, 0);
            while($data2 = $DB->fetch_assoc($res)){
                
                if(count($array['echo'][$data2['nome_alternativo']]['total']) > 1){
                    $array['print'][$data2['nome_alternativo']]['total'] =  array_sum($array['echo'][$data2['nome_alternativo']]['total']);
                }else{
                    $array['print'][$data2['nome_alternativo']]['total'] = implode('',$array['echo'][$data2['nome_alternativo']]['total']);
                }
                
                $array['print'][$data2['nome_alternativo']]['modelo'] = implode(' / ',$array['echo'][$data2['nome_alternativo']]['modelo']);
                $array['print'][$data2['nome_alternativo']]['tipo'] = implode(' / ',$array['echo'][$data2['nome_alternativo']]['tipo']);
                $array['print'][$data2['nome_alternativo']]['fabricante'] = implode(' / ',$array['echo'][$data2['nome_alternativo']]['fabricante']);
                $array['print'][$data2['nome_alternativo']]['id'] = $data2['id'];
                $array['print'][$data2['nome_alternativo']]['name'] = $data2['nome_alternativo'];
            }
        
        foreach($array['print'] as $result){
            $c[] = $result['total'];
            echo "<tr style='background-color: #DEE1E5;' height='25px'>";
            echo "<td>&nbsp{$result['tipo']}</td>";            
            echo "<td>&nbsp{$result['fabricante']}</td>";
            echo "<td>&nbsp{$result['modelo']}</td>";
            echo "<td><a href='../../../front/stock.form.php?id={$result['id']}'>&nbsp{$result['name']}</a></td>"; 
            echo "<td width='45px' style='text-align:center;'>{$result['total']}</td>";
            echo "</tr>";
        }

        $count = array_sum($c);

        echo "<tr>";
        echo "<td height='30px' colspan='4' style='text-align:right;'>TOTAL : &nbsp;</td>";
        echo "<td style='text-align:center;'>{$count}</td>";        
        echo "<td>&nbsp;</td>";        
        echo "</tr>";
        echo "</table>";

        return null;
    }
    
    function reportGlineInventarioPecasVenda() {
        global $DB, $LANG;
        
        $datas = array();
        $graph = new PluginMreportingGraph();   
        
        $datas['options']['showTypeEquipamentoVenda'] = true;
        $datas['options']['force_show_label'] = true;  
        $datas['datas'] = false;        
            
        if($_REQUEST['typeEquipVenda']){  
          //die('1');
            return $this->InvPecasVenda($_REQUEST['typeEquipVenda']);
            
        }elseif($_REQUEST['tipoEquipamentoVenda']){
            
            $fabricante = $_REQUEST["manufacturers_id"];
            $states_id = $_REQUEST["states_id"];
            
            switch($_REQUEST['tipoEquipamentoVenda']){
                case 'Computer':
                    $impmodelo = $_REQUEST["computermodels_id"];
                    $type_peca = $_REQUEST["computertypes_id"];
                    break;
                case 'Monitor':
                    $impmodelo = $_REQUEST["monitormodels_id"];
                    $type_peca = $_REQUEST["monitortypes_id"];
                    break;
                case 'Printer':
                    $impmodelo = $_REQUEST["printermodels_id"];
                    $type_peca = $_REQUEST["printertypes_id"];
                    break;
                
            }
             return $this->InvPecasVenda($_SESSION['TempTipoEquipamentoVenda'],$impmodelo,$type_peca,$fabricante,$states_id);
            
        }
       
      return $datas;
       
    }
    
    function InvPecasVenda($type, $impmodelo = 0, $type_peca = 0,$fabricante = 0,$states_id = 0) {
        global $DB, $LANG;  
       
        $graph = new PluginMreportingGraph();   
        
        $this->where_entities = "'" . implode("', '", $_SESSION['glpiactiveentities']) . "'";      
       
       $count = 'count(*) AS total,';
       $entities = "AND `entities_id` IN ($this->where_entities)";
        switch($type){
            case 'Printer':                
                $modelo = '`glpi_printermodels`.`name` AS modelo,';
                $nome = '`glpi_printers`.`contact` AS nome_alternativo,`glpi_printers`.`name` AS nome,';
                $tipo = '`glpi_printertypes`.`name` AS tipo,';
                $chave = "CONCAT(`glpi_printertypes`.`name`,' - ',`glpi_printermodels`.`name`) as chave,";
                $inner_modelo = 'INNER JOIN `glpi_printermodels` ON  `glpi_printermodels`.`id` = `glpi_printers`.`printermodels_id`';
                $inner_tipo = 'INNER JOIN `glpi_printertypes` ON  `glpi_printertypes`.`id` = `glpi_printers`.`printertypes_id`';
                $inner_fabricante = 'INNER JOIN `glpi_manufacturers` ON  `glpi_manufacturers`.`id` = `glpi_printers`.`manufacturers_id`';
                $group_by = 'GROUP BY `glpi_printermodels`.`id`';
                
                
                $showlabelModel = 'showPrinterModels';
                $showlabelType = 'showPrinterType';
                
                $tabelaModel = 'glpi_printermodels';
                $tabelaType = 'glpi_printertypes';
                $tabela = 'glpi_printers';
                $form = 'printer';
                $labelname = 'IMPRESSORA(S)';
                
                $datas['options']['showPrinterModels'] = true;
                $datas['options']['showPrinterType'] = true;
                break;
            case 'Monitor':
                $modelo = '`glpi_monitormodels`.`name` AS modelo,';
                $nome = '`glpi_monitors`.`contact` AS nome_alternativo,`glpi_monitors`.`name` AS nome,';
                $tipo = '`glpi_monitortypes`.`name` AS tipo,';
                $chave = "CONCAT(`glpi_monitortypes`.`name`,' - ',`glpi_monitormodels`.`name`) as chave,";
                $inner_modelo = 'INNER JOIN `glpi_monitormodels` ON  `glpi_monitormodels`.`id` = `glpi_monitors`.`monitormodels_id`';
                $inner_tipo = 'INNER JOIN `glpi_monitortypes` ON  `glpi_monitortypes`.`id` = `glpi_monitors`.`monitortypes_id`';
                $inner_fabricante = 'INNER JOIN `glpi_manufacturers` ON  `glpi_manufacturers`.`id` = `glpi_monitors`.`manufacturers_id`';
                $group_by = 'GROUP BY `glpi_monitormodels`.`id`';                            
                
                $showlabelModel = 'showMonitorModels';
                $showlabelType = 'showMonitorType';
                
                $tabelaModel = 'glpi_monitormodels';
                $tabelaType = 'glpi_monitortypes';
                $tabela = 'glpi_monitors';
                $form = 'monitor';
                $labelname = 'MONITOR(ES)';
                
                $datas['options']['showMonitorModels'] = true;
                $datas['options']['showMonitorType'] = true;
                break;
            case 'Computer':
                $modelo = '`glpi_computermodels`.`name` AS modelo,';
                $nome = '`glpi_computers`.`contact` AS nome_alternativo,`glpi_computers`.`name` AS nome,';
                $tipo = '`glpi_computertypes`.`name` AS tipo,';
                $chave = "CONCAT(`glpi_computertypes`.`name`,' - ',`glpi_computermodels`.`name`) as chave,";
                $inner_modelo = 'INNER JOIN `glpi_computermodels` ON  `glpi_computermodels`.`id` = `glpi_computers`.`computermodels_id`';
                $inner_tipo = 'INNER JOIN `glpi_computertypes` ON  `glpi_computertypes`.`id` = `glpi_computers`.`computertypes_id`';
                $inner_fabricante = 'INNER JOIN `glpi_manufacturers` ON  `glpi_manufacturers`.`id` = `glpi_computers`.`manufacturers_id`';
                $group_by = 'GROUP BY `glpi_computermodels`.`id`';                
                               
                $showlabelModel = 'showComputerModels';
                $showlabelType = 'showComputerType';
                
                $tabelaModel = 'glpi_computermodels';
                $tabelaType = 'glpi_computertypes';
                $tabela = 'glpi_computers';
                $form = 'computer';
                $labelname = 'COMPUTADOR(ES)';
                
                $datas['options']['showComputerModels'] = true;
                $datas['options']['showComputerType'] = true;
                break;
            case 'Software':
                $count = '`glpi_softwarelicenses`.`number` AS total,';
                $nome = '`glpi_softwares`.`name` AS nome_alternativo,';
                $chave = "CONCAT(glpi_softwares.`name`,' - ',glpi_softwares.`id`) as chave,";
                $inner_fabricante = 'INNER JOIN `glpi_manufacturers` ON  `glpi_manufacturers`.`id` = `glpi_softwares`.`manufacturers_id`';
                $inner_software = 'INNER JOIN `glpi_softwarelicenses` ON `glpi_softwarelicenses`.`softwares_id` = `glpi_softwares`.`id` ';
                
                $tabelaModel = 'glpi_computermodels';
                $tabelaType = 'glpi_computertypes';
                $tabela = 'glpi_softwares';   
                $labelname = 'SOFTWARE(S)';
                $entities = "AND `glpi_softwares`.`entities_id` IN ($this->where_entities)";
				$form = 'software';
                                
                break;
        }
        
        //filtro para modelo especifico
        if($impmodelo > 0){
            $where_printermodel = "AND {$tabelaModel}.`id` = " . $impmodelo;
        }else{
           $where_printermodel = ''; 
        }         
        
        //filtro para buscar por fabricante
        if($fabricante > 0){
            $where_fabricante = "AND {$tabela}.`manufacturers_id` = " . $fabricante;
        }else{
            $where_fabricante = ''; 
        } 
        
        //filtro por tipo de equipamento
        if($type_peca > 0){
            $where_type = "AND {$tabelaType}.`id` = " . $type_peca;
        }else{
            $where_type = ''; 
        }  
        
        if($states_id > 0){
            $where_states = "AND {$tabela}.`states_id` = " . $states_id;
            
            $query_n = "SELECT `name` FROM `glpi_states` WHERE `id` = {$states_id}";
            $res_n = $DB->query($query_n);
            $data_n = $DB->fetch_assoc($res_n);
            
            $label = $labelname . ' - ' .$data_n['name'];
            
            
        }else{
            $where_states = ''; 
            $label = $labelname . ' - GERAL';
        }  
        
        $query = "SELECT {$count} 
                        {$modelo} 
                        `glpi_manufacturers`.`name` AS fabricante,
                        {$nome}
                        {$tipo}
                        {$chave}
                        {$tabela}.`id` AS id
                    FROM {$tabela} 
                    {$inner_modelo}
                    {$inner_fabricante}
                    {$inner_tipo}
                    {$inner_software}
                    WHERE 1                     
                    ".$where_printermodel."
                    ".$where_fabricante."
                    ".$where_type."
                    ".$where_states."
                    ".$entities."
                    ".$group_by." ";        
        
        $res = $DB->query($query);
       // var_export($query);
       // var_export($DB->fetch_assoc($res));
        session_start();
        $_SESSION['TempTipoEquipamentoVenda'] = $type;

        //curve lines
        $datas['spline'] = true;
        $datas['options'][$showlabel] = true;
        $datas['options']['force_show_label'] = true;
        $datas['options']['hide_graph'] = true;
        $datas['options']['showManufacturer'] = true;
        $datas['options']['showStates'] = true;
        $datas['options'][$showlabel2] = true;
        $datas['datas'] = true;
        //$datas['options']['showData'] = true;

        $graph->showGline($datas, $LANG['plugin_mreporting']['Helpdesk']['reportGlineInventarioPecasVenda']['title'], '');

        echo "<table border='1' width='100%' style='text-align:left; border-collapse:collapse;' cellspacing='0' cellspading='0'>";
        echo "<tr style='background-color: #BFC7D1;' height='30px'>";
        echo "<td colspan = 5 align = 'center'>&nbsp;{$label}</td>";
        echo "</tr>";
        echo "<tr style='background-color: #BFC7D1;' height='30px'>";
        echo "<td>&nbsp;TIPO</td>";
        echo "<td>&nbsp;FABRICANTE</td>";        
        echo "<td>&nbsp;MODELO</td>";
        echo "<td>&nbsp;NOME ALTERNATIVO</td>";
        echo "<td style='text-align:center;'>QUANTIDADE</td>";
        echo "</tr>";
        
        $c = array();
        
        if (mysql_num_rows($res) > 0)
            mysql_data_seek($res, 0);
            while ($data = $DB->fetch_assoc($res)) { 
                //var_export($data);
                $array['echo'][$data['chave']]['total'][] =  $data['total'];
                $array['echo'][$data['chave']]['name_alternativo'][] = $data['nome_alternativo'];
                $array['echo'][$data['chave']]['name'][] = $data['nome'];
                $array['echo'][$data['chave']]['id'][] = $data['id'];
                $array['echo'][$data['chave']]['fabricante'][]= $data['fabricante'];
                $array['echo'][$data['chave']]['modelo'][] = $data['modelo'];
                $array['echo'][$data['chave']]['tipo'][] = $data['tipo'];              
            }
        
        
        foreach($array['echo'] as $result){            
            $c[] = $result['total'][0];
            
            if($result['name_alternativo'][0] == NULL){
                $result['name_alternativo'][0] = $result['name'][0];
            }
            
            if(!isset($result['tipo'][0])){
                $result['tipo'][0] = 'Software';
            }
            
            if(!isset($result['modelo'][0])){
                $result['modelo'][0] = ' - ';
            }
            
            echo "<tr style='background-color: #DEE1E5;' height='25px'>";
            echo "<td>&nbsp{$result['tipo'][0]}</td>";                  
            echo "<td>&nbsp{$result['fabricante'][0]}</td>";
            echo "<td>&nbsp{$result['modelo'][0]}</td>";
            echo "<td><a href='../../../front/{$form}.form.php?id={$result['id'][0]}'>&nbsp{$result['name_alternativo'][0]}</a></td>"; 
            echo "<td width='45px' style='text-align:center;'>{$result['total'][0]}</td>";
            echo "</tr>";            
        }

        $count = array_sum($c);

        echo "<tr>";
        echo "<td height='30px' colspan='4' style='text-align:right;'>TOTAL : &nbsp;</td>";
        echo "<td style='text-align:center;'>{$count}</td>";        
        echo "<td>&nbsp;</td>";        
        echo "</tr>";
        echo "</table>";

        return null;
    }
    
    /*function reportGlineEquipamentosBackup() {
        global $DB, $LANG;        
        
        $graph = new PluginMreportingGraph();        
        
        $type = $_REQUEST["typeEquipBackup"];
        
        if($type == 'Computer-11'){     
            
            $from = "`glpi_computers`";
            
            $select = "`glpi_computermodels`.`name` AS modelo, 
                        `glpi_manufacturers`.`name` AS fabricante,
                        `glpi_computers`.`contact` AS nome_alternativo,
                        `glpi_computertypes`.`name` AS tipo,
                        `glpi_computers`.`id` AS id";
            
            $INNER = "INNER JOIN `glpi_computermodels` ON  `glpi_computermodels`.`id` = `glpi_computers`.`computermodels_id`
                    INNER JOIN `glpi_manufacturers` ON  `glpi_manufacturers`.`id` = `glpi_computers`.`manufacturers_id`
                    INNER JOIN `glpi_computertypes` ON  `glpi_computertypes`.`id` = `glpi_computers`.`computertypes_id`";
            
            $GROUP = "`glpi_computermodels`.`id`";
            $status = "AND `glpi_computers`.`states_id` = 24";
            $type_where = "AND `glpi_computers`.`computertypes_id` = 11";
        }
        
        if($type == 'Computer-3'){ 
            
            $from = "`glpi_computers`";
            
            $select = "`glpi_computermodels`.`name` AS modelo, 
                        `glpi_manufacturers`.`name` AS fabricante,
                        `glpi_computers`.`contact` AS nome_alternativo,
                        `glpi_computertypes`.`name` AS tipo,
                        `glpi_computers`.`id` AS id";
            
            $INNER = "INNER JOIN `glpi_computermodels` ON  `glpi_computermodels`.`id` = `glpi_computers`.`computermodels_id`
                    INNER JOIN `glpi_manufacturers` ON  `glpi_manufacturers`.`id` = `glpi_computers`.`manufacturers_id`
                    INNER JOIN `glpi_computertypes` ON  `glpi_computertypes`.`id` = `glpi_computers`.`computertypes_id`";
            
            $GROUP = "`glpi_computermodels`.`id`";
            $status = "AND `glpi_computers`.`states_id` = 24";
            $type_where = "AND `glpi_computers`.`computertypes_id` = 3";
        }
        
        if($type == 'Computer-2'){   
            
            $from = "`glpi_computers`";
            
            $select = "`glpi_computermodels`.`name` AS modelo, 
                        `glpi_manufacturers`.`name` AS fabricante,
                        `glpi_computers`.`contact` AS nome_alternativo,
                        `glpi_computertypes`.`name` AS tipo,
                        `glpi_computers`.`id` AS id";
            
            $INNER = "INNER JOIN `glpi_computermodels` ON  `glpi_computermodels`.`id` = `glpi_computers`.`computermodels_id`
                    INNER JOIN `glpi_manufacturers` ON  `glpi_manufacturers`.`id` = `glpi_computers`.`manufacturers_id`
                    INNER JOIN `glpi_computertypes` ON  `glpi_computertypes`.`id` = `glpi_computers`.`computertypes_id`";
            
            $GROUP = "`glpi_computermodels`.`id`";
            $status = "AND `glpi_computers`.`states_id` = 24";
            $type_where = "AND `glpi_computers`.`computertypes_id` = 2";
        }
        
        if($type == 'Printer'){
            
            $from = "`glpi_printers`";
            
           // $where_type = "AND `glpi_printers`.`computertypes_id` = 2";
            $select = "`glpi_printermodels`.`name` AS modelo, 
                        `glpi_manufacturers`.`name` AS fabricante,
                        `glpi_printers`.`contact` AS nome_alternativo,
                        `glpi_printertypes`.`name` AS tipo,
                        `glpi_printers`.`id` AS id";
            
            $INNER = "INNER JOIN `glpi_printermodels` ON  `glpi_printermodels`.`id` = `glpi_printers`.`printermodels_id`
                    INNER JOIN `glpi_manufacturers` ON  `glpi_manufacturers`.`id` = `glpi_printers`.`manufacturers_id`
                    INNER JOIN `glpi_printertypes` ON  `glpi_printertypes`.`id` = `glpi_printers`.`printertypes_id`";
            
            $GROUP = "`glpi_printermodels`.`id`";
            $status = "AND `glpi_printers`.`states_id` = 24";
            $where_type = "";
        }
        
        if($type == 'Monitor'){
            
            $from = "`glpi_monitors`";
            
            //$where_type = "AND `glpi_computers`.`computertypes_id` = 2";
            $select = "`glpi_monitormodels`.`name` AS modelo, 
                        `glpi_manufacturers`.`name` AS fabricante,
                        `glpi_monitors`.`contact` AS nome_alternativo,
                        `glpi_monitortypes`.`name` AS tipo,
                        `glpi_monitors`.`id` AS id";
            
            $INNER = "INNER JOIN `glpi_monitormodels` ON  `glpi_monitormodels`.`id` = `glpi_monitors`.`monitormodels_id`
                    INNER JOIN `glpi_manufacturers` ON  `glpi_manufacturers`.`id` = `glpi_monitors`.`manufacturers_id`
                    INNER JOIN `glpi_monitortypes` ON  `glpi_monitortypes`.`id` = `glpi_monitors`.`monitortypes_id`";
            
            $GROUP = "`glpi_monitormodels`.`id`";
            $status = "AND `glpi_monitors`.`states_id` = 24";
            $type_where = "";
        }

        
        $query = "SELECT count(*) AS total, 
                        ".$select."
                    FROM ".$from." 
                    ".$INNER."
                    WHERE 1 
                    ".$status."
                    ".$type_where."                    
                    GROUP BY ".$GROUP."";

        $res = $DB->query($query);

        //curve lines
        $datas['spline'] = true;        
        $datas['options']['force_show_label'] = true;
        $datas['options']['hide_graph'] = true;        
        $datas['options']['showTypeEquipamentoBackup'] = true;
        $datas['datas'] = true;
        //$datas['options']['showData'] = true;

        $graph->showGline($datas, $LANG['plugin_mreporting']['Helpdesk']['reportGlineEquipamentosBackup']['title'], '');

        echo "<table border='1' width='100%' style='text-align:left; border-collapse:collapse;' cellspacing='0' cellspading='0'>";
        echo "<tr style='background-color: #BFC7D1;' height='30px'>";
        echo "<td>&nbsp;TIPO</td>";
        echo "<td>&nbsp;FABRICANTE</td>";        
        echo "<td>&nbsp;MODELO</td>";
        echo "<td>&nbsp;NOME ALTERNATIVO</td>";
        echo "<td style='text-align:center;'>QUANTIDADE</td>";
        echo "</tr>";
        
        $c = array();
        
        while($result = $DB->fetch_assoc($res)){
            $c[] = $result['total'];
            echo "<tr style='background-color: #DEE1E5;' height='25px'>";
            echo "<td>&nbsp{$result['tipo']}</td>";            
            echo "<td>&nbsp{$result['fabricante']}</td>";
            echo "<td>&nbsp{$result['modelo']}</td>";
            echo "<td><a href='../../../front/computer.form.php?id={$result['id']}'>&nbsp{$result['nome_alternativo']}</a></td>"; 
            echo "<td width='45px' style='text-align:center;'>{$result['total']}</td>";
            echo "</tr>";
        }

        $count = array_sum($c);

        echo "<tr>";
        echo "<td height='30px' colspan='4' style='text-align:right;'>TOTAL : &nbsp;</td>";
        echo "<td style='text-align:center;'>{$count}</td>";        
        echo "<td>&nbsp;</td>";
        echo "</tr>";
        echo "</table>";

        return null;
    }*/
	
	function reportGlineFaturamento() {
        global $DB, $LANG;
        
       // $mreporting = new PluginMreportingProfile();
       // $permissao = $mreporting->confereIndicadores($_REQUEST['f_name'], $_SESSION["glpiactiveprofile"]["id"]);
                
       /* if($permissao == false){
            print "<div style='text-align: center;'>Sem permissões de visualização deste indicador.</div>";
            die();
        }*/
        
	
	$graph = new PluginMreportingGraph();

        $datas = array();
        $delay = 365;
        //curve lines        
        $datas['spline'] = true;
        $datas['options']['showContracts'] = true;
        $datas['options']['force_show_label'] = true;
        $datas['options']['hide_graph'] = true;
        $datas['datas'] = true;

        $graph->showGline($datas, $LANG['plugin_mreporting']['Helpdesk']['reportGlineFaturamento']['title'], '');

        echo "<table border='1' width='100%' style='text-align:left; border-collapse:collapse;' cellspacing='0' cellspading='0'>";
        echo "<tr style='background-color: #BFC7D1;' height='50px'>";
        echo "<td colspan = 10 style='text-align:center;' >&nbsp;<b>RELATÓRIO DE FATURAMENTO</b></td>";
        echo "</tr>";
        echo "<tr style='background-color: #BFC7D1;' height='30px'>";        
        echo "<td style='text-align:center;'>&nbsp;</td>";
        echo "<td colspan = 2 style='text-align:center;color:#006400;'><b>FATURAMENTO</b></td>";
        echo "<td colspan = 6 style='text-align:center;color:#FF0000;'><b>CUSTOS</b></td>";
        echo "<td style='text-align:center;'>&nbsp;</td>";
        echo "</tr>";
        
        echo "<tr style='background-color: #BFC7D1;' height='30px'>";        
        echo "<td style='text-align:center;'><b>CONTRATOS</b></td>";
        echo "<td style='text-align:center;'><b>ALUGUEL</b></td>";
        echo "<td style='text-align:center;'><b>TARIFAÇÃO</b></td>";
        echo "<td style='text-align:center;'><b>EQUIPAMENTOS</b></td>";
        echo "<td style='text-align:center;'><b>PEÇAS</b></td>";
        echo "<td style='text-align:center;'><b>SUPRIMENTOS</b></td>";
        echo "<td style='text-align:center;'><b>CHAMADOS</b></td>";
        echo "<td style='text-align:center;'><b>OUTROS</b></td>";
        echo "<td style='text-align:center;'><b>IMPOSTOS</b></td>";
        echo "<td style='text-align:center;'><b>RESULTADO</b></td>";
        echo "</tr>";
        //echo "<td style='text-align:center;'>Valor</td>";
        echo "</tr>";
        
        $contracts = $_POST['contracts'];
        
        if($contracts != NULL){
            $contracts = "'" . implode("', '", $_POST['contracts']) . "'";
            $where_contracts = " AND `glpi_contracts`.`id` IN (".$contracts.")";
        }else{
            $where_entity = "AND `entities_id` IN (" . $this->where_entities . ")";
        }

        $count = 0;
        
        $query = "SELECT * FROM `glpi_contracts` WHERE `is_active` = 1 
                {$where_entity} {$where_contracts} ORDER BY `name`";
       
        $res = $DB->query($query);
        
        $ft = new Supridesk_Faturamento();
		
	$qtd = $ft->getQtdMeses($_REQUEST["date1"],$_REQUEST["date2"]); 
        if($qtd == 0){
            $qtd = null;
        }else{
            $qtd = $qtd / 30;
            $qtd = (int)$qtd;
        }
        
        while ($data = $DB->fetch_assoc($res)){  

            $entidades_ = $ft->getEntityTree($data['entities_id']);
            $result = array_values($entidades_);
            $entidades_filhas = implode(',',$result);
            
            if($entidades_filhas == NULL){
                $entidades_filhas = $entidades_;
            }
           // var_export($entidades_filhas);
            $count++;
            $color = "#DEE1E5";
            if ($count % 2)
                $color = "#F2F2F2";
            
            $aluguel = $ft->get_valor_aluguel($data['id'],$qtd);         
            $bilhetagem = $ft->get_valor_bilhetagem($data['id'],$_REQUEST["date1"],$_REQUEST["date2"]);
                   
            $pecas = $ft->get_valor_pecas($data['id'],$_REQUEST["date1"],$_REQUEST["date2"]); 
            if($pecas == '0'){
                $pecas = '0.00';
            }elseif($pecas == NULL){
                $pecas = '0.00';
            }
           
            $suprimentos = $ft->get_valor_suprimentos($entidades_filhas,$_REQUEST["date1"],$_REQUEST["date2"]);
            
            //pega periodo em meses
            $qry_meses = "SELECT DATEDIFF('{$_REQUEST["date2"]}','{$_REQUEST["date1"]}') AS qtd_meses";
            $res_meses = $DB->query($qry_meses);
            $data_meses = $DB->fetch_assoc($res_meses);
            $arrayqtd = $data_meses['qtd_meses'];
            $arrayqtd = (int)($arrayqtd/30);
            
            for ($i = 0; $i <= $arrayqtd; $i++) {
                $select_date = "SELECT DATE_ADD('{$_REQUEST["date1"]}', INTERVAL $i MONTH) AS DATA_MES";                    
                $res_date = $DB->query($select_date);
                $data_date = $DB->fetch_assoc($res_date);
                
                $dateinicio = $data_date['DATA_MES'];
                
                if($i>=1){ 
                    $date1 = explode("-", $dateinicio);    
                    $date1 = "$date1[0]-$date1[1]-01";                    
                }else{
                    $date1 = explode("-", $dateinicio);    
                    $date1 = "$date1[0]-$date1[1]-$date1[2]";
                }
                
                if($i == $arrayqtd){
                    $data_lastday['last_day'] = $_REQUEST['date2'];
                }else{
                    $qry_lastday = "SELECT LAST_DAY('{$date1}') AS last_day";
                    $res_lastday = $DB->query($qry_lastday);
                    $data_lastday = $DB->fetch_assoc($res_lastday);
                }
                
                $chamados['teste'][] = $ft->get_valor_chamado($date1,$data_lastday['last_day'],$entidades_filhas,$data['id']);
                $outros['teste'][] = $ft->getValorOutros($date1,$data_lastday['last_day'],$data['id']);
            }
            //var_export($outros['teste']);
            $chamados = array_sum($chamados['teste']);    
            $outros = array_sum($outros['teste']);    
            $leasing = $ft->get_valor_leasing($data['id']);            
            $porcentagem = 9.25 / 100;
            $faturamento_valor = $bilhetagem + $aluguel;
            $impostos = $faturamento_valor * $porcentagem;            
            $custos_valor = $leasing + $impostos + $suprimentos + $chamados+ $outros + $pecas;
           
            $resultado = $faturamento_valor - $custos_valor;
            
            if($resultado >= 0 ){                
                $color2 = "#006400";
            }else{
                $color2 = "#FF0000";
            }
            
            $total_aluguel[] = $aluguel;
            $total_bilhetagem[] = $bilhetagem;
            $total_leasing[] = $leasing;
            $total_pecas[] = $pecas;
            $total_suprimentos[] = $suprimentos;
            $total_chamados[] = $chamados;
            $total_outros[] = $outros;
            $total_impostos[] = $impostos;
            $total_resultado[] = $resultado;
            
            echo "<tr style='background-color: {$color};' height='25px'>";
            echo "<td><a href='../../../front/contract.form.php?id={$data['id']}'>{$data['name']}</a></td>";
            echo "<td style='text-align:center;'>".number_format($aluguel, 2, ',', '.')."</td>";
            echo "<td style='text-align:center;'>".number_format($bilhetagem, 2, ',', '.')."</td>";
            echo "<td style='text-align:center;'>".number_format($leasing, 2, ',', '.')."</td>";
            echo "<td style='text-align:center;'>".number_format($pecas, 2, ',', '.')."</td>";
            echo "<td style='text-align:center;'>".number_format($suprimentos, 2, ',', '.')."</td>";
            echo "<td style='text-align:center;'>".number_format($chamados, 2, ',', '.')."</td>";
            echo "<td style='text-align:center;'>".number_format($outros, 2, ',', '.')."</td>";
            echo "<td style='text-align:center;'>".number_format($impostos, 2, ',', '.')."</td>";
            echo "<td style='text-align:center;color:{$color2};'><b>".number_format($resultado, 2, ',', '.')."</b></td>";         
            echo "</tr>";            
        }
        
        
        $total_aluguel = array_sum($total_aluguel);
        $total_bilhetagem = array_sum($total_bilhetagem);
        $total_leasing = array_sum($total_leasing);
        
        $total_pecas = array_sum($total_pecas);
        if(is_int($total_pecas)){
            $total_pecas = $total_pecas.',00';
        }
        
        $total_suprimentos = array_sum($total_suprimentos);
        $total_chamados = array_sum($total_chamados);
        $total_outros = array_sum($total_outros);
        $total_impostos = array_sum($total_impostos);
        
        $total_resultado = array_sum($total_resultado);
        $total_faturamento_final = $total_aluguel + $total_bilhetagem;
        $total_custo_final = $total_leasing + $total_pecas + $total_suprimentos + $total_chamados + $total_outros + $total_impostos;
                
        echo "<tr>";
        echo "<td height='30px' style='text-align:right;'><b>Totais (R$)</b></td>";
        echo "<td height='30px' style='text-align:center;'><b>".number_format($total_aluguel, 2, ',', '.')."</b></td>";
        echo "<td height='30px' style='text-align:center;'><b>".number_format($total_bilhetagem, 2, ',', '.')."</b></td>";
        echo "<td height='30px' style='text-align:center;'><b>".number_format($total_leasing, 2, ',', '.')."</b></td>";
        echo "<td height='30px' style='text-align:center;'><b>".number_format($total_pecas, 2, ',', '.')."</b></td>";
        echo "<td height='30px' style='text-align:center;'><b>".number_format($total_suprimentos, 2, ',', '.')."</b></td>";
        echo "<td height='30px' style='text-align:center;'><b>".number_format($total_chamados, 2, ',', '.')."</b></td>";
        echo "<td height='30px' style='text-align:center;'><b>".number_format($total_outros, 2, ',', '.')."</b></td>";
        echo "<td height='30px' style='text-align:center;'><b>".number_format($total_impostos, 2, ',', '.')."</b></td>";
        echo "<td height='30px' style='text-align:center;'><b>".number_format($total_resultado, 2, ',', '.')."</b></td>";
        echo "<td>&nbsp;</td>";
        echo "</tr>";
        
        $resultado_final_final = $total_faturamento_final - $total_custo_final;
        if($resultado_final_final >= 0){
            $color3 = "#006400";
        }else{
            $color3 = "#FF0000";
        }
        echo "<tr>";
        echo "<td height='30px' style='text-align:right;'><b>Faturamento Total (R$) &nbsp;</b></td>";
        echo "<td height='30px' style='text-align:center;color:#006400;' colspan='2'><b>".number_format($total_faturamento_final, 2, ',', '.')."</b></td>";       
        echo "<td height='30px' style='text-align:right;' colspan='2'><b>Custo Total (R$) &nbsp;</b></td>"; 
        echo "<td height='30px' style='text-align:center;color:#FF0000;' colspan='2'><b>".number_format($total_custo_final, 2, ',', '.')."</b></td>";
        echo "<td height='30px' style='text-align:right;'><b>Resultado Total (R$) &nbsp;</b></td>";
        echo "<td height='30px' style='text-align:center;color:{$color3};' colspan='2'><b>".number_format($resultado_final_final, 2, ',', '.')."</b></td>";        
        echo "<td>&nbsp;</td>";
        echo "</tr>";
        
        echo "</table>";

        return null;
    }
    


}

?>