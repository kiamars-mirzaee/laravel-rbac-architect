<?php

namespace Kiamars\RbacArchitect\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Partner extends Model
{
    protected $fillable = [
        'name',
        'parent_id',
        'type',
        'description',
        'is_business',
    ];

    /**
     * Get the business partner.
     */
    public static function getBusiness(): ?self
    {
        return self::where('is_business', true)->first();
    }

    /**
     * Get the parent partner.
     */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(Partner::class , 'parent_id');
    }

    /**
     * Get child partners.
     */
    public function children(): HasMany
    {
        return $this->hasMany(Partner::class , 'parent_id');
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
     * Get all employees (users) of this partner.
     */
    public function employees(): BelongsToMany
    {
        return $this->belongsToMany(
            config('auth.providers.users.model'),
            'partner_employees',
            'partner_id',
            'user_id'
        )->withPivot('position', 'is_active')->withTimestamps();
    }

    /**
     * Check if this partner is a descendant of another.
     */
    public function isDescendantOf(Partner $partner): bool
    {
        return in_array($partner->id, array_map(fn($p) => $p->id, $this->ancestors()));
    }

    /**
     * Check if this partner is an ancestor of another.
     */
    public function isAncestorOf(Partner $partner): bool
    {
        return in_array($partner->id, array_map(fn($p) => $p->id, $this->descendants()));
    }
}