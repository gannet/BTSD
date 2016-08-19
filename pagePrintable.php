<?php
include 'lib/sopDev.php';
require ('lib/fpdf16/fpdf.php');

$header = new template ();

// $progress = new progress();
// if ($progress->status < 5) $progress->moveTo();

$string = "SELECT id, title, dept, version, author, reviewed, type, effective, status
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
	$effective = $row ["effective"];
	$status = $row ["status"];
}
class PDF extends FPDF {
	var $version;
	var $widths;
	var $aligns;
	
	// Page header
	function Header() {
		// Logo
		// $this->Image('img/CC-Logo_300.jpg',10,8,50);
		// Arial bold 15
		$this->SetFont ( 'Arial', 'B', 15 );
		// Move to the right
		$this->Cell ( 80 );
		
		// Title
		$this->Cell ( 30, 10, $this->title, 0, 0, 'C' );
		// Line break
		$this->Ln ( 0 );
		
		// Arial bold 15
		$this->SetFont ( 'Arial', 'B', 15 );
		$this->SetTextColor ( 0, 0, 0 );
	}
	
	// Page footer
	function Footer() {
		// Position at 1.5 cm from bottom
		$this->SetY ( - 15 );
		// Arial italic 8
		$this->SetFont ( 'Arial', 'I', 8 );
		// Page number
		$y = $this->GetY ();
		$this->SetY ( $y );
		$this->Cell ( 0, 10, "Version:" . $this->version, 0, 0, 'L' );
		$this->SetY ( $y );
		$this->Cell ( 0, 10, 'Page ' . $this->PageNo () . '/{nb}', 0, 0, 'C' );
		$this->SetY ( $y );
		$date = 'Printed on:' . date ( 'F jS, Y' );
		$this->Cell ( 0, 10, $date, 0, 0, 'R' );
	}
	function SetVersion($v) {
		// Set the array of column widths
		$this->version = $v;
	}
	function SetWidths($w) {
		// Set the array of column widths
		$this->widths = $w;
	}
	function SetAligns($a) {
		// Set the array of column alignments
		$this->aligns = $a;
	}
	function Row($data, $img) {
		// Calculate the height of the row
		$nb = 0;
		for($i = 0; $i < count ( $data ); $i ++)
			$nb = max ( $nb, $this->NbLines ( $this->widths [$i], $data [$i] ) );
		$h = 5 * $nb;
		// Issue a page break first if needed
		$this->CheckPageBreak ( $h );
		// Draw the cells of the row
		for($i = 0; $i < count ( $data ); $i ++) {
			$w = $this->widths [$i];
			$a = isset ( $this->aligns [$i] ) ? $this->aligns [$i] : 'L';
			// Save the current position
			$x = $this->GetX ();
			$y = $this->GetY ();
			// Draw the border
			// $this->Rect($x,$y,$w,$h);
			
			if ($img [$i] != 0) {
				$this->Image ( $data [$i], $x, $y - 5, 35 );
			} else {
				// $this->SetFillColor(200);
				// Print the text
				if ($data [$i] == '')
					$this->MultiCell ( $w, 5, '', 0, $a, false );
				else
					$this->MultiCell ( $w, 5, $data [$i], 0, $a, true );
			}
			// Put the position to the right of the cell
			$this->SetXY ( $x + $w, $y );
		}
		// Go to the next line
		$this->Ln ( $h );
	}
	function CheckPageBreak($h) {
		// If the height h would cause an overflow, add a new page immediately
		if ($this->GetY () + $h > $this->PageBreakTrigger)
			$this->AddPage ( $this->CurOrientation );
	}
	function NbLines($w, $txt) {
		// Computes the number of lines a MultiCell of width w will take
		$cw = &$this->CurrentFont ['cw'];
		if ($w == 0)
			$w = $this->w - $this->rMargin - $this->x;
		$wmax = ($w - 2 * $this->cMargin) * 1000 / $this->FontSize;
		$s = str_replace ( "\r", '', $txt );
		$nb = strlen ( $s );
		if ($nb > 0 and $s [$nb - 1] == "\n")
			$nb --;
		$sep = - 1;
		$i = 0;
		$j = 0;
		$l = 0;
		$nl = 1;
		while ( $i < $nb ) {
			$c = $s [$i];
			if ($c == "\n") {
				$i ++;
				$sep = - 1;
				$j = $i;
				$l = 0;
				$nl ++;
				continue;
			}
			if ($c == ' ')
				$sep = $i;
			$l += $cw [$c];
			if ($l > $wmax) {
				if ($sep == - 1) {
					if ($i == $j)
						$i ++;
				} else
					$i = $sep + 1;
				$sep = - 1;
				$j = $i;
				$l = 0;
				$nl ++;
			} else
				$i ++;
		}
		return $nl;
	}
}

