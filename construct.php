<?php
include 'lib/sopDev.php';
include 'lib/progress.php';

$header = new template ();

// progress = new progress();
// if ($progress->status < 4) $progress->moveTo();

$string = "SELECT tblDocRequirements.id as rid, tblDocRequirements.di, tblDocRequirements.rdi, tblDocRequirements.description,
					  tblDocItems.id, tblDocItems.order, tblDocItems.ri, tblDocItems.content, tblDocItems.header, tblDocItems.depth							
				FROM tblDocRequirements, tblDocItems				
				WHERE tblDocRequirements.di = " . $_SESSION ['docID'] . " AND tblDocRequirements.id = tblDocItems.ri
				UNION SELECT '','','','',tblDocItems.id,tblDocItems.order, tblDocItems.ri, tblDocItems.content, tblDocItems.header, tblDocItems.depth 							
				FROM tblDocItems				
				WHERE tblDocItems.di = " . $_SESSION ['docID'] . " AND tblDocItems.header = 1 
				ORDER BY `order`;";
$constructAll = $header->db->query ( $string );

if (isset ( $_POST ['items'] )) {
	
	$allItems = "";
	$contentText = "";
	
	foreach ( $_POST ['items'] as $row ) {
		
		$contentText = $header->db->escape_string ( $row [1] );
		
		$allItems = $allItems . "(" . $_SESSION ['docID'] . "," . $row [0] . ",'" . $contentText . "'," . $row [2] . "," . $row [3] . "," . $row [4] . "),";
	}
	
	$allItems = rtrim ( $allItems, "," );
	// echo "All items are: ".$allItems;
	
	// Clear all previous document items before adding new ones
	$string = "DELETE FROM tblDocItems WHERE di = '{$_SESSION['docID']}';"; // AND header = 0;";
	$header->db->query ( $string );
	
	// Add new items
	$string = "INSERT INTO tblDocItems (di,depth,content,`order`,header,ri)
							VALUES {$allItems} ON DUPLICATE KEY UPDATE id=LAST_INSERT_ID(id);";
	
	$header->db->query ( $string );
	$response [] = $header->db->insert_id;
}

if (isset ( $_POST ['nextPage'] )) {
	header ( 'Location: review.php' );
	exit ();
}

$header->display ();

?>
<script type="text/javascript">

		$(document).ready(function() {$('#treeList').nestedSortable({
			
			forcePlaceholderSize: true,
			handle: 'div',
			helper: 'clone',
			items: 'li',
			maxLevels: 4,
			opacity: 0.6,
			placeholder: 'placeholder',
			revert: 250,
			tabSize: 20,
			tolerance: 'pointer',
			toleranceElement: '> div',
			listType: 'ol'
			});
		
		});

		var hNum = 1;
		
		function addHeader()
		{			
			if (!$('#addHeader').val()=="")
			{	while($('#HEADER'+hNum).length) hNum++;
						
				var header = "<li id='HEADER" + hNum + "'><div class='ui-state-highlight ui-corner-all'><B>" + $('#addHeader').val() + "</B>"
				header = header + "<input type=\"button\" class=\"ui-state-default ui-corner-all\" value=\"X\" style=\"display:inline;float:right\" id=\"removeHeaderButton\" onclick=\"removeHeader('#HEADER" + hNum + "');\" onkeydown=\"removeHeader('#HEADER" + hNum + "');\"></div></li>";
				$("#treeList").prepend(header);
				$("#treeList").sortable("refresh");
			}
			$('#addHeader').val("");
		}

		function removeHeader(item)
		{
			if ($(item).children().length > 1) alert("You cannot delete a heading that has sub items.  Please remove them before deleting");
			else $(item).remove();
			return;
		}	


		

		/*
			Looping through all it all
		*/

		function getArray()
		{
			$items = [];
			walk($('#treeList').children());
			order = 0;
			
			$.post('<?php echo $_SERVER["PHP_SELF"] ?>' , {items: items},function(){$('form').submit();});	
					
		}		



		var depth = -1;
		var order = 0;
		var items = new Array();
		var subitems = new Array();
		
		function walk(children) {
			  if (typeof children == "undefined" || children.size() === 0) {
			    return;
			  }
			  if (children.is("li"))  depth++;
			  
			  children.each(function(){
     			  var child = $(this);
				  if (child.children().size() > 0) {
					  if(child.is("li")) {
						  order++;
						  //alert("Depth:" + depth + "   li:"+child.attr("id") + " and div is: " + child.children('div').text() + " and Order is: " + order);
						  
						  var header;
						  var id;
						  var headerLength;
						  if (child.attr("id").substring(0, 6) == "HEADER")
						  {
							  headerLength = child.attr("id").length - 6;
							  id = child.attr("id").substring(6);
							  header = 1; 
						  }
						  else 
						  {
							  id = child.attr("id");
							  header = 0;
						  }
						  
						  subitems.push(depth);						  
						  subitems.push(child.children('div').text());
						  subitems.push(order);
						  subitems.push(header);
						  subitems.push(id);
						  items.push(subitems);
						  subitems = [];
						    
					  }
				      walk(child.children());				      
				  }				  				  
			  })
			  if (children.is("li")) depth--;
			  
			  ;			  
		}

		
		</script>



