<?php
/*
 * @version $Id: computer.class.php 19244 2012-09-11 18:17:25Z remi $
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

if (!defined('GLPI_ROOT')) {
   die("Sorry. You can't access directly to this file");
}

/**
 *  Stock class
**/
class Supridesk_Faturamento extends CommonDBTM {
    
    /*//SUPRISERVICE*/
   public $supridesk_custom_table = "supridesk_faturamento";
   
   // From CommonDBTM
   public $dohistory = true;

   /**
    * Name of the type
    *
    * @param $nb : number of item in the type
    *
    * @return $LANG
   **/
   static function getTypeName($nb=0) {
      global $LANG;

      if ($nb>1) {
         return "Faturamentos";
      }
      return "Faturamento";
   }


   function canCreate() {
      return Session::haveRight('contract', 'w');
   }


   function canCreateItem() {
      return Session::haveRight('contract', 'w');
   }


   function canView() {
      return Session::haveRight('contract', 'r');
   }


   function canViewItem() {
      return Session::haveRight('contract', 'r');
   }


   function canUpdate() {
      return Session::haveRight('contract', 'w');
   }


   function canUpdateItem() {
      return Session::haveRight('contract', 'w');
   }

   function prepareInputForUpdate($input) {
      return $input;
   }


   function pre_deleteItem() {
      return true;
   }


   function defineTabs($options=array()) {
      global $LANG, $CFG_GLPI;

      $ong = array();
      $this->addStandardTab('Log', $ong, $options);
      
      return $ong;
   }
    
