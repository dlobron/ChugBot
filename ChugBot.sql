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

# Create a table to hold admin data.  The ISP for CRNE tells us to create an email account in cPanel
# use the full email as the username and the email account password as the password.
CREATE TABLE admin_data(
admin_email varchar(50) NOT NULL,
admin_password varchar(255) NOT NULL,
admin_email_username varchar(50),
admin_email_password varchar(255),
regular_user_token varchar(255))
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
rosh_name varchar(100) DEFAULT "",
rosh_phone varchar(20) DEFAULT "",
comments varchar(512) DEFAULT "",
UNIQUE KEY uk_edot(name))
COLLATE utf8_unicode_ci;

# Create a table of bunks.  Campers are optionally assigned to one bunk
# for the summer, which can be changed as needed on the edit camper page.
CREATE TABLE bunks(
bunk_id int NOT NULL AUTO_INCREMENT PRIMARY KEY,
name varchar(50) NOT NULL,
UNIQUE KEY uk_bunks(name))
COLLATE utf8_unicode_ci;

# A bunk instance is an assignment of bunk to edah.
CREATE TABLE bunk_instances(
bunk_id int NOT NULL,
FOREIGN KEY fk_bunk_id(bunk_id) REFERENCES bunks(bunk_id)
ON DELETE CASCADE
ON UPDATE CASCADE,
edah_id int NOT NULL,
FOREIGN KEY fk_edah_id(edah_id) REFERENCES edot(edah_id)
ON DELETE CASCADE
ON UPDATE CASCADE,
PRIMARY KEY pk_bunk_instances(bunk_id, edah_id))
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
bunk_id int,
FOREIGN KEY fk_bunk_id(bunk_id) REFERENCES bunks(bunk_id)
ON DELETE SET NULL
ON UPDATE CASCADE,
first varchar(50) NOT NULL,
last varchar(50) NOT NULL,
email varchar(50) NOT NULL,
needs_first_choice bool DEFAULT 0,
inactive bool NOT NULL DEFAULT 0)
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
# To check: I think that chugim with the same name can exist in more than one group (for example, Swimming aleph,
# Swimming bet).  
CREATE TABLE chugim(
name varchar(50) NOT NULL,
group_id int,
FOREIGN KEY fk_group(group_id) REFERENCES groups(group_id)
ON DELETE SET NULL
ON UPDATE CASCADE,
max_size int NULL,
min_size int NULL,
description varchar(2048),
UNIQUE KEY uk_chugim(name, group_id),
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
first_choice_ct float DEFAULT 0,
second_choice_ct float DEFAULT 0,
third_choice_ct float DEFAULT 0,
fourth_choice_or_worse_ct float DEFAULT 0,
under_min_list varchar(512) DEFAULT "",
over_max_list varchar(512) DEFAULT "",
ctime TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
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
PRIMARY	KEY pk_matches(camper_id, block_id, group_id))
COLLATE utf8_unicode_ci;

# Insert starter data for testing.  For production use, make sure to remove or comment-out all lines after this one.
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

INSERT INTO bunks (name) VALUES ("1");
INSERT INTO bunks (name) VALUES	("2");
INSERT INTO bunks (name) VALUES	("3.14159");
INSERT INTO bunks (name) VALUES	("h bar");

