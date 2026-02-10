<?php

namespace Kiamars\RbacArchitect\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Organization extends Model
{
    protected $fillable = [
        'name',
        'parent_id',
        'type',
        'description',
        'is_business',
    ];

    /**
     * Get the business organization.
     */
    public static function getBusiness(): ?self
    {
        return self::where('is_business', true)->first();
    }

    /**
     * Get the parent organization.
     */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(Organization::class , 'parent_id');
    }

    /**
     * Get child organizations.
     */
    public function children(): HasMany
    {
        return $this->hasMany(Organization::class , 'parent_id');
    }

    /**
     * Get all descendants recursively.
     */
    public function descendants(): array
    {
        $descendants = [];

        foreach ($this->children as $child) {
            $descendants[] = $child;
            $descendants = array_merge($descendants, $child->descendants());
        }

        return $descendants;
    }

    /**
     * Get all ancestors recursively.
     */
    public function ancestors(): array
    {
        $ancestors = [];
        $current = $this->parent;

        while ($current) {
            $ancestors[] = $current;
            $current = $current->parent;
        }

        return $ancestors;
    }

    /**
     * Get all employees (users) of this organization.
     */
    public function employees(): BelongsToMany
    {
        return $this->belongsToMany(
            config('auth.providers.users.model'),
            'organization_employees',
            'organization_id',
            'user_id'
        )->withPivot('position', 'is_active')->withTimestamps();
    }

    /**
     * Check if this organization is a descendant of another.
     */
    public function isDescendantOf(Organization $organization): bool
    {
        return in_array($organization->id, array_map(fn($o) => $o->id, $this->ancestors()));
    }

    /**
     * Check if this organization is an ancestor of another.
     */
    public function isAncestorOf(Organization $organization): bool
    {
        return in_array($organization->id, array_map(fn($o) => $o->id, $this->descendants()));
    }
}