<?php
/*
 * @version $Id: networkport_vlan.class.php 18771 2012-06-29 08:49:19Z moyo $
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
class Stock_Tickets extends CommonITILActor {

   // From CommonDBRelation
   public $itemtype_1 = 'Stock';
   public $items_id_1 = 'stocks_id';
   public $itemtype_2 = 'Ticket';
   public $items_id_2 = 'tickets_id';
	
    protected $table = "supridesk_pecas_ticket";



   function canCreate() {
      return Session::haveRight('stock', 'w');
   }


   function canView() {
      return Session::haveRight('stock', 'r');
   }


   /**
    * Get search function for the class
    *
    * @return array of search option
   **/
   function getSearchOptions() {
      global $LANG;

      //$tab = parent::getSearchOptions();

      /*$tab[7]['table']    = 'supridesk_agrupamentos';
      $tab[7]['field']    = 'name';
      $tab[7]['name']     = 'Agrupamento';
      $tab[7]['datatype'] = 'dropdown';

      return $tab;*/
   }



   function addStockTicket( $stock_id, $ticket_id, $quantidade )
    {
      global $DB;

      $query = "INSERT INTO
                `supridesk_pecas_ticket` ( `peca_id`, `tickets_id`, `quantidade`, `data`)
                VALUES ( $stock_id, $ticket_id, $quantidade , NOW())";

        if ( $DB->query( $query ) ) {
                return $DB->insert_id();
        }
        return null;
   }

   
}

?>
