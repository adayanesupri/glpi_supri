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
class PecasTypeModel extends CommonDBTM {
    
    /*//SUPRISERVICE*/
   public $supridesk_custom_table = "supridesk_pecas_type_model";
   
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
         return 'Modelos Compatíveis';
      }
      return 'Modelo Compatível';
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
      $this->addStandardTab('PecasTypeModel', $ong, $options);

      return $ong;
   }



    static function displayTabContentForItem(CommonGLPI $item, $tabnum=1, $withtemplate=0) {
      global $CFG_GLPI;
        
        /*switch($item->getType()){
                
            case 'Stock':
              //  self::show('PecasTypeModel');
               // return true;
        }
       var_export($item);*/
       self::showModels($item, $withtemplate);
       
       return true;
   }
    
    
   function getTabNameForItem(CommonGLPI $item, $withtemplate=0) {
      global $LANG, $CFG_GLPI;

      // Can exists on template
      if (Session::haveRight("stock","r")) {
         switch ($item->getType()) {
            default :
               if ($_SESSION['glpishow_count_on_tabs']) {
                  return self::createTabEntry('Modelos Compatíveis', countElementsInTable("supridesk_pecas_type_model", "peca_id = '".$item->getID()."'"));
               }
               return 'Modelo Compatível';

         }
      }
       
       var_export($item);
      return 'Modelo Compatível';
   }
    
    function showModels(CommonDBTM $item, $withtemplate=''){
        global $DB, $CFG_GLPI, $LANG;

        $rand = mt_rand();
        // var_export($item);
        //$itemtype = $item->getType();
        $peca_id = $item->getField('id');
        
        $qryitem = "SELECT `itemtype` FROM supridesk_pecas WHERE `id` = {$peca_id}";
        $resultitem = $DB->query($qryitem);
        $ftch = $DB->fetch_assoc($resultitem);
        
        $itemtype = $ftch['itemtype'];

        if (!Session::haveRight('stock','r') || !$item->can($peca_id, 'r')) {
            return false;
        }

        $canedit = $item->can($peca_id, 'w');
        //var_export(array($canedit));
        $showmassiveactions = false;
        if ($withtemplate!=2) {
            $showmassiveactions = count(Dropdown::getMassiveActions(__CLASS__));
        }
        
        switch($itemtype){
            case 'Printer':
              $innerjointype = "inner JOIN `glpi_printertypes` gt ON (`gt`.`id` = `supridesk_pecas_type_model`.`type_id`)";
              $innerjoinmodel = "inner JOIN `glpi_printermodels` gm ON (`gm`.`id` = `supridesk_pecas_type_model`.`model_id`)";
              break;
            case 'Computer':
              $innerjointype = "inner JOIN `glpi_computertypes` gt ON (`gt`.`id` = `supridesk_pecas_type_model`.`type_id`)";
              $innerjoinmodel = "inner JOIN `glpi_computermodels` gm ON (`gm`.`id` = `supridesk_pecas_type_model`.`model_id`)";
              break;
            case 'Monitor':
              $innerjointype = "inner JOIN `glpi_monitortypes` gt ON (`gt`.`id` = `supridesk_pecas_type_model`.`type_id`)";
              $innerjoinmodel = "inner JOIN `glpi_monitormodels` gm ON (`gm`.`id` = `supridesk_pecas_type_model`.`model_id`)";
              break; 
        }


      Session::initNavigateListItems('PecasTypeModel', $item->getTypeName()." = ".$item->getName());

      $query = "SELECT 
                    `gt`.`name` AS tipo,
                    `gm`.`name` AS modelo,
                    `supridesk_pecas_type_model`. id AS id
                FROM `supridesk_pecas_type_model`
                {$innerjointype}
                {$innerjoinmodel}
                WHERE `peca_id` = $peca_id
                ORDER BY `supridesk_pecas_type_model`.id DESC";
 
      if ($result = $DB->query($query)) {
         echo "<div class='spaced'>";

         // die();
         if ($DB->numrows($result) != 0) {
            $colspan = 5;

            if ($showmassiveactions) {
               $colspan++;
               echo "\n<form id='pecastypemodel$rand' name='pecastypemodel$rand' method='post'
                     action='" . $CFG_GLPI["root_doc"] . "/front/pecastypemodel.form.php'>\n";
            }

            echo "<table class='tab_cadre_fixe'>\n";

            echo "<tr><th colspan='$colspan'>\n";
            echo "Registros encontrados: ".$DB->numrows($result)."</th></tr>\n";

            echo "<tr>";
            if ($showmassiveactions) {
               echo "<th>&nbsp;</th>\n";
            }
            echo "<th>Modelo</th>\n";
            echo "<th>Tipo</th>\n";             
             
            
            $i = 0;
            //$transportLogs = new TransportLogs();

            while ($devid = $DB->fetch_assoc($result)) {
               //$transportLogs->getFromDB(current($devid));                

               Session::addToNavigateListItems('TransportLogs', $transportLogs->fields["id"]);
               
               echo "<tr class='tab_bg_1'>\n";
               if ($showmassiveactions) {
                  echo "<td class='center' width='20'>";
                  echo "<input type='checkbox' name='item[".$devid["id"]."]' value='".$devid["id"]."'>";
                  echo "</td>\n";
               }
               echo "<td class='center'><span class='b'>";               
               echo $devid["modelo"];               
               echo "</td>\n";

               echo "<td class='center'>".$devid["tipo"]."</td>\n";               
					
               if ($canedit && $withtemplate != 2) {
                  echo "</a>";
               }
               echo "</tr>\n";
            }
            echo "</table>\n";

            if ($showmassiveactions) {
               Html::openArrowMassives("pecastypemodel$rand", true);
               Dropdown::showForMassiveAction('PecasTypeModel');
               $actions = array();
               Html::closeArrowMassives($actions);
               Html::closeForm();
            }

         } else {
            echo "<table class='tab_cadre_fixe'><tr><th>Nenhum Modelo Associado</th></tr>";
            echo "</table>";
         }
         echo "</div>";
      }
        
    }
    
    /*static function countForStock(PecasTypeModel $item) {

      $restrict = "`supridesk_pecas_type_model`.`peca_id` = '".$item->getField('id')."'";

      return countElementsInTable(array('supridesk_pecas_type_model'), $restrict);
   }*/

   
   //*****SUPRISERVICE******//
   function updateEquipamentos($quantidade, $id) {
      global $DB;
      
      $this->id = $id;

		/*//SUPRISERVICE*/
      return $this->update(array('id'         => $this->id,
                                 'quantidade' => $quantidade));
   }
    
    function delete_modellogs($id){
        global $DB;
        
      $query = "DELETE FROM `supridesk_pecas_type_model` WHERE `id` =  {$id}";
                
      $result = $DB->query($query);
    }
   
   

   function getSearchOptions() {
      global $LANG,$CFG_GLPI;

      $tab = array();
      $tab['common'] = $LANG['common'][32];

      $tab[1]['table']         = $this->getTable();
      $tab[1]['field']         = 'id';
      $tab[1]['datatype']      = 'number';      
      $tab[1]['massiveaction'] = false; // implicit key==1

      $tab[2]['table']         = $this->getTable();
      $tab[2]['field']         = 'model_id';
      $tab[2]['datatype']     = 'number';
      $tab[2]['massiveaction'] = false; // implicit field is id
      
      $tab[3]['table']         = $this->getTable();
      $tab[3]['field']         = 'peca_id';
      $tab[3]['datatype']     = 'number';
      $tab[3]['massiveaction'] = false;
      
      $tab[4]['table']         = $this->getTable();
      $tab[4]['field']         = 'type_id';
      $tab[4]['datatype']      = 'number';
      $tab[4]['massiveaction'] = false;

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

}

?>
