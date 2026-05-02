<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class HomeController extends AbstractController {
    #[Route(path: '/', name: 'app_home_redirect')]
    public function redirectToDefaultLocale(): RedirectResponse {
        return $this->redirectToRoute('app_home', [
            '_locale' => 'fr',
        ]);
    }

    #[Route(
        path: '/{_locale}',
        name: 'app_home',
        requirements: ['_locale' => 'fr|en']
    )]
    public function index(): Response {
        return $this->render('home/index.html.twig');
    }
}
