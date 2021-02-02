-- phpMyAdmin SQL Dump
-- version 4.1.14
-- http://www.phpmyadmin.net
--
-- Хост: 127.0.0.1
-- Час створення: Бер 22 2016 р., 20:19
-- Версія сервера: 5.6.17
-- Версія PHP: 5.5.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8 */;

--
-- База даних: `lmr`
--

-- --------------------------------------------------------

--
-- Структура таблиці `deputies`
--

CREATE TABLE IF NOT EXISTS `deputies` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT 'PK',
  `name` varchar(255) COLLATE utf8_unicode_ci NOT NULL COMMENT 'П. І. Б.',
  PRIMARY KEY (`id`),
  UNIQUE KEY `name` (`name`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=392 ;

-- --------------------------------------------------------

--
-- Структура таблиці `elections`
--

CREATE TABLE IF NOT EXISTS `elections` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT 'PK',
  `issue` int(10) unsigned NOT NULL COMMENT 'id голосування',
  `deputy` int(10) unsigned NOT NULL COMMENT 'id депутата',
  `election` int(10) unsigned NOT NULL COMMENT 'Вибір: 0 - ВІДСУТНІЙ, 1 - ЗА, 2 - ПРОТИ, 3 - УТРИМАВСЯ, 4- НЕ ГОЛОСУВАВ, 5 - ХЗ',
  PRIMARY KEY (`id`),
  KEY `issue` (`issue`),
  KEY `deputy` (`deputy`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=53109 ;

-- --------------------------------------------------------

--
-- Структура таблиці `issues`
--

CREATE TABLE IF NOT EXISTS `issues` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT 'PK',
  `issue` text COLLATE utf8_unicode_ci NOT NULL COMMENT 'Питання',
  `session` int(10) unsigned NOT NULL COMMENT 'id сесії',
  PRIMARY KEY (`id`),
  KEY `session` (`session`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=834 ;

-- --------------------------------------------------------

--
-- Структура таблиці `sessions`
--

CREATE TABLE IF NOT EXISTS `sessions` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT 'PK',
  `date` date NOT NULL COMMENT 'Дата засідання',
  `url` varchar(255) COLLATE utf8_unicode_ci NOT NULL COMMENT 'URL засідання',
  PRIMARY KEY (`id`),
  UNIQUE KEY `date` (`date`),
  UNIQUE KEY `url` (`url`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci COMMENT='Сесії ЛМР' AUTO_INCREMENT=39 ;

--
-- Обмеження зовнішнього ключа збережених таблиць
--

--
-- Обмеження зовнішнього ключа таблиці `elections`
--
ALTER TABLE `elections`
  ADD CONSTRAINT `elections_ibfk_2` FOREIGN KEY (`deputy`) REFERENCES `deputies` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `elections_ibfk_1` FOREIGN KEY (`issue`) REFERENCES `issues` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Обмеження зовнішнього ключа таблиці `issues`
--
ALTER TABLE `issues`
  ADD CONSTRAINT `issues_ibfk_1` FOREIGN KEY (`session`) REFERENCES `sessions` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
