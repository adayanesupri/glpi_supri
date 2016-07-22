<?php
/*
 * @version $Id: HEADER 10411 2010-02-09 07:58:26Z moyo $
 -------------------------------------------------------------------------
 GLPI - Gestionnaire Libre de Parc Informatique
 Copyright (C) 2003-2010 by the INDEPNET Development Team.

 http://indepnet.net/   http://glpi-project.org
 -------------------------------------------------------------------------

 LICENSE
Inventaire
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
 along with GLPI; if not, write to the Free Software
 Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 --------------------------------------------------------------------------
 */

// ----------------------------------------------------------------------
// Original Author of file:
// Purpose of file:
// ----------------------------------------------------------------------

// Entry menu case
define('GLPI_ROOT', '../../..');
include (GLPI_ROOT . "/inc/includes.php");

$itemtype = $_GET['itemtype'];

$menu_obj = new PluginMobileMenu;
$menu = $menu_obj->getMenu();

if (isset($_GET['menu']) && isset($_GET['ssmenu'])) {
   $welcome = $menu[$_GET['menu']]['content'][$_GET['ssmenu']]['title'];
   $_SESSION['plugin_mobile']['menu'] = $_GET['menu'];
   $_SESSION['plugin_mobile']['ssmenu'] = $_GET['ssmenu'];
   if (isset($_GET['start'])) $_SESSION['plugin_mobile']['start'] = $_GET['start'];
   else $_SESSION['plugin_mobile']['start'] = 0;
}
else $welcome = "&nbsp;";

$common = new PluginMobileCommon;
$common->displayHeader($welcome, "ss_menu.php?menu=".$_GET['menu'], true);

Search::manageGetValues($itemtype, false);
$numrows = PluginMobileSearch::show(ucfirst($itemtype));

PluginMobileSearch::displayFooterNavBar("search.php?itemtype=".$itemtype
   ."&menu=".$_GET['menu']."&ssmenu=".$_GET['ssmenu'], $numrows);
$common->displayFooter();
?>
