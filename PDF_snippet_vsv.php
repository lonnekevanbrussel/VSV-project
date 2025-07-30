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
	protected $__herculan_colors = array("RAL 9005"=>"121c24","RAL 7024"=>"2f404d","RAL 7030"=>"818582","RAL 7005"=>"818582","RAL 7001"=>"808e99",
			"RAL 7035"=>"c4c8cc","RAL 3009"=>"672b2c","RAL 8002"=>"73463b","RAL 1011"=>"ab834f","RAL 1019"=>"9a8a78","RAL 1001"=>"d0af7e",
			"RAL 1015"=>"e8d7b7","RAL 6005"=>"073633","RAL 6011"=>"517956","RAL 6033"=>"2a7e7b","RAL 6017"=>"3b7e3b","NCS 2050-G40Y"=>"83a44e",
			"RAL 6021"=>"749573","RAL 5009"=>"10496f","RAL 5014"=>"4e6c8b","RAL 5012"=>"0d71b6","RAL 5024"=>"5085a7","RAL 4005"=>"755792",
			"NCS 3030-R60B"=>"7d76a7","RAL 3014"=>"cd646f","NCS 3060-Y40R"=>"af5f1d","RAL 1007"=>"e89b01","NCS 1060Y"=>"ffd44e");
	protected $__finishings = array("houtlak"=>"Hout lak","houtwas"=>"Hout was","linoleum"=>"Linoleum","pu-coating"=>"PU Coating","pvc"=>"PVC","other"=>"Overig");
	protected $__appearance = array(1=>"Schoon en mat",2=>"Licht verzadigd",3=>"Verzadigd",4=>"Erg verzadigd",5=>"Extreem verzadigd");
	protected $__clean = array(0=>"Matig",1=>"Prima",2=>"Keurig");
	protected $__stripes = array("geen"=>"Geen","enkele"=>"Enkele","veel"=>"Veel","zeerveel"=>"Zeer veel");
	
	protected $__years = array();
	protected $__infill = array("epdm-st" => 'EPDM ST',"epdm-bionic" => 'EPDM (bionic fibre)',"pe-promax-soft" => 'PE (ProMax soft)',
	"pe-promax-v2" => 'PE (ProMax V2)',"pe-promax-r" => 'PE (ProMax R)',"pro-gran-groen" => 'Pro gran (groen gecoat)',"pro-gran-bruin" => 'Pro gran (bruin)',
	"sbr" => 'SBR',"sbr-groen" => 'SBR (groen)',"sbr-grof" => 'SBR (grof)',"tpe" => 'TPE',"tpe-stervorm-groen" => 'TPE (ster vorm groen)',
	"tpe-stervorm-wit" => 'TPE (ster vorm wit)',"tpe-cruijf" => 'TPE (Cruijff court)',"tpe-forgrin" => 'TPE (Forgrin)',"tpe-holo" => 'TPE (Holo)',
	"tpe-terra" => 'TPE (Terra)',"kokos-vezels" => 'Kokos vezels / kurk',"kurk" => 'Kurk',"zand" => 'Zand');
	protected $__fundatie = array("lava" => 'Lava',"zand" => 'Zand',"overig" => 'Overig');
	protected $__onderbouw = array("beton" => 'Beton',"zand" => 'Zand',"overig" => 'Overig');	
	protected $__access = array("Sleutels ophalen"=>"Sleutels ophalen","Alarm codes met sleutel"=>"Alarm codes met sleutel",">Bellen voor toegan"=>"Bellen voor toegang");
	protected $__entrance = array("Voordeur"=>"Voordeur","Nooduitgang voor / achter-zijde"=>"Nooduitgang voor / achter-zijde");	
	protected $__cleaning = array(0=>"Dagelijks",1=>"Wekelijks",5=>"Twee wekelijks",2=>"Maandelijks",3=>"Elk kwartaal",6=>"Elk half jaar",4=>"Jaarlijks",7=>"Onbekend",8=>"Nooit");
	protected $__prod_day = array("pulastic_eco_clean"=>"Pulastic Eco Clean","vsv_r1"=>"VSV-R1","onbekend"=>"Onbekend","other"=>"Anders");
	protected $__prod_per = array("pulastic_deep_clean"=>"Pulastic deep clean","vsv_p2"=>"VSV-P2","onbekend"=>"Onbekend","other"=>"Anders");
	protected $__machine = array("achterloper"=>"Achterloper","opzitter"=>"Opzitter","robot"=>"Robot","onbekend"=>"Onbekend");
	protected $__machine_holder = array("pads"=>"Pads","borstels"=>"Borstels","onbekend"=>"Onbekend");
	protected $__machine_app = array("vlekverwijderen"=>"Vlekverwijderen","direct"=>"Direct reinigen","indirect"=>"Indirect reinigen","onbekend"=>"Onbekend");
	protected $__equipment_suppliers = array(0=>"Janssen &amp; Fritsen",1=>"Bosan",2=>"Jeka",3=>"Nijha",4=>"Schelde",5=>"Anders-onbekend");

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
			if(in_array("A",$measurements)) $this->__sHTML_Content .= $this->getMeasurementA("A");
			if(in_array("E",$measurements)) $this->__sHTML_Content .= $this->getMeasurementE("E");
			if(in_array("B",$measurements)) $this->__sHTML_Content .= $this->getMeasurementB("B");
			if(in_array("D",$measurements)) $this->__sHTML_Content .= $this->getMeasurementD("D");
			if(in_array("C",$measurements)) $this->__sHTML_Content .= $this->getMeasurementC("C");
			if(in_array("F",$measurements)) $this->__sHTML_Content .= $this->getMeasurementF("F");
			if(in_array("OA",$measurements)) $this->__sHTML_Content .= $this->getMeasurementOAGH("OA");
			if(in_array("OB",$measurements)) $this->__sHTML_Content .= $this->getMeasurementOBEC("OB");
			if(in_array("OE",$measurements)) $this->__sHTML_Content .= $this->getMeasurementOBEC("OE");
			if(in_array("OC",$measurements)) $this->__sHTML_Content .= $this->getMeasurementOBEC("OC");
			if(in_array("OD",$measurements)) $this->__sHTML_Content .= $this->getMeasurementOD("OD");
			if(in_array("OF",$measurements)) $this->__sHTML_Content .= $this->getMeasurementOF("OF");
			if(in_array("OG",$measurements)) $this->__sHTML_Content .= $this->getMeasurementOAGH("OG");
			if(in_array("OH",$measurements)) $this->__sHTML_Content .= $this->getMeasurementOAGH("OH");
			if(in_array("G",$measurements)) $this->__sHTML_Content .= $this->getMeasurementG("G");
			if(in_array("OI",$measurements)) $this->__sHTML_Content .= $this->getMeasurementG("OI");
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
	
	private function getHeader(){
		$returnstr = '
	<!DOCTYPE html>
	<html>
		<head>
		<style>
.stroefheid_table td { padding: 5px; }
.stroefheid_table th { padding: 5px; }	
		</style>
		</head>
		<body >';
		return $returnstr;
	}
	private function getFooter(){
		$returnstr = '
		</body>
	</html>';
		return $returnstr;
	}
	private function getFrontPage(){
		$cover_img = "";
		foreach($this->__images as $image){
			if ($image['code']=='COVER') $cover_img = "rapport/".$this->__rapport["r_id"]."/".$image['filename'];
		}
		$bg_image = "pdf_front.jpg";
		if ($this->__stype == 1) $bg_image = "pdf_front_outdoor.jpg";
		else if ($this->__rapport['owner_organisation_id']==1163) $bg_image = "pdf_front_sika.jpg";
		else if ($this->__rapport['owner_organisation_id']==1164) $bg_image = "pdf_front_nijha.jpg";
		else if ($this->__rapport['floortype']==2) $bg_image = "pdf_front_hout.jpg";

		$returnstr = '
	<div class="pdf-vsv-front" style="page-break-before: always; background-image:url(images/'.$bg_image.');">
		<div class="container" style="height: 975px;">
				'.($cover_img != '' ? '
				<div style="padding-top: 100px; width:100%;">
					<img src="'.$cover_img.'" class="cover-img" style="border: 10px solid #F5D70D;" />
				</div>':'').'
		</div>
		<div style="font-size: 16pt; font-weight: 900; text-align: right; width: 95%; height: 110px; padding-right: 5%; ">
			Inspectie ten behoeve van duurzaamheid en<br /> veiligheid van uw sportvloer
		</div>
		<div style="font-size: 18pt; font-weight: 900; float: left; margin-left: 150px;">
			'.ucfirst($this->__rapport['halltype']).'
		</div>
		<div class="pdf-title-vsv" style="">
			<div><div class="col-vsv-front"><b>Dossier nr.</b></div><div class="col-vsv-front">'.$this->__rapport['dosiernr'].'</div></div>
			<div><div class="col-vsv-front"><b>Rapport datum</b></div><div class="col-vsv-front">'.date("d-m-Y", strtotime($this->__rapport['r_date'])).'</div></div>
			'.(isset($this->__rapport['date_revitalization']) && $this->__rapport['date_revitalization']!='0000-00-00' ? 
				'<div><div class="col-vsv-front"><b>Inspectie datum</b></div><div class="col-vsv-front">'.date("d-m-Y", strtotime($this->__rapport['date_revitalization'])).'</div></div>':'').'
			<div><div class="col-vsv-front"><b>Opdrachtgever</b></div><div class="col-vsv-front">'.$this->__rapport['o_name'].'</div></div>
			'.(isset($this->__rapport['s_phone']) && $this->__rapport['s_phone']!='' ? 
				'<div><div class="col-vsv-front"><b>Telefoonnummer</b></div><div class="col-vsv-front">'.$this->__rapport['s_phone'].'</div></div>':'').'
			'.(isset($this->__rapport['s_contact_email']) && $this->__rapport['s_contact_email']!='' ? 
				'<div><div class="col-vsv-front"><b>E-mailadres</b></div><div class="col-vsv-front">'.$this->__rapport['s_contact_email'].'</div></div>':'').'
		</div>
	</div>';
		return $returnstr;		
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
	private function getMeasurementA($m){				
		$returnstr = '
	<div class="pdf-vsv-page'.$this->__outdoor.'" style="page-break-before: always;">
		<div class="pdf-vsv-header'.$this->__outdoor.'">3. RESULTATEN</div>
		<div class="pdf-vsv-main">';
		$returnstr .= '
			<h3>Stroefheid/Gladheid</h3>
			<div style="display: flex; flex-direction: column">
				  <p>Er zijn metingen op locatie uitgevoerd met de Floor Slide Control 2011 (FSC 2011). De
					metingen zijn uitgevoerd passend binnen Wuppertaler safety standards.
					Met de FSC 2011 wordt de dynamische wrijvingsco&#235;ffici&#235;nt van vloeren bepaald onder de
					normaalbelasting van de stempel van 24N en met een snelheid 0,2 m/sec.</p>
				  <p>De waarden die de FSC aangeeft zijn als volgt te interpreteren:</p>
				  <table class="stroefheid_table">
					<tbody><tr>
					  <th style="width: 200px;">Waarde</th>
					  <th></th>
					</tr>
					<tr>
					  <td>&lt; 0.30</td>
					  <td>Onveilige vloer</td>
					</tr>
					<tr>
					  <td>0.30 - 0.44</td>
					  <td>Voorwaardelijk veilig</td>
					</tr>
					<tr>
					  <td>&gt; 0.45</td>
					  <td>Veilig</td>
					</tr>
					<tr>
					  <td>&gt; 0.60</td>
					  <td>Zeer veilig (situatie nieuwe vloer)</td>
					</tr>
				  </tbody></table>

			</div>
			<br />
			<div>
				<img src="images/stroefheid1.png" style="width: 400px;">
				<img src="images/stroefheid2.jpg" style="width: 400px;">
			</div>
			<br />
			<table class="stroefheid_table">
				<tbody><tr>
				  <th></th>
				  <th>Zonder bewerking</th>
				  <th>Handmatig bewerkt</th>
				  <th>Na revitalisatie</th>
				</tr>';
		$measurement_nrs = explode(",",$this->__rapport['measurement_nrs']);
		foreach($measurement_nrs as $m_nr){
			$returnstr .= '
				<tr>
					<td><b>Positie '.$m_nr.'</b></td>';
			for($i=0; $i<=2; $i++)
				$returnstr .= '
					<td>'.$this->__measurements[$m.'_'.$m_nr.'_'.$i].'</td>';
			$returnstr .= '
				</tr>';
		}
		$returnstr .= '
				<tr>
					<td><b>Overall gemiddelde</b></td>';
		for($i=0; $i<=2; $i++)
			$returnstr .= '
					<td><b>'.$this->__measurements[$m.'_AVG_'.$i].'</b></td>';
		$color = "#4bbf6b";
		if (isset($this->__measurements[$m.'_NORM_0']) && $this->__measurements[$m.'_NORM_0'] == 'onveilig') 
			$color = "#eb5a46";
		else if (isset($this->__measurements[$m.'_NORM_0']) && strpos(strtolower($this->__measurements[$m.'_NORM_0']),'voorwaardelijk') !==false) 
			$color = "#FF9F1A";
		$returnstr .= '
				</tr>
		  </table>
		  <br />
		  <div>
			<div class="vsv-beoordeling">Beoordeling &emsp;</div>
			<div class="vsv-beoordeling" style="background-color: '.$color.'; font-weight: 900;">'.ucfirst($this->__measurements[$m.'_NORM_0']).'</div>
			<br /><br />
		  </div>
		</div>
		<div class="pdf-vsv-footer">'.$this->nextPage().'</div>
	</div>';
		$returnstr .= $this->printImages("A",0);
		return $returnstr;		
	}
	private function getMeasurementE($m){				
		$returnstr = '
	<div class="pdf-vsv-page'.$this->__outdoor.'" style="page-break-before: always;">
		<div class="pdf-vsv-header'.$this->__outdoor.'">3. RESULTATEN</div>
		<div class="pdf-vsv-main">';
		$returnstr .= '
			<h3>Glanswaarden</h3>
			<br />
			<div style="display: flex; flex-direction: column">
			  <table class="stroefheid_table" style="width: 300px; float: left;">
				<tbody><tr>
				  <th>Norm</th>
				  <th></th>
				</tr>
				<tr>
				  <td>Klasse 1</td>
				  <td>&lt; 15%</td>
				</tr>
				<tr>
				  <td>Klasse 2</td>
				  <td>&lt; 15%</td>
				</tr>
				<tr>
				  <td>Klasse 3</td>
				  <td>&lt; 25%</td>
				</tr>
			  </tbody></table>
			  <img src="images/glansmeter2.jpg" style="width: 350px; margin: -40px 0 0 50px;">
			</div>
			<br /><br />
			<div style="display: flex; flex-direction: column">
			  <p>Gemeten waarden in %: Glansmeter:</p>	
			  <table class="stroefheid_table">
				<tbody><tr>
				  <th></td>
				  <th>Zonder bewerking</th>
				  <th>Handmatig bewerkt</th>
				  <th>Na revitalisatie</th>
				</tr>';
		$measurement_nrs = explode(",",$this->__rapport['measurement_nrs']);
		foreach($measurement_nrs as $m_nr){
			$returnstr .= '
				<tr>
					<td><b>Positie '.$m_nr.'</b></td>';
			for($i=0; $i<=2; $i++)
				$returnstr .= '
					<td>'.$this->__measurements[$m.'_'.$m_nr.'_'.$i].'</td>';
			$returnstr .= '
				</tr>';
		}
		$returnstr .= '
				<tr>
					<td><b>Overall gemiddelde</b></td>';
		for($i=0; $i<=2; $i++)
			$returnstr .= '
					<td><b>'.$this->__measurements[$m.'_AVG_'.$i].'</b></td>';
		$color = "#4bbf6b";
		if (isset($this->__measurements[$m.'_NORM_0']) && $this->__measurements[$m.'_NORM_0'] == 'onvoldoende')
			$color = "#eb5a46";
		$returnstr .= '
				</tr>
			  </table>
			  <br />
			  <div>
				<div class="vsv-beoordeling">Beoordeling &emsp;</div>
				<div class="vsv-beoordeling" style="background-color: '.$color.'; font-weight: 900;">'.ucfirst($this->__measurements[$m.'_NORM_0']).'</div>
			  </div>';
		$returnstr .= $this->printImages("E",2);
		return $returnstr;		
	}
	private function getMeasurementB($m){				
		$returnstr = '
	<div class="pdf-vsv-page'.$this->__outdoor.'" style="page-break-before: always;">
		<div class="pdf-vsv-header'.$this->__outdoor.'">3. RESULTATEN</div>
		<div class="pdf-vsv-main">';
		$returnstr .= '
			<h3>Oppervlaktehardheid</h3>
			<br />
			<div style="display: flex; flex-direction: column;">
				<div style="float: left;">
				  <table class="stroefheid_table" style="width: 400px;">
					<tbody><tr>
					  <th>Norm</th>
					  <th></th>
					</tr>
					<tr>
					  <td>Klasse 1</td>
					  <td>70-95 Shore A</td>
					</tr>
					<tr>
					  <td>Klasse 2</td>
					  <td>70-98 Shore A</td>
					</tr>
					<tr>
					  <td>Klasse 3</td>
					  <td>50-98 Shore A</td>
					</tr>
				  </tbody></table>
				</div>
				<div style="float: right;">
				  <img src="images/oppervlaktehardheid.jpg" style="width: 350px; margin: -80px 0 0 50px;">
				</div>
			</div>
			<br /><br />
			<div style="margin-top: 120px;">Gemeten waarden: Shoremeter A</div>
			<div style="">
				<table class="stroefheid_table" style="width: 500px;">
					<tbody><tr>
						<th><b>Punt</th>
						<th>Waarde</b></th>
					</tr>';
		$measurement_nrs = explode(",",$this->__rapport['measurement_nrs']);
		foreach($measurement_nrs as $m_nr){
			$returnstr .= '
					<tr>
						<td><b>Positie '.$m_nr.'</b></td>';
			$returnstr .= '
						<td>'.$this->__measurements[$m.'_'.$m_nr.'_0'].'</td>';
			$returnstr .= '
					</tr>';
		}
		$returnstr .= '
					<tr>
						<td><b>Overall gemiddelde</b></td>';
		$returnstr .= '
						<td><b>'.$this->__measurements[$m.'_AVG_0'].'</b></td>';
		$color = "#4bbf6b";
		if (isset($this->__measurements[$m.'_NORM_0']) && $this->__measurements[$m.'_NORM_0'] == 'onvoldoende')
			$color = "#eb5a46";
		$returnstr .= '
					</tr>
				</table>
				<br />
				<div>
					<div class="vsv-beoordeling">Beoordeling &emsp;</div>
					<div class="vsv-beoordeling" style="background-color: '.$color.'; font-weight: 900;">'.ucfirst($this->__measurements[$m.'_NORM_0']).'</div>
				</div>
			</div>';
		$returnstr .= $this->printImages("B",2);
		return $returnstr;		
	}
	private function getMeasurementD($m){				
		$norm_floortype = array(
		0 => array("Klasse 1"=>"40% - 75%","Klasse 2"=>"30% - 40%","Klasse 3"=>"25% - 30%","onveilig"=>"< 25%"), 
		1 => array("Klasse 1"=>"40% - 75%","aandacht nodig"=>"25% - 40%","onveilig"=>"< 25%"),
		2 => array("Klasse 1"=>"55% - 75%","Klasse 2"=>"40% - 55%","aandacht nodig"=>"25% - 40%","onveilig"=>"< 25%"),
		3 => array("Klasse 1"=>"55% - 75%","Klasse 2"=>"45% - 55%","aandacht nodig"=>"25% - 45%","onveilig"=>"< 25%"));
		$floortype = (isset($this->__rapport['floortype']) ? $this->__rapport['floortype'] : 0);
		$returnstr = '
	<div class="pdf-vsv-page'.$this->__outdoor.'" style="page-break-before: always;">
		<div class="pdf-vsv-header'.$this->__outdoor.'">3. RESULTATEN</div>
		<div class="pdf-vsv-main">';
		$returnstr .= '
			<h3>Schokabsorptie</h3>
			<br />
			<div style="display: flex; flex-direction: column">
				  <table class="stroefheid_table">
					<tbody><tr>
					  <th><b>Norm</b></th>
					  <th>'.(isset($this->__rapport['floortype']) ? $this->__floors[$floortype]: '').'</th>
					</tr>';
		foreach($norm_floortype[$floortype] as $norm=>$value)
			$returnstr .= '
					<tr>
					  <td>'.ucfirst($norm).'</td>
					  <td>'.$value.'</td>
					</tr>';
		$returnstr .= '
				  </tbody></table>
			</div>
			<br />
			<div style="display: flex; align-items: center; justify-content: center">
				<div style="flex-basis: 25%;"><img src="images/vertialevervorming.png" style="height: 250px;"></div>
				<div>Een essentieel aspect van sportvloeren is hun vermogen om schokken te absorberen, wat van cruciaal belang is voor de veiligheid en het welzijn van sporters. De Europese norm CE 14904 norm stelt specifieke eisen aan sportvloeren, waaronder het schokabsorptiepercentage. Wanneer een sportvloer een schokabsorptiepercentage van minder dan 25% scoort, voldoet deze niet langer aan de vereisten van de CE 14904 norm. Het niet voldoen aan deze norm betekent dat de sportvloer niet de garantie biedt die nodig is voor een veilige sportomgeving en zal worden afgekeurd.</div>
			</div>
			<br />
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
						
		if ($this->__measurements[$m.'_NORM_0'] == 'onvoldoende'){
			$this->__measurements[$m.'_NORM_0'] == 'onveilig';
			if ($floortype != 0 && isset($this->__measurements[$m.'_AVG_3']) && $this->__measurements[$m.'_AVG_3']>=25)
				$this->__measurements[$m.'_NORM_0'] = 'aandacht nodig';
		}
		$color = "#4bbf6b";
		if (isset($this->__measurements[$m.'_NORM_0']) && $this->__measurements[$m.'_NORM_0'] == 'aandacht nodig') $color = "#FF9F1A";
		if (isset($this->__measurements[$m.'_NORM_0']) && $this->__measurements[$m.'_NORM_0'] == 'onveilig') $color = "#eb5a46";
		$returnstr .= '
					</tr>
				</table>	
			</div>
		  <br />
		  <div>
			<div class="vsv-beoordeling">Beoordeling &emsp;</div>
			<div class="vsv-beoordeling" style="background-color: '.$color.'; font-weight: 900;">'.ucfirst($this->__measurements[$m.'_NORM_0']).'</div>
			<br /><br />
			* Meting 1 is een testmeting
			<br />
			** Gemiddelde van meting 2 + 3 is het resultaat
		  </div>
		</div>
		<div class="pdf-vsv-footer">'.$this->nextPage().'</div>
	</div>';
		$returnstr .= $this->printImages("D",0);
		return $returnstr;		
	}
	private function getMeasurementC($m){				
		$norm_floortype = array( 
		0 => array("Klasse 1"=>"&#8805; 3.5mm","Klasse 2"=>"&#8805; 3.0 mm","Klasse 3"=>"&#8805; 2.0 mm","Onvoldoende"=>"< 2.0 mm"),
		1 => array("Klasse 1"=>"&#8805; 3.5mm","Onvoldoende"=>"< 3.5mm"),
		2 => array("Klasse 1"=>"2.3 - 5mm","Klasse 2"=>"1.8 - 3.5mm","Onvoldoende"=>"< 1.8mm"),
		3 => array("Klasse 1"=>"2.3 - 5mm","Klasse 2"=>"1.8 - 5mm","Onvoldoende"=>"< 1.8mm"));
		$floortype = (isset($this->__rapport['floortype']) ? $this->__rapport['floortype'] : 0);
		$returnstr = '
	<div class="pdf-vsv-page'.$this->__outdoor.'" style="page-break-before: always;">
		<div class="pdf-vsv-header'.$this->__outdoor.'">3. RESULTATEN</div>
		<div class="pdf-vsv-main">';
		$returnstr .= '
			<h3>Verticale deformatie</h3>
			<br />
			<div style="display: flex; flex-direction: column">
				  <table class="stroefheid_table">
					<tbody><tr>
					  <th><b>Norm</b></th>
					  <th>'.(isset($this->__rapport['floortype']) ? $this->__floors[$floortype]: '').'</th>
					</tr>';
		foreach($norm_floortype[$floortype] as $norm=>$value)
			$returnstr .= '
					<tr>
					  <td>'.$norm.'</td>
					  <td>'.$value.'</td>
					</tr>';
		$returnstr .= '
				  </tbody></table>
			</div>
			<br />
			<div style="display: flex; flex-direction: column">
				<p>Gemeten waarden in mm: Fieldtester (conform Berlin Athlete 3a)</p>
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
				* Meting 1 is een testmeting
				<br />
				** Gemiddelde van meting 2 + 3 is het resultaat
			</div>';
		$returnstr .= $this->printImages("C",2);
		return $returnstr;		
	}
	private function getMeasurementF($m){				
		$sports = array("BAD"=>"Badminton","BAS"=>"Basketbal","FANTA"=>"Fantasieveld","HAND"=>"Handbal","KORF"=>"Korfbal","SOC"=>"Voetbal",
		"TEN"=>"Tennis","VOL"=>"Volleybal");
		$state_lines = array(1=>"Slecht",2=>"Redelijk",3=>"Prima",4=>"Uitstekend");		
		
		$returnstr = '
	<div class="pdf-vsv-page'.$this->__outdoor.'" style="page-break-before: always;">
		<div class="pdf-vsv-header'.$this->__outdoor.'">3. RESULTATEN</div>
		<div class="pdf-vsv-main">';
		$returnstr .= '
			<h3>Belijning en vloervoorzieningen</h3>
			<br />
			<div style="display: flex; flex-direction: column">
				  <table class="stroefheid_table">
					<tbody><tr>
					  <th><b>Spel</th>
					  <th>Kleur</th>
					  <th>Aantal</b></th>
					</tr>';
		foreach($sports as $s=>$sport){
			if (isset($this->__measurements["F_".$s."_0"]))
				$returnstr .= '
					<tr>
					  <td><b>'.$sport.'</b></td>
					  <td>'.$this->__measurements["F_".$s."_0"].'</td>
					  <td>'.$this->__measurements["F_".$s."_1"].'</td>
					</tr>';
			if (isset($this->__measurements["F_".$s."2_0"]))
				$returnstr .= '
					<tr>
					  <td><b>'.$sport.'2</b></td>
					  <td>'.$this->__measurements["F_".$s."2_0"].'</td>
					  <td>'.$this->__measurements["F_".$s."2_1"].'</td>
					</tr>';
		}
		$returnstr .= '
				  </tbody></table>
			</div>
			<br />';
		if (isset($this->__measurements["F_STAAT_0"])) $returnstr .= '<p><b>Algehele staat belijning:</b><br />'.$state_lines[$this->__measurements["F_STAAT_0"]].'</p>';
		if (isset($this->__measurements["F_REM1_0"])) $returnstr .= '<p><b>Opmerkingen staat belijning:</b><br />'.$this->__measurements["F_REM1_0"].'</p>';
		if (isset($this->__measurements["F_REM2_0"])) $returnstr .= '<p><b>Opmerkingen inrichting en voorzieningen sporthal:</b><br />'.$this->__measurements["F_REM2_0"].'</p>';
		$returnstr .= $this->printImages("F",2);
		return $returnstr;		
	}
	private function getMeasurementG($m){	
		$Gvars = array("HUMID"=>"Luchtvochtigheid %","TEMP"=>"Temperatuur C","TIME"=>"Tijdstip");
		$returnstr = '
	<div class="pdf-vsv-page'.$this->__outdoor.'" style="page-break-before: always;">
		<div class="pdf-vsv-header'.$this->__outdoor.'">3. RESULTATEN</div>
		<div class="pdf-vsv-main">';
		$returnstr .= '
			<h3>Temperatuur en relatieve luchtvochtigheid</h3>
			<br />
			<div style="display: flex; flex-direction: column">
				  <table class="stroefheid_table">
					<tbody><tr>
					  <th><b>Spel</th>
					  <th>Waarde</th>
					</tr>';
		foreach($Gvars as $g=>$desc){
			if (isset($this->__measurements[$m."_".$g."_0"]))
				$returnstr .= '
					<tr>
					  <td><b>'.$desc.'</b></td>
					  <td>'.($g=='HUMID' || $g=="TEMP" ? round($this->__measurements[$m."_".$g."_0"],1) : $this->__measurements[$m."_".$g."_0"]).'</td>
					</tr>';
		}
		$returnstr .= '
				  </tbody></table>
			</div>
			<br /><br />';
		$returnstr .= $this->printImages($m,2);
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
	private function getMeasurementOD($m){				
		$returnstr = '
	<div class="pdf-vsv-page'.$this->__outdoor.'" style="page-break-before: always;">
		<div class="pdf-vsv-header'.$this->__outdoor.'">3. RESULTATEN</div>
		<div class="pdf-vsv-main">';
		$returnstr .= '
			<h3>'.$this->__meetpuntenO[$m].'</h3>
			<br />
			<div>'.(isset($this->__measurements[$m.'_DESCRIPTION_0'])?$this->__measurements[$m.'_DESCRIPTION_0']:
				'Norm volgens NOCNSF-KNVB2-18: tussen de 0,60 en 1,10 meter (verticaal gemeten).').'
			</div>
			<br /><br /><b>Voor revitalisatie</b>
			<div style="display: flex; flex-direction: column">
				<table class="stroefheid_table">
					<tbody><tr>
					  <th><b>Punt</th>
					  <th>Meting 1</th>
					  <th>Meting 2</th>
					  <th>Gem. 1+2 *</b></th>
					</tr>';
		$measurement_nrs = explode(",",$this->__rapport['measurement_nrs']);
		foreach($measurement_nrs as $m_nr){
			$returnstr .= '
					<tr>
						<td><b>Positie '.$m_nr.'</b></td>';
			for($i=0; $i<=2; $i++)
				$returnstr .= '
						<td>'.($i==2?'<b>':'').$this->__measurements[$m.'_'.$m_nr.'_'.$i].($i==2?'</b>':'').'</td>';
			$returnstr .= '
					</tr>';
		}
		$returnstr .= '
					<tr>
						<td><b>Overall gemiddelde</b></td>';
		for($i=0; $i<=2; $i++)
			$returnstr .= '
						<td><b>'.$this->__measurements[$m.'_AVG_'.$i].'</b></td>';
		$returnstr .= '
					</tr>
				</table>	
     			* Gemiddelde van meting 1 + 2 is het resultaat
			</div>
			<br /><b>Na revitalisatie</b>		
			<div style="display: flex; flex-direction: column">
				<table class="stroefheid_table">
					<tbody><tr>
					  <th><b>Punt</th>
					  <th>Meting 1</th>
					  <th>Meting 2</th>
					  <th>Gem. 1+2 *</b></th>
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
			* Gemiddelde van meting 1 + 2 is het resultaat
			</div>
			<br />
		  <div>
			<div class="vsv-beoordeling">Beoordeling &emsp;</div>
			<div class="vsv-beoordeling" style="background-color: '.$color.'; font-weight: 900;">'.ucfirst($this->__measurements[$m.'_NORM_0']).'</div>
			<br /><br />
		  </div>
		</div>
		<div class="pdf-vsv-footer">'.$this->nextPage().'</div>
	</div>';
		$returnstr .= $this->printImages($m,0);
		return $returnstr;		
	}
	private function getMeasurementOF($m){				
		$directions = array("N"=>"Noord","O"=>"Oost","Z"=>"Zuid","W"=>"West");
		$returnstr = '
	<div class="pdf-vsv-page'.$this->__outdoor.'" style="page-break-before: always;">
		<div class="pdf-vsv-header'.$this->__outdoor.'">3. RESULTATEN</div>
		<div class="pdf-vsv-main">';
		$returnstr .= '
			<h3>'.$this->__meetpuntenO[$m].'</h3>
			<div>'.(isset($this->__measurements[$m.'_DESCRIPTION_0'])?$this->__measurements[$m.'_DESCRIPTION_0']:
				'Norm volgens NOCNSF-KNVB2-18: tussend de 4 en 15 meter').'
			</div>
			<b>Voor revitalisatie</b>
			<div style="display: flex; flex-direction: column; font-size: 10pt;">
				<table class="stroefheid_table" style="width: 100%;">
					<tbody><tr>
					  <th><b>Punt</th>
					  <th>Richting</th>
					  <th>Meting 1</th>
					  <th>Meting 2</th>
					  <th>Gem. 1+2 *</b></th>
					</tr>';
		$measurement_nrs = explode(",",$this->__rapport['measurement_nrs']);
		foreach($measurement_nrs as $m_nr){
			$c = 0;
			foreach($directions as $d=>$dir){
				$returnstr .= '
					<tr>
						<td><b>Positie '.$m_nr.'</b></td>
						<td>'.$dir.'</td>';
				for($i=$c; $i<=$c+2; $i++)
					$returnstr .= '
						<td>'.($i==($c+2)?'<b>':'').$this->__measurements[$m.'_'.$m_nr.'_'.$i].($i==($c+2)?'</b>':'').'</td>';
				$returnstr .= '
					</tr>';
				$c = $c + 3;
			}
		}
		$returnstr .= '
					<tr>
						<td><b>Overall gemiddelde</b></td>
						<td></td>';
		for($i=0; $i<=2; $i++)
			$returnstr .= '
						<td><b>'.$this->__measurements[$m.'_AVG_'.$i].'</b></td>';
		$returnstr .= '
					</tr>
				</table>	
     			* Gemiddelde van meting 1 + 2 is het resultaat
			</div>
		</div>
		<div class="pdf-vsv-footer">'.$this->nextPage().'</div>
	</div>
	<div class="pdf-vsv-page'.$this->__outdoor.'" style="page-break-before: always;">
		<div class="pdf-vsv-header'.$this->__outdoor.'">3. RESULTATEN</div>
		<div class="pdf-vsv-main">			
			<b>Na revitalisatie</b>		
			<div style="display: flex; flex-direction: column; font-size: 10pt;">
				<table class="stroefheid_table" style="width: 100%;">
					<tbody><tr>
					  <th><b>Punt</th>
					  <th><b>Richting</th>
					  <th>Meting 1</th>
					  <th>Meting 2</th>
					  <th>Gem. 1+2 *</b></th>
					</tr>';
		$measurement_nrs = explode(",",$this->__rapport['measurement_nrs']);
		foreach($measurement_nrs as $m_nr){
			$c = 12;
			foreach($directions as $d=>$dir){
				$returnstr .= '
					<tr>
						<td><b>Positie '.$m_nr.'</b></td>
						<td>'.$dir.'</td>';
				for($i=$c; $i<=$c+2; $i++)
					$returnstr .= '
						<td>'.($i==($c+2)?'<b>':'').$this->__measurements[$m.'_'.$m_nr.'_'.$i].($i==($c+2)?'</b>':'').'</td>';
				$returnstr .= '
					</tr>';
				$c = $c + 3;
			}
		}
		$returnstr .= '
					<tr>
						<td><b>Overall gemiddelde</b></td>
						<td></td>';
		for($i=12; $i<=14; $i++)
			$returnstr .= '
						<td><b>'.$this->__measurements[$m.'_AVG_'.$i].'</b></td>';
		$color = "#4bbf6b";
		if (isset($this->__measurements[$m.'_NORM_0']) && $this->__measurements[$m.'_NORM_0'] == 'onvoldoende')
			$color = "#eb5a46";
		$returnstr .= '
					</tr>
				</table>	
			* Gemiddelde van meting 1 + 2 is het resultaat
			</div>
			<br />
		  <div>
			<div class="vsv-beoordeling">Beoordeling &emsp;</div>
			<div class="vsv-beoordeling" style="background-color: '.$color.'; font-weight: 900;">'.ucfirst($this->__measurements[$m.'_NORM_0']).'</div>
			<br /><br />
		  </div>
		</div>
		<div class="pdf-vsv-footer">'.$this->nextPage().'</div>
	</div>';
		$returnstr .= $this->printImages($m,0);
		return $returnstr;		
	}
	private function getDammages(){	
		$offerte = $this->__data->getDammagesOfferteProducts($this->__r_id);
		$returnstr = '
	<div class="pdf-vsv-page'.$this->__outdoor.'" style="page-break-before: always;">
		<div class="pdf-vsv-header'.$this->__outdoor.'">4. BESCHADIGINGEN</div>
		<div class="pdf-vsv-main">';
		$returnstr .= '
			<div>
				<div class="col-vsv-i"><b>Beschadigingen</b></div>
				<div class="col-vsv-i">'.$this->__rapport['dammages'].'</div>
			</div><br />';
		if ($this->__rapport['dammages_desc'] != "")
			$returnstr .= '
			<div>
				<b>Omschrijving</b>
				<br />'.$this->__rapport['dammages_desc'].'
			</div>
			<br />';
		if ($this->__rapport['dammages_remark'] != "")
			$returnstr .= '
			<div>
				<b>Opmerkingen</b>
				<br />'.$this->__rapport['dammages_remark'].'
			</div>
			<br />';
		if (count($offerte)>0){
			$productsdb = $this->__data->getProducts();
			$products = array();
			foreach($productsdb as $row) $products[$row['product_id']] = $row;
			$returnstr .= '
				<b>Prijsindicatie</b>
				<br />Let op: Genoemde prijzen zijn een grove indicatie van de daadwerkelijke kosten. Deze offerte
is opgemaakt n.a.v. een inspectie. Eventuele afwijkende kosten zullen met u worden gecommuniceerd.
			<div style="display: flex; flex-direction: column">
			  <table class="stroefheid_table">
				<tbody><tr>
				  <th><b>Product</th>
				  <th>Prijs ex. BTW</th>
				  <th>Aantal</th>
				  <th>Totaal ex. BTW</th>
				</tr>';
			$p_id = "";
			foreach($offerte as $p_id=>$row){
				$returnstr .= '
				<tr>
				  <td>'.$products[$p_id]['name'].'</td>
				  <td>'.number_format($products[$p_id]['price_part'],2).'</td>
				  <td>'.$row['quotation_size'].'</td>
				  <td>'.number_format($row['quotation_price_exvat'],2).'</td>
				</tr>';
			}
			$mk = 0;
			$vk = 0;
			foreach($products as $row){
				if (strpos(strtolower($row['name']),'voorrijkosten') !== false) $vk = $row['price_part'];
				if (strpos(strtolower($row['name']),'materiaalkosten') !== false) $mk = $row['price_part'];
			}
			$returnstr .= '
				<tr>
				  <td>Materiaalkosten</td><td></td><td></td><td>'.number_format($mk,2).'</td>
				</tr>
				<tr>
				  <td>Voorrijkosten</td><td></td><td></td><td>'.number_format($vk,2).'</td>
				</tr>
				<tr>
				  <td>Totaal ex. BTW</td><td></td><td></td><td>'.number_format($offerte[$p_id]['total_exvat'],2).'</td>
				</tr>
				</tbody>
			  </table>
			</div>
';
		}
		$returnstr .= '	
		</div>
		<div class="pdf-vsv-footer">'.$this->nextPage().'</div>
	</div>';
		$returnstr .= $this->printImages("DAMMAGES",0,"4. BESCHADIGINGEN");
		return $returnstr;		
	}
	private function getConclusion(){	
		$returnstr = '
	<div class="pdf-vsv-page'.$this->__outdoor.'" style="page-break-before: always;">
		<div class="pdf-vsv-header'.$this->__outdoor.'">5. CONCLUSIE</div>
		<div class="pdf-vsv-main">';
		$returnstr .= '
			<h3>Gemeten norm</h3>
			<br />
			<div style="display: flex; flex-direction: column;">
			  <table class="stroefheid_table">
				<tbody>
				<tr style="background-color: #f6f6f6;">
				  <th style="padding: 5px;"><b>Meetmethode</b></th>
				  <th><b>Norm<b></th>
				</tr>';
		$conclusion_measurements = $this->__meetpunten; //array("A"=>"Stroefheid/Gladheid","B"=>"Oppervlaktehardheid","E"=>"Glanswaarden","D"=>"Schokabsorptie","C"=>"Verticale deformatie");
		if ($this->__stype==1) $conclusion_measurements = $this->__meetpuntenO;
		foreach($conclusion_measurements as $m=>$title){
			if(strpos($this->__rapport['measurements'],$m)!==false && $m!='F' && $m!='G' && $m!='OI'){
				$color = "#4bbf6b";
				if (isset($this->__measurements[$m.'_NORM_0']) && ($this->__measurements[$m.'_NORM_0'] == 'onvoldoende' || $this->__measurements[$m.'_NORM_0'] == 'onveilig'))
					$color = "#eb5a46";
				if (isset($this->__measurements[$m.'_NORM_0']) && ($this->__measurements[$m.'_NORM_0'] == 'voorwaardelijkveilig' || $this->__measurements[$m.'_NORM_0'] == 'aandacht nodig'))
					$color = "#FF9F1A";
				$returnstr .= '
				<tr>
				  <td>'.$title.'</td>
				  <td style="background-color: '.$color.';">'.(isset($this->__measurements[$m.'_NORM_0']) ? ucfirst($this->__measurements[$m.'_NORM_0']):'').'</td>
				</tr>';
			}
		}
		$returnstr .= '
				</tbody></table>
			</div><br />';
		if ($this->__rapport['conclusion'] != "")
			$returnstr .= '
			<div>
				'.str_replace("\n","<br />",$this->__rapport['conclusion']).'
			</div>
			<br />';
		$returnstr .= '
		</div>
		<div class="pdf-vsv-footer">'.$this->nextPage().'</div>
	</div>';
		return $returnstr;		
	}
	private function getAanbevelingen(){	
		$returnstr = '
	<div class="pdf-vsv-page'.$this->__outdoor.'" style="page-break-before: always;">
		<div class="pdf-vsv-header'.$this->__outdoor.'">6. AANBEVELINGEN</div>
		<div class="pdf-vsv-main">';
		$returnstr .= '';
		if ($this->__rapport['recommendations'] != "")
			$returnstr .= '
			<div>
				'.str_replace("\n","<br />",$this->__rapport['recommendations']).'
			</div>
			<br />';
		$returnstr .= '
			<div style="font-size:14pt; font-weight: 900; text-align: center;">
				Bestel uw middelen voor onderhoud direct in onze webshop<br />
				www.veiligesportvloer.com/nl/webshop
			</div>';
		$returnstr .= '
		</div>
		<div class="pdf-vsv-footer">'.$this->nextPage().'</div>
	</div>';
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
	private function getExtra($m){				
		$counter = 0;
		foreach($this->__images as $image){
			if ($image['code']=='EXT') $counter++;
		}
		if ($counter > 0){
			$returnstr = '
	<div class="pdf-vsv-page'.$this->__outdoor.'" style="page-break-before: always;">
		<div class="pdf-vsv-header'.$this->__outdoor.'">8. FOTO BIJLAGEN</div>
		<div class="pdf-vsv-main">';
			$returnstr .= $this->printImages("EXT", 0, "8. FOTO BIJLAGEN");
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