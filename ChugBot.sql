# To load this file:
# source <path-to-file>
# Note that this file only contains table structure.  To load a database with
# sample data for testing, use ChugBotWithData.sql (for local development), or
# ChugBotWithDataProdVers.sql (for AWS development).

# Create the database
CREATE DATABASE IF NOT EXISTS camprama_chugbot_db COLLATE utf8_unicode_ci;

# Create a user for the chugbot program (if it does not already exist), and
# grant the access it needs.
CREATE USER IF NOT EXISTS 'camprama_chugbot'@'localhost' IDENTIFIED BY '$2y$10$BqkFi/IXwXz5aIr9FKYjMu8W75kqvBbI3l5nSxJ.LK6hEabIZpJDG';
GRANT CREATE,INSERT,SELECT,UPDATE,DELETE ON camprama_chugbot_db.* TO 'camprama_chugbot'@'localhost';

# Switch to the new database, in preparation for creating tables.
USE camprama_chugbot_db;

# Create a table to hold admin data.  The ISP for CRNE tells us to create an email account in cPanel
# use the full email as the username and the email account password as the password.  The default encrypted
# password is "kayitz" without the quotes.
CREATE TABLE IF NOT EXISTS admin_data(
admin_email varchar(50) NOT NULL,
admin_password varchar(255) NOT NULL,
admin_email_cc varchar(255),
admin_email_from_name varchar(255),
send_confirm_email boolean NOT NULL DEFAULT 1,
chug_term_singular varchar(255) NOT NULL DEFAULT 'chug',
chug_term_plural varchar(255) NOT NULL DEFAULT 'chugim',
block_term_singular varchar(255) NOT NULL DEFAULT 'block',
block_term_plural varchar(255) NOT NULL DEFAULT 'blocks',
pref_count int NOT NULL DEFAULT 6,
regular_user_token varchar(255) NOT NULL DEFAULT 'kayitz',
regular_user_token_hint varchar(512) DEFAULT 'Hebrew word for summer',
pref_page_instructions varchar(2048) DEFAULT '&lt;h3&gt;How to Make Your Choices:&lt;/h3&gt;&lt;ol&gt;&lt;li&gt;For each time period, choose six Chugim, and drag them from the left column to the right column.  Hover over a Chug name in the left box to see a brief description.  If you have existing preferences, they will be pre-loaded in the right box: you can reorder or remove them as needed.&lt;/li&gt;&lt;li&gt;Use your mouse to drag the right column into order of preference, from top (first choice) to bottom (last choice).&lt;/li&gt;&lt;li&gt;When you have arranged preferences for all your time periods, click &lt;font color=&quot;green&quot;&gt;Submit&lt;/font&gt;.&lt;/li&gt;&lt;/ol&gt;',
camp_name varchar(255) NOT NULL DEFAULT 'Camp Ramah New England',
camp_web varchar(128) NOT NULL DEFAULT 'www.campramahne.org')
COLLATE utf8_unicode_ci
ENGINE = INNODB;

# Admin password reset codes, with expiration.
CREATE TABLE IF NOT EXISTS password_reset_codes(
code varchar(512) NOT NULL,
expires DATETIME NOT NULL,
code_id int NOT NULL AUTO_INCREMENT PRIMARY KEY)
COLLATE utf8_unicode_ci
ENGINE = INNODB;

# This table holds sessions, e.g., "July", "August", "Full Summer", "Mini Bet", etc.
CREATE TABLE IF NOT EXISTS sessions(
session_id int NOT NULL AUTO_INCREMENT PRIMARY KEY,
name varchar(50) NOT NULL,
UNIQUE KEY uk_sessions(name))
COLLATE utf8_unicode_ci
ENGINE = INNODB;

# A block is a division of a session, e.g.,
# "July 1" or "August 2".
CREATE TABLE IF NOT EXISTS blocks(
block_id int NOT NULL AUTO_INCREMENT PRIMARY KEY,
name varchar(50) NOT NULL,
visible_to_campers boolean NOT NULL DEFAULT 0,
UNIQUE KEY uk_blocks(name))
COLLATE utf8_unicode_ci
ENGINE = INNODB;

# A block instance is a block/session tuple.  We use this table to
# translate the session(s) for which a camper signs up with the blocks that she
# should be assigned for.  For example, campers in the July session need
# assignments for the July 1 block, and so do campers signed up for
# July + August and Mini Aleph.  In theory, we could ask campers to just
# indicate the blocks they are signed up for, but they sign up for things
# in terms of sessions.
CREATE TABLE IF NOT EXISTS block_instances(
block_id int NOT NULL,
FOREIGN KEY fk_block_id(block_id) REFERENCES blocks(block_id)
ON DELETE CASCADE
ON UPDATE CASCADE,
session_id int,
FOREIGN KEY fk_session_id(session_id) REFERENCES sessions(session_id)
ON DELETE CASCADE
ON UPDATE CASCADE,
PRIMARY KEY pk_block_instances(block_id, session_id))
COLLATE utf8_unicode_ci
ENGINE = INNODB;

