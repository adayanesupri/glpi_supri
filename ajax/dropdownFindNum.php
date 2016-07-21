<?php

/*
 * @version $Id: dropdownFindNum.php 17152 2012-01-24 11:22:16Z moyo $
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
// Purpose of file: List of device for tracking.
// ----------------------------------------------------------------------

define('GLPI_ROOT', '..');
include (GLPI_ROOT . "/inc/includes.php");

header("Content-Type: text/html; charset=UTF-8");
Html::header_nocache();

Session::checkRight("create_ticket", "1");
// Security
if (!TableExists($_POST['table'])) {
    exit();
}

$itemtypeisplugin = isPluginItemType($_POST['itemtype']);
$item = new $_POST['itemtype']();

//var_export($_POST['itemtype']);
if ($item->isEntityAssign()) {
   
    if (isset($_POST["entity_restrict"]) && $_POST["entity_restrict"] >= 0) {
        $entity = $_POST["entity_restrict"];
    } else {
        $entity = '';
    }

    /* //SUPRISERVICE */
    if (array_key_exists("entity_sons", $_POST) && $_POST["entity_sons"]) {
        $where = " WHERE entities_id IN ( '" . implode("', '", getSonsOf('glpi_entities', $entity)) . "')";
    } else {
        // allow opening ticket on recursive object (printer, software, ...)
        $recursive = $item->maybeRecursive();
        $where = getEntitiesRestrictRequest("WHERE", $_POST['table'], '', $entity, $recursive);
    }
   
} else {
    $where = "WHERE 1";
}

if ($item->maybeDeleted()) {
    $where .= " AND `is_deleted` = '0' ";
}

if ($item->maybeTemplate()) {
    $where .= " AND `is_template` = '0' ";
}


if (strlen($_POST['searchText']) > 0 && $_POST['searchText'] != $CFG_GLPI["ajax_wildcard"]) {
    $search = Search::makeTextSearch($_POST['searchText']);   
    
    $where .= " AND (`name` " . $search . "
                    OR `" . $_POST['table'] . "`.`id` = '" . $_POST['searchText'] . "'";

    /* //SUPRISERVICE */
    if ($_POST['table'] != "glpi_softwares" && !$itemtypeisplugin && $_POST['table'] != "glpi_computermodels" && $_POST['table'] != "glpi_monitormodels" && $_POST['table'] != "glpi_printermodels" && $_POST['table'] != "glpi_computertypes" && $_POST['table'] != "glpi_monitortypes" && $_POST['table'] != "glpi_printertypes") {
        
        /* //SUPRISERVICE */
        if($_POST['table'] == 'supridesk_veiculos'){
            
            $where .= " OR `placa` " . $search;
        }else{
            
            $where .= " OR `contact` " . $search . "
                  OR `serial` " . $search . "
                  OR `otherserial` " . $search;
        }
        
    }
    $where .= ")";
}

 
/* //SUPRISERVICE */
if ($_POST['table'] == 'glpi_computers') {
    $tipo = 'Computer';
} elseif ($_POST['table'] == 'glpi_monitors') {
    $tipo = 'Monitor';
} elseif ($_POST['table'] == 'glpi_printers') {
    $tipo = 'Printer';
}

//If software or plugins : filter to display only the objects that are allowed to be visible in Helpdesk
if (in_array($_POST['itemtype'], $CFG_GLPI["helpdesk_visible_types"])) {
    $where .= " AND `is_helpdesk_visible` = '1' ";
}

$NBMAX = $CFG_GLPI["dropdown_max"];
$LIMIT = "LIMIT 0,$NBMAX";

/* //SUPRISERVICE */
if ($search != NULL) {
    $and = "";
} else {
    $y = 1;
    $LIMIT = "LIMIT 0,1";
}

if ($_POST['searchText'] == $CFG_GLPI["ajax_wildcard"]) {    
    $LIMIT = "";
}

/* //SUPRISERVICE */
if (isset($_POST["show_used_mark"]) && $_POST["show_used_mark"]) {
    $left = "LEFT JOIN {$_POST['mark_table']} ON ({$_POST['mark_table']}.`printers_id` = `" . $_POST['table'] . "`.`id`)";
    $mark_active = isset($_POST["mark_active"]) ? " AND {$_POST["mark_active"]} = 1" : "";
    $query = " SELECT DISTINCT type,
            ( SELECT COUNT(*) > 0 FROM {$_POST['mark_table']} WHERE printers_id = `" . $_POST['table'] . "`.`id` {$mark_active} AND `type` = '" . $tipo . "') as used,                
                {$_POST['mark_table']}.`type` as tipo,       
                `" . $_POST['table'] . "`.* ";

} else {
    $query = "SELECT * ";
    $left = " ";
}

