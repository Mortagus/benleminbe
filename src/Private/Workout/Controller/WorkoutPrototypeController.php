<?php

declare(strict_types=1);

namespace App\Private\Workout\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/private/workout', name: 'app_private_workout_')]
final class WorkoutPrototypeController extends AbstractController
{
    #[Route('/prototype', name: 'prototype', methods: ['GET'])]
    public function prototype(): Response
    {
        return $this->render('private/workout/prototype.html.twig');
    }
}
