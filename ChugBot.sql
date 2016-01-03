# To load this file:
# source <path-to-file>
# Remember to remove test data from the end of this file before loading for production use.

# Create the database
CREATE DATABASE IF NOT EXISTS chugbot_db COLLATE utf8_unicode_ci;

# Create a user for the chugbot program (if it does not already exist), and
# grant the access it needs.
GRANT CREATE,INSERT,SELECT,UPDATE,DELETE ON chugbot_db.* TO 'chugbot'@'localhost' IDENTIFIED BY 'chugbot';

# Switch to the new database, in preparation for creating tables.
USE chugbot_db;

# Create a table to hold admin data.
CREATE TABLE admin_data(
admin_email varchar(50) NOT NULL,
admin_password varchar(255) NOT NULL)
COLLATE utf8_unicode_ci;

# This table holds sessions, e.g., "July", "August", "Full Summer", "Mini Bet", etc.
CREATE TABLE sessions(
session_id int NOT NULL AUTO_INCREMENT PRIMARY KEY,
name varchar(50) NOT NULL,
UNIQUE KEY uk_sessions(name))
COLLATE utf8_unicode_ci;

# A block is a division of a session, e.g.,
# "July 1" or "August 2".  
CREATE TABLE blocks(
block_id int NOT NULL AUTO_INCREMENT PRIMARY KEY,
name varchar(50) NOT NULL,
UNIQUE KEY uk_blocks(name))
COLLATE utf8_unicode_ci;

# A block instance is a block/session tuple.  We use this table to
# translate the session(s) for which a camper signs up with the blocks that she
# should be assigned for.  For example, campers in the July session need
# assignments for the July 1 block, and so do campers signed up for
# July + August and Mini Aleph.  In theory, we could ask campers to just
# indicate the blocks they are signed up for, but they sign up for things 
# in terms of sessions.
CREATE TABLE block_instances(
block_id int NOT NULL,
FOREIGN KEY fk_block_id(block_id) REFERENCES blocks(block_id)
ON DELETE CASCADE
ON UPDATE CASCADE,
session_id int,
FOREIGN KEY fk_session_id(session_id) REFERENCES sessions(session_id)
ON DELETE CASCADE
ON UPDATE CASCADE,
PRIMARY KEY pk_block_instances(block_id, session_id))
COLLATE utf8_unicode_ci;

# List all edot (Kochavim, Ilanot 1, Ilanot 2, etc).
CREATE TABLE edot(
edah_id int NOT NULL AUTO_INCREMENT PRIMARY KEY,
name varchar(50) NOT NULL,
UNIQUE KEY uk_edot(name))
COLLATE utf8_unicode_ci;

# This table stores camper registration for the summer.  Each 
# camper signs up for one edah in a summer, and they choose
# a session.
CREATE TABLE campers(
camper_id int NOT NULL AUTO_INCREMENT PRIMARY KEY,
edah_id int,
FOREIGN KEY fk_edah_id(edah_id) REFERENCES edot(edah_id)
ON DELETE SET NULL
ON UPDATE CASCADE,
session_id int,
FOREIGN KEY fk_session_id(session_id) REFERENCES sessions(session_id)
ON DELETE SET NULL
ON UPDATE CASCADE,
first varchar(50) NOT NULL,
last varchar(50) NOT NULL,
email varchar(50) NOT NULL,
needs_first_choice bool DEFAULT 0,
active bool NOT NULL DEFAULT 1)
COLLATE utf8_unicode_ci;

# Each chug instance is assigned to a group for the whole summer.
# For example, swimming might be in group aleph.
CREATE TABLE groups(
group_id int NOT NULL AUTO_INCREMENT PRIMARY KEY,
name varchar(50) NOT NULL, # aleph, bet, or gimel
UNIQUE KEY uk_groups(name))
COLLATE utf8_unicode_ci;

# This table holds data on each chug.  Each chug belongs to exactly one group (aleph, bet, or gimel), and
# the group is consistent across all edot for the whole summer.  We assume for now
# that all chugim are offered in all sessions to all edot, and that size limits are consistent for all
# edot and sessions.  
# The "active" bit indicates that this chug is active for the current summer.
CREATE TABLE chugim(
name varchar(50) NOT NULL,
group_id int,
FOREIGN KEY fk_group(group_id) REFERENCES groups(group_id)
ON DELETE SET NULL
ON UPDATE CASCADE,
max_size int NULL,
min_size int NULL,
description varchar(2048),
chug_id int NOT NULL AUTO_INCREMENT PRIMARY KEY)
COLLATE utf8_unicode_ci;

