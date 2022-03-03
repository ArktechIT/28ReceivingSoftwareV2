<?php
    require ('./includes/marlon_connection.php');
    session_start();

    if(isset($_POST['filter']))
    {
        $items = $_POST['item_list_input'];
        $poContentId = $_POST['poContent_list_input'];
        $itemList = implode("','" ,$items);
        $n = key(array_slice($items, -1, 1, true));
        if(!empty($items))
        {
            $orderSql = '';
            $count = 0;
            while($count<=$n)
            {
                $orderSql .= ' productionTag = "'.$items[$count].'",';
                $orderSql .= ' lotNumber = "'.$items[$count].'",';
                
                $count++;
            }

            $orderBy = rtrim($orderSql,",");
           
            while($n>=0)
            {
                $sql = "SELECT lotNumber AS lotNum, productionTag, identifier FROM ppic_lotlist WHERE lotNumber = '$items[$n]' OR productionTag = '$items[$n]'";
                $finishList = mysqli_query($connection, $sql);

                if ($finishList->num_rows != 0)
                {
                    while ($row = mysqli_fetch_array($finishList))
                    {
                        $sql = "SELECT * FROM purchasing_pocontents WHERE poContentId = '$poContentId[$n]'"; 
                        $poContent = mysqli_query($connection, $sql);
                        $result = mysqli_fetch_array($poContent);

                        if($row['identifier'] == 1)
                        {
                            $itemName = $result['itemDescription'];
                        } else {
                            $itemName = $result['itemName'];
                        }

                        echo 
                        '<tr>
                        <td>
                        <input type="hidden" value="'.$row['lotNum'].'" name="finished_items[]"></input>
                        <input type="hidden" value="'.$result['supplierAlias'].'" name="item_supplier[]"></input>
                        <input type="hidden" value="'.$itemName.'" name="item_name[]"></input>
                        <input type="hidden" value="'.$result['poNumber'].'" name="item_poNumber[]"></input>
                        <input type="hidden" value="'.$result['itemDescription'].'" name="item_desc[]"></input>
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