<?php

declare(strict_types=1);

namespace App\Private\Controller;

use App\Private\Service\Network\ContactMergeReviewService;
use InvalidArgumentException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/private/network/contact-merge-reviews', name: 'app_private_network_contact_merge_reviews_')]
final class ContactMergeReviewController extends AbstractController
{
    public function __construct(
        private readonly ContactMergeReviewService $contactMergeReviewService,
    ) {
    }

    #[Route('', name: 'index', methods: ['GET'])]
    public function index(): Response
    {
        return $this->render('private/network/contact_merge_reviews/index.html.twig', $this->contactMergeReviewService->getQueueData());
    }

    #[Route('/generate', name: 'generate', methods: ['POST'])]
    public function generate(Request $request): RedirectResponse
    {
        if (!$this->isCsrfTokenValid('private-network-contact-merge-reviews-refresh', $request->request->getString('_token'))) {
            $this->addFlash('error', 'Le formulaire de détection a expiré. Réessaie.');

            return $this->redirectToRoute('app_private_network_contact_merge_reviews_index');
        }

        $summary = $this->contactMergeReviewService->refreshCandidates();
        $details = [];

        if ($summary['auto_merged_contacts'] > 0) {
            $details[] = sprintf(
                '%d contact%s fusionné%s automatiquement dans %d groupe%s',
                $summary['auto_merged_contacts'],
                $summary['auto_merged_contacts'] > 1 ? 's' : '',
                $summary['auto_merged_contacts'] > 1 ? 's' : '',
                $summary['auto_merged_groups'],
                $summary['auto_merged_groups'] > 1 ? 's' : '',
            );
        }

        if ($summary['purged_pending_reviews'] > 0) {
            $details[] = sprintf(
                '%d doublon%s en attente obsolète%s supprimé%s',
                $summary['purged_pending_reviews'],
                $summary['purged_pending_reviews'] > 1 ? 's' : '',
                $summary['purged_pending_reviews'] > 1 ? 's' : '',
                $summary['purged_pending_reviews'] > 1 ? 's' : '',
            );
        }

        $this->addFlash(
            'success',
            trim(sprintf(
                '%d candidat%s créé%s, %d mis à jour, %d ignoré%s sur %d paire%s étudiée%s.%s',
                $summary['created'],
                $summary['created'] > 1 ? 's' : '',
                $summary['created'] > 1 ? 's' : '',
                $summary['updated'],
                $summary['skipped'],
                $summary['skipped'] > 1 ? 's' : '',
                $summary['considered'],
                $summary['considered'] > 1 ? 's' : '',
                $summary['considered'] > 1 ? 's' : '',
                $details !== [] ? ' ' . implode(' ', $details) : '',
            )),
        );

        return $this->redirectToRoute('app_private_network_contact_merge_reviews_index');
    }

    #[Route('/purge-pending', name: 'purge_pending', methods: ['POST'])]
    public function purgePending(Request $request): RedirectResponse
    {
        if (!$this->isCsrfTokenValid('private-network-contact-merge-reviews-purge-pending', $request->request->getString('_token'))) {
            $this->addFlash('error', 'Le formulaire de purge a expiré. Réessaie.');

            return $this->redirectToRoute('app_private_network_contact_merge_reviews_index');
        }

        $summary = $this->contactMergeReviewService->purgePendingReviews();
        if ($summary['deleted'] > 0) {
            $this->addFlash(
                'success',
                sprintf(
                    '%d doublon%s en attente supprimé%s.',
                    $summary['deleted'],
                    $summary['deleted'] > 1 ? 's' : '',
                    $summary['deleted'] > 1 ? 's' : '',
                ),
            );
        } else {
            $this->addFlash('info', 'Aucun doublon en attente à supprimer.');
        }

        return $this->redirectToRoute('app_private_network_contact_merge_reviews_index');
    }

