<?php
include("db.php");
include("language.php");
include("ses.php");
require_once('WkHtmlToPdf.php');

class PDF{
	protected $__data;
	protected $__page = 0;
	protected $__floors = array(0=>"Punt elastisch",1=>"Mix elastisch",2=>"Vlak elastisch",3=>"Combi elastisch");
	protected $__suppliers = array("herculan"=>"Herculan","pulastic"=>"Pulastic","other"=>"Anders");
	protected $__finishings = array("houtlak"=>"Hout lak","houtwas"=>"Hout was","linoleum"=>"Linoleum","pu-coating"=>"PU Coating","pvc"=>"PVC","other"=>"Overig");
	protected $__meetpunten = array("A"=>"Stroefheid/Gladheid","E"=>"Glanswaarden","B"=>"Oppervlaktehardheid","D"=>"Schokabsorptie",
			"C"=>"Verticale deformatie","F"=>"Belijningen en vloervoorziening","G"=>"Temperatuur en relatieve luchtvochtigheid");	
	protected $__meetpuntenO = array("OA"=>"Laagdikte vulling","OB"=>"Schokabsorptie","OE"=>"Verticale deformatie","OC"=>"Energierestitutie",
			"OD"=>"Balstuit","OF"=>"Balrol","OG"=>"Vlakheid","OH"=>"Rotational","OI"=>"Temperatuur en relatieve luchtvochtigheid");
	protected $__outdooor = "";
	protected $__stype = 0; //0: indoor; 1: outdoor
	protected $__inspectie = "standard";
	
