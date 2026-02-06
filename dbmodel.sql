
-- ------
-- BGA framework: © Gregory Isabelli <gisabelli@boardgamearena.com> & Emmanuel Colin <ecolin@boardgamearena.com>
-- RollForTheGalaxy implementation : © <Your name here> <Your email address here>
-- 
-- This code has been produced on the BGA studio platform for use on http://boardgamearena.com.
-- See http://en.boardgamearena.com/#!doc/Studio for more information.
-- -----

-- dbmodel.sql

-- This is the file where you are describing the database schema of your game
-- Basically, you just have to export from PhpMyAdmin your table structure and copy/paste
-- this export here.
-- Note that the database itself and the standard tables ("global", "stats", "gamelog" and "player") are
-- already created and must not be created here

-- Note: The database schema is created from this file when the game starts. If you modify this file,
--       you have to restart a game to see your changes in database.

-- Example 1: create a standard "card" table to be used with the "Deck" tools (see example game "hearts"):

-- CREATE TABLE IF NOT EXISTS `card` (
--   `card_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
--   `card_type` varchar(16) NOT NULL,
--   `card_type_arg` int(11) NOT NULL,
--   `card_location` varchar(16) NOT NULL,
--   `card_location_arg` int(11) NOT NULL,
--   PRIMARY KEY (`card_id`)
-- ) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;


-- Example 2: add a custom field to the standard "player" table
-- ALTER TABLE `player` ADD `player_my_custom_field` INT UNSIGNED NOT NULL DEFAULT '0';



ALTER TABLE  `player` ADD  `player_credit` MEDIUMINT UNSIGNED NOT NULL DEFAULT  '1';
ALTER TABLE  `player` ADD  `player_vp_chip` MEDIUMINT UNSIGNED NOT NULL DEFAULT  '0';
ALTER TABLE  `player` ADD  `player_choosed_phase` TINYINT UNSIGNED NULL DEFAULT NULL ;
ALTER TABLE  `player` ADD  `player_dictate` TINYINT NOT NULL DEFAULT  '0';
ALTER TABLE  `player` ADD  `player_manage_initial_credit` MEDIUMINT UNSIGNED NULL DEFAULT NULL;

CREATE TABLE IF NOT EXISTS `tile` (
  `card_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `card_type` varchar(16) NOT NULL COMMENT 'see materials.inc.php',
  `card_type_arg` int(11) NOT NULL COMMENT 'track reassign power usage',
  `card_location` varchar(16) NOT NULL COMMENT 'one of {deck, tableau, bd<N>, bw<N>, homeworlds, factiontiles, scout}',
  `card_location_arg` int(11) NOT NULL COMMENT 'player id or position in deck/stack',
  PRIMARY KEY (`card_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

CREATE TABLE IF NOT EXISTS `dice` (
  `card_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `card_type` varchar(16) NOT NULL COMMENT 'color of die',
  `card_type_arg` int(11) NOT NULL COMMENT 'result of roll',
  `card_location` varchar(16) NOT NULL COMMENT 'one of {deck<N>, cup, cup_recruited, citizenry, phase<N>, worldconstruct, devconstruct, resource}',
  `card_location_arg` int(11) NOT NULL COMMENT 'player id or position in deck or tile id',
  PRIMARY KEY (`card_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

