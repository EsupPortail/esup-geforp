# Geforp changelog :

## 2.3.15 (2025-01-23)

### Ajouts
- Ajout éditeur HTML dans le template des modèles de mail
- Ajout données dans les évaluations
- Ajout affichage données stagiaires

### Corrections
- Corrections dans les exports
- Corrections dans les modèles de mail envoyés côté stagiaire

## 2.3.14 (2024-10-09)

### Ajouts
- Ajout dans l'export des sessions
- Ajout 'liens externes' barre de menu
- Ajout paramètre recherche d'inscription

### Corrections
- Correction export des inscriptions

## 2.3.13 (2024-09-25)

### Ajouts
- Ajout envoi direct mail aux stagiaires

### Corrections
- Correction tableau des inscriptions avant tri
- Correction génération PDF suite à deprecated sur unoconv
- Correction ajout/modif utilisateur 

## 2.3.12 (2024-09-19)

### Corrections
- Corrections conf CSV

## 2.3.11 (2024-09-18)

### Ajouts
- Ajout mots clés pour avis favorable/défavorable du N+1

### Corrections
- Mise à jour librairie CSV pour PHP8.3
- Corrections conf CSV
- Affichage contacts

## 2.3.10 (2024-07-22)

### Ajouts
- Ajout formateur interne ou externe dans les exports de session

### Corrections
- Correction ordre des critères d'évaluation côté stagiaire
- Suppression pagination mails envoyés par session
- Affichage FAQ

## 2.3.9 (2024-05-27)

### Ajouts
- Ajout param export formateurs
- Prise en compte modèles de mails avis favorable/défavorable du N+1 côté validation par le N+1

## 2.3.8 (2024-05-13)

### Ajouts
- Ajout campus dans export inscriptions

### Corrections
- Correction tri des inscriptions côté stagiaire

## 2.3.7 (2024-04-16)

### Ajouts
- Envoi mail et copie au N+1 (et correspondant formation) en cas de désistement côté stagiaire

### Corrections
- Correction affichage dates dans email au format HTML
- Correction fiche stagiaire en cas de formation le même jour

## 2.3.6 (2024-03-04)

### Corrections
- Corrections avertissement chevauchement dates en cas de statut désisté, refusé...

## 2.3.5 (2024-02-20)

### Corrections
- Corrections tableau de bord et recap des inscriptions : nb acceptés doit inclure nb acceptés et nb convoqués

## 2.3.4 (2024-02-19)

### Corrections
- Corrections effet de bord statut accepté/convoqué

## 2.3.3 (2024-02-19)

### Corrections
- Modification désistement côté stagiaire : possible uniquement jusqu'au statut 'accepté'

## 2.3.2 (2024-02-06)

### Ajouts
- Format HTML rendu disponible pour l'envoi de mails indépendants

### Corrections
- Affichage email au lieu de l'identifiant pour les gestionnaires connectés
- Accessibilité du param format HTML lors de l'envoi de mail

## 2.3.1 (2024-01-22)

### Ajouts
- Message d'avertissement pour les stagiaires à l'inscription s'ils sont déjà inscrits à une session englobant les mêmes dates
- Message de rappel pour les stagiaires à l'inscription : affichage du N+1
- Désistement élargi côté stagiaire (même si l'inscription est acceptée)

## 2.3.0 (2024-01-08)

### Ajouts
- Création d'une interface N+1
- Possibilité d'ajouter une invitation calendrier lors de l'envoi de convocations

### Corrections
- Corrections sur les tris côté gestionnaire
- Amélioration affichage côté stagiaire
- Corrections diverses


