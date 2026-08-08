<?php

namespace App\Http\Controllers;

use App\Domain\Transactions\ReceiptService;
use App\Models\Group;
use App\Models\TransactionReceipt;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReceiptController extends Controller
{
    /**
     * Receipts are stored on a private disk and only ever leave through here,
     * after the group and the viewer's right to see them have been checked.
     */
    public function show(Group $group, TransactionReceipt $receipt, ReceiptService $receipts): StreamedResponse
    {
        $this->authorize('viewReceipt', $receipt->transaction);

        abort_unless($receipts->exists($receipt), 404);

        return $receipts->download($receipt);
    }
}
