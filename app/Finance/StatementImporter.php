<?php

namespace App\Finance;

use App\Models\ImportProfile;
use App\Models\StatementImport;
use App\Models\Transaction;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use RuntimeException;
use Throwable;

/**
 * Applies a profile to a statement file and stores what is new.
 *
 * Re-importing the same month is expected to be a no-op, and a month that
 * overlaps the previous one by a few days must insert only the days it adds.
 * That is the whole difficulty; the rest is parsing.
 */
class StatementImporter
{
    public function __construct(private readonly StatementReader $reader) {}

    public function import(string $path, ImportProfile $profile, StatementImport $record): StatementImport
    {
        $record->update(['status' => 'processing']);

        try {
            $rows = $this->reader->rows($path, $profile);
        } catch (Throwable $e) {
            $record->update(['status' => 'failed', 'error' => $e->getMessage()]);

            return $record;
        }

        $parsed = [];
        $failed = 0;

        foreach ($rows as $row) {
            try {
                $parsed[] = $this->parse($row, $profile);
            } catch (Throwable) {
                // A footer line, a subtotal, a row the bank left half-empty.
                // Counted and reported rather than aborting the whole file.
                $failed++;
            }
        }

        [$imported, $duplicate] = $this->store($parsed, $profile, $record);

        $dates = array_column($parsed, 'booked_on');
        sort($dates);

        $record->update([
            'status' => 'completed',
            'rows_total' => count($rows),
            'rows_imported' => $imported,
            'rows_duplicate' => $duplicate,
            'rows_failed' => $failed,
            'period_start' => $dates[0] ?? null,
            'period_end' => end($dates) ?: null,
        ]);

        return $record;
    }

    /**
     * One source row into the fields of a transaction.
     *
     * @param  array<string, string>  $row
     * @return array<string, mixed>
     */
    private function parse(array $row, ImportProfile $profile): array
    {
        $bookedOn = $this->date($this->value($row, $profile, 'booked_on'), $profile);

        if ($bookedOn === null) {
            throw new RuntimeException('Riga senza data valida.');
        }

        $amount = $this->amount($row, $profile);

        if ($amount === null) {
            throw new RuntimeException('Riga senza importo valido.');
        }

        $description = $this->value($row, $profile, 'description') ?? '';

        return [
            'booked_on' => $bookedOn,
            'valued_on' => $this->date($this->value($row, $profile, 'valued_on'), $profile),
            'amount' => $amount,
            'raw_description' => $description,
            'description' => $this->tidy($description),
            // Truncated on the way in: a bank is free to put a whole payment
            // reference in the field a profile maps here, and a column-length
            // error aborts the entire file rather than one row.
            'counterparty' => Str::limit((string) $this->value($row, $profile, 'counterparty'), 255, '') ?: null,
            'notes' => $this->value($row, $profile, 'notes'),
        ];
    }

    private function value(array $row, ImportProfile $profile, string $field): ?string
    {
        $raw = $this->rawValue($row, $profile, $field);

        if ($raw === null) {
            return null;
        }

        $value = trim((string) $raw);

        return $value === '' ? null : $value;
    }

    /** The cell as the reader gave it — a float from a spreadsheet stays a float. */
    private function rawValue(array $row, ImportProfile $profile, string $field): string|float|int|null
    {
        $column = $profile->column($field);

        if ($column === null) {
            return null;
        }

        return $row[$column] ?? null;
    }

    /**
     * Dates are parsed with the format from the profile and then checked by
     * formatting them back.
     *
     * Carbon will happily read "19/08/2026" as an American date and hand back
     * something plausible. The round-trip is what catches it: a date that does
     * not re-print identically was not the date it looked like.
     */
    private function date(?string $value, ImportProfile $profile): ?string
    {
        if ($value === null) {
            return null;
        }

        // Spreadsheets hand back dates already normalised by the reader.
        if (preg_match('/^\d{4}-\d{2}-\d{2}/', $value) === 1) {
            return substr($value, 0, 10);
        }

        try {
            $date = CarbonImmutable::createFromFormat($profile->date_format, $value);
        } catch (Throwable) {
            return null;
        }

        if ($date === false || $date->format($profile->date_format) !== $value) {
            return null;
        }

        return $date->format('Y-m-d');
    }

