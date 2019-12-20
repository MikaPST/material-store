<?php

namespace App\Controller;

use App\Entity\Category;
use App\Form\CategoryType;
use App\Repository\CategoryRepository;
use Psr\Cache\CacheItemPoolInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/category")
 */
class CategoryController extends AbstractController
{
    /**
     * @param CategoryRepository $categoryRepository
     * @param CacheItemPoolInterface $pool
     * @Route("/", name="category_index", methods={"GET"})
     * @return Response
     * @throws \Psr\Cache\InvalidArgumentException
     */
    public function index(CategoryRepository $categoryRepository, CacheItemPoolInterface $pool): Response
    {
        $key = "categories";
        $item = $pool->getItem($key);
        if (!$item->isHit()) {
            $item->set($categoryRepository->findAll());
            $pool->save($item);
        }

        return $this->render('category/index.html.twig', [
            'categories' => $categoryRepository->findAll(),
        ]);
    }

    /**
     * @param Request $request
     * @param CacheItemPoolInterface $cache
     * @param CategoryRepository $repository
     * @return Response
     * @Route("/new", name="category_new", methods={"GET","POST"})
     * @throws \Psr\Cache\InvalidArgumentException
     */
    public function new(Request $request, CacheItemPoolInterface $pool, CategoryRepository $repository): Response
    {

        $category = new Category();
        $form = $this->createForm(CategoryType::class, $category);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->persist($category);
            $entityManager->flush();

            $key = "categories";
            $item = $pool->getItem($key);
            $categories = $item->get();
            if (!$categories) {
                $categories = $repository->findAll();
            } else {
                $categories[] = $form->getData();
            }
            $item->set($categories);
            $pool->save($item);

            return $this->redirectToRoute('category_index');
        }

        return $this->render('category/new.html.twig', [
            'category' => $category,
            'form' => $form->createView(),
        ]);
    }

    /**
     * @Route("/{id}", name="category_show", methods={"GET"})
     */
    public function show(Category $category): Response
    {
        return $this->render('category/show.html.twig', [
            'category' => $category,
        ]);
    }

    /**
     * @Route("/{id}/edit", name="category_edit", methods={"GET","POST"})
     */
    public function edit(Request $request, Category $category): Response
    {
        $form = $this->createForm(CategoryType::class, $category);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->getDoctrine()->getManager()->flush();

            return $this->redirectToRoute('category_index');
        }

        return $this->render('category/edit.html.twig', [
            'category' => $category,
            'form' => $form->createView(),
        ]);
    }

    /**
     * @Route("/{id}", name="category_delete", methods={"DELETE"})
     */
    public function delete(Request $request, Category $category): Response
    {
        if ($this->isCsrfTokenValid('delete' . $category->getId(), $request->request->get('_token'))) {
            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->remove($category);
            $entityManager->flush();
        }

        return $this->redirectToRoute('category_index');
    }
}
