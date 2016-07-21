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
class Stock extends CommonDBTM {
    
    /*//SUPRISERVICE*/
   public $supridesk_custom_table = "supridesk_pecas";
   
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
         return $LANG['stock'][1];
      }
      return $LANG['stock'][0];
   }


   function canCreate() {
      return Session::haveRight('stock', 'w');
   }


   function canView() {
      return Session::haveRight('stock', 'r');
   }


   function defineTabs($options=array()) {
      global $LANG, $CFG_GLPI;

      $ong = array();
      $this->addStandardTab('Stock', $ong, $options);
      //
      $this->addStandardTab('PecasTypeModel', $ong, $options);
      $this->addStandardTab('Log', $ong, $options); 
      //$this->addStandardTab('Log', $ong, $options);

      return $ong;
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
       
       $tk = new Ticket(); 

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
       
       switch($this->fields["itemtype"]){
           case 'Computer':
               $dev_modelo = 'glpi_computermodels';
               $dev_tipo = 'glpi_computertypes';
               $itemtypem = "ComputerModel";
               break;
            case 'Monitor':
               $dev_modelo = 'glpi_monitormodels';
               $dev_tipo = 'glpi_monitortypes';
               $itemtypem = "MonitorModel";
               break;
            case 'Printer':
               $dev_modelo = 'glpi_printermodels';
               $dev_tipo = 'glpi_printertypes';
               $itemtypem = "PrinterModel";
               break;               
       }
       
       
      $dev_items_id = $this->fields["modelo_id"];
      $dev_itemstype_id = $this->fields["tipo_id"];
      $options['_users_id_requester'] = Session::getLoginUserID();
      
      echo "<tr class='tab_bg_1'>";
      echo "<td>".$LANG['common'][16].($template?"*":"")."&nbsp;:</td>";
      echo "<td>";
      $objectName = autoName($this->fields["name"], "name", ($template === "newcomp"),
                             $this->getType(), $this->fields["entities_id"]);
      Html::autocompletionTextField($this, 'name', array('value' => $objectName));
      echo "</td>";
      
      echo "<td>".$LANG['state'][0]."&nbsp;:</td>";
      echo "<td>";
      Dropdown::show('State', array('value' => $this->fields["states_id"]));
      echo "</td></tr>";  
      
      echo "</tr>\n";
      
      echo "<tr class='tab_bg_1'>";
      echo "<td>".$LANG['stock'][3].($template?"*":"")."&nbsp;:</td>";
      echo "<td><input type='text' name='value' $option value='".
                  Html::formatNumber($this->fields["value"], true)."' size='14'>";
      echo "</td>";
      
      echo "<td>Part Number".($template?"*":"")."&nbsp;:</td>";
      echo "<td>";
      Html::autocompletionTextField($this, "part_number");
      echo "</td>";       
      
      echo "</tr>\n";
      
      echo "<tr class='tab_bg_1'>";
      echo "<td>".$LANG['stock'][2].($template?"*":"")."&nbsp;:</td>";
      echo "<td>";
      Html::autocompletionTextField($this, "quantidade",array('size' => 10));
      echo "</td>";      
      // var_export(array($dev_tipo, $dev_itemstype_id,));
      
      echo "<td>".$LANG['common'][5]."&nbsp;:</td>";
      echo "<td>";
      Dropdown::show('Manufacturer', array('value' => $this->fields["manufacturers_id"]));
      echo "</td>";
      
           
      echo "</tr>\n";      
       
      echo "<tr class='tab_bg_1'>";
      echo "<td>Descrição".($template?"*":"")."&nbsp;:</td>";
      echo "<td><textarea cols='40' rows='4' name='descricao'>".$this->fields["descricao"]."</textarea>";     
      echo "</td>";   
      
      echo "<td>Para &nbsp;:</td>";
      echo "<td>";
      $rand = mt_rand();
      $types = array("Computer" => "Computador", "Monitor" => "Monitor" , "Printer" => "Impressora");
                
        echo "<select id='search_itemtype$rand' name='itemtype'>\n";
        echo "<option value='-1' >" . Dropdown::EMPTY_VALUE . "</option>\n";
        $found_type = false;

        foreach ($types as $type => $label) {                    
            if (strcmp($type, $itemtype) == 0) {
                $found_type = true;
            }
            echo "<option value='" . $type . "' " . (strcmp($type, $this->fields["itemtype"]) == 0 ? " selected" : "") . ">" . $label;
            echo "</option>\n";
        }
          
      echo "</select></td>";
       
      
      
               
     // echo "<td>&nbsp;</td>";
     // echo "<td>&nbsp;</td>";
      
      echo "<tr class='tab_bg_1'>";
      echo "<td colspan='2' class='center' height='30'>".$LANG['common'][26].":&nbsp;".$date;
      echo "</td>";
      echo "</tr>\n";

      $this->showFormButtons($options);
      $this->addDivForTabs();
       
       if($this->fields["itemtype"] != NULL){
           
           echo "<form method='post' action=\"".$CFG_GLPI["root_doc"]."/front/stock.form.php\">";
          echo "<table class='tab_cadre_fixe'>";
          echo "<tr><td class='center tab_bg_2'>Modelo &nbsp;:</td>";
          echo "<td>";
           Dropdown::show($this->fields["itemtype"].'Model');         
          echo "<input type='hidden' name='tID' value='$ID'>\n";
          echo "<input type='hidden' name='tType' value='{$this->fields["itemtype"]}'>\n";
          echo "</td>";

          echo "<td class='center tab_bg_2'>Tipo &nbsp;:</td>";
          echo "<td>"; 
           Dropdown::show($this->fields["itemtype"].'Type');      
          echo "</td>";
          echo "</tr>";
          echo "<tr><td class='center tab_bg_2' colspan=4>           
                    <input type='submit' name='add_model' value=\"".$LANG['buttons'][8]."\" class='submit'></td>";
          echo "</tr>";
          echo "</table>";
          echo "</form>";
       }
       
       
      
      
      //echo "<div class='firstbloc'>";
      echo "<form method='post' action=\"".$CFG_GLPI["root_doc"]."/front/stock.form.php\">";
      echo "<table class='tab_cadre_fixe'>";
      echo "<tr><td class='center tab_bg_2'>";
       echo "<span class='small_space'>";
      Dropdown::showInteger('to_add',1,1,100);
      echo "</span>&nbsp;";
      echo $LANG['stock'][0]."&nbsp;&nbsp;";
      
      echo "<input type='hidden' name='tID' value='$ID'>\n";
      echo "<input type='hidden' name='qtd_atual' value='".$this->fields["quantidade"]."'>\n";
      echo "<input type='submit' name='add_several' value=\"".$LANG['buttons'][8]."\"
           class='submit'></td></tr>";
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

      $tab[1]['table']         = $this->getTable();
      $tab[1]['field']         = 'name';
      $tab[1]['name']          = $LANG['common'][16];
      $tab[1]['datatype']      = 'itemlink';
      $tab[1]['itemlink_type'] = $this->getType();
      $tab[1]['massiveaction'] = false; // implicit key==1

      $tab[2]['table']         = $this->getTable();
      $tab[2]['field']         = 'id';
      $tab[2]['name']          = $LANG['common'][2];
      $tab[2]['massiveaction'] = false; // implicit field is id
      
      $tab[3]['table']         = $this->getTable();
      $tab[3]['field']         = 'value';
      $tab[3]['name']          = $LANG['stock'][6];
      $tab[3]['datatype']     = 'decimal';
      $tab[3]['massiveaction'] = false;
      
      $tab[4]['table']         = $this->getTable();
      $tab[4]['field']         = 'quantidade';
      $tab[4]['name']          = $LANG['stock'][2];
      $tab[4]['datatype']      = 'number';
      $tab[4]['massiveaction'] = false;
      
      $tab[5]['table']         = $this->getTable();
      $tab[5]['field']         = 'descricao';
      $tab[5]['name']          = $LANG['stock'][10];
      $tab[5]['datatype']      = 'text';
      $tab[5]['massiveaction'] = false;
      
      $tab[6]['table']         = $this->getTable();
      $tab[6]['field']         = 'part_number';
      $tab[6]['name']          = $LANG['stock'][11];
      $tab[6]['datatype']      = 'text';
      $tab[6]['massiveaction'] = false;

      return $tab;
   }
   
   //******* SUPRISERVICE ******//
   static function getEstoqueExistente($items_id) {
      global $DB;

      $query = "select *
		from supridesk_pecas
		where quantidade > 0 
                AND id = {$items_id}";
                
      $result = $DB->query($query);

      return $DB->fetch_assoc($result);

   }
    
    //******* SUPRISERVICE ******//
   static function addTypeModel($id,$modelo,$tipo) {
      global $DB;

      $query = "INSERT INTO `supridesk_pecas_type_model` (`model_id`,`peca_id`,`type_id`) VALUES ({$modelo},{$id},{$tipo})";
                
      $DB->query($query);

   }

}

?>
