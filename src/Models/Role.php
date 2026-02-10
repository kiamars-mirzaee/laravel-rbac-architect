<?php

namespace Kiamars\RbacArchitect\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Role extends Model
{
    protected $fillable = ['name', 'guard_name', 'label'];

    public function permissions(): BelongsToMany
    {
        return $this->belongsToMany(Permission::class , 'role_has_permissions');
    }
}