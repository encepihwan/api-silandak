<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use App\Http\Helpers\Json;
// use Validator;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use MongoDB\Laravel\Eloquent\Model;


class AuthController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:api', ['except' => ['login', 'register']]);
    }

    public function register(Request $request)
    {
        try{

            // Validasi data masukan
            $validator = Validator::make($request->all(), [
                'name' => 'required|string|between:2,100',
                'email' => 'required|string|email|max:100|unique:users',
                // 'password' => 'required|string|confirmed|min:6',
            ]);
            if ($validator->fails()) {
                return response()->json($validator->errors()->toJson(), 400);
            }
            // Buat pengguna baru
            $user = new User();
            $user->name = $request->name;
            $user->email = $request->email;
            $user->password = bcrypt($request->password);
            $user->status = 'not_active';
            $user->created_at = now();
            $user->updated_at = '';
            $user->save();

            // Berikan respons sukses
            return Json::response($user);
            
        }catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return Json::exception('Error Model ' . $debug = env('APP_DEBUG', false) == true ? $e : '');
        } catch (\Illuminate\Database\QueryException $e) {
            return Json::exception('Error Query ' . $debug = env('APP_DEBUG', false) == true ? $e : '');
        } catch (\ErrorException $e) {
            return Json::exception('Error Exception ' . $debug = env('APP_DEBUG', false) == true ? $e : '');
        }
       
    }

    public function login(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
            'password' => 'required',
        ]);
    
        if ($validator->fails()) {
            return response()->json([
                'status' => 'failed',
                'message' => $validator->errors()->first()
            ], 400);
        }
    
        $credentials = $request->only('email', 'password');
        // Cari pengguna berdasarkan alamat email
        $user = User::where('email', $request->email)->first();
    
        if (!auth()->attempt($credentials)) {
            return Json::exception("Invalid password");
        }

        $token = auth()->attempt($credentials);

        $data = [
            'token' => $token,
            'user' => $user
        ];

        return $this->createNewToken($data);
    }

    protected function createNewToken($token)
    {

        $responses = [
            'access_token' => $token,
            'token_type' => 'bearer',
            'message' => 'Login Success',
            'status' => 'success',
            'expires_in' => auth()->factory()->getTTL() * 60,
        ];

        return Json::response($responses);
    }
}
