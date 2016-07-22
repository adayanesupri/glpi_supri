<?php

/*
 * @version $Id: HEADER 15930 2011-10-30 15:47:55Z tsmr $
  -------------------------------------------------------------------------
  Mreporting plugin for GLPI
  Copyright (C) 2003-2011 by the mreporting Development Team.

  https://forge.indepnet.net/projects/mreporting
  -------------------------------------------------------------------------

  LICENSE

  This file is part of mreporting.

  mreporting is free software; you can redistribute it and/or modify
  it under the terms of the GNU General Public License as published by
  the Free Software Foundation; either version 2 of the License, or
  (at your option) any later version.

  mreporting is distributed in the hope that it will be useful,
  but WITHOUT ANY WARRANTY; without even the implied warranty of
  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
  GNU General Public License for more details.

  You should have received a copy of the GNU General Public License
  along with mreporting. If not, see <http://www.gnu.org/licenses/>.
  --------------------------------------------------------------------------
 */

class PluginMreportingMisc {

    static function showNavigation() {
        global $LANG;

        echo "<div class='center'>";
        echo "<a href='central.php'>" . $LANG['buttons'][13] . "</a>";
        echo "</div>";
    }

    static function getRequestString($var) {
        unset($var['submit']);

        $request_string = "";
        foreach ($var as $key => $value) {
            $request_string.= "$key=$value&";
        }

        return substr($request_string, 0, -1);
    }

