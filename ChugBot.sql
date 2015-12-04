# To load this file:
# source <path-to-file>
# Remember to remove test data from the end of this file before loading for production use.

# Create the database
CREATE DATABASE IF NOT EXISTS chugbot_db COLLATE utf8_unicode_ci;

# Create a user for the chugbot program (if it does not already exist), and
# grant the access it needs.
GRANT INSERT,SELECT,UPDATE,DELETE ON chugbot_db.* TO 'chugbot'@'localhost' IDENTIFIED BY 'chugbot';

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
chug_id int NOT NULL AUTO_INCREMENT PRIMARY KEY,
UNIQUE KEY uk_chugim(name))
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

# This table matches registered campers to chug instances.
# This table will be the source for the view/edit page.  The results of the assignment algorithm should
# be insert-able into this table, after possibly editing.  Everything in this table is a foreign key.  Updates
# and deletes cascade, because assignments depend on the camp structure.
CREATE TABLE assignments(
camper_id int NOT NULL,
FOREIGN KEY fk_camper_id(camper_id) REFERENCES campers(camper_id)
ON DELETE CASCADE
ON UPDATE CASCADE,
chug_instance_id int NOT NULL,
FOREIGN KEY chug_instance_id_fk(chug_instance_id) REFERENCES chug_instances(chug_instance_id)
ON DELETE CASCADE
ON UPDATE CASCADE,
PRIMARY KEY(camper_id, chug_instance_id))
COLLATE utf8_unicode_ci;

# Insert starter data for testing.
INSERT INTO sessions (name) VALUES ("July");
INSERT INTO sessions (name) VALUES ("August");
INSERT INTO sessions (name) VALUES ("July and August");
INSERT INTO sessions (name) VALUES ("Mini Aleph");

INSERT INTO blocks (name) VALUES ("July 1");
INSERT INTO blocks (name) VALUES ("August 2");

INSERT INTO block_instances (block_id, session_id) VALUES (1, 1);
INSERT INTO block_instances (block_id, session_id) VALUES (2, 2);
INSERT INTO block_instances (block_id, session_id) VALUES (1, 3);
INSERT INTO block_instances (block_id, session_id) VALUES (2, 3);

INSERT INTO edot (name) VALUES ("Kochavim");
INSERT INTO edot (name) VALUES ("Ilanot 1");
INSERT INTO edot (name) VALUES ("Ilanot 2");
INSERT INTO edot (name) VALUES ("Solelim");

INSERT INTO groups (name) VALUES ("aleph");
INSERT INTO groups (name) VALUES ("bet");
INSERT INTO groups (name) VALUES ("gimel");

INSERT INTO campers (edah_id, session_id, first, last, email) VALUES (1, 1, "Elena", "TheGreat", "dlobron@gmail.com");
INSERT INTO campers (edah_id, session_id, first, last, email) VALUES (2, 2, "Robin", "EconomistGirl", "dlobron@gmail.com");

INSERT INTO chugim (name, group_id, min_size, max_size) VALUES ("Swimming", 1, 5, 10);
INSERT INTO chugim (name, group_id, min_size, max_size)	VALUES ("Krav Maga", 1, 3, 15);

INSERT INTO chug_instances(chug_id, block_id) VALUES (1, 1);
INSERT INTO chug_instances(chug_id, block_id) VALUES (2, 2);
