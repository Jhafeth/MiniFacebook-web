<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Auth;
use Carbon\Carbon;
use Exception;
use App\User;
use Image;
use Illuminate\Support\Facades\Response;

class AuthController extends Controller
{
    public function index(){
        return view('auth.index');
    }

    public function login(Request $request){
        $request->validate([
            'email_' => 'required|email',
            'password_' => 'required',
        ]);
        if(!User::where('email',$request->email_)->first()){
            throw AuthController::newError('email_','Correo no registrado');
        }
        if (Auth::attempt(['email'=>$request->email_,'password'=>$request->password_])) {
            return redirect()->intended('home');
        }
        throw AuthController::newError('password_','Contraseña incorrecta');
    }

    public function register(Request $request){
        $request->validate([
            'names' => 'required',
            'paternal_surname' => 'required',
            'maternal_surname' => 'required',
            'birthday' => 'required|date',
            'email' => 'required|email|unique:users',
            'password' => 'required|confirmed',
        ]);
        $user=new User();
        $user->names=$request->names;
        $user->paternal_surname=$request->paternal_surname;
        $user->maternal_surname=$request->maternal_surname;
        $user->birthday=$request->birthday;
        $user->email=$request->email;

        $image=Image::make(base_path('public/images/pp-default.jpeg'));
        Response::make($image->encode('jpeg'));
        $user->profile_picture=$image;

        $user->password=bcrypt($request->password);
        $user->created_at=Carbon::now();
        $user->updated_at=Carbon::now();
        $user->save();
        if (Auth::attempt(['email'=>$request->email,'password'=>$request->password])) {
            return redirect()->route('home');
        }
        return redirect()->route('authenticate');
    }

    public function logout(){
        Auth::logout();
        return redirect()->route('authenticate');
    }

    public function changePassword(Request $request){
        $request->validate([
            'old_password' => 'required',
            'password' => 'required|confirmed',
        ]);
        $user=Auth::user();
        if (Auth::attempt(['email'=>$user->email,'password'=>$request->old_password])) {
            $user->password=bcrypt($request->password);
            $user->save();
        }else{
            throw AuthController::newError("old_password","Contraseña incorrecta.");
        }
    }

    public static function newError($key,$value){
        $error = \Illuminate\Validation\ValidationException::withMessages([
            $key=>$value
        ]);
        return $error;
    }
}