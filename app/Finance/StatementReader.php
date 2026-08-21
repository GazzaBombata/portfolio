<?php

namespace App\Finance;

use App\Models\ImportProfile;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;
use RuntimeException;

/**
 * Turns a statement file into plain rows keyed by their column heading.
 *
 * Knows about file formats (CSV, XLS, XLSX) and nothing about banks. Where the
 * table starts and which sheet holds it come from the profile, because every
 * institution pads the top of its export with a different number of lines of
 * account holder, IBAN and balances.
 */
class StatementReader
{
    /**
     * @return array<int, array<string, string|float|int>>
     */
    public function rows(string $path, ImportProfile $profile): array
    {
        return match (strtolower(pathinfo($path, PATHINFO_EXTENSION))) {
            'csv', 'txt' => $this->fromCsv($path, $profile),
            'xlsx', 'xls' => $this->fromSpreadsheet($path, $profile),
            default => throw new RuntimeException(
                'Formato non supportato: '.pathinfo($path, PATHINFO_EXTENSION).'. Carica un CSV o un Excel.'
            ),
        };
    }

    /**
     * The headings found at the profile's header row — what the mapping screen
     * offers to choose from, and how a changed layout is noticed before it is
     * imported rather than after.
     *
     * @return array<int, string>
     */
    public function headings(string $path, ImportProfile $profile): array
    {
        $rows = $this->rows($path, $profile);

        return $rows === [] ? [] : array_keys($rows[0]);
    }

    /**
     * @return array<int, array<string, string|float|int>>
     */
    private function fromCsv(string $path, ImportProfile $profile): array
    {
        $handle = fopen($path, 'r');

        if ($handle === false) {
            throw new RuntimeException("Impossibile aprire il file: {$path}");
        }

        $raw = [];

        while (($line = fgetcsv($handle, 0, $profile->delimiter, '"', '\\')) !== false) {
            $raw[] = $line;
        }

        fclose($handle);

        return $this->assemble($raw, $profile);
    }

    /**
     * @return array<int, array<string, string|float|int>>
     */
    private function fromSpreadsheet(string $path, ImportProfile $profile): array
    {
        $reader = IOFactory::createReaderForFile($path);
        /*
         * Formats have to be read, not just values.
         *
         * With `setReadDataOnly(true)` a date cell arrives as the bare number
         * Excel stores underneath — 46254 for the 20th of August — and nothing
         * marks it as a date, so every row of three of these five statements
         * was thrown away as "no valid date". The cell format is the only thing
         * that distinguishes that number from an amount.
         */
        $reader->setReadDataOnly(false);
        $book = $reader->load($path);

        $sheet = $profile->sheet_name !== null && $profile->sheet_name !== ''
            ? $book->getSheetByName($profile->sheet_name)
            : $book->getSheet(0);

        if ($sheet === null) {
            throw new RuntimeException("Il foglio «{$profile->sheet_name}» non esiste in questo file.");
        }

        $raw = [];

        foreach ($sheet->getRowIterator() as $row) {
            $line = [];

            foreach ($row->getCellIterator() as $cell) {
                $value = $cell->getValue();

                // Excel keeps dates as a number of days since 1900. Left alone
                // it would reach the parser as "46265" and fail every row.
                if (ExcelDate::isDateTime($cell) && is_numeric($value)) {
                    $value = ExcelDate::excelToDateTimeObject((float) $value)->format('Y-m-d');
                }

                /*
                 * Numbers stay numbers.
                 *
                 * A spreadsheet holds 1418.81 as a float; a CSV holds the text
                 * "1.418,81". Flattening the first into a string makes the two
                 * indistinguishable, and the italian thousands rule then strips
                 * the decimal point and multiplies the amount by a hundred.
                 * Keeping the type is what tells the importer not to reformat.
                 */
                $line[] = is_int($value) || is_float($value) ? $value : ($value === null ? '' : trim((string) $value));
            }

            $raw[] = $line;
        }

        return $this->assemble($raw, $profile);
    }

    /**
     * Take the heading row named by the profile and key every row below it.
     *
     * @param  array<int, array<int, mixed>>  $raw
     * @return array<int, array<string, string|float|int>>
     */
    private function assemble(array $raw, ImportProfile $profile): array
    {
        $headerIndex = max(0, $profile->header_row - 1);

        if (! isset($raw[$headerIndex])) {
            throw new RuntimeException(
                "Il file non arriva alla riga {$profile->header_row}, dove il profilo si aspetta le intestazioni. "
                .'Controlla la riga di intestazione nel profilo di importazione.'
            );
        }

        $headings = [];

        foreach ($raw[$headerIndex] as $i => $heading) {
            $heading = trim((string) $heading);
            // Unnamed columns still need a stable key: mapping by position is
            // what lets a nameless column be chosen at all.
            $headings[$i] = $heading !== '' ? $heading : 'Colonna '.($i + 1);
        }

        $rows = [];

        foreach (array_slice($raw, $headerIndex + 1) as $line) {
            $row = [];

            foreach ($headings as $i => $heading) {
                $cell = $line[$i] ?? '';
                $row[$heading] = is_int($cell) || is_float($cell) ? $cell : trim((string) $cell);
            }

            // Blank separator rows and the totals footer both come through as
            // empty; neither is a transaction.
            if (implode('', array_map(fn ($v) => (string) $v, $row)) !== '') {
                $rows[] = $row;
            }
        }

        return $rows;
    }
}
