<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use JWTAuth;
use Tymon\JWTAuth\JWT;
use Exception;

use App\Models\User;
use App\Models\Token;
use JwtApi;

class ResController extends Controller
{
    /**
     * Create a new ResController instance.
     *
     * @return void
     */
    public function __construct()
    {

        $this->middleware('jwt.xresource');
    }

    public function user (Request $request){

        $token_obj = Token::findByValue(auth()->getToken()->get());
        $grantedAttr=[];
        foreach ( $token_obj->grants as $grant=>$val ){
        if ( $val ) array_push($grantedAttr, $grant);
        }

        return response()->json(['user' => auth()->user()->only($grantedAttr) ], 200);
    }
}
