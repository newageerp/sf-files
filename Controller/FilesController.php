<?php

namespace Newageerp\SfFiles\Controller;

use Doctrine\ORM\EntityManagerInterface;
use Newageerp\SfBaseEntity\Controller\OaBaseController;
use Newageerp\SfFiles\Object\FileBase;
use Newageerp\SfFiles\Service\FileService;
use Newageerp\SfSocket\Event\SocketSendPoolEvent;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\HeaderUtils;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use OpenApi\Annotations as OA;

/**
 * @Route("/app/nae-core/files")
 */
class FilesController extends OaBaseController
{
    protected string $className = 'App\Entity\File';

    protected FileService $fileService;

    public function __construct(FileService $fileService, EntityManagerInterface $em, EventDispatcherInterface $eventDispatcher)
    {
        parent::__construct($em, $eventDispatcher);
        $this->fileService = $fileService;
    }

    /**
     * @param Request $request
     * @param EntityManagerInterface $entityManager
     * @return Response
     * @Route ("/upload", methods={"POST"})
     */
    public function upload(Request $request, EntityManagerInterface $entityManager): Response
    {
        $className = $this->className;

        try {
            if (!($user = $this->findUser($request))) {
                throw new \Exception('Invalid user');
            }

            $files = $request->files;

            $folder = $request->get('folder') ?: 'uploads';
            $this->fileService->createFolder($folder);

            $path = $this->fileService->getLocalStorage() . '/' . ltrim($folder, '/');

            $output = [];

            /**
             * @var UploadedFile $file
             */
            foreach ($files as $key => $file) {
                $orm = new $className();
                $orm->setCreator($user);


                $newFileName = $key . random_int(0, 1000) . '' . time() . '___' . mb_strtolower($file->getClientOriginalName());
                $filePath = $path . '/' . $newFileName;
                $localPath = $folder . '/' . $newFileName;

                file_put_contents(
                    $filePath,
                    file_get_contents($file->getPathname())
                );
                $output[$key] = [
                    'path' => $localPath,
                    'filename' => mb_strtolower($file->getClientOriginalName()),
                ];

                $orm->setFolder($folder);
                $orm->setFileName($newFileName);
                $orm->setOrgFileName(mb_strtolower($file->getClientOriginalName()));
                $orm->setPath(ltrim($localPath, '/'));
                $entityManager->persist($orm);
            }
            $entityManager->flush();

            $event = new SocketSendPoolEvent();
            $this->eventDispatcher->dispatch($event, SocketSendPoolEvent::NAME);

            return $this->json(['success' => 1, 'data' => $output]);
        } catch (\Exception $e) {
            return $this->json(['success' => 0, 'e' => $e->getMessage(), 'f' => $e->getFile(), 'l' => $e->getLine()]);
        }
    }

    /**
     * @Route (path="/download", methods={"GET"})
     */
    public function download(Request $request): BinaryFileResponse|\Symfony\Component\HttpFoundation\JsonResponse
    {
        try {
            $file = $request->get('f');
            if (!$file) {
                $this->json(['success' => 0]);
            }
            $file = json_decode($file, true, 512, JSON_THROW_ON_ERROR);

            $path = $this->fileService->getLocalStorage() . '/' . ltrim($file['path'], '/');
            $response = new BinaryFileResponse($path);

            $filenameFallback = preg_replace(
                '#^.*\.#',
                md5($file['name']) . '.',
                $file['name']
            );

            $disposition = HeaderUtils::makeDisposition(
                HeaderUtils::DISPOSITION_ATTACHMENT,
                $file['name'],
                $filenameFallback
            );
            $response->headers->set('Content-Disposition', $disposition);
            return $response;
        } catch (\Exception $exception) {
            return $this->json(['success' => -1, 'e' => $exception->getMessage()]);
        }
    }

    /**
     * @Route (path="/download-zip", methods={"GET"})
     */
    public function downloadZip(Request $request, EntityManagerInterface $entityManager)
    {
        try {
            $fileRepository = $entityManager->getRepository($this->className);

            $requestData = $request->get('f');
            if (!$requestData) {
                $this->json(['success' => 0]);
            }
            $requestData = json_decode($requestData, true, 512, JSON_THROW_ON_ERROR);

            $folder = $requestData['folder'];
            $folderTrim = ltrim($folder, '/');

            $files = $fileRepository->findByFolder($folderTrim);

            $zipFileName = 'files-' . time() . '.zip';
            $zipPath = $this->fileService->getLocalStorage() . '/tmp' . '/' . $zipFileName;

            $zip = new \ZipArchive();
            $zip->open($zipPath, \ZipArchive::CREATE);

            /**
             * @var FileBase $fileocket
             */
            foreach ($files as $file) {
                $zip->addFromString(
                    $file->getOrgFileName(),
                    file_get_contents($this->fileService->getLocalStorage() . '/' . $file->getPath())
                );
            }

            $zip->close();

            $response = new Response(file_get_contents($zipPath));
            $response->headers->set('Content-Type', 'application/zip');
            $response->headers->set('Content-Disposition', 'attachment;filename="' . $zipFileName . '"');
            $response->headers->set('Content-length', filesize($zipPath));

            @unlink($zipPath);

            return $response;
        } catch (\Exception $exception) {
            return $this->json(['success' => -1, 'e' => $exception->getMessage()]);
        }
    }

