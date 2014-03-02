# sql file for chat_new module

CREATE TABLE `chat_members` (
  `chat_member_id` int(8) NOT NULL AUTO_INCREMENT,
  `member_id` int(8) NOT NULL,
  `jid` varchar(250) NOT NULL ,
  `password` varchar(250) NOT NULL ,
  PRIMARY KEY (`chat_member_id`)
) DEFAULT CHARSET=utf8;

CREATE TABLE `chat_messages` (
  `id` int(8) NOT NULL AUTO_INCREMENT,
  `from` varchar(250) NOT NULL,
  `to` varchar(250) NOT NULL,
  `msg` text NOT NULL,
  `timestamp` bigint(13) NOT NULL,
  PRIMARY KEY (`id`)
) DEFAULT CHARSET=utf8;

CREATE TABLE `chat_muc_messages` (
  `id` int(8) NOT NULL AUTO_INCREMENT,
  `from` varchar(250) NOT NULL,
  `to` varchar(250) NOT NULL,
  `msg` text NOT NULL,
  `timestamp` bigint(13) NOT NULL,
  PRIMARY KEY (`id`)
) DEFAULT CHARSET=utf8;

CREATE TABLE `chat_user_mucs` (
  `user_jid` varchar(250) NOT NULL,
  `muc_jid` varchar(250) NOT NULL
) DEFAULT CHARSET=utf8;


INSERT INTO `language_text` VALUES ('en', '_module','chat_new','XMPP Chat',NOW(),'');
INSERT INTO `language_text` VALUES ('en', '_module','chat_new_text','XMPP-based chat for ATutor.',NOW(),'');