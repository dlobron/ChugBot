// On page load, clear some structures, grab nav data, 
// and then get and display current matches.
$(function() {
        $.when(
               getNav()
               ).then(getAndDisplayCurrentMatches);
    });

function getParameterByName(nameTok) {
    var nameTokArray = nameTok + "[]";
    var query = window.location.search.substring(1);
    var vars = query.split("&");
    var arrayVal = [];
    var textVal = "";
    var seen = 0;
    for (var i = 0; i < vars.length; i++) {
	// Look for our parameter name.  If the parameter has
	// the form param[]=foo, or if it appears more than once,
	// then return an array of values.  Otherwise, if
	// param=foo appears, return foo.  Otherwise, return the empty
	// string.
	var pair = vars[i].split("=");
	if (pair.length != 2) {
	    continue;
	}
	var val = pair[1];
	if (pair[0] == nameTok) {
	    if (seen) {
		arrayVal.push(val);
	    } else {
		textVal = val;
	    }
	    seen = 1;
	} else if (pair[0] == nameTokArray) {
	    arrayVal.push(val);
	}
    }
    if (arrayVal.length > 0) {
	return arrayVal;
    } else {
	return textVal;
    }
}

function removeLastDirectoryPartOf(the_url)
{
    var the_arr = the_url.split('/');
    the_arr.pop();
    return (the_arr.join('/'));
}

var chugCountColorClasses = ["text-success", "text-danger", "text-warning"];

function getColorForCount(curCount, chugMin,chugMax) {
    var colorClass = chugCountColorClasses[0];
    if (curCount > chugMax &&
	chugMax > 0) {
	colorClass = chugCountColorClasses[1];
    } else if (curCount < chugMin) {
	colorClass = chugCountColorClasses[2];
    }
    return colorClass;
}

// Loop through all chugim in a group, and update their 
// current count and associated count color.
function updateCount(chugId2Beta, curChugHolder) {
    var groupHolder = $(curChugHolder).closest(".groupholder");
    var chugHolders = $(groupHolder).children(".chugholder");
    $(chugHolders).each(function(index) {
	    var chugId = $(this).attr('name');
	    var newCount = $(this).find("ul").children().length;
	    var min = parseInt(chugId2Beta[chugId]["min_size"]);
	    var max = parseInt(chugId2Beta[chugId]["max_size"]);
	    var colorClass = getColorForCount(newCount, min, max);
	    var curCountHolder = $(this).find("span[name=curCountHolder]");
	    $.each(chugCountColorClasses, function(index, classToRemove) {
		    // Remove old color class.
		    $(curCountHolder).removeClass(classToRemove);
		});
	    // Add new color class and count.
	    $(curCountHolder).attr('value', newCount);
	    $(curCountHolder).text("cur = " + newCount);
	    $(curCountHolder).addClass(colorClass);
	});
}

function sortedGroupIdKeysByName(groupId2ChugId2MatchedCampers, groupId2Name) {
    // Populate the sorted list.
    var sorted = new Array();
    for (var groupId in groupId2ChugId2MatchedCampers) {
	sorted.push(groupId);
    }
    // Do the actual sort by chug name, and return the sorted array.
    sorted.sort(function(x,y) {
	    var xName = groupId2Name[x];
	    var yName = groupId2Name[y];
	    if (xName.toLowerCase() < yName.toLowerCase()) {
		return -1;
	    }
	    if (xName.toLowerCase() > yName.toLowerCase()) {
		return 1;
	    }
	    return 0;
	});

    return sorted;
}

function chugIdsSortedByName(chugId2Beta, chugId2Entity) {
    // Populate the sorted list.
    var sorted = new Array();
    for (var chugId in chugId2Entity) {
	if (chugId2Entity.hasOwnProperty(chugId)) { // to be safe
	    sorted.push(chugId);
	}
    }
    // Do the actual sort, by chug name and then group name.
    sorted.sort(function(x,y) {
	    var betaX = chugId2Beta[x];
	    var betaY = chugId2Beta[y];
	    if (betaX.name.toLowerCase() < betaY.name.toLowerCase()) {
		return -1;
	    } 
	    if (betaX.name.toLowerCase() > betaY.name.toLowerCase()) {
		return 1;
	    }
	    if (betaX.group_name.toLowerCase() < betaY.group_name.toLowerCase()) {
		return -1;
	    }
	    if (betaX.group_name.toLowerCase() > betaY.group_name.toLowerCase()) {
	    	return 1;
	    }
	    return 0;
	});

    return sorted;
}

