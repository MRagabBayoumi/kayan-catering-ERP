<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class UserController extends Controller
{
    /**
     * Display a listing of system users.
     */
    public function index(): View
    {
        $users = User::with('assignedWarehouse')
            ->orderBy('id')
            ->paginate(15);

        return view('users.index', [
            'title' => 'إدارة المستخدمين وحسابات الدخول',
            'moduleName' => 'الإدارة والنظام',
            'users' => $users,
        ]);
    }

    /**
     * Show the form for creating a new user.
     */
    public function create(): View
    {
        $roles = User::rolesList();
        $warehouses = Warehouse::where('is_active', true)->orderBy('name')->get();

        return view('users.create', [
            'title' => 'إضافة مستخدم جديد',
            'moduleName' => 'الإدارة والنظام',
            'roles' => $roles,
            'warehouses' => $warehouses,
        ]);
    }

    /**
     * Store a newly created user in storage.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'username' => ['nullable', 'string', 'max:50', 'unique:users,username'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:6'],
            'role' => ['required', 'string', Rule::in(array_keys(User::rolesList()))],
            'assigned_warehouse_id' => ['nullable', 'exists:warehouses,id'],
            'phone' => ['nullable', 'string', 'max:35'],
            'is_active' => ['sometimes', 'boolean'],
        ], [
            'name.required' => 'اسم المستخدم مطلوب.',
            'email.required' => 'البريد الإلكتروني مطلوب.',
            'email.email' => 'صيغة البريد الإلكتروني غير صالحة.',
            'email.unique' => 'البريد الإلكتروني مسجل بالفعل لمستخدم آخر.',
            'password.required' => 'كلمة المرور مطلوبة.',
            'password.min' => 'كلمة المرور يجب ألا تقل عن 6 أحرف.',
            'role.required' => 'يرجى اختيار دور المستخدم.',
            'role.in' => 'الدور المختار غير صالح.',
        ]);

        $username = filled($validated['username'] ?? null)
            ? Str::slug($validated['username'], '_')
            : Str::slug(explode('@', $validated['email'])[0], '_');

        if (empty($username)) {
            $username = 'user_'.time();
        }

        $candidate = $username;
        $counter = 1;
        while (User::where('username', $candidate)->exists()) {
            $candidate = $username.$counter++;
        }

        User::create([
            'name' => $validated['name'],
            'username' => $candidate,
            'email' => $validated['email'],
            'phone' => filled($validated['phone'] ?? null) ? trim($validated['phone']) : null,
            'password' => Hash::make($validated['password']),
            'role' => $validated['role'],
            'assigned_warehouse_id' => $validated['assigned_warehouse_id'] ?? null,
            'is_active' => $request->boolean('is_active', true),
        ]);

        return redirect()->route('users.index')
            ->with('status', 'success')
            ->with('message', 'تم إنشاء حساب المستخدم بنجاح.');
    }

    /**
     * Show the form for editing the specified user.
     */
    public function edit(User $user): View
    {
        $roles = User::rolesList();
        $warehouses = Warehouse::where('is_active', true)->orderBy('name')->get();

        return view('users.edit', [
            'title' => 'تعديل بيانات المستخدم: '.$user->name,
            'moduleName' => 'الإدارة والنظام',
            'user' => $user,
            'roles' => $roles,
            'warehouses' => $warehouses,
        ]);
    }

    /**
     * Update the specified user in storage.
     */
    public function update(Request $request, User $user): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'username' => ['nullable', 'string', 'max:50', Rule::unique('users', 'username')->ignore($user->id)],
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->id)],
            'password' => ['nullable', 'string', 'min:6'],
            'role' => ['required', 'string', Rule::in(array_keys(User::rolesList()))],
            'assigned_warehouse_id' => ['nullable', 'exists:warehouses,id'],
            'phone' => ['nullable', 'string', 'max:35'],
            'is_active' => ['sometimes', 'boolean'],
        ]);

        $updateData = [
            'name' => $validated['name'],
            'email' => $validated['email'],
            'phone' => filled($validated['phone'] ?? null) ? trim($validated['phone']) : null,
            'role' => $validated['role'],
            'assigned_warehouse_id' => $validated['assigned_warehouse_id'] ?? null,
            'is_active' => $request->boolean('is_active', true),
        ];

        if (filled($validated['username'] ?? null)) {
            $updateData['username'] = Str::slug($validated['username'], '_');
        }

        if (filled($validated['password'] ?? null)) {
            $updateData['password'] = Hash::make($validated['password']);
        }

        $user->update($updateData);

        return redirect()->route('users.index')
            ->with('status', 'success')
            ->with('message', 'تم تحديث بيانات المستخدم بنجاح.');
    }

    /**
     * Remove the specified user from storage.
     */
    public function destroy(User $user): RedirectResponse
    {
        if ($user->id === 1 || $user->id === auth()->id()) {
            return redirect()->route('users.index')
                ->with('status', 'error')
                ->with('message', 'لا يمكن حذف الحساب الإداري الرئيسي أو الحساب الحالي.');
        }

        $user->delete();

        return redirect()->route('users.index')
            ->with('status', 'success')
            ->with('message', 'تم حذف المستخدم بنجاح.');
    }
}
