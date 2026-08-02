<?php

namespace ME\SflInventory\Http\Controllers;

use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;

class InvSignatureController extends Controller
{
    /**
     * Self-service signature upload: every user manages only their own —
     * whoever approves/authorizes a document later gets this image printed
     * at that signature line (see requisitions/print and issues/print).
     *
     * Storage convention matches the host's own User Management "Upload
     * Signature" field exactly (app/Http/Controllers/Admin/AdminController.php):
     * stored under public/employees/signatures, with the 'storage/' prefix
     * baked into the saved path (not added again at render time) — so
     * signatures set from either place stay interchangeable.
     */
    public function edit(): View
    {
        $this->authorize('inv_signature.edit');

        return view('sfl-inventory::admin.signature.edit', ['user' => auth()->user()]);
    }

    public function update(Request $request): RedirectResponse
    {
        $this->authorize('inv_signature.edit');

        $request->validate([
            'signature' => ['required', 'image', 'mimes:jpeg,png,jpg,gif,svg,webp', 'max:2048'],
        ]);

        $user = auth()->user();

        if ($user->signature && File::exists($user->signature)) {
            File::delete($user->signature);
        }

        $signaturePath = $request->file('signature')->store('employees/signatures', 'public');
        $user->forceFill(['signature' => 'storage/' . $signaturePath])->save();

        return back()->with('success', 'Signature updated successfully.');
    }

    public function destroy(): RedirectResponse
    {
        $this->authorize('inv_signature.edit');

        $user = auth()->user();

        if ($user->signature && File::exists($user->signature)) {
            File::delete($user->signature);
            $user->forceFill(['signature' => null])->save();
        }

        return back()->with('success', 'Signature removed.');
    }
}
