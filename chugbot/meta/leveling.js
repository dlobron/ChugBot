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

// Get the name for the current edah and block IDs.
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

// Action for the Cancel button.
$(function() {
        $("#Cancel").click(function(event) {
                event.preventDefault();
		// Simulate clicking a link, so this page goes in the browser history.
		window.location.href("staffHome.php");
	    })
	    });

$(function() {
	var edah = getParameterByName("edah");
	var block = getParameterByName("block");
	doAssignmentAjax("get_current_stats", "Current Stats", "fetch current assignment stats",
			 edah, block);
    });

// Action for the Reassign button.
$(function() {
	var edah = getParameterByName("edah");
	var block = getParameterByName("block");
	var curUrl = window.location.href;
	var homeUrl = curUrl.replace("levelHome.html", "staffHome.php");
        $("#Reassign").click(function(event) {
                event.preventDefault();
		doAssignmentAjax("reassign", "Assignment saved!", "reassign",
				 edah, block);
            });
    });
