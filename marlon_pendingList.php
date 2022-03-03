<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="./assets/css/bootstrap-min.css">   
    <link rel="stylesheet" href="./assets/css/bootstrap-sweetalert.css">   
    <link rel="stylesheet" href="./assets/css/style.css">
    <script src="./assets/js/jquery-3.6.0.min.js"></script>
    <script src="./assets/js/sweetalert.min.js"></script>
    <title>Receiving Software | Pending List</title>
    <script>
        //check if pending is empty
        var rowPending = $('.pending-table .pending').length;
        if (rowPending == 0) {
            $('.remove-btn').hide();
            swal(
            {
                title: 'NO PENDING ITEMS',
                type: 'info',
                closeOnConfirm: false,
                showCancelButton: false,
                confirmButtonText: 'OK',
            },
            function (isConfirm) {
                if (isConfirm) {
                $('.remove-btn').click();
                }
            }
            );
        }
        document.getElementById('item-count').value = rowPending;
    </script>
</head>
<body>
    <div class="container-fluid">
        <header>
            <h3>RECEIVING SOFTWARE</h3>
        </header>
        <div class="card text-center">
            <div class="card-body">
                <h6>PENDING LIST:</h6>
                <form method="POST" action="marlon_finishList.php">
                    <div class="item-table-list">
                       <table class="table pending-table">
                        <span class="pb-2" style="float: left">Item(s): <input type="text" id="item-count" value="0" tabindex="-1" readonly></span>
                            <tbody>
                                <?php include 'marlon_filter.php'?>
                            </tbody>
                        </table>
                    </div>
                    <button type="submit" class="btn remove-btn" name="remove">REMOVE</button>
                </form>
            </div>
        </div>
    </div>
	<script src="./assets/js/popper.min.js"></script>
	<script src="./assets/js/bootstrap.min.js"></script>
	<script src="./assets/js/script.js"></script>
</body>
</html>