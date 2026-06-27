<?php

declare(strict_types=1);

namespace App\Tests\Functional\Public;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;

final class GamesWebTest extends WebTestCase
{
    public function testGamesIndexRendersTheFeaturedSimonCardInFrench(): void
    {
        $client = static::createClient();
        $client->request('GET', '/fr/games');

        self::assertResponseIsSuccessful();
        self::assertStringContainsString('<html lang="fr">', $client->getResponse()->getContent());
        self::assertSelectorTextContains('h1', 'Jeux');
        self::assertSelectorTextContains('body', 'Simon');
        self::assertSelectorTextContains('body', 'Jouer à Simon');
        self::assertStringContainsString('page_games', $client->getResponse()->getContent());
        self::assertStringContainsString('href="/en/games"', $client->getResponse()->getContent());
    }

    public function testGamesIndexRendersTheFeaturedSimonCardInEnglish(): void
    {
        $client = static::createClient();
        $client->request('GET', '/en/games');

        self::assertResponseIsSuccessful();
        self::assertStringContainsString('<html lang="en">', $client->getResponse()->getContent());
        self::assertSelectorTextContains('h1', 'Games');
        self::assertSelectorTextContains('body', 'Simon');
        self::assertSelectorTextContains('body', 'Play Simon');
        self::assertStringContainsString('href="/fr/games"', $client->getResponse()->getContent());
    }

    public function testSimonPageLoadsTheDedicatedEntrypoint(): void
    {
        $client = static::createClient();
        $client->request('GET', '/fr/games/simon');

        self::assertResponseIsSuccessful();
        self::assertStringContainsString('<html lang="fr">', $client->getResponse()->getContent());
        self::assertSelectorTextContains('h1', 'Simon');
        self::assertStringContainsString('page_games_simon', $client->getResponse()->getContent());
    }

    public function testLegacyGamesIndexUrlRedirectsToTheFrenchGamesIndex(): void
    {
        $client = static::createClient();
        $client->request('GET', '/games?mode=arcade');

        self::assertResponseRedirects('/fr/games?mode=arcade', Response::HTTP_MOVED_PERMANENTLY);
    }

    public function testLegacyGamesSimonUrlRedirectsToTheFrenchSimonPage(): void
    {
        $client = static::createClient();
        $client->request('GET', '/games/simon?round=3');

        self::assertResponseRedirects('/fr/games/simon?round=3', Response::HTTP_MOVED_PERMANENTLY);
    }

    public function testLegacyLabSimonUrlRedirectsPermanentlyToGamesSimon(): void
    {
        $client = static::createClient();
        $client->request('GET', '/lab/game-simon?round=3');

        self::assertResponseRedirects('/fr/games/simon?round=3', Response::HTTP_MOVED_PERMANENTLY);
    }
}
