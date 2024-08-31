<?php
/**
 * @file DisplayPaginatedText.php
 * @author Jean-arthur SILVE <contact@minipavi.fr>
 * @version 1.1 Août 2024
 *
 * Affichage d'un texte contenu dans un fichier sur plusieurs pages
 * avec gestion des touche Suite, Retour et Répétition
 * Pour utilisation avec MiniPavi
 *
 * License GPL v2 ou supérieure
 *
 *
 *
 * Exemple d'un fichier (extrait) qui pourra être affiché en multi-page:
 * - La premère ligne représente le titre
 * - les lignes commençant par # seront d'une couleur différente
 
Les autres...
Suite à l'annulation de sa licence le 2 février 1987, un nouvel appel d'offres est effectué afin d'occupper le 6ème réseau de télévision.

Suite à cet appel, plusieurs projets sont alors en lice, dont voici les présentations telles qu'indiquées dans le Télé 7 Jours du 28/02/1987.

Le projet retenu, pour des raisons politiques, les 25 et 26 février 1987 sera celui de Metrole Télévision, c'est à dire M6.


#CANAL PLUS JUNIOR

Les enfants regardent en moyenne par jour 2h10 la télévision et pourtant sur les chaînes publiques aucun programme ne leur est destiné.

"Car, en-dessous de 13 ans, les enfants n’intéressent pas les annonceurs", dit François Garçon, responsable du projet Canal Plus Junior, élaboré par Canal Plus avec plusieurs partenaires dont Larousse, Nathan, les jouets Smoby.

...
**/

class DisplayPaginatedText {
		private $vdtStart;			// Videotex à afficher avant tout (masque), qui ne sera affiché qu'une fois au départ
		private $vdtClearPage;		// Videotex popur effacer le contenu d'une page sans effacer le masque, affiché à chaque chargement de page
		private $textFilename;		// Fichier ou se trouve le texte
		private $lTitle;			// Numéro de ligne pour positionner le titre (-24)
		private $cTitle;			// Numéro de colonne pour positionner le titre (1-40)
		private $vdtPreTitle;		// Videotex éventuel à afficher avant le titre
		private $lCounter;			// Numéro de ligne pour positionner le compteur de page
		private $cCounter;			// Numéro de colonne pour positionner le compteur de page
		private $vdtPreCounter;		// Videotex éventuel à afficher avant le compteur de page
		
		private $lText;				// Numéro de ligne pour positionner le début du texte 
		private $cText;				// Numéro de colonne pour positionner le début du texte
		private $maxLengthText;		// Longueur max d'une ligne de texte
		private $normalColor;		// Couleur texte normale (code videotex)
		private $specialColor;		// Couleur texte si la ligne commence par un "#" (code videotex)
		
		private $vdtPreText;		// Videotex éventuel à afficher avant chaque début de ligne
		
		private $vdtNone;			// Videotex à afficher indiquant les choix possible si 1ère et unique page (positionnement inclus)
		private $vdtSuite;			// Videotex à afficher indiquant les choix possible si 1ère page (positionnement inclus)
		private $vdtRetour;			// Videotex à afficher indiquant les choix possible si dernière page (positionnement inclus)
		private $vdtSuiteRetour; 	// Videotex à afficher indiquant les choix possible si page intermédiaire (positionnement inclus)
		
		private $vdtErrNoPrev;		// Videotex à afficher en ligne 0 indiquant qu'il n y a pas de page précédente
		private $vdtErrNoNext;		// Videotex à afficher en ligne 0 indiquant qu'il n y a pas de page suivante
		
		private $lines;				// Nombre de lignes par page

		private $step;				// Usage interne : Etape (initialisation, etc)
		private $displayedPage;		// Usage interne : Page à afficher


	/*************************************************
	// Gère l'affichage multi-page.
	*************************************************/
	
