<?php

/*
 * @version $Id: HEADER 15930 2011-10-30 15:47:55Z tsmr $
  -------------------------------------------------------------------------
  Mreporting plugin for GLPI
  Copyright (C) 2003-2011 by the mreporting Development Team.

  https://forge.indepnet.net/projects/mreporting
  -------------------------------------------------------------------------

  LICENSE

  This file is part of mreporting.

  mreporting is free software; you can redistribute it and/or modify
  it under the terms of the GNU General Public License as published by
  the Free Software Foundation; either version 2 of the License, or
  (at your option) any later version.

  mreporting is distributed in the hope that it will be useful,
  but WITHOUT ANY WARRANTY; without even the implied warranty of
  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
  GNU General Public License for more details.

  You should have received a copy of the GNU General Public License
  along with mreporting. If not, see <http://www.gnu.org/licenses/>.
  --------------------------------------------------------------------------
 */

if (!defined('GLPI_ROOT')) {
    die("Sorry. You can't access directly to this file");
}

class PluginMreportingProfile extends CommonDBTM {

    static function getTypeName() {
        global $LANG;

        return $LANG['plugin_mreporting']["name"];
    }

    function canCreate() {
        return Session::haveRight('profile', 'w');
    }

    function canView() {
        return Session::haveRight('profile', 'r');
    }

    //if profile deleted
    static function purgeProfiles(Profile $prof) {
        $plugprof = new self();
        $plugprof->deleteByCriteria(array('profiles_id' => $prof->getField("id")));
    }

    function getTabNameForItem(CommonGLPI $item, $withtemplate = 0) {
        global $LANG;

        if ($item->getType() == 'Profile' && $item->getField('interface') != 'helpdesk') {
            return $LANG['plugin_mreporting']["name"];
        }
        return '';
    }

    static function displayTabContentForItem(CommonGLPI $item, $tabnum = 1, $withtemplate = 0) {
        global $CFG_GLPI;

        if ($item->getType() == 'Profile') {
            $ID = $item->getField('id');
            $prof = new self();

            if (!$prof->getFromDBByProfile($item->getField('id'))) {
                $prof->createAccess($item->getField('id'));
            }
            $prof->showForm($item->getField('id'), array('target' =>
                $CFG_GLPI["root_doc"] . "/plugins/mreporting/front/profile.form.php"));
        }
        return true;
    }

    function getFromDBByProfile($profiles_id) {
        global $DB;

        $query = "SELECT * FROM `" . $this->getTable() . "`
					WHERE `profiles_id` = '" . $profiles_id . "' ";
        if ($result = $DB->query($query)) {
            if ($DB->numrows($result) != 1) {
                return false;
            }
            $this->fields = $DB->fetch_assoc($result);
            if (is_array($this->fields) && count($this->fields)) {
                return true;
            } else {
                return false;
            }
        }
        return false;
    }

    static function createFirstAccess($ID) {
        $myProf = new self();
        if (!$myProf->getFromDBByProfile($ID)) {
            $myProf->add(array(
                'profiles_id' => $ID,
                'reports' => 'r',
                'config' => 'w'
            ));
        }
    }

    function createAccess($ID) {

        $this->add(array(
            'profiles_id' => $ID));
    }

    static function changeProfile() {
        $prof = new self();
        if ($prof->getFromDBByProfile($_SESSION['glpiactiveprofile']['id'])) {
            $_SESSION["glpi_plugin_mreporting_profile"] = $prof->fields;
        } else
            unset($_SESSION["glpi_plugin_mreporting_profile"]);
    }

