-- ---------------------------------------------
-- needed for lib/util/counter/class_counter.php
-- ---------------------------------------------
CREATE TABLE pcpages (
	`page_id` CHAR(32) NOT NULL,
	`page` CHAR(150) NOT NULL,
	`vcount` INT UNSIGNED NOT NULL DEFAULT 1,
	`created` DATETIME NOT NULL,
	UNIQUE INDEX (`page`),
	PRIMARY KEY (`page_id`)
) ENGINE=INNODB;

CREATE TABLE referrers (
	`referrer_id` CHAR(32) NOT NULL,
	`referrer_url` VARCHAR(250) NOT NULL,
	PRIMARY KEY (`referrer_id`)
) ENGINE=INNODB;

CREATE TABLE referrer_visit (
	`page_id` CHAR(32) NOT NULL,
	`referrer_id` CHAR(32) NOT NULL,
	`vtime` DATETIME NOT NULL,
	`rcount` INT UNSIGNED NOT NULL DEFAULT 1,
	PRIMARY KEY (`page_id`, `referrer_id`),
	INDEX `by_page` (`page_id`),
	INDEX `by_referrer` (`referrer_id`),
	FOREIGN KEY (`page_id`) REFERENCES `pcpages`(`page_id`) ON DELETE CASCADE ON UPDATE CASCADE
	FOREIGN KEY (`referrer_id`) REFERENCES `referrers`(`referrer_id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=INNODB;
-- SELECT * FROM botvisit LEFT JOIN pcpages ON (botvisit.page_id=pcpages.page_id) WHERE agent_id=''
CREATE TABLE pcvisit (
	`session` CHAR(32) NOT NULL,
	`address` CHAR(15) NOT NULL,
	`page_id` CHAR(32) NOT NULL,
	`dtime` DATETIME NOT NULL,
	`scount` INT NOT NULL DEFAULT 1,
	PRIMARY KEY (`session`),
	INDEX (`page_id`),
	FOREIGN KEY (`page_id`) REFERENCES `pcpages`(`page_id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=INNODB;

CREATE TABLE pcvisits (
	`session` CHAR(32) NOT NULL,
	`dtime` DATETIME NOT NULL,
	PRIMARY KEY(`session`, `dtime`),
	FOREIGN KEY (`session`) REFERENCES `pcvisit`(`session`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=INNODB;

CREATE TABLE user_agent (
	`agent_id` CHAR(32) NOT NULL,
	`agent_string` VARCHAR(200) NOT NULL,
	`agent_count` int unsigned not null default 0,
	`created` datetime not null,
	PRIMARY KEY (`agent_id`)
) ENGINE=INNODB;

CREATE TABLE bots (
	`bot_id` INT UNSIGNED AUTO_INCREMENT NOT NULL,
	`agent_id` CHAR(32) NOT NULL,
	PRIMARY KEY (`bot_id`),
	FOREIGN KEY (`agent_id`) REFERENCES `user_agent`(`agent_id`) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=INNODB;

CREATE TABLE botvisit (
	`session` CHAR(32) NOT NULL,
	`agent_id` CHAR(32) NOT NULL,
	`page_id` CHAR(32) NOT NULL,
	`dtime` DATETIME NOT NULL,
	`scount` INT NOT NULL DEFAULT 1,
	PRIMARY KEY (`session`),
	INDEX (`page_id`),
	FOREIGN KEY (`page_id`) REFERENCES `pcpages`(`page_id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=INNODB;

CREATE TABLE botvisits (
	`session` CHAR(32) NOT NULL,
	`dtime` DATETIME NOT NULL,
	PRIMARY KEY(`session`, `dtime`),
	FOREIGN KEY (`session`) REFERENCES `botvisit`(`session`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=INNODB;

-- -------------------
-- end counter tables
-- -------------------

CREATE TABLE errors (
	err_id INT UNSIGNED AUTO_INCREMENT NOT NULL,
	err_date DATETIME NOT NULL,
	err_msg TEXT,
	err_query TEXT,
	PRIMARY KEY (err_id)
);