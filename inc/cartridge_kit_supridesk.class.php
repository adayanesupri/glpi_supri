<?php
/*
 * @version $Id: computer.class.php 19244 2012-09-11 18:17:25Z remi $
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

/**
 *  Computer class
**/
class Cartridge_Kit_Supridesk extends CommonDBTM {

   /*//SUPRISERVICE*/
   public $supridesk_custom_table = "supridesk_cartridges_kits";

   // From CommonDBTM
   public $dohistory = true;
   protected $forward_entity_to = array('ComputerDisk','ComputerVirtualMachine', 'Infocom',
                                        'NetworkPort', 'Ocslink', 'ReservationItem');
   // Specific ones
   ///Device container - format $device = array(ID,"device type","ID in device table","specificity value")
   var $devices = array();


   /**
    * Name of the type
    *
    * @param $nb : number of item in the type
    *
    * @return $LANG
   **/
   static function getTypeName($nb=0) {
      global $LANG;

      if ($nb>1) {
         return $LANG['cartridge_kit'][2];
      }
      return $LANG['cartridge_kit'][1];
   }


   function canCreate() {
      return Session::haveRight('cartridge_kits', 'w');
   }


   function canView() {
      return Session::haveRight('cartridge_kits', 'r');
   }


   function defineTabs($options=array()) {
      global $LANG, $CFG_GLPI;

      $ong = array();
      $this->addStandardTab('Cartridge_Kit_Supridesk', $ong, $options);
      $this->addStandardTab('Infocom', $ong, $options);
      $this->addStandardTab('Log', $ong, $options);

      return $ong;
   }


   function post_restoreItem() {

      $comp_softvers = new Computer_SoftwareVersion();
      $comp_softvers->updateDatasForComputer($this->fields['id']);
   }


   function post_deleteItem() {

      $comp_softvers = new Computer_SoftwareVersion();
      $comp_softvers->updateDatasForComputer($this->fields['id']);
   }


