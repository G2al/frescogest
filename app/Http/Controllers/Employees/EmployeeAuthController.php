<?php

namespace App\Http\Controllers\Employees;

use App\Http\Controllers\Controller;
use App\Http\Requests\Employees\EmployeeLoginRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class EmployeeAuthController extends Controller
{
    public function create(): View|RedirectResponse
    {
        if (Auth::guard('employee')->check()) {
            return redirect()->route('employee.attendance');
        }

        return view('employees.auth.login');
    }

    public function store(EmployeeLoginRequest $request): RedirectResponse
    {
        $credentials = [
            'email' => mb_strtolower(trim($request->string('email')->toString())),
            'password' => $request->string('password')->toString(),
        ];

        if (! Auth::guard('employee')->attempt($credentials, $request->boolean('remember'))) {
            return back()
                ->withInput($request->only('email'))
                ->withErrors(['email' => 'Email o password non corretti.']);
        }

        $request->session()->regenerate();
        $user = Auth::guard('employee')->user();

        if (! $user->active || ! $user->employee?->active) {
            Auth::guard('employee')->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return back()
                ->withInput($request->only('email'))
                ->withErrors(['email' => 'Questo account dipendente non è attivo.']);
        }

        return redirect()->intended(route('employee.attendance'));
    }

    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('employee')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('employee.login');
    }
}
