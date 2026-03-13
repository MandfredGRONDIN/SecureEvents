# PostMortem — Projet SecureEvents

Document de retour d’expérience sur le projet SecureEvents (livrable CDC).

---

## 1. Difficultés rencontrées

_(Blocages techniques, choix d’architecture, délais.)_

- **Persistance des filtres et soumission du formulaire** : au départ, lorsqu’on vidait les champs de filtre puis qu’on cliquait sur « Filtrer », les anciens filtres (stockés en session) réapparaissaient. Il a fallu distinguer « requête sans paramètres » (ex. retour depuis une fiche) et « soumission du formulaire avec champs vides » en se basant sur la présence des paramètres dans l’URL, pas seulement sur le fait qu’ils soient non vides.
- **Calendrier « Date de début »** : le sélecteur de date natif du navigateur ne permet pas de styliser les dates désactivées (grisage). Pour afficher clairement les dates passées comme non utilisables, passage à Flatpickr (librairie JS) avec `minDate: 'today'` et styles CSS dédiés sur les jours désactivés.
- **Cohérence des autorisations** : s’assurer que la liste des participants n’est visible que par les admins (vue événement et vue réservation) tout en gardant le compteur (X / capacité max) visible par tous pour ne pas casser l’UX.
- **Intégration Docker + Makefile** : prise en main du workflow (migrations, seeds, `db-reset`, `seed-demo`) et alignement des commandes avec l’environnement conteneurisé.

---

## 2. Réussites

_(Ce qui a bien fonctionné, points forts du projet.)_

- **Sécurité** : mise en place propre (Argon2id, CSRF, login throttling, EventVoter pour VIEW/EDIT/DELETE, contrôle d’accès par rôles). Réinitialisation de mot de passe par token avec expiration et invalidation.
- **Architecture** : séparation Controller / Service / Repository, formulaire typé (EventType, etc.), thème de formulaire Tailwind réutilisable.
- **Fonctionnalités livrées** : événements (CRUD, publication, capacité), réservations, catégories, utilisateurs et rôles, API REST pour le catalogue d’événements, internationalisation (FR/EN), filtres persistants sur la liste avec réinitialisation explicite.
- **Outillage** : Makefile pour Docker (up, migrate, seed-events, seed-demo, db-reset), commandes de seed cohérentes pour la démo.
- **UX** : interface sombre cohérente, filtres conservés lors de la navigation et de la pagination, dates passées grisées dans le calendrier de création/édition d’événement.

---

## 3. Ce qui aurait pu être amélioré

_(Qualité du code, tests, sécurité, UX, performance.)_

- **Tests automatisés** : renforcer les tests (PHPUnit) sur les cas métier (EventService, EventVoter, filtres, validation de la date de début). Couvrir les parcours critiques (réservation, création d’événement, reset password).
- **Accessibilité** : vérifier les contrastes, les labels de formulaire et la navigation clavier, notamment sur le calendrier Flatpickr.
- **Performance** : éviter le N+1 sur la liste d’événements (réservations, créateur, catégorie) via des jointures ou du chargement eager si la liste grossit.
- **Documentation** : décrire clairement les rôles (anonyme, ROLE_USER, ROLE_ADMIN) et les droits associés (voir participants, éditer, supprimer) dans un README ou une doc technique.

---

## 4. Ce que nous aurions aimé ajouter

_(Fonctionnalités ou évolutions non réalisées par manque de temps ou de priorité.)_

- **Export / rapport** : export CSV ou PDF des participants par événement (réservé aux admins).
- **Notifications** : rappel par email avant l’événement ou confirmation de réservation.
- **Recherche avancée** : filtres par créateur, par plage de capacité, ou combinaison de critères plus riche.
- **Mode sombre pour Flatpickr** : thème du calendrier entièrement aligné avec l’interface sombre de l’application.
- **Tests E2E** : scénarios complets (inscription, création d’événement, réservation, réinitialisation des filtres) avec un outil type Playwright ou Cypress.
