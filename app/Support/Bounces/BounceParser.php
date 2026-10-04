<?php

namespace App\Support\Bounces;

use Illuminate\Support\Str;

/**
 * Reads a bounce notice (RFC 3464 delivery report, or the plain Postfix
 * "<addr>: host ... said: 550 ..." text) and says, per failed recipient,
 * whether the address is dead, the inbox is full, or something else
 * (spam block, server trouble) that says nothing about the address.
 */
final class BounceParser
{
    public const DEAD = 'dead';

    public const FULL = 'full';

    public const OTHER = 'other';

    /** @return list<array{recipient: string, status: ?string, diagnostic: string, kind: string}> */
    public static function parse(string $raw): array
    {
        $text = self::unfold(str_replace("\r\n", "\n", $raw));
        $found = [];

        // 1. Machine-readable delivery report: one block per recipient
        if (preg_match_all('/^Final-Recipient:\s*[^;\n]*;\s*<?([^\s>]+@[^\s>]+)>?\s*$/mi', $text, $m, PREG_OFFSET_CAPTURE)) {
            foreach ($m[1] as $i => [$address, $offset]) {
                $end = $m[0][$i + 1][1] ?? strlen($text);
                $block = substr($text, $offset, $end - $offset);
                $status = preg_match('/^Status:\s*([245]\.\d{1,3}\.\d{1,3})/mi', $block, $s) ? $s[1] : null;
                $diagnostic = preg_match('/^Diagnostic-Code:\s*(?:[a-z]+;\s*)?(.+)$/mi', $block, $d) ? trim($d[1]) : '';
                $found[Str::lower($address)] = self::result($address, $status, $diagnostic);
            }
        }

        // 2. Human-readable Postfix text: "<a@b.com>: host x said: 550 5.1.1 ..."
        if ($found === [] && preg_match_all('/^<([^\s>]+@[^\s>]+)>:\s*(.+?)(?=\n\n|\n<|\z)/ms', $text, $m, PREG_SET_ORDER)) {
            foreach ($m as [, $address, $why]) {
                $why = preg_replace('/\s+/', ' ', $why);
                $status = preg_match('/\b([245]\.\d{1,3}\.\d{1,3})\b/', $why, $s) ? $s[1] : null;
                $found[Str::lower($address)] = self::result($address, $status, $why);
            }
        }

        return array_values($found);
    }

    private static function result(string $address, ?string $status, string $diagnostic): array
    {
        return [
            'recipient' => Str::lower($address),
            'status' => $status,
            'diagnostic' => Str::limit(trim(preg_replace('/\s+/', ' ', $diagnostic)), 300, ''),
            'kind' => self::classify($status, $diagnostic),
        ];
    }

    public static function classify(?string $status, string $diagnostic): string
    {
        $text = Str::lower($diagnostic);

        if (in_array($status, ['4.2.2', '5.2.2'], true)
            || Str::contains($text, ['out of storage', 'over quota', 'overquota', 'mailbox full', 'mailbox is full', 'quota exceeded', 'insufficient storage'])) {
            return self::FULL;
        }

        // Blocks for spam/policy/reputation, or a problem with OUR address, say
        // nothing about whether the recipient's address exists
        if (($status !== null && str_starts_with($status, '5.7.')) || Str::contains($text, 'sender address')) {
            return self::OTHER;
        }

        $deadStatus = in_array($status, ['5.1.0', '5.1.1', '5.1.2', '5.1.3', '5.1.6', '5.1.10', '5.2.1'], true);
        $deadText = Str::contains($text, [
            'does not exist', 'no such user', 'user unknown', 'unknown user', 'no mailbox', 'mailbox not found',
            'mailbox unavailable', 'recipient not found', 'address rejected', 'account is disabled',
            'account has been disabled', 'account disabled', 'mailbox is disabled', 'mailbox has been disabled',
            'inactive', 'host or domain name not found',
            'domain not found', 'invalid recipient', 'not a valid', 'no such recipient',
        ]);

        if ($deadStatus || ($deadText && ($status === null || str_starts_with($status, '5.')))) {
            return self::DEAD;
        }

        return self::OTHER;
    }

    /** Joins folded header lines (continuations start with whitespace). */
    private static function unfold(string $text): string
    {
        return preg_replace('/\n[ \t]+/', ' ', $text);
    }
}
