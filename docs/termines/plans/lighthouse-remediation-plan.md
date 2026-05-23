# Plan D'Action Lighthouse

Date de redaction : 2026-05-21

Source : [Audit Lighthouse - 2026-05-21](../audits/lighthouse-audit-2026-05-21.md)

Objectif : transformer les constats Lighthouse en corrections applicables, verifiables et priorisees.

## Objectif De Fin De Cycle

Le cycle de correction peut etre considere termine quand :

- les pages auditees localement repassent a 100 en accessibilite Lighthouse ;
- les contrastes signales par Lighthouse ont disparu sur les pages FR auditees ;
- la page experiences ne remonte plus `label-content-name-mismatch` ;
- les alertes SEO/cache/compression ont ete verifiees sur la production ;
- les optimisations CSS bloquantes ont ete classees comme action a faire ou a reporter avec justification.

## Lot 1 - Corriger Les Contrastes

Priorite : haute.

But :

- corriger les ratios insuffisants sans changer fortement l'identite visuelle du site ;
- garder une palette coherente en theme clair et sombre.

Constats Lighthouse :

- `#5aa3c7` sur fond blanc : ratio environ 2.8:1 ;
- `#6b7280` sur fond `#f8f1e4` : ratio environ 4.3:1.

Fichiers probables :

- `assets/styles/base/tokens.css`
- `assets/styles/pages/home.css`
- `assets/styles/pages/experiences.css`
- `assets/styles/pages/project-detail.css`
- `assets/styles/pages/contact.css`
- `assets/styles/pages/skills.css`
- `assets/styles/components/links.css`

Approche recommandee :

1. Introduire ou ajuster un token d'accent lisible sur fond clair.
2. Remplacer l'usage de `var(--accent-hover)` pour les textes normaux par une couleur plus foncee en theme clair.
3. Garder l'accent actuel pour les fonds, bordures ou effets non textuels si le rendu visuel le demande.
4. Corriger les textes gris sur fond `--bg-soft`, surtout dans les cartes de competences.
5. Verifier explicitement le theme sombre apres correction.

Elements a corriger :

- `.landing-page .text-highlight`
- `.project-tag`
- `.hero-section h2`
- `.experience-card__period`
- `.experience-card__link`
- `.project-associated-experience__eyebrow`
- `.project-associated-experience a`
- `.contact-card__action`
- paragraphes des cartes `.skills-highlight-grid article`
- liens generiques qui utilisent `var(--accent-hover)` sur fond blanc.

Decision technique a prendre :

- option A : foncer `--accent-hover` globalement en theme clair ;
- option B : ajouter un token explicite, par exemple `--accent-text`, et l'utiliser pour les textes ;
- option C : corriger uniquement les selecteurs signales.

Option recommandee :

- option B, car elle separe l'accent decoratif de l'accent textuel. Cela reduit le risque de modifier trop largement le rendu visuel.

Critere de validation :

- Lighthouse ne signale plus `color-contrast` sur les pages suivantes :
  - `/fr`
  - `/fr/projects/delcampe`
  - `/fr/experiences`
  - `/fr/skills`
  - `/fr/contact`

Commandes de verification :

```bash
make check
lighthouse https://127.0.0.1:8000/fr --hostname=127.000.000.001 --port=9222 --output=json --output=html --output-path=var/audits/lighthouse/home-fr-after-contrast --quiet
```

Repeter Lighthouse sur les pages concernees.

## Lot 2 - Corriger L'Accessibilite Des Cartes Experiences

Priorite : haute.

But :

- corriger `label-content-name-mismatch` sur la page experiences ;
- garder les cartes entierement cliquables ;
- ne pas degrader la comprehension pour les lecteurs d'ecran.

Constat Lighthouse :

- les liens `.experience-card-link` ont un `aria-label` court ;
- le texte visible complet de la carte n'est pas inclus dans le nom accessible.

Fichier concerne :

- `templates/experiences/index.html.twig`

Approche recommandee :

1. Supprimer l'`aria-label` sur `.experience-card-link` si le contenu visible donne deja un nom de lien suffisamment clair.
2. Verifier que le texte du lien reste comprehensible au lecteur d'ecran avec le contenu de la carte.
3. Garder l'`aria-label` de la liste de technologies si elle reste utile.

Alternative :

- construire un `aria-label` plus complet avec periode, entreprise, role et action.

Option recommandee :

- supprimer l'`aria-label` du lien de carte. Le contenu visible contient deja periode, entreprise, role, resume, technologies et action.

Critere de validation :

- Lighthouse ne signale plus `label-content-name-mismatch` sur `/fr/experiences` ;
- les cartes restent navigables au clavier ;
- le focus visible reste clair.

Commandes de verification :

```bash
make check
lighthouse https://127.0.0.1:8000/fr/experiences --hostname=127.000.000.001 --port=9222 --output=json --output=html --output-path=var/audits/lighthouse/experiences-fr-after-a11y --quiet
```

## Lot 3 - Refaire Une Passe Lighthouse Locale

Priorite : moyenne.

