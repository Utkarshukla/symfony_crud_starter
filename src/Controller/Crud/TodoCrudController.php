<?php

namespace App\Controller\Crud;

use App\Entity\Todo;
use App\Form\TodoType;
use App\Repository\TodoRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/todo')]
class TodoCrudController extends AbstractController
{
    #[Route('/', name: 'todo_index', methods: ['GET'])]
    public function index(TodoRepository $todoRepository): Response
    {
        return $this->render('todo/index.html.twig', [
            'todos' => $todoRepository->findBy([], ['id' => 'DESC']),
        ]);
    }

    #[Route('/new', name: 'todo_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $em): Response
    {
        $todo = new Todo();
        $form = $this->createForm(TodoType::class, $todo);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->persist($todo);
            $em->flush();
            $this->addFlash('success', 'Todo created');
            return $this->redirectToRoute('todo_index');
        }

        return $this->render('todo/new.html.twig', [
            'form' => $form,
        ]);
    }

    #[Route('/{id}/edit', name: 'todo_edit', methods: ['GET', 'POST'])]
    public function edit(Todo $todo, Request $request, EntityManagerInterface $em): Response
    {
        $form = $this->createForm(TodoType::class, $todo);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $em->flush();
            $this->addFlash('success', 'Todo updated');
            return $this->redirectToRoute('todo_index');
        }
        return $this->render('todo/edit.html.twig', [
            'form' => $form,
            'todo' => $todo,
        ]);
    }

    #[Route('/{id}', name: 'todo_delete', methods: ['POST'])]
    public function delete(Todo $todo, Request $request, EntityManagerInterface $em): Response
    {
        $this->denyAccessUnlessGranted('ROLE_USER');
        if ($this->isCsrfTokenValid('delete_todo_'.$todo->getId(), (string) $request->request->get('_token'))) {
            $em->remove($todo);
            $em->flush();
            $this->addFlash('success', 'Todo deleted');
        }
        return $this->redirectToRoute('todo_index');
    }
}


