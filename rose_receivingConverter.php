<?php 
	include($_SERVER['DOCUMENT_ROOT']."/version.php");
	$path = $_SERVER['DOCUMENT_ROOT']."/".v."/Common Data/";
	set_include_path($path);	
	include('Templates/mysqliConnection.php');
	require('Libraries/PHP/FPDF/fpdf.php');
	ini_set('display_errors','on');
	
	$batchId = isset($_GET['batchId']) ? $_GET['batchId'] : "";
	
	class PDF extends FPDF
	{
		function AutoFitCell($w='',$h='',$font='',$style='',$fontSize='',$string='',$border='',$ln='',$align='',$fill='',$link='') 
		{
			$decrement = 0.1;
			$limit = round($w)-(round($w)/3);
			
			$this->SetFont($font, $style, $fontSize);
			if(strlen($string)>$limit)
			{
				$string = substr($string,0,$limit);
				$string .= '...';
			}
			
			while($this->GetStringWidth($string) > $w)
			{
				$this->SetFontSize($fontSize -= $decrement);
			}
			
			return $this->Cell($w,$h,$string,$border,$ln,$align,$fill,$link);
		}		
	}	
	
	$pdf=new PDF('L','mm','A4');
	$pdf->SetLeftMargin(12);
	$pdf->AddPage();

	$sql = "SELECT supplier, date FROM `system_receivingHistory` where batchId='".$batchId."' LIMIT 1";
	$queryReceiving = $db->query($sql);
	if($queryReceiving->num_rows > 0)
	{
		$resultReceiving = $queryReceiving->fetch_array();
		$supplier = $resultReceiving['supplier'];
		$date = $resultReceiving['date'];
	}

	
	$pdf->SetFont('Arial','B',12);
	$pdf->Ln();$pdf->Ln();
	$pdf->Image('../Common Data/Templates/images/Ared.jpg',11,7,10,10);
	$pdf->Cell(0,3,'       ARKTECH PHILIPPINES INCORPORATED',0,0,'L');
	$pdf->Ln();$pdf->Ln();
	$pdf->SetFont('Arial','B',10);
	$pdf->Cell(0,3,'         FPIP Sto. Tomas, Batangas',0,0,'L');
	$pdf->SetFont('Arial','B',30);
	$pdf->Cell(-20,2,'PROOF OF RECEIPT',0,0,'R');
	$pdf->Ln();$pdf->Ln();
	$pdf->SetFont('Arial','B',10);
	$pdf->Cell(0,8,'         Tel #: 043-405-6140/6142 Fax #: 043-405-6138',0,0,'L');
	$pdf->Ln(); $pdf->Ln();
	$pdf->SetFont('Arial','',10);
	
	$pdf->Cell(30,5,'Supplier Name : ',0,0,'L');
	$pdf->Cell(0,5,$supplier,0,0,'L');
	$pdf->Ln();
	$pdf->Cell(30,5,'Receive Date : ',0,0,'L');	
	$pdf->Cell(0,5,$date,0,0,'L');
	
	$pdf->SetFont('Arial','B',9);
	$pdf->Ln(8);
	$pdf->Cell(10,8,'#',1,0,'C');
	$pdf->Cell(20,8,'PO Number',1,0,'C');
	$pdf->Cell(25,8,'Lot Number',1,0,'C');
	$pdf->Cell(86,8,'Item Name',1,0,'C');
	$pdf->Cell(100,8,'Item Description',1,0,'C');
	$pdf->Cell(18,8,'Qty',1,0,'C');
	
	$pdf->SetFont('Arial','',9);
	$count = $totalQuantity = 0;
	$sql = "SELECT `poNumber`, `lotNumber`, `itemName`, `itemDescription`, `quantity`, `supplier`, `returnedQuantity` FROM `system_receivingHistory` where batchId='".$batchId."'";
	$queryReceiving = $db->query($sql);
	if($queryReceiving->num_rows > 0)
	{
		while($resultReceiving = $queryReceiving->fetch_array())
		{
			$poNumber = $resultReceiving['poNumber'];
			$lotNumber = $resultReceiving['lotNumber'];
			$itemName = $resultReceiving['itemName'];
			$itemDescription = $resultReceiving['itemDescription'];
			$quantity = $resultReceiving['quantity'];
			$returnedQuantity = $resultReceiving['returnedQuantity'];
			
			$pdf->Ln();
			$pdf->Cell(10,5,++$count,1,0,'C');
			$pdf->Cell(20,5,$poNumber,1,0,'C');
			$pdf->Cell(25,5,$lotNumber,1,0,'C');
			$pdf->Cell(86,5,$itemName,1,0,'C');//87
			//~ $pdf->Cell(100,5,$itemDescription,1,0,'C');
			$pdf->AutoFitCell(100,5,'Arial','',9,$itemDescription,1,0,'C');
			$pdf->SetFont('Arial','',9);
			$pdf->Cell(18,5,$quantity,1,0,'C');
			if($returnedQuantity > 0)
			{
				$pdf->Cell(15,5,$returnedQuantity." Returned",0,0,'L');
			}
			
			$totalQuantity += $quantity;
		}
	}
	
	$pdf->Ln();
	$pdf->Cell(241,5,'',0,0,'C');
	$pdf->SetFont('Arial','',9);
	$pdf->Cell(18,5,$totalQuantity,1,0,'C');
	
	$pdf->Ln();
	
	$pdf->Cell(50,5,'Received By :',0,0,'C');
	$pdf->Cell(50,5,'',0,0,'C');
	$pdf->Cell(50,5,'Delivered By',0,0,'C'); // Ace
	$pdf->Ln();
	$pdf->Cell(50,5,'',0,0,'C');
	$pdf->Ln();
	$pdf->Cell(50,5,'Employee Signature over printed name','T',0,'C');
	$pdf->Cell(50,5,'',0,0,'C');
	$pdf->Cell(50,5,'Employee Signature over printed name','T',0,'C'); // Ace
	
	$pdf->Output();	
?>
