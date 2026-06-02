<?php

declare(strict_types=1);

namespace App\Public\Service;

use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Yaml\Yaml;

final class ProjectProvider
{
    private const array PROJECTS = [
        [
            'key' => 'raidgbs',
            'experience' => 'cbmn',
            'context' => 'research_data',
        ],
        [
            'key' => 'sogesa',
            'experience' => 'sogesa',
            'context' => 'business_systems',
        ],
        [
            'key' => 'publifund',
            'experience' => 'f2c',
            'context' => 'research_data',
        ],
        [
            'key' => 'easy4pro',
            'experience' => 'adneom',
            'context' => 'business_systems',
        ],
        [
            'key' => 'logic_immo',
            'experience' => 'adneom',
            'context' => 'commercial_platforms',
        ],
        [
            'key' => 'isobar',
            'experience' => 'isobar',
            'context' => 'agency_projects',
        ],
        [
            'key' => 'delcampe',
            'experience' => 'blubird',
            'context' => 'commercial_platforms',
        ],
        [
            'key' => 'keytrade',
            'experience' => 'blubird',
            'context' => 'access_auth',
        ],
        [
            'key' => 'famille_chretienne',
            'experience' => 'contraste-digital',
            'context' => 'content_institutional',
        ],
        [
            'key' => 'stanhome',
            'experience' => 'contraste-digital',
            'context' => 'commercial_platforms',
        ],
        [
            'key' => 'his',
            'experience' => 'contraste-digital',
            'context' => 'content_institutional',
        ],
        [
            'key' => 'moveit',
            'experience' => 'contraste-digital',
            'context' => 'access_auth',
        ],
        [
            'key' => 'marge_delhaize',
            'experience' => null,
            'context' => 'research_data',
        ],
        [
            'key' => 'coaching',
            'experience' => null,
            'context' => 'coaching',
        ],
    ];

    /**
     * @var list<array{key: string, translation_key: string}>
     */
    private const array PROJECT_CONTEXTS = [
        [
            'key' => 'research_data',
            'translation_key' => 'research_data',
        ],
        [
            'key' => 'business_systems',
            'translation_key' => 'business_systems',
        ],
        [
            'key' => 'commercial_platforms',
            'translation_key' => 'commercial_platforms',
        ],
        [
            'key' => 'content_institutional',
            'translation_key' => 'content_institutional',
        ],
        [
            'key' => 'access_auth',
            'translation_key' => 'access_auth',
        ],
        [
            'key' => 'agency_projects',
            'translation_key' => 'agency_projects',
        ],
        [
            'key' => 'coaching',
            'translation_key' => 'coaching',
        ],
    ];

    /**
     * @var array<string, array<string, mixed>>
     */
    private array $projectTranslationsByLocale = [];

    public function __construct(
        #[Autowire('%kernel.project_dir%')]
        private readonly string $projectDir,
    ) {
    }

    /**
     * @return list<array{key: string, experience: string|null, context: string}>
     */
    public function getProjects(): array
    {
        return self::PROJECTS;
    }

    /**
     * @return list<string>
     */
    public function getProjectKeys(): array
    {
        return array_map(
            static fn (array $project): string => $project['key'],
            self::PROJECTS,
        );
    }

    public function findProjectIndex(string $project): int
    {
        foreach (self::PROJECTS as $index => $projectConfig) {
            if ($projectConfig['key'] === $project) {
                return $index;
            }
        }

        throw new NotFoundHttpException(sprintf('Project "%s" not found.', $project));
    }

    /**
     * @return array<string, mixed>
     */
    public function getProjectData(string $project, string $locale): array
    {
        $translations = $this->getProjectTranslations($locale);

        if (!isset($translations[$project]) || !is_array($translations[$project])) {
            throw new NotFoundHttpException(sprintf('Project translation data "%s" not found.', $project));
        }

        return $translations[$project];
    }

