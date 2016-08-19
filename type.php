<?php
include 'lib/sopDev.php';

$header = new template ();

// saving the document data back to the database
if (isset ( $_POST ['docTitle'], $_POST ['docVersion'] ))
	//if (isset($_POST['document_id'], $_POST['existingVersion'])) 
	{) 
	
	// Check if user logged in. If not then ask to login
	if (isset ( $_SESSION ['login_user'] )) {
		
		$docTitle = $header->db->escape_string ( $_POST ['docTitle'] );
		$docVersion = $header->db->escape_string ( $_POST ['docVersion'] );
		
		// handle creation of new document by testing $formSaveDocID.
		$string = "SELECT id, title, status 
					   FROM tblDocument 
					   WHERE title ='" . $docTitle . "' AND version = '" . $docVersion . "';";
		// var_dump($string);
		$result = $header->db->query ( $string );
		
		while ( $row = $result->fetch_assoc () ) {
			$docID = $row ['id'];
			$docTitle = $row ['title'];
			$docStatus = $row ['status'];
		}
		
		if ($docStatus == 'Available') {
			
			// Set session document id
			$_SESSION ['docID'] = $docID;
			$_SESSION ['docTitle'] = $docTitle;
			
			// Move to finalize page. No edits allowed.
			header ( 'Location: finalize.php' );
			exit ();
		}
	}
}

if (isset ( $_POST ['clearDoc'] )) {
	unset ( $_SESSION ['docID'] );
	unset ( $_SESSION ['docTitle'] );
	
	header ( 'Location: requirements.php' );
	exit ();
}

// handle autocomplete search request
if (isset ( $_POST ['title'] )) {
	
	// if ($header->authorized()) {
	
	// TODO: do we need to urldecode() first?
	// it seems '/', '=', ',', '%', '+', '"' get encoded
	// while "'" "(" do not
	$term = $header->db->escape_string ( $_POST ['title'] );
	
	$string = "SELECT DISTINCT title
					   FROM tblDocument 
					   WHERE title LIKE '%$term%' LIMIT 30;";
	
	$result = $header->db->query ( $string );
	
	$answer = array ();
	
	while ( $row = $result->fetch_assoc () )
		$answer [] = $row ['title'];
	$result->free ();
	echo json_encode ( $answer );
	
	exit ();
}

// Handling version selection
if (isset ( $_POST ['fulltitle'] )) {
	
	$term = $header->db->escape_string ( $_POST ['fulltitle'] );
	
	$string = "SELECT version
					   FROM tblDocument 
					   WHERE title = '" . $term . "' LIMIT 30;";
	
	$result = $header->db->query ( $string );
	
	$answer = array ();
	
	while ( $row = $result->fetch_assoc () )
		$answer [] = $row;
	$result->free ();
	echo json_encode ( $answer );
	
	exit ();
}

if (isset ( $_POST ['UserLookup'] )) {
	if (! function_exists ( "ldap_connect" ))
		die ( 'Missing php5-ldap package' );
		
		// Connect to ldap
	$ldap = ldap_connect ( 'mccorpdc1.chmccorp.cchmc.org' ) or die ( 'Authentication system missing.' );
	
	// Bind with bmisop login
	$msg = ldap_bind ( $ldap, "CN=bmisop,OU=Service Accts,OU=Specialized Accts,DC=chmccorp,DC=cchmc,DC=org", "cchmc:bmisop" );
	
	// set search filter
	$filter = "(SamAccountName=" . $user . ")";
	
	// search ldap for user and return dn
	$usrdn = ldap_search ( $ldap, "ou=MAIN,dc=chmccorp,dc=cchmc,dc=org", $filter ) or die ( "Unable to perform search" );
	$entry = ldap_first_entry ( $ldap, $usrdn );
	
	$error = "Central Login<BR><I><font size=2 color='Red'>Username and password combination not found</font></I>";
	
	if (! $entry or ! $pass) {
		$this->message = $error;
		return false;
	}
	
	$dn = ldap_get_dn ( $ldap, $entry );
	
	// Now that we have the dn, use the dn to bind
	$msg = @ldap_bind ( $ldap, $dn, $pass );
	
	if (! $msg) {
		$this->message = $error;
		return false;
	}
	
	ldap_close ( $ldap );
	
	return $msg;
}

