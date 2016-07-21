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
 *  Transport class
**/
class Transport extends CommonDBTM {
    
    /*//SUPRISERVICE*/
   public $supridesk_custom_table = "supridesk_veiculos";
   
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
         return $LANG['transport'][0];
      }
      return $LANG['transport'][1];
   }


   function canCreate() {
      return Session::haveRight('transport', 'w');
   }


   function canView() {
      return Session::haveRight('transport', 'r');
   }
    
     function isEntityAssign() {
      return false;
   }
    
    function maybeDeleted() {
      // deleted information duplicate from computers
      return false;
   }
    
    function maybeTemplate() {
      // deleted information duplicate from computers
      return false;
   }


   function defineTabs($options=array()) {
      global $LANG, $CFG_GLPI;

      $ong = array();
       
     $this->addStandardTab('Ticket', $ong, $options);
     $this->addStandardTab('Log', $ong, $options);
     $this->addStandardTab('TransportLogs', $ong, $options);
        

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

      echo "<tr class='tab_bg_1'>";
      echo "<td>Modelo".($template?"*":"")."&nbsp;:</td>";
      echo "<td>";
      $objectName = autoName($this->fields["name"], "name", ($template === "newcomp"),
                             $this->getType(), $this->fields["entities_id"]);
      Html::autocompletionTextField($this, 'name', array('value' => $objectName));
      echo "</td>";
      
      echo "<td>Placa".($template?"*":"")."&nbsp;:</td>";
      echo "<td>";
      Html::autocompletionTextField($this, "placa");
      echo "</td>";
      
      echo "</tr>\n";
       
      echo "<tr class='tab_bg_1'>";
      echo "<td>Ano&nbsp;:</td>";
      echo "<td>";
      Html::autocompletionTextField($this, "ano",array('size' => 15));
      echo "</td>";
      
      echo "<td>Data Licenciamento".($template?"*":"")."&nbsp;:</td>";
      echo "<td>";
      Html::autocompletionTextField($this, "data_licenciamento",array('size' => 15));
      echo "</td>";
      
      echo "</tr>\n";
      
      echo "<tr class='tab_bg_1'>";
      echo "<td>Analista responsável pelo veiculo".($template?"*":"")."&nbsp;:</td>";
      echo "<td>";
       User::dropdown(array('name'   => 'users_id_tech',
                           'value'  => $this->fields["users_id_tech"],
                           'right'  => 'own_ticket',
                           'entity' => $this->fields["entities_id"]));
      // Html::autocompletionTextField($this, "analista_responsavel");
      echo "</td>";
      
      echo "<td>Comentários&nbsp;:</td>";
      echo "<td><textarea cols='40' rows='4' name='comentarios'>".$this->fields["comentarios"]."</textarea>";     
      echo "</td>";
      echo "</tr>\n";
      
      
      echo "<tr class='tab_bg_1'>";
      echo "<td colspan='2' class='center' height='30'>".$LANG['common'][26].":&nbsp;".$date;
      echo "</td>";
      echo "</tr>\n";
	  
	  if($_SESSION['glpiactiveprofile']['name'] == 'super-admin'){
           
           $this->showFormButtons($options);
       }
       echo "</tbody>";
       echo "</table>";

      //$this->showFormButtons($options);
      $this->addDivForTabs();      
      
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
      $tab[1]['name']          = $LANG['transport'][2];
      $tab[1]['datatype']      = 'itemlink';
      $tab[1]['itemlink_type'] = $this->getType();
      $tab[1]['massiveaction'] = false; // implicit key==1

      $tab[2]['table']         = $this->getTable();
      $tab[2]['field']         = 'id';
      $tab[2]['name']          = $LANG['common'][2];
      $tab[2]['massiveaction'] = false; // implicit field is id
      
      $tab[3]['table']         = $this->getTable();
      $tab[3]['field']         = 'placa';
      $tab[3]['name']          = $LANG['transport'][3];
      $tab[3]['datatype']     = 'text';
      $tab[3]['massiveaction'] = false;
      
      $tab[4]['table']         = $this->getTable();
      $tab[4]['field']         = 'users_id_tech';
      $tab[4]['name']          = $LANG['transport'][4];
      $tab[4]['datatype']      = 'text';
      $tab[4]['massiveaction'] = false;
      
      $tab[5]['table']         = $this->getTable();
      $tab[5]['field']         = 'comentarios';
      $tab[5]['name']          = $LANG['common'][25];
      $tab[5]['datatype']      = 'text';
      $tab[5]['massiveaction'] = false;      
       
      $tab[6]['table']         = $this->getTable();
      $tab[6]['field']         = 'ano';
      $tab[6]['name']          = $LANG['common'][25];
      $tab[6]['datatype']      = 'text';
      $tab[6]['massiveaction'] = false;
       
      $tab[7]['table']         = $this->getTable();
      $tab[7]['field']         = 'data_licenciamento';
      $tab[7]['name']          = $LANG['common'][25];
      $tab[7]['datatype']      = 'text';
      $tab[7]['massiveaction'] = false;
      

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
   
  
}

?>
