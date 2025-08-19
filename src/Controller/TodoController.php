<?php

namespace App\Controller;

use App\Repository\TodoRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class TodoController extends AbstractController
{
    #[Route('/todos/public', name: 'todos_public', methods: ['GET'])]
    public function publicIndex(TodoRepository $todoRepository): Response
    {
        $todos = $todoRepository->findBy([], ['id' => 'DESC']);
        return $this->render('todo/public.html.twig', [
            'todos' => $todos,
        ]);
    }
}


