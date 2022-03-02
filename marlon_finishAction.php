<?php
	require('./includes/marlon_connection.php');
	// include '../../V4/Common Data/PHP Modules/gerald_functions.php';
	require('FPDF/fpdf.php');
    require('marlon_emailPdf.php');
    date_default_timezone_set("Asia/Manila");
    error_reporting(0);

	if(isset($_POST['finish-btn']))
    {
        $lotNumber = $_POST['finished_items'];
    	$supplier = $_POST['item_supplier'];
    	$itemName = $_POST['item_name'];
    	$poNumber = $_POST['item_poNumber'];
    	$itemDesc = $_POST['item_desc'];
    	$n = key(array_slice($lotNumber, -1, 1, true));

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

        while($n>=0)
        {
            $sqlRh = "INSERT INTO system_receivingHistory (poNumber, lotNumber, itemName, itemDescription, supplier, idNumber, pallet, batchId, date)
            VALUES ('$poNumber[$n]', '$lotNumber[$n]', '$itemName[$n]', '$itemDesc[$n]', '$supplier[$n]', ' ', ' ', '', NOW())";
            $recievingHistoryInsert = mysqli_query($connection, $sqlRh);

            $n--;
        }

        $sql1 = "SELECT * FROM system_receivingHistory WHERE date = '".date('Y-m-d')."' AND batchId = '' GROUP BY supplier";
        $groupReceiving = mysqli_query($connection, $sql1);

        while($result = mysqli_fetch_array($groupReceiving))
        {
            $genbatchId = date('Ymdhis');
            $sql2 = "UPDATE system_receivingHistory SET batchId = '$genbatchId' 
            WHERE date = '".date('Y-m-d')."' AND batchId = '' AND supplier = '".$result['supplier']."'";
            $updateReceiving = mysqli_query($connection, $sql2);

            sleep(1);
        }


        //GENERATE PR
        $sqlReceiving = "SELECT * FROM system_receivingHistory WHERE status = 0 GROUP BY batchId";
        $receivingQuery = mysqli_query($connection, $sqlReceiving);
        
        while ($receivingRecord = mysqli_fetch_array($receivingQuery))
        {
            $batchId = $receivingRecord['batchId'];

            $pdf=new PDF('L','mm','A4');
            $pdf->SetLeftMargin(12);
            $pdf->AddPage();
            
            $pdf->SetFont('Arial','B',12);
            $pdf->Ln();$pdf->Ln();
            // $pdf->Image('../Common Data/Templates/images/Ared.jpg',11,7,10,10);
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
            $pdf->Cell(0,5,$receivingRecord['supplier'],0,0,'L');
            $pdf->Ln();
            $pdf->Cell(30,5,'Receive Date : ',0,0,'L');	
            $pdf->Cell(0,5,$receivingRecord['date'],0,0,'L');

            $pdf->SetFont('Arial','B',9);
            $pdf->Ln(8);
            $pdf->Cell(12,8,'#',1,0,'C');
            $pdf->Cell(23,8,'PO Number',1,0,'C');
            $pdf->Cell(29,8,'Lot Number',1,0,'C');
            $pdf->Cell(91,8,'Item Name',1,0,'C');
            $pdf->Cell(104,8,'Item Description',1,0,'C');

            $count = 1;
            
            $sqlBatch = "SELECT * FROM system_receivingHistory WHERE batchId = '$batchId'";
            $receivingBatchId = mysqli_query($connection, $sqlBatch);
            
            while ($result = mysqli_fetch_array($receivingBatchId))
            {
                $date = $result['date'];
                $supplierAlias = $result['supplier'];
                $poNumber = $result['poNumber'];
                $lotNumber = $result['lotNumber'];
                $itemName = $result['itemName'];
                $itemDescription = $result['itemDescription'];
                
                $pdf->Ln();
                $pdf->SetFont('Arial','',9);
                $pdf->Cell(12,5,$count,1,0,'C');
                $pdf->Cell(23,5,$poNumber,1,0,'C');
                $pdf->Cell(29,5,$lotNumber,1,0,'C');
                $pdf->Cell(91,5,$itemName,1,0,'C');//87
                //~ $pdf->Cell(100,5,$itemDescription,1,0,'C');
                $pdf->AutoFitCell(104,5,'Arial','',9,$itemDescription,1,0,'C');
                $pdf->SetFont('Arial','',9);
                $count++;
            }

            $pdf->Ln();
            $pdf->Cell(241,5,'',0,0,'C');
            $pdf->SetFont('Arial','',9);
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
            $path = "pr_temp/".$batchId.".pdf";
            if(!file_exists($path))
            {
                $pdf->Output($path,'F');
            }

            sendEmail($batchId);
        }

        header('location: index.php');
    }
?>