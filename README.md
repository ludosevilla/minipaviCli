****************************************************  
****           MINIPAVI Client 1.0              ****  
****               Novembre 2023                ****  
****            Jean-arthur Silve               ****  
****     Infos : http://www.minipavi.fr         ****  
****                                            ****  
****    L'ensemble des fichiers peuvent être    ****  
****         modifiés, distribués etc !         ****  
****             Licence GNU GPL                ****  
****************************************************  

# Informations

MiniPavi est une passerelle qui permet de développer des services Minitel sur une architecture classique Web+PHP.

MiniPaviCli.php est la classe pour s'interfacer avec la passerelle.

Plus d'info sur http://www.minipavi.fr

Les scripts de mini-services MiniChat, France24 et SNCF sont fournis à titre d'exemple.

# Contenu

- MiniPaviCli.php: Classe pour communiquer avec la passerelle MiniPavi  
- README.md: Ce fichier  

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
  XMLint est un interpreteur XML permettant la réalisation de service Minitel sans programmation.
  
  - index.php: script du service  
  - XMLfunctions.php: Fonctions utilisées dans le script du service 
  - fond.vdt: page videotex utilisée par le script du service
  
  Fichiers de démonstration:
  
  - demo.xml: exemple de fichier XML décrivant un service Minitel
  - moto.vdt: page videotex utilisée pour le service défini dans "demo.xml"
  - pirate.vdt: page videotex utilisée pour le service défini dans "demo.xml"  
  - salut.vdt: page videotex utilisée pour le service défini dans "demo.xml"  
  
  - XMLint-doc.pdf: documentation concernant la réalisation de services Minitel par fichier XML
  

# Pré-requis

Serveur Web + PHP (les scripts ont été testés avec PHP8.2 et 7.3)

# Installation rapide d'un service

- Copier dans un repertoire accessible les fichiers d'un service + MiniPaviCli.php
- Modifier le fichier index.php pour que le chemin vers MiniPaviCli.php soit correct

Voilà, terminé.

Si le dossier de votre installation est accessible par exemple via l'url:
http://www.monsite.fr/test/

alors, le service installé est accessible par websocket à l'url:
ws://go.minipavi.fr:8182/url=http://www.monsite.fr/test/

Vous pouvez utiliser, par exemple, l'émulateur Minitel (de MiEdit) disponible sur www.minipavi.fr pour y accéder.

La librairie est aussi installable via composer: `composer require ludosevilla/minipavi-cli`.