	function __construct($vdtStart,$vdtClearPage,$textFilename,$lTitle,$cTitle,$vdtPreTitle,$lCounter,$cCounter,$vdtPreCounter,$lText,$cText,$maxLengthText,$normalColor,$specialColor,$vdtPreText,$vdtNone,$vdtSuite,$vdtRetour,$vdtSuiteRetour,$vdtErrNoPrev,$vdtErrNoNext,$lines) {
		$this->vdtStart = $vdtStart;			
		$this->vdtClearPage= $vdtClearPage;		
		$this->textFilename=$textFilename;		
		$this->lTitle=$lTitle;					
		$this->cTitle=$cTitle;					
		$this->vdtPreTitle = $vdtPreTitle;		
		$this->lCounter=$lCounter;				
		$this->cCounter=$cCounter;				
		$this->vdtPreCounter = $vdtPreCounter;	
		
		$this->lText = $lText;					
		$this->cText = $cText;					
		$this->maxLengthText = $maxLengthText;
		$this->normalColor = $normalColor;					
		$this->specialColor = $specialColor;					
		$this->vdtPreText = $vdtPreText;		

		
		$this->vdtNone = $vdtNone;			
		$this->vdtSuite = $vdtSuite;			
		$this->vdtRetour = $vdtRetour;			
		$this->vdtSuiteRetour = $vdtSuiteRetour; 
		
		$this->vdtErrNoPrev = $vdtErrNoPrev;
		$this->vdtErrNoNext = $vdtErrNoNext;
		
		$this->lines = $lines;					
		
		$this->step = 0;					
		$this->displayedPage = 0;					
	}
	
	/*************************************************
	// Gère l'affichage multi-page.
	// Méthode devant être appellée à chaque action de l'utilisateur
	// (entre chaque action ,l'objet pourra être sauvegardé dans le contexte utilisateur et ainsi récupéré à l'action suivante)
	// Retourne false si la touche de fonction indiquée est autre que Suite, Retour, Répétition ou vide, sinon true
	// fctn : la touche de fonction saisie par l'utilisateur. Vide si premier appel.
	// vdt: code videotex à renvoyer à l'utilisateur
	**************************************************/
	function process($fctn,&$vdt) {
		if ($this->step == 0) {
			// Affiche le masque
			$vdt= "\x14".$this->vdtStart;
			$this->step = 1;
		} else {
			if ($fctn!='' && $fctn != 'SUITE' && $fctn != 'RETOUR' && $fctn != 'REPETITION')
				return false;
			else $vdt= "\x14";
		}
		
		
		$tLines=$this->getArticle($this->textFilename,$this->maxLengthText,$title);	
		$numPages = ceil(count($tLines)/$this->lines);
		$cLines = count($tLines);
		
		switch ($fctn) {
			case 'SUITE':
				if ($this->displayedPage+1<$numPages) {
					$this->displayedPage++;		
					$vdt.= $this->vdtClearPage;
					break;
				} else { 
					$vdt.= "\x1F@A".$this->vdtErrNoNext."\n";
					return true;
				}
			case 'RETOUR':
				if ($this->displayedPage>0) {
					$this->displayedPage--;
					$vdt.= $this->vdtClearPage;
					break;
				} else {
					$vdt.= "\x1F@A".$this->vdtErrNoPrev."\n";
					return true;
				}
			case 'REPETITION':
				$vdt.= $this->vdtClearPage;
				break;
			case '':
				$vdt.= "\x1F".chr(64+$this->lTitle).chr(64+$this->cTitle).$this->vdtPreTitle.$this->toG2($title);
		}
		

		$start = $this->displayedPage*$this->lines;
		$stop = $start + $this->lines;
		$color = $this->normalColor;
		for ($j=0,$i=$start;$i<$stop;$i++,$j++) {
			if ($i>=$cLines)
				break;
				
			$vdt.="\x1F".chr(64+$this->lText+$j).chr(64+$this->cText);
			if (substr($tLines[$i],0,1)=='#') {	// si la ligne commence par "#" on ecrit en couleur spéciale
				$line = substr($tLines[$i],1);
				$color = $this->specialColor;
			} else if ($tLines[$i]=='') {
				$line='';
				$color = $this->normalColor;
			} else 
				$line = $tLines[$i];
			$vdt.=$color.$this->vdtPreText.$this->toG2($line);
		}
		
		
		$vdt.= "\x1F".chr(64+$this->lCounter).chr(64+$this->cCounter).$this->vdtPreCounter;
		$vdt.= ($this->displayedPage+1).'/'.$numPages;
		
		$vdt.= "\x1F".chr(64+$this->lCounter).chr(64+$this->cCounter).$this->vdtPreCounter;
		
		
		if ($this->displayedPage+1<$numPages && $this->displayedPage>0) 
			$vdt.=$this->vdtSuiteRetour;
		else if ($this->displayedPage>0) 
			$vdt.=$this->vdtRetour;
		else if ($numPages>1)
			$vdt.=$this->vdtSuite;
		else 
			$vdt.=$this->vdtNone;
		return true;
	}


