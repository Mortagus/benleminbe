# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

Personal site `benlemin.be` (Symfony 8, PHP 8.4, Twig, Asset Mapper, vanilla JS). `AGENTS.md` is the canonical project instruction file (in French) and lists the docs to read at the start of a session (`README.md`, `docs/documentation-index.md`, `docs/project-architecture.md`, `docs/development-workflow.md`, ...). Follow it; the points below are the ones that matter most.

## Commands

The Makefile is named `makefile` (lowercase).

```bash
make check            # main verification: composer validate, php -l, lint:yaml, lint:twig, lint:container, stylelint, eslint, vitest
make serv             # symfony local:server:start
make reload_assets    # php bin/console asset-map:compile
make cc               # reload_assets + cache:clear (needed to see compiled asset changes)
make test_php         # creates test DB, runs migrations, runs PHPUnit (needs MariaDB: docker compose up -d db)
make db-check         # verifies the docker `db` service is reachable
npm run lint:md       # Markdown lint (disabled inside make check; run it for doc changes)
```

Single tests:

```bash
./vendor/bin/phpunit --configuration phpunit.xml.dist tests/Unit/Public/SomeTest.php
./vendor/bin/phpunit --configuration phpunit.xml.dist --filter testName
npx vitest run tests/js/lab/some.test.js
```

`make install-hooks` enables the versioned `pre-commit` hook, which runs `make check` on every commit. `make deploy` is the production routine (pull, composer --no-dev, prod migrations, cache warmup, asset compile, sitemap generation, `private-prod-check`).

## Architecture

The site is split into "universes" (Pro, Lab, Games, Private; Articles reserved), see `docs/site-universes-and-navigation.md`.

- `src/Public/` : public site. Controllers are thin (`Home`, `Page`, `Experiences`, `Projects`, `BusinessCard`, `Lab`, `Games`, `Tracking`). `Public/Service/` holds providers (`CvProvider`, `ExperienceProvider`, `ProjectProvider`) and `Dnd/` (DnD Initiative Tracker logic, with sample data in `tests/fixtures/dnd` and tooling in `tools/dnd/`).
- `src/Private/` : authenticated zone (own `Security/`, plus `Music`, `Workout`, `Service/Network`, contacts/network dashboards). Backed by Doctrine entities (`src/Entity/Private`, `src/Entity/Network`) and `migrations/` on MariaDB (`DATABASE_URL` in `.env`, docker-compose `db` service). Admin secret handling: `docs/private/private-security-recommendations.md`.
- Public content has no database: it comes from Symfony translation YAML. **`translations/*.fr.yaml` / `*.en.yaml` are the runtime source of truth**; `docs/editorial/` is editorial source material that the app never reads. Every public text needs both FR and EN. Long texts can use `**keyword**` markers rendered by the `highlight_keywords` Twig filter (`src/Twig/TextHighlightExtension.php`); see `docs/content-workflow.md`.
- Front-end: `assets/app.js` is the common bundle; per-page entrypoints are in `assets/pages/*.js` and registered in `importmap.php`. CSS lives in `assets/styles/` (bases, components, layouts, pages, plus `lab/`, `games/`, `private/`). No JS framework. JS tests are in `tests/js/` (Vitest); PHP tests in `tests/Unit` and `tests/Functional` (PHPUnit).
- Sitemap is generated from routes by `app:generate-sitemap` (`GenerateSitemapCommand`). `PageController` carries `lastmod` attributes to update when a page changes.

## Workflow rules

- Task tracking uses Backlog.md via MCP (see the `AGENTS.md` critical block and `docs/development-workflow.md`): read `backlog://workflow/overview` before creating or working on tasks; don't edit `.backlog/` files by hand. Create a task for significant work only. Active work docs go in `docs/en-cours/`, finished ones in `docs/termines/`.
- Run `make check` after any significant application or documentation change.
- Prefer existing conventions and native Symfony components; no speculative abstractions (SOLID applied pragmatically). Keep diffs focused; no opportunistic refactors.
- Playwright screenshots go in `.playwright-mcp/`, not the repo root.
- Project skills live in `.agents/skills/` (`requirements-analyst`, `symfony-developer`, `code-reviewer`).
- Language: code, comments and commits in English; project documentation (README, docs) in French.
