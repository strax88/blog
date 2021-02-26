-- Скрипт сгенерирован Devart dbForge Studio for MySQL, Версия 6.0.441.0
-- Домашняя страница продукта: http://www.devart.com/ru/dbforge/mysql/studio
-- Дата скрипта: 26.02.2021 12:45:30
-- Версия сервера: 5.5.5-10.3.22-MariaDB
-- Версия клиента: 4.1

--
-- Описание для базы данных blog_db
--
DROP DATABASE IF EXISTS blog_db;
CREATE DATABASE IF NOT EXISTS blog_db
	CHARACTER SET utf8
	COLLATE utf8_general_ci;

-- 
-- Отключение внешних ключей
-- 
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;

-- 
-- Установка кодировки, с использованием которой клиент будет посылать запросы на сервер
--
SET NAMES 'utf8';

-- 
-- Установка базы данных по умолчанию
--
USE blog_db;

--
-- Описание для таблицы user_types
--
CREATE TABLE IF NOT EXISTS user_types (
  IDut INT(11) NOT NULL AUTO_INCREMENT,
  utName VARCHAR(50) DEFAULT 'NULL',
  PRIMARY KEY (IDut)
)
ENGINE = INNODB
AUTO_INCREMENT = 3
AVG_ROW_LENGTH = 8192
CHARACTER SET utf8
COLLATE utf8_general_ci
COMMENT = 'Типы пользователей'
ROW_FORMAT = DYNAMIC;

--
-- Описание для таблицы users
--
CREATE TABLE IF NOT EXISTS users (
  IDu INT(11) NOT NULL,
  uName VARCHAR(255) DEFAULT '''''''NULL''''''',
  uEmail VARCHAR(50) DEFAULT '''''''NULL''''''',
  IDut INT(11) DEFAULT NULL,
  PRIMARY KEY (IDu),
  UNIQUE INDEX uEmail (uEmail),
  CONSTRAINT FK_users_user_types_IDut FOREIGN KEY (IDut)
    REFERENCES user_types(IDut) ON DELETE CASCADE ON UPDATE RESTRICT
)
ENGINE = INNODB
CHARACTER SET utf8
COLLATE utf8_general_ci
COMMENT = 'Пользователи'
ROW_FORMAT = DYNAMIC;

--
-- Описание для таблицы posts
--
CREATE TABLE IF NOT EXISTS posts (
  IDp INT(11) NOT NULL,
  IDu INT(11) DEFAULT NULL,
  pTitle VARCHAR(255) DEFAULT '''NULL''',
  PRIMARY KEY (IDp),
  CONSTRAINT FK_posts_users_IDu FOREIGN KEY (IDu)
    REFERENCES users(IDu) ON DELETE CASCADE ON UPDATE RESTRICT
)
ENGINE = INNODB
CHARACTER SET utf8
COLLATE utf8_general_ci
COMMENT = 'Посты'
ROW_FORMAT = DYNAMIC;

--
-- Описание для таблицы comments
--
CREATE TABLE IF NOT EXISTS comments (
  IDc INT(11) NOT NULL,
  IDp INT(11) DEFAULT NULL,
  IDu INT(11) DEFAULT NULL,
  cBody TEXT DEFAULT NULL,
  PRIMARY KEY (IDc),
  CONSTRAINT FK_comments_posts FOREIGN KEY (IDp)
    REFERENCES posts(IDp) ON DELETE CASCADE ON UPDATE RESTRICT,
  CONSTRAINT FK_comments_users FOREIGN KEY (IDu)
    REFERENCES users(IDu) ON DELETE CASCADE ON UPDATE RESTRICT
)
ENGINE = INNODB
CHARACTER SET utf8
COLLATE utf8_general_ci
COMMENT = 'Комментарии'
ROW_FORMAT = DYNAMIC;

--
-- Описание для таблицы posts_bodies
--
CREATE TABLE IF NOT EXISTS posts_bodies (
  IDp INT(11) DEFAULT NULL,
  pbText TEXT DEFAULT NULL,
  CONSTRAINT FK_posts_bodies_posts_IDp FOREIGN KEY (IDp)
    REFERENCES posts(IDp) ON DELETE CASCADE ON UPDATE RESTRICT
)
ENGINE = INNODB
CHARACTER SET utf8
COLLATE utf8_general_ci
COMMENT = 'Содержимое постов'
ROW_FORMAT = DYNAMIC;

-- 
-- Вывод данных для таблицы user_types
--
INSERT INTO user_types(IDut, utName) VALUES
(1, 'Author'),
(2, 'Commentator');

-- 
-- Вывод данных для таблицы users
--

-- Таблица blog_db.users не содержит данных

-- 
-- Вывод данных для таблицы posts
--

-- Таблица blog_db.posts не содержит данных

-- 
-- Вывод данных для таблицы comments
--

-- Таблица blog_db.comments не содержит данных

-- 
-- Вывод данных для таблицы posts_bodies
--

-- Таблица blog_db.posts_bodies не содержит данных

-- 
-- Включение внешних ключей
-- 
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;