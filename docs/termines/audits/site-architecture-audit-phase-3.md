# Site Architecture Audit - Phase 3

## Objectif

Auditer la coherence des templates Twig, la structure UX, les composants reutilisables et la separation entre templates publics, pages professionnelles et module DnD.

Cette phase reste un audit. Aucun changement applicatif n'a ete effectue.

## Verification Technique

Commande executee :

```bash
php bin/console lint:twig templates
```

Resultat :

```text
OK - All 22 Twig files contain valid syntax.
```

## Vue Generale Des Templates

Structure actuelle :

```text
templates/
  base.html.twig

  components/
    _lang_switcher.html.twig
    _site_footer.html.twig
    _site_header.html.twig
    _theme_switcher.html.twig

  home/
    index.html.twig

  pages/
    about.html.twig
    card.html.twig
    contact.html.twig
    legal_notice.html.twig
    privacy_policy.html.twig
    skills.html.twig
    terms_and_conditions.html.twig

  projects/
    index.html.twig
    detailed_project.html.twig

  experiences/
    index.html.twig
    detailed_experience.html.twig

  lab/
    dnd/
      initiative_tracker.html.twig
      _monsters_panel.html.twig
      _players_panel.html.twig
      _rules_panels.html.twig
      _turn_order_panel.html.twig
```

Constat global :

- la structure est lisible ;
- les grands domaines sont bien separes ;
- le layout global est simple ;
- les partials existent la ou ils sont deja necessaires ;
- le module DnD est mieux isole cote templates que cote PHP.

## Layout Global

### base.html.twig

Responsabilites actuelles :

- document HTML de base ;
- favicon ;
- titre et meta description ;
- importmap ;
- header/footer ;
- bloc body ;
- bloc javascripts.

Constat :

- layout clair et minimal ;
- les blocs `site_header` et `site_footer` permettent des exceptions comme la carte de visite ;
- `importmap` est surcharge par le DnD, ce qui est coherent.

Points a surveiller :

- le bloc `javascripts` existe mais n'est pas utilise a ce stade ;
- les scripts principaux sont plutot centralises via `assets/app.js`, ce qui est acceptable.

## Composants Globaux

### Header

Fichier :

- `templates/components/_site_header.html.twig`

Constat :

- composant global propre ;
- navigation explicite ;
- bon usage des attributs `data-*` pour le menu mobile ;
- etat actif calcule directement dans Twig.

Points a surveiller :

- la liste de navigation est codee en dur dans le template ;
- si la navigation grossit, elle pourrait devenir une petite structure de donnees ou un composant plus declaratif.

Priorite : basse.

### Footer

Fichier :

- `templates/components/_site_footer.html.twig`

Constat :

- structure claire ;
- contenu legal bien separe ;
- utilisation correcte des traductions layout.

Priorite : aucune action necessaire a court terme.

### Language Switcher

Fichier :

- `templates/components/_lang_switcher.html.twig`

Constat :

- composant simple et utile ;
- gere `_canonical_route`, ce qui est important pour les routes localisees explicitement comme la carte.

Point a surveiller :

- suppose que les routes exposees par le switcher acceptent `_locale` ou disposent d'une route canonique compatible.
- fonctionne pour le perimetre actuel, mais sera a verifier pour les futures routes privees ou lab si le switcher y apparait.

Priorite : basse.

### Theme Switcher

Fichier :

- `templates/components/_theme_switcher.html.twig`

Constat :

- composant isole ;
- bon couplage avec JS via `data-theme-*`.

Probleme repere :

- les libelles sont en dur en francais : `Changer le theme`, `Clair`, `Sombre`.
- le reste du layout est traduit via `translations/layout.*.yaml`.

Recommandation :

- deplacer les libelles du theme switcher dans `layout.fr.yaml` et `layout.en.yaml`.

Priorite : moyenne, car c'est visible sur toutes les pages en anglais.

## Pages Professionnelles

### Home

Fichier :

- `templates/home/index.html.twig`

Constat :

- page autonome et lisible ;
- sections bien separees ;
- les CTA CV sont dupliques dans le hero et la section contact.

Points a surveiller :

- six usages de `|raw` sur les textes `help.card_*`.
- c'est probablement voulu pour les `<strong class="text-highlight">`, mais cela lie fortement les traductions au HTML.