INSERT INTO campers (edah_id, session_id, first, last, email, bunk_id) VALUES (1, 1, "Wolfgang Amadeus", "Mozart", "dlobron@gmail.com", 1);
INSERT INTO campers (edah_id, session_id, first, last, email, bunk_id) VALUES (2, 3, "Ludwig van", "Beethoven", "dlobron@gmail.com", 1);
INSERT INTO campers (edah_id, session_id, first, last, email, needs_first_choice, bunk_id) VALUES (2, 3, "Johann Sebastian", "Bach", "dlobron@gmail.com", 1, 2);
# Assign campers to the same session, so we can test assignment
INSERT INTO campers (edah_id, session_id, first, last, email, bunk_id) VALUES (2, 3, "Hector", "Berlioz", "dlobron@gmail.com", 2);
INSERT INTO campers (edah_id, session_id, first, last, email, bunk_id) VALUES (2, 3, "Carl", "Nielsen", "dlobron@gmail.com", 2);
INSERT INTO campers (edah_id, session_id, first, last, email, bunk_id) VALUES (2, 3, "Guiseppe", "Verdi", "dlobron@gmail.com", 2);
INSERT INTO campers (edah_id, session_id, first, last, email, bunk_id) VALUES (2, 3, "Franz", "Schubert", "dlobron@gmail.com", 3);
INSERT INTO campers (edah_id, session_id, first, last, email, bunk_id) VALUES (2, 3, "Robert", "Schumann", "dlobron@gmail.com", 3);
INSERT INTO campers (edah_id, session_id, first, last, email, bunk_id) VALUES (2, 3, "Clara", "Schumann", "MyFavoriteMartian@trex.com", 4);
INSERT INTO campers (edah_id, session_id, first, last, email, bunk_id) VALUES (3, 2, "Johannes", "Brahms", "MyFavoriteMartian@trex.com", 4);

# Insert some chugim.  Vary the case, to verify we compare case-insensitively
# aleph chugim
INSERT INTO chugim (name, group_id, min_size, max_size, description) VALUES ("Swimming", 1, 0, 2, "Playing in the water");
INSERT INTO chugim (name, group_id, min_size, max_size, description) VALUES ("Krav Maga", 1, 2, 2, "Israeli martial art");
INSERT INTO chugim (name, group_id, min_size, max_size, description) VALUES ("Cooking", 1, 2, 2, "Making food");
INSERT INTO chugim (name, group_id, min_size, max_size, description) VALUES ("Boating", 1, 2, 0, "Kayaking and suchlike");
INSERT INTO chugim (name, group_id, min_size, max_size, description) VALUES ("Ropes", 1, 1, 1, "Awesome climbs on fun rock");
INSERT INTO chugim (name, group_id, min_size, max_size, description) VALUES ("Archery", 1, 3, 0, "Bow and arrow");
# bet chugim
INSERT INTO chugim (name, group_id, min_size, max_size, description) VALUES ("Paddling", 2, 1, 1, "SUP and kayaking");
INSERT INTO chugim (name, group_id, min_size, max_size, description) VALUES ("Outdoor Cooking", 2, 1, 1, "Smores and such");
INSERT INTO chugim (name, group_id, min_size, max_size, description) VALUES ("Israeli Dance", 2, 1, 2, "Also known as rikud");
INSERT INTO chugim (name, group_id, min_size, max_size, description) VALUES ("Canoeing", 2, 3, 3, "Messing about in boats");
INSERT INTO chugim (name, group_id, min_size, max_size, description) VALUES ("Archery", 2, 2, 3, "Bow and arrow");
INSERT INTO chugim (name, group_id, min_size, max_size, description) VALUES ("Omanut", 2, 2, 2, "Art");
# gimel chugim
INSERT INTO chugim (name, group_id, min_size, max_size, description) VALUES ("Jogging", 3, 2, 2, "Marathon training");
INSERT INTO chugim (name, group_id, min_size, max_size, description) VALUES ("outdoor cooking", 3, 2, 2, "Smores and such, with lowercase");
INSERT INTO chugim (name, group_id, min_size, max_size, description) VALUES ("Israeli Dance", 3, 1, 2, "Also known as rikud");
INSERT INTO chugim (name, group_id, min_size, max_size, description) VALUES ("Etgar", 3, 1, 2, "Avoid angering the bears");
INSERT INTO chugim (name, group_id, min_size, max_size, description) VALUES ("Advanced Ivrit", 3, 2, 3, "Hu is he, hi is she");
INSERT INTO chugim (name, group_id, min_size, max_size, description) VALUES ("Omanut", 3, 2, 2, "Art and sculpture");

