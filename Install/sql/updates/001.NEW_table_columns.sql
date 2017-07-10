/* Removed legacy columns (replaced by testlauf table */
ALTER TABLE `test_case` DROP `last_test_by`, DROP `last_test_time`;

/* Add planned efforts to test cases */
ALTER TABLE `test_case` ADD `effort_planned` DEC(5,2) NULL AFTER `feature_id`;

/* Add test description to test cases */
ALTER TABLE `testlauf` ADD `test_description` TEXT NULL AFTER `comment`;

  