    /**
    * Show ports for an item
    *
    * @param $item CommonDBTM object
    * @param $withtemplate integer : withtemplate param
   **/
   static function showForItem(CommonDBTM $item, $withtemplate='') {
      global $DB, $CFG_GLPI, $LANG;

      $rand = mt_rand();
      // var_export($item);
      $itemtype = $item->getType();
      $contract_id = $item->getField('id');

      if (!Session::haveRight('contract','r') || !$item->can($contract_id, 'r')) {
         return false;
      }

      $canedit = $item->can($contract_id, 'w');
       //var_export(array($canedit));
      $showmassiveactions = false;
      if ($withtemplate!=2) {
         $showmassiveactions = count(Dropdown::getMassiveActions(__CLASS__));
      }


      Session::initNavigateListItems('Supridesk_Faturamento', $item->getTypeName()." = ".$item->getName());

      $query = "SELECT *
                FROM `supridesk_faturamento`
                WHERE `contract_id` = $contract_id
                ORDER BY `date` DESC";
       
       //var_export($query);

      if ($result = $DB->query($query)) {
         echo "<div class='spaced'>";

         if ($DB->numrows($result) != 0) {
            $colspan = 6;

            if ($showmassiveactions) {
               $colspan++;
               echo "\n<form id='suprideskfaturamento$rand' name='suprideskfaturamento$rand' method='post'
                     action='" . $CFG_GLPI["root_doc"] . "/front/supridesk_faturamento.form.php'>\n";
            }

            echo "<table class='tab_cadre_fixe'>\n";

            echo "<tr><th colspan='$colspan'>\n";
            echo "Registros encontrados: ".$DB->numrows($result)."</th></tr>\n";

            echo "<tr>";
            if ($showmassiveactions) {
               echo "<th>&nbsp;</th>\n";
            }
            echo "<th>Data</th>\n";
            echo "<th>Faturamento (Tarifação)</th>\n";
            echo "<th>Chamado</th>\n";
            echo "<th>Impostos (%)</th>\n";
            echo "<th>Outros (%)</th>\n";
            echo "<th>Tipo</th>\n";
             
            
            $i = 0;
            $faturamento = new Supridesk_Faturamento();

            while ($devid = $DB->fetch_row($result)) {
               $faturamento->getFromDB(current($devid));                

               Session::addToNavigateListItems('Supridesk_Faturamento', $faturamento->fields["id"]);
               
               echo "<tr class='tab_bg_1'>\n";
               if ($showmassiveactions) {
                  echo "<td class='center' width='20'>";
                  echo "<input type='checkbox' name='item[".$faturamento->fields["id"]."]' value='".$faturamento->fields["id"]."'>";
                  echo "</td>\n";
               }
               echo "<td class='center'><span class='b'>";
               if ($canedit && $withtemplate != 2) {
                  echo "<a href=\"" . $CFG_GLPI["root_doc"] . "/front/supridesk_faturamento.form.php?id=" .
                         $faturamento->fields["id"] . "&contract_id=" .
                         $contract_id . "\">";
               }
               echo $faturamento->fields["date"];
               if ($canedit && $withtemplate != 2) {
                  echo "</a>";
               }
               //Html::showToolTip($printerLogs->fields['comment']);
               echo "</td>\n";
                
                //$tipo = $faturamento->fields["tipo_valor"];
                
                switch ($faturamento->fields["tipo_valor"]) {
                    case "ticket":
                        $tipo = "Chamado"; 
                        $ticket = $faturamento->fields["valor_chamado"];
                        $imposto = '-';
                        $tarifacao = '-'; 
                        $outros = '-';
                        break;
                    case "imposto":
                        $tipo = "Imposto (%)";
                        $imposto = $faturamento->fields["valor_impostos"]." (%)";
                        $ticket = '-';
                        $tarifacao = '-'; 
                        $outros = '-';
                        break;
                    case "tarifacao":
                        $tipo = "Faturamento (Tarifação)";
                        $tarifacao = $faturamento->fields["faturamento_planilha"];
                        $imposto = '-';
                        $ticket = '-'; 
                        $outros = '-';
                        break;
                    case "outros":
                        $tipo = "Outros (%)";
                        $outros = $faturamento->fields["valor_outros"]." (%)";
                        $tarifacao = '-';
                        $imposto = '-';
                        $ticket = '-'; 
                        break;
                }

                

               echo "<td class='center'>".$tarifacao."</td>\n"; 
               echo "<td class='center'>".$ticket."</td>\n";  
               echo "<td class='center'>".$imposto."</td>\n";
               echo "<td class='center'>".$outros."</td>\n";
               echo "<td class='center'>".$tipo."</td>\n";    
					
               if ($canedit && $withtemplate != 2) {
                  echo "</a>";
               }
               echo "</tr>\n";
            }
            echo "</table>\n";

            if ($showmassiveactions) {
               Html::openArrowMassives("suprideskfaturamento$rand", true);
               Dropdown::showForMassiveAction('Supridesk_Faturamento');
               $actions = array();
               Html::closeArrowMassives($actions);
               Html::closeForm();
            }

         } else {
            echo "<table class='tab_cadre_fixe'><tr><th>Nenhum faturamento encontrado</th></tr>";
            echo "</table>";
         }
           
         echo "\n<div class='firstbloc'><table class='tab_cadre_fixe'>";
         echo "<tr><td class='tab_bg_2 center b'>";
         echo "<a href='" . $CFG_GLPI["root_doc"] . "/front/supridesk_faturamento.form.php?contract_id=" .
                         $contract_id . "'>";
         echo "<img src=\"" . $CFG_GLPI["root_doc"] . "/pics/add_dropdown.png\" alt=\"" . $LANG['buttons'][8] . "\" title=\"" . $LANG['buttons'][8] . "\"> ";
			echo "Cadastrar Faturamento";
			echo "</a></td>\n";
         echo "</tr></table></div>\n";
         echo "</div>";
      }
   }



