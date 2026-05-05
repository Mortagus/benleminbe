<?php

namespace App\Controller;

use App\Service\CvProvider;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class HomeController extends AbstractController {

    /**
     * @param CvProvider $cvProvider
     */
    public function __construct(
        private readonly CvProvider $cvProvider,
    ) {}

    #[Route(path: '/', name: 'app_home_redirect', methods: ['GET'])]
    public function redirectToDefaultLocale(): RedirectResponse {
        return $this->redirectToRoute('app_home', [
            '_locale' => 'fr',
        ]);
    }

    #[Route(
        path: '/{_locale}',
        name: 'app_home',
        requirements: ['_locale' => 'fr|en'],
        options: [
            'sitemap' => [
                'enabled' => TRUE,
                'locales' => ['fr', 'en'],
            ],
        ],
        methods: ['GET'],
    )]
    public function index(Request $request): Response {
        $cv = $this->cvProvider->getCvData($request->getLocale());

        return $this->render('home/index.html.twig', [
            'cvFile' => $cv['file'],
            'cvVersion' => $cv['version'],
        ]);
    }
}
