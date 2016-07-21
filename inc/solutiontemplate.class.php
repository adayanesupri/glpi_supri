<?php
/*
 * @version $Id: solutiontemplate.class.php 17152 2012-01-24 11:22:16Z moyo $
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

/// Class SolutionTemplate
class SolutionTemplate extends CommonDropdown {

   // From CommonDBTM
   public $dohistory = true;

	/*//SUPRISERVICE*/
   protected $forward_entity_to = array('SolutionType_SolutionTemplates');


   static function getTypeName($nb=0) {
      global $LANG;

      if ($nb>1) {
         return $LANG['dropdown'][7];
      }
      return $LANG['jobresolution'][6];
   }

	/*//SUPRISERVICE*/
   function defineTabs($options=array()) {
      global $LANG;

      $ong = array();
      $this->addStandardTab(__CLASS__,$ong, $options);
      $this->addStandardTab('SolutionType_SolutionTemplates', $ong, $options);
      $this->addStandardTab('Log', $ong, $options);

      return $ong;
   }

   function getTabNameForItem(CommonGLPI $item, $withtemplate=0) {
      global $LANG;

      return $LANG['jobresolution'][6];
   }


   static function displayTabContentForItem(CommonGLPI $item, $tabnum=1, $withtemplate=0) {

      if ($item->getType()=='TicketTemplate') {
         self::showForTicketTemplate($item, $withtemplate);
      }
      return parent::displayTabContentForItem($item, $tabnum, $withtemplate);
   }
	//--

   function canCreate() {
      return Session::haveRight('entity_dropdown', 'w');
   }


   function canView() {
      return Session::haveRight('entity_dropdown', 'r');
   }


   function getAdditionalFields() {
      global $LANG;

		/*//SUPRISERVICE*/
      return array(
						/*array('name'  => 'solutiontypes_id',
                         'label' => $LANG['job'][48],
                         'type'  => 'dropdownValue',
                         'list'  => true),*/
                   array('name'  => 'content',
                         'label' => $LANG['knowbase'][15],
                         'type'  => 'tinymce'));
		//--
   }

   /**
    * @since version 0.83
   **/
   function getSearchOptions() {
      global $LANG;

      $tab = parent::getSearchOptions();

      $tab[4]['name']   = $LANG['knowbase'][15];
      $tab[4]['field']  = 'content';
      $tab[4]['table']  = $this->getTable();

      $tab[3]['name']   = $LANG['job'][48];
      $tab[3]['field']  = 'name';
      $tab[3]['table']  = getTableForItemType('SolutionType');

      return $tab;
   }


   function displaySpecificTypeField($ID, $field = array()) {

      switch ($field['type']) {
         case 'tinymce' :
            // Display empty field
            echo "&nbsp;</td></tr>";
            // And a new line to have a complete display
            echo "<tr class='center'><td colspan='5'>";
            $rand = mt_rand();
            Html::initEditorSystem($field['name'].$rand);
            echo "<textarea id='".$field['name']."$rand' name='".$field['name']."' rows='3'>".
                   $this->fields[$field['name']]."</textarea>";
            break;
      }
   }

	/*//SUPRISERVICE*/
   static function dropdownForSolutionType(SolutionType $solutiontype) {
      global $DB, $LANG;

      $query = "	SELECT *
						FROM glpi_solutiontemplates st
						WHERE st.id NOT IN (	SELECT stst.solutiontemplates_id
													FROM supridesk_solutiontypes_solutiontemplates stst
													WHERE stst.solutiontypes_id = ".$solutiontype->fields["id"].")
						ORDER BY name";

      if ($result = $DB->query($query)) {
         if ($DB->numrows($result)) {
            echo "<select name='solutiontemplates_id' size=1>";
            while ($data= $DB->fetch_assoc($result)) {
               echo "<option value='".$data["id"]."'>".$data["name"]."</option>";
            }
            echo "</select>";
            return true;
         }
      }
      return false;
   }

   static function dropdownBySolutionType(SolutionType $solutiontype, $field_id = 'solutiontemplates_id', $selected_id = 0 ) {
      global $DB, $LANG;

		$solutiontype_id = -1;
		if ( $solutiontype->isField("id") )
			$solutiontype_id = $solutiontype->fields["id"];

      $query = "	SELECT *
						FROM glpi_solutiontemplates st
						WHERE st.id IN (	SELECT stst.solutiontemplates_id
													FROM supridesk_solutiontypes_solutiontemplates stst
													WHERE stst.solutiontypes_id = {$solutiontype_id})";

		echo "<select id='$field_id' name='solutiontemplates_id' size=1>";
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