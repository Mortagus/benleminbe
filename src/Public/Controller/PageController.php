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
                'lastmod' => '2026-06-04',
            ],
        ],
        methods: ['GET'],
    )]
    public function contact(): Response
    {
        return $this->render('pages/contact.html.twig');
    }

    #[Route(
        path: '/terms-and-conditions',
        name: 'terms_and_conditions',
        options: [
            'sitemap' => [
                'enabled' => true,
                'locales' => ['fr', 'en'],
                'lastmod' => '2026-05-13',
            ],
        ],
        methods: ['GET'],
    )]
    public function termsAndConditions(): Response
    {
        return $this->render('pages/terms_and_conditions.html.twig');
    }

    #[Route(
        path: '/privacy-policy',
        name: 'privacy_policy',
        options: [
            'sitemap' => [
                'enabled' => true,
                'locales' => ['fr', 'en'],
                'lastmod' => '2026-05-13',
            ],
        ],
        methods: ['GET'],
    )]
    public function privacyPolicy(): Response
    {
        return $this->render('pages/privacy_policy.html.twig');
    }

    #[Route(
        path: '/legal-notice',
        name: 'legal_notice',
        options: [
            'sitemap' => [
                'enabled' => true,
                'locales' => ['fr', 'en'],
                'lastmod' => '2026-05-13',
            ],
        ],
        methods: ['GET'],
    )]
    public function legalNotice(): Response
    {
        return $this->render('pages/legal_notice.html.twig');
    }

    #[Route(
        path: '/skills',
        name: 'skills',
        options: [
            'sitemap' => [
                'enabled' => true,
                'locales' => ['fr', 'en'],
                'lastmod' => '2026-06-04',
            ],
        ],
        methods: ['GET'],
    )]
    public function skills(): Response
    {
        return $this->render('pages/skills.html.twig');
    }
}
