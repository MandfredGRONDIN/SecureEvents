<?php

namespace App\Security;

use App\Entity\Event;
use App\Entity\User;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Vote;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

/**
 * Voter pour les événements : contrôle la visibilité (VIEW) et les actions (EDIT, DELETE)
 * selon que l'utilisateur est anonyme, connecté non-admin ou admin.
 */
class EventVoter extends Voter
{
    public const VIEW = 'EVENT_VIEW';
    public const EDIT = 'EVENT_EDIT';
    public const DELETE = 'EVENT_DELETE';

    public function __construct(
        private readonly Security $security
    ) {
    }

    protected function supports(string $attribute, mixed $subject): bool
    {
        return \in_array($attribute, [self::VIEW, self::EDIT, self::DELETE], true)
            && $subject instanceof Event;
    }

    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token, ?Vote $vote = null): bool
    {
        $user = $token->getUser();
        $event = $subject;

        if ($attribute === self::VIEW) {
            return $this->canView($event, $user);
        }
        if ($attribute === self::EDIT || $attribute === self::DELETE) {
            return $this->canEditOrDelete($event, $user);
        }

        return false;
    }

    /**
     * Règles de visibilité : anonyme = publié uniquement ; utilisateur = publié ou créateur ; admin = tout.
     */
    private function canView(Event $event, User|string|null $user): bool
    {
        if ($this->security->isGranted('ROLE_ADMIN')) {
            return true;
        }
        if ($event->isPublished()) {
            return true;
        }
        if ($user instanceof User && $event->getCreatedBy() === $user) {
            return true;
        }

        return false;
    }

    /**
     * Édition / suppression : uniquement le créateur ou un admin.
     */
    private function canEditOrDelete(Event $event, User|string|null $user): bool
    {
        if ($this->security->isGranted('ROLE_ADMIN')) {
            return true;
        }
        if ($user instanceof User && $event->getCreatedBy() === $user) {
            return true;
        }

        return false;
    }
}
