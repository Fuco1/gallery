-- phpMyAdmin SQL Dump
-- version 3.4.5
-- http://www.phpmyadmin.net
--
-- Host: 127.0.0.1
-- Generation Time: Dec 25, 2012 at 09:52 PM
-- Server version: 5.5.16
-- PHP Version: 5.4.0beta2

SET SQL_MODE="NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";

--
-- Database: `gallery`
--

-- --------------------------------------------------------

--
-- Table structure for table `images`
--

CREATE TABLE IF NOT EXISTS `images` (
  `image_id` int(11) NOT NULL AUTO_INCREMENT,
  `title` varchar(255) COLLATE utf8_czech_ci NOT NULL,
  `path` varchar(255) COLLATE utf8_czech_ci NOT NULL,
  `filename` varchar(255) COLLATE utf8_czech_ci NOT NULL,
  `hash` varchar(32) COLLATE utf8_czech_ci NOT NULL,
  `author_id` int(11) NOT NULL DEFAULT '0',
  `date` int(11) NOT NULL,
  `views` int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (`image_id`),
  UNIQUE KEY `hash` (`hash`,`author_id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COLLATE=utf8_czech_ci AUTO_INCREMENT=103 ;

-- --------------------------------------------------------

--
-- Table structure for table `image_tags`
--

CREATE TABLE IF NOT EXISTS `image_tags` (
  `image_id` int(11) NOT NULL,
  `tag_id` int(11) NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_czech_ci;

-- --------------------------------------------------------

--
-- Table structure for table `sessions`
--

CREATE TABLE IF NOT EXISTS `sessions` (
  `sessionId` varchar(32) COLLATE utf8_czech_ci NOT NULL,
  `username` varchar(255) COLLATE utf8_czech_ci DEFAULT NULL,
  `userId` int(11) DEFAULT NULL,
  `userPrivs` int(11) DEFAULT NULL,
  `start` int(11) DEFAULT NULL,
  `lastActive` int(11) DEFAULT NULL,
  `browser` varchar(255) COLLATE utf8_czech_ci DEFAULT NULL,
  `ip` varchar(40) COLLATE utf8_czech_ci DEFAULT NULL,
  `autologin` int(11) DEFAULT NULL,
  PRIMARY KEY (`sessionId`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_czech_ci;

-- --------------------------------------------------------

--
-- Table structure for table `tags`
--

CREATE TABLE IF NOT EXISTS `tags` (
  `tag_id` int(11) NOT NULL AUTO_INCREMENT,
  `title` varchar(255) COLLATE utf8_czech_ci NOT NULL,
  PRIMARY KEY (`tag_id`),
  UNIQUE KEY `title` (`title`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COLLATE=utf8_czech_ci AUTO_INCREMENT=182 ;

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE IF NOT EXISTS `users` (
  `user_id` int(11) NOT NULL AUTO_INCREMENT,
  `username` varchar(255) COLLATE utf8_czech_ci NOT NULL,
  `password` varchar(40) COLLATE utf8_czech_ci NOT NULL,
  `date` int(11) NOT NULL,
  `access` tinyint(4) NOT NULL DEFAULT '0',
  PRIMARY KEY (`user_id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COLLATE=utf8_czech_ci AUTO_INCREMENT=5 ;
