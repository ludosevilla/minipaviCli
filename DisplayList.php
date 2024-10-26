<?php
/**
 * @file DisplayList.php
 * @author Jean-arthur SILVE <contact@minipavi.fr>
 * @version 1.1 Août 2024
 *
 * Affichage d'une liste d'élements sur plusieurs pages
 * avec gestion des touche Suite, Retour, Envoi et Répétition
 * Pour utilisation avec MiniPavi
  *
 * License GPL v2 ou supérieure
 *
 *
 **/

class DisplayList {
		private $vdtStart;			// Videotex à afficher avant tout (masque), qui ne sera affiché qu'une fois au départ
		private $vdtClearPage;		// Videotex popur effacer le contenu d'une page sans effacer le masque, affiché à chaque chargement de page
		private $list;				// Tableau des elements à afficher (indexes numériques)
		private $lCounter;			// Numéro de ligne pour positionner le compteur de page
		private $cCounter;			// Numéro de colonne pour positionner le compteur de page
		private $vdtPreCounter;		// Videotex éventuel à afficher avant le compteur de page
		private $vdtItemNum;		// Videotex pour afficher le numéro d'un choix. Le caractère "#" sera remplacdé par le numéro du choix.
		
		private $lText;				// Numéro de ligne pour positionner le début de la liste
		private $cText;				// Numéro de colonne pour positionner le début de la liste
		
		private $vdtPreText;		// Videotex éventuel à afficher avant chaque début de ligne
		
		private $vdtNone;			// Videotex à afficher indiquant les choix possible si 1ère et unique page (positionnement inclus)
		private $vdtSuite;			// Videotex à afficher indiquant les choix possible si 1ère page (positionnement inclus)
		private $vdtRetour;			// Videotex à afficher indiquant les choix possible si dernière page (positionnement inclus)
		private $vdtSuiteRetour; 	// Videotex à afficher indiquant les choix possible si page intermédiaire (positionnement inclus)

		private $vdtErrNoPrev;		// Videotex à afficher en ligne 0 indiquant qu'il n y a pas de page précédente
		private $vdtErrNoNext;		// Videotex à afficher en ligne 0 indiquant qu'il n y a pas de page suivante
		private $vdtErrChoice;		// Videotex à afficher en ligne 0 indiquant que le choix saisi est incorrect
		
		private $lines;				// Nombre de lignes par page
		private $spaceLines;		// Nombre de lignes vide entre deux choix
		private $linesPerItem;		// Nombre de ligne par elements
		private $step;				// Usage interne : Etape (initialisation, etc)
		private $displayedPage;		// Usage interne : Page à afficher


	/*************************************************
	// Gère l'affichage multi-page.
	*************************************************/
	
	function __construct($vdtStart,$vdtClearPage,$list,$lCounter,$cCounter,$vdtPreCounter,$vdtItemNum,$lText,$cText,$vdtPreText,$vdtNone,$vdtSuite,$vdtRetour,$vdtSuiteRetour,$vdtErrNoPrev,$vdtErrNoNext,$vdtErrChoice,$lines,$spaceLines,$linesPerItem=1) {
		$this->vdtStart = $vdtStart;			
		$this->vdtClearPage= $vdtClearPage;		
		$this->list=$list;		
		$this->lCounter=$lCounter;				
		$this->cCounter=$cCounter;				
		$this->vdtPreCounter = $vdtPreCounter;	
		
		$this->vdtItemNum = $vdtItemNum;
		$this->lText = $lText;					
		$this->cText = $cText;					
		$this->vdtPreText = $vdtPreText;		

		
		$this->vdtNone = $vdtNone;			
		$this->vdtSuite = $vdtSuite;			
		$this->vdtRetour = $vdtRetour;			
		$this->vdtSuiteRetour = $vdtSuiteRetour; 
		
		$this->vdtErrNoPrev = $vdtErrNoPrev;
		$this->vdtErrNoNext = $vdtErrNoNext;
		$this->vdtErrChoice = $vdtErrChoice;
		
		$this->lines = $lines;	
		$this->linesPerItem	 = $linesPerItem;
		$this->spaceLines = $spaceLines;				
		
		$this->step = 0;					
		$this->displayedPage = 0;					
	}
	
