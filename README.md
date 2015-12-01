# ChugBot
A website that assigns campers to activities using a modified version of the stable marriage algorithm.

ChugBot is a PHP/MySQL Web application.  It's designed for use by my kids' summer camp.  The camp staff uses the administrative pages to
add activities for the upcoming summer sessions.  Activities are divided into groups, and groups are assigned in 2-week blocks.  Each 
activity has a maximum and minimum capacity.

The campers log into a camper view, and select their preferred activities.  The list of activities they see is determined by the session
for which they have signed up: each session includes one or more blocks.  The campers rank their preferred activities in order of 
preference.  Once the rankings have been collected from all campers, the camp staff uses the "leveling" page to automatically assign
campers to activities.  This page uses a variant of the Gale/Shapely Stable Marriage algorithm to do the assignment.  In our case, the
assignment is not truly "stable," because preferences only go in one direction (from campers to activity, but not vice-versa).  However,
we use the one-directional nature of the problem to our advantage by introducing a "fairness" bias: an activity will "prefer" campers
who got a less-good assignment on a previous round.  The goal is to do an optimal assignment while also being fair.

Since the bot cannot know all the details of each camper and activity, the camp staff have the ability to override the assignment.  In
fact, the assignment should be treated as a "suggestion," which the camp staff will refine.
