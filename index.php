<?php
    require ('./includes/marlon_session.php');
    require ('./includes/marlon_connection.php');
    $sql = "SELECT 
            e.firstName, 
            e.surName, 
            p.positionName 
            FROM hr_employee e 
            LEFT JOIN hr_positions p 
            ON p.positionId=e.position 
            WHERE e.idNumber = '".$_SESSION['idNumber']."'";
    $query = $connection->query($sql);
    $result = $query->fetch_assoc();
    extract($result);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="./assets/css/bootstrap-min.css">   
    <link rel="stylesheet" href="./assets/css/sweetalert2.css">   
    <link rel="stylesheet" href="./assets/css/style.css"> 
    <link rel="stylesheet" href="https://use.fontawesome.com/releases/v5.7.0/css/all.css">
    <script src="./assets/js/jquery-3.6.0.min.js"></script>
    <script src="./assets/js/sweetalert.min.js"></script>
    <title>Receiving Software</title>
</head>
<body>
    <div class="loader"></div>
    <div class="container-fluid">
        <header>
            <h3>RECEIVING SOFTWARE</h3>
        </header>
        <div class="user-profile mx-auto text-center">
            <div class="row no-gutters">
                <div class="col-6">
                    <div class="img-box">
                        <img src="./assets/images/profile.jpg" class="rounded-circle mx-auto border border-white" width="65px" height="65px" alt="Profile">
                    </div>
                </div>
                <div class="col-6">
                    <div class="info-box">
                        <p>
                            <b><?php echo $firstName.' '.$surName?></b>
                            <br>
                            <small class="user-position"><?php echo $positionName?></small>
                        </p>
                    </div>
                </div>
            </div>
        </div>
        <div class="card list-card text-center">
            <div class="card-body">
                <h6>INPUT/SCAN/BARCODE ITEMS:</h6>
                <form method="POST" action="marlon_finishList.php" class="add-form" autocomplete="off">
                    <div class="input-group">
                    <input type="text" class="form-control search-input" name="item_tags" id="itemTags" placeholder="Item Tags">
                        <div class="input-group-append">
                            <button class="btn btn-outlined add-filter" disabled>ADD</button>
                        </div>
                    </div>
                    <div class="table-list">
                        <span class="pb-2 top-left">Item(s): <input type="text" class="item-count" id="item-count" tabindex="-1" value="0" readonly></span>
                        <span class="pb-2 top-right"><input type="text" class="supplier_name" id="supplier_name" tabindex="-1" value="" readonly></span>
                        <table class="table table-bordered table-input" id="validation-table">
                            <tbody>
                            </tbody>
                        </table>
                    </div>
                    <button type="submit" class="btn form-btn" name="filter">SUBMIT</button>
                </form>
            </div>
        </div>
    </div>
	<script src="./assets/js/popper.min.js"></script>
	<script src="./assets/js/bootstrap.min.js"></script>
    <script src="./assets/js/script.js"></script>
</body>
</html>