   /**
   * Print the computer form
   *
   * @param $ID integer ID of the item
   * @param $options array
   *     - target for the Form
   *     - withtemplate template or basic computer
   *
   *@return Nothing (display)
   *
   **/
   function showForm($ID, $options=array()) {
      global $LANG, $CFG_GLPI, $DB;       
        

      if ($ID > 0) {
         $this->check($ID,'r');
      } else {
         // Create item
         $this->check(-1,'w');
      }

      $this->showTabs($options);
      $this->showFormHeader($options);
      
      $date = Html::convDateTime($this->fields["date_mod"]);
      if($date == '0000-00-00 00:00'){
          $date = "";
      }
       
      
       switch($this->fields["tipo_valor"]){
           case 'ticket':
               $this->fields["valor"] = $this->fields["valor_chamado"];
               $selectedticket = "Selected";
               break;
            case 'imposto':
               $this->fields["valor"] = $this->fields["valor_impostos"];
               $selectedimposto = "Selected";
               break;
            case 'tarifacao':
               $this->fields["valor"] = $this->fields["faturamento_planilha"];
               $selectedtarifacao = "Selected";
               break;
           case 'outros':
               $this->fields["valor"] = $this->fields["valor_outros"];
               $selectedoutros = "Selected";
               break;
       }
      
      echo "<tr class='tab_bg_1'>";
      echo "<td>Tipo ".($template?"*":"")."&nbsp;:</td>";
      echo "<td>";
      echo "<select name='tipo_valor'>";
      echo "<option value='ticket' {$selectedticket}>Chamado</option>"; 
      echo "<option value='imposto' {$selectedimposto}>Impostos</option>"; 
      echo "<option value='tarifacao' {$selectedtarifacao}>Faturamento (Tarifação)</option>"; 
      echo "<option value='outros' {$selectedoutros}>Outros (%)</option>";
      echo "</select>";
      echo "</td>";
      
     echo "<td>Data&nbsp;:</td>";
      echo "<td>";
       
       if($this->fields["date"] != null){
           $date = $this->fields["date"];
       }else{
           $date = date("Y-m-d H:i:s");
       }
       
       Html::showDateTimeFormItem("date", $date, 1, false);
      
      echo "</td>";
      
      echo "</tr>\n";     
      
      
      echo "<tr class='tab_bg_1'>";
      echo "<td>Valor".($template?"*":"")."&nbsp;:</td>";
      echo "<td>";
      Html::autocompletionTextField($this, "valor", array('size' => '15'));
      echo "</td>";
       
      echo "<td colspan = 2>&nbsp;</td>";      
      
      echo "</tr>\n";                 
      echo "<input type='hidden' name='contract_id' value={$_REQUEST['contract_id']}>";
      echo "<tr class='tab_bg_1'>";
      echo "<td colspan='2' class='center' height='30'>".$LANG['common'][26].":&nbsp;".$date;
      echo "</td>";       
      echo "</tr>\n";

      $this->showFormButtons($options);
      $this->addDivForTabs();      
      
      echo "</table>";
      Html::closeForm();
       
      return true;
   }
    
    
   
   //*****SUPRISERVICE******//
   function updateEquipamentos($quantidade, $id) {
      global $DB;
      
      $this->id = $id;

		/*//SUPRISERVICE*/
      return $this->update(array('id'         => $this->id,
                                 'quantidade' => $quantidade));
   }
    
    function getTabNameForItem(CommonGLPI $item, $withtemplate=0) {
      global $LANG, $CFG_GLPI;

      // Can exists on template
      if (Session::haveRight("contract","r")) {
         switch ($item->getType()) {
            default :
               if ($_SESSION['glpishow_count_on_tabs']
                   && in_array($item->getType(), $CFG_GLPI["contract_types"])) {
                  return self::createTabEntry('Faturamento', self::countForPrinter($item));
               }
               return 'Faturamento';

         }
      }
      return '';
   }
    
    static function displayTabContentForItem(CommonGLPI $item, $tabnum=1, $withtemplate=0) {
      global $CFG_GLPI;
       
       Supridesk_Faturamento::showForItem($item, $withtemplate);
       
       return true;
      
   }
     


   /**
    * Return the SQL command to retrieve linked object
    *
    * @return a SQL command which return a set of (itemtype, items_id)
    */
   /*8function getSelectLinkedItem() {

      return "SELECT `itemtype`, `items_id`
              FROM `glpi_computers_items`
              WHERE `computers_id` = '" . $this->fields['id']."'";
   }*/


   function getSearchOptions() {
      global $LANG,$CFG_GLPI;

      $tab = array();
      $tab['common'] = $LANG['common'][32];
      

      return $tab;
   }
   
   static function get_valor_bilhetagem($contract_id,$data_inicial,$data_final) {
      global $DB;
       
      $query = "SELECT SUM(`faturamento_planilha`) AS faturamento_planilha
                FROM `supridesk_faturamento`  
                WHERE date BETWEEN '{$data_inicial} 00:00:00' AND '{$data_final} 23:59:59' 
                AND `contract_id` = {$contract_id}";       
      //var_export($query);
      $result = $DB->query($query);
      $dados = $DB->fetch_assoc($result);
      
      return $dados['faturamento_planilha'];
   } 
    