    function showForm($ID, $options = array()) {
        global $LANG;
        
        //$profiles_id = $item->getField('id');
        $target = Toolbox::getItemTypeFormURL(__CLASS__);

        if (!Session::haveRight("profile", "r"))
            return false;

        $prof = new Profile();
        if ($ID) {
            $this->getFromDBByProfile($ID);
            $prof->getFromDB($ID);
        }

        $this->showFormHeader($options);

        echo "<tr class='tab_bg_2'>";

        echo "<th colspan='4'>" . $LANG['plugin_mreporting']["name"] . " " .
        $prof->fields["name"] . "</th>";

        echo "</tr>";
        echo "<tr class='tab_bg_2'>";

        echo "<td>" . $LANG['reports'][15] . ":</td><td>";
        Profile::dropdownNoneReadWrite("reports", $this->fields["reports"], 1, 1, 0);
        echo "</td>";

        echo "<td>" . $LANG['common'][12] . ":</td><td>";
        Profile::dropdownNoneReadWrite("config", $this->fields["config"], 1, 0, 1);
        echo "</td>";

        echo "</tr>";

        echo "<input type='hidden' name='id' value="  . $this->fields["id"] . ">"; 

        $options['candel'] = false;
        $this->showFormButtons($options);
        echo "<form action='" . $target . "' method='post'>";
        
        // ************* SUPRISERVICE **************
        echo "<table class='tab_cadre_fixe'>";
        echo "<tr><th colspan='4' class='center b'>Gerenciamento de permissões por perfil</th></tr>";
        //echo "<th colspan='4'> Gerenciamento de permissões por perfil </th>";
        
        $array = $this->getIndicadores();
        foreach ($array as $d) {
            echo "<tr class='tab_bg_2'>";
                        
            echo "<td>" . $d['name'] . ":</td><td>";
            Profile::dropdownNoneReadWrite($d['report'], $this->confereIndicadores($d['report'],$ID), 1, 1, 0);
            echo "<input type='hidden' name='report_{$d['report']}' value='".$d['report']."'>";
            echo "<input type='hidden' name='name_{$d['report']}' value='".$d['name']."'>";
            //echo "<input type='hidden' name='id_{$d['report']}' value='".$id_ind."'>";
            echo "</td></tr>";
            
        }        
        
        echo "<tr class='tab_bg_1'>";
        echo "<td class='center' colspan='4'>";
        echo "<input type='hidden' name='profiles_id' value={$ID}>";
        echo "<input type='submit' name='update_user_profile' value='" .
        $LANG['buttons'][7] . "' class='submit'>";
        echo "</td></tr>\n";
        echo "</table>";
        echo "</form>";
        ////////////////////////////////////////////////
        
    }

    //****************** SUPRISERVICE *********
    function getIndicadores() {
        global $DB;

        $query = "SELECT DISTINCT `report`, `name` FROM `glpi_mreporting_profiles`";
        $result = $DB->query($query);

        if ($DB->numrows($result) > 0) {
            while ($data = $DB->fetch_assoc($result)) {
                $report[] = array("name"=>$data['name'],"report"=>$data['report']);
                //$report[] = $data['report'];
            }
        } else {
            return false;
        }

        return $report;
    }
    
    function getID($report,$profiles_id) {
        global $DB;

        $query = "SELECT `id` FROM `glpi_mreporting_profiles` WHERE `report`= '".$report."' AND `profiles_id` = {$profiles_id}";
        $result = $DB->query($query);

        if ($DB->numrows($result) > 0) {
            $data = $DB->fetch_assoc($result);
            return $data['id'];
            
        }else{
            return false;
        }
    }
    
    
    
    function insereIndicadores($report, $name,$profiles_id) {
        global $DB; 
        
        if($this->getID($report, $profiles_id)){
            return false;
        }
        
        $query = "INSERT INTO `glpi_mreporting_profiles` (`profiles_id`,`report`,`access`,`name`)
              VALUES({$profiles_id},'".$report."','r','".$name."')";
        
        $result = $DB->query($query);

        
    }
    
    function delIndicadores($report,$profiles_id) {
        global $DB; 
                
        $query = "DELETE FROM `glpi_mreporting_profiles` WHERE `report`= '".$report."' AND `profiles_id` = {$profiles_id}";        
        $result = $DB->query($query);        
    }
    
    function confereIndicadores($report,$profiles_id) {       
        global $DB; 
        
        $query = "SELECT * FROM `glpi_mreporting_profiles` WHERE `report`= '".$report."' AND `profiles_id` = {$profiles_id}";        
        $result = $DB->query($query);  
                
        if ($DB->numrows($result) > 0) {
            return true;
        } 
    }
    ////////////////////////////////////

	
}

?>