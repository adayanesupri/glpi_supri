ALTER TABLE `glpi_project` ADD `group` int(11) DEFAULT '0' NOT NULL;
ALTER TABLE `glpi_project` ADD `parentID` int(11) DEFAULT '0' NOT NULL;
ALTER TABLE `glpi_project` ADD `notes` LONGTEXT NOT NULL;
ALTER TABLE `glpi_project` ADD `advance` float DEFAULT '0' NOT NULL;
ALTER TABLE `glpi_project` ADD `status` enum('old','new','old_notdone') NOT NULL default 'new';
ALTER TABLE `glpi_project` RENAME `glpi_plugin_project`;

ALTER TABLE `glpi_project_tasks` ADD `title` varchar(250) NOT NULL default '';
ALTER TABLE `glpi_project_tasks` ADD `realtime` float DEFAULT '0' NOT NULL;
ALTER TABLE `glpi_project_tasks` ADD `assign_ent` int(11) NOT NULL default '0';
ALTER TABLE `glpi_project_tasks` ADD `assign_group` int(11) NOT NULL default '0';
ALTER TABLE `glpi_project_tasks` ADD `priority` tinyint(4) DEFAULT '1' NOT NULL;
ALTER TABLE `glpi_project_tasks` ADD `others` text;
ALTER TABLE `glpi_project_tasks` ADD `affect` text;
ALTER TABLE `glpi_project_tasks` DROP `date` ;
ALTER TABLE `glpi_project_tasks` CHANGE `type` `type_task` int(4) NOT NULL default '0';
ALTER TABLE `glpi_project_tasks` MODIFY `status` enum('old','new','plan','old_notdone') NOT NULL default 'new';
ALTER TABLE `glpi_project_tasks` MODIFY `sub` text;
ALTER TABLE `glpi_project_tasks` ADD `advance` float DEFAULT '0' NOT NULL;
ALTER TABLE `glpi_project_tasks` RENAME `glpi_plugin_project_tasks`;

ALTER TABLE `glpi_project_user` RENAME `glpi_plugin_project_users`;
ALTER TABLE `glpi_project_items` DROP INDEX `FK_contract`;
ALTER TABLE `glpi_project_items` DROP INDEX `FK_project_2`;
ALTER TABLE `glpi_project_items` DROP INDEX `FK_device`;
ALTER TABLE `glpi_project_items` ADD INDEX `FK_device` ( `FK_device` , `FK_project` );
ALTER TABLE `glpi_project_items` ADD INDEX `FK_project` ( `FK_project` );
ALTER TABLE `glpi_project_items` ADD INDEX `FK_device_2` ( `FK_device` );
ALTER TABLE `glpi_project_items` ADD INDEX `device_type` ( `device_type` );
ALTER TABLE `glpi_project_items` RENAME `glpi_plugin_project_items`;

ALTER TABLE `glpi_dropdown_project_tasks_type` RENAME `glpi_dropdown_plugin_project_tasks_type`;

