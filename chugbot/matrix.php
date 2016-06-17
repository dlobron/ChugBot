<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
          "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
  <head>
  <meta charset="utf-8" />
  <title>Exclusion Matrix</title>
  <link rel="stylesheet"
        href="jquery/ui/1.11.4/themes/smoothness/jquery-ui.css" />
  <script src="jquery/jquery-1.11.3.min.js"></script>
  <script src="jquery/ui/1.11.4/jquery-ui.js"></script>
  <script src="meta/matrix.js"></script>
  <link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/u/dt/jqc-1.12.3,dt-1.10.12,fc-3.2.2,fh-3.1.2/datatables.min.css"/>
  <script type="text/javascript" src="https://cdn.datatables.net/u/dt/jqc-1.12.3,dt-1.10.12,fc-3.2.2,fh-3.1.2/datatables.min.js"></script>
  <link rel="stylesheet" href="meta/view.css" />
  <style>
    .control_buttons {
    position: relative;
    margin: auto;
    margin-top: 20px;
    text-align: center;
    width:35%;
    -moz-border-radius: 10px;
    -webkit-border-radius: 10px;
    }
    .control_buttons input[type=submit] {
    padding:3px 8px;
    color:black;
    background-color: #cccccc;
    border:1px solid grey;
    box-shadow: 2px 2px grey;
    cursor:pointer;
    -webkit-border-radius: 3px;
    -moz-border-radius: 3px;
    border-radius: 3px; 
    font-size: 1.5em;    
    }
    .matrix_container
    {
    position: relative;
    overflow: auto;
    margin-top: 20px;
    margin-left: 10px;
    width: 95%;
    font-size: 1.2em;
    background: #fff;
    padding-left: 5px;
    padding-right: 5px;
    padding-bottom: 5px;
    border: 1px black;
    border-radius: 5px;
    border-collapse: collapse;
    -moz-border-radius: 5px;
    -webkit-border-radius: 5px;
    }
  </style>
  </head>
  <body>
    
    <div id="nav"></div>

    <div class="centered_container">
      <h2>De-Duplication Matrix</h2>
      <p>This page lets you tell the system that certain chugim should not be
	assigned to the same camper in the same block as certain other chugim.
	To disallow a chug combination, find the first chug on the left side, and
	match it to the column headed by the second chug.  
      <p>For example, to prevent the system from assigning both Cooking and
	Outdoor Cooking, find the row with "Cooking" on the left, then locate
	the column with "Outdoor Cooking" at the top, and click the checkbox at
	the intersection of that row/column.</p>
      <p>When you are done, click "Save Changes" at the bottom of the matrix.</p>
    </div>

    <div id="errors"></div>
    
    <div class="image_container"><img src="images/Matrix.jpg" alt="" /></div>

    <div id="checkboxes"><div class="message_container">Building matrix: please wait...</div></div>
    </div>
    
    <div class="control_buttons">
      <input title="Save changes and exit this page"  type="submit" name="SaveChanges" id="SaveChanges" value="Save Changes" />
    </div>
    
  </body>
</html>