    #[Route('/reset', name: 'reset', methods: ['POST'])]
    public function reset(Request $request): RedirectResponse
    {
        if (!$this->isCsrfTokenValid('private-network-contact-merge-reviews-reset', $request->request->getString('_token'))) {
            $this->addFlash('error', 'Le formulaire de réinitialisation a expiré. Réessaie.');

            return $this->redirectToRoute('app_private_network_contact_merge_reviews_index');
        }

        $summary = $this->contactMergeReviewService->resetNetworkData();
        $this->addFlash(
            'success',
            sprintf(
                '%d contact%s, %d interaction%s, %d import%s et %d doublon%s supprimé%s.',
                $summary['contacts'],
                $summary['contacts'] > 1 ? 's' : '',
                $summary['interactions'],
                $summary['interactions'] > 1 ? 's' : '',
                $summary['imports'],
                $summary['imports'] > 1 ? 's' : '',
                $summary['reviews'],
                $summary['reviews'] > 1 ? 's' : '',
                $summary['reviews'] > 1 ? 's' : '',
            ),
        );

        return $this->redirectToRoute('app_private_network_contact_merge_reviews_index');
    }

    #[Route('/{id}', name: 'show', methods: ['GET'])]
    public function show(string $id): Response
    {
        return $this->render('private/network/contact_merge_reviews/show.html.twig', [
            'review' => $this->contactMergeReviewService->getReview($id),
        ]);
    }

    #[Route('/{id}/resolve', name: 'resolve', methods: ['POST'])]
    public function resolve(string $id, Request $request): RedirectResponse
    {
        if (!$this->isCsrfTokenValid('private-network-contact-merge-reviews-resolve-' . $id, $request->request->getString('_token'))) {
            $this->addFlash('error', 'Le formulaire de fusion a expiré. Réessaie.');

            return $this->redirectToRoute('app_private_network_contact_merge_reviews_show', ['id' => $id]);
        }

        /** @var array<string, string> $fieldChoices */
        $fieldChoices = $request->request->all('field_choices');

        try {
            $result = $this->contactMergeReviewService->resolveReview($id, $request->request->getString('canonical_side', 'left'), $fieldChoices);
        } catch (InvalidArgumentException|NotFoundHttpException $exception) {
            $this->addFlash('error', $exception->getMessage());

            return $this->redirectToRoute('app_private_network_contact_merge_reviews_show', ['id' => $id]);
        }

        $this->addFlash(
            'success',
            sprintf(
                'Doublon fusionné vers "%s"%s.',
                $result['resolved_contact']['display_name'] ?? 'contact principal',
                $result['moved_interactions'] > 0 ? sprintf(' avec %d interaction%s déplacée%s', $result['moved_interactions'], $result['moved_interactions'] > 1 ? 's' : '', $result['moved_interactions'] > 1 ? 's' : '') : '',
            ),
        );

        return $this->redirectToRoute('app_private_network_contact_merge_reviews_index');
    }

    #[Route('/{id}/ignore', name: 'ignore', methods: ['POST'])]
    public function ignore(string $id, Request $request): RedirectResponse
    {
        if (!$this->isCsrfTokenValid('private-network-contact-merge-reviews-ignore-' . $id, $request->request->getString('_token'))) {
            $this->addFlash('error', "Le formulaire d'ignorance a expiré. Réessaie.");

            return $this->redirectToRoute('app_private_network_contact_merge_reviews_show', ['id' => $id]);
        }

        try {
            $this->contactMergeReviewService->ignoreReview($id);
        } catch (InvalidArgumentException|NotFoundHttpException $exception) {
            $this->addFlash('error', $exception->getMessage());

            return $this->redirectToRoute('app_private_network_contact_merge_reviews_show', ['id' => $id]);
        }

        $this->addFlash('info', 'Doublon ignoré.');

        return $this->redirectToRoute('app_private_network_contact_merge_reviews_index');
    }
}
