<?php

namespace ME\SflInventory\Approvals\Concerns;

use App\Models\User;

/**
 * Shared recipients() lookup for every ME\SflInventory\Approvals\*Handler —
 * see src/Config/mail.php for the config shape and the override rule.
 */
trait ResolvesConfiguredRecipients
{
    protected function resolveRecipients(string $module, string $approvePermission): array
    {
        $configured = array_values(array_filter(
            (array) config("sfl-inventory-mail.approval_recipients.{$module}", [])
        ));

        if (! empty($configured)) {
            return $configured;
        }

        return User::all()
            ->filter(fn (User $user) => $user->hasPermission($approvePermission))
            ->pluck('email')
            ->filter()
            ->values()
            ->all();
    }
}