   function post_updateItem($history=1) {
      global $DB, $LANG, $CFG_GLPI;

      // Manage changes for OCS if more than 1 element (date_mod)
      // Need dohistory==1 if dohistory==2 no locking fields
      if ($this->fields["is_ocs_import"] && $history==1 && count($this->updates)>1) {
         OcsServer::mergeOcsArray($this->fields["id"], $this->updates, "computer_update");
      }

      if (isset($this->input["_auto_update_ocs"])) {
         $query = "UPDATE `glpi_ocslinks`
                   SET `use_auto_update` = '".$this->input["_auto_update_ocs"]."'
                   WHERE `computers_id` = '".$this->input["id"]."'";
         $DB->query($query);
      }

      for ($i=0 ; $i<count($this->updates) ; $i++) {
         // Update contact of attached items
         if (($this->updates[$i]=="contact" || $this->updates[$i]=="contact_num")
             && $CFG_GLPI["is_contact_autoupdate"]) {

            $items = array('Monitor', 'Peripheral', 'Phone', 'Printer');

            $update_done = false;
            $updates3[0] = "contact";
            $updates3[1] = "contact_num";

            foreach ($items as $t) {
               $query = "SELECT *
                         FROM `glpi_computers_items`
                         WHERE `computers_id` = '".$this->fields["id"]."'
                               AND `itemtype` = '".$t."'";
               if ($result=$DB->query($query)) {
                  $resultnum = $DB->numrows($result);
                  $item = new $t();
                  if ($resultnum>0) {
                     for ($j=0 ; $j<$resultnum ; $j++) {
                        $tID = $DB->result($result, $j, "items_id");
                        $item->getFromDB($tID);
                        if (!$item->getField('is_global')) {
                           if ($item->getField('contact')!=$this->fields['contact']
                               || $item->getField('contact_num')!=$this->fields['contact_num']) {

                              $tmp["id"]          = $item->getField('id');
                              $tmp['contact']     = $this->fields['contact'];
                              $tmp['contact_num'] = $this->fields['contact_num'];
                              $item->update($tmp);
                              $update_done = true;
                           }
                        }
                     }
                  }
               }
            }

            if ($update_done) {
               Session::addMessageAfterRedirect($LANG['computers'][49], true);
            }
         }

         // Update users and groups of attached items
         if (($this->updates[$i]=="users_id"
              && $this->fields["users_id"]!=0
              && $CFG_GLPI["is_user_autoupdate"])
             ||($this->updates[$i]=="groups_id" && $this->fields["groups_id"]!=0
                && $CFG_GLPI["is_group_autoupdate"])) {

            $items = array('Monitor', 'Peripheral', 'Phone', 'Printer');

            $update_done = false;
            $updates4[0] = "users_id";
            $updates4[1] = "groups_id";

            foreach ($items as $t) {
               $query = "SELECT *
                         FROM `glpi_computers_items`
                         WHERE `computers_id` = '".$this->fields["id"]."'
                               AND `itemtype` = '".$t."'";

               if ($result=$DB->query($query)) {
                  $resultnum = $DB->numrows($result);
                  $item = new $t();
                  if ($resultnum>0) {
                     for ($j=0 ; $j<$resultnum ; $j++) {
                        $tID = $DB->result($result, $j, "items_id");
                        $item->getFromDB($tID);
                        if (!$item->getField('is_global')) {
                           if ($item->getField('users_id')!=$this->fields["users_id"]
                               ||$item->getField('groups_id')!=$this->fields["groups_id"]) {

                              $tmp["id"] = $item->getField('id');

                              if ($CFG_GLPI["is_user_autoupdate"]) {
                                 $tmp["users_id"] = $this->fields["users_id"];
                              }
                              if ($CFG_GLPI["is_group_autoupdate"]) {
                                 $tmp["groups_id"] = $this->fields["groups_id"];
                              }
                              $item->update($tmp);
                              $update_done = true;
                           }
                        }
                     }
                  }
               }
            }
            if ($update_done) {
               Session::addMessageAfterRedirect($LANG['computers'][50], true);
            }
         }

         // Update state of attached items
         if ($this->updates[$i]=="states_id" && $CFG_GLPI["state_autoupdate_mode"]<0) {
            $items = array('Monitor', 'Peripheral', 'Phone', 'Printer');
            $update_done = false;

            foreach ($items as $t) {
               $query = "SELECT *
                         FROM `glpi_computers_items`
                         WHERE `computers_id` = '".$this->fields["id"]."'
                               AND `itemtype` = '".$t."'";

               if ($result=$DB->query($query)) {
                  $resultnum = $DB->numrows($result);
                  $item = new $t();

                  if ($resultnum>0) {
                     for ($j=0 ; $j<$resultnum ; $j++) {
                        $tID = $DB->result($result, $j, "items_id");
                        $item->getFromDB($tID);
                        if (!$item->getField('is_global')) {
                           if ($item->getField('states_id')!=$this->fields["states_id"]) {
                              $tmp["id"]        = $item->getField('id');
                              $tmp["states_id"] = $this->fields["states_id"];
                              $item->update($tmp);
                              $update_done = true;
                           }
                        }
                     }
                  }
               }
            }
            if ($update_done) {
               Session::addMessageAfterRedirect($LANG['computers'][56], true);
            }
         }

         // Update loction of attached items
         if ($this->updates[$i]=="locations_id"
             && $this->fields["locations_id"]!=0
             && $CFG_GLPI["is_location_autoupdate"]) {

            $items = array('Monitor', 'Peripheral', 'Phone', 'Printer');
            $update_done = false;
            $updates2[0] = "locations_id";

            foreach ($items as $t) {
               $query = "SELECT *
                         FROM `glpi_computers_items`
                         WHERE `computers_id` = '".$this->fields["id"]."'
                               AND `itemtype` = '".$t."'";

               if ($result=$DB->query($query)) {
                  $resultnum = $DB->numrows($result);
                  $item = new $t();

                  if ($resultnum>0) {
                     for ($j=0 ; $j<$resultnum ; $j++) {
                        $tID = $DB->result($result, $j, "items_id");
                        $item->getFromDB($tID);
                        if (!$item->getField('is_global')) {
                           if ($item->getField('locations_id')!=$this->fields["locations_id"]) {
                              $tmp["id"]           = $item->getField('id');
                              $tmp["locations_id"] = $this->fields["locations_id"];
                              $item->update($tmp);
                              $update_done = true;
                           }
                        }
                     }
                  }
               }
            }
            if ($update_done) {
               Session::addMessageAfterRedirect($LANG['computers'][48], true);
            }
         }
      }
   }