INSERT INTO chug_instances(chug_id, block_id) VALUES (1, 1);
INSERT INTO chug_instances(chug_id, block_id) VALUES (2, 1);
INSERT INTO chug_instances(chug_id, block_id) VALUES (3, 1);
INSERT INTO chug_instances(chug_id, block_id) VALUES (4, 1);
INSERT INTO chug_instances(chug_id, block_id) VALUES (5, 1);
INSERT INTO chug_instances(chug_id, block_id) VALUES (6, 1);
INSERT INTO chug_instances(chug_id, block_id) VALUES (7, 1);
INSERT INTO chug_instances(chug_id, block_id) VALUES (8, 1);
INSERT INTO chug_instances(chug_id, block_id) VALUES (9, 1);
INSERT INTO chug_instances(chug_id, block_id) VALUES (10, 1);
INSERT INTO chug_instances(chug_id, block_id) VALUES (11, 1);
INSERT INTO chug_instances(chug_id, block_id) VALUES (12, 1);
INSERT INTO chug_instances(chug_id, block_id) VALUES (13, 1);
INSERT INTO chug_instances(chug_id, block_id) VALUES (14, 1);
INSERT INTO chug_instances(chug_id, block_id) VALUES (15, 1);
INSERT INTO chug_instances(chug_id, block_id) VALUES (16, 1);
INSERT INTO chug_instances(chug_id, block_id) VALUES (17, 1);
INSERT INTO chug_instances(chug_id, block_id) VALUES (18, 1);

INSERT INTO chug_instances(chug_id, block_id) VALUES (1, 2);
INSERT INTO chug_instances(chug_id, block_id) VALUES (2, 2);
INSERT INTO chug_instances(chug_id, block_id) VALUES (3, 2);
INSERT INTO chug_instances(chug_id, block_id) VALUES (4, 2);
INSERT INTO chug_instances(chug_id, block_id) VALUES (5, 2);
INSERT INTO chug_instances(chug_id, block_id) VALUES (6, 2);
INSERT INTO chug_instances(chug_id, block_id) VALUES (7, 2);
INSERT INTO chug_instances(chug_id, block_id) VALUES (8, 2);
INSERT INTO chug_instances(chug_id, block_id) VALUES (9, 2);
INSERT INTO chug_instances(chug_id, block_id) VALUES (10, 2);
INSERT INTO chug_instances(chug_id, block_id) VALUES (11, 2);
INSERT INTO chug_instances(chug_id, block_id) VALUES (12, 2);
INSERT INTO chug_instances(chug_id, block_id) VALUES (13, 2);
INSERT INTO chug_instances(chug_id, block_id) VALUES (14, 2);
INSERT INTO chug_instances(chug_id, block_id) VALUES (15, 2);
INSERT INTO chug_instances(chug_id, block_id) VALUES (16, 2);
INSERT INTO chug_instances(chug_id, block_id) VALUES (17, 2);
INSERT INTO chug_instances(chug_id, block_id) VALUES (18, 2);

INSERT INTO chug_instances(chug_id, block_id) VALUES (1, 3);
INSERT INTO chug_instances(chug_id, block_id) VALUES (2, 3);
INSERT INTO chug_instances(chug_id, block_id) VALUES (3, 3);
INSERT INTO chug_instances(chug_id, block_id) VALUES (4, 3);
INSERT INTO chug_instances(chug_id, block_id) VALUES (5, 3);
INSERT INTO chug_instances(chug_id, block_id) VALUES (6, 3);
INSERT INTO chug_instances(chug_id, block_id) VALUES (7, 3);
INSERT INTO chug_instances(chug_id, block_id) VALUES (8, 3);
INSERT INTO chug_instances(chug_id, block_id) VALUES (9, 3);
INSERT INTO chug_instances(chug_id, block_id) VALUES (10, 3);
INSERT INTO chug_instances(chug_id, block_id) VALUES (11, 3);
INSERT INTO chug_instances(chug_id, block_id) VALUES (12, 3);
INSERT INTO chug_instances(chug_id, block_id) VALUES (13, 3);
INSERT INTO chug_instances(chug_id, block_id) VALUES (14, 3);
INSERT INTO chug_instances(chug_id, block_id) VALUES (15, 3);
INSERT INTO chug_instances(chug_id, block_id) VALUES (16, 3);
INSERT INTO chug_instances(chug_id, block_id) VALUES (17, 3);
INSERT INTO chug_instances(chug_id, block_id) VALUES (18, 3);

