<?php

namespace App\Service;

use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Yaml\Yaml;

final readonly class ProjectProvider
{
    private const array PROJECTS = [
        [
            'key' => 'raidgbs',
        ],
        [
            'key' => 'sogesa',
        ],
        [
            'key' => 'publifund',
        ],
        [
            'key' => 'easy4pro',
        ],
        [
            'key' => 'logic_immo',
        ],
        [
            'key' => 'isobar',
        ],
        [
            'key' => 'delcampe',
        ],
        [
            'key' => 'keytrade',
        ],
        [
            'key' => 'famille_chretienne',
        ],
        [
            'key' => 'stanhome',
        ],
        [
            'key' => 'his',
        ],
        [
            'key' => 'moveit',
        ],
        [
            'key' => 'marge_delhaize',
        ],
        [
            'key' => 'coaching',
        ],
    ];

    public function __construct(
        private KernelInterface $kernel,
    ) {}

    /**
     * @return list<array{key: string}>
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
        $translationFile = sprintf(
            '%s/translations/projects.%s.yaml',
            $this->kernel->getProjectDir(),
            $locale,
        );

        if (!is_file($translationFile)) {
            throw new NotFoundHttpException(sprintf('Project translation file "%s" not found.', $translationFile));
        }

        $translations = Yaml::parseFile($translationFile);

        if (!is_array($translations) || !isset($translations[$project]) || !is_array($translations[$project])) {
            throw new NotFoundHttpException(sprintf('Project translation data "%s" not found.', $project));
        }

        return $translations[$project];
    }

    /**
     * @return array{key: string}|null
     */
    public function getPreviousProject(string $project): ?array
    {
        return $this->getAdjacentProject($this->findProjectIndex($project), -1);
    }

    /**
     * @return array{key: string}|null
     */
    public function getNextProject(string $project): ?array
    {
        return $this->getAdjacentProject($this->findProjectIndex($project), 1);
    }

    /**
     * @return array{key: string}|null
     */
    private function getAdjacentProject(int $projectIndex, int $offset): ?array
    {
        return self::PROJECTS[$projectIndex + $offset] ?? null;
    }
}
