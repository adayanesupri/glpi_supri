<?php

/*
 * @version $Id: stat.class.php 18967 2012-07-20 13:50:28Z moyo $
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

if (!defined('GLPI_ROOT')) {
    die("Sorry. You can't access directly to this file");
}

/**
 *  Computer class
 * */
class Stat {

    /// TODO clean type names : technicien -> tech enterprise -> supplier
    static function getItems($itemtype, $date1, $date2, $type, $parent = 0) {
        global $CFG_GLPI, $DB;

        $item = new $itemtype();

        $val = array();

        $cond = '';
        switch ($type) {
            case "technicien" :
                $val = $item->getUsedTechBetween($date1, $date2);
                break;

            case "technicien_followup" :
                $val = $item->getUsedTechTaskBetween($date1, $date2);
                break;

            case "enterprise" :
                $val = $item->getUsedSupplierBetween($date1, $date2);
                break;

            case "user" :
                $val = $item->getUsedAuthorBetween($date1, $date2);
                break;

            case "users_id_recipient" :
                $val = $item->getUsedRecipientBetween($date1, $date2);
                break;

            case 'group_tree' :
            case 'groups_tree_assign' :
                // Get all groups
                $query = "SELECT `id`, `name`
                      FROM `glpi_groups`" .
                        getEntitiesRestrictRequest(" WHERE", "glpi_groups", '', '', true) . "
                            AND (`id`=$parent OR `groups_id`='$parent')
                            AND " . ($type == 'group_tree' ? '`is_requester`' : '`is_assign`') . "
                      ORDER BY `completename`";

                $result = $DB->query($query);
                $val = array();
                if ($DB->numrows($result) >= 1) {
                    while ($line = $DB->fetch_assoc($result)) {
                        $tmp['id'] = $line["id"];
                        $tmp['link'] = $line["name"];
                        $val[] = $tmp;
                    }
                }
                break;

            case "itilcategories_tree" :
                $cond = "AND (`id`='$parent' OR `itilcategories_id`='$parent')";
            // nobreak
            case "itilcategories_id" :

                //*******SUPRISERVICE ********//
                if (!$cond) {
                    $where = "AND NOT `glpi_tickets`.`is_deleted` AND `glpi_tickets`.`status` IN ('closed') AND `glpi_tickets`.`closedate` IS NOT NULL AND ( `glpi_tickets`.`closedate` >= '{$date1}' AND `glpi_tickets`.`closedate` <= ADDDATE('{$date2}' , INTERVAL 1 DAY) )";
                } else {
                    $where = "";
                }
                //*******SUPRISERVICE ********//
                if($_SESSION['glpiactive_entity'] != 0){
                    $where_entities = "'" . implode("', '", $_SESSION['glpiactiveentities']) . "'";
                    $clause = "WHERE `glpi_tickets`.`entities_id` IN ({$where_entities})";
                }else{
                    $clause = "WHERE (1)";
                }
                
                //getEntitiesRestrictRequest(" WHERE", "glpi_itilcategories", '', '', true) substituido por $clause
                // Get all ticket categories for tree merge management
                $query = "SELECT DISTINCT `glpi_itilcategories`.`id`,
                             `glpi_itilcategories`.`" . ($cond ? 'name' : 'completename') . "` AS category
                      FROM `glpi_itilcategories`
                      LEFT JOIN `glpi_tickets` ON (`glpi_tickets`.`itilcategories_id` = `glpi_itilcategories`.`id`)
                            $clause
                            $cond
                            $where
                      ORDER BY `completename`";
                
                $result = $DB->query($query);
                $val = array();               
                
                if ($DB->numrows($result) >= 1) {
                    while ($line = $DB->fetch_assoc($result)) {
                        $tmp['id'] = $line["id"];
                        $tmp['link'] = $line["category"];
                        $val[] = $tmp;
                    }
                }
                break;

            case "type" :
                $types = $item->getTypes();
                $val = array();
                foreach ($types as $id => $v) {
                    $tmp['id'] = $id;
                    $tmp['link'] = $v;
                    $val[] = $tmp;
                }
                break;

            case "group" :
                $val = $item->getUsedGroupBetween($date1, $date2);
                break;

            case "groups_id_assign" :
                $val = $item->getUsedAssignGroupBetween($date1, $date2);
                break;

            case "priority" :
                $val = $item->getUsedPriorityBetween($date1, $date2);
                break;

            case "urgency" :
                $val = $item->getUsedUrgencyBetween($date1, $date2);
                break;

            case "impact" :
                $val = $item->getUsedImpactBetween($date1, $date2);
                break;

            case "requesttypes_id" :
                $val = $item->getUsedRequestTypeBetween($date1, $date2);
                break;

            case "solutiontypes_id" :
                $val = $item->getUsedSolutionTypeBetween($date1, $date2);
                break;

            case "usertitles_id" :
                $val = $item->getUsedUserTitleOrTypeBetween($date1, $date2, true);
                break;

            case "usercategories_id" :
                $val = $item->getUsedUserTitleOrTypeBetween($date1, $date2, false);
                break;

            // DEVICE CASE
            default :
                $item = new $type();
                if ($item instanceof CommonDevice) {
                    $device_table = $item->getTable();

                    //select devices IDs (table row)
                    $query = "SELECT `id`, `designation`
                         FROM `" . $device_table . "`
                         ORDER BY `designation`";
                    $result = $DB->query($query);

                    if ($DB->numrows($result) >= 1) {
                        $i = 0;
                        while ($line = $DB->fetch_assoc($result)) {
                            $val[$i]['id'] = $line['id'];
                            $val[$i]['link'] = $line['designation'];
                            $i++;
                        }
                    }
                } else {
                    // Dropdown case for computers
                    $field = "name";
                    $table = getTableFOrItemType($type);
                    $item = new $type();
                    if ($item instanceof CommonTreeDropdown) {
                        $field = "completename";
                    }
                    $where = '';
                    $order = " ORDER BY `$field`";
                    if ($item->isEntityAssign()) {
                        $where = getEntitiesRestrictRequest(" WHERE", $table);
                        $order = " ORDER BY `entities_id`, `$field`";
                    }

                    $query = "SELECT *
                         FROM `$table`
                         $where
                         $order";

                    $val = array();
                    $result = $DB->query($query);
                    if ($DB->numrows($result) > 0) {
                        while ($line = $DB->fetch_assoc($result)) {
                            $tmp['id'] = $line["id"];
                            $tmp['link'] = $line[$field];
                            $val[] = $tmp;
                        }
                    }
                }
        }
        return $val;
    }

