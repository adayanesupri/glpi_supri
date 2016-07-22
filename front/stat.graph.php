<?php
/*
 * @version $Id: stat.graph.php 18771 2012-06-29 08:49:19Z moyo $
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
// Original Author of file: Julien Dombre
// Purpose of file:
// ----------------------------------------------------------------------

define('GLPI_ROOT', '..');
include (GLPI_ROOT . "/inc/includes.php");

Html::header($LANG['Menu'][13], $_SERVER['PHP_SELF'], "maintain", "stat");

Session::checkRight("statistic", "1");

$item  = new $_REQUEST['itemtype'];

if (empty($_POST["date1"]) && empty($_POST["date2"])) {
   if (isset($_GET["date1"])) {
      $_POST["date1"] = $_GET["date1"];
   }
   if (isset($_GET["date2"])) {
      $_POST["date2"] = $_GET["date2"];
   }
}

if (!empty($_POST["date1"])
    && !empty($_POST["date2"])
    && strcmp($_POST["date2"],$_POST["date1"]) < 0) {

   $tmp            = $_POST["date1"];
   $_POST["date1"] = $_POST["date2"];
   $_POST["date2"] = $tmp;
}

$cleantarget = preg_replace("/[&]date[12]=[0-9-]*/","",$_SERVER['QUERY_STRING']);
$cleantarget = preg_replace("/[&]*id=([0-9]+[&]{0,1})/","",$cleantarget);
$cleantarget = preg_replace("/&/","&amp;",$cleantarget);

$next  = 0;
$prev  = 0;
$title = "";
$cond  = '';
$parent = 0;

