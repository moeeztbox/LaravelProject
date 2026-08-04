# RBAC Model

## 1. Project Overview

This is a Laravel 12 API for managing Projects and Tasks across three user roles — Admin, Manager, and Employee. Authentication is token-based via Laravel Sanctum, and authorization is enforced through Laravel Policies bound to the `Project` and `Task` models. The system's core rule set: Admins have broad access, Managers share most of that access, and Employees are scoped to resources they own or are assigned to.

## 2. Authentication

Authentication uses **Laravel Sanctum** for stateless, token-based API authentication — not session cookies.

- **Login** (`POST /api/login`) validates credentials via `Auth::attempt()` and, on success, issues a personal access token via `$user->createToken('auth_token')->plainTextToken`.
- **Registration** (`POST /api/register`) creates a user and immediately issues a token the same way, so a client is authenticated right after signing up.
- **Token-based authentication**: every subsequent request must include `Authorization: Bearer <token>`. Sanctum resolves this header to a `User` model on each request; there is no server-side session state to maintain.
- **Logout** (`POST /api/logout`) revokes only the token used in that specific request (`$request->user()->currentAccessToken()->delete()`), so a user logged in on multiple devices only signs out the one they're using.
- **Protected routes**: all routes except `register` and `login` sit behind the `auth:sanctum` middleware in `routes/api.php`. A request without a valid token receives `401 {"message": "Unauthenticated."}`.

## 3. Authorization

Authorization is implemented with **Laravel Policies** — `ProjectPolicy` and `TaskPolicy` — registered explicitly in `AppServiceProvider::boot()` via `Gate::policy()`.

Enforcement happens at two levels, depending on the resource:

- **Projects**: authorization is checked inside `ProjectController`, using `$this->authorize('ability', $project)`. This lets the controller run the check as part of its own logic before proceeding.
- **Tasks**: authorization is checked at the **route level**, using the `can:ability,task` middleware directly in `routes/api.php`. The check happens before the controller method even runs.

Both approaches ultimately call the same underlying Policy method — they're two idiomatic ways of invoking the same Gate system, chosen per-resource for readability rather than for any functional difference.

## 4. Roles

Roles are stored in a `roles` table and linked to `users` via `role_id`. There is no fixed enum — a role is identified by its `name` (`"Admin"`, `"Manager"`, `"Employee"`), checked in code via `User::hasRole(string $role): bool`.

### Admin
The most privileged role. Can create, view, update, and delete **any** project or task, assign/reassign any task, and is the **only** role permitted to delete a project at all.

### Manager
Shares nearly all of Admin's capabilities on Projects and Tasks: create, view, update, delete, and assign, all unrestricted by ownership. The one exception is **project deletion**, which is Admin-only. Managers are not currently scoped to a "team" — see §8.

### Employee
The most restricted role. Cannot create, delete, or assign tasks under any circumstance. Can only:
- **View** a task if it is assigned to them.
- **Update** a task if it is assigned to them.
- **Create** a project (unrestricted — see §5) and later **update** that same project, but cannot delete any project, including their own.

## 5. Ownership Rules

Two distinct ownership relationships exist, using columns already present in the schema — no additional tables were introduced for this.

**Project ownership** — `projects.created_by`. Set server-side from the authenticated user at creation time (never trusted from client input). Governs `ProjectPolicy::update`: Admin/Manager may update any project; anyone else may only update a project where `created_by === $user->id`. Ownership does **not** extend to deletion — that remains Admin-only regardless of who created the project.

**Task assignment** — `tasks.assigned_to`. Governs both `TaskPolicy::view` and `TaskPolicy::update`: Admin/Manager may view/update any task; anyone else may only view/update a task where `assigned_to === $user->id`.

**Task assignment as an action** — changing *who* a task is assigned to is a separate, privileged operation (`PUT /api/tasks/{task}/assign`, gated by `TaskPolicy::assign`), restricted to Admin/Manager. An Employee cannot reassign a task even if it is currently assigned to them.

## 6. Policy Overview

### `ProjectPolicy`
| Method | Responsibility |
|---|---|
| `viewAny` | Gates the project list endpoint — unrestricted (any authenticated user). |
| `view` | Gates viewing a single project — unrestricted. |
| `create` | Gates project creation — unrestricted, so ownership (`created_by`) has meaning for every role. |
| `update` | Admin/Manager: any project. Others: only if they are the creator. |
| `delete` | Admin only, no exceptions. |

### `TaskPolicy`
| Method | Responsibility |
|---|---|
| `viewAny` | Gates the task list endpoint — unrestricted as a boolean, but note: it does **not** control *which* tasks appear in the response. `TaskController::index()` separately filters the query itself, returning all tasks for Admin/Manager and only assigned tasks for everyone else, since a Policy can gate an action but cannot filter a collection. |
| `view` | Admin/Manager: any task. Others: only their assigned task. |
| `create` | Admin/Manager only. |
| `update` | Admin/Manager: any task. Others: only their assigned task. |
| `delete` | Admin/Manager only — not extended to an Employee even for their own assigned task. |
| `assign` | Admin/Manager only — a custom (non-standard) Policy method, following the same pattern as the other five. |

## 7. API Protection

`auth:sanctum` and the Policies operate as two independent, sequential layers, and both must pass for a request to succeed:

1. **`auth:sanctum`** answers *"who is this?"* — it resolves the bearer token to a `User` or rejects the request outright with `401` if no valid token is present. Nothing past this layer runs without an authenticated user.
2. **Policies** (`can:` middleware or `$this->authorize()`) answer *"is this specific user allowed to do this specific thing to this specific resource?"* — evaluated only after authentication succeeds. A failed check returns `403 {"message": "This action is unauthorized."}`.

This separation means role/ownership logic never has to re-implement "is there even a valid user" — that's handled once, upstream, by Sanctum.

## 8. Future Improvements

- **Team-based authorization**: Managers currently have broad, unscoped access to all projects/tasks rather than being limited to "their team's" resources, because no `Team` entity exists in the schema yet. A future iteration could add a `teams` table plus `team_id` on `users` and `projects` to properly scope Manager access.
- **Permission-based access control**: a `permissions` table and `role_permissions` pivot already exist in the schema but are currently unused — Policies check role names directly rather than granular permissions. Wiring these up would allow finer-grained, admin-configurable access control without code changes.
- **Role slugs**: Policies currently match role names as raw strings (`hasRole('Admin')`); a stable `slug` column separate from a display `name` would decouple authorization logic from UI-facing role labels.
