# Audit Lighthouse - 2026-05-21

Date d'audit : 2026-05-21

Outil : Lighthouse CLI 12.8.2 avec Google Chrome 148.0.7778.178.

Perimetre : audit mobile Lighthouse sur l'interface locale Symfony, via `https://127.0.0.1:8000`.

Rapports generes :

- `var/audits/lighthouse/home-fr.report.html`
- `var/audits/lighthouse/projects-fr.report.html`
- `var/audits/lighthouse/project-delcampe-fr.report.html`
- `var/audits/lighthouse/experiences-fr.report.html`
- `var/audits/lighthouse/skills-fr.report.html`
- `var/audits/lighthouse/contact-fr.report.html`

Les rapports JSON correspondants sont disponibles dans le meme dossier.

## Pages Auditees

| Page                     | URL locale                                    |
| ------------------------ | --------------------------------------------- |
| Accueil FR               | `https://127.0.0.1:8000/fr`                   |
| Projets FR               | `https://127.0.0.1:8000/fr/projects`          |
| Fiche projet Delcampe FR | `https://127.0.0.1:8000/fr/projects/delcampe` |
| Experiences FR           | `https://127.0.0.1:8000/fr/experiences`       |
| Competences FR           | `https://127.0.0.1:8000/fr/skills`            |
| Contact FR               | `https://127.0.0.1:8000/fr/contact`           |

## Scores

| Page                     | Performance | Accessibilite | Bonnes pratiques | SEO |   FCP |   LCP |  TBT | CLS | Speed Index |
| ------------------------ | ----------: | ------------: | ---------------: | --: | ----: | ----: | ---: | --: | ----------: |
| Accueil FR               |          99 |            95 |              100 |  63 | 1.2 s | 2.0 s | 0 ms |   0 |       1.2 s |
| Projets FR               |         100 |           100 |              100 |  63 | 1.1 s | 1.8 s | 0 ms |   0 |       1.1 s |
| Fiche projet Delcampe FR |          99 |            95 |              100 |  63 | 1.2 s | 2.0 s | 0 ms |   0 |       1.4 s |
| Experiences FR           |          99 |            95 |              100 |  63 | 1.2 s | 2.0 s | 0 ms |   0 |       1.2 s |
| Competences FR           |          99 |            95 |              100 |  63 | 1.2 s | 2.0 s | 0 ms |   0 |       1.2 s |
| Contact FR               |          99 |            95 |              100 |  63 | 1.2 s | 1.8 s | 0 ms |   0 |       1.2 s |

## Lecture Rapide

Le socle est tres bon :

- performance mobile excellente sur les pages auditees ;
- pas de blocage JavaScript visible : `TBT` a 0 ms partout ;
- stabilite visuelle excellente : `CLS` a 0 partout ;
- bonnes pratiques a 100 partout ;
- texte lisible selon Lighthouse : 100% de texte avec taille legible ;
- meta descriptions, titres, liens crawlables, viewport, robots.txt et hreflang valides.

Les sujets utiles pour ameliorer le site sont concentres sur :

- quelques contrastes insuffisants ;
- un probleme d'accessibilite sur les cartes de la page experiences ;
- des signaux SEO/deploiement a verifier en production ;
- le rendu CSS bloquant, a optimiser seulement si cela reste pertinent apres audit production.

## Points A Traiter

### 1. Contrastes Insuffisants

Impact : accessibilite et confort de lecture, surtout mobile.

Pages concernees :

- accueil ;
- fiche projet Delcampe ;
- experiences ;
- competences ;
- contact.

Cas recurrents :

- couleur `#5aa3c7` sur fond blanc, ratio mesure a environ 2.8:1 ;
- couleur `#6b7280` sur fond `#f8f1e4`, ratio mesure a environ 4.3:1.

Elements signales :

- textes mis en avant `.text-highlight` sur l'accueil ;
- periodes et liens des cartes experiences ;
- lien "Voir l'experience correspondante" sur une fiche projet ;
- actions des cartes contact : "Ouvrir", "Ecrire" ;
- paragraphes dans certaines cartes competences.

Action recommandee :

- foncer legerement la couleur d'accent utilisee sur fond clair ;
- verifier les variantes light/dark pour ne pas corriger un theme en cassant l'autre ;
- viser au moins 4.5:1 pour le texte normal.

Priorite : haute, car le correctif est limite et ameliore directement l'accessibilite.

### 2. Nom Accessible Des Cartes Experiences

Impact : accessibilite lecteur d'ecran et coherence entre texte visible et nom accessible.

Page concernee :

- `https://127.0.0.1:8000/fr/experiences`

