<?php

/*
 * @version $Id: cartridge.class.php 18771 2012-06-29 08:49:19Z moyo $
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

//!  Cartridge Class
/**
 * This class is used to manage the cartridges.
 * @see CartridgeItem
 * @author Julien Dombre
 * */
class Cartridge extends CommonDBTM {

    // From CommonDBTM
    protected $forward_entity_to = array('Infocom');
    var $no_form_page = false;
    public $dohistory=true;

    static function getTypeName($nb = 0) {
        global $LANG;

        if ($nb > 1) {
            return $LANG['Menu'][21];
        }
        return $LANG['cartridges'][0];
    }

    function canCreate() {
        return Session::haveRight('cartridge', 'w');
    }

    function canView() {
        return Session::haveRight('cartridge', 'r');
    }

    function prepareInputForAdd($input) {

        $item = new CartridgeItem();
        if ($item->getFromDB($input["tID"])) {
            return array("cartridgeitems_id" => $item->fields["id"],
                "entities_id" => $item->getEntityID(),
                "date_in" => date("Y-m-d"));
        }
        return array();
    }

    function post_addItem() {

        $ic = new Infocom();
        $ic->cloneItem('CartridgeItem', $this->fields["cartridgeitems_id"], $this->fields['id'], $this->getType());
    }

    function post_updateItem($history = 1) {

        if (in_array('pages', $this->updates)) {
            $printer = new Printer();
            if ($printer->getFromDB($this->fields['printers_id']) && ($this->fields['pages'] > $printer->getField('last_pages_counter') || $this->oldvalues['pages'] == $printer->getField('last_pages_counter'))) {

                $printer->update(array('id' => $printer->getID(),
                    'last_pages_counter' => $this->fields['pages']));
            }
        }
    }

    function restore($input, $history = 1) {
        global $DB;

        /* //SUPRISERVICE */
        $query = "UPDATE `" . $this->getTable() . "`
                SET `date_out` = NULL,
                    `date_use` = NULL,
                    `pages` = NULL,
                    `alocado_para` = NULL,
                    `alocado_tipo` = NULL,
                    `aplicado_por` = NULL,
                    `aplicado_data` = NULL,
                    `aplicado_chamado` = NULL,
                    `printers_id` = '0'
                WHERE `id`='" . $input["id"] . "'";

        /* $query = "UPDATE `".$this->getTable()."`
          SET `date_out` = NULL,
          `date_use` = NULL,
          `printers_id` = '0'
          WHERE `id`='".$input["id"]."'";
         */
        if ($result = $DB->query($query) && $DB->affected_rows() > 0) {
            return true;
        }
        return false;
    }

    // SPECIFIC FUNCTIONS
    /**
     * Update count pages value of a cartridge
     *
     * @param $pages  count pages value
     *
     * @return boolean : true for success
     * */
    function updatePages($pages) {

        return $this->update(array('id' => $this->fields['id'],
                    'pages' => $pages));
    }

    /**
     * Update dates use value of a cartridge
     *
     * @param $date_use  date_use value
     *
     * @return boolean : true for success
     * */
    function updateCartUse($date_use) {
        global $DB;

        if ($date_use && ($date_use != 'NULL')) {
            return $this->update(array('id' => $this->fields['id'],
                        'date_use' => $date_use));
        }
        return false;
    }
    
    function defineTabs($options=array()) {
      global $LANG;

      $ong = array();      
      $this->addStandardTab('Log', $ong, $options);

      return $ong;
   }
   
   
    /**
     * Update count pages and date out value of a cartridge
     *
     * @param $pages  count pages value
     * @param $date_out  date_out value
     *
     * @return boolean : true for success
     * */
    function updateCartOut($pages, $date_out, $tickets_id) {
        global $DB;

        if ($date_out == 'NULL') {
            $pages = 0;
        }
        /* //SUPRISERVICE */
        return $this->update(array('id' => $this->fields['id'],
                    'date_out' => $date_out,
                    'pages' => $pages,
                    'tickets_id' => $tickets_id));
    }

    /**
     * Link a cartridge to a printer.
     *
     * Link the first unused cartridge of type $Tid to the printer $pID
     *
     * @param $tID : cartridge type identifier
     * @param $pID : printer identifier
     *
     * @return boolean : true for success
     * */
    /* //SUPRISERVICE */
    function install($pID, $tID, $userID) {

        //var_export($_POST);
        // die();
        global $DB, $LANG;

        if ($userID > 0)
            $queryUserID = "AND `aplicado_por` = {$userID} "; // Cartuchos Aplicados
        else {
            $queryUserID = "AND `aplicado_por` IS NULL "; // Cartuchos Novos
            $queryUserID .= "AND `alocado_para` IS NULL ";
        }

        // Get first unused cartridge
        $query = "SELECT `id`
                FROM `" . $this->getTable() . "`
                WHERE (`cartridgeitems_id` = '$tID'
							  $queryUserID
                       AND `date_use` IS NULL)";
        $result = $DB->query($query);

        if ($DB->numrows($result) > 0) {
            // Mise a jour cartouche en prenant garde aux insertion multiples
            $query = "UPDATE `" . $this->getTable() . "`
                   SET `date_use` = '" . date("Y-m-d") . "',
                       `printers_id` = '$pID'
                   WHERE (`id`='" . $DB->result($result, 0, 0) . "'
                          AND `date_use` IS NULL)";

            if ($result = $DB->query($query) && $DB->affected_rows() > 0) {
                /* //SUPRISERVICE */
                //log
                if ($userID > 0) {
                    $u = new User();
                    $u->getFromDB($userID);
                    $username = $u->fields['firstname'] . ' ' . $u->fields['realname'];
                } else
                    $username = $LANG['cartridges'][13];

                $ci = new CartridgeItem();
                $ci->getFromDB($tID);
                $cartridgeitem_name = $ci->fields['name'] . " - " . $ci->fields['ref'];

                $changes[0] = $tID;
                $changes[1] = '';
                $changes[2] = "instalado o consumível '{$cartridgeitem_name}' ({$username})";
                Log::history($pID, 'Printer', $changes, 'CartridgeItem', Log::HISTORY_LOG_SIMPLE_MESSAGE);
                return true;
            }
        } else {
            Session::addMessageAfterRedirect($LANG['cartridges'][34], false, ERROR);
        }
        return false;
    }

    /**
     * UnLink a cartridge linked to a printer
     *
     * UnLink the cartridge identified by $ID
     *
     * @param $ID : cartridge identifier
     *
     * @return boolean
     * */
    function uninstall($ID) {
        global $DB;

        $query = "UPDATE`" . $this->getTable() . "`
                SET `date_out` = '" . date("Y-m-d") . "'
                WHERE `id`='$ID'";

        if ($result = $DB->query($query) && $DB->affected_rows() > 0) {
            /* //SUPRISERVICE */
            $c = new Cartridge();
            $c->getFromDB($ID);

            $ci = new CartridgeItem();
            $ci->getFromDB($c->fields['cartridgeitems_id']);
            $cartridgeitem_name = $ci->fields['name'] . " - " . $ci->fields['ref'];

            $changes[0] = $ci->fields['id'];
            $changes[1] = '';
            $changes[2] = "desinstalado o consumível '{$cartridgeitem_name}'";
            Log::history($c->fields['printers_id'], 'Printer', $changes, 'CartridgeItem', Log::HISTORY_LOG_SIMPLE_MESSAGE);
            return true;
        }
        return false;
    }

//    function isEntityAssign() {
//       return true;
//    }

