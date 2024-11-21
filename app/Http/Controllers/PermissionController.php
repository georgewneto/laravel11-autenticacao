<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Tymon\JWTAuth\Facades\JWTAuth;
use Tymon\JWTAuth\Exceptions\JWTException;
use Illuminate\Support\Facades\Http;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class PermissionController extends Controller
{
    public function index()
    {
        // Cria uma permissão
        Permission::create(['name' => 'edit users']);

        // Cria um papel e atribui permissão a ele
        $role = Role::create(['name' => 'admin']);
        $role->givePermissionTo('edit users');

        $user = User::find(1);
        $user->assignRole('admin');

        $user2 = User::find(2);
        $user2->assignRole('admin');

        return json_encode("OK");
    }
}
