<?php

/*
 * @version $Id: notificationmailsetting.tabs.php 11726 2010-06-15 19:25:06Z tsmr $
 -------------------------------------------------------------------------
 GLPI - Gestionnaire Libre de Parc Informatique
 Copyright (C) 2003-2010 by the INDEPNET Development Team.

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
 along with GLPI; if not, write to the Free Software
 Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 --------------------------------------------------------------------------
 */

// ----------------------------------------------------------------------
// Original Author of file:
// Purpose of file:
// ----------------------------------------------------------------------

define('GLPI_ROOT', '..');
include (GLPI_ROOT . "/inc/includes.php");
header("Content-Type: text/html; charset=UTF-8");
header_nocache();

checkRight("config",'r');
if (!isset($_POST['id'])) {
   exit();
}
if (!isset($_REQUEST['glpi_tab'])) {
   exit();
}

if ($_POST['id'] > 0) {
   $target = new NotificationMailSetting();
   switch($_REQUEST['glpi_tab']) {
      case -1 :
         $target->showFormMailServerConfig();
         if ($CFG_GLPI['use_mailing']) {
            $target->showFormAlerts();
            Plugin::displayAction($target,$_REQUEST['glpi_tab']);
         }
         break;

      case 1 :
         $target->showFormMailServerConfig();
         break;

      case 2 :
         if ($CFG_GLPI['use_mailing']) {
            $target->showFormAlerts();
         }
         break;

      default :
         if (!Plugin::displayAction($target,$_REQUEST['glpi_tab'])) {
         }
   }
}

ajaxFooter();

?>
