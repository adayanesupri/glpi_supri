<?php
define('DO_NOT_CHECK_HTTP_REFERER', 1);
// If config_db doesn't exist -> start installation
define('GLPI_ROOT', '.');
include (GLPI_ROOT . "/config/based_config.php");
include (GLPI_ROOT . "/inc/includes.php"); 

if (!file_exists(GLPI_CONFIG_DIR . "/config_db.php")) {
   include_once (GLPI_ROOT . "/inc/autoload.function.php");
   Html::redirect("install/install.php");
   die();

} else {
   global $PLUGIN_HOOKS, $LANG;
   Plugin::registerClass('PluginMobileCommon');
   $plug = new Plugin;
   if ($plug->isInstalled('mobile') && $plug->isActivated('mobile')) {
      require_once GLPI_ROOT."/plugins/mobile/inc/common.function.php";
      checkParams();
		/*//SUPRISERVICE*/
      if (isNavigatorMobile())
		{
			redirectMobile();
		}
   }
}

?>