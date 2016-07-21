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

class Contract_Item_Printer_Supridesk extends CommonDBRelation {

    // From CommonDBRelation
    public $itemtype_1 = 'Contracts_Items_Supridesk';
    public $items_id_1 = 'contracts_items_id';
    public $itemtype_2 = 'Printer';
    public $items_id_2 = 'printers_id';
    public $itemtype_3 = 'Agrupamento_Supridesk';
    public $items_id_3 = 'agrupamentos_id';
    protected $table = "supridesk_contracts_items_printers";

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
     * */
    function getSearchOptions() {
        global $LANG;

        //$tab = parent::getSearchOptions();

        $tab[7]['table'] = 'supridesk_agrupamentos';
        $tab[7]['field'] = 'name';
        $tab[7]['name'] = 'Agrupamento';
        $tab[7]['datatype'] = 'dropdown';

        return $tab;
    }

    function moveToGroup($contractItemPrinterID, $agrupamentoID) {
        global $DB;

        $query = "UPDATE `supridesk_contracts_items_printers`
						SET	`agrupamentos_id` = $agrupamentoID
						WHERE `id` = $contractItemPrinterID ";
        $DB->query($query);
    }

    function unassignPrinterbyID($ID) {
        global $DB;

        $query = "SELECT *
                FROM `supridesk_contracts_items_printers`
                WHERE `id` = '$ID'";
        if ($result = $DB->query($query)) {
            // Delete Contract Item Printer
            $query = "DELETE
                   FROM `supridesk_contracts_items_printers`
                   WHERE `id` = '$ID'";
            $DB->query($query);
        }
    }

    function unassignPrinter($contractItem, $printer, $contadorID, $type) {
        global $DB;

        $query = "UPDATE `supridesk_contracts_items_printers`
		SET `is_active` = 0,
                    `out_contadores_id` = $contadorID
		WHERE `contracts_items_id` = $contractItem
                    AND	`printers_id` = $printer 
                    AND `type` = '" . $type . "' ";

        $DB->query($query);
    }

    function assignPrinter($contractItem, $printer, $agrupamento, $contadorID, $replaced_printers_id, $type) {
        global $DB;

        $query = "INSERT INTO
                `supridesk_contracts_items_printers` ( `contracts_items_id`, `printers_id`, `agrupamentos_id`, `is_active`, `in_contadores_id`, `replaced_printers_id`, `type`)
                VALUES ( $contractItem, $printer, $agrupamento, 1, $contadorID, $replaced_printers_id, '" . $type . "'  )";

        if ($DB->query($query)) {
            return $DB->insert_id();
        }
        return null;
    }

    function addContador($date, $impressao_mono, $impressao_color, $copia_mono, $copia_color, $digitalizacao_copia, $digitalizacao_fax, $digitalizacao_rede, $digitalizacao_usb) {
        global $DB;

        $query = "INSERT INTO
                        `supridesk_contadores` ( `date`, `impressao_total`, `impressao_mono`, `impressao_color`, `copia_total`, `copia_mono`, `copia_color`, `digitalizacao_copia`, `digitalizacao_fax`, `digitalizacao_rede`, `digitalizacao_usb` )
                        VALUES (
                                               '$date',
                                               $impressao_mono + $impressao_color,
                                               $impressao_mono,
                                               $impressao_color,
                                               $copia_mono + $copia_color,
                                               $copia_mono,
                                               $copia_color,
                                               $digitalizacao_copia,
                                               $digitalizacao_fax,
                                               $digitalizacao_rede,
                                               $digitalizacao_usb )";
        if ($DB->query($query)) {
            return $DB->insert_id();
        }
        return null;
    }

    function maybeDeleted() {

        return false;
    }

