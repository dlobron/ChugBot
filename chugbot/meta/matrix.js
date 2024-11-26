var chugChecked = {};

function show(id, value) {
	document.getElementById(id).style.display = value ? 'block' : 'none';
}

$(function () {
	show('page', false);
	show('loading', true);
	$.ajax({
		url: 'ajax.php',
		type: 'post',
		data: { get_nav: 1 },
		success: function (txt) {
			$("#nav").html(txt);
		}
	});
});

$(function () {
	var chugNames = [];
	var chugIds = [];
	$("#SaveChanges").hide();
	$.ajax({
		url: 'matrix.php',
		type: 'post',
		data: { get_chug_map: 1 },
		success: function (json) {
			var obj = JSON.parse(json);
			$(".chugimfill").text(function () {
				if (obj.chugimTerm &&
					obj.chugimTerm.length > 0) {
					return $(this).text().replace("CHUGIM", obj.chugimTerm);
				}
			});
			$(".chugfill").text(function () {
				if (obj.chugTerm &&
					obj.chugTerm.length > 0) {
					return $(this).text().replace("CHUG", obj.chugTerm);
				}
			});
			$(".blocktermfill").text(function () {
				if (obj.blockTerm &&
					obj.blockTerm.length > 0) {
					return $(this).text().replace("BLOCK_TERM", obj.blockTerm);
				}
			});
			$.each(obj.chugMap, function (index, chugName) {
				chugNames.push(chugName);
			});
			$.each(obj.chugIds, function (index, chugId) {
				chugIds.push(chugId);
			});
			$.each(obj.matrixMap, function (leftChug, rightChug2Enabled) {
				$.each(rightChug2Enabled, function (rightChug, enabled) {
					if (leftChug in chugChecked == false) {
						chugChecked[leftChug] = {};
					}
					chugChecked[leftChug][rightChug] = 1;
				});
			});
		}, error: function (xhr, desc, err) {
			console.log(xhr);
			console.log("Details: " + desc + "\nError:" + err);
			var errHtml = "<div class=panel_heading><h3>Unable to save changes:</h3></div><div class=\"panel-body\">" + err + ". Please contact an administrator.</div>";
			$("#errors").html(errHtml);
		}
	}).then(function () {
		var target = $('#checkboxes');
		var i, x, y, checkbox, html;
		html = "<div class=\"card card-body p-3 container\"><table id=\"matrix\" class=\"table table-striped\"><thead><tr><th scope=\"col\" class=\"topLeft\"></th>";
		// Table column headers
		for (i = 0; i < chugNames.length; i++) {
			html += "<th scope=\"col\" class=\"topRow p-2\">" + chugNames[i] + "</th>";
		}
		html += "</tr></thead><tbody>";
		for (x = 0; x < chugNames.length; x++) {
			// Add a row for each chug.
			html += "<tr><td class=\"chugCol p-1\">" + chugNames[x] + "</td>";
			// Add blank cells at beginning of row
			html += "<td></td>".repeat(x);
			for (y = x; y < chugNames.length; y++) {
				html += "<td class=\"align-middle\" style=\"text-align: center;\">";
				var checkedText = " ";
				if ((chugIds[x] in chugChecked) &&
					chugIds[y] in chugChecked[chugIds[x]]) {
					checkedText = " checked=1 ";
				}
				checkbox = '<input class=\'form-check-input\' type=checkbox' + checkedText;
				checkbox += 'data-x="' + chugIds[x] + '"';
				checkbox += ' data-y="' + chugIds[y] + '"';
				checkbox += ' style="outline: 1px solid #1e5180;"/>';
				html += checkbox;
				html += "</td>";
			}
			html += "</tr>";
		}
		html += "</tbody></table></div>";
		target.html(html); // Display the table.
		show('loading', false);
		show('page', true);
		// For debugging: alert when a box is checked.
		//target.on('change', 'input:checkbox', function() {
		//var $this = $(this),
		//  x = $this.data('x'),
		//  y = $this.data('y'),
		//  checked = $this.prop('checked');
		//alert('checkbox changed chug intersection (' + x + ', ' + y + '): ' + checked);
		//});
	}).then(function () {
		$('#matrix').DataTable({
			autoWidth: false,
			columnDefs: [{ className: "chugBox p-1", targets: "_all" }],
			fixedHeader: true,
			fixedColumns: true,
			ordering: false,
			scrollCollapse: true,
			initComplete: function (settings, json) {  
				// from https://stackoverflow.com/a/51667956
				$("#matrix").wrap("<div style='overflow:auto; width:100%;position:relative;' class='wrapper'></div>");            				
			},
		});
		$("#SaveChanges").toggle();
	});
});

$(function () {
	$("#SaveChanges").click(function (event) {
		// Compute redirect URL.
		var curUrl = window.location.href;
		var homeUrl = curUrl.replace("exclusionMatrix.html", "staffHome.php");
		// Remove query string before redir.
		var qpos = homeUrl.indexOf("?");
		if (qpos > 0) {
			homeUrl = homeUrl.substr(0, qpos);
		}
		// Prepare to send ajax.  We send and reset the array when we hit our max
		// value, because PHP only allows a configurable number of input vars in a
		// submit (max-input-vars, default 1,000).  See:
		// php.net/manual/en/info.configuration.php#ini.max-input-vars
		var maxSubmitSize = 750;
		event.preventDefault();
		var leftRight2Checked = {};
		var count = 0;
		var ajaxError = 0;
		$('#matrix').find('input[type="checkbox"]').each(function () {
			var $this = $(this);
			var leftChug = $this.data('x');
			var rightChug = $this.data('y');
			if (leftChug in leftRight2Checked == false) {
				leftRight2Checked[leftChug] = {};
			}
			leftRight2Checked[leftChug][rightChug] = $this.prop('checked') ? 1 : 0;
			if (++count >= maxSubmitSize) {
				$.ajax({
					url: 'matrix.php',
					type: 'post',
					data: { update_table: 1, checkMap: leftRight2Checked },
					success: function (data) {
					},
					error: function (xhr, desc, err) {
						console.log(xhr);
						console.log("Details: " + desc + "\nError:" + err);
						var errHtml = "<div class=panel_heading><h3>Unable to save changes:</h3></div><div class=\"panel-body\">" + err + ". Please contact an administrator.</div>";
						$("#errors").html(errHtml);
						leftRight2Checked = {};
						ajaxError = 1;
						return false; // Break out of the .each loop.
					}
				});
				// Reset our counter and object, and continue the .each loop.
				leftRight2Checked = {};
				count = 0;
			}
		});
		// Send any remaining data after the loop finishes.
		if (!jQuery.isEmptyObject(leftRight2Checked)) {
			$.ajax({
				url: 'matrix.php',
				type: 'post',
				data: { update_table: 1, checkMap: leftRight2Checked },
				success: function (data) {
				},
				error: function (xhr, desc, err) {
					console.log(xhr);
					console.log("Details: " + desc + "\nError:" + err);
					var errHtml = "<div class=panel_heading><h3>Unable to save changes:</h3></div><div class=\"panel-body\">" + err + ". Please contact an administrator.</div>";
					$("#errors").html(errHtml);
					ajaxError = 1;
				}
			});
		}
		if (!ajaxError) {
			homeUrl += "?update=ex";
			window.location.href = homeUrl;
		}
	});
});


