<?php
/**
 * @file XMLfunctions.php
 * @author Jean-arthur SILVE <contact@minipavi.fr>
 * @version 1.0 Février 2024
 *
 * Fonctions utlisées dans le script XMLint
 * 
 */

function display_xml_error($error, $xml) {
    $return  = substr(trim($xml[$error->line - 1]),0,120) .VDT_CRLF;
    switch ($error->level) {
        case LIBXML_ERR_WARNING:
            $return .= "Warning $error->code: ";
            break;
         case LIBXML_ERR_ERROR:
            $return .= "Error $error->code: ";
            break;
        case LIBXML_ERR_FATAL:
            $return .= "Fatal Error $error->code: ";
            break;
    }

    $return .= trim($error->message) .
               "  Line: $error->line" .
               "  Column: $error->column";

    if ($error->file) {
        $return .= VDT_CRLF."File: $error->file";
    }
    return $return;
}

function getPage($objXML,$nom) {
	foreach ($objXML as $elementName=>$obj) {
		if ($elementName == 'page' && @$obj['nom'] == $nom) {
			return $obj;
		}
	}
	return false;
}

function getPageElement($objXML,$elementName) {
	foreach ($objXML as $currElementName=>$obj) {
		if ($currElementName == $elementName) {
			return $obj;
		}
	}
	return false;
}


function processAfficheElement($obj,&$err) {
	$vdt = '';
	$err = '';

	$url=filter_var(@$obj['url'], FILTER_VALIDATE_URL);
	if (!$url) {
		$err = "URL ".@$obj['url']." incorrecte";
		return false;
	}
	$ch = curl_init();
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
	curl_setopt($ch, CURLOPT_BINARYTRANSFER, 1);
	curl_setopt($ch, CURLOPT_URL,$url);
	$r= curl_exec($ch);
	if ($r === false) {
		$err = "URL ".@$obj['url']." incorrecte";
		return false;
	}
	$vdt=$r;
	curl_close($ch);
	return $vdt;
}


function processPositionElement($obj,&$err) {
	$vdt = '';
	$err = '';

	if (!isset($obj['ligne']) || !isset($obj['col'])) {
		$err = "Ligne/col manquant";
		return false;
	}
	$l = (int)@$obj['ligne'];
	$c = (int)@$obj['col'];
	if ($l<0 || $l>24 || $c<1 ||$c>40) {
		$err = "Ligne/col valeur incorrecte";
		return false;
	}
	$vdt=MiniPavi\MiniPaviCli::setPos($c,$l);
	return $vdt;
}


function processRepeteElement($obj,&$err) {
	$vdt = '';
	$err = '';

	if (!isset($obj['caractere']) || !isset($obj['nombre'])) {
		$err = "Car/nbr manquant";
		return false;
	}
	$car = substr($obj['caractere'],0,1);
	$nombre = (int)@$obj['nombre'];
	if ($nombre> 63) {
		$err = "Repetition incorrecte";
		return false;
	}
	$vdt=MiniPavi\MiniPaviCli::repeatChar($car,$nombre);
	return $vdt;
}


function processCurseurElement($obj,&$err) {
	$vdt = '';
	$err = '';

	if (!isset($obj['mode'])) {
		$err = "mode manquant";
		return false;
	}
	switch($obj['mode']) {
		case 'visible':
			$vdt.=VDT_CURON;
			return $vdt;
		case 'invisible':
			$vdt.=VDT_CUROFF;
			return $vdt;
		default:
			$err = "Mode incorrect";
			return false;
	}
	return $vdt;
}


function processClignoteElement($obj,&$err) {
	$vdt = '';
	$err = '';

	if (!isset($obj['mode'])) {
		$err = "mode manquant";
		return false;
	}
	switch($obj['mode']) {
		case 'actif':
			$vdt.=VDT_BLINK;
			return $vdt;
		case 'inactif':
			$vdt.=VDT_FIXED;
			return $vdt;
		default:
			$err = "Mode incorrect";
			return false;
	}
	return $vdt;
}


