# Site Architecture Audit - Phase 5

## Objectif

Auditer la coherence entre les sources Markdown, les YAML de traduction et les providers PHP.

Cette phase reste un audit. Aucun changement applicatif n'a ete effectue.

## Verification Technique

Commandes executees :

```bash
find docs/pro_exp -maxdepth 2 -type f | sort
find translations -maxdepth 1 -type f | sort
wc -l docs/pro_exp/*.md translations/*.yaml
php -r '...' # comparaison des cles FR/EN par domaine
php -r '...' # verification cartes projets/details projets
php -r '...' # verification YAML parse via Symfony Yaml
php bin/console lint:yaml translations docs/pro_exp
php bin/console debug:translation fr --only-missing
php bin/console debug:translation en --only-missing
```

Resultats :

```text
YAML parse : OK
lint:yaml : OK, 18 fichiers YAML valides
Comparaison cles FR/EN : OK, aucune cle manquante sur les domaines principaux
Cartes projets/details projets : OK, 14 cartes et 14 fiches detail
debug:translation : 1 cle manquante detectee dans le domaine messages
```

Cle manquante detectee :

```text
messages / home.meta.title
```

Cette cle vient du fallback de titre dans `templates/base.html.twig`. Les pages principales surchargent le bloc `title`, donc le probleme n'est pas forcement visible en navigation normale, mais il signale un contrat de traduction imparfait.

## Sources De Contenu Actuelles

Sources Markdown professionnelles :

```text
docs/pro_exp/
  experience_compilation.md
  projects_compilation.md
  skill_matrix.md
  soft_skills.md
```

Domaines de traduction :

```text
translations/
  about.fr.yaml / about.en.yaml
  card.fr.yaml / card.en.yaml
  contact.fr.yaml / contact.en.yaml
  experiences.fr.yaml / experiences.en.yaml
  home.fr.yaml / home.en.yaml
  layout.fr.yaml / layout.en.yaml
  legal.fr.yaml / legal.en.yaml
  projects.fr.yaml / projects.en.yaml
  skills.fr.yaml / skills.en.yaml
```

Providers concernes :

```text
src/Public/Service/CvProvider.php
src/Public/Service/ExperienceProvider.php
src/Public/Service/ProjectProvider.php
```

Constat global :

- les fichiers Markdown ne sont pas lus par l'application ;
- les YAML sont les donnees publiees pour les pages publiques ;
- les providers PHP definissent l'ordre, les slugs et certains liens structurels ;
- les templates definissent une partie du schema attendu pour les YAML ;
- la vraie source de rendu est donc la combinaison `Provider PHP + YAML + Twig`.

## Modele Actuel Des Donnees

### Experiences

Structure actuelle :

- `ExperienceProvider` contient la liste des experiences, leur ordre, leur slug, leur cle de traduction, les annees et les technologies principales ;
- `translations/experiences.*.yaml` contient les textes publies ;
- les pages detail construisent les sections a partir de listes traduites ;
- les projets associes sont obtenus via `ProjectProvider`.

Contrat implicite :

```text
slug PHP
  -> translation_key PHP
  -> experiences.items.<translation_key> dans YAML
```

Points solides :

- l'ordre des experiences est explicite ;
- les cles FR/EN sont synchronisees ;
- les sections detail sont simples et stables ;
- les technologies principales restent hors traduction, ce qui evite de les dupliquer en FR/EN.

Points de vigilance :

- la periode courte du listing est codee dans PHP ;
- la periode detail est traduite dans YAML ;
- les dates existent donc a deux endroits ;
- le Markdown source peut diverger du YAML publie.

Incoherence concrete reperee :

```text
docs/pro_exp/experience_compilation.md
  Isobar : August 2017 - February 2018

translations/experiences.fr.yaml
  Isobar : juillet 2017 - fevrier 2018

translations/experiences.en.yaml
  Isobar : July 2017 - February 2018
```

Les traductions publiees sont coherentes avec la confirmation donnee pendant le travail precedent. Le Markdown source reste a aligner si ce dossier est considere comme source editoriale.

### Projets

Structure actuelle :

- `ProjectProvider` contient la liste des projets, leur ordre et leur experience associee ;
- `translations/projects.*.yaml` contient les cartes de listing et les fiches detail completes ;
- `templates/projects/detailed_project.html.twig` rend un schema YAML riche :
  - meta ;
  - sections ;
  - paragraphs ;
  - list ;
  - labeled_list ;
  - groups ;
  - subsections ;
  - flow ;
  - highlight.

