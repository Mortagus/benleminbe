<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/{_locale}/projects', name: 'app_projects_', requirements: ['_locale' => 'fr|en'])]
final class ProjectsController extends AbstractController
{
    private const string TEMPLATE_DIR = 'projects/';

    #[Route('', name: 'index')]
    public function index(): Response
    {
        return $this->render(self::TEMPLATE_DIR . 'index.html.twig');
    }

    #[Route('/delcampe', name: 'delcampe')]
    public function delcampe(): Response
    {
        return $this->render(self::TEMPLATE_DIR . 'delcampe.html.twig');
    }

    #[Route('/stanhome', name: 'stanhome')]
    public function stanhome(): Response
    {
        return $this->render(self::TEMPLATE_DIR . 'stanhome.html.twig');
    }

    #[Route('/moveit', name: 'moveit')]
    public function moveit(): Response
    {
        return $this->render(self::TEMPLATE_DIR . 'moveit.html.twig');
    }
}
