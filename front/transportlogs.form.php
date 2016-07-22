<?php
/*
 * @version $Id: networkport.form.php 18718 2012-06-26 06:51:50Z moyo $
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

$fipl  = new TransportLogs();

//var_export($_SESSION);
//die();

if (isset($_POST["add"]))
{
    
    $_POST['user'] = $_SESSION['glpiname'];
    
    $ultima_km = $fipl->confere_($_POST['veiculo_id']);
    
    $saida = $fipl->confere_checkin_saida($_POST['veiculo_id'],$_POST['user']);
    $saida_other = $fipl->confere_checkin_saida_other($_POST['veiculo_id'],$_POST['user']);
    if($_POST['km'] < $ultima_km){
        Session::addMessageAfterRedirect("A kilometragem está menor que o ultimo registro.", true, ERROR);
        Html::redirect($CFG_GLPI["root_doc"] . '/front/transport.form.php?id='.$_POST['veiculo_id']);
    }
    
    
    
    if($_POST['type_checkin'] == 1){
        if($saida == true || $saida_other == true){
            Session::addMessageAfterRedirect("Existe uma saída em aberto. Favor verificar..", true, ERROR);
            Html::redirect($CFG_GLPI["root_doc"] . '/front/transport.form.php?id='.$_POST['veiculo_id']);
        }
        
        $dados_entrada = $fipl->confere_checkin_entrada($_POST['veiculo_id']);
       
        if($dados_entrada['km'] <> $_POST['km']){
            Session::addMessageAfterRedirect("A saída está com km diferente da última chegada. Favor verificar..", true, ERROR);
            Html::redirect($CFG_GLPI["root_doc"] . '/front/transport.form.php?id='.$_POST['veiculo_id']);
            
        }
    }
    
    if($_POST['type_checkin'] == 0){
        $dados_saida = $fipl->confere_checkin($_POST['veiculo_id'],$_POST['user']);      
        
        if($dados_saida == false){
            Session::addMessageAfterRedirect("Não existe nenhum checkin de saída. Favor verificar..", true, ERROR);
            Html::redirect($CFG_GLPI["root_doc"] . '/front/transport.form.php?id='.$_POST['veiculo_id']);
        }
        
        if($dados_saida['km'] > $_POST['km']){            
            Session::addMessageAfterRedirect("Kilometragem de chegada menor que a de saída.", true, ERROR);
            Html::redirect($CFG_GLPI["root_doc"] . '/front/transport.form.php?id='.$_POST['veiculo_id']);            
        }   
        
        if($dados_saida['printerlogkm_date'] > $_POST['printerlogkm_date']){            
            Session::addMessageAfterRedirect("A data de chegada é menor que a data de saída.", true, ERROR);
            Html::redirect($CFG_GLPI["root_doc"] . '/front/transport.form.php?id='.$_POST['veiculo_id']);            
        }   
        
        $fipl->update_checkin($dados_saida['id']);
    }

	$fiplID = $fipl->add($_POST);

	Html::redirect($CFG_GLPI["root_doc"].'/front/transport.form.php?id='.$_POST['veiculo_id']);
} else if (isset($_POST["update"]))
{
   $fipl->check($_POST['id'],'w');	

   $fipl->update_($_POST['km'],$_POST['type_checkin'],$_POST['printerlogkm_date'],$_POST['id'],$_POST['obs']);
   Html::back();

}  else if (isset($_POST["delete"])){
    
   // var_export('teste');
   // die();
    
   $fipl->delete($_POST);   
   Html::redirect($CFG_GLPI["root_doc"].'/front/transport.php');
} else if ($_POST['action'] == 'purge') {
    
    foreach($_POST['item'] as $i){
        $fipl->delete_printerlogs($i);
       
    }
    
    Html::back();
}
else
{
   if (empty($_GET["id"])) {
      $_GET["id"] = "";
   }
  
    $_GET["itemtype"] = "Transport";
    //var_export($_GET);

   Session::checkRight("transport", "w");
   Html::header($LANG['tarifacao'][17],$_SERVER['PHP_SELF'],"inventary","transport");

   $fipl->showForm($_GET["id"], $_GET);
   Html::footer();
}
?>