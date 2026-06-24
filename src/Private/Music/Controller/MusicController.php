<?php

declare(strict_types=1);

namespace App\Private\Music\Controller;

use App\Private\Music\Service\Import\MusicImportService;
use App\Private\Music\Service\Statistics\MusicStatisticsService;
use InvalidArgumentException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/private/music', name: 'app_private_music_')]
final class MusicController extends AbstractController
{
    private const int LIST_PAGE_SIZE = 20;

    public function __construct(
        private readonly MusicImportService $musicImportService,
        private readonly MusicStatisticsService $musicStatisticsService,
    ) {
    }

    #[Route('', name: 'index', methods: ['GET'])]
    public function index(): Response
    {
        return $this->render('private/music/index.html.twig', $this->musicStatisticsService->getDashboardData());
    }

    #[Route('/import', name: 'import', methods: ['GET', 'POST'])]
    public function import(Request $request): Response|RedirectResponse
    {
        if ($request->isMethod('POST')) {
            if (!$this->isCsrfTokenValid('private-music-import', $request->request->getString('_token'))) {
                $this->addFlash('error', 'Le formulaire d import a expiré. Réessaie.');

                return $this->redirectToRoute('app_private_music_import');
            }

            $uploadedFile = $request->files->get('archive');
            if (!$uploadedFile instanceof UploadedFile || !$uploadedFile->isValid()) {
                return $this->renderImportPage(['Le fichier ZIP Spotify est obligatoire et doit être valide.']);
            }

            try {
                $result = $this->musicImportService->importSpotifyArchive($uploadedFile);
            } catch (InvalidArgumentException $exception) {
                return $this->renderImportPage([$exception->getMessage()]);
            } catch (\Throwable $exception) {
                return $this->renderImportPage([sprintf('L import Spotify a échoué: %s', $exception->getMessage())]);
            }

            if (($result['summary']['duplicate'] ?? false) === true) {
                $this->addFlash('info', 'Cette archive a déjà été importée.');
            } else {
                $this->addFlash('success', 'Archive Spotify importée avec succès.');
            }

            return $this->renderImportPage([], $result);
        }

        return $this->renderImportPage();
    }

    #[Route('/import/reset-hard', name: 'import_reset_hard', methods: ['POST'])]
    public function resetHard(Request $request): RedirectResponse
    {
        if (!$this->isCsrfTokenValid('private-music-import-reset-hard', $request->request->getString('_token'))) {
            $this->addFlash('error', 'La réinitialisation a expiré. Réessaie.');

            return $this->redirectToRoute('app_private_music_import');
        }

        $deletedRows = $this->musicImportService->hardResetMusicData();

        $this->addFlash(
            'success',
            sprintf(
                'Réinitialisation terminée: %d import(s), %d artiste(s), %d titre(s) et %d écoute(s) supprimés.',
                $deletedRows['imports'] ?? 0,
                $deletedRows['artists'] ?? 0,
                $deletedRows['tracks'] ?? 0,
                $deletedRows['listening_events'] ?? 0,
            ),
        );

        return $this->redirectToRoute('app_private_music_import');
    }

    #[Route('/artists', name: 'artists', methods: ['GET'])]
    public function artists(Request $request): Response
    {
        $page = max(1, $request->query->getInt('page', 1));

        return $this->render('private/music/artists/index.html.twig', $this->musicStatisticsService->getArtistsPage($this->extractListFilters($request), $page, self::LIST_PAGE_SIZE));
    }

    #[Route('/tracks', name: 'tracks', methods: ['GET'])]
    public function tracks(Request $request): Response
    {
        $page = max(1, $request->query->getInt('page', 1));

        return $this->render('private/music/tracks/index.html.twig', $this->musicStatisticsService->getTracksPage($this->extractListFilters($request), $page, self::LIST_PAGE_SIZE));
    }

    #[Route('/albums', name: 'albums', methods: ['GET'])]
    public function albums(): Response
    {
        $dashboard = $this->musicStatisticsService->getDashboardData();

        return $this->render('private/music/albums/index.html.twig', [
            'albums_total' => $dashboard['stats']['albums_total'],
            'albums_available' => $dashboard['stats']['albums_available'],
        ]);
    }

    #[Route('/genres', name: 'genres', methods: ['GET'])]
    public function genres(): Response
    {
        return $this->render('private/music/genres/index.html.twig', $this->musicStatisticsService->getGenresPage());
    }

    /**
     * @param list<string> $errors
     * @param array<string, mixed>|null $result
     */
    private function renderImportPage(array $errors = [], ?array $result = null): Response
    {
        return $this->render('private/music/import.html.twig', [
            'errors' => $errors,
            'result' => $result,
            ...$this->musicImportService->getImportPageContext(),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function extractListFilters(Request $request): array
    {
        return [
            'search' => $request->query->getString('q'),
            'sort' => $request->query->getString('sort'),
            'direction' => $request->query->getString('direction'),
            'genre' => $request->query->getString('genre'),
        ];
    }
}