	/*************************************************
	// Gère l'affichage multi-page.
	// Méthode devant être appellée à chaque action de l'utilisateur
	// (entre chaque action ,l'objet pourra être sauvegardé dans le contexte utilisateur et ainsi récupéré à l'action suivante)
	// Retourne false si la touche de fonction indiquée est autre que Suite, Retour, Envoi, Répétition ou vide, sinon -1 ou l'index du tableau correspondant à la saisie
	// fctn : la touche de fonction saisie par l'utilisateur. Vide si premier appel.
	// choice : saisie de l'utilisateur
	// vdt: code videotex à renvoyer à l'utilisateur
	**************************************************/
	function process($fctn,$choice,&$vdt) {
	
		$cItems = count($this->list);
		
		if ($this->step == 0 || $fctn=='') {
			// Affiche le masque
			$vdt= "\x14".$this->vdtStart;
			$this->step = 1;
		} else {
			$vdt= "\x14";
			if ($fctn!='' && $fctn != 'SUITE' && $fctn != 'RETOUR' && $fctn != 'REPETITION') {
				$choice = (int)$choice;
				if ($choice <=0 || $choice > $cItems) {
					// Choix incorrect
					$vdt.= "\x1F@A".$this->vdtErrChoice."\n";
					return -1;
				}
				// Le choix est correct
				return ($choice-1);
			}
		}

		$vdt.= "\x1F@A\x18\n";
		$numPages = ceil($cItems/$this->lines);
		
		switch ($fctn) {
			case 'SUITE':
				if ($this->displayedPage+1<$numPages) {
					$this->displayedPage++;		
					$vdt.= $this->vdtClearPage;
					break;
				} else { 
					$vdt.= "\x1F@A".$this->vdtErrNoNext."\x18\n";
					return -1;
				}
			case 'RETOUR':
				if ($this->displayedPage>0) {
					$this->displayedPage--;
					$vdt.= $this->vdtClearPage;
					break;
				} else {
					$vdt.= "\x1F@A".$this->vdtErrNoPrev."\x18\n";
					return -1;
				}
			case 'REPETITION':
				$vdt.= $this->vdtClearPage;
				break;
		}
		
		$lengthMaxItem = '%'.strlen((string)$cItems).'d';
		
		$lItemNum = strlen((string)$cItems);
		
		$start = $this->displayedPage*$this->lines;
		$stop = $start + $this->lines;
		for ($j=0,$i=$start,$offset=0;$i<$stop;$i++,$j++,$offset+=$this->spaceLines+($this->linesPerItem-1)) {
			if ($i>=$cItems)
				break;
			
			
			
			$itemNum = preg_replace("/#/", sprintf($lengthMaxItem,($i+1)) ,$this->vdtItemNum);
			
		
			if ($this->linesPerItem <2)
				$vdt.="\x1F".chr(64+$this->lText+$j+$offset).chr(64+$this->cText).$itemNum.' '.$this->vdtPreText.$this->toG2($this->list[$i]);
			else {
				$vdt.="\x1F".chr(64+$this->lText+$j+$offset).chr(64+$this->cText).$itemNum.' '.$this->vdtPreText.$this->toG2($this->list[$i][0]);
				
				for ($ln=1;$ln<$this->linesPerItem;$ln++) {
					$vdt.="\x1F".chr(64+$this->lText+$j+$ln+$offset).chr(64+$this->cText+$lItemNum+1).$this->vdtPreText.$this->toG2($this->list[$i][$ln]);
				}
			}
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
		return -1;
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