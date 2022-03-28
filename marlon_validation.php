<?php
    require ('./includes/marlon_connection.php');
    error_reporting(0);

    function response($resp, $poContentId, $ptag, $lot)
    {
        echo json_encode(array("resp"=> $resp, "poContentId"=> $poContentId, "PTAG" => $ptag, "lot" => $lot)); 
    }

    if(isset($_GET['itemTag']))
    {
        $itemTags = $_GET['itemTag'];
        $sql = "SELECT lotNumber AS lotNum, identifier, productionTag, workingQuantity FROM ppic_lotlist WHERE lotNumber = '$itemTags' OR productionTag = '$itemTags'";
        $filteredData = mysqli_query($connection, $sql);

        if (mysqli_num_rows ($filteredData) != 0)
        {
            $row = mysqli_fetch_array($filteredData);

            $sql = "SELECT status FROM ppic_workschedule WHERE lotNumber = '".$row['lotNum']."'";
            $workSchedQuery = mysqli_query($connection, $sql);
            $workSchedResult = mysqli_fetch_array($workSchedQuery);

            $sql2 = "SELECT lotNumber, processCode, processRemarks FROM ppic_workschedule 
            WHERE lotNumber = '".$row['lotNum']."' AND (processCode = 137 OR processCode = 138)";
            $checkReceivingProcess = mysqli_query($connection, $sql2);
            $receivingResult = mysqli_fetch_array($checkReceivingProcess);
            $remarksArray = explode (",", $receivingResult['processRemarks']);

            //CHECK WORKING QUANTITY
            if($row['workingQuantity'] == 0)  
            {
                response("WORKING QUANTITY 0", "none", $row['productionTag'], $row['lotNum']);
                exit;
            }

            //CHECK RECEIVING PROCESS
            if(mysqli_num_rows($checkReceivingProcess) == 0)
            {
                response("NO RECEIVING PROCESS", "none", $row['productionTag'], $row['lotNum']);
                exit;
            }

            //CHECK IF ALREADY RECEIVED
            $sql = "SELECT lotNumber, status, processCode 
                    FROM ppic_workschedule 
                    WHERE lotNumber = '".$row['lotNum']."' AND status = 0 AND (processCode = 137 OR processCode = 138) 
                    ORDER BY processOrder ASC";
            $checkReceivingProcess2 = mysqli_query($connection, $sql);

            if(mysqli_num_rows($checkReceivingProcess2) == 0)
            {
                response("RECEIVING ALREADY FINISHED", "none", $row['productionTag'], $row['lotNum']);
                exit;
            }

            //CHECK IF NO RECEIVING PROCESS
            $sqlFirst = "SELECT lotNumber, processCode, status, processOrder, lotNumber 
                        FROM ppic_workschedule
                        WHERE lotNumber = '".$row['lotNum']."' AND status = '0' 
                        ORDER BY processOrder ASC LIMIT 1";
            $sqlFirstVal = mysqli_query($connection, $sqlFirst);
            $sqlFirstValRow = mysqli_fetch_array($sqlFirstVal);

            if(mysqli_num_rows($sqlFirstVal) == 0)
            {
                response("NO RECEIVING PROCESS", "none", $row['productionTag'], $row['lotNum']);
                exit;
            }

            //CHECK IF NOT AVAILABLE FOR RECEIVING
            if($sqlFirstValRow['processCode'] != '137' && $sqlFirstValRow['processCode'] != '138')
            {
                response("NOT AVAILABLE FOR RECEIVING", "none", $row['productionTag'], $row['lotNum']);
                exit;
            }
            
            $sql = "SELECT *, 
            CASE WHEN identifier=1 THEN GROUP_CONCAT(poContentId)
            WHEN identifier=4 THEN poId END AS pOrder
            FROM ppic_lotlist
            WHERE lotNumber = '".$row['lotNum']."'";
            
            $qry  = $connection->query($sql);
            $result = mysqli_fetch_array($qry);
            $poContentIdArray = explode (",", $result['pOrder']);
            $number = key(array_slice($poContentIdArray, -1, 1, true));
            $poContentIdArray = array_reverse($poContentIdArray);
            $remarksContent = '';

            if($row['identifier'] == 1)
            {
                if($receivingResult['processRemarks'] == '') //CHECK IF NO SUBCON PROCESS
                {
                    response("NO SUBCON PROCESS", "none", $row['productionTag'], $row['lotNum']);
                    exit;
                }
                
                while($number>=0)
                {
                    $sqlPo = "SELECT lotNumber, poContentId, itemStatus, dataThree FROM purchasing_pocontents
                    WHERE dataThree = '$remarksArray[$number]' AND poContentId IN ('".implode("','",$poContentIdArray)."')";
                    $checkPO = mysqli_query($connection, $sqlPo);
                    $rowPO = mysqli_fetch_array($checkPO);
                
                    if (mysqli_num_rows($checkPO) != 0)
                    {
                        if ($rowPO['itemStatus'] != 2)
                        {
                            if($number <= 0)
                            {
                                response("PROCEED", $rowPO['poContentId'], $row['productionTag'], $row['lotNum']);
                            }
                        }
                        else
                        {
                            response("CANCELED PURCHASE ORDER", "none", $row['productionTag'], $row['lotNum']);
                        }
                    }
                    else
                    {
                        $remarksContent .= $remarksArray[$number].'<br>';
                        if($number <= 0 && $remarksContent != '')
                        {
                            response("NO PURCHASE ORDER<br>".$remarksContent, "none", $row['productionTag'], $row['lotNum']);
                        }
                    }
                    $number--;
                }
            }
            else
            {
                $sqlPo = "SELECT lotNumber, poContentId, itemStatus, dataThree FROM purchasing_pocontents
                WHERE poContentId IN ('".implode("','",$poContentIdArray)."')";
                $checkPO = mysqli_query($connection, $sqlPo);
                $rowPO = mysqli_fetch_array($checkPO);
            
                if (mysqli_num_rows($checkPO) != 0)
                {
                    if ($rowPO['itemStatus'] != 2)
                    {
                        response("PROCEED", $rowPO['poContentId'], $row['productionTag'], $row['lotNum']);
                    }
                    else
                    {
                        response("CANCELED PURCHASE ORDER", "none", $row['productionTag'], $row['lotNum']);
                    }
                }
                else
                {
                    response("NO PURCHASE ORDER", "none", $row['productionTag'], $row['lotNum']);
                }
            }
        }
        else
        {
            response("UNKNOWN TAG", "none", "none", "none");
        }
    }
?>