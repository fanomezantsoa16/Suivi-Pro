<?php

namespace App\Controller;

use App\Entity\Employed;
use App\Repository\EmployedRepository;
use App\Repository\ProjectRepository;
use Doctrine\ORM\EntityManagerInterface;
use App\Form\EmployedType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class EmployedController extends AbstractController
{
    #[Route('/employed', name: 'employed')]
    public function index(EmployedRepository $employedRepository): Response
    {
        $employed = $employedRepository->findAll();

        return $this->render('employed/index.html.twig', [
            'employed' => $employed,
        ]);
    }

    #[Route('/employed/add', name: 'employed_add')]
    public function add(Request $request, EntityManagerInterface $entityManager): Response
    {
        $error = null;
        $success = null;

        if ($request->isMethod('POST')) {
            $fname = $request->request->get('fname');
            $lname = $request->request->get('lname');
            $role = $request->request->get('role');
            $phone = $request->request->get('phone');
            $email = $request->request->get('email');

            if (empty($fname) || empty($lname) || empty($role) || empty($phone) || empty($email)) {
                $error = 'Tous les champs sont obligatoires.';
            } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $error = 'Email invalide.';
            } else {
                $existingEmployed = $entityManager->getRepository(Employed::class)->findOneBy(['email' => $email]);
                if ($existingEmployed) {
                    $error = 'Un employé avec cet email existe déjà.';
                } else {
                    $employed = new Employed();
                    // $employed->setIdUser($this->getUser()->getId());
                    /** @var \App\Entity\User $user */
                    $user = $this->getUser();
                    $idUser = $user->getId();
                    $employed->setIdUser($idUser);
                    $employed->setFname($fname);
                    $employed->setLname($lname);
                    $employed->setRole($role);
                    $employed->setPhone($phone);
                    $employed->setEmail($email);

                    $entityManager->persist($employed);
                    $entityManager->flush();

                    $success = 'Employé ajouté avec succès!';
                }
            }
        }

        return $this->render('employed/add.html.twig', [
            'error' => $error,
            'success' => $success,
        ]);
    }

    #[Route('/employed/views/{id}', name: 'employed_view', requirements: ['id' => '\d+'])]
    public function view(int $id, EmployedRepository $employedRepository): Response
    {
        $employed = $employedRepository->find($id);

        if (!$employed) {
            throw $this->createNotFoundException('Employé non trouvé.');
        }

        return $this->render('employed/view.html.twig', [
            'employed' => $employed,
        ]);
    }

    #[Route('/employed/views/{id}/edit', name: 'employed_edit')]
    public function edit(Request $request, Employed $employed, EntityManagerInterface $em): Response
    {
        $form = $this->createForm(EmployedType::class, $employed);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $employed->setUpdatedAt(new \DateTime());
            $em->flush();

            $this->addFlash('success', 'Modification effectuée avec succès.');

            return $this->redirectToRoute('employed_view', [
                'id' => $employed->getId(),
            ]);
        }

        return $this->render('employed/edit.html.twig', [
            'form' => $form->createView(),
            'employed' => $employed,
        ]);
    }

    #[Route('/employed/views/{id}/delete', name: 'employed_delete', methods: ['POST'])]
    public function delete(
        Request $request,
        Employed $employed,
        EntityManagerInterface $em,
        ProjectRepository $ProjectRepository
    ): Response {
        if ($this->isCsrfTokenValid('delete' . $employed->getId(), $request->request->get('_token'))) {
            // 1. Récupérer les historiques liés à cet employé
            $projet = $ProjectRepository->findBy(['idEmployed' => $employed->getId()]);

            // 2. Les supprimer un par un
            foreach ($projet as $record) {
                $em->remove($record);
            }

            // 3. Supprimer l'employé
            $em->remove($employed);
            $em->flush();

            return $this->redirectToRoute('employed');
        }

        throw $this->createAccessDeniedException('Suppression refusée.');
    }
}