    /**
     * Get the ID of entity assigned to the cartdrige
     *
     * @return ID of the entity
     * */
//    function getEntityID () {
//       $ci=new CartridgeItem();
//       $ci->getFromDB($this->fields["cartridgeitems_id"]);
//       return $ci->getEntityID();
//    }

    /**
     * Print the cartridge count HTML array for a defined cartridge type
     *
     * Print the cartridge count HTML array for the cartridge item $tID
     *
     * @param $tID integer: cartridge item identifier.
     * @param $alarm_threshold integer: threshold alarm value.
     * @param $nohtml integer: Return value without HTML tags.
     *
     * @return string to display
     * */
    static function getCount($tID, $alarm_threshold, $nohtml = 0) {
        global $DB, $LANG;

        // Get total
        $total = self::getTotalNumber($tID);
        $out = "";
        if ($total != 0) {
            $unused = self::getUnusedNumber($tID);
            $used = self::getUsedNumber($tID);
            $old = self::getOldNumber($tID);
            /* //SUPRISERVICE */
            $transito = self::getEmTransitoNumber($tID);
            $previsto = self::getEstoquePrevisto($tID);

            $highlight = "";
            if ($previsto <= $alarm_threshold) {
                $highlight = "class='tab_bg_1_2'";
            }
            //--

            if (!$nohtml) {
                $out .= "<div $highlight>" . $LANG['common'][33] . "&nbsp;:&nbsp;$total";
                $out .= "<span class='b very_small_space'>";
                if ($unused > 1) {
                    $out .= $LANG['cartridges'][13];
                } else {
                    $out .= $LANG['cartridges'][20];
                }
                $out .= "&nbsp;:&nbsp;$unused</span>";
                $out .= "<br>";
                $out .= "<span>";
                if ($used > 1) {
                    $out .= $LANG['cartridges'][14];
                } else {
                    $out .= $LANG['cartridges'][21];
                }
                $out .= "&nbsp;:&nbsp;$used</span>";
                $out .= "<span class='very_small_space'>";
                if ($old > 1) {
                    $out .= $LANG['cartridges'][15];
                } else {
                    $out .= $LANG['cartridges'][22];
                }
                $out .= "&nbsp;:&nbsp;$old</span>";
                /* //SUPRISERVICE */
                $out .= "<br>";
                $out .= "<span>";
                $out .= $LANG['custom_cartridges'][1];
                $out .= "&nbsp;:&nbsp;$transito</span>";

                //Estoque previsto
                $out .= "<br>";
                $out .= "<span class='b'>";
                $out .= $LANG['custom_cartridges'][7];
                $out .= "&nbsp;:&nbsp;$previsto</span></div>";
                //--
            } else {
                $out .= $LANG['common'][33] . " : $total  ";
                if ($unused > 1) {
                    $out .= $LANG['cartridges'][13];
                } else {
                    $out .= $LANG['cartridges'][20];
                }
                $out .= " : $unused   ";
                if ($used > 1) {
                    $out .= $LANG['cartridges'][14];
                } else {
                    $out .= $LANG['cartridges'][21];
                }
                $out .= " : $used   ";
                if ($old > 1) {
                    $out .= $LANG['cartridges'][15];
                } else {
                    $out .= $LANG['cartridges'][22];
                }
                $out .= " : $old ";
                /* //SUPRISERVICE */
                $out .= $LANG['custom_cartridges'][1];
                $out .= " : $transito ";

                $out .= $LANG['custom_cartridges'][7];
                $out .= " : $previsto ";
                //--
            }
        } else {
            if (!$nohtml) {
                $out .= "<div class='tab_bg_1_2'><i>" . $LANG['cartridges'][9] . "</i></div>";
            } else {
                $out .= $LANG['cartridges'][9];
            }
        }
        return $out;
    }

    /**
     * count how many cartbridge for a cartbridge type
     *
     * count how many cartbridge for the cartridge item $tID
     *
     * @param $tID integer: cartridge item identifier.
     *
     * @return integer : number of cartridge counted.
     * */
    static function getTotalNumber($tID) {
        global $DB;

        $query = "SELECT id
                FROM `glpi_cartridges`
                WHERE (`cartridgeitems_id` = '$tID')";
        $result = $DB->query($query);
        return $DB->numrows($result);
    }

    /**
     * count how many cartridge used for a cartbridge type
     *
     * count how many cartridge used for the cartbridge item $tID
     *
     * @param $tID integer: cartridge item identifier.
     *
     * @return integer : number of cartridge used counted.
     * */
    static function getUsedNumber($tID) {
        global $DB;

        $query = "SELECT id
                FROM `glpi_cartridges`
                WHERE (`cartridgeitems_id` = '$tID'
                       AND `date_use` IS NOT NULL
                       AND `date_out` IS NULL)";
        $result = $DB->query($query);
        return $DB->numrows($result);
    }

    /**
     * count how many old cartbridge for a cartbridge type
     *
     * count how many old cartbridge for the cartbridge item $tID
     *
     * @param $tID integer: cartridge item identifier.
     *
     * @return integer : number of old cartridge counted.
     * */
    static function getOldNumber($tID) {
        global $DB;

        $query = "SELECT id
                FROM `glpi_cartridges`
                WHERE (`cartridgeitems_id` = '$tID'
                       AND `date_out` IS NOT NULL)";
        $result = $DB->query($query);
        return $DB->numrows($result);
    }

    /**
     * count how many cartbridge unused for a cartbridge type
     *
     * count how many cartbridge unused for the cartbridge item $tID
     *
     * @param $tID integer: cartridge item identifier.
     *
     * @return integer : number of cartridge unused counted.
     * */
    static function getUnusedNumber($tID) {
        global $DB;

        /* //SUPRISERVICE */
        $query = "SELECT id
                FROM `glpi_cartridges`
                WHERE (`cartridgeitems_id` = '$tID'
                    AND `date_use` IS NULL)
                    AND `aplicado_por` IS NULL
                    AND alocado_para IS NULL ";
        //--
        $result = $DB->query($query);
        return $DB->numrows($result);
    }

    /* //SUPRISERVICE */

    static function getEmTransitoNumber($tID) {
        global $DB;

        $query = "SELECT id
                FROM `glpi_cartridges`
                WHERE (`cartridgeitems_id` = '$tID'
                       AND `date_use` IS NULL)
							  AND alocado_para > 0 ";
        $result = $DB->query($query);
        return $DB->numrows($result);
    }

    static function getEstoquePrevisto($tID) {
        global $DB;

        $query_cpt = "	select count(*) as cpt
							from glpi_cartridges c
							where c.cartridgeitems_id = {$tID}
							and date_out is null
							and date_use is null";
        $result_cpt = $DB->query($query_cpt);
        $data_cpt = $DB->fetch_assoc($result_cpt);

        $query_in_tickets = "	select count(*) as in_tickets
										from supridesk_tickets_cartridgeitems stci, glpi_tickets t
										where stci.tickets_id = t.id
										and stci.cartridgeitems_id = {$tID}
										and stci.is_deleted = 0
										and t.status NOT IN ('solved', 'closed') ";
        $result_in_tickets = $DB->query($query_in_tickets);
        $data_in_tickets = $DB->fetch_assoc($result_in_tickets);

        return intval($data_cpt["cpt"] - $data_in_tickets["in_tickets"]);
    }