//( SELECT type FROM {$_POST['mark_table']} WHERE printers_id = `" . $_POST['table'] . "`.`id` {$mark_active}) as tipo,
$query .= "FROM `" . $_POST['table'] . "`
          $left                     
          $where
          $and
          ORDER BY `name`
          $LIMIT";

$result = $DB->query($query);
//die(var_export($query));

switch($_POST['table']){
    case "glpi_computermodels":
        $_POST['myname'] = "modelo_id";
        break;
    case "glpi_monitormodels":
        $_POST['myname'] = "modelo_id";
        break;
    case "glpi_printermodels":
        $_POST['myname'] = "modelo_id";
        break;
    case "glpi_computertypes":
        $_POST['myname'] = "tipo_id";
        break;
    case "glpi_monitortypes":
        $_POST['myname'] = "tipo_id";
        break;
    case "glpi_printertypes":
        $_POST['myname'] = "tipo_id";
        break;
}

echo "<select id='dropdown_find_num' name='" . $_POST['myname'] . "' size='1'>";

if ($_POST['searchText'] != $CFG_GLPI["ajax_wildcard"] && $DB->numrows($result) == $NBMAX) {
    echo "<option value='0'>--" . $LANG['common'][11] . "--</option>";
}

echo "<option value='0'>" . Dropdown::EMPTY_VALUE . "</option>";


if ($DB->numrows($result)) {
    while ($data = $DB->fetch_array($result)) {

        /* //SUPRISERVICE */
        $output = "";
        if (array_key_exists('used', $data) && $data['used'] > 0) {

            if ($data['tipo'] == $tipo) {
                $output .= "* ";
            }elseif($y == 1){
                $output .= "* ";
            }
        }
        
        $output .= $data['name'];

        if ($_POST['table'] != "glpi_softwares" && !$itemtypeisplugin) {
            if (!empty($data['contact'])) {
                $output .= " - " . $data['contact'];
            }
            if (!empty($data['serial'])) {
                $output .= " - " . $data['serial'];
            }
            if (!empty($data['otherserial'])) {
                $output .= " - " . $data['otherserial'];
            }
        }

        if (empty($output) || $_SESSION['glpiis_ids_visible']) {
            $output .= " (" . $data['id'] . ")";
        }
        echo "<option value='" . $data['id'] . "' title=\"" . Html::cleanInputText($output) . "\">" .
        Toolbox::substr($output, 0, $_SESSION["glpidropdown_chars_limit"]) . "</option>";
    }
}

echo "</select>";

// Auto update summary of active or just solved tickets
$params = array('items_id' => '__VALUE__',
    'itemtype' => $_POST['itemtype']);

Ajax::updateItemOnSelectEvent("dropdown_find_num", "item_ticket_selection_information", $CFG_GLPI["root_doc"] . "/ajax/ticketiteminformation.php", $params);
/* //SUPRISERVICE */
if (array_key_exists("extra_fields", $_POST) && $_POST["extra_fields"]) {
    echo "<table class='tab_cadre' width='800px'>";
    //echo "<br>";
    for ($i = 1; $i <= $_POST["extra_fields"]; $i++) {
        if ($_POST["ef_{$i}_type"] == "separator") {
            echo "<tr><td colspan='2' align='center'>";
            echo "&nbsp;{$_POST["ef_{$i}_label"]}";
            echo "</td></tr>";
            continue;
        }
        echo "<tr><td width='200px'>";
        echo "&nbsp;{$_POST["ef_{$i}_label"]}&nbsp;:&nbsp;";
        echo "</td><td>";
        if ($_POST["ef_{$i}_type"] == "date") {
            Html::showDateTimeFormItem($_POST["ef_{$i}_id"], '', 1, false);
        } else if ($_POST["ef_{$i}_type"] == "dropdown") {
            Agrupamento_Supridesk::getDropdown($_POST["ef_{$i}_id"], $_POST["ef_{$i}_value2"], $_POST["ef_{$i}_value"]);
        } else if ($_POST["ef_{$i}_type"] == "dropdown_printer") {
            $options_replaced = unserialize(str_replace("\\", "", $_POST["ef_{$i}_options"]));
            Ticket::dropdownAllDevices($_POST["ef_{$i}_id"] . "2", 'Printer', 0, 1, 0, 0, $options_replaced);
        } else {
            echo "&nbsp;<input id='{$_POST["ef_{$i}_id"]}' name='{$_POST["ef_{$i}_id"]}' type='{$_POST["ef_{$i}_type"]}' size='{$_POST["ef_{$i}_size"]}' value=''><br>";
        }
        echo "</td></tr>";
    }
    echo "</table>";
}
?>