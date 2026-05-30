<?php

declare(strict_types=1);

namespace App\Private\Controller;

use App\Private\Service\Network\PlatformService;
use DateTimeImmutable;
use InvalidArgumentException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\HeaderUtils;
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

    #[Route('/platforms/export', name: 'platform_export', methods: ['GET'])]
    public function platformExport(): Response|RedirectResponse
    {
        try {
            $backup = $this->platformService->exportPlatformsBackup();
            $json = json_encode($backup, JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        } catch (\Throwable $exception) {
            $this->addFlash('error', sprintf('L’export des plateformes a échoué: %s', $exception->getMessage()));

            return $this->redirectToRoute('app_private_network_platforms');
        }

        $filename = sprintf('private-network-platforms-%s.json', (new DateTimeImmutable())->format('Y-m-d'));
        $response = new Response($json);
        $response->headers->set('Content-Type', 'application/json; charset=utf-8');
        $response->headers->set('Content-Disposition', HeaderUtils::makeDisposition(HeaderUtils::DISPOSITION_ATTACHMENT, $filename));

        return $response;
    }

    #[Route('/platforms/import', name: 'platform_import', methods: ['GET', 'POST'])]
    public function platformImport(Request $request): Response|RedirectResponse
    {
        if ($request->isMethod('POST')) {
            if (!$this->isCsrfTokenValid('private-network-platform-import', $request->request->getString('_token'))) {
                $this->addFlash('error', 'Le formulaire d’import a expiré. Réessaie.');

                return $this->redirectToRoute('app_private_network_platform_import');
            }

            $uploadedFile = $request->files->get('file');
            if (!$uploadedFile instanceof UploadedFile || !$uploadedFile->isValid()) {
                return $this->renderPlatformImportPage(['Le fichier JSON de sauvegarde est obligatoire et doit être valide.']);
            }

            $contents = file_get_contents($uploadedFile->getPathname());
            if ($contents === false) {
                return $this->renderPlatformImportPage(['Le fichier JSON de sauvegarde est illisible.']);
            }

            try {
                $summary = $this->platformService->importPlatformsBackup(json_decode($contents, true, 512, JSON_THROW_ON_ERROR));
            } catch (\JsonException) {
                return $this->renderPlatformImportPage(['Le fichier JSON de sauvegarde est invalide.']);
            } catch (InvalidArgumentException $exception) {
                return $this->renderPlatformImportPage([$exception->getMessage()]);
            } catch (\Throwable $exception) {
                return $this->renderPlatformImportPage([sprintf('L’import des plateformes a échoué: %s', $exception->getMessage())]);
            }

            $this->addFlash('success', sprintf('%d plateforme%s restaurée%s.', $summary['imported'], $summary['imported'] > 1 ? 's' : '', $summary['imported'] > 1 ? 's' : ''));

            return $this->redirectToRoute('app_private_network_platforms');
        }

        return $this->renderPlatformImportPage();
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

    /**
     * @param list<string> $errors
     */
    private function renderPlatformImportPage(array $errors = []): Response
    {
        return $this->render('private/network/platforms/import.html.twig', [
            'errors' => $errors,
        ]);
    }
}