function processSouligneElement($obj,&$err) {
	$vdt = '';
	$err = '';

	if (!isset($obj['mode'])) {
		$err = "mode manquant";
		return false;
	}
	switch($obj['mode']) {
		case 'actif':
			$vdt.=VDT_STARTUNDERLINE;
			return $vdt;
		case 'inactif':
			$vdt.=VDT_STOPUNDERLINE;
			return $vdt;
		default:
			$err = "Mode incorrect";
			return false;
	}
	return $vdt;
}

function processInversionElement($obj,&$err) {
	$vdt = '';
	$err = '';

	if (!isset($obj['mode'])) {
		$err = "mode manquant";
		return false;
	}
	switch($obj['mode']) {
		case 'actif':
			$vdt.=VDT_FDINV;
			return $vdt;
		case 'inactif':
			$vdt.=VDT_FDNORM;
			return $vdt;
		default:
			$err = "Mode incorrect";
			return false;
	}
	return $vdt;
}


function processEcritElement($obj,&$err) {
	$vdt = '';
	$err = '';

	if (!isset($obj['texte'])) {
		$err = "texte manquant";
		return false;
	}
	$vdt = MiniPavi\MiniPaviCli::toG2($obj['texte']);
	return $vdt;
}

function processDoublehauteurElement($obj,&$err) {
	return VDT_SZDBLH;
}

function processDoublelargeurElement($obj,&$err) {
	return VDT_SZDBLW;
}

function processDoubletailleElement($obj,&$err) {
	return VDT_SZDBLHW;
}

function processTaillenormaleElement($obj,&$err) {
	return VDT_SZNORM;
}

function processEffacefindeligneElement($obj,&$err) {
	return VDT_CLRLN;
}


function processGraphiqueElement($obj,&$err) {
	return VDT_G1;
}
function processTexteElement($obj,&$err) {
	return VDT_G0;
}

function processDateElement($obj,&$err) {
	return date('d/m/Y');
}

function processHeureElement($obj,&$err) {
	return date('H:i');
}

function processEffaceElement($obj,&$err) {
	return MiniPavi\MiniPaviCli::clearScreen();
}


function processRectangleElement($obj,&$err) {
	$vdt = '';
	$err = '';
	
	if (!isset($obj['ligne']) || !isset($obj['col']) || !isset($obj['largeur']) || !isset($obj['hauteur']) ) {
		$err = "Ligne/col/largeur/hauteur manquant";
		return false;
	}
	if (!isset($obj['couleur'])) {
		$err = "Ligne/col/largeur/hauteur manquant";
		return false;
	}

	switch($obj['couleur']) {
	case 'noir':
		$color=VDT_TXTBLACK;
		break;
	case 'rouge':
		$color=VDT_TXTRED;
		break;
	case 'vert':
		$color=VDT_TXTGREEN;
		break;
	case 'jaune':
		$color=VDT_TXTYELLOW;
		break;
	case 'bleu':
		$color=VDT_TXTBLUE;
		break;
	case 'magenta':
		$color=VDT_TXTMAGENTA;
		break;
	case 'cyan':
		$color=VDT_TXTCYAN;
		break;
	case 'blanc':
		$color=VDT_TXTWHITE;
		break;
	default:
		$err = "Couleur incorrecte";
		return false;
	}

	
	$l = (int)@$obj['ligne'];
	$c = (int)@$obj['col'];
	$larg = (int)@$obj['largeur'];
	$haut = (int)@$obj['hauteur'];
	
	if ($l<0 || $l>24 || $c<1 ||$c>40) {
		$err = "Ligne/col valeur incorrecte";
		return false;
	}
	
	if ($larg<1 || $larg > 40) {
		$err = "Largeur valeur incorrecte";
		return false;
	}
	
	if ($haut<1 || $haut+($l-1) > 24) {
		$err = "Hauteur valeur incorrecte";
		return false;
	}
	for ($i=0;$i<$haut;$i++) {
		$vdt.=MiniPavi\MiniPaviCli::setPos($c,$l+$i);
		$vdt.=$color;
		$vdt.=VDT_FDINV;
		$vdt.=MiniPavi\MiniPaviCli::repeatChar(' ',$larg);
	}
	return $vdt;
}


