<?php

namespace App\Controller;

use App\Repository\UserRepository;
use App\Repository\EmployedRepository;
use App\Repository\ProjectRepository;
use App\Repository\TaskRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class HomeController extends AbstractController
{
    #[Route('/', name: 'home')]
    public function index(UserRepository $userRepository, EmployedRepository $employedRepository, ProjectRepository $ProjectRepository, TaskRepository $TaskRepository): Response
    {
        $users = $userRepository->findAll();
        $employed = $employedRepository->findAll();
        $project = $ProjectRepository->findAll();
        $task = $TaskRepository->findAll();

        return $this->render('home/index.html.twig', [
            'users' => $users,
            'employed' => $employed,
            'project' => $project,
            'task' => $task,
        ]);
    }
}
