<?php

declare(strict_types=1);

namespace App\Public\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route(
    '/{_locale}',
    name: 'app_',
    requirements: ['_locale' => 'fr|en'],
)]
final class PageController extends AbstractController
{
    #[Route(
        path: '/about',
        name: 'about',
        options: [
            'sitemap' => [
                'enabled' => true,
                'locales' => ['fr', 'en'],
                'lastmod' => '2026-05-11',
            ],
        ],
        methods: ['GET'],
    )]
    public function about(): Response
    {
        return $this->render('pages/about.html.twig');
    }

    #[Route(
        path: '/contact',
        name: 'contact',
        options: [
            'sitemap' => [
                'enabled' => true,
                'locales' => ['fr', 'en'],
                'lastmod' => '2026-05-08',
            ],
        ],
        methods: ['GET'],
    )]
    public function contact(): Response
    {
        return $this->render('pages/contact.html.twig');
    }
}
