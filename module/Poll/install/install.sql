SET sql_mode='STRICT_TRANS_TABLES,NO_ZERO_DATE,NO_ZERO_IN_DATE';

SET @moduleId = __module_id__;

-- application admin menu

SET @maxOrder = (SELECT `order` + 1 FROM `application_admin_menu` ORDER BY `order` DESC LIMIT 1);

INSERT INTO `application_admin_menu_category` (`name`, `module`, `icon`) VALUES
('Poll', @moduleId, 'poll_menu_item.png');

SET @menuCategoryId = (SELECT LAST_INSERT_ID());
SET @menuPartId = (SELECT `id` FROM `application_admin_menu_part` WHERE `name` = 'Modules');

INSERT INTO `application_admin_menu` (`name`, `controller`, `action`, `module`, `order`, `category`, `part`) VALUES
('List of questions', 'polls-administration', 'list-questions', @moduleId, @maxOrder + 1, @menuCategoryId, @menuPartId);

-- acl resources

INSERT INTO `acl_resource` (`resource`, `description`, `module`) VALUES
('polls_administration_list_questions', 'ACL - Viewing poll questions in admin area', @moduleId),
('polls_administration_delete_questions', 'ACL - Deleting poll questions in admin area', @moduleId),
('polls_administration_add_question', 'ACL - Adding poll questions in admin area', @moduleId);

-- application events

INSERT INTO `application_event` (`name`, `module`, `description`) VALUES
('poll_delete_question', @moduleId, 'Event - Deleting poll questions'),
('poll_add_question', @moduleId, 'Event - Adding poll questions');

-- module tables

CREATE TABLE IF NOT EXISTS `poll_question` (
    `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
    `question` VARCHAR(255) NOT NULL,
    `created` INT(10) UNSIGNED NOT NULL,
    `language` CHAR(2) DEFAULT NULL,
    PRIMARY KEY (`id`),
    UNIQUE `question` (`question`, `language`),
    FOREIGN KEY (`language`) REFERENCES `localization_list`(`language`)
        ON UPDATE CASCADE
        ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `poll_answer` (
    `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
    `answer` VARCHAR(255) NOT NULL,
    `question_id` INT(11) UNSIGNED NOT NULL,
    `order` SMALLINT(5) NOT NULL DEFAULT '0',
    PRIMARY KEY (`id`),
    FOREIGN KEY (`question_id`) REFERENCES `poll_question`(`id`)
        ON UPDATE CASCADE
        ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;