    static function getEmTransito($tID) {
        global $DB;

        $query_cpt = "	select count(*) as count, u.id as user_id, CONCAT( u.firstname, ' ', u.realname) as name
							from glpi_cartridges c, glpi_users u
							where c.alocado_para = u.id
							and c.cartridgeitems_id = {$tID}
							and date_out is null
							and date_use is null
							AND alocado_para IS NOT NULL
							AND aplicado_por IS NULL
							group by user_id";
        $result_cpt = $DB->query($query_cpt);

        $retorno = Array();
        while ($data = $DB->fetch_assoc($result_cpt)) {
            $retorno[] = $data;
        }

        return $retorno;
    }

    static function getAplicados($tID) {
        global $DB;

        $query_cpt = "	select
                        count(*) as count,
                        u.id as user_id,
                        CONCAT( u.firstname, ' ', u.realname) as name,
                        c.aplicado_data as DATA,
                        c.aplicado_chamado as CHAMADO
							from
                        glpi_cartridges c,
                        glpi_users u
							where
                        c.aplicado_por = u.id
                        and c.cartridgeitems_id = {$tID}
                        and date_out is null
                        and date_use is null
                        AND aplicado_por IS NOT NULL
							group by
                        DATA,user_id";
        $result_cpt = $DB->query($query_cpt);
        $retorno = Array();
        while ($data = $DB->fetch_assoc($result_cpt)) {
            $retorno[] = $data;
        }

        return $retorno;
    }

    //--

    /**
     * Get the dict value for the status of a cartridge
     *
     * @param $date_use date : date of use
     * @param $date_out date : date of delete
     *
     * @return string : dict value for the cartridge status.
     * */
    /* //SUPRISERVICE */
    static function getStatus($date_use, $date_out, $user_id, $aplicado_por) {
        global $LANG;

        $novo = (is_null($date_use) || empty($date_use));
        $instalado = (is_null($date_out) || empty($date_out)) && !$novo;

        /* //SUPRISERVICE */
        if ($user_id > 0 && $novo && !$instalado) {
            return $LANG['custom_cartridges'][1];
        }

        if ($aplicado_por > 0 && $novo && !$instalado) {
            return "Aplicado";
        }

        if (is_null($date_use) || empty($date_use)) {
            return $LANG['cartridges'][20];
        }
        if (is_null($date_out) || empty($date_out)) {
            return $LANG['cartridges'][21];
        }
        return $LANG['cartridges'][22];
    }

    /* //SUPRISERVICE */

