<?php
	require('./includes/marlon_connection.php');
    require ('./phpmailer/class.phpmailer.php');
	require('FPDF/fpdf.php');

    date_default_timezone_set("Asia/Manila");

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

    function sendEmail($file) {
        $mail             = new PHPMailer();

        $body             = 'Proof of Receipt';

        $mail->IsSMTP(); 
        $mail->SMTPDebug  = 1;
        $mail->SMTPAuth   = true;
        $mail->SMTPSecure = "ssl";
        $mail->Host       = "smtp.gmail.com";
        $mail->Port       = 465;
        $mail->Username   = "ubase.noreply@gmail.com";
        $mail->Password   = "ubase123";

        $mail->SetFrom('name@yourdomain.com', 'ARKTECH PHILIPPINES INC.');

        $mail->Subject    = "Proof of Receipt";

        $mail->MsgHTML($body);

        $address1 = "marlon.mercado@g.batstate-u.edu.ph";
        $address2 = "gedaguila13@gmail.com";
        $mail->AddAddress($address1);
        $mail->AddAddress($address2);

        $mail->AddAttachment("pr_temp/".$file.'.pdf');

        if(!$mail->Send()) {
            // echo "failed";
        } else {
            // echo "success";
            unlink('pr_temp/'.$file.'.pdf');
        	require('./includes/marlon_connection.php');
            $sql = "UPDATE system_receivingHistory SET status = 0 WHERE batchId = '$file'";
            $updateStatus = $connection->query($sql);
        }
    }
    
    //GENERATE PR
    if(isset($_GET['action']) && $_GET['action']=='generatePR'){
        $sql = "SELECT batchId, supplier, date FROM system_receivingHistory WHERE status = 1 AND date = '".date('Y-m-d')."' GROUP BY batchId";
        $query = $connection->query($sql);
        $records = $query->num_rows;
        if($query->num_rows > 0){
            while($row = mysqli_fetch_array($query)){
                extract($row);
                $pdf=new PDF('L','mm','A4');
                $pdf->SetLeftMargin(12);
                $pdf->AddPage();
                
                $pdf->SetFont('Arial','B',12);
                $pdf->Ln();$pdf->Ln();
                $pdf->Image('./assets/images/Ared.jpg',11,7,10,10);
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

                $records--;
                if($records == 0)
                {
                    echo 1;
                }
            }
        } else {
            echo 2;
        }
    }
?>