    /**
     * @return list<array{key: string, title: string, description: string}>
     */
    public function getProjectsByExperience(string $experience, string $locale): array
    {
        $translations = $this->getProjectTranslations($locale);
        $projects = [];

        foreach (self::PROJECTS as $project) {
            if ($project['experience'] !== $experience) {
                continue;
            }

            $projects[] = $this->getProjectCardData($project['key'], $translations);
        }

        return $projects;
    }

    /**
     * @return list<array{
     *     key: string,
     *     title: string,
     *     description: string,
     *     projects: list<array{key: string, title: string, description: string}>
     * }>
     */
    public function getProjectsByContext(string $locale): array
    {
        $translations = $this->getProjectTranslations($locale);
        $projectBuckets = [];

        foreach (self::PROJECT_CONTEXTS as $context) {
            $projectBuckets[$context['key']] = [];
        }

        foreach (self::PROJECTS as $project) {
            if (!array_key_exists($project['context'], $projectBuckets)) {
                throw new NotFoundHttpException(sprintf('Project context "%s" not found for project "%s".', $project['context'], $project['key']));
            }

            $projectBuckets[$project['context']][] = $this->getProjectCardData($project['key'], $translations);
        }

        $projectGroups = [];

        foreach (self::PROJECT_CONTEXTS as $context) {
            $contextTranslations = $translations['index']['contexts'][$context['translation_key']] ?? null;

            if (!is_array($contextTranslations)) {
                throw new NotFoundHttpException(sprintf('Project context translation data "%s" not found.', $context['key']));
            }

            $projectGroup = [
                'key' => $context['key'],
                'title' => $contextTranslations['title'] ?? $context['key'],
                'description' => $contextTranslations['description'] ?? '',
                'projects' => $projectBuckets[$context['key']],
            ];

            $projectGroups[] = $projectGroup;
        }

        return $projectGroups;
    }

    public function getExperienceForProject(string $project): ?string
    {
        foreach (self::PROJECTS as $projectConfig) {
            if ($projectConfig['key'] === $project) {
                return $projectConfig['experience'];
            }
        }

        throw new NotFoundHttpException(sprintf('Project "%s" not found.', $project));
    }

    /**
     * @return array{key: string, experience: string|null}|null
     */
    public function getPreviousProject(string $project): ?array
    {
        return $this->getAdjacentProject($this->findProjectIndex($project), -1);
    }

    /**
     * @return array{key: string, experience: string|null}|null
     */
    public function getNextProject(string $project): ?array
    {
        return $this->getAdjacentProject($this->findProjectIndex($project), 1);
    }

    /**
     * @return array{key: string, experience: string|null}|null
     */
    private function getAdjacentProject(int $projectIndex, int $offset): ?array
    {
        return self::PROJECTS[$projectIndex + $offset] ?? null;
    }

    /**
     * @param array<string, mixed> $translations
     *
     * @return array{key: string, title: string, description: string}
     */
    private function getProjectCardData(string $projectKey, array $translations): array
    {
        $cardTranslations = $translations['index']['cards'][$projectKey] ?? null;

        if (!is_array($cardTranslations)) {
            throw new NotFoundHttpException(sprintf('Project card translation data "%s" not found.', $projectKey));
        }

        return [
            'key' => $projectKey,
            'title' => $cardTranslations['title'] ?? $projectKey,
            'description' => $cardTranslations['description'] ?? '',
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function getProjectTranslations(string $locale): array
    {
        if (isset($this->projectTranslationsByLocale[$locale])) {
            return $this->projectTranslationsByLocale[$locale];
        }

        $translationFile = sprintf(
            '%s/translations/projects.%s.yaml',
            $this->projectDir,
            $locale,
        );

        if (!is_file($translationFile)) {
            throw new NotFoundHttpException(sprintf('Project translation file "%s" not found.', $translationFile));
        }

        $translations = Yaml::parseFile($translationFile);

        if (!is_array($translations)) {
            throw new NotFoundHttpException(sprintf('Project translation file "%s" is invalid.', $translationFile));
        }

        $this->projectTranslationsByLocale[$locale] = $translations;

        return $translations;
    }
}