    static function showSelector($date1, $date2, $params = null) {
        global $LANG, $DB;

        $request_string = self::getRequestString($_GET);

        echo "<div class='center'><form method='POST' action='?$request_string' name='form'>\n";
        echo "<table class='tab_cadre'><tr class='tab_bg_1'>";

        /* //SUPRISERVICE */
        //customização para filtro por modelo de impressora
        if ($params['showPrinterModels']) {
            echo "<td>";
            echo $LANG['supriservice'][1] . ":";
            Dropdown::show('PrinterModel', array('value' => $params['printermodels_id']));
            echo "<input type='hidden' name='tipoEquipamento' value='".$_SESSION['TempTipoEquipamento']."'>\n";
            echo "<input type='hidden' name='tipoEquipamentoVenda' value='".$_SESSION['TempTipoEquipamentoVenda']."'>\n";
            echo "</td>";
        }
        
        if ($params['showComputerModels']) {
            echo "<td>";
            echo "Modelo do Computador:";
            Dropdown::show('ComputerModel', array('value' => $params['computermodels_id']));
            echo "<input type='hidden' name='tipoEquipamento' value='".$_SESSION['TempTipoEquipamento']."'>\n";
            echo "<input type='hidden' name='tipoEquipamentoVenda' value='".$_SESSION['TempTipoEquipamentoVenda']."'>\n";
            echo "</td>";
        }
        
         if ($params['showMonitorModels']) {
            echo "<td>";
            echo "Modelo do Monitor:";
            Dropdown::show('MonitorModel', array('value' => $params['monitormodels_id']));
            echo "<input type='hidden' name='tipoEquipamento' value='".$_SESSION['TempTipoEquipamento']."'>\n";
            echo "<input type='hidden' name='tipoEquipamentoVenda' value='".$_SESSION['TempTipoEquipamentoVenda']."'>\n";
            echo "</td>";
        }
        
        if ($params['showManufacturer']) {
            echo "<td>";
            echo "Fabricante:";
            Dropdown::show('Manufacturer', array('value' => $params['manufacturer_id']));
            echo "<input type='hidden' name='tipoEquipamentoVenda' value='".$_SESSION['TempTipoEquipamentoVenda']."'>\n";
            echo "</td>";
        }
        
        if ($params['showPrinterType']) {
            echo "<td>";
            echo "Tipo:";
            Dropdown::show('PrinterType', array('value' => $params['printertype_id']));
            echo "</td>";
        }
        
        if ($params['showMonitorType']) {
            echo "<td>";
            echo "Tipo:";
            Dropdown::show('MonitorType', array('value' => $params['monitortype_id']));
            echo "</td>";
        }
        
        if ($params['showComputerType']) {
            echo "<td>";
            echo "Tipo:";
            Dropdown::show('ComputerType', array('value' => $params['computertype_id']));
            echo "</td>";
        }

        //customização para filtro por analista
        if ($params['showAnalistas']) {
            
            echo "<td>";
            echo "Categoria de Usuário: ";
            if ($params['showAnalistas'] === true)
                $params['showAnalistas'] = 0;
            Dropdown::show('UserCategory', array('value' => intval($params['showAnalistas'])));
            echo "</td>";
        }
        
        if ($params['showTypeEquipamento']) {            
                       
            echo "<td>";
            echo "Escolha o tipo: ";
            echo "<select name='typeEquip'>";
            echo "<option value='-1'> -----</option>";
            echo "<option value='Computer'>Computador</option>"; 
            echo "<option value='Monitor'>Monitor</option>";
            echo "<option value='Printer'>Impressora</option>"; 
            echo "</select>";             
            echo "</td>";  
            
            unset($_SESSION['TempTipoEquipamento']);
            
        }
        
        if ($params['showTypeEquipamentoVenda']) {            
                       
            echo "<td>";
            echo "Escolha o tipo: ";
            echo "<select name='typeEquipVenda'>";
            echo "<option value='-1'> -----</option>";
            echo "<option value='Computer'>Computador</option>"; 
            echo "<option value='Monitor'>Monitor</option>";
            echo "<option value='Printer'>Impressora</option>"; 
            echo "<option value='Software'>Software</option>"; 
            echo "</select>";             
            echo "</td>";  
            
            unset($_SESSION['TempTipoEquipamentoVenda']);
            
        }
        
        if ($params['showStates']) {
            
            echo "<td>";
            echo "Status: ";
            if ($params['showStates'] === true)
                $params['showStates'] = 0;
            Dropdown::show('State', array('value' => intval($params['showStates'])));
            echo "<input type='hidden' name='tipoEquipamentoVenda' value='".$_SESSION['TempTipoEquipamentoVenda']."'>\n";
            echo "</td>";
        }
        
        if ($params['showTypeEquipamentoBackup']) {            
                       
            echo "<td>";
            echo "Escolha o tipo: ";
            echo "<select name='typeEquipBackup'>";
            echo "<option value='-1'> -----</option>";
            echo "<option value='Computer-11'>ALL IN ONE</option>"; 
            echo "<option value='Computer-2'>DESKTOP</option>";
            echo "<option value='Printer'>IMPRESSORA</option>";
            echo "<option value='Computer-3'>NOTEBOOK</option>";
            echo "<option value='Monitor'>MONITOR</option>"; 
            echo "</select>";             
            echo "</td>";  
            
            unset($_SESSION['TempTipoEquipamento']);
            
        }
        
        if($params['showGroup']){            
            
            global $DB;
            $rand = mt_rand();
            $myname = 'usercategories_id';
            
            if(isset($_SESSION['TempCategoria'])){
                $where = " `usercategories_id` = ".$_SESSION['TempCategoria']." ";
            }else{
                $where = " `usercategories_id` IN (1,2,3,4,17,13)";
            }
            
            $params = array('itemtype' => '__VALUE__',
                    'entity_restrict' => $entity_restrict,
                    'admin' => $admin,
                    'options' => $options);

            if (array_key_exists("myname", $options))
                $params['myname'] = $myname;
            else
                $params['myname'] = "users_id";
            
            $query = "SELECT `id`, CONCAT(`firstname`, ' ' , `realname`) AS name 
            FROM `glpi_users` 
            WHERE ".$where." 
                AND `is_active` = 1
                AND `is_deleted` = 0
            ORDER BY `name` ASC";
            $res = $DB->query($query);
            
            echo "<td>";
            echo "Usuário: ";
            echo "<select id='dropdown_$myname$rand' name='$myname'>\n";
            echo "<option value='-1' >-----</option>\n";
            
            while($data = $DB->fetch_assoc($res)){
                echo "<option value='" . $data['id'] . "'>" . $data['name'];
                echo "</option>\n";
            }
            
            echo "</select>";  
            echo "<input type='hidden' name='categoriaTemp' value='".$_SESSION['TempCategoria']."'>\n";
            echo "</td>";
            
            
            unset($_SESSION['TempCategoria']);
            
        }

        if ($params['showContracts']) {
             
            $where_entities = "'" . implode("', '", $_SESSION['glpiactiveentities']) . "'";
            
            global $DB;
            $query = " SELECT * FROM `glpi_contracts` 
                    WHERE 1 AND `is_deleted` = '0' 
                            AND `is_template` = '0' 
							AND `is_active` = 1
                            AND `glpi_contracts`.`id` NOT IN ('0') 
                            AND ( `glpi_contracts`.`entities_id` IN (".$where_entities.") OR (`glpi_contracts`.`is_recursive`='1' AND `glpi_contracts`.`entities_id` IN ('0')) ) 
                    ORDER BY `entities_id`, name";
            
            $res = $DB->query($query);    
           
            echo "<td width='150'>";
            echo "<p class='b'>Contratos: </p> ";
            echo "<p><select name='contracts[]' size='8' multiple>";
            //echo "<option value='0' selected>".$LANG['common'][66]."</option>";
            while($data = $DB->fetch_assoc($res)){
                echo "<option value='{$data['id']}'>".$data['name']."</option>";            
            }
            echo "</select> </p></td>";           
            
        }        
        
        if ($params['showLocations']) {
            
            echo "<td>";
            echo "Localidade: ";
            Dropdown::show('Location');
            echo "</td>";
        }
        
        
        if ($params['showStatus']) {            
                       
            echo "<td>";
            echo "Status: ";
            echo "<select name='status'>";
            echo "<option value='all'> -----</option>";
            echo "<option value='new'>Novo</option>"; 
            echo "<option value='assign'>Atribuído</option>";
            echo "<option value='atendimento'>Em Atendimento</option>";
            echo "<option value='waiting'>Pendente Cliente</option>";
            echo "<option value='waiting1'>Pendente Fornecedor</option>";
            echo "<option value='waiting4'>Pendente Supriservice</option>";
            echo "<option value='solved'>Solucionado</option>";
            echo "<option value='closed'>Fechado</option>";
            echo "<option value='approbation'>Aguardando Aprovação (Cliente)</option>";
            echo "<option value='approbation1'>Aguardando Aprovação (Supriservice)</option>";
            echo "<option value='canceled'>Cancelado</option>";
            echo "<option value='plan'>Processando (planejado)</option>";
            echo "<option value='notold'>Não solucionado</option>";
            echo "<option value='notclosed'>Não fechado</option>";
            echo "<option value='process'>Processando</option>";
            echo "<option value='old'>Solucionado + Fechado</option>";
            echo "<option value='oldest'>Solucionado + Fechado + Cancelado</option>";
            echo "<option value='all'>Todos</option>";            
            echo "</select></td>";           
            
        }
       
        if ($params['showMonitorType'] === true || $params['showComputerType'] === true || $params['showPrinterType'] === true || $params['showTypeEquipamento'] === true || $params['showTypeEquipamentoVenda'] === true || $params['showManufacturer'] === true) { 
            echo "";
        }else{
            echo "<td>";
            Html::showDateFormItem("date1", $date1, false);
            echo "</td>\n";

            echo "<td>";
            Html::showDateFormItem("date2", $date2, false);
            echo "</td>\n";
        } 
        

        echo "<td rowspan='2' class='center'>";
        echo "<input type='submit' class='button' name='submit' Value=\"" . $LANG['buttons'][7] . "\">";
        echo "</td>\n";

        echo "</tr>";
        echo "</table></form></div>\n";
    }

