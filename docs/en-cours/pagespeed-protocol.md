# PageSpeed Protocol

Date de redaction : 2026-05-27

Ce document est la reference de travail pour le protocole PageSpeed du site `benlemin.be`.

## Objectif

Garder un suivi reproductible de la performance percue par PageSpeed Insights, avec une lecture claire des donnees de labo et, quand elles existent, des donnees de terrain.

## Cadre

PageSpeed Insights combine :

- des donnees de terrain issues de CrUX ;
- des donnees de labo issues de Lighthouse ;
- des vues mobile et desktop ;
- un fallback au niveau de l'origine si la page n'a pas assez de donnees URL.

Le bon usage pour ce projet est de traiter PageSpeed comme un tableau de bord de tendance, pas comme un test unitaire.

## Baseline Initiale

### Methode

- PageSpeed web UI ouverte dans Chrome headless ;
- cible : `https://benlemin.be/fr` ;
- vues capturees en `mobile` puis `desktop` ;
- donnees terrain `No Data` sur les deux vues au moment de la baseline ;
- tentative initiale via l'API officielle interrompue par un quota `429`, baseline relevee via l'interface web.

### Vue Mobile

Heure du rapport :

- `May 27, 2026, 10:56:45 AM`

Scores :

- performance : `99`
- accessibilite : `100`
- bonnes pratiques : `100`
- SEO : `100`

Metriques :

- FCP : `1.0 s`
- LCP : `1.0 s`
- TBT : `0 ms`
- CLS : `0`
- Speed Index : `3.7 s`

Points visibles :

- `Render-blocking requests` avec economie estimee de `300 ms`
- `Forced reflow`
- `Network dependency tree`
- `Optimize DOM size`
- `LCP breakdown`

### Vue Desktop

Heure du rapport :

- `May 27, 2026, 10:57:48 AM`

Scores :

- performance : `100`
- accessibilite : `100`
- bonnes pratiques : `100`
- SEO : `100`

Metriques :

- FCP : `0.2 s`
- LCP : `0.2 s`
- TBT : `0 ms`
- CLS : `0`
- Speed Index : `0.4 s`

Points visibles :

- `Render-blocking requests` avec economie estimee de `80 ms`
- `Forced reflow`
- `Network dependency tree`
- `LCP breakdown`

## Conclusion Actuelle

La conclusion operative actuelle est simple :

- le site donne deja de tres bons resultats ;
- il n'y a pas de chantier majeur urgent cote PageSpeed ;
- il reste seulement des optimisations fines, surtout autour du rendu bloquant et d'un possible `forced reflow`.

Autrement dit, le site est deja dans une zone tres saine, et le travail PageSpeed doit surtout servir a maintenir et verifier cette qualite dans le temps.

## Protocole Recommande

1. Lancer un audit PageSpeed sur la production, en mobile puis en desktop.
2. Stocker le JSON brut pour chaque page et chaque strategie.
3. Generer un resume Markdown lisible avec les points de lecture essentiels.
4. Comparer les resultats a la baseline precedente plutot qu'a une seule mesure.
5. Utiliser Lighthouse local quand un ecart de labo doit etre isole ou reproduit.

## Outillage

- `tools/pagespeed/collect_pagespeed.php` pour interroger PageSpeed Insights et generer des artefacts locaux.
- `make pagespeed_audit` pour lancer la collecte de facon repetable.
- `var/audits/pagespeed/YYYY-MM-DD/` pour conserver les JSON et le resume Markdown.

La collecte automatique peut fonctionner sans cle pour un usage ponctuel, mais une cle API reste recommandee si l'on veut multiplier les requetes ou industrialiser le suivi.
Le collecteur garde maintenant les rapports partiels et les erreurs dans des artefacts dedies au lieu d'arreter tout le lot au premier echec.
Les scripts utilitaires de ce type doivent etre enregistres dans `tools/` et ne pas rester de simples fragments ecrits a la volee dans le terminal.

## Suivi

Les prochains releves doivent surtout servir a verifier que la baseline se maintient.

Les pistes d'amelioration rapide restent secondaires tant que les scores restent dans cette zone:

- reduire le rendu bloquant si un gain concret apparait ;
- verifier les rares `forced reflow` si un ecart se reproduit ;
- ne pas complexifier le chargement CSS sans preuve de gain visible.
