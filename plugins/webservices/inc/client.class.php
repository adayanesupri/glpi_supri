<?php
/*
 * @version $Id: client.class.php 306 2011-11-08 12:36:05Z remi $
 -------------------------------------------------------------------------
 webservices - WebServices plugin for GLPI
 Copyright (C) 2003-2011 by the webservices Development Team.

 https://forge.indepnet.net/projects/webservices
 -------------------------------------------------------------------------

 LICENSE

 This file is part of webservices.

 webservices is free software; you can redistribute it and/or modify
 it under the terms of the GNU General Public License as published by
 the Free Software Foundation; either version 2 of the License, or
 (at your option) any later version.

 webservices is distributed in the hope that it will be useful,
 but WITHOUT ANY WARRANTY; without even the implied warranty of
 MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 GNU General Public License for more details.

 You should have received a copy of the GNU General Public License
 along with webservices. If not, see <http://www.gnu.org/licenses/>.
 --------------------------------------------------------------------------
 */

// ----------------------------------------------------------------------
// Original Author of file: Remi Collet
// Purpose of file: Classes for XML-RPC plugin
// ----------------------------------------------------------------------

if (!defined('GLPI_ROOT')) {
   die("Sorry. You can't access directly to this file");
}

class PluginWebservicesClient extends CommonDBTM {

   public $dohistory        = true;


   static function getTypeName($nb=0) {
      global $LANG;

      if ($nb>1) {
         return $LANG['webservices'][16];
      }
      return $LANG['webservices'][15];
   }


   function canCreate() {
      return Session::haveRight('config', 'w');
   }


   function canView() {
      return Session::haveRight('config', 'r');
   }


   function defineTabs($options=array()) {

      $ong = array('empty' => $this->getTypeName(1));
      $this->addStandardTab(__CLASS__, $ong, $options);
      $this->addStandardTab('Log', $ong, $options);

      return $ong;
   }


   function getTabNameForItem(CommonGLPI $item, $withtemplate=0) {
      global $LANG;

      if ($item->getType() == __CLASS__) {
         return $LANG['webservices'][7];
      }
      return '';
   }


   static function displayTabContentForItem(CommonGLPI $item, $tabnum=1, $withtemplate=0) {

      if ($item->getType() == __CLASS__) {
         $item->showMethods();
         return true;
      }
      return false;
   }


   function prepareInputForAdd($input) {
      return $this->prepareInputForUpdate($input);
   }


   function prepareInputForUpdate($input) {

      if (isset($input['username'])) {
         if (empty($input['username'])) {
            $input['username'] = "NULL";
            $input['password'] = "NULL";
         } else {
            $input['password'] = md5(isset($input['password']) ? $input['password'] : '');
         }
      }

      if (isset($input['_start']) && isset($input['_end'])) {
         if (empty($input['_start'])) {
            $input['ip_start'] = "NULL";
            $input['ip_end'] = "NULL";
         } else {
            $input['ip_start'] = ip2long($input['_start']);
            if (empty($input['_end'])) {
               $input['ip_end'] = $input['ip_start'];
            } else {
               $input['ip_end'] = ip2long($input['_end']);
            }
            if ($input['ip_end'] < $input['ip_start']) {
               $tmp = $input['ip_end'];
               $input['ip_end'] = $input['ip_start'];
               $input['ip_start'] = $tmp;
            }
         }
      }
      return $input;
   }