# A chug instance is a concrete offering of a chug in a block.
# For example, swimming, July first week.  Note that the chugim
# themselves are assigned to groups, so an instance also includes
# the group (aleph, bet or gimel).
CREATE TABLE chug_instances(
chug_id int NOT NULL,
FOREIGN KEY fk_chug_id(chug_id) REFERENCES chugim(chug_id)
ON DELETE CASCADE
ON UPDATE CASCADE,
block_id int NOT NULL,
FOREIGN KEY fk_block_id(block_id) REFERENCES blocks(block_id)
ON DELETE CASCADE
ON UPDATE CASCADE,
UNIQUE KEY uk_chug_instances(chug_id, block_id),
chug_instance_id int NOT NULL AUTO_INCREMENT PRIMARY KEY)
COLLATE utf8_unicode_ci;

# Each entry in this table represents a camper preference list for a given group of chugim in a 
# given block.  For example, a camper would make a pref list for the aleph chugim in July, first week.
# Up to 6 choices are allowed for each group/block tuple.
CREATE TABLE preferences(
camper_id int NOT NULL,
FOREIGN KEY fk_camper_id(camper_id) REFERENCES campers(camper_id)
ON DELETE CASCADE
ON UPDATE CASCADE,
group_id int NOT NULL, # aleph, bet, or gimel
FOREIGN KEY fk_group_id(group_id) REFERENCES groups(group_id)
ON DELETE CASCADE
ON UPDATE CASCADE,
block_id int NOT NULL,
FOREIGN KEY fk_block_id(block_id) REFERENCES blocks(block_id)
ON DELETE CASCADE
ON UPDATE CASCADE,
first_choice_id int,
FOREIGN KEY fk_first_choice_id(first_choice_id) REFERENCES chugim(chug_id)
ON DELETE SET NULL
ON UPDATE CASCADE,
second_choice_id int,
FOREIGN KEY fk_second_choice_id(second_choice_id) REFERENCES chugim(chug_id)
ON DELETE SET NULL
ON UPDATE CASCADE,
third_choice_id int,
FOREIGN KEY fk_third_choice_id(third_choice_id) REFERENCES chugim(chug_id)
ON DELETE SET NULL
ON UPDATE CASCADE,
fourth_choice_id int,
FOREIGN KEY fk_fourth_choice_id(fourth_choice_id) REFERENCES chugim(chug_id)
ON DELETE SET NULL
ON UPDATE CASCADE,
fifth_choice_id int,
FOREIGN KEY fk_fifth_choice_id(fifth_choice_id) REFERENCES chugim(chug_id)
ON DELETE SET NULL
ON UPDATE CASCADE,
sixth_choice_id int,
FOREIGN KEY fk_sixth_choice_id(sixth_choice_id) REFERENCES chugim(chug_id)
ON DELETE SET NULL
ON UPDATE CASCADE,
PRIMARY KEY(camper_id, group_id, block_id))
COLLATE utf8_unicode_ci;

# Assignments are done at the edah/block/group level.  This table holds beta
# about each assignment.  The actual matches are stored in the matches table.
CREATE TABLE assignments(
edah_id int NOT NULL,
FOREIGN KEY fk_edah_id(edah_id) REFERENCES edot(edah_id)
ON DELETE CASCADE
ON UPDATE CASCADE,
block_id int NOT NULL,
FOREIGN KEY fk_block_id(block_id) REFERENCES blocks(block_id)
ON DELETE CASCADE
ON UPDATE CASCADE,
group_id int NOT NULL, # aleph, bet, or gimel
FOREIGN KEY fk_group_id(group_id) REFERENCES groups(group_id)
ON DELETE CASCADE
ON UPDATE CASCADE,
pct_first_choice float,
pct_second_choice float,
pct_third_choice float,
pct_fourth_choice_plus float,
PRIMARY KEY pk_assignments(edah_id, block_id, group_id))
COLLATE utf8_unicode_ci;

# This table holds matches of campers to chugim.  A match is for one
# camper in a given block/group.
CREATE TABLE matches(
camper_id int NOT NULL,
FOREIGN KEY fk_camper_id(camper_id) REFERENCES campers(camper_id)
ON DELETE CASCADE
ON UPDATE CASCADE,
block_id int NOT NULL,
FOREIGN KEY fk_block_id(block_id) REFERENCES blocks(block_id)
ON DELETE CASCADE
ON UPDATE CASCADE,
group_id int NOT NULL, # We do not strict need this, because chug_id goes with a group.
FOREIGN KEY fk_group_id(group_id) REFERENCES groups(group_id)
ON DELETE CASCADE
ON UPDATE CASCADE,
chug_id int NOT NULL,
FOREIGN KEY fk_chug_id(chug_id) REFERENCES chugim(chug_id)
ON DELETE CASCADE
ON UPDATE CASCADE,
pegged bool DEFAULT 0,
PRIMARY	KEY pk_matches(camper_id, block_id, group_id))
COLLATE utf8_unicode_ci;

