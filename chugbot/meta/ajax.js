// Global variables.
var expectedChugCount = 6;
var success = false;
var existingChoicesMap = {};
var blockGroupChugInUse = {};

// Use the "promise" interface to ensure that we get existing choices and nav
// data before we get the name and fill in existing choices (otherwise, the
// functions can run in any order, and the user might not see any preferences).
$(function() {
	$.when(
	       getPrefCount(),
	       getExistingChoices(), 
	       getNav()
	       ).then(getNameAndFillChoices).then(footer);
    });

// Helper function to decode escaped HTML for the dynamic instructions in the ajax call to
// get_first_name_and_instructions.  
function htmlDecode(input) {
    var doc = new DOMParser().parseFromString(input, "text/html");
    return doc.documentElement.textContent;
}

function getPrefCount() {
    $.ajax({
	    url: 'ajax.php',
		async: false,
		type: 'post',
		data: {get_pref_count: 1},
		success: function(data) {
		expectedChugCount = data.pref_count;
	    }
	});
}

function getNav() {
        $.ajax({
                url: 'ajax.php',
		    async: false,
		    type: 'post',
		    data: {get_nav: 1},
		    success: function(txt) {
		    var html = "<div class=\"nav_container\">" + txt + "</div>";
		    $("#nav").html(html);
		}
	    });
}

function getExistingChoices() {
	$.ajax({
		url: 'ajax.php',
		    async: false,
		    type: 'post',
		    data: {get_existing_choices: 1},
		    success: function(json) {
		    $.each(json, function(blockGroupKey, chuglist) {
			    existingChoicesMap[blockGroupKey] = chuglist;
			    $.each(chuglist, function(index, chugNameAndId) {
				    var key = blockGroupKey + "||" + chugNameAndId;
				    blockGroupChugInUse[key] = 1;
				});
			});
		},
		    error: function(xhr, desc, err) {
		    console.log(xhr);
		    console.log("Details: " + desc + "\nError:" + err);
		}
	    });
}

$(function() {
	$(".SubmitPrefsButton").click(function(event) {
		event.preventDefault();
		// Collect data from the dest arrays when the submit button is clicked.
		var arrayOrderedLists = [];
		var divs = document.getElementsByName("chug_choice_container");
		var chugCountError = "<h3>Oops! Errors were found:</h3>";
		var errorCount = 0;
		for (var i = 0; i < divs.length; i++){
		    var divElement = divs[i];
		    var ulList = divElement.getElementsByTagName("ul");
		    for (var j = 0; j < ulList.length; j++) {
			var ulElement = ulList[j];
			var listName = ulElement.getAttribute("name");
			if (listName == "dragSrc") {
			    continue; // We're only interested in the drag-dest list.
			}
			var orderedList = [];
			orderedList.push(listName); // Put the block/group name first in the list.
			var listElements = ulElement.getElementsByTagName("li");
			var sessionAndGroup = listName.split("||");
			if (listElements.length < expectedChugCount) {
			    errorCount++;
			    chugCountError += "Only " + listElements.length + " chugim selected for " + sessionAndGroup[0] + ", " + sessionAndGroup[1] + " (you must choose " + expectedChugCount + ")<br>";
			    continue;
			} else if (listElements.length > expectedChugCount) {
			    errorCount++;
			    chugCountError += "Too many chugim were chosen for " + sessionAndGroup[0] + ", " + sessionAndGroup[1] + " (you must choose " + expectedChugCount + ")<br>";
			    continue;
			}
			for (var k = 0; k < listElements.length; k++) {
			    var listElement = listElements[k];
			    var value = listElement.getAttribute("value");
			    orderedList.push(value);
			}
		    }
		    arrayOrderedLists.push(orderedList);		    
		}
		// Jump to the top so the user sees either an error box or a confirmation box.
		$("body").scrollTop(0);
		// Report an error if no chugim were selected.
		if (arrayOrderedLists.length == 0) {
		    $( "#results:visible" ).removeAttr( "style" ).fadeOut();
		    $( "#results" ).text("No chugim were selected.");
		    $( "#results" ).show("slide", 250 );
		    return;
		}
		if (errorCount > 0) {
		    $( "#error:visible" ).removeAttr( "style" ).fadeOut();
		    $( "#error" ).html(chugCountError);
		    $( "#error" ).show("slide", 250 );
                    return;
                }
		$( "#error" ).hide(); // At this point, we have no validation errors.
		$.ajax({
			url: 'ajax.php',
			    type: 'post',
			    data:{submit_prefs: 1, pref_arrays: arrayOrderedLists},
			    success: function(data) {
			    $( "#results" ).html(function() {
				    if (typeof data.name === "undefined") {
					$( "#results:visible" ).removeAttr( "style" ).fadeOut();
					$( "#results" ).text("Oops! Our system was unable to record your preferences.  Please hit Submit again.  If the problem persists, please contact the administrator.");
					$( "#results" ).show("slide", 250 );
					console.log("Preferences submit failed: name not defined");
					return;
				    }
				    txt = $(this).html().replace("NAME", data.name);
				    if (data.hasOwnProperty('email')) {
					var mailText = "Confirmation email sent to " + data.email + ".<br><br>";
					txt = txt.replace("You may", mailText);
				    }
				    return txt.replace("URL", data.homeUrl);
				});
			    $( "#results:visible" ).removeAttr( "style" ).fadeOut();
			    $( "#results" ).show("slide", 500 );
			},
			    error: function(xhr, desc, err) {
			    $( "#results:visible" ).removeAttr( "style" ).fadeOut();
			    var errText = "Oops! Our system was unable to record your preferences.  Please hit Submit again.  If the problem persists, please contact the administrator, noting the following details about the error: " + xhr.responseText;
			    console.log("Error submitting preferences: " + xhr.responseText);
			    $( "#results" ).text(errText);
			    $( "#results" ).show("slide", 250 );
			}
		    });
	    });
    });