Audit Lighthouse :

- `label-content-name-mismatch`
- elements concernes : liens `.experience-card-link`

Cause probable :

- les cartes sont des liens complets avec un `aria-label` du type "Voir le detail de l'experience chez ...";
- le texte visible dans la carte contient beaucoup plus d'informations que ce nom accessible ;
- Lighthouse signale que le texte visible n'est pas inclus dans le nom accessible.

Action recommandee :

- supprimer l'`aria-label` si le contenu de la carte fournit deja un nom de lien comprehensible ;
- ou construire un `aria-label` qui reprend le texte visible essentiel : periode, societe, role et action.

Priorite : moyenne a haute.

### 3. SEO A 63 En Local

Impact : le score SEO est degrade sur toutes les pages auditees.

Audit Lighthouse :

- `is-crawlable`
- source : `x-robots-tag: noindex`

Lecture :

- ce resultat provient de l'environnement local/developpement ;
- il ne doit pas etre considere comme une preuve que le site public est bloque ;
- il faut verifier la reponse HTTP en production.

Verification recommandee :

```bash
curl -I https://benlemin.be/fr
```

Point de controle :

- en production, les pages publiques indexables ne doivent pas renvoyer `X-Robots-Tag: noindex`.

Priorite : haute en verification, mais probablement pas un correctif code si le `noindex` est seulement local.

### 4. Cache Et Compression

Impact : performance percue et efficacite reseau.

Audits Lighthouse recurrents :

- `uses-long-cache-ttl` : 28 ressources trouvees ;
- `uses-text-compression` : economies estimees de 38 a 57 KiB ;
- `cache-insight` : economies estimees autour de 57 KiB ;
- `document-latency-insight` : economies estimees de 8 a 22 KiB.

Lecture :

- ces alertes sont peu fiables en local Symfony ;
- elles doivent etre confirmees sur le site de production ;
- elles concernent surtout la configuration HTTP : cache headers, compression gzip/brotli, latence document.

Action recommandee :

- refaire un audit Lighthouse sur `https://benlemin.be/fr` ;
- completer avec PageSpeed Insights mobile/desktop ;
- verifier les headers de cache des assets compiles dans `public/assets/`.

Priorite : moyenne, a valider en production avant toute modification.

### 5. Ressources CSS Bloquantes

Impact : petit gain potentiel sur le rendu initial.

Audit Lighthouse :

- `render-blocking-resources`
- economies estimees selon les pages : environ 100 a 300 ms.

Lecture :

- le score performance est deja excellent ;
- le gain est reel mais pas prioritaire tant que le contenu, les contrastes et l'accessibilite ne sont pas corriges.

Action possible :

- verifier si certains CSS par page peuvent etre charges plus finement ;
- eviter d'augmenter la complexite de chargement pour gagner quelques millisecondes seulement.

Priorite : basse.

## Priorites Recommandees

1. Corriger les contrastes signales sur les elements recurrents.
2. Corriger le nom accessible des liens de cartes experiences.
3. Relancer Lighthouse local pour verifier le retour a 100 en accessibilite.
4. Lancer Lighthouse sur la production pour distinguer les vrais problemes SEO/cache/compression des effets de l'environnement local.
5. Reporter les optimisations CSS bloquantes tant que les scores performance restent a ce niveau.

## Verification Apres Corrections Locales

Date de verification : 2026-05-21

Corrections verifiees :

- contrastes des textes d'accent et des textes sur fonds doux ;
- nom accessible des cartes experiences.

Rapports generes :

- `var/audits/lighthouse/home-fr-after-a11y.report.html`
- `var/audits/lighthouse/projects-fr-after-a11y.report.html`
- `var/audits/lighthouse/project-delcampe-fr-after-a11y.report.html`
- `var/audits/lighthouse/experiences-fr-after-a11y.report.html`
- `var/audits/lighthouse/skills-fr-after-a11y.report.html`
- `var/audits/lighthouse/contact-fr-after-a11y.report.html`

| Page                     | Performance | Accessibilite | Bonnes pratiques | SEO |   FCP |   LCP |  TBT | CLS | Speed Index |
| ------------------------ | ----------: | ------------: | ---------------: | --: | ----: | ----: | ---: | --: | ----------: |
| Accueil FR               |          99 |           100 |              100 |  63 | 1.2 s | 2.0 s | 0 ms |   0 |       1.2 s |
| Projets FR               |          99 |           100 |              100 |  63 | 1.2 s | 2.0 s | 0 ms |   0 |       1.2 s |
| Fiche projet Delcampe FR |          99 |           100 |              100 |  63 | 1.2 s | 2.0 s | 0 ms |   0 |       1.2 s |
| Experiences FR           |          99 |           100 |              100 |  63 | 1.2 s | 2.0 s | 0 ms |   0 |       1.2 s |
| Competences FR           |          99 |           100 |              100 |  63 | 1.2 s | 2.0 s | 0 ms |   0 |       1.2 s |
| Contact FR               |         100 |           100 |              100 |  63 | 1.1 s | 1.8 s | 0 ms |   0 |       1.1 s |

