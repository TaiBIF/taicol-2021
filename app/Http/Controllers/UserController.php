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
use App\Mail\Email;
use Illuminate\Support\Facades\Mail;

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
            'id' => $user->id,
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
            'required' => 'user.required',
            'email' => 'user.email',
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
            'password_confirm' => [
                Rule::requiredIf(function () use ($request) {
                    return !!$request->get('password');
                }),
            ],
        ], [
            'required' => 'user.required',
            'email' => 'user.email',
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

            # 寄信給管理員通知需要開通
            $adminEmails = User::where('role_id',1)->where('status',1)->pluck('email')->toArray();
            Mail::bcc($adminEmails)->send(new Email('有新的註冊帳號', '[TaiCOL] 註冊帳號通知', '管理員'));

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
            'required' => 'user.required',
            'email' => 'user.email',
        ]);

        $user = User::find($id);

        $originalStatus =  $user->status;

        if (!$user) {
            throw ValidationException::withMessages([]);
        }

        $user->status = (int) $request->get('status');
        $user->save();

        # 原本不是開通 後來變成開通 寄信通知

        if ($originalStatus != 1 && $user->status == 1){
            Mail::to($user->email)->send(new Email('您在物種學名管理工具註冊的帳號已經開通，歡迎使用～<br>
                                                    Your user account in TaiCOL - Name Tool has been activated. Welcome.<br>
                                                    https://nametool.taicol.tw/', 'TaiCOL物種學名管理工具 - 註冊帳號開通通知',$user->name));
         }

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
            'required' => 'user.required',
            'email' => 'user.email',
            'same' => '「:attribute」與「:other」需一致'
        ], [
            'password' => '密碼',
            'password_confirm' => '再次確認密碼',
        ]);

        $user = User::find($id);

        $originalStatus =  $user->status;

        if (!$user) {
            throw ValidationException::withMessages([]);
        }

        $password = $request->get('password');

        $user->name = $request->get('name');
        $user->role_id = $request->get('role', 0);
        $user->biology_departments = implode(',', $request->get('biology_departments'));

        if ($password != '' && $password == $request->get('password_confirm')) {
            $user->password = Hash::make($request->get('password'));
        }

        $user->save();

        # 原本不是開通 後來變成開通 寄信通知

        if ($originalStatus != 1 && $user->status == 1){
            Mail::to($user->email)->send(new Email('您在物種學名管理工具註冊的帳號已經開通，歡迎使用～<br>
                                                    Your user account in TaiCOL - Name Tool has been activated. Welcome.<br>
                                                    https://nametool.taicol.tw/','TaiCOL物種學名管理工具 - 註冊帳號開通通知', $user->name));
         }

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