switch($_GET["type"]) {
   case "technicien" :
      $val1   = $_GET["id"];
      $val2   = "";
      $values = Stat::getItems($_REQUEST["itemtype"], $_REQUEST["date1"], $_REQUEST["date2"], $_REQUEST["type"] );
      $title  = $LANG['stats'][16]."&nbsp;: ".$item->getAssignName($_GET["id"], 'User', 1);
      break;

   case "technicien_followup" :
      $val1   = $_GET["id"];
      $val2   = "";
      $values = Stat::getItems($_REQUEST["itemtype"], $_REQUEST["date1"], $_REQUEST["date2"], $_REQUEST["type"] );
      $title  = $LANG['stats'][16]."&nbsp;: ".$item->getAssignName($_GET["id"], 'User', 1);
      break;

   case "enterprise" :
      $val1   = $_GET["id"];
      $val2   = "";
      $values = Stat::getItems($_REQUEST["itemtype"], $_REQUEST["date1"], $_REQUEST["date2"], $_REQUEST["type"] );
      $title  = $LANG['stats'][44]."&nbsp;: ".$item->getAssignName($_GET["id"], 'Supplier', 1);
      break;

   case "user" :
      $val1  = $_GET["id"];
      $val2  = "";
      $values = Stat::getItems($_REQUEST["itemtype"], $_REQUEST["date1"], $_REQUEST["date2"], $_REQUEST["type"] );
      $title = $LANG['stats'][20]."&nbsp;: ".getUserName($_GET["id"],1);
      break;

   case "users_id_recipient" :
      $val1  = $_GET["id"];
      $val2  = "";
      $values = Stat::getItems($_REQUEST["itemtype"], $_REQUEST["date1"], $_REQUEST["date2"], $_REQUEST["type"] );
      $title = $LANG['stats'][20]."&nbsp;: ".getUserName($_GET["id"],1);
      break;

   case "itilcategories_tree" :
      $parent = (isset($_REQUEST['champ']) ? $_REQUEST['champ'] : 0);
      $cond = "(`id`='$parent' OR `itilcategories_id`='$parent')";
      // nobreak;
   case "itilcategories_id" :
      $val1  = $_GET["id"];
      $val2  = "";
      $values = Stat::getItems($_REQUEST["itemtype"], $_REQUEST["date1"], $_REQUEST["date2"], $_REQUEST["type"], $parent );
      $title = $LANG['common'][36]."&nbsp;: ".Dropdown::getDropdownName("glpi_itilcategories",
                                                                        $_GET["id"]);
      break;

   case "type" :
      $val1 = $_GET["id"];
      $val2 = "";
      $values = Stat::getItems($_REQUEST["itemtype"], $_REQUEST["date1"], $_REQUEST["date2"], $_REQUEST["type"] );
      $title = $LANG['common'][17]."&nbsp;: ".Ticket::getTicketTypeName($_GET["id"]);
      break;

   case 'group_tree' :
   case 'groups_tree_assign' :
      $parent = (isset($_REQUEST['champ']) ? $_REQUEST['champ'] : 0);
      $cond = "(`id`='$parent' OR `groups_id`='$parent') AND ".
              ($_GET["type"]=='group_tree' ? '`is_requester`' : '`is_assign`');
      // nobreak;
   case "group" :
      $val1  = $_GET["id"];
      $val2  = "";
      $values = Stat::getItems($_REQUEST["itemtype"], $_REQUEST["date1"], $_REQUEST["date2"], $_REQUEST["type"], $parent);
      $title = $LANG['common'][35]."&nbsp;: ".Dropdown::getDropdownName("glpi_groups", $_GET["id"]);
      break;

   case "groups_id_assign" :
      $val1  = $_GET["id"];
      $val2  = "";
      $values = Stat::getItems($_REQUEST["itemtype"], $_REQUEST["date1"], $_REQUEST["date2"], $_REQUEST["type"] );
      $title = $LANG['common'][35]."&nbsp;: ".Dropdown::getDropdownName("glpi_groups", $_GET["id"]);
      break;

   case "priority" :
      $val1 = $_GET["id"];
      $val2 = "";
      $values = Stat::getItems($_REQUEST["itemtype"], $_REQUEST["date1"], $_REQUEST["date2"], $_REQUEST["type"] );
      $title = $LANG['joblist'][2]."&nbsp;: ".$item->getPriorityName($_GET["id"]);
      break;

   case "urgency" :
      $val1 = $_GET["id"];
      $val2 = "";
      $values = Stat::getItems($_REQUEST["itemtype"], $_REQUEST["date1"], $_REQUEST["date2"], $_REQUEST["type"] );
      $title = $LANG['joblist'][29]."&nbsp;: ".$item->getUrgencyName($_GET["id"]);
      break;

   case "impact" :
      $val1 = $_GET["id"];
      $val2 = "";
      $values = Stat::getItems($_REQUEST["itemtype"], $_REQUEST["date1"], $_REQUEST["date2"], $_REQUEST["type"] );
      $title = $LANG['joblist'][30]."&nbsp;: ".$item->getImpactName($_GET["id"]);
      break;

   case "usertitles_id" :
      $val1  = $_GET["id"];
      $val2  = "";
      $values = Stat::getItems($_REQUEST["itemtype"], $_REQUEST["date1"], $_REQUEST["date2"], $_REQUEST["type"] );
      $title = $LANG['users'][1]."&nbsp;: ".Dropdown::getDropdownName("glpi_usertitles",
                                                                      $_GET["id"]);
      break;

   case "solutiontypes_id" :
      $val1  = $_GET["id"];
      $val2  = "";
      $values = Stat::getItems($_REQUEST["itemtype"], $_REQUEST["date1"], $_REQUEST["date2"], $_REQUEST["type"] );
      $title = $LANG['job'][48]."&nbsp;: ".Dropdown::getDropdownName("glpi_solutiontypes",
                                                                      $_GET["id"]);
      break;

   case "usercategories_id" :
      $val1  = $_GET["id"];
      $val2  = "";
      $values = Stat::getItems($_REQUEST["itemtype"], $_REQUEST["date1"], $_REQUEST["date2"], $_REQUEST["type"] );
      $title = $LANG['users'][2]."&nbsp;: ".Dropdown::getDropdownName("glpi_usercategories",
                                                                      $_GET["id"]);
      break;

   case "requesttypes_id" :
      $val1 = $_GET["id"];
      $val2 = "";
      $values = Stat::getItems($_REQUEST["itemtype"], $_REQUEST["date1"], $_REQUEST["date2"], $_REQUEST["type"] );
      $title = $LANG['job'][44]."&nbsp;: ".Dropdown::getDropdownName('glpi_requesttypes',
                                                                     $_GET["id"]);
      break;

   case "device" :
      $val1 = $_GET["id"];
      $val2 = $_GET["champ"];
      $item = new $_GET["champ"]();
      $device_table = $item->getTable();
      $values = Stat::getItems($_REQUEST["itemtype"], $_REQUEST["date1"], $_REQUEST["date2"], $_REQUEST["champ"] );
      $query = "SELECT `designation`
                FROM `".$device_table."`
                WHERE `id` = '".$_GET['id']."'";
      $result = $DB->query($query);

      $title = $item->getTypeName()."&nbsp;: ".$DB->result($result,0,"designation");
      break;

   case "comp_champ" :
      $val1  = $_GET["id"];
      $val2  = $_GET["champ"];
      $values = Stat::getItems($_REQUEST["itemtype"], $_REQUEST["date1"], $_REQUEST["date2"], $_REQUEST["champ"] );
      $item  = new $_GET["champ"]();
      $table = $item->getTable();
      $title = $item->getTypeName()."&nbsp;: ".Dropdown::getDropdownName($table, $_GET["id"]);
      break;
}

