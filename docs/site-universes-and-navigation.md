# Univers Du Site Et Navigation

Ce document décrit le découpage actuel et cible du site `benlemin.be` en univers fonctionnels, ainsi que la logique de navigation associée.

L'objectif est de garder une navigation lisible, d'éviter un menu central surchargé et de permettre l'ajout progressif de nouveaux univers sans refonte brutale.

## Principes

### Un Site, Plusieurs Univers

Le site reste une seule application Symfony, mais il est organisé en univers de navigation distincts:

- `Pro` pour la partie professionnelle publique;
- `Lab` pour les expérimentations publiques;
- `Articles` pour les publications publiques à venir;
- `Games` pour les futurs petits jeux maison;
- `Private` pour la zone privée authentifiée.

Chaque univers doit pouvoir évoluer avec sa propre logique de navigation locale, tout en restant accessible depuis une navigation inter-univers discrète.

### Navigation Inter-Univers Discrète

La navigation inter-univers doit:

- rester secondaire visuellement;
- être présente partout où elle apporte un vrai raccourci;
- ne pas concurrencer la navigation locale de l'univers courant;
- rester simple à étendre quand un nouvel univers apparaît.

### Navigation Locale Prioritaire

Chaque univers doit conserver sa propre navigation principale, adaptée à ses écrans et à son public.

Exemple d'intention:

- `Pro` : `Accueil`, `Parcours`, `Projets`, `Compétences`, `Contact`;
- `Lab` : `Lab`, `DnD Initiative Tracker`, futurs outils publics;
- `Games` : `Jeux`, `Simon`, futurs petits jeux maison;
- `Private` : `Dashboard`, `Contacts`, `Plateformes`, `Import`, `Doublons`, futurs outils personnels.

## Carte Des Univers

| Univers    | Rôle                                                                 | Public cible                                       | Route racine                              | Navigation locale attendue               | Visibilité dans la navigation inter-univers |
| ---------- | -------------------------------------------------------------------- | -------------------------------------------------- | ----------------------------------------- | ---------------------------------------- | ------------------------------------------- |
| `Pro`      | Présence professionnelle, portfolio, contact et informations légales | Prospects, recruteurs, clients, visiteurs          | `/{_locale}` et pages publiques associées | Oui, navigation principale déjà en place | Oui, c'est l'univers de référence           |
| `Lab`      | Expérimentations publiques et prototypes utiles                      | Visiteurs curieux, moi-même, testeurs              | `/lab`                                    | Oui, à partir d'un shell dédié           | Oui, lien discret attendu depuis le Pro     |
| `Articles` | Publications publiques à venir                                       | Lecteurs, visiteurs, futurs abonnés                | `/articles` à créer plus tard             | Oui, à définir plus tard                 | Oui, réservé dès maintenant                 |
| `Games`    | Petits jeux maison en JavaScript                                     | Visiteurs curieux, usage personnel, démonstrations | `/{_locale}/games`                         | Oui, à partir d'un shell dédié           | Oui, lien discret attendu depuis le Pro     |
| `Private`  | Outils personnels authentifiés                                       | Moi-même                                           | `/private`                                | Oui, navigation privée dédiée            | Non depuis le public, accès réservé         |

## Univers Pro

### Rôle

Le `Pro` est la partie publique centrale du site. Il sert à présenter le profil, les expériences, les projets, les compétences, les pages de contact et les informations légales.

### Routes Publiques Existantes

- `app_home_redirect` -> `/`
- `app_home` -> `/{_locale}`
- `app_projects_index` -> `/{_locale}/projects`
- `app_projects_show` -> `/{_locale}/projects/{project}`
- `app_experiences_index` -> `/{_locale}/experiences`
- `app_experiences_show` -> `/{_locale}/experiences/{experience}`
- `app_skills` -> `/{_locale}/skills`
- `app_about` -> `/{_locale}/about`
- `app_contact` -> `/{_locale}/contact`
- `app_terms_and_conditions` -> `/{_locale}/terms-and-conditions`
- `app_privacy_policy` -> `/{_locale}/privacy-policy`
- `app_legal_notice` -> `/{_locale}/legal-notice`
- `app_card.fr` -> `/card`
- `app_card.en` -> `/en/card`
- `app_contact_vcard` -> `/contact/benjamin-lemin.vcf`

### Navigation Locale Actuelle

La navigation principale publique vit dans [templates/components/\_site_header.html.twig](/var/www/projects/benleminbe/templates/components/_site_header.html.twig).

Elle couvre:

- `Accueil`;
- `Projets`;
- `Expériences`;
- `Compétences`;
- `À propos`;
- `Contact`.

