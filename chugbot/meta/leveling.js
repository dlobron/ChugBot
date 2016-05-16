// On page load, grab nav data, then assignments, and then get and display
// current matches.
$(function() {
        $.when(
               getNav()
               ).then(getAssignments).then(getAndDisplayCurrentMatches);
    });

// Get the current assignment stats for this edah/block.
function getAssignments() {
	var edah = getParameterByName("edah");
	var block = getParameterByName("block");
	doAssignmentAjax("get_current_stats", "Current Stats", "fetch current assignment stats",
			 edah, block);
}

function getNav() {
        $.ajax({
                url: 'ajax.php',
		    type: 'post',
		    data: {get_nav: 1},
		    success: function(txt) {
		    var html = "<div class=\"nav_container\">" + txt + "</div>";
		    $("#nav").html(html);
		}
	    });
}

function getParameterByName(name) {
    name = name.replace(/[\[]/, "\\[").replace(/[\]]/, "\\]");
    var regex = new RegExp("[\\?&]" + name + "=([^&#]*)"),
        results = regex.exec(location.search);
    return results === null ? "" : decodeURIComponent(results[1].replace(/\+/g, " "));
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
		    $( "#results" ).html(function() {
				    txt = "<h3>" + title + "</h3>";
				    txt += "<ul>";
				    txt += "<li><b>Chugim under min</b>: ";
				    txt += data.under_min_list;
				    txt += "</li>";
				    txt += "<li><b>Chugim over max</b>: ";
				    txt += data.over_max_list;
				    txt += "</li>";
				    txt += "<li><b>Assignment Stats</b>:<br>";
				    txt += data.statstxt;
				    txt += "</li></ul>";
				    return txt;
			});
		    $( "#results" ).show("slide", 500);
		    $( "#results" ).attr('disabled', false);
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
        var edah = getParameterByName("edah");
        var block = getParameterByName("block");
	var succeeded = false;
	var camperId2Group2PrefList;
	var prefClasses = ["li_first_choice", "li_second_choice", "li_third_choice", "li_fourth_choice"];
	$.ajax({
                url: 'levelingAjax.php',
                    type: 'post',
		    async: false,
                    data:{ matches_and_prefs: 1,
			edah_id: edah, 
			block_id: block },
                    success: function(json) {
		    succeeded = true;
		    // Display a field for each chug.  The chug fields should be grouped
		    // by group (aleph, bet, gimel).  Each field should contain camper containers,
		    // according to how campers are currently matched.
		    // Camper containers should be labeled with the camper's name, and colored according
		    // to the pref level of the assignment.  They should be draggable between chug fields,
		    // but only within the enclosing group (i.e., when changing the aleph assignment for
		    // a camper, it should be possible to move within aleph choices only).  Also, the tooltip
		    // for the camper boxes should show an ordered list of chugim, top to bottom.  
		    // "This, I know from nothing!" - N. Lobachevsky.
		    var html = "";
		    var edahName = json["edahName"];
		    var blockName = json["blockName"];
		    var groupId2Name = json["groupId2Name"];
		    var groupId2ChugId2MatchedCampers = json["groupId2ChugId2MatchedCampers"];
		    camperId2Group2PrefList = json["camperId2Group2PrefList"];
		    var chugId2Beta = json["chugId2Beta"];
		    var camperId2Name = json["camperId2Name"];
		    $.each(groupId2ChugId2MatchedCampers,
			   function(groupId, chugId2MatchedCampers) {
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
			       // campers.
			       $.each(chugId2MatchedCampers,
				      function(chugId, matchedCampers) {
					  // Add a chug holder, and put camper holders inside it.
					  var chugName = chugId2Beta[chugId]["name"];
					  var chugMin = chugId2Beta[chugId]["min_size"];
					  var chugMax = chugId2Beta[chugId]["max_size"];
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
					  html += "<div name=\"" + chugId + "\" class=\"ui-widget ui-helper-clearfix chugholder\">\n";
					  if (chugName == "Not Assigned Yet") {
					      html += "<h4><font color=\"red\">" + chugName + "</font></h4>";
					  } else {
					      html += "<h4>" + "<a href=\"" + editChugUrl + "\">" + chugName + "</a>"
						  + " (min = " + chugMin + ", max = " + chugMax + ")</h4>";
					  }
					  html += "<ul class=\"gallery ui-helper-reset ui-helper-clearfix\">";
					  $.each(matchedCampers,
						 function(index, camperId) {
						     var camperName = camperId2Name[camperId];
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
						     html += "><h5 class=\"ui-widget-header\">" + camperName + "</h5></li>\n";
						 });
					  html += "</ul><br style=\"clear: both\"></div>\n";
				      });
			   html += "</div>\n";
			   });
		    $("#fillmatches").html(html);
                },
                    error: function(xhr, desc, err) {
		    console.log(xhr);
		    console.log("Details: " + desc + "\nError:" + err);
                }
            }).then(function(){
                    if (succeeded) {
			$("ul.gallery li").draggable({
				scroll: false,
				    revert: "invalid", // when not dropped, the item will revert back
				    cursor: "move"
				    });
			$('ul.gallery li').each(function(){
				var $el = $(this);
				$el.draggable({containment:$el.closest('.groupholder')});
			    });
			// Let chug holders be droppable.  When a camper holder is dragged, move from
			// old chug to new, and update the preference color.
			$('.chugholder').each(function(){
				var $el = $(this);
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
					}
				    });
			    });
		    } // End if succeeded	      
		});
}

// Get the name for the current edah and block IDs, and fill them.
$(function() {
	var edahId = getParameterByName('edah');
	var blockId = getParameterByName('block');
        $.ajax({
                url: 'levelingAjax.php',
                    type: 'post',
		    async: false,
                    data:{ names_for_id: 1,
		           edah_id: edahId, 
			   block_id: blockId },
                    success: function(json) {
                    $( ".edahfill" ).text(function() {
                            if (json.edahName &&
                                json.edahName.length > 0) {
                                return $(this).text().replace("EDAH", json.edahName);
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
		var edah = getParameterByName("edah");
		var block = getParameterByName("block");
		var reportUrl = baseUrl.split("?")[0];
		var reportUrl = reportUrl + "?report_method=1&block_ids%5B%5D=" + block + "&edah_id=" + edah + "&do_report=1&submit=Display";
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
		if (qpos) {
		    homeUrl = homeUrl.substr(0, qpos);
		}
		window.location.href = homeUrl;
	    })
	    });

// Action for the Reassign button.
$(function() {
	var edah = getParameterByName("edah");
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
	var edah = getParameterByName("edah");
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
			},
			    error: function(xhr, desc, err) {
			    console.log(xhr);
			    console.log("Details: " + desc + "\nError:" + err);
			}
		    });
	    });
    });