	private function getArticle($filename,$lineLength,&$title) {
		$txt = file($filename);
		$tTxt = array();
		$title='';
		foreach($txt as $k=>$line) {
			$line = wordwrap(trim($line),$lineLength,"\n");
			$tLines=explode("\n",$line);
			$tTxt = array_merge($tTxt,$tLines);
		}
		$title = $tTxt[0];
		array_shift($tTxt);
		return $tTxt;
	}


	private function toG2($str) {
		
		$str=preg_replace('/[\x00-\x1F\x81\x8D\x8F\x90\x9D]/', ' ', $str);
		
		$tabAcc=array('é','è','à','ç','ê','É','È','À','Ç','Ê',
		'β','ß','œ','Œ','ü','û','ú','ù','ö','ô','ó','ò','ï','î','í','ì','ë','ä',
		'â','á','£','°','±','←','↑','→','↓','¼','½','¾','Â','Î','ō','á','’',' ','ň','ć','ř','ý','š','í','ą');
		
		$tabG2=array("\x19"."\x42e",
		"\x19"."\x41e",
		"\x19"."\x41a",
		"\x19"."\x4B\x63",
		"\x19"."\x43e",
		"\x19"."\x42E",
		"\x19"."\x41E",
		"\x19"."\x41A",
		"\x19"."\x4B\x63",
		"\x19"."\x43E",
		"\x19"."\x7B",		
		"\x19"."\x7B",		
		"\x19"."\x7A",		
		"\x19"."\x6A",		
		"\x19"."\x48\x75",		
		"\x19"."\x43\x75",		
		"\x19"."\x42\x75",		
		"\x19"."\x41\x75",		
		"\x19"."\x48\x6F",		
		"\x19"."\x43\x6F",		
		"\x19"."\x42\x6F",		
		"\x19"."\x41\x6F",		
		"\x19"."\x48\x69",		
		"\x19"."\x43\x69",		
		"\x19"."\x42\x69",		
		"\x19"."\x41\x69",		
		"\x19"."\x48\x65",		
		"\x19"."\x48\x61",		
		"\x19"."\x43\x61",		
		"\x19"."\x42\x61",
		"\x19"."\x23",		
		"\x19"."\x30",		
		"\x19"."\x31",		
		"\x19"."\x2C",		
		"\x19"."\x2D",		
		"\x19"."\x2E",		
		"\x19"."\x2F",		
		"\x19"."\x3C",		
		"\x19"."\x3D",		
		"\x19"."\x3E",
		"\x19"."\x43A",
		'I','o','a',"'",' ','n','c','r','y','s','i','a'
		);
		
		foreach($tabAcc as $k=>$c) {
			$str=mb_ereg_replace($c,$tabG2[$k], $str);
		}
		return $str;
	}
}
?>