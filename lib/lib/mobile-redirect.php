<?php
session_start();
if(isset($_SESSION['redircheck']))return;
function startsWith($haystack, $needle){
	$length = strlen($needle);
	return (substr($haystack, 0, $length) === $needle);
}
$uagent=strtolower($_SERVER['HTTP_USER_AGENT']);
$short1=substr($uagent,1);
$short2=substr($uagent,2);
$short3=substr($uagent,3);
$match_f = false;
switch($uagent[0]){
	case '5':
		//505i
		$match_f=startsWith($short1,'05i');
	break;
	case '7':
		//770SH
		$match_f=startsWith($short1,'70sh');
	break;
	case '8':
		//802SH
		$match_f=startsWith($short1,'02sh');
	break;
	case 'a':
		switch($uagent[1]){
			case 'c':
				//AC831
				if($match_f=startsWith($short2,'831'))break;
				//ACS-NF
				if($match_f=startsWith($short2,'s-nf'))break;
			break;
			case 'h':
				//AHONG
				if($match_f=startsWith($short2,'ong'))break;
			break;
			case 'i':
				//AIKO
				if($match_f=startsWith($short2,'ko'))break;
				//Airis
				if($match_f=startsWith($short2,'ris'))break;
				//AIRNESS
				if($match_f=startsWith($short2,'rness'))break;
			break;
			case 'l':
				//Aladino
				if($match_f=startsWith($short2,'adino'))break;
				//Alcatel
				if($match_f=startsWith($short2,'catel'))break;
			break;
			case 'm':
				//Amoi
				$match_f=startsWith($short2,'oi');
			break;
			case 's':
				//ASUS
				$match_f=startsWith($short2,'us');
			break;
			case 't':
				//ATTWS
				$match_f=startsWith($short2,'tws');
			break;
			case 'u':
				//AU-MIC
				$match_f=startsWith($short2,'-mic')||
				//AUDIOVOX
				startsWith($short2,'diovox')||
				//audio180
				startsWith($short2,'dio180')||
				//audio89
				startsWith($short2,'dio89')||
				//audio99
				startsWith($short2,'dio99');
			break;
		}
	break;
	case 'b':
		switch($uagent[1]){
			case 'e':
				//BenQ
				$match_f=startsWith($short2,'nq')||
				//BELLWAVE
				startsWith($short2,'llwave');
			break;
			case 'l':
				//BlackBerry
				$match_f=startsWith($short2,'ackberry')||
				//Bleu
				startsWith($short2,'eu');
			break;
			case 'o':
				//Boost
				$match_f=startsWith($short2,'ost');
			break;
			case 'r':
				//BREW
				$match_f=startsWith($short2,'ew ')||
				//Broswer/WAP
				startsWith($short2,'owser/WAP');
			break;
		}
	break;
	case 'c':
		switch($uagent[1]){
			case 'a':
				$match_f=
				//Capitel
				startsWith($short2,'pitel')||
				//CASTOR
				startsWith($short2,'stor');
			break;
			case 'c':
				$match_f=
				//ccWAP
				startsWith($short2,'wap');
			break;
			case 'd':
				$match_f=
				//CDM
				startsWith($short2,'m');
			break;
			case 'e':
				$match_f=
				//CECT
				startsWith($short2,'ct ');
			break;
			case 'h':
				$match_f=
				//CHT
				startsWith($short2,'t ');
			break;
			case 'o':
				$match_f=
				//COCOON
				startsWith($short2,'coon')||
				//Compal-
				startsWith($short2,'mpal-')||
				//CORAL
				startsWith($short2,'ral ')||
				//CoralWeb
				startsWith($short2,'ralweb');
			break;
			default:
				$match_f=
				//Cricket-
				startsWith($short1,'ricket-')||
				//CT9A9G
				startsWith($short1,'t9a9g');
			break;
		}
	break;
	case 'd':
		switch($uagent[1]){
			case '7':
				$match_f=
				//D758
				startsWith($short2,'58');
			break;
			case 'a':
				$match_f=
				//DALLAB-
				startsWith($short2,'llab-');
			break;
			case 'b':
				$match_f=
				//DBTEL
				startsWith($short2,'tel');
			break;
			case 'i':
				$match_f=
				//DICAM-
				startsWith($short2,'cam-');
			break;
			case 'o':
				$match_f=
				//DoCoMo/
				startsWith($short2,'como/');
			break;
			case 'm':
				$match_f=
				//Dmobo
				startsWith($short2,'obo');
			break;
			case 'o':
				$match_f=
				//Dopod
				startsWith($short2,'pod')||
				//Doris/
				startsWith($short2,'ris/');
			break;
			case 'u':
				$match_f=
				//Duplii/
				startsWith($short2,'plii/');
			break;
		}
	break;
	case 'e':
		switch($uagent[1]){
			case '3':
				$match_f=
				//E300
				startsWith($short2,'00');
			break;
			case 'd':
				$match_f=
				//EDL
				startsWith($short2,'l ');
			break;
			case 'l':
				$match_f=
				//EL3
				startsWith($short2,'3')||
				//EL4
				startsWith($short2,'4')||
				//EL5
				startsWith($short2,'5')||
				//EL6
				startsWith($short2,'6');
			break;
			case 'm':
				$match_f=
				//eML2
				startsWith($short2,'l2')||
				//Emobile
				startsWith($short2,'obile')||
				//EMS100
				startsWith($short2,'s100');
			break;
			case 'r':
				$match_f=
				//Ericsson
				startsWith($short2,'icsson')||
				//ERICY
				startsWith($short2,'icy')||
				//ERKO
				startsWith($short2,'ko');
			break;
			case 's':
				$match_f=
				//ESL
				startsWith($short2,'l');
			break;
			case 'v':
				$match_f=
				//EVE/
				startsWith($short2,'e/');
			break;
			case 'z':
				$match_f=
				//EZ4
				startsWith($short2,'4')||
				//EZ5
				startsWith($short2,'5')||
				//EZ6
				startsWith($short2,'6')||
				//EZ7
				startsWith($short2,'7')||
				//EZIO
				startsWith($short2,'io')||
				//EZOS
				startsWith($short2,'os')||
				//EZZE
				startsWith($short2,'ze');
			break;
		}
	break;
	case 'g':
		switch($uagent[1]){
			case 'f':
				$match_f=
				//GF-500
				startsWith($short2,'-500')||
				//GF500
				startsWith($short2,'500');
			break;
			case 'i':
				$match_f=
				//GIGABYTE-g
				startsWith($short2,'gabyte-g')||
				//Gigaset
				startsWith($short2,'gaset ')||
				//GIONEE
				startsWith($short2,'onee');//-chan
			break;
			case 'p':
				$match_f=
				//GPFM
				startsWith($short2,'fm');
			break;
			case 'r':
				$match_f=
				//Gradiente
				startsWith($short2,'adiente')||
				//GRUNDIG
				startsWith($short2,'undig');
			break;
			case 't':
				$match_f=
				//Gtran
				startsWith($short2,'ran');
			break;
			case 'u':
				$match_f=
				//GU1100
				startsWith($short2,'1100');
			break;
		}
	break;
	case 'h':
		switch($uagent[1]){
			case '1':
				$match_f=
				//H100
				startsWith($short2,'00');
			break;
			case 'a':
				$match_f=
				//Haier-
				startsWith($short2,'ier-');
			break;
			case 'c':
				$match_f=
				//HCITX
				startsWith($short2,'itx');
			break;
			case 'e':
				$match_f=
				//HEDY
				startsWith($short2,'dy ')||
				//HEI-
				startsWith($short2,'i-');
			break;
			case 'g':
				$match_f=
				//HG7000
				startsWith($short2,'7000');
			break;
			case 'i':
				$match_f=
				//Hitachi
				startsWith($short2,'tachi');
			break;
			case 'p':
				$match_f=
				//HP iPAQ
				startsWith($short2,' ipaq')||
				//HPiPAQ
				startsWith($short2,'ipaq');
			break;
			case 's':
				$match_f=
				//HS-C
				startsWith($short2,'-c');
			break;
			case 't':
				$match_f=
				//HT-1
				startsWith($short2,'-1')||
				//HTC
				startsWith($short2,'c ')||
				//htc-cleo
				startsWith($short2,'c-cleo')||
				//HTIL
				startsWith($short2,'il');
			break;
			case 'u':
				$match_f=
				//HUAWEI
				startsWith($short2,'awei')&&!startsWith($short3,'weisymantec')||
				//Hutc3G/
				startsWith($short2,'tc3g/');
			break;
		}
	break;
	case 'i':
		switch($uagent[1]){
			case '-':
				$match_f=
				//I-GO
				startsWith($short2,'go')||
				//I-MATE
				startsWith($short2,'mate')||
				//i-mobile
				startsWith($short2,'mobile');
			break;
			case '2':
				$match_f=
				//I2300
				startsWith($short2,'300');
			break;
			case 'a':
				$match_f=
				//IAC-
				startsWith($short2,'c-')||
				//IAC/
				startsWith($short2,'c/');
			break;
			case 'c':
				$match_f=
				//Ice
				startsWith($short2,'e');
			break;
			case 'd':
				$match_f=
				//iDEA
				startsWith($short2,'ea');
			break;
			case 'k':
				$match_f=
				//iKoMo
				startsWith($short2,'omo');
			break;
			case 'm':
				$match_f=
				//IM-T880
				startsWith($short2,'-t880');
			break;
			case 'n':
				$match_f=
				//Indie/
				startsWith($short2,'die/')||
				//INNO
				startsWith($short2,'no')||
				//INQ/
				startsWith($short2,'q/')||
				//INQ1/
				startsWith($short2,'q1/');
			break;
			case 't':
				$match_f=
				//ITELCO
				startsWith($short2,'elco');
			break;
			case 'x':
				$match_f=
				//IXI-CT
				startsWith($short2,'i-ct');
			break;
		}
	break;
	case 'j':
		$match_f=
		//jBrowser/
		startsWith($short2,'browser/');
	break;
	case 'k':
		switch($uagent[1]){
			case '6':
				$match_f=
				//K610i
				startsWith($short2,'10i');
			break;
			case 'a':
				$match_f=
				//Kaiser
				startsWith($short2,'iser ');
			break;
			case 'd':
				$match_f=
				//KDDI-
				startsWith($short2,'di-');
			break;
			case 'e':
				$match_f=
				//kejian
				startsWith($short2,'jian');
			break;
			case 'g':
				$match_f=
				//KGT/
				startsWith($short2,'t/');
			break;
			case 'i':
				$match_f=
				//Kingcom
				startsWith($short2,'ngcom ');
			break;
			case 'l':
				$match_f=
				//Klondike/
				startsWith($short2,'ondike/');
			break;
			case 'o':
				$match_f=
				//KONKA
				startsWith($short2,'nka ')||
				//KOZI-
				startsWith($short2,'zi-');
			break;
			case 'p':
				$match_f=
				//KPT
				startsWith($short2,'t');
			break;
			case 'w':
				$match_f=
				//KWC
				startsWith($short2,'c');
			break;
			case 'y':
				if($uagent[2]=='o'){
					$match_f=
					//kyocera
					startsWith($short3,'cera')||
					//kyoe
					startsWith($short3,'e')||
					//kyokx2
					startsWith($short3,'kx2');
				}
			break;
		}
	break;
	case 'l':
		if($uagent[1]!='g'){
			$match_f=
			//L6668/
			startsWith($short1,'6668/')||
			//LCT
			startsWith($short1,'ct ')||
			//LENOVO
			startsWith($short1,'enovo')||
			//Levis-
			startsWith($short1,'evis-')||
			//LEXIBOOK
			startsWith($short1,'exibook')||
			//LONGCOS
			startsWith($short1,'ongcos')||
			//LT/
			startsWith($short1,'t/');
		}else{
			$match_f=
			//LG
			startsWith($short2,' ')||
			//LG-
			startsWith($short2,'-')||
			//LG/
			startsWith($short2,'/')||
			//LG1
			startsWith($short2,'1')||
			//LG2
			startsWith($short2,'2')||
			//LG3
			startsWith($short2,'3')||
			//LG4
			startsWith($short2,'4')||
			//LG5
			startsWith($short2,'5')||
			//LG7
			startsWith($short2,'7')||
			//LG8
			startsWith($short2,'8')||
			//LG9
			startsWith($short2,'9')||
			//LGE-
			startsWith($short2,'e-');
		}
	break;
	case 'm':
		switch($uagent[1]){
			case '-':
				$match_f=
				//M-Three
				startsWith($short2,'three');
			break;
			case '1':
				$match_f=
				//M1000
				startsWith($short2,'000');
			break;
			case '3':
				$match_f=
				//M3Gate
				startsWith($short2,'gate');
			break;
			case '9':
				$match_f=
				//M901
				startsWith($short2,'901');
			break;
			case 'a':
				$match_f=
				//Majestic1/
				startsWith($short2,'jestic1/')||
				//malata
				startsWith($short2,'lata')||
				//MAUI
				startsWith($short2,'ui')||
				//Maxcon
				startsWith($short2,'xcon');
			break;
			case 'c':
				$match_f=
				//MC01
				startsWith($short2,'01')||
				//MC218
				startsWith($short2,'218')||
				//MCCA
				startsWith($short2,'ca');
			break;
			case 'd':
				$match_f=
				//MD-MD2
				startsWith($short2,'-md2')||
				//MD301H
				startsWith($short2,'301h');
			break;
			case 'e':
				$match_f=
				//ME70
				startsWith($short2,'70')||
				//MEDION
				startsWith($short2,'dion')||
				//MERIDIAN
				startsWith($short2,'ridian');
			break;
			case 'i':
				$match_f=
				//MicroMax
				startsWith($short2,'cromax')||
				//Mio
				startsWith($short2,'o ')||
				//MIO8
				startsWith($short2,'o8')||
				//MIOA
				startsWith($short2,'oa')||
				//MiTAC
				startsWith($short2,'tac')||
				//Mitsu
				startsWith($short2,'tsu ')||
				//Mitsu-
				startsWith($short2,'tsu-')||
				//Mitsu/
				startsWith($short2,'tsu/');
			break;
			case 'k':
				$match_f=
				//MK99
				startsWith($short2,'99');
			break;
			case 'l':
				$match_f=
				//ML-MW
				startsWith($short2,'-mw');
			break;
			case 'o':
				if($uagent[2]!='t'){//mo
					$match_f=
					//MO01
					startsWith($short2,'01')||
					//MobileExplorer
					startsWith($short2,'bileexplorer')||
					//MODOTTEL
					startsWith($short2,'dottel')||
					//Modu1
					startsWith($short2,'du1')||
					//morangeV
					startsWith($short2,'rangev');
				}elseif($uagent[3]!='o'){//mot
					$match_f=
					//MOT-
					startsWith($short3,'-')||
					//motv9m
					startsWith($short3,'v9m');
				}else{//moto
					$short=substr($uagent,4);
					$match_f=
					//MOTO-
					startsWith($short,'-')||
					//motoa
					startsWith($short,'a')||
					//motok
					startsWith($short,'k')||
					//motol
					startsWith($short,'l')||
					//motoq
					startsWith($short,'q')||
					//MOTORAZR
					startsWith($short,'razr')||
					//MOTORIZR
					startsWith($short,'rizr')||
					//Motorola
					startsWith($short,'rola')||
					//motov
					startsWith($short,'v')||
					//motow
					startsWith($short,'w');
				}
			break;
			case 't':
				$match_f=
				//MTK
				startsWith($short2,'k')||
				//MTV 3.0
				startsWith($short2,'v 3.0');
			break;
			case 'y':
				$match_f=
				//MyPhone
				startsWith($short2,'phone')||
				//myWAY
				startsWith($short2,'way');
			break;
		}
	break;
	case 'n':
		switch($uagent[1]){
			case '1':
			case '2':
			case '3':
			case '4':
			case '5':
			case '6':
				$match_f=
				//N10
				//N20
				//N30
				//N50
				//N60
				startsWith($short2,'0');
			case '7':
				$match_f=
				//N70
				startsWith($short2,'0')||
				//N71
				startsWith($short2,'1')||
				//N73
				startsWith($short2,'3');
			break;
			case 'e':
				$match_f=
				//NEC-
				startsWith($short2,'c-')||
				//Neonode-N1
				startsWith($short2,'onode-n1')||
				//NetFront
				startsWith($short2,'tfront')||
				//NEWGEN-
				startsWith($short2,'wgen-')||
				//NEX800
				startsWith($short2,'x800')||
				//NEXIAN
				startsWith($short2,'xian');
			break;
			case 'g':
				$match_f=
				//NGB/3
				startsWith($short2,'b/3');
			break;
			case 'j':
				$match_f=
				//Njoy
				startsWith($short2,'oy');
			break;
			case 'o':
				$match_f=
				//nok6
				startsWith($short2,'k6')||
				//Nokia
				startsWith($short2,'kia')||
				//Novarra/
				startsWith($short2,'varra/');
			break;
		}
	break;
	case 'o':
		$match_f=
		//O2 X
		startsWith($short1,'2 x')||
		//O2-X
		startsWith($short1,'2-x')||
		//o2imode
		startsWith($short1,'2imode')||
		//O2Jet
		startsWith($short1,'2jet')||
		//Optimay-Seville
		startsWith($short1,'ptimay-seville');
	break;
	case 'p':
		$match_f=
		//pantech
		startsWith($short1,'antech')||
		//pg-
		startsWith($short1,'g-')||
		//phoneone
		startsWith($short1,'honeone')||
		//pirelli
		startsWith($short1,'irelli')||
		//PN-
		startsWith($short1,'n-')||
		//polaris
		startsWith($short1,'olaris')||
		//portalmmm
		startsWith($short1,'ortalmm')||
		//PPC;
		startsWith($short1,'pc;')||
		//PT-
		startsWith($short1,'t-');
	break;
	case 'q':
		$match_f=
		//QC-
		startsWith($short1,'c-')||
		//QCI-
		startsWith($short1,'ci-')||
		//Qtec
		startsWith($short1,'tec');
	break;
	case 's':
		if($uagent[1]=='a'){
			$match_f=
			//sagem
			startsWith($short2,'gem')||
			//sam-
			startsWith($short2,'m-')||
			//sama
			startsWith($short2,'ma')||
			//samsung
			startsWith($short2,'msung')||
			//samu
			startsWith($short2,'mu')||
			//sanyo
			startsWith($short2,'nyo');
		}else{
			$match_f=
			//SCH-
			startsWith($short1,'ch-')||
			//SEC-
			startsWith($short1,'ec-')||
			//SEMC-
			startsWith($short1,'emc-')||
			//sendo
			startsWith($short1,'endo')||
			//SGH-
			startsWith($short1,'gh-')||
			//SIE
			startsWith($short1,'ie')||
			//Sony
			startsWith($short1,'ony')||
			//SPH-
			startsWith($short1,'ph-')||
			//spice-
			startsWith($short1,'pice-')||
			//SPV
			startsWith($short1,'pv')||
			//sunrise
			startsWith($short1,'unrise');
		}
	break;
	case 't':
		$match_f=
		//T250
		startsWith($short1,'250')||
		//T600
		startsWith($short1,'600')||
		//T618
		startsWith($short1,'618')||
		//T66
		startsWith($short1,'66')||
		//talkabout
		startsWith($short1,'alkabout')||
		//TCL-
		startsWith($short1,'cl-')||
		//TDG-
		startsWith($short1,'dg-')||
		//Teleca-
		startsWith($short1,'eleca-')||
		//telit-
		startsWith($short1,'elit-')||
		//telme
		startsWith($short1,'elme')||
		//tianyu
		startsWith($short1,'ianyu')||
		//TIM
		startsWith($short1,'im')||
		//toplux
		startsWith($short1,'oplux')||
		//Treo
		startsWith($short1,'reo')||
		//TSM-
		startsWith($short1,'sm-');
	break;
	case 'u':
		$match_f=
		//U Mobile
		startsWith($short1,' mobile')||
		//ubiquam
		startsWith($short1,'biquam')||
		//ucweb
		startsWith($short1,'cweb')||
		//uniscope
		startsWith($short1,'niscope')||
		//unistar
		startsWith($short1,'nistar')||
		//UP.Browser
		startsWith($short1,'p.browser')||
		//uriver
		startsWith($short1,'river')||
		//usha
		startsWith($short1,'sha')||
		//utec
		startsWith($short1,'tec')||
		//uts-
		startsWith($short1,'ts-')||
		//ustar
		startsWith($short1,'star');
	break;
	case 'v':
		if($uagent[1]!='x'){
			$match_f=
			//vacom
			startsWith($short1,'acom')||
			//venera
			startsWith($short1,'enera')||
			//vertu
			startsWith($short1,'ertu')||
			//verykool
			startsWith($short1,'erykool')||
			//VK-VK
			startsWith($short1,'k-vk')||
			//VK4
			startsWith($short1,'k4')||
			//VK5
			startsWith($short1,'k5')||
			//VM4
			startsWith($short1,'m4')||
			//vodafone
			startsWith($short1,'odafone');
		}else{
			$match_f=
			//vx1
			startsWith($short2,'1')||
			//vx4
			startsWith($short2,'4')||
			//vx5
			startsWith($short2,'5')||
			//vx6
			startsWith($short2,'6')||
			//vx7
			startsWith($short2,'7')||
			//vx8
			startsWith($short2,'8')||
			//vx9
			startsWith($short2,'9');
		}
	break;
	case 'w':
		$match_f=
		//w3c && !w3c_validator
		startsWith($short1,'3c')&&!startsWith($short3,'c_va')||
		//wapsilon
		startsWith($short1,'apsilon')||
		//wapuniverse
		startsWith($short1,'apuniverse')||
		//wellcom
		startsWith($short1,'ellcom')||
		//winwap
		startsWith($short1,'inwap')||
		//wonu
		startsWith($short1,'onu');
	break;
	case 'x':
		$match_f=
		//xda
		startsWith($short1,'da');
	break;
	case 'y':
		$match_f=
		//yas-cosmos
		startsWith($short1,'as-cosmos')||
		//yogio
		startsWith($short1,'ogio')||
		//yulong
		startsWith($short1,'ulong');
	break;
	case 'z':
		$match_f=
		//zmem
		startsWith($short1,'mem')||
		//zte
		startsWith($short1,'te');
	break;
}
if(!$match_f){
	foreach( array(
		'android',
		'iphone',
//		'ipad',
		'blackberry',
		'windows ce',
		'ipod',
		'opera mini',
		'symbian os',
		'symbianos',
		'palmos',
		'samsung',
		'sony cmd',
		'sprint ',
		'teleca',
		'nokia',
		'kindle',
		'palmsource',
		'hiptop',
		'iemobile',
		'handset',
		'smartphone',
		'cellphone',
		' pda',
		'ixi/q',
		'brew 3.1.5',
		'i-mate ja',
		'up.browser',
		'avantgo',
		'wap1',
		'wap2',
		'wap browser',
		'acs-netfront',
		'au.browser',
		'blazer/',
		';foma',
		' htc/',
		'vodaphone',
		'mobilephone',
		'willcom',
		'kyocera',
		'mowser')
	as $test){
		if($match_f=(strpos($uagent,$test)!==false))break;
	}
}
if($match_f){
	$fp=fopen('matched_agents.log','a');
	fwrite($fp,"\n$uagent");
	fclose($fp);
	header('Location: http://m.example.com/');
}
$_SESSION['redircheck']=true;