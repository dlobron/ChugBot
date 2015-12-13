var success = false;
$(function() {
	var camperId = getParameterByName('cid');
	$.ajax({
		url: 'ajax.php',
		    type: 'post',
		    data: {rank_page_camper_id: camperId},
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
				  $.each(block2groupmap, function(groupname, chuglist) {
					  var sourceName = baseName + (++sortedCounter).toString();
					  var destName = baseName + (++sortedCounter).toString();
					  html += "<div id=\"chug_choice_container\">";
					  html += "<h3>" + blockname + " " + groupname + "</h3>";
					  html += "<ul name=\"" + sourceName + "\" id=\"sortable1\" class=\"connectedSortable\" ";
					  html += "title=\"Drag up to six chugim from left to right, then sort from top to bottom in order of preference.\">";
					  $.each(chuglist, function(index, value) {
						  html += "<li value=\"" + value + "\" class=\"ui-state-default\">" + value + "</li>";
					      });
					  html += "</ul>";
					  html += "<ul name=\"" + destName + "\" id=\"sortable2\" class=\"connectedSortable\">";
					  html += "</ul></div>";
				      });
				  html += "</body></html>";
				  $("body").append(html);
			      });
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
