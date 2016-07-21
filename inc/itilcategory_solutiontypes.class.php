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
class ITILCategory_SolutionTypes extends CommonDBRelation {

   // From CommonDBChild
   public $itemtype_1 = 'ITILCategory';
   public $items_id_1 = 'itilcategories_id';

   public $itemtype_2 = 'SolutionType';
   public $items_id_2 = 'solutiontypes_id';

	protected $table = "supridesk_itilcategories_solutiontypes";
   public $dohistory = true;


   static function getTypeName($nb=0) {
      global $LANG;

      if ($nb>1) {
         return $LANG['job'][48];
      }
      return $LANG['job'][48];
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
      if (!isset($input['itilcategories_id']) || $input['itilcategories_id'] <= 0) {
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
      $itilcategories_id = $item->getField('id');

      if (!Session::haveRight("show_all_ticket","1") || !$item->can($itilcategories_id, 'r')) {
         return false;
      }

      $canedit = $item->can($itilcategories_id, 'w');
      $showmassiveactions = false;
      if ($withtemplate!=2) {
         $showmassiveactions = count(Dropdown::getMassiveActions(__CLASS__));
      }

      // Show Add Form
      if ($canedit && (empty($withtemplate) || $withtemplate !=2)) {
			echo "\n<form id='itilcategory_solutiontypes$rand' name='itilcategory_solutiontypes$rand' method='post'
					action='" . $CFG_GLPI["root_doc"] . "/front/itilcategory_solutiontypes.form.php'>\n";
			echo "<input type='hidden' name='itilcategories_id' value='$itilcategories_id'>";

         echo "\n<div class='firstbloc'><table class='tab_cadre_fixe'>";
         echo "<tr><td class='tab_bg_2 center b'>";

			echo $LANG['job'][48] .": ";
			//if (SolutionType::dropdownForITILCategory($item)) {
			$opt["name"] = "solutiontypes_id";
			Dropdown::show('SolutionType', $opt);
			echo "&nbsp;<input type='submit' name='vincular' value=\"".$LANG['custom_chamado'][4]."\" class='submit'>";
			//}
			echo "</td>\n";
         echo "</tr></table></div>\n";
      }

      Session::initNavigateListItems('ITILCategory_SolutionTypes', $item->getTypeName()." = ".$item->getName());

      $query = "	SELECT *
						FROM supridesk_itilcategories_solutiontypes ics, glpi_solutiontypes st
						WHERE ics.solutiontypes_id = st.id
						  AND ics.itilcategories_id = $itilcategories_id
						ORDER BY st.name";

      if ($result = $DB->query($query)) {
         echo "<div class='spaced'>";

         if ($DB->numrows($result) != 0) {
            $colspan = 5;

            if ($showmassiveactions) {
               $colspan++;
            }

            echo "<table class='tab_cadre_fixe'>\n";

            echo "<tr><th colspan='$colspan'>\n".$LANG['custom_chamado'][6]."&nbsp;:&nbsp;".$DB->numrows($result)."</th></tr>\n";

            echo "<tr>";
            if ($showmassiveactions) {
               echo "<th>&nbsp;</th>\n";
            }
            echo "<th width='60'>#</th>\n";
            echo "<th>" . $LANG['common'][16] . "</th>\n";

            $i = 0;
            $icst = new ITILCategory_SolutionTypes();

            while ($devid = $DB->fetch_row($result)) {
               $icst->getFromDB(current($devid));

					$solutiontype = new SolutionType();
               $solutiontype->getFromDB($icst->fields["solutiontypes_id"]);

               Session::addToNavigateListItems('ITILCategory_SolutionTypes', $icst->fields["id"]);

               echo "<tr class='tab_bg_1'>\n";
               if ($showmassiveactions) {
                  echo "<td class='center' width='20'>";
                  echo "<input type='checkbox' name='del_ICTS[".$icst->fields["id"]."]' value='1'>";
                  echo "</td>\n";
               }
               echo "<td class='center'><span class='b'>";
               echo $solutiontype->fields["id"];
               echo "</td>\n";

					echo "<td><a href=\"" . $CFG_GLPI["root_doc"] . "/front/solutiontype.form.php?id=" .
							$solutiontype->fields["id"] . "\">";
               echo $solutiontype->fields["name"] . "</a></td>\n";					

               if ($canedit && $withtemplate != 2) {
                  echo "</a>";
               }
               echo "</tr>\n";
            }
            echo "</table>\n";

            if ($showmassiveactions) {
               Html::openArrowMassives("itilcategory_solutiontypes$rand", true);
               Dropdown::showForMassiveAction('ITILCategory_SolutionTypes');
               $actions = array();
               Html::closeArrowMassives($actions);
               Html::closeForm();
            }

         } else {
            echo "<table class='tab_cadre_fixe'><tr><th>".$LANG['custom_chamado'][8]."</th></tr>";
            echo "</table>";
         }
         echo "</div>";
      }
   }

   function getTabNameForItem(CommonGLPI $item, $withtemplate=0) {
      global $LANG, $CFG_GLPI;

      // Can exists on template
      if (Session::haveRight("contract","r")) {
         switch ($item->getType()) {
            case 'ITILCategory' :
               if ($_SESSION['glpishow_count_on_tabs']) {
                  return self::createTabEntry($LANG['custom_chamado'][3], self::countForITILCategory($item));
               }
               return $LANG['custom_chamado'][3];
            case 'SolutionType' :
               if ($_SESSION['glpishow_count_on_tabs']) {
                  return self::createTabEntry($LANG['custom_chamado'][12], self::countForSolutionType($item));
               }
               return $LANG['custom_chamado'][12];
         }
      }
      return '';
   }

   /**
    * @param $item   SolutionType object
   **/
   static function countForSolutionType(SolutionType $item) {

      $restrict = "`supridesk_itilcategories_solutiontypes`.`solutiontypes_id` = '".$item->getField('id')."'";

      return countElementsInTable(array('supridesk_itilcategories_solutiontypes'), $restrict);
   }

   /**
    * @param $item   ITILCategory object
   **/
   static function countForITILCategory(ITILCategory $item) {

      $restrict = "`supridesk_itilcategories_solutiontypes`.`itilcategories_id` = '".$item->getField('id')."'";

      return countElementsInTable(array('supridesk_itilcategories_solutiontypes'), $restrict);
   }

   static function displayTabContentForItem(CommonGLPI $item, $tabnum=1, $withtemplate=0) {
      global $CFG_GLPI;

      switch ($item->getType()) {
         case 'ITILCategory' :
            self::showForItem($item);
				break;
         case 'SolutionType' :
            self::showCategoriesUsing($item);
				break;
      }
      return true;
   }

   static function showCategoriesUsing(CommonDBTM $item, $withtemplate='') {
      global $DB, $CFG_GLPI, $LANG;

      $rand = mt_rand();

      $solutiontypes_id = $item->getField('id');

      if (!Session::haveRight("show_all_ticket","1") || !$item->can($solutiontypes_id, 'r')) {
         return false;
      }

      $query = "	SELECT *
						FROM supridesk_itilcategories_solutiontypes ics, glpi_itilcategories ic
						WHERE ics.itilcategories_id = ic.id
						  AND ics.solutiontypes_id = $solutiontypes_id
						ORDER BY ic.name";

      if ($result = $DB->query($query)) {
         echo "<div class='spaced'>";

         if ($DB->numrows($result) != 0) {
            $colspan = 5;

            echo "<table class='tab_cadre_fixe'>\n";

            echo "<tr><th colspan='$colspan'>\n".$LANG['custom_chamado'][13]."&nbsp;:&nbsp;".$DB->numrows($result)."</th></tr>\n";

            echo "<tr>";

				echo "<th width='60'>#</th>\n";
            echo "<th>" . $LANG['common'][16] . "</th>\n";

            $i = 0;
            $icst = new ITILCategory_SolutionTypes();

            while ($devid = $DB->fetch_row($result)) {
               $icst->getFromDB(current($devid));

					$itilcategory = new ITILCategory();
               $itilcategory->getFromDB($icst->fields["itilcategories_id"]);

               echo "<tr class='tab_bg_1'>\n";

					echo "<td class='center'><span class='b'>";
               echo $itilcategory->fields["id"];
               echo "</td>\n";

					echo "<td><a href=\"" . $CFG_GLPI["root_doc"] . "/front/itilcategory.form.php?id=" .
							$itilcategory->fields["id"] . "\">";
               echo $itilcategory->fields["completename"] . "</a></td>\n";

               echo "</tr>\n";
            }
            echo "</table>\n";
         } else {
            echo "<table class='tab_cadre_fixe'><tr><th>".$LANG['custom_chamado'][14]."</th></tr>";
            echo "</table>";
         }
         echo "</div>";
      }
   }

}

?>
