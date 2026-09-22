<?php

namespace App\Http\Controllers\Accounting;

use App\Http\Controllers\Controller;
use App\Models\MsAccountMappingType;
use App\Models\TransJournalDT;
use App\Models\TransJournalHD;

/**
 * Read-only journal lookup backing the "View Journal" button (accounting.journal._modal)
 * available on this app's own transaction show pages. Mirrored (dataByTransactionNo only -
 * kawaguci-wms has no journal list/detail pages of its own) from kawaguci-nla's
 * app/Http/Controllers/Accounting/JournalController.php since the two apps share the same
 * database but cannot share PHP classes.
 */
class JournalController extends Controller
{
    public function dataByTransactionNo(string $transactionNo)
    {
        $journal = TransJournalHD::where('TransactionNo', $transactionNo)->first();

        if (!$journal) {
            return response()->json(['found' => false]);
        }

        $details = TransJournalDT::with('account')
            ->where('TransactionNo', $journal->TransactionNo)
            ->orderByRaw("CASE WHEN Notes = 'COGS' THEN 1 ELSE 0 END")
            ->orderBy('Debit', 'desc')
            ->get();

        $taxAccountNumbers = self::taxAccountNumbers();

        return response()->json([
            'found' => true,
            'transactionNo' => $journal->TransactionNo,
            'date' => date('d/m/Y', strtotime($journal->TransactionDate)),
            'type' => $journal->TransactionType,
            'details' => $details->map(function ($detail) use ($taxAccountNumbers) {
                [$currency, $rate, $originalAmount] = self::resolveDisplayAmount($detail);
                $isTax = in_array($detail->AccountNo, $taxAccountNumbers, true);

                return [
                    'account' => trim($detail->AccountNo . ' - ' . optional($detail->account)->AccountName),
                    'division' => $detail->DivisionID,
                    'currency' => $currency,
                    'rate' => self::formatJournalAmount($rate, $isTax),
                    'original_amount' => self::formatJournalAmount($originalAmount, $isTax),
                    'debit' => self::formatJournalAmount((float) ($detail->Debit ?? 0), $isTax),
                    'credit' => self::formatJournalAmount((float) ($detail->Credit ?? 0), $isTax),
                    'notes' => $detail->Notes,
                ];
            }),
            'totalDebit' => number_format($details->sum('Debit'), 2, '.', ','),
            'totalCredit' => number_format($details->sum('Credit'), 2, '.', ','),
        ]);
    }

    /**
     * The Currency ID shown should always be the account's own COA currency, not whatever
     * currency the transaction happened to be booked in. When those two differ, Debit/Credit
     * is already the fully-converted amount in the account's currency, so Original Amount and
     * Rate are normalized to match (Rate 1, Original Amount = Debit/Credit) instead of showing
     * a Rate/Original Amount pair that belongs to a different currency than the one displayed.
     */
    private static function resolveDisplayAmount($detail): array
    {
        $accountCurrency = optional($detail->account)->CurrencyID;
        $storedCurrency = $detail->CurrencyID;

        if ($accountCurrency && $accountCurrency !== $storedCurrency) {
            return [$accountCurrency, 1.0, (float) ($detail->Debit > 0 ? $detail->Debit : $detail->Credit)];
        }

        return [$storedCurrency, (float) $detail->Rate, (float) $detail->OriginalAmount];
    }

    /**
     * Accounts mapped to one of these types are tax accounts (VAT in/out, tax payable,
     * prepaid tax) - their journal amounts are shown as whole numbers (standard rounding),
     * everything else is shown with 2 decimal places.
     */
    private static function taxAccountNumbers(): array
    {
        return MsAccountMappingType::whereIn('AccountType', [
            'VALUE_ADDED_TAX_IN_ACCOUNT',
            'VALUE_ADDED_TAX_OUT_ACCOUNT',
            'TAX_PAYABLE_ACCOUNT',
            'PREPAID_TAX_ACCOUNT',
        ])->pluck('AccountNo')->all();
    }

    private static function formatJournalAmount(float $value, bool $isTax): string
    {
        if ($isTax) {
            return number_format(round($value), 0, '.', ',');
        }

        return number_format($value, 2, '.', ',');
    }
}
