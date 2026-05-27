<?php

declare(strict_types=1);

namespace App\Private\Controller;

use App\Private\Service\Network\NetworkRepository;
use InvalidArgumentException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/private/network', name: 'app_private_network_')]
final class NetworkController extends AbstractController
{
    public function __construct(
        private readonly NetworkRepository $networkRepository,
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

        return $this->render('private/network/contacts/index.html.twig', [
            'currentQuery' => $filters['search'],
            'currentPriority' => $filters['priority'],
            'currentRelationStatus' => $filters['relationship_status'],
            'contacts' => $this->networkRepository->listContacts($filters),
            'priorityOptions' => $this->networkRepository->getContactPriorityOptions(),
            'relationOptions' => $this->networkRepository->getContactRelationOptions(),
        ]);
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
        if ($request->isMethod('POST')) {
            if (!$this->isCsrfTokenValid('private-network-import', $request->request->getString('_token'))) {
                $this->addFlash('error', 'Le formulaire d import a expiré. Réessaie.');

                return $this->redirectToRoute('app_private_network_import');
            }

            $sourceLabel = $request->request->getString('source_label');
            $rows = [];

            try {
                $rows = $this->extractImportRows($request);
            } catch (InvalidArgumentException $exception) {
                return $this->render('private/network/import.html.twig', [
                    'errors' => [$exception->getMessage()],
                    'values' => $this->extractImportValues($request),
                ]);
            }

            if ($rows === []) {
                return $this->render('private/network/import.html.twig', [
                    'errors' => ['Aucune ligne importable n a été trouvée.'],
                    'values' => $this->extractImportValues($request),
                ]);
            }

            $summary = $this->networkRepository->importContacts($rows, $sourceLabel !== '' ? $sourceLabel : 'Import manuel');
            $this->addFlash('success', sprintf('%d lignes traitées: %d créées, %d mises à jour.', $summary['total'], $summary['created'], $summary['updated']));

            return $this->redirectToRoute('app_private_network_contacts');
        }

        return $this->render('private/network/import.html.twig', [
            'errors' => [],
            'values' => [
                'source_label' => '',
                'content' => '',
                'format' => 'auto',
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
    private function extractImportRows(Request $request): array
    {
        $uploadedFile = $request->files->get('file');
        $content = $request->request->getString('content');
        $format = $request->request->getString('format');

        if ($uploadedFile instanceof UploadedFile) {
            return $this->parseImportFile($uploadedFile);
        }

        if ($content === '') {
            throw new InvalidArgumentException('Un fichier ou un contenu à importer est requis.');
        }

        return match ($format) {
            'json' => $this->parseJsonImport($content),
            'csv' => $this->parseCsvImport($content),
            default => $this->looksLikeJson($content) ? $this->parseJsonImport($content) : $this->parseCsvImport($content),
        };
    }

    /**
     * @return array<string, mixed>
     */
    private function extractImportValues(Request $request): array
    {
        return [
            'source_label' => $request->request->getString('source_label'),
            'content' => $request->request->getString('content'),
            'format' => $request->request->getString('format', 'auto'),
        ];
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function parseImportFile(UploadedFile $uploadedFile): array
    {
        $content = file_get_contents($uploadedFile->getPathname());
        if ($content === false) {
            throw new InvalidArgumentException('Impossible de lire le fichier importé.');
        }

        $extension = strtolower((string) $uploadedFile->getClientOriginalExtension());

        return match ($extension) {
            'json' => $this->parseJsonImport($content),
            default => $this->parseCsvImport($content),
        };
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function parseCsvImport(string $content): array
    {
        $lines = preg_split('/\r\n|\r|\n/', trim($content)) ?: [];
        if ($lines === []) {
            return [];
        }

        $headerLine = (string) array_shift($lines);
        $delimiter = substr_count($headerLine, ';') > substr_count($headerLine, ',') ? ';' : ',';
        $headers = str_getcsv($this->stripBom($headerLine), $delimiter, '"', "\\");
        if ($headers === []) {
            return [];
        }

        $rows = [];

        foreach ($lines as $line) {
            if (trim($line) === '') {
                continue;
            }

            $values = str_getcsv($line, $delimiter, '"', "\\");
            if ($values === []) {
                continue;
            }

            $row = [];
            foreach ($headers as $index => $header) {
                $row[$this->normalizeCsvHeader((string) $header)] = $values[$index] ?? '';
            }

            $rows[] = $this->mapImportedRow($row);
        }

        return $rows;
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function parseJsonImport(string $content): array
    {
        $decoded = json_decode($content, true);
        if (!is_array($decoded)) {
            throw new InvalidArgumentException('Le JSON importé est invalide.');
        }

        $rows = isset($decoded['contacts']) && is_array($decoded['contacts']) ? $decoded['contacts'] : $decoded;
        if (!is_array($rows)) {
            throw new InvalidArgumentException('Le JSON importé doit contenir une liste de contacts.');
        }

        $mappedRows = [];

        foreach ($rows as $row) {
            if (!is_array($row)) {
                continue;
            }

            $mappedRows[] = $this->mapImportedRow($row);
        }

        return $mappedRows;
    }

    /**
     * @param array<string, mixed> $row
     *
     * @return array<string, mixed>
     */
    private function mapImportedRow(array $row): array
    {
        return [
            'display_name' => $row['display_name'] ?? $row['name'] ?? $row['full_name'] ?? '',
            'first_name' => $row['first_name'] ?? '',
            'last_name' => $row['last_name'] ?? '',
            'organization' => $row['organization'] ?? $row['company'] ?? '',
            'role' => $row['role'] ?? $row['job_title'] ?? '',
            'main_channel' => $row['main_channel'] ?? $row['channel'] ?? '',
            'email' => $row['email'] ?? '',
            'phone' => $row['phone'] ?? '',
            'profile_url' => $row['profile_url'] ?? $row['linkedin_url'] ?? '',
            'source' => $row['source'] ?? $row['origin'] ?? '',
            'priority' => $row['priority'] ?? 'moyenne',
            'relationship_status' => $row['relationship_status'] ?? $row['status'] ?? 'a_relancer',
            'last_contact_at' => $row['last_contact_at'] ?? $row['last_contact'] ?? '',
            'next_action_at' => $row['next_action_at'] ?? '',
            'next_action' => $row['next_action'] ?? '',
            'notes' => $row['notes'] ?? '',
            'tags' => $row['tags'] ?? '',
        ];
    }

    private function normalizeCsvHeader(string $header): string
    {
        return strtolower(trim(str_replace([' ', '-'], '_', $header)));
    }

    private function stripBom(string $value): string
    {
        return preg_replace('/^\xEF\xBB\xBF/', '', $value) ?? $value;
    }

    private function looksLikeJson(string $content): bool
    {
        $trimmed = ltrim($content);

        return str_starts_with($trimmed, '{') || str_starts_with($trimmed, '[');
    }
}
