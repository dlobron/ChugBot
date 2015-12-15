#!/usr/bin/perl

use strict;
use Getopt::Std;
use Data::Dumper qw(Dumper); # For debugging

my %opt;
getopts('m:p:', \%opt);

my $highval = 1000;

sub usage() {
    print "Usage: ./chug.pl -m MAXFILE -p PREFFILE[,PREFFILE2,...]\n";
    print "MAXFILE is per-chug max counts, and PREFFILEs are camper preferences.\n";
    exit(1);
}

my $maxfile = $opt{'m'};
my $pfiles = $opt{'p'};

usage() unless (defined $maxfile && defined $pfiles);
# Read chug limits.  Formats:
# Chug Aleph,,,
# Basketball ,6,16,25
# where 6 is the min, 16 is ideal, and 25 is the max.
my $block;
my @blocks;
my %chuglimits;
open(MAX, "< $maxfile") or die "Can't open max file $maxfile: $!\n";
while(<MAX>) {
    chomp;
    my @parts = split /[,]/;
    next if (scalar(@parts) < 1);
    foreach my $part (@parts) {
	$part =~ s/^\s+|\s+$//g; # Trim whitespace.
    }
    if ($parts[0] =~ m/Chug (\w+)/) {
	$block = $1;
	next;
    } elsif (scalar(@parts) == 4) {
	$chuglimits{$block}->{$parts[0]}->{'min'} = $parts[1];
	$chuglimits{$block}->{$parts[0]}->{'ideal'} = $parts[2];
	$chuglimits{$block}->{$parts[0]}->{'max'} = $parts[3];
    }
}
close MAX;
foreach my $block (sort keys %chuglimits) {
    push @blocks, $block; # Remember blocks in order
    print "Chugim for block $block:\n";
    my $ref = $chuglimits{$block};
    foreach my $chug (sort keys %$ref) {
	my $data = $ref->{$chug};
	printf "%s: min = %d, ideal = %d, max = %d\n",
	$chug, $data->{'min'}, $data->{'ideal'}, $data->{'max'};
    }
}

# Collect camper preferences, by block.
my $prefs;
my $chugByBlock;
my $bidx = 0;
my @pfiles = split /[,]/, $pfiles;
foreach my $pfile (@pfiles) {
    if ($bidx > (scalar(@blocks)-1)) {
	print "ERROR: More blockfiles than blocks\n";
	exit(1);
    }
    my %seen_campers;
    my $block = $blocks[$bidx++];
    my @chuglist;
    open(PFILE, "< $pfile") or die "Can't open pref file $pfile: $!\n";
    my $line = 0;
    print "Reading preferences for block $block:\n";
    while(<PFILE>) {
	chomp;
	my @parts = split /[,]/;
	foreach my $part (@parts) {
	    $part =~ s/^\s+|\s+$//g; # Trim whitespace.	
	}
	next unless (scalar(@parts));
	# The first line lists the chugim:  
	# Open-Ended Response,Open-Ended Response,c1,c2,...
	# Subsequent lines look like this:
	# first,last,5,2,,,,6,1,4,3
	# There can be any number of choices.
	if ($line++ == 0) {
	    # This is the first line, with the chugim in order.
	    for (my $i = 2; $i < scalar(@parts); $i++) {
		push @chuglist, $parts[$i];
	    }
	    $chugByBlock->{$block} = \@chuglist;
	    next;
	}
	# At this point, we have a camper pref list.  Grab the camper's
	# name, and add their preferences to the per-block list.
	my $camper = "$parts[0] $parts[1]";
	if (defined $seen_campers{$camper}) {
	    print "WARNING: Found > 1 entry for $camper: ignoring subsequent ones\n";
	    next;
	}
	$seen_campers{$camper} = 1;
	#print "DBG: for $camper\n";
	for (my $i = 2; $i < scalar(@parts); $i++) {
	    my $pref = $parts[$i];
	    my $cidx = $i - 2;
	    unless ($pref =~ m/\d+/) {
		# If the entry is not a number, then the camper doesn't have a
		# preference for this chug: set the pref to high, so we do not
		# assign it. 
		$pref = $highval;
	    }
	    my $chug = $chuglist[$cidx];
	    push @{ $prefs->{$block}->{$camper} }, { 'chug' => $chug,
						     'preflevel' => $pref };
	    #print "DBG: Found chug $chuglist[$cidx] at pref $pref\n";
	}
	# Sort the chugim by preference.
	my @sorted = sort { $a->{'preflevel'} <=> $b->{'preflevel'} } @{ $prefs->{$block}->{$camper} };
	$prefs->{$block}->{$camper} = \@sorted;
    }
    foreach my $camper (sort bylast keys %{ $prefs->{$block} }) {
       printf "$camper: %s\n", join ', ',
       map { "$_->{'chug'} ($_->{'preflevel'})" } @{ $prefs->{$block}->{$camper} };
    }
    close PFILE;
}

