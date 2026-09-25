<?php

declare(strict_types=1);

namespace App\Tests\Functional\Public;

use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class ProfessionalProfileWebTest extends WebTestCase
{
    /**
     * @return iterable<string, array{string}>
     */
    public static function provideUpdatedPublicPages(): iterable
    {
        foreach (['fr', 'en'] as $locale) {
            foreach (['', '/about', '/contact', '/legal-notice', '/privacy-policy'] as $path) {
                yield $locale . ($path !== '' ? $path : '/home') => ['/' . $locale . $path];
            }
        }

        yield 'fr/card' => ['/card'];
        yield 'en/card' => ['/en/card'];
    }

    #[DataProvider('provideUpdatedPublicPages')]
    public function testUpdatedPublicPagesDoNotExposeObsoleteBusinessInformation(string $path): void
    {
        $client = static::createClient();
        $client->request('GET', $path);

        self::assertResponseIsSuccessful();

        $content = $client->getResponse()->getContent();
        self::assertIsString($content);
        self::assertStringNotContainsString('BE 1031.435.246', $content);
        self::assertStringNotContainsString('fr.malt.be/profile/benjaminlemin', $content);
        self::assertDoesNotMatchRegularExpression('/\bfreelance\b/i', $content);
        self::assertDoesNotMatchRegularExpression('/\b20\s?h\s*\/(?:semaine|week)\b/i', $content);
    }

    public function testFrenchAndEnglishPagesStateTheSalariedPositioning(): void
    {
        $client = static::createClient();

        $client->request('GET', '/fr');
        self::assertResponseIsSuccessful();
        self::assertSelectorTextContains('body', 'opportunités professionnelles salariées');

        $client->request('GET', '/en');
        self::assertResponseIsSuccessful();
        self::assertSelectorTextContains('body', 'salaried employment opportunities');
    }

    public function testContactPageKeepsLinkedInAndRemovesTheMaltProfile(): void
    {
        $client = static::createClient();
        $client->request('GET', '/fr/contact');

        self::assertResponseIsSuccessful();
        self::assertSelectorExists('a[href="https://www.linkedin.com/in/benlem/"]');
        self::assertSelectorNotExists('a[href*="malt.be"]');
    }

    public function testVcardDescribesTheCurrentProfessionalProfile(): void
    {
        $client = static::createClient();
        $client->request('GET', '/contact/benjamin-lemin.vcf');

        self::assertResponseIsSuccessful();
        self::assertResponseHeaderSame('content-type', 'text/x-vcard; charset=utf-8');

        $content = $client->getResponse()->getContent();
        self::assertIsString($content);
        self::assertStringContainsString('TITLE:Développeur web expérimenté', $content);
        self::assertStringNotContainsString('ORG:', $content);
        self::assertStringNotContainsString('ADR;TYPE=WORK:', $content);
        self::assertDoesNotMatchRegularExpression('/\bfreelance\b/i', $content);
    }

    public function testCommercialTermsPagesAreNoLongerPublished(): void
    {
        $client = static::createClient();

        $client->request('GET', '/fr/terms-and-conditions');
        self::assertResponseStatusCodeSame(404);

        $client->request('GET', '/en/terms-and-conditions');
        self::assertResponseStatusCodeSame(404);
    }

    public function testCoachingRemainsOngoingWithHistoricalPlatforms(): void
    {
        $client = static::createClient();

        $client->request('GET', '/fr/projects/coaching');
        self::assertResponseIsSuccessful();
        self::assertSelectorTextContains('body', '10/2021 – présent');
        self::assertSelectorTextContains('body', 'Les sessions sont principalement organisées via Superprof.');
        self::assertSelectorTextContains('body', 'J’ai également été présent sur Apprentus et Malt au cours de mon parcours.');

        $client->request('GET', '/en/projects/coaching');
        self::assertResponseIsSuccessful();
        self::assertSelectorTextContains('body', '10/2021 – Present');
        self::assertSelectorTextContains('body', 'Sessions are primarily arranged through Superprof.');
        self::assertSelectorTextContains('body', 'I have also used Apprentus and Malt over the course of my coaching experience.');
    }
}
