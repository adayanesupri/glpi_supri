<?php
/*
 * @version $Id: commondbrelation.class.php 19104 2012-08-21 07:43:03Z moyo $
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

if (!defined('GLPI_ROOT')) {
   die("Sorry. You can't access directly to this file");
}

/// Common DataBase Relation Table Manager Class
abstract class CommonDBRelation extends CommonDBTM {

   // Mapping between DB fields
   var $itemtype_1; // Type ref or field name (must start with itemtype)
   var $items_id_1; // Field name
   var $itemtype_2; // Type ref or field name (must start with itemtype)
   var $items_id_2; // Field name

   var $check_entities = true;

   var $checks_only_for_itemtype1 = false;
   var $logs_only_for_itemtype1   = false;


   /**
    * Get search function for the class
    *
    * @return array of search option
   **/
   function getSearchOptions() {
      global $LANG;

      $tab = array();
      $tab['common'] = $LANG['common'][32];

      $tab[2]['table']         = $this->getTable();
      $tab[2]['field']         = 'id';
      $tab[2]['name']          = $LANG['common'][2];
      $tab[2]['massiveaction'] = false;

      $tab[3]['table']         = getTableForItemType($this->itemtype_1);
      $tab[3]['field']         = $this->items_id_1;
      $tab[3]['name']          = call_user_func(array($this->itemtype_1, 'getTypeName'));
      $tab[3]['datatype']      = 'text';
      $tab[3]['massiveaction'] = false;

      $tab[4]['table']         = getTableForItemType($this->itemtype_2);
      $tab[4]['field']         = $this->items_id_2;
      $tab[4]['name']          = call_user_func(array($this->itemtype_2, 'getTypeName'));
      $tab[4]['datatype']      = 'text';
      $tab[4]['massiveaction'] = false;

      return $tab;
   }


   /**
    * Check right on an item
    *
    * @param $ID ID of the item (-1 if new item)
    * @param $right Right to check : r / w / recursive
    * @param $input array of input data (used for adding item)
    *
    * @return boolean
   **/
   function can($ID, $right, &$input=NULL) {

      if ($ID>0) {
         if (!isset($this->fields['id']) || $this->fields['id']!=$ID) {
            // Item not found : no right
            if (!$this->getFromDB($ID)) {
               return false;
            }
         }
         $input = &$this->fields;
      }
      // Must can read first Item of the relation
      $type1 = $this->itemtype_1;
      if (preg_match('/^itemtype/',$this->itemtype_1)) {
         $type1 = $input[$this->itemtype_1];
      }
      if (!($item1 = getItemForItemtype($type1))) {
         return false;
      }

      // Can create a relation with a dropdown/device (use it) without read right
      if (!($item1 instanceof CommonDropdown)
          && !$item1->can($input[$this->items_id_1],'r')) {
         return false;
      }


      // Must can read second Item of the relation
      $type2 = $this->itemtype_2;
      if (preg_match('/^itemtype/',$this->itemtype_2)) {
         $type2 = $input[$this->itemtype_2];
      }

      if (!($item2 = getItemForItemtype($type2))) {
         return false;
      }

      if (!$this->checks_only_for_itemtype1
          && !($item2 instanceof CommonDropdown)) {
         if (!$item2->can($input[$this->items_id_2],'r')) {
            return false;
         }
      } else {
         /// TODO : need to be improved, only some classe should allow relation to 0
         // id==0 is used in some relation (tickets_users)
         if ($input[$this->items_id_2]>0 && !$item2->getFromDB($input[$this->items_id_2])) {
            return false;
         }
      }

      // Read right checked on both item
      if ($right=='r') {
         return true;
      }

      // Check entity compatibility / no check for delete just check write access
      // No check if checking only itemtype1
      if (!$this->checks_only_for_itemtype1
          && $this->check_entities
          && $right!='d') {

         if ($item1->isEntityAssign() && $item2->isEntityAssign()) {

            // get items if needed : need to have entity set
            if (!isset($item1->fields['id'])) {
               $item1->getFromDB($input[$this->items_id_1]);
            }

            if (!isset($item2->fields['id'])) {
               $item2->getFromDB($input[$this->items_id_2]);
            }

            if ($item1->getEntityID() == $item2->getEntityID()) {
               $checkentity = true;

            } else if ($item1->isRecursive() && in_array($item1->getEntityID(),
                                                         getAncestorsOf("glpi_entities",
                                                                        $item2->getEntityID()))) {
               $checkentity = true;

            } else if ($item2->isRecursive() && in_array($item2->getEntityID(),
                                                         getAncestorsOf("glpi_entities",
                                                                        $item1->getEntityID()))) {
               $checkentity = true;

            } else {
               // $checkentity is false => return
               return false;
            }
         }
      }

      // can write one item is enough
      if ($item1->can($input[$this->items_id_1],'w')
          || ($this->checks_only_for_itemtype1
              || $item2->can($input[$this->items_id_2],'w'))) {
         return true;
      }

      return false;
   }


   /**
    * Actions done after the ADD of the item in the database
    *
    * @return nothing
   **/
   function post_addItem() {

      if (isset($this->input['_no_history']) && $this->input['_no_history']) {
         return false;
      }

      $type1 = $this->itemtype_1;
      if (preg_match('/^itemtype/',$this->itemtype_1)) {
         $type1 = $this->fields[$this->itemtype_1];
      }

      if (!($item1 = getItemForItemtype($type1))) {
         return false;
      }

      if (!$item1->getFromDB($this->fields[$this->items_id_1])) {
         return false;
      }

      $type2 = $this->itemtype_2;
      if (preg_match('/^itemtype/',$this->itemtype_2)) {
         $type2 = $this->fields[$this->itemtype_2];
      }

      if (!($item2 = getItemForItemtype($type2))) {
         return false;
      }

      if (!$item2->getFromDB($this->fields[$this->items_id_2])) {
         return false;
      }

      if ($item1->dohistory) {
         $changes[0] = '0';
         $changes[1] = "";
         $changes[2] = addslashes($item2->getNameID(false, true));
         Log::history($item1->fields["id"], get_class($item1), $changes, get_class($item2),
                      Log::HISTORY_ADD_RELATION);
      }

      if (!$this->logs_only_for_itemtype1 && $item2->dohistory) {
         $changes[0] = '0';
         $changes[1] = "";
         $changes[2] = addslashes($item1->getNameID(false, true));
         Log::history($item2->fields["id"], get_class($item2), $changes, get_class($item1),
                      Log::HISTORY_ADD_RELATION);
      }
   }


   /**
    * Actions done after the DELETE of the item in the database
    *
    *@return nothing
    *
   **/
   function post_deleteFromDB() {

      if (isset($this->input['_no_history']) && $this->input['_no_history']) {
         return false;
      }

      $type1 = $this->itemtype_1;
      if (preg_match('/^itemtype/',$this->itemtype_1)) {
         $type1 = $this->fields[$this->itemtype_1];
      }

      if (!($item1 = getItemForItemtype($type1))) {
         return false;
      }

      if (!$item1->getFromDB($this->fields[$this->items_id_1])) {
         return false;
      }


      $type2 = $this->itemtype_2;
      if (preg_match('/^itemtype/',$this->itemtype_2)) {
         $type2 = $this->fields[$this->itemtype_2];
      }

      if (!($item2 = getItemForItemtype($type2))) {
         return false;
      }

      if (!$item2->getFromDB($this->fields[$this->items_id_2])) {
         return false;
      }

      if ($item1->dohistory) {
         $changes[0] = '0';
         $changes[1] = addslashes($item2->getNameID(false, true));
         $changes[2] = "";
         Log::history($item1->fields["id"], get_class($item1), $changes, get_class($item2),
                      Log::HISTORY_DEL_RELATION);
      }

      if (!$this->logs_only_for_itemtype1 && $item2->dohistory) {
         $changes[0] = '0';
         $changes[1] = addslashes($item1->getNameID(false, true));
         $changes[2] = "";
         Log::history($item2->fields["id"], get_class($item2), $changes, get_class($item1),
                      Log::HISTORY_DEL_RELATION);
      }
   }


   /**
    * Clean the Relation Table when item of the relation is deleted
    * To be call from the cleanDBonPurge of each Item class
    *
    * @param $itemtype : type of the item
    * @param $item_id : id of the item
   **/
   function cleanDBonItemDelete ($itemtype, $item_id) {
      global $DB;

      $query = "SELECT `id`
                FROM `".$this->getTable()."`";

      if ($itemtype == $this->itemtype_1) {
         $where = " WHERE `".$this->items_id_1."` = '$item_id'";

      } else if (preg_match('/^itemtype/',$this->itemtype_1)) {
         $where = " WHERE (`".$this->itemtype_1."` = '$itemtype'
                           AND `".$this->items_id_1."` = '$item_id')";

      } else {
         $where = '';
      }

      if ($itemtype == $this->itemtype_2) {
         $where .= (empty($where) ? " WHERE " : " OR ")."`".$this->items_id_2."` = '$item_id'";

      } else if (preg_match('/^itemtype/',$this->itemtype_2)) {
         $where .= (empty($where) ? " WHERE " : " OR ")."(`".$this->itemtype_2."` = '$itemtype'
                                                          AND `".$this->items_id_2."` = '$item_id')";
      }

      if (empty($where)) {
         return false;
      }

      $result = $DB->query($query.$where);
      while ($data = $DB->fetch_assoc($result)) {
         $data['_no_history'] = true; // Parent is deleted
         $data['_no_notif']   = true; // Parent is deleted
         $this->delete($data);
      }
   }

}

?>
