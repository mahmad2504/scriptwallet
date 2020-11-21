<?php
namespace App\Console\Commands\waqar;

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

class Email
{
	function __construct($parent)
	{
		$this->mail = new PHPMailer(true);	
		$this->mail->isSMTP();     
		$this->mail->Host = 'localhost';
		$this->mail->SMTPAuth = false;
		$this->mail->SMTPAutoTLS = false; 
		$this->mail->Port = 25; 
		$this->mail->Username   = 'support-bot@mentorg.com'; 
		$this->mail->setFrom('support-bot@mentorg.com', 'Support Bot');
		$this->mail->addAddress('mumtaz_ahmad@mentor.com','Mumtaz Ahmad'); 
		$this->mail->addReplyTo('mumtaz_ahmad@mentor.com','Mumtaz Ahmad');
		$this->mail->isHTML(true); 
        $this->datapath = $parent->datapath;		
	}
	function Send($subject,$msg)
	{
		$this->mail->Subject = $subject;
		$this->mail->addAttachment($this->datapath.'checkmark.png');
		$this->mail->addAttachment($this->datapath.'incomplete.jpg');
		
        $this->mail->Body= $msg;
		try {
			$this->mail->send();
		} 
		catch (phpmailerException $e) 
		{
			echo $e->errorMessage(); //Pretty error messages from PHPMailer
		} 
		catch (Exception $e) {
			echo $e->getMessage(); //Boring error messages from anything else!
		}
	}
}