# Map campers to happiness level - lower is better.  This is
# used to compute bumpouts in the current assignment round.
my %happiness; 

my $totalBumps;
my $duplicatesSkipped;
my $allAssignments;
foreach my $block (sort keys %$prefs) {
    print "Making assignments for block $block\n";
    my $camperPrefsForThisBlock = $prefs->{$block};
    my $chugLimitsThisBlock = $chuglimits{$block};
    my $currentAssignmentsByChug;

    # Now, run the stable marriage algorithm.  We're assigning campers to chugim.  We try
    # to assign each camper to their first choice.  If a chug is full, we bump out the
    # camper with the best (lowest) current happiness score.
    my %assignments; # We'll store assignments here.
    my @unassignedCampers = keys %$camperPrefsForThisBlock; # Start with the full list of campers.
    my %assignedCount;
    while (my $camper = shift @unassignedCampers) {
	print "Assigning $camper\n";
	# Try to assign this camper to the first chug in their preference list, and remove
	# that chug from the list.
	# Grab this camper's next preferred choice, and remove it from the choice list.
	my $chugref = shift @{ $camperPrefsForThisBlock->{$camper} };
	my $preferredChug = $chugref->{'chug'};
	# Check to see if this chug was already assigned- if so, note that and continue.
	my $existingAssignments = $allAssignments->{$camper};
	if (defined $existingAssignments) {
	    foreach my $eblock (keys %{ $existingAssignments }) {
		if ($existingAssignments->{$eblock}->{'chug'} eq $preferredChug) {
		    $duplicatesSkipped++;
		    next;
		}
	    }
	}
	# Now, try to assign the camper to this chug:
	# - If space, assign right away.
	# - Otherwise: if this camper is less happy than one assigned to that chug, assign this
	# camper, unassign the other camper from %assignments, and put this camper back into @unassignedCampers.
	if (chugFree($preferredChug, $chugLimitsThisBlock, \%assignedCount)) {
	    # Assign camper to chug, bump chug assignment count, and continue;
	    $assignments{$camper} = $chugref;
	    $assignedCount{$preferredChug}++;
	    #print "DBG: Assigned $camper to $preferredChug, choice $chugref->{'preflevel'}\n";
	    next;
	}
	#print "DBG: chug not free\n";
	# At this point, the preferred chug is not free.  We now check to see if there is
	# an existing assigned camper whose combined happiness is higher.  We choose the
	# camper meeting that criteria who has the lowest (best) happiness, and bump them to
	# their next preference.
	my $happierCamper = findHappiestCamper($camper, $chugref, \%assignments, \%happiness,
					       $camperPrefsForThisBlock, $chugLimitsThisBlock, \%assignedCount);
	if (defined $happierCamper) {
	    # The camper with the best happiness is happier than this camper: bump
	    # that camper, and assign this camper.  The assigned total for the chug stays the same,
	    # since we're only swapping.
	    $assignments{$happierCamper} = undef;	    
	    push @unassignedCampers, $happierCamper;
	    $assignments{$camper} = $chugref;	    
	    $totalBumps++;	   
	    #printf "DBG: Assigned $camper to %s, choice %d, bumped %s\n",
	    $chugref->{'chug'}, $chugref->{'preflevel'}, $happierCamper;
	    next;
	}
	# At this point, we failed to assign: put this camper back on the list.
	push @unassignedCampers, $camper;
	#print "DBG: Put $camper back on unassigned list (no happier camper)\n";
    }
    # Update happiness levels with this block's choices.
    foreach my $camper (keys %assignments) {
	$happiness{$camper} += $assignments{$camper}->{'preflevel'};
    }
    # Record the choices.
    $allAssignments->{$block} = \%assignments;
}

# Print the assignments and stats.
my %choiceCount;
my $totalAssignments;
print "\n";
foreach my $block (sort keys %$allAssignments) {
    my $assignments = $allAssignments->{$block};
    # Sanity check: all campers should be assigned to a chug.
    my @allCampers = keys %{ $prefs->{$block} };
    foreach my $camper (@allCampers) {
	unless (defined $assignments->{$camper}) {
	    print "WARNING: No assignment for $camper for block $block\n";
	}
    }
    print "Assignments for $block:\n";
    foreach my $camper (sort bylast keys %$assignments) {
	my $assignment = $assignments->{$camper};
	printf "$camper: %s (%d)\n",
	$assignment->{'chug'}, $assignment->{'preflevel'};
	$choiceCount{$assignment->{'preflevel'}}++;
	$totalAssignments++;
    }
    print "\n";
}
printf "Assigned %d blocks: %d bumps, skipped %d duplicate choices\n",
    scalar(keys %$prefs), $totalBumps, $duplicatesSkipped;
foreach my $level (sort keys %choiceCount) {
    my $pct = ($choiceCount{$level} * 100) / $totalAssignments;
    printf "%d assigned at choice level %d (%.02f%%)\n",
    $choiceCount{$level}, $level, $pct;
}

