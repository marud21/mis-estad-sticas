<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\ActualizarPasswordRequest;
use Illuminate\Support\Facades\Auth;

class PasswordController extends Controller
{
    public function edit()
    {
        return view('auth.password');
    }

    public function update(ActualizarPasswordRequest $request)
    {
        Auth::user()->update([
            'password' => $request->validated('password'),
        ]);

        return back()->with('status', 'Contrasena actualizada correctamente.');
    }
}
