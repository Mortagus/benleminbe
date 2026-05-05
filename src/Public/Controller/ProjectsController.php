<?php

namespace App\Public\Controller;

use App\Public\Service\ExperienceProvider;
use App\Public\Service\ProjectProvider;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route(
    '/{_locale}/projects',
    name: 'app_projects_',
    requirements: ['_locale' => 'fr|en'])
]
final class ProjectsController extends AbstractController {
    private const string TEMPLATE_DIR = 'projects/';

    public function __construct(
        private readonly ProjectProvider $projectProvider,
        private readonly ExperienceProvider $experienceProvider,
    ) {}

    #[Route(
        path: '',
        name: 'index',
        options: [
            'sitemap' => [
                'enabled' => TRUE,
                'locales' => ['fr', 'en'],
                'lastmod' => '2026-05-04',
            ],
        ],
        methods: ['GET'])
    ]
    public function index(): Response {
        return $this->render(self::TEMPLATE_DIR . 'index.html.twig', [
            'projects' => $this->projectProvider->getProjects(),
        ]);
    }

    #[Route(
        path: '/{project}',
        name: 'show',
        options: [
            'sitemap' => [
                'enabled' => TRUE,
                'locales' => ['fr', 'en'],
                'lastmod' => '2026-05-04',
            ],
        ],
        methods: ['GET'])
    ]
    public function show(string $project, string $_locale): Response {
        $associatedExperienceKey = $this->projectProvider->getExperienceForProject($project);

        return $this->render(self::TEMPLATE_DIR . 'detailed_project.html.twig', [
            'project' => $project,
            'projectData' => $this->projectProvider->getProjectData($project, $_locale),
            'associatedExperience' => $associatedExperienceKey !== null
                ? $this->experienceProvider->getExperienceSummary($associatedExperienceKey, $_locale)
                : null,
            'previousProject' => $this->projectProvider->getPreviousProject($project),
            'nextProject' => $this->projectProvider->getNextProject($project),
        ]);
    }
}
