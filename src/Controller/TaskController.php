<?php

namespace App\Controller;

use App\Entity\Task;
use App\Entity\Projects;
use App\Repository\TaskRepository;
use App\Repository\ProjectRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/task')]
class TaskController extends AbstractController
{
    #[Route('/', name: 'task_index')]
    public function index(TaskRepository $taskRepository, Request $request): Response
    {
        // $idUser = $this->getUser()->getId();
        /** @var \App\Entity\User $user */
        $user = $this->getUser();
        $idUser = $user->getId();

        // Récupère les tâches via les projets de l'utilisateur
        // $tasks = $taskRepository->createQueryBuilder('t')
        //     ->join('t.project', 'p')
        //     ->where('p.idUser = :idUser')
        //     ->setParameter('idUser', $idUser)
        //     ->orderBy('t.createdAt', 'DESC')
        //     ->getQuery()
        //     ->getResult();
        $tasks = $taskRepository->findBy(['user' => $idUser]);

        return $this->render('task/index.html.twig', [
            'tasks' => $tasks,
        ]);
    }

    #[Route('/new', name: 'task_add')]
    public function add(Request $request, EntityManagerInterface $em, ProjectRepository $projectRepository): Response
    {
        $error = null;
        $success = null;

        // $projects = $projectRepository->findBy(['idUser' => $idUser]);
        $projects = $projectRepository->findAll();

        if ($request->isMethod('POST')) {
            try {
                // Validation des champs obligatoires
                $requiredFields = ['name', 'description', 'start_date', 'end_date', 'project'];
                foreach ($requiredFields as $field) {
                    if (empty($request->request->get($field))) {
                        throw new \Exception("Le champ '" . str_replace('_', ' ', $field) . "' est obligatoire");
                    }
                }

                // Validation des dates
                $startDate = new \DateTime($request->request->get('start_date'));
                $endDate = new \DateTime($request->request->get('end_date'));

                if ($endDate < $startDate) {
                    throw new \Exception("La date de fin doit être postérieure à la date de début");
                }

                // Validation du statut
                $status = (int)$request->request->get('status');
                if ($status < 0 || $status > 2) {
                    throw new \Exception("Statut invalide");
                }

                // Validation du projet
                $project = $projectRepository->find($request->request->get('project'));
                // if (!$project || $project->getIdUser() !== $idUser) {
                //     throw new \Exception("Projet invalide");
                // }

                // Création de la tâche
                $task = new Task();
                $task->setName($request->request->get('name'));
                $task->setUser($this->getUser());
                $task->setDescription($request->request->get('description'));
                $task->setStartDate($startDate);
                $task->setEndDate($endDate);
                $task->setStatus($status);
                $task->setProject($project);

                $now = new \DateTimeImmutable();
                $task->setCreatedAt($now);
                $task->setUpdatedAt($now);

                $em->persist($task);
                $em->flush();

                $success = "La tâche a été créée avec succès";
                // return $this->redirectToRoute('task_index', ['success' => $success]);

            } catch (\Exception $e) {
                $error = $e->getMessage();
                // Garder les valeurs soumises pour ré-afficher le formulaire
                $submittedData = [
                    'name' => $request->request->get('name'),
                    'description' => $request->request->get('description'),
                    'start_date' => $request->request->get('start_date'),
                    'end_date' => $request->request->get('end_date'),
                    'status' => $request->request->get('status'),
                    'project' => $request->request->get('project'),
                ];
            }
        }

        return $this->render('task/add.html.twig', [
            'projects' => $projects,
            'error' => $error,
            'success' => $success,
            'submittedData' => $submittedData ?? null,
        ]);
    }

    #[Route('/views/{id}', name: 'task_view', requirements: ['id' => '\d+'])]
    public function view(int $id, TaskRepository $TaskRepository): Response
    {
        if (!$id) {
            return $this->redirectToRoute('task_index');
        }

        $task = $TaskRepository->find($id);

        if (!$task) {
            // throw $this->createNotFoundException('Employé non trouvé.');
            return $this->redirectToRoute('task_index');
        }

        $user = $task->getUser();

        return $this->render('task/view.html.twig', [
            'task' => $task,
            'user' => $user,
        ]);
    }

    #[Route('/views/{id}/edit', name: 'task_edit')]
    public function edit(int $id, Request $request, TaskRepository $taskRepository, EntityManagerInterface $em): Response
    {
        $error = null;
        $success = null;
        
        $task = $taskRepository->find($id);

        if (!$task) {
            return $this->redirectToRoute('task_index');
        }

        $form = $this->createFormBuilder($task)
            ->add('name')
            ->add('startDate')
            ->add('endDate')
            ->add('description')
            ->add('status', ChoiceType::class, [
                'choices' => [
                    'En cours' => 0,
                    'Terminé' => 1,
                ],
                'placeholder' => 'Sélectionner un status',
                'required' => true,
            ])
            ->add('project', EntityType::class, [
                'class' => Projects::class,
                'choice_label' => 'name',
                'placeholder' => 'Sélectionner un projet',
                'required' => true,
            ])
            ->getForm();

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $task->setUpdatedAt(new \DateTime());
            $em->flush();

            return $this->redirectToRoute('task_view', ['id' => $task->getId()]);
        }

        return $this->render('task/edit.html.twig', [
            'form' => $form->createView(),
            'task' => $task,
            'error' => $error,
            'success' => $success,
        ]);
    }

    #[Route('/views/{id}/delete', name: 'task_delete', methods: ['POST'])]
    public function delete(
        int $id,
        Request $request,
        TaskRepository $taskRepository,
        EntityManagerInterface $em
    ): Response {
        $task = $taskRepository->find($id);

        if (!$task) {
            throw $this->createNotFoundException('Tâche introuvable.');
        }

        if ($this->isCsrfTokenValid('delete' . $task->getId(), $request->request->get('_token'))) {
            $em->remove($task);
            $em->flush();

            return $this->redirectToRoute('task_index');
        }

        throw $this->createAccessDeniedException('Suppression refusée.');
    }
}