// Found next and prev items 
$foundkey = -1;
foreach ($values as $key => $val) {
   if ($val['id'] == $_GET["id"]) {
      $foundkey = $key; 
   }
}

if ($foundkey>=0) {
   if (isset($values[$foundkey+1])) {
      $next = $values[$foundkey+1]['id'];
   }
   if (isset($values[$foundkey-1])) {
      $prev = $values[$foundkey-1]['id'];
   }
}

echo "<div align='center'>";
echo "<table class='tab_cadre_navigation'>";
echo "<tr><td>";
if ($prev > 0) {
   echo "<a href=\"".$_SERVER['PHP_SELF']."?$cleantarget&amp;date1=".$_POST["date1"]."&amp;date2=".
          $_POST["date2"]."&amp;id=$prev\">
          <img src='".$CFG_GLPI["root_doc"]."/pics/left.png' alt=\"".$LANG['buttons'][12]."\"
           title=\"".$LANG['buttons'][12]."\"></a>";
}
echo "</td>";

echo "<td width='400' class='center b'>$title</td>";
echo "<td>";
if ($next > 0) {
   echo "<a href=\"".$_SERVER['PHP_SELF']."?$cleantarget&amp;date1=".$_POST["date1"]."&amp;date2=".
          $_POST["date2"]."&amp;id=$next\">
          <img src='".$CFG_GLPI["root_doc"]."/pics/right.png' alt=\"".$LANG['buttons'][11]."\"
           title=\"".$LANG['buttons'][11]."\"></a>";
}
echo "</td>";
echo "</tr>";
echo "</table></div><br>";

$target = preg_replace("/&/","&amp;",$_SERVER["REQUEST_URI"]);

echo "<form method='post' name='form' action='$target'><div class='center'>";
echo "<table class='tab_cadre'>";
echo "<tr class='tab_bg_2'><td class='right'>".$LANG['search'][8]."&nbsp;: </td><td>";
Html::showDateFormItem("date1", $_POST["date1"]);
echo "</td><td rowspan='2' class='center'>";
echo "<input type='hidden' name='itemtype' value=\"".$_REQUEST['itemtype']."\">";
echo "<input type='submit' class='button' value=\"".$LANG['buttons'][7]."\"></td></tr>";

