<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use App\Models\Permission;
use App\Models\User;
use App\Models\Role;

class Permission extends Model {
    protected $fillable = ['name'];
    public function roles() { return $this->belongsToMany(Role::class); }
}