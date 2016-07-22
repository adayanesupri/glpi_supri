<?php
// Based on:
// IRMA, Information Resource-Management and Administration
// Christian Bauer
// ----------------------------------------------------------------------
// Original Author of file: Rafael Pedrini
// Purpose of file: Importação de dados de arquivos em lote para o sistema
// ----------------------------------------------------------------------

define('GLPI_ROOT', '..');
include (GLPI_ROOT . "/inc/includes.php");

Session::checkRight("importacao_dados", "1");

Html::header($LANG['Menu'][101],$_SERVER['PHP_SELF'],"utils","import");

Import::title();

Html::footer();

?>