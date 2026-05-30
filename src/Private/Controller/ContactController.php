<?php

declare(strict_types=1);

namespace App\Private\Controller;

use App\Enum\Network\ContactImportSource;
use App\Private\Service\Network\ContactService;
use InvalidArgumentException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/private/network', name: 'app_private_network_')]
final class ContactController extends AbstractController
{
    private const int CONTACTS_PAGE_SIZE = 20;

    public function __construct(
        private readonly ContactService $contactService,
    ) {
    }

    #[Route('/contacts', name: 'contacts', methods: ['GET'])]
    public function contacts(Request $request): Response
    {
        $filters = [
            'search' => $request->query->getString('q'),
            'priority' => $request->query->getString('priority'),
            'relationship_status' => $request->query->getString('relationship_status'),
            'organization_state' => $request->query->getString('organization_state'),
            'letter' => $request->query->getString('letter'),
            'sort' => $request->query->getString('sort'),
        ];
        $page = max(1, $request->query->getInt('page', 1));

        return $this->render('private/network/contacts/index.html.twig', $this->contactService->getContactsPage($filters, $page, self::CONTACTS_PAGE_SIZE));
    }

    #[Route('/contacts/merge-duplicates', name: 'contacts_merge_duplicates', methods: ['POST'])]
    public function contactsMergeDuplicates(Request $request): RedirectResponse
    {
        if (!$this->isCsrfTokenValid('private-network-contacts-merge-duplicates', $request->request->getString('_token'))) {
            $this->addFlash('error', 'Le formulaire de fusion a expiré. Réessaie.');

            return $this->redirectToRoute('app_private_network_contacts', $this->extractContactsFilters($request));
        }

        $summary = $this->contactService->autoMergeContacts();

        if ($summary['merged_contacts'] === 0) {
            $this->addFlash('info', 'Aucun doublon détecté.');
        } else {
            $this->addFlash(
                'success',
                sprintf(
                    '%d contact%s fusionné%s dans %d groupe%s de doublons.',
                    $summary['merged_contacts'],
                    $summary['merged_contacts'] > 1 ? 's' : '',
                    $summary['merged_contacts'] > 1 ? 's' : '',
                    $summary['merged_groups'],
                    $summary['merged_groups'] > 1 ? 's' : '',
                ),
            );
        }

        return $this->redirectToRoute('app_private_network_contacts', $this->extractContactsFilters($request));
    }

    #[Route('/contacts/new', name: 'contact_new', methods: ['GET', 'POST'])]
    public function contactNew(Request $request): Response
    {
        $values = $this->contactService->defaultValues();

        if ($request->isMethod('POST')) {
            $result = $this->handleContactSubmission($request);
            if ($result instanceof RedirectResponse) {
                return $result;
            }

            $values = array_merge($values, $result['values']);

            return $this->render('private/network/contacts/form.html.twig', [
                'mode' => 'create',
                'formAction' => $this->generateUrl('app_private_network_contact_new'),
                'cancelUrl' => $this->generateUrl('app_private_network_contacts'),
                'values' => $values,
                'errors' => $result['errors'],
                'priorityOptions' => $this->contactService->getPriorityOptions(),
                'relationOptions' => $this->contactService->getRelationOptions(),
            ]);
        }

        return $this->render('private/network/contacts/form.html.twig', [
            'mode' => 'create',
            'formAction' => $this->generateUrl('app_private_network_contact_new'),
            'cancelUrl' => $this->generateUrl('app_private_network_contacts'),
            'values' => $values,
            'errors' => [],
            'priorityOptions' => $this->contactService->getPriorityOptions(),
            'relationOptions' => $this->contactService->getRelationOptions(),
        ]);
    }

    #[Route('/contacts/{id}', name: 'contact_show', methods: ['GET'])]
    public function contactShow(string $id): Response
    {
        return $this->render('private/network/contacts/show.html.twig', [
            'contact' => $this->contactService->getContact($id),
            'interactions' => $this->contactService->listInteractionsForContact($id),
            'interactionAction' => $this->generateUrl('app_private_network_contact_interaction', ['id' => $id]),
        ]);
    }

