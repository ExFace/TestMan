ALTER TABLE `test_log` ADD `test_ok_flag` BOOLEAN NOT NULL DEFAULT FALSE AFTER `effort`;
ALTER TABLE `test_log` ADD `test_plan_id` INT(11) NULL AFTER `test_ok_flag`
ALTER TABLE `test_log` ADD `tested_version` VARCHAR(40) NULL AFTER `tested_installation`;
UPDATE test_log SET test_ok_flag = 1 WHERE ticket_id IS NULL;
ALTER TABLE `test_plan` ADD `closed_flag` BOOLEAN NOT NULL DEFAULT FALSE AFTER `release_no`;