   function prepareInputForAdd($input) {

      if (isset($input["id"]) && $input["id"]>0) {
         $input["_oldID"] = $input["id"];
      }
      unset($input['id']);
      unset($input['withtemplate']);

      return $input;
   }


   function post_addItem() {
      global $DB;

      // Manage add from template
      if (isset($this->input["_oldID"])) {
         // ADD Devices
         $compdev = new Computer_Device();
         $compdev->cloneComputer($this->input["_oldID"], $this->fields['id']);

         // ADD Infocoms
         $ic= new Infocom();
         $ic->cloneItem($this->getType(), $this->input["_oldID"], $this->fields['id']);

         // ADD volumes
         $query = "SELECT `id`
                   FROM `glpi_computerdisks`
                   WHERE `computers_id` = '".$this->input["_oldID"]."'";
         $result=$DB->query($query);
         if ($DB->numrows($result)>0) {
            while ($data=$DB->fetch_array($result)) {
               $disk = new ComputerDisk();
               $disk->getfromDB($data['id']);
               unset($disk->fields["id"]);
               $disk->fields["computers_id"] = $this->fields['id'];
               $disk->addToDB();
            }
         }

         // ADD software
         $inst = new Computer_SoftwareVersion();
         $inst->cloneComputer($this->input["_oldID"], $this->fields['id']);

         $inst = new Computer_SoftwareLicense();
         $inst->cloneComputer($this->input["_oldID"], $this->fields['id']);

         // ADD Contract
         $query = "SELECT `contracts_id`
                   FROM `glpi_contracts_items`
                   WHERE `items_id` = '".$this->input["_oldID"]."'
                         AND `itemtype` = '".$this->getType()."';";
         $result=$DB->query($query);
         if ($DB->numrows($result)>0) {
            $contractitem = new Contract_Item();
            while ($data=$DB->fetch_array($result)) {
               $contractitem->add(array('contracts_id' => $data["contracts_id"],
                                        'itemtype'     => $this->getType(),
                                        'items_id'     => $this->fields['id']));
            }
         }

         // ADD Documents
         $query = "SELECT `documents_id`
                   FROM `glpi_documents_items`
                   WHERE `items_id` = '".$this->input["_oldID"]."'
                         AND `itemtype` = '".$this->getType()."';";
         $result=$DB->query($query);
         if ($DB->numrows($result)>0) {
            $docitem = new Document_Item();
            while ($data=$DB->fetch_array($result)) {
               $docitem->add(array('documents_id' => $data["documents_id"],
                                   'itemtype'     => $this->getType(),
                                   'items_id'     => $this->fields['id']));
            }
         }

         // ADD Ports
         $query = "SELECT `id`
                   FROM `glpi_networkports`
                   WHERE `items_id` = '".$this->input["_oldID"]."'
                         AND `itemtype` = '".$this->getType()."';";
         $result=$DB->query($query);
         if ($DB->numrows($result)>0) {
            while ($data=$DB->fetch_array($result)) {
               $np  = new NetworkPort();
               $npv = new NetworkPort_Vlan();
               $np->getFromDB($data["id"]);
               unset($np->fields["id"]);
               unset($np->fields["ip"]);
               unset($np->fields["mac"]);
               unset($np->fields["netpoints_id"]);
               $np->fields["items_id"] = $this->fields['id'];
               $portid = $np->addToDB();
               foreach ($DB->request('glpi_networkports_vlans',
                                     array('networkports_id' => $data["id"])) as $vlan) {
                  $npv->assignVlan($portid, $vlan['vlans_id']);
               }
            }
         }

         // Add connected devices
         $query = "SELECT *
                   FROM `glpi_computers_items`
                   WHERE `computers_id` = '".$this->input["_oldID"]."';";
         $result = $DB->query($query);

         if ($DB->numrows($result)>0) {
            $conn = new Computer_Item();
            while ($data=$DB->fetch_array($result)) {
               $conn->add(array('computers_id' => $this->fields['id'],
                                'itemtype'     => $data["itemtype"],
                                'items_id'     => $data["items_id"]));
            }
         }
      }
   }