    static function get_valor_aluguel($contract_id,$meses = null) {
      global $DB;
       //var_export($meses);
      $query_itens = "SELECT `valor_aluguel`,`id` FROM `supridesk_contracts_items` 
                WHERE `contracts_id` = {$contract_id}";
      
       
      $result = $DB->query($query_itens);
      
      while ($dados = $DB->fetch_assoc($result)) {
          $querycount = "SELECT COUNT(*) AS qtd_imp 
                        FROM `supridesk_contracts_items_printers` 
                        WHERE contracts_items_id = {$dados['id']} and is_active = 1";
          
          $resultcount = $DB->query($querycount);
          $dadoscount = $DB->fetch_assoc($resultcount);
          
          $valor['aluguel'][$dados['id']] = $dados['valor_aluguel'] * $dadoscount['qtd_imp'];
		  if($meses != null && $meses != 0){
              $valor['aluguel'][$dados['id']] = $valor['aluguel'][$dados['id']] * $meses;
          }
      }
        
        $soma_aluguel = array_sum($valor['aluguel']);
        
      return $soma_aluguel;
   } 
    
    static function get_valor_suprimentos($entidade,$data_inicial,$data_final){
        global $DB;
		
		
               
		$query = "SELECT count(*) as nb,
                 glpi_cartridges.id,
                 glpi_cartridges.printers_id,
                 glpi_cartridgeitemtypes.id AS id_tipo,
                 glpi_cartridges.date_out,
                 CONCAT(`glpi_cartridgeitems`.`name`, ' - ', `glpi_cartridgeitemtypes`.`name`) AS tipo,
                 DATE_FORMAT(glpi_cartridges.date_out, '%y%m') as ordem,
                 DATE_FORMAT(glpi_cartridges.date_out, '%b%y') as month_l,
                   (
                    SELECT entity_id_to
                    FROM supridesk_log_printer_entity l
                    WHERE l.printers_id = `glpi_cartridges`.printers_id
                          AND l.date <= `glpi_cartridges`.date_out
                    order by date desc
                    LIMIT 1
                    ) as id_entity_pre_troca,
                    (
                    SELECT entity_id_from
                    FROM supridesk_log_printer_entity l
                    WHERE l.printers_id = `glpi_cartridges`.printers_id
                          AND l.date >= `glpi_cartridges`.date_out
                    order by date asc
                    LIMIT 1
                    ) as id_entity_first_2012,
                    (
                    SELECT id
                    FROM glpi_entities
                    WHERE id = (SELECT entities_id FROM glpi_printers where id = `glpi_cartridges`.printers_id)
                    ) as id_entity_atual,
                    glpi_infocoms.value as valor,
                    glpi_printermodels.name as printer_model

        FROM `glpi_cartridges`,
            `glpi_cartridgeitems_printermodels`,
            `glpi_printers`,
            `glpi_printermodels`,
            `glpi_cartridgeitemtypes`,
            `glpi_cartridgeitems` LEFT JOIN `glpi_infocoms` ON ( glpi_cartridgeitems.id = glpi_infocoms.items_id and glpi_infocoms.itemtype = 'CartridgeItem')
        WHERE `glpi_cartridgeitemtypes`.`id` = `glpi_cartridgeitems`.`cartridgeitemtypes_id`
                AND `glpi_cartridges`.`cartridgeitems_id` = `glpi_cartridgeitems`.`id`
                AND `glpi_cartridges`.`printers_id` = `glpi_printers`.`id`
                AND `glpi_printers`.`printermodels_id` = `glpi_cartridgeitems_printermodels`.`printermodels_id`
                AND `glpi_cartridgeitems_printermodels`.`cartridgeitems_id` = `glpi_cartridgeitems`.`id`						
                AND (glpi_cartridges.date_out >= '{$data_inicial} 00:00:00' AND glpi_cartridges.date_out <= '{$data_final} 23:59:59' )
                and glpi_printers.printermodels_id = glpi_printermodels.id
        GROUP BY tipo, ordem, month_l, id_entity_pre_troca, id_entity_first_2012, id_entity_atual
        HAVING
            (id_entity_pre_troca is not null and id_entity_pre_troca IN (" . $entidade . ")  )
            or
            ( id_entity_pre_troca is null and id_entity_first_2012 is not null and id_entity_first_2012 IN (" . $entidade . ") )
            or
            ( id_entity_pre_troca is null and id_entity_first_2012 is null and id_entity_atual IN (" . $entidade . ") )
        ORDER BY tipo, ordem";
        
        $res = $DB->query($query);
        
        
        $meses = Array();
        $startDate = strtotime($data_inicial);
        $endDate = strtotime($data_final);
        $currentDate = $startDate;
        while ($endDate >= $currentDate) {
            $formattedDate = date('My', $currentDate);
            $meses[] = $formattedDate;
            $datas['labels2'][$formattedDate] = $formattedDate;
            $currentDate = strtotime(date('Y/m/01/', $currentDate) . ' +1 month');
        }

        //zera o array de cada item
        if (mysql_num_rows($res) > 0)
            mysql_data_seek($res, 0);
        while ($data = $DB->fetch_assoc($res)) {
            //var_export($data);
            foreach ($meses as $mes) {
                $datas['datas'][$data['tipo']][$mes] = 0;
            }
            $datas['datas'][$data['tipo']]['valor'] = 0;
            $datas['datas']['valor_total'][$data['tipo']] = 0;
            
        }

        if (mysql_num_rows($res) > 0)
            mysql_data_seek($res, 0);
        while ($data = $DB->fetch_assoc($res)) {
            
            $sumTipo = $datas['datas'][$data['tipo']][$data['month_l']] + $data['nb'];
            $datas['datas'][$data['tipo']][$data['month_l']] = $sumTipo;
            $datas['datas'][$data['tipo']]['valor'] = $data['valor'];            
            $datas['datas']['nb'][$data['tipo']][$data['month_l']] = $sumTipo;
            
        }
        
        if (mysql_num_rows($res) > 0)
            mysql_data_seek($res, 0);
        while ($data = $DB->fetch_assoc($res)) {
            
            $datas['datas']['totalnb'][$data['tipo']]  = array_sum($datas['datas']['nb'][$data['tipo']]);
            //$total_formatnb = number_format($total_nb, 2, ',', '.');
        }
        
        if (mysql_num_rows($res) > 0)
            mysql_data_seek($res, 0);
        while ($data = $DB->fetch_assoc($res)) {
            
            if($data['id_tipo'] == 1 || $data['id_tipo'] == 2 || $data['id_tipo'] == 9 || $data['id_tipo'] == 10 || $data['id_tipo'] == 11 || $data['id_tipo'] == 25){
                
                $porcentagem = (10 * 100) / $data['valor'];
                $data['valor'] = $data['valor'] + $porcentagem;
                
            }
            
            $datas['datas']['valor_total'][$data['tipo']] = $datas['datas']['totalnb'][$data['tipo']] * $data['valor'];
            
        }
        
        $total_valor  = array_sum($datas['datas']['valor_total']);
        //$total_format = number_format($total_valor, 2, ',', '.');        
        
        return $total_valor;
        
    }
	
