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
use Ramsey\Uuid\Uuid;
use Illuminate\Support\Facades\Log;

class AuthController extends Controller
{
    // Registrar um novo usuário
    public function register(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:6|confirmed',
        ]);

        if ($validator->fails()) {
            Log::error('Cadastro de usuário falhou', [$validator->errors()]);
            return response()->json($validator->errors(), 422);
        }

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'chave' => Uuid::uuid4()->toString(), // Gerando uma chave única para o usuário
        ]);

        $token = JWTAuth::fromUser($user);
        Log::info('Cadastro de usuário realizado com sucesso', [$request->email]);
        return response()->json(compact('user', 'token'), 201);
    }

    // Login do usuário
    public function login(Request $request)
    {
        $credentials = $request->only('email', 'password');
        if (!$token = JWTAuth::attempt($credentials)) {
            Log::error('Login via email falhou', [$credentials]);
            return response()->json(['error' => 'Credenciais inválidas.'], 401);
        }
        Log::info('Login via email realizado com sucesso', [$request->email]);
        return response()->json(compact('token'));
    }

    // Login do usuário via Cpf
    public function loginCpf(Request $request)
    {
        $credentials = $request->only('cpf', 'password');
        if (!$token = JWTAuth::attempt($credentials)) {
            Log::error('Login via CPF falhou', [$credentials]);
            return response()->json(['error' => 'Credenciais inválidas.'], 401);
        }
        Log::info('Login via CPF realizado com sucesso', [$request->cpf]);
        return response()->json(compact('token'));
    }

    // Obter o usuário autenticado
    public function me()
    {
        $dados = Auth::user();

        return response()->json($dados);
    }

    // Logout do usuário
    public function logout()
    {
        Auth::logout();

        return response()->json(['message' => 'Logout realizado com sucesso!']);
    }

    // Refresh no token JWT
    /*
    public function refresh()
    {
        $newToken = Auth::refresh();
        return response()->json(['token' => $newToken]);
    }
    */
    public function refresh(Request $request)
    {
        try {
            // Gera um novo token a partir do token atual
            $newToken = JWTAuth::refresh(JWTAuth::getToken());

            return response()->json([
                'status' => 'success',
                'token' => $newToken,
            ], 200);
        } catch (JWTException $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Token inválido ou expirado'
            ], 401);
        }
    }

    protected function generateSecurePassword($length = 12)
    {
        // Definir o comprimento mínimo da senha
        if ($length < 8) {
            $length = 8; // Recomenda-se um mínimo de 8 caracteres para segurança
        }

        // Gera bytes aleatórios e converte para hexadecimal
        $bytes = random_bytes($length / 2); // Divide por 2 para obter o comprimento correto após a conversão
        $password = bin2hex($bytes);

        return substr($password, 0, $length); // Trunca para o comprimento desejado
    }

    public function forgotpassword(Request $request)
    {
        // TODO: Implementar a função para esquecer a senha
        $validator = Validator::make($request->all(), [
            'email' => 'required|string|email|max:255',
        ]);

        if ($validator->fails()) {
            Log::error('Esqueci minha senha falhou', [$validator->errors()]);
            return response()->json($validator->errors(), 422);
        }

        $user = User::where('email', $request->email)->first();
        if (!$user) {
            Log::error('Esqueci minha senha falhou', ['Email inexistente']);
            return response()->json([
                'status' => 'error',
                'message' => 'Email inexistente',
            ], 404);
        }
        $securePassword = $this->generateSecurePassword(12);
        $user->password = Hash::make($securePassword);
        $user->save();

        $credentials = [
            'email' => $request->email,
            'password' => $securePassword
        ];
        $token = JWTAuth::attempt($credentials);

        //enviar um email com esta senha para o usuário
        /*
        $dados_email = [
            'email' => $request->email,
            'subject' => 'Esqueci minha senha',
            'message' => 'Olá, ' . $user->name . '! Sua senha foi alterada para: ' . $securePassword
        ];
        */
        $dados_email = [
            'email_destino' => $request->email,
            'assunto' => 'Esqueci minha senha',
            'corpo' => 'Olá, ' . $user->name . '! Sua senha foi alterada para: ' . $securePassword,
            'from_email' => 'avisos@prefeituradeitabuna.com.br',
            'from_name' => 'Prefeitura Municipal de Itabuna - Avisos',
        ];

        // Enviando a requisição POST para a API externa
        //$response = Http::withToken($token)->post(env('SERVICE_SENDMAIL').'/api/send-email/', $dados_email);
        $response = Http::withToken($token)->post(env('SERVICE_SENDMAIL').'/api/store/', $dados_email);

        // Verificando se a requisição foi bem-sucedida
        if ($response->successful()) {
            Log::info('Email de recuperação de senha enviado com sucesso', ['email' => $request->email]);
            return response()->json(['message' => 'Email enviado com sucesso!']);
        } else {
            Log::error('Falha ao enviar email de recuperação de senha', ['email' => $request->email]);
            return response()->json(['error' => 'Falha ao enviar o email'], $response->status());
        }
    }

    public function updatepassword(Request $request)
    {
        $credentials = $request->only('email', 'password');
        if (!$token = JWTAuth::attempt($credentials)) {
            return response()->json(['error' => 'Credenciais inválidas'], 401);
        }
        $user = User::where('email', $request->email)->first();
        $user->password = Hash::make($request->new_password);
        $user->save();

        return response()->json(['message' => 'Senha alterada com sucesso!']);
    }

}
