<?php

/*
   ------------------------------------------------------------------------
   FusionInventory
   Copyright (C) 2010-2012 by the FusionInventory Development Team.

   http://www.fusioninventory.org/   http://forge.fusioninventory.org/
   ------------------------------------------------------------------------

   LICENSE

   This file is part of FusionInventory project.

   FusionInventory is free software: you can redistribute it and/or modify
   it under the terms of the GNU Affero General Public License as published by
   the Free Software Foundation, either version 3 of the License, or
   (at your option) any later version.

   FusionInventory is distributed in the hope that it will be useful,
   but WITHOUT ANY WARRANTY; without even the implied warranty of
   MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
   GNU Affero General Public License for more details.

   You should have received a copy of the GNU Affero General Public License
   along with Behaviors. If not, see <http://www.gnu.org/licenses/>.

   ------------------------------------------------------------------------

   @package   FusionInventory
   @author    Vincent Mazzoni
   @co-author David Durieux
   @copyright Copyright (c) 2010-2012 FusionInventory team
   @license   AGPL License 3.0 or (at your option) any later version
              http://www.gnu.org/licenses/agpl-3.0-standalone.html
   @link      http://www.fusioninventory.org/
   @link      http://forge.fusioninventory.org/projects/fusioninventory-for-glpi/
   @since     2010
 
   ------------------------------------------------------------------------
 */

ob_start();
ini_set("memory_limit", "-1");
ini_set("max_execution_time", "0");
ini_set('display_errors', 1);

if (!defined('GLPI_ROOT')) {
   define('GLPI_ROOT', '../../..');
}

if (session_id()=="") {
   session_start();
}

$_SESSION['glpi_use_mode'] = 2;
include_once(GLPI_ROOT."/inc/includes.php");
if (!isset($_SESSION['glpilanguage'])) {
   $_SESSION['glpilanguage'] = 'fr_FR';
}
   
ini_set('display_errors','On');
error_reporting(E_ALL | E_STRICT);
set_error_handler(array('Toolbox','userErrorHandlerDebug'));
$_SESSION['glpi_use_mode'] = 2;

ob_end_clean();
header("server-type: glpi/fusioninventory ".PLUGIN_FUSIONINVENTORY_VERSION);
if (!class_exists("PluginFusioninventoryConfig")) {
   header("Content-Type: application/xml");
   echo "<?xml version='1.0' encoding='UTF-8'?>
<REPLY>
   <ERROR>Plugin FusionInventory not installed!</ERROR>
</REPLY>";
   session_destroy();
   exit();
}

if (isset($_GET['action']) && isset($_GET['machineid'])) {
   PluginFusioninventoryFusionCommunication::managecommunication();
} else if (isset($GLOBALS["HTTP_RAW_POST_DATA"])) {
   PluginFusioninventoryOCSCommunication::managecommunication();
}

session_destroy();

?>