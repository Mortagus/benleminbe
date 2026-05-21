# Plan De Decoupage Des CSS Par Page

Date de redaction : 2026-05-21

Source : audit Lighthouse local du 2026-05-21.

Objectif : reduire les CSS bloquants en chargeant uniquement le socle commun et les styles necessaires a la page affichee.

## Probleme A Corriger

Aujourd'hui, `assets/app.js` importe `assets/styles/app.css`.

`app.css` importe ensuite toutes les feuilles CSS publiques :

- base ;
- layout ;
- composants ;
- toutes les pages : home, about, contact, card, skills, experiences, project detail, legal, etc.

Consequence :

- une page comme `/fr` charge aussi les CSS de `/fr/contact`, `/fr/skills`, `/fr/projects/delcampe`, etc. ;
- Lighthouse signale des ressources CSS bloquantes ;
- le score performance reste a 99 sur plusieurs pages, principalement a cause du FCP/LCP.

## Objectif De Fin

Le decoupage est termine quand :

- `app.css` ne contient plus que le CSS commun ;
- chaque page charge son CSS specifique via un entrypoint dedie ;
- les pages detaillees chargeent leur CSS detail dedie ;
- le lab DnD conserve son entrypoint specifique ;
- Lighthouse local ne charge plus les CSS des pages non visitees ;
- `make check` passe ;
- les rapports Lighthouse confirment que la performance reste stable ou progresse.

## Lot 1 - Definir Le Socle Commun

Fichier concerne :

- `assets/styles/app.css`

Garder dans `app.css` :

- `base/tokens.css`
- `base/init.css`
- `base/typography.css`
- `layout/header.css`
- `layout/footer.css`
- `components/cards.css`
- `components/buttons.css`
- `components/content.css`
- `components/lang-switcher.css`
- `components/links.css`
- `components/theme-switcher.css`

Retirer de `app.css` :

- `pages/home.css`
- `pages/about.css`
- `pages/contact.css`
- `pages/card.css`
- `pages/skills.css`
- `pages/experiences.css`
- `pages/experience-detail.css`
- `pages/projects.css`
- `pages/project-detail.css`
- `pages/legal.css`

Critere de validation :

- toutes les pages gardent header, footer, boutons, cartes et composants communs ;
- aucune page ne doit etre visuellement correcte uniquement parce que son CSS specifique est encore dans `app.css`.

## Lot 2 - Creer Les Entrypoints CSS Par Page

Approche recommandee :

- creer un fichier JavaScript par page dont le seul role est d'importer le CSS de page ;
- declarer ces fichiers comme entrypoints dans `importmap.php` ;
- charger les entrypoints depuis Twig avec `importmap(['app', 'page_x'])`.

Exemples de fichiers a creer :

```text
assets/pages/home.js
assets/pages/about.js
assets/pages/contact.js
assets/pages/card.js
assets/pages/skills.js
assets/pages/projects.js
assets/pages/project_detail.js
assets/pages/experiences.js
assets/pages/experience_detail.js
assets/pages/legal.js
```

Contenu attendu :

```js
import '../styles/pages/home.css';
```

Pourquoi des entrypoints JS ?

- Symfony Asset Mapper sait deja gerer les entrypoints JavaScript ;
- le projet utilise deja ce pattern avec `app`, `dnd_initiative` et `private` ;
- cela evite d'introduire un mecanisme CSS parallele.

## Lot 3 - Declarer Les Entrypoints Dans `importmap.php`

Fichier concerne :

- `importmap.php`

Ajouter des entrees :

```php
'page_home' => [
    'path' => './assets/pages/home.js',
    'entrypoint' => true,
],
```

Nommage recommande :

- `page_home`
- `page_about`
- `page_contact`
- `page_card`
- `page_skills`
- `page_projects`
- `page_project_detail`
- `page_experiences`
- `page_experience_detail`
- `page_legal`

Critere de validation :

```bash
php bin/console debug:asset-map
```

Les nouveaux entrypoints doivent etre resolus par Asset Mapper.

## Lot 4 - Charger Les Entrypoints Dans Les Templates

Fichiers concernes :

