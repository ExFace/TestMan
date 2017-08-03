CREATE TABLE `test_scenario` ( `id` int(11) NOT NULL AUTO_INCREMENT, `name` varchar(128) NOT NULL , `created_on` datetime NOT NULL DEFAULT '1970-01-01 00:00:00' , `modified_on` datetime NOT NULL DEFAULT '1970-01-01 00:00:00' , `created_by_user_oid` binary(16) DEFAULT NULL , `modified_by_user_oid` binary(16) DEFAULT NULL, PRIMARY KEY (`id`) ) ENGINE=InnoDB;
ALTER TABLE `test_scenario` ADD `description` TEXT NULL AFTER `name`;
CREATE TABLE `exf_testman`.`test_scenario_cases` ( `id` INT(11) NOT NULL AUTO_INCREMENT , `test_scenario_id` INT(11) NOT NULL , `test_case_id` INT(11) NOT NULL , `created_on` datetime NOT NULL DEFAULT '1970-01-01 00:00:00' , `modified_on` datetime NOT NULL DEFAULT '1970-01-01 00:00:00' , `created_by_user_oid` binary(16) DEFAULT NULL , `modified_by_user_oid` binary(16) DEFAULT NULL, PRIMARY KEY (`id`)) ENGINE = InnoDB;

CREATE TABLE `exf_testman`.`test_plan` ( `id` INT NOT NULL AUTO_INCREMENT , `name` VARCHAR(128) NOT NULL , `release_no` VARCHAR(10) NULL , `created_on` datetime NOT NULL DEFAULT '1970-01-01 00:00:00' , `modified_on` datetime NOT NULL DEFAULT '1970-01-01 00:00:00' , `created_by_user_oid` binary(16) DEFAULT NULL , `modified_by_user_oid` binary(16) DEFAULT NULL, PRIMARY KEY (`id`) ) ENGINE = InnoDB;
ALTER TABLE `test_plan` ADD `description` TEXT NULL AFTER `name`;
CREATE TABLE `exf_testman`.`test_plan_cases` ( `id` INT(11) NOT NULL AUTO_INCREMENT , `test_plan_id` INT(11) NOT NULL , `test_case_id` INT(11) NOT NULL , `created_on` datetime NOT NULL DEFAULT '1970-01-01 00:00:00' , `modified_on` datetime NOT NULL DEFAULT '1970-01-01 00:00:00' , `created_by_user_oid` binary(16) DEFAULT NULL , `modified_by_user_oid` binary(16) DEFAULT NULL, PRIMARY KEY (`id`)) ENGINE = InnoDB;
ALTER TABLE `test_plan_cases` ADD `priority` INT(3) NOT NULL AFTER `test_case_id`;

  
