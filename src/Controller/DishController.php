<?php

namespace App\Controller;

use App\Entity\Dish;
use App\Entity\File;
use App\Form\DishType;
use App\Repository\DishRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\String\Slugger\SluggerInterface;

#[Route('/dish')]
class DishController extends AbstractController
{
    #[Route('/', name: 'app_dish_index', methods: ['GET'])]
    public function index(DishRepository $dishRepository): Response
    {
        return $this->render('dish/index.html.twig', [
            'dishes' => $dishRepository->findAll(),
        ]);
    }

    #[Route('/new', name: 'app_dish_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager, SluggerInterface $slugger): Response
    {
        $dish = new Dish();
        $form = $this->createForm(DishType::class, $dish);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Обработка загрузки изображения
            $imageFile = $form->get('image')->getData();
            
            if ($imageFile) {
                $fileEntity = $this->handleFileUpload($imageFile, $slugger, 'dish_image');
                if ($fileEntity) {
                    $dish->setImage($fileEntity);
                    $entityManager->persist($fileEntity);
                }
            }

            $entityManager->persist($dish);
            $entityManager->flush();

            $this->addFlash('success', 'Блюдо успешно создано');
            return $this->redirectToRoute('app_dish_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('dish/new.html.twig', [
            'dish' => $dish,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_dish_show', methods: ['GET'])]
    public function show(Dish $dish): Response
    {
        return $this->render('dish/show.html.twig', [
            'dish' => $dish,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_dish_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Dish $dish, EntityManagerInterface $entityManager, SluggerInterface $slugger): Response
    {
        $form = $this->createForm(DishType::class, $dish);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Обработка загрузки изображения
            $imageFile = $form->get('image')->getData();
            
            if ($imageFile) {
                // Удаляем старое изображение если есть
                $oldImage = $dish->getImage();
                if ($oldImage) {
                    $this->deleteFileFromDisk($oldImage);
                    $entityManager->remove($oldImage);
                }

                $fileEntity = $this->handleFileUpload($imageFile, $slugger, 'dish_image');
                if ($fileEntity) {
                    $dish->setImage($fileEntity);
                    $entityManager->persist($fileEntity);
                }
            }

            $entityManager->flush();
            $this->addFlash('success', 'Блюдо успешно обновлено');

            return $this->redirectToRoute('app_dish_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('dish/edit.html.twig', [
            'dish' => $dish,
            'form' => $form,
        ]);
    }

    #[Route('/{id}/delete-image', name: 'app_dish_delete_image', methods: ['POST'])]
    public function deleteImage(Request $request, Dish $dish, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete-image'.$dish->getId(), $request->request->get('_token'))) {
            $image = $dish->getImage();
            if ($image) {
                $this->deleteFileFromDisk($image);
                $dish->setImage(null);
                $entityManager->remove($image);
                $entityManager->flush();
                $this->addFlash('success', 'Изображение удалено');
            }
        }

        return $this->redirectToRoute('app_dish_edit', ['id' => $dish->getId()]);
    }

    #[Route('/{id}', name: 'app_dish_delete', methods: ['POST'])]
    public function delete(Request $request, Dish $dish, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$dish->getId(), $request->request->get('_token'))) {
            // Удаляем изображение если есть
            $image = $dish->getImage();
            if ($image) {
                $this->deleteFileFromDisk($image);
                $entityManager->remove($image);
            }

            $entityManager->remove($dish);
            $entityManager->flush();
            
            $this->addFlash('success', 'Блюдо удалено');
        }

        return $this->redirectToRoute('app_dish_index', [], Response::HTTP_SEE_OTHER);
    }

    private function handleFileUpload($uploadedFile, SluggerInterface $slugger, string $type): ?File
    {
        $allowedMimeTypes = [
            'image/jpeg', 'image/png', 'image/jpg',
            'application/pdf', 
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'text/plain'
        ];
        
        if (!in_array($uploadedFile->getMimeType(), $allowedMimeTypes)) {
            $this->addFlash('error', 'Недопустимый тип файла: ' . $uploadedFile->getClientOriginalName());
            return null;
        }

        $originalFilename = pathinfo($uploadedFile->getClientOriginalName(), PATHINFO_FILENAME);
        $safeFilename = $slugger->slug($originalFilename);
        $newFilename = $safeFilename.'-'.uniqid().'.'.$uploadedFile->guessExtension();

        $projectDir = $this->getParameter('kernel.project_dir');
        $uploadDir = $type === 'dish_image' ? 'dishes' : 'orders';
        
        try {
            $uploadedFile->move(
                $projectDir . '/public/uploads/' . $uploadDir,
                $newFilename
            );

            $fileEntity = new File();
            $fileEntity->setFilename($newFilename);
            $fileEntity->setOriginalName($uploadedFile->getClientOriginalName());
            $fileEntity->setMimeType($uploadedFile->getMimeType());
            $fileEntity->setType($type);

            return $fileEntity;

        } catch (\Exception $e) {
            $this->addFlash('error', 'Ошибка при загрузке файла: ' . $uploadedFile->getClientOriginalName());
            return null;
        }
    }

    private function deleteFileFromDisk(File $file): void
    {
        $uploadDir = $file->getType() === 'dish_image' ? 'dishes' : 'orders';
        $filepath = $this->getParameter('kernel.project_dir') . '/public/uploads/' . $uploadDir . '/' . $file->getFilename();
        if (file_exists($filepath)) {
            unlink($filepath);
        }
    }
}