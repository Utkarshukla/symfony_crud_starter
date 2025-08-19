<?php

namespace App\Controller\Crud;

use App\Entity\Category;
use App\Form\CategoryType;
use App\Repository\CategoryRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/category')]
class CategoryCrudController extends AbstractController
{
    #[Route('/', name: 'category_index', methods: ['GET'])]
    public function index(CategoryRepository $categoryRepository): Response
    {
        return $this->render('category/index.html.twig', [
            'categories' => $categoryRepository->findBy([], ['name' => 'ASC']),
        ]);
    }

    #[Route('/new', name: 'category_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $em): Response
    {
        $category = new Category();
        $form = $this->createForm(CategoryType::class, $category);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $em->persist($category);
            $em->flush();
            $this->addFlash('success', 'Category created');
            return $this->redirectToRoute('category_index');
        }
        return $this->render('category/new.html.twig', [
            'form' => $form,
        ]);
    }

    #[Route('/{id}/edit', name: 'category_edit', methods: ['GET', 'POST'])]
    public function edit(Category $category, Request $request, EntityManagerInterface $em): Response
    {
        $form = $this->createForm(CategoryType::class, $category);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $em->flush();
            $this->addFlash('success', 'Category updated');
            return $this->redirectToRoute('category_index');
        }
        return $this->render('category/edit.html.twig', [
            'form' => $form,
            'category' => $category,
        ]);
    }

    #[Route('/{id}', name: 'category_delete', methods: ['POST'])]
    public function delete(Category $category, Request $request, EntityManagerInterface $em): Response
    {
        $this->denyAccessUnlessGranted('ROLE_USER');
        if ($this->isCsrfTokenValid('delete_category_'.$category->getId(), (string) $request->request->get('_token'))) {
            $em->remove($category);
            $em->flush();
            $this->addFlash('success', 'Category deleted');
        }
        return $this->redirectToRoute('category_index');
    }
}


