<?php

namespace App\Public\Service;

use Symfony\Component\DependencyInjection\Attribute\Autowire;

final readonly class CvProvider
{
    public function __construct(
        #[Autowire('%kernel.project_dir%')]
        private string $projectDir,
    ) {}

    public function getCvData(string $locale): array
    {
        $filename = 'Benjamin_Lemin_Senior_Backend_Developer_CV_2026_' . $locale . '.pdf';
        $relativePath = 'files/cv/' . $filename;
        $absolutePath = $this->projectDir . '/public/' . $relativePath;

        return [
            'file' => $relativePath,
            'version' => dechex(crc32(filemtime($absolutePath) . filesize($absolutePath))),
        ];
    }
}