    static function getDatas($itemtype, $type, $date1, $date2, $start, $value, $value2 = "") {

        $export_data = array();

        if (is_array($value)) {
            $end_display = $start + $_SESSION['glpilist_limit'];
            $numrows = count($value);

            for ($i = $start; $i < $numrows && $i < ($end_display); $i++) {
                //le nombre d'intervention - the number of intervention
                $opened = self::constructEntryValues($itemtype, "inter_total", $date1, $date2, $type, $value[$i]["id"], $value2);
                $nb_opened = array_sum($opened);
                $export_data['opened'][$value[$i]['link']] = $nb_opened;

                //le nombre d'intervention resolues - the number of resolved intervention
                $solved = self::constructEntryValues($itemtype, "inter_solved", $date1, $date2, $type, $value[$i]["id"], $value2);
                $nb_solved = array_sum($solved);
                $export_data['solved'][$value[$i]['link']] = $nb_solved;

                //le nombre d'intervention resolues - the number of resolved intervention
                $late = self::constructEntryValues($itemtype, "inter_solved_late", $date1, $date2, $type, $value[$i]["id"], $value2);
                $nb_late = array_sum($late);
                $export_data['late'][$value[$i]['link']] = $nb_late;

                //le nombre d'intervention closes - the number of closed intervention
                $closed = self::constructEntryValues($itemtype, "inter_closed", $date1, $date2, $type, $value[$i]["id"], $value2);
                $nb_closed = array_sum($closed);
                $export_data['closed'][$value[$i]['link']] = $nb_closed;

                if ($itemtype == 'Ticket') {
                    //open satisfaction
                    $opensatisfaction = self::constructEntryValues($itemtype, "inter_opensatisfaction", $date1, $date2, $type, $value[$i]["id"], $value2);
                    $nb_opensatisfaction = array_sum($opensatisfaction);
                    $export_data['opensatisfaction'][$value[$i]['link']] = $nb_opensatisfaction;
                }

                //answer satisfaction
//             $answersatisfaction    = self::constructEntryValues("inter_answersatisfaction", $date1, $date2, $type,
//                                                     $value[$i]["id"], $value2);
//             $nb_answersatisfaction = array_sum($answersatisfaction);
//             $export_data['opensatisfaction'][$value[$i]['link']] = $nb_answersatisfaction;
            }
        }
        return $export_data;
    }

