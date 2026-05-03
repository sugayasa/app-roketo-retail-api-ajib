<?php

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\SMTP;

if(!function_exists('sendMailHtml')){
    function sendMailHtml($emailAddress, $receiverName, $subject, $htmlBody){
        try {
            $mailer             =	new PHPMailer(true);
            $mailer->isSMTP();
            $mailer->Host       =   getenv('MAIL_HOST');
            $mailer->SMTPAuth   =   true;
            $mailer->Username   =   getenv('MAIL_NOREPLY_USERNAME');
            $mailer->Password   =   getenv('MAIL_NOREPLY_PASSWORD');
            $mailer->SMTPSecure	=   PHPMailer::ENCRYPTION_SMTPS;
            $mailer->Port       =   getenv('MAIL_SMTPPORT');

            $mailer->setFrom(getenv('MAIL_NOREPLY_USERNAME'), getenv('MAIL_NOREPLY_NAME'));
            $mailer->addAddress($emailAddress, $receiverName);
            $mailer->addReplyTo(getenv('MAIL_NOREPLY_USERNAME'), getenv('MAIL_NOREPLY_NAME'));

            $mailer->isHTML(true);
            $mailer->Subject	=	$subject;
            $mailer->Body   	=	$htmlBody;
            $mailer->send();

            return true;
        } catch (\Throwable $th) {
            return throwResponseInternalServerError("We can't send to your email address, please check the email address you entered");
		}
    }
}