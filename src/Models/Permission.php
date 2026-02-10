<?php

namespace Kiamars\RbacArchitect\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Permission extends Model
{
    protected $fillable = ['name', 'guard_name', 'label'];

    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class , 'role_has_permissions');
    }
}