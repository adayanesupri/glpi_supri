<?php
/*
 -------------------------------------------------------------------------
 GLPI - Gestionnaire Libre de Parc Informatique
 Copyright (C) 2003-2010 by the INDEPNET Development Team.

 http://indepnet.net/   http://glpi-project.org
 -------------------------------------------------------------------------

 LICENSE
Inventaire
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
 along with GLPI; if not, write to the Free Software
 Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 --------------------------------------------------------------------------
 */

// ----------------------------------------------------------------------
// Original Author of file:
// Purpose of file:
// ----------------------------------------------------------------------

class PluginMobileTab extends CommonDBTM {
   
   public static function getTitle($id, $itemtype, $items_id)  {
      $obj = new $itemtype;
      $obj->getFromDB($items_id);
      $tabs = $obj->defineTabs();
      return $tabs[$id];
   } 
   
   public function showUrl($url, $params = array()) {
      global $CFG_GLPI;
            
      $js_params = "{\n";
      foreach ($params as $key => $value) {
         $js_params .= "'$key' : '$value',\n";
      }
      $js_params = substr($js_params, 0, -2)."}";
      
      saveActiveProfileAndApplyRead();
      echo "<link rel='stylesheet'  href='".
             $CFG_GLPI["root_doc"]."/plugins/mobile/lib/scrollview/jquery.mobile.scrollview.css' type='text/css' media='screen' >\n";
      echo "<script type=\"text/javascript\" src='".
          $CFG_GLPI["root_doc"]."/plugins/mobile/lib/scrollview/jquery.easing.1.3.js'></script>";
      echo "<script type=\"text/javascript\" src='".
          $CFG_GLPI["root_doc"]."/plugins/mobile/lib/scrollview/jquery.mobile.scrollview.js'></script>";
      echo "<script type=\"text/javascript\" src='".
          $CFG_GLPI["root_doc"]."/plugins/mobile/lib/scrollview/scrollview.js'></script>";

      echo "<script type='text/javascript'>
      $.post('$url', $js_params,
         function(data){
				console.log(data);
            $('#tab_content').html(data);
            //$('#tab_content').html($('#tab_content table:first'));
            $('#tab_content th').addClass('ui-bar-b');
            $('#tab_content tr').addClass('ui-btn-up-c').removeClass('tab_bg_2');
            $('#tab_content table:first').attr('class', '').attr('style', 'width:100%');
            $('#tab_content #debugajax').remove();                        
            $('#tab_content select').attr('data-native-menu', 'true');
            $('#tab_content').page({keepNative:true});
            
            //$('#tab_content').html(data);
            mobileScrollView();
           
      });
      </script>";
		//DEBUG
		//print $url . "<br>";
      //print $js_params;
		//die();

      echo "<div id='tab_content' class='scroll_content' data-scroll='true'></div>";
      
      restoreActiveProfile();
   }
   
   public function showTab($itemtype)  {   
		//aqui direciona para ajax do glpi
      $url = Toolbox::getItemTypeTabsURL($itemtype);
		//print $url . " - " . $_GET['glpi_tab'] . " - " . $_SERVER['PHP_SELF'];
		//$url = "/supridesk/plugins/mobile/ajax/ticket.tabs.php";
	  
      $params = array(
         'id' => $_GET['id'], 
         'glpi_tab' => $_GET['glpi_tab'],
      	 'itemtype' => $itemtype,
         'target' => $_SERVER['PHP_SELF']
      );

      global $CFG_GLPI;
      $url_default = $CFG_GLPI['root_doc'] ."/ajax/common.tabs.php";

		if ( $url == $url_default )
			$url = $CFG_GLPI['root_doc'] . "/plugins/mobile/ajax/common.tabs.php";

      $this->showUrl($url, $params); 
   }
   
   public static function getPluginTitle($plugin_name, $itemtype, $items_id)  {
      $obj = new $itemtype;
      $obj->getFromDB($items_id);
      
      $target = $_SERVER['PHP_SELF'];
      $pluginsTabs = Plugin::getTabs($target,$obj, false);
      
      return $pluginsTabs[$plugin_name]['title'];
   } 
   
   public function showPluginTab($plugin_name, $itemtype, $items_id) {
      $obj = new $itemtype;
      $obj->getFromDB($items_id);
      
      $target = $_SERVER['PHP_SELF'];
      $pluginsTabs = Plugin::getTabs($target,$obj, false);
      
      $url = $pluginsTabs[$plugin_name]['url'];
      
      parse_str($pluginsTabs[$plugin_name]['params'], $paramsArray);
      
      $params = array(
         'id' => $paramsArray['id'], 
         'glpi_tab' => $paramsArray['glpi_tab'], 
         'target' => $paramsArray['target']
      );
die('debug 2');
      $this->showUrl($url, $params); 
   }
   
   public static function displayTabBar($items = array()) {
      global $LANG, $CFG_GLPI;
      
      $classname = ucfirst($_GET['itemtype']);
      $obj = new $classname;
      $obj->getFromDB($_GET['id']);
      $tabs = $obj->defineTabs();
      
      $target = $_SERVER['PHP_SELF'];
      $pluginsTabs = Plugin::getTabs($target,$obj, false);
      
      echo "<div data-role='header' data-backbtn='false' data-theme='a' data-id='TabBar'>";   
         echo "<div data-theme='c' class='ui-btn-right' style='top:0' data-position='inline'>";
         foreach($items as $item) {
            $item = str_replace('<a', "<a data-role='button' data-theme='c'", $item);
            echo $item;
         }   
         echo "</div>";
         //echo "&nbsp;|&nbsp;";
         echo "<div data-role='collapsible' data-theme='c' data-collapsed='true'>";
            echo "<h2>&nbsp;&nbsp;&nbsp;".$LANG['plugin_mobile']['common'][4]."</h2>";
            echo "<div>";
               //echo "<ul data-role='listview' id='ultabs'>";
               foreach($tabs as $key => $tab) {
                  echo "<a href='".$CFG_GLPI["root_doc"]
                     ."/plugins/mobile/front/tab.php?glpi_tab=$key&id=".$_GET['id']
                     ."&itemtype=".$_GET['itemtype']
                     ."&menu=".$_GET['menu']
                     ."&ssmenu=".$_GET['ssmenu']
                     ."' data-theme='c' rel='external' data-role='button'>".$tab."</a>";
               }
               
               //plugins tabs
               foreach($pluginsTabs as $key => $tab) {
                  $params = explode('&', $tab['params']);
                  
                  echo "<a href='".$CFG_GLPI["root_doc"]
                     ."/plugins/mobile/front/tab_plugins.php?".$params[2]."&id=".$_GET['id']
                     ."&itemtype=".$_GET['itemtype']
                     ."&menu=".$_GET['menu']
                     ."&ssmenu=".$_GET['ssmenu']
                     ."' data-theme='c' rel='external' data-role='button'>".$tab['title']."</a>";
               }               
               //echo "</ul>";
            echo "</div>";
         echo "</div>";
      echo "</div>";
   }
}
