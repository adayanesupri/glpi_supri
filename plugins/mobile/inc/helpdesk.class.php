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
// Original Author of file: MickaelH - IPEOS I-Solutions - www.ipeos.com
// Purpose of file: This class displays the form to create a new ticket
// ----------------------------------------------------------------------

if (!defined('GLPI_ROOT')) {
   die("Sorry. You can't access directly to this file");
}

class PluginMobileHelpdesk {

   public static function show($ID,$from_helpdesk) {
      global $LANG,$CFG_GLPI,$DB;

		/*//SUPRISERVICE*/
      if (!Session::haveRight("create_ticket","1")) {
          return false;
      }

		/*//SUPRISERVICE*/
		 $tt = new TicketTemplate();
       // First load default entity one
       if ($template_id = EntityData::getUsedConfig('tickettemplates_id', $_SESSION["glpiactive_entity"])) {
          // with type and categ
          $tt->getFromDBWithDatas($template_id, true);
       }

       if (Session::haveRight('validate_ticket',1)) {
          $opt=array();
          $opt['reset']  = 'reset';
          $opt['field'][0]      = 55; // validation status
          $opt['searchtype'][0] = 'equals';
          $opt['contains'][0]   = 'waiting';
          $opt['link'][0]        = 'AND';

          $opt['field'][1]      = 59; // validation aprobator
          $opt['searchtype'][1] = 'equals';
			/*//SUPRISERVICE*/
          $opt['contains'][1]   = Session::getLoginUserID();
          $opt['link'][1]        = 'AND';


          $url_validate=$CFG_GLPI["root_doc"]."/front/ticket.php?".Toolbox::append_params($opt,'&amp;');

          if (TicketValidation::getNumberTicketsToValidate(Session::getLoginUserID()) >0) {
             echo "<a href='$url_validate' title=\"".$LANG['validation'][15]."\"
                      alt=\"".$LANG['validation'][15]."\">".$LANG['validation'][33]."</a><br><br>";
          }
       }

       $query = "SELECT `email`, `realname`, `firstname`, `name`
                 FROM `glpi_users` left join `glpi_useremails` on glpi_users.id = glpi_useremails.users_id
                 WHERE glpi_users.id = '$ID'";
       $result=$DB->query($query);
       $email = $DB->result($result,0,"email");

       // Get saved data from a back system
       $use_email_notification = 1;
       if ($email=="") {
          $use_email_notification=0;
       }
       $itemtype = 0;
       $items_id="";
       $content="";
       $title="";
       $ticketcategories_id = 0;
       $urgency  = 3;
       $type  = 1;

       if (isset($_SESSION["helpdeskSaved"]["use_email_notification"])) {
          $use_email_notification = stripslashes($_SESSION["helpdeskSaved"]["use_email_notification"]);
       }
       if (isset($_SESSION["helpdeskSaved"]["email"])) {
          $email = stripslashes($_SESSION["helpdeskSaved"]["user_email"]);
       }
       if (isset($_SESSION["helpdeskSaved"]["itemtype"])) {
          $itemtype = stripslashes($_SESSION["helpdeskSaved"]["itemtype"]);
       }
       if (isset($_SESSION["helpdeskSaved"]["items_id"])) {
          $items_id = stripslashes($_SESSION["helpdeskSaved"]["items_id"]);
       }
       if (isset($_SESSION["helpdeskSaved"]["content"])) {
          $content = cleanPostForTextArea($_SESSION["helpdeskSaved"]["content"]);
       }
       if (isset($_SESSION["helpdeskSaved"]["name"])) {
          $title = stripslashes($_SESSION["helpdeskSaved"]["name"]);
       }
       if (isset($_SESSION["helpdeskSaved"]["ticketcategories_id"])) {
          $ticketcategories_id = stripslashes($_SESSION["helpdeskSaved"]["ticketcategories_id"]);
       }
       if (isset($_SESSION["helpdeskSaved"]["urgency"])) {
          $urgency = stripslashes($_SESSION["helpdeskSaved"]["urgency"]);
       }
       if (isset($_SESSION["helpdeskSaved"]["type"])) {
          $type = stripslashes($_SESSION["helpdeskSaved"]["type"]);
       }

       unset($_SESSION["helpdeskSaved"]);

       echo "<form method='post' name=\"helpdeskform\" action=\"".
              $CFG_GLPI["root_doc"]."/plugins/mobile/front/tracking.injector.php\" enctype=\"multipart/form-data\">";
       echo "<input type='hidden' name='_from_helpdesk' value='$from_helpdesk'>";
       echo "<input type='hidden' name='requesttypes_id' value='".RequestType::getDefault('helpdesk')."'>";

		 if ($CFG_GLPI['urgency_mask']==(1<<3)) {
          // Dont show dropdown if only 1 value enabled
          echo "<input type='hidden' name='urgency' value='3'>";
       }
       echo "<input type='hidden' name='entities_id' value='".$_SESSION["glpiactive_entity"]."'>";
       echo "<div class='center input_right'><table class='tab_cadre'>";

       echo "<tr><td colspan='2'>".$LANG['job'][46]."&nbsp;: <br>";
       if (Session::isMultiEntitiesMode()) {
          echo "&nbsp;(".Dropdown::getDropdownName("glpi_entities",$_SESSION["glpiactive_entity"]).")";
       }
      echo "</td></tr>";
		 /*
       echo ",<br> ou ".strtolower($LANG['entity'][10])."&nbsp;: </th></tr>";
      echo "<tr class='tab_bg_1'>";
      echo "<td colspan='2'>";
		 Dropdown::show('Entity', array('name' => 'to_entity', 'comments' => ''));
      echo "</td></tr>";
		*/

		
      echo "<tr class='tab_bg_1'>";
      echo "<th>".$LANG['common'][17]."&nbsp;:".$tt->getMandatoryMark('type')."</th>";
      echo "<td class='right'>";
      Ticket::dropdownType('type', array('value' => $type));
      echo "</td></tr>";

       if ($CFG_GLPI['urgency_mask']!=(1<<3)) {
          if (!$tt->isHiddenField('urgency')) {
             echo "<tr class='tab_bg_1'>";
             echo "<th>".$LANG['joblist'][29]."&nbsp;:".$tt->getMandatoryMark('urgency')."</th>";
             echo "<td>";
             self::dropdownUrgency("urgency", $urgency);
             echo "</td></tr>";
          }
       }

       if (NotificationTargetTicket::isAuthorMailingActivatedForHelpdesk()) {
          echo "<tr class='tab_bg_1'>";
			 /*//SUPRISERVICE*/
          echo "<th>".$LANG['plugin_mobile']['help'][8]."&nbsp;:</th>";
          echo "<td class='right'>";
          Dropdown::showYesNo('use_email_notification',$use_email_notification);
          echo "</td></tr>";
          echo "<tr class='tab_bg_1'>";
			 /*//SUPRISERVICE*/
          echo "<th>".$LANG['plugin_mobile']['help'][11]."&nbsp;:</th>";
          echo "<td class='right'><input name='user_email' value=\"$email\" size='80' onchange=\"use_email_notification.value='1'\">";
          echo "</td></tr>";
       }

       if ($_SESSION["glpiactiveprofile"]["helpdesk_hardware"]!=0) {
          echo "<tr class='tab_bg_1'>";
          echo "<th>".$LANG['help'][24]."&nbsp;: ".$tt->getMandatoryMark('itemtype')."</th>";
          echo "<td class='right'>";
          Ticket::dropdownMyDevices(Session::getLoginUserID(),$_SESSION["glpiactive_entity"]);
          Ticket::dropdownAllDevices("itemtype",$itemtype,$items_id,0,$_SESSION["glpiactive_entity"]);
          echo "</td></tr>";
       }

       echo "<tr class='tab_bg_1 input_right'>";

       echo "<th>".$LANG['common'][36]."&nbsp;:".$tt->getMandatoryMark('itilcategories_id')."</th><td>";
		 //TODO-SUPRISERVICE
       $catopt = array( /*'value' => $ticketcategories_id, */
			              'condition'=>'`is_helpdeskvisible`=1',
			              'comments' => '');
      if ($ticketcategories_id && $tt->isMandatoryField("itilcategories_id")) {
         $opt['display_emptychoice'] = false;
      }
		 Dropdown::show('ItilCategory', $catopt);

       echo "</td></tr>";

       /*//SUPRISERVICE*/
       echo "<tr class='tab_bg_1'><td colspan='2'>";
		 $values = Array();
       self::showActorsPartForm($ID,$values);
       echo "</td></tr>";

		/*//SUPRISERVICE*/
		//Elemento associado
		echo "<tr class='tab_bg_1'>";
		echo "<th>".$LANG['document'][14]."&nbsp;:".$tt->getMandatoryMark('itemtype')."</th><td>";
		$dev_user_id  = Session::getLoginUserID();
		$dev_itemtype = '';
		$dev_items_id = '';
      Ticket::dropdownAllDevices("itemtype", $dev_itemtype, $dev_items_id, 1, $dev_user_id, $_SESSION["glpiactive_entity"]);
		echo "</td></tr>";
		//--

       if (!$tt->isHiddenField('name') || $tt->isPredefinedField('name')) {
          echo "<tr class='tab_bg_1'>";
          echo "<td>".$LANG['common'][57]."&nbsp;:".$tt->getMandatoryMark('name')."</td>";
          echo "<td><input type='text' maxlength='250' size='80' name='name' value=\"".$title."\"></td></tr>";
       }

       if (!$tt->isHiddenField('content') || $tt->isPredefinedField('content')) {
         echo "<tr class='tab_bg_1'>";
         echo "<th>".$LANG['joblist'][6]."&nbsp;: ".$tt->getMandatoryMark('content')."</th>";
         echo "<td class='right' ><textarea name='content' cols='78' rows='14' >$content</textarea>";
       }

       echo "</td></tr>";

       echo "<tr class='tab_bg_1'><th>".$LANG['document'][2]." (".Document::getMaxUploadSize().")&nbsp;:";
/*
 * we hide the picture (aide.png) to prevent the form openning in other window,
 * outside the mobile plugin layout.
       echo "<img src=\"".$CFG_GLPI["root_doc"]."/pics/aide.png\" class='pointer' alt=\"".
              $LANG['central'][7]."\" onclick=\"window.open('".$CFG_GLPI["root_doc"].
              "/front/documenttype.list.php','Help','scrollbars=1,resizable=1,width=1000,height=800')\">";
*/
       echo "</th>";
       echo "<td><input type='file' class='ui-input-text ui-body-null ui-corner-all ui-shadow-inset ui-body-c' name='filename' value=\"\" size='45'></td></tr>";

       echo "<tr class='tab_bg_1'>";
       echo "<td colspan='2' class='center'>";
       echo "<input type='submit' value=\"".$LANG['help'][14]."\" class='submit'>";
       echo "</td></tr>";

       echo "</table></div></form>";
    }

