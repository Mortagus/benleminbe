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
        $client->request('GET', '/games');

        self::assertResponseIsSuccessful();
        self::assertSelectorTextContains('h1', 'Jeux');
        self::assertSelectorTextContains('body', 'Simon');
        self::assertSelectorTextContains('body', 'Jouer à Simon');
        self::assertStringContainsString('page_games', $client->getResponse()->getContent());
    }

    public function testGamesIndexRendersTheFeaturedSimonCardInEnglish(): void
    {
        $client = static::createClient();
        $client->request('GET', '/games?_locale=en');

        self::assertResponseIsSuccessful();
        self::assertSelectorTextContains('h1', 'Games');
        self::assertSelectorTextContains('body', 'Simon');
        self::assertSelectorTextContains('body', 'Play Simon');
    }

    public function testSimonPageLoadsTheDedicatedEntrypoint(): void
    {
        $client = static::createClient();
        $client->request('GET', '/games/simon');

        self::assertResponseIsSuccessful();
        self::assertSelectorTextContains('h1', 'Simon');
        self::assertStringContainsString('page_games_simon', $client->getResponse()->getContent());
    }

    public function testLegacyLabSimonUrlRedirectsPermanentlyToGamesSimon(): void
    {
        $client = static::createClient();
        $client->request('GET', '/lab/game-simon?round=3');

        self::assertResponseRedirects('/games/simon?round=3', Response::HTTP_MOVED_PERMANENTLY);
    }
}
