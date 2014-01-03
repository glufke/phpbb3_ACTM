--Changes on the table PHPBB_POSTS
ALTER TABLE `phpbb_posts` ADD `post_subject_en` VARCHAR( 255 ) NOT NULL default '' AFTER `post_subject`
ALTER TABLE `phpbb_posts` CHANGE `post_subject_en` `post_subject_en` VARCHAR( 255 ) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL DEFAULT ''
ALTER TABLE `phpbb_posts` ADD FULLTEXT (`post_subject_en`)
ALTER TABLE `phpbb_posts` ADD  `post_time_en` INT( 11 ) NOT NULL DEFAULT  '0' AFTER  `post_time`
ALTER TABLE `phpbb_posts` ADD `post_text_en` mediumtext collate utf8_bin NOT NULL AFTER `post_text`

--Changes on the table PHPBB_TOPICS
ALTER TABLE `phpbb_topics` ADD `topic_title_en` VARCHAR( 255 )  NOT NULL default '' AFTER `topic_title` ;
ALTER TABLE `phpbb_topics` CHANGE `topic_title_en` `topic_title_en` VARCHAR( 255 ) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL DEFAULT '';

--Changes on the table PHPBB_FORUMS
ALTER TABLE `phpbb_forums` ADD `forum_name_en` VARCHAR( 255 ) NOT NULL default '0' AFTER `forum_name`
ALTER TABLE `phpbb_forums` CHANGE `forum_name_en` `forum_name_en` VARCHAR( 255 ) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL DEFAULT '0'
ALTER TABLE `phpbb_forums` ADD `forum_desc_en` TEXT NOT NULL AFTER `forum_desc`
ALTER TABLE `phpbb_forums` ADD `forum_ativo_en` VARCHAR( 1 ) AFTER `forum_name_en`