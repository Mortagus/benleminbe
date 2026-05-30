<?php

declare(strict_types=1);

namespace App\Private\Controller;

use App\Private\Service\Network\ContactStatisticsService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/private', name: 'app_private_')]
final class DashboardController extends AbstractController
{
    public function __construct(
        private readonly ContactStatisticsService $contactStatisticsService,
    ) {
    }

    #[Route('', name: 'dashboard', methods: ['GET'])]
    public function dashboard(): Response
    {
        return $this->render('private/dashboard.html.twig', [
            'contact_stats' => $this->contactStatisticsService->getContactOverviewStats(),
        ]);
    }
}
