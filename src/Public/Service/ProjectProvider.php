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
        ],
        [
            'key' => 'sogesa',
            'experience' => 'sogesa',
        ],
        [
            'key' => 'publifund',
            'experience' => 'f2c',
        ],
        [
            'key' => 'easy4pro',
            'experience' => 'adneom',
        ],
        [
            'key' => 'logic_immo',
            'experience' => 'adneom',
        ],
        [
            'key' => 'isobar',
            'experience' => 'isobar',
        ],
        [
            'key' => 'delcampe',
            'experience' => 'blubird',
        ],
        [
            'key' => 'keytrade',
            'experience' => 'blubird',
        ],
        [
            'key' => 'famille_chretienne',
            'experience' => 'contraste-digital',
        ],
        [
            'key' => 'stanhome',
            'experience' => 'contraste-digital',
        ],
        [
            'key' => 'his',
            'experience' => 'contraste-digital',
        ],
        [
            'key' => 'moveit',
            'experience' => 'contraste-digital',
        ],
        [
            'key' => 'marge_delhaize',
            'experience' => null,
        ],
        [
            'key' => 'coaching',
            'experience' => null,
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
     * @return list<array{key: string, experience: string|null}>
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

            $projectKey = $project['key'];
            $cardTranslations = $translations['index']['cards'][$projectKey] ?? null;

            if (!is_array($cardTranslations)) {
                continue;
            }

            $projects[] = [
                'key' => $projectKey,
                'title' => $cardTranslations['title'] ?? $projectKey,
                'description' => $cardTranslations['description'] ?? '',
            ];
        }

        return $projects;
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
