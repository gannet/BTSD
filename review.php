<?php
include 'lib/sopDev.php';
include 'lib/progress.php';

$header = new template ();

if (isset ( $_POST ['reviewed'] )) {
	$mysqldate = date ( 'Y-m-d' );
	
	$string = "UPDATE tblDocument 
					   SET reviewed = '" . $mysqldate . "' 
					   WHERE id = " . $_SESSION ['docID'] . ";";
	$result = $header->db->query ( $string );
	
	header ( 'Location: finalize.php' );
	exit ();
}

$header->display ();

?>
<form style="float: right" action="/review.php" method="post">

	<input type="submit" name="reviewed" value="Continue onto finalize">
</form>
<BR>
<BR>


<table align='center'>
	<tr>
		<td><object style="align: center" data=page.php width="800"
				height="1000">
				<embed src=page.php width="800" height="1000">
				</embed>
				Error: Embedded pdf could not be displayed. <BR>Please contact the
				administrator.
			</object></td>
	</tr>
</table>


<?php $header->footer(); ?>
