<?php

namespace Kiamars\RbacArchitect\Traits;

use Illuminate\Database\Eloquent\Relations\MorphMany;
use Kiamars\RbacArchitect\Models\Role;
use Kiamars\RbacArchitect\Models\Permission;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

trait HasRbac
{
    public function roles(): MorphMany
    {
        return $this->morphMany(config('rbac.models.role_assignment', 'Kiamars\RbacArchitect\Models\RoleAssignment'), 'model');
    }

    public function directPermissions(): MorphMany
    {
        return $this->morphMany(config('rbac.models.permission_assignment', 'Kiamars\RbacArchitect\Models\PermissionAssignment'), 'model');
    }

    public function hasPermissionTo($permission, $context = null): bool
    {
        if ($this->isRoot()) {
            return true;
        }

        $now = Carbon::now();

        // Check direct permissions
        $hasDirect = DB::table('model_has_permissions')
            ->join('permissions', 'model_has_permissions.permission_id', '=', 'permissions.id')
            ->where('model_type', get_class($this))
            ->where('model_id', $this->id)
            ->where('permissions.name', $permission)
            ->where(function ($query) use ($now) {
            $query->whereNull('activated_at')->orWhere('activated_at', '<=', $now);
        })
            ->where(function ($query) use ($now) {
            $query->whereNull('expired_at')->orWhere('expired_at', '>=', $now);
        })
            ->when($context, function ($query) use ($context) {
            $query->where('context_type', get_class($context))
                ->where('context_id', $context->id);
        }, function ($query) {
            $query->whereNull('context_type');
        })
            ->exists();

        if ($hasDirect) {
            return true;
        }

        // Check permissions through roles
        return DB::table('model_has_roles')
            ->join('roles', 'model_has_roles.role_id', '=', 'roles.id')
            ->join('role_has_permissions', 'roles.id', '=', 'role_has_permissions.role_id')
            ->join('permissions', 'role_has_permissions.permission_id', '=', 'permissions.id')
            ->where('model_type', get_class($this))
            ->where('model_id', $this->id)
            ->where('permissions.name', $permission)
            ->where(function ($query) use ($now) {
            $query->whereNull('activated_at')->orWhere('activated_at', '<=', $now);
        })
            ->where(function ($query) use ($now) {
            $query->whereNull('expired_at')->orWhere('expired_at', '>=', $now);
        })
            ->when($context, function ($query) use ($context) {
            $query->where('context_type', get_class($context))
                ->where('context_id', $context->id);
        }, function ($query) {
            $query->whereNull('context_type');
        })
            ->exists();
    }

    public function isRoot(): bool
    {
        // Simple implementation of root mode. 
        // Can be expanded to check a config or a specific role name like 'super-admin'
        return method_exists($this, 'hasRole') && $this->hasRole('root');
    }

    public function hasRole($role, $context = null): bool
    {
        $now = Carbon::now();

        return DB::table('model_has_roles')
            ->join('roles', 'model_has_roles.role_id', '=', 'roles.id')
            ->where('model_type', get_class($this))
            ->where('model_id', $this->id)
            ->where('roles.name', $role)
            ->where(function ($query) use ($now) {
            $query->whereNull('activated_at')->orWhere('activated_at', '<=', $now);
        })
            ->where(function ($query) use ($now) {
            $query->whereNull('expired_at')->orWhere('expired_at', '>=', $now);
        })
            ->when($context, function ($query) use ($context) {
            $query->where('context_type', get_class($context))
                ->where('context_id', $context->id);
        }, function ($query) {
            $query->whereNull('context_type');
        })
            ->exists();
    }

    public function assignRole($role, $context = null, $activatedAt = null, $expiredAt = null)
    {
        if (is_string($role)) {
            $role = Role::where('name', $role)->firstOrFail();
        }

        DB::table('model_has_roles')->insert([
            'role_id' => $role->id,
            'model_type' => get_class($this),
            'model_id' => $this->id,
            'context_type' => $context ? get_class($context) : null,
            'context_id' => $context ? $context->id : null,
            'activated_at' => $activatedAt,
            'expired_at' => $expiredAt,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}