# List all edot (Kochavim, Ilanot 1, Ilanot 2, etc).
CREATE TABLE IF NOT EXISTS edot(
edah_id int NOT NULL AUTO_INCREMENT PRIMARY KEY,
name varchar(50) NOT NULL,
rosh_name varchar(100) DEFAULT "",
rosh_phone varchar(20) DEFAULT "",
comments varchar(512) DEFAULT "",
sort_order int DEFAULT 0,
UNIQUE KEY uk_edot(name))
COLLATE utf8_unicode_ci
ENGINE = INNODB;

# Create a table of bunks.  Campers are optionally assigned to one bunk
# for the summer, which can be changed as needed on the edit camper page.
CREATE TABLE IF NOT EXISTS bunks(
bunk_id int NOT NULL AUTO_INCREMENT PRIMARY KEY,
name varchar(50) NOT NULL,
UNIQUE KEY uk_bunks(name))
COLLATE utf8_unicode_ci
ENGINE = INNODB;

# A bunk instance is an assignment of bunk to edah.
CREATE TABLE IF NOT EXISTS bunk_instances(
bunk_id int NOT NULL,
FOREIGN KEY fk_bunk_id(bunk_id) REFERENCES bunks(bunk_id)
ON DELETE CASCADE
ON UPDATE CASCADE,
edah_id int NOT NULL,
FOREIGN KEY fk_edah_id(edah_id) REFERENCES edot(edah_id)
ON DELETE CASCADE
ON UPDATE CASCADE,
PRIMARY KEY pk_bunk_instances(bunk_id, edah_id))
COLLATE utf8_unicode_ci
ENGINE = INNODB;

