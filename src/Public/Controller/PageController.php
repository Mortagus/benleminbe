<?php

declare(strict_types=1);

namespace App\Public\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class PageController extends AbstractController
{
    #[Route(
        path: '/{_locale}/about',
        name: 'app_about',
        requirements: ['_locale' => 'fr|en']
    )]
    public function about(): Response
    {
        return $this->render('pages/about.html.twig');
    }

    #[Route(
        path: '/{_locale}/contact',
        name: 'app_contact',
        requirements: ['_locale' => 'fr|en']
    )]
    public function contact(): Response
    {
        return $this->render('pages/contact.html.twig');
    }
}
