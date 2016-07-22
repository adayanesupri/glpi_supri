<?php

// TRECHOS COMENTADOS 
//*****

define('GLPI_ROOT', '..');
include (GLPI_ROOT . "/inc/includes.php");

Session::checkRight("reports", "r");

require_once GLPI_ROOT . '/lib/PHPOffice/Classes/PHPExcel.php';
/** PHPExcel_IOFactory */
require_once GLPI_ROOT . '/lib/PHPOffice/Classes/PHPExcel/IOFactory.php';

$_POST['tarifacao_inicial'] = $_POST['tarifacao1'];
$_POST['tarifacao_final'] = date("Y-m-d 23:59:59", strtotime($_POST['tarifacao2']));
$_POST['bilhetagem_inicial'] = $_POST['bilhetagem1'];
$_POST['bilhetagem_final'] = date("Y-m-d 23:59:59", strtotime($_POST['bilhetagem2']));
$_POST['date'] = date("Y-m-d H:i:s", time());

$ts = new Tarifacao_Supridesk();

if ($ts->faturado($_POST))
    Html::displayErrorAndDie("Já foi fechado um faturamento para este contrato com data posterior à informada de bilhetagem.");

$objPHPExcel = new PHPExcel();

$objPHPExcel->getActiveSheet()->setShowGridlines(false);

// Set document properties
$objPHPExcel->getProperties()->setCreator("Supriservice Informática LTDA")->setLastModifiedBy("Supriservice Informática LTDA")->setTitle("Planilha de Tarifação")->setSubject("Planilha de Tarifação");

//Exibição dos dados da planilha geral
$objPHPExcel->setActiveSheetIndex(0);
$objPHPExcel->getActiveSheet()->setTitle('Relatório Geral');
$objPHPExcel->getActiveSheet()->getPageSetup()->setOrientation(PHPExcel_Worksheet_PageSetup::ORIENTATION_LANDSCAPE);
$objPHPExcel->getActiveSheet()->getPageSetup()->setPaperSize(PHPExcel_Worksheet_PageSetup::PAPERSIZE_A4);


//Define as datas finais com H:i:s
$_POST['tarifacao2'] = date("Y-m-d 23:59:59", strtotime($_POST['tarifacao2']));
$_POST['bilhetagem2'] = date("Y-m-d 23:59:59", strtotime($_POST['bilhetagem2']));

showHeader($objPHPExcel, $_POST);


$options = Array();
$options["proc_excedente"] = true;

$totalExcedente = Array();
showBody($objPHPExcel, $_POST, $totalExcedente, $options);


//Seleciona os agrupamentos do contrato
$strSQLAgrupamentos = "select * from supridesk_agrupamentos a where a.contracts_id = {$_POST['contracts_id']}";
$rs_agrupamentos = mysql_query($strSQLAgrupamentos);

if (mysql_num_rows($rs_agrupamentos) > 1) {
    $sheetIndex = 0;
    while ($rs_row_agrupamentos = mysql_fetch_array($rs_agrupamentos)) {

        $sheetIndex++;
        $objPHPExcel->createSheet($sheetIndex);
        $objPHPExcel->setActiveSheetIndex($sheetIndex);
        $objPHPExcel->getActiveSheet()->setShowGridlines(false);

        $title = $rs_row_agrupamentos["name"];
        if (strlen($rs_row_agrupamentos["name"]) > 31)
            $title = substr($rs_row_agrupamentos["name"], 0, 31);
        $objPHPExcel->getActiveSheet()->setTitle($title);

        //$strSQLAgrupamento = $strSQL . " AND cip.agrupamentos_id = " . $rs_row_agrupamentos['id'];
        //$rs_printers_agrupamento = mysql_query( $strSQLAgrupamento );
        showHeader($objPHPExcel, $_POST);


        $options["proc_excedente"] = false;
        $options["agrupamentos_id"] = $rs_row_agrupamentos["id"];

        showBody($objPHPExcel, $_POST, $totalExcedente, $options);
        //die(var_export(array($objPHPExcel, $_POST, $totalExcedente, $options)));
    }
}

//volta para a primeira planilha
$objPHPExcel->setActiveSheetIndex(0);

$objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel2007');

$directory = GLPI_ROOT . "/files/_tarifacao";
if (!is_dir($directory)) {
    mkdir($directory);
}
$filename = "tarifacao_c{$_POST['contracts_id']}_" . microtime() . ".xlsx";
$path = $directory . "/" . $filename;

$objWriter->save($path);

$webpath = "/files/_tarifacao/" . $filename;
$_POST['filepath'] = $webpath;
$_POST['filename'] = $filename;

$ts->add($_POST);
Html::redirect($CFG_GLPI["root_doc"] . '/front/contract.form.php?id=' . $_POST['contracts_id']);

//print $CFG_GLPI["root_doc"].'/front/contract.form.php?id='.$_POST['contracts_id'];

function showHeader(&$objExcel, $param_POST, $options = array()) {

    // var_export($param_POST);
    // die();
    $c = new Contract();
    $c->getFromDB($param_POST["contracts_id"]);

    $e = new Entity();
    $e->getFromDB($c->fields["entities_id"]);

    //Title Sheet 1
    $date_diff = strtotime($param_POST["tarifacao2"]) - strtotime($c->fields['begin_date']);
    $months = ceil(($date_diff) / 2628000);
    $data1 = date('d/m/Y', strtotime($param_POST["tarifacao1"]));
    $data2 = date('d/m/Y', strtotime($param_POST["tarifacao2"]));
    $title = $c->fields['name'] . " - " . $months . "º Mês";

    $objExcel->getActiveSheet()->setCellValue('A1', $title);
    $objExcel->getActiveSheet()->mergeCells('A1:J1');
    $objExcel->getActiveSheet()->getStyle('A1')->getFont()->setName('Calibri')->setSize(13)->setBold(true)->setColor(new PHPExcel_Style_Color(PHPExcel_Style_Color::COLOR_DARKBLUE));
    $objExcel->getActiveSheet()->getRowDimension('1')->setRowHeight(25);
    $objExcel->getActiveSheet()->getStyle('A1')->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);

    //Período de Corte
    $tarifacao = "Período de Locação: " . $data1 . " à " . $data2;
    $bilhetagem1 = date('d/m/Y', strtotime($param_POST["bilhetagem1"]));
    $bilhetagem2 = date('d/m/Y', strtotime($param_POST["bilhetagem2"]));
    $bilhetagem = "Período de corte da Bilhetagem: " . $bilhetagem1 . " à " . $bilhetagem2;
    $objExcel->getActiveSheet()->setCellValue('A2', $tarifacao . " - " . $bilhetagem);
    $objExcel->getActiveSheet()->mergeCells('A2:J2');
    $objExcel->getActiveSheet()->getStyle('A2')->getFont()->setName('Calibri')->setSize(11)->setBold(true)->setColor(new PHPExcel_Style_Color(PHPExcel_Style_Color::COLOR_DARKBLUE));
    $objExcel->getActiveSheet()->getRowDimension('2')->setRowHeight(20);
    $objExcel->getActiveSheet()->getStyle('A2')->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);

    $objExcel->getActiveSheet()->getStyle('A1:J2')->getFill()->getStartColor()->setARGB('FFFFE0');

    //Detalhes do Contrato
    $objExcel->getActiveSheet()->getStyle('A3:J4')->getFill()->setFillType(PHPExcel_Style_Fill::FILL_SOLID);
    $objExcel->getActiveSheet()->getStyle('A3:J4')->getFill()->getStartColor()->setARGB('C1CDCD');
    //$objExcel->getActiveSheet()->setCellValue('A3', $e->fields["name"]);
    //$objExcel->getActiveSheet()->getStyle('A3')->getFont()->setBold(true);

    $objExcel->getActiveSheet()->mergeCells('A3:J4');

    if ($c->fields["num"] != NULL) {
        $objExcel->getActiveSheet()->setCellValue('A3', "Número do Contrato: " . $c->fields["num"]);
    }

    $objExcel->getActiveSheet()->getStyle('A3')->getFont()->setBold(true);

    $styleBorderDetalhes = array(
        'borders' => array(
            'outline' => array(
                'style' => PHPExcel_Style_Border::BORDER_MEDIUM,
                'color' => array('argb' => 'FFC0C0C0'),
            ),
        ),
    );
    $objExcel->getActiveSheet()->getStyle('A3:J4')->applyFromArray($styleBorderDetalhes);
}

