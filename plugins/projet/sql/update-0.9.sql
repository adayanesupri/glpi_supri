ALTER TABLE `glpi_plugin_projet` CHANGE `author` `FK_users` int(11) NOT NULL default '0';
ALTER TABLE `glpi_plugin_projet` CHANGE `groups` `FK_groups` int(11) NOT NULL default '0';
ALTER TABLE `glpi_plugin_projet_tasks` CHANGE `manager` `FK_users` int(11) NOT NULL default '0';
ALTER TABLE `glpi_plugin_projet_tasks` CHANGE `assign_group` `FK_groups` int(11) NOT NULL default '0';
ALTER TABLE `glpi_plugin_projet_tasks` CHANGE `assign_ent` `FK_enterprise` int(11) NOT NULL default '0';
ALTER TABLE `glpi_plugin_projet_tasks` ADD `location` INT( 4 ) NOT NULL AFTER `show_export`;