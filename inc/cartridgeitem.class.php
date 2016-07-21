<?php
/*
 * @version $Id: cartridgeitem.class.php 19244 2012-09-11 18:17:25Z remi $
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


/**
 * CartridgeItem Class
 * This class is used to manage the various types of cartridges.
 * \see Cartridge
 */
class CartridgeItem extends CommonDBTM {
   // From CommonDBTM
   protected $forward_entity_to = array('Cartridge', 'Infocom');
   
   public $dohistory = true;

   static function getTypeName($nb=0) {
      global $LANG;

      if ($nb>1) {
         return $LANG['cartridges'][2];
      }
      return $LANG['cartridges'][1];
   }


   function canCreate() {
      return Session::haveRight('cartridge', 'w');
   }


   function canView() {
      return Session::haveRight('cartridge', 'r');
   }


   /**
    * Get The Name + Ref of the Object
    *
    * @param $with_comment add comments to name
    *
    * @return String: name of the object in the current language
    */
   function getName($with_comment=0) {

      $toadd = "";
      if ($with_comment) {
         $toadd = "&nbsp;".$this->getComments();
      }

      if (isset($this->fields["name"]) && !empty($this->fields["name"])) {
         $name = $this->fields["name"];

         if (isset($this->fields["ref"]) && !empty($this->fields["ref"])) {
            $name .= " - ".$this->fields["ref"];
         }
         return $name.$toadd;
      }
      return NOT_AVAILABLE;
   }


   function cleanDBonPurge() {
      global $DB;

      // Delete cartridges
      $query = "DELETE
                FROM `glpi_cartridges`
                WHERE `cartridgeitems_id` = '".$this->fields['id']."'";
      $DB->query($query);
      // Delete all cartridge assoc
      $query2 = "DELETE
                 FROM `glpi_cartridgeitems_printermodels`
                 WHERE `cartridgeitems_id` = '".$this->fields['id']."'";
      $result2 = $DB->query($query2);
   }
   
      
    function post_deleteFromDB() {

      if (isset($this->input['_no_history']) && $this->input['_no_history']) {
         return false;
      }
      $dev = new $this->input['_itemtype']();

      $dev->getFromDB($this->fields[$dev->getForeignKeyField()]);
      $changes[0] = 0;
      $changes[1] = addslashes($dev->getName());
      $changes[2] = '';
      Log::history($this->fields['id'], 'CartridgeItem', $changes, get_class($dev),
                   Log::HISTORY_DELETE_DEVICE);
   }

   function post_getEmpty() {

      $this->fields["alarm_threshold"] = EntityData::getUsedConfig("cartriges_alert_repeat",
                                                                   $this->fields["entities_id"],
                                                                   "default_alarm_threshold", 10);
   }


   function defineTabs($options=array()) {
      global $LANG;

      $ong = array();
      $this->addStandardTab('Cartridge', $ong, $options);
      $this->addStandardTab('PrinterModel', $ong, $options);
      $this->addStandardTab('Infocom', $ong, $options);
      $this->addStandardTab('Document',$ong, $options);
      $this->addStandardTab('Link',$ong, $options);
      $this->addStandardTab('Note',$ong, $options);
      $this->addStandardTab('Log', $ong, $options);

      return $ong;
   }


   ///// SPECIFIC FUNCTIONS

   /**
   * Count cartridge of the cartridge type
   *
   *@return number of cartridges
   **/
   static function getCount() {
      global $DB;

      $query = "SELECT *
                FROM `glpi_cartridges`
                WHERE `cartridgeitems_id` = '".$this->fields["id"]."'";

      if ($result = $DB->query($query)) {
         $number = $DB->numrows($result);
         return $number;
      }
      return false;
   }


   /**Add a compatible printer type for a cartridge type
   *
   * Add the compatible printer $type type for the cartridge type $tID
   *
   *@param $cartridgeitems_id integer: cartridge type identifier
   *@param printermodels_id integer: printer type identifier
   *
   *@return boolean : true for success
   **/
   function addCompatibleType($cartridgeitems_id, $printermodels_id) {
      global $DB;

      if ($cartridgeitems_id>0 && $printermodels_id>0) {
         $query = "INSERT INTO `glpi_cartridgeitems_printermodels`
                     (`cartridgeitems_id`, `printermodels_id`)
                     VALUES ('$cartridgeitems_id', '$printermodels_id');";

         if ($result = $DB->query($query) && $DB->affected_rows()>0) {
            return true;
         }
      }
      return false;
   }
   
   
   
   
   function post_updateItem($history=1) {       
      
      if (!$history
          || (isset($this->input['_no_history']) &&  $this->input['_no_history'])
          || !in_array('specificity',$this->updates)) {
         return false;
      }
      
       
      $changes[0] = 0;
      $changes[1] = addslashes($this->oldvalues['specificity']);
      $changes[2] = $this->fields['specificity'];
      // history log
      Log::history($this->fields['cartridges_id'], 'CartridgeItem', $changes, $this->input['_itemtype'],
                   Log::HISTORY_UPDATE_DEVICE);
   }


	/*//SUPRISERVICE*/
   /**Adiciona a quantidade de cartuchos ao estoque do analista - Chamado
   *
   * Adiciona a quantidade $amount do cartucho $cartridgeitems_id
   *   para o analista $user_id
   *
   *@param $amount integer: quantidade de cartuchos
   *@param $cartridgeitems_id integer: cartridge type identifier
   *@param $user_id integer: ID do analista
   *
   *@return boolean : true for success
   **/
   function addCartuchoAnalista($amount, $cartridgeitems_id, $user_id) {
      global $DB;

		//Se não tiver a quantidade necessária do cartucho, não adiciona
		$unuseds = Cartridge::getUnusedNumber( $cartridgeitems_id );

		if ( $amount > $unuseds )
			return false;

      $query = "SELECT id
                FROM `glpi_cartridges`
                WHERE `cartridgeitems_id` = $cartridgeitems_id
                       AND `date_use` IS NULL
                       AND `aplicado_por` IS NULL
							  AND alocado_para IS NULL
					 LIMIT $amount";

      $result = $DB->query($query);
		$data = Array();
		if (gettype($result) == "resource") {
			 if (mysql_num_rows($result) != 0 ) {
				  while ($row = $DB->fetch_assoc($result)) {
						$data[] = $row['id'];
				  }
			 }
		}
		if (mysql_errno())
			return false;

		$ci = new CartridgeItem();
		$ci->getFromDB($cartridgeitems_id);
		$cartridgeitem_name = $ci->fields['name'] . " - " . $ci->fields['ref'];

		$ext = $amount > 1 ? "s" : "";
		$ext2 = $amount > 1 ? "is" : "l";
		$changes[0] = $cartridgeitems_id;
		$changes[1] = '';
		$changes[2] = "adicionado{$ext} {$amount} consumíve{$ext2} '{$cartridgeitem_name}' de chamado.";
		Log::history( $user_id, 'User', $changes, 'CartridgeItem', Log::HISTORY_LOG_SIMPLE_MESSAGE);

		$cartridge_ids = implode(",", $data);
		$query_update = "	UPDATE glpi_cartridges
								SET alocado_para = {$user_id}, alocado_tipo = 'c'
								WHERE id IN ({$cartridge_ids})";
                        
      $DB->query($query_update);

		if (mysql_errno())
			return false;

      return true;
   }
   
   
   /*//SUPRISERVICE*/
   /**Adiciona a quantidade de cartuchos ao estoque do analista - Chamado
   *
   * Adiciona a quantidade $amount do cartucho $cartridgeitems_id
   *   para o analista $user_id
   *
   *@param $amount integer: quantidade de cartuchos
   *@param $cartridgeitems_id integer: cartridge type identifier
   *@param $user_id integer: ID do analista
   *
   *@return boolean : true for success
   **/
   function addEquipamentoAnalista($amount, $cartridgeitems_id, $user_id) {
      global $DB;

		//Se não tiver a quantidade necessária do cartucho, não adiciona
		$unuseds = Cartridge::getUnusedNumber( $cartridgeitems_id );

		if ( $amount > $unuseds )
			return false;

      $query = "SELECT id
                FROM `glpi_cartridges`
                WHERE `cartridgeitems_id` = $cartridgeitems_id
                       AND `date_use` IS NULL
                       AND `aplicado_por` IS NULL
							  AND alocado_para IS NULL
					 LIMIT $amount";

      $result = $DB->query($query);
		$data = Array();
		if (gettype($result) == "resource") {
			 if (mysql_num_rows($result) != 0 ) {
				  while ($row = $DB->fetch_assoc($result)) {
						$data[] = $row['id'];
				  }
			 }
		}
		if (mysql_errno())
			return false;

		$ci = new CartridgeItem();
		$ci->getFromDB($cartridgeitems_id);
		$cartridgeitem_name = $ci->fields['name'] . " - " . $ci->fields['ref'];

		$ext = $amount > 1 ? "s" : "";
		$ext2 = $amount > 1 ? "is" : "l";
		$changes[0] = $cartridgeitems_id;
		$changes[1] = '';
		$changes[2] = "adicionado{$ext} {$amount} consumíve{$ext2} '{$cartridgeitem_name}' de chamado.";
		Log::history( $user_id, 'User', $changes, 'CartridgeItem', Log::HISTORY_LOG_SIMPLE_MESSAGE);

		$cartridge_ids = implode(",", $data);
		$query_update = "	UPDATE glpi_cartridges
								SET alocado_para = {$user_id}, alocado_tipo = 'c'
								WHERE id IN ({$cartridge_ids})";
                        
      $DB->query($query_update);

		if (mysql_errno())
			return false;

      return true;
   }

