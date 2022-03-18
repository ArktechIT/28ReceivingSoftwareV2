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
    <script src="./assets/js/jquery.form.min.js"></script>
    <script src="./assets/js/sweetalert.min.js"></script>
    <title>Receiving Software | Finish List</title>
</head>
<body>
    <div class="container-fluid">
        <header>
            <h3>RECEIVING SOFTWARE</h3>
        </header>
        <div class="card big-card text-center">
            <div class="card-body">
                <h5 class="finish-h5">FINISHED LIST:</h5>
                <form method="POST" action="marlon_finishValidation.php" id="finish-form">
                    <div class="item-table-list">
                        <table class="table table-bordered table-finish">
                        <span class="pb-2" style="float: left">Item(s): <input type="text" class="item-count" id="item-count" value="0" tabindex="-1" readonly></span>
                            <tbody>
                                <?php include 'controllers/marlon_checkFinish.php';?>
                            </tbody>
                        </table>
                    </div>
                    <button type="submit" class="btn finish-btn" name="finishBtn" id="finish-btn">FINISH</button>
                </form>
            </div>
        </div>
    </div>
	<script src="./assets/js/popper.min.js"></script>
	<script src="./assets/js/bootstrap.min.js"></script>
    <script src="./assets/js/script.js"></script>
</body>
</html>