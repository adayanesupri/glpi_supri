ALTER TABLE `glpi_plugin_projet` CHANGE `begin_date` `begin_date` DATE NULL default NULL;
ALTER TABLE `glpi_plugin_projet` CHANGE `end_date` `end_date` DATE NULL default NULL;
ALTER TABLE `glpi_plugin_projet_tasks` CHANGE `begin_date` `begin_date` DATETIME NULL default NULL;
ALTER TABLE `glpi_plugin_projet_tasks` CHANGE `end_date` `end_date` DATETIME NULL default NULL;
UPDATE `glpi_plugin_projet` SET `begin_date` = NULL WHERE `begin_date` ='0000-00-00';
UPDATE `glpi_plugin_projet` SET `end_date` = NULL WHERE `end_date` ='0000-00-00';
UPDATE `glpi_plugin_projet_tasks` SET `begin_date` = NULL WHERE `begin_date` ='0000-00-00 00:00:00';
UPDATE `glpi_plugin_projet_tasks` SET `end_date` = NULL WHERE `end_date` ='0000-00-00 00:00:00';

ALTER TABLE `glpi_plugin_projet_profiles` DROP COLUMN `interface`, DROP COLUMN `is_default`,ADD `open_ticket` char(1) default NULL;