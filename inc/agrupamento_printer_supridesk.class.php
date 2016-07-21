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
class Agrupamento_Printer_Supridesk extends CommonDBRelation {

   // From CommonDBRelation
   public $itemtype_1 = 'Agrupamento_Supridesk';
   public $items_id_1 = 'agrupamentos_id';

   public $itemtype_2 = 'Printer';
   public $items_id_2 = 'printers_id';
	protected $table = "supridesk_agrupamentos_printers";



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

   /**
    * Get search function for the class
    *
    * @return array of search option
   **/
   function getSearchOptions() {
      global $LANG;

      $tab = parent::getSearchOptions();

      return $tab;
   }

   function unassignPrinterbyID($ID) {
      global $DB;

      $query = "SELECT *
                FROM `supridesk_agrupamentos_printers`
                WHERE `id` = '$ID'";
      if ($result = $DB->query($query)) {
         $data = $DB->fetch_array($result);

         // Delete Contract Item Printer
         $query = "DELETE
                   FROM `supridesk_agrupamentos_printers`
                   WHERE `id` = '$ID'";
         $DB->query($query);
      }
   }


   function assignPrinter($agrupamento, $printer) {
      global $DB;

      $query = "INSERT INTO
                `supridesk_agrupamentos_printers` ( `agrupamentos_id`, `printers_id` )
                VALUES ( $agrupamento, $printer )";
      $DB->query($query);
   }

