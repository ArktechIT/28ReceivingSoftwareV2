<?php
    require ('./includes/marlon_connection.php');
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

            $sql2 = "SELECT lotNumber, processCode, processRemarks FROM ppic_workschedule WHERE lotNumber = '".$row['lotNum']."' AND (processCode = 137 OR processCode = 138)";
            $checkReceivingProcess = mysqli_query($connection, $sql2);
            $receivingResult = mysqli_fetch_array($checkReceivingProcess);
            $remarksArray = explode (",", $receivingResult['processRemarks']);

            $sqlFirst = "SELECT lotNumber, processCode, status, processOrder, lotNumber FROM ppic_workschedule WHERE lotNumber = '".$row['lotNum']."' AND status = '0' ORDER BY processOrder ASC LIMIT 1";
            $sqlFirstVal = mysqli_query($connection, $sqlFirst);
            $sqlFirstValRow = mysqli_fetch_array($sqlFirstVal);
            
            if($row['workingQuantity'] != 0)
            {
                if(mysqli_num_rows($checkReceivingProcess) > 0)
                {
                    $sql = "SELECT lotNumber, status, processCode FROM ppic_workschedule WHERE lotNumber = '".$row['lotNum']."' AND status = 0 AND (processCode = 137 OR processCode = 138) ORDER BY processOrder ASC";
                    $checkReceivingProcess2 = mysqli_query($connection, $sql);

                    if(mysqli_num_rows($checkReceivingProcess2) > 0)
                    {
                        if(mysqli_num_rows($sqlFirstVal) > 0)
                        {
                            if($sqlFirstValRow['processCode'] == '137' || $sqlFirstValRow['processCode'] == '138')
                            {
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
                              
                                while($number>=0)
                                {
                                    $sqlPo = "SELECT lotNumber, poContentId, itemStatus, dataThree FROM purchasing_pocontents WHERE dataThree = '$remarksArray[$number]' AND poContentId IN ('".implode("','",$poContentIdArray)."')";
                                    $checkPO = mysqli_query($connection, $sqlPo);
                                    if (mysqli_num_rows($checkPO) != 0)
                                    {
                                        $rowPO = mysqli_fetch_array($checkPO);
                                        if ($rowPO['itemStatus'] != 2)
                                        {
                                            if ($row['identifier'] == 1)
                                            {
                                                if($rowPO['dataThree'] != '')
                                                {
                                                
                                                    echo json_encode(array("resp"=>"FINISHED", "poContentId"=> $rowPO['poContentId'], "PTAG" => $row['productionTag'], "lot" => $row['lotNum']));
                                                    break;
                                            
                                                }
                                                else
                                                {
                                                    echo json_encode(array("resp"=>"NO SUBCON PROCESS", "poContentId"=> "none", "PTAG" => $row['productionTag'], "lot" => $row['lotNum']));
                                                }   
                                            }   
                                            else
                                            {
                                                echo json_encode(array("resp"=>"FINISHED", "poContentId"=> $rowPO['poContentId'], "PTAG" => $row['productionTag'], "lot" => $row['lotNum']));
                                            }
                                        }
                                        else
                                        {
                                            echo json_encode(array("resp"=>"NO PURCHASE ORDER", "poContentId"=> "none", "PTAG" => $row['productionTag'], "lot" => $row['lotNum']));
                                        }
                                        
                                    }
                                    else
                                    {
                                        echo json_encode(array("resp"=>"NO PURCHASE ORDER", "poContentId"=> "none", "PTAG" => $row['productionTag'], "lot" => $row['lotNum']));
                                        break;
                                    }

                                    $number--;
                                }
                            }
                            else 
                            {
                                echo json_encode(array("resp"=>"NOT AVAILABLE FOR RECEIVING", "poContentId"=> "none", "PTAG" => $row['productionTag'], "lot" => $row['lotNum']));
                            }
                        }
                        else 
                        {
                            echo json_encode(array("resp"=>"NO RECEIVING PROCESS", "poContentId"=> "none", "PTAG" => $row['productionTag'], "lot" => $row['lotNum']));
                        }
                    }
                    else
                    {
                        echo json_encode(array("resp"=>"RECEIVING ALREADY FINISHED", "poContentId"=> "none", "PTAG" => $row['productionTag'], "lot" => $row['lotNum']));
                    }
                }
                else
                {
                    echo json_encode(array("resp"=>"NO RECEIVING PROCESS", "poContentId"=> "none", "PTAG" => $row['productionTag'], "lot" => $row['lotNum']));
                }
               
            } 
            else 
            {
                echo json_encode(array("resp"=>"WORKING QUANTITY 0", "poContentId"=> "none", "PTAG" => $row['productionTag'], "lot" => $row['lotNum']));
            }
        }
        else
        {
            echo json_encode(array("resp"=>"UNKNOWN TAG", "poContentId"=> "none"));
        }
    }
?>