   /*//SUPRISERVICE*/
   /**Adiciona a quantidade de cartuchos ao estoque do analista - Reserva
   *
   * Adiciona a quantidade $amount do cartucho $cartridgeitems_id
   *   para o analista $user_id
   *
   *@param $amount integer: quantidade de cartuchos
   *@param $cartridgeitems_id integer: cartridge type identifier
   *@param $user_id integer: ID do analista
   *
   *@return boolean : true for success
   **/
   function addCartuchoAnalistaReserva($amount, $cartridgeitems_id, $user_id) {
        global $DB;

		//Se não tiver a quantidade necessária do cartucho, não adiciona
        $unuseds = Cartridge::getUnusedNumber( $cartridgeitems_id );
        if ( $amount > $unuseds )
            return false;

        $query = "  SELECT id
                    FROM `glpi_cartridges`
                     WHERE `cartridgeitems_id` = $cartridgeitems_id
                        AND `date_use` IS NULL
                        AND `aplicado_por` IS NULL
                        AND alocado_para IS NULL
                    LIMIT $amount";

        $result = $DB->query($query);
        $data = Array();
        if (gettype($result) == "resource") {
            if (mysql_num_rows($result) != 0 ) {
                while ($row = $DB->fetch_assoc($result)) {
                    $data[] = $row['id'];
                }
            }
        }
        if (mysql_errno())
                return false;

        $ci = new CartridgeItem();
        $ci->getFromDB($cartridgeitems_id);
        $cartridgeitem_name = $ci->fields['name'] . " - " . $ci->fields['ref'];

        $ext = $amount > 1 ? "s" : "";
        $ext2 = $amount > 1 ? "is" : "l";
        $changes[0] = $cartridgeitems_id;
        $changes[1] = '';
        $changes[2] = "adicionado{$ext} {$amount} consumíve{$ext2} '{$cartridgeitem_name}' de reserva.";
        Log::history( $user_id, 'User', $changes, 'CartridgeItem', Log::HISTORY_LOG_SIMPLE_MESSAGE);

        $cartridge_ids = implode(",", $data);
        $query_update = "UPDATE glpi_cartridges
                        SET alocado_para = {$user_id}, alocado_tipo = 'r'
                        WHERE id IN ({$cartridge_ids})";
        $DB->query($query_update);

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
   function delCartuchoAnalistaC($user_id, $cartridgeitems_id = NULL, $amount = NULL) {
      global $DB;

		if ( $cartridgeitems_id == NULL )
		{
			$query = "select c.*, count(*) as amount from glpi_cartridges c
							WHERE alocado_para = {$user_id}
							AND `date_use` IS NULL
                     AND alocado_tipo = 'c'
							group by cartridgeitems_id";
			$result = $DB->query($query);

			if (gettype($result) == "resource") {
				if (mysql_num_rows($result) != 0 ) {
					while ($row = $DB->fetch_assoc($result)) {
						$ci = new CartridgeItem();
						$ci->getFromDB($row['cartridgeitems_id']);
						$cartridgeitem_name = $ci->fields['name'] . " - " . $ci->fields['ref'];
						$ext = $row['amount'] > 1 ? "s" : "";
						$ext2 = $row['amount'] > 1 ? "is" : "l";
						$changes[0] = $row['cartridgeitems_id'];
						$changes[1] = '';
						$changes[2] = "removido{$ext} {$row['amount']} consumíve{$ext2} '{$cartridgeitem_name}' de chamado.";
						Log::history( $user_id, 'User', $changes, 'CartridgeItem', Log::HISTORY_LOG_SIMPLE_MESSAGE);
					}
				}
			}

			$query_update = "	UPDATE glpi_cartridges
									SET alocado_para = NULL
									WHERE alocado_para = {$user_id}
                           AND alocado_tipo = 'c'
									AND `date_use` IS NULL";
			$DB->query($query_update);

			if (mysql_errno())
				return false;
		}
		else
		{
			$query = "SELECT id
						 FROM `glpi_cartridges`
						 WHERE `cartridgeitems_id` = $cartridgeitems_id
								  AND `date_use` IS NULL
								  AND alocado_para = $user_id
                          AND aplicado_por IS NULL
                          AND alocado_tipo = 'c'
						 LIMIT $amount";

			$result = $DB->query($query);
			$data = Array();
			if (gettype($result) == "resource") {
				 if (mysql_num_rows($result) != 0 ) {
					  while ($row = $DB->fetch_assoc($result)) {
							$data[] = $row['id'];
					  }
				 }
			}
			if (mysql_errno())
				return false;

			$cartridge_ids = implode(",", $data);
			$query_update = "	UPDATE glpi_cartridges
									SET alocado_para = NULL
									WHERE id IN ({$cartridge_ids})";
			$DB->query($query_update);

			$ci = new CartridgeItem();
			$ci->getFromDB($cartridgeitems_id);
			$cartridgeitem_name = $ci->fields['name'] . " - " . $ci->fields['ref'];

			$ext = $amount > 1 ? "s" : "";
			$ext2 = $amount > 1 ? "is" : "s";
			$changes[0] = $cartridgeitems_id;
			$changes[1] = '';
			$changes[2] = "removido{$ext} {$amount} consumíve{$ext2} '{$cartridgeitem_name}' em trânsito de chamado.";
			Log::history( $user_id, 'User', $changes, 'CartridgeItem', Log::HISTORY_LOG_SIMPLE_MESSAGE);

			if (mysql_errno())
				return false;
		}

      return true;
   }


   /*//SUPRISERVICE*/
   /**Remove cartucho(s) do estoque do analista - Reserva
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
   function delCartuchoAnalistaR($user_id, $cartridgeitems_id = NULL, $amount = NULL) {
      global $DB;

		if ( $cartridgeitems_id == NULL )
		{
			$query = "select c.*, count(*) as amount from glpi_cartridges c
							WHERE alocado_para = {$user_id}
							AND `date_use` IS NULL
                     AND alocado_tipo = 'r'
							group by cartridgeitems_id";
			$result = $DB->query($query);

			if (gettype($result) == "resource") {
				if (mysql_num_rows($result) != 0 ) {
					while ($row = $DB->fetch_assoc($result)) {
						$ci = new CartridgeItem();
						$ci->getFromDB($row['cartridgeitems_id']);
						$cartridgeitem_name = $ci->fields['name'] . " - " . $ci->fields['ref'];
						$ext = $row['amount'] > 1 ? "s" : "";
						$ext2 = $row['amount'] > 1 ? "is" : "l";
						$changes[0] = $row['cartridgeitems_id'];
						$changes[1] = '';
						$changes[2] = "removido{$ext} {$row['amount']} consumíve{$ext2} '{$cartridgeitem_name}' de reserva.";
						Log::history( $user_id, 'User', $changes, 'CartridgeItem', Log::HISTORY_LOG_SIMPLE_MESSAGE);
					}
				}
			}

			$query_update = "	UPDATE glpi_cartridges
									SET alocado_para = NULL
									WHERE alocado_para = {$user_id}
                           AND alocado_tipo = 'r'
									AND `date_use` IS NULL";
			$DB->query($query_update);

			if (mysql_errno())
				return false;
		}
		else
		{
			$query = "SELECT id
						 FROM `glpi_cartridges`
						 WHERE `cartridgeitems_id` = $cartridgeitems_id
								  AND `date_use` IS NULL
								  AND alocado_para = $user_id
                          AND aplicado_por IS NULL
                          AND alocado_tipo = 'r'
						 LIMIT $amount";

			$result = $DB->query($query);
			$data = Array();
			if (gettype($result) == "resource") {
				 if (mysql_num_rows($result) != 0 ) {
					  while ($row = $DB->fetch_assoc($result)) {
							$data[] = $row['id'];
					  }
				 }
			}
			if (mysql_errno())
				return false;

			$cartridge_ids = implode(",", $data);
			$query_update = "	UPDATE glpi_cartridges
									SET alocado_para = NULL, alocado_tipo = NULL
									WHERE id IN ({$cartridge_ids})";
			$DB->query($query_update);

			$ci = new CartridgeItem();
			$ci->getFromDB($cartridgeitems_id);
			$cartridgeitem_name = $ci->fields['name'] . " - " . $ci->fields['ref'];

			$ext = $amount > 1 ? "s" : "";
			$ext2 = $amount > 1 ? "is" : "s";
			$changes[0] = $cartridgeitems_id;
			$changes[1] = '';
			$changes[2] = "removido{$ext} {$amount} consumíve{$ext2} '{$cartridgeitem_name}' em trânsito de reserva.";
			Log::history( $user_id, 'User', $changes, 'CartridgeItem', Log::HISTORY_LOG_SIMPLE_MESSAGE);

			if (mysql_errno())
				return false;
		}

      return true;
   }


   /*//SUPRISERVICE*/
   /**Remove cartucho(s) do estoque do analista
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
   function delCartuchoAnalistaAplicado($user_id, $cartridgeitems_id = NULL, $amount = NULL) {
        global $DB;

        if ( $cartridgeitems_id == NULL )
        {
            $query = "  select c.*, count(*) as amount from glpi_cartridges c
                        WHERE 
                            alocado_para = {$user_id}
                            AND `date_use` IS NULL
                        group by cartridgeitems_id";
            $result = $DB->query($query);

            if (gettype($result) == "resource") {
                if (mysql_num_rows($result) != 0 ) {
                    while ($row = $DB->fetch_assoc($result)) {
                        $ci = new CartridgeItem();
                        $ci->getFromDB($row['cartridgeitems_id']);
                        $cartridgeitem_name = $ci->fields['name'] . " - " . $ci->fields['ref'];
                        $ext = $row['amount'] > 1 ? "s" : "";
                        $ext2 = $row['amount'] > 1 ? "is" : "s";
                        $changes[0] = $row['cartridgeitems_id'];
                        $changes[1] = '';
                        $changes[2] = "removido{$ext} {$row['amount']} consumíve{$ext2} '{$cartridgeitem_name}' que estavam aplicados";
                        Log::history( $user_id, 'User', $changes, 'CartridgeItem', Log::HISTORY_LOG_SIMPLE_MESSAGE);
                    }
                }
            }

        $query_update = "UPDATE
                            glpi_cartridges
                        SET
                            alocado_para = {$user_id},
                            aplicado_por = NULL,
                            aplicado_data = NULL,
                            aplicado_chamado = NULL
                        WHERE
                            aplicado_por = {$user_id}
                            AND `date_use` IS NULL";
        $DB->query($query_update);

        if (mysql_errno())
            return false;
        }
        else
        {
            $valores = explode("_", $cartridgeitems_id);
            $cartridgeitem_id = $valores[0];
            $aplicado_data    = $valores[1];
         
         // Remove cartuchos aplicados selecionados
            $query = "SELECT id
                    FROM `glpi_cartridges`
                    WHERE `cartridgeitems_id` = $cartridgeitem_id
                            AND `date_use` IS NULL
                            AND aplicado_por = $user_id
                            AND `aplicado_data` = '$aplicado_data'
                    LIMIT $amount";

            $result = $DB->query($query);
            $data = Array();
            if (gettype($result) == "resource") {
                if (mysql_num_rows($result) != 0 ) {
                    while ($row = $DB->fetch_assoc($result)) {
                        $data[] = $row['id'];
                    }
                }
            }
            if (mysql_errno())
                return false;

            $cartridge_ids = implode(",", $data);
            $query_update = "UPDATE
                                glpi_cartridges
                            SET 
                                alocado_para = {$user_id},
                                aplicado_por = NULL,
                                aplicado_data = NULL,
                                aplicado_chamado = NULL
                            WHERE
                                id IN ({$cartridge_ids})";

            $DB->query($query_update);

            $ci = new CartridgeItem();
            $ci->getFromDB($cartridgeitems_id);
            $cartridgeitem_name = $ci->fields['name'] . " - " . $ci->fields['ref'];

            $ext = $amount > 1 ? "s" : "";
            $ext2 = $amount > 1 ? "is" : "s";
            $changes[0] = $cartridgeitems_id;
            $changes[1] = '';
            $changes[2] = "removido{$ext} {$amount} consumíve{$ext2} '{$cartridgeitem_name}' que estava aplicado.";
            Log::history( $user_id, 'User', $changes, 'CartridgeItem', Log::HISTORY_LOG_SIMPLE_MESSAGE);

            if (mysql_errno())
                return false;
        }

      return true;
   }

   /*//SUPRISERVICE*/
   /**Marca cartucho(s) em trânsito como aplicado(s)
   *
   * Coloca campo aplicado_por igual ao ID do analista
   *   do cartucho $cartridgeitems_id
   *
   *@param $user_id integer: ID do analista
   *@param $cartridgeitems_id integer: cartridge type identifier
   *@param $amount integer: quantidade de cartuchos
   *
   *@return boolean : true for success
   **/
   function aplicaCartucho($user_id, $cartridgeitems_id = NULL, $amount = NULL,
           $data_aplicado, $aplica_chamado = NULL ) {
      global $DB;
            
      // Verifica se já existe este tipo de consumível alocado para o analista em questão
      // que já foi aplicado na mesma data.
      // Se sim, incrementa a quantidade e adiciona o valor de chamado
      $qry_verifica_aplicados = "
                                SELECT
                                    *
                                FROM
                                    glpi_cartridges
                                WHERE
                                    cartridgeitems_id = $cartridgeitems_id
                                    AND date_use IS NULL
                                    AND alocado_para = $user_id
                                    AND aplicado_por = $user_id
                                    AND aplicado_data = '$data_aplicado'
                                ";
      $res_verifica_aplicados = $DB->query($qry_verifica_aplicados);
      $num_verifica_aplicados = $DB->numrows($res_verifica_aplicados);

      if ($num_verifica_aplicados > 0 ) {
         // Existem itens aplicados nesta data

         // Monta o valor que será inserido como chamado
         $row_verifica_aplicados = $DB->fetch_assoc($res_verifica_aplicados);
         $chamado    = $row_verifica_aplicados['aplicado_chamado'];
         $ultimo_car = substr($chamado, strlen($chamado)-1,1);
         if ($chamado != '') {
            if (strpos($chamado,',') > 0) {
               $separador = ',';
            } elseif (strpos($chamado,';') > 0) {
                $separador = ";";
            } elseif (strpos($chamado,'-') > 0) {
                $separador = "-";
            } else {
               $separador = " ";
            }

            if ($ultimo_car == $separador)
               $separador = "";
            $aplica_chamado = $chamado . $separador . $aplica_chamado;
         }
        
         $qry_atualiza_aplicados = "
                                UPDATE
                                    glpi_cartridges
                                SET
                                    aplicado_chamado = '$aplica_chamado'
                                WHERE
                                    cartridgeitems_id = $cartridgeitems_id
                                    AND date_use IS NULL
                                    AND alocado_para = $user_id
                                    AND aplicado_por = $user_id
                                    AND aplicado_data = '$data_aplicado'
                                   ";
         $DB->query($qry_atualiza_aplicados);
         //echo "$qry_atualiza_aplicados<br>";
         // Percorre os novos e adiciona

         // Percorre as unidades de cartuchos aplicados para o analista em questão e que
         // não estão instalados ou aplicados. Guarda em data[]
         /*$query = "SELECT
                     id
                   FROM
                     `glpi_cartridges`
                   WHERE
                     `cartridgeitems_id` = $cartridgeitems_id
                     AND `date_use` IS NULL
                     AND alocado_para = $user_id
                     AND aplicado_por IS NULL
                   ORDER BY
                     alocado_tipo
                   LIMIT
                     $amount";
         //echo "$query<br>";
*/

      }// else {
         // Não existem itens aplicados nesta data

         // Percorre as unidades de cartuchos alocados para o analista em questão e que
         // não estão instalados ou aplicados. Guarda em data[]
         $query = "SELECT
                     id
                   FROM
                     `glpi_cartridges`
                   WHERE
                     `cartridgeitems_id` = $cartridgeitems_id
                     AND `date_use` IS NULL
                     AND alocado_para = $user_id
                     AND aplicado_por IS NULL
                   ORDER BY
                     alocado_tipo
                   LIMIT
                     $amount";

         $result = $DB->query($query);
         $data = Array();
         if (gettype($result) == "resource") {
             if (mysql_num_rows($result) != 0 ) {
                 while ($row = $DB->fetch_assoc($result)) {
                     $data[] = $row['id'];
                 }
             }
         }
         if (mysql_errno())
            return false;

         // Aplica as unidades armazenadas em data[]
         $cartridge_ids = implode(",", $data);
         $query_update = "	UPDATE
                              glpi_cartridges
                           SET
                              aplicado_por = $user_id,
                              aplicado_data = '$data_aplicado',
                              aplicado_chamado = '$aplica_chamado'
                           WHERE
                              id IN ({$cartridge_ids})";

         $DB->query($query_update);

         $ci = new CartridgeItem();
         $ci->getFromDB($cartridgeitems_id);
         $cartridgeitem_name = $ci->fields['name'] . " - " . $ci->fields['ref'];

         $ext = $amount > 1 ? "s" : "";
         $ext2 = $amount > 1 ? "is" : "s";
         $changes[0] = $cartridgeitems_id;
         $changes[1] = '';
         $changes[2] = "aplicado{$ext} {$amount} consumíve{$ext2} '{$cartridgeitem_name}' com data $data_aplicado para o{$ext} chamado{$ext} $aplica_chamado .";
         Log::history( $user_id, 'User', $changes, 'CartridgeItem', Log::HISTORY_LOG_SIMPLE_MESSAGE);

         if (mysql_errno())
            return false;

         return true;
      //}
   }

   /*//SUPRISERVICE*/
   /**Marca cartucho(s) em trânsito como aplicado(s)
   *
   * Coloca campo aplicado_por igual ao ID do analista
   *   do cartucho $cartridgeitems_id
   *
   *@param $user_id integer: ID do analista
   *@param $cartridgeitems_id integer: cartridge type identifier
   *@param $amount integer: quantidade de cartuchos
   *
   *@return boolean : true for success
   **/
   function aplicaKit($kitsel,$userID) {
      global $DB;

      // VERIFICA SE OS ITENS DO KIT EXISTEM NO ESTOQUE

      // Varre os itens do kit "$kitsel"
      $query_kititens = "SELECT
                            KI.id,
                            KI.kits_supri_id,
                            KI.cartridgeitem_id,
                            KI.quantidade,
                            K.id   KITID,
                            K.name KIT,
                            CI.name,
                            CI.ref,
                            CI.id CIID
                         FROM
                            supridesk_cartridges_kits_items KI,
                            supridesk_cartridges_kits K,
                            glpi_cartridgeitems CI
                         WHERE
                            K.id = KI.kits_supri_id
                            AND KI.cartridgeitem_id = CI.id
                            AND KI.kits_supri_id = $kitsel";

      $result_kititens = $DB->query($query_kititens);

      $_SESSION['ERRO_KIT']=null;
      if (gettype($result_kititens) == "resource") {
          if (mysql_num_rows($result_kititens) != 0 ) {
             // Varrendo os itens do kit
              while ($row = $DB->fetch_assoc($result_kititens)) {
                 // Verificando se existe no estoque
                  $query_qtd_item_estoque = "
                                   SELECT
                                      *
                                   FROM
                                      glpi_cartridges
                                   WHERE
                                      cartridgeitems_id = {$row['cartridgeitem_id']}
                                      AND date_use IS NULL
                                      AND date_out IS NULL
                                      AND alocado_para IS NULL";
                  //echo $query_qtd_item_estoque."<hr>";
                  $result_qtd_item_estoque = $DB->query($query_qtd_item_estoque);
                  $qtd_item_estoque = mysql_num_rows($result_qtd_item_estoque);
                
                  if ($qtd_item_estoque < $row['quantidade']){
                     // Se algum não existe, erro igual a 1
                     //$_SESSION['ERRO_KIT'][$row['CIID']]=$row['quantidade'];
                     $msg_erro = "Não existe(m) {$row['quantidade']} unidade(s) do item {$row['name']} - {$row['ref']}";
                     Session::addMessageAfterRedirect($msg_erro, false, ERROR);

                     // LOG DA OPERAÇÃO
                     $nome_kit               = $row['KIT'];
                     $qtd_item_kit           = $row['quantidade'];
                     $id_cartucho_item_kit   = $row['cartridgeitem_id'];
                     $fullname               = $row['name']." - ".$row['ref'];

                     $ext        = $qtd_item_kit > 1 ? "s" : "";
                     $ext2       = $qtd_item_kit > 1 ? "is" : "l";
                     $changes[0] = $id_cartucho_item_kit;
                     $changes[1] = '';
                     $changes[2] = "erro ao aplicar {$qtd_item_kit} consumíve{$ext2} '{$fullname}' referente ao kit de suprimentos '{$nome_kit}'.";
                     Log::history( $userID, 'User', $changes, 'CartridgeItem', Log::HISTORY_LOG_SIMPLE_MESSAGE);

                  } else {
                     //echo "Tem quantidade {$row['quantidade']} para item {$row['id']}<br>";
                     $query_upd_item = "UPDATE
                                          glpi_cartridges
                                        SET
                                          alocado_para = $userID,
                                          alocado_tipo = 'r'
                                        WHERE
                                          cartridgeitems_id = {$row['cartridgeitem_id']}
                                          AND date_use IS NULL
                                          AND date_out IS NULL
                                          AND alocado_para IS NULL
                                        LIMIT
                                          {$row['quantidade']}";
                     //echo $query_upd_item."<br>";
                     $DB->query($query_upd_item);

                     // LOG DA OPERAÇÃO
                     $nome_kit               = $row['KIT'];
                     $qtd_item_kit           = $row['quantidade'];
                     $id_cartucho_item_kit   = $row['cartridgeitem_id'];
                     $fullname               = $row['name']." - ".$row['ref'];

                     $ext        = $qtd_item_kit > 1 ? "s" : "";
                     $ext2       = $qtd_item_kit > 1 ? "is" : "l";
                     $changes[0] = $id_cartucho_item_kit;
                     $changes[1] = '';
                     $changes[2] = "aplicado{$ext} {$qtd_item_kit} consumíve{$ext2} '{$fullname}' referente ao kit de suprimentos '{$nome_kit}'.";
                     Log::history( $userID, 'User', $changes, 'CartridgeItem', Log::HISTORY_LOG_SIMPLE_MESSAGE);
                  }
              }
              return true;
          } else {
             // Kit sem itens
             //$_SESSION['ERRO_KIT'][-2]=1;
             $msg_erro = "Kit Vazio. Ainda não possui itens.";
             Session::addMessageAfterRedirect($msg_erro, false, ERROR);
             return false;
          }
      } else {
         // Erro ao carregar itens do kit
         //$_SESSION['ERRO_KIT'][-1]=1;
         $msg_erro = "Erro inesperado. Favor procurar equipe desenvolvimento.";
         Session::addMessageAfterRedirect($msg_erro, false, ERROR);
         return false;
      }
die("PAUSA");
      
      /* SOLUCAO ANTERIOR: SOMENTE SE EXISTIREM TODOS OS ITENS
      // Todos os itens do kit presentes no estoque
      // Aplicar kit
      $result_kititens = $DB->query($query_kititens);
      // Varre novamente os itens do kit, nas quantidades, para aloca-los
      // if ($row['quantidade'] > 0){
      while ($row = $DB->fetch_assoc($result_kititens)) {
         $query_qtd_item_estoque = "
                                   SELECT
                                      C.id,
                                      C.cartridgeitems_id,
                                      CI.name,
                                      CI.ref
                                   FROM
                                      glpi_cartridges C,
                                      glpi_cartridgeitems CI
                                   WHERE
                                      CI.id = C.cartridgeitems_id
                                      AND C.cartridgeitems_id = {$row['cartridgeitem_id']}
                                      AND C.date_use IS NULL
                                      AND C.date_out IS NULL
                                      AND C.alocado_para IS NULL
                                   LIMIT
                                      {$row['quantidade']}";

         $result_qtd_item_estoque = $DB->query($query_qtd_item_estoque);

         while ($row_qtd_item_estoque = $DB->fetch_assoc($result_qtd_item_estoque)) {
            // Aloca o item
            $query_upd_item = "UPDATE
                                  glpi_cartridges
                               SET
                                  alocado_para = $userID,
                                  alocado_tipo = 'r'
                               WHERE
                                  id = {$row_qtd_item_estoque['id']}";
            //echo $query_upd_item;
            $DB->query($query_upd_item);
         }
         

         // LOG referente a cada item do kit
         // -- Nome do kit
         $nome_kit = $row['KIT'];

         // -- Quantidade de cada item do kit
         $qtd_item_kit = $row['quantidade'];

         // -- Tipo de Cartucho do item do kit
         $id_cartucho_item_kit = $row['cartridgeitem_id'];

         // -- Nome do tipo de item do kit
         $fullname  = $row['name']." - ".$row['ref'];

         $ext  = $qtd_item_kit > 1 ? "s" : "";
         $ext2 = $qtd_item_kit > 1 ? "is" : "l";
         $changes[0] = $id_cartucho_item_kit;
         $changes[1] = '';
         $changes[2] = "aplicado{$ext} {$qtd_item_kit} consumíve{$ext2} '{$fullname}' referente ao kit de suprimentos '{$nome_kit}'.";
         Log::history( $userID, 'User', $changes, 'CartridgeItem', Log::HISTORY_LOG_SIMPLE_MESSAGE);
      //}
      //}
      return true;
       */
   }

   /**
   * Delete a compatible printer associated to a cartridge with assoc identifier $ID
   *
   *@param $ID integer: glpi_cartridge_assoc identifier.
   *
   *@return boolean : true for success
   *
   **/
   function deleteCompatibleType($ID) {
      global $DB;

      $query = "DELETE
                FROM `glpi_cartridgeitems_printermodels`
                WHERE `id` = '$ID';";

      if ($result = $DB->query($query) && $DB->affected_rows() > 0) {
         return true;
      }
      return false;
   }


   /**
   * Print the cartridge type form
   *
   * @param $ID integer ID of the item
   * @param $options array
   *     - target for the Form
   *     - withtemplate : 1 for newtemplate, 2 for newobject from template
   *
   * @return Nothing (display)
   *
   **/
   function showForm($ID, $options=array()) {
      global $LANG;

   // Show CartridgeItem or blank form
      if (!Session::haveRight("cartridge", "r")) {
        return false;
      }

      if ($ID > 0) {
         $this->check($ID, 'r');
      } else {
         // Create item
         $this->check(-1, 'w');
      }

      $this->showTabs($options);
      $this->showFormHeader($options);

      echo "<tr class='tab_bg_1'>";
      echo "<td>".$LANG['common'][16]."&nbsp;: </td>";
      echo "<td>";
      Html::autocompletionTextField($this, "name");
      echo "</td>";
      echo "<td>".$LANG['common'][17]."&nbsp;: </td>";
      echo "<td>";
      Dropdown::show('CartridgeItemType', array('value' => $this->fields["cartridgeitemtypes_id"]));
      echo "</td></tr>";

      echo "<tr class='tab_bg_1'>";
      echo "<td>".$LANG['consumables'][2]."&nbsp;: </td>";
      echo "<td>";
      Html::autocompletionTextField($this, "ref");
      echo "</td>";
      echo "<td>".$LANG['common'][5]."&nbsp;: </td>";
      echo "<td>";
      Dropdown::show('Manufacturer', array('value' => $this->fields["manufacturers_id"]));
      echo "</td></tr>";

      echo "<tr class='tab_bg_1'>";
      echo "<td>".$LANG['common'][10]."&nbsp;: </td>";
      echo "<td>";
      User::dropdown(array('name'   => 'users_id_tech',
                           'value'  => $this->fields["users_id_tech"],
                           'right'  => 'own_ticket',
                           'entity' => $this->fields["entities_id"]));
      echo "</td>";
		/*//SUPRISERVICE*/
	  echo "<input type='hidden' name='comment_compare' value='".$this->fields["comment"]."'>";
      echo "<td rowspan='6' class='middle'>".$LANG['common'][25]."&nbsp;: </td>";
      echo "<td class='middle' rowspan='4'>
             <textarea cols='45' rows='9' name='comment'>".$this->fields["comment"]."</textarea>";
      echo "</td></tr>";

      echo "<tr class='tab_bg_1'>";
      echo "<td>".$LANG['common'][109]."&nbsp;:</td>";
      echo "<td>";
      Dropdown::show('Group', array('name'      => 'groups_id_tech',
                                    'value'     => $this->fields['groups_id_tech'],
                                    'entity'    => $this->fields['entities_id'],
                                    'condition' => '`is_assign`'));
      echo "</td></tr>\n";

      echo "<tr class='tab_bg_1'>";
      echo "<td>".$LANG['consumables'][36]."&nbsp;: </td>";
      echo "<td>";
      Dropdown::show('Location', array('value'  => $this->fields["locations_id"],
                                       'entity' => $this->fields["entities_id"]));
      echo "</td></tr>";

      echo "<tr class='tab_bg_1'>";
      echo "<td>".$LANG['consumables'][38]."&nbsp;: </td>";
      echo "<td>";
      Dropdown::showInteger('alarm_threshold', $this->fields["alarm_threshold"], 0, 100, 1,
                            array('-1' => $LANG['setup'][307]));
      Alert::displayLastAlert('CartridgeItem', $ID);
      echo "</td></tr>";

		/*//SUPRISERVICE*/
      echo "<tr class='tab_bg_1'>";
      echo "<td>Consumo médio mínimo: </td>";
      echo "<td>";
      Html::autocompletionTextField($this, "previsao_min");
      echo "</td></tr>";
      echo "<tr class='tab_bg_1'>";
      echo "<td>Consumo médio máximo: </td>";
      echo "<td>";
      Html::autocompletionTextField($this, "previsao_max");
      echo "</td></tr>";
		//--

      $this->showFormButtons($options);
      $this->addDivForTabs();

      return true;
   }


   function getSearchOptions() {
      global $LANG;

      $tab = array();
      $tab['common'] = $LANG['common'][32];

      $tab[1]['table']         = $this->getTable();
      $tab[1]['field']         = 'name';
      $tab[1]['name']          = $LANG['common'][16];
      $tab[1]['datatype']      = 'itemlink';
      $tab[1]['itemlink_type'] = $this->getType();
      $tab[1]['massiveaction'] = false;

      $tab[2]['table']         = $this->getTable();
      $tab[2]['field']         = 'id';
      $tab[2]['name']          = $LANG['common'][2];
      $tab[2]['massiveaction'] = false;

      $tab[34]['table']     = $this->getTable();
      $tab[34]['field']     = 'ref';
      $tab[34]['name']      = $LANG['consumables'][2];
      $tab[34]['datatype']  = 'string';

      $tab[4]['table']  = 'glpi_cartridgeitemtypes';
      $tab[4]['field']  = 'name';
      $tab[4]['name']   = $LANG['common'][17];

      $tab[23]['table']     = 'glpi_manufacturers';
      $tab[23]['field']     = 'name';
      $tab[23]['name']      = $LANG['common'][5];

      $tab += Location::getSearchOptionsToAdd();

      $tab[24]['table']     = 'glpi_users';
      $tab[24]['field']     = 'name';
      $tab[24]['linkfield'] = 'users_id_tech';
      $tab[24]['name']      = $LANG['common'][10];

      $tab[49]['table']     = 'glpi_groups';
      $tab[49]['field']     = 'completename';
      $tab[49]['linkfield'] = 'groups_id_tech';
      $tab[49]['name']      = $LANG['common'][109];
      $tab[49]['condition'] = '`is_assign`';

      $tab[8]['table']     = $this->getTable();
      $tab[8]['field']     = 'alarm_threshold';
      $tab[8]['name']      = $LANG['consumables'][38];
      $tab[8]['datatype']  = 'number';

      $tab[16]['table']     = $this->getTable();
      $tab[16]['field']     = 'comment';
      $tab[16]['name']      = $LANG['common'][25];
      $tab[16]['datatype']  = 'text';

      $tab[90]['table']         = $this->getTable();
      $tab[90]['field']         = 'notepad';
      $tab[90]['name']          = $LANG['title'][37];
      $tab[90]['massiveaction'] = false;

      $tab[80]['table']         = 'glpi_entities';
      $tab[80]['field']         = 'completename';
      $tab[80]['name']          = $LANG['entity'][0];
      $tab[80]['massiveaction'] = false;

      $tab[40]['table']        = 'glpi_printermodels';
      $tab[40]['field']        = 'name';
      $tab[40]['name']         = $LANG['setup'][96];
      $tab[40]['forcegroupby'] = true;
      $tab[40]['joinparams']   = array('beforejoin'
                                        => array('table'      => 'glpi_cartridgeitems_printermodels',
                                                 'joinparams' => array('jointype' => 'child')));

            
      return $tab;
   }


   static function cronInfo($name) {
      global $LANG;

      return array('description' => $LANG['crontask'][2]);
   }


   /**
    * Cron action on cartridges : alert if a stock is behind the threshold
    *
    * @param $task for log, display informations if NULL?
    *
    * @return 0 : nothing to do 1 : done with success
    *
    **/
   static function cronCartridge($task=NULL) {
      global $DB, $CFG_GLPI, $LANG;

      $cron_status = 1;
      if ($CFG_GLPI["use_mailing"]) {
         $message = array();
         $alert   = new Alert();

         foreach (Entity::getEntitiesToNotify('cartridges_alert_repeat') as $entity => $repeat) {
            // if you change this query, please don't forget to also change in showDebug()
            $query_alert = "SELECT `glpi_cartridgeitems`.`id` AS cartID,
                                   `glpi_cartridgeitems`.`entities_id` AS entity,
                                   `glpi_cartridgeitems`.`ref` AS cartref,
                                   `glpi_cartridgeitems`.`name` AS cartname,
                                   `glpi_cartridgeitems`.`alarm_threshold` AS threshold,
                                   `glpi_alerts`.`id` AS alertID,
                                   `glpi_alerts`.`date`
                            FROM `glpi_cartridgeitems`
                            LEFT JOIN `glpi_alerts`
                                 ON (`glpi_cartridgeitems`.`id` = `glpi_alerts`.`items_id`
                                     AND `glpi_alerts`.`itemtype` = 'CartridgeItem')
                            WHERE `glpi_cartridgeitems`.`is_deleted` = '0'
                                  AND `glpi_cartridgeitems`.`alarm_threshold` >= '0'
                                  AND `glpi_cartridgeitems`.`entities_id` = '".$entity."'
                                  AND (`glpi_alerts`.`date` IS NULL
                                       OR (`glpi_alerts`.date+$repeat) < CURRENT_TIMESTAMP());";
            $message = "";
            $items   = array();

            foreach ($DB->request($query_alert) as $cartridge) {
               if (($unused=Cartridge::getUnusedNumber($cartridge["cartID"]))<=$cartridge["threshold"]) {
                  // define message alert
                  $message .= $LANG['mailing'][35]." ".$cartridge["cartname"]." - ".
                              $LANG['consumables'][2]."&nbsp;: ".$cartridge["cartref"]." - ".
                              $LANG['software'][20]."&nbsp;: ".$unused."<br>";
                  $items[$cartridge["cartID"]] = $cartridge;

                  // if alert exists -> delete
                  if (!empty($cartridge["alertID"])) {
                     $alert->delete(array("id" => $cartridge["alertID"]));
                  }
               }
            }

            if (!empty($items)) {
               $options['entities_id'] = $entity;
               $options['cartridges']  = $items;
               if (NotificationEvent::raiseEvent('alert', new Cartridge(), $options)) {
                  if ($task) {
                     $task->log(Dropdown::getDropdownName("glpi_entities", $entity)
                               ."&nbsp;:  $message\n");
                     $task->addVolume(1);
                  } else {
                     Session::addMessageAfterRedirect(Dropdown::getDropdownName("glpi_entities",
                                                                                $entity)
                                                      ."&nbsp;:  $message");
                  }

                  $input["type"]     = Alert::THRESHOLD;
                  $input["itemtype"] = 'CartridgeItem';

                  // add alerts
                  foreach ($items as $ID=>$consumable) {
                     $input["items_id"] = $ID;
                     $alert->add($input);
                     unset($alert->fields['id']);
                  }

               } else {
                  if ($task) {
                     $task->log(Dropdown::getDropdownName("glpi_entities", $entity)
                               ."&nbsp;: Send cartidge alert failed\n");
                  } else {
                     Session::addMessageAfterRedirect(Dropdown::getDropdownName("glpi_entities",
                                                                                $entity)
                                                      ."&nbsp;: Send cartidge alert failed", false,
                                                      ERROR);
                  }
               }
            }
          }
      }
   }


   /*//SUPRISERVICE*/
   static function dropdownForTicket(Printer $printer, $showEmpty = false)
	{
		global $DB, $LANG;

		$glpiparententities = implode(",",getAncestorsOf("glpi_entities", $printer->fields["entities_id"]));
		if (trim($glpiparententities)=="")
		{
			$glpiparententities = "-1";
		}

      $query = "SELECT COUNT(*) AS cpt,
                       `glpi_locations`.`completename` AS location,
                       `glpi_cartridgeitems`.`ref` AS ref,
                       `glpi_cartridgeitems`.`name` AS name,
                       `glpi_cartridgeitems`.`id` AS tID
                FROM `glpi_cartridgeitems`
                INNER JOIN `glpi_cartridgeitems_printermodels` ON (`glpi_cartridgeitems`.`id` = `glpi_cartridgeitems_printermodels`.`cartridgeitems_id`)
                LEFT JOIN `glpi_locations` ON (`glpi_locations`.`id` = `glpi_cartridgeitems`.`locations_id`)
                WHERE `glpi_cartridgeitems_printermodels`.`printermodels_id` = '".$printer->fields["printermodels_id"]."'
					   AND ( 
						(`glpi_cartridgeitems`.`entities_id` ='".$printer->fields["entities_id"]."') 
						OR 
						((`glpi_cartridgeitems`.`is_recursive` = 1) AND
						(`glpi_cartridgeitems`.`entities_id` IN (" . $glpiparententities . "))))
						AND glpi_cartridgeitems.is_deleted = 0
                GROUP BY tID
                ORDER BY `name`, `ref`";

      if ($result = $DB->query($query)) {
         if ($DB->numrows($result)) {
            echo "<select name='tID' size=1>";

				/*//SUPRISERVICE*/
				if ( $showEmpty )
				   echo "<option value=''>".Dropdown::EMPTY_VALUE."</option>";

            while ($data= $DB->fetch_assoc($result))
				{
					$resto = Cartridge::getEstoquePrevisto($data['tID']);

               echo "<option value='".$data["tID"]."'>".$data["name"]." - ".$data["ref"]."
                     (".$resto." ".$LANG['cartridges'][13].") - ".$data["location"]."</option>";
            }
            echo "</select>";
            return true;
         }
      }
      return false;
   }


   /**
    * Print a select with compatible cartridge
    *
    *@param $printer object Printer
    *
    *@return nothing (display)
    **/
   static function dropdownForPrinter(Printer $printer, $showEmpty = false) {
      global $DB, $LANG;

		/*//SUPRISERVICE*/
	  	  $glpiparententities = implode(",",getAncestorsOf("glpi_entities", $printer->fields["entities_id"]));
	  if (trim($glpiparententities)=="")
	  {
		$glpiparententities = "-1";
	  }

      $query = "SELECT COUNT(*) AS cpt,
                       `glpi_locations`.`completename` AS location,
                       `glpi_cartridgeitems`.`ref` AS ref,
                       `glpi_cartridgeitems`.`name` AS name,
                       `glpi_cartridgeitems`.`id` AS tID
                FROM `glpi_cartridgeitems`
                INNER JOIN `glpi_cartridgeitems_printermodels`
                     ON (`glpi_cartridgeitems`.`id`
                         = `glpi_cartridgeitems_printermodels`.`cartridgeitems_id`)
                INNER JOIN `glpi_cartridges`
                     ON (`glpi_cartridges`.`cartridgeitems_id` = `glpi_cartridgeitems`.`id`
                         AND `glpi_cartridges`.`date_use` IS NULL
                         #AND `glpi_cartridges`.`alocado_para` IS NULL
                        )
                LEFT JOIN `glpi_locations`
                     ON (`glpi_locations`.`id` = `glpi_cartridgeitems`.`locations_id`)
                WHERE `glpi_cartridgeitems_printermodels`.`printermodels_id`
                           = '".$printer->fields["printermodels_id"]."'
                      AND (
                            (`glpi_cartridgeitems`.`entities_id` ='".$printer->fields["entities_id"]."')
                            OR
                            ((`glpi_cartridgeitems`.`is_recursive` = 1) AND
                            (`glpi_cartridgeitems`.`entities_id` IN (" . $glpiparententities . ")))
                          )
                GROUP BY tID
                ORDER BY `name`, `ref`";
      if ($result = $DB->query($query)) {
         
         if ($DB->numrows($result)) {
            echo "<select name='tID' size=1>";

				/*//SUPRISERVICE*/
				if ( $showEmpty )
				   echo "<option value=''>".Dropdown::EMPTY_VALUE."</option>";

            echo "<option disabled='disabled'>Novos</option>";

            while ($data= $DB->fetch_assoc($result)) {
                                /*//SUPRISERVICE*/
                $query_novos = "SELECT
                            COUNT(C.id) AS cpt,
                            CI.name AS name,
                            CI.id AS tID,
                            CI.ref AS ref
                         FROM
                            glpi_cartridges AS C,
                            glpi_cartridgeitems AS CI
                         WHERE
                            C.cartridgeitems_id = CI.id
                            AND C.date_use IS NULL
                            AND C.alocado_para IS NULL
                            AND CI.id = ".$data['tID']."
                         GROUP BY
                            C.cartridgeitems_id
                        ";
                                /*//SUPRISERVICE*/
                if ($result_novos = $DB->query($query_novos)) {
                    if ($DB->numrows($result_novos)) {
                        $data_novos   = $DB->fetch_assoc($result_novos);
                        echo "<option value='".$data_novos["tID"]."'>".$data_novos["name"]." - ".$data_novos["ref"]."(".$data_novos["cpt"]." ".$LANG['cartridges'][13].")</option>";
                    }
                }
					//opção de instalar a partir de cartuchos novos foi desabilitada pois deve obrigatoriamente ser instalado a partir de estoque em trânsito de algum analista
					//25/06/2013: deixado ativo para baixa de cartuchos da semana anterior ao sistema
               //echo "<option value='".$data["tID"]."'>".$data["name"]." - ".$data["ref"]."(".$data["cpt"]." ".$LANG['cartridges'][13].") - ".$data["location"]."</option>";
            }

            echo "<option disabled='disabled'></option>";
            echo "<option disabled='disabled'>Aplicados</option>";
            
            // Como $result é identico a $result2 e já foi verificado, não precisa verificar denovo
            $result2 = $DB->query($query);
            while ($data = $DB->fetch_assoc($result2)) {
					//mostra as opções dos cartuchos aplicados por analistas
					$aplicados = Cartridge::getAplicados($data["tID"]);
               foreach($aplicados as $t) {
                 if ($t['CHAMADO'])
                    $chamado = ", chamado: ".$t['CHAMADO'];
                 else
                    $chamado = "";
                 echo "<option value='".$data["tID"]."-".$t["user_id"]."'>".$data["name"]." - ".$data["ref"]." (". $t["count"]." por ". $t["name"]." em ".$t["DATA"]."$chamado)</option>";
                 // echo "<option>Teste</option>";
					}

               //mostra as opções dos cartuchos alocados para os analistas
					//$emTransito = Cartridge::getEmTransito($data["tID"]);
					//foreach($emTransito as $t) {
	               ////echo "<option value='".$data["tID"]."-".$t["user_id"]."'>".$data["name"]." - ".$data["ref"]."(". $t["count"]." ".  sprintf($LANG['custom_cartridges'][8], $t["name"]).") - ".$data["location"]."</option>";
                  //echo "<option value='".$data["tID"]."-".$t["user_id"]."'>".$data["name"]." - ".$data["ref"]."(". $t["count"]." ".  sprintf($LANG['custom_cartridges'][8], $t["name"]).")</option>";
					//}
            }
           echo "</select>";
            return true;
         }
      }
      return false;
   }


   /**
    * Show the printer types that are compatible with a cartridge type
    *
    *@return nothing (display)
    **/
   function showCompatiblePrinters() {
      global $DB, $CFG_GLPI, $LANG;

      $instID = $this->getField('id');
      if (!$this->can($instID, 'r')) {
         return false;
      }

      $query = "SELECT `glpi_cartridgeitems_printermodels`.`id`,
                       `glpi_printermodels`.`name` AS `type`,
                       `glpi_printermodels`.`id` AS `pmid`
                FROM `glpi_cartridgeitems_printermodels`,
                     `glpi_printermodels`
                WHERE `glpi_cartridgeitems_printermodels`.`printermodels_id`
                           = `glpi_printermodels`.`id`
                      AND `glpi_cartridgeitems_printermodels`.`cartridgeitems_id` = '$instID'
                ORDER BY `glpi_printermodels`.`name`";

      $result = $DB->query($query);
      $number = $DB->numrows($result);
      $i = 0;

      echo "<div class='spaced'>";
      echo "<form method='post' action=\"".$CFG_GLPI["root_doc"]."/front/cartridgeitem.form.php\">";
      echo "<table class='tab_cadre_fixe'>";
      echo "<tr><th colspan='3'>".$LANG['cartridges'][32]."&nbsp;:</th></tr>";
      echo "<tr><th>".$LANG['common'][2]."</th><th>".$LANG['common'][22]."</th><th>&nbsp;</th></tr>";

      $used = array();
      while ($i < $number) {
         $ID   = $DB->result($result, $i, "id");
         $type = $DB->result($result, $i, "type");
         $pmid = $DB->result($result, $i, "pmid");
         echo "<tr class='tab_bg_1'><td class='center'>$ID</td>";
         echo "<td class='center'>$type</td>";
         echo "<td class='tab_bg_2 center b'>";
         echo "<a href='".$CFG_GLPI['root_doc'].
                "/front/cartridgeitem.form.php?deletetype=deletetype&amp;id=$ID&amp;tID=$instID'>";
         echo $LANG['buttons'][6]."</a></td></tr>";
         $used[$pmid] = $pmid;
         $i++;
      }
      if (Session::haveRight("cartridge", "w")) {
         echo "<tr class='tab_bg_1'><td>&nbsp;</td><td class='center'>";
         echo "<input type='hidden' name='tID' value='$instID'>";
         Dropdown::show('PrinterModel', array('used' => $used));
         echo "</td><td class='tab_bg_2 center'>";
         echo "<input type='submit' name='addtype' value=\"".$LANG['buttons'][8]."\" class='submit'>";
         echo "</td></tr>";
      }
      echo "</table>";
      Html::closeForm();
      echo "</div>";
   }


  function getEvents() {
      global $LANG;

      return array('alert' => $LANG['crontask'][2]);
   }


   /**
    * Display debug information for current object
    *
   **/
   function showDebug() {

      // see query_alert in cronCartridge()
      $item = array('cartID'    => $this->fields['id'],
                    'entity'    => $this->fields['entities_id'],
                    'cartref'   => $this->fields['ref'],
                    'cartname'  => $this->fields['name'],
                    'threshold' => $this->fields['alarm_threshold']);

      $options = array();
      $options['entities_id'] = $this->getEntityID();
      $options['cartridges']  = array($item);
      NotificationEvent::debugEvent(new Cartridge(), $options);
   }
   
   
   
    
    
    //***** SUPRISERVICE *****//
   static function deleteReservas($tabela,$alocado){
        global $DB;
        
        switch ($tabela){
            case 'glpi_printers':
                $classe = 'Printer';  
                break;
            case 'glpi_computers':
                $classe = 'Computer'; 
                break;
            case 'glpi_monitors':
                $classe = 'Monitor';
                break;
            case 'glpi_networkequipments':
                $classe = 'NetworkEquipment';
                break;
            case 'glpi_peripherals':                
                $classe = 'Peripheral';                
        }
        
        $query = "UPDATE {$tabela} 
                SET 
                    alocado_para = NULL , 
                    alocado_tipo = NULL 
                WHERE
                    alocado_para = {$alocado} ";
                    
        if ($result = $DB->query($query) && $DB->affected_rows() > 0) {
            
            $changes[0] = '';
            $changes[1] = '';
            $changes[2] = "removido todos os equipamentos de reserva.";
            Log::history($alocado, 'User', $changes, $classe, Log::HISTORY_LOG_SIMPLE_MESSAGE);
            
            return true;
        }
        
        return false;
    }
    
    static function foundTicket($id){
        global $DB;
        
        $query = "SELECT * FROM `glpi_tickets` WHERE id = {$id}";
        $result = $DB->query($query);
        $found = $DB->numrows($result);
        
        if($found == 1){
            return true;
        }else{
            return false;
        }
    }
}
?>