function processCouleurElement($obj,&$err) {
	$vdt = '';
	$err = '';

	if (!isset($obj['texte']) && !isset($obj['fond'])) {
		$err = "Text/fond manquant";
		return false;
	}
	if (isset($obj['texte'])) {
		switch($obj['texte']) {
		case 'noir':
			$vdt.=VDT_TXTBLACK;
			break;
		case 'rouge':
			$vdt.=VDT_TXTRED;
			break;
		case 'vert':
			$vdt.=VDT_TXTGREEN;
			break;
		case 'jaune':
			$vdt.=VDT_TXTYELLOW;
			break;
		case 'bleu':
			$vdt.=VDT_TXTBLUE;
			break;
		case 'magenta':
			$vdt.=VDT_TXTMAGENTA;
			break;
		case 'cyan':
			$vdt.=VDT_TXTCYAN;
			break;
		case 'blanc':
			$vdt.=VDT_TXTWHITE;
			break;
		}
	}
	
	if (isset($obj['fond'])) {
		switch($obj['fond']) {
		case 'noir':
			$vdt.=VDT_BGBLACK;
			break;
		case 'rouge':
			$vdt.=VDT_BGRED;
			break;
		case 'vert':
			$vdt.=VDT_BGGREEN;
			break;
		case 'jaune':
			$vdt.=VDT_BGYELLOW;
			break;
		case 'bleu':
			$vdt.=VDT_BGBLUE;
			break;
		case 'magenta':
			$vdt.=VDT_BGMAGENTA;
			break;
		case 'cyan':
			$vdt.=VDT_BGCYAN;
			break;
		case 'blanc':
			$vdt.=VDT_BGWHITE;
			break;
		}
	}
	return $vdt;
}



function processZonesaisieElement($obj,&$l,&$c,&$len,&$curseur,&$err) {

	if (!isset($obj['ligne']) || !isset($obj['col']) || !isset($obj['curseur']) || !isset($obj['longueur'])) {
		$err = "Ligne/col/longueur/curseur manquant";
		return false;
	}
	$l = (int)@$obj['ligne'];
	$c = (int)@$obj['col'];
	$len = (int)@$obj['longueur'];
	if ($l<0 || $l>24 || $c<1 ||$c>40) {
		$err = "Ligne/col valeur incorrecte";
		return false;
	}
	
	$maxLength = 41 - $c;
	if ($len<1 || $len>$maxLength) {
		$err = "Longueur valeur incorrecte";
		return false;
	}
	
	
	if ($obj['curseur']!='visible' && $obj['curseur']!='invisible') {
		$err = "Curseur valeur incorrecte";
		return false;
	}
	if ($obj['curseur'] == 'visible') $curseur = true;
	else $curseur = false;
	return true;
}

function processValidationElement($obj,&$validation,&$err) {

	if (!isset($obj['touche'])) {
		$err = "Touche manquante";
		return false;
	}
	switch ($obj['touche']) {
		case 'envoi':
		$validation+=MSK_ENVOI;
		break;
		case 'suite':
		$validation+=MSK_SUITE;
		break;
		case 'retour':
		$validation+=MSK_RETOUR;
		break;
		case 'repetition':
		$validation+=MSK_REPETITION;
		break;
		case 'guide':
		$validation+=MSK_GUIDE;
		break;
		case 'sommaire':
		$validation+=MSK_SOMMAIRE;
		break;
		default:
		$err = "Touche valeur incorrecte";
		return false;

	}
	return true;
}



