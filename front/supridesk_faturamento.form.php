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

Session::checkRight("contract", "r");

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

$stk = new Supridesk_Faturamento();

//Add a new computer
if (isset($_POST["add"])) {
    
    switch($_POST["tipo_valor"]){
        case "ticket":
            $_POST['valor_chamado'] = $_POST['valor'];
            break;
        case "imposto":
            $_POST['valor_impostos'] = $_POST['valor'];
            break;
        case "tarifacao":
            $_POST['faturamento_planilha'] = $_POST['valor'];
            break;  
        case "outros":
            $_POST['valor_outros'] = $_POST['valor'];
            break;  
    }
    
    $fiplID = $stk->add($_POST);

   Html::redirect($CFG_GLPI["root_doc"] . '/front/contract.form.php?id='.$_POST['contract_id']);

} else if (isset($_POST["delete"])) {
   $stk->delete($_POST);
   $stk->redirectToList();

} else if (isset($_POST["update"])) {
    
    switch($_POST["tipo_valor"]){
        case "ticket":
            $_POST['valor_chamado'] = $_POST['valor'];
            break;
        case "imposto":
            $_POST['valor_impostos'] = $_POST['valor'];
            break;
        case "tarifacao":
            $_POST['faturamento_planilha'] = $_POST['valor'];
            break;    
        case "outros":
            $_POST['valor_outros'] = $_POST['valor'];
            break;         
    }
    
   $_POST['contract_id'] = $_REQUEST['contract_id'];
    
   $stk->update($_POST);
   Html::redirect($CFG_GLPI["root_doc"] . '/front/contract.form.php?id='.$_POST['contract_id']);

}else if ($_POST['action'] == 'purge') {
    
    foreach($_POST['item'] as $i){
        $stk->delete_faturamento($i);       
    }
    
    Html::back();
}else {
    Html::header($LANG['Menu'][104], $_SERVER['PHP_SELF'], "financial", "contract");
    //show computer form to add
    $stk->showForm($_GET["id"]);
    Html::footer();
}

?>
