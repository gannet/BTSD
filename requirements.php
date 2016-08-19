<?php
include 'lib/sopDev.php';
include 'lib/progress.php';

$header = new template ();

// pull all documents and their content from the database
$string = "SELECT DISTINCT 
					    tblRegulations.regulatoryBody								
				FROM tblRegulations				
				ORDER BY tblRegulations.regulatoryBody;"; // Pull where document has content
$regs = $header->db->query ( $string );

// pull all documents and their content from the database
$string = "SELECT tblRegulationsDef.id,
					  tblDocRequirements.di, tblDocRequirements.description					  				
					FROM tblRegulationsDef, tblDocRequirements
					WHERE tblRegulationsDef.description = tblDocRequirements.description AND tblDocRequirements.di = " . $_SESSION ['docID'] . ";";
$requirements = $header->db->query ( $string );
$debugMess = "Just after Reg body query";
// pull all documents and their content from the database
if (isset ( $_POST ['regBody'] )) {
	$string = "SELECT tblRegulations.id,
						  tblRegulations.title					  				
					FROM tblRegulations
					WHERE tblRegulations.regulatoryBody='" . $_POST ['regBody'] . "' ORDER BY tblRegulations.title,tblRegulations.effective;";
	
	$result = $header->db->query ( $string );
	$debugMess = "In isset regBody";
	$response = array ();
	while ( $row = $result->fetch_assoc () ) {
		$response [] = $row;
	}
	echo json_encode ( $response );
	exit ();
}

if (isset ( $_POST ['docSelect'] )) {
	
	$string = "SELECT tblRegulationsContent.id,
						  tblRegulationsContent.content					  				
					FROM tblRegulationsContent
					WHERE tblRegulationsContent.ri='" . $_POST ['docSelect'] . "' ORDER BY tblRegulationsContent.id;";
	$result = $header->db->query ( $string );
	$response = array ();
	while ( $row = $result->fetch_assoc () ) {
		$response [] = $row;
	}
	echo json_encode ( $response );
	exit ();
}

if (isset ( $_POST ['regContent'] )) {
	
	$string = "SELECT tblRegulationsDef.id,
						  tblRegulationsDef.description					  				
					FROM tblRegulationsDef
					WHERE tblRegulationsDef.rci='" . $_POST ['regContent'] . "' ORDER BY tblRegulationsDef.id;";
	$result = $header->db->query ( $string );
	$response = array ();
	while ( $row = $result->fetch_assoc () ) {
		$response [] = $row;
	}
	echo json_encode ( $response );
	exit ();
}

// if form submitted
if (isset ( $_POST ['selectedReq'] )) {
	// console.log("Made it into selectedReq");
	// Get titles before forming the insert
	$string = "SELECT id, description, '" . $_SESSION ['docID'] . "'
				   FROM tblRegulationsDef
				   WHERE id in(" . implode ( ",", $_POST ['selectedReq'] ) . ");";
	
	$result = $header->db->query ( $string );
	
	$req = "";
	$reqDescription = "";
	
	while ( $row = $result->fetch_assoc () ) {
		$req = $req . "('" . implode ( "','", $row ) . "'),";
		$reqDescription = $reqDescription . "'" . $row ['description'] . "',";
	}
	$req = rtrim ( $req, "," );
	$reqDescription = rtrim ( $reqDescription, "," );
	
	// Working on pulling regulations descriptions to be place in the requirements table.
	
	// Clear all previous requirements before adding new ones
	$string = "DELETE FROM tblDocRequirements WHERE di = '{$_SESSION['docID']}' AND NOT(description IN(" . $reqDescription . "));";
	// var_dump($string);
	$header->db->query ( $string );
	
	// Add new requirements
	$string = "INSERT INTO tblDocRequirements (rdi,description,di)
							VALUES {$req} ON DUPLICATE KEY UPDATE id=LAST_INSERT_ID(id);";
	// var_dump($string);
	$header->db->query ( $string );
	$response [] = $header->db->insert_id;
	
	header ( 'Location: actions.php' );
	exit ();
}

$header->display ();

?>
<style type="text/css">
#content,#selected {
	cursor: pointer;
	border-style: solid;
	border-width: 1px;
	width: 150px;
	height: 285px;
	overflow: auto;
	list-style-type: none;
	margin: 0;
	padding: 0;
	margin-right: 10px;
	background: white;
}

#content li,#selected li {
	margin: 2px;
	padding: 1px;
	width: 140px;
}

#requirements div {
	float: left;
	margin: 4px
}

.scrollable {
	overflow: auto;
	width: 150px;
	/* adjust this width depending to amount of text to display */
	height: 285px;
	/* adjust height depending on number of options to display */
	border: 1px silver solid;
}

.scrollable select {
	border: none;
}
</style>
<script>
				$(function() {
					$( "ul.droptrue" ).sortable({						
						connectWith: "ul"
						
					});
			
					$( "ul.dropfalse" ).sortable({
						connectWith: "ul",						
						dropOnEmpty: false
					});
			
					$( "#content, #selected" ).disableSelection();
					

				});	
		</script>




<div id="requirements" style="font-size: 0.8em; height: 400px">


	<BR>

	<div>
		<H5>
			1: Select a regulatory <BR>body -->
		</H5>
		<div class="scrollable">
			<select size=15 id="regBody" style="width: 500px">
 				
