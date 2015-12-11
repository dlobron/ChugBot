$(document).ready(function() {
	var camperId = getParameterByName('cid');
	$.ajax({
		url: 'ajax.php',
		    type: 'post',
		    data: {rank_page_camper_id: camperId},
		    success: function(json) {
		       // Parse the JSON from the ajax page.  We expect an array
		       // of objects that map a block/group name to an array of
		       // chug names.  For each block/group, we emit a list of
		       // chugim and a drag target where they can be ordered.  I
		       // think we do this by calling 
		       // $("body").append(html);
		       // where html is the html text for the ULs.  Each will have
		       // the same ID, but a different name, so we can capture the
		       // contents of each and send it back to ajax.php to be put in
		       // the DB.
		       var html = "";
		       $.each(json, 
			      function(blockname, block2groupmap) {
				  $.each(block2groupmap, function(blockname, chuglist) {
					  $.each(chuglist, function(index, value) {
					      });
				      });   
			      });
		    },
		    error: function(xhr, ajaxOptions, thrownError) {
		       console.log(xhr.status);
		       console.log(thrownError);
		    }
	    }); // end ajax call
    });
