    <?php
	require('../includes/marlon_connection.php');
    error_reporting(0);

	if(isset($_POST['finishBtn']))
    {
        $lotNumber = $_POST['finished_items'];
    	$supplier = $_POST['item_supplier'];
    	$itemName = $_POST['item_name'];
    	$poNumber = $_POST['item_poNumber'];
    	$itemDesc = $_POST['item_desc'];
        
        //CHECK IF AN ITEM IS ALREADY FINISHED
        $sql = "SELECT lotNumber, status, processCode FROM ppic_workschedule WHERE lotNumber IN ('".implode("','", $lotNumber)."') AND status = 0 AND (processCode = 137 OR processCode = 138) ORDER BY processOrder ASC";
        $checkReceivingProcess = mysqli_query($connection, $sql);

        if(mysqli_num_rows($checkReceivingProcess) > 0)
        {
            $newLotNumberArray = array();
            $newSupplierArray = array();
            $newItemNameArray = array();
            $newPoNumberArray = array();
            $newItemDescArray = array();
            while($row = $checkReceivingProcess->fetch_array())
            {
                $lotNumberKey = array_search($row['lotNumber'], $lotNumber);
                array_push($newLotNumberArray, $row['lotNumber']);
                array_push($newSupplierArray, $supplier[$lotNumberKey]);
                array_push($newItemNameArray, $itemName[$lotNumberKey]);
                array_push($newPoNumberArray, $poNumber[$lotNumberKey]);
                array_push($newItemDescArray,$itemDesc[$lotNumberKey]);

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
                echo $finishedLotNumberResult;
            }
            else
            {
                echo 0; //none
            }
        }
        else
        {
            echo 3; //all finished
        }
    }

?>