function isDupOf(droppedChugId, matchHash, deDupMatrix, chugId2MatchedCampers, matchedCamperId) {
    if (droppedChugId in chugId2MatchedCampers) {
	// If the camper is being dropped into their current mapping (e.g., being
	// dragged back), do not flag that as a duplicate.
	campersOriginallyMatched = chugId2MatchedCampers[droppedChugId];
	for (var i = 0; i < campersOriginallyMatched.length; i++) {
	    if (campersOriginallyMatched[i] == matchedCamperId) {
		return -1;
	    }
	}
    }
    if (droppedChugId in deDupMatrix) {
	var forbiddenToDupSet = deDupMatrix[droppedChugId];
	for (var matchedChugId in matchHash) {
	    if (matchedChugId == droppedChugId) {
		// Don't flag our own ID as a dup: it will be in the hash.
		continue;
	    }
	    if (matchedChugId in forbiddenToDupSet) {	       
		return matchedChugId;
	    }
	}
    }
    return -1;
}

function getNav() {
        $.ajax({
                url: 'ajax.php',
		    type: 'post',
		    data: {get_nav: 1},
		    success: function(txt) {
		    $("#nav").html(txt);
		}
	    });
}

function doAssignmentAjax(action, title, errText,
			  edah, block) {    
    var values = {};
    values[action] = 1;
    values["edah"] = edah;
    values["block"] = block;
    	$.ajax({
                url: 'levelingAjax.php',
		    async: false,
                    type: 'post',
                    data: values,
                    success: function(data) {
		    if (action == "reassign") {
			// Fade and then reload with new data (for multiple clicks).
			$( "#results:visible" ).removeAttr( "style" ).fadeOut();
		    }
		},
		    error: function(xhr, desc, err) {
		    errMsg = "The system was unable to ";
		    errMsg += errText;
		    errMsg += ". If the problem persists, please contact the administrator.  Error: ";
		    errMsg += err + " " + desc;
		    $( "#results" ).text(errMsg);
		    $( "#results" ).show("slide", 250 );
		}
	    });
}

