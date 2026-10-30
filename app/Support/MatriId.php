<?php

namespace App\Support;

use App\Models\SiteSetting;

/**
 * Matri IDs typed by people (login, Search by ID, the app, admin searches).
 *
 * Crasta and Global Catholic Match were renamed from the template's "MT" to
 * CR / GC on 30 Oct 2026 (same digits: MT100010 → CR100010). Members still
 * have the old ID on cards, emails and WhatsApp, so an ID typed with the
 * site's previous prefix (SiteSetting `matri_id_previous_prefix`) finds the
 * same member under today's prefix.
 */
class MatriId
{
    public static function current(string $id): string
    {
        $id = strtoupper(trim($id));

        try {
            $old = strtoupper(trim((string) SiteSetting::getValue('matri_id_previous_prefix', '')));
            $new = strtoupper(trim((string) SiteSetting::getValue('profile_id_prefix', 'AM')));
        } catch (\Throwable) {
            return $id;
        }

        if ($old !== '' && $old !== $new && preg_match('/^' . preg_quote($old, '/') . '(\d+)$/', $id, $m)) {
            return $new . $m[1];
        }

        return $id;
    }
}
