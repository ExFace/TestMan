ALTER TABLE `test_log` CHANGE `ticket_id` `tracker_ticket_id` VARCHAR(10) NULL DEFAULT NULL;
UPDATE test_log tl SET tl.tracker_ticket_id = (SELECT t.ticket_id FROM ticket t WHERE t.id = tl.tracker_ticket_id);