<style type="text/css">
pre,code {
	font-size: 12px;
}

pre {
	width: 100%;
	overflow: auto;
}

small {
	font-size: 90%;
}

small code {
	font-size: 11px;
}

.placeholder {
	background-color: #cfcfcf;
}

.ui-nestedSortable-error {
	background: #fbe3e4;
	color: #8a1f11;
}

ol {
	margin: 0;
	padding: 0;
	padding-left: 30px;
}

ol.sortable,ol.sortable ol {
	margin: 0 0 0 25px;
	padding: 0;
	list-style-type: none;
}

ol.sortable {
	
}

.sortable li {
	padding: 0;
}

.sortable li div {
	padding: 3px;
	margin: 0;
	cursor: move;
}

h1 {
	font-size: 2em;
	margin-bottom: 0;
}

h2 {
	font-size: 1.2em;
	font-weight: normal;
	font-style: italic;
	margin-top: .2em;
	margin-bottom: 1.5em;
}

h3 {
	font-size: 1em;
	margin: 1em 0 .3em;;
}

p,ol,ul,pre,form {
	margin-top: 0;
	margin-bottom: 1em;
}

dl {
	margin: 0;
}

dd {
	margin: 0;
	padding: 0 0 0 1.5em;
}

code {
	background: #e5e5e5;
}

input {
	vertical-align: text-bottom;
}

.notice {
	color: #c33;
}
</style>

<BR>
<div class="ui-state-error">
	<p>
		Arrange the action items in the order you wish to have them appear
		within the document. <BR>Use indentions to create sub-sections
	</p>

	<p>
		<label for="addHeader" id="addHeaderLabel">Add Header</label> <input
			type="text" id="addHeader"> <input type="submit" value="Add Header"
			id="addHeaderButton" onclick="addHeader();" onkeydown="addHeader();">
	</p>
</div>


<ol id="treeList" class="sortable">				
<?php

while ( $row = $constructAll->fetch_assoc () ) {
	$ri = $row ["ri"];
	$content = $row ["content"];
	$description = $row ["description"];
	$depth = $row ["depth"];
	
	if ($row ["header"] == 1) {
		echo "<li id='HEADER" . $ri . "'><div class='ui-state-highlight ui-corner-all'><B>" . $content . "</B>";
		echo "<input type='button' class='ui-state-default ui-corner-all' value='X' style='display:inline;float:right' id='removeHeaderButton' onclick=removeHeader('#HEADER" . $ri . "'); onkeydown=removeHeader('#HEADER" . $ri . "');></div>";
		echo "</li>";
	} else {
		echo "<li id=\"" . $ri . "\"><div class='ui-state-highlight ui-corner-all' style='diplay:inline'>" . $content;
		echo "<input type='button' class='ui-state-default ui-corner-all' value='X' style='display:inline;float:right' id='removeHeaderButton' onclick=removeHeader('#" . $ri . "'); onkeydown=removeHeader('#" . $ri . "');>";
		echo "</div>";
		echo "</li>";
	}
}

?>
	</ol>



<form method="post" action='<?php echo $_SERVER["PHP_SELF"] ?>'>
	<input TYPE="hidden" NAME="nextPage"> <input type="button"
		value="Save Document Structure" id="saveStruct" onclick="getArray();"
		onkeydown="getArray();">
</form>

<?php $header->footer(); ?>