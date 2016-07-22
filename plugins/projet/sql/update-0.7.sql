DROP TABLE `glpi_plugin_project_documents`;
DROP TABLE `glpi_plugin_project_users`;
DROP TABLE `glpi_plugin_project_groups`;
DROP TABLE `glpi_plugin_project_enterprises`;
DROP TABLE `glpi_plugin_project_contracts`;
DROP TABLE glpi_plugin_project_setup;

ALTER TABLE `glpi_plugin_project` CHANGE `status` `status` VARCHAR(20) collate utf8_unicode_ci NOT NULL;
UPDATE `glpi_plugin_project` SET `status` = '1' WHERE `status` = 'new';
UPDATE `glpi_plugin_project` SET `status` = '2' WHERE `status` = 'standby';
UPDATE `glpi_plugin_project` SET `status` = '3' WHERE `status` = 'old';
UPDATE `glpi_plugin_project` SET `status` = '4' WHERE `status` = 'old_notdone';

ALTER TABLE `glpi_plugin_project_tasks` CHANGE `status` `status` VARCHAR(20) collate utf8_unicode_ci NOT NULL;
UPDATE `glpi_plugin_project_tasks` SET `status` = '1' WHERE `status` = 'new';
UPDATE `glpi_plugin_project_tasks` SET `status` = '2' WHERE `status` = 'plan';
UPDATE `glpi_plugin_project_tasks` SET `status` = '3' WHERE `status` = 'standby';
UPDATE `glpi_plugin_project_tasks` SET `status` = '4' WHERE `status` = 'old';
UPDATE `glpi_plugin_project_tasks` SET `status` = '5' WHERE `status` = 'old_notdone';

ALTER TABLE `glpi_plugin_project` CHANGE `deleted` `deleted` smallint(6) NOT NULL default '0';
UPDATE `glpi_plugin_project` SET `deleted` = '0' WHERE `deleted` = '1';
UPDATE `glpi_plugin_project` SET `deleted` = '1' WHERE `deleted` = '2';

ALTER TABLE `glpi_plugin_project_profiles` CHANGE `is_default` `is_default` smallint(6) NOT NULL default '0';
UPDATE `glpi_plugin_project_profiles` SET `is_default` = '0' WHERE `is_default` = '1';
UPDATE `glpi_plugin_project_profiles` SET `is_default` = '1' WHERE `is_default` = '2';

ALTER TABLE `glpi_plugin_project_items` CHANGE `is_template` `is_template` smallint(6) NOT NULL default '0';
UPDATE `glpi_plugin_project_items` SET `is_template` = '0' WHERE `is_template` = '1';
UPDATE `glpi_plugin_project_items` SET `is_template` = '1' WHERE `is_template` = '2';

ALTER TABLE `glpi_plugin_project_tasks_items` CHANGE `is_template` `is_template` smallint(6) NOT NULL default '0';
UPDATE `glpi_plugin_project_tasks_items` SET `is_template` = '0' WHERE `is_template` = '1';
UPDATE `glpi_plugin_project_tasks_items` SET `is_template` = '1' WHERE `is_template` = '2';

ALTER TABLE `glpi_plugin_project_tasks` ADD `parentID` int(11) NOT NULL default '0';
ALTER TABLE `glpi_plugin_project_tasks` CHANGE `title` `name` VARCHAR( 250 ) collate utf8_unicode_ci NOT NULL;
ALTER TABLE `glpi_plugin_project_tasks` CHANGE `manager` `manager` int(11) NOT NULL default '0';
ALTER TABLE `glpi_plugin_project_tasks` CHANGE `status` `status` smallint(6) NOT NULL default '0';
ALTER TABLE `glpi_plugin_project_tasks` ADD `depends` tinyint(4) NOT NULL default '0';
ALTER TABLE `glpi_plugin_project_tasks` ADD `deleted` smallint(6) NOT NULL default '0';
ALTER TABLE `glpi_plugin_project_tasks` ADD `is_template` smallint(6) NOT NULL default '0';
ALTER TABLE `glpi_plugin_project_tasks` ADD `tplname` varchar(200) collate utf8_unicode_ci NOT NULL default '';
ALTER TABLE `glpi_plugin_project_tasks` ADD `show_gantt` smallint(6) NOT NULL default '1';
ALTER TABLE `glpi_plugin_project_tasks` ADD `show_export` smallint(6) NOT NULL default '1';
ALTER TABLE `glpi_plugin_project_tasks` CHANGE `begin_date` `begin_date`  datetime NOT NULL default '0000-00-00 00:00:00';
ALTER TABLE `glpi_plugin_project_tasks` CHANGE `end_date` `end_date`  datetime NOT NULL default '0000-00-00 00:00:00';
ALTER TABLE `glpi_plugin_project_tasks` ADD `use_planning` smallint(6) NOT NULL default '1' AFTER `realtime`;

