$(function() {
	var target = $('#checkboxes');
	var chugNames = ["Ropes", "Cooking", "Outdoor Cooking"];
	var i, x, y, checkbox, html;
	html = "<table class=\"responsive-table-input-matrix\"><thead><tr><th></th>";
	// Table column headers                                                   
	for (i = 0; i < chugNames.length; i++) {
	    html += "<th>" + chugNames[i] + "</th>";
	}
	html += "</tr></thead><tbody>";

	for (x = 0; x < chugNames.length; x++) {
	    // Add a row for each chug.                             

	    html += "<tr><td>" + chugNames[x] + "</td>";

	    for (y = 0; y < chugNames.length; y++) {
		html += "<td>";
		checkbox = '<input type=checkbox ';
		checkbox += 'data-x="' + chugNames[x] + '"';
		checkbox += ' data-y="' + chugNames[y] + '"';
		checkbox += '/>';
		html += checkbox;
		html += "</td>";
	    }
	    html += "</tr>";
	}
	html += "</tbody></table>";

	target.append(html).width(function() {
		return $(this).find("input:checkbox").outerWidth() * chugNames.length
		    });

	target.on('change', 'input:checkbox', function() {
		var $this = $(this),
		    x = $this.data('x'),
		    y = $this.data('y'),
		    checked = $this.prop('checked');
		alert('checkbox changed chug intersection (' + x + ', ' + y + '): ' + checked);
	    });

    });
