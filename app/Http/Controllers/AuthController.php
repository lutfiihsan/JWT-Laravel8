<?php

namespace App\Http\Controllers;


use Exception;
use App\Models\User;
use App\Models\Token;


use App\Helpers\JwtApi;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;


class AuthController extends Controller
{
    // use JwtApi;
    /**
     * Create a new AuthController instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('jwt.verify', ['except' => ['login', 'register']]);
        $this->middleware('jwt.xauth', ['except' => ['login', 'register', 'refresh']]);
        $this->middleware('jwt.xrefresh', ['only' => ['refresh']]);
    }


    /**
     * Get a JWT via given credentials.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function login()
    {
        $credentials = request(['email', 'password']);


        if (! $access_token = auth()->claims(['xtype' => 'auth'])->attempt($credentials)) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }
			
        return $this->respondWithToken($access_token);
    }

    /**
     * Register new user
     *
     * @param  string $name, $email, $password, password_confirmation
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function register(Request $request){
        
        $validator = Validator::make($request->all(), [
                'name' => 'required|string|max:255',
                'email' => 'required|string|email|max:255|unique:users',
                'password' => 'required|string|min:6|confirmed',
            ]);
            
        if($validator->fails()){
                return response()->json([
                    'status' => 'error',
                    'success' => false,
                    'error' =>
                    $validator->errors()->toArray()
                ], 400);
        }
            
        $user = User::create([
                'name' => $request->input('name'),
                'email' => $request->input('email'),
                'password' => Hash::make($request->input('password')),
            ]);
            
        return response()->json([
            'message' => 'User created.',
                'user' => $user
            ]);	
    }


    /**
     * Get the authenticated User.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function me()
    {
        return response()->json(auth()->user());
    }


    /**
     * Log the user out (Invalidate the token).
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function logout()
    {
        $refresh_token_obj = Token::findPairByValue( auth()->getToken()->get() );
        auth()->logout();
        auth()->setToken( $refresh_token_obj->value )->logout();

        return response()->json(['message' => 'Successfully logged out']);
    }

    public function logoutall(Request $request){
        foreach( auth()->user()->authTokens() as $token_obj ){
            try{
            auth()->setToken( $token_obj->value )->invalidate(true);
            }
            catch (Exception $e){
            //do nothing, it's already bad token for various reasons
            }
        }

        return response()->json(['message' => 'Successfully logged out from all devices']);
    }


    /**
     * Refresh a token.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function refresh()
    {
        $access_token = auth()->claims(['xtype' => 'auth'])->refresh(true,true);
        auth()->setToken($access_token); 

        return $this->respondWithToken($access_token);
    
    }


    /**
     * Get the token array structure.
     *
     * @param  string $token
     *
     * @return \Illuminate\Http\JsonResponse
     */
    protected function respondWithToken($access_token)
    {
        $response_array = [
            'access_token' => $access_token,
            'token_type' => 'bearer',
            'access_expires_in' => auth()->factory()->getTTL() * 60,
        ];

        $access_token_obj = Token::create([
            'user_id' => auth()->user()->id,
            'value' => $access_token, //or auth()->getToken()->get();
            'jti' => auth()->payload()->get('jti'),
            'type' => auth()->payload()->get('xtype'),
            'payload' => auth()->payload()->toArray(),
            'ip' => JwtApi::getIp(),
            'device' => JwtApi::getUserAgent()
        ]);

        $refresh_token = auth()->claims([
            'xtype' => 'refresh',
            'xpair' => auth()->payload()->get('jti')
            ])->setTTL(auth()->factory()->getTTL() * 3)->tokenById(auth()->user()->id);

        $response_array +=[
            'refresh_token' => $refresh_token,
            'refresh_expires_in' => auth()->factory()->getTTL() * 60
        ];

        $refresh_token_obj = Token::create([
            'user_id' => auth()->user()->id,
            'value' => $refresh_token,
            'jti' => auth()->setToken($refresh_token)->payload()->get('jti'),
            'type' => auth()->setToken($refresh_token)->payload()->get('xtype'),
            'pair' => $access_token_obj->id,
            'payload' => auth()->setToken($refresh_token)->payload()->toArray(),
            'ip' => JwtApi::getIp(),
            'device' => JwtApi::getUserAgent()
        ]);

        $access_token_obj->pair = $refresh_token_obj->id;
        $access_token_obj->save();

        return response()->json($response_array);
    }
	
	/**
     * Issue resource token
     * @param id, name, email as bool
     * @return \Illuminate\Http\JsonResponse
     */
    public function tokenIssue(Request $request){
        $validate = Validator::make($request->all(), [
            'id' => 'required|boolean',
            'name' => 'required|boolean',
            'email' => 'required|boolean'
        ]);

        if ( $validate->fails() ){
            return response()->json(['message' => 'Error! Bad input.'], 400);
        }

        $resource_token = auth()->claims([
            'xtype' => 'resource'
            ])->setTTL(60 * 24 * 365)->tokenById(auth()->user()->id); //expire in 1 year

        $resource_token_obj = Token::create([
            'user_id' => auth()->user()->id,
            'value' => $resource_token,
            'jti' => auth()->setToken($resource_token)->payload()->get('jti'),
            'type' => auth()->setToken($resource_token)->payload()->get('xtype'),
            'pair' => null,
            'payload' => auth()->setToken($resource_token)->payload()->toArray(),
            'grants' => [
                'id' => $request->input('id'),
                'name' => $request->input('name'),
                'email' => $request->input('email')
            ],
            'ip' => null,
            'device' => null
        ]);
        return response()->json(['token' => $resource_token]);
    }
}