   static function showForContractItem($ID, $canedit, $withtemplate) {
      global $DB, $CFG_GLPI, $LANG;

      $used = array();

      $query = "SELECT `glpi_printermodels`.`name` as modelname,
								supridesk_agrupamentos_printers.id as idmaster,
								supridesk_agrupamentos_printers.*, 
								glpi_printers.* , 
								glpi_states.* 
                FROM `supridesk_agrupamentos_printers`
                LEFT JOIN `glpi_printers`
                        ON (`supridesk_agrupamentos_printers`.`printers_id` = `glpi_printers`.`id`)
                LEFT JOIN `glpi_states`
                        ON (`glpi_printers`.`states_id` = `glpi_states`.`id`)
                LEFT JOIN `glpi_printermodels`
                        ON (`glpi_printers`.`printermodels_id` = `glpi_printermodels`.`id`)
                WHERE `agrupamentos_id` = '$ID'";

      $result = $DB->query($query);
      if ($DB->numrows($result) > 0) {

         echo "\n<table width='100%'>";

			echo "<th>" . "ID" . "</th>\n";
			echo "<th>" . "Modelo" . "</th>\n";
			echo "<th>" . "Equipamento" . "</th>\n";
			echo "<th>" . "Ações" . "</th>\n";

         while ($line = $DB->fetch_array($result)) {
            $used[$line["printers_id"]] = $line["printers_id"];

				echo "<tr>";
				//ID
				echo "<td align='center' width='50'>{$line['printers_id']}</td>";
				//modelo
				echo "<td>{$line['modelname']}</td>";
				//equipamento
				echo "<td width='100'>";
				echo "<a href='" . $CFG_GLPI["root_doc"] . "/front/printer.form.php?id=".$line["printers_id"] . "'>";
				echo Dropdown::getDropdownName("glpi_printers", $line["printers_id"]) . "&nbsp;";
            Html::showToolTip("<b>".$LANG['common'][19]."&nbsp;:</b> ".$line['serial']."<br>
                              <b>".$LANG['common'][20] ."&nbsp;:</b> ".$line['otherserial']."<br>
                              <b>".$LANG['joblist'][0] ."&nbsp;:</b> ".$line['completename'] );
            echo  "</a>";
            echo "</td>";

				echo "<td align='center' width='100'>";
            if ($canedit) {
               echo "<a href='" . $CFG_GLPI["root_doc"] . "/front/agrupamento_supridesk.form.php?unassign_printer=".
                     "unassigned&amp;id=" . $line["idmaster"] . "'>";
               echo "<img src=\"" . $CFG_GLPI["root_doc"] . "/pics/delete.png\" alt=\"" .
                     $LANG['agrupamento'][12] . "\" title=\"" . $LANG['agrupamento'][12] . "\"></a>";
            } else {
               echo "&nbsp;";
            }
            echo "</td></tr>\n";
         }
         echo "</table>";
      } else {
         echo "&nbsp;";
      }
      return $used;
   }

   static function showForContractItemForm($ID) {

      global $DB, $CFG_GLPI, $LANG;
      $item = new Agrupamento_Supridesk();

      if ($ID && $item->can($ID,'w')) {

         echo "\n<div class='center'>";
         echo "<form method='post' action='" . $CFG_GLPI["root_doc"] . "/front/agrupamento_supridesk.form.php'>";
         echo "<input type='hidden' name='agrupamentos_id' value='$ID'>\n";

         echo "<table class='tab_cadre' width='800px'>";
         echo "<tr><th>" . $LANG['tarifacao'][29] . "</th></tr>\n";
         echo "<tr class='tab_bg_2'><td>";
         $used=self::showForContractItem($ID, true,0);
         echo "</td></tr>\n";

			echo "<tr class='tab_bg_2'><td>";
			echo "Itens da lista filtrada que possuem <b>*</b> são equipamentos já associados a outro agrupamento.";

			$options= array('entity_sons'        => true,
                         'show_used_mark'     => true,
                         'mark_table'         => 'supridesk_agrupamentos_printers',
                         'separador'          => ' ');
			//sempre mostra equipamentos de todas entidades, pois no supridesk está associado o local físico
			Ticket::dropdownAllDevices(0, 'Printer', 0, 1, 0, 0, $options);
         echo "&nbsp;<input type='submit' name='assign_printer' value='&nbsp;" . $LANG['buttons'][3] . "&nbsp;' class='submit'>";
			echo "</td></tr>";
         echo "</table>";
         Html::closeForm();
      }
   }


   static function getPrinterAlreadyUsed( $printerID ) {
      global $DB;

      $query = "SELECT *
               FROM `supridesk_agrupamentos_printers`
					WHERE `printers_id` = $printerID ";
      $result = $DB->query($query);
      return ($DB->numrows($result) > 0);
   }

   static function getFromAgrupamentoID( $agrupamentoID ) {
      global $DB;

      $query = "SELECT *
               FROM `supridesk_agrupamentos_printers`
					WHERE `supridesk_agrupamentos_printers`.`agrupamentos_id` = $agrupamentoID";
      $result = $DB->query($query);

      return intval($DB->numrows($result)) > 0;
   }

   function getFromPrinterID( $printersID ) {
      global $DB;

      $query = "SELECT *
               FROM `supridesk_agrupamentos_printers`
					WHERE `supridesk_agrupamentos_printers`.`printers_id` = $printersID";
		if ($result = $DB->query($query)) {
         if ($DB->numrows($result)==1) {
            $this->fields = $DB->fetch_assoc($result);
            $this->post_getFromDB();
         }
			return intval($DB->numrows($result)) > 0;
      }
		return 0;
   }


   function getTabNameForItem(CommonGLPI $item, $withtemplate=0) {
      global $LANG;

      if (!$withtemplate) {
         switch ($item->getType()) {
            case 'Agrupamento_Supridesk' :
               if ($_SESSION['glpishow_count_on_tabs']) {
                  return self::createTabEntry($LANG['agrupamento'][8],
                                              countElementsInTable($this->getTable(),
                                                                   "agrupamentos_id = '".$item->getID()."'"));
               }
               return $LANG['agrupamento'][8];
         }
      }
      return '';
   }


   static function displayTabContentForItem(CommonGLPI $item, $tabnum=1, $withtemplate=0) {
      if ($item->getType()=='Agrupamento_Supridesk') {
         self::showForContractItemForm($item->getID());
      }
      return true;
   }

}

?>
