<?php

declare(strict_types=1);

namespace App\Public\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class BusinessCardController extends AbstractController
{
    #[Route(
        path: [
            'fr' => '/card',
            'en' => '/en/card',
        ],
        name: 'app_card',
        options: [
            'sitemap' => [
                'enabled' => true,
                'locales' => ['fr', 'en'],
                'lastmod' => '2026-09-22',
            ],
        ],
        methods: ['GET'],
    )]
    public function card(): Response
    {
        return $this->render('pages/card.html.twig');
    }

    #[Route(
        path: '/contact/benjamin-lemin.vcf',
        name: 'app_contact_vcard',
        methods: ['GET'],
    )]
    public function vcard(): Response
    {
        $fields = [
            'BEGIN:VCARD',
            'VERSION:3.0',
            'FN:' . $this->escapeVcardText('Benjamin Lemin'),
            'N:' . implode(';', [
                $this->escapeVcardText('Lemin'),
                $this->escapeVcardText('Benjamin'),
                '',
                '',
                '',
            ]),
            'TITLE:' . $this->escapeVcardText('Développeur web expérimenté'),
            'EMAIL;TYPE=INTERNET:' . $this->escapeVcardText('benjamin@lemin.be'),
            'URL:' . $this->escapeVcardText('https://benlemin.be'),
            'NOTE:' . $this->escapeVcardText('Développeur web expérimenté spécialisé en backend PHP, Symfony et Drupal, avec une expérience full-stack'),
            'END:VCARD',
            '',
        ];

        return new Response(
            implode("\r\n", $fields),
            Response::HTTP_OK,
            [
                'Content-Type' => 'text/x-vcard; charset=utf-8',
                'Content-Disposition' => 'inline; filename="benjamin-lemin.vcf"; filename*=UTF-8\'\'benjamin-lemin.vcf',
            ],
        );
    }

    private function escapeVcardText(string $value): string
    {
        return str_replace(
            ["\\", "\r\n", "\n", "\r", ';', ','],
            ['\\\\', '\\n', '\\n', '\\n', '\\;', '\\,'],
            $value,
        );
    }
}
