<?php

/*
 * @version $Id: log.class.php 19130 2012-08-22 14:33:50Z remi $
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
// Original Author of file: Julien Dombre
// Purpose of file:
// ----------------------------------------------------------------------

if (!defined('GLPI_ROOT')) {
   die("Sorry. You can't access directly to this file");
}

// Log class
class PluginMobileLog extends CommonDBTM {

   const HISTORY_ADD_DEVICE         = 1;
   const HISTORY_UPDATE_DEVICE      = 2;
   const HISTORY_DELETE_DEVICE      = 3;
   const HISTORY_INSTALL_SOFTWARE   = 4;
   const HISTORY_UNINSTALL_SOFTWARE = 5;
   const HISTORY_DISCONNECT_DEVICE  = 6;
   const HISTORY_CONNECT_DEVICE     = 7;
   const HISTORY_OCS_IMPORT         = 8;
   const HISTORY_OCS_DELETE         = 9;
   const HISTORY_OCS_IDCHANGED      = 10;
   const HISTORY_OCS_LINK           = 11;
   const HISTORY_LOG_SIMPLE_MESSAGE = 12;
   const HISTORY_DELETE_ITEM        = 13;
   const HISTORY_RESTORE_ITEM       = 14;
   const HISTORY_ADD_RELATION       = 15;
   const HISTORY_DEL_RELATION       = 16;
   const HISTORY_ADD_SUBITEM        = 17;
   const HISTORY_UPDATE_SUBITEM     = 18;
   const HISTORY_DELETE_SUBITEM     = 19;
   const HISTORY_CREATE_ITEM        = 20;
	/*//SUPRISERVICE*/
   const HISTORY_LOG_SUPRISERVICE_MESSAGE  = 21;

   /**
    * Show History of an item
    *
    * @param $item CommonDBTM object
    * @param $withtemplate integer : withtemplate param

   **/
   static function showForItem(CommonDBTM $item, $withtemplate='') {
      global $DB,$LANG;

      $itemtype = $item->getType();
      $items_id = $item->getField('id');

      //$SEARCHOPTION = Search::getOptions($itemtype);

      if (isset($_REQUEST["start"])) {
         $start = $_REQUEST["start"];
      } else {
         $start = 0;
      }

      // Total Number of events
      $number = countElementsInTable("glpi_logs",
                                     "`items_id`='$items_id' AND `itemtype`='$itemtype'");

      // No Events in database
      if ($number < 1) {
         echo "<div class='center'>";
         echo "<table class='tab_cadre_fixe'>";
         echo "<tr><th>".$LANG['event'][20]."</th></tr>";
         echo "</table>";
         echo "</div><br>";
         return;
      }

      // Display the pager
      //Html::printAjaxPager($LANG['title'][38],$start,$number);

      // Output events
      echo "<div class='center'><table class='tab_cadre_fixe'>";
      echo "<tr><th>".$LANG['common'][2]."</th><th>".$LANG['common'][27]."</th>";
      echo "<th>".$LANG['common'][34]."</th><th>".$LANG['event'][18]."</th>";
      echo "<th>".$LANG['event'][19]."</th></tr>";

      foreach (self::getHistoryData($item,$start,0) as $data) {
         if ($data['display_history']) {
            // show line
            echo "<tr class='tab_bg_2'>";
            echo "<td>".$data['id']."</td><td>".$data['date_mod'].
                 "</td><td>".$data['user_name']."</td><td>".$data['field']."</td>";
            echo "<td width='60%' align='left'>".$data['change']."</td></tr>";
         }
      }
      echo "</table></div>";
   }


   /**
    * Retrieve last history Data for an item
    *
    * @param $item CommonDBTM object
    * @param $start interger first line to retrieve
    * @param $limit interfer max number of line to retrive (0 for all)
    * @param $sqlfilter string to add an SQL filter
    *
    * @return array of localized log entry (TEXT only, no HTML)
   **/
   static function getHistoryData(CommonDBTM $item, $start=0, $limit=0, $sqlfilter='') {
      global $DB, $LANG;

      $itemtype = $item->getType();
      $items_id = $item->getField('id');

      $SEARCHOPTION = Search::getOptions($itemtype);

      $query = "SELECT *
                FROM `glpi_logs`
                WHERE `items_id` = '$items_id'
                      AND `itemtype` = '$itemtype' ";
      if ($sqlfilter) {
         $query .= "AND ($sqlfilter) ";
      }
      $query .= "ORDER BY `id` DESC";

      if ($limit) {
         $query .= " LIMIT ".intval($start)."," . intval($limit);
      }

      $changes = array();
      foreach ($DB->request($query) as $data) {
         $tmp = array();
         $tmp['display_history'] = true;
         $tmp['id']              = $data["id"];
         $tmp['date_mod']        = Html::convDateTime($data["date_mod"]);
         $tmp['user_name']       = $data["user_name"];
         $tmp['field']           = "";
         $tmp['change']          = "";
         $tmp['datatype']        = "";

         // This is an internal device ?
         if ($data["linked_action"]) {
            // Yes it is an internal device
            switch ($data["linked_action"]) {
               case self::HISTORY_CREATE_ITEM :
                  $tmp['change'] = $LANG['log'][20];
                  break;

               case self::HISTORY_DELETE_ITEM :
                  $tmp['change'] = $LANG['log'][22];
                  break;

               case self::HISTORY_RESTORE_ITEM :
                  $tmp['change'] = $LANG['log'][23];
                  break;

               case self::HISTORY_ADD_DEVICE :
                  $tmp['field'] = NOT_AVAILABLE;
                  if ($item = getItemForItemtype($data["itemtype_link"])) {
                     $tmp['field'] = $item->getTypeName();
                  }
                  $tmp['change'] = $LANG['devices'][25]." : "."\"". $data["new_value"]."\"";
                  break;

               case self::HISTORY_UPDATE_DEVICE :
                  $tmp['field'] = NOT_AVAILABLE;
                  $change = '';
                  if ($item = getItemForItemtype($data["itemtype_link"])) {
                     $tmp['field']  = $item->getTypeName();
                     $specif_fields = $item->getSpecifityLabel();
                     $tmp['change'] = $specif_fields['specificity']." : ";
                  }
                  $tmp['change'] .= $data[ "old_value"]." --> "."\"". $data[ "new_value"]."\"";
                  break;

               case self::HISTORY_DELETE_DEVICE :
                  $tmp['field']=NOT_AVAILABLE;
                  if ($item = getItemForItemtype($data["itemtype_link"])) {
                     $tmp['field'] = $item->getTypeName();
                  }
                  $tmp['change'] = $LANG['devices'][26]." : "."\"". $data["old_value"]."\"";
                  break;

               case self::HISTORY_INSTALL_SOFTWARE :
                  $tmp['field']  = $LANG['help'][31];
                  $tmp['change'] = $LANG['software'][44]." : "."\"".$data["new_value"]."\"";
                  break;

               case self::HISTORY_UNINSTALL_SOFTWARE :
                  $tmp['field']  = $LANG['help'][31];
                  $tmp['change'] = $LANG['software'][45]." : "."\"". $data["old_value"]."\"";
                  break;

               case self::HISTORY_DISCONNECT_DEVICE :
                  $tmp['field'] = NOT_AVAILABLE;
                  if ($item = getItemForItemtype($data["itemtype_link"])) {
                     $tmp['field'] = $item->getTypeName();
                  }
                  $tmp['change'] = $LANG['log'][26]." : "."\"". $data["old_value"]."\"";
                  break;

               case self::HISTORY_CONNECT_DEVICE :
                  $tmp['field'] = NOT_AVAILABLE;
                  if ($item = getItemForItemtype($data["itemtype_link"])) {
                     $tmp['field'] = $item->getTypeName();
                  }
                  $tmp['change'] = $LANG['log'][27]." : "."\"". $data["new_value"]."\"";
                  break;

               case self::HISTORY_OCS_IMPORT :
                  if (Session::haveRight("view_ocsng","r")) {
                     $tmp['field']  = "";
                     $tmp['change'] = $LANG['ocsng'][7]." ".$LANG['ocsng'][45]." :";
                     $tmp['change'].= " "."\"".$data["new_value"]."\"";
                  } else {
                     $tmp['display_history'] = false;
                  }
                  break;

               case self::HISTORY_OCS_DELETE :
                  if (Session::haveRight("view_ocsng","r")) {
                     $tmp['field']  ="";
                     $tmp['change'] = $LANG['ocsng'][46]." ".$LANG['ocsng'][45]." :";
                     $tmp['change'].= " "."\"".$data["old_value"]."\"";
                  } else {
                     $tmp['display_history'] = false;
                  }
                  break;

               case self::HISTORY_OCS_LINK :
                  if (Session::haveRight("view_ocsng","r")) {
                     $tmp['field'] = NOT_AVAILABLE;
                     if ($item = getItemForItemtype($data["itemtype_link"])) {
                        $tmp['field'] = $item->getTypeName();
                     }
                     $tmp['change'] = $LANG['ocsng'][47]." ".$LANG['ocsng'][45]." :";
                     $tmp['change'].= " "."\"".$data["new_value"]."\"";

                  } else {
                     $tmp['display_history'] = false;
                  }
                  break;

               case self::HISTORY_OCS_IDCHANGED :
                  if (Session::haveRight("view_ocsng","r")) {
                     $tmp['field']  = "";
                     $tmp['change'] = $LANG['ocsng'][48]." "." : "."\"".
                                      $data["old_value"]."\" -->  : "."\"".
                                      $data["new_value"]."\"";
                  } else {
                     $tmp['display_history'] = false;
                  }
                  break;

               case self::HISTORY_LOG_SIMPLE_MESSAGE :
                  $tmp['field']  = "";
                  $tmp['change'] = $data["new_value"];
                  break;

               case self::HISTORY_ADD_RELATION :
                  $tmp['field'] = NOT_AVAILABLE;
                  if ($item = getItemForItemtype($data["itemtype_link"])) {
                     $tmp['field'] = $item->getTypeName();
                  }
                  $tmp['change'] = $LANG['log'][32]." : "."\"". $data["new_value"]."\"";
                  break;

               case self::HISTORY_DEL_RELATION :
                  $tmp['field'] = NOT_AVAILABLE;
                  if ($item = getItemForItemtype($data["itemtype_link"])) {
                     $tmp['field'] = $item->getTypeName();
                  }
                  $tmp['change'] = $LANG['log'][33]." : "."\"". $data["old_value"]."\"";
                  break;

               case self::HISTORY_ADD_SUBITEM :
                  $tmp['field'] = '';
                  if ($item = getItemForItemtype($data["itemtype_link"])) {
                     $tmp['field'] = $item->getTypeName();
                  }
                  $tmp['change'] = $LANG['log'][98]." : ".$tmp['field']." (".$data["new_value"].")";
                  break;

               case self::HISTORY_UPDATE_SUBITEM :
                  $tmp['field'] = '';
                  if ($item = getItemForItemtype($data["itemtype_link"])) {
                     $tmp['field'] = $item->getTypeName();
                  }
                  $tmp['change'] = $LANG['log'][99]." : ".$tmp['field']." (".$data["new_value"].")";
                  break;

               case self::HISTORY_DELETE_SUBITEM :
                  $tmp['field'] = '';
                  if ($item = getItemForItemtype($data["itemtype_link"])) {
                     $tmp['field'] = $item->getTypeName();
                  }
                  $tmp['change'] = $LANG['log'][100]." : ".$tmp['field']." (".$data["old_value"].")";
                  break;

					/*//SUPRISERVICE*/
               case self::HISTORY_LOG_SUPRISERVICE_MESSAGE :
                  $tmp['field']  = '';
                  if ($item = getItemForItemtype($data["itemtype_link"])) {
                     $tmp['field'] = $item->getTypeName();
                  }
                  $tmp['change'] = $data["new_value"];
                  break;

            }

         } else {
            $fieldname = "";
            // It's not an internal device
            foreach ($SEARCHOPTION as $key2 => $val2) {
               if ($key2==$data["id_search_option"]) {
                  $tmp['field'] =  $val2["name"];
                  $fieldname    = $val2["field"];

                  if (isset($val2['datatype'])) {
                     $tmp['datatype'] = $val2["datatype"];
                  }
               }
            }

            switch ($tmp['datatype']) {
               case "bool" :
                  $data["old_value"] = Dropdown::getYesNo($data["old_value"]);
                  $data["new_value"] = Dropdown::getYesNo($data["new_value"]);
                  break;

               case "datetime" :
                  $data["old_value"] = Html::convDateTime($data["old_value"]);
                  $data["new_value"] = Html::convDateTime($data["new_value"]);
                  break;

               case "date" :
                  $data["old_value"] = Html::convDate($data["old_value"]);
                  $data["new_value"] = Html::convDate($data["new_value"]);
                  break;

               case "timestamp" :
                  $data["old_value"] = Html::timestampToString($data["old_value"]);
                  $data["new_value"] = Html::timestampToString($data["new_value"]);
                  break;

               case "actiontime" :
                  $data["old_value"] = CommonITILObject::getActionTime($data["old_value"]);
                  $data["new_value"] = CommonITILObject::getActionTime($data["new_value"]);
                  break;

               case "number" :
                  $data["old_value"] = Html::formatNumber($data["old_value"],false,0);
                  $data["new_value"] = Html::formatNumber($data["new_value"],false,0);
                  break;

               case "decimal" :
                  $data["old_value"] = Html::formatNumber($data["old_value"]);
                  $data["new_value"] = Html::formatNumber($data["new_value"]);
                  break;

               case "right" :
                  $data["old_value"] = Profile::getRightValue($data["old_value"]);
                  $data["new_value"] = Profile::getRightValue($data["new_value"]);
                  break;

               case "text" :
                  $tmp['change'] = $LANG['log'][64];
                  break;
            }
            if (empty($tmp['change'])) {
               $tmp['change'] = "\"".$data["old_value"]."\" --> \"". $data["new_value"]."\"";
            }
         }
         $changes[] = $tmp;
      }
      return $changes;
   }
}
?>