<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <script src="./assets/js/jquery-3.6.0.min.js"></script>
    <link rel="stylesheet" href="./assets/css/bootstrap-min.css">   
    <link rel="stylesheet" href="./assets/css/style.css">
    <script src="./assets/js/jquery.form.min.js"></script>
    <title>Receiving Software | Send PR</title>
</head>
<body>
    <div class="container-fluid">
        <header>
            <h3>RECEIVING SOFTWARE</h3>
        </header>
        <div class="card small-card text-center">
            <div class="card-body">
                <form method="POST" action="marlon_emailPdf.php" id="emailForm">
                    <?php 
                        $fileCount = count(glob("pr_temp/" . "*"));
                        $fileList = glob('pr_temp/*.pdf');
                        if($fileCount != 0)
                        {
                            foreach($fileList as $filename){
                                if(is_file($filename)){
                                    echo "<input type='hidden' name='filename[]' value='".substr($filename, 8, 18)."' id='fileName'></input>";
                                }   
                            }
                        } else {
                            echo "<input type='hidden' name='filename[]' value='' id='fileName'></input>";
                        }
                      
                    ?>
                    <h3>TOTAL PDF FILES: <input type='text' id="fileCount" value='<?php echo $fileCount;?>' disabled></input></h3>
                    <h4 class="mt-4"><i id="sendingFilesText"></i></h4>
                    <button type="submit" class="btn form-btn invisible" name="send" id="send">SEND</button>
                </form>
            </div>
        </div>
    </div>
	<script src="./assets/js/popper.min.js"></script>
	<script src="./assets/js/bootstrap.min.js"></script>
	<script src="./assets/js/script.js"></script>
    <script>
        $(document).ready(function () {
            setTimeout(function(){
            $('#send').click();
            }, 3000);
        });
    </script>
</body>
</html>