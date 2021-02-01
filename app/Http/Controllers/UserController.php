<?php

namespace App\Http\Controllers;

use App\User;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class UserController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        return response([
            'name' => trim("$user->last_name, $user->first_name $user->middle_name"),
        ]);
    }

    public function logout(Request $request)
    {
        try {
            $request->user()->tokens()->delete();
        } catch (Exception $e) {
            Log::error("[logout]: {$e->getMessage()}");
        }

        return response([]);
    }

    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required',
            'device' => 'required',
        ], [
            'required' => '必填',
            'email' => '格式不符',
        ]);

        $user = User::where('email', $request->email)->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['帳號或密碼錯誤'],
            ]);
        }

        $token = $user->createToken($request->device)->plainTextToken;
        return response(['tk' => $token]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'email' => 'required|email|unique:users',
            'password' => 'required',
            'last_name' => 'required',
            'first_name' => 'required',
        ], [
            'required' => '必填',
            'email' => '格式不符',
        ]);


        $isPerson = (bool) $request->get('isPerson', false);

        DB::beginTransaction();
        try {
            $user = new User();
            $user->first_name = $request->get('first_name');
            $user->last_name = $request->get('last_name');
            $user->middle_name = $request->get('middle_name', '') ?? '';
            $user->email = $request->get('email');
            $user->password = Hash::make($request->get('password'));
            $user->biology_departments = implode(',', $request->get('biology_departments'));
            $user->save();

//            $user->saveAsPerson();
            DB::commit();
        } catch (Exception $e) {
            DB::rollBack();
            return response([
                'messages' => $e->getMessage()
            ])->setStatusCode(500);
        }
    }
}
