# Audit Lighthouse Production - 2026-05-21

Date d'audit : 2026-05-21.

Outil : Lighthouse CLI 12.8.2 avec Google Chrome 148.0.7778.178.

Perimetre : audit mobile Lighthouse sur `https://benlemin.be`.

Rapports generes :

- `var/audits/lighthouse/prod-home-fr.report.html`
- `var/audits/lighthouse/prod-projects-fr.report.html`
- `var/audits/lighthouse/prod-experiences-fr.report.html`
- `var/audits/lighthouse/prod-contact-fr.report.html`
- `var/audits/lighthouse/prod-home-fr-after-cache.report.html`
- `var/audits/lighthouse/prod-projects-fr-after-cache.report.html`
- `var/audits/lighthouse/prod-experiences-fr-after-cache.report.html`
- `var/audits/lighthouse/prod-contact-fr-after-cache.report.html`

Les rapports JSON correspondants sont disponibles dans le meme dossier.

## Pages Auditees

| Page | URL production |
| --- | --- |
| Accueil FR | `https://benlemin.be/fr` |
| Projets FR | `https://benlemin.be/fr/projects` |
| Experiences FR | `https://benlemin.be/fr/experiences` |
| Contact FR | `https://benlemin.be/fr/contact` |

## Scores

| Page | Performance | Accessibilite | Bonnes pratiques | SEO | FCP | LCP | TBT | CLS | Speed Index |
| --- | ---: | ---: | ---: | ---: | ---: | ---: | ---: | ---: | ---: |
| Accueil FR | 100 | 100 | 100 | 100 | 1.0 s | 1.0 s | 0 ms | 0 | 1.0 s |
| Projets FR | 100 | 100 | 100 | 100 | 1.0 s | 1.0 s | 0 ms | 0 | 1.0 s |
| Experiences FR | 100 | 100 | 100 | 100 | 0.9 s | 0.9 s | 0 ms | 0 | 0.9 s |
| Contact FR | 100 | 100 | 100 | 100 | 1.0 s | 1.0 s | 0 ms | 0 | 1.0 s |

## Verification Des En-Tetes HTTP

Commandes utilisees :

```bash
curl -sI https://benlemin.be/fr
curl -sI https://benlemin.be/fr/projects
curl -sI https://benlemin.be/assets/manifest.json
curl -sI https://benlemin.be/assets/styles/app-O-xrRXy.css
curl -sI https://benlemin.be/assets/app-4uIkO32.js
curl -sI -H 'Accept-Encoding: br, gzip' https://benlemin.be/fr
curl -sI -H 'Accept-Encoding: br, gzip' https://benlemin.be/assets/styles/app-O-xrRXy.css
```

Constats :

- les pages publiques testees ne renvoient pas `X-Robots-Tag: noindex` ;
- l'alerte SEO locale etait donc liee a l'environnement local ;
- les pages HTML renvoient `Cache-Control: no-cache, private` ;
- l'asset CSS versionne teste renvoie `Content-Encoding: gzip` quand `Accept-Encoding: br, gzip` est envoye ;
- les assets versionnes testes ne renvoient pas de `Cache-Control` long explicite ;
- le serveur renvoie `Strict-Transport-Security: max-age=16000000`.

## Alertes Lighthouse Residuelles

Malgre les scores a 100, Lighthouse signale encore des opportunites non bloquantes :

- `uses-long-cache-ttl` sur les quatre pages : 20 ressources trouvees ;
- `cache-insight` sur les quatre pages : economies estimees de 12 a 13 KiB ;
- `render-blocking-insight` sur les quatre pages ;
- `network-dependency-tree-insight` sur les quatre pages ;
- `max-potential-fid` a 130 ms sur l'accueil et les projets.

Lecture :

- les scores production valident les corrections accessibilite, SEO et performance ;
- le sujet cache reste une optimisation serveur utile, mais sans impact visible sur les scores actuels ;
- le rendu bloquant residuel est acceptable avec les mesures actuelles ;
- aucune correction applicative urgente n'est identifiee par cet audit production.

## Mise En Cache Des Assets Versionnes

Une regle a ete ajoutee dans `public/.htaccess` pour cibler uniquement les fichiers fingerprintes :

