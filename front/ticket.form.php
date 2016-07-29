<?php

/*
 * @version $Id: ticket.form.php 17152 2012-01-24 11:22:16Z moyo $
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

Session::checkLoginUser();
$fup = new TicketFollowup();
$track = new Ticket();
$stock = new Stock();
$stk_tkt = new Stock_Tickets();
$atd = new Atendimento();


if (!isset($_GET['id'])) {
    $_GET['id'] = "";
}

if (isset($_POST["add"])) { 
    
   //******* SUPRISERVICE *****//
    switch ($_POST['itemtype']) {
        case 'Computer':
            $dados = $track->buscaLocalidade('glpi_computers', $_POST['items_id']);
            break;
        case 'Monitor':
            $dados = $track->buscaLocalidade('glpi_monitors', $_POST['items_id']);
            break;
        case 'NetworkEquipment':
            $dados = $track->buscaLocalidade('glpi_networkequipments', $_POST['items_id']);
            break;
        case 'Peripheral':
            $dados = $track->buscaLocalidade('glpi_peripherals', $_POST['items_id']);
            break;
        case 'Phone':
            $dados = $track->buscaLocalidade('glpi_phones', $_POST['items_id']);
            break;
        case 'Printer':
            $dados = $track->buscaLocalidade('glpi_printers', $_POST['items_id']);
            $dados_impressora = $track->buscaContrato($_POST['items_id']);
            break;
        case 'Doc':
            $_POST['itemtype'] = 'Documentos';            
            break;
    }

    if (isset($dados['locations_id'])) {
        $_POST['locations_id'] = $dados['locations_id'];
    } else {
        $_POST['locations_id'] = 0;
    }


    if (isset($dados_impressora['contracts_id'])) {
        $_POST['contracts_id'] = $dados_impressora['contracts_id'];
    } else {
        $_POST['contracts_id'] = 0;
    }
    
    $check = $track->verificaCategoria($_POST['type'], $_POST['itilcategories_id']); 
    
    if($check != true){
        Session::addMessageAfterRedirect("Tipo do chamando e categoria não correspondem.", true, ERROR);
        Html::back();
    }
        
   $_POST['type_fechamento'] = $_POST['type'];
   
    $track->check(-1, 'w', $_POST);

    if (isset($_POST["_my_items"]) && !empty($_POST["_my_items"])) {
        $splitter = explode("_", $_POST["_my_items"]);
        if (count($splitter) == 2) {
            $_POST["itemtype"] = $splitter[0];
            $_POST["items_id"] = $splitter[1];
        }
    }
    
    if($_POST['itemtype'] === 'Document'){ 
        $_SESSION['itemtypedoc'] = 'true';
    }
    

    /* //SUPRISERVICE */
    $tID = $track->add($_POST);
    // Envio de SMS

    $notificationSMS = new NotificationSms();
    $notificationSMS->enviaSMS($tID, NULL, 'add');
    //$notificationSMS->enviaSMSGilson($tID, NULL, 'add');

    if ($tID) {
        $track->addCartridgeItem($tID, $_POST["tID"], 1);
        

        /* //SUPRISERVICE */
        //se adicionar um chamado, atualiza o contador vinculado
        if ($_POST['itemtype'] == "Printer" && $_POST['items_id'] > 0) {
            if ((intval($_POST['pages_n_b']) > 0 || intval($_POST['pages_color'] > 0)) && $_POST['printerlogs_date'] != "NULL") {
                $printerLogs = new Fusioninventory_Printerlogs();
                $printerlogs_data['pages_n_b'] = $printerlogs_data['pages_n_b_print'] = intval($_POST['pages_n_b']);
                $printerlogs_data['pages_color'] = $printerlogs_data['pages_color_print'] = intval($_POST['pages_color']);
                $printerlogs_data['pages_total'] = $printerlogs_data['pages_total_print'] = $_POST['pages_n_b'] + $_POST['pages_color'];
                $printerlogs_data['date'] = $_POST['printerlogs_date'];

                $printerlogs_data['tickets_id'] = $tID;
                $printerlogs_data['printers_id'] = $_POST['items_id'];
                $printerlogs_data['import_mode'] = 'MANUAL';
                $printerLogs->add($printerlogs_data);
            }
        }
    }
    
    if ((intval($_POST['km']) > 0) && $_POST['printerlogskm_date'] != "NULL") {
        
        $transp = new Transport();
        $printerlogs_km['km'] = intval($_POST['km']);
        $printerlogs_km['printerlogkm_date'] = $_POST['printerlogkm_date'];
        $printerlogs_km['tickets_id'] = $tID;
        $printerlogs_km['veiculo_id'] = $_POST['items_id'];
        
        $transp->add_printerlogs($printerlogs_km);
    }
    //--
    Html::back();
} else if (isset($_POST['update'])) {   
    
    if(!isset($_POST['solution_itilcategories_id']) && !isset($_POST['dropdown_stock'])){
        $check = $track->verificaCategoria($_POST['type'], $_POST['itilcategories_id']);
        
        if($check != true){
            Session::addMessageAfterRedirect("Tipo do chamando e categoria não correspondem.", true, ERROR);
            Html::back();
        }
    }
       
    
    $track->check($_POST['id'], 'w');  

    if (isset($_POST["_my_items"]) && !empty($_POST["_my_items"])) {
        $splitter = explode("_", $_POST["_my_items"]);
        if (count($splitter) == 2) {
            $_POST["itemtype"] = $splitter[0];
            $_POST["items_id"] = $splitter[1];
        }
    }

    if (isset($_POST['inicio_atendimento']) && isset($_POST['solvedate'])) {
        if (strtotime($_POST['inicio_atendimento']) > strtotime($_POST['solvedate'])) {
            Session::addMessageAfterRedirect("Início do atendimento deve ser anterior à solução do chamado.", true, ERROR);
            Html::redirect($CFG_GLPI["root_doc"] . "/front/ticket.form.php?id=" . $_POST["id"]);
        }
    }
    
   
    
    if($_POST["solution_itilcategories_id"] != NULL && $_POST["solution_itilcategories_id"] != 0){
        
        if($_POST["solutiontypes_id"] == 0 || $_POST["solutiontemplates_id"] == 0 || (!array_key_exists('solution', $_POST) && $_POST["solution"] == '')){
            Session::addMessageAfterRedirect("Todos os campos são obrigatórios.", true, ERROR);
            Html::redirect($CFG_GLPI["root_doc"] . "/front/ticket.form.php?id=" . $_POST["id"]);
        }else{
            
            if (array_key_exists('solution', $_POST) && $_POST["solution"] != '') {
        
                $solution_text = str_replace('<p>', '&lt;p&gt;', $_POST["solution"]);
                $solution_text = strip_tags($solution_text);
                $solution_text = htmlspecialchars_decode(trim($solution_text));

                $header = "<strong>Solução:</strong>\r\n\r\n";
                $fup = new TicketFollowup();
                $_dataFUP = array("content" => $header . $solution_text,
                    "tickets_id" => $_POST['id'],
                    "is_private" => 0
                );
                $fup->add($_dataFUP);
            } 
            
        }
        
    }
    
    
    if($_POST['status'] == 'solved'){         
        if($_POST['confere_status'] == ''){
            if(!isset($_POST["solution_itilcategories_id"])){
                Session::addMessageAfterRedirect("Você deve aplicar uma solução a este chamado.", true, ERROR);
                Html::redirect($CFG_GLPI["root_doc"] . "/front/ticket.form.php?id=" . $_POST["id"]);
            }
        }        
        
        $horas = $atd->horasUteis($_POST['id']);        
        $_POST['horas_uteis_atendimento'] = $horas;            
    }
    
    if($_POST['status'] == 'canceled' && $_SESSION['glpiactiveprofile']['name'] != 'super-admin'){        
        Session::addMessageAfterRedirect("Você não possui autorização para cancelar este chamado.", true, ERROR);
        Html::redirect($CFG_GLPI["root_doc"] . "/front/ticket.form.php?id=" . $_POST["id"]);
    }
    

    //**** SUPRISERVICE ****///
    //// Controle de Estoque | Atualiza custo e controla itens de estoque
    if (isset($_POST['update_cost'])) {
        $id_item = $_POST['dropdown_stock'];
        $qtd_item = $_POST['to_add'];

        $estoque_selecionado = $stock->getEstoqueExistente($id_item);

        if ($estoque_selecionado) {

            //Faz o calculo do custo | Valor * quantidade selecionada         
            $custo = $estoque_selecionado['value'] * $qtd_item;
            $custo_total = $track->fields["cost_material"] + $custo;
            $_POST['cost_material'] = $custo_total;

            //Insere o id item de estoque, o id do ticket e a quantidade de estoque utilizada
            $stk_tkt->addStockTicket($id_item, $_POST['id'], $qtd_item);

            //Faz a retirada do estoque | Estoque - Item(s)
            $estoque_atualizado = $estoque_selecionado['quantidade'] - $qtd_item;
            $stock->updateEquipamentos($estoque_atualizado, $id_item);

            $fup = new TicketFollowup();
            $_dataFUP = array("content" => "Foi liberada {$qtd_item} peça(s) [ {$estoque_selecionado['name']} ] para atender este chamado.",
                "tickets_id" => $_POST['id'],
                "is_private" => 1
            );
            $fup->add($_dataFUP);
        } else {
            Session::addMessageAfterRedirect("Peça em falta no Estoque.", true, ERROR);
            Html::redirect($CFG_GLPI["root_doc"] . "/front/ticket.form.php?id=" . $_POST["id"]);
        }
    }  
    



    if ($track->update($_POST)) {  
        
        if (isset($_POST["tID"]))
            $track->addCartridgeItem($_POST["id"], intval($_POST["tID"]), 1);
        
        if($_POST['itemtype'] == -1){
            
            $_POST['itemtype'] = $_POST['typeitem'];
            $_POST['items_id'] = $_POST['itemsid'];
            
        }

        /* //SUPRISERVICE */
        //se atualizar o chamado, atualiza o contador vinculado
        if ($_POST['itemtype'] == "Printer" || $_POST['itemtype'] == "printer") {
            if ( $_POST['items_id'] > 0) {
                if ((intval($_POST['pages_n_b']) > 0 || intval($_POST['pages_color'] > 0)) && $_POST['printerlogs_date'] != "NULL") {
                    $printerLogs = new Fusioninventory_Printerlogs();
                    $printerlogs_data['pages_n_b'] = $printerlogs_data['pages_n_b_print'] = intval($_POST['pages_n_b']);
                    $printerlogs_data['pages_color'] = $printerlogs_data['pages_color_print'] = intval($_POST['pages_color']);
                    $printerlogs_data['pages_total'] = $printerlogs_data['pages_total_print'] = $_POST['pages_n_b'] + $_POST['pages_color'];
                    $printerlogs_data['date'] = $_POST['printerlogs_date'];

                    //verifica se jÃ¡ hÃ¡ registro no printerlogs
                    $query = "SELECT *
                            FROM `glpi_plugin_fusinvsnmp_printerlogs`
                            WHERE `tickets_id` = {$_POST['id']}
                              AND is_deleted = 0
                            ORDER BY date DESC LIMIT 1";
                    if ($result = $DB->query($query)) {
                        if ($row = $DB->fetch_row($result)) {
                            $printerlogs_data['id'] = $row[0];
                            $printerLogs->update($printerlogs_data);
                        } else {
                            $printerlogs_data['tickets_id'] = $_POST['id'];
                            $printerlogs_data['printers_id'] = $_POST['items_id'];
                            $printerlogs_data['import_mode'] = 'MANUAL';
                            $printerLogs->add($printerlogs_data);
                        }
                    }
                }
            }
        }        
       
        
        if ((intval($_POST['km']) > 0) && $_POST['printerlogskm_date'] != "NULL") {
            
            $transp = new TransportLogs();
            
            $printerlogs_km['km'] = intval($_POST['km']);
            $printerlogs_km['printerlogkm_date'] = $_POST['printerlogkm_date'];
            $printerlogs_km['tickets_id'] = intval($_POST["id"]);
            $printerlogs_km['veiculo_id'] = intval($_POST['items_id']);

            
            if($_POST['id_km_hid'] != ""){
                
                $printerlogs_km['id'] = $_POST['id_km_hid'];
                $transp->update_printerlogs($printerlogs_km);
                
            }else{

                $transp->add_printerlogs($printerlogs_km);
            }
            
        }
    }    
    

    if ((is_array($_POST['_itil_assign'])) &&
            (count($_POST['_itil_assign']) > 1) &&
            ($_POST['_itil_assign']['_type']) == "user") {
        /* //SUPRISERVICE */
        // Envio de SMS
        $notificationSMS = new NotificationSms();
        $notificationSMS->enviaSMS($_POST['id'], $_POST['_itil_assign']['users_id'], 'update');
    }
    
    

    if ($_POST['status'] == 'atendimento') {
        $track->updateInicioAtendimento($_POST['id']);
        
        $fup = new TicketFollowup();
        $_dataFUP = array("content" => "Chamado em atendimento.",
            "tickets_id" => $_POST['id'],
            "is_private" => 0
        );
        $fup->add($_dataFUP);

        ///*** SUPRISERVICE ***///
        if ($atd->verificaAtendimento($_POST['id']) == NULL) {
            $atd->addTempoAtendimento($_POST['id'], Session::getLoginUserID());
        }
    } else {
        if ($atd->verificaAtendimento($_POST['id'])) {
            $atd->atualizaAtendimento($_POST['id'], Session::getLoginUserID());
        }        
        
    }
    
    //--
    Event::log($_POST["id"], "ticket", 4, "tracking", $_SESSION["glpiname"] . " " . $LANG['log'][21]);

    /* //SUPRISERVICE - TODO: revisar se Ã© necessÃ¡rio */
    if (strpos($_SERVER['PHP_SELF'], "/plugins/mobile/") === true) {
        $plug = new Plugin;
        if ($plug->isInstalled('mobile') && $plug->isActivated('mobile')) {
            require_once GLPI_ROOT . "/plugins/mobile/inc/common.function.php";
            checkParams();
            if (isNavigatorMobile()) {
                define("MOBILE_EXTRANET_ROOT", GLPI_ROOT . "/plugins/mobile");
                Html::redirect/* glpi_header */(MOBILE_EXTRANET_ROOT . "/front/central.php?message=" . urlencode($LANG['plugin_mobile']['ticket'][2]));
            }
        }
    }    
    
    if($_POST['content'] != NULL){
        
       $t = $_POST["content"];
    
        $track->updateTitle($t, $_POST["id"]);
    }

    // Copy solution to KB redirect to KB
    if (isset($_POST['_sol_to_kb']) && $_POST['_sol_to_kb']) {
        Html::redirect($CFG_GLPI["root_doc"] . "/front/knowbaseitem.form.php?id=new&itemtype=Ticket&items_id=" .
                $_POST["id"]);
    } else {       
        
       // var_export();
        if ($track->can($_POST["id"], 'r')) {
            Html::redirect($CFG_GLPI["root_doc"] . "/front/ticket.form.php?id=" . $_POST["id"]);
        }
        Session::addMessageAfterRedirect($LANG['job'][26], true, ERROR);
        Html::redirect($CFG_GLPI["root_doc"] . "/front/ticket.php");
    }
} else if (isset($_POST['delete'])) {
    $track->check($_POST['id'], 'd');
    if ($track->delete($_POST)) {
        Event::log($_POST["id"], "ticket", 4, "tracking", $_SESSION["glpiname"] . " " . $LANG['log'][22] . " " . $track->getField('name'));
    }
    $track->redirectToList();
} else if (isset($_POST['purge'])) {
    $track->check($_POST['id'], 'd');
    if ($track->delete($_POST, 1)) {
        Event::log($_POST["id"], "ticket", 4, "tracking", $_SESSION["glpiname"] . " " . $LANG['log'][24] . " " . $track->getField('name'));
    }
    $track->redirectToList();
} else if (isset($_POST["restore"])) {
    $track->check($_POST['id'], 'd');
    if ($track->restore($_POST)) {
        Event::log($_POST["id"], "ticket", 4, "tracking", $_SESSION["glpiname"] . " " . $LANG['log'][23] . " " . $track->getField('name'));
    }
    $track->redirectToList();
    /*
      } else if (isset($_POST['add']) || isset($_POST['add_close']) || isset($_POST['add_reopen'])) {
      Session::checkSeveralRightsOr(array('add_followups'     => '1',
      'global_add_followups' => '1',
      'show_assign_ticket' => '1'));
      $newID = $fup->add($_POST);

      Event::log($_POST["tickets_id"], "ticket", 4, "tracking",
      $_SESSION["glpiname"]." ".$LANG['log'][20]." $newID.");
      Html::redirect($CFG_GLPI["root_doc"]."/front/ticket.form.php?id=".
      $_POST["tickets_id"]."&glpi_tab=1&itemtype=Ticket");

     */
} else if (isset($_POST['sla_delete'])) {
    $track->check($_POST["id"], 'w');

    $track->deleteSLA($_POST["id"]);
    Event::log($_POST["id"], "ticket", 4, "tracking", $_SESSION["glpiname"] . " " . $LANG['log'][21]);

    Html::redirect($CFG_GLPI["root_doc"] . "/front/ticket.form.php?id=" . $_POST["id"]);
} else if (isset($_REQUEST['delete_link'])) {
    $ticket_ticket = new Ticket_Ticket();
    $ticket_ticket->check($_REQUEST['id'], 'w');

    $ticket_ticket->delete($_REQUEST);

    Event::log($_REQUEST['tickets_id'], "ticket", 4, "tracking", $_SESSION["glpiname"] . " " . $LANG['log'][120]);
    Html::redirect($CFG_GLPI["root_doc"] . "/front/ticket.form.php?id=" . $_REQUEST['tickets_id']);
} else if (isset($_REQUEST['delete_user'])) {
    $ticket_user = new Ticket_User();
    $ticket_user->check($_REQUEST['id'], 'w');
    $ticket_user->delete($_REQUEST);

    Event::log($_REQUEST['tickets_id'], "ticket", 4, "tracking", $_SESSION["glpiname"] . " " . $LANG['log'][122]);
    Html::redirect($CFG_GLPI["root_doc"] . "/front/ticket.form.php?id=" . $_REQUEST['tickets_id']);
} else if (isset($_REQUEST['delete_group'])) {
    $group_ticket = new Group_Ticket();
    $group_ticket->check($_REQUEST['id'], 'w');
    $group_ticket->delete($_REQUEST);

    Event::log($_REQUEST['tickets_id'], "ticket", 4, "tracking", $_SESSION["glpiname"] . " " . $LANG['log'][122]);
    Html::redirect($CFG_GLPI["root_doc"] . "/front/ticket.form.php?id=" . $_REQUEST['tickets_id']);
} else if (isset($_REQUEST['addme_observer'])) {
    $ticket_user = new Ticket_User();
    $track->check($_REQUEST['tickets_id'], 'r');
    $input = array('tickets_id' => $_REQUEST['tickets_id'],
        'users_id' => Session::getLoginUserID(),
        'use_notification' => 1,
        'type' => Ticket::OBSERVER);
    $ticket_user->add($input);

    Event::log($_REQUEST['tickets_id'], "ticket", 4, "tracking", $_SESSION["glpiname"] . " " . $LANG['log'][21]);
    Html::redirect($CFG_GLPI["root_doc"] . "/front/ticket.form.php?id=" . $_REQUEST['tickets_id']);

//******* SUPRISERVICE *****//
} else if (isset($_GET["deletesupplier"])) {

    $valor_total = $_GET['vlr'] * $_GET['qtde'];
    
    $track->updateCostMaterial($_GET['id'], $valor_total);
    $track->returnStock($_GET['idstk'], $_GET['qtde']);
    $track->delStockTicket($_GET['id_stck_tick']);
    
    $estoque_selecionado = $stock->getEstoqueExistente($_GET['idstk']);

    $fup = new TicketFollowup();
    $_dataFUP = array("content" => "Foi retirado {$_GET['qtde']} peça(s) [ {$estoque_selecionado['name']} ] deste chamado.",
        "tickets_id" => $_GET["id"],
        "is_private" => 1
    );
    $fup->add($_dataFUP);
    Html::redirect($CFG_GLPI["root_doc"] . "/front/ticket.form.php?id=" . $_GET['id']);
}

if (isset($_POST["inicio_atd"])) {

    $track->updateInicioAtendimento($_POST['id']);
    $atd->updateEmAtendimento($_POST['id']);
    
    Session::addMessageAfterRedirect("Primeiro atendimento iniciado!");
    Html::redirect($CFG_GLPI["root_doc"] . "/front/ticket.form.php?id=" . $_POST["id"]);
}

if (isset($_GET["id"]) && $_GET["id"] > 0) {
    if ($_SESSION["glpiactiveprofile"]["interface"] == "helpdesk") {
        Html::helpHeader($LANG['Menu'][5], '', $_SESSION["glpiname"]);
    } else {
        Html::header($LANG['Menu'][5], '', "maintain", "ticket");
    }

    $available_options = array('load_kb_sol');
    $options = array();
    foreach ($available_options as $key) {
        if (isset($_GET[$key])) {
            $options[$key] = $_GET[$key];
        }
    }
    $track->showForm($_GET["id"], $options);
} else {
    Html::header($LANG['job'][13], '', "maintain", "ticket");

    $track->showForm(0);
}


if ($_SESSION["glpiactiveprofile"]["interface"] == "helpdesk") {
    Html::helpFooter();
} else {
    Html::footer();
}
?>