	static function getEntityTree($entidade){
        global $DB;
        
        $query = "SELECT `id` FROM `glpi_entities` WHERE `entities_id` = {$entidade}";
        $res = $DB->query($query);
        if (mysql_num_rows($res) > 0){
            while($data = $DB->fetch_assoc($res)){
                $entities[] = $data['id']; 
                $query_2 = "SELECT `id` FROM `glpi_entities` WHERE `entities_id` = {$data['id']}";
                $res2 = $DB->query($query_2);
                
                if (mysql_num_rows($res2) > 0){
                    while($data2 = $DB->fetch_assoc($res2)){
                        $entities[] = $data2['id'];

                        $query_3 = "SELECT `id` FROM `glpi_entities` WHERE `entities_id` = {$data2['id']}";
                        $res3 = $DB->query($query_3);
                        
                        if (mysql_num_rows($res3) > 0){
                            while($data3 = $DB->fetch_assoc($res3)){
                                $entities[] = $data3['id'];
                                
                                $query_4 = "SELECT `id` FROM `glpi_entities` WHERE `entities_id` = {$data3['id']}";
                                $res4 = $DB->query($query_4);
                                
                                if (mysql_num_rows($res4) > 0){
                                    while($data4 = $DB->fetch_assoc($res4)){
                                        $entities[] = $data4['id'];

                                        $query_5 = "SELECT `id` FROM `glpi_entities` WHERE `entities_id` = {$data4['id']}";
                                        $res5 = $DB->query($query_5);
                                        
                                        if (mysql_num_rows($res5) > 0){
                                            while($data5 = $DB->fetch_assoc($res5)){
                                                $entities[] = $data5['id'];
                                            }
                                        }
                                    }
                                }
                            }
                        }
                    }
                }
            }
        }else{
            return $entidade;
        }
        //var_export($entities);
        $entidade2 = array($entidade);
        $result = array_merge($entities,$entidade2);
        //$result = array_values($result);
        //var_export($result);
        return $result;
        
    }
    
