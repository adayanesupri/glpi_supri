<?php
/*
 * @version $Id: networkport.class.php 18771 2012-06-29 08:49:19Z moyo $
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

/// Contract_Item_Supridesk class
class TransportLogs extends CommonDBTM {

   // From CommonDBTM
   public $dohistory = true;
	protected $table = "supridesk_veiculos_km";


   static function getTypeName($nb=0) {
      global $LANG;

      return "Kilometragem";
   }


   function canCreate() {
      return Session::haveRight('transport', 'w');
   }


   function canCreateItem() {
      return Session::haveRight('transport', 'w');
   }


   function canView() {
      return Session::haveRight('transport', 'r');
   }


   function canViewItem() {
      return Session::haveRight('transport', 'r');
   }


   function canUpdate() {
      return Session::haveRight('transport', 'w');
   }


   function canUpdateItem() {
      return Session::haveRight('transport', 'w');
   }

   function prepareInputForUpdate($input) {
      return $input;
   }


   function pre_deleteItem() {
      return true;
   }


   function defineTabs($options=array()) {
      global $LANG;

      $ong = array();
      //$this->addStandardTab('Contract_Item_Printer_Supridesk', $ong, $options);
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
      $veiculo_id = $item->getField('id');

      if (!Session::haveRight('transport','r') || !$item->can($veiculo_id, 'r')) {
         return false;
      }

      $canedit = $item->can($veiculo_id, 'w');
       //var_export(array($canedit));
      $showmassiveactions = false;
      if ($withtemplate!=2) {
         $showmassiveactions = count(Dropdown::getMassiveActions(__CLASS__));
      }


      Session::initNavigateListItems('TransportLogs', $item->getTypeName()." = ".$item->getName());

      $query = "SELECT *
					FROM `supridesk_veiculos_km`
					WHERE `veiculo_id` = $veiculo_id
					ORDER BY printerlogkm_date DESC";
       
       //var_export($query);

      if ($result = $DB->query($query)) {
         echo "<div class='spaced'>";

         if ($DB->numrows($result) != 0) {
            $colspan = 5;

            if ($showmassiveactions) {
               $colspan++;
               echo "\n<form id='transportlogs$rand' name='transportlogs$rand' method='post'
                     action='" . $CFG_GLPI["root_doc"] . "/front/transportlogs.form.php'>\n";
            }
            
            echo "\n<div class='firstbloc'><table class='tab_cadre_fixe'>";
            echo "<tr><td class='tab_bg_2 center b'>";
            echo "<a href='" . $CFG_GLPI["root_doc"] . "/front/transportlogs.form.php?veiculo_id=" .
                         $veiculo_id . "'>";
            echo "<img src=\"" . $CFG_GLPI["root_doc"] . "/pics/add_dropdown.png\" alt=\"" . $LANG['buttons'][8] . "\" title=\"" . $LANG['buttons'][8] . "\"> ";
			echo "Check-in / Check-out";
			echo "</a></td>\n";
            echo "</tr></table></div>\n";

            echo "<table class='tab_cadre_fixe'>\n";

            echo "<tr><th colspan='$colspan'>\n";
            echo "Registros encontrados: ".$DB->numrows($result)."</th></tr>\n";

            echo "<tr>";
            if ($showmassiveactions) {
               echo "<th>&nbsp;</th>\n";
            }
            echo "<th>Data</th>\n";
            echo "<th>Km</th>\n";
            echo "<th>Checkin/Checkout</th>\n";
            echo "<th>Observação</th>\n";
            echo "<th>User</th>\n";
           // echo "<th>Ticket</th>\n";
             
             
            
            $i = 0;
            $transportLogs = new TransportLogs();

            while ($devid = $DB->fetch_row($result)) {
               $transportLogs->getFromDB(current($devid));                

               Session::addToNavigateListItems('TransportLogs', $transportLogs->fields["id"]);
               
               echo "<tr class='tab_bg_1'>\n";
               if ($showmassiveactions) {
                  echo "<td class='center' width='20'>";
                  echo "<input type='checkbox' name='item[".$transportLogs->fields["id"]."]' value='".$transportLogs->fields["id"]."'>";
                  echo "</td>\n";
               }
               echo "<td class='center'><span class='b'>";
               if ($canedit && $withtemplate != 2) {
                  echo "<a href=\"" . $CFG_GLPI["root_doc"] . "/front/transportlogs.form.php?id=" .
                         $transportLogs->fields["id"] . "\">";
               }
               echo $transportLogs->fields["printerlogkm_date"];
               if ($canedit && $withtemplate != 2) {
                  echo "</a>";
               }
               //Html::showToolTip($printerLogs->fields['comment']);
               echo "</td>\n";
               
               //Kilometragem
               echo "<td class='center'>".$transportLogs->fields["km"]."</td>\n";
                
               //Checkin/checkout
                switch($transportLogs->fields["type_checkin"]){
                    case 0:
                        $transportLogs->fields["type_checkin"] = "Chegada";
                        break;                        
                    case 1:
                        $transportLogs->fields["type_checkin"] = "Saída";
                        break;
                    case 2:
                        $transportLogs->fields["type_checkin"] = " - ";
                        break;                    
                }                
                   
               echo "<td class='center'>".$transportLogs->fields["type_checkin"]."</td>\n";
                
               //Observação
                   
               echo "<td class='center'>".$transportLogs->fields["obs"]."</td>\n";
                
               //Usuario
                if($transportLogs->fields["user"] == NULL){
                    $transportLogs->fields["user"] = ' - ';
                }
               echo "<td class='center'>".$transportLogs->fields["user"]."</td>\n";   
               
               //Ticket
              /* if($transportLogs->fields["tickets_id"] == 0){
                   $transportLogs->fields["tickets_id"] = " - ";
                    
                }
               echo "<td class='center'>".$transportLogs->fields["tickets_id"]."</td>\n";*/
					
               if ($canedit && $withtemplate != 2) {
                  echo "</a>";
               }
               echo "</tr>\n";
            }
            echo "</table>\n";

            if ($showmassiveactions) {
               Html::openArrowMassives("transportlogs$rand", true);
               Dropdown::showForMassiveAction('TransportLogs');
               $actions = array();
               Html::closeArrowMassives($actions);
               Html::closeForm();
            }

         } else {
            echo "<table class='tab_cadre_fixe'><tr><th>".$LANG['tarifacao'][33]."</th></tr>";
            echo "</table>";
         }
          
         echo "\n<div class='firstbloc'><table class='tab_cadre_fixe'>";
         echo "<tr><td class='tab_bg_2 center b'>";
         echo "<a href='" . $CFG_GLPI["root_doc"] . "/front/transportlogs.form.php?veiculo_id=" .
                         $veiculo_id . "'>";
         echo "<img src=\"" . $CFG_GLPI["root_doc"] . "/pics/add_dropdown.png\" alt=\"" . $LANG['buttons'][8] . "\" title=\"" . $LANG['buttons'][8] . "\"> ";
			echo "Check-in / Check-out";
			echo "</a></td>\n";
         echo "</tr></table></div>\n";
         echo "</div>";
      }
   }


   function showForm($ID, $options=array()) {
      global $CFG_GLPI, $LANG;

      if (!isset($options['several'])) {
         $options['several'] = false;
      }

      if (!Session::haveRight("transport", "r")) {
         return false;
      }

      if ($ID > 0) {
         $this->check($ID,'r');
      } else {
         $input = array('itemtype' => $options["itemtype"], 'veiculo_id' => $options["id"]);
         // Create item
         $this->check(-1, 'w', $input);
      }

      $link = NOT_AVAILABLE;

       $item = new TransportLogs();
       $type = $item->getTypeName();
       
       $itemtype = $item->getType();
       $veiculo_id = $item->getField('id');
      // var_export($type);

      // Ajout des infos deja remplies
      if (isset($_POST) && !empty($_POST)) {
         foreach ($this->fields as $key => $val) {
            if ($key!='id' && isset($_POST[$key])) {
               $this->fields[$key] = $_POST[$key];
            }
         }
      }

      $this->showTabs();

      $options['colspan'] = 2;
      $this->showFormHeader($options);

      echo "<tr class='tab_bg_1'><td>Data&nbsp;:</td>\n<td>";      
      echo "<input type='hidden' name='veiculo_id' value='".$options['veiculo_id']."'>\n";
//var_export($options);
       
       if($this->fields["printerlogkm_date"] != null){
           $date = $this->fields["printerlogkm_date"];
       }else{
           $date = date("Y-m-d H:i:s");
       }
       
       Html::showDateTimeFormItem("printerlogkm_date", $date, 1, false);		

      echo "</td>";
          
      echo "<td>Kilometragem :</td>\n";
      echo "<td>";
		Html::autocompletionTextField($this,"km", array('size' => '15'));
      echo "</td>";
      echo "</tr>\n";
       
       if($this->fields["type_checkin"] == 0){
           $selected0 = "selected";
       }elseif($this->fields["type_checkin"] == 1){
           $selected1 = "selected";
       }
       
      echo "<tr class='tab_bg_1'><td>Tipo&nbsp;:</td>\n<td>";     
      echo "<select name='type_checkin'>";
      echo "<option value='0' {$selected0}>Chegada</option>"; 
      echo "<option value='1' {$selected1}>Saída</option>"; 
      echo "</select>"; 
      echo "</td>";
          
      echo "<td>Observação :</td>\n";
      echo "<td><textarea cols='40' rows='4' name='obs'>".$this->fields["obs"]."</textarea>";     
      echo "</td>";

      echo "</tr>\n";

      echo "<tr class='tab_bg_1'><td class='center' colspan=2></td></tr>";
      echo "<tr class='tab_bg_1'><td></td>\n";
      echo "<td></td>";     
      echo "<td></td>\n";
      echo "<td></td>";
      echo "</tr>\n";
      
       
       if($_SESSION['glpiactiveprofile']['name'] == 'super-admin'){
           
           $this->showFormButtons($options);
       }else{
           if($this->fields["id"] == ''){
           echo "<tr><td class='tab_bg_2 center' colspan='4'><input type='submit' name='add' value='Adicionar' class='submit'></td></tr>";
           echo "</tbody>";
           echo "</table>";
        }else{
            echo "</tbody>";
           echo "</table>";
        }
       }

      //$this->showFormButtons($options);
      $this->addDivForTabs();
   }


   function getTabNameForItem(CommonGLPI $item, $withtemplate=0) {
      global $LANG, $CFG_GLPI;

      // Can exists on template
      if (Session::haveRight("transport","r")) {
         switch ($item->getType()) {
            default :
               if ($_SESSION['glpishow_count_on_tabs']
                   && in_array($item->getType(), $CFG_GLPI["contract_types"])) {
                  return self::createTabEntry('Kilometragem', self::countForPrinter($item));
               }
               return 'Kilometragem';

         }
      }
      return '';
   }

   static function countForPrinter(Printer $item) {

      $restrict = "`supridesk_veiculos_km`.`id` = '".$item->getField('id')."'";

      return countElementsInTable(array('supridesk_veiculos_km'), $restrict);
   }

   static function displayTabContentForItem(CommonGLPI $item, $tabnum=1, $withtemplate=0) {
      global $CFG_GLPI;
       
       TransportLogs::showForItem($item, $withtemplate);
       
       return true;

      /*switch ($item->getType()) {
         case 'Printer' :
            if (in_array($item->getType(), $CFG_GLPI["contract_types"])) {
               Fusioninventory_Printerlogs::showForItem($item, $withtemplate);
            }
				break;
         default :
            if (in_array($item->getType(), $CFG_GLPI["contract_types"])) {
               Contract::showAssociated($item, $withtemplate);
            }
      }
      return true;*/
   }
    
     function getSearchOptions() {
      global $LANG,$CFG_GLPI;

      $tab = array();
      $tab['common'] = $LANG['common'][32];

      $tab[1]['table']         = $this->getTable();
      $tab[1]['field']         = 'veiculo_id';
      $tab[1]['datatype']      = 'number';      
      $tab[1]['massiveaction'] = false; // implicit key==1

      $tab[2]['table']         = $this->getTable();
      $tab[2]['field']         = 'id';
      $tab[2]['name']          = $LANG['common'][2];
      $tab[2]['massiveaction'] = false; // implicit field is id
      
      $tab[3]['table']         = $this->getTable();
      $tab[3]['field']         = 'tickets_id';
      $tab[3]['datatype']     = 'number';
      $tab[3]['massiveaction'] = false;
      
      $tab[4]['table']         = $this->getTable();
      $tab[4]['field']         = 'km';
      $tab[4]['datatype']      = 'number';
      $tab[4]['massiveaction'] = false;
      
      $tab[5]['table']         = $this->getTable();
      $tab[5]['field']         = 'printerlogkm_date';
      $tab[5]['datatype']      = 'datetime';
      $tab[5]['massiveaction'] = false;      
      

      return $tab;
   }
    
        
    function add_printerlogs($result){
        global $DB;
        
      $query = "INSERT INTO 
                    supridesk_veiculos_km 
                        (veiculo_id,tickets_id,km,printerlogkm_date) 
                    VALUES 
                        ({$result['veiculo_id']},{$result['tickets_id']},{$result['km']},'".$result['printerlogkm_date']."')";
                
      $result = $DB->query($query);
    }
    
    function update_printerlogs($result){
        global $DB;
        
      $query = "UPDATE supridesk_veiculos_km 
                SET km = {$result['km']},
                    printerlogkm_date = '".$result['printerlogkm_date']."' 
                WHERE
                    id = {$result['id']}";
                
      $result = $DB->query($query);
    }
    
    function delete_printerlogs($id){
        global $DB;
        
      $query = "DELETE FROM `supridesk_veiculos_km` WHERE `id` =  {$id}";
                
      $result = $DB->query($query);
    }
    
    function confere_checkin($veiculo_id,$user){
        global $DB;
        
        $query = "SELECT `id`,`km`,`printerlogkm_date` FROM `supridesk_veiculos_km` 
                WHERE `type_checkin` = 1 
                    AND `checked` IS NULL 
                    AND `tickets_id` = 0 
                    AND `veiculo_id` = {$veiculo_id}
                    AND `user` = '".$user."'
                ORDER BY `id` DESC
                LIMIT 1";
                
        $result = $DB->query($query);
        
        return $DB->fetch_assoc($result);
        
    }
    
    function confere_checkin_entrada($veiculo_id,$user){
        global $DB;
        
        $query = "SELECT `id`,`km`,`printerlogkm_date` FROM `supridesk_veiculos_km` 
                WHERE `type_checkin` = 0
                    AND `tickets_id` = 0 
                    AND `veiculo_id` = {$veiculo_id}
                ORDER BY `id` DESC
                LIMIT 1";
                
        $result = $DB->query($query);
        
        return $DB->fetch_assoc($result);
        
    }
    
    function confere_checkin_saida_other($veiculo_id,$user){
        global $DB;
        
        $query = "SELECT `id`,`km`,`printerlogkm_date` FROM `supridesk_veiculos_km` 
                WHERE `type_checkin` = 1 
                    AND `checked` IS NULL 
                    AND `tickets_id` = 0 
                    AND `veiculo_id` = {$veiculo_id}
                    AND `user` != '".$user."'
                ORDER BY `id` DESC
                LIMIT 1";
                
        $result = $DB->query($query);
        
        return $DB->fetch_assoc($result);
        
    }
    
    function confere_checkin_saida($veiculo_id,$user){
        global $DB;
        
        $query = "SELECT `id`,`km`,`printerlogkm_date` FROM `supridesk_veiculos_km` 
                WHERE `type_checkin` = 1 
                    AND `checked` IS NULL 
                    AND `tickets_id` = 0 
                    AND `veiculo_id` = {$veiculo_id}
                    AND `user` = '".$user."'
                ORDER BY `id` DESC
                LIMIT 1";
                
        $result = $DB->query($query);
        
        return $DB->fetch_assoc($result);
        
    }
    
    function confere_($veiculo_id){
        global $DB;
        
        $query = "SELECT `km`,`printerlogkm_date` FROM `supridesk_veiculos_km` 
                WHERE `tickets_id` = 0 AND `veiculo_id` = {$veiculo_id}
                ORDER BY `id` DESC
                LIMIT 1";
                
        $result = $DB->query($query);
        $dados = $DB->fetch_assoc($result);
        
        return $dados['km'];
        
    }
    
    function update_checkin($id){
        global $DB; 
        
      $query = "UPDATE supridesk_veiculos_km 
                SET checked = 'ok'
                WHERE
                    id = {$id}";        
        
      $result = $DB->query($query);
    }
    
    function update_($km,$type_checkin,$printerlogkm_date,$id,$obs){
        global $DB;     
        
      $query = "UPDATE supridesk_veiculos_km 
                SET km = {$km}, 
                    type_checkin = {$type_checkin},
                    printerlogkm_date = '".$printerlogkm_date."',
                    obs = '".$obs."'
                WHERE
                    id = {$id}";
        
      $result = $DB->query($query);
    }

}

?>