- `templates/home/index.html.twig`
- `templates/pages/about.html.twig`
- `templates/pages/contact.html.twig`
- `templates/pages/card.html.twig`
- `templates/pages/skills.html.twig`
- `templates/pages/legal_notice.html.twig`
- `templates/pages/privacy_policy.html.twig`
- `templates/pages/terms_and_conditions.html.twig`
- `templates/projects/index.html.twig`
- `templates/projects/detailed_project.html.twig`
- `templates/experiences/index.html.twig`
- `templates/experiences/detailed_experience.html.twig`

Pattern recommande :

```twig
{% block importmap %}
    {{ importmap(['app', 'page_home']) }}
{% endblock %}
```

Mapping recommande :

| Template | Entrypoint |
| --- | --- |
| `home/index.html.twig` | `page_home` |
| `pages/about.html.twig` | `page_about` |
| `pages/contact.html.twig` | `page_contact` |
| `pages/card.html.twig` | `page_card` |
| `pages/skills.html.twig` | `page_skills` |
| `pages/legal_notice.html.twig` | `page_legal` |
| `pages/privacy_policy.html.twig` | `page_legal` |
| `pages/terms_and_conditions.html.twig` | `page_legal` |
| `projects/index.html.twig` | `page_projects` |
| `projects/detailed_project.html.twig` | `page_project_detail` |
| `experiences/index.html.twig` | `page_experiences` |
| `experiences/detailed_experience.html.twig` | `page_experience_detail` |

Cas particulier :

- `templates/lab/dnd/initiative_tracker.html.twig` charge deja `['app', 'dnd_initiative']`.
- si `lab_dnd_initiative.css` depend de styles communs, conserver `app`.
- ne pas ajouter les CSS de pages publiques au lab.

## Lot 5 - Verifier Les Dependances Entre CSS

Points a surveiller :

- certaines pages peuvent utiliser des classes communes comme `.page-hero`, `.eyebrow`, `.content-section`, `.card`, `.button` ;
- ces classes doivent rester dans le socle commun si elles sont partagees ;
- les classes vraiment propres a une page doivent rester dans le CSS de page.

Actions :

1. Ouvrir chaque page localement.
2. Verifier header, footer, layout, boutons, cartes et liens.
3. Corriger uniquement les styles qui ont ete mal classes.

Pages a verifier :

- `/fr`
- `/fr/projects`
- `/fr/projects/delcampe`
- `/fr/experiences`
- `/fr/experiences/contraste-digital`
- `/fr/skills`
- `/fr/about`
- `/fr/contact`
- `/card`
- `/fr/legal-notice`
- `/fr/privacy-policy`
- `/fr/terms-and-conditions`
- `/lab/dnd-initiative`

## Lot 6 - Recompiler Et Tester

Commandes :

```bash
make reload_assets
make check
```

Verifier que :

- `asset-map:compile` genere les nouveaux entrypoints ;
- Twig reste valide ;
- CSS lint reste vert ;
- aucune page ne perd son style specifique.

## Lot 7 - Relancer Lighthouse Local

Pages minimales :

- `/fr`
- `/fr/projects`
- `/fr/projects/delcampe`
- `/fr/experiences`
- `/fr/skills`
- `/fr/contact`

Objectif :

- verifier que les ressources CSS bloquees sont reduites ;
- verifier si le score performance passe de 99 a 100 sur les pages concernees ;
- confirmer que l'accessibilite reste a 100.

Point important :

- si le score reste a 99 mais que les ressources inutiles ne sont plus chargees, le decoupage reste pertinent ;
- Lighthouse local peut encore etre limite par le serveur Symfony local, l'absence de compression ou la variabilite de mesure.

## Risques

Risque principal :

- oublier un entrypoint sur un template et obtenir une page partiellement non stylisee.

Mitigation :

- ajouter les blocs `importmap` template par template ;
- relire les pages une par une ;
- utiliser Lighthouse et une verification visuelle.

Risque secondaire :

- multiplier les entrypoints augmente legerement la maintenance.

Mitigation :

- garder un nommage strict ;
- limiter les entrypoints aux grandes familles de pages ;
- ne pas fragmenter les petits composants.

## Decision Recommandee

Proceder en deux commits :

1. Decoupage technique des entrypoints CSS.
2. Verification Lighthouse/documentation des resultats.

## Note De Reprise

Prochaine action recommandee :

```text
Commencer par retirer les imports `pages/*` de `assets/styles/app.css`, creer les entrypoints `assets/pages/*.js`, les declarer dans `importmap.php`, puis ajouter les blocs `importmap(['app', 'page_*'])` dans les templates concernes.
```