    static function showForUser(User $user, $show_old = 0) {
        global $DB, $CFG_GLPI, $LANG;

        $uID = $user->getField('id');
        if (!$user->can($uID, 'r')) {
            return false;
        }
        $canedit = $user->can($uID, 'w');

        // Suprimentos da Listagem Principal
        $query = "SELECT
                    COUNT(*) AS qtd, c.*, ci.*
                FROM glpi_cartridges c
                LEFT JOIN glpi_cartridgeitems ci ON ( c.cartridgeitems_id = ci.id )
                WHERE 
                    date_out IS NULL
                    AND date_use IS NULL
                    AND alocado_para = {$uID}
                    AND aplicado_por IS NULL
                GROUP BY cartridgeitems_id
                ORDER BY name, ref";

        $result = $DB->query($query);
        $number = $DB->numrows($result);
        $i = 0;

        echo "<div class='spaced'>";
        echo "<form method='post' action=\"" . $CFG_GLPI["root_doc"] . "/front/cartridgeitem.form.php\">";
        echo "<table width='100%' class='tab_cadre'>";
        echo "<tr>";
        echo "<th colspan='6'>" . $LANG['custom_cartridges'][2] . "&nbsp;:</th>";
        echo "</tr>";
        echo "<tr>";
        echo "<th width='40%'>" . $LANG['cartridges'][1] . "</th>";
        //echo "<th width='12%'>Aplicados</th>";
        echo "<th width='12%'>Qtd Chamado</th>";
        echo "<th width='12%'>Qtd Reserva</th>";
        echo "<th width='21%'>Aplicar</th>";
        echo "<th width='15%' colspan = '2'>Remover</th>";
        echo "</tr>";

        if ($number > 0) {
            while ($i < $number) {
                $ID = $DB->result($result, $i, "cartridgeitems_id");
                $name = $DB->result($result, $i, "name");
                $ref = $DB->result($result, $i, "ref");
                $fullname = sprintf("%s - %s", $name, $ref);
                $qtd = $DB->result($result, $i, "qtd");

                // Quantidade de alocados chamado
                $query_alocados_c = "	SELECT
                                       COUNT(*) AS qtd, c.*, ci.*
                                    FROM glpi_cartridges c
                                       LEFT JOIN glpi_cartridgeitems ci ON ( c.cartridgeitems_id = ci.id )
                                    WHERE date_out IS NULL
                                    AND date_use IS NULL
                                    AND aplicado_por IS NULL
                                    AND alocado_para = {$uID}
                                    AND cartridgeitems_id = {$ID}
                                    AND alocado_tipo = 'c'
                                    GROUP BY cartridgeitems_id
                                    ORDER BY name, ref";
                //echo $ID." - ".$query_alocados."<hr>";
                $result_alocados_c = $DB->query($query_alocados_c);
                $valores_alocados_c = $DB->fetch_assoc($result_alocados_c);
                $qtd_alocados_c = $valores_alocados_c['qtd'];

                // Quantidade de alocados reserva
                $query_alocados_r = "	SELECT
                                       COUNT(*) AS qtd, c.*, ci.*
                                    FROM glpi_cartridges c
                                       LEFT JOIN glpi_cartridgeitems ci ON ( c.cartridgeitems_id = ci.id )
                                    WHERE date_out IS NULL
                                    AND date_use IS NULL
                                    AND aplicado_por IS NULL
                                    AND alocado_para = {$uID}
                                    AND cartridgeitems_id = {$ID}
                                    AND alocado_tipo = 'r'
                                    GROUP BY cartridgeitems_id
                                    ORDER BY name, ref";
                //echo $ID." - ".$query_alocados."<hr>";
                $result_alocados_r = $DB->query($query_alocados_r);
                $valores_alocados_r = $DB->fetch_assoc($result_alocados_r);
                $qtd_alocados_r = $valores_alocados_r['qtd'];

                echo "<tr class='tab_bg_2'>";

                echo "<td><a href=\"" . $CFG_GLPI["root_doc"] . "/front/cartridgeitem.form.php?id=" . $ID . "\">" .
                $fullname . "</a></td>";
                //echo "<td><a href=\"".$fullname."\">$fullname</td>";
                //echo "<td class='center'>$qtd_aplicados</td>";
                echo "<td class='center'>$qtd_alocados_c</td>";
                echo "<td class='center'>$qtd_alocados_r</td>";
                echo "<td class='tab_bg_2 center b'>";
                echo "<div align='center'>";
                echo "Data<br>";
                $data_aplicado = date("Y-m-d");
                Html::showDateFormItem("data_aplicado_{$ID}", $data_aplicado, 0);
                echo "Chamado<br>";
                //echo "<input type='text' name='aplica_chamado_{$ID}' size='5'>";
                echo "<textarea name='aplica_chamado_{$ID}' cols='20' rows='3'></textarea>";
                echo "<br>";
                echo "Quantidade<br>";

                echo "<select name='sAplica_{$ID}' size=1>";
                echo "<option value='0'>0</option>";
                for ($j = 1; $j <= $qtd_alocados_c + $qtd_alocados_r; $j++) {
                    echo "<option value='$j'>$j</option>";
                }
                echo "</select>";
                echo "</div>";
                echo "</td>";
                echo "<td class='tab_bg_2 center b'>";

                echo "Qtd Chamado:";
                echo "<select name='sRemoveC_{$ID}' size=1>";
                echo "<option value='0'>0</option>";
                for ($j = 1; $j <= $qtd_alocados_c; $j++) {
                    echo "<option value='$j'>$j</option>";
                }
                echo "</select>";

                echo "<br>";


                echo "Qtd Reserva:";
                echo "<select name='sRemoveR_{$ID}' size=1>";
                echo "<option value='0'>0</option>";
                for ($j = 1; $j <= $qtd_alocados_r; $j++) {
                    echo "<option value='$j'>$j</option>";
                }
                echo "</select>";

                echo "</td></tr>";

                $i++;
            }
        }

        if (Session::haveRight("cartridge_kits", "w")) {
            if ($number > 0) {
                echo "<tr class='tab_bg_2'>";
                //echo "<td>&nbsp;</td>";
                echo "<td class='tab_bg_2 center'>&nbsp;</td>";
                //echo "<td class='tab_bg_2 center'>&nbsp;</td>";
                echo "<td class='tab_bg_2 center'>";
                echo "<input type='submit' name='removerTodosChamado' value=\"" . $LANG['custom_cartridges'][5] . "\" class='submit'>";
                echo "</td>";
                echo "<td class='tab_bg_2 center'>";
                echo "<input type='submit' name='removerTodosReserva' value=\"" . $LANG['custom_cartridges'][5] . "\" class='submit'>";
                echo "</td>";
                echo "<td class='tab_bg_2 center'>";
                echo "<input type='submit' name='aplicarSelecionados' value='Aplicar selecionados' class='submit'>";
                echo "</td>";
                echo "<td class='tab_bg_2 center'>";
                echo "<input type='submit' name='removerSelecionados' value=\"" . $LANG['custom_cartridges'][6] . "\" class='submit'>";
                echo "</td>";
                echo "</tr>";
            } else {
                // Se não existirem cartuchos Em Trânsito para este analista
                echo "<tr class='tab_bg_2'>";
                echo "<td colspan='6' class='center'><b>Não existem itens em trânsito para este analista.</b></td>";
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
                'rand' => $rand
            );

            $toupdate = array();
            $moreparams = array('value' => '__VALUE__');

            $toupdate[] = array('value_fieldname' => 'value',
                'to_update' => "show_quantities",
                'url' => $CFG_GLPI["root_doc"] . "/ajax/dropdownCartridgeItem.php",
                'moreparams' => $moreparams);
            $opt['toupdate'] = $toupdate;

            Dropdown::show('CartridgeItem', $opt);

            echo " Quantidade: ";
            echo "<span id='show_quantities'></span>";


            echo "&nbsp;&nbsp;<input type='submit' name='addCartuchoC' value=\" " . $LANG['buttons'][8] . " Chamado " . "\" class='submit'>";
            echo "&nbsp;&nbsp;<input type='submit' name='addCartuchoR' value=' Adicionar Reserva ' class='submit'>";
            echo "</td>";
            echo "</tr>";

            // SUPRISERVICE
            // Aplicação de Kits de Suprimentos - Reserva
            echo "<tr>";
            echo "<td colspan='6' class='tab_bg_2' width='30%' height='30' align='left'>";
            echo "Aplicar Kit de Suprimento: <input type='hidden' name='uID' value='$uID'>";
            echo "<select name='kitsel' id='kitsel'>";
            $query_kits = "SELECT
                                id,
                                name
                            FROM
                                supridesk_cartridges_kits
                            ORDER BY
                                name";

            $result_kits = $DB->query($query_kits);

            while ($valores_kits = $DB->fetch_assoc($result_kits)) {
                echo "<option value='{$valores_kits['id']}'>{$valores_kits['name']}</option>";
            }
            echo "</select>";

            echo "&nbsp;&nbsp;<input type='submit' name='aplicarKit' value='Aplicar' class='submit'>";

            $_SESSION['ERRO_KIT'] = 0;

            echo "</td>";
            echo "</tr>";

            // SUPRISERVICE
            // Termo de responsabilidade de suprimentos
            // Removido Termo para análise do processo
            echo "<tr>";
            echo "<td colspan='6' class='tab_bg_2' width='30%' height='30' align='center'>";
            echo "<a href='" . $CFG_GLPI["root_doc"] . "/front/report.termo.php?id={$uID}' target='_blank'>Imprimir</a>";
            echo "</td>";
            echo "</tr>";


            // CARTUCHOS APLICADOS
            // Suprimentos da Listagem Principal
            $query = "SELECT
                    ci.id,
                     ci.name,
                     ci.ref,
                     c.aplicado_data,
                     c.aplicado_chamado,
                     c.cartridgeitems_id,
                     COUNT(*) AS qtd
		FROM
                     glpi_cartridges c
		LEFT JOIN glpi_cartridgeitems ci ON ( c.cartridgeitems_id = ci.id )
		WHERE
                     date_out IS NULL
                     AND date_use IS NULL
                     AND aplicado_por = {$uID}
		GROUP BY
                     cartridgeitems_id,
                     aplicado_data
		ORDER BY
                     aplicado_data,name, ref";

            $result = $DB->query($query);
            $number = $DB->numrows($result);
            $i = 0;

            // Cartuchos aplicados pelo analista com data
            echo "<div class='spaced'>";
            echo "<form method='post' action=\"" . $CFG_GLPI["root_doc"] . "/front/cartridgeitem.form.php\">";
            echo "<table width='100%' class='tab_cadre_fixe'>";
            echo "<tr>";
            echo "<th colspan='6'>Cartuchos Aplicados pelo Analista&nbsp;:</th>";
            echo "</tr>";
            echo "<tr>";
            echo "<th width='45%'>" . $LANG['cartridges'][1] . "</th>";
            echo "<th colspan='2' width='20%'>Data</th>";
            echo "<th>Chamados</th>";
            echo "<th>Quantidade</th>";
            echo "<th>Remover</th>";
            echo "</tr>";
//echo "</table>";
            if ($number > 0) {
                while ($i < $number) {
                    $ID = $DB->result($result, $i, "cartridgeitems_id");
                    $name = $DB->result($result, $i, "name");
                    $ref = $DB->result($result, $i, "ref");
                    $fullname = sprintf("%s - %s", $name, $ref);
                    $qtd = $DB->result($result, $i, "qtd");
                    $data = $DB->result($result, $i, "aplicado_data");
                    $aplicado_chamado = $DB->result($result, $i, "aplicado_chamado");
                    $aplicado_chamado = explode(";", $aplicado_chamado);

                    $texto = "";
                    foreach ($aplicado_chamado as $valor) {
                        $texto .= "<a href=\"" . $CFG_GLPI["root_doc"] . "/front/ticket.form.php?id=" . $valor . "\">$valor</a><br>";
                    }
                    // Limita a quantidade de caracteres do campo para exibição
                    /* $contador   = 1;
                      $texto      = '';
                      for ($aux=0;$aux<=strlen($aplicado_chamado)-1;$aux++) {
                      // $texto .= $aplicado_chamado[$aux];
                      $texto .= $aplicado_chamado[$aux];
                      if ($contador==15) {
                      $texto .= "<br>";
                      $contador = 1;
                      }
                      $contador++;
                      } */

                    $aplicado_chamado = $texto;

                    echo "<tr>";
                    echo "<td><a href=\"" . $CFG_GLPI["root_doc"] . "/front/cartridgeitem.form.php?id=" . $ID . "\">" .
                    $fullname . "</a></td>";
                    //echo "<td>$fullname</td>";
                    echo "<td class='center' colspan='2'>$data</td>";
                    echo "<td width='100' class='center'>$aplicado_chamado</td>";
                    echo "<td class='center'>$qtd</td>";
                    echo "<td class='tab_bg_2 center b'>";
                    echo "<select name='sRemoveApl_{$ID}_{$data}' size=1>";
                    echo "<option value='0'>0</option>";
                    for ($j = 1; $j <= $qtd; $j++) {
                        echo "<option value='$j'>$j</option>";
                    }
                    echo "</select>";
                    echo "</td>";
                    echo "</tr>";

                    $i++;
                }
            } else {
                // Se não existirem cartuchos Em Trânsito para este analista
                echo "<tr class='tab_bg_1'>";
                echo "<td colspan='6' class='center'><b>Não existem itens aplicados para este analista.</b></td>";
                echo "</tr>";
            }

            if (Session::haveRight("cartridge", "w")) {
                if ($number > 0) {
                    echo "<tr class='tab_bg_1'>";
                    echo "<td colspan = '4' class='tab_bg_2 center'>&nbsp;</td>";
            
                    echo "<td class='tab_bg_2 center'>";
                    echo "<input type='submit' name='removerTodosAplicados' value=\"" . $LANG['custom_cartridges'][5] . "\" class='submit'>";
                    echo "</td>";
                    echo "<td class='tab_bg_2 center'>";
                    echo "<input type='submit' name='removerAplicadosSelecionados' value=\"" . $LANG['custom_cartridges'][6] . "\" class='submit'>";
                    echo "</td>";
                    echo "</tr>";
                }
            }    
            
            echo "<tr>";
            echo "<td colspan='6' class='tab_bg_2 center'>&nbsp;</td>";
            echo "</tr>";

            echo "<tr>";
            echo "<th colspan='6'>Equipamentos em trânsito alocados para o analista&nbsp;:</th>";
            echo "</tr>";
            echo "<tr>";
            echo "<th colspan = '4'>Equipamento</th>";
            echo "<th width='21%'>Chamado</th>";
            echo "<th width='15%'>Remover</th>";
            echo "</tr>";
            
            $tabelas = array('glpi_printers','glpi_computers','glpi_monitors','glpi_networkequipments','glpi_peripherals','supridesk_pecas');
            //var_export($uID);
            foreach($tabelas as $tb){
                
                if($tb == 'supridesk_pecas'){
                    $sql = '';
                }else{
                    $sql = ', `serial`';
                }
                
                $qry = "SELECT `name`, `id` $sql
                        FROM {$tb} 
                        WHERE `alocado_para` != ''
                                AND `alocado_para` = {$uID}";
                //var_export($qry);
                
                $rs = $DB->query($qry);
                $number = $DB->numrows($rs);
                
                if ($number > 0) {
                    $var = 1;
                    switch ($tb){
                        case 'glpi_printers':
                            $form = 'printer';
                            break;
                        case 'glpi_computers':
                            $form = 'computer';
                            break;
                        case 'glpi_monitors':
                            $form = 'monitor';
                            break;
                        case 'glpi_networkequipments':
                            $form = 'networkequipment';
                            break;
                        case 'glpi_peripherals':
                            $form = 'peripheral';
                            break;
                        case 'supridesk_pecas':
                            $form = 'stock';
                            break;
                    }  
                    $i = 0;
                                        
                    while($i < $number){ 
                        $name = $DB->result($rs, $i, "name");  
                        
                        if($tb != 'supridesk_pecas'){
                            $serial = $DB->result($rs, $i, "serial"); 
                        }
                                                 
                        $id = $DB->result($rs, $i, "id");
                        
                        $busca_chamado = "SELECT `tickets_id` 
                                        FROM `supridesk_tickets_equipamentos`
                                        WHERE `equipamentos_id` = {$id}
                                                AND `type` = '{$form}' ";
                                                
                        $resultchamado = $DB->query($busca_chamado);
                        $rowchamado = $DB->fetch_assoc($resultchamado);                       
                                                          
                        if($name == ''){
                           $name = $serial;
                        }
                        
                        //if($rowchamado['tickets_id'] == ''){
                        //   $rowchamado['tickets_id'] = '-'; 
                        //}
                       
                        $form2[$form][$id]= $form."_".$id;
                        
                        echo "<tr class='tab_bg_1'>";

                        echo "<td colspan = '4'><a href=\"" . $CFG_GLPI["root_doc"] . "/front/{$form}.form.php?id=" . $id . "\">" .
                        $name . "</a><input type='hidden' name='{$form2[$form][$id]}' value={$id}></td>";

                        echo "<td align = 'center'><a href=\"" . $CFG_GLPI["root_doc"] . "/front/ticket.form.php?id=" . $rowchamado['tickets_id'] . "\">" .
                         "{$rowchamado['tickets_id']}</a></td>";
                        echo "<td class='tab_bg_2 center'><a href='" . $CFG_GLPI["root_doc"] .
                        "/front/cartridgeitem.form.php?deleteequip=delete&amp;id=" . $id .
                        "&amp;tID=" . $uID . "&amp;type=".$form."&amp;ticket=".$rowchamado['tickets_id']."'><img title=\"" . $LANG['buttons'][6] . "\" alt=\"" . $LANG['buttons'][6] . "\" src='" . $CFG_GLPI["root_doc"] . "/pics/delete.png'></a></td>";   

                        echo "</tr>";
                        $i++;

                    }                    
                }
                
            }
            
            if(!isset($var)){                
                    
                echo "<tr class='tab_bg_1'>";
                echo "<td colspan = '5' class='center'><b>Não existem equipamentos em trânsito para este analista.</b></td>";
                echo "</tr>";                
            }
            
            if (Session::haveRight("cartridge", "w")) {
                if ($var == 1) {
                    echo "<tr class='tab_bg_1'>";
                    echo "<td colspan = '6' class='tab_bg_2 center'>&nbsp;</td>";                    
                    //echo "<td class='tab_bg_2 center'>";
                    //echo "<input type='submit' name='removerReservas' value='Remover Todos' class='submit'>";
                    //echo "</td>";
                    echo "</tr>";
                }
            } 
            
            echo "<tr>";
            echo "<td  class='tab_bg_2' width='30%' height='30' align='left'>";
            echo "Equipamentos: <input type='hidden' name='uID' value='$uID'>";


            $options = array('dropdown_label' => 'Selecione o equipamento',
                'myname' => 'items_id',
                'entity_sons' => true,
                'show_used_mark' => true,
                'mark_table' => 'supridesk_contracts_items_printers',
                'mark_active' => 'is_active');

            //sempre mostra equipamentos de todas entidades, pois no supridesk está associado o local físico
            Ticket::dropdownAllDevices(0, 'Printer', 0, 1, 0, 0, $options);
            echo "</td>";
            echo "<td colspan='4' class='tab_bg_2' width='30%' height='30' align='left'>";
            echo "&nbsp;&nbsp;<input name='chamado_equip' size='10'>&nbsp;&nbsp;<input type='submit' name='addEquipamentoC' value=' Adicionar Chamado ' class='submit'>";
            echo "</td>";
            echo "<td class='tab_bg_2' width='30%' height='30' align='left'>";
            echo "&nbsp;&nbsp;<input type='submit' name='addEquipamentoR' value=' Adicionar Reserva ' class='submit'>";
            echo "</td>";
            echo "</tr>";
            
            echo "<tr>";
            echo "<td colspan='6' class='tab_bg_2' width='30%' height='30' align='center'>";
            echo "<a href='" . $CFG_GLPI["root_doc"] . "/front/report.termo_equip.php?id={$uID}' target='_blank'>Imprimir</a>";
            echo "</td>";
            echo "</tr>";
        }
        echo "</table>";

        Html::closeForm();
        echo "</div>";
    }

