<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/{_locale}/projects', name: 'app_projects_', requirements: ['_locale' => 'fr|en'])]
final class ProjectsController extends AbstractController
{
    private const string TEMPLATE_DIR = 'projects/';

    private const array PROJECTS = [
        [
            'key' => 'delcampe',
            'route' => 'app_projects_delcampe',
        ],
        [
            'key' => 'stanhome',
            'route' => 'app_projects_stanhome',
        ],
        [
            'key' => 'moveit',
            'route' => 'app_projects_moveit',
        ],
    ];

    #[Route('', name: 'index')]
    public function index(): Response
    {
        return $this->render(self::TEMPLATE_DIR . 'index.html.twig', [
            'projects' => self::PROJECTS,
        ]);
    }

    #[Route('/delcampe', name: 'delcampe')]
    public function delcampe(): Response
    {
        return $this->renderProject('delcampe');
    }

    #[Route('/stanhome', name: 'stanhome')]
    public function stanhome(): Response
    {
        return $this->renderProject('stanhome');
    }

    #[Route('/moveit', name: 'moveit')]
    public function moveit(): Response
    {
        return $this->renderProject('moveit');
    }

    private function renderProject(string $project): Response
    {
        $projectIndex = $this->findProjectIndex($project);

        return $this->render(self::TEMPLATE_DIR . 'detailed_project.html.twig', [
            'project' => $project,
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
     * @return array{key: string, route: string}|null
     */
    private function getAdjacentProject(int $projectIndex, int $offset): ?array
    {
        return self::PROJECTS[$projectIndex + $offset] ?? null;
    }
}