   /*function cleanDBonPurge() {
      global $DB;

      $query = "DELETE
                FROM `glpi_computers_softwareversions`
                WHERE `computers_id` = '".$this->fields['id']."'";
      $result = $DB->query($query);

      $query = "SELECT `id`
                FROM `glpi_computers_items`
                WHERE `computers_id` = '".$this->fields['id']."'";

      if ($result = $DB->query($query)) {
         if ($DB->numrows($result)>0) {
            $conn = new Computer_Item();
            while ($data = $DB->fetch_array($result)) {
               $data['_no_auto_action'] = true;
               $conn->delete($data);
            }
         }
      }

      $query = "DELETE
                FROM `glpi_registrykeys`
                WHERE `computers_id` = '".$this->fields['id']."'";
      $result = $DB->query($query);

      $compdev = new Computer_Device();
      $compdev->cleanDBonItemDelete('Computer', $this->fields['id']);

      $query = "DELETE
                FROM `glpi_ocslinks`
                WHERE `computers_id` = '".$this->fields['id']."'";
      $result = $DB->query($query);

      $disk = new ComputerDisk();
      $disk->cleanDBonItemDelete('Computer', $this->fields['id']);

      $vm = new ComputerVirtualMachine();
      $vm->cleanDBonItemDelete('Computer', $this->fields['id']);
   }*/


   /**
   * Print the computer form
   *
   * @param $ID integer ID of the item
   * @param $options array
   *     - target for the Form
   *     - withtemplate template or basic computer
   *
   *@return Nothing (display)
   *
   **/
   function showForm($ID, $options=array()) {
      global $LANG, $CFG_GLPI, $DB;

      if ($ID > 0) {
         $this->check($ID,'r');
      } else {
         // Create item
         $this->check(-1,'w');
      }

      $this->showTabs($options);
      $this->showFormHeader($options);

      echo "<tr class='tab_bg_1'>";
      echo "<td>".$LANG['common'][16].($template?"*":"")."&nbsp;:</td>";
      echo "<td>";
      $objectName = autoName($this->fields["name"], "name", ($template === "newcomp"),
                             $this->getType(), $this->fields["entities_id"]);
      Html::autocompletionTextField($this, 'name', array('value' => $objectName));
      echo "</td>";

      echo "<td>".$LANG['common'][25].($template?"*":"")."&nbsp;:</td>";
      echo "<td>";
      echo "<textarea cols='45' rows='".($rowspan+3)."' name='content' >".$this->fields["content"]."</textarea>";
      echo "</td>";

      echo "</tr>\n";


      $this->showFormButtons($options);
      $this->addDivForTabs();

      return true;
   }


   /**
    * Return the SQL command to retrieve linked object
    *
    * @return a SQL command which return a set of (itemtype, items_id)
    */
   /*8function getSelectLinkedItem() {

      return "SELECT `itemtype`, `items_id`
              FROM `glpi_computers_items`
              WHERE `computers_id` = '" . $this->fields['id']."'";
   }*/


   function getSearchOptions() {
      global $LANG,$CFG_GLPI;

      $tab = array();
      $tab['common'] = $LANG['common'][32];

      $tab[1]['table']         = $this->getTable();
      $tab[1]['field']         = 'name';
      $tab[1]['name']          = $LANG['common'][16];
      $tab[1]['datatype']      = 'itemlink';
      $tab[1]['itemlink_type'] = $this->getType();
      $tab[1]['massiveaction'] = false; // implicit key==1

      $tab[2]['table']         = $this->getTable();
      $tab[2]['field']         = 'id';
      $tab[2]['name']          = $LANG['common'][2];
      $tab[2]['massiveaction'] = false; // implicit field is id

      return $tab;
   }


   /*//SUPRISERVICE*/
   function getTabNameForItem(CommonGLPI $item, $withtemplate=0) {
      global $LANG;

      // ABA de Itens de Kit de Suprimento
      return 'Itens';
   }

