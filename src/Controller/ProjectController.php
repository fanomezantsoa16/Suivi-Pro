<?php

namespace App\Controller;

use App\Entity\Projects;
use App\Form\ProjectType;
use App\Entity\Employed;
use App\Repository\TaskRepository;
use App\Repository\ProjectRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/project')]
class ProjectController extends AbstractController
{
    #[Route('/', name: 'project_index')]
    public function index(ProjectRepository $projectRepository): Response
    {
        $error = null;
        $success = null;
        $projects = $projectRepository->findAll();

        return $this->render('project/index.html.twig', [
            'projects' => $projects,
            'error' => $error,
            'success' => $success,
        ]);
    }

    #[Route('/new', name: 'project_new')]
    public function add(Request $request, EntityManagerInterface $em): Response
    {
        $error = null;
        $success = null;

        // Récupération de tous les employés pour le champ <select>
        $employes = $em->getRepository(Employed::class)->findAll();

        if ($request->isMethod('POST')) {
            $name = $request->request->get('name');
            $description = $request->request->get('descriptions');
            $beginning = $request->request->get('beginning');
            $end = $request->request->get('end');
            $status = $request->request->get('status');
            $idEmployed = $request->request->get('idEmployed');

            // Validation de base
            if (empty($name) || empty($description) || empty($beginning) || empty($end) || empty($idEmployed)) {
                $error = 'Tous les champs sont obligatoires.';
            } elseif (!in_array($status, [0, 1])) {
                $error = 'Statut invalide.';
            } elseif (!$em->getRepository(Employed::class)->find($idEmployed)) {
                $error = 'Employé sélectionné invalide.';
            } else {
                try {
                    $project = new Projects();
                    $project->setName($name);
                    $project->setDescriptions($description);
                    $project->setBeginning(new \DateTime($beginning));
                    $project->setEnd(new \DateTime($end));
                    $project->setStatus($status);
                    $project->setIdEmployed((int)$idEmployed);
                    // $project->setIdUser($this->getUser()->getId());
                    /** @var \App\Entity\User $user */
                    $user = $this->getUser();
                    $idUser = $user->getId();
                    $project->setIdUser($idUser);
                    $project->setCreatedAt(new \DateTime());
                    $project->setUpdatedAt(new \DateTime());

                    $em->persist($project);
                    $em->flush();

                    $success = 'Projet ajouté avec succès !';
                } catch (\Exception $e) {
                    $error = 'Erreur lors de l\'ajout du projet : ' . $e->getMessage();
                }
            }
        }

        return $this->render('project/new.html.twig', [
            'error' => $error,
            'success' => $success,
            'employes' => $employes,
        ]);
    }

    #[Route('/views/{id}/delete', name: 'project_delete', methods: ['POST'])]
    public function delete(
        int $id,
        Request $request,
        TaskRepository $taskRepository,
        ProjectRepository $projectRepository,
        EntityManagerInterface $em
    ): Response {
        $error = null;
        $success = null;

        $project = $projectRepository->find($id);

        if (!$project) {
            throw $this->createNotFoundException('Projet introuvable.');
        }

        if ($this->isCsrfTokenValid('delete' . $project->getId(), $request->request->get('_token'))) {
            // Supprimer toutes les tâches associées
            $tasks = $taskRepository->findBy(['project' => $project]);
            foreach ($tasks as $task) {
                $em->remove($task);
            }

            // Supprimer le projet
            $em->remove($project);
            $em->flush();

            $success = 'Effacement terminé avec succès.';
            return $this->redirectToRoute('project_index', [
                'error' => $error,
                'success' => $success,
            ]);
        }

        throw $this->createAccessDeniedException('Suppression refusée.');
    }
}