    /**
     * Print out the cartridges of a defined type
     *
     * @param $cartitem object of CartridgeItem class
     * @param $show_old boolean : show old cartridges or not.
     *
     * @return Nothing (displays)
     * */
    static function showForCartridgeItem(CartridgeItem $cartitem, $show_old = 0) {
        global $DB, $CFG_GLPI, $LANG;

        $tID = $cartitem->getField('id');
        if (!$cartitem->can($tID, 'r')) {
            return false;
        }
        $canedit = $cartitem->can($tID, 'w');

        $query = "SELECT count(*) AS COUNT
                FROM `glpi_cartridges`
                WHERE (`cartridgeitems_id` = '$tID')";

        if ($result = $DB->query($query)) {
            $total = $DB->result($result, 0, "COUNT");
//          $unused = self::getUnusedNumber($tID);
//          $used   = self::getUsedNumber($tID);
//          $old    = self::getOldNumber($tID);

            echo "<div class='spaced'><table class='tab_cadre_fixe'>";
            if (!$show_old) {
                echo "<tr><th colspan='" . ($canedit ? '8' : '6') . "'>" . self::getCount($tID, -1) . "</th>";
                echo "</tr>";
            } else { // Old
                echo "<tr><th colspan='" . ($canedit ? '10' : '8') . "'>" . $LANG['cartridges'][35] . "</th>";
                echo "</tr>";
            }
            $i = 0;
            echo "<tr><th>" . $LANG['common'][2] . "</th><th>" . $LANG['consumables'][23] . "</th>";
            echo "<th>" . $LANG['cartridges'][24] . "</th><th>" . $LANG['consumables'][26] . "</th>";
            echo "<th>" . $LANG['cartridges'][27] . "</th>";

            if ($show_old) {
                echo "<th>" . $LANG['search'][9] . "</th>";
                echo "<th>" . $LANG['cartridges'][39] . "</th>";
            }

            echo "<th width='18%'>" . $LANG['financial'][3] . "</th>";
            if ($canedit) {
                echo "<th colspan='2'>" . $LANG['rulesengine'][7] . "</th>";
            }

            echo "</tr>";
        }

        if (!$show_old) { // NEW
            $where = " AND `glpi_cartridges`.`date_out` IS NULL";
        } else { //OLD
            $where = " AND `glpi_cartridges`.`date_out` IS NOT NULL";
        }

        $stock_time = 0;
        $use_time = 0;
        $pages_printed = 0;
        $nb_pages_printed = 0;
        $ORDER = " `glpi_cartridges`.`date_use` ASC,
                `glpi_cartridges`.`date_out` DESC,
                `glpi_cartridges`.`date_in`";

        if (!$show_old) {
            $ORDER = " `glpi_cartridges`.`date_out` ASC,
                   `glpi_cartridges`.`date_use` ASC,
                   `glpi_cartridges`.`date_in`";
        }
        /* //SUPRISERVICE */
        $query = "SELECT `glpi_cartridges`.*,
                       `glpi_printers`.`id` AS printID,
                       `glpi_printers`.`name` AS printname,
                       `glpi_printers`.`init_pages_counter`,
							  glpi_users.id as user_id
                FROM `glpi_cartridges`
                LEFT JOIN `glpi_printers`
                     ON (`glpi_cartridges`.`printers_id` = `glpi_printers`.`id`)
					 LEFT JOIN `glpi_users`
							ON (`glpi_cartridges`.`alocado_para` = `glpi_users`.`id`)
                WHERE `glpi_cartridges`.`cartridgeitems_id` = '$tID'
                      $where
                ORDER BY $ORDER";

        $pages = array();

        if ($result = $DB->query($query)) {
            $number = $DB->numrows($result);
            while ($data = $DB->fetch_array($result)) {
                $date_in = Html::convDate($data["date_in"]);
                $date_use = Html::convDate($data["date_use"]);
                $date_out = Html::convDate($data["date_out"]);
                $printer = $data["printers_id"];
                $page = $data["pages"];

                echo "<tr class='tab_bg_1'><td class='center'>" . $data["id"] . "</td>";
                /* //SUPRISERVICE */
                echo "<td class='center'>" . self::getStatus($data["date_use"], $data["date_out"], $data["user_id"], $data["aplicado_por"]);
                echo "</td><td class='center'>" . $date_in . "</td>";
                echo "<td class='center'>" . $date_use . "</td>";
                echo "<td class='center'>";
                if (!is_null($date_use)) {
                    if ($data["printID"] > 0) {
                        echo "<a href='" . $CFG_GLPI["root_doc"] . "/front/printer.form.php?id=" .
                        $data["printID"] . "'><span class='b'>" . $data["printname"];
                        if ($_SESSION['glpiis_ids_visible'] || empty($data["printname"])) {
                            echo " (" . $data["printID"] . ")";
                        }
                        echo "</span></a>";
                    } else {
                        echo NOT_AVAILABLE;
                    }
                    $tmp_dbeg = explode("-", $data["date_in"]);
                    $tmp_dend = explode("-", $data["date_use"]);
                    $stock_time_tmp = mktime(0, 0, 0, $tmp_dend[1], $tmp_dend[2], $tmp_dend[0]) - mktime(0, 0, 0, $tmp_dbeg[1], $tmp_dbeg[2], $tmp_dbeg[0]);
                    $stock_time += $stock_time_tmp;
                } else {
                    if (!is_null($data["user_id"])) {
                        echo "<a href='" . $CFG_GLPI["root_doc"] . "/front/user.form.php?id=" . $data["user_id"] . "'>" . $data["user_id"] . "</a>"; //NOT_AVAILABLE;
                    }
                }
                if ($show_old) {
                    echo "</td><td class='center'>";
                    echo $date_out;
                    $tmp_dbeg = explode("-", $data["date_use"]);
                    $tmp_dend = explode("-", $data["date_out"]);
                    $use_time_tmp = mktime(0, 0, 0, $tmp_dend[1], $tmp_dend[2], $tmp_dend[0]) - mktime(0, 0, 0, $tmp_dbeg[1], $tmp_dbeg[2], $tmp_dbeg[0]);
                    $use_time += $use_time_tmp;
                }

                echo "</td>";
                if ($show_old) {
                    // Get initial counter page
                    if (!isset($pages[$printer])) {
                        $pages[$printer] = $data['init_pages_counter'];
                    }
                    echo "<td class='center'>";
                    if ($pages[$printer] < $data['pages']) {
                        $pages_printed += $data['pages'] - $pages[$printer];
                        $nb_pages_printed++;
                        echo ($data['pages'] - $pages[$printer]) . " " . $LANG['printers'][31];
                        $pages[$printer] = $data['pages'];
                    } else if ($data['pages'] != 0) {
                        echo "<span class='tab_bg_1_2'>" . $LANG['cartridges'][3] . "</span>";
                    }
                    echo "</td>";
                }
                echo "<td class='center'>";
                Infocom::showDisplayLink('Cartridge', $data["id"], 1);
                echo "</td>";
                if ($canedit) {
                    echo "<td class='center'>";
                    if (!is_null($date_use)) {
                        echo "<a href='" . $CFG_GLPI["root_doc"] . "/front/cartridge.form.php?restore=restore&amp;id=" .
                        $data["id"] . "&amp;tID=$tID'>" . $LANG['consumables'][37] . "</a>";
                    } else {
                        echo "&nbsp;";
                    }
                    echo "</td>";
                }
                if ($canedit) {
                    echo "<td class='center'>";
                    echo "<a href='" . $CFG_GLPI["root_doc"] . "/front/cartridge.form.php?delete=delete&amp;id=" .
                    $data["id"] . "&amp;tID=$tID'><img title=\"" . $LANG['buttons'][6] . "\" alt=\"" . $LANG['buttons'][6] . "\" src='" . $CFG_GLPI["root_doc"] . "/pics/delete.png'></a>";
                    echo "</td>";
                }
                echo "</tr>";
            }
            if ($show_old && $number > 0) {
                if ($nb_pages_printed == 0) {
                    $nb_pages_printed = 1;
                }
                echo "<tr class='tab_bg_2'><td colspan='3'>&nbsp;</td>";
                echo "<td class='center b'>" . $LANG['cartridges'][40] . "&nbsp;:<br>";
                echo round($stock_time / $number / 60 / 60 / 24 / 30.5, 1) . " " . $LANG['financial'][57] . "</td>";
                echo "<td>&nbsp;</td>";
                echo "<td class='center b'>" . $LANG['cartridges'][41] . "&nbsp;:<br>";
                echo round($use_time / $number / 60 / 60 / 24 / 30.5, 1) . " " . $LANG['financial'][57] . "</td>";
                echo "<td class='center b'>" . $LANG['cartridges'][42] . "&nbsp;:<br>";
                echo round($pages_printed / $nb_pages_printed) . "</td>";
                echo "<td colspan='" . ($canedit ? '3' : '1') . "'>&nbsp;</td></tr>";
            }
        }
        echo "</table></div>\n\n";
    }

