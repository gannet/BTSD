<?php
include 'lib/sopDev.php';
include 'lib/progress.php';

$header = new template ();

// $progress = new progress();
// if ($progress->status < 6) $progress->moveTo();

if (isset ( $_POST ['formSaveDocStatus'], $_POST ['formSaveEffectiveDate'] )) {
	
	$mysqldate = date ( 'Y-m-d', strtotime ( $_POST ['formSaveEffectiveDate'] ) );
	$string = "UPDATE tblDocument 
					   SET effective = '" . $mysqldate . "',status ='" . $_POST ['formSaveDocStatus'] . "' 
					   WHERE id = " . $_SESSION ['docID'] . ";";
	$result = $header->db->query ( $string );
}

$string = "SELECT id, title, dept, version, author, reviewed, type, DATE_FORMAT( effective, '%m/%e/%Y') as effectiveDate, status
					   FROM tblDocument 
					   WHERE id = " . $_SESSION ['docID'] . ";";

$result = $header->db->query ( $string );
while ( $row = $result->fetch_assoc () ) {
	$id = $row ["id"];
	$title = $row ["title"];
	$dept = $row ["dept"];
	$version = $row ["version"];
	$author = $row ["author"];
	$reviewed = $row ["reviewed"];
	$type = $row ["type"];
	$effective = $row ["effectiveDate"];
	$status = $row ["status"];
}
// header("Content-Disposition: attachment; filename='pagePrintable.php'");
$header->display ();

?>
<div style='margin-top: 20px; padding: 0pt 0.7em;'
	class='ui-state-highlight ui-corner-all'>
	<p>
		<span style='float: left; margin-right: 0.3em;'
			class='ui-icon ui-icon-info'></span>
<?php
// Check status and customize message based on current status;
if ($status == 'Development')
	echo "To finalize your document, set the status to <strong>'Available'</strong> this will block future edits and remove the watermark.<br>";
else if ($status == 'Available')
	echo "To print your document, use the controls within the PDF window.<br>";
?>
		</p>
</div>

<div id="finalize"
	class="ui-tabs-panel ui-widget-content ui-corner-bottom">
	<form id="formSaveDoc" action="<?php echo $_SERVER['PHP_SELF'] ?>"
		method="post">

		<div id="docStatus">
			<table>
				<tr>
					<td valign="top" width="175"><label for="selectDocStatus"
						id="selectDocStatusLabel">Document Status</label></td>
					<td><select id="selectDocStatus" name="formSaveDocStatus">
								
									<?php
									// $options = array('Available','Archived','Development','Removed','Review');
									$options = array (
											'Available',
											'Development' 
									);
									
									foreach ( $options as $option ) {
										
										$selected = ($status == $option) ? 'selected="selected"' : '';
										print ("<option value=\"$option\"$selected>$option</option>") ;
									}
									
									?>
									
								</select></td>
				</tr>
			</table>
		</div>

		<div id="effDateDiv">
			<table>
				<tr>
					<td valign="top" width="175"><label for="effectiveDate"
						id="effectiveDateLabel">Effective Date</label></td>
					<td><input type="text" id="effectiveDate" width="100"
						value="<?php echo $effective; ?>" name="formSaveEffectiveDate"></td>

				</tr>
			</table>

		</div>

		<input type="submit" value="Save and Continue" id="saveDoc">

	</form>
</div>

<script type="text/javascript">

 			

 			$(document).ready(function() {
	 			$('#effectiveDate').focusout(function(){
	 	 			checkDate();
	 				
	 			})
 			});

 			$("#selectDocStatus").change(function() { 	 			
 				 if($(this).val() == 'Development' && $("#effectiveDate").val() != "") 
 				 {	    
	 				  	if (confirm("Changing the value to 'Development' will clear the effective date") == true) 
			         		$("#effectiveDate").val(null); 
			        	else 
			           		$("#selectDocStatus").val("Available");
				  }      
 			});
 			
 			$(function() {
 				$( "#effectiveDate" ).datepicker();
 			});
 			
	 		function isDate(txtDate) {
	 		    var objDate,  // date object initialized from the txtDate string
	 		        mSeconds, // txtDate in milliseconds
	 		        day,      // day
	 		        month,    // month
	 		        year;     // year

	 		    if (txtDate.length == 0) return true;    
	 		    // date length should be 10 characters (no more no less)
	 		    if (txtDate.length !== 10) {
	 		        return false;
	 		    }
	 		    // third and sixth character should be '/'
	 		    if (txtDate.substring(2, 3) !== '/' || txtDate.substring(5, 6) !== '/') {
	 		        return false;
	 		    }
	 		    // extract month, day and year from the txtDate (expected format is mm/dd/yyyy)
	 		    // subtraction will cast variables to integer implicitly (needed
	 		    // for !== comparing)
	 		    month = txtDate.substring(0, 2) - 1; // because months in JS start from 0
	 		    day = txtDate.substring(3, 5) - 0;
	 		    year = txtDate.substring(6, 10) - 0;
	 		    // test year range
	 		    if (year < 1000 || year > 3000) {
	 		        return false;
	 		    }
	 		    // convert txtDate to milliseconds
	 		    mSeconds = (new Date(year, month, day)).getTime();
	 		    // initialize Date() object from calculated milliseconds
	 		    objDate = new Date();
	 		    objDate.setTime(mSeconds);
	 		    // compare input date and parts from Date() object
	 		    // if difference exists then date isn't valid
	 		    if (objDate.getFullYear() !== year ||
	 		        objDate.getMonth() !== month ||
	 		        objDate.getDate() !== day) {
	 		        return false;
	 		    }
	 		    // otherwise return true
	 		    return true;
	 		}


	 		function checkDate(){
	 		    // define date string to test
	 		    var txtDate = document.getElementById('effectiveDate').value;
	 		    // check date and print message
	 		    if (isDate(txtDate)) {
	 		        
	 		    }
	 		    else {
	 		        alert('Invalid date format!');
	 		    }
	 		}

		
	 		$(function() {
	 			$( "#pages" ).tabs();
	 		});
		</script>

<?php

// Call the pdf page for the status the document is in.
?>
<div id="pages">
	<ul>
		<li><a href="#tabs-1">Printable PDF without traceback tags</a></li>
			<?php
			if ($status == 'Available') {
				echo "<li><a href='#tabs-2'>Printable PDF with traceback tags</a></li>";
			}
			?>
		</ul>

	<div id="tabs-1">
		<object
			data=<?php if ($status=='Development') echo "page.php"; else if ($status=='Available') echo "pagePrintable.php";?>
			width='800' height='1100'>
			<embed
				src=<?php if ($status=='Development') echo "page.php"; else if ($status=='Available') echo "pagePrintable.php"; ?>
				width='800' height='1100'>
			</embed>
			Error: Embedded pdf could not be displayed. <BR>Please contact the
			administrator
		</object>
	</div>	
					
			<?php
			if ($status == 'Available') {
				echo "<div id='tabs-2'><li><table align='center'><td>
						<object data='pagePrintableWTags.php' width='800' height='1100'> 
							<embed src='pagePrintableWTags.php' width='800' height='1100'>
							</embed> 
							Error: Embedded pdf could not be displayed. <BR>Please contact the administrator
						</object>
					</td></table></li></div>";
			}
			?>
		
	</div>


<?php
$header->footer ();

?>
							