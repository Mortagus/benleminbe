# Recommandations D'Audit Contenu Et Impact

Date de redaction : 2026-05-21

Ce document regroupe des recommandations pour auditer le site `benlemin.be` du point de vue d'un lecteur potentiel : clarte du message, confiance, lisibilite, impact des pages et capacite du site a convertir vers un contact ou un telechargement de CV.

L'objectif n'est pas d'auditer l'architecture technique du projet, mais de comprendre ce qu'un recruteur, un CTO, un client PME ou un partenaire retient vraiment apres avoir consulte le site.

## Objectifs De L'Audit

Questions principales :

- comprend-on rapidement que je suis developpeur web senior specialise PHP, Symfony, Drupal et reprise d'existant ?
- comprend-on a qui le site s'adresse : entreprises, recruteurs, equipes techniques, clients avec outils metier ou legacy ?
- le site donne-t-il confiance pour intervenir sur un projet existant critique ?
- les pages projets et experiences sont-elles lues ou seulement survolees ?
- les appels a l'action vers le contact et le CV sont-ils visibles au bon moment ?
- les contenus restent-ils assez concrets sans devenir trop longs ou trop techniques ?

## Priorite 1 - Mesurer Le Comportement Reel

Outil recommande : Microsoft Clarity.

Interet :

- heatmaps ;
- scroll maps ;
- enregistrements de sessions ;
- detection des zones ignorees ;
- detection des hesitations, clics repetes et abandons.

Ce qu'il faut observer sur le site :

- la page d'accueil est-elle lue au-dela du hero ?
- les visiteurs scrollent-ils jusqu'aux projets representatifs ?
- les liens vers `Projects`, `Experiences`, `Skills` et `Contact` sont-ils utilises ?
- les visiteurs consultent-ils les pages detaillees des projets ?
- le bouton de telechargement du CV est-il vu et clique ?
- le bouton de contact est-il accessible avant que le visiteur quitte la page ?

Livrables attendus :

- liste des zones les plus vues ;
- liste des zones ignorees ;
- pages ou sections avec abandon de lecture ;
- CTA qui fonctionnent ou qui restent invisibles ;
- hypotheses de modifications a tester.

Point d'attention :

- anonymiser et respecter la vie privee des visiteurs ;
- eviter de tirer des conclusions definitives avec trop peu de sessions ;
- attendre d'avoir un minimum de trafic avant de modifier fortement la structure.

## Priorite 2 - Tester La Premiere Impression

Outil recommande : Five Second Test via Lyssna, ou test manuel equivalent.

Principe :

- montrer une page pendant 5 secondes ;
- cacher la page ;
- poser quelques questions simples.

Pages a tester en priorite :

- page d'accueil ;
- page projets ;
- une fiche projet representative ;
- page experiences ;
- page contact.

Questions a poser :

- "Que fait cette personne ?"
- "A qui s'adresse ce site ?"
- "Quel probleme peut-elle resoudre ?"
- "Qu'est-ce qui vous a marque ?"
- "Lui confieriez-vous un projet existant critique ? Pourquoi ?"

Signaux a rechercher :

- les testeurs doivent identifier rapidement le positionnement freelance senior ;
- ils doivent comprendre le lien entre experience, projets et capacite a reprendre un existant ;
- ils ne doivent pas retenir uniquement une liste de technologies ;
- ils doivent percevoir une proposition claire : stabiliser, reprendre, faire evoluer et livrer des applications web metier.

Livrables attendus :

- mots exacts utilises par les testeurs pour decrire le site ;
- elements compris immediatement ;
- elements mal compris ou absents ;
- phrases du hero ou des pages detaillees a simplifier ;
- messages a rendre plus visibles.

## Priorite 3 - Verifier La Structure SEO Et La Lisibilite Mobile

Outils recommandes :

- Google Lighthouse dans Chrome ;
- PageSpeed Insights pour une vision Lighthouse et Core Web Vitals.

Ce qu'il faut verifier :

- titres et hierarchie `h1`, `h2`, `h3` ;
- meta descriptions ;
- contraste ;
- taille des zones cliquables ;
- lisibilite mobile ;
- stabilite visuelle ;
- performance percue ;
- accessibilite de la navigation ;
- comprehension des pages par les moteurs de recherche.

Pages a auditer :

- accueil FR et EN ;
- projets FR et EN ;
- une fiche projet FR et EN ;
- experiences FR et EN ;
- competences FR et EN ;
- contact FR et EN.

Livrables attendus :

- scores Lighthouse par page ;
- problemes bloquants ou recurrents ;
- recommandations separees entre contenu, UX et technique ;
- verification que les titres et descriptions correspondent bien au positionnement du site.

## Priorite 4 - Simplifier Le Copywriting

Outil recommande : Hemingway Editor, ou relecture manuelle avec les memes criteres.

Sections a relire :

