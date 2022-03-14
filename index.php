<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="./assets/css/bootstrap-min.css">   
    <link rel="stylesheet" href="./assets/css/sweetalert2.css">   
    <link rel="stylesheet" href="./assets/css/style.css"> 
    <script src="./assets/js/jquery-3.6.0.min.js"></script>
    <script src="./assets/js/sweetalert.min.js"></script>
    <title>Receiving Software</title>
</head>
<body>
    <div class="container-fluid">
        <header>
            <h3>RECEIVING SOFTWARE</h3>
        </header>
        <div class="card big-card text-center">
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
                    <span class="pb-2" style="float: left">Item(s): <input type="text" id="item-count"  tabindex="-1" value="0" readonly></span>
                        <table class="table table-bordered table-input" id="validation-table">
                            <tbody>
                                <tr class="first-tr">
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    <button type="submit" class="btn form-btn" name="filter" disabled>SUBMIT</button>
                </form>
            </div>
        </div>
    </div>
	<script src="./assets/js/popper.min.js"></script>
	<script src="./assets/js/bootstrap.min.js"></script>
    <script src="./assets/js/script.js"></script>
</body>
</html>