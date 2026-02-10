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

    /**
     * Get all partners this user belongs to.
     */
    public function partners()
    {
        return $this->belongsToMany(
            'Kiamars\RbacArchitect\Models\Partner',
            'partner_employees',
            'user_id',
            'partner_id'
        )->withPivot('position', 'is_active')->withTimestamps();
    }

    /**
     * Join a partner.
     */
    public function joinPartner($partner, $position = null)
    {
        if (is_numeric($partner)) {
            $partner = \Kiamars\RbacArchitect\Models\Partner::findOrFail($partner);
        }

        $this->partners()->attach($partner->id, [
            'position' => $position,
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return $this;
    }

    /**
     * Leave a partner.
     */
    public function leavePartner($partner)
    {
        if (is_numeric($partner)) {
            $partner = \Kiamars\RbacArchitect\Models\Partner::findOrFail($partner);
        }

        $this->partners()->detach($partner->id);

        return $this;
    }

    /**
     * Check if user is a member of a partner.
     */
    public function isMemberOfPartner($partner): bool
    {
        if (is_numeric($partner)) {
            $partnerId = $partner;
        }
        else {
            $partnerId = $partner->id;
        }

        return $this->partners()->withPivot('partner_id', $partnerId)->exists();
    }

    /**
     * Check if user has permission in partner or its ancestors.
     */
    public function hasPermissionInPartner($permission, $partner, $checkHierarchy = true): bool
    {
        if ($this->isRoot()) {
            return true;
        }

        if (is_numeric($partner)) {
            $partner = \Kiamars\RbacArchitect\Models\Partner::findOrFail($partner);
        }

        // Check permission in current partner
        if ($this->hasPermissionTo($permission, $partner)) {
            return true;
        }

        // Check hierarchy if enabled
        if ($checkHierarchy) {
            foreach ($partner->ancestors() as $ancestor) {
                if ($this->hasPermissionTo($permission, $ancestor)) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Check if user is a system user.
     */
    public function isSystemUser(): bool
    {
        return $this->user_type === \Kiamars\RbacArchitect\Enums\UserType::SYSTEM->value ||
            $this->user_type === \Kiamars\RbacArchitect\Enums\UserType::SYSTEM;
    }

    /**
     * Check if user is a site user.
     */
    public function isSiteUser(): bool
    {
        return $this->user_type === \Kiamars\RbacArchitect\Enums\UserType::SITE->value ||
            $this->user_type === \Kiamars\RbacArchitect\Enums\UserType::SITE;
    }
}