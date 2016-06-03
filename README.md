# ChugBot
A website that assigns campers to activities using a modified version of the stable marriage problem.

ChugBot is a PHP/MySQL Web application.  It's designed for use by my kids' summer camp.  The camp staff uses the administrative pages to add activities for the upcoming summer sessions.  Activities are divided into groups, and groups are assigned in 2-week blocks.  Each activity has a maximum and minimum capacity.

The campers log into a camper view, and select their preferred activities.  The list of activities they see is determined by the session for which they have signed up: each session includes one or more blocks.  The campers rank their preferred activities in order of preference.  Once the rankings have been collected from all campers, the camp staff uses the "leveling" page to automatically assign
campers to activities.  This page uses a variant of the Gale/Shapely Stable Marriage algorithm to do the assignment.  In our case, the
assignment is not truly "stable," because preferences only go in one direction (from campers to activity, but not vice-versa).  However, we use the one-directional nature of the problem to our advantage by introducing a "fairness" bias: an activity will "prefer" campers who got a less-good assignment on a previous round.  The goal is to do an optimal assignment while also being as fair as possible.

Since the bot cannot know all the details of each camper and activity, the camp staff have the ability to override the assignment.  In
fact, the assignment should be treated as a "suggestion," which the camp staff will refine.

The application assumes that you have PHP installed in your webserver, and that you have access to a MySQL database.  To set things up,
please follow these instructions:

1. Enter the MySQL command line as root, and run "source ChugBot.sql".  Note that there is a line in the SQL that pulls in sample data: you should comment that out.
Alternately, you can enter the database commands into an admin window.  You may have to change the name of the database to fit your ISP's conventions.  The default database name is "camprama_chugbot_db".

2. Update constants.php with the login information for your MySQL database and your email account.  If your ISP's email authentication is broken, you might also need to add the following to the sendMail function in functions.php (please see https://github.com/PHPMailer/PHPMailer/wiki/Troubleshooting for details):

    $mail->SMTPOptions = array(
        'ssl' => array(
            'verify_peer' => false,
            'verify_peer_name' => false,
            'allow_self_signed' => true
        )
    );

3. Copy the contents of the "chugbot" directory to the directory where you want the website to run.  For example, if your webserver
root is /home/web/htdocs (assuming a Unix-like directory structure), and you want this application to appear in a browser as mycamp.org/leveling/, you would copy these files to /home/web/htdocs/leveling/.

That's it!  You should now be able to use the admin staff pages to add groups, blocks, activities, and groups.  Campers can log into the camper view to add or modify their preferences.  Note that when you first log in as the administrator, you will be prompted to enter an admin email and password.  Campers do not need a password: they use a plain text token for access.  If campers need to modify their choices after entering them, they identify themselves with their email address.  This design obviously favors ease of use over high security.

The name "ChugBot" comes from the Hebrew word "chug", pronounced "HOOG", which means "circle" or "camp activity group." Some of the terms in the application are also transliterated Hebrew (our kids attend a Jewish summer camp).  Feel free to change these for your camp, or keep them as-is.  A quick glossary of terms:

- chug ("HOOG"): An individual activity (plural: chugim). (חוּג)
- edah ("Eh-DAH"): A grouping of campers (plural: edot). (עֵדָה)
- group: A group of activities.
- session: A session for which campers can register, such as July or August.
- block: A time block within a session, such as July 1 or August 2.  This is the unit within which the bot does camper/chug assignment.

My daughter drew a picture of what the bot might look like in real life.  He's pretty cool:

![bot image](chugbot/images/ChugBot.JPG?raw=true)
