<?php

declare(strict_types=1);

namespace App\Service;

/**
 * Service métier pour la zone d'administration : entrées du tableau de bord back-office.
 * Centralise la logique pour garder l'AdminController "maigre".
 */
final class AdminService
{
    /**
     * Retourne les entrées du tableau de bord admin (liens vers les sections à gérer).
     *
     * @return list<array{route: string, label_key: string, description_key: string}>
     */
    public function getDashboardEntries(): array
    {
        return [
            [
                'route' => 'app_user_index',
                'label_key' => 'app.admin.manage_users',
                'description_key' => 'app.admin.manage_users_desc',
            ],
            [
                'route' => 'app_event_index',
                'label_key' => 'app.admin.manage_events',
                'description_key' => 'app.admin.manage_events_desc',
            ],
            [
                'route' => 'app_admin_category_index',
                'label_key' => 'app.admin.manage_categories',
                'description_key' => 'app.admin.manage_categories_desc',
            ],
        ];
    }
}
