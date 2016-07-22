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

Session::checkRight("stock", "r");

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

$stk = new Stock();

//Add a new computer
if (isset($_POST["add"])) {
    
   if ($newID = $stk->add($_POST)) {
      //Event::log($newID, "computers", 4, "inventory",
        //         $_SESSION["glpiname"]." ".$LANG['log'][20]." ".$_POST["name"].".");
   }
   Html::back();

// delete a computer
} else if (isset($_POST["delete"])) {
   $stk->delete($_POST);
   $stk->redirectToList();

} else if (isset($_POST["update"])) {
    
    if($_POST['itemtype'] == -1){
        unset($_POST['itemtype']);
    }
    
   $stk->update($_POST);
   Html::back();

//Atualiza  novos equipamentos ao estoque 
//SUPRISERVICE
}else if (isset($_POST["add_several"])) {
    $update_qtd = $_POST["to_add"] + $_POST["qtd_atual"];
    $stk->updateEquipamentos($update_qtd,$_POST["tID"]);
    Html::back();

}else if (isset($_POST["add_model"])) {
    
    switch($_POST['tType']){
        case 'Printer':
            $model = $_POST['printermodels_id'];
            $type = $_POST['printertypes_id'];
            break;
        case 'Computer':
            $model = $_POST['computermodels_id'];
            $type = $_POST['computertypes_id'];
            break;
        case 'Monitor':
            $model = $_POST['monitormodels_id'];
            $type = $_POST['monitortypes_id'];
            break;
            
    }
    
    $stk->addTypeModel($_POST['tID'],$model,$type);    
    Html::back();

}else {
    Html::header($LANG['Menu'][104], $_SERVER['PHP_SELF'], "inventory", "stock");
    //show computer form to add
    $stk->showForm($_GET["id"]);
    Html::footer();
}

?>