CREATE TABLE `glpi_plugin_project_tasks_items` (
	`ID` int(11) NOT NULL auto_increment,
	`FK_task` int(11) NOT NULL default '0',
	`FK_device` int(11) NOT NULL default '0',
	`device_type` tinyint(4) NOT NULL default '0',
	`is_template` enum('0','1') NOT NULL default '0',
	PRIMARY KEY  (`ID`),
	KEY `FK_device` (`FK_device`,`FK_task`),
	KEY `FK_task` (`FK_task`),
	KEY `FK_device_2` (`FK_device`),
	KEY device_type (`device_type`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

CREATE TABLE `glpi_plugin_project_groups` (
	`ID` int(11) NOT NULL auto_increment,
	`FK_groups` int(11) NOT NULL default '0',
	`FK_project` int(11) NOT NULL default '0',
	PRIMARY KEY  (`ID`),
	UNIQUE KEY `FK_groups` (`FK_groups`,`FK_project`),
	KEY `FK_groups_2` (`FK_groups`),
	KEY `FK_project` (`FK_project`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

CREATE TABLE `glpi_plugin_project_enterprises` (
	`ID` int(11) NOT NULL auto_increment,
	`FK_enterprise` int(11) NOT NULL default '0',
	`FK_project` int(11) NOT NULL default '0',
	PRIMARY KEY  (`ID`),
	UNIQUE KEY `FK_enterprise` (`FK_enterprise`,`FK_project`),
	KEY `FK_enterprise_2` (`FK_enterprise`),
	KEY `FK_project` (`FK_project`)
	) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

CREATE TABLE `glpi_plugin_project_contracts` (
	`ID` int(11) NOT NULL auto_increment,
	`FK_contracts` int(11) NOT NULL default '0',
	`FK_project` int(11) NOT NULL default '0',
	PRIMARY KEY  (`ID`),
	UNIQUE KEY `FK_contracts` (`FK_contracts`,`FK_project`),
	KEY `FK_contracts_2` (`FK_contracts`),
	KEY `FK_project` (`FK_project`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

CREATE TABLE `glpi_plugin_project_documents` (
	`ID` int(11) NOT NULL auto_increment,
	`FK_documents` int(11) NOT NULL default '0',
	`FK_project` int(11) NOT NULL default '0',
	PRIMARY KEY  (`ID`),
	UNIQUE KEY `FK_documents` (`FK_documents`,`FK_project`),
	KEY `FK_documents_2` (`FK_documents`),
	KEY `FK_project` (`FK_project`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

CREATE TABLE `glpi_plugin_project_setup` (
	`ID` int(11) NOT NULL auto_increment,
	`active`  enum('0','1') NOT NULL default '0',
	`send_user`  enum('0','1') NOT NULL default '0',
	`send_group`  enum('0','1') NOT NULL default '0',
	`send_ent`  enum('0','1') NOT NULL default '0',
	`title_mailing` varchar(50) NOT NULL default '',
	`body_mailing` TEXT NULL,
	`format` enum('text','html') NOT NULL default 'text',
	PRIMARY KEY  (`ID`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

INSERT INTO `glpi_plugin_project_setup` ( `ID`, `active` , `send_user`, `send_group`, `send_ent`, `title_mailing`,`body_mailing`,`format`) VALUES ('1', '0','0','0','0', '', '','text');

CREATE TABLE `glpi_plugin_project_profiles` (
	`ID` int(11) NOT NULL auto_increment,
	`name` varchar(255) default NULL,
	`interface` varchar(50) NOT NULL default 'project',
	`is_default` enum('0','1') NOT NULL default '0',
	`project` char(1) default NULL,
	`create_project` char(1) default NULL,
	`update_project` char(1) default NULL,
	`delete_project` char(1) default NULL,
	`all_project` char(1) default NULL,
	`create_task` char(1) default NULL,
	`update_task` char(1) default NULL,
	`delete_task` char(1) default NULL,
	`all_task` char(1) default NULL,
	PRIMARY KEY  (`ID`),
	KEY `interface` (`interface`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

INSERT INTO `glpi_plugin_project_profiles` ( `ID`, `name` , `interface`, `is_default`, `project`,`create_project`,`update_project`,`delete_project`,`all_project`,`create_task`,`update_task`,`delete_task`,`all_task`) VALUES ('1', 'post-only','project','1',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL);

INSERT INTO `glpi_plugin_project_profiles` ( `ID`, `name` , `interface`, `is_default`, `project`,`create_project`,`update_project`,`delete_project`,`all_project`,`create_task`,`update_task`,`delete_task`,`all_task`) VALUES ('2', 'normal','project','0','r',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL);

INSERT INTO `glpi_plugin_project_profiles` ( `ID`, `name` , `interface`, `is_default`, `project`,`create_project`,`update_project`,`delete_project`,`all_project`,`create_task`,`update_task`,`delete_task`,`all_task`) VALUES ('3', 'admin','project','0','w','1','1','0','1','1','1','1','1');

INSERT INTO `glpi_plugin_project_profiles` ( `ID`, `name` , `interface`, `is_default`, `project`,`create_project`,`update_project`,`delete_project`,`all_project`,`create_task`,`update_task`,`delete_task`,`all_task`) VALUES ('4', 'super-admin','project','0','w','1','1','1','1','1','1','1','1');