<?php

namespace App\Controller;

use App\Entity\Comment;
use App\Entity\Todo;
use App\Form\CommentType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/comment')]
class CommentController extends AbstractController
{
    #[Route('/new/{id}', name: 'comment_new', methods: ['POST'])]
    public function new(Todo $todo, Request $request, EntityManagerInterface $em): Response
    {
        $this->denyAccessUnlessGranted('ROLE_USER');
        $comment = new Comment();
        $form = $this->createForm(CommentType::class, $comment);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $comment->setTodo($todo);
            $em->persist($comment);
            $em->flush();
            $this->addFlash('success', 'Comment added');
        }
        return $this->redirectToRoute('todo_edit', ['id' => $todo->getId()]);
    }

    #[Route('/{id}', name: 'comment_delete', methods: ['POST'])]
    public function delete(Comment $comment, Request $request, EntityManagerInterface $em): Response
    {
        $this->denyAccessUnlessGranted('ROLE_USER');
        $todoId = $comment->getTodo()->getId();
        if ($this->isCsrfTokenValid('delete_comment_'.$comment->getId(), (string) $request->request->get('_token'))) {
            $em->remove($comment);
            $em->flush();
            $this->addFlash('success', 'Comment deleted');
        }
        return $this->redirectToRoute('todo_edit', ['id' => $todoId]);
    }
}


