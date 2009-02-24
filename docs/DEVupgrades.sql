/*
  Mysql 5.0 Upgrade script for the table changes made in 2.x Arcturus branch
*/

/* Rework the dd objects table */
ALTER TABLE xar_dynamic_objects DROP column parent_id;;

/* Add new fields*/
ALTER TABLE xar_dynamic_objects ADD COLUMN sources TEXT;
UPDATE `xar_dynamic_objects` set `sources` = 'a:0:{}';
ALTER TABLE xar_dynamic_objects ADD COLUMN relations TEXT;
UPDATE `xar_dynamic_objects` set `relations` = 'a:0:{}';
ALTER TABLE xar_dynamic_objects ADD COLUMN objects TEXT;
UPDATE `xar_dynamic_objects` set `objects` = 'a:0:{}';

/* Update the core object definitions */
UPDATE `xar_dynamic_objects` set `sources` = 'a:1:{s:15:"dynamic_objects";s:19:"xar_dynamic_objects";}' WHERE `name` = 'objects';
UPDATE `xar_dynamic_objects` set `sources` = 'a:1:{s:18:"dynamic_properties";s:22:"xar_dynamic_properties";}' WHERE `name` = 'properties';
UPDATE `xar_dynamic_objects` set `sources` = 'a:1:{s:22:"dynamic_configurations";s:26:"xar_dynamic_configurations";}' WHERE `name` = 'configurations';
UPDATE `xar_dynamic_objects` set `sources` = 'a:1:{s:7:"modules";s:11:"xar_modules";}' WHERE `name` = 'modules';
UPDATE `xar_dynamic_objects` set `config` = 'a:1:{s:5:"where";s:13:"role_type = 2";}' WHERE `name` = 'roles_users';
UPDATE `xar_dynamic_objects` set `sources` = 'a:1:{s:5:"roles";s:9:"xar_roles";}' WHERE `name` = 'roles_roles';
UPDATE `xar_dynamic_objects` set `config` = 'a:1:{s:5:"where";s:13:"role_type = 3";}' WHERE `name` = 'roles_groups';
UPDATE `xar_dynamic_objects` set `sources` = 'a:1:{s:5:"roles";s:9:"xar_roles";}' WHERE `name` = 'roles_groups';
UPDATE `xar_dynamic_objects` set `sources` = 'a:1:{s:10:"privileges";s:14:"xar_privileges";}' WHERE `name` = 'privileges_baseprivileges';
UPDATE `xar_dynamic_objects` set `sources` = 'a:1:{s:10:"privileges";s:14:"xar_privileges";}' WHERE `name` = 'privileges_privileges';

/* Update the properties of the core objectdefinitions */
UPDATE `xar_dynamic_properties` SET `source` = REPLACE(source, "xar_dynamic_objects", "dynamic_objects");
UPDATE `xar_dynamic_properties` SET `source` = REPLACE(source, "xar_dynamic_properties", "dynamic_properties");
UPDATE `xar_dynamic_properties` SET `source` = REPLACE(source, "xar_dynamic_configurations", "dynamic_configurations");
UPDATE `xar_dynamic_properties` SET `source` = REPLACE(source, "xar_modules", "modules");
UPDATE `xar_dynamic_properties` SET `source` = REPLACE(source, "xar_roles", "roles");
