<?php

declare(strict_types=1);

namespace App\Public\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route(
    path: '/{_locale}/games',
    name: 'app_games_',
    requirements: ['_locale' => 'fr|en'],
)]
final class GamesController extends AbstractController
{
    #[Route(
        path: '',
        name: 'index',
        options: [
            'sitemap' => [
                'enabled' => true,
                'locales' => ['fr', 'en'],
                'lastmod' => '2026-06-27',
            ],
        ],
        methods: ['GET'],
    )]
    public function index(): Response
    {
        return $this->render('games/index.html.twig');
    }

    #[Route(
        path: '/simon',
        name: 'simon',
        options: [
            'sitemap' => [
                'enabled' => true,
                'locales' => ['fr', 'en'],
                'lastmod' => '2026-06-27',
            ],
        ],
        methods: ['GET'],
    )]
    public function simon(): Response
    {
        return $this->render('games/simon.html.twig');
    }
}
