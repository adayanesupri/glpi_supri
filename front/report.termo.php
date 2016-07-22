<?php
    /*
     * SUPRISERVICE
     -------------------------------------------------------------------------
     Termo de Responsabilidade de Suprimentos
     Desenvolvido em Out/2013 por Fabio Rosalem Dazzi.

     -------------------------------------------------------------------------
     */

    define('GLPI_ROOT', '..');
    include (GLPI_ROOT . "/inc/includes.php");
    
    $uID            = $_REQUEST['id'];
    $imprimir       = $_REQUEST['imprimir'];
    $v_edit_01      = $_REQUEST['v_edit_01'];
    $v_edit_02      = $_REQUEST['v_edit_02'];
    $v_edit_03      = $_REQUEST['v_edit_03'];
    $v_edit_04      = $_REQUEST['v_edit_04'];
    $v_edit_05      = $_REQUEST['v_edit_05'];
    $v_itens        = $_REQUEST['v_itens'];
    $v_itens_qtd    = $_REQUEST['v_itens_qtd'];
    
    global $DB;
    
    // ESTOQUISTA QUE ESTÁ LIBERANDO OS SUPRIMENTOS
    $nomeEstoquista = utf8_decode($_SESSION["glpifirstname"]." ".$_SESSION["glpirealname"]);

    // ANALISTA QUE ESTÁ LEVANDO OS SUPRIMENTOS
    if ($uID != ''){
       $sql_nome_analista = "SELECT
                                firstname,
                                realname
                             FROM
                                glpi_users
                             WHERE
                                id = {$uID}";
       $res_nome_analista = mysql_query($sql_nome_analista);
       $vlr_nome_analista = mysql_fetch_assoc($res_nome_analista);
       $nomeAnalista = utf8_decode($vlr_nome_analista['firstname']." ".$vlr_nome_analista['realname']);
    } else
       $nomeAnalista = "";

    // DATA DA ENTREGA DO SUPRIMENTO
    $dataEntrega = date("d/m/Y");


    //echo "<hr>Itens:<br>";
    //print_r($v_itens);
    //if (count($v_itens)>0) {
    //    $v_itens = "AND ci.id IN (".implode(",", $v_itens).")";
    //} else {
        //if ($imprimir!='Imprimir')
            $v_itens = "AND true";
       // else
        //    $v_itens = "AND false";
    //}
    
    
    // Insere Log
    if ($imprimir=='Imprimir') {
        $log_type           = "User";
        $log_items_id       = $uID;
        $log_tyoelink       = "CartridgeItem";
        $log_link_action    = 12;
        $log_username       = $_SESSION["glpifirstname"]." ".$_SESSION["glpirealname"];
        $log_data           = $_SESSION["glpi_currenttime"];
        $log_search_option  = 0;
        $log_old_value      = "";
        $log_new_value      = "Impressão de Termo de Responsabilidade de Suprimentos";

        $queryLog = "INSERT INTO glpi_logs(
                itemtype,
                items_id,
                itemtype_link,
                linked_action,
                user_name,
                date_mod,
                id_search_option,
                old_value,
                new_value
            ) VALUES(
                '$log_type',
                $log_items_id,
                '$log_tyoelink',
                $log_link_action,
                '$log_username',
                '$log_data',
                $log_search_option,
                '$log_old_value',
                '$log_new_value'
            )";
        //echo $queryLog;
        $DB->query($queryLog);
    }
    //echo "<hr>Itens Qtd:<br>";
    //print_r($v_itens_qtd);
    //echo "<hr>";
    
    echo "<html>";
    echo "<head>";
    echo "<title>SUPRIDESK - TERMO DE RESPONSABILIDADE DE SUPRIMENTOS</title>";
    
    // JavaScript
    echo "<script>";
    echo "function verifica(Campo, Max) {
            if (!isNaN(Campo.value)) {
                //alert('Numero');
                if ((Campo.value==0) || (Campo.value > Max)) {
                    alert('Quantidade Minima: 1\\nQuantidade maxima: '+Max);
                    Campo.focus();
                    Campo.select();
                    return false;
                }
            } else {
                alert('Numero Invalido');
                Campo.focus();
                return false;
            }
            
            return true;
         }";
    echo "</script>";
    
    
    // Styles (Estilos)
    echo "<style>";
    echo ".Titulo {
        font-family: Bitstream Vera Sans, arial, Tahoma, Sans serif;
        font-size: 25px;
        text-decoration: underline;
        font-weight: bold;
    }";
    echo ".subTitulo {
        font-family: Bitstream Vera Sans, arial, Tahoma, Sans serif;
        font-size: 18px;
        text-decoration: none;
        font-weight: none;
    }";
    echo ".tabTituloU {
        font-family: Bitstream Vera Sans, arial, Tahoma, Sans serif;
        font-size: 15px;
        text-decoration: underline;
        font-weight: bold;
    }";
    echo ".tabTituloNU {
        font-family: Bitstream Vera Sans, arial, Tahoma, Sans serif;
        font-size: 15px;
        text-decoration: none;
        font-weight: bold;
    }";
    echo ".tabConteudo {
        font-family: Bitstream Vera Sans, arial, Tahoma, Sans serif;
        font-size: 15px;
        text-decoration: none;
        font-weight: bold;
    }";
    echo ".textoComplementar {
        font-family: Bitstream Vera Sans, arial, Tahoma, Sans serif;
        font-size: 15px;
        text-decoration: none;
        font-weight: bold;
    }";
    echo ".tituloObs {
        font-family: Bitstream Vera Sans, arial, Tahoma, Sans serif;
        font-size: 25px;
        text-decoration: none;
        font-weight: bold;
    }";
    echo "</style>";

    if ($imprimir=='Imprimir') {
       echo "<script>print();</script>";
    }
    echo "</head>";
    echo "<body>";
    
    // Titullo
    $divTitulo = "<div align='center' class='Titulo'>";
    $divTitulo .= "TERMO DE RESPONSABILIDADE DE SUPRIMENTOS";
    $divTitulo .= "</div>";
    
    // Sub-título
    $divSubTitulo = "<div align='center' class='subTitulo'>";
    $divSubTitulo .= "Motociclistas, favor conferir os Suprimentos antes de sair para atendimento";
    $divSubTitulo .= "</div>";
    
    // Tabela de conteúdo
    $tabela = "<table width='800px' align='center' border='1' cellspacing='0'>";

        // Cabeçalho da Tabela
        $tabela .= "<tr>";
        //$tabela .= "<th class='tabTituloU' bgcolor='#cccccc'>C&oacute;digo</th>";
        $tabela .= "<th class='tabTituloU' bgcolor='#cccccc'>Produto-Descri&ccedil;&atilde;o</th>";
        $tabela .= "<th class='tabTituloU' bgcolor='#cccccc'>Chamado</th>";
        $tabela .= "<th class='tabTituloU' bgcolor='#cccccc'>Reserva</th>";
        $tabela .= "<th class='tabTituloNU'>Suprimentos<br>Trocados</th>";
        $tabela .= "</tr>";
        
        // Conteúdo da Tabela
        $query = "  SELECT
                        COUNT(*) AS qtd, c.*, ci.*
                    FROM glpi_cartridges c
                        LEFT JOIN glpi_cartridgeitems ci ON ( c.cartridgeitems_id = ci.id )
                    WHERE date_out IS NULL
                        AND date_use IS NULL
                        AND alocado_para = {$uID}
                        AND aplicado_por IS NULL
                    GROUP BY cartridgeitems_id
                    ORDER BY name, ref";
        //echo $query;
        $result = $DB->query($query);
        $number = $DB->numrows($result);
        $i = 0;
        while ($i < $number) {
            $ID         = $DB->result($result, $i, "cartridgeitems_id");
            $name       = $DB->result($result, $i, "name");
            $ref        = $DB->result($result, $i, "ref");
            $fullname   = sprintf("%s - %s", $name, $ref);
            $qtd        = $DB->result($result, $i, "qtd");

            // Quantidade de alocados chamado
            $query_alocados_c = "	SELECT
                                       COUNT(*) AS qtd, c.*, ci.*
                                    FROM glpi_cartridges c
                                       LEFT JOIN glpi_cartridgeitems ci ON ( c.cartridgeitems_id = ci.id )
                                    WHERE date_out IS NULL
                                    AND date_use IS NULL
                                    AND aplicado_por IS NULL
                                    AND alocado_para = {$uID}
                                    AND cartridgeitems_id = {$ID}
                                    AND alocado_tipo = 'c'
                                    GROUP BY cartridgeitems_id
                                    ORDER BY name, ref";
                                    //echo $ID." - ".$query_alocados."<hr>";
            $result_alocados_c = $DB->query($query_alocados_c);
            $valores_alocados_c = $DB->fetch_assoc($result_alocados_c);
            $qtd_alocados_c = $valores_alocados_c['qtd'];


            // Quantidade de alocados reserva
            $query_alocados_r = "	SELECT
                                       COUNT(*) AS qtd, c.*, ci.*
                                    FROM glpi_cartridges c
                                       LEFT JOIN glpi_cartridgeitems ci ON ( c.cartridgeitems_id = ci.id )
                                    WHERE date_out IS NULL
                                    AND date_use IS NULL
                                    AND aplicado_por IS NULL
                                    AND alocado_para = {$uID}
                                    AND cartridgeitems_id = {$ID}
                                    AND alocado_tipo = 'r'
                                    GROUP BY cartridgeitems_id
                                    ORDER BY name, ref";
                                    //echo $ID." - ".$query_alocados."<hr>";
            $result_alocados_r = $DB->query($query_alocados_r);
            $valores_alocados_r = $DB->fetch_assoc($result_alocados_r);
            $qtd_alocados_r = $valores_alocados_r['qtd'];
            if (trim($qtd_alocados_r) == '')
               $qtd_alocados_r = "&nbsp";


            $tabela .= "<tr>";
            //$tabela .= "<td class='tabConteudo' align='center'>$ID</td>";
            $tabela .= "<td class='tabConteudo'>".$fullname."</td>";
            $tabela .= "<td class='tabConteudo' align='center'>$qtd_alocados_c</td>";
            $tabela .= "<td class='tabConteudo' align='center'>$qtd_alocados_r</td>";
            $tabela .= "<td class='tabConteudo'>&nbsp;</td>";
            $tabela .= "</tr>";
            
            $i++;
        }
    $tabela .= "<tr>";
    $tabela .= "<td colspan='4' class='tabConteudo'>
        OBS: Em caso de perda, extravio ou quebra o respons&aacute;vel pelos suprimentos estar&aacute; arcando com os custos dos mesmos.
        </td>";
    $tabela .= "</tr>";
    $tabela .= "</table>";
    
    
    // Texto complementar e assinaturas
    $textoComp  = "<table width='800px' align='center' border='0' cellspacing='0' cellpadding='10' class='textoComplementar'>";
        
        $textoComp .= "<tr><td>";
        $textoComp .= "Estoquista que est&aacute; liberando os Suprimentos:";
        if ($imprimir!='Imprimir') {
            $textoComp .= "<input type='text' name='v_edit_01' size='50' value='$nomeEstoquista'>";
        } else {
            if ($v_edit_01!='')
                $textoComp .= "<span style='font-weight: normal'>&nbsp;$v_edit_01</span>";
            else
                $textoComp .= "________________________________________________________";
        }
        $textoComp .= "</td></tr>";
        
        $textoComp .= "<tr><td>";
        $textoComp .= "Analista Respons&aacute;vel:";
        if ($imprimir!='Imprimir') {
            $textoComp .= "<input type='text' name='v_edit_02' size='50' value='$nomeAnalista'>";
        } else {
           if ($v_edit_02!='') {
            $contagem = 78;
            $subtrair = strlen($v_edit_02);
            $contagem = $contagem - $subtrair;
            //$textoComp = $nomeAnalista;
           
            /*for ($aux=1;$aux<=$contagem;$aux++) {
               $v_edit_02 .= "_";
            }*/
            //die("TextoComp: $textoComp | Contagem: $contagem | Subtrair: $subtrair");
            //if ($v_edit_02!='')
                 $textoComp .= "<span style='font-weight: normal'>&nbsp;$v_edit_02</span>";
           } else {
                $textoComp .= "________________________________________________________";
                $textoComp .= "__________________";
           }
        }
        $textoComp .= "</td></tr>";
        
        $textoComp .= "<tr><td>";
        $textoComp .= "Data de Entrega de Suprimentos:";
        if ($imprimir!='Imprimir') {
            $textoComp .= "<input type='text' name='v_edit_03' size='10' value='$dataEntrega' maxlength='10'>";
        } else {
            if ($v_edit_03!='')
                $textoComp .= "<span style='font-weight: normal'>&nbsp;$v_edit_03</span>";
            else
                $textoComp .= "_____________________________________";
        }
        $textoComp .= "</td></tr>";
        
        $textoComp .= "<tr><td>";
        $textoComp .= "Data de Devolu&ccedil;&atilde;o ao Estoque:";
        if ($imprimir!='Imprimir') {
            $textoComp .= "<input type='text' name='v_edit_04' size='15' maxlength='10'>";
        } else {
            if ($v_edit_04!='')
                $textoComp .= "<span style='font-weight: normal'>&nbsp;$v_edit_04</span>";
            else
                $textoComp .= "_______________________________________";
        }
        $textoComp .= " (Obrigat&oacute;rio Preencher - Estoque)";
        $textoComp .= "</td></tr>";
        
        $textoComp .= "<tr><td>";
        $textoComp .= "Estoquista que est&aacute; Recebendo os Suprimentos:";
       if ($imprimir!='Imprimir') {
            $textoComp .= "<input type='text' name='v_edit_05' size='50'>";
        } else {
            if ($v_edit_05!='') {
               $contagem = 54;
               $subtrair = strlen($v_edit_05);
               $contagem = $contagem - $subtrair;
               //$textoComp = $nomeAnalista;

               for ($aux=1;$aux<=$contagem;$aux++) {
                  $v_edit_05 .= "_";
               }
               $textoComp .= "<span style='font-weight: normal'>&nbsp;$v_edit_05</span>";
            } else
                $textoComp .= "______________________________________________________";
        }
        $textoComp .= "</td></tr>";
        
    $textoComp .= "</table>";
    
    
    // Tabela de Observações
    $tabelaObs = "<table width='800px' align='center' border='1' cellspacing='0'>";
        // Cabeçalho da Tabela
        $tabelaObs .= "<tr>";
        $tabelaObs .= "<th colspan='3' class='tituloObs'>Observa&ccedil;&otilde;es</th>";
        $tabelaObs .= "</tr>";
        
        // Conteúdo da Tabela
        for($aux=1;$aux<=5;$aux++) {
            $tabelaObs .= "<tr>";
            $tabelaObs .= "<td width='100px' class='tabConteudo'>&nbsp;</td>";
            $tabelaObs .= "<td width='350px' class='tabConteudo'>&nbsp;</td>";
            $tabelaObs .= "<td width='350px' class='tabConteudo'>&nbsp;</td>";
            $tabelaObs .= "</tr>";
        }
    $tabelaObs .= "</table>";
    
    // Formulário
    $abreForm   = "<form name='frmTermo' method='POST' action='#'>";
    $botao      = "<div align='center'><input type='submit' value='Imprimir' name='imprimir'></div>";
    $fechaForm  = "</form>";
    
    echo $divTitulo;
    echo $divSubTitulo;
    
    echo $abreForm;
    echo $tabela;
    echo "<br><br>";
    echo $textoComp;
    echo "<br>";
    echo $tabelaObs;
    if ($imprimir!='Imprimir')
        echo $botao;
    echo $fechaForm;
    
    echo "</body>";
    echo "</html>";
?>