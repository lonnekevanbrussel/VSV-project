<?php
// Cronjob: execute every day at 9am
// 0 7 * * * /opt/bitnami/php/bin/php /opt/bitnami/apache2/htdocs/*****/aftersales_mails.php >> /opt/bitnami/apache2/htdocs/*****/aftersales.log 2>&1

$aftersales = new AfterSales();
$aftersales->activateAftersales();

class AfterSales{
	protected $__INTERVAL_MAX_DELAY = 7;
	protected $__min_delay = 0;
	protected $__max_delay = 100;
	protected $__mailflow = array();
	protected $__mailLog = array();

	public function __Construct() {
		$this->__data = new Datatable();
		$this->__mail = new Mail();
	}
	private function setMailflowData(){
		$activeMailflow = $this->__data->getActiveMailflow();
		foreach($activeMailflow as $row)
			$this->__mailflow[$row['template']] = $row['delay'];
		asort($this->__mailflow);
		$this->__min_delay = min($this->__mailflow);	
		$this->__max_delay = max($this->__mailflow) + $this->__INTERVAL_MAX_DELAY;			
	}
	private function getMailLogData(){
		$mailflowLog = $this->__data->getMailflowLog();
		foreach($mailflowLog as $row){
			if (!isset($this->__mailLog[$row['sporthall_rapport_id']][$row['mail_template']]))
				$this->__mailLog[$row['sporthall_rapport_id']][$row['mail_template']] = $row['to_email_adress'];
			else
				$this->__mailLog[$row['sporthall_rapport_id']][$row['mail_template']] .= ";".$row['to_email_adress'];
		}
	}
	private function execAftersales(){
		echo "--- Aftersales Mail @".date("d-m-Y H:i:s")." --- \n";
		foreach($this->__aftersalesReports as $report){
			$nextreport = 0;
			foreach($this->__mailflow as $template=>$delay){
				if(!$nextreport 
				&& (strtotime($report['date_mail']) <= strtotime("-".$delay." days")) 
				&& !isset($this->__mailLog[$report['id']][$template])){
					$this->__mail->mailAftersale($report,$template);
					$nextreport = 1;
				}
			}
		}
		echo "--- *** --- \n \n";
	}
	public function activateAftersales(){
		$this->setMailflowData();
		$this->getMailLogData();		
		$this->__aftersalesReports = $this->__data->getAftersalesReports($this->__min_delay, $this->__max_delay);
		$this->execAftersales();
	}
}


class Mail{
	protected $__ses;
	protected $__m;
	protected $__data;
	protected $__subject;
	protected $__message;

	public function __Construct() {
		$this->__ses = new SimpleEmailService();
		$this->__m = new SimpleEmailServiceMessage();
		$this->__data = new Datatable();		
	}
	public function mailAftersale($report, $code="MAIL_AFTERSALE_W1"){
		$emails_sent = array();
		$result_ct = $this->__data->getCommunicationTemplate($code);
		
		// subject
		$this->__subject = $result_ct['subject'];
		$this->__message = $result_ct['content'];
		
		$emails = $this->__data->getRecipientsAftersales($report['organisation_id']);
						
		foreach($emails as $email){
			$r = $this->sendMail($email);
			echo date("d-m-Y H:i:s")."\t **sendmail** title:".$this->__subject." to:".$email." status:".(!$r?"failed!":"")."\n";
			if ($r !== false) $emails_sent[] = $email;
		}
		$this->__data->saveMailflowLog($report['id'], $code, $emails_sent);
	}	
	private function sendMail($to){
		unset($this->__m->to);
		$this->__m->addTo($to);
		$this->__m->addCustomHeader("Force-headers:\t1");
		$this->__m->setSubject($this->__subject);
		$this->__m->setMessageFromString('',$this->__message);
		$response = $this->__ses->sendEmail($this->__m); //, false, false); //$sesMessage, $use_raw_request = false , $trigger_error = null
		return $response;
	}
}


?>