   /**
    * show actor part in ITIL object form
    *
    * @param $ID integer ITIL object ID
    * @param $options array options for default values ($options of showForm)
    *
    * @return nothing display
   **/
   static function showActorsPartForm($ID, $options) {
      global $LANG, $CFG_GLPI;

      $showuserlink = 0;
      if (Session::haveRight('user','r')) {
         $showuserlink = 1;
      }

      // check is_hidden fields
      foreach (array('_users_id_assign', '_groups_id_assign',
                     'suppliers_id_assign') as $f) {
         $is_hidden[$f] = false;
         if (isset($options['_tickettemplate'])
            && $options['_tickettemplate']->isHiddenField($f)) {
            $is_hidden[$f] = true;
         }
      }

      // Manage actors : requester and assign
      echo "<table class='tab_cadre_fixe'>";

		/*//SUPRISERVICE*/
		//Criado para servir de referência para as checagens de permissões
		$ticket = new Ticket();
		
		//Requerente
      echo "<tr class='tab_bg_1'>";
      echo "<th width='29%'>";
      if ((!$is_hidden['_users_id_assign'] || !$is_hidden['_groups_id_assign']
               || !$is_hidden['suppliers_id_assign'])) {
         echo $LANG['job'][4];
      }
      $rand_requester = -1;
      $candeleterequester    = false;

      if ($ID && ($ticket->canAssign() || $ticket->canAssignToMe()) && (!$is_hidden['_users_id_assign'] || !$is_hidden['_groups_id_assign'] || !$is_hidden['suppliers_id_assign'])) {
         $rand_requester = mt_rand();

         echo "&nbsp;&nbsp;";
         echo "<img title=\"".$LANG['buttons'][8]."\" alt=\"".$LANG['buttons'][8]."\"
                    onClick=\"Ext.get('itilactor$rand_requester').setDisplayed('block')\"
                    class='pointer' src='".$CFG_GLPI["root_doc"]."/pics/add_dropdown.png'>";
      }

      if ($ID && $ticket->canAssign()) {
         $candeleterequester = true;
      }
      echo "</th></tr>";
      echo "<tr class='tab_bg_1 top'>";
      echo "<td>";

      if ($rand_requester>=0) {
         Ticket::showActorAddForm(Ticket::REQUESTER, $rand_requester, $_SESSION["glpiactive_entity"],
                                $is_hidden, $ticket->canAssign(),
                                $ticket->canAssign() );
      }
      echo "</td></tr>";

		//observador
      echo "<tr class='tab_bg_1'>";
      echo "<th width='29%'>";
      if ((!$is_hidden['_users_id_assign'] || !$is_hidden['_groups_id_assign']
               || !$is_hidden['suppliers_id_assign'])) {
         echo $LANG['common'][104];
      }
      $rand_observer = -1;
      $candeleteobserver    = false;

      if ($ID && ($ticket->canAssign() || $ticket->canAssignToMe()) && (!$is_hidden['_users_id_assign'] || !$is_hidden['_groups_id_assign'] || !$is_hidden['suppliers_id_assign'])) {
         $rand_observer = mt_rand();

         echo "&nbsp;&nbsp;";
         echo "<img title=\"".$LANG['buttons'][8]."\" alt=\"".$LANG['buttons'][8]."\"
                    onClick=\"Ext.get('itilactor$rand_observer').setDisplayed('block')\"
                    class='pointer' src='".$CFG_GLPI["root_doc"]."/pics/add_dropdown.png'>";
      }

      if ($ID && $ticket->canAssign()) {
         $candeleteobserver = true;
      }
      echo "</th></tr>";
      echo "<tr class='tab_bg_1 top'>";
      echo "<td>";

      if ($rand_observer>=0) {
         Ticket::showActorAddForm(Ticket::OBSERVER, $rand_observer, $_SESSION["glpiactive_entity"],
                                $is_hidden, $ticket->canAssign(),
                                $ticket->canAssign() );
      }
      echo "</td></tr>";

		/*//SUPRISERVICE*/
		//Atribuido para
      echo "<tr class='tab_bg_1'>";
      echo "<th width='29%'>";
      if ((!$is_hidden['_users_id_assign'] || !$is_hidden['_groups_id_assign']
               || !$is_hidden['suppliers_id_assign'])) {
         echo $LANG['job'][5];
      }
      $rand_assign = -1;
      $candeleteassign    = false;

      if ($ID && ($ticket->canAssign() || $ticket->canAssignToMe())
         && (!$is_hidden['_users_id_assign'] || !$is_hidden['_groups_id_assign']
               || !$is_hidden['suppliers_id_assign'])) {

         $rand_assign = mt_rand();

         echo "&nbsp;&nbsp;";
         echo "<img title=\"".$LANG['buttons'][8]."\" alt=\"".$LANG['buttons'][8]."\"
                    onClick=\"Ext.get('itilactor$rand_assign').setDisplayed('block')\"
                    class='pointer' src='".$CFG_GLPI["root_doc"]."/pics/add_dropdown.png'>";
      }

      if ($ID && $ticket->canAssign()) {
         $candeleteassign = true;
      }
      echo "</th></tr>";
      echo "<tr class='tab_bg_1 top'>";
      echo "<td>";

      if ($rand_assign>=0) {
         Ticket::showActorAddForm(Ticket::ASSIGN, $rand_assign, $_SESSION["glpiactive_entity"],
                                $is_hidden, $ticket->canAssign(),
                                $ticket->canAssign() );
      }
      echo "</td>";
      echo "</tr>";

      echo "</table>";
   }

}
