<?php

namespace App\Controller;

use App\Entity\User;
use App\Form\RegistrationFormType;
use App\Security\EmailVerifier;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mime\Address;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Contracts\Translation\TranslatorInterface;
use SymfonyCasts\Bundle\VerifyEmail\Exception\VerifyEmailExceptionInterface;

class RegistrationController extends AbstractController
{
    public function __construct(private EmailVerifier $emailVerifier)
    {
    }

    #[Route('/register', name: 'app_register')]
    public function register(Request $request, UserPasswordHasherInterface $userPasswordHasher, EntityManagerInterface $entityManager): Response
    {
        $user = new User();
        $form = $this->createForm(RegistrationFormType::class, $user);
        $form->handleRequest($request);
    
        if ($form->isSubmitted() && $form->isValid()) {
            // Normalize and check email
            $userEmail = strtolower($form->get('email')->getData());
            $existingUserByEmail = $entityManager->getRepository(User::class)->findOneBy(['email' => $userEmail]);
            if ($existingUserByEmail) {
                $this->addFlash('error', 'Registration failed. Please try again.');
                return $this->redirectToRoute('app_register');
            }
    
            // Normalize and check username
            $user->setUsername($form->get('username')->getData());
            $existingUserByUsername = $entityManager->getRepository(User::class)->findOneBy(['username' => $user->getUsername()]);
            if ($existingUserByUsername) {
                $this->addFlash('error', 'Username is already taken.');
                return $this->redirectToRoute('app_register');
            }
    
            // Hash password securely
            $user->setPassword(
                $userPasswordHasher->hashPassword($user, $form->get('plainPassword')->getData())
            );
    
            // Assign roles (Admin if email matches)
            $adminEmails = ['edwinjones.m@gmail.com', 'edwinjones.m1980@gmail.com'];
            $user->setRoles(in_array($userEmail, $adminEmails, true) ? ['ROLE_ADMIN'] : ['ROLE_USER']);
    
            // Persist user
            $entityManager->persist($user);
            $entityManager->flush();
    
            // Send verification email
            $this->emailVerifier->sendEmailConfirmation('app_verify_email', $user,
                (new TemplatedEmail())
                    ->from(new Address('mailer@cineconnect.com', 'CineConnect'))
                    ->to($userEmail)
                    ->subject('Please Confirm Your Email')
                    ->htmlTemplate('registration/confirmation_email.html.twig')
            );
    
            // Redirect to login instead of films
            return $this->redirectToRoute('app_login');
        }
    
        return $this->render('registration/register.html.twig', [
            'registrationForm' => $form->createView(),
        ]);
    }
    
    
    #[Route('/verify/email', name: 'app_verify_email')]
    public function verifyUserEmail(Request $request, TranslatorInterface $translator): Response
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');

        try {
            $user = $this->getUser();
            $this->emailVerifier->handleEmailConfirmation($request, $user);
        } catch (VerifyEmailExceptionInterface $exception) {
            $this->addFlash('verify_email_error', $translator->trans($exception->getReason(), [], 'VerifyEmailBundle'));
            return $this->redirectToRoute('app_register');
        }

        $this->addFlash('success', 'Your email address has been verified.');
        return $this->redirectToRoute('app_film_index');
    }
}