function processEcranElement($ecran,&$vdt) {
	
	foreach ( $ecran->children() as $node ) {
		
		switch($node->getName()) {
			case 'affiche':

				$r = processAfficheElement($node,$err);
				if ($r === false) {
					$vdt.=VDT_CLR.VDT_G0.VDT_POS.'BA'.VDT_SZDBLH.VDT_TXTWHITE.VDT_BGRED.' XML #006'.VDT_TXTBLACK.chr(0x7D).VDT_TXTWHITE.VDT_BGBLUE.MiniPavi\MiniPaviCli::toG2(' Affiche:URL incorrecte').VDT_CLRLN;
					$vdt.=MiniPavi\MiniPaviCli::setPos(1,4);
					$vdt.=VDT_CRLF.$err.VDT_CRLF.$node->asXML();
					return false;
				}
				$vdt.=$r;
				break;
			case 'position':

				$r = processPositionElement($node,$err);
				if ($r === false) {
					$vdt.=VDT_CLR.VDT_G0.VDT_POS.'BA'.VDT_SZDBLH.VDT_TXTWHITE.VDT_BGRED.' XML #007'.VDT_TXTBLACK.chr(0x7D).VDT_TXTWHITE.VDT_BGBLUE.MiniPavi\MiniPaviCli::toG2(' Position: erreur').VDT_CLRLN;
					$vdt.=MiniPavi\MiniPaviCli::setPos(1,4);
					$vdt.=VDT_CRLF.$err.VDT_CRLF.$node->asXML();
					return false;
				}
				$vdt.=$r;
				break;
				
			case 'curseur':

				$r = processCurseurElement($node,$err);
				if ($r === false) {
					$vdt.=VDT_CLR.VDT_G0.VDT_POS.'BA'.VDT_SZDBLH.VDT_TXTWHITE.VDT_BGRED.' XML #008'.VDT_TXTBLACK.chr(0x7D).VDT_TXTWHITE.VDT_BGBLUE.MiniPavi\MiniPaviCli::toG2(' Curseur: erreur').VDT_CLRLN;
					$vdt.=MiniPavi\MiniPaviCli::setPos(1,4);
					$vdt.=VDT_CRLF.$err.VDT_CRLF.$node->asXML();
					return false;
				}
				$vdt.=$r;
				break;

			case 'clignote':

				$r = processClignoteElement($node,$err);
				if ($r === false) {
					$vdt.=VDT_CLR.VDT_G0.VDT_POS.'BA'.VDT_SZDBLH.VDT_TXTWHITE.VDT_BGRED.' XML #019'.VDT_TXTBLACK.chr(0x7D).VDT_TXTWHITE.VDT_BGBLUE.MiniPavi\MiniPaviCli::toG2(' Clignote: erreur').VDT_CLRLN;
					$vdt.=MiniPavi\MiniPaviCli::setPos(1,4);
					$vdt.=VDT_CRLF.$err.VDT_CRLF.$node->asXML();
					return false;					
				}
				$vdt.=$r;
				break;

				
			case 'ecrit':

				$r = processEcritElement($node,$err);
				if ($r === false) {
					$vdt.=VDT_CLR.VDT_G0.VDT_POS.'BA'.VDT_SZDBLH.VDT_TXTWHITE.VDT_BGRED.' XML #009'.VDT_TXTBLACK.chr(0x7D).VDT_TXTWHITE.VDT_BGBLUE.MiniPavi\MiniPaviCli::toG2(' Ecrit: erreur').VDT_CLRLN;
					$vdt.=MiniPavi\MiniPaviCli::setPos(1,4);
					$vdt.=VDT_CRLF.$err.VDT_CRLF.$node->asXML();
					return false;					
				}
				$vdt.=$r;
				break;
				
			case 'couleur':

				$r = processCouleurElement($node,$err);
				if ($r === false) {
					$vdt.=VDT_CLR.VDT_G0.VDT_POS.'BA'.VDT_SZDBLH.VDT_TXTWHITE.VDT_BGRED.' XML #010'.VDT_TXTBLACK.chr(0x7D).VDT_TXTWHITE.VDT_BGBLUE.MiniPavi\MiniPaviCli::toG2(' Couleur: erreur').VDT_CLRLN;
					$vdt.=MiniPavi\MiniPaviCli::setPos(1,4);
					$vdt.=VDT_CRLF.$err.VDT_CRLF.$node->asXML();
					return false;					
				}
				$vdt.=$r;
				break;
				
				
			case 'doublehauteur':
				$vdt.= processDoublehauteurElement($node,$err);
				break;
			case 'doublelargeur':
				$vdt.= processDoublelargeurElement($node,$err);
				break;
			case 'doubletaille':
				$vdt.= processDoubletailleElement($node,$err);
				break;
			case 'taillenormale':
				$vdt.= processTaillenormaleElement($node,$err);
				break;

			case 'effacefindeligne':
				$vdt.= processEffacefindeligneElement($node,$err);
				break;

			case 'graphique':
				$vdt.= processGraphiqueElement($node,$err);
				break;

			case 'efface':
				$vdt.= processEffaceElement($node,$err);
				break;
				
			case 'texte':
				$vdt.= processTexteElement($node,$err);
				break;

			case 'date':
				$vdt.= processDateElement($node,$err);
				break;
				
			case 'heure':
				$vdt.= processHeureElement($node,$err);
				break;
				
				
			case 'souligne':
				$r = processSouligneElement($node,$err);
				if ($r === false) {
					$vdt.=VDT_CLR.VDT_G0.VDT_POS.'BA'.VDT_SZDBLH.VDT_TXTWHITE.VDT_BGRED.' XML #011'.VDT_TXTBLACK.chr(0x7D).VDT_TXTWHITE.VDT_BGBLUE.MiniPavi\MiniPaviCli::toG2(' Souligne: erreur').VDT_CLRLN;
					$vdt.=MiniPavi\MiniPaviCli::setPos(1,4);
					$vdt.=VDT_CRLF.$err.VDT_CRLF.$node->asXML();
					return false;
				}
				$vdt.=$r;
				break;

			case 'inversion':
				$r = processInversionElement($node,$err);
				if ($r === false) {
					$vdt.=VDT_CLR.VDT_G0.VDT_POS.'BA'.VDT_SZDBLH.VDT_TXTWHITE.VDT_BGRED.' XML #012'.VDT_TXTBLACK.chr(0x7D).VDT_TXTWHITE.VDT_BGBLUE.MiniPavi\MiniPaviCli::toG2(' Inversion: erreur').VDT_CLRLN;
					$vdt.=MiniPavi\MiniPaviCli::setPos(1,4);
					$vdt.=VDT_CRLF.$err.VDT_CRLF.$node->asXML();
					return false;					
				}
				$vdt.=$r;
				break;
				
			case 'repete':
				$r = processRepeteElement($node,$err);
				if ($r === false) {
					$vdt.=VDT_CLR.VDT_G0.VDT_POS.'BA'.VDT_SZDBLH.VDT_TXTWHITE.VDT_BGRED.' XML #013'.VDT_TXTBLACK.chr(0x7D).VDT_TXTWHITE.VDT_BGBLUE.MiniPavi\MiniPaviCli::toG2(' Repete: erreur').VDT_CLRLN;
					$vdt.=MiniPavi\MiniPaviCli::setPos(1,4);
					$vdt.=VDT_CRLF.$err.VDT_CRLF.$node->asXML();
					return false;					
				}
				$vdt.=$r;
				break;
				
			case 'rectangle':
				$r = processRectangleElement($node,$err);
				if ($r === false) {
					$vdt.=VDT_CLR.VDT_G0.VDT_POS.'BA'.VDT_SZDBLH.VDT_TXTWHITE.VDT_BGRED.' XML #020'.VDT_TXTBLACK.chr(0x7D).VDT_TXTWHITE.VDT_BGBLUE.MiniPavi\MiniPaviCli::toG2(' Rectangle: erreur').VDT_CLRLN;
					$vdt.=MiniPavi\MiniPaviCli::setPos(1,4);
					$vdt.=VDT_CRLF.$err.VDT_CRLF.$node->asXML();
					return false;					
				}
				$vdt.=$r;
				break;
				
				
		}
		
	}
	return true;
}