    /**
     * Print out a link to add directly a new cartridge from a cartridge item.
     *
     * @param $cartitem object of CartridgeItem class
     *
     * @return Nothing (displays)
     * */
    static function showAddForm(CartridgeItem $cartitem) {
        global $CFG_GLPI, $LANG;

        $ID = $cartitem->getField('id');
        if (!$cartitem->can($ID, 'w')) {
            return false;
        }
        if ($ID > 0) {
            echo "<div class='firstbloc'>";
            echo "<form method='post' action=\"" . $CFG_GLPI["root_doc"] . "/front/cartridge.form.php\">";
            echo "<table class='tab_cadre_fixe'>";
            echo "<tr><td class='center tab_bg_2'>";
            echo "<input type='submit' name='add_several' value=\"" . $LANG['buttons'][8] . "\"
                class='submit'>";
            echo "<input type='hidden' name='tID' value='$ID'>\n";
            echo "<span class='small_space'>";
            Dropdown::showInteger('to_add', 1, 1, 100);
            echo "</span>&nbsp;";
            echo $LANG['cartridges'][16] . "</td></tr>";
            echo "</table>";
            Html::closeForm();
            echo "</div>";
        }
    }

    /**
     * Show installed cartridges
     *
     * @param $printer object Printer
     * @param $old boolean : old cartridges or not ?
     *
     * @return nothing (display)
     * */
    static function showInstalled(Printer $printer, $old = 0) {
        global $DB, $CFG_GLPI, $LANG;

        $instID = $printer->getField('id');
        if (!Session::haveRight("cartridge", "r")) {
            return false;
        }
        $canedit = Session::haveRight("cartridge", "w");

        $query = "SELECT `glpi_cartridgeitems`.`id` AS tID,
                       `glpi_cartridgeitems`.`is_deleted`,
                       `glpi_cartridgeitems`.`ref` AS ref,
                       `glpi_cartridgeitems`.`name` AS type,
                       `glpi_cartridges`.`id`,
                       `glpi_cartridges`.`pages` AS pages,
                       `glpi_cartridges`.`date_use` AS date_use,
                       `glpi_cartridges`.`date_out` AS date_out,
                       `glpi_cartridges`.`date_in` AS date_in,
							  `glpi_cartridges`.`tickets_id`
                FROM `glpi_cartridges`,
                     `glpi_cartridgeitems`
                WHERE (`glpi_cartridges`.`date_out` IS " . ($old ? "NOT" : "") . " NULL
                       AND `glpi_cartridges`.`printers_id` = '$instID'
                       AND `glpi_cartridges`.`cartridgeitems_id` = `glpi_cartridgeitems`.`id`)
                ORDER BY `glpi_cartridges`.`date_out` ASC,
                         `glpi_cartridges`.`date_use` DESC,
                         `glpi_cartridges`.`date_in`";

        $result = $DB->query($query);
        $number = $DB->numrows($result);
        $i = 0;

        $pages = $printer->fields['init_pages_counter'];
        if ($canedit) {
            echo "<form method='post' action=\"" . $CFG_GLPI["root_doc"] . "/front/cartridge.form.php\">";
        }
        echo "<div class='spaced'><table class='tab_cadre_fixe'>";
        if ($old == 0) {
            /* //SUPRISERVICE */
            echo "<tr><th colspan='" . ($canedit ? '6' : '5') . "'>" . $LANG['cartridges'][33] . "&nbsp;:</th></tr>";
        } else {
            /* //SUPRISERVICE */
            echo "<tr><th colspan='" . ($canedit ? '8' : '7') . "'>" . $LANG['cartridges'][35] . "&nbsp;:</th></tr>";
        }
        echo "<tr><th>" . $LANG['common'][2] . "</th><th>" . $LANG['cartridges'][12] . "</th>";
        echo "<th>" . $LANG['cartridges'][24] . "</th>";
        echo "<th>" . $LANG['consumables'][26] . "</th>";
        if ($old != 0) {
            echo "<th>" . $LANG['search'][9] . "</th><th>" . $LANG['cartridges'][39] . "</th>";
            /* //SUPRISERVICE */
            echo "<th>ID Chamado</th>";
        }
        //
        if ($canedit) {
            echo "<th>" . $LANG['rulesengine'][7] . "</th>";
        }
        echo "</tr>";
        $stock_time = 0;
        $use_time = 0;
        $pages_printed = 0;
        $nb_pages_printed = 0;

        while ($data = $DB->fetch_array($result)) {
            $cart_id = $data["id"];
            $date_in = Html::convDate($data["date_in"]);
            $date_use = Html::convDate($data["date_use"]);
            $date_out = Html::convDate($data["date_out"]);
            echo "<tr class='tab_bg_1" . ($data["is_deleted"] ? "_2" : "") . "'>";
            echo "<td class='center'>" . $data["id"] . "</td>";
            echo "<td class='center'>";
            echo "<a href=\"" . $CFG_GLPI["root_doc"] . "/front/cartridgeitem.form.php?id=" . $data["tID"] . "\">" .
            $data["type"] . " - " . $data["ref"] . "</a></td>";
            echo "<td class='center'>" . $date_in . "</td>";
            echo "<td class='center'>";

            if ($old == 0 && $canedit) {
                Html::showDateFormItem("date_use[$cart_id]", $data["date_use"], false, true, $date_in);
            } else {
                echo $date_use;
            }

            $tmp_dbeg = explode("-", $data["date_in"]);
            $tmp_dend = explode("-", $data["date_use"]);

            $stock_time_tmp = mktime(0, 0, 0, $tmp_dend[1], $tmp_dend[2], $tmp_dend[0]) - mktime(0, 0, 0, $tmp_dbeg[1], $tmp_dbeg[2], $tmp_dbeg[0]);
            $stock_time += $stock_time_tmp;

            if ($old != 0) {
                echo "</td>";
                echo "<td class='center'>";
                if ($canedit) {
                    Html::showDateFormItem("date_out[$cart_id]", $data["date_out"], true, true, $date_use);
                } else {
                    echo $date_out;
                }

                $tmp_dbeg = explode("-", $data["date_use"]);
                $tmp_dend = explode("-", $data["date_out"]);

                $use_time_tmp = mktime(0, 0, 0, $tmp_dend[1], $tmp_dend[2], $tmp_dend[0]) - mktime(0, 0, 0, $tmp_dbeg[1], $tmp_dbeg[2], $tmp_dbeg[0]);
                $use_time+=$use_time_tmp;

                /* //SUPRISERVICE */
                echo "</td><td class='center'>";
                //--

                if ($canedit) {
                    echo "<input type='text' name='pages[$cart_id]' value=\"" . $data['pages'] . "\" size='6'>";
                } else {
                    echo "<input type='text' name='pages' value=\"" . $data['pages'] . "\" size='6'>";
                }

                if ($pages < $data['pages']) {
                    $pages_printed += $data['pages'] - $pages;
                    $nb_pages_printed++;
                    //echo "&nbsp;".($data['pages']-$pages)." ".$LANG['printers'][31];
                    $pages = $data['pages'];
                }
                echo "</td>";
                /* //SUPRISERVICE */
                echo "<td class='center'>";
                if ($canedit) {
                    echo "<input type='text' name='tickets_id[$cart_id]' value=\"" . $data['tickets_id'] . "\" size='6'>";
                } else {
                    echo "<input type='text' name='tickets_id' value=\"" . $data['tickets_id'] . "\" size='6'>";
                }
            }
            echo "</td>";
            //
            if ($canedit) {
                echo "<td class='center'>";
                if (is_null($date_out)) {
                    echo "<a href='" . $CFG_GLPI["root_doc"] .
                    "/front/cartridge.form.php?uninstall=uninstall&amp;id=" . $data["id"] .
                    "&amp;tID=" . $data["tID"] . "'>" . $LANG['cartridges'][29] . "</a>";
                } else {
                    echo "<a href='" . $CFG_GLPI["root_doc"] .
                    "/front/cartridge.form.php?delete=delete&amp;id=" . $data["id"] .
                    "&amp;tID=" . $data["tID"] . "'><img title=\"" . $LANG['buttons'][6] . "\" alt=\"" . $LANG['buttons'][6] . "\" src='" . $CFG_GLPI["root_doc"] . "/pics/delete.png'></a>";
                }
                echo "</td></tr>";
            }
        }
        if ($old == 0) {
            if ($canedit) {
                echo "<tr class='tab_bg_1'><td colspan='2' class='center'>";
                echo "<input type='hidden' name='pID' value='$instID'>";
                if ($number > 0) {
                    echo "<input type='submit' name='update_cart_use' value=\"" . $LANG['buttons'][7] . "\" class='submit'>";
                }
                echo "</td><td  colspan='3' class='tab_bg_2 center'>";
                if (CartridgeItem::dropdownForPrinter($printer)) {
                    echo "&nbsp;<input type='submit' name='install' value=\"" . $LANG['buttons'][4] . "\"
                           class='submit'>";
                }
                echo "</td></tr>";
            }
        } else { // Print average
            if ($number > 0) {
                if ($nb_pages_printed == 0) {
                    $nb_pages_printed = 1;
                }
                /* //SUPRISERVICE */
                echo "<tr class='tab_bg_2'><td colspan='5'>&nbsp;</td>";
                echo "<td class='center b'>" . $LANG['cartridges'][40] . "&nbsp;:<br>";
                echo round($stock_time / $number / 60 / 60 / 24 / 30.5, 1) . " " . $LANG['financial'][57] . "</td>";
                echo "<td class='center b'>" . $LANG['cartridges'][41] . ":<br>";
                echo round($use_time / $number / 60 / 60 / 24 / 30.5, 1) . " " . $LANG['financial'][57] . "</td>";
                /* //SUPRISERVICE */
                //echo "<td class='center b'>".$LANG['cartridges'][42].":<br>";
                //echo round($pages_printed/$nb_pages_printed)."</td>";
                if ($canedit) {
                    echo "<td>";
                    echo "<input type='submit' name='update_cart_out' value=\"" . $LANG['buttons'][7] . "\" class='submit'>";
                    echo "</td>";
                }
                echo "</tr>";
            }
        }
        echo "</table></div>";
        if ($canedit) {
            Html::closeForm();
        }
    }

