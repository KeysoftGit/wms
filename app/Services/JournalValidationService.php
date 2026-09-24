<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;

class JournalValidationService
{
    public static function validateNoSqlErrors(string $context, string $sql, array $bindings): void
    {
        $errors = DB::connection('sqlsrv')->select($sql, $bindings);
        $messages = [];

        foreach ($errors as $error) {
            $message = trim((string) ($error->Message ?? ''));
            if ($message !== '') {
                $messages[] = $message;
            }
        }

        $messages = array_values(array_unique($messages));

        if (count($messages) > 0) {
            throw new \InvalidArgumentException($context . ': ' . implode(' ', $messages));
        }
    }

    public static function validateHeader(string $transactionNo, string $table, string $context, array $fields = []): void
    {
        $selects = ["case when h.TransactionNo is null then '{$context} transaction was not found.' end"];

        foreach ($fields as $field => $label) {
            $selects[] = "case when h.TransactionNo is not null and (h.{$field} is null or ltrim(rtrim(cast(h.{$field} as nvarchar(max)))) = '') then '{$label} is required.' end";
        }

        self::validateNoSqlErrors(
            "Cannot rebuild journal for {$transactionNo}",
            "select v.Message from (select cast(? as nvarchar(50)) as TransactionNo) x left join {$table} h on h.TransactionNo = x.TransactionNo cross apply (values (" . implode('), (', $selects) . ")) v(Message) where v.Message is not null",
            [$transactionNo]
        );
    }

    /**
     * Guards every journal rebuild against silently posting an unbalanced journal (e.g. an
     * account mapping join that drops rows and leaves debit with no offsetting credit). Called
     * as the last step of each *JournalService::rebuild*() after all Trans_JournalDT rows for
     * the transaction have been inserted.
     */
    public static function validateBalanced(string $transactionNo, string $context): void
    {
        $totals = DB::connection('sqlsrv')
            ->table('Trans_JournalDT')
            ->where('TransactionNo', $transactionNo)
            ->selectRaw('sum(Debit) as TotalDebit, sum(Credit) as TotalCredit')
            ->first();

        $debit = round((float) ($totals->TotalDebit ?? 0), 2);
        $credit = round((float) ($totals->TotalCredit ?? 0), 2);

        if (abs($debit - $credit) > 0.01) {
            throw new \InvalidArgumentException(
                "Cannot rebuild journal for {$transactionNo}: {$context} journal is not balanced (Debit " . number_format($debit, 2) . " vs Credit " . number_format($credit, 2) . ")."
            );
        }
    }
}