Points de controle :

- `color-contrast` : score 1 sur les six pages, avec 0 element signale ;
- `label-content-name-mismatch` : score 1 sur les six pages, avec 0 element signale ;
- accessibilite : 100 sur les six pages ;
- SEO : reste a 63 en local a cause de `x-robots-tag: noindex`, a verifier en production dans le dernier lot.

## Verification Apres Decoupage CSS

Date de verification : 2026-05-21

Changement verifie :

- `assets/styles/app.css` ne charge plus les CSS propres aux pages ;
- chaque template public charge `app` plus son entrypoint page dedie ;
- les CSS des pages non visitees ne sont plus charges en reseau.

Rapports generes :

- `var/audits/lighthouse/home-fr-after-css-split.report.html`
- `var/audits/lighthouse/projects-fr-after-css-split.report.html`
- `var/audits/lighthouse/project-delcampe-fr-after-css-split.report.html`
- `var/audits/lighthouse/experiences-fr-after-css-split.report.html`
- `var/audits/lighthouse/skills-fr-after-css-split.report.html`
- `var/audits/lighthouse/contact-fr-after-css-split.report.html`

| Page                     | Performance | Accessibilite | Bonnes pratiques | SEO |   FCP |   LCP |   TBT | CLS | Speed Index |
| ------------------------ | ----------: | ------------: | ---------------: | --: | ----: | ----: | ----: | --: | ----------: |
| Accueil FR               |         100 |           100 |              100 |  63 | 1.1 s | 1.1 s | 70 ms |   0 |       1.1 s |
| Projets FR               |         100 |           100 |              100 |  63 | 1.0 s | 1.7 s | 60 ms |   0 |       1.0 s |
| Fiche projet Delcampe FR |          99 |           100 |              100 |  63 | 1.2 s | 2.0 s |  0 ms |   0 |       1.2 s |
| Experiences FR           |         100 |           100 |              100 |  63 | 1.1 s | 1.8 s |  0 ms |   0 |       1.1 s |
| Competences FR           |         100 |           100 |              100 |  63 | 1.1 s | 1.7 s | 30 ms |   0 |       1.1 s |
| Contact FR               |         100 |           100 |              100 |  63 | 0.9 s | 0.9 s | 50 ms |   0 |       0.9 s |

Controle reseau :

- `/fr` charge `app.css` et `home.css`, plus les imports du socle commun ;
- `/fr/contact` charge `app.css` et `contact.css`, plus les imports du socle commun ;
- les CSS des autres pages restent presents dans l'importmap compilee, mais ne sont plus charges comme feuilles de style en reseau.

Lecture :

- le decoupage supprime bien le chargement des CSS de pages non visitees ;
- cinq pages auditees atteignent 100 en performance locale ;
- la fiche projet reste a 99, avec un LCP a 2.0 s et un signal de CSS bloquant residuel lie au CSS necessaire a cette page et au socle commun ;
- SEO reste a 63 en local a cause de `x-robots-tag: noindex`, a verifier en production.

## Commandes Utilisees

Chrome headless a ete lance manuellement pour contourner le comportement WSL du lanceur Lighthouse :

```bash
google-chrome-stable --headless=new --remote-debugging-port=9222 --ignore-certificate-errors --no-sandbox --disable-gpu --disable-crash-reporter --disable-crashpad --user-data-dir=/tmp/lighthouse-codex-profile about:blank
```

Exemple de commande Lighthouse utilisee :

```bash
lighthouse https://127.0.0.1:8000/fr --hostname=127.000.000.001 --port=9222 --output=json --output=html --output-path=var/audits/lighthouse/home-fr --quiet
```

La variante `--hostname=127.000.000.001` evite que Lighthouse relance lui-meme Chrome sous WSL, tout en se connectant au Chrome deja ouvert sur le port `9222`.

## Note De Reprise

Prochaine action recommandee :

```text
Corriger d'abord les contrastes et le aria-label des cartes experiences, puis relancer Lighthouse local sur les memes pages. Ensuite seulement, lancer un audit production pour valider SEO, cache et compression.
```
