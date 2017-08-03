RENAME TABLE `exf_testman`.`produkt` TO `exf_testman`.`product`;
ALTER TABLE `product` ENGINE = INNODB;

RENAME TABLE `exf_testman`.`kunde` TO `exf_testman`.`customer`;
ALTER TABLE `customer` CHANGE `kuerzel` `short_name` VARCHAR(6) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL;

ALTER TABLE `feature_kunde_mapping` CHANGE `kunde_id` `customer_id` INT(11) NOT NULL;
RENAME TABLE `exf_testman`.`feature_kunde_mapping` TO `exf_testman`.`feature_customers`;
ALTER TABLE `feature_customers` CHANGE `prio` `priority` INT(1) NOT NULL;

ALTER TABLE `feature` CHANGE `konfiguration` `configuration` TEXT CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL;

ALTER TABLE `module` CHANGE `hinweise` `hints` TEXT CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL;
ALTER TABLE `module` CHANGE `bemerkungen` `notes` TEXT CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL;
ALTER TABLE `module` CHANGE `testprotokoll` `test_protocol` VARCHAR(256) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL;
ALTER TABLE `module` CHANGE `produkt_id` `product_id` INT(11) NOT NULL;

ALTER TABLE `partner` CHANGE `uid` `id` INT(11) NOT NULL;

ALTER TABLE `test_case` CHANGE `effort_planned` `effort_planned` DECIMAL(6,2) NULL DEFAULT NULL;

RENAME TABLE `exf_testman`.`testlauf` TO `exf_testman`.`test_log`;
ALTER TABLE `test_log` CHANGE `aufwand` `effort` FLOAT NULL DEFAULT NULL;
ALTER TABLE `test_log` CHANGE `effort` `effort` DECIMAL(6,2) NULL DEFAULT NULL;

DROP table module_table;

  