    #[Route('/contacts/{id}/edit', name: 'contact_edit', methods: ['GET', 'POST'])]
    public function contactEdit(string $id, Request $request): Response
    {
        $contact = $this->contactService->getContact($id);

        if ($request->isMethod('POST')) {
            $result = $this->handleContactSubmission($request, $id);
            if ($result instanceof RedirectResponse) {
                return $result;
            }

            $contact = array_merge($contact, $result['values']);

            return $this->render('private/network/contacts/form.html.twig', [
                'mode' => 'edit',
                'contact' => $contact,
                'formAction' => $this->generateUrl('app_private_network_contact_edit', ['id' => $id]),
                'cancelUrl' => $this->generateUrl('app_private_network_contact_show', ['id' => $id]),
                'values' => $contact,
                'errors' => $result['errors'],
                'priorityOptions' => $this->contactService->getPriorityOptions(),
                'relationOptions' => $this->contactService->getRelationOptions(),
            ]);
        }

        return $this->render('private/network/contacts/form.html.twig', [
            'mode' => 'edit',
            'contact' => $contact,
            'formAction' => $this->generateUrl('app_private_network_contact_edit', ['id' => $id]),
            'cancelUrl' => $this->generateUrl('app_private_network_contact_show', ['id' => $id]),
            'values' => $contact,
            'errors' => [],
            'priorityOptions' => $this->contactService->getPriorityOptions(),
            'relationOptions' => $this->contactService->getRelationOptions(),
        ]);
    }

    #[Route('/contacts/{id}/delete', name: 'contact_delete', methods: ['POST'])]
    public function contactDelete(string $id, Request $request): RedirectResponse
    {
        if (!$this->isCsrfTokenValid('private-network-contact-delete-' . $id, $request->request->getString('_token'))) {
            $this->addFlash('error', 'Le formulaire de suppression a expiré. Réessaie.');

            return $this->redirectToRoute('app_private_network_contact_show', ['id' => $id]);
        }

        $query = array_filter([
            'q' => $request->request->getString('q'),
            'priority' => $request->request->getString('priority'),
            'relationship_status' => $request->request->getString('relationship_status'),
            'organization_state' => $request->request->getString('organization_state'),
            'letter' => $request->request->getString('letter'),
            'sort' => $request->request->getString('sort'),
            'page' => max(1, $request->request->getInt('page', 1)),
        ], static fn (mixed $value): bool => $value !== '' && $value !== null);

        if (($query['sort'] ?? '') === 'default') {
            unset($query['sort']);
        }

        try {
            $this->contactService->deleteContact($id);
        } catch (NotFoundHttpException) {
            $this->addFlash('error', 'Le contact à supprimer est introuvable.');

            return $this->redirectToRoute('app_private_network_contacts', $query);
        }

        $this->addFlash('success', 'Contact supprimé.');

        return $this->redirectToRoute('app_private_network_contacts', $query);
    }

    #[Route('/contacts/{id}/interactions', name: 'contact_interaction', methods: ['POST'])]
    public function contactInteraction(string $id, Request $request): RedirectResponse|Response
    {
        if (!$this->isCsrfTokenValid('private-network-contact-interaction-' . $id, $request->request->getString('_token'))) {
            $this->addFlash('error', 'Le formulaire d interaction a expiré. Réessaie.');

            return $this->redirectToRoute('app_private_network_contact_show', ['id' => $id]);
        }

        try {
            $this->contactService->addInteraction($id, [
                'date' => $request->request->getString('date'),
                'channel' => $request->request->getString('channel'),
                'summary' => $request->request->getString('summary'),
                'result' => $request->request->getString('result'),
                'next_action' => $request->request->getString('next_action'),
                'next_action_at' => $request->request->getString('next_action_at'),
            ]);
        } catch (InvalidArgumentException $exception) {
            $this->addFlash('error', $exception->getMessage());

            return $this->redirectToRoute('app_private_network_contact_show', ['id' => $id]);
        }

        $this->addFlash('success', 'Interaction enregistrée.');

        return $this->redirectToRoute('app_private_network_contact_show', ['id' => $id]);
    }

