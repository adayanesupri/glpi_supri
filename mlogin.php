<?php

/*
 * @version $Id: login.php 18886 2012-07-11 09:51:01Z yllen $
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

define('DO_NOT_CHECK_HTTP_REFERER', 1);
//print_r($_POST);
define('GLPI_ROOT', '.');
include (GLPI_ROOT . "/inc/includes.php");

/*
if (!isset($_SESSION["glpicookietest"]) || $_SESSION["glpicookietest"]!='testcookie') {
   if (!is_writable(GLPI_SESSION_DIR)) {
      Html::redirect($CFG_GLPI['root_doc'] . "/index.php?error=2");
   } else {
      Html::redirect($CFG_GLPI['root_doc'] . "/index.php?error=1");
   }
}
*/

$_POST = array_map('stripslashes', $_POST);

//Do login and checks
//$user_present = 1;
if (!isset($_POST['username'])) {
   $_POST['username'] = '';
}

if (isset($_POST['password'])) {
   $_POST['password'] = Toolbox::unclean_cross_side_scripting_deep($_POST['password']);
} else {
   $_POST['password'] = '';
}

/*
// Redirect management
$REDIRECT = "";
if (isset($_POST['redirect']) && strlen($_POST['redirect'])>0) {
   $REDIRECT = "?redirect=" .$_POST['redirect'];

} else if (isset($_GET['redirect']) && strlen($_GET['redirect'])>0) {
   $REDIRECT = "?redirect=" .$_GET['redirect'];
}
*/

$auth = new Auth();

//echo "<br>Sessão ID: " . session_id() . "<br>";

// now we can continue with the process...
if ($auth->Login($_POST['username'], $_POST['password'],
                 (isset($_REQUEST["noAUTO"])?$_REQUEST["noAUTO"]:false))) {
	echo "true";
} else {
   // we have done at least a good login? No, we exit.
   echo "false";
   exit();
}

?>
