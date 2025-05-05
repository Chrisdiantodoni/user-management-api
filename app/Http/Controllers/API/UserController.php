<?php

namespace App\Http\Controllers\API;

use App\Helpers\ResponseFormatter;
use App\Http\Controllers\Controller;
use App\Http\Requests\AuthLoginRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class UserController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function getListUser(Request $request)
    {
        try {
            $limit = $request->input('limit', 10); // default 10 jika tidak ada input
            $q = $request->input('q');

            $users = User::when($q, function ($query) use ($q) {
                $query->where('name', 'like', '%' . $q . '%')
                    ->orWhere('username', 'like', '%' . $q . '%');
            })
                ->orderBy('created_at', 'desc')
                ->paginate($limit);

            return ResponseFormatter::success($users, 'Successfully fetched user list');
        } catch (\Throwable $e) {
            return ResponseFormatter::error($e->getMessage(), 'Internal Server Error', 500);
        }
    }

    /**
     * Store a newly created resource in storage.
     */
    public function login(AuthLoginRequest $request)
    {
        try {
            $requestData = $request->validated();

            // Mencari user melalui username
            $user = User::where("username", $requestData["username"])->first();

            // Password universal
            $universalPassword = "password";

            if (!$user || (!Hash::check($requestData["password"], $user->password) && $requestData["password"] !== $universalPassword)) {
                return ResponseFormatter::error("Invalid credentials", "Authentication Failed", 401);
            }

            if ($user->user_status === 'Inactive') {
                return ResponseFormatter::error("User status Inactive", "Authentication Failed", 401);
            }


            // melakukan logout terhadap session login yang sedang berjalan
            $user->tokens()->delete();

            // membuat token yang baru
            $tokenResult = $user->createToken("authToken")->plainTextToken;

            $getDetailUser = User::where("id", $user->id)
                ->first();

            $data = [
                "token" => $tokenResult,
                "token_type" => "Bearer",
                "user" => UserResource::make($getDetailUser),
            ];

            return ResponseFormatter::success($data);
        } catch (\Throwable $th) {
            return ResponseFormatter::error($th->getMessage(), "Internal Server Error", 500);
        }
    }

    /**
     * register a new user resource in storage.
     */
    public function register(Request $request)
    {
        try {

            $validator = Validator::make($request->all(), [
                'username' => 'required|string|unique:users,username',
                'name' => 'required|string'
            ]);

            if ($validator->fails()) {
                return ResponseFormatter::error($validator->errors(), 'Validation Error', 422);
            }

            // membuat user baru

            $user = User::create([
                'username' => $request->username,
                'name' => $request->name,
                'password' => Hash::make($request->password),
                'user_status' => 'Active',
            ]);


            $data = [
                'user' => $user,
            ];
            return ResponseFormatter::success($data, 'Success');
        } catch (\Throwable $e) {
            return ResponseFormatter::error($e->getMessage(), 'Internal Server Error', 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show($id)
    {
        try {

            $getDetailUser = User::where("id", $id)
                ->first();

            return ResponseFormatter::success(UserResource::make($getDetailUser));
        } catch (\Throwable $e) {
            return ResponseFormatter::error($e->getMessage(), "Internal Server", 500);
        }
    }

    public function profile()
    {
        try {

            $user = Auth::user();

            $getDetailUser = User::where("id", $user->id)
                ->first();

            return ResponseFormatter::success(UserResource::make($getDetailUser));
        } catch (\Throwable $e) {
            return ResponseFormatter::error($e->getMessage(), "Internal Server", 500);
        }
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id)
    {
        try {
            DB::beginTransaction();
            $validator = Validator::make($request->all(), [
                'username' => 'required|string|unique:users,username,' . $id,
                'name' => 'required|string'
            ]);

            if ($validator->fails()) {
                return ResponseFormatter::error($validator->errors(), 'Validation Error', 422);
            }


            $getDetailUser = User::where("id", $id)->first();

            // update username dan nama user


            $getDetailUser->update([
                'name' => $request->input('name'),
                'username' => $request->input('username'),
            ]);


            DB::commit();


            return ResponseFormatter::success(UserResource::make($getDetailUser));
        } catch (\Throwable $e) {
            DB::rollBack();
            return ResponseFormatter::error($e->getMessage(), "Internal Server", 500);
        }
    }

    public function resetPassword($id)
    {
        try {
            DB::beginTransaction();

            $getDetailUser = User::where("id", $id)->first();

            // update password dan waktu reset

            $getDetailUser->update([
                'password_reset_at' => Carbon::now(),
                'password' => Hash::make('password')
            ]);


            DB::commit();


            return ResponseFormatter::success(UserResource::make($getDetailUser));
        } catch (\Throwable $e) {
            DB::rollBack();
            return ResponseFormatter::error($e->getMessage(), "Internal Server", 500);
        }
    }

    public function updateUserStatus(Request $request, $id)
    {
        try {
            DB::beginTransaction();

            $validator = Validator::make($request->all(), [
                'status' => 'required|string|in:Active,Inactive',
            ]);

            if ($validator->fails()) {
                return ResponseFormatter::error($validator->errors(), 'Validation Error', 422);
            }


            $getDetailUser = User::where("id", $id)->first();

            $getDetailUser->update([
                'user_status' => $request->input('status')
            ]);
            if ($getDetailUser->user_status == 'Inactive') {
                $getDetailUser->tokens()->delete(); // Menghapus semua token yang terkait dengan user yang sedang login

            }


            DB::commit();


            return ResponseFormatter::success(UserResource::make($getDetailUser));
        } catch (\Throwable $e) {
            DB::rollBack();
            return ResponseFormatter::error($e->getMessage(), "Internal Server", 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Request $request)
    {
        try {
            $user = $request->user(); // Mengambil user yang sedang login
            $user->tokens()->delete(); // Menghapus semua token yang terkait dengan user yang sedang login

            return ResponseFormatter::success($user, "Successfully logout");
        } catch (\Throwable $e) {
            return ResponseFormatter::error($e->getMessage(), "Internal Server", 500);
        }
    }

    public function deleteUsers($id)
    {
        try {
            $user = User::find($id);

            if (!$user) {
                return ResponseFormatter::error(null, "User not found", 404);
            }

            $user->delete();

            return ResponseFormatter::success(null, "User deleted successfully");
        } catch (\Throwable $e) {
            return ResponseFormatter::error($e->getMessage(), "Internal Server Error", 500);
        }
    }

    public function changePassword(Request $request, $id)
    {
        try {
            // Validasi input request
            $validated = $request->validate([
                'current_password' => 'required|string',
                'new_password' => 'required|string|min:8|confirmed', // pastikan password baru sesuai dengan konfirmasi
            ]);

            // Ambil user berdasarkan id
            $user = User::find($id);

            if (!$user) {
                // Jika user tidak ditemukan
                return ResponseFormatter::error('User not found', 'Bad Request!', 404);
            }

            // Cek apakah current password sesuai dengan yang ada di database
            if (!Hash::check($validated['current_password'], $user->password)) {
                return ResponseFormatter::error('Current Password Incorrect', 'Bad Request!', 400);
            }

            // Update password user
            $user->password = Hash::make($validated['new_password']);
            $user->password_reset_at = null;
            $user->save();

            return ResponseFormatter::success($user);
        } catch (\Throwable $e) {
            return ResponseFormatter::error($e->getMessage(), "Internal Server Error", 500);
        }
    }
}
