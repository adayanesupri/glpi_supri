<?php
/*
 * @version $Id: solutiontype.class.php 17152 2012-01-24 11:22:16Z moyo $
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
// Original Author of file: Remi Collet
// Purpose of file:
// ----------------------------------------------------------------------

if (!defined('GLPI_ROOT')) {
   die("Sorry. You can't access directly to this file");
}

/// SolutionType class
class SolutionType extends CommonDropdown {
	
	/*//SUPRISERVICE*/
   // From CommonDBTM
   public $dohistory = true;

	/*//SUPRISERVICE*/
   protected $forward_entity_to = array('SolutionType_SolutionTemplates', 'ITILCategory_SolutionTypes');
	//--

   function canCreate() {
      return Session::haveRight('entity_dropdown', 'w');
   }


   function canView() {
      return Session::haveRight('entity_dropdown', 'r');
   }

   static function getTypeName($nb=0) {
      global $LANG;

      if ($nb>1) {
         return $LANG['dropdown'][5];
      }
      return $LANG['job'][48];
   }


	/*//SUPRISERVICE*/
   function defineTabs($options=array()) {
      global $LANG;

      $ong = array();
      $this->addStandardTab(__CLASS__,$ong, $options);
      $this->addStandardTab('SolutionType_SolutionTemplates', $ong, $options);
      $this->addStandardTab('ITILCategory_SolutionTypes', $ong, $options);
      $this->addStandardTab('Log', $ong, $options);

      return $ong;
   }


   function getTabNameForItem(CommonGLPI $item, $withtemplate=0) {
      global $LANG;

      return $LANG['job'][48];
   }


   static function displayTabContentForItem(CommonGLPI $item, $tabnum=1, $withtemplate=0) {

      if ($item->getType()=='TicketTemplate') {
         self::showForTicketTemplate($item, $withtemplate);
      }
      return parent::displayTabContentForItem($item, $tabnum, $withtemplate);
   }

   static function dropdownForITILCategory(ITILCategory $category) {
      global $DB, $LANG;

      $query = "	SELECT *
						FROM glpi_solutiontypes st
						WHERE st.id NOT IN (	SELECT ics.solutiontypes_id
													FROM supridesk_itilcategories_solutiontypes ics
													WHERE ics.itilcategories_id = ".$category->fields["id"].")
						ORDER BY name";

      if ($result = $DB->query($query)) {
         if ($DB->numrows($result)) {
            echo "<select name='solutiontypes_id' size=1>";
            while ($data= $DB->fetch_assoc($result)) {
               echo "<option value='".$data["id"]."'>".$data["name"]."</option>";
            }
            echo "</select>";
            return true;
         }
      }
      return false;
   }

   static function dropdownByITILCategory(ITILCategory $category, $field_id = 'solutiontypes_id', $selected_id = 0) {
      global $DB, $LANG;

      $query = "	SELECT *
						FROM glpi_solutiontypes st
						WHERE st.id IN (	SELECT ics.solutiontypes_id
													FROM supridesk_itilcategories_solutiontypes ics
													WHERE ics.itilcategories_id = ".$category->fields["id"].")";

		echo "<select id='$field_id' name='solutiontypes_id' size=1>";
		echo "<option value='0'>" . Dropdown::EMPTY_VALUE . "</option>";
      if ($result = $DB->query($query)) {
         if ($DB->numrows($result)) {
            while ($data= $DB->fetch_assoc($result))
				{
					$selected = "";
					if ( $data["id"] == $selected_id )
						$selected = "selected";
               echo "<option value='".$data["id"]."' {$selected}>".$data["name"]."</option>";
            }
         }
      }
      echo "</select>";
   }
	//--
}

?>
