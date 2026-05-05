<?php

declare(strict_types=1);

namespace App\Public\Controller;

use App\Public\Service\ExperienceProvider;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route(
    '/{_locale}/experiences',
    name: 'app_experiences_',
    requirements: ['_locale' => 'fr|en'],
)]
final class ExperiencesController extends AbstractController
{
    private const string TEMPLATE_DIR = 'experiences/';

    public function __construct(
        private readonly ExperienceProvider $experienceProvider,
    ) {
    }

    #[Route(
        path: '',
        name: 'index',
        options: [
            'sitemap' => [
                'enabled' => true,
                'locales' => ['fr', 'en'],
                'lastmod' => '2026-05-05',
            ],
        ],
        methods: ['GET'],
    )]
    public function index(): Response
    {
        return $this->render(self::TEMPLATE_DIR . 'index.html.twig', [
            'experiences' => $this->experienceProvider->getExperiences(),
        ]);
    }

    #[Route(
        path: '/{experience}',
        name: 'show',
        options: [
            'sitemap' => [
                'enabled' => true,
                'locales' => ['fr', 'en'],
                'lastmod' => '2026-05-05',
            ],
        ],
        methods: ['GET'],
    )]
    public function show(string $experience, string $_locale): Response
    {
        return $this->render(self::TEMPLATE_DIR . 'detailed_experience.html.twig', [
            'experience' => $experience,
            'experienceData' => $this->experienceProvider->getExperienceData($experience, $_locale),
            'previousExperience' => $this->experienceProvider->getPreviousExperience($experience),
            'nextExperience' => $this->experienceProvider->getNextExperience($experience),
        ]);
    }
}
