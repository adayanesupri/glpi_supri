DROP TABLE IF EXISTS `glpi_plugin_projet_projets`;
CREATE TABLE `glpi_plugin_projet_projets` (
	`id` int(11) NOT NULL auto_increment,
	`entities_id` int(11) NOT NULL default '0',
	`is_recursive` tinyint(1) NOT NULL default '0',
	`name` varchar(255) collate utf8_unicode_ci default NULL,
	`date_begin` date default NULL,
	`date_end` date default NULL,
	`users_id` int(11) NOT NULL default '0' COMMENT 'RELATION to glpi_users (id)',
	`groups_id` int(11) NOT NULL default '0' COMMENT 'RELATION to glpi_groups (id)',
	`plugin_projet_projetstates_id` int(11) NOT NULL default '0',
	`plugin_projet_projets_id` int(11) NOT NULL default '0',
	`advance` float NOT NULL default '0',
	`show_gantt` tinyint(1) NOT NULL default '0',
	`comment` text collate utf8_unicode_ci,
	`description` text collate utf8_unicode_ci,
	`is_helpdesk_visible` int(11) NOT NULL default '1',
	`date_mod` datetime default NULL,
	`notepad` longtext collate utf8_unicode_ci,
	`is_template` tinyint(1) NOT NULL default '0',
	`template_name` varchar(255) collate utf8_unicode_ci default NULL,
	`is_deleted` tinyint(1) NOT NULL default '0',
	PRIMARY KEY  (`id`),
	KEY `name` (`name`),
	KEY `entities_id` (`entities_id`),
	KEY `users_id` (`users_id`),
	KEY `groups_id` (`groups_id`),
	KEY `date_mod` (`date_mod`),
	KEY `is_helpdesk_visible` (`is_helpdesk_visible`),
	KEY `is_deleted` (`is_deleted`),
	KEY `is_template` (`is_template`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

DROP TABLE IF EXISTS `glpi_plugin_projet_projetstates`;
CREATE TABLE `glpi_plugin_projet_projetstates` (
	`id` int(11) NOT NULL auto_increment,
	`name` varchar(255) collate utf8_unicode_ci NOT NULL default '',
   `comment` text COLLATE utf8_unicode_ci,
   `color` char(20) COLLATE utf8_unicode_ci DEFAULT '#CCCCCC',
   `type` tinyint(1) NOT NULL default '0',
	PRIMARY KEY  (`id`),
	KEY `name` (`name`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

DROP TABLE IF EXISTS `glpi_plugin_projet_projets_items`;
CREATE TABLE `glpi_plugin_projet_projets_items` (
	`id` int(11) NOT NULL auto_increment,
	`plugin_projet_projets_id` int(11) NOT NULL default '0'COMMENT 'RELATION to glpi_plugin_projet_projets (id)',
	`items_id` int(11) NOT NULL default '0' COMMENT 'RELATION to various table, according to itemtype (id)',
	`itemtype` varchar(100) collate utf8_unicode_ci NOT NULL COMMENT 'see .class.php file',
	`comment` text collate utf8_unicode_ci,
	PRIMARY KEY  (`id`),
	UNIQUE KEY `unicity` (`plugin_projet_projets_id`,`itemtype`,`items_id`),
	KEY `FK_device` (`items_id`,`itemtype`),
	KEY `item` (`itemtype`,`items_id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

DROP TABLE IF EXISTS `glpi_plugin_projet_tasks`;
CREATE TABLE `glpi_plugin_projet_tasks` (
	`id` int(11) NOT NULL auto_increment,
	`name` varchar(255) collate utf8_unicode_ci default NULL,
	`entities_id` int(11) NOT NULL default '0',
	`is_recursive` tinyint(1) NOT NULL DEFAULT '0',
	`plugin_projet_projets_id` int(11) NOT NULL default '0'COMMENT 'RELATION to glpi_plugin_projet_projets (id)',
	`users_id` int(11) NOT NULL default '0' COMMENT 'RELATION to glpi_users (id)',
	`groups_id` int(11) NOT NULL default '0' COMMENT 'RELATION to glpi_groups (id)',
	`contacts_id` int(11) NOT NULL default '0' COMMENT 'RELATION to glpi_contacts (id)',
	`plugin_projet_tasktypes_id` int(11) NOT NULL default '0' COMMENT 'RELATION to glpi_plugin_projet_tasktypes (id)',
	`plugin_projet_taskstates_id` int(11) NOT NULL default '0',
	`realtime` float NOT NULL default '0',
	`advance` float NOT NULL default '0',
	`priority` int(11) NOT NULL default '1',
	`comment` text collate utf8_unicode_ci,
	`sub` text collate utf8_unicode_ci,
	`others` text collate utf8_unicode_ci,
	`affect` text collate utf8_unicode_ci,
	`plugin_projet_tasks_id` int(11) NOT NULL default '0',
	`depends` tinyint(4) NOT NULL default '0',
	`show_gantt` tinyint(1) NOT NULL default '0',
	`locations_id` int(11) NOT NULL default '0' COMMENT 'RELATION to glpi_locations (id)',
	`is_template` tinyint(1) NOT NULL default '0',
	`template_name` varchar(255) collate utf8_unicode_ci default NULL,
	`is_deleted` tinyint(1) NOT NULL default '0',
	`date_mod` datetime default NULL,
	PRIMARY KEY  (`id`),
	KEY `name` (`name`),
	KEY `entities_id` (`entities_id`),
	KEY `plugin_projet_projets_id` (`plugin_projet_projets_id`),
	KEY `plugin_projet_tasktypes_id` (`plugin_projet_tasktypes_id`),
	KEY `users_id` (`users_id`),
	KEY `groups_id` (`groups_id`),
	KEY `contacts_id` (`contacts_id`),
	KEY `plugin_projet_taskstates_id` (`plugin_projet_taskstates_id`),
	KEY `is_deleted` (`is_deleted`),
	KEY `is_template` (`is_template`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

DROP TABLE IF EXISTS `glpi_plugin_projet_taskstates`;
CREATE TABLE `glpi_plugin_projet_taskstates` (
	`id` int(11) NOT NULL auto_increment,
	`name` varchar(255) collate utf8_unicode_ci NOT NULL default '',
   `comment` text COLLATE utf8_unicode_ci,
   `color` char(20) COLLATE utf8_unicode_ci DEFAULT '#CCCCCC',
   `for_dependency` tinyint(1) NOT NULL default '0',
   `for_planning` tinyint(1) NOT NULL default '0',
	PRIMARY KEY  (`id`),
	KEY `name` (`name`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

DROP TABLE IF EXISTS `glpi_plugin_projet_taskplannings`;
CREATE TABLE `glpi_plugin_projet_taskplannings` (
  `id` int(11) NOT NULL auto_increment,
  `plugin_projet_tasks_id` int(11) NOT NULL default '0'COMMENT 'RELATION to glpi_plugin_projet_tasks (id)',
  `begin` datetime default NULL,
  `end` datetime default NULL,
  PRIMARY KEY  (`id`),
  KEY `begin` (`begin`),
  KEY `end` (`end`),
  KEY `plugin_projet_tasks_id` (`plugin_projet_tasks_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

DROP TABLE IF EXISTS `glpi_plugin_projet_tasks_items`;
CREATE TABLE `glpi_plugin_projet_tasks_items` (
	`id` int(11) NOT NULL auto_increment,
	`plugin_projet_tasks_id` int(11) NOT NULL default '0'COMMENT 'RELATION to glpi_plugin_projet_tasks (id)',
	`items_id` int(11) NOT NULL default '0' COMMENT 'RELATION to various table, according to itemtype (id)',
	`itemtype` varchar(100) collate utf8_unicode_ci NOT NULL COMMENT 'see .class.php file',
	`comment` text collate utf8_unicode_ci,
	PRIMARY KEY  (`id`),
	UNIQUE KEY `unicity` (`plugin_projet_tasks_id`,`itemtype`,`items_id`),
	KEY `FK_device` (`items_id`,`itemtype`),
	KEY `item` (`itemtype`,`items_id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

DROP TABLE IF EXISTS `glpi_plugin_projet_tasktypes`;
CREATE TABLE `glpi_plugin_projet_tasktypes` (
	`id` int(11) NOT NULL auto_increment,
	`entities_id` int(11) NOT NULL default '0',
	`name` varchar(255) collate utf8_unicode_ci default NULL,
	`comment` text collate utf8_unicode_ci,
	PRIMARY KEY  (`id`),
	KEY `name` (`name`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

DROP TABLE IF EXISTS `glpi_plugin_projet_profiles`;
CREATE TABLE `glpi_plugin_projet_profiles` (
	`id` int(11) NOT NULL auto_increment,
	`profiles_id` int(11) NOT NULL default '0' COMMENT 'RELATION to glpi_profiles (id)',
	`projet` char(1) collate utf8_unicode_ci default NULL,
	`task` char(1) collate utf8_unicode_ci default NULL,
	`open_ticket` char(1) collate utf8_unicode_ci default NULL,
	PRIMARY KEY  (`id`),
	KEY `profiles_id` (`profiles_id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

INSERT INTO `glpi_displaypreferences` VALUES (NULL,'PluginProjetProjet','2','1','0');
INSERT INTO `glpi_displaypreferences` VALUES (NULL,'PluginProjetProjet','3','2','0');
INSERT INTO `glpi_displaypreferences` VALUES (NULL,'PluginProjetProjet','4','3','0');
INSERT INTO `glpi_displaypreferences` VALUES (NULL,'PluginProjetProjet','5','4','0');
INSERT INTO `glpi_displaypreferences` VALUES (NULL,'PluginProjetProjet','6','5','0');
INSERT INTO `glpi_displaypreferences` VALUES (NULL,'PluginProjetProjet','7','6','0');
INSERT INTO `glpi_displaypreferences` VALUES (NULL,'PluginProjetTask','7','2','0');
INSERT INTO `glpi_displaypreferences` VALUES (NULL,'PluginProjetTask','10','3','0');
INSERT INTO `glpi_displaypreferences` VALUES (NULL,'PluginProjetTask','11','4','0');
INSERT INTO `glpi_displaypreferences` VALUES (NULL,'PluginProjetTask','6','5','0');
INSERT INTO `glpi_displaypreferences` VALUES (NULL,'PluginProjetTask','8','6','0');
INSERT INTO `glpi_displaypreferences` VALUES (NULL,'PluginProjetTask','9','7','0');
INSERT INTO `glpi_displaypreferences` VALUES (NULL,'PluginProjetTask','16','8','0');

INSERT INTO `glpi_notificationtemplates` VALUES(NULL, 'Projets', 'PluginProjetProjet', '2010-12-29 11:04:46','',NULL);
INSERT INTO `glpi_notificationtemplates` VALUES(NULL, 'Alert Projets Tasks', 'PluginProjetProjet', '2010-12-29 11:04:46','',NULL);