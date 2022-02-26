<?php
    require ('./includes/marlon_connection.php');
    session_start();
    if(isset($_POST['filter']))
    {
        $items = $_POST['item_list'];
        $uniqueItem = array_unique($items);
        $uniqueItem = array_values($uniqueItem);
        $n = key(array_slice($uniqueItem, -1, 1, true));
        $status = '';
        $finish = array();
        while ($n >= 0)
        {   
            $class = 'pending';
            $inputName = '';

            $sql = "SELECT lotNumber AS lotNum, identifier, productionTag, workingQuantity FROM ppic_lotlist WHERE lotNumber = '$uniqueItem[$n]' OR productionTag = '$uniqueItem[$n]'";
            $filteredData = mysqli_query($connection, $sql);

            $inputValue = '';
            if (mysqli_num_rows ($filteredData) != 0)
            {
                $row = mysqli_fetch_array($filteredData);
                $inputValue = $uniqueItem[$n];

                $sql = "SELECT status FROM ppic_workschedule WHERE lotNumber = '".$row['lotNum']."'";
                $workSchedQuery = mysqli_query($connection, $sql);
                $workSchedResult = mysqli_fetch_array($workSchedQuery);

                if($row['productionTag'] == $uniqueItem[$n])
                {
                    $uniqueItem = \array_diff($uniqueItem, [$row['lotNum']]);
                    $uniqueItem = array_values($uniqueItem);
                }
                if ($row['lotNum'] == $uniqueItem[$n])
                {
                    $uniqueItem = \array_diff($uniqueItem, [$row['productionTag']]);
                    $uniqueItem = array_values($uniqueItem);
                }

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
                                    // echo $remarksArray[$number].'->>'.$poContentIdArray[$number].'<br><br>';
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

                                                    $status = '<span class="text-success">FINISHED</span><br>';
                                                    $class = 'hidden';
                                                    $inputName = 'item_list_input[]';
                                                    $poContentIdInput = 'poContent_list_input[]';
                                                    echo '<tr class="'.$class.'"><td><input type="hidden" value="'.$inputValue.'" name="'.$inputName.'"></input>
                                                    <input type="hidden" value="'.$rowPO['poContentId'].'" name="'.$poContentIdInput.'"></input>'.$uniqueItem[$n].' '.$status.'</td></tr>';
                                                }
                                                else
                                                {
                                                    $status = '<span class="text-danger">NO SUBCON PROCESS</span><br>';
                                                    echo '<tr class="'.$class.'"><td><input type="hidden" value="'.$inputValue.'" name="'.$inputName.'"></input>'.$uniqueItem[$n].' '.$status.'</td></tr>';
                                                }   
                                            }   
                                            else
                                            {
                                                $status = '<span class="text-success">FINISHED</span><br>';
                                                $class = 'hidden';
                                                $inputName = 'item_list_input[]';
                                                $poContentIdInput = 'poContent_list_input[]';
                                                echo '<tr class="'.$class.'"><td><input type="hidden" value="'.$inputValue.'" name="'.$inputName.'"></input>
                                                <input type="hidden" value="'.$rowPO['poContentId'].'" name="'.$poContentIdInput.'"></input>'.$uniqueItem[$n].' '.$status.'</td></tr>';
                                            }
                                        }
                                        else
                                        {
                                            $status = '<span class="text-danger">NO PURCHASE ORDER</span><br>';
                                            echo '<tr class="'.$class.'"><td><input type="hidden" value="'.$inputValue.'" name="'.$inputName.'"></input>'.$uniqueItem[$n].' '.$status.'</td></tr>';

                                        }
                                    }
                                    else
                                    {
                                        if($number <= 0){
                                            $status = '<span class="text-danger">NO PURCHASE ORDER</span><br>';
                                            echo '<tr class="'.$class.'"><td><input type="hidden" value="'.$inputValue.'" name="'.$inputName.'"></input>'.$uniqueItem[$n].' '.$status.'</td></tr>';
                                        }
                                    }
                                    $number--;
                                }
                            }
                            else 
                            {
                                $status = '<span class="text-danger">NOT AVAILABLE FOR RECEIVING</span><br>';
                                echo '<tr class="'.$class.'"><td><input type="hidden" value="'.$inputValue.'" name="'.$inputName.'"></input>'.$uniqueItem[$n].' '.$status.'</td></tr>';
                            }
                        } 
                        else 
                        {
                            $status = '<span class="text-danger">NOT FINISHED</span><br>';
                            echo '<tr class="'.$class.'"><td><input type="hidden" value="'.$inputValue.'" name="'.$inputName.'"></input>'.$uniqueItem[$n].' '.$status.'</td></tr>';
                        }
                    }
                    else 
                    {
                        $status = '<span class="text-danger">NO RECEIVING PROCESS</span><br>';
                        echo '<tr class="'.$class.'"><td><input type="hidden" value="'.$inputValue.'" name="'.$inputName.'"></input>'.$uniqueItem[$n].' '.$status.'</td></tr>';
                    }
                } 
                else 
                {
                    $status = '<br><span class="text-danger">WORKING QUANTITY 0</span><br>';
                    echo '<tr class="'.$class.'"><td><input type="hidden" value="'.$inputValue.'" name="'.$inputName.'"></input>'.$uniqueItem[$n].' '.$status.'</td></tr>';
                }
            }
            else
            {
                $status = '<span class="text-danger">UNKNOWN TAG</span><br>';
                echo '<tr class="'.$class.'"><td><input type="hidden" value="'.$inputValue.'" name="'.$inputName.'"></input>'.$uniqueItem[$n].' '.$status.'</td></tr>';
            }
            $n--;
        }
    }
?>