	public function __Construct() {
		$this->__data = new Datatable();
	}
	private function setContentOpnamePDF($s_id){
		$this->__r_id = $s_id;
		$this->__sh = $this->__data->getRapportDetails_Sporthall($s_id);
		if (isset($this->__sh['s_type']) && $this->__sh['s_type'] == 'outdoor') {
			$this->__stype = 1;
			$this->__outdoor = "-outdoor";
		}

		$this->__pdf_title ="_opnameformulier";
		
		$this->__sHTML_Header = $this->getHeader();
		$this->__sHTML_Content .= $this->getOpnameData(); 
		$this->__sHTML_Footer = $this->getFooter();
	}
	private function setContentPDF($r_id){
		$this->__r_id = $r_id;
		$this->__rapport = $this->__data->getRapportDetails($r_id);
		$this->__images = $this->__data->getRapportImages($r_id);
		if (isset($this->__rapport['s_type']) && $this->__rapport['s_type'] == 'outdoor') {
			$this->__stype = 1;
			$this->__outdoor = "-outdoor";
		}
		$this->__inspection = (isset($this->__rapport['type']) && $this->__rapport['type'] != '' ? $this->__rapport['type'] : 'standard');

		// $this->__pdf_title ="pdf_rapportage";
		$this->__pdf_title ="";
		
		$this->__sHTML_Header = $this->getHeader();
		
		if(isset($_GET['action'])){
			if ($_GET['action']=='generateworkorderpdf'){
				$this->__sHTML_Content = $this->getWorkorder();
				// $this->__pdf_title ="werkbon";
				$this->__pdf_title ="_work_order";
			}
			else if($_GET['action']=='generatedammagespdf'){
				$this->__sHTML_Content = $this->getDammages();
				$this->__pdf_title ="_dammages";
			}
			else if($_GET['action']=='certificate'){
				$this->__sHTML_Content = $this->getCertificate();
				$this->__pdf_title ="_certificate";
			}			
		}
		else {
			$this->__measurements = $this->__data->getRapportMeasures($this->__r_id);
			$this->__sHTML_Content = $this->getFrontPage(); //0
			$this->__sHTML_Content .= $this->getInspectie(); //1
			$this->__sHTML_Content .= $this->getInhoudsopgave(); //2
			if ($this->__inspection != 'short'){
				$this->__sHTML_Content .= $this->getInleiding(); //3
				$this->__sHTML_Content .= $this->getKeurpunten(); //4
			}
			$measurements = explode(',',$this->__rapport['measurements']);
			if(in_array("OA",$measurements)) $this->__sHTML_Content .= $this->getMeasurementOAGH("OA");
			if(in_array("OB",$measurements)) $this->__sHTML_Content .= $this->getMeasurementOBEC("OB");
			if(in_array("OE",$measurements)) $this->__sHTML_Content .= $this->getMeasurementOBEC("OE");
			if(in_array("OC",$measurements)) $this->__sHTML_Content .= $this->getMeasurementOBEC("OC");
			if(in_array("OG",$measurements)) $this->__sHTML_Content .= $this->getMeasurementOAGH("OG");
			if(in_array("OH",$measurements)) $this->__sHTML_Content .= $this->getMeasurementOAGH("OH");
			$this->__sHTML_Content .= $this->getDammages(); 
			$this->__sHTML_Content .= $this->getConclusion(); 
			if ($this->__inspection != 'short')
				$this->__sHTML_Content .= $this->getAanbevelingen(); 
			$this->__sHTML_Content .= $this->getSignature(); 
			if ($this->__stype == 0)
				$this->__sHTML_Content .= $this->getCertificate(); 
			// $this->__sHTML_Content .= $this->getExtra(); 	
			$this->__sHTML_Content .= $this->printImages("EXTRA", 0, "8. FOTO BIJLAGEN");
		}
//echo $this->__sHTML_Content;
		$this->__sHTML_Footer = $this->getFooter();		
	}
	public function generatePDF($sendsave="send"){
		if (isset($_GET['sporthall_id']) && isset($_GET['action']) && $_GET['action']=='generateopnamepdf')
			$this->setContentOpnamePDF($_GET['sporthall_id']);
		else if (isset($_GET['rapport_id']))
			$this->setContentPDF($_GET['rapport_id']);
		else
			echo "Rapport of sporthal ID mist";
		
		$filename = "test.html";  //your filename
		 
		// Let's make sure the file exists and is writable first. 
		IF (IS_WRITABLE($filename)) { 
		 
		   // In our example we're opening $filename in append mode. 
		   // The file pointer is at the bottom of the file hence 
		   // that's where $somecontent will go when we fwrite() it. 
		   IF (!$handle = FOPEN($filename, 'w')) { 
				 ECHO "Cannot open file ($filename)"; 
				 EXIT; 
		   } 
		 
		   // Write $somecontent to our opened file. 
		 IF (FWRITE($handle, $this->__sHTML_Header) === FALSE) { 
			   ECHO "Cannot write to file ($filename)"; 
			   EXIT; 
		   }ELSE{ 
			  //file is ok so write the other elements to it 
			  FWRITE($handle, $this->__sHTML_Content); 
			  FWRITE($handle, $this->__sHTML_Footer); 
		   } 
		 
		   FCLOSE($handle); 
		 
		}ELSE{ 
		   ECHO "The file $filename is not writable"; 
		}

		$pdf = new WkHtmlToPdf(array(
			// Explicitly tell wkhtmltopdf that we're using an X environment
			'use-xserver',

			// Enable built in Xvfb support
			'enableXvfb' => true,

			// If this is not set, the xvfb-run binary is autodected
			'xvfbRunBin' => '/usr/bin/xvfb-run',

			// By default the following options are passed to xvfb-run.
			// So only use this option if you want/have to change them.
			'xvfbRunOptions' =>  ' --server-args="-screen 0, 1024x768x24" ',
			'margin-top'	=> 	'0',
			'margin-bottom'	=> 	'0',
			'margin-left'	=> 	'0',
			'margin-right'	=> 	'0',
			'user-style-sheet' =>	'/css/pdf_vsv.css' //your pdf css file
		));
		$pageOptionsCover = array(
			'margin-top'	=> 	'0',
			'margin-bottom'	=> 	'0',
			'margin-left'	=> 	'0',
			'margin-right'	=> 	'0'
		);
		$pageOptions = array(
			'margin-top'	=> 	'0',
			'margin-bottom'	=> 	'0',
			'margin-left'	=> 	'0',
			'margin-right'	=> 	'0',
//			'footer-center' => 'Pagina [page] van [toPage]',
			'footer-spacing' => '-10',
//			'header-spacing' => '40 -T 55mm'
		);
		
		// Add a HTML file, a HTML string or a page from a URL
		$pdf->addPage('https://www.yoururl.nl/test.html'); //your url + filename

		$dir = '/sporthall/pdf/'; //your dir
		$file = $this->__r_id.$this->__pdf_title.'.pdf';
		if (file_exists($dir.$file)) unlink($dir.$file);
		$pdf->saveAs($dir.$file);
		if (isset($_GET['loadRapport'])){
			header('Location: sporthall/pdf/'.$file);
		}
		else{
			$pdf->send($this->__r_id.$this->__pdf_title.'_'.date('dmY').'.pdf');
		}
		if ($_SESSION['usergroup']=='BEDRIJF')
			$this->__data->saveRapportView($this->__r_id);
	}
	