function showBody(&$objExcel, $param_POST, &$totalExcedente, &$options = array()) {
    global $DB;

    //loop pelos contracts_items
    $sql_contracts_items = " SELECT * FROM supridesk_contracts_items ci WHERE ci.contracts_id = {$param_POST['contracts_id']} ORDER BY Nome ";
    $rs_contracts_items = $DB->query($sql_contracts_items);


    $itemStartLine = 5;
    $itemActualLine = $itemStartLine;
    $current_column = "K";
    $next_column = $current_column;

    $options['totalCustoTotalGeral'] = '';
    $options['totalPaginasImpressas'] = '';
    $options['totalEquipamentos'] = '';

    while ($row_ci = $DB->fetch_assoc($rs_contracts_items)) {

        $contracts_items_id = $row_ci["id"];
        $options['is_sem_franquia'] = $row_ci["is_sem_franquia"];
        $options['is_franquia_unica_mono_color'] = $row_ci["is_franquia_unica_mono_color"];
        $options['franquia_mono'] = $row_ci["franquia_mono"];
        $options['franquia_color'] = $row_ci["franquia_color"];
        $options['franquia_a3_mono'] = $row_ci["franquia_a3_mono"];
        $options['franquia_a3_color'] = $row_ci["franquia_a3_color"];
        $options['franquia_digitalizacao'] = $row_ci["franquia_digitalizacao"];
        $options['franquia_plotagem'] = $row_ci["franquia_plotagem"];
        $options['aluguel_pro_rata'] = 0;

        //Seleciona impressoras de acordo com os parâmetros passados
        $strSQL = "SELECT 
                        ci.nome,
                        replace(LCASE(replace(LCASE(pm.name), 'lexmark', '')), 'xerox ', '') as printermodel,
                        '' as IP,
                        p.id as printers_id,
                        p.serial as serial,
                        p.otherserial as otherserial,
                        p.name as patrimonio,
                        p.contact as nome_alternativo,
                        e.name as localizacao,
                        IF(cip.out_contadores_id is null and ( ( cOUT.date >= '{$_POST['tarifacao1']}' OR cOUT.date is null ) AND ( cIN.date <= '{$_POST['tarifacao2']}' OR cIN.date is null ) ), 1, '') as equipamentos,
                        '' as tarifacao,
                        '' as lastscan,
                        cip.contracts_items_id,
                        agrupamentos_id,
                        cIN.date as dateIN,
                        cIN.impressao_mono as impressao_monoIN,
                        cOUT.impressao_mono as impressao_monoOUT,
                        cIN.impressao_color as impressao_colorIN,
                        cOUT.impressao_color as impressao_colorOUT,
                        cOUT.date as dateOUT,
                        replaced_printers_id
                    FROM supridesk_contracts_items_printers cip
                        LEFT JOIN glpi_printers p ON cip.printers_id = p.id
                        LEFT JOIN glpi_entities e ON p.entities_id = e.id
                        LEFT JOIN glpi_printermodels pm ON p.printermodels_id = pm.id
                        LEFT JOIN supridesk_contracts_items ci ON cip.contracts_items_id = ci.id
                        LEFT JOIN supridesk_contadores cIN ON cip.in_contadores_id = cIN.id
                        LEFT JOIN supridesk_contadores cOUT ON cip.out_contadores_id = cOUT.id
                    WHERE
                        cip.contracts_items_id = {$contracts_items_id}
                        AND 
                        (
                        ( ( cOUT.date >= '{$_POST['tarifacao1']}' OR cOUT.date is null ) AND ( cIN.date <= '{$_POST['tarifacao2']}' OR cIN.date is null ) )
                        OR
                        ( ( cOUT.date >= '{$_POST['bilhetagem1']}' OR cOUT.date is null ) AND ( cIN.date <= '{$_POST['bilhetagem2']}' OR cIN.date is null ) )
                        ) ";

        if (array_key_exists("agrupamentos_id", $options))
            $strSQL .= " AND cip.agrupamentos_id = {$options['agrupamentos_id']} ";

        $rs_printers = mysql_query($strSQL);

        if (mysql_num_rows($rs_printers) == 0)
            continue;

        //Seta o início do próximo Item para a linha atual.
        $itemStartLine = $itemActualLine;
        showItemHeader($objExcel, $itemActualLine, $row_ci["id"]);

        while ($rs_row_printer = mysql_fetch_array($rs_printers)) {
            $itemActualLine++;
            $ci = new Contract_Item_Supridesk();
            $ci->getFromDB($contracts_items_id);
            //var_export($ci->fields);
            //die();
            
            //busca ip na tabela de contadores importados do facilities
            $busca_ip = "SELECT	`ip`
                            FROM glpi_plugin_fusinvsnmp_printerlogs
                            WHERE
                                printers_id = {$rs_row_printer['printers_id']}
                                AND date < '{$param_POST['bilhetagem2']}'
                            ORDER BY date DESC limit 1";
                                
            $rs_busca_ip = mysql_query($busca_ip);
            
            if ($rs = mysql_fetch_array($rs_busca_ip)) {
                if($rs["ip"] == 0){
                    $rs_row_printer["IP"] = '';
                }else{
                    $rs_row_printer["IP"] = $rs["ip"];
                }
            }

            $objExcel->getActiveSheet()->setCellValue('A' . $itemActualLine, $rs_row_printer["printermodel"]);
            $objExcel->getActiveSheet()->setCellValue('B' . $itemActualLine, $rs_row_printer["IP"]);
            $objExcel->getActiveSheet()->setCellValue('C' . $itemActualLine, $rs_row_printer["serial"]);
            
            $localizacao_maiuscula = strtoupper($rs_row_printer["localizacao"]);
            
            $objExcel->getActiveSheet()->setCellValue('D' . $itemActualLine, $rs_row_printer["otherserial"] . "-" . $localizacao_maiuscula);
            $objExcel->getActiveSheet()->setCellValue('E' . $itemActualLine, $rs_row_printer["equipamentos"]);

            $obj_printerIN = null;
            if ($rs_row_printer["dateIN"] < $param_POST["bilhetagem1"]) {
                $strSQL2 = "SELECT	*
                            FROM glpi_plugin_fusinvsnmp_printerlogs
                            WHERE
                                printers_id = {$rs_row_printer['printers_id']}
                                AND date < '{$param_POST['bilhetagem1']}'
                            ORDER BY date DESC limit 1";
                $rs_printer_log = mysql_query($strSQL2);
                $obj_printerIN = mysql_fetch_array($rs_printer_log);
                $pages_n_b = $obj_printerIN["pages_n_b_print"];
            } else {
                if ($rs_row_printer["replaced_printers_id"] == 0) {
                    //aluguel pró rata dia
                    $dataInicial = date('d-m-Y', strtotime($rs_row_printer["dateIN"]));
                    $dataFinal = date('d-m-Y', strtotime($param_POST["bilhetagem2"]));
                    $cnt = 0;
                    $nodays = (strtotime($dataFinal) - strtotime($dataInicial)) / (60 * 60 * 24); //it will count no. of days
                    $nodays = $nodays + 1;

                    $options['aluguel_pro_rata'] += ($row_ci["valor_aluguel"] / 30) * $nodays;
                    $options['aluguel_pro_rata'] = number_format($options['aluguel_pro_rata'], 2);
                    //print "<br>" . $strSQL . "<br>";
                    //print "<br>pro-rata: " . $rs_row_printer["serial"] . ", item: " . $itemActualLine . "<br>";
                    $objExcel->getActiveSheet()->setCellValue('E' . $itemActualLine, 0);
                }

                $pages_n_b = $rs_row_printer["impressao_monoIN"];
            }
            $objExcel->getActiveSheet()->setCellValue('G' . $itemActualLine, $pages_n_b);

            $lastScan = "";
            $obj_printerOUT = null;
            if ($rs_row_printer["dateOUT"] == null || $rs_row_printer["dateOUT"] > $param_POST["bilhetagem2"]) {
                $strSQL3 = "SELECT	*
                            FROM glpi_plugin_fusinvsnmp_printerlogs
                            WHERE
                                printers_id = {$rs_row_printer['printers_id']}
                                AND date < '{$param_POST['bilhetagem2']}'
                            ORDER BY date DESC limit 1";
                $rs_printer_log = mysql_query($strSQL3);

                if ($obj_printerOUT = mysql_fetch_array($rs_printer_log)) {
                    $pages_n_b2 = $obj_printerOUT["pages_n_b_print"];
                    $lastScan = $obj_printerOUT["date"];
                }
            } else {
                $pages_n_b2 = $rs_row_printer["impressao_monoOUT"];
            }
            $objExcel->getActiveSheet()->setCellValue('H' . $itemActualLine, $pages_n_b2);

            $celulaDif1 = "G" . $itemActualLine;
            $celulaDif2 = "H" . $itemActualLine;

            $current_column = "H";
            $next_column = ++$current_column;

            //*****
            //Para não imprimir celula credito na planilha
            //*****
            //Se for Mono/Color juntos, não imprime crédito separado
            //$celulaCredito = 0;
            //if ( $row_ci["is_franquia_unica_mono_color"] != 1 )
            //{
            //	$celulaCredito = $next_column . $itemActualLine;
            //	++$next_column;
            //}
            //$formulaDiferencialMono = "=({$celulaDif2}-{$celulaDif1}-{$celulaCredito})";
            //*****
            //*****

            $formulaDiferencialMono = "=({$celulaDif2}-{$celulaDif1})";
            $objExcel->getActiveSheet()->setCellValue($next_column . $itemActualLine, $formulaDiferencialMono);

            $hasContadorGeral = false;
            $formulaContadorGeral = "={$next_column}{$itemActualLine}";
            $colunasDiferenciais[] = $next_column;
            ++$next_column;

            if ($ci->fields["franquia_color"] > 0 || $row_ci["is_franquia_unica_mono_color"] == 1) {
                $pages_colorIN = 0;
                if ($rs_row_printer["dateIN"] < $param_POST["bilhetagem1"])
                    $pages_colorIN = $obj_printerIN["pages_color_print"];
                else
                    $pages_colorIN = $rs_row_printer["impressao_colorIN"];

                $pages_colorOUT = 0;
                if ($rs_row_printer["dateOUT"] == null || $rs_row_printer["dateOUT"] > $param_POST["bilhetagem2"])
                    $pages_colorOUT = $obj_printerOUT["pages_color_print"];
                else
                    $pages_colorOUT = $rs_row_printer["impressao_colorOUT"];

                $celulaDif1 = $next_column . $itemActualLine;
                $objExcel->getActiveSheet()->setCellValue($next_column . $itemActualLine, $pages_colorIN);
                ++$next_column;
                $celulaDif2 = $next_column . $itemActualLine;
                $objExcel->getActiveSheet()->setCellValue($next_column . $itemActualLine, $pages_colorOUT);
                ++$next_column;

                //Se for Mono/Color juntos, não imprime crédito separado
                /* if ( $row_ci["is_franquia_unica_mono_color"] != 1 )
                  {
                  $celulaCreditoColor = $next_column . $itemActualLine;
                  $objExcel->getActiveSheet()->setCellValue($celulaCreditoColor, 0);
                  ++$next_column;
                  }
                  else
                  {
                  $celulaCreditoColor = 0;
                  } */

                $hasContadorGeral = true;
                $colunasDiferenciais[] = $next_column;
                $formulaContadorGeral .= "+{$next_column}{$itemActualLine}";

                $formulaDiferencialColor = "=({$celulaDif2}-{$celulaDif1})";
                //$formulaDiferencialColor = "=({$celulaDif2}-{$celulaDif1}-{$celulaCreditoColor})";
                //$formulaDiferencialColor = "=({$pages_colorOUT}-{$pages_colorIN}-{$celulaCreditoColor})";
                //$objExcel->getActiveSheet()->setCellValue('J'.$itemActualLine, $formulaDiferencialMono);

                $objExcel->getActiveSheet()->setCellValue($next_column . $itemActualLine, $formulaDiferencialColor);
                ++$next_column;
            }

            if ($ci->fields["franquia_a3_mono"] > 0) {
                //mês anterior
                $celulaA3_monoIN = $next_column . $itemActualLine;
                $objExcel->getActiveSheet()->setCellValue($celulaA3_monoIN, 0);
                ++$next_column;

                //mês atual
                $celulaA3_monoOUT = $next_column . $itemActualLine;
                $objExcel->getActiveSheet()->setCellValue($celulaA3_monoOUT, 0);
                ++$next_column;

               // $celulaCreditoA3_mono = $next_column . $itemActualLine;
              //  $objExcel->getActiveSheet()->setCellValue($celulaCreditoA3_mono, 0);
               // ++$next_column;

                //diferencial a3 mono
                $hasContadorGeral = true;
                $colunasDiferenciais[] = $next_column;
                $formulaContadorGeral .= "+{$next_column}{$itemActualLine}";
                $formulaDiferencialA3_mono = "=({$celulaA3_monoOUT}-{$celulaA3_monoIN})";
                $objExcel->getActiveSheet()->setCellValue($next_column . $itemActualLine, $formulaDiferencialA3_mono);
                ++$next_column;
            }

            if ($ci->fields["franquia_a3_color"] > 0) {
                //mês anterior
                $celulaA3_colorIN = $next_column . $itemActualLine;
                $objExcel->getActiveSheet()->setCellValue($celulaA3_colorIN, 0);
                ++$next_column;

                //mês atual
                $celulaA3_colorOUT = $next_column . $itemActualLine;
                $objExcel->getActiveSheet()->setCellValue($celulaA3_colorOUT, 0);
                ++$next_column;

                //diferencial a3 mono
                $hasContadorGeral = true;
                $colunasDiferenciais[] = $next_column;
                $formulaContadorGeral .= "+{$next_column}{$itemActualLine}";
                $formulaDiferencialA3_color = "=({$celulaA3_colorOUT}-{$celulaA3_colorIN})";
                $objExcel->getActiveSheet()->setCellValue($next_column . $itemActualLine, $formulaDiferencialA3_color);
                ++$next_column;
            }

            if ($ci->fields["franquia_digitalizacao"] > 0) {
                //mês anterior
                $celulaDigitalizacaoIN = $next_column . $itemActualLine;
                $objExcel->getActiveSheet()->setCellValue($celulaDigitalizacaoIN, 0);
                ++$next_column;

                //mês atual
                $celulaDigitalizacaoOUT = $next_column . $itemActualLine;
                $objExcel->getActiveSheet()->setCellValue($celulaDigitalizacaoOUT, 0);
                ++$next_column;

                //diferencial a3 mono
                $hasContadorGeral = true;
                $colunasDiferenciais[] = $next_column;
                $formulaContadorGeral .= "+{$next_column}{$itemActualLine}";
                $formulaDiferencialDigitalizacao = "=({$celulaDigitalizacaoOUT}-{$celulaDigitalizacaoIN})";
                $objExcel->getActiveSheet()->setCellValue($next_column . $itemActualLine, $formulaDiferencialDigitalizacao);
                ++$next_column;
            }

            if ($ci->fields["franquia_plotagem"] > 0) {
                //mês anterior
                $celulaPlotagemIN = $next_column . $itemActualLine;
                $objExcel->getActiveSheet()->setCellValue($celulaPlotagemIN, 0);
                ++$next_column;

                //mês atual
                $celulaPlotagemOUT = $next_column . $itemActualLine;
                $objExcel->getActiveSheet()->setCellValue($celulaPlotagemOUT, 0);
                ++$next_column;

                //diferencial a3 mono
                $hasContadorGeral = true;
                $colunasDiferenciais[] = $next_column;
                $formulaContadorGeral .= "+{$next_column}{$itemActualLine}";
                $formulaDiferencialPlotagem = "=({$celulaPlotagemOUT}-{$celulaPlotagemIN})";
                $objExcel->getActiveSheet()->setCellValue($next_column . $itemActualLine, $formulaDiferencialPlotagem);
                ++$next_column;
            }

            //Se for Mono/Color juntos, imprime coluna de crédito geral
            /* if ( $row_ci["is_franquia_unica_mono_color"] == 5 )
              {
              $objExcel->getActiveSheet()->setCellValue($next_column.$itemActualLine, 0);
              $formulaContadorGeral .= "-{$next_column}{$itemActualLine}";
              ++$next_column;
              } */

            if ($hasContadorGeral) {
               // if(isset($a3)){
                    //$objExcel->getActiveSheet()->setCellValue("P" . $itemActualLine, $formulaContadorGeral);
                  //  $objExcel->getActiveSheet()->getStyle($next_column . $itemActualLine)->getFont()->setBold(true);
                 //   ++$next_column;
              //  }else{
                    $objExcel->getActiveSheet()->setCellValue($next_column . $itemActualLine, $formulaContadorGeral);
                    $objExcel->getActiveSheet()->getStyle($next_column . $itemActualLine)->getFont()->setBold(true);
                    ++$next_column;
                //}
            }

            $objExcel->getActiveSheet()->setCellValue($next_column . $itemActualLine, $obj_printerOUT["import_mode"]);

            //Formatação de fonte da linha
            $objExcel->getActiveSheet()->getStyle("A{$itemActualLine}:$next_column{$itemActualLine}")->getFont()->setName('Calibri');
            $objExcel->getActiveSheet()->getStyle("A{$itemActualLine}:$next_column{$itemActualLine}")->getFont()->setSize(8);
            $objExcel->getActiveSheet()->getStyle("A{$itemActualLine}:$next_column{$itemActualLine}")->getFont()->setColor(new PHPExcel_Style_Color(PHPExcel_Style_Color::COLOR_DARKBLUE));
            $objExcel->getActiveSheet()->getStyle("A{$itemActualLine}:$next_column{$itemActualLine}")->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
            $objExcel->getActiveSheet()->getStyle("D{$itemActualLine}:D{$itemActualLine}")->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_LEFT);
            $objExcel->getActiveSheet()->getStyle("A{$itemActualLine}:$next_column{$itemActualLine}")->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);
            $objExcel->getActiveSheet()->getRowDimension($itemActualLine)->setRowHeight(12);
            // var_export("funcao 7");
//die();
            foreach ($colunasDiferenciais as $col)
                $objExcel->getActiveSheet()->getStyle($col . $itemActualLine)->getFont()->setBold(true);

            if ($pages_n_b2 - $pages_n_b <= 0) {
                $lastScan = "Não Tarifada\n" . $lastScan;
                $objExcel->getActiveSheet()->getStyle('F' . $itemActualLine)->getFont()->setColor(new PHPExcel_Style_Color(PHPExcel_Style_Color::COLOR_RED));
                $objExcel->getActiveSheet()->getStyle('F' . $itemActualLine)->getFont()->setBold(true);
                $objExcel->getActiveSheet()->getStyle('F' . $itemActualLine)->getAlignment()->setWrapText(true);
                $objExcel->getActiveSheet()->getStyle('F' . $itemActualLine)->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);
                $objExcel->getActiveSheet()->getRowDimension($itemActualLine)->setRowHeight(21);
            }
            $objExcel->getActiveSheet()->setCellValue('F' . $itemActualLine, $lastScan);

            if ($rs_row_printer["equipamentos"] == '')
                $objExcel->getActiveSheet()->getStyle("C{$itemActualLine}:D{$itemActualLine}")->getFont()->setColor(new PHPExcel_Style_Color(PHPExcel_Style_Color::COLOR_RED));

            if ($itemActualLine % 2 == 0) {
                $objExcel->getActiveSheet()->getStyle("A{$itemActualLine}:$next_column{$itemActualLine}")->getFill()->setFillType(PHPExcel_Style_Fill::FILL_SOLID);
                $objExcel->getActiveSheet()->getStyle("A{$itemActualLine}:$next_column{$itemActualLine}")->getFill()->getStartColor()->setARGB('FAFAD2');
            }
        }


        showItemFooter($objExcel, $itemStartLine, $itemActualLine, $next_column, $row_ci["id"], $param_POST, $totalExcedente, $options);

        $itemActualLine++;
    }

    $styleBorderOutline = array(
        'borders' => array(
            'allborders' => array(
                'style' => PHPExcel_Style_Border::BORDER_MEDIUM,
                'color' => array('argb' => 'FFC0C0C0'),
            ),
        ),
    );

    //formatação da linha Grand Totals
    $objExcel->getActiveSheet()->getStyle("A{$itemActualLine}:$next_column{$itemActualLine}")->getFont()->setColor(new PHPExcel_Style_Color(PHPExcel_Style_Color::COLOR_DARKBLUE));
    $objExcel->getActiveSheet()->getStyle("A{$itemActualLine}:$next_column{$itemActualLine}")->getFont()->setBold(true)->setSize(14);
    $objExcel->getActiveSheet()->getStyle("A{$itemActualLine}:$next_column{$itemActualLine}")->applyFromArray($styleBorderOutline);

    //Texto Grand Totals
    $celulaGrandTotals = "A" . $itemActualLine;
    $celulaGrandTotalsMerge = "D" . $itemActualLine;
    $objExcel->getActiveSheet()->setCellValue($celulaGrandTotals, 'Grand Totals:');
    $objExcel->getActiveSheet()->getStyle("{$celulaGrandTotals}:{$celulaGrandTotalsMerge}")->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
    $objExcel->getActiveSheet()->mergeCells("{$celulaGrandTotals}:{$celulaGrandTotalsMerge}");

    //Total equipamentos
    $celulaTotalEquipamentos = "E" . $itemActualLine;
    $formulaTotalEquipamentos = '=SUM(' . $options['totalEquipamentos'] . "0)";
    $objExcel->getActiveSheet()->setCellValue($celulaTotalEquipamentos, $formulaTotalEquipamentos);
    //$objExcel->getActiveSheet()->setCellValue($celulaTotalEquipamentos, $options['totalEquipamentos']);
    $objExcel->getActiveSheet()->getStyle($celulaTotalEquipamentos)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);

    //Custo total geral
    $celulaCustoTotalGeral = "F" . $itemActualLine;
    $formulaCustoTotalGeral = '=SUM(' . $options['totalCustoTotalGeral'] . "0)";
    $objExcel->getActiveSheet()->setCellValue($celulaCustoTotalGeral, $formulaCustoTotalGeral);
    $objExcel->getActiveSheet()->getStyle($celulaCustoTotalGeral)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
    $objExcel->getActiveSheet()->getStyle($celulaCustoTotalGeral)->getNumberFormat()->setFormatCode(PHPExcel_Style_NumberFormat::FORMAT_CURRENCY_REAL);

    //Páginas impressas
    $celulaPaginasImpressas = "H" . $itemActualLine;
    //$celulaPaginasImpressasMerge = "I" . $itemActualLine;
    $celulaPaginasImpressasDescricao = "I" . $itemActualLine;
    $formulaPaginasImpressas = '=SUM(' . $options['totalPaginasImpressas'] . "0)";
    $objExcel->getActiveSheet()->setCellValue($celulaPaginasImpressas, $formulaPaginasImpressas);
    //$objExcel->getActiveSheet()->mergeCells("{$celulaPaginasImpressas}:{$celulaPaginasImpressasMerge}");
    $objExcel->getActiveSheet()->setCellValue($celulaPaginasImpressasDescricao, 'Páginas impressas');
    $objExcel->getActiveSheet()->getStyle($celulaPaginasImpressas)->getNumberFormat()->setFormatCode(PHPExcel_Style_NumberFormat::FORMAT_NUMBER_MILHAR);

    $i = 2;

    for ($i = 0; $i < 2; $i++) {
        ++$next_column;
    }


    $testando = $celulaPaginasImpressasDescricao . ":J" . $itemActualLine;

    $objExcel->getActiveSheet()->getStyle("{$celulaPaginasImpressasDescricao}:J{$itemActualLine}")->applyFromArray($styleBorderOutline);
    $objExcel->getActiveSheet()->getStyle("A{$itemActualLine}:J{$itemActualLine}")->getFill()->getStartColor()->setARGB('FFFFE0');

    //$objExcel->getActiveSheet()->getRowDimension("A{$itemActualLine}:$next_column{$itemActualLine}")->setRowHeight(16);
    //$objExcel->getActiveSheet()->getStyle($labelItem)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
    //$objExcel->getActiveSheet()->getStyle($labelItem)->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);
}