   function showForm ($ID, $options=array()) {
      global $DB, $CFG_GLPI, $LANG;

      if ($ID > 0) {
         $this->check($ID,'r');
      } else {
         $this->check(-1,'w');
         $this->getEmpty();
      }

      $this->showTabs($options);
      $this->showFormHeader($options);

      echo "<tr class='tab_bg_1'>";
      echo "<td>".$LANG["common"][16]."&nbsp;:</td><td>";
      Html::autocompletionTextField($this, "name");
      echo "</td>";
      echo "<td rowspan='11' class='middle right'>".$LANG["common"][25]."&nbsp;:</td>";
      echo "<td class='center middle' rowspan='11'><textarea cols='45' rows='16' name='comment' >".
             $this->fields["comment"]."</textarea></td></tr>";

      echo "<tr class='tab_bg_1'>";
      echo "<td >".$LANG["webservices"][1]."&nbsp;:</td><td>";
      Dropdown::showYesNo("is_active",$this->fields["is_active"]);
      echo "</td></tr>";

      echo "<tr class='tab_bg_1'>";
      echo "<td>".$LANG['webservices'][9]."&nbsp;:</td><td>";
      Dropdown::showYesNo("deflate",$this->fields["deflate"])."</td></tr>";
      echo "<tr><td></td><td><i>  " . nl2br($LANG['webservices'][14]) . "</i></td></tr>";

      echo "<tr class='tab_bg_1'>";
      echo "<td >".$LANG["webservices"][6]."&nbsp;:</td><td>";
      Dropdown::showFromArray("do_log", array(0 => $LANG['choice'][0], // Non
                                              1 => $LANG['title'][38], // Historique
                                              2 => $LANG['Menu'][30]), // Jounaux
                              array('value' => $this->fields["do_log"]));
      echo "</td></tr>";

      echo "<tr class='tab_bg_1'>";
      echo "<td >".$LANG['setup'][137]."&nbsp;:</td><td>";
      Dropdown::showYesNo("debug",$this->fields["debug"]);
      echo "</td></tr>";

      echo "<tr class='tab_bg_1'>";
      echo "<td >".$LANG["webservices"][2]."&nbsp;:</td><td>";
      Html::autocompletionTextField($this, "pattern");
      echo "</td></tr>";

      echo "<tr class='tab_bg_1'>";
      echo "<td >".$LANG['webservices'][3]."&nbsp;:</td><td>";
      echo "<input type='text' name='_start' value='".
            ($this->fields["ip_start"] ? long2ip($this->fields["ip_start"]) : '') .
            "' size='17'> - ";
      echo "<input type='text' name='_end' value='" .
            ($this->fields["ip_end"] ? long2ip($this->fields["ip_end"]) : '') .
            "' size='17'></td></tr>";

      echo "<tr class='tab_bg_1'>";
      echo "<td >".$LANG['webservices'][17]."&nbsp;:</td><td>";
      echo "<input type='text' name='ipv6' value='".$this->fields['ipv6'].
            "' size='40'></td></tr>";

      echo "<tr class='tab_bg_1'><";
      echo "td >".$LANG['webservices'][4]."&nbsp;:</td><td>";
      Html::autocompletionTextField($this, "username");
      echo "</td></tr>";

      echo "<tr class='tab_bg_1'>";
      echo "<td >".$LANG['webservices'][5]."&nbsp;:</td><td>";
      echo "<input type='text' name='password' size='40' />";
      echo "</td></tr>";

      $this->showFormButtons($options);

      echo "<div id='tabcontent'></div>";
      echo "<script type='text/javascript'>loadDefaultTab();</script>";
   }


   function showMethods() {
      global $LANG, $WEBSERVICES_METHOD;

      echo "<div class='center'><br><table class='tab_cadre_fixehov'>";
      echo "<tr><th colspan='4'>".$LANG['webservices'][8]."</th></tr>";
      echo "<tr><th>".$LANG['webservices'][10]."</th>" .
           "<th>".$LANG['webservices'][12]."</th>" .
           "<th>".$LANG['webservices'][11]."</th>" .
           "<th>".$LANG['webservices'][13]."</th></tr>";

      // Allow all plugins to register their methods
      $WEBSERVICES_METHOD=array();
      Plugin::doHook("webservices");

      foreach ($WEBSERVICES_METHOD as $method => $function) {
         // Display if MySQL REGEXP match
         if (countElementsInTable($this->getTable(), "ID='".$this->fields['id'].
                                  "' AND '".addslashes($method)."' REGEXP pattern")>0) {
            $result = $function;
            if (is_array($function)) {
               if ($tmp = isPluginItemType($function[0])) {
                  $plugin = $tmp['plugin'];
               } else {
                  $plugin="&nbsp;";
               }
               $result = implode('::',$function);
            } else if (preg_match('/^plugin_(.*)_method/', $function, $res)) {
               $plugin=$res[1];
            } else {
               $plugin="&nbsp;";
            }
            $call = (is_callable($function) ? $LANG['choice'][1] : $LANG['choice'][0]);
            $color = (is_callable($function) ? "greenbutton" : "redbutton");
            echo "<tr class='tab_bg_1'><td class='b'>$method</td><td>$plugin</td>".
                  "<td>$result</td><td class='center'>".
                  "<img src=\"".GLPI_ROOT."/pics/$color.png\" alt='ok'>&nbsp;$call</td></tr>";
         }
      }
      echo "</table></div>";
   }


