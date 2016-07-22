<?php
/*
 */

// ----------------------------------------------------------------------
// Original Author of file: Olivier Moron
// Purpose of file: Provides frame for masking button clicks and key pressed
// ----------------------------------------------------------------------

// Init the hooks of the plugin
function plugin_init_mask() {
   global $PLUGIN_HOOKS,$LANG,$CFG_GLPI;

   // Add specific files to add to the header : javascript or css
   $PLUGIN_HOOKS['add_javascript']['mask'] = 'mask.js';
}


// Get the name and the version of the plugin - Needed
function plugin_version_mask() {

   return array('name'           => 'Mask',
                'version'        => '1.1.0',
                'author'         => 'Olivier Moron',
                'minGlpiVersion' => '0.80');// For compatibility / no install in version < 0.80
}


// Optional : check prerequisites before install : may print errors or add to message after redirect
function plugin_mask_check_prerequisites() {

   if (version_compare(GLPI_VERSION,'0.80','lt') || version_compare(GLPI_VERSION,'0.84','ge')) {
      echo "This plugin requires GLPI >= 0.80";
      return false;
   }
   return true;
}


// Check configuration process for plugin : need to return true if succeeded
// Can display a message only if failure and $verbose is true
function plugin_mask_check_config($verbose=false) {
   global $LANG;

   if (true) { // Your configuration check
      return true;
   }
   if ($verbose) {
      echo $LANG['plugins'][2];
   }
   return false;
}


?>
