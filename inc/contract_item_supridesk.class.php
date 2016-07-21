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
class Contract_Item_Supridesk extends CommonDBChild {

   // From CommonDBChild
   public $itemtype = 'Contract';
   public $items_id = 'contracts_id';
	protected $table = "supridesk_contracts_items";
   public $dohistory = true;


   static function getTypeName($nb=0) {
      global $LANG;

      if ($nb>1) {
         return $LANG['tarifacao'][18];
      }
      return $LANG['tarifacao'][18];
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


   function prepareInputForAdd($input) {
      // Not attached to contract -> not added
      if (!isset($input['contracts_id']) || $input['contracts_id'] <= 0) {
         return false;
      }
      return $input;
   }


   function pre_deleteItem() {
      return true;
   }


   function defineTabs($options=array()) {
      global $LANG;

      $ong = array();
      $this->addStandardTab('Contract_Item_Printer_Supridesk', $ong, $options);
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

      $itemtype = $item->getType();
      $contracts_id = $item->getField('id');      
      
      if (!Session::haveRight('contract','r') || !$item->can($contracts_id, 'r')) {
         return false;
      }

      $canedit = $item->can($contracts_id, 'w');
      $showmassiveactions = false;
      if ($withtemplate!=2) {
         $showmassiveactions = count(Dropdown::getMassiveActions(__CLASS__));
      }

      // Show Add Form
      if ($canedit
          && (empty($withtemplate) || $withtemplate !=2)) {
         echo "\n<div class='firstbloc'><table class='tab_cadre_fixe'>";
         echo "<tr><td class='tab_bg_2 center b'>";
         echo "<a href='" . $CFG_GLPI["root_doc"] . "/front/contract_item_supridesk.form.php?contracts_id=$contracts_id&amp;itemtype=$itemtype'>";
         echo "<img src=\"" . $CFG_GLPI["root_doc"] . "/pics/add_dropdown.png\" alt=\"" . $LANG['tarifacao'][14] . "\" title=\"" . $LANG['tarifacao'][14] . "\"> ";
			echo $LANG['tarifacao'][10];
			echo "</a></td>\n";
         echo "</tr></table></div>\n";
      }

      Session::initNavigateListItems('Contract_Item_Supridesk', $item->getTypeName()." = ".$item->getName());

      $query = "SELECT `id`,
                    (SELECT count(*) FROM supridesk_contracts_items_printers 
                     WHERE supridesk_contracts_items_printers.contracts_items_id = supridesk_contracts_items.id and is_active = 1) as total
                FROM `supridesk_contracts_items`
                WHERE `contracts_id` = $contracts_id
                ORDER BY `nome`";

      if ($result = $DB->query($query)) {
         echo "<div class='spaced'>";

         if ($DB->numrows($result) != 0) {
            $colspan = 5;

            if ($showmassiveactions) {
               $colspan++;
               echo "\n<form id='contracts_items$rand' name='contracts_items$rand' method='post'
                     action='" . $CFG_GLPI["root_doc"] . "/front/contract_item_supridesk.form.php'>\n";
            }

            echo "<table class='tab_cadre_fixe'>\n";

            echo "<tr><th colspan='$colspan'>\n";
            if ($DB->numrows($result)==1) {
               echo $LANG['tarifacao'][13];
            } else {
               echo $LANG['tarifacao'][12];
            }
            echo "&nbsp;:&nbsp;".$DB->numrows($result)."</th></tr>\n";

            echo "<tr>";
            if ($showmassiveactions) {
               echo "<th>&nbsp;</th>\n";
            }
            echo "<th>#</th>\n";
            echo "<th width='400'>" . $LANG['common'][16] . "</th>\n";
            echo "<th>" . $LANG['tarifacao'][36] . "</th>\n";
            
            $i = 0;
            $contractItem = new Contract_Item_Supridesk();

            while ($devid = $DB->fetch_row($result)) {
               $contractItem->getFromDB(current($devid));

               Session::addToNavigateListItems('Contract_Item_Supridesk', $contractItem->fields["id"]);

               echo "<tr class='tab_bg_1'>\n";
               if ($showmassiveactions) {
                  echo "<td class='center' width='20'>";
                  echo "<input type='checkbox' name='del_contract_item[".$contractItem->fields["id"]."]' value='1'>";
                  echo "</td>\n";
               }
               echo "<td class='center'><span class='b'>";
               if ($canedit && $withtemplate != 2) {
                  echo "<a href=\"" . $CFG_GLPI["root_doc"] . "/front/contract_item_supridesk.form.php?id=" .
                        $contractItem->fields["id"] . "\">";
                        $_SESSION['contract_id'] = $contracts_id;
               }
               echo $contractItem->fields["id"];
               if ($canedit && $withtemplate != 2) {
                  echo "</a>";
               }
               Html::showToolTip($contractItem->fields['comment']);
               echo "</td>\n";

               echo "<td>" . $contractItem->fields["nome"] . "</td>\n";
               echo "<td>"; // . intval($devid[1]) . "</td>\n";

                $queryAgrupamentosQtd = "select a.*,
                                                (
                                                select count(*) 
                                                from supridesk_contracts_items_printers cip
                                                where cip.contracts_items_id = " . $contractItem->fields["id"] . "
                                                and cip.agrupamentos_id = a.id
                                                and cip.is_active = 1
                                                ) as qtd
                                        from supridesk_agrupamentos a
                                        where a.contracts_id = {$contracts_id}";

                $resultAgrupamentosQtd = $DB->query($queryAgrupamentosQtd);
                while ($agrupamentos_qtd = $DB->fetch_row($resultAgrupamentosQtd)) {
                        //print_r($devid2);
                        echo $agrupamentos_qtd[1] . ": <b>" . $agrupamentos_qtd[4] . "</b><br>";
                }
                echo "<b>Total: " . intval($devid[1]) . "</b>";
                
               echo "</td>\n";
					
               if ($canedit && $withtemplate != 2) {
                  echo "</a>";
               }
               echo "</tr>\n";
            }
            echo "</table>\n";

            if ($showmassiveactions) {
               Html::openArrowMassives("contractitems_printers$rand", true);
               Dropdown::showForMassiveAction('Contract_Item_Supridesk');
               $actions = array();
               Html::closeArrowMassives($actions);
               Html::closeForm();
            }

         } else {
            echo "<table class='tab_cadre_fixe'><tr><th>".$LANG['tarifacao'][33]."</th></tr>";
            echo "</table>";
         }
         echo "</div>";
      }
   }


   function showForm($ID, $options=array()) {
      global $CFG_GLPI, $LANG;

      if (!isset($options['several'])) {
         $options['several'] = false;
      }

      if (!Session::haveRight("contract", "r")) {
         return false;
      }

      if ($ID > 0) {
         $this->check($ID,'r');
      } else {
         $input = array('itemtype' => $options["itemtype"], 'contracts_id' => $options["contracts_id"]);
         // Create item
         $this->check(-1, 'w', $input);
      }

      $link = NOT_AVAILABLE;

		$item = new Contract();
      $type = $item->getTypeName();

		if ($item->getFromDB($this->fields["contracts_id"])) {
			$link = $item->getLink();
		} else {
			return false;
		}

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

      echo "<tr class='tab_bg_1'><td>$type&nbsp;:</td>\n<td>";

      if (!($ID>0)) {
         echo "<input type='hidden' name='contracts_id' value='".$this->fields["contracts_id"]."'>\n";
      }
      echo $link. "</td></tr>\n";

		//nome
		echo "<tr class='tab_bg_1'><td>" . $LANG['common'][16] . "&nbsp;:</td>\n";
		echo "<td>";
		Html::autocompletionTextField($this,"nome");
      echo "</td>";
      echo "<td>Franquia única Mono/Color:</td><td>";
      Dropdown::showYesNo('is_franquia_unica_mono_color',$this->fields['is_franquia_unica_mono_color']);
		echo "</td></tr>\n";

		//valor aluguel
		echo "<tr class='tab_bg_1'><td>" . $LANG['tarifacao'][19] . "&nbsp;:</td>\n";
		echo "<td>";
		Html::autocompletionTextField($this,"valor_aluguel", array( 'value' => Html::formatNumber($this->fields["valor_aluguel"], true, 4) ));
      echo "</td>";
      echo "<td>Não possui franquia :</td><td>";
      Dropdown::showYesNo('is_sem_franquia',$this->fields['is_sem_franquia']);
		echo "</td></tr>\n";
		
		//franquia mono
		echo "<tr class='tab_bg_1'><td>" . $LANG['tarifacao'][21] . "&nbsp;:</td>\n";
		echo "<td>";
		Html::autocompletionTextField($this,"franquia_mono", array( 'value' => Html::formatNumber($this->fields["franquia_mono"], true, 0) ));
		echo "</td>";
		//franquia color
		echo "<td>" . $LANG['tarifacao'][22] . "&nbsp;:</td>\n";
		echo "<td>";
		Html::autocompletionTextField($this,"franquia_color", array( 'value' => Html::formatNumber($this->fields["franquia_color"], true, 0) ));
		echo "</td>";
		echo "</tr>\n";

		//franquia a3 mono
		echo "<tr class='tab_bg_1'><td>" . $LANG['tarifacao'][44] . "&nbsp;:</td>\n";
		echo "<td>";
		Html::autocompletionTextField($this,"franquia_a3_mono", array( 'value' => Html::formatNumber($this->fields["franquia_a3_mono"], true, 0) ));
		echo "</td>";
		//franquia a3 color
		echo "<td>" . $LANG['tarifacao'][45] . "&nbsp;:</td>\n";
		echo "<td>";
		Html::autocompletionTextField($this,"franquia_a3_color", array( 'value' => Html::formatNumber($this->fields["franquia_a3_color"], true, 0) ));
		echo "</td>";
		echo "</tr>\n";
		
		//franquia plotagem
		echo "<tr class='tab_bg_1'><td>" . $LANG['tarifacao'][46] . "&nbsp;:</td>\n";
		echo "<td>";
		Html::autocompletionTextField($this,"franquia_plotagem", array( 'value' => Html::formatNumber($this->fields["franquia_plotagem"], true, 0) ));
		echo "</td>";
		echo "</td>";
		//franquia digitalizacao
		echo "<td>" . $LANG['tarifacao'][20] . "&nbsp;:</td>\n";
		echo "<td>";
		Html::autocompletionTextField($this,"franquia_digitalizacao", array( 'value' => Html::formatNumber($this->fields["franquia_digitalizacao"], true, 0) ));
		echo "</td>";
		echo "</tr>\n";
		
		
		
		
		
		echo "<tr><td colspan='4'>&nbsp;</td></tr>\n";
		
		//valor impressao mono franquia
		echo "<tr class='tab_bg_1'><td>" . $LANG['tarifacao'][23] . "&nbsp;:</td>\n";
		echo "<td>";
		Html::autocompletionTextField($this,"valor_impressao_mono_franquia", array( 'value' => Html::formatNumber($this->fields["valor_impressao_mono_franquia"], true, 4) ));
		echo "</td>";
		//valor impressao mono
		echo "<td>" . $LANG['tarifacao'][24] . "&nbsp;:</td>\n";
		echo "<td>";
		Html::autocompletionTextField($this,"valor_impressao_mono", array( 'value' => Html::formatNumber($this->fields["valor_impressao_mono"], true, 4) ));
		echo "</td>";
		echo "</tr>\n";

		//valor impressao color franquia
		echo "<tr class='tab_bg_1'><td>" . $LANG['tarifacao'][25] . "&nbsp;:</td>\n";
		echo "<td>";
		Html::autocompletionTextField($this,"valor_impressao_color_franquia", array( 'value' => Html::formatNumber($this->fields["valor_impressao_color_franquia"], true, 4) ));
		echo "</td>";
		//impressao color
		echo "<td>" . $LANG['tarifacao'][26] . "&nbsp;:</td>\n";
		echo "<td>";
		Html::autocompletionTextField($this,"valor_impressao_color", array( 'value' => Html::formatNumber($this->fields["valor_impressao_color"], true, 4) ));
		echo "</td>";
		echo "</tr>\n";

		//valor impressao A3 mono franquia
		echo "<tr class='tab_bg_1'><td>" . $LANG['tarifacao'][47] . "&nbsp;:</td>\n";
		echo "<td>";
		Html::autocompletionTextField($this,"valor_impressao_a3_mono_franquia", array( 'value' => Html::formatNumber($this->fields["valor_impressao_a3_mono_franquia"], true, 4) ));
		echo "</td>";
		//valor impressao A3 mono
		echo "<td>" . $LANG['tarifacao'][48] . "&nbsp;:</td>\n";
		echo "<td>";
		Html::autocompletionTextField($this,"valor_impressao_a3_mono", array( 'value' => Html::formatNumber($this->fields["valor_impressao_a3_mono"], true, 4) ));
		echo "</td>";
		echo "</tr>\n";

		//valor impressao A3 color franquia
		echo "<tr class='tab_bg_1'><td>" . $LANG['tarifacao'][49] . "&nbsp;:</td>\n";
		echo "<td>";
		Html::autocompletionTextField($this,"valor_impressao_a3_color_franquia", array( 'value' => Html::formatNumber($this->fields["valor_impressao_a3_color_franquia"], true, 4) ));
		echo "</td>";
		//valor impressao A3 color
		echo "<td>" . $LANG['tarifacao'][50] . "&nbsp;:</td>\n";
		echo "<td>";
		Html::autocompletionTextField($this,"valor_impressao_a3_color", array( 'value' => Html::formatNumber($this->fields["valor_impressao_a3_color"], true, 4) ));
		echo "</td>";
		echo "</tr>\n";

		//valor impressao plotagem franquia
		echo "<tr class='tab_bg_1'><td>" . $LANG['tarifacao'][51] . "&nbsp;:</td>\n";
		echo "<td>";
		Html::autocompletionTextField($this,"valor_impressao_plotagem_franquia", array( 'value' => Html::formatNumber($this->fields["valor_impressao_plotagem_franquia"], true, 4) ));
		echo "</td>";
		//valor impressao plotagem
		echo "<td>" . $LANG['tarifacao'][52] . "&nbsp;:</td>\n";
		echo "<td>";
		Html::autocompletionTextField($this,"valor_impressao_plotagem", array( 'value' => Html::formatNumber($this->fields["valor_impressao_plotagem"], true, 4) ));
		echo "</td>";
		echo "</tr>\n";

		//valor digitalizacao franquia
		echo "<tr class='tab_bg_1'><td>" . $LANG['tarifacao'][27] . "&nbsp;:</td>\n";
		echo "<td>";
		Html::autocompletionTextField($this,"valor_digitalizacao_franquia", array( 'value' => Html::formatNumber($this->fields["valor_digitalizacao_franquia"], true, 4) ));
		echo "</td>";
		//valor digitalizacao
		echo "<td>" . $LANG['tarifacao'][28] . "&nbsp;:</td>\n";
		echo "<td>";
		Html::autocompletionTextField($this,"valor_digitalizacao", array( 'value' => Html::formatNumber($this->fields["valor_digitalizacao"], true, 4) ));
		echo "</td>";
		echo "</tr>\n";

		echo "<tr><td colspan='4'>&nbsp;</td></tr>\n";

		//comments
		echo "<tr class='tab_bg_1'><td>" . $LANG['common'][25] . "&nbsp;:</td>\n";
		echo "<td colspan='3' class='middle'>";
      echo "<textarea cols='102' rows='3' name='comment' >".$this->fields["comment"]."</textarea>";
		echo "</td></tr>\n";

      $this->showFormButtons($options);       
      $this->addDivForTabs();
     
   }


   static function getSearchOptionsToAdd($itemtype) {
      global $LANG;

      $tab = array();

      $tab['network'] = $LANG['setup'][88];

      $joinparams = array('jointype' => 'itemtype_item');
      if ($itemtype=='Computer') {
         $joinparams['beforejoin'] = array('table'      => 'glpi_computers_devicenetworkcards',
                                           'joinparams' => array('jointype' => 'child',
                                                                 'nolink'   => true));
      }

      $tab[20]['table']         = 'glpi_networkports';
      $tab[20]['field']         = 'ip';
      $tab[20]['name']          = $LANG['networking'][14];
      $tab[20]['forcegroupby']  = true;
      $tab[20]['massiveaction'] = false;
      $tab[20]['joinparams']    = $joinparams;

      return $tab;
   }


   function getSearchOptions() {
      global $LANG;

      $tab = array();
      $tab['common'] = $LANG['common'][32];

      $tab[1]['table']         = $this->getTable();
      $tab[1]['field']         = 'nome';
      $tab[1]['name']          = $LANG['common'][16];
      $tab[1]['datatype']      = 'string';
      $tab[1]['massiveaction'] = false;

      $tab[2]['table']         = $this->getTable();
      $tab[2]['field']         = 'id';
      $tab[2]['name']          = $LANG['common'][2];
      $tab[2]['massiveaction'] = false;

      $tab[3]['table']    = $this->getTable();
      $tab[3]['field']    = 'is_sem_franquia';
      $tab[3]['name']     = 'Não possui franquia';
      $tab[3]['datatype'] = 'bool';

      $tab[4]['table']    = $this->getTable();
      $tab[4]['field']    = 'is_franquia_unica_mono_color';
      $tab[4]['name']     = 'Franquia única Mono/Color';
      $tab[4]['datatype'] = 'bool';

      $tab[5]['table']    = $this->getTable();
      $tab[5]['field']    = 'valor_aluguel';
      $tab[5]['name']     = 'Valor do aluguel';
      $tab[5]['datatype'] = 'decimal';

      $tab[6]['table']    = $this->getTable();
      $tab[6]['field']    = 'franquia_mono';
      $tab[6]['name']     = 'Franquia impressão mono';
      $tab[6]['datatype'] = 'number';

      $tab[7]['table']    = $this->getTable();
      $tab[7]['field']    = 'franquia_color';
      $tab[7]['name']     = 'Franquia impressão color';
      $tab[7]['datatype'] = 'number';

      $tab[8]['table']    = $this->getTable();
      $tab[8]['field']    = 'franquia_a3_mono';
      $tab[8]['name']     = 'Franquia A3 mono';
      $tab[8]['datatype'] = 'number';

      $tab[9]['table']    = $this->getTable();
      $tab[9]['field']    = 'franquia_a3_color';
      $tab[9]['name']     = 'Franquia A3 color';
      $tab[9]['datatype'] = 'number';

      $tab[10]['table']    = $this->getTable();
      $tab[10]['field']    = 'franquia_plotagem';
      $tab[10]['name']     = 'Franquia plotagem';
      $tab[10]['datatype'] = 'number';

      $tab[11]['table']    = $this->getTable();
      $tab[11]['field']    = 'franquia_digitalizacao';
      $tab[11]['name']     = 'Franquia digitalização ';
      $tab[11]['datatype'] = 'number';

      $tab[12]['table']    = $this->getTable();
      $tab[12]['field']    = 'valor_impressao_mono_franquia';
      $tab[12]['name']     = 'Valor impressão mono (franquia)';
      $tab[12]['datatype'] = 'decimal';

      $tab[13]['table']    = $this->getTable();
      $tab[13]['field']    = 'valor_impressao_mono';
      $tab[13]['name']     = 'Valor impressão mono';
      $tab[13]['datatype'] = 'decimal';

      $tab[14]['table']    = $this->getTable();
      $tab[14]['field']    = 'valor_impressao_color_franquia';
      $tab[14]['name']     = 'Valor impressão color (franquia)';
      $tab[14]['datatype'] = 'decimal';

      $tab[15]['table']    = $this->getTable();
      $tab[15]['field']    = 'valor_impressao_color';
      $tab[15]['name']     = 'Valor impressão color';
      $tab[15]['datatype'] = 'decimal';

      $tab[16]['table']    = $this->getTable();
      $tab[16]['field']    = 'comment';
      $tab[16]['name']     = $LANG['common'][25];
      $tab[16]['datatype'] = 'text';

      $tab[17]['table']    = $this->getTable();
      $tab[17]['field']    = 'valor_impressao_a3_mono_franquia';
      $tab[17]['name']     = 'Valor impressão A3 mono (franquia)';
      $tab[17]['datatype'] = 'decimal';

      $tab[18]['table']    = $this->getTable();
      $tab[18]['field']    = 'valor_impressao_a3_mono';
      $tab[18]['name']     = 'Valor impressão A3 mono';
      $tab[18]['datatype'] = 'decimal';

      $tab[19]['table']    = $this->getTable();
      $tab[19]['field']    = 'valor_impressao_a3_color_franquia';
      $tab[19]['name']     = 'Valor impressão A3 color (franquia)';
      $tab[19]['datatype'] = 'decimal';

      $tab[20]['table']    = $this->getTable();
      $tab[20]['field']    = 'valor_impressao_a3_color';
      $tab[20]['name']     = 'Valor impressão A3 color';
      $tab[20]['datatype'] = 'decimal';

      $tab[21]['table']    = $this->getTable();
      $tab[21]['field']    = 'valor_digitalizacao_franquia';
      $tab[21]['name']     = 'Valor digitalização (franquia)';
      $tab[21]['datatype'] = 'decimal';

      $tab[22]['table']    = $this->getTable();
      $tab[22]['field']    = 'valor_digitalizacao';
      $tab[22]['name']     = 'Valor digitalização';
      $tab[22]['datatype'] = 'decimal';

      $tab[23]['table']    = $this->getTable();
      $tab[23]['field']    = 'valor_impressao_plotagem_franquia';
      $tab[23]['name']     = 'Valor impressão plotagem (franquia)';
      $tab[23]['datatype'] = 'decimal';

      $tab[24]['table']    = $this->getTable();
      $tab[24]['field']    = 'valor_impressao_plotagem';
      $tab[24]['name']     = 'Valor impressão plotagem';
      $tab[24]['datatype'] = 'decimal';


      return $tab;
   }


   function getTabNameForItem(CommonGLPI $item, $withtemplate=0) {
      global $LANG, $CFG_GLPI;

      // Can exists on template
      if (Session::haveRight("contract","r")) {
         switch ($item->getType()) {
            case 'Contract' :
               if ($_SESSION['glpishow_count_on_tabs']) {
                  return self::createTabEntry($LANG['common'][96], self::countForContract($item));
               }
               return $LANG['common'][96];

            default :
               if ($_SESSION['glpishow_count_on_tabs']
                   && in_array($item->getType(), $CFG_GLPI["contract_types"])) {
                  return self::createTabEntry($LANG['Menu'][25], self::countForPrinter($item));
               }
               return $LANG['Menu'][25];

         }
      }
      return '';
   }

   static function countForPrinter(Printer $item) {

      $restrict = "`supridesk_contracts_items_printers`.`printers_id` = '".$item->getField('id')."' && is_active = 1";

      return countElementsInTable(array('supridesk_contracts_items_printers'), $restrict);
   }

   /**
    * @param $item   Contract object
   **/
   static function countForContract(Contract $item) {

      $restrict = "`supridesk_contracts_items`.`contracts_id` = '".$item->getField('id')."'";

      return countElementsInTable(array('supridesk_contracts_items'), $restrict);
   }

   static function displayTabContentForItem(CommonGLPI $item, $tabnum=1, $withtemplate=0) {
      global $CFG_GLPI;

      switch ($item->getType()) {
         case 'Contract' :
            self::showForItem($item);

         case 'Printer' :
            if (in_array($item->getType(), $CFG_GLPI["contract_types"])) {
               Contract::showAssociatedPrinter($item, $withtemplate);
            }
				break;
         default :
            if (in_array($item->getType(), $CFG_GLPI["contract_types"])) {
               Contract::showAssociated($item, $withtemplate);
            }
      }
      return true;
   }

}

?>