// handle autocomplete search request
if (isset ( $_POST ['formAJAXRqstTitle'], $_POST ['formAJAXRqstVers'] )) {
	$formVals [] = $header->db->escape_string ( $_POST ['formAJAXRqstTitle'] );
	$formVals [] = $header->db->escape_string ( $_POST ['formAJAXRqstVers'] );
	$string = "SELECT id, title, revision, version, dept, author, dept, reviewed, type, effective, status
					   FROM tblDocument 
					   WHERE title = '" . $formVals [0] . "' AND version = '" . $formVals [1] . "';";
	
	// var_dump($string);
	$result = $header->db->query ( $string );
	$answer = $result->fetch_assoc ();
	$answer ['watchers'] = '';
	$result->free ();
	
	$string = "SELECT a.email FROM tblWatchersDef as a, tblWatchers as b WHERE a.id = b.wdi AND b.di = '{$answer['id']}'";
	$result = $header->db->query ( $string );
	if ($header->db->errno)
		die ( $header->db->error );
	
	$emails = array ();
	while ( $row = $result->fetch_assoc () ) {
		$emails [] = $row ['email'];
	}
	
	$answer ['watchers'] = implode ( ',', $emails );
	
	echo json_encode ( $answer );
	
	// TODO: if the document is of status 'Available' then jump to viewing
	
	exit ();
}

// saving the document data back to the database
if (isset ( $_POST ['formSaveTitle'], $_POST ['formSaveVersion'], $_POST ['formSaveAuthor'], $_POST ['formSaveWatchers'], $_POST ['formSaveDocID'] )) {
	// Check if user logged in. If not then ask to login
	if (isset ( $_SESSION ['login_user'] )) {
		
		$formVals [] = $header->db->escape_string ( $_POST ['formSaveTitle'] );
		$formVals [] = $header->db->escape_string ( $_POST ['formSaveVersion'] );
		$formVals [] = $header->db->escape_string ( $_POST ['formSaveAuthor'] );
		$formVals [] = explode ( ',', $header->db->escape_string ( $_POST ['formSaveWatchers'] ) );
		$formVals [] = $header->db->escape_string ( $_POST ['formSaveDocID'] );
		$formVals [] = $header->db->escape_string ( $_POST ['type'] );
		$formVals [] = $header->db->escape_string ( $_POST ['formSaveDept'] );
		
		// handle creation of new document by testing $formSaveDocID.
		$string = ($formVals [4]) ?
			
						//Perform update to existing document …
						"UPDATE tblDocument SET title='" ? 
 . $formVals [0] . "',
								   	   version='" . $formVals [1] . "',
								   	   dept='" . $formVals [6] . "',
								   	   author='" . $formVals [2] . "',
								   	   type='" . $formVals [5] . "'					   	   
								   WHERE id='" . $formVals [4] . "';" :
								   
					    // … else insert new document
					    "INSERT INTO tblDocument (`title`,`version`,`dept`,`author`,`type`) VALUES ('{$formVals[0]}','{$formVals[1]}','{$formVals[6]}','{$formVals[2]}','{$formVals[5]}')" : 
;
		
		$header->db->query ( $string );
		if (! $formVals [4])
			$formVals [4] = $header->db->insert_id;
		
			if ($formVals [3]) {
			$response = array ();
			
			foreach ( $formVals [3] as $val ) {
				$email = $header->db->escape_string ( trim ( $val ) );
				
				$string = "INSERT  INTO tblWatchersDef (email)
								VALUES ('{$email}') ON DUPLICATE KEY UPDATE id=LAST_INSERT_ID(id),email = '{$email}';";
				
				$header->db->query ( $string );
				$response [] = $header->db->insert_id;
			}
			
			$string = "DELETE FROM tblWatchers WHERE di = '{$formVals[4]}'";
			$header->db->query ( $string );
			
			// functional paradigm is teh shit (used in subsequent query generation)
			function map_wdi_to_di($wdi, $di) {
				return "('$wdi', '$di')";
			}
			
			$padded_doc_id = array ();
			$string = "INSERT INTO tblWatchers VALUES " . implode ( ',', array_map ( 'map_wdi_to_di', $response, array_pad ( $padded_doc_id, count ( $response ), $formVals [4] ) ) );
			
			$header->db->query ( $string );
			if ($header->db->errno)
				die ( $header->db->error );
		}
		
		// Set session document id
		$_SESSION ['docID'] = $formVals [4];
		$_SESSION ['docTitle'] = $formVals [0];
		
		header ( 'Location: requirements.php' );
		exit ();
	}
}

$header->display ();

?>
<style type="text/css">
.hideDiv {
	display: none
}
</style>





<!--        **************************************************************
	 		   BEGIN PAGE CONTROLS
	 		**************************************************************-->

<div id='type' class='ui-tabs-panel ui-widget-content ui-corner-bottom'>
	<form id='formSaveDoc' action='<?php echo $_SERVER['PHP_SELF'] ?>'
		method='post'>	
			
<?php

