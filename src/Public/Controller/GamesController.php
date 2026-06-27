<?php

declare(strict_types=1);

namespace App\Public\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Contracts\Translation\TranslatorInterface;

#[Route(
    path: '/games',
    name: 'app_games_',
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
    public function index(Request $request, TranslatorInterface $translator): Response
    {
        $this->applyRequestedLocale($request, $translator);

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
    public function simon(Request $request, TranslatorInterface $translator): Response
    {
        $this->applyRequestedLocale($request, $translator);

        return $this->render('games/simon.html.twig');
    }

    private function applyRequestedLocale(Request $request, TranslatorInterface $translator): void
    {
        $requestedLocale = $request->query->getString('_locale', $request->getLocale());

        if (in_array($requestedLocale, ['fr', 'en'], true)) {
            $request->setLocale($requestedLocale);
            $translator->setLocale($requestedLocale);
        }
    }
}
