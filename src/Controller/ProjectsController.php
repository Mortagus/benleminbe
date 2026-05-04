<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Yaml\Yaml;

#[Route('/{_locale}/projects', name: 'app_projects_', requirements: ['_locale' => 'fr|en'])]
final class ProjectsController extends AbstractController
{
    private const string TEMPLATE_DIR = 'projects/';

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
        private readonly KernelInterface $kernel,
    ) {
    }

    #[Route('', name: 'index')]
    public function index(): Response
    {
        return $this->render(self::TEMPLATE_DIR . 'index.html.twig', [
            'projects' => self::PROJECTS,
        ]);
    }

    #[Route('/{project}', name: 'show')]
    public function show(string $project, string $_locale): Response
    {
        $projectIndex = $this->findProjectIndex($project);

        return $this->render(self::TEMPLATE_DIR . 'detailed_project.html.twig', [
            'project' => $project,
            'projectData' => $this->getProjectData($project, $_locale),
            'previousProject' => $this->getAdjacentProject($projectIndex, -1),
            'nextProject' => $this->getAdjacentProject($projectIndex, 1),
        ]);
    }

    private function findProjectIndex(string $project): int
    {
        foreach (self::PROJECTS as $index => $projectConfig) {
            if ($projectConfig['key'] === $project) {
                return $index;
            }
        }

        throw $this->createNotFoundException(sprintf('Project "%s" not found.', $project));
    }

    /**
     * @return array<string, mixed>
     */
    private function getProjectData(string $project, string $locale): array
    {
        $translationFile = sprintf(
            '%s/translations/projects.%s.yaml',
            $this->kernel->getProjectDir(),
            $locale,
        );

        if (!is_file($translationFile)) {
            throw $this->createNotFoundException(sprintf('Project translation file "%s" not found.', $translationFile));
        }

        $translations = Yaml::parseFile($translationFile);

        if (!is_array($translations) || !isset($translations[$project]) || !is_array($translations[$project])) {
            throw $this->createNotFoundException(sprintf('Project translation data "%s" not found.', $project));
        }

        return $translations[$project];
    }

    /**
     * @return array{key: string}|null
     */
    private function getAdjacentProject(int $projectIndex, int $offset): ?array
    {
        return self::PROJECTS[$projectIndex + $offset] ?? null;
    }
}
