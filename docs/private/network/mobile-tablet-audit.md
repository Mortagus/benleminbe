# Audit Mobile Et Tablette - Zone Privee Réseau

Date : 2026-05-28

Portee :

- pages `contacts` et `platforms` du module `private/network` ;
- formulaires de creation / edition ;
- listings de contacts et de plateformes ;
- comportement visuel sur mobile et tablette.

## Constat General

Le socle prive est lisible sur desktop, mais la version mobile / tablette reste trop proche du layout grand ecran. Les formulaires et les listings n'ont pas encore de strategie de rendu reellement adaptee aux petits ecrans.

Le probleme principal n'est pas seulement la largeur. C'est aussi la densite d'information :

- les formulaires gardent trop de champs simultanement dans une grille encore trop desktop ;
- les listings conservent des colonnes et des metadonnees qui prennent trop de place sur petit ecran ;
- certaines informations secondaires devraient etre masquees ou deplacees dans une variante mobile.

## Findings

### 1. Les formulaires repliés restent trop larges pour les petits ecrans

Dans [assets/styles/private/private.css](/var/www/projects/benleminbe/assets/styles/private/private.css#L369), la grille de formulaire utilise `repeat(auto-fit, minmax(15.5rem, 1fr))`.

Effet observe :

- sur certaines largeurs de tablette, la grille conserve deux colonnes au lieu de tomber franchement en une colonne ;
- les champs prennent encore beaucoup de place verticale et horizontale ;
- le rendu reste trop "formulaire bureau" pour une saisie mobile.

Impact :

- saisie moins confortable ;
- impression de formulaire trop dense ;
- perte de lisibilite sur les appareils etroits.

### 2. Les listings restent structures comme des tableaux desktop

Les pages [templates/private/network/contacts/index.html.twig](/var/www/projects/benleminbe/templates/private/network/contacts/index.html.twig#L57) à [#L123](/var/www/projects/benleminbe/templates/private/network/contacts/index.html.twig#L123) et [templates/private/network/platforms/index.html.twig](/var/www/projects/benleminbe/templates/private/network/platforms/index.html.twig#L36) à [#L91](/var/www/projects/benleminbe/templates/private/network/platforms/index.html.twig#L91) utilisent toujours des `table` classiques.

Effet observe :

- les colonnes prennent beaucoup d'espace ;
- les lignes de tableau sont trop chargees sur mobile ;
- l'utilisateur doit lire trop d'informations dans une seule ligne.

Impact :

- lecture penible sur ecran mobile ;
- risque de debordement horizontal ou de compression visuelle ;
- hierarchie peu adaptee a une consultation rapide en mobilite.

### 3. Les cartes de liste sont trop riches en contenu pour une vue compacte

Dans les pages `dashboard` et surtout dans les listings, les items affichent encore plusieurs niveaux d'information en une seule ligne :

- nom ;
- sous-texte ;
- badges ;
- actions.

Sur mobile, cela donne des blocs trop hauts, meme quand ils restent techniquement corrects.

Impact :

- scrolling excessif ;
- sensation d'ecran lourd ;
- difficulte a reperer rapidement l'element utile.

### 4. Certaines informations devraient etre masquees ou differees sur mobile

Pour le mobile, tout ne merite pas d'etre visible immediatement.

Exemples :

- dans les listings de contacts, le role et certaines metadonnees peuvent devenir secondaires ;
- dans les listings de plateformes, la categorie ou la note courte peuvent etre deplacees derriere un mode details ;
- les badges multiples devraient etre reduits a un seul indicateur prioritaire.

Impact :

- meilleur rythme de lecture ;
- moins de hauteur par item ;
- focalisation sur l'information actionnable.

### 5. Le breakpoint actuel ne suffit pas a redefinir l'ergonomie

Le CSS possede deja un breakpoint mobile dans [assets/styles/private/private.css](/var/www/projects/benleminbe/assets/styles/private/private.css#L506), mais il traite surtout :

- les largeurs globales ;
- les colonnes principales ;
- le header et certains conteneurs.

Il ne reconfigure pas encore le mode de lecture des pages `contacts` et `platforms`.

Impact :

- on obtient une version retrecie du desktop, pas une vraie version mobile ;
- les formulaires sont moins adaptes ;
- les listings restent trop denses.

## Lecture Produit

Le besoin n'est pas seulement de "retrecir" l'interface.
Il faut probablement distinguer deux experiences :

- desktop et tablette large : vue complete ;
- mobile / tablette etroite : vue simplifiee, plus verticale, avec moins de champs visibles d'un coup.

## Recommandation

### Priorite 1

Introduire une variante mobile explicite pour les listings :

- transformer les tableaux en listes verticales ou cartes compactes ;
- ne garder que deux ou trois informations majeures par item ;
- deplacer le reste derriere un lien "voir" ou une vue detail.

### Priorite 2

Simplifier les formulaires en mobile / tablette etroite :

- rendre les champs mono-colonne plus tot ;
- alleger l'espace vertical entre labels et champs ;
- eviter que les champs "wide" donnent une fausse impression de largeur utile.

### Priorite 3

Reduire la densite des metadonnees :

- un seul badge important par ligne sur mobile ;
- masquer les metadonnees secondaires ;
- garder les actions visibles, mais compactes.

## Direction Concrète Recommandee

Pour la suite, la meilleure approche n'est probablement pas un simple `media query` de plus.

Il faut plutot :

1. definir une version mobile des listings en Twig/CSS ;
2. utiliser des cartes compactes ou une liste d'items simplifiee ;
3. garder la vue tableau uniquement pour desktop / tablette large ;
4. reduire les formulaires en une colonne plus tot ;
5. masquer certaines informations secondaires sur petit ecran.

## Conclusion

Le probleme mobile / tablette est reel et il ne se limite pas aux formulaires. Les listings restent trop proches du layout desktop, ce qui explique l'impression de surcharge.

Le prochain travail utile est une refonte ciblee du rendu mobile des pages `contacts` et `platforms`, avec une version plus compacte des listings et des formulaires.