function getNameAndFillChoices() {
	$.ajax({
		url: 'ajax.php',
		    async: false,
		    type: 'post',
		    data:{get_first_name_and_instructions: 1},
		    success: function(data) {
		    $( ".firstname" ).text(function() {
			    if (data.name &&
				data.name.length > 0) {
				return $(this).text().replace("Ramahniks", data.name);
			    }
			});
		    $( ".pref_page_instructions" ).html(function() {
			    if (data.instructions &&
				data.instructions.length > 0) {
				return $(this).text().replace("INSTRUCTIONS", 
							      // data.instructions might be encoded, so use our
							      // helper function.
							      htmlDecode(data.instructions));
			    }
			});
		},
		    error: function(xhr, desc, err) {
		    console.log(xhr);
		    console.log("Details: ", desc);
		    console.log("Error: ", err);
		}
	    });
	$.ajax({
		url: 'ajax.php',
		    type: 'post',
		    data: {get_chug_info: 1},
		    success: function(json) {
		    success = true; // Set a global
		    // Parse the JSON from the ajax page.  We expect a hash from block name
		    // to another hash from group name to chug list.  We can build the html by
		    // calling:
		    // $("body").append(html);
		    // where html is the html text for the ULs.  Each will have
		    // the same ID, but a different name, so we can capture the
		    // contents of each and send it back to ajax.php to be put in
		    // the DB.
		    var html = "";
		    var baseName = "sortedSource";
		    $.each(json, 
			   function(blockname, block2groupmap) {
			       $.each(block2groupmap, function(groupname, chugNameAndId2DescList) {
				       var destName = blockname + "||" + groupname;
				       html += "<div class=\"chug_choice_container\" name=\"chug_choice_container\" >\n";
				       html += "<h3>" + blockname + " " + groupname + "</h3>\n";
				       html += "<ul name=\"dragSrc\" id=\"sortable1\" class=\"connectedSortable\" >\n";
				       var existingChoicesForThisDiv = {};
				       if (destName in existingChoicesMap) {
					   existingChoicesForThisDiv = existingChoicesMap[destName];
				       }
				       var chugId2Desc = {};
				       $.each(chugNameAndId2DescList, function(index, chugNameAndId2Desc) {
					       $.each(chugNameAndId2Desc, function(chugNameAndId, chugDesc) {
						       // Check to see if this chug is in use for this block/group.  If so, do
						       // not write it, since we'll be putting it in the destination.
						       var key = blockname + "||" + groupname + "||" + chugNameAndId;
						       var p = chugNameAndId.split("||");
						       var chugName = p[0];
						       var chugId = p[1];
						       var titleText = "";
						       if (chugDesc) {
							   // If we have a chug description, write it as a tool tip.
							   // We map the description in chugId2Desc in case we need
							   // it below when we render the destination chugim.
							   titleText = "title=\"" + chugName + ": " + chugDesc + "\"";
							   chugId2Desc[chugId] = chugDesc;
						       }
						       if (key in blockGroupChugInUse) {
							   return true; // This is like "continue"
						       }
						       html += "<li value=\"" + chugId + "\" class=\"ui-state-default\" " + 
							   titleText + " >" + chugName + "</li>";
						   });
					   });
				       html += "</ul>";
				       html += "<div class=\"centered_invisible\"><img src=\"images/RightArrow.png\" height=\"35\" width=\"35\"></div>";
				       html += "<ul name=\"" + destName + "\" id=\"sortable2\" class=\"connectedSortable\">\n";
				       $.each(existingChoicesForThisDiv, function(index, chugNameAndId) {
					       var p = chugNameAndId.split("||");
					       var chugName = p[0];
					       var chugId = p[1];
					       var titleText = "";
					       //var chugDesc = chugId2Desc[chugId];
					       //if (chugDesc) {
					       // If we have a chug description, write it as a tool tip.
					       //    titleText = "title=\"" + chugName + ": " + chugDesc + "\"";
					       //}
					       html += "<li value=\"" + chugId + "\" class=\"ui-state-default\" " + 
						   titleText + " >" + chugName + "</li>";
					   });
				       html += "</ul>";
				       html += "<div class=\"right_invisible\"><img src=\"images/UpDownArrows.png\"></div>";
				       html += "<div class=\"ui-progressbar\"><div class=\"progress-label\"></div></div>";
				       html += "</div>";
				   });
			   });
		    if (html.length == 0) {
			html = "<div class=\"error_box\"><h3>No chugim were found for your edah and session.</h3></div>";
		    } 
		    $("#filltarget").html(html);
		},
		    error: function(xhr, desc, err) {
		    console.log(xhr);
		    console.log("Details: ", desc);
		    console.log("Error: ", err);
		}
	    }).then(function() {
		    if (success) {
			// Display default progress bars on each chug holder.
			$('*[class*=chug_choice_container]').each(function() {
				var rd = $(this).find("#sortable2 li");
				var ct = rd.length;
				var label = $(this).find(".progress-label");
				var bar = $(this).find(".ui-progressbar");
				var barValue = $(bar).find( ".ui-progressbar-value" );
				var text = "<small>" + ct + "/" + expectedChugCount + "</small>";
				var color = 'Yellow';
				if (ct == expectedChugCount) {
				    color = "#00ff00";
				} else if (ct > expectedChugCount) {
				    color = "Red";
				}
				$(bar).height(35);
				$(bar).width(120);
				$(bar).progressbar({
					max: expectedChugCount,
					    value: ct,
					    create: function() {
					    label.html(text);
					    $(this).find(".ui-progressbar-value").css({ 'background': color });
					}
				    });
			    });
			$( "#sortable1, #sortable2" ).sortable({
				connectWith: ".connectedSortable",
				    receive: function(event, ui) {
				    // Count the number of dropped items, and display
				    // a message indicating how many to go.
				    var rd = $(event.target.parentElement).find("#sortable2 li");
				    var ct = rd.length;
				    var text = "<small>" + ct + "/" + expectedChugCount + "</small>";
				    var color = "Yellow";
				    if (ct == expectedChugCount) {
					color = "#00ff00";
				    } else if (ct > expectedChugCount) {
					color = "Red";
				    }
				    var label = $(event.target.parentElement).find(".progress-label");
				    var bar = $(event.target.parentElement).find(".ui-progressbar");
				    var barValue = $(bar).find( ".ui-progressbar-value" );
				    $(bar).height(35);
				    $(bar).width(120);
				    $(bar).progressbar({
					    max: expectedChugCount,
						value: ct,
						create: function() {
						label.html(text);
						$(this).find( ".ui-progressbar-value" ).css({ 'background': color });
					    },
						change: function() {
						label.html(text);
						$(this).find( ".ui-progressbar-value" ).css({ 'background': color });
					    },
						complete: function() {
                                                label.html(text);
                                                $(this).find( ".ui-progressbar-value" ).css({ 'background': color });
                                            }
					});	    
				}
			    }).disableSelection();
		    }});
}

function footer() {
	$( "ul[name='dragSrc']" ).after( "<div class=\"centered_invisible\"><img src=\"images/RightGreenArrow.png\" height=\"50\" width=\"100\"></div>" );
}