    static function show($itemtype, $type, $date1, $date2, $start, $value, $value2 = "") {
        global $LANG, $CFG_GLPI;

        // Set display type for export if define
        $output_type = HTML_OUTPUT;
        if (isset($_GET["display_type"])) {
            $output_type = $_GET["display_type"];
        }

        if ($output_type == HTML_OUTPUT) { // HTML display
            echo "<div class ='center'>";
        }
        
        if (is_array($value)) {
            $end_display = $start + $_SESSION['glpilist_limit'];
            $numrows = count($value);

            if (isset($_GET['export_all'])) {
                $start = 0;
                $end_display = $numrows;
            }

            $nbcols = 8;
            if ($output_type != HTML_OUTPUT) { // not HTML display
                $nbcols--;
            }

            echo Search::showHeader($output_type, $end_display - $start + 1, $nbcols);
            $subname = '';
            switch ($type) {
                case 'group_tree' :
                case 'groups_tree_assign' :
                    $subname = Dropdown::getDropdownName('glpi_groups', $value2);
                    break;

                case 'itilcategories_tree' :
                    $subname = Dropdown::getDropdownName('glpi_itilcategories', $value2);
                    break;
            }

            if ($output_type == HTML_OUTPUT) { // HTML display
                echo Search::showNewLine($output_type);
                $header_num = 1;

                if ($output_type == HTML_OUTPUT && strstr($type, '_tree') && $value2) {
                    // HTML display
                    $link = $_SERVER['PHP_SELF'] .
                            "?date1=$date1&amp;date2=$date2&amp;itemtype=$itemtype&amp;type=$type" .
                            "&amp;value2=0";
                    $link = "<a href='$link'>" . $LANG['buttons'][13] . "</a>";
                    echo Search::showHeaderItem($output_type, $link, $header_num);
                } else {
                    echo Search::showHeaderItem($output_type, "&nbsp;", $header_num);
                }
                echo Search::showHeaderItem($output_type, '', $header_num);
                

                echo Search::showHeaderItem($output_type, $LANG['tracking'][29], $header_num, '', 0, '', "colspan='4'");
                if ($itemtype == 'Ticket') {
                    echo Search::showHeaderItem($output_type, $LANG['satisfaction'][0], $header_num, '', 0, '', "colspan='3'");
                }
                echo Search::showHeaderItem($output_type, $LANG['stats'][8], $header_num, '', 0, '', $itemtype == 'Ticket' ? "colspan='3'" : "colspan='2'");
                echo Search::showHeaderItem($output_type, $LANG['stats'][26], $header_num, '', 0, '', "colspan='2'");
            }

            echo Search::showNewLine($output_type);
            $header_num = 1;
            $header_to_add = '';
            echo Search::showHeaderItem($output_type, $subname, $header_num);

            if ($output_type == HTML_OUTPUT) { // HTML display
                echo Search::showHeaderItem($output_type, "", $header_num);
            }
            if ($output_type != HTML_OUTPUT) {
                $header_to_add = $LANG['stats'][13] . ' - ';
            }
            echo Search::showHeaderItem($output_type, $header_to_add . $LANG['job'][14], $header_num);
            echo Search::showHeaderItem($output_type, $header_to_add . $LANG['job'][15], $header_num);
            echo Search::showHeaderItem($output_type, $header_to_add . $LANG['job'][17], $header_num);
            echo Search::showHeaderItem($output_type, $header_to_add . $LANG['job'][16], $header_num);

            if ($itemtype == 'Ticket') {

                if ($output_type != HTML_OUTPUT) {
                    $header_to_add = $LANG['satisfaction'][0] . ' - ';
                }
                echo Search::showHeaderItem($output_type, $header_to_add . $LANG['satisfaction'][13], $header_num);
                echo Search::showHeaderItem($output_type, $header_to_add . $LANG['satisfaction'][14], $header_num);
                echo Search::showHeaderItem($output_type, $header_to_add . $LANG['common'][107], $header_num);
            }

            if ($output_type != HTML_OUTPUT) {
                $header_to_add = $LANG['stats'][8] . ' - ';
            }
            if ($itemtype == 'Ticket') {
                echo Search::showHeaderItem($output_type, $header_to_add . $LANG['stats'][12], $header_num);
            }
            echo Search::showHeaderItem($output_type, $header_to_add . $LANG['stats'][9], $header_num);
            echo Search::showHeaderItem($output_type, $header_to_add . $LANG['stats'][10], $header_num);

            if ($output_type != HTML_OUTPUT) {
                $header_to_add = $LANG['stats'][26] . ' - ';
            }
            echo Search::showHeaderItem($output_type, $header_to_add . $LANG['common'][107], $header_num);
            echo Search::showHeaderItem($output_type, $header_to_add . $LANG['common'][33], $header_num);
            // End Line for column headers
            echo Search::showEndLine($output_type);
            $row_num = 1;

            for ($i = $start; $i < $numrows && $i < ($end_display); $i++) {
                $row_num++;
                $item_num = 1;
                echo Search::showNewLine($output_type, $i % 2);
                if ($output_type == HTML_OUTPUT && strstr($type, '_tree') && $value[$i]['id'] != $value2) {

                    // HTML display
                    $link = $_SERVER['PHP_SELF'] .
                            "?date1=$date1&amp;date2=$date2&amp;itemtype=$itemtype&amp;type=$type" .
                            "&amp;value2=" . $value[$i]['id'];
                    $link = "<a href='$link'>" . $value[$i]['link'] . "</a>";

                    echo Search::showItem($output_type, $link, $item_num, $row_num);
                } else {
                    echo Search::showItem($output_type, $value[$i]['link'], $item_num, $row_num);
                }

                if ($output_type == HTML_OUTPUT) { // HTML display
                    $link = "";
                    if ($value[$i]['id'] > 0) {
                        $link = "<a href='stat.graph.php?id=" . $value[$i]['id'] .
                                "&amp;date1=$date1&amp;date2=$date2&amp;itemtype=$itemtype&amp;type=$type" .
                                (!empty($value2) ? "&amp;champ=$value2" : "") . "'>" .
                                "<img src='" . $CFG_GLPI["root_doc"] . "/pics/stats_item.png' alt='' title=''>" .
                                "</a>";
                    }
                    echo Search::showItem($output_type, $link, $item_num, $row_num);
                }

                //le nombre d'intervention - the number of intervention
                $opened = self::constructEntryValues($itemtype, "inter_total", $date1, $date2, $type, $value[$i]["id"], $value2);

                $nb_opened = array_sum($opened);
                $arrayopen[] = $nb_opened;
                echo Search::showItem($output_type, $nb_opened, $item_num, $row_num);
                //le nombre d'intervention resolues - the number of resolved intervention
                $solved = self::constructEntryValues($itemtype, "inter_solved", $date1, $date2, $type, $value[$i]["id"], $value2);

                $nb_solved = array_sum($solved);                
                $arraysolved[] = $nb_solved;   
                
                if ($nb_opened > 0 && $nb_solved > 0) {
                    $nb_solved .= ' (' . round($nb_solved * 100 / $nb_opened) . '%)';
                }

                echo Search::showItem($output_type, $nb_solved, $item_num, $row_num);

                //le nombre d'intervention resolues - the number of resolved intervention
                $solved_late = self::constructEntryValues($itemtype, "inter_solved_late", $date1, $date2, $type, $value[$i]["id"], $value2);
                $nb_solved_late = array_sum($solved_late);
                $arraysolved_late[] = $nb_solved_late;
                
                if ($nb_solved > 0 && $nb_solved_late > 0) {
                    $nb_solved_late .= ' (' . round($nb_solved_late * 100 / $nb_solved) . '%)';
                }
                echo Search::showItem($output_type, $nb_solved_late, $item_num, $row_num);

                //le nombre d'intervention closes - the number of closed intervention
                $closed = self::constructEntryValues($itemtype, "inter_closed", $date1, $date2, $type, $value[$i]["id"], $value2);
                $nb_closed = array_sum($closed);
                $arrayclosed[] = $nb_closed;

                if ($nb_opened > 0 && $nb_closed > 0) {
                    $nb_closed .= ' (' . round($nb_closed * 100 / $nb_opened) . '%)';
                }

                echo Search::showItem($output_type, $nb_closed, $item_num, $row_num);


                if ($itemtype == 'Ticket') {

                    //Satisfaction open
                    $opensatisfaction = self::constructEntryValues($itemtype, "inter_opensatisfaction", $date1, $date2, $type, $value[$i]["id"], $value2);
                    $nb_opensatisfaction = array_sum($opensatisfaction);
                    $arrayopensatisfaction[] = $nb_opensatisfaction;
                    
                    if ($nb_opensatisfaction > 0) {
                        $nb_opensatisfaction .= ' (' . round($nb_opensatisfaction * 100 / $nb_closed) . '%)';
                    }

                    echo Search::showItem($output_type, $nb_opensatisfaction, $item_num, $row_num);

                    //Satisfaction answer
                    $answersatisfaction = self::constructEntryValues($itemtype, "inter_answersatisfaction", $date1, $date2, $type, $value[$i]["id"], $value2);
                    $nb_answersatisfaction = array_sum($answersatisfaction);
                    $arrayanswersatisfaction[] = $nb_answersatisfaction;
                    
                    if ($nb_answersatisfaction > 0) {
                        $nb_answersatisfaction .= ' (' . round($nb_answersatisfaction * 100 / $nb_opensatisfaction) . '%)';
                    }

                    echo Search::showItem($output_type, $nb_answersatisfaction, $item_num, $row_num);

                    //Satisfaction rate
                    $satisfaction = self::constructEntryValues($itemtype, "inter_avgsatisfaction", $date1, $date2, $type, $value[$i]["id"], $value2);
                    foreach ($satisfaction as $key2 => $val2) {
                        $satisfaction[$key2] *= $answersatisfaction[$key2];
                    }
                    if ($nb_answersatisfaction > 0) {
                        $avgsatisfaction = round(array_sum($satisfaction) / $nb_answersatisfaction, 1);
                        $avgsatisfaction = TicketSatisfaction::displaySatisfaction($avgsatisfaction);
                        $arrayavgsatisfaction[] = $avgsatisfaction;
                    } else {
                        $avgsatisfaction = '&nbsp;';
                    }
                    echo Search::showItem($output_type, $avgsatisfaction, $item_num, $row_num);

                    //Le temps moyen de prise en compte du ticket - The average time to take a ticket into account
                    $data = self::constructEntryValues($itemtype, "inter_avgtakeaccount", $date1, $date2, $type, $value[$i]["id"], $value2);
                  
                    foreach ($data as $key2 => $val2) {
                        $data[$key2] *= $solved[$key2];
                    }

                    if ($nb_solved > 0) {
                        $timedisplay = array_sum($data) / $nb_solved;
                    } else {
                        $timedisplay = 0;
                    }

                    if ($output_type == HTML_OUTPUT || $output_type == PDF_OUTPUT_LANDSCAPE || $output_type == PDF_OUTPUT_PORTRAIT) {
                        $timedisplay = Html::timestampToString($timedisplay, 0);
                    }
                    
                    $arraytimedisplay[] = $timedisplay;
                    echo Search::showItem($output_type, $timedisplay, $item_num, $row_num);
                }

                //Le temps moyen de resolution - The average time to resolv
                $data = self::constructEntryValues($itemtype, "inter_avgsolvedtime", $date1, $date2, $type, $value[$i]["id"], $value2);
                
                foreach ($data as $key2 => $val2) {
                    $data[$key2] = round($data[$key2] * $solved[$key2]);
                }

                if ($nb_solved > 0) {
                    $timedisplay = array_sum($data) / $nb_solved;
                } else {
                    $timedisplay = 0;
                }
                
                $arraytimedisplay_2[] = $timedisplay;
                
                if ($output_type == HTML_OUTPUT || $output_type == PDF_OUTPUT_LANDSCAPE || $output_type == PDF_OUTPUT_PORTRAIT) {
                    $timedisplay = Html::timestampToString($timedisplay, 0);
                } 
                
                echo Search::showItem($output_type, $timedisplay, $item_num, $row_num);

                //Le temps moyen de cloture - The average time to close
                $data = self::constructEntryValues($itemtype, "inter_avgclosedtime", $date1, $date2, $type, $value[$i]["id"], $value2);

                foreach ($data as $key2 => $val2) {
                    $data[$key2] = round($data[$key2] * $solved[$key2]);
                }

                if ($nb_closed > 0) {
                    $timedisplay = array_sum($data) / $nb_closed;
                } else {
                    $timedisplay = 0;
                }
                
                $arraytimedisplay_3[] = $timedisplay;
                if ($output_type == HTML_OUTPUT || $output_type == PDF_OUTPUT_LANDSCAPE || $output_type == PDF_OUTPUT_PORTRAIT) {
                    $timedisplay = Html::timestampToString($timedisplay, 0);
                }
                echo Search::showItem($output_type, $timedisplay, $item_num, $row_num);
                
                //Le temps moyen de l'intervention reelle - The average actiontime to resolv
                $data = self::constructEntryValues($itemtype, "inter_avgactiontime", $date1, $date2, $type, $value[$i]["id"], $value2);
               
                /*foreach ($data as $key2 => $val2) {
                    if (isset($solved[$key2])) {
                        $data[$key2] *= $solved[$key2];
                    } else {
                        $data[$key2] *= 0;
                    }
                }*/
                //$total_actiontime = array_sum($data);

                /*if ($nb_solved > 0) {
                    $timedisplay = $total_actiontime / $nb_solved;
                } else {
                    $timedisplay = 0;
                }*/
                
                $arraytimemedia[] = $data['media'];
                $arraytimetotal[] = $data['total'];
                
                if ($output_type == HTML_OUTPUT || $output_type == PDF_OUTPUT_LANDSCAPE || $output_type == PDF_OUTPUT_PORTRAIT) {
                    $timedisplay = Html::timestampToString($timedisplay, 2);
                }
                echo Search::showItem($output_type, $data['media'], $item_num, $row_num);
                //Le temps total de l'intervention reelle - The total actiontime to resolv
                $timedisplay = $total_actiontime;
                
                if ($output_type == HTML_OUTPUT || $output_type == PDF_OUTPUT_LANDSCAPE || $output_type == PDF_OUTPUT_PORTRAIT) {
                    $timedisplay = Html::timestampToString($timedisplay, 0);
                }
                echo Search::showItem($output_type, $data['total'], $item_num, $row_num);

                echo Search::showEndLine($output_type);
            }
            
            $atd = new Atendimento();
            
            $total_open = array_sum($arrayopen);
            $total_solved = array_sum($arraysolved);
            $total_solved_late = array_sum($arraysolved_late);
            $total_closed = array_sum($arrayclosed);
            $total_opensatisfaction = array_sum($arrayopensatisfaction);
            $total_answersatisfaction = array_sum($arrayanswersatisfaction);
            $total_avgsatisfaction = array_sum($arrayavgsatisfaction);
            $total_timedisplay = array_sum($arraytimedisplay);
            $total_timedp = $atd->minEmhoras($total_timedisplay);
            $total_timedisplay_2 = array_sum($arraytimedisplay_2);
            $total_timedp2 = Html::timestampToString($total_timedisplay_2, 0);
            $total_timedisplay_3 = array_sum($arraytimedisplay_3);
            $total_timedp3 = Html::timestampToString($total_timedisplay_3, 0);
            $total_timemedia = $atd->somaHoras($arraytimemedia);
            $total_timetotal = $atd->somaHoras($arraytimetotal);
            
            $solved = ' (' . round($total_solved * 100 / $total_open) . '%)';
            $solved_late = ' (' . round($total_solved_late * 100 / $total_open) . '%)';
            $closed = ' (' . round($total_closed * 100 / $total_open) . '%)';
           
            echo "<tr class='tab_bg_2'><td valign='top' align='center'><b>TOTAL</b></td>";
            echo "<td valign='top'>&nbsp;</td>";
            echo "<td valign='top'><b>{$total_open}</b></td>";
            echo "<td valign='top'><b>{$total_solved} {$solved}</b></td>";
            echo "<td valign='top'><b>{$total_solved_late} {$solved_late}</b></td>";
            echo "<td valign='top'><b>{$total_closed} {$closed}</b></td>";
            echo "<td valign='top'><b>{$total_opensatisfaction}</b></td>";
            echo "<td valign='top'><b>{$total_answersatisfaction}</b></td>";
            echo "<td valign='top'>&nbsp;</td>";
            echo "<td valign='top'><b>{$total_timedp}:00</b></td>";
            echo "<td valign='top'><b>{$total_timedp2}</b></td>";
            echo "<td valign='top'><b>{$total_timedp3}</b></td>";
            //echo "<td valign='top'>02:09:30</td>";
            echo "<td valign='top' align='center'><b>{$total_timemedia}</b></td>";
            echo "<td valign='top' align='center'><b>{$total_timetotal}</b></td>";
            echo "</tr>";
            
            // Display footer
            echo Search::showFooter($output_type);
        } else {
            echo $LANG['stats'][23];
        }

        if ($output_type == HTML_OUTPUT) { // HTML display
            echo "</div>";
        }
    }