    /**
     * Get notification parameters by entity
     * @param entity the entity
     */
    static function getNotificationParameters($entity = 0) {
        global $DB, $CFG_GLPI;

        //Look for parameters for this entity
        $query = "SELECT `cartridges_alert_repeat`
                FROM `glpi_entitydatas`
                WHERE `entities_id`='$entity'";
        $iterator = $DB->request($query);

        if (!$iterator->numrows()) {
            //No specific parameters defined, taking global configuration params
            return $CFG_GLPI['cartridges_alert_repeat'];
        } else {
            $datas = $iterator->next();
            //This entity uses global parameters -> return global config
            if ($datas['cartridges_alert_repeat'] == -1) {
                return $CFG_GLPI['cartridges_alert_repeat'];
            }
            // ELSE Special configuration for this entity
            return $datas['cartridges_alert_repeat'];
        }
    }

    function getTabNameForItem(CommonGLPI $item, $withtemplate = 0) {
        global $LANG;

        if (!$withtemplate && Session::haveRight("cartridge", "r"))
            switch ($item->getType()) {
                case 'Printer' :
                    if ($_SESSION['glpishow_count_on_tabs']) {
                        return self::createTabEntry($LANG['Menu'][21], self::countForPrinter($item));
                    }
                    return $LANG['Menu'][21];

                case 'CartridgeItem' :
                    if ($_SESSION['glpishow_count_on_tabs']) {
                        return self::createTabEntry($LANG['Menu'][21], self::countForCartridgeItem($item));
                    }
                    return $LANG['Menu'][21];
                /* //SUPRISERVICE */
                case 'User' :
                    if ($_SESSION['glpishow_count_on_tabs']) {
                        return self::createTabEntry($LANG['custom_cartridges'][1], self::countForUser($item));
                    }
                    return $LANG['custom_cartridges'][1];
            }
        return '';
    }

