<?php

namespace App\Controller;

use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use SymfonyCasts\Bundle\ResetPassword\ResetPasswordHelperInterface;
use SymfonyCasts\Bundle\ResetPassword\Exception\ResetPasswordExceptionInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class ResetPasswordController extends AbstractController
{
    private ResetPasswordHelperInterface $resetPasswordHelper;
    private EntityManagerInterface $entityManager;

    public function __construct(ResetPasswordHelperInterface $resetPasswordHelper, EntityManagerInterface $entityManager)
    {
        $this->resetPasswordHelper = $resetPasswordHelper;
        $this->entityManager = $entityManager;
    }

    /**
     * Request a password reset.
     */
    #[Route('/reset-password', name: 'app_forgot_password_request', methods: ['GET', 'POST'])]
    public function request(Request $request, MailerInterface $mailer, UserRepository $userRepository): Response
    {
        if ($request->isMethod('POST')) {
            $email = $request->request->get('email');
            $user = $userRepository->findOneBy(['email' => $email]);

            if (!$user) {
                $this->addFlash('danger', 'No user found with this email.');
                return $this->redirectToRoute('app_forgot_password_request');
            }

            try {
                $resetToken = $this->resetPasswordHelper->generateResetToken($user);
            } catch (ResetPasswordExceptionInterface $e) {
                $this->addFlash('danger', 'There was a problem generating the reset token.');
                return $this->redirectToRoute('app_forgot_password_request');
            }

            $resetUrl = $this->generateUrl(
                'app_reset_password',
                ['token' => $resetToken->getToken()],
                UrlGeneratorInterface::ABSOLUTE_URL
            );

            $emailMessage = (new Email())
                ->from('edwinjones.m1980@gmail.com')  // Ensure this email is authorized in your SMTP settings
                ->to($user->getEmail())
                ->subject('Reset your password')
                ->html("
                    <p>Hello,</p>
                    <p>Click the link below to reset your password:</p>
                    <p><a href='{$resetUrl}'>Reset Password</a></p>
                    <p>If you didn't request a password reset, you can ignore this email.</p>
                    <p>Regards,<br>CineConnect Team</p>
                ");

            $mailer->send($emailMessage);

            $this->addFlash('success', 'Password reset link sent to your email.');
            return $this->redirectToRoute('app_login');
        }

        return $this->render('reset_password/request.html.twig');
    }

    /**
     * Reset password form.
     */
    #[Route('/reset-password/{token}', name: 'app_reset_password', methods: ['GET', 'POST'], requirements: ['token' => '.+'])]
    public function reset(Request $request, string $token, UserPasswordHasherInterface $passwordHasher): Response
    {
        if (!$token) {
            $this->addFlash('danger', 'Invalid or missing reset token.');
            return $this->redirectToRoute('app_forgot_password_request');
        }

        try {
            $user = $this->resetPasswordHelper->validateTokenAndFetchUser($token);
        } catch (ResetPasswordExceptionInterface $e) {
            $this->addFlash('danger', 'Invalid or expired token.');
            return $this->redirectToRoute('app_forgot_password_request');
        }

        if ($request->isMethod('POST')) {
            $newPassword = $request->request->get('password');

            if (empty($newPassword)) {
                $this->addFlash('danger', 'Password cannot be empty.');
                return $this->redirectToRoute('app_reset_password', ['token' => $token]);
            }

            $user->setPassword($passwordHasher->hashPassword($user, $newPassword));
            $this->entityManager->persist($user);
            $this->entityManager->flush();

            $this->resetPasswordHelper->removeResetRequest($token);

            $this->addFlash('success', 'Password reset successful. You can now log in.');
            return $this->redirectToRoute('app_login');
        }

        return $this->render('reset_password/reset.html.twig', ['token' => $token]);
    }
}
