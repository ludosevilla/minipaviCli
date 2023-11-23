****************************************************  
****           MINIPAVI Client 1.0              ****  
****               Novembre 2023                ****  
****            Jean-arthur Silve               ****  
****     Infos : http://www.minipavi.fr         ****  
****                                            ****  
****    L'ensemble des fichiers peuvent être    ****  
****         modifiés, distribués etc !         ****  
****************************************************  


# Contenu

- MiniPaviCli.php: Classe pour communiquer avec la psserelle MiniPavi  
- README.md: Ce fichier  

- **France24**  
  - FRANCE24.VDT: Page videotex d'accueil du service  
  - france24Functions.php: Fonctions utilisée dans le script du service  
  - index.php: script du service  

- **MiniChat**  
  - index.php: script du service  
  - MiniChat.vdt: Page videotex du service  
  - miniChatFunctions: script du service  

- **MiniSncf**  
  - index.php: script du service  
  - MiniAPISncf.php: classe "light" pour communiquer avec l'API SNCF
  - sncf2.vdt: logo SNCF videotex
  - train.vdt: train videotex
  
  Remarque: vous devez indiquer une clé pour l'API Sncf dans le fichier MiniAPISncf.php  
  Disponible sur https://numerique.sncf.com/startup/api/
  
  
# Pré-requis

Serveur Web + PHP (les scripts ont été testés avec PHP8.2 et 7.3)

# Installation rapide d'un service
- Copier dans un repertoire accessible les fichiers d'un service +  MiniPaviCli.php
- Modifier le fichier index.php pour que le chemin vers MiniPaviCli.php soit correct

Voilà, terminé.

Si le dossier de votre installation est acessible par exemple via l'url:
http://www.monsite.fr/test/

alors, le service installé est accessible par websocket à l'url:
ws://go.minipavi.fr:8182/url=http://www.monsite.fr/test/

Vous pouvez utiliser, par exemple, l'emulateur Minitel (de MiEdit) disponible sur www.minipavi.fr pour y accéder

