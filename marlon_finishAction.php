<?php
	require('./includes/marlon_connection.php');
	require('FPDF/fpdf.php');
    require('marlon_emailPdf.php');
    error_reporting(0);
    session_start();

	if(isset($_POST['finishBtn']))
    {
        $lotNumber = $_POST['finished_items'];
    	$supplier = $_POST['item_supplier'];
    	$itemName = $_POST['item_name'];
    	$poNumber = $_POST['item_poNumber'];
    	$itemDesc = $_POST['item_desc'];
    	$quantity = $_POST['quantity'];
        $location = $_POST['itemLocation'];
        $bucket = $_POST['itemBucket'];
        $idNumber = $_SESSION['idNumber'];

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
        
        //CHECK IF AN ITEM IS ALREADY FINISHED
        $sql = "SELECT 
                lotNumber, 
                status, 
                processCode 
                FROM ppic_workschedule 
                WHERE lotNumber 
                IN ('".implode("','", $lotNumber)."') AND status = 0 AND (processCode = 137 OR processCode = 138) 
                ORDER BY processOrder ASC";
        $checkReceivingProcess = mysqli_query($connection, $sql);

        if(mysqli_num_rows($checkReceivingProcess) > 0)
        {
            $newLotNumberArray = array();
            $newSupplierArray = array();
            $newItemNameArray = array();
            $newPoNumberArray = array();
            $newItemDescArray = array();
            $newQuantityArray = array();
            while($row = $checkReceivingProcess->fetch_array())
            {
                $lotNumberKey = array_search($row['lotNumber'], $lotNumber);
                array_push($newLotNumberArray, $row['lotNumber']);
                array_push($newSupplierArray, $supplier[$lotNumberKey]);
                array_push($newItemNameArray, $itemName[$lotNumberKey]);
                array_push($newPoNumberArray, $poNumber[$lotNumberKey]);
                array_push($newItemDescArray,$itemDesc[$lotNumberKey]);
                array_push($newQuantityArray,$quantity[$lotNumberKey]);
            }

    	    $n = key(array_slice($newLotNumberArray, -1, 1, true));
            $finishedLotNumber = array_diff($lotNumber, $newLotNumberArray);
            $finishedLotNumberCount = count($finishedLotNumber);
            $l = key(array_slice($finishedLotNumber, -1, 1, true));
            if($finishedLotNumberCount > 0)
            {

                $finishedLotNumberResult = '';
                while($l >= 0)
                {
                    if($finishedLotNumber[$l] != '')
                    {
                        $finishedLotNumberResult .= $finishedLotNumber[$l].' IS ALREADY FINISHED<br>';
                    }
                    $l--;
                }
            }

            //FINISHING THE ITEMS
            $genbatchId = date('Ymdhis');
            while($n>=0)
            {
                $sqlRh = "INSERT INTO system_receivingHistory 
                	(
                    	poNumber, 
                		lotNumber, 
                		itemName, 
                		itemDescription,
                		quantity, 
                		supplier, 
                		idNumber, 
                		pallet, 
                		batchId, 
                		date, 
                		status
                    )
                VALUES 
                	(
                    	'$newPoNumberArray[$n]', 
                    	'$newLotNumberArray[$n]', 
                    	'".mysqli_real_escape_string($connection, $newItemNameArray[$n])."', 
                    	'".mysqli_real_escape_string($connection, $newItemDescArray[$n])."', 
                    	'$newQuantityArray[$n]', 
                   		'$newSupplierArray[$n]', 
                    	'$idNumber', 
                    	' ', 
                    	'$genbatchId', 
                    	NOW(), 
                    	1
                    )";
                $recievingHistoryInsert = mysqli_query($connection, $sqlRh);

                $sql = "UPDATE ppic_workschedule SET status = 1 WHERE lotNumber = '$newLotNumberArray[$n]' AND status = '0' ORDER BY processOrder ASC LIMIT 1";
                // $updateWorkSched = mysqli_query($connection, $sql);

                $n--;
            }
        }
        include_once('gerald_receivedSql.php');
    }
?>