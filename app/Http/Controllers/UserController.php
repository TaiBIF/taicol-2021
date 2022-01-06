<?php

namespace App\Http\Controllers;

use App\User;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class UserController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();


        if ($user->status != User::STATUS_ENABLE) {
            return response()->noContent()->setStatusCode(401);
        }

        return response([
            'name' => $user->name,
            'role_id' => $user->role_id,
        ]);
    }

    public function getUser($id)
    {
        $user = User::find($id);

        if (!$user) {
            return response()->noContent()->setStatusCode(404);
        }

        return response([
            'data' => [
                'id' => $user->id,
                'role' => $user->role_id,
                'name' => $user->name,
                'email' => $user->email,
                'biology_departments' => explode(',', $user->biology_departments),
                'status' => $user->status,
                'updated_at' => $user->updated_at->format('Y-m-d H:i:s'),
            ],
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
                'email' => ['帳號密碼不符或該組帳號尚未開通。'],
            ]);
        }

        if ($user->status !== 1) {
            throw ValidationException::withMessages([
                'email' => ['帳號密碼不符或該組帳號尚未開通。'],
            ]);
        }

        $token = $user->createToken($request->device)->plainTextToken;
        return response(['tk' => $token]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'email' => 'required|email|unique:users',
            'password' => 'required|same:password_confirm',
            'name' => 'required',
            'role' => 'required|in:1,0',
            'password_confirm' => [
                Rule::requiredIf(function () use ($request) {
                    return !!$request->get('password');
                }),
            ]
        ], [
            'required' => '必填',
            'email' => '格式不符',
            'same' => '「:attribute」與「:other」需一致',
            'unique' => '已被使用',
        ], [
            'password' => '密碼',
            'password_confirm' => '再次確認密碼',
        ]);

        DB::beginTransaction();
        try {
            $user = new User();
            $user->name = $request->get('name');
            $user->role_id = $request->get('role', 0);
            $user->email = $request->get('email');
            $user->password = Hash::make($request->get('password'));
            $user->biology_departments = implode(',', $request->get('biology_departments'));
            $user->status = 2; // 新的帳號皆為等待開通
            $user->save();

            DB::commit();
        } catch (Exception $e) {
            DB::rollBack();
            return response([
                'messages' => $e->getMessage()
            ])->setStatusCode(500);
        }
    }

    public function updateStatus(Request $request, $id)
    {
        $request->validate([
            'status' => 'required'
        ], [
            'required' => '必填',
            'email' => '格式不符',
        ]);

        $user = User::find($id);

        if (!$user) {
            throw ValidationException::withMessages();
        }

        $user->status = (int) $request->get('status');
        $user->save();

        return response([]);
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'name' => 'required',
            'status' => 'required',
            'role' => 'required|in:1,0',
            'password' => [
                'same:password_confirm',
                Rule::requiredIf(function () use ($request) {
                    return !!$request->get('password_confirm');
                }),
            ],
            'password_confirm' => [
                Rule::requiredIf(function () use ($request) {
                    return !!$request->get('password');
                }),
            ]
        ], [
            'required' => '必填',
            'email' => '格式不符',
            'same' => '「:attribute」與「:other」需一致'
        ], [
            'password' => '密碼',
            'password_confirm' => '再次確認密碼',
        ]);

        $user = User::find($id);

        if (!$user) {
            throw ValidationException::withMessages();
        }

        $password = $request->get('password');

        $user->name = $request->get('name');
        $user->role_id = $request->get('role', 0);
        $user->biology_departments = implode(',', $request->get('biology_departments'));

        if ($password != '' && $password == $request->get('password_confirm')) {
            $user->password = Hash::make($request->get('password'));
        }

        $user->save();

        return response(['user' => $user]);
    }

    public function list(Request $request)
    {
        $usersQuery = User::query();

        if ($request->get('sortby')) {
            $usersQuery->orderBy($request->get('sortby'), $request->get('direction', 'asc'));
        }

        $users = $usersQuery->paginate(20);

        return response([
            'message' => 'success',
            'total' => $users->total(),
            'data' => $users->items(),
            'per_page' => $users->perPage(),
            'current_page' => $users->currentPage(),
            'last_page' => $users->lastPage(),
        ]);
    }
}