    static function getSQLDate($field = "glpi_tickets.date", $delay = 365) {

        if (!isset($_REQUEST['date1']))
            $_REQUEST['date1'] = strftime("%Y-%m-%d", time() - ($delay * 24 * 60 * 60));
        if (!isset($_REQUEST['date2']))
            $_REQUEST['date2'] = strftime("%Y-%m-%d");

        $date_array1 = explode("-", $_REQUEST['date1']);
        $time1 = mktime(0, 0, 0, $date_array1[1], $date_array1[2], $date_array1[0]);

        $date_array2 = explode("-", $_REQUEST['date2']);
        $time2 = mktime(0, 0, 0, $date_array2[1], $date_array2[2], $date_array2[0]);

        //if data inverted, reverse it
        if ($time1 > $time2) {
            list($time1, $time2) = array($time2, $time1);
            list($_REQUEST['date1'], $_REQUEST['date2']) = array($_REQUEST['date2'], $_REQUEST['date1']);
        }

        $begin = date("Y-m-d H:i:s", $time1);
        $end = date("Y-m-d H:i:s", $time2);

        return "($field >= '$begin' AND $field <= ADDDATE('$end' , INTERVAL 1 DAY) )";
    }

    /* //SUPRISERVICE */

    static function getSQLDateSupri($field = "glpi_tickets.date", $delay = 365, $overrideDates = false) {

        if (!isset($_REQUEST['date1']) || $overrideDates)
            $_REQUEST['date1'] = strftime("%Y-%m-%d", time() - ($delay * 24 * 60 * 60));
        if (!isset($_REQUEST['date2']) || $overrideDates)
            $_REQUEST['date2'] = strftime("%Y-%m-%d");

        $date_array1 = explode("-", $_REQUEST['date1']);
        $time1 = mktime(0, 0, 0, $date_array1[1], $date_array1[2], $date_array1[0]);

        $date_array2 = explode("-", $_REQUEST['date2']);
        $time2 = mktime(23, 59, 59, $date_array2[1], $date_array2[2], $date_array2[0]);

        //if data inverted, reverse it
        if ($time1 > $time2) {
            list($time1, $time2) = array($time2, $time1);
            list($_REQUEST['date1'], $_REQUEST['date2']) = array($_REQUEST['date2'], $_REQUEST['date1']);
        }

        $begin = date("Y-m-d H:i:s", $time1);
        $end = date("Y-m-d H:i:s", $time2);

        return "($field >= '$begin' AND $field <= '$end' )";
    }

    static function exportSvgToPng($svgin) {
        $im = new Imagick();

        $im->readImageBlob($svgin);

        $im->setImageFormat("png24");
        $im->resizeImage(720, 445, imagick::FILTER_LANCZOS, 1);

        echo '<img src="data:image/jpg;base64,' . base64_encode($im) . '"  />';

        $im->clear();
        $im->destroy();
    }

    static function DOM_getElementByClassName($referenceNode, $className, $index = false) {
        $className = strtolower($className);
        $response = array();

        foreach ($referenceNode->getElementsByTagName("*") as $node) {
            $nodeClass = strtolower($node->getAttribute("class"));

            if (
                    $nodeClass == $className ||
                    preg_match("/\b" . $className . "\b/", $nodeClass)
            ) {
                $response[] = $node;
            }
        }

        if ($index !== false) {
            return isset($response[$index]) ? $response[$index] : false;
        }

        return $response;
    }

}

?>