Controle effectue :

```text
index.cards : 14 projets
details     : 14 projets
ecart       : aucun
```

Points solides :

- le systeme permet des pages projet riches sans multiplier les templates ;
- les cartes de listing et les details sont synchronises en nombre ;
- les metadonnees peuvent varier selon le type de projet ;
- les projets personnels ou transverses ne sont pas forces dans un schema trop rigide.

Points de vigilance :

- le schema de `projects.yaml` est puissant mais implicite ;
- le template doit connaitre toutes les formes possibles de section ;
- il n'y a pas de validation structurelle dediee ;
- l'ordre des projets est code dans PHP, pas dans YAML ;
- l'association projet/experience est codee dans PHP, pas dans YAML.

Metadonnees heterogenes observees :

```text
projets classiques :
  Periode, Role, Organisation, Stack principale

missions client recentes :
  Periode, Role, Organisation, Client, Stack principale

marge_delhaize :
  Periode, Role, Client, Stack principale

coaching :
  Periode, Role, Plateformes, Volume d'activite
```

Cette heterogeneite est pertinente editorialement. Elle ne doit pas etre corrigee automatiquement.

### Skills

Structure actuelle :

- `docs/pro_exp/skill_matrix.md` et `docs/pro_exp/soft_skills.md` servent de corpus source ;
- `translations/skills.*.yaml` contient les textes publies ;
- `templates/pages/skills.html.twig` contient plusieurs listes techniques directement en HTML.

Points solides :

- les textes narratifs sont traduits ;
- les libelles de sections sont dans YAML ;
- les technologies sont visibles et faciles a modifier dans le template.

Points de vigilance :

- certaines donnees de competence sont dans Twig, pas dans YAML ;
- si la page competences doit devenir plus maintenable, les listes techniques pourraient devenir des donnees structurees ;
- pour le moment, le volume reste acceptable.

### Home, About, Contact, Legal

Constat :

- ces pages utilisent principalement des domaines YAML dedies ;
- `home` contient quelques traductions rendues avec `|raw`, deja signale en phase 3 ;
- les pages legales sont longues mais leur contenu est bien centralise dans `legal.*.yaml`.

Point concret deja signale :

- `templates/pages/contact.html.twig` contient `mailto:contact@example.com`, qui semble etre un placeholder.

## Role Reel De docs/pro_exp

Le dossier `docs/pro_exp` contient le materiau editorial de fond, mais il n'est pas branche au rendu.

Aujourd'hui, son role ressemble a :

- archive source ;
- compilation longue ;
- base de redaction ;
- reference humaine pour enrichir les YAML.

Ce n'est pas un probleme en soi, mais il faut nommer ce role.

Risque principal :

- une correction peut etre appliquee dans les YAML publies sans etre reportee dans le Markdown ;
- ou inversement, une information peut etre corrigee dans le Markdown sans etre publiee.

Exemple deja observe :

- la periode Isobar a ete corrigee dans les YAML publies ;
- le Markdown garde l'ancienne valeur.

## Sources De Verite

### Source De Verite Actuelle

Pour le site affiche :

```text
Providers PHP + YAML de traduction + Templates Twig
```

Pour la redaction longue :

```text
docs/pro_exp/*.md
```

Le point important est que ces deux mondes ne sont pas synchronises automatiquement.

### Source De Verite Recommandee A Court Terme

Recommandation pragmatique :

```text
Les YAML restent la source de verite pour le contenu publie.
docs/pro_exp reste le corpus editorial de reference.
```

Condition :

- documenter explicitement que toute correction factuelle publiee doit aussi etre reportee dans `docs/pro_exp` quand elle concerne le parcours, les dates ou les projets.

Pourquoi ne pas tout basculer maintenant vers Markdown :

- les pages detail projets utilisent un schema structure riche ;
- les traductions FR/EN sont deja bien synchronisees ;
- les providers s'appuient sur des donnees structurelles simples ;
- convertir maintenant ajouterait plus de risque que de valeur immediate.

## Faut-Il Ajouter De Nouveaux Champs ?

Pas maintenant.

Les besoins actuels sont couverts par :