	private function getVsv_iLine($title, $field, $show=0, $collection='r'){
		$data = $this->__rapport;
		if($show==3) $data = $field;
		else if ($collection == 's') $data = $this->__sh;
		$returnstr = "";
		if ($show==0)
			return '<div><div class="col-vsv-i"><b>'.$title.'</b></div><div class="col-vsv-i">'.(isset($data[$field]) ? ucfirst($data[$field]) : '').'</div></div>';
		else if ($show==1 && isset($data[$field]) && strlen($data[$field])>0)
			return '<div><div class="col-vsv-i"><b>'.$title.'</b></div><div class="col-vsv-i">'.ucfirst($data[$field]).'</div></div>';
		else if ($show==2 && isset($data[$field]) && $data[$field]!='0000-00-00')
			return '<div><div class="col-vsv-i"><b>'.$title.'</b></div><div class="col-vsv-i">'.(date("d-m-Y",strtotime($data[$field]))).'</div></div>';
		else if ($show==3)
			return '<div><div class="col-vsv-i"><b>'.$title.'</b></div><div class="col-vsv-i">'.ucfirst($field).'</div></div>';
		else if ($show==4)
			return '<div><div class="col-vsv-i"><b>'.$title.'</b></div><div class="col-vsv-i">'.(isset($data[$field]) && $data[$field]==1?'Ja':'Nee').'</div></div>';
	}
	private function getOpnameData(){
	
		$returnstr = '
	<div class="pdf-vsv-page'.$this->__outdoor.'" style="page-break-before: always;">
		<div class="pdf-vsv-header'.$this->__outdoor.'">'.$this->__sh['o_name'].' - '.$this->__sh['s_name'].'</div>
		<div class="pdf-vsv-main">
			<h3>BASIS GEGEVENS</h3>'.
			$this->getVsv_iLine('Locatie naam','s_name',0,'s').
			$this->getVsv_iLine('Contactpersoon','s_contact_name',0,'s').
			$this->getVsv_iLine('Contactpersoon telefoon','s_phone',0,'s').
			$this->getVsv_iLine('Contactpersoon mobiel','s_contact_mobile',0,'s').
			$this->getVsv_iLine('E-mailadres','s_contact_email',0,'s').
			$this->getVsv_iLine('Straatnaam en nummer','s_address',0,'s').
			$this->getVsv_iLine('Postcode','zip',0,'s').
			$this->getVsv_iLine('Plaats','s_city',0,'s').
			'<br />
			<h3>OVERIGE GEGEVENS</h3>'.
			($this->__stype ==0 ?
				$this->getVsv_iLine('Toegang',(isset($this->__sh['access']) ? $this->__access[$this->__sh['access']]:''),3).
				$this->getVsv_iLine('Parkeren','parking',0,'s').
				$this->getVsv_iLine('Ingang','entrance_large',0,'s').
				$this->getVsv_iLine('Nuts-voorzieningen','nuts',4,'s').
				$this->getVsv_iLine('Alarm','security',4,'s').
				$this->getVsv_iLine('Alarm code','security_code',0,'s').
				$this->getVsv_iLine('Lichtinstallatie','light',4,'s').
				$this->getVsv_iLine('Type zaal','halltype',0,'s').
				$this->getVsv_iLine('Type sportvloer',(isset($this->__sh['floortype']) ? $this->__floors[$this->__sh['floortype']]:''),3).
				$this->getVsv_iLine('Producent',(isset($this->__sh['supplier']) ? $this->__suppliers[$this->__sh['supplier']]:''),3).
				$this->getVsv_iLine('Kleur','color',0,'s').
				$this->getVsv_iLine('Afwerking',(isset($this->__sh['finishing']) ? $this->__finishings[$this->__sh['finishing']]:''),3).
				$this->getVsv_iLine('Bouwjaar','constructionyear',0,'s').
				$this->getVsv_iLine('Toplaag renovatie','renovation',0,'s').
				$this->getVsv_iLine('Afmetingen','size',0,'s').
				$this->getVsv_iLine('Oppervlakte m2','area',0,'s').
				$this->getVsv_iLine('Oppervlakte berging 1 m2','area1',0,'s').
				$this->getVsv_iLine('Oppervlakte berging 2 m2','area2',0,'s').
				$this->getVsv_iLine('Reiniging door','clean_by',0,'s').
				$this->getVsv_iLine('Frequentie stofwissen',(isset($this->__sh['clean_dust']) ? $this->__cleaning[$this->__sh['clean_dust']]:''),3).
				$this->getVsv_iLine('Frequentie nat schoonmaak',(isset($this->__sh['clean_freq_maint']) ? $this->__cleaning[$this->__sh['clean_freq_maint']]:''),3).
				$this->getVsv_iLine('Dagelijks product',(isset($this->__prod_day[$this->__sh['clean_prod_day']])?$this->__prod_day[$this->__sh['clean_prod_day']]:$this->__sh['clean_prod_day']),3).
				$this->getVsv_iLine('Frequentie nat reinigen',(isset($this->__sh['clean_freq']) ? $this->__cleaning[$this->__sh['clean_freq']]:''),3).
				$this->getVsv_iLine('Periodiek product','prod_per',0,'s').
				$this->getVsv_iLine('Schrobmachine merk','clean_machine',0,'s').
				$this->getVsv_iLine('Schrobmachine type',(isset($this->__sh['clean_machine_type'])?$this->__machine[$this->__sh['clean_machine_type']]:''),3).
				$this->getVsv_iLine('Toestellen leverancier',(isset($this->__sh['equipment_supplier']) ? $this->__suppliers[$this->__sh['equipment_supplier']]:''),3).
				$this->getVsv_iLine('Vragen / Opmerkingen','remark',0,'s')
			:
				$this->getVsv_iLine('Parkeren','parking',0,'s').
				$this->getVsv_iLine('Veldnummer','o_fieldnumber',0,'s').
				$this->getVsv_iLine('Type veld','halltype',0,'s').
				$this->getVsv_iLine('Infill','o_infill',0,'s').
				$this->getVsv_iLine('Fundatie','o_fundatie',0,'s').
				$this->getVsv_iLine('Onderbouw','o_onderbouw',0,'s').
				$this->getVsv_iLine('Type toplaag','o_toplaag',0,'s').
				$this->getVsv_iLine('Producent/Aannemer','supplier_other',0,'s').
				$this->getVsv_iLine('Bouwjaar','constructionyear',0,'s').
				$this->getVsv_iLine('Toplaag renovatie','renovation',0,'s').
				$this->getVsv_iLine('Logboek aanwezig','o_logboek',4,'s').
				$this->getVsv_iLine('Afmetingen','size',0,'s').
				$this->getVsv_iLine('Oppervlakte m2','area',0,'s').
				$this->getVsv_iLine('Onderhoud door','clean_by',0,'s').
				$this->getVsv_iLine('Frequentie borstelen',(isset($this->__sh['clean_dust']) ? $this->__cleaning[$this->__sh['clean_dust']]:''),3).
				$this->getVsv_iLine('Freq specialistisch onderhoud',(isset($this->__sh['clean_freq_maint']) ? $this->__cleaning[$this->__sh['clean_freq_maint']]:''),3).
				$this->getVsv_iLine('Bestrijdingsmiddelen/overige','o_pesticides',4,'s').
				$this->getVsv_iLine('Vragen / Opmerkingen','remark',0,'s')
			);
		return $returnstr;		
	}
	private function getKeurpunten($measurements){	
		$meetpunten = $this->__meetpunten;
		$enter_location = array(1=>"Bij punt 1",2=>"Bij punt 2",3=>"Bij punt 3","1-4"=>"Bij de middelijn tussen punt 1 en 9",
		5=>"Bij punt 5",6=>"Bij punt 6",7=>"Bij punt 7","7-3"=>"Bij de middelijn tussen punt 3 en 11",8=>"Bij punt 8",
		9=>"Bij punt 9",10=>"Bij punt 10",11=>"Bij punt 11");
		if ($this->__stype == 1){
			$meetpunten = $this->__meetpuntenO;
			$enter_location = array(1=>"Bij punt 1",2=>"Bij punt 2",5=>"Bij punt 5",7=>"Bij punt 7","1-4"=>"Lange zijde bij punt 1",
			"7-3"=>"Lange zijde bij punt 3");
		}
		
		$returnstr = '
	<div class="pdf-vsv-page'.$this->__outdoor.'" style="page-break-before: always;">
		<div class="pdf-vsv-header'.$this->__outdoor.'">2. WAAR IS '.($this->__stype == 0?'DE VLOER':'HET VELD').' OP GEKEURD?</div>
		<div class="pdf-vsv-main">';
		$measurements = explode(",",$this->__rapport['measurements']);	
		foreach($measurements as $measurement)
			$returnstr .= $meetpunten[$measurement].'<br />';
		if ($this->__inspection != 'short'){
			$returnstr .= '<br /><br />
<b>Eventuele aanvullende informatie</b><ol>';
			if ($this->__stype==0) $returnstr .= '<li>Microscopische foto\'s,</li>';
			$returnstr .= '
<li>Controle op belijning</li>
<li>Beschadigingen</li>
<li>Foto\'s locatie</li></ol>';
		}
		$returnstr .= '
			<br /><br />
			Op de volgende locaties hebben we gemeten: '.$this->__rapport['measurement_nrs'].'<br />
			We zijn binnengekomen '.strtolower($enter_location[$this->__rapport['enter_location']]).'<br /><br />
			<img src="images/'.($this->__stype == 0?'plattegrond_klein_new.png':'plattegrond_outdoor.jpg').'" style="width: 800px;">
			<br /><br />
		</div>
		<div class="pdf-vsv-footer">'.$this->nextPage().'</div>
	</div>';
		return $returnstr;		
	}
	private function getMeasurementOAGH($m){				
		$returnstr = '
	<div class="pdf-vsv-page'.$this->__outdoor.'" style="page-break-before: always;">
		<div class="pdf-vsv-header'.$this->__outdoor.'">3. RESULTATEN</div>
		<div class="pdf-vsv-main">';
		$returnstr .= '
			<h3>'.$this->__meetpuntenO[$m].'</h3>
			<br />
			Meting
			<div style="display: flex; flex-direction: column">
				  <table class="stroefheid_table">
					<tbody><tr>
					  <th></th>
					  <th><b>Zonder bewerking</b></th>
					  <th><b>Na revitalisatie</b></th>
					</tr>';
		$measurement_nrs = explode(",",$this->__rapport['measurement_nrs']);
		foreach($measurement_nrs as $m_nr){
			$returnstr .= '
					<tr>
						<td><b>Positie '.$m_nr.'</b></td>';
			for($i=0; $i<=1; $i++)
				$returnstr .= '
						<td>'.$this->__measurements[$m.'_'.$m_nr.'_'.$i].'</td>';
			$returnstr .= '
					</tr>';
		}
		$returnstr .= '
					<tr>
						<td><b>Overall gemiddelde</b></td>';
		for($i=0; $i<=1; $i++)
			$returnstr .= '
						<td><b>'.$this->__measurements[$m.'_AVG_'.$i].'</b></td>';
		$color = "#4bbf6b";
		if (isset($this->__measurements[$m.'_NORM_0']) && $this->__measurements[$m.'_NORM_0'] == 'onvoldoende')
			$color = "#eb5a46";
		$returnstr .= '
					</tr>
				</table>	
			</div>
		  <br />
		  <div>
			<div class="vsv-beoordeling">Beoordeling &emsp;</div>
			<div class="vsv-beoordeling" style="background-color: '.$color.'; font-weight: 900;">'.ucfirst($this->__measurements[$m.'_NORM_0']).'</div>
			<br /><br />
		  </div>';
		$returnstr .= $this->printImages($m,2);
		return $returnstr;		
	}
	private function getMeasurementOBEC($m){				
		$returnstr = '
	<div class="pdf-vsv-page'.$this->__outdoor.'" style="page-break-before: always;">
		<div class="pdf-vsv-header'.$this->__outdoor.'">3. RESULTATEN</div>
		<div class="pdf-vsv-main">';
		$returnstr .= '
			<h3>'.$this->__meetpuntenO[$m].'</h3>
			<br />
			<div style="height: 250px;">
				<div class="column-left-vsv"><img src="images/vertialevervorming.png" style="height: 250px;"></div>
				<div class="column-right-vsv">'.(isset($this->__measurements[$m.'_DESCRIPTION_0'])?$this->__measurements[$m.'_DESCRIPTION_0']:'').'
				</div>
			</div>
			'.($m=='OB'?'<div>Gemeten waarden in %: Fieldtester (conform Berlin Athlete 3a)</div>':'').'
			<div><br /><b>Voor revitalisatie</b></div>
			<div style="display: flex; flex-direction: column">
				<table class="stroefheid_table">
					<tbody><tr>
					  <th><b>Punt</th>
					  <th>Meting 1 *</th>
					  <th>Meting 2</th>
					  <th>Meting 3</th>
					  <th>Gem. 2+3 **</b></th>
					</tr>';
		$measurement_nrs = explode(",",$this->__rapport['measurement_nrs']);
		foreach($measurement_nrs as $m_nr){
			$returnstr .= '
					<tr>
						<td><b>Positie '.$m_nr.'</b></td>';
			for($i=0; $i<=3; $i++)
				$returnstr .= '
						<td>'.($i==3?'<b>':'').$this->__measurements[$m.'_'.$m_nr.'_'.$i].($i==3?'</b>':'').'</td>';
			$returnstr .= '
					</tr>';
		}
		$returnstr .= '
					<tr>
						<td><b>Overall gemiddelde</b></td>';
		for($i=0; $i<=3; $i++)
			$returnstr .= '
						<td><b>'.$this->__measurements[$m.'_AVG_'.$i].'</b></td>';
		$returnstr .= '
					</tr>
				</table>	
			</div>
		</div>
		<div class="pdf-vsv-footer">'.$this->nextPage().'</div>
	</div>
	<div class="pdf-vsv-page'.$this->__outdoor.'" style="page-break-before: always;">
		<div class="pdf-vsv-header'.$this->__outdoor.'">3. RESULTATEN</div>
		<div class="pdf-vsv-main">			
			<b>Na revitalisatie</b>		
			<div style="display: flex; flex-direction: column">
				<table class="stroefheid_table">
					<tbody><tr>
					  <th><b>Punt</th>
					  <th>Meting 1 *</th>
					  <th>Meting 2</th>
					  <th>Meting 3</th>
					  <th>Gem. 2+3 **</b></th>
					</tr>';
		$measurement_nrs = explode(",",$this->__rapport['measurement_nrs']);
		foreach($measurement_nrs as $m_nr){
			$returnstr .= '
					<tr>
						<td><b>Positie '.$m_nr.'</b></td>';
			for($i=4; $i<=7; $i++)
				$returnstr .= '
						<td>'.($i==7?'<b>':'').$this->__measurements[$m.'_'.$m_nr.'_'.$i].($i==7?'</b>':'').'</td>';
			$returnstr .= '
					</tr>';
		}
		$returnstr .= '
					<tr>
						<td><b>Overall gemiddelde</b></td>';
		for($i=4; $i<=7; $i++)
			$returnstr .= '
						<td><b>'.$this->__measurements[$m.'_AVG_'.$i].'</b></td>';
		$color = "#4bbf6b";
		if (isset($this->__measurements[$m.'_NORM_0']) && $this->__measurements[$m.'_NORM_0'] == 'onvoldoende')
			$color = "#eb5a46";
		$returnstr .= '
					</tr>
				</table>	
			</div>
			<br />
		  <div>
			<div class="vsv-beoordeling">Beoordeling &emsp;</div>
			<div class="vsv-beoordeling" style="background-color: '.$color.'; font-weight: 900;">'.ucfirst($this->__measurements[$m.'_NORM_0']).'</div>
			<br /><br />
			<br /><br />
			* Meting 1 is een testmeting
			<br />
			** Gemiddelde van meting 2 + 3 is het resultaat
		  </div>';
		$returnstr .= $this->printImages($m,2);
		return $returnstr;		
	}
	private function getSignature(){	
		$returnstr = '
	<div class="pdf-vsv-page'.$this->__outdoor.'" style="page-break-before: always;">
		<div class="pdf-vsv-header'.$this->__outdoor.'">7. ONDERTEKENING</div>
		<div class="pdf-vsv-main">';
		$returnstr .= 'Deze inspectie is uitgevoerd met de grootste zorg. De inspecteurs van Veiligesportvloer zijn
allen zeer ervaren product- en materiaal deskundigen. Ons advies in deze is de intensief
gebruikte sportaccommodatie minimaal 1 maal per jaar te laten keuren.<br /><br />
Met sportieve groet,<br />
www.sportvloermanager365.nl<br /><br />';
		if (isset($this->__rapport['signeefilename']) && $this->__rapport['signeefilename'] != "")
			$returnstr .= '<br /><img src="rapport/'.$this->__r_id.'/'.$this->__rapport['signeefilename'].'">
				<br /><br />'.$this->__rapport['signeename'].'<br /><br />';
		$returnstr .= '
		</div>';
		$returnstr .= $this->printImages("SIGNATURE",0,"7. ONDERTEKENING");
		return $returnstr;		
	}
	private function getCertificate(){	
		if( $this->__measurements['A_NORM_0'] != 'onvoldoende' && $this->__measurements['B_NORM_0'] != 'onvoldoende' && $this->__measurements['E_NORM_0'] != 'onvoldoende'){
			$sporthall = $this->__data->getSporthallAddress($this->__rapport['sporthall_id']);
			$reports = $this->__data->getRapportData($this->__rapport['sporthall_id']);
			$returnstr = '
		<div class="pdf-vsv-cert" style="page-break-before: always;">';
			$returnstr .= '
			<div style="height: 1440px;">
				<div style="padding: 655px 0 0 0px; height: 200px;">
					<div class="column-left" style="padding-left: 72px; width: 500px; font-size: 11pt;">
						Specialisten van Veiligesportvloer.nl<br />
						hebben o.a. gemeten en gekeurd op:
						<ul>';
			$conclusion_measurements = array("A"=>"Stroefheid/Gladheid","B"=>"Oppervlaktehardheid","E"=>"Glanswaarden","D"=>"Schokabsorptie","C"=>"Verticale deformatie");
			foreach($conclusion_measurements as $m=>$title){
				if(strpos($this->__rapport['measurements'],$m)!==false) 
					$returnstr .= '<li>'.$title.'</li>';
			}
			$returnstr .= '
						</ul>
					</div>
					'.(count($sporthall)>0 ? '
					<div class="column-right" style="width: 430px;">
						<div style="height: 45px;">'.$sporthall['s_name'].' - '.$sporthall['o_name'].'</div>
						<div style="height: 50px;">'.$sporthall['address'].'</div>
						<div style="height: 40px;">'.$sporthall['city'].'</div>
					</div>':'').'
				</div>
				<div style="padding-left: 100px;">';
			$count = 0;
			foreach($reports as $r=>$report){
				if (strtotime($report['r_date']) <= strtotime($this->__rapport['r_date']) && $count < 4){
					$returnstr .= '
					<div style="padding: 0 0 25px 0;">
						<div class="col-vsv-i" style="width: 110px;"><b>'.substr($report['r_date'],0,4).'</b></div>
						<div class="col-vsv-i" style="width: 140px;"><b>'.date("d-m-Y", strtotime($report['r_date'])).'</b></div>
						<div class="col-vsv-i" style="width: 330px;">'.(isset($report['signeename'])?'<b>'.$report['signeename'].'</b>':'').'</div>
						<div class="col-vsv-i" style="height: 50px; width: 200px;">'.(isset($report['signeefilename']) && $report['signeefilename']!="" ?'
							<img src="rapport/'.$report['r_id'].'/'.$report['signeefilename'].'" style="height: 50px; max-width: 200px;">':'').'</div>
					</div>';
					$count++;
				}
			}
			$returnstr .= '
				</div>
			</div>
			<div class="pdf-vsv-footer">'.$this->nextPage().'</div>
		</div>';
			return $returnstr;		
		}
	}
	private function printImages($code, $images_p1, $title = "3. RESULTATEN"){
		$returnstr = '';
		$counter = 0;
		foreach($this->__images as $image){
			if ($image['code']==$code) $counter++;
		}
		if($counter > 0){
			if($images_p1 == 0)
				$returnstr .= '
	<div class="pdf-vsv-page'.$this->__outdoor.'" style="page-break-before: always;">
		<div class="pdf-vsv-header'.$this->__outdoor.'">'.$title.'</div>
		<div class="pdf-vsv-main">';
			$counter2 = 0;
			foreach($this->__images as $image){
				if ($image['code']==$code && $counter2 <=5) {
]					$returnstr .= '
				<div style="float: left; margin: 50px 0; text-align: center;">
					'.($image['before_revitalisation']==1?'<div>Voor revitalisatie</div>':($image['after_revitalisation']==1?'<div>Na revitalisatie</div>':'')).'
					<div><img src="rapport/'.$this->__r_id.'/'.$image['filename'].'" style="width: 300px; max-height: 400px; margin: 0 50px;"></div>
					'.($image['description']!=""?'<div>'.$image['description'].'</div>':'').'
				</div>';
					$counter2++;
					if($counter2 == (4-$images_p1) && $counter > $counter2  )
						$returnstr .= '
		</div>
		<div class="pdf-vsv-footer">'.$this->nextPage().'</div>
	</div>
	<div class="pdf-vsv-page'.$this->__outdoor.'" style="page-break-before: always;">
		<div class="pdf-vsv-header'.$this->__outdoor.'">3. RESULTATEN</div>
		<div class="pdf-vsv-main">';			
				}				
			}
		}
		if ($counter > 0 || ($counter==0 && $images_p1!=0))
			$returnstr .= '
		</div>
		<div class="pdf-vsv-footer">'.$this->nextPage().'</div>
	</div>';	
		return $returnstr;
	}
	private function nextPage(){
		$this->__page++;
		$page = $this->__page;
		//if ($page<10) $page = "0".$page;
		return $page;
	}

}

?>

