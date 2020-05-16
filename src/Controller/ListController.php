<?php

namespace App\Controller;

use App\Entity\Task;
use App\Entity\Note;
use App\Entity\TaskList;
use App\Repository\TaskListRepository;
use App\Repository\TaskRepository;
use Doctrine\ORM\EntityManagerInterface;
use FOS\RestBundle\Controller\AbstractFOSRestController;
use FOS\RestBundle\Controller\Annotations as Rest;
use FOS\RestBundle\Request\ParamFetcher;
use FOS\RestBundle\Controller\Annotations\RequestParam;
use FOS\RestBundle\Controller\Annotations\FileParam;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Sensio\Bundle\FrameworkExtraBundle\SensioFrameworkExtraBundle;


class ListController extends AbstractFOSRestController
{
    /**
     * @var TaskListRepository
     */
    private $taskListRepository;

    /**
     * @var EntityManagerInterface
     */
    private $entityManager;

     /**
     * @var TaskRepository
     */
    private $taskRepository;


    public function __construct(TaskListRepository $taskListRepository,TaskRepository $taskRepository, EntityManagerInterface $entityManager)
    {
        $this->taskListRepository = $taskListRepository;
        $this->entityManager = $entityManager;
        $this->taskRepository = $taskRepository;
    }

    public function getTaskNotesAction(TaskList $list)
    {
        if ($list) {
            return $this->view($list->getNotes(), Response::HTTP_OK);
        }
        return $this->view(['message' => 'Something went wrong'], Response::HTTP_INTERNAL_SERVER_ERROR);
    }

    /**
     * @return \FOS\RestBundle\View\View
     */
    
    public function getListAction(TaskList $list)
    {
        return $this->view($list, \Symfony\Component\HttpFoundation\Response::HTTP_OK);
    }

    public function getListsAction()
    {
        $data = $this->taskListRepository->findAll();
        return $this->view($data, \Symfony\Component\HttpFoundation\Response::HTTP_OK);
    }



    /**
     * @RequestParam(name="title", description="Title of the list", nullable=false)
     * @param ParamFetcher $paramFetcher
     * @return \FOS\RestBundle\View\View
     */

    public function postListsAction(ParamFetcher $paramFetcher)
    {
        $title = $paramFetcher->get('title');
        if($title) {
            $list = new TaskList();

            $list->setTitle($title);

            $this->entityManager->persist($list);
            $this->entityManager->flush();

            return $this->view($list, \Symfony\Component\HttpFoundation\Response::HTTP_CREATED);
        }
        return $this->view(['title' => 'This cannot be null'], \Symfony\Component\HttpFoundation\Response::HTTP_BAD_REQUEST);
    }

    /**
     * @RequestParam(name="title", description="Title if the new task", nullable=false)
     * @param ParamFetcher $paramFetcher
     * @param TaskList $list
     * @return \FOS\RestBundle\View\View
     */
    public function postListTaskAction(ParamFetcher $paramFetcher, TaskList $list)
    {
        if($list) {
            $title = $paramFetcher->get('title');

            $task = new Task();
            $task->setTitle($title);
            
            $list->addTask($task);

            $this->entityManager->persist($task);
            $this->entityManager->flush();

            return $this->view($task, Response::HTTP_OK);
        }
        return $this->view(['message' => 'Something went wrong'], Response::HTTP_INTERNAL_SERVER_ERROR);
    }

    public function getListsTasksAction(TaskList $list)
    {
        return $this->view($list->getTasks(), Response::HTTP_OK);
    }

    /**
     * @FileParam(name="image", description="The background of the list", nullable=false, image=true)
     * @param Request $request
     * @param ParamFetcher $paramFetcher
     * @param TaskList $list
     * @return \FOS\RestBundle\View\View
     */

    public function backgroundListsAction(Request $request, ParamFetcher $paramFetcher, TaskList $list)
    {
        $currentBackground = $list->getBackground();
        if(!is_null($currentBackground)) {
            $filesystem = new Filesystem();
            $filesystem->remove(
                $this->getUploadsDir() . $currentBackground
            );
        }
        /** @var UploadedFile $file */
        $file = ($paramFetcher->get('image'));

        if ($file) {
            $filename = md5(uniqid()) . '.' . $file->guessClientExtension();

            $file->move(
                $this->getUploadsDir(),
                $filename
            );

            $list->setBackground($filename);
            $list->setBackgroundPath('/uploads/' . $filename);

            $this->entityManager->persist($list);
            $this->entityManager->flush();

            $data = $request->getUriForPath(
                $list->getBackgroundPath()
            );

            return $this->view($data, \Symfony\Component\HttpFoundation\Response::HTTP_OK);
        }
        return $this->view(['message' => 'Something went wrong'], \Symfony\Component\HttpFoundation\Response::HTTP_BAD_REQUEST);
    }

    public function deleteListsAction(TaskList $list)
    {
        $this->entityManager->remove($list);
        $this->entityManager->flush();

        return $this->view(null, Response::HTTP_NO_CONTENT);
    }

    /**
     * @RequestParam(name="title", description="The new title for the list", nullable=false)
     * @param ParamFetcher $paramFetcher
     * @param TaskList $list
     * @return \FOS\RestBundle\View\View
     */
    public function patchListsTitleAction(ParamFetcher $paramFetcher, TaskList $list)
    {
        $errors = [];
        $title = $paramFetcher->get('title');

        if(trim($title) !== '') {
            if($list) {
                $list->setTitle($title);

                $this->entityManager->persist($list);
                $this->entityManager->flush();

                return $this->view(null, Response::HTTP_NO_CONTENT);
            }
            $errors[] = [
                'title' => 'This value can not be empty'
            ];
        }
        $errors[] = [
            'list' => 'List not found'
        ];
    }

    private function getUploadsDir()
    {
        return $this->getParameter('uploads_dir');
    }
}