# Insert prefs for all campers for block 1, for each group.
INSERT INTO preferences (camper_id, group_id, block_id, first_choice_id, second_choice_id, third_choice_id, fourth_choice_id, fifth_choice_id, sixth_choice_id) VALUES (1, 1, 1, NULL, NULL, NULL, NULL, NULL, NULL);
INSERT INTO preferences (camper_id, group_id, block_id, first_choice_id, second_choice_id, third_choice_id, fourth_choice_id, fifth_choice_id, sixth_choice_id) VALUES (2, 1, 1, 4, 2, 3, 1, 5, 6);
INSERT INTO preferences (camper_id, group_id, block_id, first_choice_id, second_choice_id, third_choice_id, fourth_choice_id, fifth_choice_id, sixth_choice_id) VALUES (3, 1, 1, 4, 1, 2, 3, 5, 6);
INSERT INTO preferences (camper_id, group_id, block_id, first_choice_id, second_choice_id, third_choice_id, fourth_choice_id, fifth_choice_id, sixth_choice_id) VALUES (4, 1, 1, 4, 5, 2, 6, NULL, NULL);
INSERT INTO preferences (camper_id, group_id, block_id, first_choice_id, second_choice_id, third_choice_id, fourth_choice_id, fifth_choice_id, sixth_choice_id) VALUES (5, 1, 1, 4, 1, 2, 6, NULL, NULL);
INSERT INTO preferences (camper_id, group_id, block_id, first_choice_id, second_choice_id, third_choice_id, fourth_choice_id, fifth_choice_id, sixth_choice_id) VALUES (6, 1, 1, 4, 2, 5, 3, 1, NULL);
INSERT INTO preferences (camper_id, group_id, block_id, first_choice_id, second_choice_id, third_choice_id, fourth_choice_id, fifth_choice_id, sixth_choice_id) VALUES (7, 1, 1, 4, 2, 5, 3, 1, NULL);
INSERT INTO preferences (camper_id, group_id, block_id, first_choice_id, second_choice_id, third_choice_id, fourth_choice_id, fifth_choice_id, sixth_choice_id) VALUES (8, 1, 1, 4, 2, 5, 3, 1, NULL);
INSERT INTO preferences (camper_id, group_id, block_id, first_choice_id, second_choice_id, third_choice_id, fourth_choice_id, fifth_choice_id, sixth_choice_id) VALUES (9, 1, 1, 4, 2, 5, 1, 6, NULL);
INSERT INTO preferences (camper_id, group_id, block_id, first_choice_id, second_choice_id, third_choice_id, fourth_choice_id, fifth_choice_id, sixth_choice_id) VALUES (10, 1, 1, 5, 4, 3, 2, 1, 6);

