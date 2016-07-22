<?php
/*
 * @version $Id: tracking.injector.php 12820 2010-10-21 09:50:37Z moyo $
 -------------------------------------------------------------------------
 GLPI - Gestionnaire Libre de Parc Informatique
 Copyright (C) 2003-2010 by the INDEPNET Development Team.

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
 along with GLPI; if not, write to the Free Software
 Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 --------------------------------------------------------------------------
 */

// Based on:
// IRMA, Information Resource-Management and Administration
// Christian Bauer
// ----------------------------------------------------------------------
// Original Author of file: MickaelH - IPEOS I-Solutions - www.ipeos.com
// Purpose of file: This file is the result of the ticket creation form
// ----------------------------------------------------------------------

define('GLPI_ROOT', '../../..');
include (GLPI_ROOT . "/inc/includes.php");

$welcome = $LANG['job'][13];

$common = new PluginMobileCommon;

if (empty($_POST["type"]) || ($_POST["type"] != "Helpdesk") || !$CFG_GLPI["use_anonymous_helpdesk"]) {
   Session::haveRight("create_ticket","1");
}

$track = new Ticket();

if (!empty($_POST["type"]) && ($_POST["type"] == "Helpdesk")) {
   $welcome = $LANG['title'][10];

} else if ($_POST["_from_helpdesk"]) {
   $welcome = $LANG['Menu'][31];

} else {
   $welcome = $LANG['Menu'][31];
}

$common->displayHeader($welcome, 'ss_menu.php?menu=maintain');

if (isset($_POST["_my_items"]) && !empty($_POST["_my_items"])) {
   $splitter = explode("_",$_POST["_my_items"]);
   if (count($splitter) == 2) {
      $_POST["itemtype"] = $splitter[0];
      $_POST["items_id"] = $splitter[1];
   }
}

if (!isset($_POST["itemtype"]) || (empty($_POST["items_id"]) && $_POST["itemtype"] != 0)) {
   $_POST["itemtype"] = '';
   $_POST["items_id"] = 0;
}

/*//SUPRISERVICE*/
$fieldsRequireds = '';

if ( !isset($_POST["items_id"]) ) {
	$fieldsRequireds .= $LANG['help'][24] . ', ';
}
if ( !isset($_POST["itilcategories_id"]) || $_POST["itilcategories_id"] == 0 ) {
	$fieldsRequireds .= $LANG['common'][36] . ', ';
}
if ( array_key_exists("name", $_POST) && ( !isset($_POST["name"]) || $_POST["name"] == '' ) ) {
	$fieldsRequireds .= $LANG['common'][57] . ', ';
}
if ( array_key_exists("content", $_POST) && ( !isset($_POST["content"]) || $_POST["content"] == '' ) ) {
	$fieldsRequireds .= $LANG['joblist'][6] . ', ';
}
if ( strlen($fieldsRequireds) > 2 && substr($fieldsRequireds, -2) == ', ' )
{
	$fieldsRequireds = substr($fieldsRequireds, 0, strlen($fieldsRequireds) - 2);
}

if ( strlen($fieldsRequireds) > 0 ){
   echo "<div class='center'>";
   echo "<img src=\"".$CFG_GLPI["root_doc"]."/pics/warning.png\" alt='warning'><br>";
	echo $LANG['job'][68] . " {$fieldsRequireds}</div>";
}
else
{
	/*//SUPRISERVICE*/
	$_POST['_users_id_requester'] = $_POST["_itil_requester"]['users_id'];
	$_POST['_users_id_observer'] = $_POST["_itil_observer"]['users_id'];
	$_POST['_users_id_assign'] = $_POST["_itil_assign"]['users_id'];
	//-

   $_POST["entities_id"] = $_SESSION["glpiactive_entity"];

  if ($newID = $track->add($_POST)){
	  if (isset($_POST["type"]) && ($_POST["type"] == "Helpdesk")) {
		  echo "<div class='center'>".$LANG['help'][18]."<br><br>";
  /*
		  displayBackLink();
  */
		  echo "</div>";
	  } else {
		  echo "<div class='center b'>";
		  echo "<img src=\"".$CFG_GLPI["root_doc"]."/pics/ok.png\" alt=\"OK\"><br><br>";
		  echo $LANG['help'][18]." (".$LANG['job'][38]."&nbsp;";
		  echo "<a href='item.php?itemtype=Ticket&menu=maintain&ssmenu=ticket&id=$newID'>$newID</a>)<br>";
		  echo $LANG['help'][19]."</div>";
		  $_SESSION["MESSAGE_AFTER_REDIRECT"]="";
	  }
  } else {
	  echo "<div class='center'>";
	  echo "<img src=\"".$CFG_GLPI["root_doc"]."/pics/warning.png\" alt='warning'><br></div>";
	  displayMessageAfterRedirect();
  /*
	  displayBackLink();
  */
  }
}
$common->displayFooter();

?>
