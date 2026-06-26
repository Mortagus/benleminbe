# Documentation De La Zone Privee

Ce dossier regroupe la documentation stable de la zone privee du site `benlemin.be`.

La zone privee fait partie de l'univers global du site, mais elle conserve sa propre navigation et ne doit pas être confondue avec la partie publique professionnelle. Le découpage général des univers est décrit dans [Univers du site et navigation](../site-universes-and-navigation.md).

Les actions `Import` et `Revue des doublons` relèvent du flux Contacts et doivent rester contextuelles, pas devenir des entrées principales de navigation.

## Points D'Entree

- [Recommandations de sécurité](private-security-recommendations.md) : strategie de gestion du secret admin prive.
- [Passkeys de la zone privee](private-passkeys.md) : architecture WebAuthn, flux de login et recuperation manuelle.
- [Prochaines étapes](private-next-steps.md) : note de reprise et suivi des suites a donner a la zone privee.
- [Réseau privé](network/network-index.md) : documentation du premier outil prive `Contacts et reseau`.
- [Musique](music/music-index.md) : documentation du module prive d'analyse de l'historique Spotify.

La fonctionnalité d'import des contacts est traitée comme une action contextuelle du module réseau, pas comme une entrée de navigation de premier niveau.

## Organisation

```text
docs/private/
├── private-area-index.md
├── private-security-recommendations.md
├── private-passkeys.md
├── private-next-steps.md
├── music/
│   ├── music-index.md
│   └── music-listening-history-specification.md
└── network/
    ├── network-index.md
    ├── network-vision.md
    ├── network-besoin-analysis.md
    ├── network-mvp-specification.md
    ├── contact-write-matching-rules.md
    ├── merge-review-scoring-rules.md
    ├── contact-auto-merge-rules.md
    ├── network-ui-structure-audit.md
    └── network-responsive-audit.md
```

## Regle De Rangement

- Utiliser ce dossier pour tout document qui concerne directement la zone privee.
- Conserver les notes de travail actives ici tant qu'elles appartiennent encore au sous-domaine prive.
- Garder les documents de suivi generaux dans `docs/en-cours/` seulement s'ils ne sont pas specifiques a la zone privee.