// Get the current match and chug info for this edah/block, and display it by group. 
// Also, display chugim with no matches, because the user needs the ability to drag
// to them.
function getAndDisplayCurrentMatches() {
        var curUrl = window.location.href;
        var curUrlBase = curUrl.substr(0, curUrl.lastIndexOf("/"));
	var editChugBase = curUrlBase + "/editChug.php?eid=";
        var edah_ids = getParameterByName("edah_ids");
        var block = getParameterByName("block");
	var succeeded = false;
	var camperId2Group2PrefList;
	var chugId2Beta = {};
	var existingMatches = {};
	var deDupMatrix = {};
	var groupId2ChugId2MatchedCampers = {};
	var prefClasses = ["li_first_choice", "li_second_choice", "li_third_choice", "li_fourth_choice"];
	$.ajax({
                url: 'levelingAjax.php',
                    type: 'post',
		    async: false,
                    data:{ matches_and_prefs: 1,
			edah_ids: edah_ids, 
			block_id: block },
                    success: function(json) {
		    succeeded = true;
		    // Display a field for each chug.  The chug fields should be grouped
		    // by group (aleph, bet, gimel).  Each field should contain camper containers,
		    // according to how campers are currently matched.
		    // Camper containers should be labeled with the camper's name and edah, and colored according
		    // to the pref level of the assignment.  They should be draggable between chug fields,
		    // but only within the enclosing group (i.e., when changing the aleph assignment for
		    // a camper, it should be possible to move within aleph choices only).  Also, the tooltip
		    // for the camper boxes should show an ordered list of chugim, top to bottom.  
		    // "This, I know from nothing!" - N. Lobachevsky.
		    var html = "";
		    var edahNames = json["edahNames"];
		    var blockName = json["blockName"];
		    var groupId2Name = json["groupId2Name"];
		    var edahId2Name = json["edahId2Name"];
		    groupId2ChugId2MatchedCampers = json["groupId2ChugId2MatchedCampers"];
		    camperId2Group2PrefList = json["camperId2Group2PrefList"];
		    existingMatches = json["existingMatches"];
		    deDupMatrix = json["deDupMatrix"];
		    chugId2Beta = json["chugId2Beta"];
		    var camperId2Name = json["camperId2Name"];
		    var camperId2Edah = json["camperId2Edah"];
		    var sortedGroupIds = sortedGroupIdKeysByName(groupId2ChugId2MatchedCampers, groupId2Name);
		    for (var j = 0; j < sortedGroupIds.length; j++) {
			var groupId = sortedGroupIds[j];
			var chugId2MatchedCampers = groupId2ChugId2MatchedCampers[groupId];
			// Add a holder for each group (aleph, bet, gimel).
			var groupName = groupId2Name[groupId];
			html += "<div class=\"groupholder\" name=\"" + groupId + "\" >\n";
			if (Object.keys(chugId2MatchedCampers).length > 0) {
			    html += "<h3>" + groupName + " assignments</h3>\n";
			} else {
			    html += "<h3>" + groupName + ": no chugim are available for " + edahName +
				", " + blockName + "</h3>\n";
			}
			// Within each group, add a holder for campers, and then populate with
			// campers.  List chugim in alphabetical order.
			var sortedChugIds = chugIdsSortedByName(chugId2Beta, chugId2MatchedCampers);
			for (var i = 0; i < sortedChugIds.length; i++) {
			    var chugId = sortedChugIds[i];
			    var matchedCampers = chugId2MatchedCampers[chugId];
			    // Add a chug holder, and put camper holders inside it.
			    var chugName = chugId2Beta[chugId]["name"];
			    var chugMin = chugId2Beta[chugId]["min_size"];
			    var chugMax = chugId2Beta[chugId]["max_size"];
			    var allowedEdahIds = chugId2Beta[chugId]["allowed_edot"]; // array
			    var editChugUrl = editChugBase + chugId;
			    if (chugMax == "0" ||
				chugMax == 0 ||
				chugMax == "10000" ||
				chugMax == 10000 ||
				chugMax === null ||
				(typeof(chugMax) === 'undefined')) {
				chugMax = "no limit";
			    }
			    if (chugMin == "-1" ||
				chugMin == -1 ||
				chugMin === null ||
				(typeof(chugMin) === 'undefined')) {
				chugMin = "no minimum";
			    }
			    var curCount = matchedCampers.length;
			    var colorClass = getColorForCount(curCount, chugMin, chugMax);
			    html += "<div id=\"chugholder_" + chugId + "\" name=\"" + chugId + "\" class=\"ui-widget ui-helper-clearfix chugholder\">\n";
			    if (chugName == "Not Assigned Yet") {
				html += "<h4><font color=\"red\">" + chugName + "</font></h4>";
			    } else {
				html += "<h4>" + "<a href=\"" + editChugUrl + "\">" + chugName + "</a>"
				    + " (min = " + chugMin + ", max = " + chugMax + ", <span name=\"curCountHolder\" class=\"" + colorClass + "\" value=\"" + curCount + "\">cur = " + curCount + "</span>)</h4>";
			    }
			    html += "<ul class=\"gallery ui-helper-reset ui-helper-clearfix\">";
			    $.each(matchedCampers,
				   function(index, camperId) {
				       var camperName = camperId2Name[camperId];
				       var edahId = camperId2Edah[camperId];
				       var camperEdah = edahId2Name[edahId];
				       var prefListText = "";
				       var prefClass = prefClasses[prefClasses.length - 1];
				       if (camperId in camperId2Group2PrefList) {
					   var group2PrefList = camperId2Group2PrefList[camperId];
					   if (groupId in group2PrefList) {
					       var prefList = group2PrefList[groupId];
					       $.each(prefList, function(index, prefChugId) {
						       var listNum = index + 1;
						       if (prefListText == "") {
							   prefListText += "Preferences:\n";
						       }
						       if (prefChugId in chugId2Beta) {
							   prefListText += listNum + ". " + chugId2Beta[prefChugId]["name"] + "\n";
						       }
						       if (prefChugId == chugId) {
							   if (index < prefClasses.length) {
							       prefClass = prefClasses[index];
							   } else {
							       prefClass = prefClasses[prefClasses.length - 1];
							   }
						       }
						   });
					   }
				       }
				       var titleText = "title=\"<no preferences>\"";
				       if (prefListText) {
					   // If we have a pref list, write it as a tool tip.
					   titleText = "title=\"" + prefListText + "\"";
				       }
				       html += "<li value=\"" + camperId + "\" class=\"ui-widget-content " + prefClass + " \" "  + titleText;
				       html += "><h5 class=\"ui-widget-header\">" + camperName + " (" + camperEdah + ") " +  "</h5><div class=\"dup-warning\"></div></li>\n";
				   });
			    html += "</ul><br style=\"clear: both\"></div>\n";
			}
			html += "</div>\n";
		    };
		    // Compute and display chugim with space.  Link to the reporting page.
		    var loc = window.location;
		    var basePath = removeLastDirectoryPartOf(loc.pathname);		    
		    var edahQueryString = "";
		    $.each(edahId2Name, function(edahId, edahName) {
			    edahQueryString += "&edah_ids%5B%5D=" + edahId;
			});
		    var reportLink = "<a class=\"btn btn-primary btn-med btn-with-padding\" role=\"button\" href=\"" + loc.protocol + "//" + loc.hostname + ":" + loc.port + basePath + "/report.php?report_method=7&do_report=1&block_ids%5B%5D=" + block + edahQueryString + "&submit=Display\">Report</a>";
		    var freeHtml = "<h4>Chugim with Free Space:</h4>";
		    var sortedChugIds = chugIdsSortedByName(chugId2Beta, chugId2Beta);
		    for (var i = 0; i < sortedChugIds.length; i++) {
			var betaHash = chugId2Beta[sortedChugIds[i]];
			var freeSpace = betaHash["free"];
			var name = betaHash["name"];
			var groupName = betaHash["group_name"];
			if (freeSpace) {
			    var sp = "spaces";
			    var endTag = " left<br>";
			    if (freeSpace == 1) {
				sp = "space";
			    } else if (freeSpace == "unlimited") {
				sp = "space";
				endTag = "<br>";
			    }
			    var sp = (freeSpace == 1 || freeSpace == "unlimited") ? "space" : "spaces";
			    freeHtml += name + " (" + groupName + "): " + freeSpace + " " + sp + endTag;
			}
		    }
		    freeHtml += reportLink;
		    $("#results").html(freeHtml);
		    $("#results:visible").removeAttr( "style" ).fadeOut();
		    $("#results").show("slide", 500);
		    $("#results").attr('disabled', false);
		    // Display matches and chugim.
		    $("#fillmatches").html(html);
		},
		    error: function(xhr, desc, err) {
		    console.log(xhr);
		    console.log("Details: " + desc + "\nError:" + err);
		}
            }).then(function(){
                    if (succeeded) {
			$("ul.gallery li").draggable({
				scroll: true,
				    revert: "invalid", // when not dropped, the item will revert back
				    cursor: "move"
				    });
			$('ul.gallery li').each(function(){
				var $el = $(this);
				$el.draggable({
					containment: $el.closest('.groupholder'),
					    start: function(event, ui) {
					    // Store the ID of the chug from which we're being dragged.
					    var sourceChugId = $(this).closest(".chugholder").attr("name");
					    $(this).data("sourceChugId", sourceChugId);
					    }
					    });
			    });
			// Let chug holders be droppable.  When a camper holder is dragged, move from
			// old chug to new, and update the preference color.
			// TODO: Only allow campers to be dropped into chugim for which the camper's edah
			// is eligible.
			$('.chugholder').each(function(){
				var $el = $(this);
				var groupId = $el.parent().attr('name');
				var chugId2MatchedCampers = groupId2ChugId2MatchedCampers[groupId];
				$el.droppable({accept: "ul.gallery li",
					    activeClass: "ui-state-active",
					    hoverClass: "ui-state-hover",
					    drop: function(event, ui) {
					    var droppedOn = $(this).find(".gallery").addBack(".gallery");
					    var droppedChugId = $(droppedOn).parent().attr("name");
					    var dropped = ui.draggable;
					    //$(dropped).addClass("ui-state-highlight");
					    // Change the color of the dropped item according to the camper's
					    // preference for the dropped-on chug.
					    var camperId = $(dropped).attr("value");
					    var groupId = $(this).parent().attr("name");
					    var prefClass = prefClasses[prefClasses.length - 1];
					    if (camperId in camperId2Group2PrefList) {
						var group2PrefList = camperId2Group2PrefList[camperId];
						if (groupId in group2PrefList) {
						    var prefList = group2PrefList[groupId];
						    $.each(prefList, function(index, prefChugId) {
							    if (prefChugId == droppedChugId) {
								var idx = (index < prefClasses.length) ? index : prefClasses.length - 1;
								prefClass = prefClasses[idx];
								return false; // break
							    }
							});
						}
					    }
					    if (prefClass) {
						$.each(prefClasses, function(index, prefClassToRemove) {
							// Remove old color class.
							$(dropped).removeClass(prefClassToRemove);
						    });
						// Add new color class.
						$(dropped).addClass(prefClass);
					    }
					    $(dropped).detach().css({top:0,left:0}).appendTo(droppedOn);
					    // Check to see if the dropped-on chug is a duplicate for the dropped
					    // camper, and if so, show a warning.
					    var dupWarningDiv = $(dropped).find(".dup-warning");
					    $(dupWarningDiv).hide(); // Hide dup warning by default.
					    // Store sourceChugId in a variable, and remove it from the element.
					    var sourceChugId = $(dropped).data("sourceChugId");
					    $(dropped).removeData("sourceChugId");
					    if (camperId in existingMatches) {
						var matchHash = existingMatches[camperId];
						// Update matchHash: we need to remove the chug from which the camper
						// was dragged, and add the one in which they were dropped.  We won't
						// count the one in which they were dropped when we check for dups.
						delete matchHash[sourceChugId];
						var ourBlockName = $(".blockfill").text().substring(12);
						matchHash[droppedChugId] = ourBlockName;
						var dupId = isDupOf(droppedChugId, matchHash, 
								    deDupMatrix, chugId2MatchedCampers,
								    $(dropped).attr('value'));
						if (dupId in chugId2Beta) {
						    var dupName = chugId2Beta[dupId]["name"];
						    var dupGroup = chugId2Beta[dupId]["group_name"];
						    var dupBlockName = matchHash[dupId];
						    $(dupWarningDiv).html("<small><span class=\"label label-warning\">Dup of " + dupName + " (" + dupBlockName + " " + dupGroup + ")</span></small>");
						    $(dupWarningDiv).fadeIn("fast");
						}
					    }
					    // Update counts.
					    updateCount(chugId2Beta, droppedOn);
					}
				    });
			    });
		    } // End if succeeded	      
		});
}

