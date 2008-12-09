/*
  Mysql 5.0 Upgrade script for the table changes made in 2.x Arcturus branch
*/

/* Rework the dd objects table */
ALTER TABLE xar_dynamic_objects DROP column parentid;;
ALTER TABLE xar_dynamic_objects ADD COLUMN sources TEXT;