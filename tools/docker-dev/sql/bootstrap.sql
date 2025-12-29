-- phpMyAdmin SQL Dump
-- version 4.9.5deb2
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Jul 13, 2023 at 02:29 AM
-- Server version: 10.3.38-MariaDB-0ubuntu0.20.04.1
-- PHP Version: 7.4.3-4ubuntu2.19

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET AUTOCOMMIT = 0;
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `unity`
--

-- --------------------------------------------------------

--
-- Table structure for table `account_deletion_requests`
--

CREATE TABLE `account_deletion_requests` (
  `id` int(11) NOT NULL,
  `timestamp` timestamp NOT NULL DEFAULT current_timestamp(),
  `uid` varchar(128) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `audit_log`
--

CREATE TABLE `audit_log` (
  `id` int(11) NOT NULL,
  `timestamp` timestamp NOT NULL DEFAULT current_timestamp(),
  `operator` varchar(128) NOT NULL,
  `operator_ip` varchar(15) NOT NULL,
  `action_type` varchar(768) NOT NULL,
  `recipient` varchar(128) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `user_last_logins`
--

CREATE TABLE user_last_logins (
   operator VARCHAR(768) PRIMARY KEY,
   last_login TIMESTAMP
) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;

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

-- --------------------------------------------------------

--
-- Table structure for table `notices`
--

CREATE TABLE `notices` (
  `id` int(11) NOT NULL,
  `date` timestamp NOT NULL DEFAULT current_timestamp(),
  `title` varchar(300) NOT NULL,
  `message` longtext NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `notices`
--

INSERT INTO `notices` (`id`, `date`, `title`, `message`) VALUES
(9, '2022-09-19 15:49:10', 'Example Notice 1', '<p>Lorem ipsum dolor sit amet, consectetur adipiscing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua. Ut enim ad minim veniam, quis nostrud exercitation ullamco laboris nisi ut aliquip ex ea commodo consequat. Duis aute irure dolor in reprehenderit in voluptate velit esse cillum dolore eu fugiat nulla pariatur. Excepteur sint occaecat cupidatat non proident, sunt in culpa qui officia deserunt mollit anim id est laborum.</p>'),
(10, '2022-09-14 11:48:39', 'Example Notice 2', '<p>Lorem ipsum dolor sit amet, consectetur adipiscing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua. Ut enim ad minim veniam, quis nostrud exercitation ullamco laboris nisi ut aliquip ex ea commodo consequat. Duis aute irure dolor in reprehenderit in voluptate velit esse cillum dolore eu fugiat nulla pariatur. Excepteur sint occaecat cupidatat non proident, sunt in culpa qui officia deserunt mollit anim id est laborum.</p>');

-- --------------------------------------------------------

--
-- Table structure for table `pages`
--

CREATE TABLE `pages` (
  `id` int(11) NOT NULL,
  `page` varchar(300) NOT NULL,
  `content` longtext NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `pages`
--

INSERT INTO `pages` (`id`, `page`, `content`) VALUES
(0,'home','<h1>Docmentation and FAQ</h1><p>You can find our documentation <a href=\"https://docs.unity.rc.umass.edu/\">here</a>. We also have an <a href=\"https://docs.unity.rc.umass.edu/documentation/help/faq/\">FAQ</a> page which could help answer quick questions.</p><h3>Office Hours</h3><p>We offer office hours every week on <strong>Tuesdays 2-4 PM</strong> in-person at <strong>W.E.B. DuBois Library 786</strong> or remote on <a href=\"https://github.com/\"><strong>Zoom</strong></a>. Be sure to check the <a href=\"/\">cluster notes</a> page for up-to-date information on any canceled/delayed office hours.</p><h3>Support Email</h3><p>You can create a support ticket by emailing <a href=\"mailto:hpc@umass.edu\">hpc@umass.edu</a>. We will do our best to reply as fast as possible!</p><p>&nbsp;</p>'),
(1,'support','<h1>Docmentation and FAQ</h1><p>You can find our documentation <a href=\"https://esdconfluence.it.umass.edu/confluence/display/UNITY/Unity+Cluster+Documentation+Home\">here</a>. We also have an <a href=\"https://esdconfluence.it.umass.edu/confluence/display/UNITY/Frequently+Asked+Questions\">FAQ</a> page which could help answer quick questions.</p><h3>Office Hours</h3><p>We offer office hours every week on <strong>Tuesdays 2-4 PM</strong> in-person at <strong>W.E.B. DuBois Library 786</strong> or remote on <a href=\"https://umass-amherst.zoom.us/j/95663998309\"><strong>Zoom</strong></a>. Be sure to check the <a href=\"&lt;?php echo $CONFIG[\">/index.php\"&gt;cluster notes</a> page for up-to-date information on any canceled/delayed office hours.</p><h3>Support Email</h3><p>You can create a support ticket by emailing <a href=\"mailto:hpc@umass.edu\">hpc@umass.edu</a>. We will do our best to reply as fast as possible!</p>');

-- --------------------------------------------------------

--
-- Table structure for table `requests`
--

CREATE TABLE `requests` (
  `id` int(11) NOT NULL,
  `request_for` varchar(131) NOT NULL,
  `uid` varchar(128) NOT NULL,
  `timestamp` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Indexes for dumped tables
--

--
-- Indexes for table `account_deletion_requests`
--
ALTER TABLE `account_deletion_requests`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `audit_log`
--
ALTER TABLE `audit_log`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `notices`
--
ALTER TABLE `notices`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `pages`
--
ALTER TABLE `pages`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `requests`
--
ALTER TABLE `requests`
  ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `account_deletion_requests`
--
ALTER TABLE `account_deletion_requests`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `audit_log`
--
ALTER TABLE `audit_log`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `notices`
--
ALTER TABLE `notices`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT for table `pages`
--
ALTER TABLE `pages`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `requests`
--
ALTER TABLE `requests`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1031;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
