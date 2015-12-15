var success = false;
$(function() {
	$.ajax({
		url: 'ajax.php',
		    type: 'post',
		    data:{get_first_name: 1},
		    success: function(data) {
		    var json = JSON.stringify(data);
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
		       var sortedCounter = 0;
		       $.each(json, 
			      function(blockname, block2groupmap) {
				  $.each(block2groupmap, function(groupname, chugName2DescList) {
					  var sourceName = baseName + (++sortedCounter).toString();
					  var destName = baseName + (++sortedCounter).toString();
					  html += "<div id=\"chug_choice_container\">\n";
					  html += "<h3>" + blockname + " " + groupname + "</h3>\n";
					  html += "<ul name=\"" + sourceName + "\" id=\"sortable1\" class=\"connectedSortable\" >\n";
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
		       html += "</body></html>";
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