Recommandations :

- conserver pour l'instant si les traductions sont controlees localement ;
- documenter que ces cles de traduction contiennent du HTML ;
- si cela se repete, envisager un composant Twig ou une approche avec fragments structures.

Priorite : basse.

### About / Skills / Legal Pages

Fichiers :

- `templates/pages/about.html.twig`
- `templates/pages/skills.html.twig`
- `templates/pages/legal_notice.html.twig`
- `templates/pages/privacy_policy.html.twig`
- `templates/pages/terms_and_conditions.html.twig`

Constat :

- beaucoup de sections suivent le pattern :

```twig
<section class="content-section" aria-labelledby="...">
    <div class="card">
        <h2 id="...">...</h2>
        ...
    </div>
</section>
```

- c'est coherent, lisible et accessible ;
- les pages legales sont verbeuses mais structurellement claires.

Point a surveiller :

- forte repetition de markup pour les sections cartees ;
- pour le moment, cette repetition reste acceptable car chaque page est statique et explicite.

Recommandation :

- ne pas extraire trop tot ;
- si les pages statiques continuent a grossir, creer un partial generique du type :

```text
templates/components/_content_card_section.html.twig
```

Priorite : basse.

### Contact

Fichier :

- `templates/pages/contact.html.twig`

Constat :

- structure claire ;
- cards de contact explicites ;
- liens externes correctement marques avec `target="_blank"` et `rel`.

Probleme concret repere :

- le lien email pointe vers `mailto:contact@example.com`.

Recommandation :

- verifier si c'est volontaire ou un placeholder residuel ;
- si c'est un placeholder, le remplacer lors d'une intervention dediee.

Priorite : moyenne, car c'est un lien utilisateur direct.

### Business Card

Fichier :

- `templates/pages/card.html.twig`

Constat :

- utilise proprement les blocs `site_header` et `site_footer` pour afficher une page autonome ;
- reutilise `components/_lang_switcher.html.twig`.

Point a surveiller :

- cette page est volontairement speciale, donc il ne faut pas forcer sa structure a ressembler aux autres pages.

Priorite : aucune action necessaire.

## Projets Et Experiences

### Listings

Fichiers :

- `templates/projects/index.html.twig`
- `templates/experiences/index.html.twig`

Constat :

- les deux listings sont simples et orientes cards ;
- le listing projets est tres sobre ;
- le listing experiences est plus riche mais reste maintenant plus scannable.

Incoherence mineure :

- `projects/index.html.twig` utilise `trans({}, 'projects')` a chaque appel au lieu de `trans_default_domain`.
- `experiences/index.html.twig` utilise `trans_default_domain 'experiences'`.

Recommandation :

- harmoniser progressivement le style de traduction.

Priorite : basse.

### Pages Detail

Fichiers :

- `templates/projects/detailed_project.html.twig`
- `templates/experiences/detailed_experience.html.twig`

Constat :

- les deux pages detail ont des patterns proches :
    - hero ;
    - meta ;
    - table des matieres ;
    - sections ;
    - navigation precedente/suivante.

Bonne evolution recente :

- les ancres et le sommaire rendent les pages longues plus utilisables ;
- le comportement mobile est separe via `data-content-toc`.

Points a surveiller :

- le sommaire est duplique entre projet et experience avec markup tres proche ;
- la navigation precedente/suivante existe sous deux variantes CSS/classes ;
- les sections projet sont tres generiques et capables de rendre beaucoup de formes (`paragraphs`, `flow`, `list`, `labeled_list`, `groups`, `subsections`), ce qui rend le template puissant mais long.

Recommandations :

- extraire a terme un composant `content_toc` si d'autres pages longues l'utilisent ;
- ne pas extraire immediatement si seules deux pages l'utilisent ;
- surveiller `detailed_project.html.twig`, qui commence a ressembler a un renderer de schema de contenu.

Priorite : moyenne pour le sommaire si reutilisation future, basse pour le reste.

## Module DnD

Fichiers :

- `templates/lab/dnd/initiative_tracker.html.twig`
- `templates/lab/dnd/_monsters_panel.html.twig`
- `templates/lab/dnd/_players_panel.html.twig`
- `templates/lab/dnd/_turn_order_panel.html.twig`
- `templates/lab/dnd/_rules_panels.html.twig`