    static function get_valor_pecas($contracts,$data_inicial,$data_final){
        global $DB;             
                
        $query = "SELECT `glpi_tickets`.`entities_id`, 
                           `supridesk_pecas_ticket`.`quantidade`,
                           `supridesk_pecas_ticket`.`peca_id`,
                           `supridesk_pecas`.`value`,
                           `supridesk_pecas_ticket`.`id` AS id,
                           `glpi_contracts`.`name`
                    FROM `supridesk_pecas_ticket`
                    LEFT JOIN `glpi_tickets` ON `glpi_tickets`.`id` = `supridesk_pecas_ticket`.`tickets_id`
                    LEFT JOIN `glpi_entities` ON `glpi_tickets`.`entities_id` = `glpi_entities`.`id`
                    LEFT JOIN `supridesk_pecas` ON `supridesk_pecas`.`id` = `supridesk_pecas_ticket`.`peca_id`
                    LEFT JOIN `glpi_contracts` ON `glpi_contracts`.`id` = `glpi_tickets`.`contracts_id`
                    WHERE `glpi_contracts`.`id` IN (".$contracts.")
                           AND `supridesk_pecas_ticket`.`data` 
                           BETWEEN '".$data_inicial."' AND '".$data_final."'";            
        
        $res = $DB->query($query);
        if (mysql_num_rows($res) > 0)
            mysql_data_seek($res, 0);

        while ($data = $DB->fetch_assoc($res)) {
            
            $valor = $data['quantidade'] * $data['value'];
            $return[$data['name']] [$data['id']] = $valor;
            $sum_array = array_sum($return[$data['name']]);            
            $total_name[$data['name']] = $sum_array; 
            
        }
        
        if (mysql_num_rows($res) > 0)
            mysql_data_seek($res, 0);
        while ($data = $DB->fetch_assoc($res)) {
            $return[$data['name']][$data['id']] = $data['quantidade'];            
            $sum_array_qtd = array_sum($return[$data['name']]);
            $total_quantidade[$data['name']] = $sum_array_qtd;   
            
        }

        if (mysql_num_rows($res) > 0)
            mysql_data_seek($res, 0);
        while ($data = $DB->fetch_assoc($res)) {
            $datas['datas'][$data['name'] . " - " . $total_name[$data['name']]] = $total_quantidade[$data['name']];
            $valorpecas = $total_name[$data['name']];
        }
       
        if(!isset($valorpecas)){
            $valorpecas = '0.00';
        }
        
        return $valorpecas;
        
    }
    
