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
use App\Services\LogHubCloudService;
use Ramsey\Uuid\Uuid;

class AuthController extends Controller
{
    protected $logHubCloudService;
    protected $logger = [];

    public function __construct()
    {
        $this->logHubCloudService = new LogHubCloudService();
    }

    // Registrar um novo usuário
    public function register(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:6|confirmed',
        ]);

        if ($validator->fails()) {

            //LogHubCloud
            $this->logger['function'] = 'register';
            $this->logger['message'] = 'Erro tentando criar usuario. nome: '. $request->name . ' email: '. $request->email;
            $this->logger['type'] = 4;
            $this->logHubCloudService->logger($this->logger);

            return response()->json($validator->errors(), 422);
        }

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'chave' => Uuid::uuid4()->toString(), // Gerando uma chave única para o usuário
        ]);

        $token = JWTAuth::fromUser($user);

        //LogHubCloud
        $this->logger['function'] = 'register';
        $this->logger['message'] = 'Usuário criado com sucesso. nome: '. $request->name . ' email: '. $request->email;
        $this->logger['type'] = 1;
        $this->logHubCloudService->logger($this->logger);

        return response()->json(compact('user', 'token'), 201);
    }

    // Login do usuário
    public function login(Request $request)
    {
        $credentials = $request->only('email', 'password');
        if (!$token = JWTAuth::attempt($credentials)) {

            //LogHubCloud
            $this->logger['function'] = 'login';
            $this->logger['message'] = 'Credenciais inválidas. email: '. $request->email;
            $this->logger['type'] = 4;
            $this->logHubCloudService->logger($this->logger);

            return response()->json(['error' => 'Credenciais inválidas.'], 401);
        }

        //LogHubCloud
        $this->logger['function'] = 'login';
        $this->logger['message'] = 'Login efetuado com sucesso. email: '. $request->email;
        $this->logger['type'] = 1;
        $this->logHubCloudService->logger($this->logger);

        return response()->json(compact('token'));
    }

    // Obter o usuário autenticado
    public function me()
    {
        $dados = Auth::user();

        //LogHubCloud
        $this->logger['function'] = 'me';
        $this->logger['message'] = 'Dados de ' . $dados->email;
        $this->logger['type'] = 0;
        $this->logHubCloudService->logger($this->logger);

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
            return response()->json($validator->errors(), 422);
        }

        $user = User::where('email', $request->email)->first();
        if (!$user) {

            //LogHubCloud
            $this->logger['function'] = 'forgotpassword';
            $this->logger['message'] = 'Email inexistente: ' . $request->email;
            $this->logger['type'] = 2;
            $this->logHubCloudService->logger($this->logger);

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
        $dados_email = [
            'email' => $request->email,
            'subject' => 'Esqueci minha senha',
            'message' => 'Olá, ' . $user->name . '! Sua senha foi alterada para: ' . $securePassword
        ];

        // Enviando a requisição POST para a API externa
        $response = Http::withToken($token)->post(env('SERVICE_SENDMAIL').'/api/send-email/', $dados_email);

        // Verificando se a requisição foi bem-sucedida
        if ($response->successful()) {

            //LogHubCloud
            $this->logger['function'] = 'forgotpassword';
            $this->logger['message'] = 'Email inexistente: ' . $request->email;
            $this->logger['type'] = 1;
            $this->logHubCloudService->logger($this->logger);

            return response()->json(['message' => 'Email enviado com sucesso!']);
        } else {

            //LogHubCloud
            $this->logger['function'] = 'forgotpassword';
            $this->logger['message'] = 'Falha ao enviar o email: ' . $request->email;
            $this->logger['type'] = 4;
            $this->logHubCloudService->logger($this->logger);

            return response()->json(['error' => 'Falha ao enviar o email'], $response->status());
        }
    }

    public function updatepassword(Request $request)
    {
        $credentials = $request->only('email', 'password');
        if (!$token = JWTAuth::attempt($credentials)) {

            //LogHubCloud
            $this->logger['function'] = 'updatepassword';
            $this->logger['message'] = 'Credenciais inválidas: ' . $request->email;
            $this->logger['type'] = 4;
            $this->logHubCloudService->logger($this->logger);

            return response()->json(['error' => 'Credenciais inválidas'], 401);
        }
        $user = User::where('email', $request->email)->first();
        $user->password = Hash::make($request->new_password);
        $user->save();

        //LogHubCloud
        $this->logger['function'] = 'updatepassword';
        $this->logger['message'] = 'Senha alterada com sucesso: ' . $request->email;
        $this->logger['type'] = 1;
        $this->logHubCloudService->logger($this->logger);

        return response()->json(['message' => 'Senha alterada com sucesso!']);
    }

}
