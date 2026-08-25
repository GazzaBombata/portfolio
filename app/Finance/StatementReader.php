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
     * Una riga di totale: quasi tutte le colonne vuote, e una parola come
     * «totale» o «saldo» dove dovrebbe esserci una descrizione.
     *
     * @param  array<string, string|float|int>  $row
     */
    private static function isTotalsRow(array $row): bool
    {
        $valori = array_values(array_filter(
            array_map(fn ($v) => trim((string) $v), $row),
            fn (string $v): bool => $v !== '',
        ));

        // Una transazione ha almeno una data, una descrizione e un importo.
        if (count($valori) > 2) {
            return false;
        }

        foreach ($valori as $v) {
            if (preg_match('/^(totale|saldo|total)\b/iu', $v) === 1) {
                return true;
            }
        }

        return false;
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
        $primaRiga = implode('|', $headings);

        foreach (array_slice($raw, $headerIndex + 1) as $line) {
            $row = [];

            foreach ($headings as $i => $heading) {
                $cell = $line[$i] ?? '';
                $row[$heading] = is_int($cell) || is_float($cell) ? $cell : trim((string) $cell);
            }

            $testo = implode('', array_map(fn ($v) => (string) $v, $row));

            // Righe vuote di separazione: non sono transazioni.
            if ($testo === '') {
                continue;
            }

            /*
             * Piedi e intestazioni in mezzo al file.
             *
             * Gli estratti scaricati più volte finiscono concatenati: un
             * blocco, il suo totale, e poi un altro blocco con la sua
             * intestazione. Quelle righe non sono movimenti, e lasciarle
             * passare le fa arrivare al parser, che le scarta — ma solo dopo
             * averle contate come «righe non leggibili», nascondendo che il
             * file conteneva due documenti.
             */
            if (static::isTotalsRow($row) || implode('|', array_values($row)) === $primaRiga) {
                continue;
            }

            $rows[] = $row;
        }

        return $rows;
    }
}
