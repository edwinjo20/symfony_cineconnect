<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;

class TestEmailController extends AbstractController
{
    #[Route('/test-email', name: 'test_email')]
    public function sendTestEmail(MailerInterface $mailer): Response
    {
        $email = (new Email())
            ->from('edwinjones.m1980@gmail.com')
            ->to('edwinjones.m@gmail.com') // Use your Mailtrap email
            ->subject('Test Email from Symfony')
            ->text('This is a test email to confirm Mailtrap is working.');

        $mailer->send($email);

        return new Response('Test email sent successfully!');
    }
}
