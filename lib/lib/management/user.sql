create table users (
	user_id int(10) unsigned not null auto_increment,
	uname varchar(15) not null unique,
	upass char(32) not null,
	verification char(32) not null,
	fname varchar(15) not null,
	lname varchar(15) not null,
	address varchar(25) not null default '',
	zip int(9) not null default 0,
	city varchar(20) not null default '',
	`state` enum('AL', 'AK', 'AZ', 'AR', 'CA', 'CO', 'CT', 'DE', 'DC', 'FL', 'GA', 'HI', 'ID', 'IL', 'IN', 'IA', 'KS', 'KY', 'LA', 'ME', 'MD', 'MA', 'MI', 'MN', 'MS', 'MO', 'MT', 'NE', 'NV', 'NH', 'NJ', 'NM', 'NY', 'NC', 'ND', 'OH', 'OK', 'OR', 'PA', 'RI', 'SC', 'SD', 'TN', 'TX', 'UT', 'VT', 'VA', 'WA', 'WV', 'WI', 'WY'),
	joined date not null,
	email varchar(20) not null unique,
	verified enum('Y', 'N') not null default 'N',
	is_admin int(1) not null default 0,
	primary key(user_id)
) engine=InnoDB;