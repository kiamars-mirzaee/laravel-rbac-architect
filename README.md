# Laravel RBAC Architect

[![PHP Version](https://img.shields.io/badge/php-%5E8.2-blue)](https://www.php.net/)
[![Laravel Version](https://img.shields.io/badge/laravel-%5E12.0-red)](https://laravel.com/)
[![License](https://img.shields.io/badge/license-MIT-green)](LICENSE)

A flexible and powerful Role-Based Access Control (RBAC) system for Laravel with polymorphic context support, temporal permissions, and root mode. Perfect for multi-tenant applications, project-based permissions, and complex authorization scenarios.

## Features

- 🏢 **Hierarchical Partners** - Support for partner hierarchies with permission inheritance
- ⏰ **Temporal Logic** - Set activation and expiration dates for role/permission assignments
- 👑 **Root Mode** - Built-in superuser support that bypasses all permission checks
- 🔄 **Polymorphic Relationships** - Apply permissions to any model type
- 🛡️ **Middleware Protection** - Easy route protection with context-aware middleware
- 🎨 **Flexible Architecture** - Easy to extend and customize
- 📊 **Many-to-Many Support** - Users can have multiple roles and permissions
- 👥 **User Types** - Support for system users and site users via Enum

## Installation

Install the package via Composer:

```bash
composer require kiamars/laravel-rbac-architect
```

Run the migrations:

```bash
php artisan migrate
```

## Quick Start

### 1. Add the Trait to Your User Model

```php
use Kiamars\RbacArchitect\Traits\HasRbac;

class User extends Authenticatable
{
    use HasRbac;
}
```

### 2. Create Roles and Permissions

```php
use Kiamars\RbacArchitect\Models\Role;
use Kiamars\RbacArchitect\Models\Permission;

// Create a global role
$adminRole = Role::create([
    'name' => 'admin',
    'description' => 'Administrator role',
]);

// Create a permission
$editPermission = Permission::create([
    'name' => 'edit-settings',
    'description' => 'Can edit application settings',
]);
```

### 3. Assign Roles to Users

```php
// Global role assignment
$user->assignRole('admin');

// Context-specific role (e.g., project manager for a specific project)
$project = Project::find(1);
$user->assignRole('manager', $project);

// With activation and expiration dates
$user->assignRole('trial-user', null, now(), now()->addDays(30));
```

### 4. Check Permissions

```php
// Global permission check
if ($user->hasPermissionTo('edit-settings')) {
    // User has permission
}

// Context-specific permission check
$project = Project::find(1);
if ($user->hasPermissionTo('edit-project', $project)) {
    // User has permission for this specific project
}

// Check if user is root (superuser)
if ($user->isRoot()) {
    // User has all permissions
}
```

### 5. Protect Routes with Middleware

```php
// Global permission
Route::get('/settings', [SettingsController::class, 'edit'])
    ->middleware('rbac:edit-settings');

// Context-specific permission
Route::get('/project/{id}', [ProjectController::class, 'edit'])
    ->middleware('rbac:edit-project,App\Models\Project,id');
```

## Usage Examples

### Context-Based Permissions

Perfect for multi-tenant or project-based applications:

```php
// User is a manager for Project #1
$project1 = Project::find(1);
$user->assignRole('manager', $project1);

// User is a viewer for Project #2
$project2 = Project::find(2);
$user->assignRole('viewer', $project2);

// Check permissions
$user->hasPermissionTo('edit-project', $project1); // true
$user->hasPermissionTo('edit-project', $project2); // false
```

### Temporal Permissions

Grant time-limited access:

```php
// Trial user for 30 days
$user->assignRole(
    'trial-user',
    null,
    now(),
    now()->addDays(30)
);

// Seasonal access
$user->assignPermission(
    'access-summer-features',
    null,
    now()->startOfSummer(),
    now()->endOfSummer()
);
```

### Root Mode

Superuser with all permissions:

```php
// Set user as root
$user->update(['is_root' => true]);

// Root users bypass all permission checks
$user->hasPermissionTo('any-permission'); // always true
$user->hasPermissionTo('any-permission', $anyContext); // always true
```

### Direct Permission Assignment

Assign permissions directly without roles:

```php
// Global permission
$user->assignPermission('view-reports');

// Context-specific permission
$partner = Partner::find(1);
$user->assignPermission('manage-billing', $partner);
```

### Checking Multiple Permissions

```php
// Check if user has any of the permissions
if ($user->hasAnyPermission(['edit-posts', 'delete-posts'])) {
    // User has at least one permission
}

// Check if user has all permissions
if ($user->hasAllPermissions(['edit-posts', 'publish-posts'])) {
    // User has all permissions
}
```

### Hierarchical Partners

Create partner hierarchies with permission inheritance:

```php
use Kiamars\RbacArchitect\Models\Partner;

// Create partner hierarchy
$company = Partner::create(['name' => 'Acme Corp', 'type' => 'company']);
$engineering = Partner::create([
    'name' => 'Engineering Department',
    'parent_id' => $company->id,
    'type' => 'department'
]);
$backend = Partner::create([
    'name' => 'Backend Team',
    'parent_id' => $engineering->id,
    'type' => 'team'
]);

// Add user as employee
$user->joinPartner($engineering, 'Senior Developer');

// Assign role with partner context
$user->assignRole('manager', $engineering);

// Check permission with hierarchy inheritance
$user->hasPermissionInPartner('manage-projects', $backend); // Inherits from parent

// Check partner membership
$user->isMemberOfPartner($engineering); // true

// Leave partner
$user->leavePartner($engineering);

// Get all user's partners
$partners = $user->partners;

// Get partner hierarchy
$ancestors = $engineering->ancestors(); // [company]
$descendants = $engineering->descendants(); // [backend team]
```

### User Types

Support for different user types via Enum:

```php
use Kiamars\RbacArchitect\Enums\UserType;

// Create system user (admin/super user)
$admin = User::create([
    'name' => 'Admin',
    'email' => 'admin@example.com',
    'user_type' => UserType::SYSTEM
]);

// Create site user (regular user)
$user = User::create([
    'name' => 'John Doe',
    'email' => 'john@example.com',
    'user_type' => UserType::SITE
]);

// Check user type
if ($user->isSystemUser()) {
    // System user logic
}

if ($user->isSiteUser()) {
    // Site user logic
}
```

## Database Schema

### Roles Table

| Column | Type | Description |
|--------|------|-------------|
| id | bigint | Primary key |
| name | string | Role name |
| description | text | Role description |
| created_at | timestamp | Creation time |
| updated_at | timestamp | Last update time |

### Permissions Table

| Column | Type | Description |
|--------|------|-------------|
| id | bigint | Primary key |
| name | string | Permission name |
| description | text | Permission description |
| created_at | timestamp | Creation time |
| updated_at | timestamp | Last update time |

### Model Has Roles Table

| Column | Type | Description |
|--------|------|-------------|
| id | bigint | Primary key |
| model_type | string | User model class |
| model_id | bigint | User ID |
| role_id | bigint | Role ID |
| context_type | string | Context model class (nullable) |
| context_id | bigint | Context model ID (nullable) |
| activated_at | timestamp | When role becomes active |
| expired_at | timestamp | When role expires (nullable) |
| created_at | timestamp | Assignment time |

### Model Has Permissions Table

| Column | Type | Description |
|--------|------|-------------|
| id | bigint | Primary key |
| model_type | string | User model class |
| model_id | bigint | User ID |
| permission_id | bigint | Permission ID |
| context_type | string | Context model class (nullable) |
| context_id | bigint | Context model ID (nullable) |
| activated_at | timestamp | When permission becomes active |
| expired_at | timestamp | When permission expires (nullable) |
| created_at | timestamp | Assignment time |

## Advanced Usage

### Custom Context Models

Use any model as a context:

```php
// Site-specific permissions
$site = Site::find(1);
$user->assignRole('site-admin', $site);

// Partner-specific permissions
$partner = Partner::find(1);
$user->assignPermission('manage-members', $partner);

// Department-specific permissions
$department = Department::find(1);
$user->assignRole('department-head', $department);
```

### Middleware with Dynamic Context

```php
// The middleware will automatically resolve the context from route parameters
Route::put('/project/{project}/settings', [ProjectController::class, 'updateSettings'])
    ->middleware('rbac:edit-project-settings,App\Models\Project,project');
```

### Revoking Permissions

```php
// Revoke a role
$user->revokeRole('manager', $project);

// Revoke a permission
$user->revokePermission('edit-project', $project);

// Revoke all roles
$user->revokeAllRoles();

// Revoke all permissions
$user->revokeAllPermissions();
```

### Querying Users by Permission

```php
// Get all users with a specific permission
$users = User::whereHas('permissions', function ($query) {
    $query->where('name', 'edit-settings');
})->get();

// Get all users with a specific role
$users = User::whereHas('roles', function ($query) {
    $query->where('name', 'admin');
})->get();
```

## API Reference

### HasRbac Trait Methods

```php
// Role methods
$user->assignRole(string $roleName, ?Model $context = null, ?Carbon $activatedAt = null, ?Carbon $expiredAt = null)
$user->revokeRole(string $roleName, ?Model $context = null)
$user->hasRole(string $roleName, ?Model $context = null): bool
$user->revokeAllRoles()

// Permission methods
$user->assignPermission(string $permissionName, ?Model $context = null, ?Carbon $activatedAt = null, ?Carbon $expiredAt = null)
$user->revokePermission(string $permissionName, ?Model $context = null)
$user->hasPermissionTo(string $permissionName, ?Model $context = null): bool
$user->hasAnyPermission(array $permissions, ?Model $context = null): bool
$user->hasAllPermissions(array $permissions, ?Model $context = null): bool
$user->revokeAllPermissions()

// Root mode
$user->isRoot(): bool

// Partner methods
$user->joinPartner($partner, ?string $position = null)
$user->leavePartner($partner)
$user->isMemberOfPartner($partner): bool
$user->hasPermissionInPartner(string $permission, $partner, bool $checkHierarchy = true): bool
$user->partners() // BelongsToMany relationship

// User type methods
$user->isSystemUser(): bool
$user->isSiteUser(): bool
```


## Testing

Run the test suite:

```bash
composer test
```

## Contributing

Contributions are welcome! Please see [CONTRIBUTING.md](CONTRIBUTING.md) for details.

## Security

If you discover any security-related issues, please email kiamars.mirzaee@gmail.com instead of using the issue tracker.

## Credits

Made with ❤️ by [Architect PHP](https://github.com/kiamars-mirzaee)

## License

The MIT License (MIT). Please see [License File](LICENSE) for more information.
