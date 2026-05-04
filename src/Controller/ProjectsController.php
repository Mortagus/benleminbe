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
        return $this->render(self::TEMPLATE_DIR . 'detailed_project.html.twig', [
            'project' => $project,
        ]);
    }
}