function showItemHeader(&$objExcel, $actualLine, $contractItemID, $options = array()) {
    $ci = new Contract_Item_Supridesk();
    $ci->getFromDB($contractItemID);

    $objExcel->getActiveSheet()->setCellValue("A{$actualLine}", "Modelo");
    $objExcel->getActiveSheet()->getColumnDimension('A')->setWidth(11);
    $objExcel->getActiveSheet()->setCellValue("B{$actualLine}", "Endereço IP");
    $objExcel->getActiveSheet()->getColumnDimension('B')->setWidth(11);
    $objExcel->getActiveSheet()->setCellValue("C{$actualLine}", "Serial");
    $objExcel->getActiveSheet()->getColumnDimension('C')->setWidth(11);
    $objExcel->getActiveSheet()->setCellValue("D{$actualLine}", "Localização");
    $objExcel->getActiveSheet()->getColumnDimension('D')->setWidth(60);
    $objExcel->getActiveSheet()->setCellValue("E{$actualLine}", "Total de\nEquipamentos");
    $objExcel->getActiveSheet()->getColumnDimension('E')->setWidth(11);
    $objExcel->getActiveSheet()->setCellValue("F{$actualLine}", "Último Scan");
    $objExcel->getActiveSheet()->getColumnDimension('F')->setWidth(18);
    $objExcel->getActiveSheet()->setCellValue("G{$actualLine}", "Páginas Mono\n(mês anterior)");
    $objExcel->getActiveSheet()->getColumnDimension('G')->setWidth(11);
    $objExcel->getActiveSheet()->setCellValue("H{$actualLine}", "Páginas Mono\n(mês atual)");
    $objExcel->getActiveSheet()->getColumnDimension('H')->setWidth(11);

    $current_column = "H";
    $next_column = ++$current_column;

    //*****
    //Para não imprimir celula credito na planilha
    //*****
    //Se for Mono/Color juntos, não imprime crédito separado
    /* if ( $ci->fields["is_franquia_unica_mono_color"] != 1 )
      {
      $objExcel->getActiveSheet()->setCellValue("{$next_column}{$actualLine}", "Crédito Mono\n(se aplicado)");
      $objExcel->getActiveSheet()->getColumnDimension($next_column)->setWidth(11);
      ++$next_column;
      } */

    //*****
    //*****

    $objExcel->getActiveSheet()->setCellValue("{$next_column}{$actualLine}", "Páginas Mono\n(diferencial)");
    $objExcel->getActiveSheet()->getColumnDimension($next_column)->setWidth(11);
    ++$next_column;

    $hasContadorGeral = false;

    if ($ci->fields["franquia_color"] > 0 || $ci->fields["is_franquia_unica_mono_color"] == 1) {
        $hasContadorGeral = true;
        $objExcel->getActiveSheet()->setCellValue("{$next_column}{$actualLine}", "Páginas Color\n(mês anterior)");
        $objExcel->getActiveSheet()->getColumnDimension($next_column)->setWidth(11);
        ++$next_column;
        $objExcel->getActiveSheet()->setCellValue("{$next_column}{$actualLine}", "Páginas Color\n(mês atual)");
        $objExcel->getActiveSheet()->getColumnDimension($next_column)->setWidth(11);
        ++$next_column;
        //Se for Mono/Color juntos, não imprime crédito separado
        /* if ( $ci->fields["is_franquia_unica_mono_color"] != 1 )
          {
          $objExcel->getActiveSheet()->setCellValue("{$next_column}{$actualLine}", "Crédito Color\n(se aplicado)");
          $objExcel->getActiveSheet()->getColumnDimension($next_column)->setWidth(11);
          ++$next_column;
          } */
        $objExcel->getActiveSheet()->setCellValue("{$next_column}{$actualLine}", "Páginas Color\n(diferencial) ");
        $objExcel->getActiveSheet()->getColumnDimension($next_column)->setWidth(11);
        ++$next_column;
    }

    if ($ci->fields["franquia_a3_mono"] > 0) {
        $hasContadorGeral = true;
        $objExcel->getActiveSheet()->setCellValue("{$next_column}{$actualLine}", "Páginas A3 Mono\n(mês anterior)");
        $objExcel->getActiveSheet()->getColumnDimension($next_column)->setWidth(11);
        ++$next_column;
        $objExcel->getActiveSheet()->setCellValue("{$next_column}{$actualLine}", "Páginas A3 Mono\n(mês atual)");
        $objExcel->getActiveSheet()->getColumnDimension($next_column)->setWidth(11);
        ++$next_column;
        /* $objExcel->getActiveSheet()->setCellValue("{$next_column}{$actualLine}", "Crédito A3 Mono\n(se aplicado)");
          $objExcel->getActiveSheet()->getColumnDimension($next_column)->setWidth(11);
          ++$next_column; */
        $objExcel->getActiveSheet()->setCellValue("{$next_column}{$actualLine}", "Páginas A3 Mono\n(diferencial) ");
        $objExcel->getActiveSheet()->getColumnDimension($next_column)->setWidth(11);
        ++$next_column;
    }

    if ($ci->fields["franquia_a3_color"] > 0) {
        $hasContadorGeral = true;
        $objExcel->getActiveSheet()->setCellValue("{$next_column}{$actualLine}", "Páginas A3 Color\n(mês anterior)");
        $objExcel->getActiveSheet()->getColumnDimension($next_column)->setWidth(11);
        ++$next_column;
        $objExcel->getActiveSheet()->setCellValue("{$next_column}{$actualLine}", "Páginas A3 Color\n(mês atual)");
        $objExcel->getActiveSheet()->getColumnDimension($next_column)->setWidth(11);
        ++$next_column;
        /* $objExcel->getActiveSheet()->setCellValue("{$next_column}{$actualLine}", "Crédito A3 Color\n(se aplicado)");
          $objExcel->getActiveSheet()->getColumnDimension($next_column)->setWidth(11);
          ++$next_column; */
        $objExcel->getActiveSheet()->setCellValue("{$next_column}{$actualLine}", "Páginas A3 Color\n(diferencial) ");
        $objExcel->getActiveSheet()->getColumnDimension($next_column)->setWidth(11);
        ++$next_column;
    }

    if ($ci->fields["franquia_digitalizacao"] > 0) {
        $hasContadorGeral = true;
        $objExcel->getActiveSheet()->setCellValue("{$next_column}{$actualLine}", "Digitalizações\n(mês anterior)");
        $objExcel->getActiveSheet()->getColumnDimension($next_column)->setWidth(11);
        ++$next_column;
        $objExcel->getActiveSheet()->setCellValue("{$next_column}{$actualLine}", "Digitalizações\n(mês atual)");
        $objExcel->getActiveSheet()->getColumnDimension($next_column)->setWidth(11);
        ++$next_column;
        /* $objExcel->getActiveSheet()->setCellValue("{$next_column}{$actualLine}", "Crédito Digitalizações\n(se aplicado)");
          $objExcel->getActiveSheet()->getColumnDimension($next_column)->setWidth(11);
          ++$next_column; */
        $objExcel->getActiveSheet()->setCellValue("{$next_column}{$actualLine}", "Digitalizações\n(diferencial) ");
        $objExcel->getActiveSheet()->getColumnDimension($next_column)->setWidth(11);
        ++$next_column;
    }

    if ($ci->fields["franquia_plotagem"] > 0) {
        $hasContadorGeral = true;
        $objExcel->getActiveSheet()->setCellValue("{$next_column}{$actualLine}", "Plotagens (m²)\n(mês anterior)");
        $objExcel->getActiveSheet()->getColumnDimension($next_column)->setWidth(11);
        ++$next_column;
        $objExcel->getActiveSheet()->setCellValue("{$next_column}{$actualLine}", "Plotagens (m²)\n(mês atual)");
        $objExcel->getActiveSheet()->getColumnDimension($next_column)->setWidth(11);
        ++$next_column;
        /* $objExcel->getActiveSheet()->setCellValue("{$next_column}{$actualLine}", "Crédito Plotagens(se aplicado)");
          $objExcel->getActiveSheet()->getColumnDimension($next_column)->setWidth(11);
          ++$next_column; */
        $objExcel->getActiveSheet()->setCellValue("{$next_column}{$actualLine}", "Plotagens (m²)\n(diferencial) ");
        $objExcel->getActiveSheet()->getColumnDimension($next_column)->setWidth(11);
        ++$next_column;
    }

    //Se for Mono/Color juntos, imprime coluna de crédito geral
    /* if ( $ci->fields["is_franquia_unica_mono_color"] == 5 )
      {
      $objExcel->getActiveSheet()->setCellValue("{$next_column}{$actualLine}", "Crédito\n(se aplicado)");
      $objExcel->getActiveSheet()->getColumnDimension($next_column)->setWidth(11);
      ++$next_column;
      } */

    if ($hasContadorGeral) {
        $objExcel->getActiveSheet()->setCellValue("{$next_column}{$actualLine}", "Contador Geral\n(diferencial)");
        $objExcel->getActiveSheet()->getColumnDimension($next_column)->setWidth(11);
        ++$next_column;
    }

    $objExcel->getActiveSheet()->setCellValue("{$next_column}{$actualLine}", "Tipo de\nTarifação");
    $objExcel->getActiveSheet()->getColumnDimension($next_column)->setWidth(11);

    //Cabeçalho Grid
    $objExcel->getActiveSheet()->getStyle("A{$actualLine}:$next_column{$actualLine}")->getFill()->setFillType(PHPExcel_Style_Fill::FILL_SOLID);
    $objExcel->getActiveSheet()->getStyle("A{$actualLine}:$next_column{$actualLine}")->getFill()->getStartColor()->setARGB('E6E6FA');
    $objExcel->getActiveSheet()->getRowDimension($actualLine)->setRowHeight(45);
    $objExcel->getActiveSheet()->getStyle("A{$actualLine}:$next_column{$actualLine}")->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);
    $objExcel->getActiveSheet()->getStyle("A{$actualLine}:$next_column{$actualLine}")->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);

    $objExcel->getActiveSheet()->getStyle("A{$actualLine}:$next_column{$actualLine}")->getFont()->setName('Calibri');
    $objExcel->getActiveSheet()->getStyle("A{$actualLine}:$next_column{$actualLine}")->getFont()->setSize(8);
    $objExcel->getActiveSheet()->getStyle("A{$actualLine}:$next_column{$actualLine}")->getFont()->setBold(true);
    $objExcel->getActiveSheet()->getStyle("A{$actualLine}:$next_column{$actualLine}")->getFont()->setColor(new PHPExcel_Style_Color(PHPExcel_Style_Color::COLOR_DARKBLUE));
    $objExcel->getActiveSheet()->getStyle("A{$actualLine}:$next_column{$actualLine}")->getAlignment()->setWrapText(true);

    $styleBorderCabecalho = array(
        'borders' => array(
            'allborders' => array(
                'style' => PHPExcel_Style_Border::BORDER_MEDIUM,
                'color' => array('argb' => 'FFC0C0C0'),
            ),
        ),
    );
    $objExcel->getActiveSheet()->getStyle("A{$actualLine}:$next_column{$actualLine}")->applyFromArray($styleBorderCabecalho);
}

