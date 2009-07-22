/*
  Mysql 5.0 Upgrade script for the table changes made in 2.x Arcturus branch
DROP TABLE IF EXISTS xar_template_tags;

ALTER TABLE `xar_dynamic_objects` DROP `property_id`;

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

/* Update the properties of the core object definitions */
UPDATE `xar_dynamic_properties` SET `source` = REPLACE(source, "xar_dynamic_objects", "dynamic_objects");
UPDATE `xar_dynamic_properties` SET `source` = REPLACE(source, "xar_dynamic_properties", "dynamic_properties");
UPDATE `xar_dynamic_properties` SET `source` = REPLACE(source, "xar_dynamic_configurations", "dynamic_configurations");
UPDATE `xar_dynamic_properties` SET `source` = REPLACE(source, "xar_modules", "modules");
UPDATE `xar_dynamic_properties` SET `source` = REPLACE(source, "xar_roles", "roles");

/* Update the properties of the core object definitions */

/* Add some properties to the objects object */
INSERT INTO `xar_dynamic_properties` (`name`, `label`, `object_id`, `type`, `defaultvalue`, `source`, `status`, `seq`, `configuration`) VALUES
('sources', 'Sources', 1, 999, 'a:0:{}', 'dynamic_objects.sources', 34, 11, 'a:7:{s:15:"display_columns";s:2:"30";s:12:"display_rows";s:1:"1";s:17:"display_key_label";s:5:"Alias";s:19:"display_value_label";s:5:"Table";s:14:"display_layout";s:7:"default";s:19:"initialization_rows";s:1:"2";s:32:"initialization_associative_array";s:1:"1";}'),
('relations', 'Relations', 1, 999, 'a:0:{}', 'dynamic_objects.relations', 34, 11, 'a:7:{s:15:"display_columns";s:2:"30";s:12:"display_rows";s:1:"1";s:17:"display_key_label";s:9:"Link From";s:19:"display_value_label";s:7:"Link To";s:14:"display_layout";s:7:"default";s:19:"initialization_rows";s:1:"2";s:32:"initialization_associative_array";s:1:"1";}'),
('objects', 'Objects', 1, 999, 'a:0:{}', 'dynamic_objects.objects', 34, 11, 'a:7:{s:15:"display_columns";s:2:"30";s:12:"display_rows";s:1:"1";s:17:"display_key_label";s:9:"Link From";s:19:"display_value_label";s:7:"Link To";s:14:"display_layout";s:7:"default";s:19:"initialization_rows";s:1:"2";s:32:"initialization_associative_array";s:1:"1";}');

/* Change some properties to the objects object */
UPDATE `xar_dynamic_properties` SET `label` = 'Configuration', `type` = 999, `configuration` = 'a:7:{s:15:"display_columns";s:2:"30";s:12:"display_rows";s:1:"1";s:17:"display_key_label";s:3:"Key";s:19:"display_value_label";s:5:"Value";s:14:"display_layout";s:7:"default";s:19:"initialization_rows";s:1:"2";s:32:"initialization_associative_array";s:1:"1";}' WHERE `object_id` = 1 AND `name` = 'config';
UPDATE `xar_dynamic_properties` SET `status` = 67 WHERE `object_id` = 1 AND `name` = 'itemtype';
UPDATE `xar_dynamic_properties` SET `status` = 35 WHERE `object_id` = 1 AND `name` = 'urlparam';
UPDATE `xar_dynamic_properties` SET `status` = 35 WHERE `object_id` = 1 AND `name` = 'maxid';
UPDATE `xar_dynamic_properties` SET `status` = 35 WHERE `object_id` = 1 AND `name` = 'isalias';

