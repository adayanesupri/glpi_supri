DROP TABLE IF EXISTS `glpi_plugin_projet`;
CREATE TABLE `glpi_plugin_projet` (
	`ID` int(11) NOT NULL auto_increment,
	`FK_entities` int(11) NOT NULL default '0',
	`recursive` tinyint(1) NOT NULL default '0',
	`name` varchar(255) collate utf8_unicode_ci NOT NULL default '',
	`begin_date` date NOT NULL default '0000-00-00',
	`end_date` date NOT NULL default '0000-00-00',
	`author` int(11) NOT NULL default '0',
	`groups` int(11) NOT NULL default '0',
	`status` INT( 4 ) NOT NULL default '0',
	`parentID` int(11) NOT NULL default '0',
	`advance` float DEFAULT '0' NOT NULL,
	`show_gantt` smallint(6) NOT NULL default '1',
	`show_export` smallint(6) NOT NULL default '1',
	`comments` text,
	`description` text,
	`notes` LONGTEXT,
	`is_template` smallint(6) NOT NULL default '0',
	`tplname` varchar(200) collate utf8_unicode_ci NOT NULL default '',
	`deleted` smallint(6) NOT NULL default '0',
	PRIMARY KEY  (`ID`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

DROP TABLE IF EXISTS `glpi_plugin_projet_items`;
CREATE TABLE `glpi_plugin_projet_items` (
	`ID` int(11) NOT NULL auto_increment,
	`FK_projet` int(11) NOT NULL default '0',
	`FK_device` int(11) NOT NULL default '0',
	`device_type` int(11) NOT NULL default '0',
	`is_template` smallint(6) NOT NULL default '0',
	PRIMARY KEY  (`ID`),
	KEY `FK_device` (`FK_device`,`FK_projet`),
	KEY `FK_projet` (`FK_projet`),
	KEY `FK_device_2` (`FK_device`),
	KEY device_type (`device_type`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

DROP TABLE IF EXISTS `glpi_plugin_projet_tasks`;
CREATE TABLE `glpi_plugin_projet_tasks` (
	`ID` int(11) NOT NULL auto_increment,
	`name` varchar(250) collate utf8_unicode_ci NOT NULL default '',
	`FK_projet` int(4) NOT NULL default '0',
	`manager` int(11) NOT NULL default '0',
	`assign_group` int(11) NOT NULL default '0',
	`assign_ent` int(11) NOT NULL default '0',
	`type_task` int(4) NOT NULL default '0',
	`status` int(4) NOT NULL default '0',
	`begin_date` datetime NOT NULL default '0000-00-00 00:00:00',
	`end_date` datetime NOT NULL default '0000-00-00 00:00:00',
	`realtime` float DEFAULT '0' NOT NULL,
	`use_planning` smallint(6) NOT NULL default '1',
	`advance` float DEFAULT '0' NOT NULL,
	`priority` tinyint(4) DEFAULT '1' NOT NULL,
	`contents` text,
	`sub` text,
	`others` text,
	`affect` text,
	`parentID` int(11) NOT NULL default '0',
	`depends` tinyint(4) NOT NULL default '0',
	`is_template` smallint(6) NOT NULL default '0',
	`tplname` varchar(200) collate utf8_unicode_ci NOT NULL default '',
	`show_gantt` smallint(6) NOT NULL default '1',
	`show_export` smallint(6) NOT NULL default '1',
	`deleted` smallint(6) NOT NULL default '0',
	PRIMARY KEY  (`ID`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

DROP TABLE IF EXISTS `glpi_plugin_projet_tasks_items`;
CREATE TABLE `glpi_plugin_projet_tasks_items` (
	`ID` int(11) NOT NULL auto_increment,
	`FK_task` int(11) NOT NULL default '0',
	`FK_device` int(11) NOT NULL default '0',
	`device_type` int(11) NOT NULL default '0',
	`is_template` smallint(6) NOT NULL default '0',
	PRIMARY KEY  (`ID`),
	KEY `FK_device` (`FK_device`,`FK_task`),
	KEY `FK_task` (`FK_task`),
	KEY `FK_device_2` (`FK_device`),
	KEY device_type (`device_type`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

DROP TABLE IF EXISTS `glpi_dropdown_plugin_projet_status`;
CREATE TABLE `glpi_dropdown_plugin_projet_status` (
	`ID` int(11) NOT NULL auto_increment,
	`name` varchar(255) collate utf8_unicode_ci NOT NULL default '',
	`comments` text,
	PRIMARY KEY  (`ID`),
	KEY `name` (`name`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

DROP TABLE IF EXISTS `glpi_dropdown_plugin_projet_tasks_type`;
CREATE TABLE `glpi_dropdown_plugin_projet_tasks_type` (
	`ID` int(11) NOT NULL auto_increment,
	`FK_entities` int(11) NOT NULL default '0',
	`name` varchar(255) collate utf8_unicode_ci NOT NULL default '',
	`comments` text,
	PRIMARY KEY  (`ID`),
	KEY `name` (`name`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

DROP TABLE IF EXISTS `glpi_dropdown_plugin_projet_task_status`;
CREATE TABLE `glpi_dropdown_plugin_projet_task_status` (
	`ID` int(11) NOT NULL auto_increment,
	`name` varchar(255) collate utf8_unicode_ci NOT NULL default '',
	`comments` text,
	PRIMARY KEY  (`ID`),
	KEY `name` (`name`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

DROP TABLE IF EXISTS `glpi_plugin_projet_mailing`;
CREATE TABLE `glpi_plugin_projet_mailing` (
	`ID` int(11) NOT NULL auto_increment,
	`type` varchar(255) collate utf8_unicode_ci default NULL,
	`FK_item` int(11) NOT NULL default '0',
	`item_type` int(11) NOT NULL default '0',
	PRIMARY KEY  (`ID`),
	UNIQUE KEY `mailings` (`type`,`FK_item`,`item_type`),
	KEY `type` (`type`),
	KEY `FK_item` (`FK_item`),
	KEY `item_type` (`item_type`),
	KEY `items` (`item_type`,`FK_item`)
) ENGINE=MyISAM AUTO_INCREMENT=14 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

DROP TABLE IF EXISTS `glpi_plugin_projet_profiles`;
CREATE TABLE `glpi_plugin_projet_profiles` (
	`ID` int(11) NOT NULL auto_increment,
	`name` varchar(255) collate utf8_unicode_ci default NULL,
	`interface` varchar(50) collate utf8_unicode_ci NOT NULL default 'projet',
	`is_default` smallint(6) NOT NULL default '0',
	`projet` char(1) default NULL,
	`task` char(1) default NULL,
	PRIMARY KEY  (`ID`),
	KEY `interface` (`interface`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

INSERT INTO glpi_plugin_projet_mailing VALUES ('1','projet','1','1');
INSERT INTO glpi_plugin_projet_mailing VALUES ('2','task','1','1');

INSERT INTO `glpi_display` ( `ID` , `type` , `num` , `rank` , `FK_users` )  VALUES (NULL,'2300','2','1','0');
INSERT INTO `glpi_display` ( `ID` , `type` , `num` , `rank` , `FK_users` )  VALUES (NULL,'2300','3','2','0');
INSERT INTO `glpi_display` ( `ID` , `type` , `num` , `rank` , `FK_users` )  VALUES (NULL,'2300','4','3','0');
INSERT INTO `glpi_display` ( `ID` , `type` , `num` , `rank` , `FK_users` )  VALUES (NULL,'2300','5','4','0');
INSERT INTO `glpi_display` ( `ID` , `type` , `num` , `rank` , `FK_users` )  VALUES (NULL,'2300','6','5','0');
INSERT INTO `glpi_display` ( `ID` , `type` , `num` , `rank` , `FK_users` )  VALUES (NULL,'2300','7','6','0');
INSERT INTO `glpi_display` ( `ID` , `type` , `num` , `rank` , `FK_users` )  VALUES (NULL,'2301','7','2','0');
INSERT INTO `glpi_display` ( `ID` , `type` , `num` , `rank` , `FK_users` )  VALUES (NULL,'2301','10','3','0');
INSERT INTO `glpi_display` ( `ID` , `type` , `num` , `rank` , `FK_users` )  VALUES (NULL,'2301','11','4','0');
INSERT INTO `glpi_display` ( `ID` , `type` , `num` , `rank` , `FK_users` )  VALUES (NULL,'2301','6','5','0');
INSERT INTO `glpi_display` ( `ID` , `type` , `num` , `rank` , `FK_users` )  VALUES (NULL,'2301','8','6','0');
INSERT INTO `glpi_display` ( `ID` , `type` , `num` , `rank` , `FK_users` )  VALUES (NULL,'2301','9','7','0');
INSERT INTO `glpi_display` ( `ID` , `type` , `num` , `rank` , `FK_users` )  VALUES (NULL,'2301','16','8','0');