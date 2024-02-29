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

use Google\Cloud\RecaptchaEnterprise\V1\RecaptchaEnterpriseServiceClient;
use Google\Cloud\RecaptchaEnterprise\V1\Event;
use Google\Cloud\RecaptchaEnterprise\V1\Assessment;
use Google\Cloud\RecaptchaEnterprise\V1\TokenProperties\InvalidReason;


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
            $user->status = 'active';
            $user->created_at = now();
            $user->role = $request->role;
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
    
        $userResponseToken = $request->input('g-recaptcha-response');

        // Panggil fungsi untuk memverifikasi reCAPTCHA
        $verificationResult = $this->createAssessment($userResponseToken);

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

        return $this->createNewToken($data, $verificationResult);
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

    
    public function logout()
    {
        auth()->logout();
        return Json::response();
        // return response()->json(['message' => 'User successfully signed out']);
    }

    private function createAssessment(string $token)
    {
        try {
            // Create the reCAPTCHA client.
            $client = new RecaptchaEnterpriseServiceClient();

            // Set the properties of the event to be tracked.
            $event = (new Event())
                ->setSiteKey(env('RECAPTCHA_ENTERPRISE_KEY_ID', '6LdtdXEpAAAAAIWc0qZNIBpGIirR1pjUt05GKWDC')) // Replace with your reCAPTCHA site key
                ->setToken($token);

            // Build the assessment request.
            $assessment = (new Assessment())
                ->setEvent($event);

            // Get the project name from the client.
            $projectName = $client->projectName(env('GOOGLE_CLOUD_PROJECT_ID', 'sibedasabsensi-1707845400236')); // Replace with your Google Cloud Project ID

            // Create assessment.
            $response = $client->createAssessment($projectName, $assessment);

            // Check if the token is valid.
            if ($response->getTokenProperties()->getValid() == false) {
                // Handle invalid token.
                return response()->json(['error' => 'Invalid token']);
            }

            // Check if the expected action was executed.
            if ($response->getTokenProperties()->getAction() == 'LOGIN') {
                // Get the risk score and the reason(s).
                $score = $response->getRiskAnalysis()->getScore();
                return response()->json(['score' => $score]);
            } else {
                // Handle action mismatch.
                return response()->json(['error' => 'Action mismatch']);
            }
        } catch (\Exception $e) {
            // Handle exceptions.
            return response()->json(['error' => $e->getMessage()]);
        } finally {
            // Close the client to free up resources.
            if (isset($client)) {
                $client->close();
            }
        }
    }
}