   /*//SUPRISERVICE*/
   static function showForCartridge_Kit_Supridesk(Cartridge_Kit_Supridesk $carkit, $show_old=0) {
      global $DB, $CFG_GLPI, $LANG;

      $uID = $carkit->getField('id');
      /*if (!$carkit->can($uID,'r')) {
         return false;
      }*/
      //$canedit = $user->can($uID,'w');

      // Suprimentos da Listagem Principal
      $query = "	SELECT
                     CI.id,
                     CI.name,
                     CI.ref,
                     CKI.quantidade
                  FROM
                     supridesk_cartridges_kits CK,
                     supridesk_cartridges_kits_items CKI,
                     glpi_cartridgeitems CI
                  WHERE
                     CK.id = CKI.kits_supri_id
                     AND CKI.cartridgeitem_id = CI.id
                     AND CK.id = {$uID}
                  ORDER BY
                     CI.name, CI.ref";
      $result = $DB->query($query);
      $number = $DB->numrows($result);
      $i = 0;

      echo "<div class='spaced'>";
      echo "<form name='formKit' method='post' action=\"".$CFG_GLPI["root_doc"]."/front/cartridge_kit_supridesk.form.php\">";
      echo "<table width='100%' class='tab_cadre_fixe'>";
      echo "<tr>";
      echo "<th colspan='3'>Itens deste Kit&nbsp;:</th>";
      echo "</tr>";

      echo "<tr>";
		echo "<th width='60%'>".$LANG['cartridges'][1]."</th>";
		echo "<th width='20%'>Quantidade</th>";
      echo "<th width='20%'>Remover</th>";
		echo "</tr>";

      if ($number > 0) {
         while ($i < $number) {
            $ID         = $DB->result($result, $i, "id");
            $name       = $DB->result($result, $i, "name");
            $ref        = $DB->result($result, $i, "ref");
            $fullname   = sprintf("%s - %s", $name, $ref);
            $qtd        = $DB->result($result, $i, "quantidade");

            echo "<tr class='tab_bg_1'>";
            echo "<td>$fullname</td>";
            echo "<td class='center'>$qtd</td>";
            echo "<td class='tab_bg_2 center b'>";
            //echo "Chamado:";
            echo "<select name='sRemove_{$ID}' size=1>";
            echo "<option value='0'>0</option>";
            for($j = 1; $j <= $qtd; $j++)
            {
               echo "<option value='$j'>$j</option>";
            }
            echo "</select>";
            echo "</td></tr>";

            $i++;
         }
      }

      if (Session::haveRight("cartridge_kits", "w")) {
         if ($number > 0) {
            echo "<tr class='tab_bg_1'>";
            //echo "<td>&nbsp;</td>";
            echo "<td class='tab_bg_2 center'>&nbsp;</td>";
            //echo "<td class='tab_bg_2 center'>&nbsp;</td>";
            echo "<td class='tab_bg_2 center'>";
            echo "<input type='hidden' id='rmvT' name='rmvT' value='-1'>";
            echo "<input type='button' name='removerTodos' value=\"".$LANG['custom_cartridges'][5]."\" onclick='document.formKit.rmvT.value=1;document.formKit.submit();' class='submit'>";
            echo "</td>";
            echo "<td class='tab_bg_2 center'>";
            echo "<input type='button' name='removerSelecionados' value=\"".$LANG['custom_cartridges'][6]."\" onclick='document.formKit.rmvT.value=2;document.formKit.submit();' class='submit'>";
            echo "</td>";
            echo "</tr>";
         } else {
            // Se não existirem cartuchos Em Trânsito para este analista
            echo "<tr class='tab_bg_1'>";
            echo "<td colspan='5' class='center'><b>Não existem itens para este kit.</b></td>";
            echo "</tr>";
         }

         // SUPRISERVICE
         // Inserção de ítens - Chamado
         echo "<tr>";
         echo "<td colspan='6' class='tab_bg_2' width='30%' height='30' align='left'>";
         echo "Consumível: <input type='hidden' name='uID' value='$uID'>";

			$rand = mt_rand();
			$opt = array(
							'value' => '__VALUE__',
							'myname' => 'cartridgeitems_id',
							'comments' => false,
                     'onkeydown' => 'return false',
							'rand' => $rand
				 );

         $toupdate = array();
			$moreparams = array(	'value' => '__VALUE__' );

			$toupdate[] = array('value_fieldname' => 'value',
												  'to_update'  => "show_quantities",
												  'url'        => $CFG_GLPI["root_doc"]."/ajax/dropdownCartridgeKit.php",
													'moreparams' => $moreparams);
			$opt['toupdate'] = $toupdate;

         Dropdown::show('CartridgeItem', $opt);

			echo " Quantidade: ";
			echo "<span id='show_quantities'></span>";


         echo "&nbsp;&nbsp;<input type='submit' name='addCartucho' value=\" ".$LANG['buttons'][8]." \" class='submit'>";
         echo "</td>";
         echo "</tr>";
      }
      echo "</table>";
      echo "</form>";
   }