   function getSearchOptions() {
      global $LANG;

      $tab = array();
      $tab['common'] = $LANG['webservices'][0];

      $tab[1]['table']           = $this->getTable();
      $tab[1]['field']           = 'name';
      $tab[1]['name']            = $LANG['common'][16];
      $tab[1]['datatype']        = 'itemlink';

      $tab[3]['table']           = $this->getTable();
      $tab[3]['field']           = 'comment';
      $tab[3]['name']            = $LANG['common'][25];
      $tab[3]['datatype']        = 'text';

      $tab[8]['table']           = $this->getTable();
      $tab[8]['field']           = 'is_active';
      $tab[8]['name']            = $LANG['webservices'][1];
      $tab[8]['datatype']        = 'bool';

      $tab[9]['table']           = $this->getTable();
      $tab[9]['field']           = 'do_log';
      $tab[9]['name']            = $LANG['webservices'][6];

      $tab[10]['table']          = $this->getTable();
      $tab[10]['field']          = 'deflate';
      $tab[10]['name']           = $LANG['webservices'][9];
      $tab[10]['datatype']       = 'bool';

      $tab[13]['table']          = $this->getTable();
      $tab[13]['field']          = 'ip';
      $tab[13]['name']           = $LANG['networking'][14];
      $tab[13]['massiveaction']  = false;

      $tab[14]['table']          = $this->getTable();
      $tab[14]['field']          = 'ipv6';
      $tab[14]['name']           = $LANG['webservices'][17];

      $tab[17]['table']          = $this->getTable();
      $tab[17]['field']          = 'pattern';
      $tab[17]['name']           = $LANG['webservices'][2];

      return $tab;
   }

   static function install(Migration $migration) {
      global $DB, $LANG;

      $table = 'glpi_plugin_webservices_clients';

      $migration->renameTable('glpi_plugin_webservices', $table);

      if (TableExists('glpi_plugin_webservices_clients')) {

         $migration->changeField($table, 'ID', 'id', 'autoincrement');
         $migration->changeField($table, 'FK_entities', 'entities_id', 'integer');
         $migration->changeField($table, 'recursive', 'is_recursive', 'bool');
         $migration->changeField($table, 'active', 'is_active', 'bool');
         $migration->changeField($table, 'comments', 'comment', 'text');
         $migration->changeField($table, 'FK_entities', 'entities_id', 'integer');

         $migration->addField($table, 'deflate', 'bool', array('after' => 'is_active'));
         $migration->addField($table, 'debug', 'bool', array('after' => 'do_log'));

         $migration->addKey($table, 'entities_id');

         // Version 1.3.0
         $opt = array('after'     => 'ip_end',
                      'update'    => "'::1'",
                      'condition' => "WHERE `ip_start`=INET_ATON('127.0.0.1')");
         $migration->addField($table, 'ipv6', 'string', $opt);

      } else {
         $sql = "CREATE TABLE `glpi_plugin_webservices_clients` (
                  `id` INT NOT NULL AUTO_INCREMENT,
                  `entities_id` INT NOT NULL DEFAULT '0',
                  `is_recursive` TINYINT( 1 ) NOT NULL DEFAULT '0',
                  `name` VARCHAR( 255 ) NOT NULL ,
                  `pattern` VARCHAR( 255 ) NOT NULL ,
                  `ip_start` BIGINT NULL ,
                  `ip_end` BIGINT NULL ,
                  `ipv6`  VARCHAR( 255 ) NULL,
                  `username` VARCHAR( 255 ) NULL ,
                  `password` VARCHAR( 255 ) NULL ,
                  `do_log` TINYINT NOT NULL DEFAULT '0',
                  `debug` TINYINT NOT NULL DEFAULT '0',
                  `is_active` TINYINT NOT NULL DEFAULT '0',
                  `deflate` TINYINT NOT NULL DEFAULT '0',
                  `comment` TEXT NULL ,
                  PRIMARY KEY (`id`),
                  KEY `entities_id` (`entities_id`)
                ) ENGINE = MYISAM CHARACTER SET utf8 COLLATE utf8_unicode_ci ";
         $DB->query($sql) or die ("SQL Error");

         $sql = "INSERT INTO
                 `glpi_plugin_webservices_clients` (`id`, `entities_id`, `is_recursive`, `name`,
                                                    `pattern`, `ip_start`, `ip_end` , `ipv6`,
                                                    `do_log`, `is_active`, `comment`)
                 VALUES (NULL, 0, 1, '".$LANG['webservices'][100]."',
                         '.*', INET_ATON('127.0.0.1'), INET_ATON('127.0.0.1'), '::1',
                         1, 1, '".$LANG['webservices'][101]."')";
         $DB->query($sql);
      }
   }

   static function uninstall() {
      global $DB;

      $tables = array ('glpi_plugin_webservices',
                       'glpi_plugin_webservices_clients');

      foreach ($tables as $table) {
         $query = "DROP TABLE IF EXISTS `$table`;";
         $DB->query($query) or die($DB->error());
      }
   }
}
?>