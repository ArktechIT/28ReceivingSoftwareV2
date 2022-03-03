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

            $sqlFirst = "SELECT lotNumber, processCode, GROUP_CONCAT(processRemarks) AS remarks, status, processOrder, lotNumber FROM ppic_workschedule WHERE lotNumber = '".$row['lotNum']."' AND status = '0' ORDER BY processOrder ASC LIMIT 1";
            $sqlFirstVal = mysqli_query($connection, $sqlFirst);
            $sqlFirstValRow = mysqli_fetch_array($sqlFirstVal);
            $remarksArray = explode (",", $sqlFirstValRow['remarks']);
            if($row['workingQuantity'] != 0)
            {
                if(mysqli_num_rows($sqlFirstVal) > 0)
                {
                    if($workSchedResult['status'] != '0')
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
                            $count=0;
                            while($number>=0)
                            {
                                $sqlPo = "SELECT lotNumber, poContentId, itemStatus, dataThree FROM purchasing_pocontents WHERE dataThree = '$remarksArray[$number]' AND poContentId = '$poContentIdArray[$number]'";
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
                                                if($count==1)
                                                {
                                                    break;
                                                }
                                                $count++;
                                                echo json_encode(array("resp"=>"FINISHED", "poContentId"=> $rowPO['poContentId']));
                                            }
                                            else
                                            {
                                                echo json_encode(array("resp"=>"NO SUBCON PROCESS", "poContentId"=> "none"));
                                            }   
                                        }   
                                        else
                                        {
                                            echo json_encode(array("resp"=>"FINISHED", "poContentId"=> $rowPO['poContentId']));
                                        }
                                    }
                                    else
                                    {
                                        echo json_encode(array("resp"=>"NO PURCHASE ORDER", "poContentId"=> "none"));
                                    }
                                }
                                else
                                {
                                    if($number <= 0){
                                        echo json_encode(array("resp"=>"NO PURCHASE ORDER", "poContentId"=> "none"));

                                    }
                                }
                                $number--;
                            }
                        }
                        else 
                        {
                        echo json_encode(array("resp"=>"NOT AVAILABLE FOR RECEIVING", "poContentId"=> "none"));

                        }
                    } 
                    else 
                    {
                        echo json_encode(array("resp"=>"NO FINISHED", "poContentId"=> "none"));

                    }
                }
                else 
                {
                    echo json_encode(array("resp"=>"NO RECEIVING PROCESS", "poContentId"=> "none"));
                }
            } 
            else 
            {
                echo json_encode(array("resp"=>"WORKING QUANTITY 0", "poContentId"=> "none"));
            }
        }
        else
        {
            // echo 'UNKNOWN TAG';
            echo json_encode(array("resp"=>"UNKNOWN TAG", "poContentId"=> "none"));
        }
    }
?>
