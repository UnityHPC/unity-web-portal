SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET AUTOCOMMIT = 0;
START TRANSACTION;
SET time_zone = "+00:00";

-- magic numbers explained:
-- SET @eppn_max_length = 128; -- https://www.educause.edu/fidm/attributes
-- SET @group_max_length = 131; -- "pi_" is 3 chars
-- SET @big_buffer_length = 768; -- varchar(769) is too big to be a primary key

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

CREATE TABLE `account_deletion_requests` (
  `id` int(11) NOT NULL,
  `timestamp` timestamp NOT NULL DEFAULT current_timestamp(),
  `uid` varchar(128) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `audit_log` (
  `id` int(11) NOT NULL,
  `timestamp` timestamp NOT NULL DEFAULT current_timestamp(),
  `operator` varchar(128) NOT NULL,
  `operator_ip` varchar(15) NOT NULL,
  `action_type` varchar(768) NOT NULL,
  `recipient` varchar(128) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE user_last_logins (
    `operator` varchar(128) NOT NULL,
    `last_login` timestamp  NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- CREATE TABLE `groupJoinRequests` (
--   `id` int(11) NOT NULL,
--   `group_name` varchar(768) NOT NULL,
--   `requestor` varchar(768) NOT NULL,
--   `requested_on` timestamp NOT NULL DEFAULT current_timestamp()
-- ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- CREATE TABLE `groupRequests` (
--   `id` int(11) NOT NULL,
--   `group_type` varchar(768) NOT NULL,
--   `group_name` varchar(768) NOT NULL,
--   `requestor` varchar(128) NOT NULL,
--   `requested_on` timestamp NOT NULL DEFAULT current_timestamp(),
--   `start_date` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
--   `end_date` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00'
-- ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- CREATE TABLE `groupRoleAssignments` (
--   `id` int(11) NOT NULL,
--   `user` varchar(128) NOT NULL,
--   `role` varchar(768) NOT NULL,
--   `group` varchar(768) NOT NULL
-- ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- CREATE TABLE `groupRoles` (
--   `id` int(11) NOT NULL,
--   `name` varchar(768) NOT NULL,
--   `slug` varchar(768) NOT NULL,
--   `priority` int(11) NOT NULL,
--   `color` varchar(768) NOT NULL,
--   `perms` varchar(768) NOT NULL
-- ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- CREATE TABLE `groupTypes` (
--   `id` int(11) NOT NULL,
--   `name` varchar(768) NOT NULL,
--   `slug` varchar(768) NOT NULL,
--   `color` varchar(768) NOT NULL,
--   `time_limited` tinyint(1) NOT NULL,
--   `def_role` varchar(768) NOT NULL,
--   `av_roles` varchar(768) NOT NULL,
--   `can_request` tinyint(1) NOT NULL
-- ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `notices` (
  `id` int(11) NOT NULL,
  `date` timestamp NOT NULL DEFAULT current_timestamp(),
  `title` varchar(300) NOT NULL,
  `message` longtext NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `notices` (`id`, `date`, `title`, `message`) VALUES
(9, '2022-09-19 15:49:10', 'Example Notice 1', '<p>Lorem ipsum dolor sit amet, consectetur adipiscing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua. Ut enim ad minim veniam, quis nostrud exercitation ullamco laboris nisi ut aliquip ex ea commodo consequat. Duis aute irure dolor in reprehenderit in voluptate velit esse cillum dolore eu fugiat nulla pariatur. Excepteur sint occaecat cupidatat non proident, sunt in culpa qui officia deserunt mollit anim id est laborum.</p>'),
(10, '2022-09-14 11:48:39', 'Example Notice 2', '<p>Lorem ipsum dolor sit amet, consectetur adipiscing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua. Ut enim ad minim veniam, quis nostrud exercitation ullamco laboris nisi ut aliquip ex ea commodo consequat. Duis aute irure dolor in reprehenderit in voluptate velit esse cillum dolore eu fugiat nulla pariatur. Excepteur sint occaecat cupidatat non proident, sunt in culpa qui officia deserunt mollit anim id est laborum.</p>');

CREATE TABLE `pages` (
  `id` int(11) NOT NULL,
  `page` varchar(300) NOT NULL,
  `content` longtext NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `pages` (`id`, `page`, `content`) VALUES
(1, 'home', '<h3>Docmentation and FAQ</h3>\r\n<p>You can find our documentation <a href=\"https://esdconfluence.it.umass.edu/confluence/display/UNITY/Unity+Cluster+Documentation+Home\" target=\"_blank\">here</a>. We also have an <a target=\"_blank\" href=\"https://esdconfluence.it.umass.edu/confluence/display/UNITY/Frequently+Asked+Questions\">FAQ</a> page which could help answer quick questions.\r\n\r\n<h3>Office Hours</h3>\r\n<p>We offer office hours every week on <strong>Tuesdays 2-4 PM</strong> in-person at <strong>W.E.B. DuBois Library 786</strong> or remote on <strong><a target=\"_blank\" href=\"https://umass-amherst.zoom.us/j/95663998309\">Zoom</a></strong>. Be sure to check the <a href=\"<?php echo $CONFIG[\"site\"][\"prefix\"]; ?>/index.php\">cluster notes</a> page for up-to-date information on any canceled/delayed office hours.</p>\r\n\r\n<h3>Support Email</h3>\r\n<p>You can create a support ticket by emailing <a target=\"_blank\" href=\"mailto:hpc@umass.edu\">hpc@umass.edu</a>. We will do our best to reply as fast as possible!</p>'),
(2, 'support', '<h3>Docmentation and FAQ</h3>\r\n<p>You can find our documentation <a href=\"https://esdconfluence.it.umass.edu/confluence/display/UNITY/Unity+Cluster+Documentation+Home\" target=\"_blank\">here</a>. We also have an <a target=\"_blank\" href=\"https://esdconfluence.it.umass.edu/confluence/display/UNITY/Frequently+Asked+Questions\">FAQ</a> page which could help answer quick questions.\r\n\r\n<h3>Office Hours</h3>\r\n<p>We offer office hours every week on <strong>Tuesdays 2-4 PM</strong> in-person at <strong>W.E.B. DuBois Library 786</strong> or remote on <strong><a target=\"_blank\" href=\"https://umass-amherst.zoom.us/j/95663998309\">Zoom</a></strong>. Be sure to check the <a href=\"<?php echo $CONFIG[\"site\"][\"prefix\"]; ?>/index.php\">cluster notes</a> page for up-to-date information on any canceled/delayed office hours.</p>\r\n\r\n<h3>Support Email</h3>\r\n<p>You can create a support ticket by emailing <a target=\"_blank\" href=\"mailto:hpc@umass.edu\">hpc@umass.edu</a>. We will do our best to reply as fast as possible!</p>'),
(3, 'policy', '<p>By using resources associated with Unity, you agree to comply with the following conditions of use.  This is an extension of the University of Massachussetts Amherst Information Technology Acceptable Use Policy, which can be found <a target=\"_blank\" href=\"https://www.umass.edu/it/security/acceptable-use-policy\">here</a>.</p>\r\n\r\n<ol>\r\n    <li>You will not use Unity resources for illicit financial gain, such as virtual currency mining, or any unlawful purpose, nor attempt to breach or circumvent any Unity administrative or security controls. You will comply with all applicable laws, working with your home institution and the specific Unity service providers utilized to determine what constraints may be placed on you by any relevant regulations such as export control law or HIPAA.</li>\r\n    <li>You will respect intellectual property rights and observe confidentiality agreements.</li>\r\n    <li>You will protect the access credentials (e.g., passwords, private keys, and/or tokens) issued to you or generated to access Unity resources; these are issued to you for your sole use.</li>\r\n    <li>You will immediately report any known or suspected security breach or loss or misuse of Unity access credentials to <a href=\"mailto:hpc@it.umass.edu\">hpc@it.umass.edu</a>.</li>\r\n    <li>You will have only one Unity User account and will keep your profile information up-to-date.</li>\r\n    <li>Use of resources and services through Unity is at your own risk. There are no guarantees that resources and services will be available, that they will suit every purpose, or that data will never be lost or corrupted. Users are responsible for backing up critical data.</li>\r\n    <li>Logged information, including information provided by you for registration purposes, is used for administrative, operational, accounting, monitoring and security purposes. This information may be disclosed, via secured mechanisms, only for the same purposes and only as far as necessary to other organizations cooperating with Unity .</li>\r\n</ol>\r\n\r\n<p>The Unity team reserves the right to restrict access to any individual/group found to be in breach of the above.</p>');

CREATE TABLE `requests` (
  `id` int(11) NOT NULL,
  `request_for` varchar(131) NOT NULL,
  `uid` varchar(128) NOT NULL,
  `timestamp` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `sitevars` (
  `id` int(11) NOT NULL,
  `name` varchar(768) NOT NULL,
  `value` varchar(768) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `sitevars` (`id`, `name`, `value`) VALUES
(0, 'MAX_UID', '33129'),
(1, 'MAX_GID', '33129'),
(2, 'MAX_PIGID', '10284');

ALTER TABLE `account_deletion_requests`
  ADD PRIMARY KEY (`id`);

ALTER TABLE `audit_log`
  ADD PRIMARY KEY (`id`);

ALTER TABLE `user_last_logins`
  ADD PRIMARY KEY (`operator`);

-- ALTER TABLE `groupJoinRequests`
--   ADD PRIMARY KEY (`id`);

-- ALTER TABLE `groupRequests`
--   ADD PRIMARY KEY (`id`);

-- ALTER TABLE `groupRoleAssignments`
--   ADD PRIMARY KEY (`id`);

-- ALTER TABLE `groupRoles`
--   ADD PRIMARY KEY (`id`);

-- ALTER TABLE `groupTypes`
--   ADD PRIMARY KEY (`id`);

ALTER TABLE `notices`
  ADD PRIMARY KEY (`id`);

ALTER TABLE `pages`
  ADD PRIMARY KEY (`id`);

ALTER TABLE `requests`
  ADD PRIMARY KEY (`id`);

ALTER TABLE `sitevars`
  ADD PRIMARY KEY (`id`);

ALTER TABLE `account_deletion_requests`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

ALTER TABLE `audit_log`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

-- ALTER TABLE `groupJoinRequests`
--   MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

-- ALTER TABLE `groupRequests`
--   MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

-- ALTER TABLE `groupRoleAssignments`
--   MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

-- ALTER TABLE `groupRoles`
--   MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

-- ALTER TABLE `groupTypes`
--   MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

ALTER TABLE `notices`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

ALTER TABLE `pages`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

ALTER TABLE `requests`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1031;

ALTER TABLE `sitevars`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

-- automatically update `user_last_logins` from `audit_log`
DELIMITER //
CREATE TRIGGER update_last_login
AFTER INSERT ON audit_log
FOR EACH ROW
BEGIN
    IF NEW.action_type = 'user_login' THEN
        INSERT INTO user_last_logins (operator, last_login)
        VALUES (NEW.operator, NEW.timestamp)
        ON DUPLICATE KEY UPDATE last_login = NEW.timestamp;
    END IF;
END;//
DELIMITER ;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
