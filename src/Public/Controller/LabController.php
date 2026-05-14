<?php

namespace App\Public\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route(path: '/lab', name: 'app_lab_')]
class LabController extends AbstractController
{
    #[Route(path: '/dnd-initiative', name: 'dnd_initiative')]
    final public function initiativeDnd(): Response
    {
        return $this->render('lab/dnd/initiative_dnd.html.twig');
    }
}
