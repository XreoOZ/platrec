<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;

class StaffController extends Controller
{
    public function index()
    {
        // Hanya ambil user dengan role staff
        $staffs = User::where('role', 'staff')->orderBy('created_at', 'desc')->get();
        return view('daftarstaff', compact('staffs'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => ['required', Password::defaults()],
        ]);

        User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'role' => 'staff',
        ]);

        return redirect()->route('staff.index')->with('success', 'Staff ' . $request->name . ' berhasil didaftarkan.');
    }

    public function update(Request $request, $id)
    {
        $staff = User::findOrFail($id);
        
        $rules = [
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users,email,'.$id
        ];

        if ($request->filled('password')) {
            $rules['password'] = ['required', Password::defaults()];
        }

        $request->validate($rules);

        $staff->name = $request->name;
        $staff->email = $request->email;
        // Keep role as staff implicitly or don't touch it
        
        if ($request->filled('password')) {
            $staff->password = Hash::make($request->password);
        }
        
        $staff->save();

        return redirect()->route('staff.index')->with('success', 'Data staff berhasil diperbarui.');
    }

    public function destroy($id)
    {
        $staff = User::findOrFail($id);
        $name = $staff->name;
        $staff->delete();

        return redirect()->route('staff.index')->with('success', 'Staff ' . $name . ' berhasil dihapus.');
    }
}