// Get the name for the current edah and block IDs, and fill them.
$(function() {
	var edahIds = getParameterByName('edah_ids');
	var blockId = getParameterByName('block');
        $.ajax({
                url: 'levelingAjax.php',
                    type: 'post',
		    async: false,
                    data:{ names_for_id: 1,
		           edah_ids: edahIds, 
			   block_id: blockId },
                    success: function(json) {
                    $( ".edahfill" ).text(function() {
                            if (json.edahNames &&
                                json.edahNames.length > 0) {
                                return $(this).text().replace("EDAH", json.edahNames);
                            }
                        });
		    $( ".blockfill" ).text(function() {
                            if (json.blockName &&
                                json.blockName.length > 0) {
                                return $(this).text().replace("BLOCK", json.blockName);
                            }
                        });
                },
                    error: function(xhr, desc, err) {
		    console.log(xhr);
		    console.log("Details: " + desc + "\nError:" + err);
                }
            })
	    });

// Action for the Report button.
$(function() {
        $("#Report").click(function(event) {
                event.preventDefault();
                // Simulate clicking a link, so this page goes in the browser history.
                var baseUrl = window.location.href;
		baseUrl = baseUrl.replace("levelHome.html", "report.php");
		var edahText = "";
		var edah_ids = getParameterByName("edah_ids");
		for (var i = 0; i < edah_ids.length; i++) {
		    edahText += "&edah_ids%5B%5D=" + edah_ids[i];
		}
		var block = getParameterByName("block");
		var reportUrl = baseUrl.split("?")[0];
		var reportUrl = reportUrl + "?report_method=1&block_ids%5B%5D=" + block + edahText + "&do_report=1&submit=Display";
                window.location.href = reportUrl;
            })
            });