   /*//SUPRISERVICE*/
   static function displayTabContentForItem(CommonGLPI $item, $tabnum=1, $withtemplate=0) {

      switch ($item->getType()) {
			
         case 'Cartridge_Kit_Supridesk' :
            self::showForCartridge_Kit_Supridesk($item);
            return true;
      }
   }

   /*//SUPRISERVICE*/
   function addCartuchoKit($analistaAddCartridge,$cartridgeitems_id,$uID) {
      global $DB;

		//Se não tiver a quantidade necessária do cartucho, não adiciona
		$unuseds = Cartridge::getUnusedNumber( $cartridgeitems_id );
		if ( $amount > $unuseds )
			return false;

      // Se o cartucho já pertencer ao kit, atualiza, do contrário, adiciona
      $query = "SELECT
                  quantidade
                FROM 
                  supridesk_cartridges_kits_items
                WHERE 
                  kits_supri_id = {$uID}
                  AND cartridgeitem_id = {$cartridgeitems_id}";

      $result  = $DB->query($query);
      $row = $DB->fetch_assoc($result);
      
      if($row['quantidade']) {
         $query2 = "UPDATE
                       supridesk_cartridges_kits_items
                    SET
                       quantidade = quantidade + {$analistaAddCartridge}
                    WHERE
                       kits_supri_id = {$uID}
                       AND cartridgeitem_id = {$cartridgeitems_id}";
      } else {
         $query2 = "INSERT INTO
                       supridesk_cartridges_kits_items
                       (kits_supri_id,cartridgeitem_id,quantidade)
                    VALUES
                       ({$uID},{$cartridgeitems_id},{$analistaAddCartridge})";
      }
      $DB->query($query2);

      $ci = new CartridgeItem();
		$ci->getFromDB($cartridgeitems_id);
		$cartridgeitem_name = $ci->fields['name'] . " - " . $ci->fields['ref'];

      $ck = new Cartridge_Kit_Supridesk();
		$ck->getFromDB($uID);
		$kit_name = $ck->fields['name'];

      $amount     = $analistaAddCartridge;
		$ext        = $amount > 1 ? "s" : "";
		$ext2       = $amount > 1 ? "is" : "l";
		$changes[0] = $cartridgeitems_id;
		$changes[1] = '';
		$changes[2] = "adicionado{$ext} {$amount} consumíve{$ext2} '{$cartridgeitem_name}' ao kit '{$kit_name}'.";
		Log::history( $uID, 'Cartridge_Kit_Supridesk', $changes, 'Cartridge_Kit_Supridesk', Log::HISTORY_LOG_SIMPLE_MESSAGE);

		if (mysql_errno())
			return false;

      return true;
   }

