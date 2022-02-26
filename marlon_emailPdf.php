<?php
    require ('./phpmailer/class.phpmailer.php');

    function sendEmail($file) {
        $mail             = new PHPMailer();

        $body             = 'Proof of Receipt';
        // $body             = eregi_replace("[\]",'',$body);

        $mail->IsSMTP(); // telling the class to use SMTP
        $mail->SMTPDebug  = 1;                     // enables SMTP debug information (for testing)
                                                // 1 = errors and messages
                                                // 2 = messages only
        $mail->SMTPAuth   = true;
        $mail->SMTPSecure = "ssl";                  // enable SMTP authentication
        $mail->Host       = "smtp.gmail.com"; // sets the SMTP server
        $mail->Port       = 465;                    // set the SMTP port for the GMAIL server
        $mail->Username   = "ubase.noreply@gmail.com"; // SMTP account username
        $mail->Password   = "ubase123";        // SMTP account password

        $mail->SetFrom('name@yourdomain.com', 'Arktech');

        // $mail->AddReplyTo("name@yourdomain.com","First Last");

        $mail->Subject    = "Proof of Receipt";

        // $mail->AltBody    = "To view the message, please use an HTML compatible email viewer!"; // optional, comment out and test

        $mail->MsgHTML($body);

        $address = "marlonmercado111@gmail.com";
        $mail->AddAddress($address, "Marlon Mercado");

        $mail->AddAttachment("pr_temp/".$file.".pdf");      // attachment

        if(!$mail->Send()) {
        echo "Mailer Error: " . $mail->ErrorInfo;
        } else {
            echo "Message sent!";
            unlink('pr_temp/'.$file.'.pdf');
        }
    }

?>