function showItemFooter(&$objExcel, $startLine, &$actualLine, $lastColumn, $contractItemID, $param_POST, &$totalExcedente, &$options = array()) {
    $ci = new Contract_Item_Supridesk();
    $ci->getFromDB($contractItemID);

    //Formatação das bordas do grid
    $styleBorderGeralGrid = array(
        'borders' => array(
            'left' => array(
                'style' => PHPExcel_Style_Border::BORDER_MEDIUM,
                'color' => array('argb' => 'FFC0C0C0'),
            ),
            'right' => array(
                'style' => PHPExcel_Style_Border::BORDER_MEDIUM,
                'color' => array('argb' => 'FFC0C0C0'),
            ),
            'bottom' => array(
                'style' => PHPExcel_Style_Border::BORDER_DOTTED,
                'color' => array('argb' => 'FFC0C0C0'),
            ),
        ),
    );
    for ($i = $startLine; $i <= $actualLine; $i++)
        $objExcel->getActiveSheet()->getStyle("A{$i}:$lastColumn{$i}")->applyFromArray($styleBorderGeralGrid);

    $styleBorderBottomGrid = array(
        'borders' => array(
            'bottom' => array(
                'style' => PHPExcel_Style_Border::BORDER_MEDIUM,
                'color' => array('argb' => 'FFC0C0C0'),
            ),
        ),
    );
    $objExcel->getActiveSheet()->getStyle("A{$actualLine}:$lastColumn{$actualLine}")->applyFromArray($styleBorderBottomGrid);


    $styleBorderOutline = array(
        'borders' => array(
            'allborders' => array(
                'style' => PHPExcel_Style_Border::BORDER_MEDIUM,
                'color' => array('argb' => 'FFC0C0C0'),
            ),
        ),
    );

    $diasTarifacaoExtra = 0;
    //se o mês da data inicial for menor que o mês da data final, calcula os dias de tarifação extra
    if (date("m", strtotime($param_POST['tarifacao1'])) < date("m", strtotime($param_POST['tarifacao2']))) {
        $ultimoDiaTarifacao1 = date("Y-m-t 23:59:59", strtotime($param_POST['tarifacao1']));
        $date_diff = strtotime($ultimoDiaTarifacao1) - strtotime($param_POST['tarifacao1']);
        $diasTarifacaoExtra = ceil($date_diff / (24 * 60 * 60));

        if ($diasTarifacaoExtra == 31) {
            $diasTarifacaoExtra = 0;
        }
    }

    $franquiaTotal = $options['franquia_mono'] +
                    $options['franquia_color'] +
                    $options['franquia_a3_mono'] +
                    $options['franquia_a3_color'] +
                    $options['franquia_digitalizacao'] +
                    $options['franquia_plotagem'];

    //SUBTOTAL
    //Label
    $labelSubtotal = "D" . ($actualLine + 1);
    $objExcel->getActiveSheet()->setCellValue($labelSubtotal, 'Sub-Total');
    //LABELS RODAPÉ
    $objExcel->getActiveSheet()->getStyle($labelSubtotal)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_RIGHT);
    $objExcel->getActiveSheet()->getStyle($labelSubtotal)->getFont()->setBold(true)->setSize(9)->setColor(new PHPExcel_Style_Color(PHPExcel_Style_Color::COLOR_DARKBLUE));
    $objExcel->getActiveSheet()->getStyle($labelSubtotal)->getFill()->setFillType(PHPExcel_Style_Fill::FILL_SOLID)->getStartColor()->setARGB('E6E6FA');
    $objExcel->getActiveSheet()->getStyle($labelSubtotal)->applyFromArray($styleBorderOutline);
    //Célula
    $celulaSubtotal = "E" . ($actualLine + 1);
    $celulaSubtotalUltima = $lastColumn . ($actualLine + 1);

    $formulaSubtotal = "=SUM(E" . $startLine . ":E" . $actualLine . ")";
    $objExcel->getActiveSheet()->setCellValue($celulaSubtotal, $formulaSubtotal);

    $columnSubtotal = "H";
    if ($options['is_franquia_unica_mono_color'] != 1) {
        ++$columnSubtotal;
    }

    if ($ci->fields["franquia_color"] < 0 || $options['is_franquia_unica_mono_color'] != 1) {
        $celulaSubtotalMono = $columnSubtotal . ($actualLine + 1);
        $formulaSubtotalMono = "=SUM({$columnSubtotal}" . $startLine . ":{$columnSubtotal}" . $actualLine . ")";
        $objExcel->getActiveSheet()->setCellValue($celulaSubtotalMono, $formulaSubtotalMono);
    }


    $hasContadorGeral = false;
    $colunaSubContadorGeral = "";

    $subtotalAtual = $columnSubtotal;
    if ($ci->fields["franquia_color"] > 0 || $options['is_franquia_unica_mono_color'] == 1) {
        $columnSubtotal = "H";
        ++$columnSubtotal;
        $celulaSubtotalMono = $columnSubtotal . ($actualLine + 1);
        $formulaSubtotalMono = "=SUM({$columnSubtotal}" . $startLine . ":{$columnSubtotal}" . $actualLine . ")";
        $objExcel->getActiveSheet()->setCellValue($celulaSubtotalMono, $formulaSubtotalMono);


        ++$subtotalAtual;
        ++$subtotalAtual;
        //++$subtotalAtual;
        //++$subtotalAtual;
        if ($options['is_franquia_unica_mono_color'] != 1) {
            ++$subtotalAtual;
        } elseif ($options['is_franquia_unica_mono_color'] == 1) {
            ++$subtotalAtual;
            ++$subtotalAtual;
        }

        $celulaSubtotalColor = $subtotalAtual . ($actualLine + 1);
        $formulaSubtotalColor = "=SUM(" . $subtotalAtual . $startLine . ":" . $subtotalAtual . $actualLine . ")";
        $objExcel->getActiveSheet()->setCellValue($celulaSubtotalColor, $formulaSubtotalColor);

        //var_export(array($subtotalAtual,$startLine, $subtotalAtual,$actualLine));
        //die();

        $hasContadorGeral = true;
        $colunaSubContadorGeral = ++$subtotalAtual;

        if ($options['is_franquia_unica_mono_color'] == 1)
            ++$colunaSubContadorGeral;
    }

    $celulaSubtotalA3Mono = "";
    if ($ci->fields["valor_impressao_a3_mono_franquia"] > 0 || $ci->fields["valor_impressao_a3_mono"] > 0) {
        $a3 = true;
        //++$subtotalAtual;
        //++$subtotalAtual;
        ++$subtotalAtual;

        if ($options['is_franquia_unica_mono_color'] == 0)
            ++$subtotalAtual;

        $celulaSubtotalA3Mono = $subtotalAtual . ($actualLine + 1);
        $formulaSubtotalA3Mono = "=SUM(" . $subtotalAtual . $startLine . ":" . $subtotalAtual . $actualLine . ")";
        $objExcel->getActiveSheet()->setCellValue($celulaSubtotalA3Mono, $formulaSubtotalA3Mono);

        $hasContadorGeral = true;
        $colunaSubContadorGeral = ++$subtotalAtual;
        
        if ($options['is_franquia_unica_mono_color'] == 1)
            ++$colunaSubContadorGeral;
            
    }

    $celulaSubContadorGeral = "";
    if ($hasContadorGeral) {
        
        if(isset($a3)){
            $colunaSubContadorGeral = 'P';
        }else{
            $colunaSubContadorGeral = 'M';
        }
        //incrementa contador geral no total de páginas
        $celulaSubContadorGeral = $colunaSubContadorGeral . ($actualLine + 1);
        $formulaContadorGeral = "=SUM(" . $colunaSubContadorGeral . $startLine . ":" . $colunaSubContadorGeral . $actualLine . ")";
        $objExcel->getActiveSheet()->setCellValue($celulaSubContadorGeral, $formulaContadorGeral);
        //------
        $options['totalPaginasImpressas'] .= $celulaSubContadorGeral . '+';
    } else {
        $options['totalPaginasImpressas'] .= $celulaSubtotalMono . '+';
    }
    $options['totalEquipamentos'] .= $celulaSubtotal . '+';


    $objExcel->getActiveSheet()->getStyle("{$celulaSubtotal}:{$celulaSubtotalUltima}")->getFont()->setBold(true)->setSize(9);
    $objExcel->getActiveSheet()->getStyle("{$celulaSubtotal}:{$celulaSubtotalUltima}")->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
    $objExcel->getActiveSheet()->getStyle("{$celulaSubtotal}:{$celulaSubtotalUltima}")->applyFromArray($styleBorderOutline);



    if ($options['is_sem_franquia'] == 0) {
        //TOTAL FRANQUIA
        //Label
        $labelFranquiaTotal = "D" . ($actualLine + 2);
        $objExcel->getActiveSheet()->setCellValue($labelFranquiaTotal, 'Franquia Total (páginas)');
        $objExcel->getActiveSheet()->getStyle($labelFranquiaTotal)->getFont()->setBold(true)->setSize(9);
        $objExcel->getActiveSheet()->getStyle($labelFranquiaTotal)->getFill()->getStartColor()->setARGB('E6E6FA');
        $objExcel->getActiveSheet()->getStyle($labelFranquiaTotal)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_RIGHT);
        $objExcel->getActiveSheet()->getStyle($labelFranquiaTotal)->getFont()->setColor(new PHPExcel_Style_Color(PHPExcel_Style_Color::COLOR_DARKBLUE));
        $objExcel->getActiveSheet()->getStyle($labelFranquiaTotal)->getFill()->setFillType(PHPExcel_Style_Fill::FILL_SOLID);
        $objExcel->getActiveSheet()->getStyle($labelFranquiaTotal)->applyFromArray($styleBorderOutline);
        //Célula
        $celulaFranquiaTotal = "E" . ($actualLine + 2);
        $celulaFranquiaTotalMerge = "G" . ($actualLine + 2);
        //$extraFranquiaTotal = "+FLOOR(((" . intval($ci->fields["franquia_mono"]) . "*" . $celulaSubtotal . ")/30)*" . $diasTarifacaoExtra . ", 1)";
        $formulaFranquiaTotal = "=(" . intval($franquiaTotal) . "*" . $celulaSubtotal . ")"; // . $extraFranquiaTotal;
        $objExcel->getActiveSheet()->getStyle($celulaFranquiaTotal)->getFont()->setBold(true)->setSize(9);
        $objExcel->getActiveSheet()->getStyle($celulaFranquiaTotal)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);

        $objExcel->getActiveSheet()->setCellValue($celulaFranquiaTotal, $formulaFranquiaTotal);
        $objExcel->getActiveSheet()->mergeCells("{$celulaFranquiaTotal}:{$celulaFranquiaTotalMerge}");
        $objExcel->getActiveSheet()->getStyle("{$celulaFranquiaTotal}:{$celulaFranquiaTotalMerge}")->applyFromArray($styleBorderOutline);
        $objExcel->getActiveSheet()->getStyle("{$celulaFranquiaTotal}:{$celulaFranquiaTotalMerge}")->getNumberFormat()->setFormatCode(PHPExcel_Style_NumberFormat::FORMAT_NUMBER_MILHAR);
        $objExcel->getActiveSheet()->getStyle("{$celulaFranquiaTotal}:{$celulaFranquiaTotalMerge}")->getFont()->setColor(new PHPExcel_Style_Color(PHPExcel_Style_Color::COLOR_DARKBLUE));

        //CUSTO UNITARIO
        //Label
        $labelCustoUnitario = "D" . ($actualLine + 3);
        $objExcel->getActiveSheet()->setCellValue($labelCustoUnitario, 'Custo do Equipamento (unitário)');
        $objExcel->getActiveSheet()->getStyle($labelCustoUnitario)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_RIGHT);
        $objExcel->getActiveSheet()->getStyle($labelCustoUnitario)->getFont()->setBold(true)->setSize(9)->setColor(new PHPExcel_Style_Color(PHPExcel_Style_Color::COLOR_DARKBLUE));
        $objExcel->getActiveSheet()->getStyle($labelCustoUnitario)->getFill()->setFillType(PHPExcel_Style_Fill::FILL_SOLID)->getStartColor()->setARGB('E6E6FA');
        $objExcel->getActiveSheet()->getStyle($labelCustoUnitario)->applyFromArray($styleBorderOutline);
        //Célula
        $celulaCustoUnitario = "E" . ($actualLine + 3);
        $celulaCustoUnitarioMerge = "G" . ($actualLine + 3);
        $extraCustoUnitario = "(((" . $ci->fields["valor_aluguel"] . ")/30)*" . $diasTarifacaoExtra . ")";
        $formulaCustoUnitario = "=(" . $ci->fields["valor_aluguel"] . "+" . $extraCustoUnitario . ")";
        $objExcel->getActiveSheet()->getStyle($celulaCustoUnitario)->getFont()->setSize(9);
        $objExcel->getActiveSheet()->getStyle($celulaCustoUnitario)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_RIGHT);
        $objExcel->getActiveSheet()->getStyle("{$celulaCustoUnitario}:{$celulaCustoUnitarioMerge}")->applyFromArray($styleBorderOutline);
        $objExcel->getActiveSheet()->getStyle("{$celulaCustoUnitario}:{$celulaCustoUnitarioMerge}")->getNumberFormat()->setFormatCode(PHPExcel_Style_NumberFormat::FORMAT_CURRENCY_REAL);
        $objExcel->getActiveSheet()->mergeCells("{$celulaCustoUnitario}:{$celulaCustoUnitarioMerge}");
        $objExcel->getActiveSheet()->setCellValue($celulaCustoUnitario, $formulaCustoUnitario);

        $printMono = " Mono ";
        if ($options['is_franquia_unica_mono_color'] == 1)
            $printMono = " ";

        //MONO DENTRO DA FRANQUIA
        //Total de Páginas Mono dentro da franquia (Mensal)
        $labelMonoFranquiaPaginas = "D" . ($actualLine + 4);
        $objExcel->getActiveSheet()->setCellValue($labelMonoFranquiaPaginas, "Total de Páginas{$printMono}dentro da franquia (Mensal)");
        $objExcel->getActiveSheet()->getStyle($labelMonoFranquiaPaginas)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_RIGHT);
        $objExcel->getActiveSheet()->getStyle($labelMonoFranquiaPaginas)->getFont()->setBold(true)->setSize(9)->setColor(new PHPExcel_Style_Color(PHPExcel_Style_Color::COLOR_DARKBLUE));
        $objExcel->getActiveSheet()->getStyle($labelMonoFranquiaPaginas)->getFill()->setFillType(PHPExcel_Style_Fill::FILL_SOLID)->getStartColor()->setARGB('E6E6FA');
        $objExcel->getActiveSheet()->getStyle($labelMonoFranquiaPaginas)->applyFromArray($styleBorderOutline);
        //Célula 'Total Páginas'
        $celulaMonoFranquiaPaginas = "E" . ($actualLine + 4);
        $celulaMonoFranquiaPaginasMerge = "G" . ($actualLine + 4);
        //$extraFranquiaTotal = "+FLOOR(((" . intval($ci->fields["franquia_mono"]) . "*" . $celulaSubtotal . ")/30)*" . $diasTarifacaoExtra . ", 1)";
        $formulaMonoFranquiaPaginas = "=(" . $celulaSubtotal . "*" . $ci->fields["franquia_mono"] . ")";
        $objExcel->getActiveSheet()->getStyle($celulaMonoFranquiaPaginas)->getFont()->setBold(true)->setSize(9);
        $objExcel->getActiveSheet()->getStyle($celulaMonoFranquiaPaginas)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
        $objExcel->getActiveSheet()->getStyle("{$celulaMonoFranquiaPaginas}:{$celulaMonoFranquiaPaginasMerge}")->applyFromArray($styleBorderOutline);
        $objExcel->getActiveSheet()->getStyle("{$celulaMonoFranquiaPaginas}:{$celulaMonoFranquiaPaginasMerge}")->getNumberFormat()->setFormatCode(PHPExcel_Style_NumberFormat::FORMAT_NUMBER_MILHAR);
        $objExcel->getActiveSheet()->getStyle("{$celulaMonoFranquiaPaginas}:{$celulaMonoFranquiaPaginasMerge}")->getFont()->setColor(new PHPExcel_Style_Color(PHPExcel_Style_Color::COLOR_DARKBLUE));
        $objExcel->getActiveSheet()->mergeCells("{$celulaMonoFranquiaPaginas}:{$celulaMonoFranquiaPaginasMerge}");
        $objExcel->getActiveSheet()->setCellValue($celulaMonoFranquiaPaginas, $formulaMonoFranquiaPaginas);
        //Valor de Impressão Mono dentro da Franquia (unitário)
        $labelMonoFranquiaUnitario = "D" . ($actualLine + 5);
        $objExcel->getActiveSheet()->setCellValue($labelMonoFranquiaUnitario, "Valor de Impressão{$printMono}dentro da Franquia (unitário)");
        $objExcel->getActiveSheet()->getStyle($labelMonoFranquiaUnitario)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_RIGHT);
        $objExcel->getActiveSheet()->getStyle($labelMonoFranquiaUnitario)->getFont()->setBold(true)->setSize(9)->setColor(new PHPExcel_Style_Color(PHPExcel_Style_Color::COLOR_DARKBLUE));
        $objExcel->getActiveSheet()->getStyle($labelMonoFranquiaUnitario)->getFill()->setFillType(PHPExcel_Style_Fill::FILL_SOLID)->getStartColor()->setARGB('E6E6FA');
        $objExcel->getActiveSheet()->getStyle($labelMonoFranquiaUnitario)->applyFromArray($styleBorderOutline);
        //VALOR DE IMPRESSAO UNITÁRIO
        $celulaMonoFranquiaUnitario = "E" . ($actualLine + 5);
        $celulaMonoFranquiaUnitarioMerge = "G" . ($actualLine + 5);
        $objExcel->getActiveSheet()->getStyle($celulaMonoFranquiaUnitario)->getFont()->setSize(9);
        $objExcel->getActiveSheet()->getStyle($celulaMonoFranquiaUnitario)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_RIGHT);
        $objExcel->getActiveSheet()->getStyle("{$celulaMonoFranquiaUnitario}:{$celulaMonoFranquiaUnitarioMerge}")->applyFromArray($styleBorderOutline);
        $objExcel->getActiveSheet()->getStyle("{$celulaMonoFranquiaUnitario}:{$celulaMonoFranquiaUnitarioMerge}")->getNumberFormat()->setFormatCode(PHPExcel_Style_NumberFormat::FORMAT_CURRENCY_REAL4);
        $objExcel->getActiveSheet()->mergeCells("{$celulaMonoFranquiaUnitario}:{$celulaMonoFranquiaUnitarioMerge}");
        $objExcel->getActiveSheet()->setCellValue($celulaMonoFranquiaUnitario, $ci->fields["valor_impressao_mono_franquia"]);
        //Valor Total de Impressão Mono dentro da Franquia (páginas)
        $labelMonoFranquiaTotal = "D" . ($actualLine + 6);
        $objExcel->getActiveSheet()->setCellValue($labelMonoFranquiaTotal, "Valor Total de Impressão{$printMono}dentro da Franquia (páginas)");
        $objExcel->getActiveSheet()->getStyle($labelMonoFranquiaTotal)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_RIGHT);
        $objExcel->getActiveSheet()->getStyle($labelMonoFranquiaTotal)->getFont()->setBold(true)->setSize(9)->setColor(new PHPExcel_Style_Color(PHPExcel_Style_Color::COLOR_DARKBLUE));
        $objExcel->getActiveSheet()->getStyle($labelMonoFranquiaTotal)->getFill()->setFillType(PHPExcel_Style_Fill::FILL_SOLID)->getStartColor()->setARGB('E6E6FA');
        $objExcel->getActiveSheet()->getStyle($labelMonoFranquiaTotal)->applyFromArray($styleBorderOutline);
        //VALOR DE IMPRESSAO UNITÁRIO
        $celulaMonoFranquiaTotal = "E" . ($actualLine + 6);
        $celulaMonoFranquiaTotalMerge = "G" . ($actualLine + 6);
        $formulaMonoFranquiaTotal = "=(" . $celulaMonoFranquiaPaginas . "*" . $celulaMonoFranquiaUnitario . ")";
        $objExcel->getActiveSheet()->getStyle($celulaMonoFranquiaTotal)->getFont()->setSize(9);
        $objExcel->getActiveSheet()->getStyle($celulaMonoFranquiaTotal)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_RIGHT);
        $objExcel->getActiveSheet()->getStyle("{$celulaMonoFranquiaTotal}:{$celulaMonoFranquiaTotalMerge}")->applyFromArray($styleBorderOutline);
        $objExcel->getActiveSheet()->getStyle("{$celulaMonoFranquiaTotal}:{$celulaMonoFranquiaTotalMerge}")->getNumberFormat()->setFormatCode(PHPExcel_Style_NumberFormat::FORMAT_CURRENCY_REAL);
        $objExcel->getActiveSheet()->mergeCells("{$celulaMonoFranquiaTotal}:{$celulaMonoFranquiaTotalMerge}");
        $objExcel->getActiveSheet()->setCellValue($celulaMonoFranquiaTotal, $formulaMonoFranquiaTotal);

        $actualLinePlus = $actualLine + 6;

        //MONO EXCEDENTE DA FRANQUIA
        //TOTAL DE PAGINAS
        //Total de Páginas Mono excedente da franquia (Mensal)
        $actualLinePlus++;
        $labelMonoExcedentePaginas = "D" . $actualLinePlus;
        $objExcel->getActiveSheet()->setCellValue($labelMonoExcedentePaginas, "Total de Páginas{$printMono}excedente da franquia (Mensal)");
        $objExcel->getActiveSheet()->getStyle($labelMonoExcedentePaginas)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_RIGHT);
        $objExcel->getActiveSheet()->getStyle($labelMonoExcedentePaginas)->getFont()->setBold(true)->setSize(9)->setColor(new PHPExcel_Style_Color(PHPExcel_Style_Color::COLOR_DARKBLUE));
        $objExcel->getActiveSheet()->getStyle($labelMonoExcedentePaginas)->getFill()->setFillType(PHPExcel_Style_Fill::FILL_SOLID)->getStartColor()->setARGB('E6E6FA');
        $objExcel->getActiveSheet()->getStyle($labelMonoExcedentePaginas)->applyFromArray($styleBorderOutline);
        //Célula 'Total Páginas'
        $celulaMonoExcedentePaginas = "E" . ($actualLinePlus);
        $celulaMonoExcedentePaginasMerge = "G" . ($actualLinePlus);

        $celulaSubtotalExcedente = $celulaSubtotalMono;
        //if ( $options['is_franquia_unica_mono_color'] == 1 )
        //$celulaSubtotalExcedente = $celulaSubContadorGeral;
        //. "*" . $ci->fields["franquia_mono"] 
        //$celulaSubtotalExcedente = 'I60';
        $formulaMonoExcedente = "=(" . $celulaSubtotalExcedente . " - " . $celulaSubtotal . "*" . $ci->fields["franquia_mono"] . ")";

        $saldoMonoExcedente = $objExcel->getActiveSheet()->getCell($celulaSubtotalExcedente)->getCalculatedValue() - ($objExcel->getActiveSheet()->getCell($celulaSubtotal)->getCalculatedValue() * $ci->fields["franquia_mono"]);

        $creditoMonoExcedente = 0;
        if ($saldoMonoExcedente < 0) {
            $creditoMonoExcedente = $saldoMonoExcedente * -1;
            //$formulaMonoExcedente = 0;
        }

        $objExcel->getActiveSheet()->getStyle($celulaMonoExcedentePaginas)->getFont()->setBold(true)->setSize(9);
        $objExcel->getActiveSheet()->getStyle($celulaMonoExcedentePaginas)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
        $objExcel->getActiveSheet()->getStyle("{$celulaMonoExcedentePaginas}:{$celulaMonoExcedentePaginasMerge}")->applyFromArray($styleBorderOutline);
        $objExcel->getActiveSheet()->getStyle("{$celulaMonoExcedentePaginas}:{$celulaMonoExcedentePaginasMerge}")->getNumberFormat()->setFormatCode(PHPExcel_Style_NumberFormat::FORMAT_NUMBER_MILHAR);
        $objExcel->getActiveSheet()->getStyle("{$celulaMonoExcedentePaginas}:{$celulaMonoExcedentePaginasMerge}")->getFont()->setColor(new PHPExcel_Style_Color(PHPExcel_Style_Color::COLOR_DARKBLUE));
        $objExcel->getActiveSheet()->mergeCells("{$celulaMonoExcedentePaginas}:{$celulaMonoExcedentePaginasMerge}");
        $objExcel->getActiveSheet()->setCellValue($celulaMonoExcedentePaginas, $formulaMonoExcedente);

        if ($creditoMonoExcedente > 0)

        //*****
        //Comentario na celula atribuindo credito.
        //*****
        //Lançado um crédito de {$creditoMonoExcedente} páginas para os próximos meses.
        //	$objExcel->getActiveSheet()->getComment($celulaMonoExcedentePaginas)->getText()->createTextRun("");
        //*****
        //*****
        //VALOR DE IMPRESSAO
        //Valor de Impressão Mono excedente da Franquia (unitário)
            $actualLinePlus++;
        $labelMonoExcedenteUnitario = "D" . ($actualLinePlus);
        $objExcel->getActiveSheet()->setCellValue($labelMonoExcedenteUnitario, "Valor de Impressão{$printMono}excedente da Franquia (unitário)");
        $objExcel->getActiveSheet()->getStyle($labelMonoExcedenteUnitario)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_RIGHT);
        $objExcel->getActiveSheet()->getStyle($labelMonoExcedenteUnitario)->getFont()->setBold(true)->setSize(9)->setColor(new PHPExcel_Style_Color(PHPExcel_Style_Color::COLOR_DARKBLUE));
        $objExcel->getActiveSheet()->getStyle($labelMonoExcedenteUnitario)->getFill()->setFillType(PHPExcel_Style_Fill::FILL_SOLID)->getStartColor()->setARGB('E6E6FA');
        $objExcel->getActiveSheet()->getStyle($labelMonoExcedenteUnitario)->applyFromArray($styleBorderOutline);
        //VALOR DE IMPRESSAO UNITÁRIO
        $celulaMonoExcedenteUnitario = "E" . ($actualLinePlus);
        $celulaMonoExcedenteUnitarioMerge = "G" . ($actualLinePlus);
        $objExcel->getActiveSheet()->getStyle($celulaMonoExcedenteUnitario)->getFont()->setSize(9);
        $objExcel->getActiveSheet()->getStyle($celulaMonoExcedenteUnitario)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_RIGHT);
        $objExcel->getActiveSheet()->getStyle("{$celulaMonoExcedenteUnitario}:{$celulaMonoExcedenteUnitarioMerge}")->applyFromArray($styleBorderOutline);
        $objExcel->getActiveSheet()->getStyle("{$celulaMonoExcedenteUnitario}:{$celulaMonoExcedenteUnitarioMerge}")->getNumberFormat()->setFormatCode(PHPExcel_Style_NumberFormat::FORMAT_CURRENCY_REAL4);
        $objExcel->getActiveSheet()->mergeCells("{$celulaMonoExcedenteUnitario}:{$celulaMonoExcedenteUnitarioMerge}");
        $objExcel->getActiveSheet()->setCellValue($celulaMonoExcedenteUnitario, $ci->fields["valor_impressao_mono"]);
        //VALOR TOTAL DE IMPRESSAO
        //Valor Total de Impressão Mono excedente da Franquia (páginas)
        $actualLinePlus++;
        $labelMonoExcedenteTotal = "D" . ($actualLinePlus);
        $objExcel->getActiveSheet()->setCellValue($labelMonoExcedenteTotal, "Valor Total de Impressão{$printMono}excedente da Franquia (páginas)");
        $objExcel->getActiveSheet()->getStyle($labelMonoExcedenteTotal)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_RIGHT);
        $objExcel->getActiveSheet()->getStyle($labelMonoExcedenteTotal)->getFont()->setBold(true)->setSize(9)->setColor(new PHPExcel_Style_Color(PHPExcel_Style_Color::COLOR_DARKBLUE));
        $objExcel->getActiveSheet()->getStyle($labelMonoExcedenteTotal)->getFill()->setFillType(PHPExcel_Style_Fill::FILL_SOLID)->getStartColor()->setARGB('E6E6FA');
        $objExcel->getActiveSheet()->getStyle($labelMonoExcedenteTotal)->applyFromArray($styleBorderOutline);
        //VALOR DE IMPRESSAO UNITÁRIO
        $celulaMonoExcedenteTotal = "E" . ($actualLinePlus);
        $celulaMonoExcedenteTotalMerge = "G" . ($actualLinePlus);
        $formulaMonoExcedenteTotal = "=(" . $celulaMonoExcedentePaginas . "*" . $celulaMonoExcedenteUnitario . ")";
        $objExcel->getActiveSheet()->getStyle($celulaMonoExcedenteTotal)->getFont()->setSize(9);
        $objExcel->getActiveSheet()->getStyle($celulaMonoExcedenteTotal)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_RIGHT);
        $objExcel->getActiveSheet()->getStyle("{$celulaMonoExcedenteTotal}:{$celulaMonoExcedenteTotalMerge}")->applyFromArray($styleBorderOutline);
        $objExcel->getActiveSheet()->getStyle("{$celulaMonoExcedenteTotal}:{$celulaMonoExcedenteTotalMerge}")->getNumberFormat()->setFormatCode(PHPExcel_Style_NumberFormat::FORMAT_CURRENCY_REAL);
        $objExcel->getActiveSheet()->mergeCells("{$celulaMonoExcedenteTotal}:{$celulaMonoExcedenteTotalMerge}");
        $objExcel->getActiveSheet()->setCellValue($celulaMonoExcedenteTotal, $formulaMonoExcedenteTotal);

        $custoTotalExtra = "";
        if ($ci->fields["valor_impressao_color_franquia"] > 0 || $ci->fields["valor_impressao_color"] > 0 || $ci->fields["franquia_color"] > 0) {
            //COLOR DENTRO DA FRANQUIA
            //TOTAL DE PAGINAS
            //Total de Páginas Color dentro da franquia (Mensal)
            $actualLinePlus++;
            $labelColorFranquiaPaginas = "D" . ($actualLinePlus);
            $objExcel->getActiveSheet()->setCellValue($labelColorFranquiaPaginas, "Total de Páginas Color dentro da franquia (Mensal)");
            $objExcel->getActiveSheet()->getStyle($labelColorFranquiaPaginas)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_RIGHT);
            $objExcel->getActiveSheet()->getStyle($labelColorFranquiaPaginas)->getFont()->setBold(true)->setSize(9)->setColor(new PHPExcel_Style_Color(PHPExcel_Style_Color::COLOR_DARKBLUE));
            $objExcel->getActiveSheet()->getStyle($labelColorFranquiaPaginas)->getFill()->setFillType(PHPExcel_Style_Fill::FILL_SOLID)->getStartColor()->setARGB('E6E6FA');
            $objExcel->getActiveSheet()->getStyle($labelColorFranquiaPaginas)->applyFromArray($styleBorderOutline);
            //Célula 'Total Páginas'
            $celulaColorFranquiaPaginas = "E" . ($actualLinePlus);
            $celulaColorFranquiaPaginasMerge = "G" . ($actualLinePlus);
            $formulaColorFranquiaPaginas = "=(" . $celulaSubtotal . "*" . $ci->fields["franquia_color"] . ")";
            $objExcel->getActiveSheet()->getStyle($celulaColorFranquiaPaginas)->getFont()->setBold(true)->setSize(9);
            $objExcel->getActiveSheet()->getStyle($celulaColorFranquiaPaginas)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
            $objExcel->getActiveSheet()->getStyle("{$celulaColorFranquiaPaginas}:{$celulaColorFranquiaPaginasMerge}")->applyFromArray($styleBorderOutline);
            $objExcel->getActiveSheet()->getStyle("{$celulaColorFranquiaPaginas}:{$celulaColorFranquiaPaginasMerge}")->getNumberFormat()->setFormatCode(PHPExcel_Style_NumberFormat::FORMAT_NUMBER_MILHAR);
            $objExcel->getActiveSheet()->getStyle("{$celulaColorFranquiaPaginas}:{$celulaColorFranquiaPaginasMerge}")->getFont()->setColor(new PHPExcel_Style_Color(PHPExcel_Style_Color::COLOR_DARKBLUE));
            $objExcel->getActiveSheet()->mergeCells("{$celulaColorFranquiaPaginas}:{$celulaColorFranquiaPaginasMerge}");
            $objExcel->getActiveSheet()->setCellValue($celulaColorFranquiaPaginas, $formulaColorFranquiaPaginas);

            //VALOR DE IMPRESSAO
            $actualLinePlus++;
            //Valor de Impressão Color dentro da Franquia (unitário)
            $labelColorFranquiaUnitario = "D" . ($actualLinePlus);
            $objExcel->getActiveSheet()->setCellValue($labelColorFranquiaUnitario, "Valor de Impressão Color dentro da Franquia (unitário)");
            $objExcel->getActiveSheet()->getStyle($labelColorFranquiaUnitario)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_RIGHT);
            $objExcel->getActiveSheet()->getStyle($labelColorFranquiaUnitario)->getFont()->setBold(true)->setSize(9)->setColor(new PHPExcel_Style_Color(PHPExcel_Style_Color::COLOR_DARKBLUE));
            $objExcel->getActiveSheet()->getStyle($labelColorFranquiaUnitario)->getFill()->setFillType(PHPExcel_Style_Fill::FILL_SOLID)->getStartColor()->setARGB('E6E6FA');
            $objExcel->getActiveSheet()->getStyle($labelColorFranquiaUnitario)->applyFromArray($styleBorderOutline);
            //VALOR DE IMPRESSAO UNITÁRIO
            $celulaColorFranquiaUnitario = "E" . ($actualLinePlus);
            $celulaColorFranquiaUnitarioMerge = "G" . ($actualLinePlus);
            $objExcel->getActiveSheet()->getStyle($celulaColorFranquiaUnitario)->getFont()->setSize(9);
            $objExcel->getActiveSheet()->getStyle($celulaColorFranquiaUnitario)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_RIGHT);
            $objExcel->getActiveSheet()->getStyle("{$celulaColorFranquiaUnitario}:{$celulaColorFranquiaUnitarioMerge}")->applyFromArray($styleBorderOutline);
            $objExcel->getActiveSheet()->getStyle("{$celulaColorFranquiaUnitario}:{$celulaColorFranquiaUnitarioMerge}")->getNumberFormat()->setFormatCode(PHPExcel_Style_NumberFormat::FORMAT_CURRENCY_REAL4);
            $objExcel->getActiveSheet()->mergeCells("{$celulaColorFranquiaUnitario}:{$celulaColorFranquiaUnitarioMerge}");
            $objExcel->getActiveSheet()->setCellValue($celulaColorFranquiaUnitario, $ci->fields["valor_impressao_color_franquia"]);

            //VALOR TOTAL DE IMPRESSAO
            $actualLinePlus++;
            //Valor Total de Impressão Color dentro da Franquia (páginas)
            $labelColorFranquiaTotal = "D" . ($actualLinePlus);
            $objExcel->getActiveSheet()->setCellValue($labelColorFranquiaTotal, "Valor Total de Impressão Color dentro da Franquia (páginas)");
            $objExcel->getActiveSheet()->getStyle($labelColorFranquiaTotal)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_RIGHT);
            $objExcel->getActiveSheet()->getStyle($labelColorFranquiaTotal)->getFont()->setBold(true)->setSize(9)->setColor(new PHPExcel_Style_Color(PHPExcel_Style_Color::COLOR_DARKBLUE));
            $objExcel->getActiveSheet()->getStyle($labelColorFranquiaTotal)->getFill()->setFillType(PHPExcel_Style_Fill::FILL_SOLID)->getStartColor()->setARGB('E6E6FA');
            $objExcel->getActiveSheet()->getStyle($labelColorFranquiaTotal)->applyFromArray($styleBorderOutline);
            //VALOR DE IMPRESSAO UNITÁRIO
            $celulaColorFranquiaTotal = "E" . ($actualLinePlus);
            $celulaColorFranquiaTotalMerge = "G" . ($actualLinePlus);
            $formulaColorFranquiaTotal = "=(" . $celulaColorFranquiaPaginas . "*" . $celulaColorFranquiaUnitario . ")";
            $objExcel->getActiveSheet()->getStyle($celulaColorFranquiaTotal)->getFont()->setSize(9);
            $objExcel->getActiveSheet()->getStyle($celulaColorFranquiaTotal)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_RIGHT);
            $objExcel->getActiveSheet()->getStyle("{$celulaColorFranquiaTotal}:{$celulaColorFranquiaTotalMerge}")->applyFromArray($styleBorderOutline);
            $objExcel->getActiveSheet()->getStyle("{$celulaColorFranquiaTotal}:{$celulaColorFranquiaTotalMerge}")->getNumberFormat()->setFormatCode(PHPExcel_Style_NumberFormat::FORMAT_CURRENCY_REAL);
            $objExcel->getActiveSheet()->mergeCells("{$celulaColorFranquiaTotal}:{$celulaColorFranquiaTotalMerge}");
            $objExcel->getActiveSheet()->setCellValue($celulaColorFranquiaTotal, $formulaColorFranquiaTotal);


            //COLOR EXCEDENTE DA FRANQUIA
            //TOTAL DE PAGINAS
            //Total de Páginas Color excedente da franquia (Mensal)
            $actualLinePlus++;
            $labelColorExcedentePaginas = "D" . $actualLinePlus;
            $objExcel->getActiveSheet()->setCellValue($labelColorExcedentePaginas, "Total de Páginas Color excedente da franquia (Mensal)");
            $objExcel->getActiveSheet()->getStyle($labelColorExcedentePaginas)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_RIGHT);
            $objExcel->getActiveSheet()->getStyle($labelColorExcedentePaginas)->getFont()->setBold(true)->setSize(9)->setColor(new PHPExcel_Style_Color(PHPExcel_Style_Color::COLOR_DARKBLUE));
            $objExcel->getActiveSheet()->getStyle($labelColorExcedentePaginas)->getFill()->setFillType(PHPExcel_Style_Fill::FILL_SOLID)->getStartColor()->setARGB('E6E6FA');
            $objExcel->getActiveSheet()->getStyle($labelColorExcedentePaginas)->applyFromArray($styleBorderOutline);
            //Célula 'Total Páginas'
            $celulaColorExcedentePaginas = "E" . ($actualLinePlus);
            $celulaColorExcedentePaginasMerge = "G" . ($actualLinePlus);

            $celulaSubtotalExcedenteColor = $celulaSubtotalColor;

            $formulaColorExcedente = "=(" . $celulaSubtotalExcedenteColor . " - " . $celulaSubtotal . "*" . $ci->fields["franquia_color"] . ")";

            $saldoColorExcedente = $objExcel->getActiveSheet()->getCell($celulaSubtotalExcedenteColor)->getCalculatedValue() - ($objExcel->getActiveSheet()->getCell($celulaSubtotal)->getCalculatedValue() * $ci->fields["franquia_color"]);

            $creditoColorExcedente = 0;
            if ($saldoColorExcedente < 0) {
                $creditoColorExcedente = $saldoColorExcedente * -1;
                $formulaColorExcedente = 0;
            }

            $objExcel->getActiveSheet()->getStyle($celulaColorExcedentePaginas)->getFont()->setBold(true)->setSize(9);
            $objExcel->getActiveSheet()->getStyle($celulaColorExcedentePaginas)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
            $objExcel->getActiveSheet()->getStyle("{$celulaColorExcedentePaginas}:{$celulaColorExcedentePaginasMerge}")->applyFromArray($styleBorderOutline);
            $objExcel->getActiveSheet()->getStyle("{$celulaColorExcedentePaginas}:{$celulaColorExcedentePaginasMerge}")->getNumberFormat()->setFormatCode(PHPExcel_Style_NumberFormat::FORMAT_NUMBER_MILHAR);
            $objExcel->getActiveSheet()->getStyle("{$celulaColorExcedentePaginas}:{$celulaColorExcedentePaginasMerge}")->getFont()->setColor(new PHPExcel_Style_Color(PHPExcel_Style_Color::COLOR_DARKBLUE));
            $objExcel->getActiveSheet()->mergeCells("{$celulaColorExcedentePaginas}:{$celulaColorExcedentePaginasMerge}");
            $objExcel->getActiveSheet()->setCellValue($celulaColorExcedentePaginas, $formulaColorExcedente);

            if ($creditoColorExcedente > 0)
            //$objExcel->getActiveSheet()->getComment($celulaColorExcedentePaginas)->getText()->createTextRun("Lançado um crédito de {$creditoColorExcedente} páginas para os próximos meses.");
            //VALOR DE IMPRESSAO
            //Valor de Impressão Color excedente da Franquia (unitário)
                $actualLinePlus++;
            $labelColorExcedenteUnitario = "D" . ($actualLinePlus);
            $objExcel->getActiveSheet()->setCellValue($labelColorExcedenteUnitario, "Valor de Impressão Color excedente da Franquia (unitário)");
            $objExcel->getActiveSheet()->getStyle($labelColorExcedenteUnitario)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_RIGHT);
            $objExcel->getActiveSheet()->getStyle($labelColorExcedenteUnitario)->getFont()->setBold(true)->setSize(9)->setColor(new PHPExcel_Style_Color(PHPExcel_Style_Color::COLOR_DARKBLUE));
            $objExcel->getActiveSheet()->getStyle($labelColorExcedenteUnitario)->getFill()->setFillType(PHPExcel_Style_Fill::FILL_SOLID)->getStartColor()->setARGB('E6E6FA');
            $objExcel->getActiveSheet()->getStyle($labelColorExcedenteUnitario)->applyFromArray($styleBorderOutline);
            //VALOR DE IMPRESSAO UNITÁRIO
            $celulaColorExcedenteUnitario = "E" . ($actualLinePlus);
            $celulaColorExcedenteUnitarioMerge = "G" . ($actualLinePlus);
            $objExcel->getActiveSheet()->getStyle($celulaColorExcedenteUnitario)->getFont()->setSize(9);
            $objExcel->getActiveSheet()->getStyle($celulaColorExcedenteUnitario)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_RIGHT);
            $objExcel->getActiveSheet()->getStyle("{$celulaColorExcedenteUnitario}:{$celulaColorExcedenteUnitarioMerge}")->applyFromArray($styleBorderOutline);
            $objExcel->getActiveSheet()->getStyle("{$celulaColorExcedenteUnitario}:{$celulaColorExcedenteUnitarioMerge}")->getNumberFormat()->setFormatCode(PHPExcel_Style_NumberFormat::FORMAT_CURRENCY_REAL4);
            $objExcel->getActiveSheet()->mergeCells("{$celulaColorExcedenteUnitario}:{$celulaColorExcedenteUnitarioMerge}");
            $objExcel->getActiveSheet()->setCellValue($celulaColorExcedenteUnitario, $ci->fields["valor_impressao_color"]);
            //VALOR TOTAL DE IMPRESSAO
            //Valor Total de Impressão Color excedente da Franquia (páginas)
            $actualLinePlus++;
            $labelColorExcedenteTotal = "D" . ($actualLinePlus);
            $objExcel->getActiveSheet()->setCellValue($labelColorExcedenteTotal, "Valor Total de Impressão Color excedente da Franquia (páginas)");
            $objExcel->getActiveSheet()->getStyle($labelColorExcedenteTotal)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_RIGHT);
            $objExcel->getActiveSheet()->getStyle($labelColorExcedenteTotal)->getFont()->setBold(true)->setSize(9)->setColor(new PHPExcel_Style_Color(PHPExcel_Style_Color::COLOR_DARKBLUE));
            $objExcel->getActiveSheet()->getStyle($labelColorExcedenteTotal)->getFill()->setFillType(PHPExcel_Style_Fill::FILL_SOLID)->getStartColor()->setARGB('E6E6FA');
            $objExcel->getActiveSheet()->getStyle($labelColorExcedenteTotal)->applyFromArray($styleBorderOutline);
            //VALOR DE IMPRESSAO UNITÁRIO
            $celulaColorExcedenteTotal = "E" . ($actualLinePlus);
            $celulaColorExcedenteTotalMerge = "G" . ($actualLinePlus);
            $formulaColorExcedenteTotal = "=(" . $celulaColorExcedentePaginas . "*" . $celulaColorExcedenteUnitario . ")";
            $objExcel->getActiveSheet()->getStyle($celulaColorExcedenteTotal)->getFont()->setSize(9);
            $objExcel->getActiveSheet()->getStyle($celulaColorExcedenteTotal)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_RIGHT);
            $objExcel->getActiveSheet()->getStyle("{$celulaColorExcedenteTotal}:{$celulaColorExcedenteTotalMerge}")->applyFromArray($styleBorderOutline);
            $objExcel->getActiveSheet()->getStyle("{$celulaColorExcedenteTotal}:{$celulaColorExcedenteTotalMerge}")->getNumberFormat()->setFormatCode(PHPExcel_Style_NumberFormat::FORMAT_CURRENCY_REAL);
            $objExcel->getActiveSheet()->mergeCells("{$celulaColorExcedenteTotal}:{$celulaColorExcedenteTotalMerge}");
            $objExcel->getActiveSheet()->setCellValue($celulaColorExcedenteTotal, $formulaColorExcedenteTotal);

            $custoTotalExtra .= "+" . $celulaColorFranquiaTotal . "+" . $celulaColorExcedenteTotal;
        }

        if ($ci->fields["valor_impressao_a3_mono_franquia"] > 0 || $ci->fields["valor_impressao_a3_mono"] > 0) {
            //A3 MONO DENTRO DA FRANQUIA
            //TOTAL DE PAGINAS
            //Total de Páginas A3Mono dentro da franquia (Mensal)
            $actualLinePlus++;
            $labelA3MonoFranquiaPaginas = "D" . ($actualLinePlus);
            $objExcel->getActiveSheet()->setCellValue($labelA3MonoFranquiaPaginas, "Total de Páginas A3 Mono dentro da franquia (Mensal)");
            $objExcel->getActiveSheet()->getStyle($labelA3MonoFranquiaPaginas)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_RIGHT);
            $objExcel->getActiveSheet()->getStyle($labelA3MonoFranquiaPaginas)->getFont()->setBold(true)->setSize(9)->setColor(new PHPExcel_Style_Color(PHPExcel_Style_Color::COLOR_DARKBLUE));
            $objExcel->getActiveSheet()->getStyle($labelA3MonoFranquiaPaginas)->getFill()->setFillType(PHPExcel_Style_Fill::FILL_SOLID)->getStartColor()->setARGB('E6E6FA');
            $objExcel->getActiveSheet()->getStyle($labelA3MonoFranquiaPaginas)->applyFromArray($styleBorderOutline);
            //Célula 'Total Páginas'
            $celulaA3MonoFranquiaPaginas = "E" . ($actualLinePlus);
            $celulaA3MonoFranquiaPaginasMerge = "G" . ($actualLinePlus);
            $formulaA3MonoFranquiaPaginas = "=(" . $celulaSubtotal . "*" . $ci->fields["franquia_a3_mono"] . ")";
            $objExcel->getActiveSheet()->getStyle($celulaA3MonoFranquiaPaginas)->getFont()->setBold(true)->setSize(9);
            $objExcel->getActiveSheet()->getStyle($celulaA3MonoFranquiaPaginas)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
            $objExcel->getActiveSheet()->getStyle("{$celulaA3MonoFranquiaPaginas}:{$celulaA3MonoFranquiaPaginasMerge}")->applyFromArray($styleBorderOutline);
            $objExcel->getActiveSheet()->getStyle("{$celulaA3MonoFranquiaPaginas}:{$celulaA3MonoFranquiaPaginasMerge}")->getNumberFormat()->setFormatCode(PHPExcel_Style_NumberFormat::FORMAT_NUMBER_MILHAR);
            $objExcel->getActiveSheet()->getStyle("{$celulaA3MonoFranquiaPaginas}:{$celulaA3MonoFranquiaPaginasMerge}")->getFont()->setColor(new PHPExcel_Style_Color(PHPExcel_Style_Color::COLOR_DARKBLUE));
            $objExcel->getActiveSheet()->mergeCells("{$celulaA3MonoFranquiaPaginas}:{$celulaA3MonoFranquiaPaginasMerge}");
            $objExcel->getActiveSheet()->setCellValue($celulaA3MonoFranquiaPaginas, $formulaA3MonoFranquiaPaginas);

            //VALOR DE IMPRESSAO
            $actualLinePlus++;
            //Valor de Impressão A3Mono dentro da Franquia (unitário)
            $labelA3MonoFranquiaUnitario = "D" . ($actualLinePlus);
            $objExcel->getActiveSheet()->setCellValue($labelA3MonoFranquiaUnitario, "Valor de Impressão A3 Mono dentro da Franquia (unitário)");
            $objExcel->getActiveSheet()->getStyle($labelA3MonoFranquiaUnitario)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_RIGHT);
            $objExcel->getActiveSheet()->getStyle($labelA3MonoFranquiaUnitario)->getFont()->setBold(true)->setSize(9)->setColor(new PHPExcel_Style_Color(PHPExcel_Style_Color::COLOR_DARKBLUE));
            $objExcel->getActiveSheet()->getStyle($labelA3MonoFranquiaUnitario)->getFill()->setFillType(PHPExcel_Style_Fill::FILL_SOLID)->getStartColor()->setARGB('E6E6FA');
            $objExcel->getActiveSheet()->getStyle($labelA3MonoFranquiaUnitario)->applyFromArray($styleBorderOutline);
            //VALOR DE IMPRESSAO UNITÁRIO
            $celulaA3MonoFranquiaUnitario = "E" . ($actualLinePlus);
            $celulaA3MonoFranquiaUnitarioMerge = "G" . ($actualLinePlus);
            $objExcel->getActiveSheet()->getStyle($celulaA3MonoFranquiaUnitario)->getFont()->setSize(9);
            $objExcel->getActiveSheet()->getStyle($celulaA3MonoFranquiaUnitario)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_RIGHT);
            $objExcel->getActiveSheet()->getStyle("{$celulaA3MonoFranquiaUnitario}:{$celulaA3MonoFranquiaUnitarioMerge}")->applyFromArray($styleBorderOutline);
            $objExcel->getActiveSheet()->getStyle("{$celulaA3MonoFranquiaUnitario}:{$celulaA3MonoFranquiaUnitarioMerge}")->getNumberFormat()->setFormatCode(PHPExcel_Style_NumberFormat::FORMAT_CURRENCY_REAL4);
            $objExcel->getActiveSheet()->mergeCells("{$celulaA3MonoFranquiaUnitario}:{$celulaA3MonoFranquiaUnitarioMerge}");
            $objExcel->getActiveSheet()->setCellValue($celulaA3MonoFranquiaUnitario, $ci->fields["valor_impressao_a3_mono_franquia"]);

            //VALOR TOTAL DE IMPRESSAO
            $actualLinePlus++;
            //Valor Total de Impressão A3Mono dentro da Franquia (páginas)
            $labelA3MonoFranquiaTotal = "D" . ($actualLinePlus);
            $objExcel->getActiveSheet()->setCellValue($labelA3MonoFranquiaTotal, "Valor Total de Impressão A3 Mono dentro da Franquia (páginas)");
            $objExcel->getActiveSheet()->getStyle($labelA3MonoFranquiaTotal)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_RIGHT);
            $objExcel->getActiveSheet()->getStyle($labelA3MonoFranquiaTotal)->getFont()->setBold(true)->setSize(9)->setColor(new PHPExcel_Style_Color(PHPExcel_Style_Color::COLOR_DARKBLUE));
            $objExcel->getActiveSheet()->getStyle($labelA3MonoFranquiaTotal)->getFill()->setFillType(PHPExcel_Style_Fill::FILL_SOLID)->getStartColor()->setARGB('E6E6FA');
            $objExcel->getActiveSheet()->getStyle($labelA3MonoFranquiaTotal)->applyFromArray($styleBorderOutline);
            //VALOR DE IMPRESSAO UNITÁRIO
            $celulaA3MonoFranquiaTotal = "E" . ($actualLinePlus);
            $celulaA3MonoFranquiaTotalMerge = "G" . ($actualLinePlus);
            $formulaA3MonoFranquiaTotal = "=(" . $celulaA3MonoFranquiaPaginas . "*" . $celulaA3MonoFranquiaUnitario . ")";
            $objExcel->getActiveSheet()->getStyle($celulaA3MonoFranquiaTotal)->getFont()->setSize(9);
            $objExcel->getActiveSheet()->getStyle($celulaA3MonoFranquiaTotal)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_RIGHT);
            $objExcel->getActiveSheet()->getStyle("{$celulaA3MonoFranquiaTotal}:{$celulaA3MonoFranquiaTotalMerge}")->applyFromArray($styleBorderOutline);
            $objExcel->getActiveSheet()->getStyle("{$celulaA3MonoFranquiaTotal}:{$celulaA3MonoFranquiaTotalMerge}")->getNumberFormat()->setFormatCode(PHPExcel_Style_NumberFormat::FORMAT_CURRENCY_REAL);
            $objExcel->getActiveSheet()->mergeCells("{$celulaA3MonoFranquiaTotal}:{$celulaA3MonoFranquiaTotalMerge}");
            $objExcel->getActiveSheet()->setCellValue($celulaA3MonoFranquiaTotal, $formulaA3MonoFranquiaTotal);


            //A3Mono EXCEDENTE DA FRANQUIA
            //TOTAL DE PAGINAS
            //Total de Páginas A3Mono excedente da franquia (Mensal)
            $actualLinePlus++;
            $labelA3MonoExcedentePaginas = "D" . $actualLinePlus;
            $objExcel->getActiveSheet()->setCellValue($labelA3MonoExcedentePaginas, "Total de Páginas A3 Mono excedente da franquia (Mensal)");
            $objExcel->getActiveSheet()->getStyle($labelA3MonoExcedentePaginas)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_RIGHT);
            $objExcel->getActiveSheet()->getStyle($labelA3MonoExcedentePaginas)->getFont()->setBold(true)->setSize(9)->setColor(new PHPExcel_Style_Color(PHPExcel_Style_Color::COLOR_DARKBLUE));
            $objExcel->getActiveSheet()->getStyle($labelA3MonoExcedentePaginas)->getFill()->setFillType(PHPExcel_Style_Fill::FILL_SOLID)->getStartColor()->setARGB('E6E6FA');
            $objExcel->getActiveSheet()->getStyle($labelA3MonoExcedentePaginas)->applyFromArray($styleBorderOutline);
            //Célula 'Total Páginas'
            $celulaA3MonoExcedentePaginas = "E" . ($actualLinePlus);
            $celulaA3MonoExcedentePaginasMerge = "G" . ($actualLinePlus);

            $celulaSubtotalExcedenteA3Mono = $celulaSubtotalA3Mono;

            $formulaA3MonoExcedente = "=(" . $celulaSubtotalExcedenteA3Mono . " - " . $celulaSubtotal . "*" . $ci->fields["franquia_a3_mono"] . ")";

            $saldoA3MonoExcedente = $objExcel->getActiveSheet()->getCell($celulaSubtotalExcedenteA3Mono)->getCalculatedValue() - ($objExcel->getActiveSheet()->getCell($celulaSubtotal)->getCalculatedValue() * $ci->fields["franquia_a3_mono"]);

            $creditoA3MonoExcedente = 0;
            if ($saldoA3MonoExcedente < 0) {
                $creditoA3MonoExcedente = $saldoA3MonoExcedente * -1;
                $formulaA3MonoExcedente = 0;
            }

            $objExcel->getActiveSheet()->getStyle($celulaA3MonoExcedentePaginas)->getFont()->setBold(true)->setSize(9);
            $objExcel->getActiveSheet()->getStyle($celulaA3MonoExcedentePaginas)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
            $objExcel->getActiveSheet()->getStyle("{$celulaA3MonoExcedentePaginas}:{$celulaA3MonoExcedentePaginasMerge}")->applyFromArray($styleBorderOutline);
            $objExcel->getActiveSheet()->getStyle("{$celulaA3MonoExcedentePaginas}:{$celulaA3MonoExcedentePaginasMerge}")->getNumberFormat()->setFormatCode(PHPExcel_Style_NumberFormat::FORMAT_NUMBER_MILHAR);
            $objExcel->getActiveSheet()->getStyle("{$celulaA3MonoExcedentePaginas}:{$celulaA3MonoExcedentePaginasMerge}")->getFont()->setColor(new PHPExcel_Style_Color(PHPExcel_Style_Color::COLOR_DARKBLUE));
            $objExcel->getActiveSheet()->mergeCells("{$celulaA3MonoExcedentePaginas}:{$celulaA3MonoExcedentePaginasMerge}");
            $objExcel->getActiveSheet()->setCellValue($celulaA3MonoExcedentePaginas, $formulaA3MonoExcedente);

            if ($creditoA3MonoExcedente > 0)
            //	$objExcel->getActiveSheet()->getComment($celulaA3MonoExcedentePaginas)->getText()->createTextRun("Lançado um crédito de {$creditoA3MonoExcedente} páginas para os próximos meses.");
            //VALOR DE IMPRESSAO
            //Valor de Impressão A3Mono excedente da Franquia (unitário)
                $actualLinePlus++;
            $labelA3MonoExcedenteUnitario = "D" . ($actualLinePlus);
            $objExcel->getActiveSheet()->setCellValue($labelA3MonoExcedenteUnitario, "Valor de Impressão A3 Mono excedente da Franquia (unitário)");
            $objExcel->getActiveSheet()->getStyle($labelA3MonoExcedenteUnitario)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_RIGHT);
            $objExcel->getActiveSheet()->getStyle($labelA3MonoExcedenteUnitario)->getFont()->setBold(true)->setSize(9)->setColor(new PHPExcel_Style_Color(PHPExcel_Style_Color::COLOR_DARKBLUE));
            $objExcel->getActiveSheet()->getStyle($labelA3MonoExcedenteUnitario)->getFill()->setFillType(PHPExcel_Style_Fill::FILL_SOLID)->getStartColor()->setARGB('E6E6FA');
            $objExcel->getActiveSheet()->getStyle($labelA3MonoExcedenteUnitario)->applyFromArray($styleBorderOutline);
            //VALOR DE IMPRESSAO UNITÁRIO
            $celulaA3MonoExcedenteUnitario = "E" . ($actualLinePlus);
            $celulaA3MonoExcedenteUnitarioMerge = "G" . ($actualLinePlus);
            $objExcel->getActiveSheet()->getStyle($celulaA3MonoExcedenteUnitario)->getFont()->setSize(9);
            $objExcel->getActiveSheet()->getStyle($celulaA3MonoExcedenteUnitario)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_RIGHT);
            $objExcel->getActiveSheet()->getStyle("{$celulaA3MonoExcedenteUnitario}:{$celulaA3MonoExcedenteUnitarioMerge}")->applyFromArray($styleBorderOutline);
            $objExcel->getActiveSheet()->getStyle("{$celulaA3MonoExcedenteUnitario}:{$celulaA3MonoExcedenteUnitarioMerge}")->getNumberFormat()->setFormatCode(PHPExcel_Style_NumberFormat::FORMAT_CURRENCY_REAL4);
            $objExcel->getActiveSheet()->mergeCells("{$celulaA3MonoExcedenteUnitario}:{$celulaA3MonoExcedenteUnitarioMerge}");
            $objExcel->getActiveSheet()->setCellValue($celulaA3MonoExcedenteUnitario, $ci->fields["valor_impressao_a3_mono"]);
            //VALOR TOTAL DE IMPRESSAO
            //Valor Total de Impressão A3Mono excedente da Franquia (páginas)
            $actualLinePlus++;
            $labelA3MonoExcedenteTotal = "D" . ($actualLinePlus);
            $objExcel->getActiveSheet()->setCellValue($labelA3MonoExcedenteTotal, "Valor Total de Impressão A3 Mono excedente da Franquia (páginas)");
            $objExcel->getActiveSheet()->getStyle($labelA3MonoExcedenteTotal)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_RIGHT);
            $objExcel->getActiveSheet()->getStyle($labelA3MonoExcedenteTotal)->getFont()->setBold(true)->setSize(9)->setColor(new PHPExcel_Style_Color(PHPExcel_Style_Color::COLOR_DARKBLUE));
            $objExcel->getActiveSheet()->getStyle($labelA3MonoExcedenteTotal)->getFill()->setFillType(PHPExcel_Style_Fill::FILL_SOLID)->getStartColor()->setARGB('E6E6FA');
            $objExcel->getActiveSheet()->getStyle($labelA3MonoExcedenteTotal)->applyFromArray($styleBorderOutline);
            //VALOR DE IMPRESSAO UNITÁRIO
            $celulaA3MonoExcedenteTotal = "E" . ($actualLinePlus);
            $celulaA3MonoExcedenteTotalMerge = "G" . ($actualLinePlus);
            $formulaA3MonoExcedenteTotal = "=(" . $celulaA3MonoExcedentePaginas . "*" . $celulaA3MonoExcedenteUnitario . ")";
            $objExcel->getActiveSheet()->getStyle($celulaA3MonoExcedenteTotal)->getFont()->setSize(9);
            $objExcel->getActiveSheet()->getStyle($celulaA3MonoExcedenteTotal)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_RIGHT);
            $objExcel->getActiveSheet()->getStyle("{$celulaA3MonoExcedenteTotal}:{$celulaA3MonoExcedenteTotalMerge}")->applyFromArray($styleBorderOutline);
            $objExcel->getActiveSheet()->getStyle("{$celulaA3MonoExcedenteTotal}:{$celulaA3MonoExcedenteTotalMerge}")->getNumberFormat()->setFormatCode(PHPExcel_Style_NumberFormat::FORMAT_CURRENCY_REAL);
            $objExcel->getActiveSheet()->mergeCells("{$celulaA3MonoExcedenteTotal}:{$celulaA3MonoExcedenteTotalMerge}");
            $objExcel->getActiveSheet()->setCellValue($celulaA3MonoExcedenteTotal, $formulaA3MonoExcedenteTotal);

            $custoTotalExtra .= "+" . $celulaA3MonoFranquiaTotal . "+" . $celulaA3MonoExcedenteTotal;
        }

        //A3 MONO DENTRO DA FRANQUIA
        //TOTAL DE PAGINAS
        //VALOR DE IMPRESSAO
        //VALOR TOTAL DE IMPRESSAO
        //A3 MONO EXCEDENTE DA FRANQUIA
        //TOTAL DE PAGINAS
        //VALOR DE IMPRESSAO
        //VALOR TOTAL DE IMPRESSAO
        //A3 COLOR DENTRO DA FRANQUIA
        //TOTAL DE PAGINAS
        //VALOR DE IMPRESSAO
        //VALOR TOTAL DE IMPRESSAO
        //A3 COLOR EXCEDENTE DA FRANQUIA
        //TOTAL DE PAGINAS
        //VALOR DE IMPRESSAO
        //VALOR TOTAL DE IMPRESSAO
        //DIGITALIZACAO DENTRO DA FRANQUIA
        //TOTAL DE PAGINAS
        //VALOR DE IMPRESSAO
        //VALOR TOTAL DE IMPRESSAO
        //DIGITALIZACAO EXCEDENTE DA FRANQUIA
        //TOTAL DE PAGINAS
        //VALOR DE IMPRESSAO
        //VALOR TOTAL DE IMPRESSAO
        //PLOTAGEM DENTRO DA FRANQUIA
        //METRAGEM IMPRESSA
        //VALOR POR METRO
        //VALOR TOTAL DE IMPRESSAO
        //PLOTAGEM EXCEDENTE DA FRANQUIA
        //METRAGEM IMPRESSA
        //VALOR POR METRO
        //VALOR TOTAL DE IMPRESSAO
    }
    else {

        //CUSTO UNITARIO
        //Label
        $labelCustoUnitario = "D" . ($actualLine + 2);
        $objExcel->getActiveSheet()->setCellValue($labelCustoUnitario, 'Custo do Equipamento (unitário)');
        $objExcel->getActiveSheet()->getStyle($labelCustoUnitario)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_RIGHT);
        $objExcel->getActiveSheet()->getStyle($labelCustoUnitario)->getFont()->setBold(true)->setSize(9)->setColor(new PHPExcel_Style_Color(PHPExcel_Style_Color::COLOR_DARKBLUE));
        $objExcel->getActiveSheet()->getStyle($labelCustoUnitario)->getFill()->setFillType(PHPExcel_Style_Fill::FILL_SOLID)->getStartColor()->setARGB('E6E6FA');
        $objExcel->getActiveSheet()->getStyle($labelCustoUnitario)->applyFromArray($styleBorderOutline);
        //Célula
        $celulaCustoUnitario = "E" . ($actualLine + 2);
        $celulaCustoUnitarioMerge = "G" . ($actualLine + 2);
        $extraCustoUnitario = "(((" . $ci->fields["valor_aluguel"] . ")/30)*" . $diasTarifacaoExtra . ")";
        $formulaCustoUnitario = "=(" . $ci->fields["valor_aluguel"] . "+" . $extraCustoUnitario . ")";
        $objExcel->getActiveSheet()->getStyle($celulaCustoUnitario)->getFont()->setSize(9);
        $objExcel->getActiveSheet()->getStyle($celulaCustoUnitario)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_RIGHT);
        $objExcel->getActiveSheet()->getStyle("{$celulaCustoUnitario}:{$celulaCustoUnitarioMerge}")->applyFromArray($styleBorderOutline);
        $objExcel->getActiveSheet()->getStyle("{$celulaCustoUnitario}:{$celulaCustoUnitarioMerge}")->getNumberFormat()->setFormatCode(PHPExcel_Style_NumberFormat::FORMAT_CURRENCY_REAL);
        $objExcel->getActiveSheet()->mergeCells("{$celulaCustoUnitario}:{$celulaCustoUnitarioMerge}");
        $objExcel->getActiveSheet()->setCellValue($celulaCustoUnitario, $formulaCustoUnitario);

        /*
          //Total de Páginas Mono
          $labelMonoPaginas = "D" . ($actualLine +3);
          $objExcel->getActiveSheet()->setCellValue($labelMonoPaginas, "Total de Páginas Mono");
          $objExcel->getActiveSheet()->getStyle($labelMonoPaginas)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_RIGHT);
          $objExcel->getActiveSheet()->getStyle($labelMonoPaginas)->getFont()->setBold(true)->setSize(9)->setColor( new PHPExcel_Style_Color( PHPExcel_Style_Color::COLOR_DARKBLUE ) );
          $objExcel->getActiveSheet()->getStyle($labelMonoPaginas)->getFill()->setFillType(PHPExcel_Style_Fill::FILL_SOLID)->getStartColor()->setARGB('FFFFBC16');
          $objExcel->getActiveSheet()->getStyle($labelMonoPaginas)->applyFromArray($styleBorderOutline);
          //Célula 'Total Páginas'
          $celulaMonoPaginas = "E" . ($actualLine +3);
          $celulaMonoPaginasMerge = "G" . ($actualLine +3);
          $formulaMonoPaginas = "=(" . $celulaSubtotalMono . ")";
          $objExcel->getActiveSheet()->getStyle($celulaMonoPaginas)->getFont()->setBold(true)->setSize(9);
          $objExcel->getActiveSheet()->getStyle($celulaMonoPaginas)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
          $objExcel->getActiveSheet()->getStyle("{$celulaMonoPaginas}:{$celulaMonoPaginasMerge}")->applyFromArray($styleBorderOutline);
          $objExcel->getActiveSheet()->getStyle("{$celulaMonoPaginas}:{$celulaMonoPaginasMerge}")->getNumberFormat()->setFormatCode(PHPExcel_Style_NumberFormat::FORMAT_NUMBER_MILHAR);
          $objExcel->getActiveSheet()->getStyle("{$celulaMonoPaginas}:{$celulaMonoPaginasMerge}")->getFont()->setColor( new PHPExcel_Style_Color( PHPExcel_Style_Color::COLOR_DARKBLUE ) );
          $objExcel->getActiveSheet()->mergeCells("{$celulaMonoPaginas}:{$celulaMonoPaginasMerge}");
          $objExcel->getActiveSheet()->setCellValue($celulaMonoPaginas, $formulaMonoPaginas);
         */

        //Valor de Impressão Mono dentro da Franquia (unitário)
        $labelMonoFranquiaUnitario = "D" . ($actualLine + 3);
        $objExcel->getActiveSheet()->setCellValue($labelMonoFranquiaUnitario, "Valor de Impressão Mono (unitário)");
        $objExcel->getActiveSheet()->getStyle($labelMonoFranquiaUnitario)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_RIGHT);
        $objExcel->getActiveSheet()->getStyle($labelMonoFranquiaUnitario)->getFont()->setBold(true)->setSize(9)->setColor(new PHPExcel_Style_Color(PHPExcel_Style_Color::COLOR_DARKBLUE));
        $objExcel->getActiveSheet()->getStyle($labelMonoFranquiaUnitario)->getFill()->setFillType(PHPExcel_Style_Fill::FILL_SOLID)->getStartColor()->setARGB('E6E6FA');
        $objExcel->getActiveSheet()->getStyle($labelMonoFranquiaUnitario)->applyFromArray($styleBorderOutline);
        //VALOR DE IMPRESSAO UNITÁRIO
        $celulaMonoFranquiaUnitario = "E" . ($actualLine + 3);
        $celulaMonoFranquiaUnitarioMerge = "G" . ($actualLine + 3);
        $objExcel->getActiveSheet()->getStyle($celulaMonoFranquiaUnitario)->getFont()->setSize(9);
        $objExcel->getActiveSheet()->getStyle($celulaMonoFranquiaUnitario)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_RIGHT);
        $objExcel->getActiveSheet()->getStyle("{$celulaMonoFranquiaUnitario}:{$celulaMonoFranquiaUnitarioMerge}")->applyFromArray($styleBorderOutline);
        $objExcel->getActiveSheet()->getStyle("{$celulaMonoFranquiaUnitario}:{$celulaMonoFranquiaUnitarioMerge}")->getNumberFormat()->setFormatCode(PHPExcel_Style_NumberFormat::FORMAT_CURRENCY_REAL4);
        $objExcel->getActiveSheet()->mergeCells("{$celulaMonoFranquiaUnitario}:{$celulaMonoFranquiaUnitarioMerge}");
        $objExcel->getActiveSheet()->setCellValue($celulaMonoFranquiaUnitario, $ci->fields["valor_impressao_mono_franquia"]);
        //Valor Total de Impressão Mono dentro da Franquia (páginas)
        $labelMonoFranquiaTotal = "D" . ($actualLine + 4);
        $objExcel->getActiveSheet()->setCellValue($labelMonoFranquiaTotal, "Valor Total de Impressão Mono (páginas)");
        $objExcel->getActiveSheet()->getStyle($labelMonoFranquiaTotal)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_RIGHT);
        $objExcel->getActiveSheet()->getStyle($labelMonoFranquiaTotal)->getFont()->setBold(true)->setSize(9)->setColor(new PHPExcel_Style_Color(PHPExcel_Style_Color::COLOR_DARKBLUE));
        $objExcel->getActiveSheet()->getStyle($labelMonoFranquiaTotal)->getFill()->setFillType(PHPExcel_Style_Fill::FILL_SOLID)->getStartColor()->setARGB('E6E6FA');
        $objExcel->getActiveSheet()->getStyle($labelMonoFranquiaTotal)->applyFromArray($styleBorderOutline);
        //VALOR DE IMPRESSAO UNITÁRIO
        $celulaMonoFranquiaTotal = "E" . ($actualLine + 4);
        $celulaMonoFranquiaTotalMerge = "G" . ($actualLine + 4);
        $formulaMonoFranquiaTotal = "=(" . $celulaSubtotalMono . "*" . $celulaMonoFranquiaUnitario . ")";
        $objExcel->getActiveSheet()->getStyle($celulaMonoFranquiaTotal)->getFont()->setSize(9);
        $objExcel->getActiveSheet()->getStyle($celulaMonoFranquiaTotal)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_RIGHT);
        $objExcel->getActiveSheet()->getStyle("{$celulaMonoFranquiaTotal}:{$celulaMonoFranquiaTotalMerge}")->applyFromArray($styleBorderOutline);
        $objExcel->getActiveSheet()->getStyle("{$celulaMonoFranquiaTotal}:{$celulaMonoFranquiaTotalMerge}")->getNumberFormat()->setFormatCode(PHPExcel_Style_NumberFormat::FORMAT_CURRENCY_REAL);
        $objExcel->getActiveSheet()->mergeCells("{$celulaMonoFranquiaTotal}:{$celulaMonoFranquiaTotalMerge}");
        $objExcel->getActiveSheet()->setCellValue($celulaMonoFranquiaTotal, $formulaMonoFranquiaTotal);