   /*//SUPRISERVICE*/
   /**Remove cartucho(s) do estoque do analista - Chamado
   *
   * Remove do analista $user_id a quantidade $amount
   *   do cartucho $cartridgeitems_id
   *
   *@param $user_id integer: ID do analista
   *@param $cartridgeitems_id integer: cartridge type identifier
   *@param $amount integer: quantidade de cartuchos
   *
   *@return boolean : true for success
   **/
   function delCartuchoKit($kit_id, $cartridgeitems_id = NULL, $amount = NULL) {
      global $DB;

		if ( $cartridgeitems_id == NULL ) { // Remove Todos
         $query = "DELETE FROM
                      supridesk_cartridges_kits_items
						 WHERE
                      kits_supri_id = {$kit_id}";
			$DB->query($query);

         if (mysql_errno())
				return false;

         $ck = new Cartridge_Kit_Supridesk();
         $ck->getFromDB($kit_id);
         $kit_name = $ck->fields['name'];

         $changes[2] = "removidos todos os consumíveis do kit '{$kit_name}'.";
         Log::history( $kit_id, 'Cartridge_Kit_Supridesk', $changes, 'CartridgeItem', Log::HISTORY_LOG_SIMPLE_MESSAGE);
		} else { // Remove Selecionados
			$query = "SELECT
                      id,
                      kits_supri_id,
                      cartridgeitem_id,
                      quantidade
                   FROM
                      supridesk_cartridges_kits_items
						 WHERE
                      kits_supri_id = {$kit_id}
                      AND cartridgeitem_id = {$cartridgeitems_id}";
			$result = $DB->query($query);
         
			if (gettype($result) == "resource") {
				if (mysql_num_rows($result) != 0 ) {
					$row  = $DB->fetch_assoc($result);

               /*
               $ci   = new CartridgeItem();
               $ci->getFromDB($row['cartridgeitem_id']);
               $cartridgeitem_name = $ci->fields['name'] . " - " . $ci->fields['ref'];
               
               $ck   = new Cartridge_Kit();
               $ck->getFromDB($kit_id);
               $kit_name = $ck->fields['name'];

               //$amount     = $row['quantidade'];
               $ext        = $amount > 1 ? "s" : "";
               $ext2       = $amount > 1 ? "is" : "l";
               $changes[0] = $row['cartridgeitems_id'];
               $changes[1] = '';
               $changes[2] = "removido{$ext} {$amount} consumíve{$ext2} '{$cartridgeitem_name}' do kit '{$kit_name}'.";
               Log::history( $kit_id, 'Cartridge_Kit', $changes, 'CartridgeItem', Log::HISTORY_LOG_SIMPLE_MESSAGE);
               */
				}
			}
         

			if (mysql_errno())
				return false;

         $cartridge_ids = implode(",", $data);

         // Se a quantidade a remover for igua a quantidade do item no kit, deleta
         if ($row['quantidade'] == $amount) {
            $query_update = "	DELETE FROM
                                 supridesk_cartridges_kits_items
                              WHERE
                                 kits_supri_id = {$kit_id}
                                 AND cartridgeitem_id = {$cartridgeitems_id}";
            //echo $query_update."<br>";
            $DB->query($query_update);
         } else {
            $query_update = "	UPDATE
                                 supridesk_cartridges_kits_items
                              SET
                                 quantidade = quantidade - {$amount}
                              WHERE
                                 kits_supri_id = {$kit_id}
                                 AND cartridgeitem_id = {$cartridgeitems_id}";
            //echo $query_update."<br>";
            $DB->query($query_update);
         }

         
			if (mysql_errno())
				return false;

         $ci = new CartridgeItem();
         $ci->getFromDB($cartridgeitems_id);
         $cartridgeitem_name = $ci->fields['name'] . " - " . $ci->fields['ref'];

         $ck = new Cartridge_Kit_Supridesk();
         $ck->getFromDB($kit_id);
         $kit_name = $ck->fields['name'];

         $ext        = $amount > 1 ? "s" : "";
         $ext2       = $amount > 1 ? "is" : "l";
         $changes[0] = $cartridgeitems_id;
         $changes[1] = '';
         $changes[2] = "removido{$ext} {$amount} consumíve{$ext2} '{$cartridgeitem_name}' do kit '{$kit_name}'.";
         //print_r($changes);
         //die("Atualiza");
         Log::history( $kit_id, 'Cartridge_Kit_Supridesk', $changes, 'CartridgeItem', Log::HISTORY_LOG_SIMPLE_MESSAGE);
		}

      return true;
   }
}

?>
