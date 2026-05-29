<?php

declare(strict_types=1);

namespace App\Private\Controller;

use App\Private\Service\Network\PlatformService;
use InvalidArgumentException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/private/network', name: 'app_private_network_')]
final class PlatformController extends AbstractController
{
    public function __construct(
        private readonly PlatformService $platformService,
    ) {
    }

    #[Route('/platforms', name: 'platforms', methods: ['GET'])]
    public function platforms(Request $request): Response
    {
        $query = $request->query->getString('q');

        return $this->render('private/network/platforms/index.html.twig', [
            'currentQuery' => $query,
            'platforms' => $this->platformService->listPlatforms($query),
            'statusOptions' => $this->platformService->getStatusOptions(),
        ]);
    }

    #[Route('/platforms/new', name: 'platform_new', methods: ['GET', 'POST'])]
    public function platformNew(Request $request): Response
    {
        $values = $this->platformService->defaultValues();

        if ($request->isMethod('POST')) {
            $result = $this->handlePlatformSubmission($request);
            if ($result instanceof RedirectResponse) {
                return $result;
            }

            $values = array_merge($values, $result['values']);

            return $this->render('private/network/platforms/form.html.twig', [
                'mode' => 'create',
                'formAction' => $this->generateUrl('app_private_network_platform_new'),
                'cancelUrl' => $this->generateUrl('app_private_network_platforms'),
                'values' => $values,
                'errors' => $result['errors'],
                'statusOptions' => $this->platformService->getStatusOptions(),
            ]);
        }

        return $this->render('private/network/platforms/form.html.twig', [
            'mode' => 'create',
            'formAction' => $this->generateUrl('app_private_network_platform_new'),
            'cancelUrl' => $this->generateUrl('app_private_network_platforms'),
            'values' => $values,
            'errors' => [],
            'statusOptions' => $this->platformService->getStatusOptions(),
        ]);
    }

    #[Route('/platforms/{slug}', name: 'platform_show', methods: ['GET'])]
    public function platformShow(string $slug): Response
    {
        return $this->render('private/network/platforms/show.html.twig', [
            'platform' => $this->platformService->getPlatform($slug),
        ]);
    }

    #[Route('/platforms/{slug}/edit', name: 'platform_edit', methods: ['GET', 'POST'])]
    public function platformEdit(string $slug, Request $request): Response
    {
        $platform = $this->platformService->getPlatform($slug);

        if ($request->isMethod('POST')) {
            $result = $this->handlePlatformSubmission($request, $slug);
            if ($result instanceof RedirectResponse) {
                return $result;
            }

            $platform = array_merge($platform, $result['values']);

            return $this->render('private/network/platforms/form.html.twig', [
                'mode' => 'edit',
                'platform' => $platform,
                'formAction' => $this->generateUrl('app_private_network_platform_edit', ['slug' => $slug]),
                'cancelUrl' => $this->generateUrl('app_private_network_platform_show', ['slug' => $slug]),
                'values' => $platform,
                'errors' => $result['errors'],
                'statusOptions' => $this->platformService->getStatusOptions(),
            ]);
        }

        return $this->render('private/network/platforms/form.html.twig', [
            'mode' => 'edit',
            'platform' => $platform,
            'formAction' => $this->generateUrl('app_private_network_platform_edit', ['slug' => $slug]),
            'cancelUrl' => $this->generateUrl('app_private_network_platform_show', ['slug' => $slug]),
            'values' => $platform,
            'errors' => [],
            'statusOptions' => $this->platformService->getStatusOptions(),
        ]);
    }

    /**
     * @return array{values: array<string, mixed>, errors: list<string>}|RedirectResponse
     */
    private function handlePlatformSubmission(Request $request, ?string $existingSlug = null): array|RedirectResponse
    {
        if (!$this->isCsrfTokenValid('private-network-platform', $request->request->getString('_token'))) {
            $this->addFlash('error', 'Le formulaire plateforme a expiré. Réessaie.');

            return $this->redirectToRoute($existingSlug === null ? 'app_private_network_platform_new' : 'app_private_network_platform_edit', array_filter(['slug' => $existingSlug]));
        }

        $values = [
            'name' => $request->request->getString('name'),
            'category' => $request->request->getString('category'),
            'profile_url' => $request->request->getString('profile_url'),
            'status' => $request->request->getString('status'),
            'note' => $request->request->getString('note'),
            'last_reviewed_at' => $request->request->getString('last_reviewed_at'),
            'active' => $request->request->getBoolean('active'),
        ];

        try {
            $platform = $this->platformService->savePlatform($values, $existingSlug);
        } catch (InvalidArgumentException $exception) {
            return [
                'values' => $values,
                'errors' => [$exception->getMessage()],
            ];
        }

        $this->addFlash('success', sprintf('Plateforme "%s" enregistrée.', $platform['name']));

        return $this->redirectToRoute('app_private_network_platform_show', ['slug' => $platform['slug']]);
    }
}