echo "<tr class='tab_bg_2'><td class='right'>".$LANG['search'][9]."&nbsp;: </td><td>";
Html::showDateFormItem("date2", $_POST["date2"]);
echo "</td></tr>";
echo "</table></div>";




$show_all = false;
if (!isset($_REQUEST['graph']) || count($_REQUEST['graph'])==0) {
   $show_all = true;
}


///////// Stats nombre intervention
// Total des interventions
$values['total']  = Stat::constructEntryValues($_REQUEST['itemtype'], "inter_total", $_REQUEST["date1"],
                                               $_REQUEST["date2"], $_GET["type"], $val1, $val2);
// Total des interventions résolues
$values['solved'] = Stat::constructEntryValues($_REQUEST['itemtype'], "inter_solved", $_REQUEST["date1"],
                                               $_REQUEST["date2"], $_GET["type"], $val1, $val2);
// Total des interventions closes
$values['closed'] = Stat::constructEntryValues($_REQUEST['itemtype'], "inter_closed", $_REQUEST["date1"],
                                               $_REQUEST["date2"], $_GET["type"], $val1, $val2);
// Total des interventions closes
$values['late']   = Stat::constructEntryValues($_REQUEST['itemtype'], "inter_solved_late", $_REQUEST["date1"],
                                               $_REQUEST["date2"], $_GET["type"], $val1, $val2);

$available = array('total'  => $LANG['job'][14],
                   'solved' => $LANG['job'][15],
                   'late'   => $LANG['job'][17],
                   'closed' => $LANG['job'][16],);
echo "<div class='center'>";

foreach ($available as $key => $name) {
   echo "<input type='checkbox' onchange='submit()' name='graph[$key]' ".
          ($show_all||isset($_REQUEST['graph'][$key])?"checked":"")."> ".$name."&nbsp;";
}
echo "</div>";

$toprint = array();
foreach ($available as $key => $name) {
   if ($show_all || isset($_REQUEST['graph'][$key])) {
      $toprint[$name] = $values[$key];
   }
}

Stat::showGraph($toprint, array('title'     => $LANG['stats'][13],
                                'showtotal' => 1,
                                'unit'      => $LANG['stats'][35]));

//Temps moyen de resolution d'intervention
$values2['avgsolved']     = Stat::constructEntryValues($_REQUEST['itemtype'], "inter_avgsolvedtime", $_REQUEST["date1"],
                                                       $_REQUEST["date2"], $_GET["type"], $val1,
                                                       $val2);
// Pass to hour values
foreach ($values2['avgsolved'] as $key => $val) {
   $values2['avgsolved'][$key] /= HOUR_TIMESTAMP;
}
//Temps moyen de cloture d'intervention
$values2['avgclosed']     = Stat::constructEntryValues($_REQUEST['itemtype'], "inter_avgclosedtime", $_REQUEST["date1"],
                                                       $_REQUEST["date2"], $_GET["type"], $val1,
                                                       $val2);
// Pass to hour values
foreach ($values2['avgclosed'] as $key => $val) {
   $values2['avgclosed'][$key] /= HOUR_TIMESTAMP;
}
//Temps moyen d'intervention reel
$values2['avgactiontime'] = Stat::constructEntryValues($_REQUEST['itemtype'], "inter_avgactiontime", $_REQUEST["date1"],
                                                       $_REQUEST["date2"], $_GET["type"], $val1,
                                                       $val2);
// Pass to hour values
foreach ($values2['avgactiontime'] as $key => $val) {
   $values2['avgactiontime'][$key] /= HOUR_TIMESTAMP;
}


$available = array('avgclosed'     => $LANG['stats'][10],
                   'avgsolved'     => $LANG['stats'][9],
                   'avgactiontime' => $LANG['stats'][14]);


