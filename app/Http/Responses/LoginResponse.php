<?php

namespace App\Http\Responses;

use Filament\Http\Responses\Auth\LoginResponse as BaseLoginResponse;
use Illuminate\Http\RedirectResponse;
use Livewire\Features\SupportRedirects\Redirector;
use Illuminate\Support\Facades\Auth;

class LoginResponse extends BaseLoginResponse
{
    public function toResponse($request): RedirectResponse|Redirector
    {
        // Ambil user yang sedang login menggunakan Facade
        $user = Auth::user();

        // Cek: Apakah user ada? DAN Apakah role-nya customer?
        if ($user && $user->role === 'customer') {
            // Jika Customer, lempar ke HOME
            return redirect()->to('/');
        }

        // Jika Admin (atau lainnya), biarkan perilaku default (ke Dashboard)
        return parent::toResponse($request);
    }
}
