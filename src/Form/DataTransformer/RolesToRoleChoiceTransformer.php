<?php

namespace App\Form\DataTransformer;

use Symfony\Component\Form\DataTransformerInterface;

/**
 * Transforme un tableau de rôles (entité) en une chaîne pour un champ ChoiceType,
 * et inversement à la soumission du formulaire.
 */
class RolesToRoleChoiceTransformer implements DataTransformerInterface
{
    /**
     * Tableau → chaîne : prend le premier rôle du tableau pour l'affichage.
     *
     * @param array<int, string> $value
     */
    public function transform(mixed $value): ?string
    {
        if (!\is_array($value) || $value === []) {
            return null;
        }

        // Retourne le premier rôle (évite ROLE_USER si on a un rôle plus spécifique en premier)
        return $value[0] ?? null;
    }

    /**
     * Chaîne → tableau : enveloppe le rôle sélectionné dans un tableau.
     *
     * @param string|null $value
     *
     * @return array<int, string>
     */
    public function reverseTransform(mixed $value): array
    {
        if ($value === null || $value === '') {
            return [];
        }

        return [(string) $value];
    }
}