//print "<pre>";
//print_r($options);
//print "</pre>";
//die('debug 1');

        $actualLinePlus = $actualLine + 4;

        $celulaMonoExcedenteTotal = "0";
        $custoTotalExtra = "0";
    }

//print "debug 1";
    //CUSTO TOTAL
    //Custo Total
    $actualLinePlus++;
    $labelCustoTotal = "D" . ($actualLinePlus);
    $labelCustoTotalMerge = "D" . ($actualLinePlus + 1);
    $objExcel->getActiveSheet()->setCellValue($labelCustoTotal, "Custo Total");
    $objExcel->getActiveSheet()->getStyle($labelCustoTotal)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_RIGHT)->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);
    $objExcel->getActiveSheet()->getStyle($labelCustoTotal)->getFont()->setBold(true)->setSize(9)->setColor(new PHPExcel_Style_Color(PHPExcel_Style_Color::COLOR_DARKBLUE));
    $objExcel->getActiveSheet()->getStyle($labelCustoTotal)->getFill()->setFillType(PHPExcel_Style_Fill::FILL_SOLID)->getStartColor()->setARGB('E6E6FA');
    $objExcel->getActiveSheet()->mergeCells("{$labelCustoTotal}:{$labelCustoTotalMerge}");
    $objExcel->getActiveSheet()->getStyle("{$labelCustoTotal}:{$labelCustoTotalMerge}")->applyFromArray($styleBorderOutline);
    //CUSTO TOTAL DE ALUGUEL