But :

- confirmer que les corrections des lots 1 et 2 produisent l'effet attendu ;
- eviter de melanger les problemes de production avec les problemes de code local.

Pages a relancer :

- `/fr`
- `/fr/projects`
- `/fr/projects/delcampe`
- `/fr/experiences`
- `/fr/skills`
- `/fr/contact`

Resultats attendus :

- accessibilite : 100 sur toutes les pages auditees ;
- performance : rester autour de 99-100 ;
- bonnes pratiques : 100 ;
- SEO local : peut rester a 63 si `x-robots-tag: noindex` est volontaire en local.

Livrable :

- ajouter une section "Verification apres corrections" dans `docs/termines/audits/lighthouse-audit-2026-05-21.md` ou creer un nouveau fichier date si les changements sont importants.

## Lot 4 - Decider Sur Les Ressources CSS Bloquantes

Priorite : basse.

But :

- eviter une optimisation prematuree ;
- ne traiter ce point que si l'audit local apres corrections montre encore un interet clair.

Constat Lighthouse :

- economie estimee locale : environ 100 a 300 ms.

Decision recommandee :

- reporter tant que les scores performance restent a 99-100 ;
- ne pas complexifier le chargement CSS sans benefice utilisateur clair.

Pistes possibles si le sujet devient pertinent :

- verifier que seules les entrees CSS utiles sont importees ;
- eviter les CSS de pages non utilisees dans le rendu initial ;
- garder Asset Mapper simple tant que le gain reste marginal.

Critere de validation si traite :

- pas de regression visuelle ;
- score performance au moins stable ;
- reduction mesuree du temps de rendu initial en production.

## Lot 5 - Verifier SEO, Cache Et Compression En Production

Priorite : moyenne, mais a faire avant toute conclusion SEO.

But :

- distinguer les alertes dues a l'environnement local des vrais problemes HTTP du site public.

Constats locaux a verifier :

- `is-crawlable` avec source `x-robots-tag: noindex` ;
- cache assets trop court ;
- compression texte absente ;
- latence document.

Commandes recommandees :

```bash
curl -I https://benlemin.be/fr
curl -I https://benlemin.be/fr/projects
curl -I https://benlemin.be/assets/manifest.json
```

Points de controle :

- les pages publiques indexables ne doivent pas renvoyer `X-Robots-Tag: noindex` ;
- les assets versionnes doivent avoir un cache long ;
- les reponses texte devraient etre compressees en gzip ou brotli en production ;
- les redirections HTTP vers HTTPS doivent etre propres.

Audit Lighthouse production :

```bash
lighthouse https://benlemin.be/fr --hostname=127.000.000.001 --port=9222 --output=json --output=html --output-path=var/audits/lighthouse/prod-home-fr --quiet
```

Pages production minimales :

- `https://benlemin.be/fr`
- `https://benlemin.be/fr/projects`
- `https://benlemin.be/fr/experiences`
- `https://benlemin.be/fr/contact`

Livrable :

- creer `docs/termines/audits/lighthouse-production-audit-YYYY-MM-DD.md` si les resultats different significativement du local.

Verification effectuee le 2026-05-21 :

- audit documente dans `docs/termines/audits/lighthouse-production-audit-2026-05-21.md` ;
- score production a 100 en performance, accessibilite, bonnes pratiques et SEO sur les quatre pages testees ;
- absence de `X-Robots-Tag: noindex` sur les pages publiques testees ;
- compression gzip confirmee sur l'asset CSS versionne teste ;
- absence initiale de `Cache-Control` long explicite sur les assets versionnes testes.

Verification cache effectuee apres deploiement le 2026-05-21 :

- cache long confirme sur les assets CSS/JS fingerprintes ;
- pas de cache long sur `manifest.json`, `importmap.json` et `entrypoint.app.json` ;
- pas de cache long sur les pages HTML publiques ou privees ;
- Lighthouse production apres cache : 100 sur les quatre categories pour les quatre pages testees ;
- `uses-long-cache-ttl` : 0 ressource signalee sur les quatre pages testees.

## Ordre D'Execution Recommande

1. Lot 1 : contrastes.
2. Lot 2 : cartes experiences.
3. Lot 3 : Lighthouse local apres corrections.
4. Lot 4 : decision sur CSS bloquant.
5. Lot 5 : verification production SEO/cache/compression.

## Checklist De Fin

- `make check` passe.
- Lighthouse local accessibilite a 100 sur les pages auditees.
- Plus aucune alerte `color-contrast` sur les pages corrigees.
- Plus aucune alerte `label-content-name-mismatch` sur `/fr/experiences`.
- Headers production verifies pour `X-Robots-Tag`, cache et compression.
- Les rapports HTML/JSON sont conserves dans `var/audits/lighthouse/`.
- La documentation d'audit est mise a jour avec les resultats apres correction.

## Note De Reprise

Prochaine action recommandee :

```text
Le cycle Lighthouse local, production, puis cache HTTP est clos pour les pages auditees. Reprendre les tests de production de la partie privee.
```