# This table stores camper registration for the summer.  Each
# camper signs up for one edah in a summer, and they choose
# a session.
CREATE TABLE IF NOT EXISTS campers(
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
COLLATE utf8_unicode_ci
ENGINE = INNODB;

# Each chug instance is assigned to a group for the whole summer.
# For example, swimming might be in group aleph.
CREATE TABLE IF NOT EXISTS chug_groups(
group_id int NOT NULL AUTO_INCREMENT PRIMARY KEY,
name varchar(50) NOT NULL, # aleph, bet, or gimel
UNIQUE KEY uk_groups(name))
COLLATE utf8_unicode_ci
ENGINE = INNODB;

# This table holds data on each chug.  Each chug belongs to exactly one group (aleph, bet, or gimel), and
# the group is consistent across all edot for the whole summer.  We assume for now
# that all chugim are offered in all sessions to all edot, and that size limits are consistent for all
# edot and sessions.
# To check: I think that chugim with the same name can exist in more than one group (for example, Swimming aleph,
# Swimming bet).
CREATE TABLE IF NOT EXISTS chugim(
name varchar(50) NOT NULL,
group_id int,
FOREIGN KEY fk_group(group_id) REFERENCES chug_groups(group_id)
ON DELETE SET NULL
ON UPDATE CASCADE,
max_size int NULL,
min_size int NULL DEFAULT 0,
description varchar(2048),
UNIQUE KEY uk_chugim(name, group_id),
chug_id int NOT NULL AUTO_INCREMENT PRIMARY KEY)
COLLATE utf8_unicode_ci
ENGINE = INNODB;

# The next table maps a chug name to another chug name.  Its purpose is to prevent us
# from pairing certain chugim to the same camper in the same block (we do this de-dup automatically
# for the same chug).  For example, we might not want to assign both Cooking and Outdoor Cooking.
CREATE TABLE IF NOT EXISTS chug_dedup_instances_v2(
left_chug_id int NOT NULL,
FOREIGN KEY fk_left_chug_id(left_chug_id) REFERENCES chugim(chug_id)
ON DELETE CASCADE
ON UPDATE CASCADE,
right_chug_id int NOT NULL,
FOREIGN KEY fk_right_chug_id(right_chug_id) REFERENCES chugim(chug_id)
ON DELETE CASCADE
ON UPDATE CASCADE)
COLLATE utf8_unicode_ci
ENGINE = INNODB;

# A chug instance is a concrete offering of a chug in a block.
# For example, swimming, July first week.  Note that the chugim
# themselves are assigned to groups, so an instance also includes
# the group (aleph, bet or gimel).
CREATE TABLE IF NOT EXISTS chug_instances(
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
COLLATE utf8_unicode_ci
ENGINE = INNODB;

# Each entry in this table represents a camper preference list for a given group of chugim in a
# given block.  For example, a camper would make a pref list for the aleph chugim in July, first week.
# Up to 6 choices are allowed for each group/block tuple.
CREATE TABLE IF NOT EXISTS preferences(
camper_id int NOT NULL,
FOREIGN KEY fk_camper_id(camper_id) REFERENCES campers(camper_id)
ON DELETE CASCADE
ON UPDATE CASCADE,
group_id int NOT NULL, # aleph, bet, or gimel
FOREIGN KEY fk_group_id(group_id) REFERENCES chug_groups(group_id)
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
UNIQUE KEY(camper_id, group_id, block_id),
preference_id int NOT NULL AUTO_INCREMENT PRIMARY KEY)
COLLATE utf8_unicode_ci
ENGINE = INNODB;

# This table holds matches of campers to chugim.  A match is for one
# camper to an instance of a chug.  Chugim are associated with groups,
# and instances have a chug and a block, so a match associates a camper
# with an activity for a group and block.  For example, a match could
# be: Shira -> Climbing, aleph, July 1.
CREATE TABLE IF NOT EXISTS matches(
camper_id int NOT NULL,
FOREIGN KEY fk_camper_id(camper_id) REFERENCES campers(camper_id)
ON DELETE CASCADE
ON UPDATE CASCADE,
chug_instance_id int NOT NULL,
FOREIGN KEY fk_chug_instance_id(chug_instance_id) REFERENCES chug_instances(chug_instance_id)
ON DELETE CASCADE
ON UPDATE CASCADE,
UNIQUE KEY uk_matches(camper_id, chug_instance_id),
match_id int NOT NULL AUTO_INCREMENT PRIMARY KEY)
COLLATE utf8_unicode_ci
ENGINE = INNODB;

CREATE TABLE IF NOT EXISTS edot_for_chug(
chug_id int NOT NULL,
FOREIGN KEY fk_chug_id(chug_id) REFERENCES chugim(chug_id)
ON DELETE CASCADE
ON UPDATE CASCADE,
edah_id int NOT NULL,
FOREIGN KEY fk_edah_id(edah_id) REFERENCES edot(edah_id)
ON DELETE CASCADE
ON UPDATE CASCADE,
PRIMARY KEY pk_edot_for_chug(chug_id, edah_id))
COLLATE utf8_unicode_ci
ENGINE = INNODB;

CREATE TABLE IF NOT EXISTS edot_for_block(
block_id int NOT NULL,
FOREIGN KEY fk_block_id(block_id) REFERENCES blocks(block_id)
ON DELETE CASCADE
ON UPDATE CASCADE,
edah_id int NOT NULL,
FOREIGN KEY fk_edah_id(edah_id) REFERENCES edot(edah_id)
ON DELETE CASCADE
ON UPDATE CASCADE,
PRIMARY KEY pk_edot_for_block(block_id, edah_id))
COLLATE utf8_unicode_ci
ENGINE = INNODB;

CREATE TABLE IF NOT EXISTS edot_for_group(
group_id int NOT NULL,
FOREIGN KEY fk_group_id(group_id) REFERENCES chug_groups(group_id)
ON DELETE CASCADE
ON UPDATE CASCADE,
edah_id int NOT NULL,
FOREIGN KEY fk_edah_id(edah_id) REFERENCES edot(edah_id)
ON DELETE CASCADE
ON UPDATE CASCADE,
PRIMARY KEY pk_edot_for_group(group_id, edah_id))
COLLATE utf8_unicode_ci
ENGINE = INNODB;

# For safety, determine which category tables, such as blocks and groups,
# may have items deleted.
CREATE TABLE IF NOT EXISTS category_tables(
name varchar(50) NOT NULL,
category_table_id int NOT NULL AUTO_INCREMENT PRIMARY KEY,
delete_ok bool NOT NULL DEFAULT 1)
COLLATE utf8_unicode_ci
ENGINE = INNODB;
# Enter default values.
INSERT INTO category_tables (name, delete_ok) VALUES ("blocks", 0);
INSERT INTO category_tables (name, delete_ok) VALUES ("bunks", 0);
INSERT INTO category_tables (name, delete_ok) VALUES ("campers", 1);
INSERT INTO category_tables (name, delete_ok) VALUES ("chugim", 1);
INSERT INTO category_tables (name, delete_ok) VALUES ("edot", 0);
INSERT INTO category_tables (name, delete_ok) VALUES ("chug_groups", 0);
INSERT INTO category_tables (name, delete_ok) VALUES ("sessions", 0);