//print "debug 2";

    $celulaCustoTotalAluguel = "E" . ($actualLinePlus);
    //$celulaMonoExcedenteTotalMerge = "G" . ($actualLinePlus);
    $formulaCustoTotalAluguel = "=(" . $celulaSubtotal . "*" . $celulaCustoUnitario . " + " . $options['aluguel_pro_rata'] . ")";
    $objExcel->getActiveSheet()->getStyle($celulaCustoTotalAluguel)->getFont()->setSize(9);
    $objExcel->getActiveSheet()->getStyle($celulaCustoTotalAluguel)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_RIGHT);
    $objExcel->getActiveSheet()->getStyle("{$celulaCustoTotalAluguel}")->applyFromArray($styleBorderOutline);
    $objExcel->getActiveSheet()->getStyle("{$celulaCustoTotalAluguel}")->getNumberFormat()->setFormatCode(PHPExcel_Style_NumberFormat::FORMAT_CURRENCY_REAL);
    //$objExcel->getActiveSheet()->mergeCells("{$celulaCustoTotalAluguel}:{$celulaMonoExcedenteTotalMerge}");
    $objExcel->getActiveSheet()->setCellValue($celulaCustoTotalAluguel, $formulaCustoTotalAluguel);
    //CUSTO TOTAL DE PÁGINAS