```apache
<FilesMatch ".+-[A-Za-z0-9_-]{7,}\.(?:css|js|mjs|png|jpe?g|gif|svg|webp|avif|ico|woff2?)$">
```

Objectif :

- ajouter `Cache-Control: public, max-age=31536000, immutable` sur les assets versionnes ;
- ajouter un `Expires` long via `mod_expires` si le module est disponible ;
- ne pas appliquer de cache long aux fichiers de mapping comme `manifest.json`, `importmap.json` ou `entrypoint.*.json` ;
- ne pas modifier le cache des pages HTML publiques ou privees.

Verification effectuee apres deploiement :

```bash
curl -sI https://benlemin.be/assets/styles/app-O-xrRXy.css
curl -sI https://benlemin.be/assets/app-4uIkO32.js
curl -sI https://benlemin.be/assets/manifest.json
curl -sI https://benlemin.be/assets/importmap.json
curl -sI https://benlemin.be/assets/entrypoint.app.json
curl -sI https://benlemin.be/fr
curl -sI https://benlemin.be/private/login
```

Resultat :

- les fichiers CSS/JS fingerprintes renvoient `Cache-Control: public, max-age=31536000, immutable` ;
- les fichiers CSS/JS fingerprintes renvoient un `Expires` a 1 an ;
- les fichiers JSON de mapping ne recoivent pas de cache long ;
- la page HTML publique conserve `Cache-Control: no-cache, private` ;
- la page de login privee conserve un cache court prive.

## Verification Apres Cache

Date de verification : 2026-05-21.

Rapports generes :

- `var/audits/lighthouse/prod-home-fr-after-cache.report.html`
- `var/audits/lighthouse/prod-projects-fr-after-cache.report.html`
- `var/audits/lighthouse/prod-experiences-fr-after-cache.report.html`
- `var/audits/lighthouse/prod-contact-fr-after-cache.report.html`

| Page | Performance | Accessibilite | Bonnes pratiques | SEO | FCP | LCP | TBT | CLS | Speed Index | Cache TTL |
| --- | ---: | ---: | ---: | ---: | ---: | ---: | ---: | ---: | ---: | --- |
| Accueil FR | 100 | 100 | 100 | 100 | 0.9 s | 0.9 s | 50 ms | 0 | 0.9 s | 0 ressource signalee |
| Projets FR | 100 | 100 | 100 | 100 | 0.9 s | 0.9 s | 0 ms | 0 | 0.9 s | 0 ressource signalee |
| Experiences FR | 100 | 100 | 100 | 100 | 0.9 s | 0.9 s | 0 ms | 0 | 0.9 s | 0 ressource signalee |
| Contact FR | 100 | 100 | 100 | 100 | 0.9 s | 0.9 s | 0 ms | 0 | 0.9 s | 0 ressource signalee |

Points de controle :

- `uses-long-cache-ttl` : score 1 sur les quatre pages, avec 0 ressource signalee ;
- `cache-insight` : score 1 sur les quatre pages ;
- les scores Lighthouse restent a 100 sur les quatre categories ;
- le cycle Lighthouse local puis production est clos pour les pages auditees.

## Commandes Lighthouse Utilisees

Chrome headless a ete lance manuellement :

```bash
google-chrome-stable --headless=new --remote-debugging-port=9222 --ignore-certificate-errors --no-sandbox --disable-gpu --disable-crash-reporter --disable-crashpad --user-data-dir=/tmp/lighthouse-codex-profile-prod about:blank
```

Exemple de commande Lighthouse :

```bash
lighthouse https://benlemin.be/fr --hostname=127.000.000.001 --port=9222 --output=json --output=html --output-path=var/audits/lighthouse/prod-home-fr --quiet
```

Exemple de commande Lighthouse apres cache :

```bash
lighthouse https://benlemin.be/fr --hostname=127.000.000.001 --port=9223 --output=json --output=html --output-path=var/audits/lighthouse/prod-home-fr-after-cache --quiet
```

## Note De Reprise

Le cycle Lighthouse local, production, puis cache HTTP est clos pour les pages auditees.

Prochaine action recommandee :

```text
Reprendre les tests de production de la partie privee : secret admin cible, parcours login/logout, acces protege a /private, absence du sitemap et verification robots.txt.
```
