<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\LoginRequest;
use Illuminate\Support\Facades\Auth;

class LoginController extends Controller
{
    public function create()
    {
        if (Auth::check()) {
            return redirect()->to(Auth::user()->pantallaDeInicio());
        }

        return view('auth.login');
    }

    public function store(LoginRequest $request)
    {
        // Antes de probar nada: si ya fallo demasiadas veces, no se le deja
        // seguir adivinando.
        $request->asegurarQueNoEstaBloqueado();

        $credentials = $request->validated();

        if (! Auth::attempt($credentials, $request->boolean('remember'))) {
            $request->registrarIntentoFallido();

            return back()->withErrors(['email' => 'Credenciales incorrectas.'])->onlyInput('email');
        }

        $request->limpiarIntentos();
        $request->session()->regenerate();

        return redirect()->intended(auth()->user()->pantallaDeInicio());
    }

    public function destroy()
    {
        Auth::logout();

        request()->session()->invalidate();
        request()->session()->regenerateToken();

        return redirect()->route('login');
    }
}
