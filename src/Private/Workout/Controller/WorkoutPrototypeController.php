<?php

declare(strict_types=1);

namespace App\Private\Workout\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/private/workout', name: 'app_private_workout_')]
final class WorkoutPrototypeController extends AbstractController
{
    private static function prototypeExercises(): array
    {
        return [
            [
                'id' => 'cardio',
                'name' => 'Cardio',
                'goal' => '25 minutes d’exercice, suivies de 5 minutes de récupération',
                'sets' => [[
                    'id' => '1',
                    'label' => 'Bloc cardio',
                    'fields' => [
                        ['id' => 'duration', 'label' => 'Exercice (minutes)', 'value' => '25', 'step' => '1', 'inputmode' => 'numeric'],
                        ['id' => 'recovery', 'label' => 'Récupération (minutes)', 'value' => '5', 'step' => '1', 'inputmode' => 'numeric'],
                    ],
                ]],
            ],
            [
                'id' => 'leg-press',
                'name' => 'Leg Press',
                'goal' => '3 séries de 12 répétitions à 90 kg',
                'sets' => self::createStrengthSets('90', '12'),
            ],
            [
                'id' => 'seated-row',
                'name' => 'Seated Row',
                'goal' => '3 séries de 12 répétitions à 45 kg',
                'sets' => self::createStrengthSets('45', '12'),
            ],
            [
                'id' => 'romanian-deadlift',
                'name' => 'Romanian Deadlift',
                'goal' => '3 séries de 12 répétitions à 50 kg',
                'sets' => self::createStrengthSets('50', '12'),
            ],
            [
                'id' => 'converging-chest-press',
                'name' => 'Converging Chest Press',
                'goal' => '3 séries de 8 répétitions à 45 kg',
                'sets' => self::createStrengthSets('45', '8'),
            ],
            [
                'id' => 'suitcase-carry',
                'name' => 'Suitcase Carry',
                'goal' => '3 allers-retours à 32 kg',
                'sets' => self::createSuitcaseCarrySets(),
            ],
        ];
    }

    #[Route('/prototype', name: 'prototype', methods: ['GET'])]
    public function prototype(): Response
    {
        return $this->render('private/workout/prototype.html.twig', [
            'exercises' => self::prototypeExercises(),
        ]);
    }

    private static function createStrengthSets(string $load, string $repetitions): array
    {
        return array_map(static fn (int $number): array => [
            'id' => (string) $number,
            'label' => sprintf('Série %d', $number),
            'fields' => [
                ['id' => 'load', 'label' => 'Charge (kg)', 'value' => $load, 'step' => '1.25', 'inputmode' => 'decimal'],
                ['id' => 'repetitions', 'label' => 'Répétitions', 'value' => $repetitions, 'step' => '1', 'inputmode' => 'numeric'],
            ],
        ], range(1, 3));
    }

    private static function createSuitcaseCarrySets(): array
    {
        return array_map(static fn (int $number): array => [
            'id' => (string) $number,
            'label' => sprintf('Aller-retour %d', $number),
            'fields' => [
                ['id' => 'load', 'label' => 'Charge (kg)', 'value' => '32', 'step' => '1.25', 'inputmode' => 'decimal'],
                ['id' => 'round-trips', 'label' => 'Allers-retours', 'value' => '1', 'step' => '1', 'inputmode' => 'numeric'],
            ],
        ], range(1, 3));
    }
}
