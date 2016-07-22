<?php
/*
 * @version $Id: report.networking.php 18771 2012-06-29 08:49:19Z moyo $
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
/*!
  \brief affiche les diffents choix de rapports reseaux
 */

define('GLPI_ROOT', '..');
include (GLPI_ROOT . "/inc/includes.php");

Session::checkRight("reports", "r");

Html::header($LANG['Menu'][6],$_SERVER['PHP_SELF'],"utils","report");

if (empty($_POST["tarifacao1"]) && empty($_POST["tarifacao2"])) {
   $month = date("m")-1;
   $_POST["tarifacao1"] = date("Y-m-d",mktime(1,0,0,$month,date("d"),date("Y")));
   $_POST["tarifacao2"] = date("Y-m-d");
}

if (empty($_POST["bilhetagem1"]) && empty($_POST["bilhetagem2"])) {
   $month = date("m")-1;
   $_POST["bilhetagem1"] = date("Y-m-d",mktime(1,0,0,$month,date("d"),date("Y")));
   $_POST["bilhetagem2"] = date("Y-m-d");
}

if (!empty($_POST["tarifacao1"]) && !empty($_POST["tarifacao2"]) && strcmp($_POST["tarifacao2"], $_POST["tarifacao1"])<0) {
   $tmp = $_POST["tarifacao1"];
   $_POST["tarifacao1"] = $_POST["tarifacao2"];
   $_POST["tarifacao2"] = $tmp;
}

if (!empty($_POST["bilhetagem1"]) && !empty($_POST["bilhetagem2"]) && strcmp($_POST["bilhetagem2"], $_POST["bilhetagem1"])<0) {
   $tmp = $_POST["bilhetagem1"];
   $_POST["bilhetagem1"] = $_POST["bilhetagem2"];
   $_POST["bilhetagem2"] = $tmp;
}

Report::title();

# Titre

echo "<table class='tab_cadre' >";
echo "<tr><th colspan='4'>&nbsp;".$LANG['tarifacao'][40]."&nbsp;</th></tr>";
echo "</table><br>";

// 3. Selection d'affichage pour generer la liste

echo "<form name='form' method='post' action='report.tarifacao.result.php'>";
echo "<table class='tab_cadre' width='500'>";

echo "<tr class='tab_bg_1'>";
echo "<td width='120'>".$LANG['tarifacao'][41]." : </td>";
echo "<td colspan='3'>";
	$entities = array_reverse(getSonsOf("glpi_entities", $_SESSION["glpiactive_entity"]));
	Dropdown::show('Contract', array('entity' => $entities));
echo "</td>";
echo "</tr>";

echo "<tr class='tab_bg_1'>";
echo "<td class='center' colspan=4>";
echo "Período de Locação";
echo "</td>";
echo "</tr>";

echo "<tr class='tab_bg_1'>";
echo "<td width='120'>".$LANG['search'][8]." : </td>";
echo "<td class='center'>";
Html::showDateFormItem("tarifacao1", $_POST["tarifacao1"]);
echo "</td>";

echo "<td width='120'>".$LANG['search'][9]." : </td>";
echo "<td class='center'>";
Html::showDateFormItem("tarifacao2", $_POST["tarifacao2"]);
echo "</td>";
echo "</tr>";

echo "<tr class='tab_bg_1'>";
echo "<td class='center' colspan=4>";
echo "Período de Bilhetagem";
echo "</td>";
echo "</tr>";

echo "<tr class='tab_bg_1'>";
echo "<td width='120'>".$LANG['search'][8]." : </td>";
echo "<td class='center'>";
Html::showDateFormItem("bilhetagem1", $_POST["bilhetagem1"]);
echo "</td>";

echo "<td width='120'>".$LANG['search'][9]." : </td>";
echo "<td class='center'>";
Html::showDateFormItem("bilhetagem2", $_POST["bilhetagem2"]);
echo "</td>";
echo "</tr>";

echo "<tr class='tab_bg_1'>";
echo "<td colspan='4' class='center' width='120'>";
echo "<input type='submit' value=\"".$LANG['tarifacao'][42]."\" class='submit'>";
echo "</td>";
echo "</tr>";
echo "</table>";
Html::closeForm();

Html::footer();
?>