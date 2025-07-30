<?php
include("db.php");
include("ses.php");
include("password.php");

class Mail{
	protected $__db;
	protected $__ses;
	protected $__m;
	protected $__subject;
	protected $__message;

	public function __Construct() {
		$this->__ses = new SimpleEmailService();
		$this->__m = new SimpleEmailServiceMessage();
		$this->__data = new Datatable();		
	}
	public function mailPassword($result_u, $return=false, $code="MAIL_PASSWORD_SET"){
		$result_ct = $this->__data->getCommunicationTemplate($code);
		
		// subject
		$this->__subject = str_replace("{host}",str_replace("‘","",$result_u['host']),$result_ct['subject']);
					
		$token = $this->generateToken($result_u['u_id']);
		if (!isset($token) || count($result_ct)==0) {
			$returnstr = '<div class="alert alert-danger"><span class="fa fa-exclamation-circle"></span> Er is iets mis gegaan.<br></div>';
			if ($return) return $returnstr;
			else return false;
		}
		$url = "https://www.yoururl.nl/change_password.php?token=".$token;
		$this->__message = str_replace("{hours_valid}","4",str_replace("{host}",str_replace("‘","",$result_u['host']),str_replace("{url}",$url,$result_ct['content'])));
		
		$r = $this->sendMail($result_u['email']);
		if ($r) {
			if ($return) return '<div class="alert alert-success"><span class="fa fa-check"></span> Mail verstuurd om het wachtwoord te resetten.<br></div>';
			else echo '<div class="alert alert-success"><span class="fa fa-check"></span> Bedankt voor uw aanvraag. U ontvangt een e-mail om uw wachtwoord te resetten.<br></div>';;
		}
	}
	public function sendEmail_newRapport($s_id, $emails, $code="MAIL_NEW_RAPPPORT"){
		$result_ct = $this->__data->getCommunicationTemplate($code);
		$sporthall = $this->__data->getLocDetails($s_id);
		
		// subject
		$this->__subject = str_replace("{sporthall-name}",str_replace("‘","",$sporthall['s_name']),$result_ct['subject']);
					
		$this->__message = str_replace("{sporthall-name}",str_replace("‘","",$sporthall['s_name']),$result_ct['content']);
		
		$r = 0;
		if ($this->sendMail($emails)) $r = 1;
		return $this->__data->returnR($r,'Mail(s) verstuurd.');
	}
	public function mailOpnameFormulier($s_name, $emails, $code="MAIL_OPNAMEFORMULIER"){
		$returntrue = "Mail verstuurd naar: ";
		$returnfalse = "";
		$result_ct = $this->__data->getCommunicationTemplate($code);
		
		// subject
		$this->__subject = $result_ct['subject'];
		$url = "<a href='https://sportvloermanager365.nl'>https://sportvloermanager365.nl</a>";
		$this->__message = str_replace("{url}",$url,str_replace("{location_name}",("<b>".$s_name."</b>"), $result_ct['content']));
						
		foreach($emails as $email){
			$r = $this->sendMail($email);
			// $r = true;
			if ($r == false) $returnfalse .= $email.", ";
			else $returntrue .= $email.", ";
		}
		if ($returnfalse == "") return substr($returntrue, 0, -2);
		else return $returntrue . "geen mail verstuurd naar: ". substr($returnfalse, 0, -2);
	}
	private function generateString($length, $strength) {
		$vowels = 'aeuy';
		$consonants = 'bdghjmnpqrstvz';
		if ($strength & 1) {
			$consonants .= 'BDGHJLMNPQRSTVWXZ';
		}
		if ($strength & 2) {
			$vowels .= "AEUY";
		}
		if ($strength & 4) {
			$consonants .= '23456789';
		}
		if ($strength & 8) {
			$consonants .= '@#$%';
		}
	 
		$password = '';
		$alt = time() % 2;
		for ($i = 0; $i < $length; $i++) {
			if ($alt == 1) {
				$password .= $consonants[(rand() % strlen($consonants))];
				$alt = 0;
			} else {
				$password .= $vowels[(rand() % strlen($vowels))];
				$alt = 1;
			}
		}
		return $password;
	}	
	private function generateToken($user_id){
		$token = password_hash($this->generateString(10,8), PASSWORD_BCRYPT, array("cost" => 10));	
		$sql = "UPDATE user SET login_token='".$token."', updateTS=CURRENT_TIMESTAMP, updateUser='".$user_id."' WHERE id='".$user_id."'";
		$r = $this->__data->execSql($sql);
		if ($r != 1) return NULL;
		else return $token;
	}
	private function sendMail($to){
		unset($this->__m->to);
		$this->__m->addTo($to);
		$this->__m->addCustomHeader("Force-headers:\t1");
		$this->__m->setSubject($this->__subject);
		$this->__m->addAttachmentFromFile('1547.png', 'email_signatures/1547.png', 'image/png', 'signature','inline');
		$this->__message .= '<img src="cid:signature" />';
		$this->__m->setMessageFromString('',$this->__message);
		$response = $this->__ses->sendEmail($this->__m);
		return $response;
	}

}

?>