# Content Workflow And Contracts

This document describes the current source of truth for published content and the lightweight contracts used by the public site.

## Source Of Truth

Published content lives in the Symfony translation YAML files:

```text
translations/*.fr.yaml
translations/*.en.yaml
```

The files in `docs/editorial/` are editorial source material. They are useful to draft, review and compare professional content, but they are not read by the application at runtime.

Practical rule:

- update YAML when the public website must change;
- update `docs/editorial/` when the editorial corpus should stay aligned;
- when both exist for the same information, the YAML value is the source of truth for the website.

## Editorial Keyword Highlights

Long public texts can use a limited Markdown-like marker to improve scanability:

```text
This sentence highlights **one important idea** and leaves the rest normal.
```

The Twig filter `highlight_keywords` renders this marker as:

```html
<strong class="text-highlight">one important idea</strong>
```

Rules:

- use this only in visible body copy, summaries, card descriptions and list items;
- do not use it in meta titles, meta descriptions, route labels, button labels or legal identifiers;
- prefer one highlighted idea per paragraph or list item;
- highlight meaningful phrases, not isolated filler words;
- keep French and English highlights broadly aligned;
- render highlighted translations with `|highlight_keywords`;
- render any highlighted value reused as metadata with `|strip_keyword_highlights`.

## Project Content Contract

Project content is split between:

- `translations/projects.fr.yaml`;
- `translations/projects.en.yaml`;
- `src/Public/Service/ProjectProvider.php`.

There is no standalone `projects.yaml` file at the moment. When the audit mentions the `projects.yaml` schema, it refers to the project content schema currently implemented in `translations/projects.*.yaml`.

`translations/projects.*.yaml` contains all public text for the project index and project detail pages.

Top-level keys expected by the templates:

```text
project
index
<project_key>
```

`project` contains shared labels for project detail pages, such as navigation labels and associated experience labels.

`index` contains:

- `meta_title`;
- `meta_description`;
- `eyebrow`;
- `title`;
- `lead`;
- `contexts_intro`;
- `contexts`;
- `cards`.

`index.contexts_intro` contains the contextual introduction shown above the grouped listing. It should contain:

- `eyebrow`;
- `title`;
- `lead`;
- `nav_title`.

`index.contexts.<context_key>` is used for the grouped project listing on the projects index page. Each context entry should contain:

- `title`;
- `description`.

`index.cards.<project_key>` is used for project cards and project associations on experience detail pages. Each card should contain:

- `title`;
- `description`.

Each `<project_key>` detail entry should contain:

- `meta_title`;
- `eyebrow`;
- `title`;
- `lead`;
- `meta`;
- `sections`;
- `back`.

The current detail sections support these fields:

- `title`;
- `paragraphs`;
- `list`;
- `groups`;
- `highlight`.

`meta` entries should contain:

- `label`;
- `value`.

`groups` entries should contain:

- `title`;
- `items`.

The project keys must stay synchronized between FR and EN files.

## ProjectProvider Contract

`ProjectProvider::PROJECTS` is the structural project registry.

It defines:

- the display order of projects;
- the project key used in routes and translation files;
- the associated experience slug, when a project belongs to an experience;
- the context key used by the grouped projects listing.

Shape:

```php
[
    'key' => 'project_key',
    'experience' => 'experience-slug-or-null',
    'context' => 'context-key',
]
```

Rules:

- `key` must exist as a top-level key in both `translations/projects.fr.yaml` and `translations/projects.en.yaml`;
- `key` should also exist under `index.cards` in both locales;
- `experience` must be either `null` or a slug present in `ExperienceProvider::EXPERIENCES`;
- `context` must be a key present under `index.contexts` in both locales;
- order in `PROJECTS` is used by project pagination.

## ExperienceProvider Contract

`ExperienceProvider::EXPERIENCE_ORDER` defines experience pagination order.

`ExperienceProvider::EXPERIENCES` defines the structural registry for professional experiences.

Shape:

```php
[
    'slug' => 'experience-slug',
    'translation_key' => 'experience_translation_key',
    'period' => 'short period for summaries',
    'start_year' => 2020,
    'end_year' => 2024,
    'technologies' => ['PHP', 'Symfony'],
]
```

Rules:

- the array key and `slug` should match;
- each slug in `EXPERIENCE_ORDER` must exist in `EXPERIENCES`;
- `translation_key` must exist under `experiences.items` in both `translations/experiences.fr.yaml` and `translations/experiences.en.yaml`;
- `period` is the short period used by summaries;
- the translated `period` under `experiences.items.<translation_key>.period` is used by the detail page;
- `technologies` is structural data and is not translated.

Expected translation fields for each experience:

- `company`;
- `role`;
- `period`;
- `meta_title`;
- `summary`;
- `context`;
- `responsibilities`;
- `takeaways`.

`context`, `responsibilities` and `takeaways` are numbered translation lists. The provider reads them from `1` upward and stops at the first missing key.

## Maintenance Checklist

When adding or editing a project:

- update both `translations/projects.fr.yaml` and `translations/projects.en.yaml`;
- add or update `ProjectProvider::PROJECTS`;
- verify the project card exists in `index.cards`;
- verify the project detail top-level key exists;
- verify the associated experience slug, if any.

When adding or editing an experience:

- update both `translations/experiences.fr.yaml` and `translations/experiences.en.yaml`;
- add or update `ExperienceProvider::EXPERIENCES`;
- update `ExperienceProvider::EXPERIENCE_ORDER` if pagination order changes;
- verify associated projects in `ProjectProvider::PROJECTS`.

Useful checks:

```bash
php bin/console lint:yaml translations
php bin/console lint:twig templates
```
