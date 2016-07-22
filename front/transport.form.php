<?php
/*
 * @version $Id: computer.form.php 17307 2012-01-31 13:10:42Z yllen $
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

define('GLPI_ROOT', '..');
include (GLPI_ROOT . "/inc/includes.php");

Session::checkRight("transport", "r");

if (!isset($_GET["id"])) {
   $_GET["id"] = "";
}

if (!isset($_GET["sort"])) {
   $_GET["sort"] = "";
}

if (!isset($_GET["order"])) {
   $_GET["order"] = "";
}

if (!isset($_GET["withtemplate"])) {
   $_GET["withtemplate"] = "";
}

$transp = new Transport();

//Add a new computer
if (isset($_POST["add"])) {
   if ($newID = $transp->add($_POST)) {
      //Event::log($newID, "computers", 4, "inventory",
        //         $_SESSION["glpiname"]." ".$LANG['log'][20]." ".$_POST["name"].".");
   }
   Html::back();

// delete a computer
} else if (isset($_POST["delete"])) {
   $transp->delete($_POST);
   $transp->redirectToList();

} else if (isset($_POST["update"])) {
   $transp->update($_POST);
   Html::back();

}else {    
    
    Html::header($LANG['Menu'][105], $_SERVER['PHP_SELF'], "inventory", "transport");
    //show computer form to add
    $transp->showForm($_GET["id"]);
    Html::footer();
}


?>
