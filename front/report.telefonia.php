<?php
/*
 * @version $Id: report.networking.php 18771 2012-06-29 08:49:19Z moyo $
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
/*!
  \brief affiche les diffents choix de rapports reseaux
 */

define('GLPI_ROOT', '..');
include (GLPI_ROOT . "/inc/includes.php");

Session::checkRight("reports", "r");

Html::header($LANG['Menu'][6],$_SERVER['PHP_SELF'],"utils","report");

if (empty($_POST["dt_inicio"])) {
   $_POST["dt_inicio"] = date("Y-m-d H:i:s");
} else {
    if ($_POST["dt_inicio"] == 'NULL')
        $_POST["dt_inicio"] = null;
}

if (empty($_POST["dt_fim"])) {
   $_POST["dt_fim"] = date("Y-m-d H:i:s");
} else {
    if ($_POST["dt_fim"] == 'NULL')
        $_POST["dt_fim"] = null;
}

//$_POST["dt_inicio"] = date("Y-m-d h:i:s");//date("Y-m-d",mktime(1,0,0,date("m"),date("d"),date("Y")));
/*if (empty($_POST["bilhetagem1"]) && empty($_POST["bilhetagem2"])) {
   $month = date("m")-1;
   $_POST["bilhetagem1"] = date("Y-m-d",mktime(1,0,0,$month,date("d"),date("Y")));
   $_POST["bilhetagem2"] = date("Y-m-d");
}
*/

Report::title();

echo "<table class='tab_cadre' >";
echo "<tr><th colspan='4'>&nbsp;".$LANG['telefonia'][1]."&nbsp;</th></tr>";
echo "</table><br>";

echo "<form name='form' method='post' action='report.telefonia.php'>";
echo "<table class='tab_cadre' width='500'>";
echo "<tr class='tab_bg_1'>";
echo "<td width='120'>".$LANG['telefonia'][2]." : </td>";
echo "<td>";
echo "<input type='text' 
            id='origem' 
            name='origem' 
            size='12' 
            title='".$LANG['telefonia'][3]."'
            value='".$_POST["origem"]."' />";
echo "</td>";
echo "<td width='120'>".$LANG['telefonia'][5]." : </td>";
echo "<td>";
echo "	<input type='text' 
            id='destino' 
            name='destino' 
            size='12' 
            title='".$LANG['telefonia'][3]."'
            value='".$_POST["destino"]."' />";
echo "</td>";
echo "</tr>";

echo "<tr class='tab_bg_1'>";
echo "<td width='120'>".$LANG['search'][8]." : </td>";
echo "<td class='center'>";
Html::showDateTimeFormItem("dt_inicio", $_POST["dt_inicio"]);
echo "</td>";

echo "<td width='120'>".$LANG['search'][9]." : </td>";
echo "<td class='center'>";
Html::showDateTimeFormItem("dt_fim", $_POST["dt_fim"]);
echo "</td>";
echo "</tr>";

/*echo "<tr class='tab_bg_1'>";
echo "<td class='center' colspan=4>";
echo "Período de Bilhetagem";
echo "</td>";
echo "</tr>";

echo "<tr class='tab_bg_1'>";
echo "<td width='120'>".$LANG['search'][8]." : </td>";
echo "<td class='center'>";
Html::showDateFormItem("bilhetagem1", $_POST["bilhetagem1"]);
echo "</td>";

echo "<td width='120'>".$LANG['search'][9]." : </td>";
echo "<td class='center'>";
Html::showDateFormItem("bilhetagem2", $_POST["bilhetagem2"]);
echo "</td>";
echo "</tr>";*/

echo "<tr class='tab_bg_1'>";
echo "<td colspan='4' class='center' width='120'>";
echo "<input type='submit' name='exibir' value=\"".$LANG['telefonia'][4]."\" class='submit'>";
echo "</td>";
echo "</tr>";
echo "</table>";


if (isset($_POST["exibir"])) {
    // Conexão com ASTERISK
    $conn = mysql_connect("10.10.10.76","fdazzi","frdazzi1980") or 
            die("Não foi possível conectar ao banco do Asterisk");
    mysql_select_db("asteriskcdrdb");
    
    // Recupera valores do form
    //$origem  = $_POST['origem'];
    //$destino = $_POST['destino'];
    //$dt_inicio = $_POST['dt_inicio'];
    //$dt_fim    = $_POST['dt_fim'];
    
    // Faz consulta ao banco do ASTERISK
    $query = "select * from cdr where ";
    if (isset($_POST["origem"]))
        $query .= "src like '%{$_POST['origem']}%' ";
    else
        $query .= "true ";
    
    if (isset($_POST["destino"]))
        $query .= "and dst like '%{$_POST['destino']}%' ";
    else
        $query .= "and true ";
    
    if ((isset($_POST["dt_inicio"])) && ($_POST["dt_inicio"]!='NULL'))
        $query .= "and calldate >= '{$_POST['dt_inicio']}' ";
    else
        $query .= "and true ";
    
    if ((isset($_POST["dt_fim"])) && ($_POST["dt_fim"]!='NULL'))
        $query .= "and calldate <= '{$_POST['dt_fim']}' ";
    else
        $query .= "and true ";
    
    $query .= "order by calldate";
    //echo $query;
    
    //$query = "select count(*) as Valor from cdr";
    $res = mysql_query($query);
    
    $tamFonte = "12px";
    
    // listagem
    if ((!$res)||(mysql_num_rows($res)==0)){
        echo "<br><br>";
        echo "<table class='tab_cadre_pager'>";
        echo "<tr>";
        echo "<th>Não foram encontrados registros para a pesquisa.</th>";
        echo "</tr>";
        echo "</table>";
    } else {
        echo "<br>";
        echo "<table align='center'>";
        echo "<tr>";
        echo "<td style='font-size:$tamFonte'>Número de Ligações: ".mysql_num_rows($res)."</td>";
        echo "</tr>";
        echo "</table>";
    
        echo "<table class='tab_cadre_pager'>";
        echo "<tr>";
        echo "<th style='font-size:$tamFonte'>Data</th>";
        echo "<th style='font-size:$tamFonte'>Origem</th>";
        echo "<th style='font-size:$tamFonte'>Destino</th>";
        echo "<th style='font-size:$tamFonte'>Disposição</th>";
        echo "<th style='font-size:$tamFonte'>Duração</th>";
        echo "</tr>";
        
        $classLinha = "tab_bg_1";
        while ($val = mysql_fetch_assoc($res)) {
            $segundos = $val['duration'];
            $converter = date('i:s',mktime(0,0,$segundos,0,0,0));
            $duracao = $converter;
            
            switch ($val['disposition']) {
                case 'ANSWERED':
                    $disposicao = "Atendida";
                    break;
                case 'NO ANSWER':
                    $disposicao = "Não Atendida";
                    break;
                case 'BUSY':
                    $disposicao = "Ocupado";
                    break;
                case 'FAILED':
                    $disposicao = "Falha";
                    break;
                default:
                    $disposicao = "--";
                    break;
            }
            echo "<tr class='$classLinha' style='font-size:$tamFonte'>";
            echo "<td>{$val['calldate']}</td>";
            echo "<td>{$val['src']}</td>";
            echo "<td>{$val['dst']}</td>";
            echo "<td>$disposicao</td>";
            echo "<td>{$duracao}</td>";
            echo "</tr>";
            
            if ($classLinha=="tab_bg_1")
                $classLinha="tab_bg_2";
            else
                $classLinha="tab_bg_1";
        }
        echo "</table>";
    }
    //mysql_close($conn);
}

Html::closeForm();

Html::footer();
?>