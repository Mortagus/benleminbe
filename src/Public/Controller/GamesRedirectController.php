<?php

declare(strict_types=1);

namespace App\Public\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route(name: 'app_games_')]
final class GamesRedirectController extends AbstractController
{
    #[Route(path: '/games', name: 'index_redirect', methods: ['GET'])]
    public function index(Request $request): Response
    {
        return $this->redirectToLocalizedRoute('app_games_index', $request);
    }

    #[Route(path: '/games/simon', name: 'simon_redirect', methods: ['GET'])]
    public function simon(Request $request): Response
    {
        return $this->redirectToLocalizedRoute('app_games_simon', $request);
    }

    private function redirectToLocalizedRoute(string $routeName, Request $request): Response
    {
        $queryParameters = $request->query->all();
        $queryLocale = $request->query->getString('_locale');
        $locale = \in_array($queryLocale, ['fr', 'en'], true) ? $queryLocale : $request->getLocale();

        if (!\in_array($locale, ['fr', 'en'], true)) {
            $locale = 'fr';
        }

        $queryParameters['_locale'] = $locale;

        return $this->redirectToRoute($routeName, $queryParameters, Response::HTTP_MOVED_PERMANENTLY);
    }
}