function processEntreeElement($entree,&$cmd,&$vdt) {
	$validation = 0;
	$l=-1;

	foreach ( $entree->children() as $node ) {
		
		switch($node->getName()) {
			case 'zonesaisie':

				$r = processZonesaisieElement($node,$l,$c,$len,$curseur,$err);
				if ($r === false) {
					$vdt.=VDT_CLR.VDT_G0.VDT_POS.'BA'.VDT_SZDBLH.VDT_TXTWHITE.VDT_BGRED.' XML #014'.VDT_TXTBLACK.chr(0x7D).VDT_TXTWHITE.VDT_BGBLUE.MiniPavi\MiniPaviCli::toG2(' Zonesaisie: erreur').VDT_CLRLN;
					$vdt.=MiniPavi\MiniPaviCli::setPos(1,4);
					$vdt.=VDT_CRLF.$err.VDT_CRLF.$node->asXML();
					return false;
				}
				break;
			case 'validation':

				$r = processValidationElement($node,$validation,$err);
				if ($r === false) {
					$vdt.=VDT_CLR.VDT_G0.VDT_POS.'BA'.VDT_SZDBLH.VDT_TXTWHITE.VDT_BGRED.' XML #015'.VDT_TXTBLACK.chr(0x7D).VDT_TXTWHITE.VDT_BGBLUE.MiniPavi\MiniPaviCli::toG2(' Validation: erreur').VDT_CLRLN;
					$vdt.=MiniPavi\MiniPaviCli::setPos(1,4);
					$vdt.=VDT_CRLF.$err.VDT_CRLF.$node->asXML();
					return false;
				}
				
				break;
				
		}
		
	}
	
	if ($l==-1 || $validation == 0) {
		$vdt.=VDT_CLR.VDT_G0.VDT_POS.'BA'.VDT_SZDBLH.VDT_TXTWHITE.VDT_BGRED.' XML #016'.VDT_TXTBLACK.chr(0x7D).VDT_TXTWHITE.VDT_BGBLUE.MiniPavi\MiniPaviCli::toG2(' Zonesaisie: manque zonesaisie/validation').VDT_CLRLN;
		$vdt.=MiniPavi\MiniPaviCli::setPos(1,4);
		$vdt.=VDT_CRLF.$err.VDT_CRLF.$entree->asXML();
		return false;
		
	}
	
	$cmd=MiniPavi\MiniPaviCli::createInputTxtCmd($c,$l,$len,$validation,$curseur);
	return true;
}

