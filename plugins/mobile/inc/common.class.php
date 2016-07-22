<?php
/*
 * @version $Id: HEADER 10411 2010-02-09 07:58:26Z moyo $
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
//include ("common.function.php");

class PluginMobileCommon extends CommonDBTM {


   function __construct () {
      $this->checkMobileLogin();
      //$_SESSION['glpilist_limit'] = 5;
   }

   function displayCommonHtmlHeader(){
      $this->includeCommonHtmlHeader('mobile');
      echo "<body>";
   }

   function getMobileExtranetRoot() {
      return $this->_mobile_extranet_root;
   }

   function checkMobileLogin() {
      //check Profile
      if (
         isset($_SESSION['glpi_plugin_mobile_profile'])
         && $_SESSION['glpi_plugin_mobile_profile']['mobile_user'] == ''
      )Html::Redirect/*glpi_header*/(GLPI_ROOT . "/front/central.php");

      //check glpi login && redirect to plugin mobile
      if (!isset ($_SESSION["glpiactiveprofile"])
      || $_SESSION["glpiactiveprofile"]["interface"] != "central") {
         // Gestion timeout session
         if (!Session::getLoginUserID()) {

            if (strpos($_SERVER['PHP_SELF'], 'index.php') === false
            && strpos($_SERVER['PHP_SELF'], 'login.php') === false
            && strpos($_SERVER['PHP_SELF'], 'logout.php') === false
            && strpos($_SERVER['PHP_SELF'], 'recoverpassword.form.php') === false
            ) {
               Html::Redirect/*glpi_header*/(GLPI_ROOT . "/plugins/mobile/index.php");
               exit ();
            }
         }
      }
   }

   function includeCommonHtmlHeader($title='') {
      global $CFG_GLPI,$PLUGIN_HOOKS,$LANG;

      // Send UTF8 Headers
      header("Content-Type: text/html; charset=UTF-8");
      // Send extra expires header
      Html::header_nocache();

      // Start the page
      echo "<!DOCTYPE HTML PUBLIC \"-//W3C//DTD HTML 4.01 Transitional//EN\"
         \"http://www.w3.org/TR/html4/loose.dtd\">";
      echo "\n<html><head><title>GLPI - ".$title."</title>";
      echo "<meta http-equiv=\"Content-Type\" content=\"text/html; charset=utf-8 \" >";
      // Send extra expires header
      echo "<meta http-equiv=\"Expires\" content=\"Fri, Jun 12 1981 08:20:00 GMT\" >\n";
      echo "<meta http-equiv=\"Pragma\" content=\"no-cache\">\n";
      echo "<meta http-equiv=\"Cache-Control\" content=\"no-cache\">\n";


		/*//SUPRISERVICE*/		// AJAX library
      echo "<script type=\"text/javascript\" src='".
             $CFG_GLPI["root_doc"]."/lib/extjs/adapter/ext/ext-base.js'></script>\n";

      if ($_SESSION['glpi_use_mode'] == Session::DEBUG_MODE) {
         echo "<script type='text/javascript' src='".
                $CFG_GLPI["root_doc"]."/lib/extjs/ext-all-debug.js'></script>\n";
      } else {
         echo "<script type='text/javascript' src='".
                $CFG_GLPI["root_doc"]."/lib/extjs/ext-all.js'></script>\n";
      }
      echo "<script type='text/javascript' src='".$CFG_GLPI["root_doc"].
            "/lib/tiny_mce/tiny_mce.js'></script>";

      // FAV & APPLE DEVICE ICON
      echo "<link rel='apple-touch-icon' type='image/png' href='".$CFG_GLPI["root_doc"]."/plugins/mobile/pics/apple-touch-icon.png' />";
      echo "<link rel='icon' type='image/png' href='".$CFG_GLPI["root_doc"]."/plugins/mobile/pics/favicon.png' />";

      // CSS link JQUERY MOBILE
      echo "<link rel='stylesheet'  href='".
         $CFG_GLPI["root_doc"]."/plugins/mobile/lib/jquery.mobile-1.0a4.1/jquery.mobile-1.0a4.1.css' type='text/css' media='screen' >\n";


      // CSS link MOBILE GLPI PLUGIN
      echo "<link rel='stylesheet'  href='".
         $CFG_GLPI["root_doc"]."/plugins/mobile/mobile.css' type='text/css' media='screen' >\n";

      // CSS link DATEBOX PLUGIN
      echo "<link rel='stylesheet' href='".
         $CFG_GLPI["root_doc"]."/plugins/mobile/lib/datebox/jquery.mobile.datebox.css' />\n";


      // LOAD JS JQUERY
		/*//SUPRISERVICE*/
      //echo "<script type=\"text/javascript\" src='".$CFG_GLPI["root_doc"]."/plugins/mobile/lib/jquery-1.5.2.min.js'></script>";

		//Mobiscroll
		echo "<script type=\"text/javascript\" src='".$CFG_GLPI["root_doc"]."/plugins/mobile/lib/jquery-1.6.0.min.js'></script>";
      //echo "<script type=\"text/javascript\" src='//ajax.googleapis.com/ajax/libs/jquery/1.8.2/jquery.min.js'></script>";
      //echo "<script type=\"text/javascript\" src='//ajax.googleapis.com/ajax/libs/jqueryui/1.8.23/jquery-ui.min.js'></script>";

      //Timepicker
      //echo "<link rel='stylesheet'  href='".$CFG_GLPI["root_doc"]."/plugins/mobile/lib/timepicker/jquery-ui-timepicker-addon.css' type='text/css' media='screen' >\n";
		//echo "<script type=\"text/javascript\" src='".$CFG_GLPI["root_doc"]."/plugins/mobile/lib/timepicker/jquery-ui-timepicker-addon.js'></script>\n";

      //Anytime
      //echo "<link rel='stylesheet'  href='".$CFG_GLPI["root_doc"]."/plugins/mobile/lib/timepicker/anytime.compressed.css' type='text/css' media='screen' >\n";
		//echo "<script type=\"text/javascript\" src='".$CFG_GLPI["root_doc"]."/plugins/mobile/lib/timepicker/anytime.compressed.js'></script>\n";
		//--




	 
      // EXTEND JQUERY MOBILE OPTIONS
      echo "<script type='text/javascript'>";
      echo "$(document).bind('mobileinit', function(){\n";

         // DISABLE JQUERY MOBILE AJAX SUBMIT
         echo "$.extend(  $.mobile, { ajaxFormsEnabled: false });\n";

         // change back button text
         echo "$.mobile.page.prototype.options.backBtnText = '".$LANG['buttons'][13]."';\n";

         //change loading message
         echo "$.extend(  $.mobile, { loadingMessage: '".$LANG['common'][80]."' });\n";

         if (navigatorDetect() == 'Android' && getOsVersion() < "3.0")
            echo "$.mobile.defaultTransition = 'none';\n";

         //echo "alert($.mobile.nonHistorySelectors);";
         // disable history on data-rel navigation
         //echo "$.mobile.nonHistorySelectors = 'dialog][data-rel=navigation';\n";

         //reset type=date inputs to text
         //echo "$.mobile.page.prototype.options.degradeInputs.date = true;\n";

         echo "$.mobile.selectmenu.prototype.options.nativeMenu = true;";

         if (nativeSelect()) echo "$.mobile.page.prototype.options.keepNative = 'select'";

      echo "});\n";
      echo "</script>\n";

      // LOAD JS JQUERY MOBILE
      echo "<script type=\"text/javascript\" src='".
            $CFG_GLPI["root_doc"]."/plugins/mobile/lib/jquery.mobile-1.0a4.1/jquery.mobile-1.0a4.1.min.js'></script>\n";
      /*echo "<script type=\"text/javascript\" src='".
            $CFG_GLPI["root_doc"]."/plugins/mobile/lib/jquery.mobile-1.0a4.1/jquery.mobile-1.0a4.1.js'></script>\n";*/

		/*//SUPRISERVICE*/
		$path_mobiscroll = $CFG_GLPI["root_doc"]."/plugins/mobile/lib/mobiscroll-master/js";
echo <<<MOBISCROLL
	<link rel="stylesheet" href="http://www.fajrunt.org/css/mobiscroll-2.4.custom.min.css" />
	<script src="http://www.fajrunt.org/js/mobiscroll-2.4.custom.min.js">
	<script src="{$path_mobiscroll}/mobiscroll.android.js" type="text/javascript"></script>
	<script src="{$path_mobiscroll}/i18n/mobiscroll.i18n.pt-BR.js" type="text/javascript"></script>
MOBISCROLL;
		//--

      // LOAD DATEBOX PLUGIN (JS)
      echo "<script type=\"text/javascript\" src='".
            $CFG_GLPI["root_doc"]."/plugins/mobile/lib/datebox/jquery.mobile.datebox.js'></script>\n";


      //DOM READY
      echo "<script type='text/javascript'>";
      echo "$(document).ready(function() {

         //post screen resolution
         $.post('".$CFG_GLPI["root_doc"]."/plugins/mobile/lib/resolution.php', { width: $(document).width(), height: $(document).height() });

         //INIT DATEBOX PLUGIN
         ".getDateBoxOptions()."

         $('input[type=date], input[data-role=date]', this ).each(function() {
            $(this).datebox(opts);
         });

         $('.ui-page').live('pagecreate', function() {
            $('input[type=date], input[data-role=date]', this ).each(function() {
               $(this).datebox(opts);
            });
         });

      });\n";
      echo "</script>\n";

      // End of Head
      echo "</head>\n";

  }

  function displayHeader($title="&nbsp;", $back = '', $external = false, $title2 = '', $id_attr='')
  {
      global $CFG_GLPI, $LANG;
      /*if ($external)  $external = "rel='external'";
      else */$external = "";

      if ($back != '') $back = $CFG_GLPI["root_doc"]."/plugins/mobile/front/".$back;

      if (strlen($title2) > 0) $title2 = " " . $title2;

      $this->displayCommonHtmlHeader($title);

      echo "<div data-role='page' data-theme='c' id='$id_attr' ";
      if (nativeSelect()) echo "class='native-select'";
      echo ">";

      if (!$this->checkDisplayHeaderBar()) {
      echo "<div data-role='header' data-theme='c'>";
      echo "<a href='".$CFG_GLPI["root_doc"]."/plugins/mobile/front/central.php' rel='external'>";
      echo "<img src='"
         .$CFG_GLPI["root_doc"]
         ."/plugins/mobile/pics/logo.png' alt='Logo' width='84' height='19' />";
      echo "</a>";
      echo "<h1>".$title.$title2."</h1>";

      $dataTransition = "data-transition='slide'";
      if (navigatorDetect() == 'Android' && getOsVersion() < "3.0") $dataTransition = "";

      if ($back != '')
         echo "<a href='".$back."' ".$external." data-icon='arrow-l' data-back='true' $dataTransition class='ui-btn-right'>"
            .$LANG['buttons'][13]."</a>";
      elseif (strpos($_SERVER['PHP_SELF'], 'central.php') === false)
         echo "<a href='#' onclick='history.back();' data-icon='arrow-l' class='ui-btn-right' $dataTransition data-back='true'>"
            .$LANG['buttons'][13]."</a>";

      if (strpos($_SERVER['PHP_SELF'], 'central.php') !== false)
         echo "<a href='".GLPI_ROOT . "/plugins/mobile/front/option.php' data-icon=\"gear\" class='ui-btn-right' data-rel='dialog'>"
            .$LANG['plugin_mobile']['navigation']['options']."</a>";

      echo "</div>";
      }
   }

   function displayPopHeader($title, $id='popup') {
      global $LANG;

      echo "<div data-role='page'>";
      echo "<div data-role='header' data-theme='c'>";
         echo "<h1>$title : </h1>";
      echo "</div>";
      echo "<div data-role='content' data-theme='c' id='$id'>";
   }

   function displayPopFooter() {
      echo "</div>";
      echo "</div>";
   }

   function checkDisplayHeaderBar() {
      if (
         strpos($_SERVER['PHP_SELF'], 'index.php') !== false ||
         strpos($_SERVER['PHP_SELF'], 'login.php') !== false
      ) return true;
      else return false;
   }

   function displayLoginBox($error = '', $REDIRECT = "") {
      global $CFG_GLPI, $LANG;


      echo "<div data-role='header' data-theme='c'>";
      echo "<a href='#'><img src='".$CFG_GLPI["root_doc"]."/plugins/mobile/pics/logo.png' alt='Logo' /></a>";
         echo "<h1>".$LANG['login'][10]."</h1>";
      echo "</div>";
	//echo navigatorDetect();
      echo "<div data-role='content' class='login-box'>";
      if (trim($error) != "") {
      echo '<div class="center b">' . $error . '<br><br>';
      }

      echo "<form action='login.php' method='post'>";
      echo "<fieldset>";

      echo "<div data-role='fieldcontain'>";
      echo "<label for='login_name'>".$LANG['login'][6].":</label>";
      echo "<input type='text' name='login_name' id='login_name' value=''  />";
      echo "</div>";

      echo "<div data-role='fieldcontain'>";
      echo "<label for='login_password'>".$LANG['login'][7].":</label>";
      echo "<input type='password' name='login_password' id='login_password' value='' />";
      echo "</div>";

      echo "<button type='submit' data-theme='a'>".$LANG['buttons'][2]."</button>";

      echo "</fieldset>";
      echo "</form>";

      echo "</div>";
   }

   function displayFooter() {
      echo "</div>";
      echo "</body>";
      echo "</html>";
   }


   public function showCentralFooter() {
      global $LANG;

      //display footer central bar
      echo "<div data-role='footer' data-position='fixed' data-theme='d'>";
         echo "<div data-role='navbar'>";
         echo "<ul>";

          echo "<li><a href='#' data-icon='search'>".$LANG['buttons'][0]."</a></li>";

          echo "<li><a href='#' data-icon='custom' id='icon-preference'>".$LANG['Menu'][11]."</a></li>";

         echo "</ul>";
         echo "</div>";
      echo "</div>";
   }

};

?>
