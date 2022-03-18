<?php
    require ('./phpmailer/class.phpmailer.php');

    function sendEmail($file) {
        $mail             = new PHPMailer();

        $body             = 'Proof of Receipt';

        $mail->IsSMTP(); 
        $mail->SMTPDebug  = 1;
        $mail->SMTPAuth   = true;
        $mail->SMTPSecure = "ssl";
        $mail->Host       = "smtp.gmail.com";
        $mail->Port       = 465;
        $mail->Username   = "ubase.noreply@gmail.com";
        $mail->Password   = "ubase123";

        $mail->SetFrom('name@yourdomain.com', 'ARKTECH PHILIPPINES INC.');

        $mail->Subject    = "Proof of Receipt";

        $mail->MsgHTML($body);

        $address1 = "marlon.mercado@g.batstate-u.edu.ph";
        $address2 = "gedaguila13@gmail.com";
        $mail->AddAddress($address1);
        $mail->AddAddress($address2);

        $mail->AddAttachment("pr_temp/".$file);

        if(!$mail->Send()) {
            echo "failed";
        } else {
            echo "success";
            unlink('pr_temp/'.$file);
        	require('./includes/marlon_connection.php');
            $sql = "UPDATE system_receivingHistory SET status = 0 WHERE batchId = '".substr($file, 0, -4)."'";
            $updateStatus = $connection->query($sql);
        }
    }

    if(isset($_POST["filename"]))
    {
        $fileName = $_POST["filename"];
        $n = key(array_slice($fileName, -1, 1, true));

        if(!empty($fileName))
        {
            while($n>=0)
            {
                sendEmail($fileName[$n]);
                $n--;
            }   
        }
        header("location: marlon_sendEmail.php");
    }

?>