<?php

namespace App\Http\Controllers\Api;
use Illuminate\Support\Facades\Validator;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;

class UserController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $users = User::all();
        $users->each(function($user) {
            $user->active_tasks_count = $user->countActiveTasks();
        });
    
        return response()->json($users);
    }

    /**
     * Tìm kiếm người dùng theo tên hoặc email.
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function search(Request $request)
    {
        $searchTerm = $request->input('search');

        $users = User::where('name', 'like', "%{$searchTerm}%")
                     ->orWhere('email', 'like', "%{$searchTerm}%")
                     ->take(20) 
                     ->get(['id', 'name']);

        return response()->json($users);
    }

    /**
     * Cập nhật thông tin người dùng.
     */
    // public function update(Request $request)
    // {
    //     $user = $request->user();

    //     if (!$user) {
    //         return response()->json(['message' => 'Unauthorized'], 401);
    //     }

    //     $data = $request->only(['name', 'google_id']);
        
    //     // Xác thực dữ liệu đầu vào
    //     $validator = Validator::make($data, [
    //         'name' => 'nullable|string|max:255',
    //         'google_id' => 'nullable|string|unique:users,google_id,' . $user->id,
    //     ]);

    //     if ($validator->fails()) {
    //         return response()->json(['errors' => $validator->errors()], 422);
    //     }

    //     $user->update($data);

    //     return response()->json([
    //         'message' => 'User profile updated successfully',
    //         'user' => $user
    //     ]);
    // }

    // Phương thức lấy tất cả tên các task mà user tham gia
    public function getAllTaskNameToUser(Request $request)
    {; 
        $user = $request->user();
        if (!$user) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }
        $taskNames = $user->getAllTaskNames(); 

        return response()->json([
            'user_id' => $user->id,
            'name' => $user->name,
            'tasks' => $taskNames
        ]);
    }
}