//print "debug 3";

    $celulaCustoTotalPaginas = "G" . ($actualLinePlus);
    $formulaCustoTotalPaginas = "=SUM(" . $celulaMonoFranquiaTotal . "+" . $celulaMonoExcedenteTotal . $custoTotalExtra . ")";
    $objExcel->getActiveSheet()->getStyle($celulaCustoTotalPaginas)->getFont()->setSize(9);
    $objExcel->getActiveSheet()->getStyle($celulaCustoTotalPaginas)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_RIGHT);
    $objExcel->getActiveSheet()->getStyle("{$celulaCustoTotalPaginas}")->applyFromArray($styleBorderOutline);
    $objExcel->getActiveSheet()->getStyle("{$celulaCustoTotalPaginas}")->getNumberFormat()->setFormatCode(PHPExcel_Style_NumberFormat::FORMAT_CURRENCY_REAL);
    //$objExcel->getActiveSheet()->mergeCells("{$celulaCustoTotalAluguel}:{$celulaMonoExcedenteTotalMerge}");
    $objExcel->getActiveSheet()->setCellValue($celulaCustoTotalPaginas, $formulaCustoTotalPaginas);

//print "debug 4";
    //Merge label Custo Total pula uma linha
    $actualLinePlus++;
    //CUSTO TOTAL DE PÁGINAS
    $celulaCustoTotal = "E" . ($actualLinePlus);
    $celulaCustoTotalMerge = "G" . ($actualLinePlus);
