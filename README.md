# ChugBot
Assign summer campers to their preferred activities using a modified version of the stable marriage problem.

ChugBot is a PHP/MySQL Web application.  It's designed for use by my kids' summer camp.  The camp staff uses the administrative pages to add activities for the upcoming summer sessions.  Activities are divided into groups, and groups are assigned in 2-week blocks.  Each activity has a maximum and minimum capacity.  The design favors ease-of-use over security, and should **not** be used for security-sensitive data: please see below for more about security.

Campers log into a camper view, and select their preferred activities.  The list of activities they see is determined by the session for which they have signed up: each session includes one or more blocks.  The campers rank their preferred activities in order of preference.  Once the rankings have been collected from all campers, the camp staff uses the "leveling" page to automatically assign campers to activities.  This page uses a variant of the Gale/Shapely Stable Marriage algorithm to do the assignment.  In our case, the assignment is not truly "stable," because preferences only go in one direction (from campers to activity, but not vice-versa).  However, we use the one-directional nature of the problem to our advantage by introducing a "fairness" bias: an activity will "prefer" campers who got a less-good assignment on a previous round.  The goal is to do an optimal assignment while also being as fair as possible.

Camp staff always have the ability to override the assignment.  In fact, the assignment should be treated as a "suggestion," which the camp staff will refine.

The application assumes that you have PHP installed in your webserver, and that you have access to a MySQL database.  To set things up, follow these instructions:

1. Enter the MySQL command line as root, and run "source ChugBot.sql". Alternately, you can cut-and-paste the file into a database admin window.  You may have to change the name of the database to fit your ISP's naming conventions.  The default database name is "camprama_chugbot_db".  Make sure to grant database permissions to the user that this program will run as: I recommend using an admin window for this.
2. Because some ISPs do not allow PHP scripts to create databases, I recommend manually creating archive databases for the next few years.  This program expects the archive database for a given year to have the same name as the database, with the year appended.  So if your database is called camprama_chugbot_db, the archive for summer 2016 would be called camprama_chugbot_db2016.  I suggest creating camprama_chugbot_dbYEAR databases for the next couple of years.
3. Update constants.php with the login information for your MySQL database and your email account, the database user for this program, and the path to your mysql and mysqldump binaries.  If your ISP's email authentication is broken, you might also need to add the following to the sendMail function in functions.php (please see https://github.com/PHPMailer/PHPMailer/wiki/Troubleshooting for details):

    $mail->SMTPOptions = array(
        'ssl' => array(
            'verify_peer' => false,
            'verify_peer_name' => false,
            'allow_self_signed' => true
        )
    );
    
4. Copy the contents of the "chugbot" directory to the directory where you want the website to run.  For example, if your webserver root is /home/web/htdocs (assuming a Unix-like directory structure), and you want this application to appear in a browser as mycamp.org/leveling/, you should copy these files to /home/web/htdocs/leveling/.
5. In order for ChugBot to archive the current summer's data, the MYSQL_USER defined in constants.php needs permission to create a backup database.  If you do not want the user to have permission to create a database, then you should use your ISP's database admin tool (or the mysql command-line tool) to create a database called DB2016, where DB is the name of your main database from step (1).  For example, if your database is called camprama_chugbot_db, and the current summer year is 2016, you would create camprama_chugbot_db2016.

That's it!  You should now be able to use the admin staff pages to add groups, blocks, activities, and groups.  Campers can log into the camper view to add or modify their preferences.  Note that when you first log in as the administrator, you will be prompted to enter an admin email and password.  Campers do not need a password: they use a plain text token for access.  If campers need to modify their choices after entering them, they identify themselves with their email address.  

**Important**: this design obviously favors ease of use over security.  It's trivial for one camper to impersonate another, or for someone to view or modify any camper's choices or registration data.  If your data is considered sensitive, then additional security **must** be added.  The admin staff section is password-protected, but even this depends on the security of your hosting provider, e.g., whether TLS encryption is used across the site.  I'm not a security professional, so if you have security concerns, please consult a qualified person.

The name "ChugBot" comes from the Hebrew word "chug", pronounced "HOOG", which means "circle" or "camp activity group." Some of the terms in the application are also transliterated Hebrew (our kids attend a Jewish summer camp).  Feel free to change these for your camp, or keep them as-is.  A quick glossary of terms:

- chug ("HOOG"): An individual activity (plural: chugim). (חוּג)
- edah ("Eh-DAH"): A grouping of campers (plural: edot). (עֵדָה)
- group: A group of activities.
- session: A session for which campers can register, such as July or August.
- block: A time block within a session, such as July 1 or August 2.  This is the unit within which the bot does camper/chug assignment.

This project is affectionately dedicated to Danny Lewin (1970-2001), co-founder of [Akamai Technologies](http://www.akamai.com), whose algorithms were an inspiration for this work (the code here only uses the publicly-available Gale-Shapely algorithm).

My daughter drew a picture of what the bot might look like in real life.  He's pretty cool:

![bot image](chugbot/images/ChugBot.JPG?raw=true)
