DROP TABLE IF EXISTS `glpi_plugin_projet_projets`;

CREATE TABLE `glpi_plugin_projet_projets` (
	`id` int(11) NOT NULL auto_increment,
	`entities_id` int(11) NOT NULL default '0',
	`is_recursive` tinyint(1) NOT NULL default '0',
	`name` varchar(255) collate utf8_unicode_ci NOT NULL default '',
	`begin_date` DATE NULL default NULL,
	`end_date` DATE NULL default NULL,
	`users_id` int(11) NOT NULL default '0',
	`groups_id` int(11) NOT NULL default '0',
	`plugin_projet_projetstates_id` INT( 4 ) NOT NULL default '0',
	`plugin_projet_projets_id` int(11) NOT NULL default '0',
	`advance` float DEFAULT '0' NOT NULL,
	`show_gantt` smallint(6) NOT NULL default '1',
	`show_export` smallint(6) NOT NULL default '1',
   `comment` text COLLATE utf8_unicode_ci,
	`description` text,
	`notes` LONGTEXT,
	`is_template` smallint(6) NOT NULL default '0',
	`template_name` varchar(255) collate utf8_unicode_ci NOT NULL default '',
	`is_deleted` smallint(6) NOT NULL default '0',
	PRIMARY KEY  (`id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;



DROP TABLE IF EXISTS `glpi_plugin_projet_projetitems`;

CREATE TABLE `glpi_plugin_projet_projetitems` (
	`id` int(11) NOT NULL auto_increment,
	`plugin_projet_projets_id` int(11) NOT NULL default '0',
	`items_id` int(11) NOT NULL default '0',
	`itemtype` varchar(100) NOT NULL default '0',
	`is_template` smallint(6) NOT NULL default '0',
	PRIMARY KEY  (`id`),
	KEY `items_id` (`items_id`,`plugin_projet_projets_id`),
	KEY `plugin_projet_projets_id` (`plugin_projet_projets_id`),
	KEY `items_id_2` (`items_id`),
	KEY itemtype (`itemtype`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;



DROP TABLE IF EXISTS `glpi_plugin_projet_tasks`;

CREATE TABLE `glpi_plugin_projet_tasks` (
	`id` int(11) NOT NULL auto_increment,
	`name` varchar(250) collate utf8_unicode_ci NOT NULL default '',
	`plugin_projet_projets_id` int(4) NOT NULL default '0',
	`users_id` int(11) NOT NULL default '0',
	`groups_id` int(11) NOT NULL default '0',
	`suppliers_id` int(11) NOT NULL default '0',
	`plugin_projet_tasktypes_id` int(4) NOT NULL default '0',
	`plugin_projet_taskstates_id` int(4) NOT NULL default '0',
	`begin_date` DATETIME NULL default NULL,
	`end_date` DATETIME NULL default NULL,
	`realtime` float DEFAULT '0' NOT NULL,
	`use_planning` smallint(6) NOT NULL default '1',
	`advance` float DEFAULT '0' NOT NULL,
	`priority` tinyint(4) DEFAULT '1' NOT NULL,
	`contents` text,
	`sub` text,
	`others` text,
	`affect` text,
	`plugin_projet_tasks_id` int(11) NOT NULL default '0',
	`depends` tinyint(4) NOT NULL default '0',
	`show_gantt` smallint(6) NOT NULL default '1',
	`show_export` smallint(6) NOT NULL default '1',
	`locations_id` INT( 11 ) NOT NULL,
	`is_template` smallint(6) NOT NULL default '0',
	`template_name` varchar(255) collate utf8_unicode_ci NOT NULL default '',
	`is_deleted` smallint(6) NOT NULL default '0',
	PRIMARY KEY  (`id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;



DROP TABLE IF EXISTS `glpi_plugin_projet_taskitems`;

CREATE TABLE `glpi_plugin_projet_taskitems` (
	`id` int(11) NOT NULL auto_increment,
	`plugin_projet_tasks_id` int(11) NOT NULL default '0',
	`items_id` int(11) NOT NULL default '0',
	`itemtype` int(11) NOT NULL default '0',
	`is_template` smallint(6) NOT NULL default '0',
	PRIMARY KEY  (`id`),
	KEY `items_id` (`items_id`,`plugin_projet_tasks_id`),
	KEY `plugin_projet_tasks_id` (`plugin_projet_tasks_id`),
	KEY `items_id_2` (`items_id`),
	KEY `itemtype` (`itemtype`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;



DROP TABLE IF EXISTS `glpi_plugin_projet_projetstates`;

CREATE TABLE `glpi_plugin_projet_projetstates` (
	`id` int(11) NOT NULL auto_increment,
	`name` varchar(255) collate utf8_unicode_ci NOT NULL default '',
   `comment` text COLLATE utf8_unicode_ci,
	PRIMARY KEY  (`id`),
	KEY `name` (`name`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;



DROP TABLE IF EXISTS `glpi_plugin_projet_tasktypes`;

CREATE TABLE `glpi_plugin_projet_tasktypes` (
	`id` int(11) NOT NULL auto_increment,
	`entities_id` int(11) NOT NULL default '0',
	`name` varchar(255) collate utf8_unicode_ci NOT NULL default '',
   `comment` text COLLATE utf8_unicode_ci,
	PRIMARY KEY  (`id`),
	KEY `name` (`name`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;



DROP TABLE IF EXISTS `glpi_plugin_projet_taskstates`;

CREATE TABLE `glpi_plugin_projet_taskstates` (
	`id` int(11) NOT NULL auto_increment,
	`name` varchar(255) collate utf8_unicode_ci NOT NULL default '',
   `comment` text COLLATE utf8_unicode_ci,
	PRIMARY KEY  (`id`),
	KEY `name` (`name`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;



DROP TABLE IF EXISTS `glpi_plugin_projet_mailings`;

CREATE TABLE `glpi_plugin_projet_mailings` (
	`id` int(11) NOT NULL auto_increment,
	`type` varchar(255) collate utf8_unicode_ci default NULL,
	`plugin_projet_items_id` int(11) NOT NULL default '0',
	`itemtype` int(11) NOT NULL default '0',
	PRIMARY KEY  (`id`),
	UNIQUE KEY `mailings` (`type`,`plugin_projet_items_id`,`itemtype`),
	KEY `type` (`type`),
	KEY `plugin_projet_items_id` (`plugin_projet_items_id`),
	KEY `itemtype` (`itemtype`),
	KEY `items` (`itemtype`,`plugin_projet_items_id`)
) ENGINE=MyISAM AUTO_INCREMENT=14 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;



DROP TABLE IF EXISTS `glpi_plugin_projet_profiles`;

CREATE TABLE `glpi_plugin_projet_profiles` (
	`id` int(11) NOT NULL auto_increment,
	`name` varchar(255) collate utf8_unicode_ci default NULL,
	`projet` char(1) default NULL,
	`task` char(1) default NULL,
	`open_ticket` char(1) default NULL,
	PRIMARY KEY  (`id`),
	KEY `name` (`name`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;



INSERT INTO glpi_plugin_projet_mailings VALUES ('1','projet','1','1');
INSERT INTO glpi_plugin_projet_mailings VALUES ('2','task','1','1');

INSERT INTO `glpi_displaypreferences` ( `id`, `itemtype`, `num`, `rank`, `users_id` )
         VALUES (NULL,'PluginProjetProjet','2','1','0');
INSERT INTO `glpi_displaypreferences` ( `id`, `itemtype`, `num`, `rank`, `users_id` )
         VALUES (NULL,'PluginProjetProjet','3','2','0');
INSERT INTO `glpi_displaypreferences` ( `id`, `itemtype`, `num`, `rank`, `users_id` )
         VALUES (NULL,'PluginProjetProjet','4','3','0');
INSERT INTO `glpi_displaypreferences` ( `id`, `itemtype`, `num`, `rank`, `users_id` )
         VALUES (NULL,'PluginProjetProjet','5','4','0');
INSERT INTO `glpi_displaypreferences` ( `id`, `itemtype`, `num`, `rank`, `users_id` )
         VALUES (NULL,'PluginProjetProjet','6','5','0');
INSERT INTO `glpi_displaypreferences` ( `id`, `itemtype`, `num`, `rank`, `users_id` )
         VALUES (NULL,'PluginProjetProjet','7','6','0');
INSERT INTO `glpi_displaypreferences` ( `id`, `itemtype`, `num`, `rank`, `users_id` )
         VALUES (NULL,'PluginProjetTask','7','2','0');
INSERT INTO `glpi_displaypreferences` ( `id`, `itemtype`, `num`, `rank`, `users_id` )
         VALUES (NULL,'PluginProjetTask','10','3','0');
INSERT INTO `glpi_displaypreferences` ( `id`, `itemtype`, `num`, `rank`, `users_id` )
         VALUES (NULL,'PluginProjetTask','11','4','0');
INSERT INTO `glpi_displaypreferences` ( `id`, `itemtype`, `num`, `rank`, `users_id` )
         VALUES (NULL,'PluginProjetTask','6','5','0');
INSERT INTO `glpi_displaypreferences` ( `id`, `itemtype`, `num`, `rank`, `users_id` )
         VALUES (NULL,'PluginProjetTask','8','6','0');
INSERT INTO `glpi_displaypreferences` ( `id`, `itemtype`, `num`, `rank`, `users_id` )
         VALUES (NULL,'PluginProjetTask','9','7','0');
INSERT INTO `glpi_displaypreferences` ( `id`, `itemtype`, `num`, `rank`, `users_id` )
         VALUES (NULL,'PluginProjetTask','16','8','0');