// Action for the Cancel button.
$(function() {
        $("#Cancel").click(function(event) {
                event.preventDefault();
		// Simulate clicking a link, so this page goes in the browser history.
		var curUrl = window.location.href;
		var homeUrl = curUrl.replace("levelHome.html", "staffHome.php");
		// Remove query string before redir.
		var qpos = homeUrl.indexOf("?");
		if (qpos > 0) {
		    homeUrl = homeUrl.substr(0, qpos);
		}
		window.location.href = homeUrl;
	    })
	    });

// Action for the Reassign button.
$(function() {
	// TODO: Allow multiple edot.
	var edah = getParameterByName("edah_ids");
	var block = getParameterByName("block");
        $("#Reassign").click(function(event) {
                event.preventDefault();
		var r = confirm("Reassign campers on page? Please click OK to confirm.");
		if (r != true) {
		    return;
		}
		var ajaxAction = function() {
		    var ra = $.Deferred();
		    doAssignmentAjax("reassign", "Assignment saved!", "reassign",
				     edah, block);
		    ra.resolve();
		    return ra;
		};
		var displayAction = function() {                    
		    getAndDisplayCurrentMatches();
		};
		ajaxAction().then(displayAction);
            });
    });

// Action for the Save button
// Collect the current assignments and send them to the ajax page to be
// saved in the DB.
$(function() {
	// TODO: allow multiple edot.
	var edah = getParameterByName("edah_ids");
	var block = getParameterByName("block");
        $("#Save").click(function(event) {
                event.preventDefault();
		var r = confirm("Save changes? Please click OK to confirm.");
		if (r != true) {
		    return;
		}
		// Loop through the groups, and then loop through the 
		// chugim within each group.
		var assignments = new Object(); // Associative array
                var groupDivs = $(document).find(".groupholder");
                for (var i = 0; i < groupDivs.length; i++) {
                    var groupElement = groupDivs[i];
		    var groupId = groupElement.getAttribute("name");
		    var chugDivs = $(groupElement).find(".chugholder");
		    assignments[groupId] = new Object();// Associative array 
                    for (var j = 0; j < chugDivs.length; j++) {
			var chugDiv = chugDivs[j];
			var chugId = chugDiv.getAttribute("name");
			var ulElement = $(chugDiv).find("ul");
			var camperElements = $(ulElement).find("li");
			assignments[groupId][chugId] = [];
			for (var k = 0; k < camperElements.length; k++) {
			    var camperElement = camperElements[k];
			    var camperId = camperElement.getAttribute("value");
			    assignments[groupId][chugId].push(camperId);
			}
		    }
		}
		var values = {};
		values["save_changes"] = 1;
		values["assignments"] = assignments;
		values["edah"] = edah;
		values["block"] = block;
		$.ajax({
			url: 'levelingAjax.php',
			    type: 'post',
			    async: false,
			    data: values,
			    success: function(json) {
			    doAssignmentAjax("get_current_stats", "Changes Saved! Stats:", "save your changes",
					     edah, block);
			    getAndDisplayCurrentMatches();
			},
			    error: function(xhr, desc, err) {
			    console.log(xhr);
			    console.log("Details: " + desc + "\nError:" + err);
			}
		    });
	    });
    });
