<?php
    require ('./includes/marlon_connection.php');

    if(isset($_GET['location']) && $_GET['location'] == 1)
    {
        $sql = "SELECT locationRackNumber FROM system_rack";
        $query = $connection->query($sql);

        $locationDataList = '';
        while ($row = $query->fetch_assoc())
        {
            extract($row);
            $locationDataList .= '<option value="'.trim($locationRackNumber).'">'.trim($locationRackNumber).'</option>';
        }
        echo $locationDataList;
    }

    if(isset($_GET['bucket']) && $_GET['bucket'] == 1)
    {
        $sql = "SELECT containerNumber FROM system_container";
        $query = $connection->query($sql);

        $bucketDataList = '';
        while ($row = $query->fetch_assoc())
        {
            extract($row);
            $bucketDataList .= '<option value="'.trim($containerNumber).'">';
        }
        echo $bucketDataList;
    }
?>