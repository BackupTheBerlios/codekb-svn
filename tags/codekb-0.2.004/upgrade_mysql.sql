--
-- MySQL upgrade
--

-- Adding timestamps to files

ALTER TABLE `files` ADD COLUMN `created` timestamp AFTER `highlight`;

ALTER TABLE `files` ADD COLUMN `modified` timestamp AFTER `created`;

UPDATE `files` SET `created` = now();


-- Bug fix: datetime was updated on entry change! Damn MySQL...

ALTER TABLE `entries` CHANGE COLUMN `created` `created` timestamp DEFAULT 0;