    /**
     * @Route (path="/viewById", methods={"GET"})
     */
    public function viewById(Request $request)
    {
        try {
            $fileId = $request->get('id');
            $fileRepo = $this->getEm()->getRepository($this->className);
            $file = $fileRepo->find($fileId);

            $path = $this->fileService->getLocalStorage() . '/' . ltrim($file->getPath(), '/');
            $response = new BinaryFileResponse($path);

            $filenameFallback = preg_replace(
                '#^.*\.#',
                md5($file->getFileName()) . '.',
                $file['name']
            );

            $disposition = HeaderUtils::makeDisposition(
                HeaderUtils::DISPOSITION_INLINE,
                $file->getFileName(),
                $filenameFallback
            );
            $response->headers->set('Content-Disposition', $disposition);
            return $response;
        } catch (\Exception $exception) {
            return $this->json(['success' => -1, 'e' => $exception->getMessage()]);
        }
    }

    /**
     * @Route (path="/view", methods={"GET"})
     */
    public function view(Request $request)
    {
        try {
            $file = $request->get('f');
            if (!$file) {
                $this->json(['success' => 0]);
            }
            $file = json_decode($file, true, 512, JSON_THROW_ON_ERROR);

            $path = $this->fileService->getLocalStorage() . '/' . ltrim($file['path'], '/');
            $response = new BinaryFileResponse($path);

            $filenameFallback = preg_replace(
                '#^.*\.#',
                md5($file['name']) . '.',
                $file['name']
            );

            $disposition = HeaderUtils::makeDisposition(
                HeaderUtils::DISPOSITION_INLINE,
                $file['name'],
                $filenameFallback
            );
            $response->headers->set('Content-Disposition', $disposition);
            return $response;
        } catch (\Exception $exception) {
            return $this->json(['success' => -1, 'e' => $exception->getMessage()]);
        }
    }

    /**
     * @Route (path="/remove", methods={"POST"})
     * @OA\Post (operationId="NAEfileRemove")
     */
    public function remove(Request $request, EntityManagerInterface $entityManager)
    {
        try {
            $request = $this->transformJsonBody($request);

            $localPath = $request->get('path');

            $fileRepository = $entityManager->getRepository($this->className);

            $orm = $fileRepository->findOneBy(['path' => ltrim($localPath, '/')]);
            if ($orm) {
                $orm->setDeleted(true);
                $entityManager->persist($orm);
                $entityManager->flush();
            }

            return $this->json(['success' => 1, 'p' => ltrim($localPath, '/')]);
        } catch (\Exception $exception) {
            return $this->json(['success' => -1, 'e' => $exception->getMessage()]);
        }
    }


    /**
     * @Route ("/list", methods={"POST"})
     * @OA\Post (operationId="NAEfilesList")
     */
    public function list(Request $request, EntityManagerInterface $entityManager)
    {
        try {
            $fileRepository = $entityManager->getRepository($this->className);

            $request = $this->transformJsonBody($request);

            $folder = $request->get('folder');
            $folderTrim = ltrim($folder, '/');

            $files = $fileRepository->findByFolder($folderTrim);

            $contents = [];

            /**
             * @var FileBase $file
             */
            foreach ($files as $file) {
                $ext = explode(".", $file->getOrgFileName());
                $ext = $ext[count($ext) - 1];

                $contents[] = [
                    'path' => $file->getPath(),
                    'filename' => $file->getOrgFileName(),
                    'extension' => $ext,
                    'deleted' => $file->isDeleted(),
                    'appproved' => $file->isAppproved(),
                    'id' => $file->getId()
                ];
            }

            return $this->json(['data' => array_values($contents), 'folder' => $folder, 'c' => count($contents)]);
        } catch (\Exception $e) {
            $response = $this->json([
                'description' => $e->getMessage(),
                'f' => $e->getFile(),
                'l' => $e->getLine()

            ]);
            $response->setStatusCode(Response::HTTP_BAD_REQUEST);
            return $response;
        }
    }

    /**
     * @Route ("/multipleList", methods={"POST"})
     * @OA\Post (operationId="NAEfilesMultipleList")
     */
    public function multipleList(Request $request, EntityManagerInterface $entityManager)
    {
        try {
            $fileRepository = $entityManager->getRepository($this->className);

            $request = $this->transformJsonBody($request);

            $folders = $request->get('folders');

            $data = [];
            foreach ($folders as $folder) {
                $folderTrim = ltrim($folder, '/');

                $files = $fileRepository->findByFolder($folderTrim);

                $contents = [];

                /**
                 * @var FileBase $file
                 */
                foreach ($files as $file) {
                    $ext = explode(".", $file->getOrgFileName());
                    $ext = $ext[count($ext) - 1];

                    $contents[] = [
                        'path' => $file->getPath(),
                        'filename' => $file->getOrgFileName(),
                        'extension' => $ext,
                        'deleted' => $file->isDeleted(),
                        'appproved' => $file->isAppproved(),
                        'id' => $file->getId()
                    ];
                }
                $data[] = [
                    'folder' => $folder,
                    'contents' => array_values($contents)
                ];
            }

            return $this->json(['data' => $data]);
        } catch (\Exception $e) {
            $response = $this->json([
                'description' => $e->getMessage(),
                'f' => $e->getFile(),
                'l' => $e->getLine()

            ]);
            $response->setStatusCode(Response::HTTP_BAD_REQUEST);
            return $response;
        }
    }
}
