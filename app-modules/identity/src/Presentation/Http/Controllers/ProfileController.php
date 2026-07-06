<?php

declare(strict_types=1);

namespace Modules\Identity\Presentation\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;
use Modules\Identity\Application\Actions\DeleteAccount;
use Modules\Identity\Application\Actions\UpdateProfile;
use Modules\Identity\Infrastructure\Models\User;
use Modules\Identity\Presentation\Http\Requests\ProfileUpdateRequest;
use Modules\Shared\Support\Currencies;

class ProfileController extends Controller
{
    public function edit(Request $request): Response
    {
        return Inertia::render('settings/profile', [
            'currencies' => Currencies::options(),
            'status' => $request->session()->get('status'),
        ]);
    }

    public function update(ProfileUpdateRequest $request, UpdateProfile $updateProfile): RedirectResponse
    {
        /** @var User $user */
        $user = $request->user();

        $updateProfile->handle($user, $request->validated());

        return to_route('profile.edit')->with('success', __('ui.saved'));
    }

    public function destroy(Request $request, DeleteAccount $deleteAccount): RedirectResponse
    {
        $request->validate([
            'password' => ['required', 'current_password'],
        ]);

        /** @var User $user */
        $user = $request->user();

        Auth::logout();

        $deleteAccount->handle($user);

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/sampai-jumpa');
    }
}