- hero de la page d'accueil ;
- cartes "Je peux vous aider si..." ;
- descriptions de projets ;
- resumes d'experiences ;
- page a propos ;
- page contact.

Critiques a appliquer :

- phrases trop longues ;
- jargon inutile ;
- formulations trop abstraites ;
- repetition des memes idees ;
- manque de benefice concret pour le lecteur ;
- contenu trop dense pour une lecture rapide.

Bon angle editorial pour le site :

- moins "voici toutes les technologies que je connais" ;
- plus "je peux reprendre, stabiliser et faire evoluer un projet existant sans creer de chaos" ;
- plus de resultats, contraintes gerees, contexte metier et responsabilites concretes ;
- des phrases courtes, scannables, avec un vocabulaire client ou recruteur.

Livrables attendus :

- liste des phrases a raccourcir ;
- sections a reorganiser ;
- titres a rendre plus explicites ;
- propositions de reformulation pour le hero, les CTA et les descriptions de projets.

## Priorite 5 - Obtenir Du Feedback Humain Cible

Aucun outil automatique ne remplace ce point.

Profils utiles :

- recruteur tech ;
- freelance senior ;
- CTO ou lead dev ;
- personne non technique ;
- client PME ou responsable metier.

Questions minimales :

- "Qu'est-ce que tu penses que je fais ?"
- "Est-ce que tu me ferais confiance sur un projet existant critique ?"
- "Qu'est-ce qui manque ou semble flou ?"

Questions complementaires :

- "Quelle page t'a donne le plus confiance ?"
- "Quelle page t'a fait decrocher ?"
- "Est-ce que tu aurais envie de me contacter apres cette visite ?"
- "Qu'est-ce qui te ferait hesiter ?"
- "Quel mot ou quelle phrase retiens-tu ?"

Livrables attendus :

- synthese par profil teste ;
- citations exactes quand elles sont utiles ;
- objections recurrentes ;
- points de confiance ;
- points de confusion ;
- priorites de correction.

## Plan D'Execution Recommande

### Etape 1 - Baseline

Actions :

- lancer Lighthouse sur les pages principales ;
- noter les scores et problemes recurrents ;
- faire une premiere relecture Hemingway ou equivalente ;
- capturer l'etat actuel du hero, des CTA et des pages projets.

Sortie :

- document de baseline avec les problemes visibles avant tout test utilisateur.

### Etape 2 - Observation Passive

Actions :

- installer Microsoft Clarity ;
- attendre un volume minimal de visites ;
- analyser heatmaps et scroll maps ;
- reperer les pages qui ne sont pas lues ou qui ne convertissent pas.

Sortie :

- liste d'hypotheses basees sur comportement reel.

### Etape 3 - Test De Premiere Impression

Actions :

- tester la page d'accueil avec 5 a 10 personnes ;
- tester au moins une page projet ;
- comparer ce que les gens comprennent avec le positionnement voulu.

Sortie :

- corrections prioritaires sur hero, titres, premiers paragraphes et CTA.

### Etape 4 - Feedback Qualitatif

Actions :

- faire relire le site par 3 a 5 profils cibles ;
- poser les questions minimales ;
- noter les objections sans les justifier pendant l'entretien.

Sortie :

- liste triee des points a clarifier, renforcer ou supprimer.

### Etape 5 - Iteration

Actions :

- modifier les contenus les plus visibles ;
- simplifier les formulations trop longues ;
- rendre les benefices plus explicites ;
- verifier a nouveau Lighthouse et Clarity apres modification.

Sortie :

- version amelioree du site avec impact mesurable.

## Indicateurs A Suivre

Indicateurs quantitatifs :

- taux de scroll sur la page d'accueil ;
- clics sur le CV ;
- clics vers la page contact ;
- clics vers les pages projets ;
- temps passe sur les fiches projets ;
- sorties rapides depuis le hero ;
- consultation des pages FR versus EN.

Indicateurs qualitatifs :

- clarte du positionnement ;
- confiance inspiree ;
- comprehension des types de missions ;
- perception senior ;
- capacite percue a reprendre un projet existant ;
- objections ou zones floues recurrentes.

## Checklist De Fin D'Audit

L'audit peut etre considere utile si :

- au moins 5 personnes ont donne un retour humain cible ;
- au moins une analyse de premiere impression a ete faite ;
- les pages principales ont ete auditees avec Lighthouse ;
- les textes du hero, des CTA et des projets ont ete relus ;
- les principales zones de friction sont documentees ;
- chaque recommandation importante est reliee a une observation, pas seulement a une preference personnelle ;
- les prochaines modifications sont classees par impact potentiel.

## Note De Reprise

Prochaine action recommandee :

```text
Commencer par une baseline Lighthouse + relecture copywriting de la page d'accueil, puis preparer un test de premiere impression avec 5 personnes sur le hero et une fiche projet.
```