    static function constructEntryValues($itemtype, $type, $begin = "", $end = "", $param = "", $value = "", $value2 = "") {
        global $DB;
        $item = new $itemtype();
        $table = $item->getTable();
        $fkfield = $item->getForeignKeyField();
        $userlinkclass = new $item->userlinkclass();
        $userlinktable = $userlinkclass->getTable();
        $grouplinkclass = new $item->grouplinkclass();
        $grouplinktable = $grouplinkclass->getTable();
        $tasktable = getTableForItemType($item->getType() . 'Task');

        $closed_status = $item->getClosedStatusArray();
        $solved_status = array_merge($closed_status, $item->getSolvedStatusArray());

        $query = "";
        $WHERE = "WHERE NOT `$table`.`is_deleted` " .
                getEntitiesRestrictRequest("AND", $table);
        $LEFTJOIN = "";
        $LEFTJOINUSER = "LEFT JOIN `$userlinktable`
                           ON (`$userlinktable`.`$fkfield` = `$table`.`id`)";
        $LEFTJOINGROUP = "LEFT JOIN `$grouplinktable`
                           ON (`$grouplinktable`.`$fkfield` = `$table`.`id`)";

        switch ($param) {
            case "technicien" :
                $LEFTJOIN = $LEFTJOINUSER;
                $WHERE .= " AND (`$userlinktable`.`users_id` = '$value'
                              AND `$userlinktable`.`type`='" . CommonITILObject::ASSIGN . "')";
                break;

            case "technicien_followup" :
                $WHERE .= " AND `$tasktable`.`users_id` = '$value'";
                $LEFTJOIN = " LEFT JOIN `$tasktable`
                              ON (`$tasktable`.`$fkfield` = `$table`.`id`)";
                break;

            case "enterprise" :
                $WHERE .= " AND `$table`.`suppliers_id_assign` = '$value'";
                break;

            case "user" :
                $LEFTJOIN = $LEFTJOINUSER;
                $WHERE .= " AND (`$userlinktable`.`users_id` = '$value'
                              AND `$userlinktable`.`type` ='" . CommonITILObject::REQUESTER . "')";
                break;

