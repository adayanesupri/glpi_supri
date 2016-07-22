<?php

/*
 * @version $Id: cartridgeitem.form.php 17152 2012-01-24 11:22:16Z moyo $
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

Session::checkRight("cartridge", "r");

if (!isset($_GET["id"])) {
    $_GET["id"] = "";
}

$cartype = new CartridgeItem();

if (isset($_POST["add"])) {
    $cartype->check(-1, 'w', $_POST);

    /* //SUPRISERVICE */
    $_POST['previsao_min'] = intval($_POST['previsao_min']);
    $_POST['previsao_max'] = intval($_POST['previsao_max']);
    
    if ($newID = $cartype->add($_POST)) {
        Event::log($newID, "cartridges", 4, "inventory", $_SESSION["glpiname"] . " " . $LANG['log'][20] . " " . $_POST["name"] . ".");
        
        $changes[0] = $newID;
        $changes[1] = '';
        $changes[2] = "criado cartucho '{$_POST['name']}'.";
        Log::history($newID, 'CartridgeItem', $changes, 'CartridgeItem', Log::HISTORY_LOG_SIMPLE_MESSAGE);
        
    }
    Html::back();
} else if (isset($_POST["delete"])) {
    $cartype->check($_POST["id"], 'w');

    if ($cartype->delete($_POST)) {
        Event::log($_POST["id"], "cartridges", 4, "inventory", $_SESSION["glpiname"] . " " . $LANG['log'][22]);
    }
    $cartype->redirectToList();
} else if (isset($_POST["restore"])) {
    $cartype->check($_POST["id"], 'w');

    if ($cartype->restore($_POST)) {
        Event::log($_POST["id"], "cartridges", 4, "inventory", $_SESSION["glpiname"] . " " . $LANG['log'][23]);
    }
    $cartype->redirectToList();
} else if (isset($_POST["purge"])) {
    $cartype->check($_POST["id"], 'w');

    if ($cartype->delete($_POST, 1)) {
        Event::log($_POST["id"], "cartridges", 4, "inventory", $_SESSION["glpiname"] . " " . $LANG['log'][24]);
    }
    $cartype->redirectToList();
} else if (isset($_POST["update"])) {

    if ($_POST["comment"] != $_POST["comment_compare"]) {
        $_POST["notepad"] = $_POST["comment"];
    }

    $cartype->check($_POST["id"], 'w');

    if ($cartype->update($_POST)) {
        Event::log($_POST["id"], "cartridges", 4, "inventory", $_SESSION["glpiname"] . " " . $LANG['log'][21]);
    
        //$changes[0] = $_POST["id"];
        //$changes[1] = '';
        //$changes[2] = "adicionado$ext {$_POST["to_add"]} consumíve$ext2 novos.";
        //Log::history($_POST["id"], 'CartridgeItem', $changes, 'CartridgeItem', Log::HISTORY_LOG_SIMPLE_MESSAGE);

    }
    Html::back();
} else if (isset($_POST["addtype"])) {
    $cartype->check($_POST["tID"], 'w');

    if ($cartype->addCompatibleType($_POST["tID"], $_POST["printermodels_id"])) {
        Event::log($_POST["tID"], "cartridges", 4, "inventory", $_SESSION["glpiname"] . " " . $LANG['log'][30]);
    }
    Html::back();
} else if (isset($_GET["deletetype"])) {
    $cartype->check($_GET["tID"], 'w');

    if ($cartype->deleteCompatibleType($_GET["id"])) {
        Event::log($_GET["tID"], "cartridges", 4, "inventory", $_SESSION["glpiname"] . " " . $LANG['log'][31]);
    }
    Html::back();

    /* //SUPRISERVICE */
} else if (isset($_POST["addCartuchoR"])) {

    $cartype->check($_POST["cartridgeitems_id"], 'w');

    if ($cartype->addCartuchoAnalistaReserva($_POST["analistaAddCartridge"], $_POST["cartridgeitems_id"], $_POST["uID"])) {
        Event::log($_POST["tID"], "cartridges", 4, "inventory", $_SESSION["glpiname"] . " " . $LANG['log'][30]);
    }
    Html::back();
} else if (isset($_POST["addCartuchoC"])) {    
    $cartype->check($_POST["cartridgeitems_id"], 'w');
    
    //var_export($_POST);
   // die();
    if ($cartype->addCartuchoAnalista($_POST["analistaAddCartridge"], $_POST["cartridgeitems_id"], $_POST["uID"])) {
        Event::log($_POST["tID"], "cartridges", 4, "inventory", $_SESSION["glpiname"] . " " . $LANG['log'][30]);
    }
    Html::back();
} else if (isset($_POST["addEquipamentoR"])) {
    if($_POST[0] == 'Phone' || $_POST[0] == 'PluginProjetProjet' || $_POST[0] == 'Transport' || 
            $_POST[0] == 'ConsumableItem' || $_POST[0] == 'Document'){
        
        Session::addMessageAfterRedirect("Não é possível fazer reserva para este tipo de Equipamento.", true, ERROR);
        Html::back();
        
    }
    
    switch ($_POST[0]) {
        case 'Computer':
            $tabela = 'glpi_computers';
            break;
        case 'Monitor':
            $tabela = 'glpi_monitors';
            break;
        case 'NetworkEquipment':
            $tabela = 'glpi_networkequipments';
            break;
        case 'Peripheral':
            $tabela = 'glpi_peripherals';
            break;
        case 'Printer':
            $tabela = 'glpi_printers';
            break;  
        case 'Stock':           
            $tabela = 'supridesk_pecas';
            break; 
    }
    
    
    $class = new Reservas_Equipamentos(); 
    
    if ($class->addReserva($tabela,$_POST['items_id'],$_POST["uID"])){        
        Session::addMessageAfterRedirect("Realizada a reserva de Equipamento.");
                 
    }else{
        Session::addMessageAfterRedirect("Equipamento já está em reserva.", true, ERROR);
    }
    
    Html::back();
     
}else if (isset($_POST["addEquipamentoC"])){
    
    if($_POST[0] == 'Phone' || $_POST[0] == 'PluginProjetProjet' || $_POST[0] == 'Transport' || 
            $_POST[0] == 'ConsumableItem' || $_POST[0] == 'Document'){
        
        Session::addMessageAfterRedirect("Não é possível fazer reserva de chamado para este tipo de Equipamento.", true, ERROR);
        Html::back();        
    }
    
    $found = $cartype->foundTicket($_POST['chamado_equip']);
    if($found == false){
        Session::addMessageAfterRedirect("Chamado não encontrado. Favor verificar.", true, ERROR);
        Html::back();
    }
    
    switch ($_POST[0]) {
        case 'Computer':
            $tabela = 'glpi_computers';
            break;
        case 'Monitor':
            $tabela = 'glpi_monitors';
            break;
        case 'NetworkEquipment':
            $tabela = 'glpi_networkequipments';
            break;
        case 'Peripheral':
            $tabela = 'glpi_peripherals';
            break;
        case 'Printer':
            $tabela = 'glpi_printers';           
            break;   
        case 'Stock':
            $tabela = 'supridesk_pecas';
            break;
    }
    
        
    $class = new Reservas_Equipamentos();    
    
    
    if ($equipamento = $class->addReservaChamado($tabela,$_POST['items_id'],$_POST["uID"],$_POST['chamado_equip'])){
        
        $usr = new User();
        $name = $usr->getNameUser($_POST["uID"]);
        
        $fup = new TicketFollowup();
        $_dataFUP = array("content" => "O equipamento {$equipamento} foi entregue ao analista {$name} e está em trânsito.",
            "tickets_id" => $_POST['chamado_equip'],
            "is_private" => 1
        );
        $fup->add($_dataFUP);
        
        $class->setStatus($tabela,$_POST['items_id'], 10); //Coloca no status em transito
        
        Session::addMessageAfterRedirect("Realizada a reserva de chamado do Equipamento.");
                 
    }else{
        Session::addMessageAfterRedirect("Equipamento já está em reserva.", true, ERROR);
    }
    
    Html::back();
    
    
    
}else if (isset($_GET["deleteequip"])) {    
    
        
    switch ($_GET['type']){
        case 'printer':
            $tabela = 'glpi_printers';
            $log = "exclusão de um impressora";   
            break;
        case 'computer':
            $tabela = 'glpi_computers';
            $log = "exclusão de um computador.";   
            break;
        case 'monitor':
            $tabela = 'glpi_monitors';
            $log = "exclusão de um monitor.";   
            break;
        case 'networkequipment':
            $tabela = 'glpi_networkequipments';
            $log = "exclusão de um periférico de rede.";   
            break;
        case 'peripheral':
            $tabela = 'glpi_peripherals';            
            $log = "exclusão de um periférico.";   
            break;
         case 'stock':
            $tabela = 'supridesk_pecas';
            $log = "exclusão de uma peça.";   
            break;
    }
   
   $class = new Reservas_Equipamentos();
   
   
   if ($equipamento = $class->deleteReservaEquip($tabela,$_GET['id'], $_GET['tID'],$_GET['ticket'])){
        
       if($_GET['ticket'] != ''){
            //adiciona followup
            $fup = new TicketFollowup();
            $_dataFUP = array("content" => "O equipamento {$equipamento} saiu do trânsito.",
                "tickets_id" => $_GET['ticket'],
                "is_private" => 1
            );
            $fup->add($_dataFUP);
       }
       
        //emite mensagem
        Session::addMessageAfterRedirect("Equipamentos retirados da reserva.");
    }
    
   Html::back();

}else if (isset($_POST["removerTodosChamado"])) {
    //TODO: checar se pode editar usuÃ¡rio
    //$cartype->check($_POST["cartridgeitems_id"],'w');

    if ($cartype->delCartuchoAnalistaC($_POST["uID"])) {
        Event::log($_POST["tID"], "cartridges", 4, "inventory", $_SESSION["glpiname"] . " " . $LANG['log'][30]);
        Session::addMessageAfterRedirect("Não é possível fazer reserva para este tipo de Equipamento.", true, ERROR);

        
    }
    Html::back();
} else if (isset($_POST["removerTodosReserva"])) {
    //TODO: checar se pode editar usuÃ¡rio
    //$cartype->check($_POST["cartridgeitems_id"],'w');

    if ($cartype->delCartuchoAnalistaR($_POST["uID"])) {
        Event::log($_POST["tID"], "cartridges", 4, "inventory", $_SESSION["glpiname"] . " " . $LANG['log'][30]);
    }
    Html::back();
}else if (isset($_POST["removerReservas"])) {
    //TODO: checar se pode editar usuÃ¡rio
    //$cartype->check($_POST["cartridgeitems_id"],'w');
   // var_export($_POST);
    //die();
    $type = array('computer','monitor','printer','networkequipment','peripheral');
    
    foreach ($type as $tp){
        
        switch ($tp){
            case 'printer':
                $tabela = 'glpi_printers';
                $log = "exclusão de um impressora";  
                break;
            case 'computer':
                $tabela = 'glpi_computers';
                $log = "exclusão de um computador";  
                break;
            case 'monitor':
                $tabela = 'glpi_monitors';
                $log = "exclusão de um monitor";  
                break;
            case 'networkequipment':
                $tabela = 'glpi_networkequipments';
                $log = "exclusão de um periférico de rede";  
                break;
            case 'peripheral':
                $tabela = 'glpi_peripherals';
                $log = "exclusão de um periférico";       
        }
        
               
            
        if ($cartype->deleteReservas($tabela, $_POST['uID']))
        {            
            Session::addMessageAfterRedirect("Equipamentos retirados da reserva.");
        }
            
                
    }
                
    
    Html::back();
} else if (isset($_POST["removerTodosAplicados"])) {
    //TODO: checar se pode editar usuÃ¡rio
    //$cartype->check($_POST["cartridgeitems_id"],'w');

    if ($cartype->delCartuchoAnalistaAplicado($_POST["uID"])) {
        Event::log($_POST["tID"], "cartridges", 4, "inventory", $_SESSION["glpiname"] . " " . $LANG['log'][30]);
    }
    Html::back();
} else if (isset($_POST["aplicarSelecionados"])) {
    //TODO: checar se pode editar usuÃ¡rio
    //$cartype->check($_POST["cartridgeitems_id"],'w');
    //var_export($_POST);
    //die();

    foreach ($_POST as $k => $v) {
        if (strpos($k, 'sAplica_') !== false) {
            $_cartridge_ids = substr($k, strlen("sAplica_"));
            $_data_aplicado = $_POST["data_aplicado_$_cartridge_ids"];
            $_aplica_chamado = $_POST["aplica_chamado_$_cartridge_ids"];

            if ($v > 0 && $cartype->aplicaCartucho($_POST["uID"], $_cartridge_ids, $v, $_data_aplicado, $_aplica_chamado)) {
                Event::log($_POST["tID"], "cartridges", 4, "inventory", $_SESSION["glpiname"] . " " . $LANG['log'][30]);
            }
        }
    }
    Html::back();
} else if (isset($_POST["aplicarKit"])) {
    //TODO: checar se pode editar usuÃ¡rio
    //$cartype->check($_POST["cartridgeitems_id"],'w');

    $kitsel = $_POST["kitsel"];

    if ($kitsel > 0 && $cartype->aplicaKit($kitsel, $_POST["uID"])) {
        Event::log($_POST["tID"], "cartridges", 4, "inventory", $_SESSION["glpiname"] . " " . $LANG['log'][30]);
    }
    Html::back();
} else if (isset($_POST["removerSelecionados"])) {
    //TODO: checar se pode editar usuário
    //$cartype->check($_POST["cartridgeitems_id"],'w');

    foreach ($_POST as $k => $v) {
        if (strpos($k, 'sRemoveC_') !== false) {
            $_cartridge_ids = substr($k, strlen("sRemoveC_"));

            if ($v > 0 && $cartype->delCartuchoAnalistaC($_POST["uID"], $_cartridge_ids, $v)) {
                Event::log($_POST["tID"], "cartridges", 4, "inventory", $_SESSION["glpiname"] . " " . $LANG['log'][30]);
            }
        }

        if (strpos($k, 'sRemoveR_') !== false) {
            $_cartridge_ids = substr($k, strlen("sRemoveR_"));

            if ($v > 0 && $cartype->delCartuchoAnalistaR($_POST["uID"], $_cartridge_ids, $v)) {
                Event::log($_POST["tID"], "cartridges", 4, "inventory", $_SESSION["glpiname"] . " " . $LANG['log'][30]);
            }
        }
    }

    Html::back();
} else if (isset($_POST["removerAplicadosSelecionados"])) {
    //TODO: checar se pode editar usuÃ¡rio
    //$cartype->check($_POST["cartridgeitems_id"],'w');

    foreach ($_POST as $k => $v) {
        if (strpos($k, 'sRemoveApl_') !== false) {
            $_cartridge_ids = substr($k, strlen("sRemoveApl_"));

            if ($v > 0 && $cartype->delCartuchoAnalistaAplicado($_POST["uID"], $_cartridge_ids, $v)) {
                Event::log($_POST["tID"], "cartridges", 4, "inventory", $_SESSION["glpiname"] . " " . $LANG['log'][30]);
            }
        }
    }

    Html::back();
} else {
    Html::header($LANG['Menu'][21], $_SERVER['PHP_SELF'], "inventory", "cartridge");
    $cartype->showForm($_GET["id"]);
    Html::footer();
}
?>
