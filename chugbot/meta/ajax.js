var success = false;
$(function() {
	$("#SubmitPrefs").click(function(event) {
		event.preventDefault();
		// Collect data from the dest arrays when the submit button is clicked.
		var arrayOrderedLists = [];
		var divs = document.getElementsByName("chug_choice_container");
		for (var i = 0; i < divs.length; i++){
		    var divElement = divs[i];
		    var ulList = divElement.getElementsByTagName("ul");
		    for (var j = 0; j < ulList.length; j++) {
			var ulElement = ulList[j];
			var listName = ulElement.getAttribute("name");
			if (listName == "src") {
			    continue; // We're only interested in the drag-dest list.
			}
			var orderedList = [];
			orderedList.push(listName); // Put the block/group name first in the list.
			var listElements = ulElement.getElementsByTagName("li");
			for (var k = 0; k < listElements.length; k++) {
			    var listElement = listElements[k];
			    var value = listElement.getAttribute("value");
			    orderedList.push(value);
			}
		    }
		    arrayOrderedLists.push(orderedList);		    
		}
		$.ajax({
			url: 'ajax.php',
			    type: 'post',
			    data:{submit_prefs: 1, pref_arrays: arrayOrderedLists},
			    success: function(data) {
			    $( "#results" ).html(function() {
				    txt = $(this).html().replace("NAME", data.name);
				    return txt.replace("URL", data.homeUrl);
				});
			    $( "#results" ).show("slide", 500 );
			},
			    error: function() {
			    $( "#results" ).text("Oops! The system was unable to record your preferences.  Please hit Submit again.  If the problem persists, please contact the administrator.");
			    $( "#results" ).show("slide", 250 );
			}
		    });
	    });
    });
$(function() {
	$.ajax({
		url: 'ajax.php',
		    type: 'post',
		    data:{get_first_name: 1},
		    success: function(data) {
		    $( ".firstname" ).text(function() {
			    if (data.name &&
				data.name.length > 0) {
				return $(this).text().replace("Ramahniks", data.name);
			    }
			});
		},
		    error: function(xhr, desc, err) {
		       console.log(xhr);
		       console.log("Details: " + desc + "\nError:" + err);
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
				  $.each(block2groupmap, function(groupname, chugName2DescList) {
					  var destName = blockname + "||" + groupname;
					  html += "<div class=\"chug_choice_container\" name=\"chug_choice_container\" >\n";
					  html += "<h3>" + blockname + " " + groupname + "</h3>\n";
					  html += "<ul name=\"src\" id=\"sortable1\" class=\"connectedSortable\" >\n";
					  $.each(chugName2DescList, function(index, chugName2Desc) {
						  $.each(chugName2Desc, function(chugName, chugDesc) {
							  var titleText = "";
							  if (chugDesc) {
							      // If we have a chug description, write it as a tool tip.
							      titleText = "title=\"" + chugName + ": " + chugDesc + "\"";
							  }
							  html += "<li value=\"" + chugName + "\" class=\"ui-state-default\" " + 
							      titleText + " >" + chugName + "</li>";
						      });
					      });
					  html += "</ul>";
					  html += "<ul name=\"" + destName + "\" id=\"sortable2\" class=\"connectedSortable\">\n";
					  html += "</ul></div>\n";				  
				      });
			      });
		       $("body").append(html);
		},
		    error: function(xhr, desc, err) {
		       console.log(xhr);
		       console.log("Details: " + desc + "\nError:" + err);
		}
	    }).then(function(){
		    if (success) {
			$( "#sortable1, #sortable2" ).sortable({
				connectWith: ".connectedSortable"
				    }).disableSelection();
		    }});
    });