INSERT INTO preferences (camper_id, group_id, block_id, first_choice_id, second_choice_id, third_choice_id, fourth_choice_id, fifth_choice_id, sixth_choice_id) VALUES (1, 2, 1, 7, 8, 9, 10, 11, 12);
INSERT INTO preferences (camper_id, group_id, block_id, first_choice_id, second_choice_id, third_choice_id, fourth_choice_id, fifth_choice_id, sixth_choice_id) VALUES (2, 2, 1, 7, 9, 8, 10, 11, 12);
INSERT INTO preferences (camper_id, group_id, block_id, first_choice_id, second_choice_id, third_choice_id, fourth_choice_id, fifth_choice_id, sixth_choice_id) VALUES (3, 2, 1, 7, 8, 9, 10, 11, NULL);
INSERT INTO preferences (camper_id, group_id, block_id, first_choice_id, second_choice_id, third_choice_id, fourth_choice_id, fifth_choice_id, sixth_choice_id) VALUES (4, 2, 1, 7, 9, 8, 10, 11, NULL);
INSERT INTO preferences (camper_id, group_id, block_id, first_choice_id, second_choice_id, third_choice_id, fourth_choice_id, fifth_choice_id, sixth_choice_id) VALUES (5, 2, 1, 7, 8, 9, 10, 11, NULL);
INSERT INTO preferences (camper_id, group_id, block_id, first_choice_id, second_choice_id, third_choice_id, fourth_choice_id, fifth_choice_id, sixth_choice_id) VALUES (6, 2, 1, 7, 9, 8, 10, 11, 12);
INSERT INTO preferences (camper_id, group_id, block_id, first_choice_id, second_choice_id, third_choice_id, fourth_choice_id, fifth_choice_id, sixth_choice_id) VALUES (7, 2, 1, 7, 8, 9, 10, 11, 12);
INSERT INTO preferences (camper_id, group_id, block_id, first_choice_id, second_choice_id, third_choice_id, fourth_choice_id, fifth_choice_id, sixth_choice_id) VALUES (8, 2, 1, 7, 9, 8, 10, 11, 12);
INSERT INTO preferences (camper_id, group_id, block_id, first_choice_id, second_choice_id, third_choice_id, fourth_choice_id, fifth_choice_id, sixth_choice_id) VALUES (9, 2, 1, 7, 8, 9, 10, 11, NULL);
INSERT INTO preferences (camper_id, group_id, block_id, first_choice_id, second_choice_id, third_choice_id, fourth_choice_id, fifth_choice_id, sixth_choice_id) VALUES (10, 2, 1, 11, 10, 9, 8, 7, 12);

INSERT INTO preferences (camper_id, group_id, block_id, first_choice_id, second_choice_id, third_choice_id, fourth_choice_id, fifth_choice_id, sixth_choice_id) VALUES (1, 3, 1, 13, 14, 15, 16, 17, 18);
INSERT INTO preferences (camper_id, group_id, block_id, first_choice_id, second_choice_id, third_choice_id, fourth_choice_id, fifth_choice_id, sixth_choice_id) VALUES (2, 3, 1, 13, 14, 15, 16, 17, 18);
INSERT INTO preferences (camper_id, group_id, block_id, first_choice_id, second_choice_id, third_choice_id, fourth_choice_id, fifth_choice_id, sixth_choice_id) VALUES (3, 3, 1, 13, 14, 15, 16, 17, 18);
INSERT INTO preferences (camper_id, group_id, block_id, first_choice_id, second_choice_id, third_choice_id, fourth_choice_id, fifth_choice_id, sixth_choice_id) VALUES (4, 3, 1, 13, 14, 15, 16, 17, 18);
INSERT INTO preferences (camper_id, group_id, block_id, first_choice_id, second_choice_id, third_choice_id, fourth_choice_id, fifth_choice_id, sixth_choice_id) VALUES (5, 3, 1, 13, 14, 15, 16, 17, 18);
INSERT INTO preferences (camper_id, group_id, block_id, first_choice_id, second_choice_id, third_choice_id, fourth_choice_id, fifth_choice_id, sixth_choice_id) VALUES (6, 3, 1, 13, 14, 15, 16, 17, 18);
INSERT INTO preferences (camper_id, group_id, block_id, first_choice_id, second_choice_id, third_choice_id, fourth_choice_id, fifth_choice_id, sixth_choice_id) VALUES (7, 3, 1, 13, 14, 15, 16, 17, 18);
INSERT INTO preferences (camper_id, group_id, block_id, first_choice_id, second_choice_id, third_choice_id, fourth_choice_id, fifth_choice_id, sixth_choice_id) VALUES (8, 3, 1, 13, 14, 15, 16, 17, 18);
INSERT INTO preferences (camper_id, group_id, block_id, first_choice_id, second_choice_id, third_choice_id, fourth_choice_id, fifth_choice_id, sixth_choice_id) VALUES (9, 3, 1, 13, 14, 15, 16, 17, 18);
INSERT INTO preferences (camper_id, group_id, block_id, first_choice_id, second_choice_id, third_choice_id, fourth_choice_id, fifth_choice_id, sixth_choice_id) VALUES (10, 3, 1, 17, 16, 15, 14, 13, 18);