    static function showForContractItem($ID, $canedit, $withtemplate) {
        global $DB, $CFG_GLPI, $LANG;

        // ********* SUPRISERVICE ************
        $query = "SELECT 
                    `supridesk_contracts_items_printers`.*, 
                    `supridesk_agrupamentos`.`name` as agrupamento 
                FROM `supridesk_contracts_items_printers`
                LEFT JOIN `supridesk_agrupamentos`
                    ON (`supridesk_contracts_items_printers`.`agrupamentos_id` = `supridesk_agrupamentos`.`id`)
                WHERE contracts_items_id = '$ID' AND `is_active` = 1";

        $result = $DB->query($query);

        $rand = mt_rand();

        $used = array();

        $showmassiveactions = false;
        if ($withtemplate != 2) {
            $showmassiveactions = count(Dropdown::getMassiveActions(__CLASS__));
        }

        if ($DB->numrows($result) > 0) {

            if ($showmassiveactions) {
                echo "\n<form id='contracts_items_printers$rand' name='contracts_items_printers$rand' method='post'
                                    action='" . $CFG_GLPI["root_doc"] . "/front/contract_item_supridesk.form.php'>\n";
            }

            echo "\n<table width='100%'>";

            echo "<tr>";
            if ($showmassiveactions) {
                echo "<th>&nbsp;</th>\n";
            }
            echo "<th>" . "ID" . "</th>\n";
            echo "<th width='80px'>" . "Serial" . "</th>\n";
            echo "<th>" . "Modelo" . "</th>\n";
            echo "<th>" . "Equipamento" . "</th>\n";
            echo "<th>" . "Agrupamento" . "</th>\n";
            echo "<th>" . "Tipo" . "</th>\n";
            echo "</tr>";

            //lista todas as impressoras
            while ($line = $DB->fetch_array($result)) {

                if ($line['type'] == 'Printer') {
                    $sql = "SELECT * FROM `glpi_printers` WHERE `id` = {$line['printers_id']}";
                    $result_sql = $DB->query($sql);

                    $dados = $DB->fetch_array($result_sql);
                    $form = 'printer';
                    $type = 'Impressora';
                    $table = 'glpi_printers';
                }

                if ($line['type'] == 'Computer') {
                    $sql = "SELECT * FROM `glpi_computers` WHERE `id` = {$line['printers_id']}";
                    $result_sql = $DB->query($sql);

                    $dados = $DB->fetch_array($result_sql);
                    $form = 'computer';
                    $type = 'Computador';
                    $table = 'glpi_computers';
                }

                if ($line['type'] == 'Monitor') {
                    $sql = "SELECT * FROM `glpi_monitors` WHERE `id` = {$line['printers_id']}";
                    $result_sql = $DB->query($sql);

                    $dados = $DB->fetch_array($result_sql);
                    $form = 'monitor';
                    $type = 'Monitor';
                    $table = 'glpi_monitors';
                }


                $used[$line["printers_id"]] = $line["printers_id"];

                echo "<tr>";
                if ($showmassiveactions) {
                    echo "<td class='center' width='20'>";
                    echo "<input type='checkbox' name='move_to_group[" . $line['id'] . "]' value='1'>";
                    echo "</td>\n";
                }
                //ID
                echo "<td align='center' width='50'>{$line['printers_id']}</td>";
                //serial
                echo "<td>{$dados['serial']}</td>";
                //modelo
                echo "<td>{$dados['name']}</td>";
                //equipamento / link
                echo "<td width='150'>";
                echo "<a href='" . $CFG_GLPI["root_doc"] . "/front/{$form}.form.php?id=" . $line["printers_id"] . "'>";

                echo Dropdown::getDropdownName($table, $line["printers_id"]) . "&nbsp;";
                Html::showToolTip("<b>" . $LANG['common'][19] . "&nbsp;:</b> " . $dados['serial'] . "<br>
          <b>" . $LANG['common'][20] . "&nbsp;:</b> " . $dados['otherserial'] . "<br>
          <b>" . $LANG['joblist'][0] . "&nbsp;:</b> " . $dados['completename']);

                echo "</a>";
                echo "</td>";
                //agrupamento
                echo "<td width='100'>{$line['agrupamento']}</td>";
                //tipo
                echo "<td width='100'>{$type}</td>";

                echo "</tr>\n";
            }

            echo "</table>";

            if ($showmassiveactions) {
                Html::openArrowMassives("contracts_items_printers$rand", true);
                $ci = new Contract_Item_Supridesk();
                $ci->getFromDB($ID);
                Dropdown::showForMassiveAction('Contract_Item_Printer_Supridesk', 0, Array('contractID' => $ci->fields['contracts_id']));
                $actions = array();
                Html::closeArrowMassives($actions);
                Html::closeForm();
            }
        } else {
            echo "&nbsp;";
        }
        return $used;
    }

    static function showForContractItemForm($ID) {

        global $DB, $CFG_GLPI, $LANG;
        $item = new Contract_Item_Supridesk();

        if ($ID && $item->can($ID, 'w')) {

            echo "\n<div class='center'>";
            echo "<form method='post' action='" . $CFG_GLPI["root_doc"] . "/front/contract_item_supridesk.form.php'>";
            echo "<input type='hidden' name='contracts_items_id' value='$ID'>\n";

            echo "<table class='tab_cadre' width='800px'>";
            echo "<tr><th>" . $LANG['tarifacao'][29] . "</th></tr>\n";
            echo "<tr class='tab_bg_2'><td>";
            $used = self::showForContractItem($ID, true, 0);

            //var_export($used);
            //die();

            echo "</td></tr>\n";

            echo "<tr class='tab_bg_2'><td>";
            echo "Itens da lista filtrada que possuem <b>*</b> são equipamentos já associados a outro item de contrato.";
            echo "<br><br>";
            $entities_id = self::getEntityFromContractItemID($ID);
            $ci = new Contract_Item_Supridesk();
            $ci->getFromDB($ID);

            $options_replaced = array('dropdown_label' => '',
                'myname' => 'replaced_printers_id',
                'entity_sons' => true,
                'separador' => ' ',
                'show_used_mark' => true,
                'mark_table' => 'supridesk_contracts_items_printers',
                'mark_active' => 'is_active');

            $options = array('dropdown_label' => 'Selecione o equipamento',
                'myname' => 'items_id',
                'entity_sons' => true,
                'show_used_mark' => true,
                'mark_table' => 'supridesk_contracts_items_printers',
                'mark_active' => 'is_active',
                'separador' => ' ',
                'extra_fields' => 12,
                'ef_1_label' => 'Data',
                'ef_1_id' => 'date',
                'ef_1_type' => 'date',
                'ef_1_size' => 30,
                'ef_2_label' => 'Contador de impressões mono',
                'ef_2_id' => 'impressao_mono',
                'ef_2_type' => 'text',
                'ef_2_size' => 10,
                'ef_3_label' => 'Contador de impressões color',
                'ef_3_id' => 'impressao_color',
                'ef_3_type' => 'text',
                'ef_3_size' => 10,
                'ef_4_label' => 'Contador de cópias mono',
                'ef_4_id' => 'copia_mono',
                'ef_4_type' => 'text',
                'ef_4_size' => 10,
                'ef_5_label' => 'Contador de cópias color',
                'ef_5_id' => 'copia_color',
                'ef_5_type' => 'text',
                'ef_5_size' => 10,
                'ef_6_label' => 'Digitalizações (Cópia)',
                'ef_6_id' => 'digitalizacao_copia',
                'ef_6_type' => 'text',
                'ef_6_size' => 10,
                'ef_7_label' => 'Digitalizações (Fax)',
                'ef_7_id' => 'digitalizacao_fax',
                'ef_7_type' => 'text',
                'ef_7_size' => 10,
                'ef_8_label' => 'Digitalizações (Rede)',
                'ef_8_id' => 'digitalizacao_rede',
                'ef_8_type' => 'text',
                'ef_8_size' => 10,
                'ef_9_label' => 'Digitalizações (USB)',
                'ef_9_id' => 'digitalizacao_usb',
                'ef_9_type' => 'text',
                'ef_9_size' => 10,
                'ef_10_type' => 'separator',
                'ef_10_label' => 'Para nova associação, preencha os campos abaixo:',
                'ef_11_label' => 'Agrupamento',
                'ef_11_id' => 'agrupamentos_id',
                'ef_11_value' => 0,
                'ef_11_type' => 'dropdown',
                'ef_11_value2' => $ci->fields["contracts_id"],
                'ef_12_label' => 'Equipamento substituído (em branco caso seja instalação)',
                'ef_12_id' => 'replaced_printers_id',
                'ef_12_type' => 'dropdown_printer',
                'ef_12_options' => $options_replaced);

            //sempre mostra equipamentos de todas entidades, pois no supridesk está associado o local físico
            Ticket::dropdownAllDevices(0, 'Printer', 0, 1, 0, 0, $options);

            //var_export(Ticket::dropdownAllDevices(0, 'Printer', 0, 1, 0, 0, $options));
            // die();
            echo "<br>&nbsp;<input type='submit' name='assign_printer' value='&nbsp;" . $LANG['tarifacao'][43] . "&nbsp;' class='submit'>";
            echo "&nbsp;&nbsp;<input type='submit' name='unassign_printer' value='&nbsp;" . $LANG['tarifacao'][31] . "&nbsp;' class='submit'>";
            echo "</td></tr>";
            echo "</table>";
            Html::closeForm();
        }
    }

    static function getPrinterAlreadyUsed($printerID, $contractItemID = null, $itemtype = null) {
        global $DB;

        $query = "SELECT *
                FROM `supridesk_contracts_items_printers`
		WHERE `printers_id` = $printerID
		AND is_active = 1 ";

        if ($contractItemID != null) {
            $query .= " AND contracts_items_id = $contractItemID";
        }

        if ($itemtype != null) {
            if ($itemtype == 'Printer') {
                $query .= " AND type = 'Printer'";
            } elseif ($itemtype == 'Computer') {
                $query .= " AND type = 'Computer'";
            } elseif ($itemtype == 'Monitor') {
                $query .= " AND type = 'Monitor'";
            }
        }

        $result = $DB->query($query);
        return ($DB->numrows($result) > 0);
    }

    static function getDados($table, $id) {
        die();
        global $DB;

        $sql = "SELECT * FROM '" . $table . "'             
                WHERE `id` = {$id}";
        var_export($sql);
        die();
        $result = $DB->query($sql);

        if ($DB->numrows($result) > 0) {
            return $DB->fetch_array($result);
        }
    }

    static function getEntityFromContractItemID($contractItemID) {
        global $DB;

        $query = "SELECT `glpi_contracts`.`entities_id`
                FROM `glpi_contracts`, `supridesk_contracts_items`
                WHERE `glpi_contracts`.`id` = `supridesk_contracts_items`.`contracts_id`
                AND `supridesk_contracts_items`.`id` = $contractItemID";
        $result = $DB->query($query);

        if ($DB->numrows($result) > 0) {
            if ($line = $DB->fetch_array($result))
                return $line["entities_id"];
        }

        return -1;
    }

    function getFromIDs($contractItemID, $printerID = null, $active = null) {
        global $DB;

        $query = "SELECT *
               FROM `supridesk_contracts_items_printers`
					WHERE 1=1 ";
        if ($contractItemID != null)
            $query .= " AND `supridesk_contracts_items_printers`.`contracts_items_id` = $contractItemID";
        if ($printerID != null)
            $query .= " AND `supridesk_contracts_items_printers`.`printers_id` = $printerID";
        if ($active != null)
            $query .= " AND `supridesk_contracts_items_printers`.`is_active` = $active";
        else
            $query .= " AND `supridesk_contracts_items_printers`.`is_active` = 1";

        if ($result = $DB->query($query)) {
            if ($DB->numrows($result) == 1) {
                $this->fields = $DB->fetch_assoc($result);
                $this->post_getFromDB();
            }
            return intval($DB->numrows($result)) > 0;
        }
        return 0;
    }

    function getTabNameForItem(CommonGLPI $item, $withtemplate = 0) {
        global $LANG;

        if (!$withtemplate) {
            switch ($item->getType()) {
                case 'Contract_Item_Supridesk' :
                    if ($_SESSION['glpishow_count_on_tabs']) {
                        return self::createTabEntry($LANG['tarifacao'][29], countElementsInTable($this->getTable(), "contracts_items_id = '" . $item->getID() . "' and is_active = 1"));
                    }
                    return $LANG['tarifacao'][29];
            }
        }
        return '';
    }

    static function displayTabContentForItem(CommonGLPI $item, $tabnum = 1, $withtemplate = 0) {
        if ($item->getType() == 'Contract_Item_Supridesk') {
            self::showForContractItemForm($item->getID());
        }
        return true;
    }

}

?>
