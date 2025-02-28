<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use App\Repository\FormRepository;

final class HomeController extends AbstractController
{
    #[Route('/', name: 'app_home')]
public function index(FormRepository $formRepository): Response
{
    return $this->render('home/index.html.twig', [
        'forms' => $formRepository->findAll(),
    ]);
}

}