            case "usertitles_id" :
                $LEFTJOIN = $LEFTJOINUSER;
                $LEFTJOIN .= " LEFT JOIN `glpi_users`
                              ON (`glpi_users`.`id` = `$userlinktable`.`users_id`)";
                $WHERE .= " AND (`glpi_users`.`usertitles_id` = '$value'
                              AND `$userlinktable`.`type` = '" . CommonITILObject::REQUESTER . "')";
                break;

            case "usercategories_id" :
                $LEFTJOIN = $LEFTJOINUSER;
                $LEFTJOIN .= " LEFT JOIN `glpi_users`
                              ON (`glpi_users`.`id` = `$userlinktable`.`users_id`)";
                $WHERE .= " AND (`glpi_users`.`usercategories_id` = '$value'
                              AND `$userlinktable`.`type` = '" . CommonITILObject::REQUESTER . "')";
                break;

            case "users_id_recipient" :
                $WHERE .= " AND `$table`.`users_id_recipient` = '$value'";
                break;

            case "type" :
                $WHERE .= " AND `$table`.`type` = '$value'";
                break;

            case "itilcategories_tree" :
                if ($value == $value2) {
                    $categories = array($value);
                } else {
                    $categories = getSonsOf("glpi_itilcategories", $value);
                }
                $condition = implode("','", $categories);
                $WHERE .= " AND `$table`.`itilcategories_id` IN ('$condition')";
                break;

            case "itilcategories_id" :
                /*
                  Drop this =>  Flat display, don't count child (use tree display for that)

                  if (!empty($value)) {
                  // do not merge for pie chart
                  if (!isset($_REQUEST['showgraph']) || !$_REQUEST['showgraph']) {
                  $categories = getSonsOf("glpi_itilcategories", $value);
                  $condition  = implode("','",$categories);
                  $WHERE .= " AND `$table`.`itilcategories_id` IN ('$condition')";
                  } else {
                  $WHERE .= " AND `$table`.`itilcategories_id` = '$value' ";
                  }

                  } else { */

                $WHERE .= " AND `$table`.`itilcategories_id` = '$value' ";
                break;

            case 'group_tree' :
            case 'groups_tree_assign' :
                $grptype = ($param == 'group_tree' ? CommonITILObject::REQUESTER : CommonITILObject::ASSIGN);
                if ($value == $value2) {
                    $groups = array($value);
                } else {
                    $groups = getSonsOf("glpi_groups", $value);
                }
                $condition = implode("','", $groups);

                $LEFTJOIN = $LEFTJOINGROUP;
                $WHERE .= " AND (`$grouplinktable`.`groups_id` IN ('$condition')
                              AND `$grouplinktable`.`type` = '$grptype')";
                break;

            case "group" :
                $LEFTJOIN = $LEFTJOINGROUP;
                $WHERE .= " AND (`$grouplinktable`.`groups_id` = '$value'
                              AND `$grouplinktable`.`type` = '" . CommonITILObject::REQUESTER . "')";
                break;

            case "groups_id_assign" :
                $LEFTJOIN = $LEFTJOINGROUP;
                $WHERE .= " AND (`$grouplinktable`.`groups_id` = '$value'
                              AND `$grouplinktable`.`type` = '" . CommonITILObject::ASSIGN . "')";
                break;

            case "requesttypes_id" :
            case "solutiontypes_id" :
            case "urgency" :
            case "impact" :
            case "priority" :
                $WHERE .= " AND `$table`.`$param` = '$value'";
                break;


            case "device":
                $devtable = getTableForItemType('Computer_' . $value2);
                $fkname = getForeignKeyFieldForTable(getTableForItemType($value2));
                //select computers IDs that are using this device;
                $LEFTJOIN = " INNER JOIN `glpi_computers`
                              ON (`glpi_computers`.`id` = `$table`.`items_id`
                                  AND `$table`.`itemtype` = 'Computer')
                          INNER JOIN `$devtable`
                              ON (`glpi_computers`.`id` = `$devtable`.`computers_id`
                                  AND `$devtable`.`$fkname` = '$value')";
                $WHERE .= " AND `glpi_computers`.`is_template` <> '1' ";
                break;

            case "comp_champ" :
                $ftable = getTableForItemType($value2);
                $champ = getForeignKeyFieldForTable($ftable);
                $LEFTJOIN = " INNER JOIN `glpi_computers`
                              ON (`glpi_computers`.`id` = `$table`.`items_id`
                                  AND `$table`.`itemtype` = 'Computer')";
                $WHERE .= " AND `glpi_computers`.`$champ` = '$value'
                        AND `glpi_computers`.`is_template` <> '1'";
                break;
        }

        switch ($type) {
            case "inter_total" :
                $WHERE .= " AND " . getDateRequest("`$table`.`date`", $begin, $end);

                $query = "SELECT FROM_UNIXTIME(UNIX_TIMESTAMP(`$table`.`date`),'%Y-%m')
                                 AS date_unix,
                             COUNT(`$table`.`id`) AS total_visites
                      FROM `$table`
                      $LEFTJOIN
                      $WHERE
                      GROUP BY date_unix
                      ORDER BY `$table`.`date`";
                break;

            case "inter_solved" :
                $WHERE .= " AND `$table`.`status` IN ('" . implode("','", $solved_status) . "')
                        AND `$table`.`solvedate` IS NOT NULL
                        AND " . getDateRequest("`$table`.`solvedate`", $begin, $end);

                $query = "SELECT FROM_UNIXTIME(UNIX_TIMESTAMP(`$table`.`solvedate`),'%Y-%m')
                                 AS date_unix,
                             COUNT(`$table`.`id`) AS total_visites
                      FROM `$table`
                      $LEFTJOIN
                      $WHERE
                      GROUP BY date_unix
                      ORDER BY `$table`.`solvedate`";
                break;

            case "inter_solved_late" :
                $WHERE .= " AND `$table`.`status` IN ('" . implode("','", $solved_status) . "')
                        AND `$table`.`solvedate` IS NOT NULL
                        AND `$table`.`due_date` IS NOT NULL
                        AND " . getDateRequest("`$table`.`solvedate`", $begin, $end) . "
                        AND `$table`.`solvedate` > `$table`.`due_date`";

                $query = "SELECT FROM_UNIXTIME(UNIX_TIMESTAMP(`$table`.`solvedate`),'%Y-%m')
                                 AS date_unix,
                             COUNT(`$table`.`id`) AS total_visites
                      FROM `$table`
                      $LEFTJOIN
                      $WHERE
                      GROUP BY date_unix
                      ORDER BY `$table`.`solvedate`";
                break;

            case "inter_closed" :
                $WHERE .= " AND `$table`.`status` IN ('" . implode("','", $closed_status) . "')
                        AND `$table`.`closedate` IS NOT NULL
                        AND " . getDateRequest("`$table`.`closedate`", $begin, $end);

                $query = "SELECT FROM_UNIXTIME(UNIX_TIMESTAMP(`$table`.`closedate`),'%Y-%m')
                                 AS date_unix,
                             COUNT(`$table`.`id`) AS total_visites
                      FROM `$table`
                      $LEFTJOIN
                      $WHERE
                      GROUP BY date_unix
                      ORDER BY `$table`.`closedate`";
                
                break;

            case "inter_avgsolvedtime" :
                $WHERE .= " AND `$table`.`status` IN ('" . implode("','", $solved_status) . "')
                        AND `$table`.`solvedate` IS NOT NULL
                        AND " . getDateRequest("`$table`.`solvedate`", $begin, $end);

                $query = "SELECT FROM_UNIXTIME(UNIX_TIMESTAMP(`$table`.`solvedate`),'%Y-%m')
                                 AS date_unix,
                             AVG(solve_delay_stat) AS total_visites
                      FROM `$table`
                      $LEFTJOIN
                      $WHERE
                      GROUP BY date_unix
                      ORDER BY `$table`.`solvedate`";
                break;

            case "inter_avgclosedtime" :
                $WHERE .= " AND  `$table`.`status` IN ('" . implode("','", $closed_status) . "')
                        AND `$table`.`closedate` IS NOT NULL
                        AND " . getDateRequest("`$table`.`closedate`", $begin, $end);

                $query = "SELECT FROM_UNIXTIME(UNIX_TIMESTAMP(`$table`.`closedate`),'%Y-%m')
                                 AS date_unix,
                             AVG(close_delay_stat) AS total_visites
                      FROM `$table`
                      $LEFTJOIN
                      $WHERE
                      GROUP BY date_unix
                      ORDER BY `$table`.`closedate`";
                break;

            case "inter_avgactiontime" :
                if ($param == "technicien_followup") {
                    $actiontime_table = $tasktable;
                } else {
                    $actiontime_table = $table;
                }
                $WHERE .= " AND " . getDateRequest("`$table`.`solvedate`", $begin, $end);

                $query = "SELECT `glpi_tickets`.`id`,`glpi_tickets`.`horas_uteis_atendimento`                                  
                      FROM `$table`
                      $LEFTJOIN
                      $WHERE                     
                      ORDER BY `$table`.`solvedate`";
                
                $result = $DB->query($query); 
                
                while ($row = $DB->fetch_array($result)){
                    $total[] = $row['horas_uteis_atendimento'];
                }
                
                $media = count($total);
                
                if($media == 0){
                    $media = 0;
                }                
                
                $atd = new Atendimento();
                $resultado_final = $atd->somaHoras($total);
                //var_export($resultado_final);
                //die();
                //FAZ A MEDIA DE HORAS (HORAS/INTEIRO)
                $query_hora = "SELECT SEC_TO_TIME(TIME_TO_SEC('{$resultado_final}') / {$media}) AS media";
                $result_hora = $DB->query($query_hora); 
                $row = $DB->fetch_array($result_hora);  
                                
                if($row['media'] == NULL){
                    $row['media'] = "00:00:00";
                }else{
                    $media_hora = substr($row['media'], 0, 2);
                    $media_min = substr($row['media'], 3, 2);                
                    $media_seg = substr($row['media'], 6, 2);
                    
                    $row['media'] = "$media_hora:$media_min:$media_seg";
                }
                
                $result_final = array("media"=>$row['media'],"total"=>$resultado_final);                 
                
                return $result_final;
                 
                break;

            case "inter_avgtakeaccount" :
                $WHERE .= " AND `$table`.`status` IN ('" . implode("','", $solved_status) . "')
                        AND `$table`.`solvedate` IS NOT NULL
                        AND " . getDateRequest("`$table`.`solvedate`", $begin, $end);

                 $query = "SELECT `$table`.`id`,
                  FROM_UNIXTIME(UNIX_TIMESTAMP(`$table`.`solvedate`),'%Y-%m')
                  AS date_unix,
                  AVG(`$table`.`takeintoaccount_delay_stat`) AS total_visites
                  FROM `$table`
                  $LEFTJOIN
                  $WHERE
                  GROUP BY date_unix
                  ORDER BY `$table`.`solvedate`"; 
              
                break;

            case "inter_opensatisfaction" :
                $WHERE .= " AND `$table`.`status` IN ('" . implode("','", $closed_status) . "')
                        AND `$table`.`closedate` IS NOT NULL
                        AND " . getDateRequest("`$table`.`closedate`", $begin, $end);

                $query = "SELECT FROM_UNIXTIME(UNIX_TIMESTAMP(`$table`.`closedate`),'%Y-%m')
                                 AS date_unix,
                             COUNT(`$table`.`id`) AS total_visites
                      FROM `$table`
                      INNER JOIN `glpi_ticketsatisfactions`
                        ON (`$table`.`id` = `glpi_ticketsatisfactions`.`tickets_id`)
                      $LEFTJOIN
                      $WHERE
                      GROUP BY date_unix
                      ORDER BY `$table`.`closedate`";
                break;

            case "inter_answersatisfaction" :
                $WHERE .= " AND `$table`.`status` IN ('" . implode("','", $closed_status) . "')
                        AND `$table`.`closedate` IS NOT NULL
                        AND `glpi_ticketsatisfactions`.`date_answered` IS NOT NULL
                        AND " . getDateRequest("`$table`.`closedate`", $begin, $end);

                $query = "SELECT FROM_UNIXTIME(UNIX_TIMESTAMP(`$table`.`closedate`),'%Y-%m')
                                 AS date_unix,
                             COUNT(`$table`.`id`) AS total_visites
                      FROM `$table`
                      INNER JOIN `glpi_ticketsatisfactions`
                        ON (`$table`.`id` = `glpi_ticketsatisfactions`.`tickets_id`)
                      $LEFTJOIN
                      $WHERE
                      GROUP BY date_unix
                      ORDER BY `$table`.`closedate`";
                break;

            case "inter_avgsatisfaction" :
                $WHERE .= " AND `glpi_ticketsatisfactions`.`date_answered` IS NOT NULL
                        AND `$table`.`status` IN ('" . implode("','", $closed_status) . "')
                        AND `$table`.`closedate` IS NOT NULL
                        AND " . getDateRequest("`$table`.`closedate`", $begin, $end);

                $query = "SELECT FROM_UNIXTIME(UNIX_TIMESTAMP(`$table`.`closedate`),'%Y-%m')
                                 AS date_unix,
                             AVG(`glpi_ticketsatisfactions`.`satisfaction`) AS total_visites
                      FROM `$table`
                      INNER JOIN `glpi_ticketsatisfactions`
                        ON (`$table`.`id` = `glpi_ticketsatisfactions`.`tickets_id`)
                      $LEFTJOIN
                      $WHERE
                      GROUP BY date_unix
                      ORDER BY `$table`.`closedate`";
                break;
        }

        $entrees = array();
        $count = array();
        if (empty($query)) {
            return array();
        }

        $result = $DB->query($query);
        if ($result && $DB->numrows($result) > 0) {
            while ($row = $DB->fetch_array($result)) {
                $date = $row['date_unix'];
                //$visites = round($row['total_visites']);
                $entrees["$date"] = $row['total_visites'];
            }
        }

        // Remplissage de $entrees pour les mois ou il n'y a rien
