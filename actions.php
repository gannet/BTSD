<?php
include 'lib/sopDev.php';
include 'lib/progress.php';

$header = new template ();
//
// $progress = new progress($header);
// $_SESSION['docID']='1';
// if ($progress->status < 3) $progress->moveTo();
function array_push_assoc($array, $key, $value) {
	$array [$key] = $value;
	return $array;
}

if (isset ( $_POST ['submit_actions'] )) {
	
	$actions = "";
	$existing = "";
	
	// Create string to use later for INSERT into tblDocActions
	foreach ( $_POST ['ai_list'] as $ai ) {
		$itemText = $header->db->escape_string ( $_POST ['actionItem' . $ai] );
		// If list item is blank then don't save it.
		if ($_POST ['actionItem' . $ai] != "") {
			
			$actions = $actions . "(" . $_SESSION ['docID'] . "," . $ai . ",'" . $itemText . "','0','0','0'),";
		}
	}
	
	$actions = rtrim ( $actions, "," );
	
	// Clear all previous requirements before adding new ones
	$string = "DELETE FROM tblDocItems WHERE di = '{$_SESSION['docID']}' AND header=0;";
	$header->db->query ( $string );
	
	// Add new requirements
	$string = "INSERT INTO tblDocItems (di,ri,content,`order`,depth,header)
							VALUES {$actions} ON DUPLICATE KEY UPDATE id=LAST_INSERT_ID(id);";
	// var_dump($string);
	$header->db->query ( $string );
	$response [] = $header->db->insert_id;
	
	header ( 'Location: construct.php' );
	exit ();
}

$header->display ();

?>

<script type="text/javascript">
			$(function() {
				$( "#action_items" ).accordion();
			});
		</script>


<!-- Creating all elements in a form for submission later  -->
<BR>
<BR>
<div style='margin-top: 5px; padding: 0pt 0.7em;'
	class='ui-state-highlight'>
	<strong>Document Item</strong> (Fill in an action item for each
	document item)
</div>

<form action="<?php $_SERVER['PHP_SELF'] ?>" method='post'>



	<div id='action_items'>	
 		
<?php
// pull all documents and their content from the database
$string = "SELECT tblDocRequirements.id, tblDocRequirements.rdi, tblDocRequirements.description, tblDocRequirements.di,
							  tblDocItems.content							 													
						FROM tblDocRequirements 
						LEFT JOIN tblDocItems
						ON (tblDocRequirements.id = tblDocItems.ri) 												
						WHERE tblDocRequirements.di = " . $_SESSION ['docID'] . "
						ORDER BY tblDocRequirements.id;";
$action = $header->db->query ( $string );

// var_dump($action);
while ( $row = $action->fetch_assoc () ) {
	$description = $row ["description"];
	$id = $row ["id"];
	$content = $row ["content"];
	$string = "SELECT content as regContent FROM tblRegulationsDef WHERE id = " . $row ["rdi"] . " LIMIT 1;";
	$regContent = $header->db->query ( $string );
	$regulationsContent = $regContent->fetch_assoc ();
	
	echo "<h3><a href='#'>" . $description . " - <font size=2>" . $regulationsContent ['regContent'] . "</font></a></h3><div><p>";
	echo "<textarea rows='6' cols='75' id='actionItem" . $id . "' name='actionItem" . $id . "'>";
	echo $content . "</textarea></p></div>";
}

?>
			</div>
	<!-- end action_items div -->


	<select multiple="multiple" name='ai_list[]' id='ai_list'
		style="display: none">
		
<?php
// pull all documents and their content from the database
$string = "SELECT *							
							FROM tblDocRequirements				
							WHERE di = " . $_SESSION ['docID'] . "
							ORDER BY tblDocRequirements.id;"; // Pull where document has content
$action2 = $header->db->query ( $string );

while ( $row = $action2->fetch_assoc () ) {
	$description = $row ["description"];
	$id = $row ["id"];
	
	echo "<option selected value='" . $id . "'>" . $description . "</option>";
}
$action2->free ();
?>
			</select> <BR> <input type="submit" value="Submit Actions"
		id="submit_actions" name="submit_actions">
</form>



<?php $header->footer(); ?>