    /**
     * The amount, always signed the same way afterwards: negative is money out.
     *
     * Three shapes reach this method — a signed column, a column where a plus
     * means a purchase (credit cards do this), and separate debit and credit
     * columns. Which one it is comes from the profile, never from inspecting
     * the values: a month of only expenses looks identical under two of them.
     */
    private function amount(array $row, ImportProfile $profile): ?string
    {
        if ($profile->amount_mode === 'split') {
            $debit = $this->number($this->rawValue($row, $profile, 'debit'), $profile);
            $credit = $this->number($this->rawValue($row, $profile, 'credit'), $profile);

            if ($debit !== null && $debit != 0) {
                // A debit column holds a positive number for money leaving.
                return number_format(-abs((float) $debit), 2, '.', '');
            }

            if ($credit !== null && $credit != 0) {
                return number_format(abs((float) $credit), 2, '.', '');
            }

            return null;
        }

        $amount = $this->number($this->rawValue($row, $profile, 'amount'), $profile);

        if ($amount === null) {
            return null;
        }

        $value = (float) $amount;

        if ($profile->amount_mode === 'inverted') {
            $value = -$value;
        }

        return number_format($value, 2, '.', '');
    }

    private function number(string|float|int|null $value, ImportProfile $profile): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        // Already a number: the profile's separators describe how this bank
        // WRITES numbers as text, and applying them here would corrupt a value
        // that never was text.
        if (is_int($value) || is_float($value)) {
            return number_format((float) $value, 2, '.', '');
        }

        $clean = str_replace(['€', ' ', "\u{A0}"], '', $value);

        if ($profile->thousands_separator !== null && $profile->thousands_separator !== '') {
            $clean = str_replace($profile->thousands_separator, '', $clean);
        }

        if ($profile->decimal_separator !== '.') {
            $clean = str_replace($profile->decimal_separator, '.', $clean);
        }

        // Some banks write the minus after the number, or use a unicode dash.
        $clean = str_replace(['−', '–'], '-', $clean);

        if (str_ends_with($clean, '-')) {
            $clean = '-'.substr($clean, 0, -1);
        }

        return is_numeric($clean) ? $clean : null;
    }

    /** The bank's shouting, made readable. The original is kept untouched. */
    private function tidy(string $description): string
    {
        return Str::of($description)
            ->replaceMatches('/\s+/', ' ')
            ->replaceMatches('/^(operazione presso|pagamento pos|pagamento tramite pos)\s+/i', '')
            ->trim()
            ->limit(255, '')
            ->value();
    }

    /**
     * Insert what is not already there.
     *
     * Rows are grouped by fingerprint: for each group, however many are already
     * stored for this account are treated as already imported, and only the
     * surplus is written. Two identical coffees on the same day stay two
     * transactions, and importing the same file twice adds nothing.
     *
     * @param  array<int, array<string, mixed>>  $parsed
     * @return array{0: int, 1: int}
     */
    private function store(array $parsed, ImportProfile $profile, StatementImport $record): array
    {
        $accountId = (int) $profile->account_id;
        $groups = [];

        foreach ($parsed as $item) {
            $fingerprint = Transaction::fingerprintFor(
                $accountId,
                $item['booked_on'],
                $item['amount'],
                $item['raw_description'],
            );

            $groups[$fingerprint][] = $item;
        }

        $imported = 0;
        $duplicate = 0;

        DB::transaction(function () use ($groups, $accountId, $record, &$imported, &$duplicate): void {
            foreach ($groups as $fingerprint => $items) {
                $already = Transaction::query()
                    ->where('account_id', $accountId)
                    ->where('fingerprint', $fingerprint)
                    ->count();

                foreach (array_values($items) as $position => $item) {
                    if ($position < $already) {
                        $duplicate++;

                        continue;
                    }

                    Transaction::create([
                        ...$item,
                        'account_id' => $accountId,
                        'statement_import_id' => $record->id,
                        'fingerprint' => $fingerprint,
                        'occurrence' => $position + 1,
                    ]);

                    $imported++;
                }
            }
        });

        return [$imported, $duplicate];
    }
}
