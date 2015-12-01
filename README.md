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

1. Enter the MySQL command line as root, and run "source ChugBot.sql".  Note that currently there is some sample data there - you should remove this with a text editor before loading it.

2. Copy the contents of the "chugbot" directory to the root of where you want the website to run.  For example, if your webserver
root is /home/web/htdocs (assuming a Unix-like directory structure), and you want this application to appear in a browser as mycamp.org/chugbot/, you would copy these files to /home/web/htdocs/chugot/.

The name "ChugBot" comes from the Hebrew word "chug", pronounced "HOOG", which means "circle" or "camp activity group." Some of the terms in the application are also transliterated Hebrew (our kids attend a Jewish summer camp).  Feel free to change these for your camp, or keep them as-is.  A quick glossary of terms:

- chug ("HOOG"): An individual activity. (חוּג)
- edah ("A-DAH"): A grouping of campers. (עֵדָה)
- group: A group of activities.
- session: A session for which campers can register, such as July or August.
- block: A time block within a session, such as July 1 or August 2.  This is the unit within which the bot does camper/chug assignment.