# Insert starter data for testing.
INSERT INTO sessions (name) VALUES ("July");
INSERT INTO sessions (name) VALUES ("August");
INSERT INTO sessions (name) VALUES ("July and August");
INSERT INTO sessions (name) VALUES ("Mini Aleph");

INSERT INTO blocks (name) VALUES ("July 1");
INSERT INTO blocks (name) VALUES ("July 2");
INSERT INTO blocks (name) VALUES ("August 2");

INSERT INTO block_instances (block_id, session_id) VALUES (1, 1);
INSERT INTO block_instances (block_id, session_id) VALUES (1, 3);
INSERT INTO block_instances (block_id, session_id) VALUES (2, 1);
INSERT INTO block_instances (block_id, session_id) VALUES (2, 3);
INSERT INTO block_instances (block_id, session_id) VALUES (3, 2);
INSERT INTO block_instances (block_id, session_id) VALUES (3, 3);

INSERT INTO edot (name) VALUES ("Kochavim");
INSERT INTO edot (name) VALUES ("Ilanot 1");
INSERT INTO edot (name) VALUES ("Ilanot 2");
INSERT INTO edot (name) VALUES ("Solelim");

INSERT INTO groups (name) VALUES ("aleph");
INSERT INTO groups (name) VALUES ("bet");
INSERT INTO groups (name) VALUES ("gimel");

INSERT INTO campers (edah_id, session_id, first, last, email) VALUES (1, 1, "Elphaba", "Lobron", "dlobron@gmail.com");
INSERT INTO campers (edah_id, session_id, first, last, email) VALUES (2, 3, "KittyBoy", "Lobron", "dlobron@gmail.com");
INSERT INTO campers (edah_id, session_id, first, last, email) VALUES (2, 3, "Skippyjon", "Jones", "dlobron@gmail.com");

# aleph chugim
INSERT INTO chugim (name, group_id, min_size, max_size, description) VALUES ("Swimming", 1, 5, 10, "Playing in the water");
INSERT INTO chugim (name, group_id, min_size, max_size, description) VALUES ("Krav Maga", 1, 3, 15, "Israeli martial art");
INSERT INTO chugim (name, group_id, min_size, max_size, description) VALUES ("Cooking", 1, 0, 0, "Making food");
INSERT INTO chugim (name, group_id, min_size, max_size, description) VALUES ("Ropes", 1, 1, 5, "Awesome climbs on fun rock");
# bet chugim
INSERT INTO chugim (name, group_id, min_size, max_size, description) VALUES ("Boating", 2, 5, 10, "SUP and kayaking");
INSERT INTO chugim (name, group_id, min_size, max_size, description) VALUES ("Outdoor Cooking", 2, 3, 15, "Smores and such");
INSERT INTO chugim (name, group_id, min_size, max_size, description) VALUES ("Israeli Dance", 2, 0, 0, "Also known as rikud");
INSERT INTO chugim (name, group_id, min_size, max_size, description) VALUES ("Canoeing", 2, 1, 5, "Messing about in boats");

INSERT INTO chug_instances(chug_id, block_id) VALUES (1, 1);
INSERT INTO chug_instances(chug_id, block_id) VALUES (2, 1);
INSERT INTO chug_instances(chug_id, block_id) VALUES (3, 1);
INSERT INTO chug_instances(chug_id, block_id) VALUES (5, 1);
INSERT INTO chug_instances(chug_id, block_id) VALUES (6, 1);
INSERT INTO chug_instances(chug_id, block_id) VALUES (7, 1);

INSERT INTO chug_instances(chug_id, block_id) VALUES (2, 2);
INSERT INTO chug_instances(chug_id, block_id) VALUES (3, 2);
INSERT INTO chug_instances(chug_id, block_id) VALUES (4, 2);
INSERT INTO chug_instances(chug_id, block_id) VALUES (6, 2);
INSERT INTO chug_instances(chug_id, block_id) VALUES (7, 2);
INSERT INTO chug_instances(chug_id, block_id) VALUES (8, 2);

INSERT INTO chug_instances(chug_id, block_id) VALUES (2, 3);
INSERT INTO chug_instances(chug_id, block_id) VALUES (3, 3);
INSERT INTO chug_instances(chug_id, block_id) VALUES (4, 3);
INSERT INTO chug_instances(chug_id, block_id) VALUES (6, 3);
INSERT INTO chug_instances(chug_id, block_id) VALUES (7, 3);
INSERT INTO chug_instances(chug_id, block_id) VALUES (8, 3);