if ($_REQUEST['itemtype'] == 'Ticket') {
   $available['avgtaketime'] = $LANG['stats'][12];
   //Temps moyen de prise en compte de l'intervention
   $values2['avgtaketime']   = Stat::constructEntryValues($_REQUEST['itemtype'], "inter_avgtakeaccount", $_REQUEST["date1"],
                                                         $_REQUEST["date2"], $_GET["type"], $val1,
                                                         $val2);
   // Pass to hour values
   foreach ($values2['avgtaketime'] as $key => $val) {
      $values2['avgtaketime'][$key] /= HOUR_TIMESTAMP;
   }
}




echo "<div class='center'>";

$show_all2 = false;
if (!isset($_REQUEST['graph2']) || count($_REQUEST['graph2'])==0) {
   $show_all2 = true;
}

foreach ($available as $key => $name) {
   echo "<input type='checkbox' onchange='submit()' name='graph2[$key]' ".
          ($show_all2||isset($_REQUEST['graph2'][$key])?"checked":"")."> ".$name."&nbsp;";
}
echo "</div>";

$toprint = array();
foreach ($available as $key => $name) {
   if ($show_all2 || isset($_REQUEST['graph2'][$key])) {
      $toprint[$name] = $values2[$key];
   }
}

Stat::showGraph($toprint, array('title'     => $LANG['stats'][8],
                                'unit'      => Toolbox::ucfirst($LANG['gmt'][1]),
                                'showtotal' => 1,
                                'datatype'  => 'average'));


if ($_REQUEST['itemtype'] == 'Ticket') {
   ///////// Satisfaction
   $values['opensatisfaction']   = Stat::constructEntryValues($_REQUEST['itemtype'], "inter_opensatisfaction",
                                                            $_REQUEST["date1"], $_REQUEST["date2"],
                                                            $_GET["type"], $val1, $val2);

   $values['answersatisfaction'] = Stat::constructEntryValues($_REQUEST['itemtype'], "inter_answersatisfaction",
                                                            $_REQUEST["date1"], $_REQUEST["date2"],
                                                            $_GET["type"], $val1, $val2);


   $available = array('opensatisfaction'   => $LANG['satisfaction'][13],
                     'answersatisfaction' => $LANG['satisfaction'][14]);
   echo "<div class='center'>";

   foreach ($available as $key => $name) {
      echo "<input type='checkbox' onchange='submit()' name='graph[$key]' ".
            ($show_all||isset($_REQUEST['graph'][$key])?"checked":"")."> ".$name."&nbsp;";
   }
   echo "</div>";

   $toprint = array();
   foreach ($available as $key => $name) {
      if ($show_all || isset($_REQUEST['graph'][$key])) {
         $toprint[$name] = $values[$key];
      }
   }

   Stat::showGraph($toprint, array('title'     => $LANG['satisfaction'][3],
                                 'showtotal' => 1,
                                 'unit'      => $LANG['stats'][35]));

   $values['avgsatisfaction'] = Stat::constructEntryValues($_REQUEST['itemtype'], "inter_avgsatisfaction", $_REQUEST["date1"],
                                                         $_REQUEST["date2"], $_GET["type"], $val1,
                                                         $val2);

   $available = array('avgsatisfaction' => $LANG['satisfaction'][7]);
   echo "<div class='center'>";

   foreach ($available as $key => $name) {
      echo "<input type='checkbox' onchange='submit()' name='graph[$key]' ".
            ($show_all||isset($_REQUEST['graph'][$key])?"checked":"")."> ".$name."&nbsp;";
   }
   echo "</div>";

   $toprint = array();
   foreach ($available as $key => $name) {
      if ($show_all || isset($_REQUEST['graph'][$key])) {
         $toprint[$name] = $values[$key];
      }
   }

   Stat::showGraph($toprint, array('title' => $LANG['satisfaction'][7]));

}
Html::closeForm();
Html::footer();
?>