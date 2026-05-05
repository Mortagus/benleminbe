<?php

declare(strict_types=1);

namespace App\Public\Controller;

use App\Public\Service\ExperienceProvider;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class ExperiencesController extends AbstractController
{
    public function __construct(
        private readonly ExperienceProvider $experienceProvider,
    ) {
    }

    #[Route(
        '/{_locale}/experiences',
        name: 'app_experiences_index',
        requirements: ['_locale' => 'fr|en'],
        methods: ['GET'],
    )]
    public function index(): Response
    {
        return $this->render('experiences/index.html.twig', [
            'experiences' => $this->experienceProvider->getExperiences(),
        ]);
    }
}
