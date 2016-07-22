<?php

define('GLPI_ROOT', '../../..');
include (GLPI_ROOT . "/inc/includes.php");

$welcome = $LANG['job'][13];

$common = new PluginMobileCommon;

//$common->displayHeader($welcome, 'ss_menu.php?menu=maintain');

if (isset($_REQUEST['delete_user'])) {
   $ticket_user = new Ticket_User();
   $ticket_user->check($_REQUEST['id'], 'w');
   $ticket_user->delete($_REQUEST);

	Event::log($_REQUEST['tickets_id'], "ticket", 4, "tracking", $_SESSION["glpiname"]." ".$LANG['log'][122]);
   Html::redirect($CFG_GLPI["root_doc"]."/plugins/mobile/front/item.php?itemtype=Ticket&menu=maintain&ssmenu=ticket&id=".$_REQUEST['tickets_id']);

} else if (isset($_REQUEST['delete_group'])) {
   $group_ticket = new Group_Ticket();
   $group_ticket->check($_REQUEST['id'], 'w');
   $group_ticket->delete($_REQUEST);

   Event::log($_REQUEST['tickets_id'], "ticket", 4, "tracking", $_SESSION["glpiname"]." ".$LANG['log'][122]);
   Html::redirect($CFG_GLPI["root_doc"]."/plugins/mobile/front/item.php?itemtype=Ticket&menu=maintain&ssmenu=ticket&id=".$_REQUEST['tickets_id']);

}

$common->displayFooter();

?>