    #[Route('/import', name: 'import', methods: ['GET', 'POST'])]
    public function import(Request $request): Response
    {
        $source = ContactImportSource::tryFrom($request->request->getString('source_label')) ?? ContactImportSource::default();

        if ($request->isMethod('POST')) {
            if (!$this->isCsrfTokenValid('private-network-import', $request->request->getString('_token'))) {
                $this->addFlash('error', 'Le formulaire d import a expiré. Réessaie.');

                return $this->redirectToRoute('app_private_network_import');
            }

            $rows = [];

            try {
                $rows = $this->contactService->parseImportRows(
                    $request->files->get('file') instanceof UploadedFile ? $request->files->get('file') : null,
                    $request->request->getString('content'),
                    $source,
                );
            } catch (InvalidArgumentException $exception) {
                return $this->render('private/network/import.html.twig', [
                    'errors' => [$exception->getMessage()],
                    'sourceOptions' => $this->contactService->getImportSourceOptions(),
                    'values' => $this->extractImportValues($request, $source),
                ]);
            }

            if ($rows === []) {
                return $this->render('private/network/import.html.twig', [
                    'errors' => ['Aucune ligne importable n a été trouvée.'],
                    'sourceOptions' => $this->contactService->getImportSourceOptions(),
                    'values' => $this->extractImportValues($request, $source),
                ]);
            }

            $summary = $this->contactService->importContacts($rows, $source->label());
            $this->addFlash('success', sprintf('%d lignes traitées: %d créées, %d mises à jour.', $summary['total'], $summary['created'], $summary['updated']));

            return $this->redirectToRoute('app_private_network_contacts');
        }

        return $this->render('private/network/import.html.twig', [
            'errors' => [],
            'sourceOptions' => $this->contactService->getImportSourceOptions(),
            'values' => [
                'source_label' => $source->value,
                'content' => '',
            ],
        ]);
    }

    /**
     * @return array{values: array<string, mixed>, errors: list<string>}|RedirectResponse
     */
    private function handleContactSubmission(Request $request, ?string $existingId = null): array|RedirectResponse
    {
        if (!$this->isCsrfTokenValid('private-network-contact', $request->request->getString('_token'))) {
            $this->addFlash('error', 'Le formulaire contact a expiré. Réessaie.');

            return $this->redirectToRoute($existingId === null ? 'app_private_network_contact_new' : 'app_private_network_contact_edit', array_filter(['id' => $existingId]));
        }

        $values = [
            'display_name' => $request->request->getString('display_name'),
            'first_name' => $request->request->getString('first_name'),
            'last_name' => $request->request->getString('last_name'),
            'organization' => $request->request->getString('organization'),
            'role' => $request->request->getString('role'),
            'main_channel' => $request->request->getString('main_channel'),
            'email' => $request->request->getString('email'),
            'phone' => $request->request->getString('phone'),
            'profile_url' => $request->request->getString('profile_url'),
            'source' => $request->request->getString('source'),
            'priority' => $request->request->getString('priority'),
            'relationship_status' => $request->request->getString('relationship_status'),
            'last_contact_at' => $request->request->getString('last_contact_at'),
            'next_action_at' => $request->request->getString('next_action_at'),
            'next_action' => $request->request->getString('next_action'),
            'notes' => $request->request->getString('notes'),
            'tags' => $request->request->getString('tags'),
        ];

        try {
            $contact = $this->contactService->saveContact($values, $existingId);
        } catch (InvalidArgumentException $exception) {
            return [
                'values' => $values,
                'errors' => [$exception->getMessage()],
            ];
        }

        $this->addFlash('success', sprintf('Contact "%s" enregistré.', $contact['display_name']));

        return $this->redirectToRoute('app_private_network_contact_show', ['id' => $contact['id']]);
    }

    /**
     * @return array<string, scalar>
     */
    private function extractContactsFilters(Request $request): array
    {
        $filters = array_filter([
            'q' => $request->request->getString('q'),
            'priority' => $request->request->getString('priority'),
            'relationship_status' => $request->request->getString('relationship_status'),
            'organization_state' => $request->request->getString('organization_state'),
            'letter' => $request->request->getString('letter'),
            'sort' => $request->request->getString('sort'),
            'page' => 1,
        ], static fn (mixed $value): bool => $value !== '' && $value !== null);

        if (($filters['sort'] ?? '') === 'default') {
            unset($filters['sort']);
        }

        return $filters;
    }

    /**
     * @return array<string, mixed>
     */
    private function extractImportValues(Request $request, ContactImportSource $source): array
    {
        return [
            'source_label' => $request->request->getString('source_label', $source->value),
            'content' => $request->request->getString('content'),
        ];
    }
}
