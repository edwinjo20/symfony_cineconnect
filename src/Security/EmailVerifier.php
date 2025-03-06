<?php

namespace App\Security;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use SymfonyCasts\Bundle\VerifyEmail\Exception\VerifyEmailExceptionInterface;
use SymfonyCasts\Bundle\VerifyEmail\VerifyEmailHelperInterface;

class EmailVerifier
{
    public function __construct(
        private VerifyEmailHelperInterface $verifyEmailHelper,
        private MailerInterface $mailer,
        private EntityManagerInterface $entityManager
    ) {
    }

    public function sendEmailConfirmation(string $verifyEmailRouteName, User $user, TemplatedEmail $email): void
    {
        $signatureComponents = $this->verifyEmailHelper->generateSignature(
            $verifyEmailRouteName,
            (string) $user->getId(),
            (string) $user->getEmail(),
            ['id' => $user->getId()] // ✅ Add user ID to the URL
        );
    
        $context = $email->getContext();
        $context['signedUrl'] = $signatureComponents->getSignedUrl();
        $context['expiresAtMessageKey'] = $signatureComponents->getExpirationMessageKey();
        $context['expiresAtMessageData'] = $signatureComponents->getExpirationMessageData();
    
        error_log("🔍 [DEBUG] Generated verification URL: " . $signatureComponents->getSignedUrl()); // ✅ Log URL
    
        $email->context($context);
    
        $this->mailer->send($email);
    }
    

    /**
     * @throws VerifyEmailExceptionInterface
     */
    public function handleEmailConfirmation(Request $request, User $user): void
    {
        error_log("🔍 [DEBUG] Email verification process started for user ID: " . $user->getId());
    
        try {
            $this->verifyEmailHelper->validateEmailConfirmationFromRequest(
                $request, 
                (string) $user->getId(), 
                (string) $user->getEmail()
            );
        } catch (VerifyEmailExceptionInterface $e) {
            error_log("❌ [ERROR] Verification failed: " . $e->getMessage());
            throw $e;
        }
    
        error_log("✅ [DEBUG] Verification successful for user ID: " . $user->getId());
    
        $user->setIsVerified(true);
        $this->entityManager->persist($user);
        $this->entityManager->flush();
    
        error_log("✅ [SUCCESS] is_verified updated in DB for user ID: " . $user->getId());
    }
    
    
}