    static function get_valor_chamado($data_inicial,$data_final,$entidade,$contract_id){
        global $DB;
        
        $query = "SELECT
                DISTINCT DATE_FORMAT(date, '%y%m') as month,
                DATE_FORMAT(date, '%b%y') as month_l,
                COUNT(id) as nb
                FROM glpi_tickets
                WHERE (glpi_tickets.date >= '{$data_inicial} 00:00:00' AND glpi_tickets.date <= ADDDATE('{$data_final} 00:00:00' , INTERVAL 1 DAY)) 
                AND glpi_tickets.entities_id IN (" . $entidade . ")
                AND glpi_tickets.is_deleted = '0'
                GROUP BY month
                ORDER BY month";
        
        $res = $DB->query($query);
       // var_export($query);
        while ($data = $DB->fetch_assoc($res)) {
            $datas['datas'][$data_inicial]['total'] = $data['nb'];           
        }
        
        $query_ = "SELECT `valor_chamado` FROM `supridesk_faturamento` 
                    WHERE `tipo_valor` = 'ticket' AND `contract_id` = {$contract_id}  
                        AND `date` BETWEEN '{$data_inicial} 00:00:00' AND '{$data_final} 23:59:59'
                    ORDER BY `date` DESC limit 1";
                        
        $res_ = $DB->query($query_);
        $data_ = $DB->fetch_assoc($res_);
        
        if($data_['valor_chamado'] == NULL){
            
            $query2 = "SELECT `valor_chamado` FROM `supridesk_faturamento` 
                WHERE `tipo_valor` = 'ticket' AND `contract_id` = {$contract_id} 
                    AND `date` < '{$data_inicial} 00:00:00'
                ORDER BY `date` DESC limit 1";

                $res2 = $DB->query($query2);
                $data2 = $DB->fetch_assoc($res2);                
            
                
             if($data2['valor_chamado'] == NULL){
                 
                 $query3 = "SELECT `valor_chamado` FROM `supridesk_faturamento` 
                WHERE `tipo_valor` = 'ticket' AND `contract_id` = {$contract_id} 
                    AND `date` > '{$data_inicial} 00:00:00'
                ORDER BY `date` ASC limit 1";

                $res3 = $DB->query($query3);
                $data3 = $DB->fetch_assoc($res3);
                $data2['valor_chamado'] = $data3['valor_chamado'];
                
             }

            $datas['datas'][$data_inicial]['valor'] = $data2['valor_chamado'];
        }else{
            $datas['datas'][$data_inicial]['valor'] = $data_['valor_chamado'];
        }
        //var_export($datas['datas'][$data_inicial]['total']);
        $total = $datas['datas'][$data_inicial]['valor'] * $datas['datas'][$data_inicial]['total'];
        
        return $total;
    }
    
    static function get_valor_leasing($contract_id){
        global $DB;
        
        $query = "SELECT `id` FROM `supridesk_contracts_items` 
                    WHERE `contracts_id` = {$contract_id}";
        
        $res = $DB->query($query);
        
        while($data = $DB->fetch_assoc($res)){
            $query_printer = "SELECT `printers_id`,`type` FROM `supridesk_contracts_items_printers` 
                    WHERE `contracts_items_id` = {$data['id']}";
                    
            $res_printer = $DB->query($query_printer);
            
            while($data_printer = $DB->fetch_assoc($res_printer)){
                $printers['printer_id'][] = array($data_printer['printers_id'],$data_printer['type']);
            }
        }
        //var_export($printers['printer_id']);
        foreach($printers['printer_id'] as $printer){
            
            switch($printer[1]){
                case 'Printer':
                    $query_valor = "SELECT `value` FROM `glpi_infocoms` 
                    WHERE `itemtype` = 'Printer' AND `items_id` = {$printer[0]}";
                    
                    $res_valor = $DB->query($query_valor);
                    $data_valor = $DB->fetch_assoc($res_valor);
                    $equipamento['valor'][$printer[0]] = $data_valor['value'];
                    $valor = $data_valor['value'];
                    break;
                case 'Computer':
                    $query_valor = "SELECT `value` FROM `glpi_infocoms` 
                    WHERE `itemtype` = 'Computer' AND `items_id` = {$printer[0]}";
                    
                    $res_valor = $DB->query($query_valor);
                    $data_valor = $DB->fetch_assoc($res_valor);
                    $equipamento['valor'][$printer[0]] = $data_valor['value'];
                    $valor = $data_valor['value'];
                    break;
                case 'Monitor':
                    $query_valor = "SELECT `value` FROM `glpi_infocoms` 
                    WHERE `itemtype` = 'Monitor' AND `items_id` = {$printer[0]}";
                    
                    $res_valor = $DB->query($query_valor);
                    $data_valor = $DB->fetch_assoc($res_valor);
                    $equipamento['valor'][$printer[0]] = $data_valor['value'];
                    $valor = $data_valor['value'];
                    break;
            } 
          
            $query_leasing = "SELECT `fator` FROM `supridesk_leasing` 
                    WHERE `equipamento_id` = {$printer[0]}";
                    
            $res_leasing = $DB->query($query_leasing);
            $data_leasing = $DB->fetch_assoc($res_leasing);
            
            $valorleasing['valorleasing'][] = $valor * $data_leasing['fator'];
                    
        }
        
        $total_leasing = array_sum($valorleasing['valorleasing']);
        return $total_leasing;
       
    }
	