sub existingHappiness {
    my ($camper, $chugref, $happiness) = @_;
    my $existingHappiness = $happiness->{$camper};
    $existingHappiness = 0 unless defined $existingHappiness;
    return $existingHappiness + $chugref->{'preflevel'};
}

sub spaceInNextPref {
    # Return the amount of space in the camper's next-preferred chug.
    my ($camper,
	$camperPrefsForThisBlock, 
	$chugLimitsThisBlock,
	$assignedCount) = @_;
    my $nextPreferredChug;
    my $myNextFree = -1;
    my @myPrefList = @{ $camperPrefsForThisBlock->{$camper} };
    if (scalar(@myPrefList)) {
	$nextPreferredChug = ${myPrefList[0]}->{'chug'};
	$myNextFree = $chugLimitsThisBlock->{$nextPreferredChug}->{'max'} - $assignedCount->{$nextPreferredChug};
	if ($myNextFree < 0) {
	    print "ERROR: $nextPreferredChug is filled past max\n";
	    exit(1);
	}
    }
    print "DBG: spaceInNextPref for $camper: next chug = $nextPreferredChug, free = $myNextFree\n";

    return $myNextFree;
}

sub findHappiestCamper {
    # Look for the assigned camper with the lowest (best) happiness
    # level.  
    my ($camper, $chugref, $assignments, $happiness, $camperPrefsForThisBlock, 
	$chugLimitsThisBlock, $assignedCount) = @_;
    # First, get our happiness level for this chug, which is the
    # existing happiness plus the happiness if we're assigned here.
    my $ourHappiness = existingHappiness($camper, $chugref, $happiness);
    my $min = $ourHappiness;
    my $happiest = undef;
    # Look for the assigned camper with the lowest (best) happiness                                                                                                  
    # level.  IMPROVE: this can be stored rather than computed each time.
    # Also, for each assigned camper, record how much space is left in their
    # next-preferred choice.
    my $mostFreeSpaceCamper = undef;
    my $ourNextSpace = spaceInNextPref($camper, $camperPrefsForThisBlock, $chugLimitsThisBlock, $assignedCount);
    my $maxNextSpace = $ourNextSpace;
    #print "DBG: findHappiestCamper for $camper, chug $chugref->{'chug'}, pref $chugref->{'preflevel'}, ourHappiness = $min, ourNextSpace = $ourNextSpace\n";
    foreach my $assignedCamper (keys %{ $assignments }) {
	my $assignedTo = $assignments->{$assignedCamper}->{'chug'};
	next unless ($assignedTo eq $chugref->{'chug'}); # Only consider campers assigned to this chug.
	my $theirHappiness = existingHappiness($assignedCamper, $assignments->{$assignedCamper}, $happiness);
	#print "DBG: $assignedCamper happiness = $theirHappiness\n";
	if ($theirHappiness < $min) {
	    # We've found a camper with a lower happiness level.  Note that camper, and reset the min.
	    $happiest = $assignedCamper;
	    $min = $theirHappiness;
	    print "DBG: Found happier assigned camper $assignedCamper - their happiness = $theirHappiness\n";
	}
	my $theirNextSpace = spaceInNextPref($assignedCamper, $camperPrefsForThisBlock, $chugLimitsThisBlock,$assignedCount);
	if ($theirNextSpace > $maxNextSpace) {
	    $mostFreeSpaceCamper = $assignedCamper;
	    $maxNextSpace = $theirNextSpace;
	#    print "DBG: Found camper $assignedCamper with more next space - $maxNextSpace, ours was $ourNextSpace\n";
	}
    }
    # If an existing assigned camper has more free space in their next choice, return that camper.  Otherwise, if an assigned
    # camper is happier, return that camper.  Otherwise, we don't want to bump anyone.
    # Intuitively, I thought it would be better to reverse these, but experimentally, it turns out that this order gives the 
    # best results.
    if (defined $mostFreeSpaceCamper) {
	return $mostFreeSpaceCamper;
    } elsif (defined $happiest) {
	return $happiest;
    }    

    return undef;
}

# Return true if a chug has space, false otherwise.
sub chugFree {
    my ($chugToCheck, $chugLimits, $assignedCount) = @_;
    my $curCount = $assignedCount->{$chugToCheck};
    if (! defined $curCount) {
	return 1;
    }

    #printf "DBG: chugFree: $chugToCheck curCount=$curCount, limit=%d\n",
    $chugLimits->{$chugToCheck}->{'max'};

    return ($chugLimits->{$chugToCheck}->{'max'} > $curCount);
}
    
# Sort by last name.
sub bylast {
    my ($l1, $l2);
    my @p = split /\s+/, $a;
    $l1 = lc($p[1]);
    @p = split /\s+/, $b;
    $l2 = lc($p[1]);
    return ($l1 cmp $l2);
}