<?php

while ( $row = $regs->fetch_assoc () ) {
	$regBody = $row ["regulatoryBody"];
	echo '<option value="' . $regBody . '">' . $regBody . '</option>';
}
$regs->free ();
?>

				</select>
		</div>
	</div>



	<div>
		<H5>
			2: Pick a regulatory <BR>document ->
		</H5>
		<!--			Second box to be populated by 1st box-->
		<div class="scrollable">
			<select size=15 id="docSelect" style="width: 500px">
			</select>
		</div>
	</div>

	<div>
		<H5>
			3: Pick relevant <BR>content ->
		</H5>
		<!--			Third box to be populated by 2nd box-->
		<div class="scrollable">
			<select size=15 id="regContent" style="width: 500px">
			</select>
		</div>
	</div>

	<!--			Fourth box to be populated by 3rd box-->
	<div>
		<H5>
			4: Drag content to <BR>selection list ->
		</H5>
		<ul id="content" class='droptrue'>

		</ul>
	</div>




	<!--			Fifth box to be populated by dragging items-->
	<div>
		<form action="<?php echo $_SERVER['PHP_SELF'] ?>" method="post">
			<select name="selectedReq[]" multiple="multiple" id="formSelected"
				style="display: none">
			</select> <input style="float: right;" type="submit"
				value="Submit Selection" id="submit_selection"
				onclick="populateSelected();" onkeydown="populateSelected();">
		</form>

		<H5>Selection List</H5>
		<ul id="selected" class='droptrue'>		
<?php
while ( $row = $requirements->fetch_assoc () ) {
	$id = $row ['id'];
	$description = $row ['description'];
	
	echo "<li value={$id}>{$description}</li>";
}
?>							 
					</ul>

	</div>



	<script type="text/javascript">

					
					//When first selection box clicked				
					$('#regBody').click(function() {
						$.post('<?php echo $_SERVER["PHP_SELF"] ?>' , {regBody: $('#regBody').val()}, function(data){

							//Clear the fourth box
								$('#content li').remove();
										
							//Clear the third box
								var select = $('#regContent');
								
						 		//var options = select.attr('options');  
						
						 		$('option', select).remove(); 

							//Clear the second box		
						 		var select = $('#docSelect');
						
						 		//var options = select.attr('options');  
						
						 		$('option', select).remove();  

						 		var html = '';
						 	    var len = data.length;
						 	    for (var i = 0; i< len; i++) {
						 	        html += '<option value="' + data[i].id + '">' + data[i].title + '</option>';
						 	    }
						 	    $('#docSelect').append(html);
						 	   
						 	    
//						 		$.each(data, function(key, val) {  
//						 								 		
//						 		     options[options.length] = new Option(val.title, val.id);  
//									
//						 		 });  
											
						}, 'json');
						  
					});

					//When second selection box clicked	
					$('#docSelect').click(function() {
						$.post('<?php echo $_SERVER["PHP_SELF"] ?>' , {docSelect: $('#docSelect').val()}, function(data){

						//Clear the fourth box
							$('#content li').remove(); 
							
						//Clear the third box
							var select = $('#regContent');
							
					 		//var options = select.attr('options');  
					
					 		$('option', select).remove(); 

					 		var html = '';
					 	    var len = data.length;
					 	    for (var i = 0; i< len; i++) {
					 	        html += '<option value="' + data[i].id + '">' + data[i].content + '</option>';
					 	    }
					 	    $('#regContent').append(html);
					 	    
//					 		$.each(data, function(key, val) {  
//			
//					 		     options[options.length] = new Option(val.content, val.id);  
//			
//					 		 });  
											
						}, 'json');
					
					});

					//When third selection box clicked	
					$('#regContent').click(function() {
						$.post('<?php echo $_SERVER["PHP_SELF"] ?>' , {regContent: $('#regContent').val()}, function(data){

							
							$('#content li').remove();

							
					 		$.each(data, function(key, val) {
					 			
					 			$("<li value="+val.id+">"+val.description+"</li>").appendTo('#content');
					 			
					 		 });
					 		
					 					
						}, 'json');
					
					
					});


					//Before moving on to actions.php this function populates the hidden selection box and 
					function populateSelected()
					{
						//Select dropdown box and clear contents
						var select = $('#formSelected');							
				 		//var options = select.attr('options');					
				 		$('option', select).remove(); 
					
						
						//Create an array to hold selected item list
						var selected = new Array();	

						//Loop through each list item in the 'Selection Items' box													
						$('#selected li').each(function (){		
							 //add the list item value to the selected array				
							 selected.push($( this ).attr("value"));													
						});
						
							
						var html = '';
				 	    var len = selected.length;
				 	    for (var i = 0; i< len; i++) {
				 	        html += '<option value="' + selected[i] + '" selected>' + selected[i] + '</option>';
				 	    }
				 	    $('#formSelected').append(html);
							console.log("Submitted from populateSelected() " + html);
					 		//$.each(selected, function(key, val) {			
					 		//    options[options.length] = new Option(key, val,true, true);	
					 		//});
					 		//$('#formSelected option').attr("selected","selected");
						  
					
					}
					

				</script>


</div>

<div>
					Debug message: <?php echo $debugMess?>
		</div>



<?php $header->footer(); ?>