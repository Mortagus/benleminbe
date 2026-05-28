# Audit Structure Et CSS De La Zone Privee

Date : 2026-05-28

Portee :

- Twig du layout prive et des pages `network` ;
- CSS de `assets/styles/private/private.css` ;
- aucun changement de logique PHP.

## Constat

La zone privee fonctionne, mais son rendu est trop uniforme et trop compact. Le shell ne pose pas encore une vraie hierarchy d'application, et les pages `network` reposent presque toutes sur le meme modele : un grand panel central, puis d'autres cards a l'interieur.

## Points Problematques

### 1. Shell prive trop minimal

`templates/private/base.html.twig` fournit seulement `body`, flashes et `main`. Il manque un `header` de zone, une navigation globale, une zone d'actions persistante et une structure plus lisible pour les ecrans authentifies.

### 2. Theme switcher absent du prive

Le theme system existe deja cote public, avec le composant et la logique JS. Le prive n'expose pas encore ce controle, donc l'utilisateur n'a pas de point d'entree pour regler l'apparence de l'espace prive.

### 3. Tout est en card

Les pages `dashboard`, `contacts`, `platforms`, `show`, `form` et `import` utilisent le meme empilement de cards. Le resultat est lourd visuellement et donne peu de respiration entre :

- le shell global ;
- les actions ;
- les syntheses ;
- les blocs metier.

### 4. Hiérarchie visuelle trop plate

La largeur fixe de `private-main` et la repetition des memes surfaces produisent un rendu tres compact. On a du mal a distinguer ce qui releve du cadre global et ce qui releve du contenu courant.

### 5. La navigation est locale uniquement

Le sous-menu `network` existe, mais il n'y a pas de navigation privee globale. L'utilisateur doit entrer dans chaque page pour retrouver le contexte, ce qui renforce l'effet de fragmentation.

### 6. Couleurs et contrastes incomplets

Les boutons principaux utilisent encore `var(--text)` sur `var(--accent)` alors qu'un token de contraste plus adapte existe deja. En theme sombre, le contraste devient insuffisant pour un CTA principal.

### 7. Bordures incoherentes

La feuille privee utilise `--color-border`, alors que le token definitif du projet est `--border`. Cela casse la cohérence des surfaces et renforce le sentiment de rendu brut.

### 8. Champs de filtre pas assez traites

Les `select` de la page contacts gardent un rendu trop natif. Ils devraient etre alignes avec le reste du design system prive.

### 9. Checkbox visuellement fragile

Le checkbox "Plateforme active" est pris dans la regle globale des `input`, qui lui donne une largeur pleine. Ce comportement n'est pas ideal pour ce type de champ et doit etre traite explicitement.

### 10. Login a isoler

La page de connexion ne devrait pas forcement suivre le meme shell que les pages authentifiees. Elle peut avoir un layout plus simple, sans navigation globale.

## Lecture Produit

Le probleme principal n'est pas seulement visuel. C'est un probleme de structure :

- le shell ne porte pas assez d'information ;
- les pages sont trop homogenees ;
- les cards servent de solution par defaut plutot que de composant secondaire.

## Plan De Refonte

1. Introduire un vrai header prive avec marque, navigation et theme switcher.
2. Decouper `private/base.html.twig` en fragments reutilisables.
3. Transformer `private-main` en zone de page plus souple, avec des sections distinctes.
4. Revoir les pages `dashboard`, `contacts`, `platforms` et `import` pour privilegier une structure par morceaux plutot qu'un grand bloc unique.
5. Releguer les cards aux contenus secondaires, aux donnees compactes ou aux vues detaillees.
6. Nettoyer les tokens CSS et les contrastes des actions.
7. Isoler la page de login dans un layout adapte.

## Priorite

Priorite haute :

- correction des tokens de bordure ;
- contraste des boutons ;
- ajout du header prive ;
- ajout du theme switcher.

Priorite moyenne :

- refonte des pages `network` avec plus de zones semantiques ;
- traitement plus propre des filtres et du formulaire.

Priorite basse :

- variantes visuelles avancees et embellissements secondaires.