//       $min=-1;
//       $max=0;
//       if (count($entrees)==0) {
//          return $entrees;
//       }
//       foreach ($entrees as $key => $val) {
//          $time=strtotime($key."-01");
//          if ($min>$time || $min<0) {
//             $min=$time;
//          }
//          if ($max<$time) {
//             $max=$time;
//          }
//       }

        $end_time = strtotime(date("Y-m", strtotime($end)) . "-01");
        $begin_time = strtotime(date("Y-m", strtotime($begin)) . "-01");

//       if ($max<$end_time) {
//          $max=$end_time;
//       }
//       if ($min>$begin_time) {
//          $min=$begin_time;
//       }
        $current = $begin_time;

        while ($current <= $end_time) {
            $curentry = date("Y-m", $current);
            if (!isset($entrees["$curentry"])) {
                $entrees["$curentry"] = 0;
            }
            $month = date("m", $current);
            $year = date("Y", $current);
            $current = mktime(0, 0, 0, intval($month) + 1, 1, intval($year));
        }
        ksort($entrees);

        return $entrees;
    }

    /** Get groups assigned to tickets between 2 dates
     *
     * @param $entrees array : array containing data to displayed
     * @param $options array : options
     *     - title string title displayed (default empty)
     *     - showtotal boolean show total in title (default false)
     *     - width integer width of the graph (default 700)
     *     - height integer height of the graph (default 300)
     *     - unit integer height of the graph (default empty)
     *     - type integer height of the graph (default line) : line bar stack pie
     *     - csv boolean export to CSV (default true)
     *     - datatype string datatype (count or average / default is count)
     *
     * @return array contains the distinct groups assigned to a tickets
     * */
    static function showGraph($entrees, $options = array()) {
        global $CFG_GLPI, $LANG;

        if ($uid = Session::getLoginUserID(false)) {
            if (!isset($_SESSION['glpigraphtype'])) {
                $_SESSION['glpigraphtype'] = $CFG_GLPI['default_graphtype'];
            }

            $param['showtotal'] = false;
            $param['title'] = '';
            $param['width'] = 900;
            $param['height'] = 300;
            $param['unit'] = '';
            $param['type'] = 'line';
            $param['csv'] = true;
            $param['datatype'] = 'count';

            if (is_array($options) && count($options)) {
                foreach ($options as $key => $val) {
                    $param[$key] = $val;
                }
            }

            // Clean data
            if (is_array($entrees) && count($entrees)) {
                foreach ($entrees as $key => $val) {
                    if (!is_array($val) || count($val) == 0) {
                        unset($entrees[$key]);
                    }
                }
            }

            if (!is_array($entrees) || count($entrees) == 0) {
                if (!empty($param['title'])) {
                    echo "<div class='center'>" . $param['title'] . " : " . $LANG['stats'][2] . "</div>";
                }
                return false;
            }

            echo "<div class='center-h' style='width:" . $param['width'] . "px'>";
            echo "<div>";

            switch ($param['type']) {
                case 'pie' :
                    // Check datas : sum must be > 0
                    reset($entrees);
                    $sum = array_sum(current($entrees));
                    while ($sum == 0 && $data = next($entrees)) {
                        $sum += array_sum($data);
                    }
                    if ($sum == 0) {
                        echo "</div></div>";
                        return false;
                    }
                    $graph = new ezcGraphPieChart();
                    $graph->palette = new GraphPalette();
                    $graph->options->font->maxFontSize = 15;
                    $graph->title->background = '#EEEEEC';
                    $graph->renderer = new ezcGraphRenderer3d();
                    $graph->renderer->options->pieChartHeight = 20;
                    $graph->renderer->options->moveOut = .2;
                    $graph->renderer->options->pieChartOffset = 63;
                    $graph->renderer->options->pieChartGleam = .3;
                    $graph->renderer->options->pieChartGleamColor = '#FFFFFF';
                    $graph->renderer->options->pieChartGleamBorder = 2;
                    $graph->renderer->options->pieChartShadowSize = 5;
                    $graph->renderer->options->pieChartShadowColor = '#BABDB6';

                    if (count($entrees) == 1) {
                        $graph->legend = false;
                    }

                    break;

                case 'bar' :
                case 'stack' :
                    $graph = new ezcGraphBarChart();
                    $graph->options->fillLines = 210;
                    $graph->xAxis->axisLabelRenderer = new ezcGraphAxisRotatedBoxedLabelRenderer();
                    $graph->xAxis->axisLabelRenderer->angle = 45;
                    $graph->xAxis->axisSpace = .2;
                    $graph->yAxis->min = 0;
                    $graph->palette = new GraphPalette();
                    $graph->options->font->maxFontSize = 15;
                    $graph->title->background = '#EEEEEC';
                    $graph->renderer = new ezcGraphRenderer3d();
                    $graph->renderer->options->legendSymbolGleam = .5;
                    $graph->renderer->options->barChartGleam = .5;

                    if ($param['type'] == 'stack') {
                        $graph->options->stackBars = true;
                    }

                    $max = 0;
                    $valtmp = array();
                    foreach ($entrees as $key => $val) {
                        foreach ($val as $key2 => $val2) {
                            $valtmp[$key2] = $val2;
                        }
                    }
                    $graph->xAxis->labelCount = count($valtmp);
                    break;

                case 'line' :
                // No break default case

                default :
                    $graph = new ezcGraphLineChart();
                    $graph->options->fillLines = 210;
                    $graph->xAxis->axisLabelRenderer = new ezcGraphAxisRotatedLabelRenderer();
                    $graph->xAxis->axisLabelRenderer->angle = 45;
                    $graph->xAxis->axisSpace = .2;
                    $graph->yAxis->min = 0;
                    $graph->palette = new GraphPalette();
                    $graph->options->font->maxFontSize = 15;
                    $graph->title->background = '#EEEEEC';
                    $graph->renderer = new ezcGraphRenderer3d();
                    $graph->renderer->options->legendSymbolGleam = .5;
                    $graph->renderer->options->barChartGleam = .5;
                    $graph->renderer->options->depth = 0.07;
                    break;
            }


            if (!empty($param['title'])) {
                $pretoadd = "";
                $posttoadd = "";
                if (!empty($param['unit'])) {
                    $posttoadd = " " . $param['unit'];
                    $pretoadd = " - ";
                }

                // Add to title
                if (count($entrees) == 1) {
                    $param['title'] .= $pretoadd;
                    if ($param['showtotal'] == 1) {
                        reset($entrees);
                        $param['title'] .= round(array_sum(current($entrees)), 2);
                    }
                    $param['title'] .= $posttoadd;
                } else { // add sum to legend and unit to title
                    $param['title'] .= $pretoadd . $posttoadd;
                    // Cannot display totals of already average values

                    if ($param['showtotal'] == 1 && $param['datatype'] != 'average') {
                        $entree_tmp = $entrees;
                        $entrees = array();
                        foreach ($entree_tmp as $key => $data) {
                            $sum = round(array_sum($data));
                            $entrees[$key . " ($sum)"] = $data;
                        }
                    }
                }

                $graph->title = $param['title'];
            }

            switch ($_SESSION['glpigraphtype']) {
                case "png" :
                    $extension = "png";
                    $graph->driver = new ezcGraphGdDriver();
                    $graph->options->font = GLPI_FONT_FREESANS;
                    break;

                default :
                    $extension = "svg";
                    break;
            }

            $filename = $uid . '_' . mt_rand();
            $csvfilename = $filename . '.csv';
            $filename .= '.' . $extension;
            foreach ($entrees as $label => $data) {
                $graph->data[$label] = new ezcGraphArrayDataSet($data);
                $graph->data[$label]->symbol = ezcGraph::NO_SYMBOL;
            }

            switch ($_SESSION['glpigraphtype']) {
                case "png" :
                    $graph->render($param['width'], $param['height'], GLPI_GRAPH_DIR . '/' . $filename);
                    echo "<img src='" . $CFG_GLPI['root_doc'] . "/front/graph.send.php?file=$filename'>";
                    break;

                default :
                    $graph->render($param['width'], $param['height'], GLPI_GRAPH_DIR . '/' . $filename);
                    echo "<object data='" . $CFG_GLPI['root_doc'] . "/front/graph.send.php?file=$filename'
                      type='image/svg+xml' width='" . $param['width'] . "' height='" . $param['height'] . "'>
                      <param name='src' value='" . $CFG_GLPI['root_doc'] .
                    "/front/graph.send.php?file=$filename'>
                      You need a browser capeable of SVG to display this image.
                     </object> ";
                    break;
            }

            // Render CSV
            if ($param['csv']) {
                if ($fp = fopen(GLPI_GRAPH_DIR . '/' . $csvfilename, 'w')) {
                    // reformat datas
                    $values = array();
                    $labels = array();
                    $row_num = 0;
                    foreach ($entrees as $label => $data) {
                        $labels[$row_num] = $label;
                        if (is_array($data) && count($data)) {
                            foreach ($data as $key => $val) {
                                if (!isset($values[$key])) {
                                    $values[$key] = array();
                                }
                                if ($param['datatype'] == 'average') {
                                    $val = round($val, 2);
                                }
                                $values[$key][$row_num] = $val;
                            }
                        }
                        $row_num++;
                    }
                    ksort($values);
                    // Print labels
                    fwrite($fp, $_SESSION["glpicsv_delimiter"]);
                    foreach ($labels as $val) {
                        fwrite($fp, $val . $_SESSION["glpicsv_delimiter"]);
                    }
                    fwrite($fp, "\n");
                    foreach ($values as $key => $data) {
                        fwrite($fp, $key . $_SESSION["glpicsv_delimiter"]);
                        foreach ($data as $value) {
                            fwrite($fp, $value . $_SESSION["glpicsv_delimiter"]);
                        }
                        fwrite($fp, "\n");
                    }

                    fclose($fp);
                }
            }
            echo "</div>";
            echo "<div class='right' style='width:" . $param['width'] . "px'>";
            if ($_SESSION['glpigraphtype'] != 'svg') {
                echo "&nbsp;<a href='" . $CFG_GLPI['root_doc'] . "/front/graph.send.php?switchto=svg'>SVG" .
                "</a>";
            }
            if ($_SESSION['glpigraphtype'] != 'png') {
                echo "&nbsp;<a href='" . $CFG_GLPI['root_doc'] . "/front/graph.send.php?switchto=png'>PNG" .
                "</a>";
            }
            if ($param['csv']) {
                echo " / <a href='" . $CFG_GLPI['root_doc'] . "/front/graph.send.php?file=$csvfilename'>CSV" .
                "</a>";
            }
            echo "</div>";
            echo '</div>';
        }
    }

    static function showItems($target, $date1, $date2, $start) {
        global $DB, $CFG_GLPI, $LANG;

        $view_entities = Session::isMultiEntitiesMode();

        if ($view_entities) {
            $entities = getAllDatasFromTable('glpi_entities');
        }

        $output_type = HTML_OUTPUT;
        if (isset($_GET["display_type"])) {
            $output_type = $_GET["display_type"];
        }
        if (empty($date2)) {
            $date2 = date("Y-m-d");
        }
        $date2 .= " 23:59:59";

        // 1 an par defaut
        if (empty($date1)) {
            $date1 = date("Y-m-d", mktime(0, 0, 0, date("m"), date("d"), date("Y") - 1));
        }
        $date1 .= " 00:00:00";

        $query = "SELECT `itemtype`,
                       `items_id`,
                       COUNT(*) AS NB
                FROM `glpi_tickets`
                WHERE `date` <= '$date2'
                      AND `date` >= '$date1' " .
                getEntitiesRestrictRequest("AND", "glpi_tickets") . "
                      AND `itemtype` <> ''
                      AND `items_id` > 0
                GROUP BY `itemtype`, `items_id`
                ORDER BY NB DESC";

        $result = $DB->query($query);
        $numrows = $DB->numrows($result);

        if ($numrows > 0) {
            if ($output_type == HTML_OUTPUT) {
                Html::printPager($start, $numrows, $target, "date1=" . $date1 . "&amp;date2=" . $date2 .
                        "&amp;type=hardwares&amp;start=$start", 'Stat');
                echo "<div class='center'>";
            }

            $end_display = $start + $_SESSION['glpilist_limit'];
            if (isset($_GET['export_all'])) {
                $end_display = $numrows;
            }
            echo Search::showHeader($output_type, $end_display - $start + 1, 2, 1);
            $header_num = 1;
            echo Search::showNewLine($output_type);
            echo Search::showHeaderItem($output_type, $LANG['document'][14], $header_num);
            if ($view_entities) {
                echo Search::showHeaderItem($output_type, $LANG['entity'][0], $header_num);
            }
            echo Search::showHeaderItem($output_type, $LANG['stats'][13], $header_num);
            echo Search::showEndLine($output_type);

            $DB->data_seek($result, $start);

            $i = $start;
            if (isset($_GET['export_all'])) {
                $start = 0;
            }

            for ($i = $start; $i < $numrows && $i < $end_display; $i++) {
                $item_num = 1;
                // Get data and increment loop variables
                $data = $DB->fetch_assoc($result);
                if (!($item = getItemForItemtype($data["itemtype"]))) {
                    continue;
                }
                if ($item->getFromDB($data["items_id"])) {
                    echo Search::showNewLine($output_type, $i % 2);
                    echo Search::showItem($output_type, $item->getTypeName() . " - " . $item->getLink(), $item_num, $i - $start + 1, "class='center'" . " " . ($item->isDeleted() ? " class='deleted' " : ""));
                    if ($view_entities) {
                        $ent = $item->getEntityID();
                        if ($ent == 0) {
                            $ent = $LANG['entity'][2];
                        } else {
                            $ent = $entities[$ent]['completename'];
                        }
                        echo Search::showItem($output_type, $ent, $item_num, $i - $start + 1, "class='center'" . " " . ($item->isDeleted() ? " class='deleted' " : ""));
                    }
                    echo Search::showItem($output_type, $data["NB"], $item_num, $i - $start + 1, "class='center'" . " " . ($item->isDeleted() ? " class='deleted' " : ""));
                }
            }

            echo Search::showFooter($output_type);
            if ($output_type == HTML_OUTPUT) {
                echo "</div>";
            }
        }
    }

    static function title() {
        global $LANG, $PLUGIN_HOOKS, $CFG_GLPI;

        $show_problem = Session::haveRight("edit_all_problem", "1") || Session::haveRight("show_all_problem", "1");

        $opt_list["Ticket"] = $LANG['Menu'][5];
        $stat_list["Ticket"]["Ticket_Global"]["name"] = $LANG['stats'][1];
        $stat_list["Ticket"]["Ticket_Global"]["file"] = "stat.global.php?itemtype=Ticket";
        $stat_list["Ticket"]["Ticket_Ticket"]["name"] = $LANG['stats'][47];
        $stat_list["Ticket"]["Ticket_Ticket"]["file"] = "stat.tracking.php?itemtype=Ticket";
        $stat_list["Ticket"]["Ticket_Location"]["name"] = $LANG['stats'][3];
        $stat_list["Ticket"]["Ticket_Location"]["file"] = "stat.location.php?itemtype=Ticket";
        $comment = "(" . $LANG['common'][15] . ", " . $LANG['common'][17] . ", " .
                $LANG['computers'][9] . ", " . $LANG['devices'][4] . ", " . $LANG['computers'][36] . ", " .
                $LANG['devices'][2] . ", " . $LANG['devices'][5] . ")";
        $stat_list["Ticket"]["Ticket_Location"]["comment"] = $comment;
        $stat_list["Ticket"]["Ticket_Item"]["name"] = $LANG['stats'][45];
        $stat_list["Ticket"]["Ticket_Item"]["file"] = "stat.item.php";

        if ($show_problem) {
            $opt_list["Problem"] = $LANG['Menu'][7];
            $stat_list["Problem"]["Problem_Global"]["name"] = $LANG['stats'][1];
            $stat_list["Problem"]["Problem_Global"]["file"] = "stat.global.php?itemtype=Problem";
            $stat_list["Problem"]["Problem_Problem"]["name"] = $LANG['stats'][46];
            $stat_list["Problem"]["Problem_Problem"]["file"] = "stat.tracking.php?itemtype=Problem";
        }

        //Affichage du tableau de presentation des stats
        echo "<table class='tab_cadre_fixe'>";
        echo "<tr><th colspan='2'>" . $LANG['stats'][0] . "&nbsp;:</th></tr>";
        echo "<tr class='tab_bg_1'><td class='center'>";
        echo "<select name='statmenu' onchange='window.location.href=this.options
    [this.selectedIndex].value'>";
        echo "<option value='-1' selected>" . Dropdown::EMPTY_VALUE . "</option>";

        $i = 0;
        $count = count($stat_list);

        foreach ($opt_list as $opt => $group) {

            echo "<optgroup label=\"" . $group . "\">";
            while ($data = each($stat_list[$opt])) {
                $name = $data[1]["name"];
                $file = $data[1]["file"];
                $comment = "";
                if (isset($data[1]["comment"]))
                    $comment = $data[1]["comment"];

                echo "<option value='" . $CFG_GLPI["root_doc"] . "/front/" . $file . "' title=\"" . Html::cleanInputText($comment) . "\">" . $name . "</option>";
                $i++;
            }
            echo "</optgroup>";
        }
        $names = array();
        $optgroup = array();
        if (isset($PLUGIN_HOOKS["stats"]) && is_array($PLUGIN_HOOKS["stats"])) {
            foreach ($PLUGIN_HOOKS["stats"] as $plug => $pages) {
                $function = "plugin_version_$plug";
                $plugname = $function();
                if (is_array($pages) && count($pages)) {
                    foreach ($pages as $page => $name) {
                        $names[$plug . '/' . $page] = array("name" => $name,
                            "plug" => $plug);
                        $optgroup[$plug] = $plugname['name'];
                    }
                }
            }
            asort($names);
        }

        foreach ($optgroup as $opt => $title) {

            echo "<optgroup label=\"" . $title . "\">";

            foreach ($names as $key => $val) {
                if ($opt == $val["plug"]) {
                    echo "<option value='" . $CFG_GLPI["root_doc"] . "/plugins/" . $key . "'>" .
                    $val["name"] . "</option>";
                }
            }

            echo "</optgroup>";
        }

        echo "</select>";
        echo "</td>";
        echo "</tr>";
        echo "</table>";
    }

}

?>
