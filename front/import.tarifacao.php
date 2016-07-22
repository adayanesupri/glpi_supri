<?php

// ----------------------------------------------------------------------
// Original Author of file:
// Purpose of file:
// ----------------------------------------------------------------------
define('GLPI_ROOT', '..');
include (GLPI_ROOT . "/inc/includes.php");

Session::checkRight("importacao_dados", "1");

Html::header($LANG['Menu'][101], $_SERVER['PHP_SELF'], "utils", "import");

Import::title();

set_time_limit(0);

if (isset($_FILES) && array_key_exists('filename', $_FILES) && $_FILES['filename']['error'] == UPLOAD_ERR_OK //checks for errors
        && is_uploaded_file($_FILES['filename']['tmp_name'])) { //checks that file is uploaded
    $manual = 0;
    $pulosTipoTarifacao = 0;
    $pulosFM = 0;
    $pulosFalhaData = 0;
    $pulosJaTarifada = 0;
    $pulosNotFound = 0;
    $jaPossuiTicket = 0;
    $jaPossuiSolucao = 0;

    $strERRORS = "";

    $handle = fopen($_FILES['filename']['tmp_name'], "r");
    while (( $data = fgetcsv($handle, 1024, ",") ) !== FALSE) {

        //se não houver dados na terceira coluna, pula a linha
        if (!isset($data[2])) {
            $pulosTipoTarifacao++;
            $strERRORS .= "Tipo de tarifação não informado para a impressora {$data[5]}.<br>";
            continue;
        }

        //validação do tipo de tarifação, já contempla a exclusão do cabeçalho, se houver.
        if (isset($data[2]) && ( $data[2] == "FM" || $data[2] == "MANUAL" )) {
            if ($data[2] == "FM") {
                $pulosFM++;
                continue;
            } else if ($data[2] == "MANUAL") {
                $manual++;
            }
        } else {
            continue;
        }

        //se não houver dados na quarta coluna, pula a linha
        if (!isset($data[3])) {
            $pulosFalhaData++;
            $strERRORS .= "Falha na data de último scan da impressora {$data[5]}.<br>";
            continue;
        }

        $ano1 = explode(" ", $aDate_parts[2]);
        $ano1 = $ano1[0];
        $dateCompare1 = $ano1 . $aDate_parts[1];
        $dateCompare2 = date("Y") . date("m");

        if ($dateCompare1 >= $dateCompare2) {
            $pulosJaTarifada++;
            continue;
        }


        $ticket = new Ticket();

        $postData = Array();
        $postData['date'] = date("Y-m-d H:i:s");
        $postData['slas_id'] = 0;
        $postData['type'] = 2;
        $postData['type_fechamento'] = 2;
        $postData['itilcategories_id'] = 11;
        $postData['_users_id_requester'] = 4086; //Foi definido para ser criado pelo usuário SUPRISERVICE //Session::getLoginUserID(); //ID do usuário logado
        $postData['_users_id_requester_notif'] = Array('use_notification' => 1);

        $classSource = "Printer"; //Printer, Computer, Monitor, Peripheral
        $table = strtolower("glpi_{$classSource}s");
        $rs_source = $ticket->getEntityIDFromEquipamento($table, $data[5]);

        if ($rs_source === false) {
            $pulosNotFound++;
            $strERRORS .= "Dados não encontrados para o Equipamento/Serial: {$classSource}/{$data[5]}.<br>";
            continue;
        }

        $entity_id = $rs_source['entities_id'];
        $items_id = $rs_source['id'];

        if (Ticket::jaPossuiChamadoMes($classSource, $items_id)) {
            $jaPossuiTicket++;
            $strERRORS .= "O Equipamento/Serial: {$classSource}/{$data[5]} já possui chamado em aberto.<br>";
            continue;
        }

        if (Ticket::jaPossuiSolucaoMes($classSource, $items_id)) {
            $jaPossuiSolucao++;
            $strERRORS .= "O Equipamento/Serial: {$classSource}/{$data[5]} já possui chamado solucionado neste mês.<br>";
            continue;
        }

        $postData['entities_id'] = $entity_id; //entidade que o equipamento está relacionada
        $postData['_users_id_observer'] = 0;

        //Pega um coordenador de logística para atribuir os chamados sem analistas
        $user_category = new UserCategory();
        $user_coordenador = $user_category->find(" name = 'Coordenador de Logística' ", "", 1);
        $user_coordenador = array_pop($user_coordenador);

        //Pega o último analista que atendeu o equipamento, ou o coordenador de logística
        $user_assign = Ticket::getUltimoAnalistaID($items_id, $user_coordenador['id']);

        $postData['_users_id_assign'] = $user_assign; //ID do técnico que atendeu este equipamento pela última vez
        $postData['_users_id_assign_notif'] = Array('use_notification' => 1);
        $postData['status'] = 'new';
        $postData['requesttypes_id'] = 7;
        $postData['urgency'] = 3;
        $postData['impact'] = 3;
        $postData['_my_items'] = '';
        $postData['itemtype'] = $classSource; //tipo do equipamento
        $postData['items_id'] = $items_id; //ID do equipamento
        $postData['actiontime'] = 0;
        $postData['name'] = $_POST['descricao'];
        $postData['content'] = $_POST['descricao'];
        $postData['_tickettemplates_id'] = 1;
        $postData['id'] = 0;
        $postData['_glpi_csrf_token'] = Session::getNewCSRFToken();

        $ticket->add($postData);
        $_SESSION["MESSAGE_AFTER_REDIRECT"] = '';
        //flush();
    }
    // echo $mes8;
    fclose($handle);

    $processadas = $manual - $pulosJaTarifada - $jaPossuiTicket;

    echo "<table class='tab_cadre' width='950'>";
    echo "<tr class='tab_bg_2'>";
    echo "<th colspan='2'>Processo de Importação</th>";
    echo "</tr>";
    echo "<tr class='tab_bg_2'>";
    echo "<td>Automáticas (ignoradas):</td>";
    echo "<td>{$pulosFM}</td>";
    echo "</tr>";
    echo "<tr class='tab_bg_2'>";
    echo "<td>Manuais:</td>";
    echo "<td>{$manual}</td>";
    echo "</tr>";
    echo "<tr class='tab_bg_2'>";
    echo "<td>Processadas:</td>";
    echo "<td>{$processadas}</td>";
    echo "</tr>";
    echo "<tr class='tab_bg_2'>";
    echo "<td>Tipo de tarifaçao errado:</td>";
    echo "<td>{$pulosTipoTarifacao}</td>";
    echo "</tr>";
    echo "<tr class='tab_bg_2'>";
    echo "<td>Falha na data de último scan:</td>";
    echo "<td>{$pulosFalhaData}</td>";
    echo "</tr>";
    echo "<tr class='tab_bg_2'>";
    echo "<td>Impressora já tarifada no mês:</td>";
    echo "<td>{$pulosJaTarifada}</td>";
    echo "</tr>";
    echo "<tr class='tab_bg_2'>";
    echo "<td>Impressora já possui chamado em aberto:</td>";
    echo "<td>{$jaPossuiTicket}</td>";
    echo "</tr>";
    echo "<tr class='tab_bg_2'>";
    echo "<td>Impressora já possui chamado solucionado:</td>";
    echo "<td>{$jaPossuiSolucao}</td>";
    echo "</tr>";
    echo "<tr class='tab_bg_2'>";
    echo "<td>Dados não encontrados:</td>";
    echo "<td>{$pulosNotFound}</td>";
    echo "</tr>";
    echo "<tr class='tab_bg_2'>";
    echo "<td>Erros:</td>";
    echo "<td>{$strERRORS}</td>";
    echo "</tr>";
    echo "</table>";
} else {
    echo "<form enctype='multipart/form-data' method='post' name='form' action='import.tarifacao.php'>";
    echo "<table class='tab_cadre'>";
    echo "<tr class='tab_bg_2'>";
    echo "<td colspan='3'>" . $LANG['mailing'][5] . ":<br><TEXTAREA NAME='descricao' COLS=60 ROWS=6>" . $LANG['import'][4] . "</TEXTAREA></td>";
    echo "</tr>";
    echo "<tr class='tab_bg_2'>";
    echo "<td>" . $LANG['document'][2] . " (" . Document::getMaxUploadSize() . ")&nbsp;:</td>";
    echo "<td><input type='file' name='filename' value='' size='39'></td>";
    echo "<td class='center'><input type='submit' class='button' name='submit' value='" . $LANG['buttons'][37] . "'></td>";
    echo "</tr>";
    echo "</table>";
}

Html::closeForm();

Html::footer();
?>