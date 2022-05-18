<?php
    require ('./includes/marlon_connection.php');

    if(isset($_POST['filter']))
    {
        $items = $_POST['item_list_input'];
        $poContentId = $_POST['poContent_list_input'];
        $container = $_POST['container'];
        $itemList = implode("','" ,$items);
        $n = key(array_slice($items, -1, 1, true));
        if(!empty($items))
        {  
            while($n>=0)
            {
                $sql = "SELECT lotNumber AS lotNum, productionTag, identifier, workingQuantity FROM ppic_lotlist WHERE lotNumber = '$items[$n]' OR productionTag = '$items[$n]'";
                $finishList = mysqli_query($connection, $sql);

                if ($finishList->num_rows != 0)
                {
                    while ($row = mysqli_fetch_array($finishList))
                    {
                        $sql = "SELECT * FROM purchasing_pocontents WHERE poContentId = '$poContentId[$n]'"; 
                        $poContent = mysqli_query($connection, $sql);
                        $result = mysqli_fetch_array($poContent);
                    
                    	$sql2 = "SELECT lotNumber, processRemarks FROM ppic_workschedule WHERE lotNumber = '".$row['lotNum']."' AND status = '0' ORDER BY processOrder ASC LIMIT 1";
                		$processRemarksQuery = mysqli_query($connection, $sql2);
                    	$row2 = mysqli_fetch_array($processRemarksQuery);

                        if($row['identifier'] == 1)
                        {
							$itemName = $result['itemDescription'];
                        	$itemDescription = $row2['processRemarks'];
                        } else {
                            $itemName = $result['itemName'];
                        	$itemDescription = $result['itemDescription'];
                        }

                        echo 
                        '<tr>
                        <td>
                        <input type="hidden" value="'.$row['lotNum'].'" name="finished_items[]"></input>
                        <input type="hidden" value="'.$result['supplierAlias'].'" name="item_supplier[]"></input>
                        <input type="hidden" value="'.$itemName.'" name="item_name[]"></input>
                        <input type="hidden" value="'.$result['poNumber'].'" name="item_poNumber[]"></input>
                        <input type="hidden" value="'.$itemDescription.'" name="item_desc[]"></input>
                        <input type="hidden" value="'.$row['workingQuantity'].'" name="quantity[]"></input>
                        <input type="hidden" value="'.$container[$n].'" name="container[]"></input>
                        <b>'.$items[$n].'</b>
                        <br><small>'.$row['lotNum'].' | '.$result['poNumber'].' | '.$result['supplierAlias'].'</small>
                        </td>
                        </tr>'; 
                    }
                }
               $n--;
            }
        }
        else
        {
            header('location: index.php');
        }
    }
?>