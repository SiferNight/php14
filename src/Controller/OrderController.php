<?php

namespace App\Controller;

use App\Entity\Order;
use App\Entity\File;
use App\Form\OrderType;
use App\Repository\OrderRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\HeaderUtils;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\String\Slugger\SluggerInterface;

#[Route('/order')]
class OrderController extends AbstractController
{
    #[Route('/', name: 'app_order_index', methods: ['GET'])]
    public function index(OrderRepository $orderRepository): Response
    {
        return $this->render('order/index.html.twig', [
            'orders' => $orderRepository->findAll(),
        ]);
    }

    #[Route('/{id}', name: 'app_order_show', methods: ['GET'])]
    public function show(Order $order): Response
    {
        return $this->render('order/show.html.twig', [
            'order' => $order,
        ]);
    }

    #[Route('/new', name: 'app_order_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager, SluggerInterface $slugger): Response
    {
        $order = new Order();
        $form = $this->createForm(OrderType::class, $order);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Обработка загрузки файлов
            $uploadedFiles = $form->get('files')->getData();
            
            if ($uploadedFiles) {
                foreach ($uploadedFiles as $uploadedFile) {
                    if ($uploadedFile) {
                        $fileEntity = $this->handleFileUpload($uploadedFile, $slugger, 'order_document');
                        if ($fileEntity) {
                            $order->addFile($fileEntity);
                            $entityManager->persist($fileEntity);
                        }
                    }
                }
            }

            $entityManager->persist($order);
            $entityManager->flush();

            $this->addFlash('success', 'Заказ успешно создан');
            return $this->redirectToRoute('app_order_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('order/new.html.twig', [
            'order' => $order,
            'form' => $form,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_order_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Order $order, EntityManagerInterface $entityManager, SluggerInterface $slugger): Response
    {
        $form = $this->createForm(OrderType::class, $order);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Обработка загрузки новых файлов
            $uploadedFiles = $form->get('files')->getData();
            
            if ($uploadedFiles) {
                foreach ($uploadedFiles as $uploadedFile) {
                    if ($uploadedFile) {
                        $fileEntity = $this->handleFileUpload($uploadedFile, $slugger, 'order_document');
                        if ($fileEntity) {
                            $order->addFile($fileEntity);
                            $entityManager->persist($fileEntity);
                        }
                    }
                }
            }

            $entityManager->flush();
            $this->addFlash('success', 'Заказ успешно обновлен');

            return $this->redirectToRoute('app_order_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('order/edit.html.twig', [
            'order' => $order,
            'form' => $form,
        ]);
    }

    #[Route('/{id}/file/{fileId}/download', name: 'app_order_download_file', methods: ['GET'])]
    public function downloadFile(Order $order, int $fileId, EntityManagerInterface $entityManager): Response
    {
        $file = $entityManager->getRepository(File::class)->find($fileId);
        
        if (!$file || !$order->getFiles()->contains($file)) {
            throw $this->createNotFoundException('Файл не найден');
        }

        $filepath = $this->getFilepath($file);
        if (!file_exists($filepath)) {
            throw $this->createNotFoundException('Файл не найден на сервере');
        }

        $response = new BinaryFileResponse($filepath);
        $response->setContentDisposition(
            HeaderUtils::DISPOSITION_ATTACHMENT,
            $file->getOriginalName()
        );

        return $response;
    }

    #[Route('/{id}/file/{fileId}/delete', name: 'app_order_delete_file', methods: ['POST'])]
    public function deleteFile(Request $request, Order $order, int $fileId, EntityManagerInterface $entityManager): Response
    {
        $file = $entityManager->getRepository(File::class)->find($fileId);
        
        if (!$file || !$order->getFiles()->contains($file)) {
            throw $this->createNotFoundException('Файл не найден');
        }

        if ($this->isCsrfTokenValid('delete-file'.$file->getId(), $request->request->get('_token'))) {
            // Удаляем файл с диска
            $this->deleteFileFromDisk($file);
            
            // Удаляем связь и файл из БД
            $order->removeFile($file);
            $entityManager->remove($file);
            $entityManager->flush();

            $this->addFlash('success', 'Файл удален');
        }

        return $this->redirectToRoute('app_order_edit', ['id' => $order->getId()]);
    }

    #[Route('/{id}', name: 'app_order_delete', methods: ['POST'])]
    public function delete(Request $request, Order $order, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$order->getId(), $request->request->get('_token'))) {
            // Удаляем файлы заказа
            foreach ($order->getFiles() as $file) {
                $this->deleteFileFromDisk($file);
                $entityManager->remove($file);
            }

            $entityManager->remove($order);
            $entityManager->flush();
            
            $this->addFlash('success', 'Заказ удален');
        }

        return $this->redirectToRoute('app_order_index', [], Response::HTTP_SEE_OTHER);
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

    private function getFilepath(File $file): string
    {
        $uploadDir = $file->getType() === 'dish_image' ? 'dishes' : 'orders';
        return $this->getParameter('kernel.project_dir') . '/public/uploads/' . $uploadDir . '/' . $file->getFilename();
    }
}