function processActionElement($action,$content,$fctn,&$next,&$l0,&$vdt) {
	$next='';
	$l0='';
	foreach ( $action->children() as $node ) {
		
		switch($node->getName()) {
			case 'saisie':
				$r = processSaisieElement($node,$content,$fctn,$next,$err);
				if ($r === false) {
					$vdt.=VDT_CLR.VDT_G0.VDT_POS.'BA'.VDT_SZDBLH.VDT_TXTWHITE.VDT_BGRED.' XML #017'.VDT_TXTBLACK.chr(0x7D).VDT_TXTWHITE.VDT_BGBLUE.MiniPavi\MiniPaviCli::toG2(' Saisie: erreur').VDT_CLRLN;
					$vdt.=MiniPavi\MiniPaviCli::setPos(1,4);
					$vdt.=VDT_CRLF.$err.VDT_CRLF.$node->asXML();
					return false;
				}
				if ($next != '') {
					// Choix trouvé!
					return true;
				}
				break;
		}
		
	}
	// Pas de choix correspondant trouvé
	
	if (!isset($action['defaut'])) {
		$vdt.=VDT_CLR.VDT_G0.VDT_POS.'BA'.VDT_SZDBLH.VDT_TXTWHITE.VDT_BGRED.' XML #018'.VDT_TXTBLACK.chr(0x7D).VDT_TXTWHITE.VDT_BGBLUE.MiniPavi\MiniPaviCli::toG2(' Saisie: defaut absent').VDT_CLRLN;
		$vdt.=MiniPavi\MiniPaviCli::setPos(1,4);
		$vdt.=VDT_CRLF.$err.VDT_CRLF.$action->asXML();
		return false;
	}
	
	$l0 = (string)$action['defaut'];
	return true;
}



function processSaisieElement($obj,$content,$fctn,&$next,&$err) {
	
	$next = '';
	if (!isset($obj['touche'])) {
		$err = "Touche manquante";
		return false;
	}

	if (!isset($obj['suivant'])) {
		$err = "Suivant manquant";
		return false;
	}
	

	
	if (strtoupper($obj['touche'])  == $fctn) {
		if ((isset($obj['choix']) && $obj['choix'] == $content) || !isset($obj['choix'])) {
			// Le choix correspond
			$next = (string)$obj['suivant'];
		}
	}
	return true;
}


?>