### Navigation Inter-Univers

La navigation inter-univers est rendue par [templates/shared/navigation/\_universes_nav.html.twig](/var/www/projects/benleminbe/templates/shared/navigation/_universes_nav.html.twig).

Elle expose maintenant:

- `Pro`;
- `Lab`;
- `Games`;
- `Privé`.

## Univers Lab

### Rôle

Le `Lab` regroupe les expérimentations publiques utiles, intégrées au même site mais séparées du contenu professionnel.

### Routes Publiques Existantes

- `app_lab_index` -> `/lab`
- `app_lab_dnd_initiative` -> `/lab/dnd-initiative`
- `app_lab_dnd_player_import` -> `/lab/dnd-initiative/import-player`
- `app_lab_game_simon` -> `/lab/game-simon` avec redirection permanente vers `app_games_simon`

### Navigation Locale Actuelle

Le Lab est rendu via [templates/lab/index.html.twig](/var/www/projects/benleminbe/templates/lab/index.html.twig) et via [templates/lab/dnd/initiative_tracker.html.twig](/var/www/projects/benleminbe/templates/lab/dnd/initiative_tracker.html.twig).

Sa navigation principale partagée vit dans [templates/shared/navigation/\_lab_nav.html.twig](/var/www/projects/benleminbe/templates/shared/navigation/_lab_nav.html.twig) et remplace le menu Pro dans le header lorsque l'univers courant est le Lab.

### Présence Dans La Navigation Inter-Univers

Le Lab doit devenir atteignable depuis le Pro via un lien discret.

### Jeux Retirés Du Lab

- Simon ne fait plus partie du Lab.
- L'ancienne URL `/lab/game-simon` redirige vers l'univers `Games`.

## Univers Articles

### Statut

Univers réservé pour des publications publiques futures.

### Route Racine Prévue

- `/articles`

### Etat Actuel

Aucune route n'existe encore pour cet univers.

### Intention De Navigation

Quand il sera créé, `Articles` devra avoir:

- une navigation locale propre;
- une présence discrète dans la navigation inter-univers;
- une séparation claire du contenu professionnel et du contenu éditorial.

## Univers Games

### Rôle

`Games` regroupe les petits jeux maison en JavaScript.

### Routes Publiques Existantes

- `app_games_index` -> `/{_locale}/games`
- `app_games_simon` -> `/{_locale}/games/simon`
- `app_games_index_redirect` -> `/games` avec redirection permanente vers la route localisée
- `app_games_simon_redirect` -> `/games/simon` avec redirection permanente vers la route localisée

### Navigation Locale Actuelle

La navigation locale vit dans [templates/shared/navigation/_games_nav.html.twig](/var/www/projects/benleminbe/templates/shared/navigation/_games_nav.html.twig).

Elle expose:

- `Jeux` / `Games`;
- `Simon`.

### Intention De Navigation

`Games` doit rester un shell dédié aux petits jeux, sans mélanger ses entrées avec le Lab ou le Pro.

## Univers Private

### Rôle

Le `Private` regroupe les outils personnels authentifiés.

Il ne doit pas être visible comme univers public, mais il doit rester cohérent avec le reste de l'application et avec la structure documentaire.

### Routes Publiques D'Accès

- `app_private_login` -> `/private/login`
- `app_private_logout` -> `/private/logout`

### Routes Authentifiées Existantes

- `app_private_dashboard` -> `/private`
- `app_private_network_index` -> `/private/network`
- `app_private_network_contacts` -> `/private/network/contacts`
- `app_private_network_contacts_merge_duplicates` -> `/private/network/contacts/merge-duplicates`
- `app_private_network_contact_new` -> `/private/network/contacts/new`
- `app_private_network_contact_show` -> `/private/network/contacts/{id}`
- `app_private_network_contact_edit` -> `/private/network/contacts/{id}/edit`
- `app_private_network_contact_delete` -> `/private/network/contacts/{id}/delete`
- `app_private_network_contact_interaction` -> `/private/network/contacts/{id}/interactions`
- `app_private_network_contact_mark_contacted` -> `/private/network/contacts/{id}/mark-contacted`
- `app_private_network_import` -> `/private/network/import`
- `app_private_network_contact_merge_reviews_index` -> `/private/network/contact-merge-reviews`
- `app_private_network_contact_merge_reviews_generate` -> `/private/network/contact-merge-reviews/generate`
- `app_private_network_contact_merge_reviews_purge_pending` -> `/private/network/contact-merge-reviews/purge-pending`
- `app_private_network_contact_merge_reviews_reset` -> `/private/network/contact-merge-reviews/reset`
- `app_private_network_contact_merge_reviews_show` -> `/private/network/contact-merge-reviews/{id}`
- `app_private_network_contact_merge_reviews_resolve` -> `/private/network/contact-merge-reviews/{id}/resolve`
- `app_private_network_contact_merge_reviews_ignore` -> `/private/network/contact-merge-reviews/{id}/ignore`
- `app_private_network_platforms` -> `/private/network/platforms`
- `app_private_network_platform_export` -> `/private/network/platforms/export`
- `app_private_network_platform_import` -> `/private/network/platforms/import`
- `app_private_network_platform_new` -> `/private/network/platforms/new`
- `app_private_network_platform_show` -> `/private/network/platforms/{slug}`
- `app_private_network_platform_edit` -> `/private/network/platforms/{slug}/edit`

