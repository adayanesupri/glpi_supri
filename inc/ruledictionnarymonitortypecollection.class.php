<?php
/*
 * @version $Id: ruledictionnarymonitortypecollection.class.php 17152 2012-01-24 11:22:16Z moyo $
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
// Original Author of file: Walid Nouh
// Purpose of file:
// ----------------------------------------------------------------------

class RuleDictionnaryMonitorTypeCollection extends RuleDictionnaryDropdownCollection {

   // From RuleCollection
   //public $rule_class_name = 'RuleDictionnaryMonitorType';

   // Specific ones
   /// dropdown table
   var $item_table = "";

   /**
    * Constructor
   **/
   function __construct() {

      $this->item_table="glpi_monitortypes";
      //Init cache system values
      $this->initCache("glpi_rulecachemonitortypes");
      $this->menu_option = "type.monitor";
   }


   function getTitle() {
      global $LANG;

      return $LANG['rulesengine'][61];
   }

}
?>
