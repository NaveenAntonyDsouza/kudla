<?php

namespace App\Support\Bounces;

use RuntimeException;

/**
 * Minimal read-only IMAP client for the site mailbox (no ext-imap needed,
 * which PHP 8.4 drops). Only what bounce processing uses: log in, open
 * INBOX, search, and fetch messages with BODY.PEEK so nothing is marked
 * read, moved or deleted.
 */
class ImapMailbox implements BounceSource
{
    /** @var resource|null */
    private $stream = null;

    private int $tag = 0;

    public function __construct(
        private readonly string $host,
        private readonly string $username,
        private readonly string $password,
        private readonly int $port = 993,
        private readonly int $timeout = 20,
    ) {}

    public function bounces(\DateTimeInterface $since): iterable
    {
        $this->open();
        try {
            $date = $since->format('j-M-Y');
            $uids = $this->search("SINCE {$date} OR FROM \"MAILER-DAEMON\" FROM \"postmaster\"");
            foreach ($uids as $uid) {
                [$received, $raw] = $this->fetch($uid);
                yield ['uid' => $uid, 'received' => $received, 'raw' => $raw];
            }
        } finally {
            $this->close();
        }
    }

    private function open(): void
    {
        $stream = @stream_socket_client("ssl://{$this->host}:{$this->port}", $errno, $error, $this->timeout);
        if (! $stream) {
            throw new RuntimeException("Cannot reach {$this->host}:{$this->port} ({$error})");
        }
        stream_set_timeout($stream, $this->timeout);
        $this->stream = $stream;
        $this->readLine(); // greeting

        $this->command('LOGIN '.$this->quote($this->username).' '.$this->quote($this->password), 'login');
        $this->command('EXAMINE INBOX'); // read-only open
    }

    private function close(): void
    {
        if ($this->stream) {
            try {
                $this->command('LOGOUT');
            } catch (\Throwable) {
            }
            fclose($this->stream);
            $this->stream = null;
        }
    }

    /** @return int[] */
    private function search(string $criteria): array
    {
        $uids = [];
        foreach ($this->command("UID SEARCH {$criteria}") as $line) {
            if (str_starts_with($line, '* SEARCH')) {
                $uids = array_map('intval', array_filter(explode(' ', trim(substr($line, 8)))));
            }
        }

        return $uids;
    }

    /** @return array{0: ?string, 1: string} INTERNALDATE and the raw message */
    private function fetch(int $uid): array
    {
        $raw = '';
        $received = null;
        foreach ($this->command("UID FETCH {$uid} (INTERNALDATE BODY.PEEK[])", literals: true) as $part) {
            if (is_array($part)) {
                $raw = $part['literal'];
            } elseif (preg_match('/INTERNALDATE "([^"]+)"/', $part, $m)) {
                $received = $m[1];
            }
        }

        return [$received, $raw];
    }

    /**
     * Send a tagged command and collect the untagged response lines (and,
     * with $literals, {n} literal blocks as ['literal' => ...]).
     */
    private function command(string $command, string $what = 'command', bool $literals = false): array
    {
        $tag = 'A'.(++$this->tag);
        fwrite($this->stream, "{$tag} {$command}\r\n");

        $out = [];
        while (true) {
            $line = $this->readLine();
            if (str_starts_with($line, "{$tag} ")) {
                if (! preg_match('/^'.$tag.' OK/i', $line)) {
                    // Never echo the command: LOGIN carries the password
                    throw new RuntimeException("IMAP {$what} failed: ".substr($line, strlen($tag) + 1, 200));
                }

                return $out;
            }
            if ($literals && preg_match('/\{(\d+)\}$/', rtrim($line), $m)) {
                $out[] = rtrim($line);
                $out[] = ['literal' => $this->readBytes((int) $m[1])];

                continue;
            }
            $out[] = rtrim($line, "\r\n");
        }
    }

    private function readLine(): string
    {
        $line = fgets($this->stream);
        if ($line === false) {
            throw new RuntimeException('IMAP connection closed or timed out');
        }

        return $line;
    }

    private function readBytes(int $length): string
    {
        $data = '';
        while (strlen($data) < $length) {
            $chunk = fread($this->stream, $length - strlen($data));
            if ($chunk === false || $chunk === '') {
                throw new RuntimeException('IMAP connection closed while reading a message');
            }
            $data .= $chunk;
        }

        return $data;
    }

    private function quote(string $value): string
    {
        return '"'.addcslashes($value, '"\\').'"';
    }
}
