<?php

declare(strict_types=1);

namespace App\Private\Controller;

use App\Private\Service\Network\NetworkDashboardService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/private/network', name: 'app_private_network_')]
final class NetworkDashboardController extends AbstractController
{
    public function __construct(
        private readonly NetworkDashboardService $networkDashboardService,
    ) {
    }

    #[Route('', name: 'index', methods: ['GET'])]
    public function index(): Response
    {
        return $this->render('private/network/index.html.twig', $this->networkDashboardService->getDashboardData());
    }
}