// Instanciation of inherited class
$pdf = new PDF ();

$string = "SELECT tblDocument.id, tblDocument.dept, tblDocument.title, tblDocument.type, tblDocument.version					  							
				FROM tblDocument				
				WHERE tblDocument.id = " . $_SESSION ['docID'] . ";";
$docInfo = $header->db->query ( $string );

$title;
while ( $row = $docInfo->fetch_assoc () ) {
	$title = $row ['title'];
	$dept = $row ['dept'];
	$pdf->SetVersion ( $row ['version'] );
}

$pdf->AliasNbPages ();
$pdf->AddPage ();

// Set table widths for Title
$pdf->SetWidths ( array (
		130,
		10,
		50 
) );
// Pass two arrays. the first give information about what to print out. the second indicates an image or not.
$pdf->SetFillColor ( 200 );
$pdf->Row ( array (
		"\n" . $title,
		'',
		'img/logo.jpg' 
), array (
		0,
		0,
		1 
) );
$pdf->Row ( array (
		"\n" 
), array (
		0 
) );

// Insert space and line after title
// $pdf->Ln(8);
$pdf->Cell ( 0, 8, $dept, 0, 1 );
$pdf->Line ( $pdf->GetX (), $pdf->GetY (), $pdf->GetX () + 180, $pdf->GetY () );

// pull document items from the database
$string = "SELECT tblDocItems.depth, tblDocItems.order, tblDocItems.di, 
					  tblDocItems.content, tblDocItems.header							
				FROM tblDocItems				
				WHERE tblDocItems.di = " . $_SESSION ['docID'] . "
				ORDER BY tblDocItems.order;"; // Pull items in order they were created
$items = $header->db->query ( $string );

// Initize numbering. Max 4 levels.
$context = array (
		0,
		0,
		0,
		0 
);
$patterns = array (
		".0.0.0",
		".0.0",
		".0" 
);

while ( $row = $items->fetch_assoc () ) {
	$depth = $row ['depth'];
	
	// if ($depth ==0) $pdf->Cell(0,10," ",0,1);
	
	// Check the depth and insert spacing and reset numbering values
	switch ($depth) {
		case 0 :
			$pdf->Ln ( 10 );
			$context [1] = 0;
			$context [2] = 0;
			$context [3] = 0;
			break;
		
		case 1 :
			$pdf->Ln ( 5 );
			$context [2] = 0;
			$context [3] = 0;
			break;
		
		case 2 :
			$pdf->Ln ( 2 );
			$context [3] = 0;
	}
	
	// Increment the value of the context that corresponds to the depth value
	$context [$depth] ++;
	
	// If the content is marked as a header then Bold otherwise don't bold.
	if ($row ['header'] == 1)
		$pdf->SetFont ( 'Arial', 'B', 12 );
	else
		$pdf->SetFont ( 'Arial', '', 12 );
		
		// Remove trailing zeros
	$contextText = str_replace ( $patterns, "", implode ( ".", $context ) );
	
	// Indent based on depth value
	$pdf->SetX ( 10 + $depth * 12 );
	
	// Get X,Y values for later use
	$y = $pdf->GetY ();
	$x = $pdf->GetX () + (4 * ($depth + 1)); // Algorithm sets X more to right based on depth
	                                        
	// Insert the context numbering for each
	$pdf->Cell ( 0, 5, $contextText, 0, 1 );
	
	// Bring the position back to previous line
	$pdf->SetXY ( $x, $y );
	
	$content = str_replace ( '’', "'", $row ["content"] );
	// Write content to multilined cell.
	$pdf->MultiCell ( 0, 5, $content, 0, 1 );
}

$pdf->Output ();
?>
 		