### Navigation Locale Actuelle

La navigation privée actuelle vit dans [templates/private/\_header.html.twig](/var/www/projects/benleminbe/templates/private/_header.html.twig).

Elle expose:

- `Tableau de bord`;
- `Réseau`;
- `Contacts`;
- `Plateformes`.

La fonctionnalité d'import est spécifique aux contacts et reste accessible comme action contextuelle depuis la page `Contacts` et depuis le tableau de bord du réseau, mais elle n'est plus une entrée de premier niveau.

La revue des doublons suit la même logique: elle appartient au flux Contacts et reste accessible depuis la page `Contacts`, mais ne mérite pas une entrée autonome dans la navigation privée principale.

### Navigation Inter-Univers

La zone privée affiche aussi la navigation inter-univers partagée:

- `Pro`;
- `Lab`;
- `Games`;
- `Privé`.

Cette nav est rendue dans le shell privé principal, mais elle est volontairement masquée sur la page de connexion pour conserver un écran d'authentification plus sobre.

## Pages Actuellement Accessibles

### Public

- page d'accueil bilingue;
- pages `Projets`;
- pages `Expériences`;
- page `Compétences`;
- page `À propos`;
- page `Contact`;
- univers `Games` avec l'index et Simon, accessibles en `/fr/games`, `/en/games`, `/fr/games/simon` et `/en/games/simon`;
- pages légales;
- carte de visite web;
- téléchargement de CV;
- redirection racine vers la langue par défaut.

### Lab

- page d'accueil du Lab `/lab`;
- `DnD Initiative Tracker`;
- import XML de joueur pour le tracker.

### Games

- index `/games`;
- page Simon `/games/simon`;
- redirection 301 depuis `/lab/game-simon`.

### Private

- login;
- logout;
- tableau de bord privé;
- module `Contacts et reseau`;
- liste des contacts;
- fiche contact;
- création / édition / suppression de contact;
- import de contacts;
- revue des doublons;
- gestion des plateformes;
- import et export des plateformes.

## Pages Futures Prevues

### Articles

- page d'accueil d'articles;
- liste d'articles;
- page d'article;
- navigation de lecture.

### Games

- page d'accueil des jeux;
- listes ou tuiles de jeux;
- page d'un jeu;
- navigation locale orientée interaction.

### Private

- futurs outils personnels hors `network`;
- éventuelle organisation par sous-outils si le périmètre grandit.

## Règles Pour Ajouter Un Nouvel Univers

1. Réserver une route racine claire.
2. Créer un shell ou un layout dédié si la navigation locale le justifie.
3. Ajouter l'univers dans la navigation inter-univers discrète.
4. Documenter le rôle, le public et les routes dans ce document.
5. Créer un index documentaire si l'univers prend assez d'ampleur.
6. Garder les pages métier dans leur univers et ne pas les remonter artificiellement dans la navigation globale.

## Point De Lecture Recommandé

Quand une question porte sur la structure du site ou la façon d'y naviguer, ce document doit être lu avant de modifier les layouts Twig ou d'ajouter une nouvelle route d'univers.

## Contrat Twig Courant

La navigation du site s'appuie désormais sur des partials séparés:

- `templates/shared/navigation/_universe_nav.html.twig` pour le rendu générique des liens;
- `templates/shared/navigation/_pro_nav.html.twig` pour la navigation locale publique;
- `templates/shared/navigation/_private_nav.html.twig` pour la navigation locale privée;
- `templates/shared/navigation/_lab_nav.html.twig` pour la navigation locale du Lab, prête pour une utilisation future.

Les layouts racine exposent aussi des points d'extension vides pour la navigation inter-univers, afin de permettre l'évolution progressive du site sans refonte brutale.
