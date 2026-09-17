<?php

namespace App\Imports;

use Illuminate\Http\UploadedFile;
use RuntimeException;

class TabularFileReader
{
    public function read(UploadedFile $file): array
    {
        return strtolower($file->getClientOriginalExtension()) === 'xlsx' ? $this->xlsx($file->getRealPath()) : $this->csv($file->getRealPath());
    }

    private function csv(string $path): array
    {
        $handle = fopen($path, 'rb');
        if (! $handle) {
            throw new RuntimeException('The import file could not be read.');
        }
        $headers = fgetcsv($handle);
        if (! is_array($headers)) {
            fclose($handle);

            return [];
        }
        $headers = array_map(fn ($header) => trim((string) $header), $headers);
        $rows = [];
        while (($values = fgetcsv($handle)) !== false) {
            if (count(array_filter($values, fn ($value) => trim((string) $value) !== '')) === 0) {
                continue;
            }
            $values = array_pad($values, count($headers), null);
            $rows[] = array_combine($headers, array_slice($values, 0, count($headers)));
        }
        fclose($handle);

        return $rows;
    }

    private function xlsx(string $path): array
    {
        $temporary = tempnam(sys_get_temp_dir(), 'noure-xlsx-').'.zip';
        copy($path, $temporary);
        try {
            $archive = new \PharData($temporary);
            $shared = [];
            if (isset($archive['xl/sharedStrings.xml'])) {
                $xml = simplexml_load_string($archive['xl/sharedStrings.xml']->getContent());
                foreach ($xml->si ?? [] as $item) {
                    $shared[] = isset($item->t) ? (string) $item->t : implode('', array_map(fn ($run) => (string) $run->t, iterator_to_array($item->r)));
                }
            }
            if (! isset($archive['xl/worksheets/sheet1.xml'])) {
                throw new RuntimeException('The XLSX workbook has no first worksheet.');
            }
            $sheet = simplexml_load_string($archive['xl/worksheets/sheet1.xml']->getContent());
            $matrix = [];
            foreach ($sheet->sheetData->row ?? [] as $row) {
                $values = [];
                foreach ($row->c as $cell) {
                    preg_match('/([A-Z]+)[0-9]+/', (string) $cell['r'], $match);
                    $index = $this->columnIndex($match[1] ?? 'A');
                    $value = (string) $cell->v;
                    if ((string) $cell['t'] === 's') {
                        $value = $shared[(int) $value] ?? '';
                    } elseif ((string) $cell['t'] === 'inlineStr') {
                        $value = (string) $cell->is->t;
                    }
                    $values[$index] = $value;
                }
                $matrix[] = $values;
            }
            $headers = array_map(fn ($value) => trim((string) $value), $matrix[0] ?? []);

            return array_values(array_filter(array_map(function (array $values) use ($headers): array {
                $row = [];
                foreach ($headers as $index => $header) {
                    $row[$header] = $values[$index] ?? null;
                }

                return $row;
            }, array_slice($matrix, 1)), fn ($row) => count(array_filter($row, fn ($value) => trim((string) $value) !== '')) > 0));
        } finally {
            @unlink($temporary);
        }
    }

    private function columnIndex(string $letters): int
    {
        $index = 0;
        foreach (str_split($letters) as $letter) {
            $index = $index * 26 + ord($letter) - 64;
        }

        return $index - 1;
    }
}
