ALTER TABLE `feature` ADD `product_id` INT NOT NULL AFTER `name`, ADD INDEX `product_id` (`product_id`);
UPDATE feature f SET f.product_id = (SELECT m.product_id FROM module m WHERE f.module_id = m.id)
ALTER TABLE `module` DROP `product_id`;
DROP TABLE feature_customers;

CREATE TABLE `exf_testman`.`test_scenario_customers` ( `id` INT NOT NULL AUTO_INCREMENT , `customer_id` INT NOT NULL , `test_scenario_id` INT NOT NULL , `priority` INT NOT NULL , PRIMARY KEY (`id`), INDEX `customer_id` (`customer_id`), INDEX `test_scenario_id` (`test_scenario_id`)) ENGINE = InnoDB;
ALTER TABLE test_scenario_customers
ADD `created_on` datetime NOT NULL DEFAULT '1970-01-01 00:00:00',
ADD  `modified_on` datetime NOT NULL DEFAULT '1970-01-01 00:00:00',
ADD  `created_by_user_oid` binary(16) DEFAULT NULL,
ADD  `modified_by_user_oid` binary(16) DEFAULT NULL;

CREATE TABLE `exf_testman`.`test_type` ( `id` INT NOT NULL AUTO_INCREMENT , `name` VARCHAR(50) NOT NULL , PRIMARY KEY (`id`)) ENGINE = InnoDB;
ALTER TABLE test_type
ADD `created_on` datetime NOT NULL DEFAULT '1970-01-01 00:00:00',
ADD  `modified_on` datetime NOT NULL DEFAULT '1970-01-01 00:00:00',
ADD  `created_by_user_oid` binary(16) DEFAULT NULL,
ADD  `modified_by_user_oid` binary(16) DEFAULT NULL;

ALTER TABLE `test_scenario` ADD `test_type_id` INT NOT NULL AFTER `id`, ADD INDEX `test_type_id` (`test_type_id`);

ALTER TABLE `customer` CHANGE `short_name` `short_name` VARCHAR(10) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL;