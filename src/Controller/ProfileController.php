<?php

namespace App\Controller;

use App\Entity\User;
use App\Form\ProfileFormType;
use App\Entity\Favorites;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;

class ProfileController extends AbstractController
{
    private $passwordHasher;
    private $entityManager;

    public function __construct(UserPasswordHasherInterface $passwordHasher, EntityManagerInterface $entityManager)
    {
        $this->passwordHasher = $passwordHasher;
        $this->entityManager = $entityManager;
    }

    #[Route('/profile', name: 'app_profile')]
    public function index(): Response
    {
        // Get the currently logged-in user
        $user = $this->getUser();

        // Fetch the user's favorite films
        $favorites = $this->entityManager->getRepository(Favorites::class)->findBy(['user' => $user]);

        // Return the profile view page and pass user and favorites to the template
        return $this->render('profile/index.html.twig', [
            'user' => $user,
            'favorites' => $favorites,
        ]);
    }

    #[Route('/profile/edit', name: 'app_profile_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request): Response
    {
        $user = $this->getUser(); // Get the currently logged-in user

        // Fetch the user's favorite films
        $favorites = $this->entityManager->getRepository(Favorites::class)->findBy(['user' => $user]);

        // Create the form for profile update
        $form = $this->createForm(ProfileFormType::class, $user);
        $form->handleRequest($request);

        // If the form is submitted and valid, process the data
        if ($form->isSubmitted() && $form->isValid()) {

            // If password is provided, hash it
            if ($user->getPassword()) {
                $encodedPassword = $this->passwordHasher->hashPassword($user, $user->getPassword());
                $user->setPassword($encodedPassword); // Set the new hashed password
            }

            // Persist the updated user entity to the database
            $this->entityManager->flush();

            // Add success flash message
            $this->addFlash('success', 'Profile updated successfully!');

            // Redirect to the profile page
            return $this->redirectToRoute('app_profile');
        }

        // Render the form and pass the favorite films to the template
        return $this->render('profile/edit.html.twig', [
            'form' => $form->createView(),
            'favorites' => $favorites,  // Pass the favorites to the view
        ]);
    }
}