    /* //SUPRISERVICE */

    static function countForUser(User $user) {

        $restrict = "`glpi_cartridges`.`alocado_para` = '" . $user->getField('id') . "' and date_use IS NULL AND aplicado_por IS NULL";

        return countElementsInTable(array('glpi_cartridges'), $restrict);
    }

    static function countForCartridgeItem(CartridgeItem $item) {

        $restrict = "`glpi_cartridges`.`cartridgeitems_id` = '" . $item->getField('id') . "'";

        return countElementsInTable(array('glpi_cartridges'), $restrict);
    }

    static function countForPrinter(Printer $item) {

        $restrict = "`glpi_cartridges`.`printers_id` = '" . $item->getField('id') . "'";

        return countElementsInTable(array('glpi_cartridges'), $restrict);
    }

    static function displayTabContentForItem(CommonGLPI $item, $tabnum = 1, $withtemplate = 0) {

        switch ($item->getType()) {
            case 'Printer' :
                self::showInstalled($item);
                self::showInstalled($item, 1);
                return true;

            case 'CartridgeItem' :
                self::showAddForm($item);
                self::showForCartridgeItem($item);
                self::showForCartridgeItem($item, 1);
                return true;
            /* //SUPRISERVICE */
            case 'User' :
                self::showForUser($item);
                return true;
        }
    }   
    

}

?>