//print "debug 5";
    $formulaCustoTotal = "=SUM(" . $celulaCustoTotalAluguel . "+" . $celulaCustoTotalPaginas . ")";

//print "debug 5a";
    $objExcel->getActiveSheet()->getStyle($celulaCustoTotal)->getFont()->setBold(true)->setSize(9);
    $objExcel->getActiveSheet()->getStyle($celulaCustoTotal)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_RIGHT);
    $objExcel->getActiveSheet()->getStyle("{$celulaCustoTotal}:{$celulaCustoTotalMerge}")->applyFromArray($styleBorderOutline);
    $objExcel->getActiveSheet()->getStyle("{$celulaCustoTotal}")->getNumberFormat()->setFormatCode(PHPExcel_Style_NumberFormat::FORMAT_CURRENCY_REAL);
    $objExcel->getActiveSheet()->mergeCells("{$celulaCustoTotal}:{$celulaCustoTotalMerge}");
    $objExcel->getActiveSheet()->setCellValue($celulaCustoTotal, $formulaCustoTotal);
//print "debug 6";

    $objExcel->getActiveSheet()->getStyle("{$celulaCustoTotalAluguel}:{$celulaCustoTotalMerge}")->getFill()->setFillType(PHPExcel_Style_Fill::FILL_SOLID);
    $objExcel->getActiveSheet()->getStyle("{$celulaCustoTotalAluguel}:{$celulaCustoTotalMerge}")->getFill()->getStartColor()->setARGB('FFF0DCDC');
    $objExcel->getActiveSheet()->getStyle("{$celulaCustoTotal}:{$celulaCustoTotalMerge}")->getFill()->getStartColor()->setARGB('E6E6FA');

//print "debug 7";
    //criar parametro bool se for para calcular totalExcedente e $arrayExcedente

    if ($options['is_sem_franquia'] == 1)
        $actualLinePlus += 2;

    $options['totalCustoTotalGeral'] .= $celulaCustoTotal . '+';

    /*
      //fórmula dos créditos aplicados
      $credito_mono = Tarifacao_Supridesk::getCredito($contractItemID, $param_POST['bilhetagem1'], "mono");

      $calcProcExcedente = array_key_exists("proc_excedente", $options) && $options["proc_excedente"] == true;

      //cálculo da soma dos excedentes
      $linhasExcedentes = 0;

      if ( $calcProcExcedente )
      $totalExcedente[$contractItemID] = 0;

      $totalMonoPrinted = 0;
      for( $i = $startLine + 1; $i <= $actualLine; $i++ )
      {
      $valorMono1 = $objExcel->getActiveSheet()->getCell("G".$i)->getCalculatedValue();
      $valorMono2 = $objExcel->getActiveSheet()->getCell("H".$i)->getCalculatedValue();
      $valorMonoDiferencial = $valorMono2 - $valorMono1;
      $totalMonoPrinted += $valorMonoDiferencial;
      if ( $valorMonoDiferencial - $ci->fields["franquia_mono"] > 0 )
      {
      if ( $calcProcExcedente )
      $totalExcedente[$contractItemID] += $valorMonoDiferencial - $ci->fields["franquia_mono"];
      $linhasExcedentes++;
      }
      }

      //O crédito dado não pode abaixar o número de páginas cobradas para aquém da franquia global do item
      if ( $calcProcExcedente )
      {
      $franquia_monoTotal = ($actualLine-$startLine)*$ci->fields["franquia_mono"];
      if ( $credito_mono > $totalMonoPrinted - $franquia_monoTotal )
      $totalExcedente[$contractItemID."_credito"] = $totalMonoPrinted - $franquia_monoTotal;
      else
      $totalExcedente[$contractItemID."_credito"] = $credito_mono;
      }
      $credito_mono = $totalExcedente[$contractItemID."_credito"];

      $decimalSUM = 0;
      $countLinhasExcedentes = 0;
      for( $i = $startLine + 1; $i <= $actualLine; $i++ )
      {
      //VALOR DE IMPRESSAO UNITÁRIO
      if ( $options['is_franquia_unica_mono_color'] != 1 )
      {
      $celulaMonoCredito = "I" . $i;
      $formulaMonoCredito = "=(" . $celulaMonoFranquiaPaginas . "*" . $celulaMonoFranquiaUnitario . ")";
      $formulaMonoCredito = "=".$celulaMonoFranquiaPaginas;
      $objExcel->getActiveSheet()->getStyle($celulaMonoCredito)->getNumberFormat()->setFormatCode(PHPExcel_Style_NumberFormat::FORMAT_NUMBER_MILHAR);
      }

      $valorMonoFranquiaPaginas = $objExcel->getActiveSheet()->getCell($celulaMonoFranquiaPaginas)->getCalculatedValue();
      $valorSubtotalMono = $objExcel->getActiveSheet()->getCell($celulaSubtotalMono)->getCalculatedValue();

      //se for agrupamento, não verifica se o CI excedeu, somente o equipamento.
      if ( $valorSubtotalMono > $valorMonoFranquiaPaginas || !$calcProcExcedente)
      {
      $valorMono1 = $objExcel->getActiveSheet()->getCell("G".$i)->getCalculatedValue();
      $valorMono2 = $objExcel->getActiveSheet()->getCell("H".$i)->getCalculatedValue();
      $valorMonoDiferencial = $valorMono2 - $valorMono1;
      if ( $valorMonoDiferencial - $ci->fields["franquia_mono"] > 0 )
      {
      $countLinhasExcedentes++;
      $valorMonoExcedente = $valorMonoDiferencial - $ci->fields["franquia_mono"];

      $fator = $valorMonoExcedente / $totalExcedente[$contractItemID];
      $valorFinal = $credito_mono * $fator;

      //crédito dado não pode baixar o consumo aquém da franquia
      if ( $valorFinal > $valorMonoDiferencial - $ci->fields["franquia_mono"] )
      $valorFinal = $valorMonoDiferencial - $ci->fields["franquia_mono"];

      $decimalSUM += $valorFinal - FLOOR($valorFinal);
      $valorFinal = FLOOR($valorFinal);

      //contar quantas linhas excederam e então verificar se é a última dessas linhas
      if ( $countLinhasExcedentes == $linhasExcedentes )
      {
      $valorFinal += CEIL($decimalSUM);
      $decimalSUM = 0;
      }
      else if ( $decimalSUM >= 1 )
      {
      $valorFinal++;
      $decimalSUM--;
      }

      if ( $options['is_franquia_unica_mono_color'] != 1 )
      $objExcel->getActiveSheet()->setCellValue($celulaMonoCredito, $valorFinal);
      }
      }
      }
     */

    //item de contrato
    $labelItem = "A" . ($actualLine + 1);
    $labelItemMerge = "C" . ($actualLine + 3);
    $objExcel->getActiveSheet()->setCellValue($labelItem, $ci->fields["nome"]);
    $objExcel->getActiveSheet()->getStyle($labelItem)->getFont()->setBold(true)->setSize(14);
    $objExcel->getActiveSheet()->getStyle($labelItem)->getFont()->setColor(new PHPExcel_Style_Color(PHPExcel_Style_Color::COLOR_DARKBLUE));
    $objExcel->getActiveSheet()->getStyle("{$labelItem}:{$labelItemMerge}")->getAlignment()->setWrapText(true);
    $objExcel->getActiveSheet()->getStyle($labelItem)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
    $objExcel->getActiveSheet()->getStyle($labelItem)->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);
    $objExcel->getActiveSheet()->mergeCells("{$labelItem}:{$labelItemMerge}");
    $objExcel->getActiveSheet()->getStyle("{$labelItem}:{$labelItemMerge}")->applyFromArray($styleBorderOutline);

    //comentário do item de contrato
    $labelComment = "A" . ($actualLine + 4);
    $labelCommentMerge = "C" . ($actualLine + 6);
    $objExcel->getActiveSheet()->setCellValue($labelComment, $ci->fields["comment"]);
    $objExcel->getActiveSheet()->getStyle($labelComment)->getFont()->setBold(true)->setSize(10);
    $objExcel->getActiveSheet()->getStyle($labelComment)->getFont()->setColor(new PHPExcel_Style_Color(PHPExcel_Style_Color::COLOR_DARKBLUE));
    $objExcel->getActiveSheet()->getStyle("{$labelComment}:{$labelCommentMerge}")->getAlignment()->setWrapText(true);
    $objExcel->getActiveSheet()->getStyle($labelComment)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
    $objExcel->getActiveSheet()->getStyle($labelComment)->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);
    $objExcel->getActiveSheet()->mergeCells("{$labelComment}:{$labelCommentMerge}");
    $objExcel->getActiveSheet()->getStyle("{$labelComment}:{$labelCommentMerge}")->applyFromArray($styleBorderOutline);


    for ($i = ($actualLine + 1); $i <= ($actualLinePlus + 1); $i++)
        $objExcel->getActiveSheet()->getRowDimension($i)->setRowHeight(12);

    //acrescimo das linhas do subtotal
    $actualLine = $actualLinePlus + 1;

    //linha cinza ao final de um item de contrato para separação
    $objExcel->getActiveSheet()->getStyle("A{$actualLine}:$lastColumn{$actualLine}")->getFill()->setFillType(PHPExcel_Style_Fill::FILL_SOLID);
    $objExcel->getActiveSheet()->getStyle("A{$actualLine}:$lastColumn{$actualLine}")->getFill()->getStartColor()->setARGB('C1CDCD');
    $objExcel->getActiveSheet()->getStyle("A{$actualLine}:$lastColumn{$actualLine}")->applyFromArray($styleBorderBottomGrid);
    $objExcel->getActiveSheet()->getRowDimension($actualLine)->setRowHeight(15);
}

?>