Constat :

- bonne separation en panels ;
- les partials correspondent bien aux zones UI ;
- les templates sont fortement couples au JS via ids et classes ;
- c'est acceptable pour un outil interactif.

Problemes ou limites :

- tous les textes sont en dur, principalement en francais ;
- le titre/meta description sont en dur ;
- le markup initial d'un joueur est duplique avec le template `playerItemTemplate` ;
- plusieurs icones sont du SVG inline dans le template ;
- le module ne semble pas suivre la logique de traduction du reste du site.

Lecture architecturale :

- le DnD tracker se comporte comme un mini-app frontend integree dans Symfony ;
- ses templates sont moins "site vitrine" et plus "surface d'application".

Recommandations :

- si le DnD reste un outil personnel/lab non prioritaire : garder tel quel pour l'instant ;
- si le DnD devient un vrai module public maintenu : introduire un domaine de traduction `lab_dnd` ou `dnd`;
- reduire la duplication joueur initial/template ;
- documenter les ids/classes attendus par le JS.

Priorite : a traiter en phase 6, pas maintenant.

## Composants Candidats A Extraction

### Candidats Raisonnables

- `content_toc` : sommaire + bouton mobile, utilise par projets et experiences.
- `detail_navigation` : precedent/suivant/back, variantes projets/experiences.
- `content_card_section` : sections statiques des pages about/legal/skills.
- `project_section_renderer` : seulement si le schema projets continue de grossir.

### Candidats A Ne Pas Extraire Tout De Suite

- hero de page : les variations actuelles sont assez nombreuses ;
- cards contact/projets/experiences : elles ont des besoins differents ;
- DnD panels : deja en partials dedies, extraction supplementaire prematuree.

## Conventions Twig A Stabiliser

Recommandations :

- choisir entre `trans_default_domain` et `trans({}, 'domain')` par famille de templates ;
- privilegier `trans_default_domain` quand toute la page utilise un seul domaine ;
- documenter les traductions contenant du HTML et limiter `|raw` aux cles controlees ;
- reserver les textes en dur aux modules volontairement non localises ;
- garder les `data-*` comme contrat JS explicite ;
- eviter d'extraire un composant avant au moins trois usages ou une complexite reelle.

## Recommandations Priorisees

### Priorite 1 - Petites Corrections Visibles

- Traduire le theme switcher.
- Verifier/remplacer `mailto:contact@example.com`.

### Priorite 2 - Stabilisation Progressive

- Harmoniser l'usage des domaines de traduction dans les templates projets.
- Envisager un composant `content_toc` si d'autres pages longues arrivent.
- Documenter les cles de traduction qui contiennent du HTML.

### Priorite 3 - A Reporter Aux Phases Dediees

- Ne pas refactorer maintenant les pages legales malgre leur repetition.
- Reporter l'audit approfondi du DnD a la phase 6.
- Reporter les choix CSS/JS du sommaire et de l'indicateur de lecture a la phase 4.

## Note De Reprise

```text
Phase 3 terminee.

Templates audites :
- base.html.twig
- components header/footer/lang/theme
- home
- pages about/skills/contact/card/legal/privacy/terms
- projects index/detail
- experiences index/detail
- lab/dnd main template and partials

Constats principaux :
- Structure Twig globale lisible.
- Components globaux bien identifies.
- Pages statiques coherentes mais repetitives.
- Details projets/experiences partagent des patterns qui pourraient devenir des composants.
- DnD est bien separe en partials, mais textes en dur et duplication joueur initial/template.

Findings concrets :
- Theme switcher non traduit.
- contact.html.twig contient mailto:contact@example.com.
- Home utilise |raw pour six textes de traduction.
- Projects templates utilisent moins trans_default_domain que experiences.

Recommandations principales :
- Traduire le theme switcher.
- Verifier/remplacer l'email placeholder.
- Ne pas sur-extraire les sections statiques maintenant.
- Envisager un composant content_toc si le sommaire est reutilise ailleurs.
- Garder l'audit DnD approfondi pour la phase 6.

Phase suivante :
- Phase 4 - CSS, Assets Et JavaScript.
```
