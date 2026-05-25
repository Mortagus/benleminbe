<?php

declare(strict_types=1);

namespace App\Public\Controller;

use App\Public\Service\Dnd\PlayerXmlImportParser;
use InvalidArgumentException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route(path: '/lab', name: 'app_lab_')]
final class LabController extends AbstractController
{
    #[Route(path: '', name: 'index', methods: ['GET'])]
    public function index(): Response
    {
        return $this->render('lab/index.html.twig');
    }

    #[Route(path: '/dnd-initiative', name: 'dnd_initiative', methods: ['GET'])]
    public function initiativeDnd(): Response
    {
        return $this->render('lab/dnd/initiative_tracker.html.twig');
    }

    #[Route(path: '/dnd-initiative/import-player', name: 'dnd_player_import', methods: ['POST'])]
    public function importPlayerXml(Request $request, PlayerXmlImportParser $playerXmlImportParser): JsonResponse
    {
        $uploadedFile = $request->files->get('file');

        if (!$uploadedFile instanceof UploadedFile) {
            return $this->json([
                'message' => 'Un fichier XML est requis.',
            ], Response::HTTP_BAD_REQUEST);
        }

        try {
            return $this->json($playerXmlImportParser->parseUploadedFile($uploadedFile));
        } catch (InvalidArgumentException $exception) {
            return $this->json([
                'message' => $exception->getMessage(),
            ], Response::HTTP_BAD_REQUEST);
        }
    }
}
