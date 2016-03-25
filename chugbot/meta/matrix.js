var chugNames = [];
var chugIds = [];
$(function() {
	$.ajax({
		// Build parallel lists of chug IDs and corresponding names.
		// We do this with lists rather than a single associative array
		// because we need the ordering to be consistent.
                url: 'matrix.php',
                    type: 'post',
                    data: {get_chug_map: 1},
                    success: function(json) {
		    var obj = JSON.parse(json);
		    $.each(obj, function(chugId, chugName) {
			    chugIds.push(chugId);
			    chugNames.push(chugName);
			});
		    console.log("DBG: Have " + chugNames.length + " chugim");
		}, error: function(xhr, desc, err) {
		    console.log(xhr);
		    console.log("Details: " + desc + "\nError:" + err);
                }
            }).then(function() {
		    console.log("DBG: Have " + chugNames.length + " chugim");
		    var target = $('#checkboxes');
		    var i, x, y, checkbox, html;
		    html = "<table id=\"matrix\"><thead><tr><th></th>";
		    // Table column headers                                                   
		    for (i = 0; i < chugNames.length; i++) {
			html += "<th>" + chugNames[i] + "</th>";
		    }
		    html += "</tr></thead><tbody>";
		    for (x = 0; x < chugNames.length; x++) {
			// Add a row for each chug.                             
			html += "<tr><td>" + chugNames[x] + "</td>";
			for (y = 0; y < chugNames.length; y++) {
			    html += "<td class=\"idCheckBox\">";
			    checkbox = '<input type=checkbox ';
			    checkbox += 'data-x="' + chugIds[x] + '"';
			    checkbox += ' data-y="' + chugIds[y] + '"';
			    checkbox += '/>';
			    html += checkbox;
			    html += "</td>";
			}
			html += "</tr>";
		    }
		    html += "</tbody></table>";

		    console.log("html = " + html);
		    target.html(html);

		    //target.append(html).width(function() {
		    //	return $(this).find("input:checkbox").outerWidth() * chugNames.length
		    //	    });

		    //target.on('change', 'input:checkbox', function() {
		    //var $this = $(this),
		    //  x = $this.data('x'),
		    //  y = $this.data('y'),
		    //  checked = $this.prop('checked');
		    //alert('checkbox changed chug intersection (' + x + ', ' + y + '): ' + checked);
		    //});
		})
	    });

$(function() {
        $("#SaveChanges").click(function(event) {
		console.log("DBG: submit clicked");
                event.preventDefault();
		$('#matrix tr').each(function() {
			var cell = $(this).find(".idCheckBox");
			if (cell == undefined) {
			    return;
			}
			var leftChugId = $cell.data('x');
			var rightChugId = $cell.data('y');
			var checked = $cell.prop('checked');
			console.log("DBG: left ID " + leftChugId + ", right ID " + rightChugId + " checked = " + checked);
		    });
	    });
    });
		    
		
