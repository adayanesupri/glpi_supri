<?php

/*
 * @version $Id: networkport.class.php 18771 2012-06-29 08:49:19Z moyo $
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

/// Contract_Item_Supridesk class
class Fusioninventory_Printerlogs extends CommonDBTM {

    // From CommonDBTM
    public $dohistory = true;
    protected $table = "glpi_plugin_fusinvsnmp_printerlogs";

    static function getTypeName($nb = 0) {
        global $LANG;

        return "Log de contador de impressão";
    }

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

    function prepareInputForUpdate($input) {
        return $input;
    }

    function pre_deleteItem() {
        return true;
    }

    function defineTabs($options = array()) {
        global $LANG;

        $ong = array();
        $this->addStandardTab('Contract_Item_Printer_Supridesk', $ong, $options);
        $this->addStandardTab('Log', $ong, $options);

        return $ong;
    }

    /**
     * Show ports for an item
     *
     * @param $item CommonDBTM object
     * @param $withtemplate integer : withtemplate param
     * */
    static function showForItem(CommonDBTM $item, $withtemplate = '') {
        global $DB, $CFG_GLPI, $LANG;

        $rand = mt_rand();

        $itemtype = $item->getType();
        $printers_id = $item->getField('id');

        if (!Session::haveRight('contract', 'r') || !$item->can($printers_id, 'r')) {
            return false;
        }

        $canedit = $item->can($printers_id, 'w');
        $showmassiveactions = false;
        if ($withtemplate != 2) {
            $showmassiveactions = count(Dropdown::getMassiveActions(__CLASS__));
        }

        // Show Add Form
        if ($canedit && (empty($withtemplate) || $withtemplate != 2)) {
            echo "\n<div class='firstbloc'><table class='tab_cadre_fixe'>";
            echo "<tr><td class='tab_bg_2 center b'>";
            echo "<a href='" . $CFG_GLPI["root_doc"] . "/front/fusioninventory_printerlogs.form.php?printers_id=$printers_id'>";
            echo "<img src=\"" . $CFG_GLPI["root_doc"] . "/pics/add_dropdown.png\" alt=\"" . $LANG['buttons'][8] . "\" title=\"" . $LANG['buttons'][8] . "\"> ";
            echo "Adicionar um contador para esta impressora";
            echo "</a></td>\n";
            echo "</tr></table></div>\n";
        }

        Session::initNavigateListItems('Fusioninventory_Printerlogs', $item->getTypeName() . " = " . $item->getName());

        $query = "SELECT *
					FROM `glpi_plugin_fusinvsnmp_printerlogs`
					WHERE `printers_id` = $printers_id
					  AND is_deleted = 0
					ORDER BY date DESC";

        if ($result = $DB->query($query)) {
            echo "<div class='spaced'>";

            if ($DB->numrows($result) != 0) {
                $colspan = 6;

                if ($showmassiveactions) {
                    $colspan++;
                    echo "\n<form id='printerlogs$rand' name='printerlogs$rand' method='post'
                     action='" . $CFG_GLPI["root_doc"] . "/front/fusioninventory_printerlogs.form.php'>\n";
                }

                echo "<table class='tab_cadre_fixe'>\n";

                echo "<tr><th colspan='$colspan'>\n";
                echo "Contadores encontrados: " . $DB->numrows($result) . "</th></tr>\n";

                echo "<tr>";
                if ($showmassiveactions) {
                    echo "<th>&nbsp;</th>\n";
                }
                echo "<th>Data</th>\n";
                echo "<th>Total</th>\n";
                echo "<th>Mono</th>\n";
                echo "<th>Color</th>\n";
                echo "<th>Modo de input</th>\n";
                echo "<th>IP</th>\n";

                $i = 0;
                $printerLogs = new Fusioninventory_Printerlogs();

                while ($devid = $DB->fetch_row($result)) {
                    $printerLogs->getFromDB(current($devid));

                    Session::addToNavigateListItems('Fusioninventory_Printerlogs', $printerLogs->fields["id"]);

                    echo "<tr class='tab_bg_1'>\n";
                    if ($showmassiveactions) {
                        echo "<td class='center' width='20'>";
                        echo "<input type='checkbox' name='printerlogs[" . $printerLogs->fields["id"] . "]' value='1'>";
                        echo "</td>\n";
                    }
                    echo "<td class='center'><span class='b'>";
                    if ($canedit && $withtemplate != 2) {
                        echo "<a href=\"" . $CFG_GLPI["root_doc"] . "/front/fusioninventory_printerlogs.form.php?id=" .
                        $printerLogs->fields["id"] . "\">";
                    }
                    echo $printerLogs->fields["date"];
                    if ($canedit && $withtemplate != 2) {
                        echo "</a>";
                    }
                    //Html::showToolTip($printerLogs->fields['comment']);
                    echo "</td>\n";

                    if ($printerLogs->fields["ip"] == 0) {
                        $printerLogs->fields["ip"] = ' 0.0.0.0 ( Sem IP ) ';
                    }

                    echo "<td align='center'>" . $printerLogs->fields["pages_total"] . "</td>\n";
                    echo "<td align='center'>" . $printerLogs->fields["pages_n_b"] . "</td>\n";
                    echo "<td align='center'>" . $printerLogs->fields["pages_color"] . "</td>\n";
                    echo "<td align='center'>" . $printerLogs->fields["import_mode"] . "</td>\n";
                    echo "<td align='center'>" . $printerLogs->fields["ip"] . "</td>\n";

                    if ($canedit && $withtemplate != 2) {
                        echo "</a>";
                    }
                    echo "</tr>\n";
                }
                echo "</table>\n";

                if ($showmassiveactions) {
                    Html::openArrowMassives("printerlogs$rand", true);
                    Dropdown::showForMassiveAction('Fusioninventory_Printerlogs');
                    $actions = array();
                    Html::closeArrowMassives($actions);
                    Html::closeForm();
                }
            } else {
                echo "<table class='tab_cadre_fixe'><tr><th>" . $LANG['tarifacao'][33] . "</th></tr>";
                echo "</table>";
            }
            echo "</div>";
        }
    }

    function showForm($ID, $options = array()) {
        global $CFG_GLPI, $LANG;

        if (!isset($options['several'])) {
            $options['several'] = false;
        }

        if (!Session::haveRight("contract", "r")) {
            return false;
        }

        if ($ID > 0) {
            $this->check($ID, 'r');
        } else {
            $input = array('itemtype' => $options["itemtype"], 'printers_id' => $options["printers_id"]);
            // Create item
            $this->check(-1, 'w', $input);
        }

        $link = NOT_AVAILABLE;

        $item = new Fusioninventory_Printerlogs();
        $type = $item->getTypeName();

        // Ajout des infos deja remplies
        if (isset($_POST) && !empty($_POST)) {
            foreach ($this->fields as $key => $val) {
                if ($key != 'id' && isset($_POST[$key])) {
                    $this->fields[$key] = $_POST[$key];
                }
            }
        }

        $this->showTabs();

        $import_mode = "";
        if ($this->fields["import_mode"] == "FM") {
            $options['canedit'] = FALSE;
            $import_mode = " (contadores de impressoras online não podem ser alterados)";
        }
        
        if($this->fields["ip"] == 0){
            $this->fields["ip"] = " 0.0.0.0 ( Sem IP ) ";
        }

        $options['colspan'] = 2;
        $this->showFormHeader($options);

        echo "<tr class='tab_bg_1'><td>Data&nbsp;:</td>\n<td>";


        if (!($ID > 0)) {
            echo "<input type='hidden' name='printer_logs_id' value='" . $ID . "'>\n";
            $this->fields["import_mode"] = "MANUAL";
        }
        echo "<input type='hidden' name='printers_id' value='" . $this->fields["printers_id"] . "'>\n";

        Html::showDateTimeFormItem('date', $this->fields["date"], 1, false);

        echo "</td></tr>\n";

        echo "<tr class='tab_bg_1'><td class='center' colspan=2></td></tr>";

        //impressões
        echo "<tr class='tab_bg_1'><td>Modo de importação :</td>\n";
        echo "<td>";
        echo $this->fields["import_mode"] . " {$import_mode}";
        echo "</td>";
        echo "<td>IP :</td>\n";
        echo "<td>";
        echo $this->fields["ip"] ;
        echo "</td>";
        echo "</tr>\n";

        //impressões
        echo "<tr class='tab_bg_1'><td>Contador Mono :</td>\n";
        echo "<td>";
        Html::autocompletionTextField($this, "pages_n_b");
        echo "</td>";

        //impressões
        echo "<td>Contador Color :</td>\n";
        echo "<td>";
        Html::autocompletionTextField($this, "pages_color");
        echo "</td>";
        echo "</tr>\n";

        $this->showFormButtons($options);
        $this->addDivForTabs();
    }

    function getSearchOptions() {
        global $LANG;

        $tab = array();
        $tab['common'] = $LANG['common'][32];

        $tab[2]['table'] = $this->getTable();
        $tab[2]['field'] = 'id';
        $tab[2]['name'] = $LANG['common'][2];
        $tab[2]['massiveaction'] = false;

        $tab[6]['table'] = $this->getTable();
        $tab[6]['field'] = 'pages_n_b';
        $tab[6]['name'] = 'Impressões mono';
        $tab[6]['datatype'] = 'number';

        $tab[7]['table'] = $this->getTable();
        $tab[7]['field'] = 'pages_color';
        $tab[7]['name'] = 'Impressões color';
        $tab[7]['datatype'] = 'number';

        return $tab;
    }

    function getTabNameForItem(CommonGLPI $item, $withtemplate = 0) {
        global $LANG, $CFG_GLPI;

        // Can exists on template
        if (Session::haveRight("contract", "r")) {
            switch ($item->getType()) {
                default :
                    if ($_SESSION['glpishow_count_on_tabs'] && in_array($item->getType(), $CFG_GLPI["contract_types"])) {
                        return self::createTabEntry('Contadores', self::countForPrinter($item));
                    }
                    return 'Contadores';
            }
        }
        return '';
    }

    static function countForPrinter(Printer $item) {

        $restrict = "`glpi_plugin_fusinvsnmp_printerlogs`.`printers_id` = '" . $item->getField('id') . "' && is_deleted = 0";

        return countElementsInTable(array('glpi_plugin_fusinvsnmp_printerlogs'), $restrict);
    }

    static function displayTabContentForItem(CommonGLPI $item, $tabnum = 1, $withtemplate = 0) {
        global $CFG_GLPI;

        switch ($item->getType()) {
            case 'Printer' :
                if (in_array($item->getType(), $CFG_GLPI["contract_types"])) {
                    Fusioninventory_Printerlogs::showForItem($item, $withtemplate);
                }
                break;
            default :
                if (in_array($item->getType(), $CFG_GLPI["contract_types"])) {
                    Contract::showAssociated($item, $withtemplate);
                }
        }
        return true;
    }

}

?>
