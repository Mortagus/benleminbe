<?php

declare(strict_types=1);

namespace App\Private\Controller;

use App\Enum\Network\ContactImportSource;
use App\Private\Service\Network\ContactImportParser;
use App\Private\Service\Network\NetworkRepository;
use InvalidArgumentException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/private/network', name: 'app_private_network_')]
final class NetworkController extends AbstractController
{
    private const int CONTACTS_PAGE_SIZE = 20;

    public function __construct(
        private readonly NetworkRepository $networkRepository,
        private readonly ContactImportParser $contactImportParser,
    ) {
    }

    #[Route('', name: 'index', methods: ['GET'])]
    public function index(): Response
    {
        return $this->render('private/network/index.html.twig', $this->networkRepository->getDashboardData());
    }

    #[Route('/platforms', name: 'platforms', methods: ['GET'])]
    public function platforms(Request $request): Response
    {
        $query = $request->query->getString('q');

        return $this->render('private/network/platforms/index.html.twig', [
            'currentQuery' => $query,
            'platforms' => $this->filterPlatforms($query),
            'statusOptions' => $this->networkRepository->getPlatformStatusOptions(),
        ]);
    }

    #[Route('/platforms/new', name: 'platform_new', methods: ['GET', 'POST'])]
    public function platformNew(Request $request): Response
    {
        $values = $this->defaultPlatformValues();

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
                'statusOptions' => $this->networkRepository->getPlatformStatusOptions(),
            ]);
        }

        return $this->render('private/network/platforms/form.html.twig', [
            'mode' => 'create',
            'formAction' => $this->generateUrl('app_private_network_platform_new'),
            'cancelUrl' => $this->generateUrl('app_private_network_platforms'),
            'values' => $values,
            'errors' => [],
            'statusOptions' => $this->networkRepository->getPlatformStatusOptions(),
        ]);
    }

    #[Route('/platforms/{slug}', name: 'platform_show', methods: ['GET'])]
    public function platformShow(string $slug): Response
    {
        return $this->render('private/network/platforms/show.html.twig', [
            'platform' => $this->networkRepository->getPlatform($slug),
        ]);
    }

    #[Route('/platforms/{slug}/edit', name: 'platform_edit', methods: ['GET', 'POST'])]
    public function platformEdit(string $slug, Request $request): Response
    {
        $platform = $this->networkRepository->getPlatform($slug);

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
                'statusOptions' => $this->networkRepository->getPlatformStatusOptions(),
            ]);
        }

        return $this->render('private/network/platforms/form.html.twig', [
            'mode' => 'edit',
            'platform' => $platform,
            'formAction' => $this->generateUrl('app_private_network_platform_edit', ['slug' => $slug]),
            'cancelUrl' => $this->generateUrl('app_private_network_platform_show', ['slug' => $slug]),
            'values' => $platform,
            'errors' => [],
            'statusOptions' => $this->networkRepository->getPlatformStatusOptions(),
        ]);
    }

    #[Route('/contacts', name: 'contacts', methods: ['GET'])]
    public function contacts(Request $request): Response
    {
        $filters = [
            'search' => $request->query->getString('q'),
            'priority' => $request->query->getString('priority'),
            'relationship_status' => $request->query->getString('relationship_status'),
        ];
        $page = max(1, $request->query->getInt('page', 1));
        $pageSize = self::CONTACTS_PAGE_SIZE;
        $allContacts = $this->networkRepository->listContacts($filters);
        $totalContacts = count($allContacts);
        $totalPages = max(1, (int) ceil($totalContacts / $pageSize));
        $page = min($page, $totalPages);
        $offset = ($page - 1) * $pageSize;
        $contacts = array_slice($allContacts, $offset, $pageSize);
        $hasMore = $page < $totalPages;

        return $this->render('private/network/contacts/index.html.twig', [
            'currentQuery' => $filters['search'],
            'currentPriority' => $filters['priority'],
            'currentRelationStatus' => $filters['relationship_status'],
            'contacts' => $contacts,
            'currentPage' => $page,
            'pageSize' => $pageSize,
            'totalContacts' => $totalContacts,
            'visibleFrom' => $totalContacts === 0 ? 0 : $offset + 1,
            'visibleTo' => min($offset + $pageSize, $totalContacts),
            'hasMore' => $hasMore,
            'nextPage' => $page + 1,
            'priorityOptions' => $this->networkRepository->getContactPriorityOptions(),
            'relationOptions' => $this->networkRepository->getContactRelationOptions(),
        ]);
    }

    #[Route('/contacts/merge-duplicates', name: 'contacts_merge_duplicates', methods: ['POST'])]
    public function contactsMergeDuplicates(Request $request): RedirectResponse
    {
        if (!$this->isCsrfTokenValid('private-network-contacts-merge-duplicates', $request->request->getString('_token'))) {
            $this->addFlash('error', 'Le formulaire de fusion a expiré. Réessaie.');

            return $this->redirectToRoute('app_private_network_contacts', $this->extractContactsFilters($request));
        }

        $summary = $this->networkRepository->autoMergeContacts();

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
        $values = $this->defaultContactValues();

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
                'priorityOptions' => $this->networkRepository->getContactPriorityOptions(),
                'relationOptions' => $this->networkRepository->getContactRelationOptions(),
            ]);
        }

        return $this->render('private/network/contacts/form.html.twig', [
            'mode' => 'create',
            'formAction' => $this->generateUrl('app_private_network_contact_new'),
            'cancelUrl' => $this->generateUrl('app_private_network_contacts'),
            'values' => $values,
            'errors' => [],
            'priorityOptions' => $this->networkRepository->getContactPriorityOptions(),
            'relationOptions' => $this->networkRepository->getContactRelationOptions(),
        ]);
    }

    #[Route('/contacts/{id}', name: 'contact_show', methods: ['GET'])]
    public function contactShow(string $id): Response
    {
        return $this->render('private/network/contacts/show.html.twig', [
            'contact' => $this->networkRepository->getContact($id),
            'interactions' => $this->networkRepository->listInteractionsForContact($id),
            'interactionAction' => $this->generateUrl('app_private_network_contact_interaction', ['id' => $id]),
        ]);
    }

    #[Route('/contacts/{id}/edit', name: 'contact_edit', methods: ['GET', 'POST'])]
    public function contactEdit(string $id, Request $request): Response
    {
        $contact = $this->networkRepository->getContact($id);

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
                'priorityOptions' => $this->networkRepository->getContactPriorityOptions(),
                'relationOptions' => $this->networkRepository->getContactRelationOptions(),
            ]);
        }

        return $this->render('private/network/contacts/form.html.twig', [
            'mode' => 'edit',
            'contact' => $contact,
            'formAction' => $this->generateUrl('app_private_network_contact_edit', ['id' => $id]),
            'cancelUrl' => $this->generateUrl('app_private_network_contact_show', ['id' => $id]),
            'values' => $contact,
            'errors' => [],
            'priorityOptions' => $this->networkRepository->getContactPriorityOptions(),
            'relationOptions' => $this->networkRepository->getContactRelationOptions(),
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
            'page' => max(1, $request->request->getInt('page', 1)),
        ], static fn (mixed $value): bool => $value !== '' && $value !== null);

        try {
            $this->networkRepository->deleteContact($id);
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
            $this->networkRepository->addInteraction($id, [
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
                $rows = $this->extractImportRows($request, $source);
            } catch (InvalidArgumentException $exception) {
                return $this->render('private/network/import.html.twig', [
                    'errors' => [$exception->getMessage()],
                    'sourceOptions' => $this->networkRepository->getImportSourceOptions(),
                    'values' => $this->extractImportValues($request, $source),
                ]);
            }

            if ($rows === []) {
                return $this->render('private/network/import.html.twig', [
                    'errors' => ['Aucune ligne importable n a été trouvée.'],
                    'sourceOptions' => $this->networkRepository->getImportSourceOptions(),
                    'values' => $this->extractImportValues($request, $source),
                ]);
            }

            $summary = $this->networkRepository->importContacts($rows, $source->label());
            $this->addFlash('success', sprintf('%d lignes traitées: %d créées, %d mises à jour.', $summary['total'], $summary['created'], $summary['updated']));

            return $this->redirectToRoute('app_private_network_contacts');
        }

        return $this->render('private/network/import.html.twig', [
            'errors' => [],
            'sourceOptions' => $this->networkRepository->getImportSourceOptions(),
            'values' => [
                'source_label' => $source->value,
                'content' => '',
            ],
        ]);
    }

    /**
     * @param array<string, mixed> $defaults
     *
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

        $errors = [];

        if ($values['name'] === '') {
            $errors[] = 'Le nom de la plateforme est obligatoire.';
        }

        if ($errors !== []) {
            return [
                'values' => $values,
                'errors' => $errors,
            ];
        }

        try {
            $platform = $this->networkRepository->savePlatform($values, $existingSlug);
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
     * @param array<string, mixed> $defaults
     *
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

        $errors = [];

        if ($values['display_name'] === '' && trim($values['first_name'] . ' ' . $values['last_name']) === '' && trim($values['organization'] . ' ' . $values['role']) === '') {
            $errors[] = 'Un nom de contact ou un couple prénom / nom est obligatoire.';
        }

        if ($errors !== []) {
            return [
                'values' => $values,
                'errors' => $errors,
            ];
        }

        try {
            $contact = $this->networkRepository->saveContact($this->normalizeContactValues($values), $existingId);
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
     * @return list<array<string, mixed>>
     */
    private function filterPlatforms(string $query): array
    {
        $platforms = $this->networkRepository->listPlatforms();
        $query = mb_strtolower(trim($query));

        if ($query === '') {
            return $platforms;
        }

        return array_values(array_filter($platforms, static function (array $platform) use ($query): bool {
            $haystack = implode(' ', [
                $platform['name'],
                $platform['category'],
                $platform['status_label'],
                $platform['note'],
                $platform['profile_url'],
            ]);

            return str_contains(mb_strtolower($haystack), $query);
        }));
    }

    /**
     * @param array<string, mixed> $values
     *
     * @return array<string, mixed>
     */
    private function normalizeContactValues(array $values): array
    {
        return [
            'display_name' => $values['display_name'] ?? '',
            'first_name' => $values['first_name'] ?? '',
            'last_name' => $values['last_name'] ?? '',
            'organization' => $values['organization'] ?? '',
            'role' => $values['role'] ?? '',
            'main_channel' => $values['main_channel'] ?? '',
            'email' => $values['email'] ?? '',
            'phone' => $values['phone'] ?? '',
            'profile_url' => $values['profile_url'] ?? '',
            'source' => $values['source'] ?? '',
            'priority' => $values['priority'] ?? 'moyenne',
            'relationship_status' => $values['relationship_status'] ?? 'a_relancer',
            'last_contact_at' => $values['last_contact_at'] ?? null,
            'next_action_at' => $values['next_action_at'] ?? null,
            'next_action' => $values['next_action'] ?? '',
            'notes' => $values['notes'] ?? '',
            'tags' => $values['tags'] ?? '',
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function defaultPlatformValues(): array
    {
        return [
            'slug' => '',
            'name' => '',
            'category' => 'reseau',
            'profile_url' => '',
            'status' => 'a_enrichir',
            'note' => '',
            'last_reviewed_at' => '',
            'active' => true,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function defaultContactValues(): array
    {
        return [
            'display_name' => '',
            'first_name' => '',
            'last_name' => '',
            'organization' => '',
            'role' => '',
            'main_channel' => '',
            'email' => '',
            'phone' => '',
            'profile_url' => '',
            'source' => '',
            'priority' => 'moyenne',
            'relationship_status' => 'a_relancer',
            'last_contact_at' => '',
            'next_action_at' => '',
            'next_action' => '',
            'notes' => '',
            'tags' => '',
        ];
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function extractImportRows(Request $request, ContactImportSource $source): array
    {
        $uploadedFile = $request->files->get('file');
        $content = $request->request->getString('content');

        if ($uploadedFile instanceof UploadedFile) {
            return $this->contactImportParser->parseUploadedFile($uploadedFile, $source);
        }

        if ($content === '') {
            throw new InvalidArgumentException('Un fichier ou un contenu à importer est requis.');
        }

        return $this->contactImportParser->parseContent($content, $source);
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

    /**
     * @return array<string, scalar>
     */
    private function extractContactsFilters(Request $request): array
    {
        return array_filter([
            'q' => $request->request->getString('q'),
            'priority' => $request->request->getString('priority'),
            'relationship_status' => $request->request->getString('relationship_status'),
            'page' => 1,
        ], static fn (mixed $value): bool => $value !== '' && $value !== null);
    }
}