    static function getQtdMeses($datainicial, $datafinal){
        global $DB;
        
        $query = "SELECT DATEDIFF('$datafinal','$datainicial') AS qtd";
        $res = $DB->query($query);
        $qtd = $DB->fetch_assoc($res);
        
        return $qtd['qtd'];
    }
    
    static function getValorOutros($data_inicial,$data_final,$contract_id){
        global $DB;
        
        
        // Valor tarifacao
        $query = "SELECT `faturamento_planilha` FROM `supridesk_faturamento` 
                WHERE `tipo_valor` = 'tarifacao' AND `contract_id` = {$contract_id} 
                    AND `date` BETWEEN '{$data_inicial} 00:00:00' AND '{$data_final} 23:59:59'
                ORDER BY `date` ASC limit 1";                    
        
        $res = $DB->query($query);
        $data = $DB->fetch_assoc($res);
        $tarifacao = $data['faturamento_planilha'];
        
        if($tarifacao == NULL){
            $tarifacao = 0;
        }
        //////////////////////////////////////////////////////
        
        // Valor de Aluguel
        $query_aluguel = "SELECT `valor_aluguel`,`id` FROM `supridesk_contracts_items` 
                WHERE `contracts_id` = {$contract_id}";
        $resaluguel = $DB->query($query_aluguel);
        
         while ($dataaluguel = $DB->fetch_assoc($resaluguel)) {
             
            $querycount = "SELECT COUNT(*) AS qtd_imp 
                          FROM `supridesk_contracts_items_printers` 
                          WHERE contracts_items_id = {$dataaluguel['id']} and is_active = 1";

            $resultcount = $DB->query($querycount);
            $dadoscount = $DB->fetch_assoc($resultcount);

            $valor['aluguel'][$dataaluguel['id']] = $dataaluguel['valor_aluguel'] * $dadoscount['qtd_imp'];              
         }
         
         $soma_aluguel = array_sum($valor['aluguel']);       
        ///////////////////////////////////////////////////
                
        // Valor Porcentagem Outros
        $query2 = "SELECT SUM(`valor_outros`) AS valor_outros FROM `supridesk_faturamento` 
                WHERE `tipo_valor` = 'outros' AND `contract_id` = {$contract_id} 
                    AND `date` BETWEEN '{$data_inicial} 00:00:00' AND '{$data_final} 23:59:59'
                ORDER BY `date`";
                    
        $res2 = $DB->query($query2);
        $data2 = $DB->fetch_assoc($res2);            
                    
        if($data2['valor_outros'] == NULL){
            
            $query3 = "SELECT SUM(`valor_outros`) AS valor_outros FROM `supridesk_faturamento` 
                WHERE `tipo_valor` = 'outros' AND `contract_id` = {$contract_id} 
                    AND `date` < '{$data_inicial} 00:00:00'
                ORDER BY `date`";

                $res3 = $DB->query($query3);
                $data3 = $DB->fetch_assoc($res3);                
            
               
             if($data3['valor_outros'] == NULL){
                 
                 $query4 = "SELECT SUM(`valor_outros`) AS valor_outros FROM `supridesk_faturamento` 
                WHERE `tipo_valor` = 'outros' AND `contract_id` = {$contract_id} 
                    AND `date` > '{$data_inicial} 00:00:00'
                ORDER BY `date`";

                $res4 = $DB->query($query4);
                $data4 = $DB->fetch_assoc($res4);
                $data3['valor_outros'] = $data4['valor_outros'];
                
             }

            $valor_outros = $data3['valor_outros'];
        }else{
            $valor_outros = $data2['valor_outros'];
        }
        ////////////////////////////
        $totalfat = $tarifacao + $soma_aluguel;
        $total = $totalfat * ($valor_outros / 100);
        
        return $total;
    }
	
	function delete_faturamento($id){
        global $DB;
        
      $query = "DELETE FROM `supridesk_faturamento` WHERE `id` =  {$id}";
                
      $result = $DB->query($query);
    }

}

?>
