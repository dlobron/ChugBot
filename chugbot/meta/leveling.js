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
				    txt += "<li>Chugim under min: ";
				    txt += data.under_min_list;
				    txt += "</li>";
				    txt += "<li>Chugim over max: ";
				    txt += data.over_max_list;
				    txt += "</li>";
				    txt += "<li>Assignment Stats:<br>";
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
		    errMsg += err;
		    $( "#results" ).text(errMsg);
		    $( "#results" ).show("slide", 250 );
		}
	    });
}

// Get the current match and chug info for this edah/block, and display it by group. 
function getAndDisplayCurrentMatches() {
        var edah = getParameterByName("edah");
        var block = getParameterByName("block");
	var succeeded = false;
	$.ajax({
                url: 'levelingAjax.php',
                    type: 'post',
                    data:{ matches_and_prefs: 1,
			edah_id: edah, 
			block_id: block },
                    success: function(json) {
		    succeeded = true;
		    // Goal: display a field for each chug.  The chug fields should be grouped
		    // by group (aleph, bet, gimel).  Each field should contain camper containers,
		    // according to how campers are currently matched.
		    // Camper containers should be labeled with the camper's name, and colored according
		    // to the pref level of the assignment.  They should be draggable between chug fields,
		    // but only within the enclosing group (i.e., when changing the aleph assignment for
		    // a camper, it should be possible to move within aleph choices only).  Also, the tooltip
		    // for the camper boxes should show an ordered list of chugim, top to bottom.  
		    // "B-zhe moi!  This, I know from nothing!" - N. Lobachevsky.
		    var html = "";
		    var groupId2Name = json["groupId2Name"];
		    var groupId2ChugId2MatchedCampers = json["groupId2ChugId2MatchedCampers"];
		    var camperId2Group2PrefList = json["camperId2Group2PrefList"];
		    var chugId2Name = json["chugId2Name"];
		    var camperId2Name = json["camperId2Name"];
		    var prefColors = ["green", "yellow", "orange", "red"];
		    $.each(groupId2ChugId2MatchedCampers,
			   function(groupId, chugId2MatchedCampers) {
			       // Add a holder for each group (aleph, bet, gimel).
			       var groupName = groupId2Name[groupId];
			       html += "<div class=\"groupholder\" name=\"" + groupId + "\" >\n";
			       html += "<h3>" + groupName + " assignments</h3>\n";
			       // Within each group, add a holder for campers, and then populate with
			       // campers.
			       $.each(chugId2MatchedCampers,
				      function(chugId, matchedCampers) {
					  // Add a chug holder, and put camper holders inside it.
					  var chugName = chugId2Name[chugId];					  
					  html += "<div name=\"" + chugId + "\" class=\"ui-widget ui-helper-clearfix chugholder\">\n";
					  html += "<h4>" + chugName + "</h4>";
					  html += "<ul class=\"gallery ui-helper-reset ui-helper-clearfix\">";
					  $.each(matchedCampers,
						 function(index, camperId) {
						     var camperName = camperId2Name[camperId];
						     var prefListText = "";
						     var prefColor = "";
						     if (camperId in camperId2Group2PrefList) {
							 var group2PrefList = camperId2Group2PrefList[camperId];
							 if (groupId in group2PrefList) {
							     var prefList = group2PrefList[groupId];
							     $.each(prefList, function(index, prefChugId) {
								     var listNum = index + 1;
								     if (prefListText == "") {
									 prefListText += "Preferences:\n";
								     }
								     if (prefChugId in chugId2Name) {
									 prefListText += listNum + ". " + chugId2Name[prefChugId] + "\n";
								     }
								     if (prefChugId == chugId) {
									 if (index < prefColors.length) {
									     prefColor = prefColors[index];
									 } else {
									     prefColor = prefColors[prefColors.length - 1];
									 }
								     }
								 });
							 }
						     }
						     if (prefListText) {
							 // If we have a pref list, write it as a tool tip.
							 titleText = "title=\"" + prefListText + "\"";
						     }
						     html += "<li value=\"" + camperId + "\" class=\"ui-widget-content\" color=\"" + 
							 prefColor + "\"" + titleText + " ><h5 class=\"ui-widget-header\">" + 
							 camperName + "</h5></li>\n";
						 });
					  html += "</ul><br style=\"clear: both\"></div>\n";
				      });
			   html += "</div>\n";
			   });;
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
			// old chug to new, and highlight the camper.
			$('.chugholder').each(function(){
				var $el = $(this);
				$el.droppable({accept: "ul.gallery li",
					    activeClass: "ui-state-active",
					    hoverClass: "ui-state-hover",
					    drop: function(event, ui) {
					    var droppedOn = $(this).find(".gallery").addBack(".gallery");
					    var dropped = ui.draggable;
					    $(dropped).addClass("ui-state-highlight");
					    $(dropped).detach().css({top:0,left:0}).appendTo(droppedOn);
					}
				    });
			    });
		    } // End if succeeded	      
		});
}

// Get the current assignment stats for this edah/block.
$(function() {
	var edah = getParameterByName("edah");
	var block = getParameterByName("block");
	doAssignmentAjax("get_current_stats", "Current Stats", "fetch current assignment stats",
			 edah, block);
    });

// Get the name for the current edah and block IDs, and fill them.
$(function() {
	var edahId = getParameterByName('edah');
	var blockId = getParameterByName('block');
        $.ajax({
                url: 'levelingAjax.php',
                    type: 'post',
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

// Display current matches.
$(function() {
	getAndDisplayCurrentMatches();
    });

// Action for the Cancel button.
$(function() {
        $("#Cancel").click(function(event) {
                event.preventDefault();
		// Simulate clicking a link, so this page goes in the browser history.
		var curUrl = window.location.href;
		var homeUrl = curUrl.replace("levelHome.html", "staffHome.php");
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
		doAssignmentAjax("reassign", "Assignment saved!", "reassign",
				 edah, block);
		getAndDisplayCurrentMatches();
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
