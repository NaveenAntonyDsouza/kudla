<?php

namespace App\Services;

use App\Models\Interest;
use App\Models\Profile;
use App\Models\Subscription;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\StreamedResponse;

class CsvExportService
{
    public static function exportUsers(): StreamedResponse
    {
        $profiles = Profile::with(['user', 'religiousInfo', 'educationDetail', 'locationInfo'])
            ->whereNotNull('full_name')
            ->orderBy('created_at', 'desc')
            ->get();

        return self::streamCsv('users_export.csv', [
            'Matri ID', 'Full Name', 'Gender', 'DOB', 'Age', 'Religion', 'Denomination/Caste',
            'Education', 'Occupation', 'State', 'Country', 'Email', 'Phone',
            'Profile Completion %', 'Approved', 'Active', 'Created By', 'Registered At',
        ], $profiles->map(fn (Profile $p) => [
            $p->matri_id,
            $p->full_name,
            $p->gender,
            $p->date_of_birth?->format('d/m/Y'),
            $p->date_of_birth ? $p->date_of_birth->age : '',
            $p->religiousInfo?->religion,
            $p->religiousInfo?->denomination ?: $p->religiousInfo?->caste,
            $p->educationDetail?->highest_education,
            $p->educationDetail?->occupation,
            $p->locationInfo?->native_state,
            $p->locationInfo?->native_country,
            $p->user?->email,
            $p->user?->phone,
            $p->profile_completion_pct,
            $p->is_approved ? 'Yes' : 'No',
            $p->is_active ? 'Yes' : 'No',
            $p->created_by,
            $p->created_at?->format('d/m/Y'),
        ])->toArray());
    }

    public static function exportInterests(): StreamedResponse
    {
        $interests = Interest::with(['senderProfile', 'receiverProfile'])
            ->orderBy('created_at', 'desc')
            ->get();

        return self::streamCsv('interests_export.csv', [
            'ID', 'Sender Matri ID', 'Sender Name', 'Receiver Matri ID', 'Receiver Name',
            'Status', 'Message', 'Sent At',
        ], $interests->map(fn (Interest $i) => [
            $i->id,
            $i->senderProfile?->matri_id,
            $i->senderProfile?->full_name,
            $i->receiverProfile?->matri_id,
            $i->receiverProfile?->full_name,
            $i->status,
            $i->custom_message ?: '(template)',
            $i->created_at?->format('d/m/Y H:i'),
        ])->toArray());
    }

    public static function exportPayments(): StreamedResponse
    {
        $subscriptions = Subscription::with('user')
            ->orderBy('created_at', 'desc')
            ->get();

        return self::streamCsv('payments_export.csv', [
            'ID', 'User', 'Email', 'Plan', 'Amount (INR)', 'Status',
            'Razorpay Payment ID', 'Starts', 'Expires', 'Payment Date',
        ], $subscriptions->map(fn (Subscription $s) => [
            $s->id,
            $s->user?->name,
            $s->user?->email,
            $s->plan_name,
            number_format($s->amount / 100, 2),
            $s->payment_status,
            $s->razorpay_payment_id,
            $s->starts_at?->format('d/m/Y'),
            $s->expires_at?->format('d/m/Y'),
            $s->created_at?->format('d/m/Y H:i'),
        ])->toArray());
    }

    private static function streamCsv(string $filename, array $headers, array $rows): StreamedResponse
    {
        return response()->streamDownload(function () use ($headers, $rows) {
            $handle = fopen('php://output', 'w');
            // BOM for Excel UTF-8 compatibility
            fwrite($handle, "\xEF\xBB\xBF");
            fputcsv($handle, $headers);
            foreach ($rows as $row) {
                fputcsv($handle, $row);
            }
            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }
}
