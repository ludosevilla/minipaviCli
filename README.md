****************************************************  
****           MINIPAVI Client 1.1              ****  
****       Novembre 2023 - Juillet 2025         ****  
****            Jean-arthur Silve               ****  
****      Infos : http://www.minipavi.fr        ****  
****                                            ****  
****    L'ensemble des fichiers peuvent être    ****  
****         modifiés, distribués etc !         ****  
****             Licence GNU GPL                ****  
****************************************************  

# Informations

MiniPavi (MINI Point d'Accès VIdeotex) est une passerelle qui permet, entre autres, d'accèder à des services Minitel développés avec une architecture classique Web+PHP.

MiniPaviCli.php est la classe pour s'interfacer avec la passerelle MiniPavi et développer vos propres services.

DisplayPaginatedText.php et DisplayList.php sont deux classes, facultatives, destinées à faciliter la mise en œuvre de l’affichage d’un texte/d'une liste sur plusieurs pages.

Plus d'info sur http://www.minipavi.fr

Les scripts de mini-services MiniChat, France24, SNCF, XMLint, MiniMeteo sont fournis à titre d'exemple.

# Contenu

- MiniPaviCli.php: Classe pour communiquer avec la passerelle MiniPavi  
- DisplayPaginatedText.php: Classe facultative pour l'affichage d'un texte sur plusieurs pages avec navigation via les touches Répétition, Suite et Retour.
- DisplayList.php : Classe facultative pour l'affichage d'une liste de choix sur plusieurs pages avec navigation via les touches Répétition, Envoi, Suite et Retour.
- README.md: Ce fichier  
- MiniPaviCli-doc.pdf: Documentation de MiniPaviCli.php, DisplayPaginatedText.php, DisplayList & déscription du protocole service<->MiniPavi

- **MiniChat**  
  MiniChat est un service de dialogue en direct qui permet le dialogue entre personnes connectées
  avec gestion (optionnelle) de "faux" connectés gérés par ChatGPT.
  
  - index.php: script du service  
  - MiniChat.vdt: Page videotex du service  
  - miniChatFunctions: fonctions générales utilisées par le script  
  - animGPT.php : fonctions utilisées par le script pour l'interfaçage avec ChatGPT 

  Remarque: L'interfaçage avec chatGPT, désactivable, nécessite une clé ChatGPT à obtenir sur le site de CharGPT
  Disponible sur https://openai.com/product
  
- **France24**  
  France24 permet d'accèder à des dépêches d'informations.
  
  - FRANCE24.VDT: Page videotex d'accueil du service  
  - france24Functions.php: Fonctions utilisées dans le script du service  
  - index.php: script du service  

- **MiniSncf**  
  MiniSncf permet de connaître les arrivées et départs depuis les gares SNCF
  
  - index.php: script du service  
  - MiniAPISncf.php: classe "light" pour communiquer avec l'API SNCF
  - sncf2.vdt: logo SNCF videotex
  - train.vdt: train videotex
  
  Remarque: vous devez indiquer une clé pour l'API Sncf dans le fichier MiniAPISncf.php  
  Disponible sur https://numerique.sncf.com/startup/api/
  
- **XMLint**  
  XMLint est un interpreteur XML permettant la réalisation de services Minitel simples sans programmation.
  
  - index.php: script du service
  - XMLfunctions.php: Fonctions utilisées dans le script du service
  - fond.vdt: page videotex utilisée par le script du service
  
  Fichiers de démonstration:
  
  - demo.xml: exemple de fichier XML décrivant un service Minitel
  - moto.vdt: page videotex utilisée pour le service défini dans "demo.xml"
  - pirate.vdt: page videotex utilisée pour le service défini dans "demo.xml"
  - salut.vdt: page videotex utilisée pour le service défini dans "demo.xml" 
  
  
  - XMLint-doc.pdf: documentation concernant la réalisation de services Minitel par fichier XML

- **MiniMeteo**  
  MiniMeteo permet de connaître les prévisions météorologiques mondiales et la qualité de l'air (Europe)
  
  - index.php: script du service  
  - MiniMeteo.php: Récupereration des prévisions sur Open-Meteo
  - meteoacc.vdt et meteofondpage.vdt : fichiers videotex de l'accueil et page interne 
  - Dossier 'icones': contient les icones videotex des prévisions

# Pré-requis

Serveur Web + PHP (les scripts ont été testés avec PHP8.2 et 7.3)

# Installation rapide d'un service

- Copier dans un repertoire accessible les fichiers d'un service + MiniPaviCli.php
- Modifier le fichier index.php pour que le chemin vers MiniPaviCli.php soit correct

Voilà, terminé.

Si le dossier de votre installation est accessible par exemple via l'url:
`http://www.monsite.fr/test/`

alors l'adresse websocket de votre service sera:
`ws://go.minipavi.fr:8182/url=http://www.monsite.fr/test/`

Vous pouvez accèder à votre service depuis l'accueil de MiniPavi:
 - En utilisant, par exemple, l'émulateur Minitel (de MiEdit) disponible sur www.minipavi.fr 
 - En connectant un vrai Minitel à MiniPavi, par téléphone, en composant le **09 72 10 17 21** (+33 972101721)
 - En utilisant un boîter à base d'ESP32 connecté à un vrai Minitel (Minimit de Multiplié, Minitel-ESP32 de Iodeo, ...)
 - Par telnet, en utilisant un émulateur antique (du type Timtel de Goto Informatique) et en vous connectant à l'adresse `go.minipavi.fr` port `516`
 - Avec le logiciel VDT2BMP de JF Delnero (Version Linux : https://github.com/jfdelnero/minitel/tree/master/VDT2BMP ; Version Windows : http://hxc2001.free.fr/minitel/vdt2bmp.zip )
 
Vous devrez préalablement créer un profil créateur depuis l'accueil MiniPavi ou taper l'url de votre service directement depuis l'écran d'accueil.

Enfin, si vous entrez directement l'url de votre service dans un navigateur web, vous serez automatiquement redirigé vers l'émulateur avec affichage direct de votre service.

La librairie est aussi installable via composer: `composer require ludosevilla/minipavi-cli`.
