<?php

namespace App\Service;

use Symfony\Component\DependencyInjection\Attribute\Autowire;

final readonly class CvProvider
{
    public function __construct(
        #[Autowire('%kernel.project_dir%')]
        private string $projectDir,
    ) {}

    public function getCvData(string $locale): array
    {
        $filename = 'benjamin_lemin_backend_developer_' . $locale . '.pdf';
        $relativePath = 'cv/' . $filename;
        $absolutePath = $this->projectDir . '/public/' . $relativePath;

        return [
            'file' => $relativePath,
            'version' => dechex(crc32(filemtime($absolutePath) . filesize($absolutePath))),
        ];
    }
}