if (isset ( $_SESSION ['docID'] )) {
	echo "<form action='" . $_SERVER ['PHP_SELF'] . "' method='post'><input type='submit' value='Clear selected document' id='clearDocument' name='clearDoc'>";
	echo "</form><div style='display:none;'>";
} else
	echo "<div>";
?>				
			<h2>Choose a project</h2>
		<p>
			<input type='radio' name='type' id='SOP' value='SOP'> <label
				for='sop'>New SOP</label><br> <input type='radio' name='type'
				id='JACD' value='Job Aid CD'> <label for='controlled_aid'>New Job
				Aid (Controlled)</label><br> <input type='radio' name='type'
				id='JAUD' value='Job Aid UD'> <label for='uncontrolled_aid'>New Job
				Aid (Uncontrolled)</label><br> <input type='radio' name='type'
				id='exisiting_doc' value='existing_doc'> <label for='existing_doc'>Modify
				Existing Document</label><br>
		</p>

</div>


<div id="titleDiv" class="hideDiv">
	<table>
		<tr>
			<td valign="top" width="175"><label for="title" id="titleLabel">Document
					Title</label></td>
			<td><textarea id="title" style="width: 640px; height: 74px;"
					name="formSaveTitle"></textarea></td>
		</tr>
	</table>
</div>

<div id="versionDiv" class="hideDiv">
	<table>
		<tr>
			<td valign="top" width="175"><label for="version" id="versionLabel">Version
					Number</label></td>
			<td><input type="text" id="version" name="formSaveVersion"></td>
		</tr>
	</table>
</div>

<div id="deptDiv" class="hideDiv">
	<table>
		<tr>
			<td valign="top" width="175"><label for="dept" id="deptLabel">Department</label></td>
			<td><input type="text" id="dept" name="formSaveDept"></td>
		</tr>
	</table>
</div>

<div id="authorDiv" class="hideDiv">
	<table>
		<tr>
			<td valign="top" width="175"><label for="author" id="authorLabel">Author</label></td>
			<td><input type="text" id="author" name="formSaveAuthor"></td>
		</tr>
	</table>
</div>

<div id="watchersDiv" class="hideDiv">
	<table>
		<tr>
			<td valign="top" width="175"><label for="watchers" id="watchersLabel">Watchers</label></td>
			<td><textarea id="watchers" style="width: 640px; height: 74px;"
					name="formSaveWatchers"></textarea> (Separate each user by a comma)</td>
		</tr>
	</table>
</div>

<input type="hidden" id="docID" name="formSaveDocID">

<input class="hideDiv" type="submit" value="Save and Continue"
	id="saveDoc">
</form>
<form id="formOpenDoc" action='<?php echo $_SERVER['PHP_SELF'] ?>'
	method="POST">
	<div id="docDiv" class="hideDiv">
		<table>
			<tr>
				<td valign="top" width="175"><label id="docLabel" for="document_id">Search
						for document<BR>
					<font size='2'>(Begin typing for search)</font>
				</label></td>
				<td><textarea id="document_id" name="docTitle"
						style="width: 400px; display: inline"></textarea></td>
			</tr>

		</table>
	</div>

	<div id="existVer" class="hideDiv">
		<table>
			<tr>
				<td valign="top" width="175"><label for="existingVersion">Select
						Version</label></td>
				<td><select id="existingVersion" name="docVersion"
					style="width: 150px"></select></td>
			</tr>
		</table>
	</div>

	<input class="hideDiv" type="submit" value="View document" id="viewDoc">
</form>

<input class="hideDiv" type="submit" value="Open document" id="openDoc"
	onclick="openDoc();">


<!-- Open a document that is 'Available' -->



<input class="hideDiv" type="submit" value="Review document"
	id="reviewDoc" onclick="reviewDoc()">

</div>
<!--        **************************************************************
	 		   END PAGE CONTROLS
	 		**************************************************************-->



<!--        **************************************************************
	 		   BEGIN JAVASCRIPT
	 		**************************************************************-->