/* Refresh the dynamic configurations table */
TRUNCATE TABLE `xar_dynamic_configurations`;
INSERT INTO `xar_dynamic_configurations` (`id`, `name`, `description`, `property_id`, `label`, `ignore_empty`, `configuration`) VALUES
(1, 'display_layout', 'The layout for this field', 2, 'Template Layout', 1, ''),
(2, 'validation_min_length', 'Minimun length for this field', 15, 'Minimum Length', 1, ''),
(3, 'validation_max_length', 'Maximum length for this field', 15, 'Maximum Length', 1, ''),
(4, 'initialization_other_rule', 'Any other rule for this field', 2, 'Other Rule', 1, 'a:0:{}'),
(5, 'validation_regex', 'A regex the contents of this field must validate against', 2, 'Regex Filter', 1, 'a:0:{}'),
(6, 'validation_password_confirm', 'Show a second password field to be filled in', 14, 'Confirm Password', 0, 'a:1:{s:14:"display_layout";s:7:"default";}'),
(7, 'display_size', 'Length of this input box', 15, 'Size', 1, ''),
(8, 'display_maxlength', 'The maximum length of input to this input box', 15, 'Max Lemgth', 1, ''),
(9, 'display_template', 'The template used to display this field', 2, 'Property Template', 1, ''),
(10, 'validation_min_value', 'This field must have a value greater than this', 15, 'Minimum Value', 1, 'a:4:{s:12:"display_size";s:2:"10";s:17:"display_maxlength";s:2:"30";s:14:"display_layout";s:7:"default";s:20:"validation_min_value";s:0:"";}'),
(11, 'validation_max_value', 'This field must have a value smaller than this', 15, 'Maximum Value', 1, ''),
(12, 'initialization_refobject', 'The object this field refers to', 507, 'Reference Object', 1, 'a:6:{s:14:"display_layout";s:7:"default";s:23:"initialization_function";s:0:"";s:19:"initialization_file";s:0:"";s:25:"initialization_collection";s:0:"";s:22:"initialization_options";s:0:"";s:25:"initialization_other_rule";s:0:"";}'),
(13, 'initialization_display_prop', 'The object property used in the dropdown display ', 6, 'Display Property', 1, 'a:6:{s:14:"display_layout";s:7:"default";s:23:"initialization_function";s:73:"xarModAPIFunc(''dynamicdata'',''user'',''getitemfields'',array(''name'' => #(1)))";s:19:"initialization_file";s:0:"";s:25:"initialization_collection";s:0:"";s:22:"initialization_options";s:0:"";s:25:"initialization_other_rule";s:0:"";}'),
(14, 'initialization_store_prop', 'The object property used for storing the value of this field', 6, 'Storage Prioperty', 1, 'a:6:{s:14:"display_layout";s:7:"default";s:23:"initialization_function";s:73:"xarModAPIFunc(''dynamicdata'',''user'',''getitemfields'',array(''name'' => #(1)))";s:19:"initialization_file";s:0:"";s:25:"initialization_collection";s:0:"";s:22:"initialization_options";s:0:"";s:25:"initialization_other_rule";s:0:"";}'),
(15, 'initialization_function', 'A function used to generate the options for this field', 2, 'Function', 1, ''),
(16, 'initialization_file', 'A file containing the options for this field', 2, 'File', 1, ''),
(17, 'initialization_options', 'A list of options options for this field', 3, 'Options', 1, ''),
(18, 'validation_override', 'Accept values not in the list of options for this field?', 14, 'Override', 0, 'a:1:{s:14:"display_layout";s:7:"default";}'),
(19, 'initialization_collection', 'A collection object representing the options for this field', 2, 'Collection', 1, 'a:3:{s:12:"display_size";s:2:"50";s:17:"display_maxlength";s:3:"254";s:14:"display_layout";s:7:"default";}'),
(20, 'initialization_display_status', 'The default display status for this field', 15, 'Display Status', 1, ''),
(21, 'initialization_input_status', 'The default input status for this field', 15, 'Input Status', 1, ''),
(22, 'display_linkrule', 'A rule to decide whether to show a link to this field', 6, 'Display Link', 1, 'a:6:{s:14:"display_layout";s:7:"default";s:23:"initialization_function";s:0:"";s:19:"initialization_file";s:0:"";s:25:"initialization_collection";s:0:"";s:22:"initialization_options";s:66:"0,No rule;1,User must not already exist;2,User must already exist;";s:25:"initialization_other_rule";s:0:"";}'),
(23, 'validation_existrule', 'A rule to use for checking the existence of this field', 6, 'Existence Rule', 1, 'a:6:{s:14:"display_layout";s:7:"default";s:23:"initialization_function";s:0:"";s:19:"initialization_file";s:0:"";s:25:"initialization_collection";s:0:"";s:22:"initialization_options";s:66:"0,No rule;1,User must not already exist;2,User must already exist;";s:25:"initialization_other_rule";s:0:"";}'),
(24, 'initialization_basedirectory', 'The directory this field gets input from', 2, 'Base Directory', 1, ''),
(25, 'initialization_image_source', 'The source type where this field gets its input', 34, 'Image Source', 1, 'a:2:{s:14:"display_layout";s:7:"default";s:22:"initialization_options";s:48:"local,local location;url,url;upload,file upload;";}'),
(26, 'validation_file_extensions', 'The file extensions allowed for this field', 2, 'File Extensions', 1, 'a:7:{s:12:"display_size";s:2:"50";s:17:"display_maxlength";s:3:"254";s:14:"display_layout";s:7:"default";s:21:"validation_min_length";s:0:"";s:21:"validation_max_length";s:0:"";s:16:"validation_regex";s:0:"";s:25:"initialization_other_rule";s:0:"";}'),
(27, 'initialization_group_list', 'A list of groups to chow in this field', 2, 'Group List', 1, 'a:0:{}'),
(28, 'initialization_parentgroup_list', 'A list of parent groups whose children are shown in this field', 2, 'Parent Groups', 1, 'a:0:{}'),
(29, 'initialization_ancestorgroup_list', 'A list of ancestor groups whose children are shown in this field', 2, 'Ancestor Groups', 1, 'a:0:{}'),
(30, 'initialization_icon_url', 'Icon URL', 2, 'Icon URL', 1, 'a:7:{s:12:"display_size";s:2:"50";s:17:"display_maxlength";s:3:"254";s:14:"display_layout";s:7:"default";s:21:"validation_min_length";s:0:"";s:21:"validation_max_length";s:0:"";s:16:"validation_regex";s:0:"";s:25:"initialization_other_rule";s:0:"";}'),
(31, 'display_rows', 'The number of rows this field displays with', 15, 'Rows', 1, 'a:9:{s:12:"display_size";s:2:"10";s:17:"display_maxlength";s:2:"30";s:14:"display_layout";s:7:"default";s:20:"validation_min_value";s:0:"";s:20:"validation_max_value";s:0:"";s:21:"validation_min_length";s:0:"";s:21:"validation_max_length";s:0:"";s:16:"validation_regex";s:0:"";s:25:"initialization_other_rule";s:0:"";}'),
(32, 'display_columns', 'The number of columns this field displays with', 15, 'Columns', 1, 'a:9:{s:12:"display_size";s:2:"10";s:17:"display_maxlength";s:2:"30";s:14:"display_layout";s:7:"default";s:20:"validation_min_value";s:0:"";s:20:"validation_max_value";s:0:"";s:21:"validation_min_length";s:0:"";s:21:"validation_max_length";s:0:"";s:16:"validation_regex";s:0:"";s:25:"initialization_other_rule";s:0:"";}'),
(33, 'validation_single', 'This field can have only a single value', 14, 'Single Only', 0, 'a:1:{s:14:"display_layout";s:7:"default";}'),
(34, 'initialization_module', 'The module tthis field gets its value from', 19, 'Module', 1, 'a:0:{}'),
(35, 'initialization_itemtype', 'The itemtype tthis field gets its value from', 15, 'Itemtype', 1, 'a:0:{}'),
(36, 'display_combo_mode', 'This field displays as a dropdown or a textbox or both', 34, 'Combo Mode', 1, 'a:2:{s:14:"display_layout";s:7:"default";s:22:"initialization_options";s:29:"1,Dropdown;2,Text Box;3,Both;";}'),
(37, 'initialization_linktype', 'How items and subitems are linked', 6, 'Link Type', 1, 'a:8:{s:14:"display_layout";s:7:"default";s:15:"display_tooltip";s:0:"";s:24:"initialization_firstline";s:0:"";s:23:"initialization_function";s:0:"";s:19:"initialization_file";s:0:"";s:25:"initialization_collection";s:0:"";s:22:"initialization_options";s:125:"serialized,Local value;itemid,Link to subitem;childlist,List of children (child ids);parentid,Parentid reference in subitems;";s:25:"initialization_other_rule";s:0:"";}'),
(38, 'initialization_parentselect', 'Make the parent id configurable or not', 34, 'Parent ID', 1, 'a:8:{s:14:"display_layout";s:7:"default";s:15:"display_tooltip";s:0:"";s:24:"initialization_firstline";s:0:"";s:23:"initialization_function";s:0:"";s:19:"initialization_file";s:0:"";s:25:"initialization_collection";s:0:"";s:22:"initialization_options";s:36:"1,select;0,fixed (use itemid field);";s:25:"initialization_other_rule";s:0:"";}'),
(39, 'initialization_fieldlist', 'Select the fields to display in the interface', 39, 'Field List', 1, 'a:6:{s:14:"display_layout";s:7:"default";s:23:"initialization_function";s:73:"xarModAPIFunc(''dynamicdata'',''user'',''getitemfields'',array(''name'' => #(1)))";s:19:"initialization_file";s:0:"";s:25:"initialization_collection";s:0:"";s:22:"initialization_options";s:0:"";s:25:"initialization_other_rule";s:0:"";}'),
(40, 'initialization_minimumitems', 'The minimum number of subitems per parent item', 15, 'Subitems displayed', 1, 'a:0:{}'),
(41, 'initialization_subiteminput', 'Allow editing of subitems from within the parent', 6, 'Subitem editing', 1, 'a:6:{s:14:"display_layout";s:7:"default";s:23:"initialization_function";s:0:"";s:19:"initialization_file";s:0:"";s:25:"initialization_collection";s:0:"";s:22:"initialization_options";s:78:"0,No editing, display only;1,Only edit existing items;2,Add/Edit/Remove items;";s:25:"initialization_other_rule";s:0:"";}'),
(42, 'initialization_defaultitems', 'Enter a function that defines an array of default items', 2, 'Default Items', 1, 'a:0:{}'),
(43, 'validation_allowempty', 'Whether this field accepts no input as valid', 14, 'Allow empty', 1, 'a:4:{s:14:"display_layout";s:7:"default";s:15:"display_tooltip";s:2:"  ";s:21:"validation_allowempty";s:1:"1";s:29:"validation_allowempty_invalid";s:2:"  ";}'),
(44, 'display_required', 'Whether this field is tagged as "required" or not', 14, 'Required', 1, 'a:0:{}'),
(45, 'initialization_linkfield', 'A link to another table', 6, 'Link Field', 1, 'a:6:{s:14:"display_layout";s:7:"default";s:23:"initialization_function";s:73:"xarModAPIFunc(''dynamicdata'',''user'',''getitemfields'',array(''name'' => #(1)))";s:19:"initialization_file";s:0:"";s:25:"initialization_collection";s:0:"";s:22:"initialization_options";s:0:"";s:25:"initialization_other_rule";s:0:"";}'),
(46, 'initialization_store_type', 'Is this field stored as text or ID', 6, 'Storage Type', 1, 'a:6:{s:14:"display_layout";s:7:"default";s:23:"initialization_function";s:0:"";s:19:"initialization_file";s:0:"";s:25:"initialization_collection";s:0:"";s:22:"initialization_options";s:16:"id,ID;name,Name;";s:25:"initialization_other_rule";s:0:"";}'),
(47, 'validation_max_file_size', 'The maximum acceptable file size in bytes', 15, 'Maximum file size', 1, 'a:2:{s:14:"display_layout";s:7:"default";s:25:"initialization_other_rule";s:0:"";}'),
(48, 'initialization_file_input_methods', 'What methods this input uses to accept files', 1115, 'File Input Methods', 1, 'a:5:{s:14:"display_layout";s:7:"default";s:15:"display_tooltip";s:2:"  ";s:27:"validation_override_invalid";s:2:"  ";s:29:"validation_allowempty_invalid";s:2:"  ";s:22:"initialization_options";s:39:"5,Trusted;2,External;1,Upload;7,Stored;";}'),
(49, 'initialization_import_directory', 'The directory used for uploading files', 2, 'Trusted Directory', 1, 'a:2:{s:14:"display_layout";s:7:"default";s:25:"initialization_other_rule";s:0:"";}'),
(50, 'initialization_multiple_files', 'Whether the field accepts multiple file inputs', 14, 'Multiple Files', 1, 'a:2:{s:14:"display_layout";s:7:"default";s:25:"initialization_other_rule";s:0:"";}'),
(51, 'initialization_directory_name', 'A name to be assigned to a directory', 2, 'Directory Name', 1, 'a:7:{s:12:"display_size";s:2:"50";s:17:"display_maxlength";s:3:"254";s:14:"display_layout";s:7:"default";s:21:"validation_min_length";s:0:"";s:21:"validation_max_length";s:0:"";s:16:"validation_regex";s:0:"";s:25:"initialization_other_rule";s:0:"";}'),
(52, 'display_tooltip', 'The tooltip text for this field', 3, 'Tooltip', 1, ''),
(53, 'validation_matches', 'Whether this field contains a certain text string', 2, 'Text Filter', 1, 'a:8:{s:12:"display_size";s:2:"50";s:17:"display_maxlength";s:3:"254";s:14:"display_layout";s:7:"default";s:15:"display_tooltip";s:0:"";s:21:"validation_min_length";s:0:"";s:21:"validation_max_length";s:0:"";s:16:"validation_regex";s:0:"";s:25:"initialization_other_rule";s:0:"";}'),
(54, 'initialization_firstline', 'Whether this field has a line inserted at the beginning', 2, 'First Line', 1, 'a:3:{s:14:"display_layout";s:7:"default";s:15:"display_tooltip";s:0:"";s:25:"initialization_other_rule";s:0:"";}'),
(55, 'validation_equals', 'Whether this field equals some value', 2, 'Equals', 1, 'a:3:{s:14:"display_layout";s:7:"default";s:15:"display_tooltip";s:0:"";s:25:"initialization_other_rule";s:0:"";}'),
(56, 'validation_notequals', 'Whether this field does not equal some value', 2, 'Does Not Equal', 1, 'a:3:{s:14:"display_layout";s:7:"default";s:15:"display_tooltip";s:0:"";s:25:"initialization_other_rule";s:0:"";}'),
(57, 'initialization_transform', 'Whether this property transfomrs its value', 14, 'Run Transform', 1, 'a:1:{s:14:"display_layout";s:7:"default";}'),
(58, 'validation_allow_duplicates', 'How this property deals with duplicates', 6, 'Allow Duplicates', 1, 'a:2:{s:14:"display_layout";s:7:"default";s:22:"initialization_options";s:63:"0, Not allowed;1,Create a new instance;2, Use the old instance;";}'),
(59, 'validation_categories', 'The allowed categories for this property', 30050, 'Allowed Categories', 1, 'a:1:{s:14:"display_layout";s:7:"default";}'),
(60, 'display_show_route', 'Show the Route or not?', 14, 'Show Route?', 1, 'a:1:{s:14:"display_layout";s:7:"default";}'),
(61, 'initialization_rows', 'Allow adding of rows', 6, 'Add/Delete Rows', 1, 'a:2:{s:14:"display_layout";s:7:"default";s:22:"initialization_options";s:70:"0,No Addition/Deletion,display only;1,Only Add Rows;2,Add/Delete Rows;";}'),
(62, 'display_key_label', 'Changing key label of row', 2, 'Key Label', 1, 'a:1:{s:14:"display_layout";s:7:"default";}'),
(63, 'display_value_label', 'Changing value label of row', 2, 'Value Label', 1, 'a:1:{s:14:"display_layout";s:7:"default";}'),
(64, 'initialization_associative_array', 'Allow saving as associative array', 14, 'Associative Array Storage', 1, 'a:1:{s:14:"display_layout";s:7:"default";}'),
(65, 'initialization_start_year', 'Minimum year to start with', 15, 'Start Year', 0, 'a:5:{s:12:"display_size";s:2:"10";s:17:"display_maxlength";s:2:"30";s:14:"display_layout";s:7:"default";s:15:"display_tooltip";s:27:"Minimum value for the year.";s:21:"validation_min_length";s:1:"4";}'),
(66, 'initialization_end_year', 'Maximum year to end with', 15, 'End Year', 0, 'a:5:{s:12:"display_size";s:2:"10";s:17:"display_maxlength";s:2:"30";s:14:"display_layout";s:7:"default";s:15:"display_tooltip";s:25:"Maximum value of the year";s:21:"validation_min_length";s:1:"4";}'),
(67, 'validation_file_type', 'Whether this field displays files or directories', 6, 'Show type file or directory', 1, 'a:2:{s:14:"display_layout";s:7:"default";s:22:"initialization_options";s:30:"file,file;directory,directory;";}');
