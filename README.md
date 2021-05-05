

# Plugin Cocote pour PrestaShop 1.6 à 1.7

Ce module communique avec Cocote.com et genere un flux xml de vos offres produits.

Pour installer ce module:

1) Télécharger https://github.com/Cocote-Team/PrestashopPlugin/raw/master/cocotefeed.zip


2) Transfert et copie des fichiers


Aller sur votre Admin PrestaShop : Modules > Modules & services et cliquer sur 'installer un module'

Faire un Drag n Drop du fichier telechargé cocotefeed.zip .


3) Configurer le Module

Cliquer sur l'element 'Cocote Feed' dans la colonne de gauche puis cliquer sur "Configurer" du Module Cocotefeed.
                   
Renseigner vos clés (diponibles depuis https://fr.cocote.com/mon-compte/ma-boutique/script-de-suivi ) 

Cliquer sur enregistrer.

Votre url flux est désormais disponible.

Cot Cot Cot!

4) Configurer et activer les crons

Pour faire fonctionner les crons sur Prestashop il faut faire les choses suivantes :

- Télécharger le module cronjobs https://github.com/Cocote-Team/PrestashopPlugin/raw/master/cronjobs.zip
- Aller sur votre Admin PrestaShop : Modules > Modules & services et cliquer sur 'installer un module'
- Faire un Drag n Drop du fichier telechargé cronjobs.zip .
- Une fois installer aller dans sa configuration et passer le *Mode cron* en Avancé et enregistrer
- Après enregistrement ajouter au sein de la crontab du serveur le cron passer dans l'encart bleu ciel
- En-dessous, dans les tâches cron il devrait y avoir le cron du *Module Cocotefeed*.

> Mode cron :
>* Le mode basique utilise un webservice Prestashop mais cela implique de configurer des éléments en plus au sein du shop qui peut potentiellement entrer collision avec d'autres modules
>* Le mode avancé utilise la crontab linux traditionnelle.