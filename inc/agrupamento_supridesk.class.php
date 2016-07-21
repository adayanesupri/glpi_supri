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
class Agrupamento_Supridesk extends CommonDBChild {

   // From CommonDBChild
   public $itemtype = 'Contract';
   public $items_id = 'contracts_id';
	protected $table = "supridesk_agrupamentos";
   public $dohistory = true;

   static function getTypeName($nb=0) {
      global $LANG;

      if ($nb>1) {
         return $LANG['agrupamento'][2];
      }
      return $LANG['agrupamento'][1];
   }

   static function getDropdown( $name, $upper_id, $value=0 ) {
      global $DB, $LANG;

      $elements = array("0" => $LANG['agrupamento'][14]);

      if ($lib) {
         $elements["-1"] = $lib;
      }

      $queryStateList = "SELECT `id`, `name`
                         FROM `supridesk_agrupamentos`
								 WHERE `contracts_id` = {$upper_id}
                         ORDER BY `name`";
      $result = $DB->query($queryStateList);

      if ($DB->numrows($result) > 0) {
         while (($data = $DB->fetch_assoc($result))) {
            $elements[$data["id"]] = $data["name"];
         }
      }
      Dropdown::showFromArray($name, $elements, array('value' => $value));
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
      //$this->addStandardTab('Agrupamento_Printer_Supridesk', $ong, $options);
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
		//TODO: não mostra massive actions nos grupos, quando homologar o sistema remover.
		$showmassiveactions = false;

      // Show Add Form
      if ($canedit
          && (empty($withtemplate) || $withtemplate !=2)) {
         echo "\n<div class='firstbloc'><table class='tab_cadre_fixe'>";
         echo "<tr><td class='tab_bg_2 center b'>";
         echo "<a href='" . $CFG_GLPI["root_doc"] . "/front/agrupamento_supridesk.form.php?contracts_id=$contracts_id&amp;itemtype=$itemtype'>";
         echo "<img src=\"" . $CFG_GLPI["root_doc"] . "/pics/add_dropdown.png\" alt=\"" . $LANG['agrupamento'][4] . "\" title=\"" . $LANG['agrupamento'][4] . "\"> ";
			echo $LANG['agrupamento'][3];
			echo "</a></td>\n";
         echo "</tr></table></div>\n";
      }

      Session::initNavigateListItems('Agrupamento_Supridesk', $item->getTypeName()." = ".$item->getName());

      $query = "SELECT `id`
                FROM `supridesk_agrupamentos`
                WHERE `contracts_id` = $contracts_id
                ORDER BY `name`";

      if ($result = $DB->query($query)) {
         echo "<div class='spaced'>";

         if ($DB->numrows($result) != 0) {
            $colspan = 5;

            if ($showmassiveactions) {
               $colspan++;
               echo "\n<form id='agrupamentos$rand' name='agrupamentos$rand' method='post'
                     action='" . $CFG_GLPI["root_doc"] . "/front/agrupamento_supridesk.form.php'>\n";
            }

            echo "<table class='tab_cadre_fixe'>\n";

            echo "<tr><th colspan='$colspan'>\n";
            if ($DB->numrows($result)==1) {
               echo $LANG['agrupamento'][6];
            } else {
               echo $LANG['agrupamento'][5];
            }
            echo "&nbsp;:&nbsp;".$DB->numrows($result)."</th></tr>\n";

            echo "<tr>";
            if ($showmassiveactions) {
               echo "<th>&nbsp;</th>\n";
            }
            echo "<th>#</th>\n";
            echo "<th>" . $LANG['common'][16] . "</th>\n";
            echo "<th>" . $LANG['common'][25] . "</th>\n";

            $i = 0;
            $agrupamento = new Agrupamento_Supridesk();

            while ($devid = $DB->fetch_row($result)) {
               $agrupamento->getFromDB(current($devid));

               Session::addToNavigateListItems('Agrupamento_Supridesk', $agrupamento->fields["id"]);

               echo "<tr class='tab_bg_1'>\n";
               if ($showmassiveactions) {
                  echo "<td class='center' width='20'>";
                  echo "<input type='checkbox' name='del_port[".$agrupamento->fields["id"]."]' value='1'>";
                  echo "</td>\n";
               }
               echo "<td class='center' width='50'><span class='b'>";
               if ($canedit && $withtemplate != 2) {
                  echo "<a href=\"" . $CFG_GLPI["root_doc"] . "/front/agrupamento_supridesk.form.php?id=" .
                        $agrupamento->fields["id"] . "\">";
               }
               echo $agrupamento->fields["id"];
               if ($canedit && $withtemplate != 2) {
                  echo "</a>";
               }
               Html::showToolTip($agrupamento->fields['comment']);
               echo "</td>\n";

               echo "<td>" . $agrupamento->fields["name"] . "</td>\n";
               echo "<td>" . $agrupamento->fields["comment"] . "</td>\n";
					
               if ($canedit && $withtemplate != 2) {
                  echo "</a>";
               }
               echo "</tr>\n";
            }
            echo "</table>\n";

            if ($showmassiveactions) {
               Html::openArrowMassives("agrupamentos_printers$rand", true);
               Dropdown::showForMassiveAction('Agrupamento_Supridesk');
               $actions = array();
               Html::closeArrowMassives($actions);
               Html::closeForm();
            }

         } else {
            echo "<table class='tab_cadre_fixe'><tr><th>".$LANG['agrupamento'][9]."</th></tr>";
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

      $options['colspan'] = 1;
      $this->showFormHeader($options);

      echo "<tr class='tab_bg_1'><td>$type&nbsp;:</td>\n<td>";

      if (!($ID>0)) {
         echo "<input type='hidden' name='contracts_id' value='".$this->fields["contracts_id"]."'>\n";
      }
      echo $link. "</td></tr>\n";

		//nome
		echo "<tr class='tab_bg_1'><td>" . $LANG['common'][16] . "&nbsp;:</td>\n";
		echo "<td>";
		Html::autocompletionTextField($this,"name");
		echo "</td></tr>\n";

		//comments
		echo "<tr class='tab_bg_1'><td>" . $LANG['common'][25] . "&nbsp;:</td>\n";
		echo "<td class='middle'>";
      echo "<textarea cols='80' rows='3' name='comment' >".$this->fields["comment"]."</textarea>";
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

		return $tab;
   }


   function getSearchOptions() {
      global $LANG;

      $tab = array();
      $tab['common'] = $LANG['common'][32];

      $tab[1]['table']         = $this->getTable();
      $tab[1]['field']         = 'name';
      $tab[1]['name']          = $LANG['common'][16];
      $tab[1]['type']          = 'text';
      $tab[1]['massiveaction'] = false;

      $tab[2]['table']         = $this->getTable();
      $tab[2]['field']         = 'id';
      $tab[2]['name']          = $LANG['common'][2];
      $tab[2]['massiveaction'] = false;

      $tab[16]['table']    = $this->getTable();
      $tab[16]['field']    = 'comment';
      $tab[16]['name']     = $LANG['common'][25];
      $tab[16]['datatype'] = 'text';

      return $tab;
   }


   function getTabNameForItem(CommonGLPI $item, $withtemplate=0) {
      global $LANG, $CFG_GLPI;

      // Can exists on template
      if (Session::haveRight("contract","r")) {
         switch ($item->getType()) {
            case 'Contract' :
               if ($_SESSION['glpishow_count_on_tabs']) {
                  return self::createTabEntry($LANG['agrupamento'][2], self::countForContract($item));
               }
               return $LANG['agrupamento'][2];
         }
      }
      return '';
   }

   /**
    * @param $item   Contract object
   **/
   static function countForContract(Contract $item) {

      $restrict = "`supridesk_agrupamentos`.`contracts_id` = '".$item->getField('id')."'";

      return countElementsInTable(array('supridesk_agrupamentos'), $restrict);
   }

   static function displayTabContentForItem(CommonGLPI $item, $tabnum=1, $withtemplate=0) {
      global $CFG_GLPI;

      switch ($item->getType()) {
         case 'Contract' :
            self::showForItem($item);

         default :
            if (in_array($item->getType(), $CFG_GLPI["contract_types"])) {
               Contract::showAssociated($item, $withtemplate);
            }
      }
      return true;
   }

}

?>