- les champs PHP pour l'ordre, les slugs, les technologies et les associations ;
- les YAML pour les textes publies ;
- les templates pour le rendu.

Ajouter une couche de fichiers structures dedies serait pertinent seulement si :

- les projets continuent de grossir fortement ;
- les fiches doivent etre editees souvent ;
- plusieurs pages doivent consommer les memes donnees ;
- une validation automatique devient necessaire ;
- la partie privee reutilise le meme modele de contenu.

## Validation Et Maintenance

Ce qui est deja correct :

- les YAML sont valides ;
- les cles FR/EN sont synchronisees ;
- les cartes et details projets sont alignes ;
- les structures experience FR/EN ont les memes nombres d'elements par section.

Ce qui manque :

- validation du schema `projects.yaml` ;
- validation des cles attendues par `ExperienceProvider` ;
- verification automatique que chaque projet associe a une experience pointe vers une experience existante ;
- verification automatique que chaque experience a ses textes traduits ;
- verification des divergences factuelles entre Markdown et YAML.

Recommandation legere :

- ajouter plus tard une commande de diagnostic interne, par exemple `app:audit-content`, si le contenu continue d'evoluer ;
- cette commande pourrait verifier les cles projets/experiences, les associations et les champs obligatoires ;
- ne pas l'ajouter tant que les changements restent ponctuels.

## Recommandations Priorisees

### Priorite 1 - Clarifier Le Workflow Editorial

- Decider officiellement que les YAML sont la source de verite du contenu publie.
- Traiter `docs/pro_exp` comme corpus editorial, pas comme donnees executees.
- Aligner `docs/pro_exp/experience_compilation.md` sur la periode Isobar confirmee.
- Corriger ou clarifier le fallback `home.meta.title` dans `base.html.twig`.
- Remplacer ou confirmer `mailto:contact@example.com`.

### Priorite 2 - Stabiliser Les Contrats

- Documenter le schema attendu pour `projects.yaml`.
- Documenter le contrat `ProjectProvider::PROJECTS` :
  - ordre ;
  - slug ;
  - experience associee.
- Documenter le contrat `ExperienceProvider::EXPERIENCES` :
  - slug ;
  - translation_key ;
  - technologies ;
  - periode courte.
- Garder les metadonnees projet flexibles.

### Priorite 3 - Automatiser Si Le Volume Augmente

- Ajouter une commande de diagnostic de contenu si les pages detail continuent de grossir.
- Extraire les listes techniques de `skills.html.twig` vers une structure de donnees seulement si elles changent souvent.
- Envisager un format donnees dedie pour les projets uniquement si le YAML devient trop difficile a maintenir.
- Ne pas convertir les Markdown en source applicative tant que le besoin n'est pas clair.

## Note De Reprise

```text
Phase 5 terminee.

Sources analysees :
- docs/pro_exp/experience_compilation.md
- docs/pro_exp/projects_compilation.md
- docs/pro_exp/skill_matrix.md
- docs/pro_exp/soft_skills.md
- translations/*.yaml
- src/Public/Service/CvProvider.php
- src/Public/Service/ExperienceProvider.php
- src/Public/Service/ProjectProvider.php
- templates projects/experiences/skills/home/base

Verifications :
- YAML valide.
- Cles FR/EN synchronisees.
- 14 cartes projets et 14 fiches detail projets.
- Structures experiences FR/EN coherentes.
- CV FR/EN presents dans public/files/cv.

Incoherences reperees :
- base.html.twig reference home.meta.title dans le domaine messages.
- docs/pro_exp/experience_compilation.md garde Isobar en August 2017 - February 2018.
- translations experiences indiquent bien Isobar en July/juillet 2017 - February/fevrier 2018.
- contact.html.twig contient encore mailto:contact@example.com.

Source de verite recommandee :
- YAML = contenu publie.
- Providers PHP = ordre, slugs, associations et donnees structurelles courtes.
- docs/pro_exp = corpus editorial de reference, a maintenir en coherence manuellement.

Actions possibles :
- Aligner le Markdown Isobar.
- Corriger le fallback title de base.html.twig.
- Confirmer/remplacer l'email de contact.
- Documenter le schema projects.yaml.
- Ajouter une commande de diagnostic de contenu si le volume augmente.

Phase suivante :
- Phase 6 - Audit Specifique DnD Initiative Tracker.
```
