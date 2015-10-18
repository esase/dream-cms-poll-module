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
('polls_administration_add_question', 'ACL - Adding poll questions in admin area', @moduleId),
('polls_administration_edit_question', 'ACL - Editing poll questions in admin area', @moduleId),
('polls_administration_browse_answers', 'ACL - Browsing poll answers in admin area', @moduleId),
('polls_administration_add_answer', 'ACL - Adding poll answers in admin area', @moduleId),
('polls_administration_delete_answers', 'ACL - Deleting poll answers in admin area', @moduleId),
('polls_administration_edit_answer', 'ACL - Editing poll answers in admin area', @moduleId);

INSERT INTO `acl_resource` (`resource`, `description`, `module`) VALUES
('polls_make_votes', 'ACL - Making votes in polls', @moduleId);
SET @makePollsVotesResourceId = (SELECT LAST_INSERT_ID());

INSERT INTO `acl_resource_connection` (`role`, `resource`) VALUES
(3, @makePollsVotesResourceId),
(2, @makePollsVotesResourceId);

-- application events

INSERT INTO `application_event` (`name`, `module`, `description`) VALUES
('poll_delete_question', @moduleId, 'Event - Deleting poll questions'),
('poll_add_question', @moduleId, 'Event - Adding poll questions'),
('poll_edit_question', @moduleId, 'Event - Editing poll questions'),
('poll_add_answer', @moduleId, 'Event - Adding poll answers'),
('poll_delete_answer', @moduleId, 'Event - Deleting poll answers'),
('poll_edit_answer', @moduleId, 'Event - Editing poll answers');

-- system pages and widgets

INSERT INTO `page_widget` (`name`, `module`, `type`, `description`, `duplicate`, `forced_visibility`, `depend_page_id`) VALUES
('pollWidget', @moduleId, 'public', 'Poll', 1, NULL, NULL);
SET @widgetId = (SELECT LAST_INSERT_ID());

INSERT INTO `page_widget_setting` (`name`, `widget`, `label`, `type`, `required`, `order`, `category`, `description`, `check`,  `check_message`, `values_provider`) VALUES
('poll_question', @widgetId, 'Question', 'select', 1, 1, 1, NULL, NULL, NULL, 'return Poll\\Service\\Poll::getAllQuestions();');

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
    `created` INT(10) UNSIGNED NOT NULL,
    `order` SMALLINT(5) NOT NULL DEFAULT '0',
    PRIMARY KEY (`id`),
    FOREIGN KEY (`question_id`) REFERENCES `poll_question`(`id`)
        ON UPDATE CASCADE
        ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE `poll_answer_track` (
    `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
    `question_id` INT(11) UNSIGNED NOT NULL,
    `answer_id` INT(11) UNSIGNED NOT NULL,
    `ip` VARBINARY(16) NOT NULL,
    `created` INT(10) UNSIGNED NOT NULL,
    PRIMARY KEY (`id`),
    UNIQUE `visitor` (`question_id`, `ip`),
    FOREIGN KEY (`question_id`) REFERENCES `poll_question`(`id`)
        ON UPDATE CASCADE
        ON DELETE CASCADE,
    FOREIGN KEY (`answer_id`) REFERENCES `poll_answer`(`id`)
        ON UPDATE CASCADE
        ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;