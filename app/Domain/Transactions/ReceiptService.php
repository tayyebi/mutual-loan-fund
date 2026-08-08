<?php

namespace App\Domain\Transactions;

use App\Models\Transaction;
use App\Models\TransactionReceipt;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

/**
 * Evidence attached to a transaction — a bank slip, a screenshot.
 *
 * Receipts are stored on the private disk and are only ever served through an
 * authorised route, never by a public URL.
 */
class ReceiptService
{
    public const DISK = 'receipts';

    public function attach(Transaction $transaction, UploadedFile $file, User $actor): TransactionReceipt
    {
        // Group-scoped path, so a stray path traversal still cannot reach
        // another fund's evidence.
        $path = $file->store("g{$transaction->group_id}/t{$transaction->getKey()}", self::DISK);

        return TransactionReceipt::create([
            'group_id' => $transaction->group_id,
            'transaction_id' => $transaction->getKey(),
            'storage_path' => $path,
            'original_filename' => $file->getClientOriginalName(),
            'mime_type' => $file->getClientMimeType(),
            'size' => $file->getSize(),
            // Lets an auditor prove the stored file is the one that was uploaded.
            'sha256' => hash_file('sha256', $file->getRealPath()),
            'uploaded_by' => $actor->getKey(),
        ]);
    }

    public function exists(TransactionReceipt $receipt): bool
    {
        return Storage::disk(self::DISK)->exists($receipt->storage_path);
    }

    public function download(TransactionReceipt $receipt): \Symfony\Component\HttpFoundation\StreamedResponse
    {
        return Storage::disk(self::DISK)->download($receipt->storage_path, $receipt->original_filename);
    }
}
