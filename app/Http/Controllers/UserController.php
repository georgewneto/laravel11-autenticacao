<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class UserController extends Controller
{
    public function index()
    {
        $users = User::orderBy('nome', 'ASC')->paginate(25);

        return response()->json([
            'status' => 'success',
            'data' => $users,
        ]);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|string|min:6',
            'cpf' => 'required|string|max:14',
            'telefone' => 'nullable|string|max:15',
            'role' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'errors' => $validator->errors(),
            ], 422);
        }

        $user = new User();
        $user->name = $request->input('name');
        $user->email = $request->input('email');
        $user->password = bcrypt($request->input('password'));
        $user->cpf = $request->input('cpf');
        $user->telefone = $request->input('telefone');
        $user->role = $request->input('role');
        $user->save();

        return response()->json([
            'status' => 'success',
            'message' => 'Usuário adicionado com sucesso!',
            'data' => $user,
        ]);
    }

    public function edit($id)
    {
        $user = User::find($id);

        if (!$user) {
            return response()->json([
                'status' => 'error',
                'message' => 'Usuário não encontrado.',
            ], 404);
        }

        return response()->json([
            'status' => 'success',
            'data' => $user,
        ]);
    }

    public function update(Request $request)
    {
        $user = User::find($request->input('id'));

        if (!$user) {
            return response()->json([
                'status' => 'error',
                'message' => 'Usuário não encontrado.',
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email,' . $user->id,
            'password' => 'nullable|string|min:6',
            'cpf' => 'required|string|max:14',
            'telefone' => 'nullable|string|max:15',
            'role' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'errors' => $validator->errors(),
            ], 422);
        }

        $user->name = $request->input('name');
        $user->email = $request->input('email');
        if ($request->input('password')) {
            $user->password = bcrypt($request->input('password'));
        }
        $user->cpf = $request->input('cpf');
        $user->telefone = $request->input('telefone');
        $user->role = $request->input('role');
        $user->save();

        return response()->json([
            'status' => 'success',
            'message' => 'Usuário atualizado com sucesso!',
            'data' => $user,
        ]);
    }

    public function delete($id)
    {
        $user = User::find($id);

        if (!$user) {
            return response()->json([
                'status' => 'error',
                'message' => 'Usuário não encontrado.',
            ], 404);
        }

        $user->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Usuário excluído com sucesso!',
        ]);
    }
}