ALTER TABLE `glpi_plugin_project` CHANGE `group` `groups` INT( 11 ) NOT NULL DEFAULT '0';
ALTER TABLE `glpi_plugin_project` ADD `is_template` smallint(6) NOT NULL default '0';
ALTER TABLE `glpi_plugin_project` ADD `tplname` varchar(200) collate utf8_unicode_ci NOT NULL default '';
ALTER TABLE `glpi_plugin_project` ADD `show_gantt` smallint(6) NOT NULL default '1';
ALTER TABLE `glpi_plugin_project` ADD `show_export` smallint(6) NOT NULL default '1';
ALTER TABLE `glpi_plugin_project` ADD `FK_entities` int(11) NOT NULL default '0' AFTER `ID`;
ALTER TABLE `glpi_plugin_project` CHANGE `status` `status` INT( 4 ) NOT NULL default '0';
ALTER TABLE `glpi_plugin_project` CHANGE `comments` `comments` TEXT;
ALTER TABLE `glpi_plugin_project` CHANGE `description` `description` TEXT;
ALTER TABLE `glpi_plugin_project` CHANGE `notes` `notes` LONGTEXT;

ALTER TABLE `glpi_plugin_project_items` CHANGE `device_type` `device_type` int(11) NOT NULL default '0';

ALTER TABLE `glpi_plugin_project_tasks_items` CHANGE `device_type` `device_type` int(11) NOT NULL default '0';

ALTER TABLE `glpi_dropdown_plugin_project_tasks_type` ADD `FK_entities` int(11) NOT NULL default '0' AFTER `ID`;

ALTER TABLE `glpi_plugin_project_profiles` DROP `create_project`;
ALTER TABLE `glpi_plugin_project_profiles` DROP `update_project`;
ALTER TABLE `glpi_plugin_project_profiles` DROP `delete_project`;
ALTER TABLE `glpi_plugin_project_profiles` DROP `all_project`;
ALTER TABLE `glpi_plugin_project_profiles` DROP `create_task`;
ALTER TABLE `glpi_plugin_project_profiles` DROP `update_task`;
ALTER TABLE `glpi_plugin_project_profiles` DROP `delete_task`;
ALTER TABLE `glpi_plugin_project_profiles` DROP `all_task`;
ALTER TABLE `glpi_plugin_project_profiles` CHANGE `project` `projet` char(1) default NULL;
UPDATE `glpi_plugin_project_profiles` SET `interface` = 'projet';

CREATE TABLE `glpi_dropdown_plugin_project_status` (
	`ID` int(11) NOT NULL auto_increment,
	`name` varchar(255) collate utf8_unicode_ci NOT NULL default '',
	`comments` text,
	PRIMARY KEY  (`ID`),
	KEY `name` (`name`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

CREATE TABLE `glpi_dropdown_plugin_project_task_status` (
	`ID` int(11) NOT NULL auto_increment,
	`name` varchar(255) collate utf8_unicode_ci NOT NULL default '',
	`comments` text,
	PRIMARY KEY  (`ID`),
	KEY `name` (`name`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

CREATE TABLE `glpi_plugin_project_mailing` (
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

INSERT INTO glpi_plugin_project_mailing VALUES ('1','project','1','1');
INSERT INTO glpi_plugin_project_mailing VALUES ('2','task','1','1');

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