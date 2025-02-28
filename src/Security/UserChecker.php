<?php
namespace App\Security;

use Symfony\Component\Security\Core\Exception\CustomUserMessageAccountStatusException;
use Symfony\Component\Security\Core\User\UserCheckerInterface;
use Symfony\Component\Security\Core\User\UserInterface;

class UserChecker implements UserCheckerInterface
{
    public function checkPreAuth(UserInterface $user): void
    {
        if (method_exists($user, 'isBlocked') && $user->isBlocked()) {
            throw new CustomUserMessageAccountStatusException('Your account has been blocked.');
        }
    }

    public function checkPostAuth(UserInterface $user): void
    {
        // No action needed after authentication
    }
}