<script type="text/javascript">

			
			var editFields = '#title, #version, #dept, #author, #watchers';
			var editFieldDivs = '#titleDiv, #versionDiv, #deptDiv, #authorDiv, #watchersDiv, #saveDoc';
			var selectFields = '#document_id';
			var selectFieldDivs = '#docDiv, #existVer, #openDoc';
			
			//Show or hide controls based on type of document selected
			$('input[name=type]:radio').change(function() {

				var typeVal = $(this).val();

				//If an existing document
				if (typeVal == "existing_doc")
				{	
					//hide all edit fields, clear them of any content, 
					//and show selection fields while doing selection
					toggleEditFields('hide','medium');
					clearFields();
   					toggleSelectionFields('show','medium');
   					$('#document_id').autocomplete({
   						source: function( request, response ) {
							$.post("<?php echo $_SERVER['PHP_SELF'] ?>",
								{title: $('#document_id').val()},
								function( data ) {
									response(data)
								},
								'json'
		   					);
		   				},
		   				minLength: 2,

						change: function( event, ui ) {
							 														
							
								$.post('<?php echo $_SERVER["PHP_SELF"] ?>' , {fulltitle: $('#document_id').val()}, function(data){

									//Select the document version dropdown
									var elSel = document.getElementById('existingVersion');

							 		//Remove all options from version selection	
							 		var i;
							 		for(i=elSel.options.length-1;i>=0;i--)
							 		{
							 			elSel.remove(i);
							 		}
							 		 
									//Add options to version selection
							 		$.each(data, function(key, val) { 
							 			var optNew = document.createElement('option');	
							 			optNew.text = val.version;
							 		    optNew.value = val.version;	

							 		   try {
							 		      elSel.add(optNew, null); // standards compliant; doesn't work in IE
							 		    }
							 		    catch(ex) {
							 		      elSel.add(optNew); // IE only
							 		    }			 		    
							 							
							 		 });
							 		 
								}, 'json');								
							
						},

						open: function() {
							$( this ).removeClass( "ui-corner-all" ).addClass( "ui-corner-top" );
						},
						close: function() {
							$( this ).removeClass( "ui-corner-top" ).addClass( "ui-corner-all" );
						}
					});
				}
				else
				{
					//hide selection fields, clear all fields of content
					//and show edit fields
			      	toggleSelectionFields('hide','medium');
			      	clearFields();
					toggleEditFields('show','medium');				
				}	
			});

			
			
			function openDoc()
			{						
				$.post('<?php echo $_SERVER["PHP_SELF"] ?>', {formAJAXRqstTitle: $('#document_id').val(), formAJAXRqstVers: $('#existingVersion option:selected').text()},
					function(data){
						var status;
						var type;
				 			$('#title').val(data.title);
				 			$('#version').val(data.version);
				 			$('#dept').val(data.dept);
				 			$('#author').val(data.author);
				 			$('#docID').val(data.id);
				 			$('#watchers').val(data.watchers);
				 			type = data.type;				 			
				 			status = data.status;
							
						//Checking to see if the document returned is in development
						switch(status) {			
									            
				            case 'Development':
				            	toggleSelectionFields('hide','medium');	
								toggleEditFields ('show','medium');	
				                break;
				            case 'Available':
					            //TODO: create actions for existing available document
					            $('#viewDoc').show();
					            break;
				            case 'Review':
					            //TODO: create actions
				            	//$('reviewDoc').show();
				                
				        	};

					    //Set radio button to document type
						switch(type) {			
										            
					            case 'SOP':
					            	$('input[name="type"]')[0].checked = true;
					                break;
					            case 'Job Aid CD':
					            	$('input[name="type"]')[1].checked = true;
					            	break;
					            case 'Job Aid UD':
					            	$('input[name="type"]')[2].checked = true;
					            	
					                
					        	};
				        	
				       						
				},'json');						
			}			

			function clearDoc()
			{						
				$.post('<?php echo $_SERVER["PHP_SELF"] ?>',{clearDoc: "clearDoc"});
			}

			function viewDoc()
			{						
				
				$.submit('<?php echo $_SERVER["PHP_SELF"] ?>', {docTitle: $('#title').val(),docVersion: $('#version').val()});
			}
			
		    function toggleEditFields (state, speed) 
		    {
			    if (state == 'show') $(editFieldDivs).show(speed);				
			    else $(editFieldDivs).hide(speed);  				
		    }

		    
		    function toggleSelectionFields (state, speed) 
		    {
		    	if (state == 'show') $(selectFieldDivs).show(speed);
				else $(selectFieldDivs).hide(speed);								
		    }

		    function clearFields ()
		    {
		    	$(editFields).each(function() 
						{
					        switch(this.type) {
				            case 'password':
				            case 'select-multiple':
				            case 'select-one':
				            case 'text':
				            case 'textarea':
				                $(this).val('');
				                break;
				            case 'checkbox':
				            case 'radio':
				                this.checked = false;
				        	};
						});	
				 //clear the version selection box
				 var select = $('#existingVersion');					
				 var options = select.attr('options');			
				 $('option', select).remove();   

				 $('#document_id').val('');      		   
		    }
		    </script>

<!--        **************************************************************
	 		   END JAVASCRIPT
	 		**************************************************************-->
<?php $header->footer(); ?>