<?php

namespace App\Services;


use Maatwebsite\Excel\Facades\Excel;
use Smalot\PdfParser\Parser as PdfReader;


class StatementParser
{
    public function parse($file, $bank)
    {
        $extension = $file->getClientOriginalExtension();

        // 1. Проверка на поддерживаемое расширение
        if (!in_array($extension, ['xlsx', 'xls', 'pdf'])) {
            throw new \Exception("Формат файла .$extension не поддерживается. Загрузите Excel или PDF.");
        }

        if ($extension === 'xlsx' || $extension === 'xls') {
            $rawExcel = Excel::toArray([], $file)[0];
            $transactions = $this->parseExcel($rawExcel, $bank);
        } 
        
        if ($extension === 'pdf') {
            $pdfReader = new PdfReader();
            $pdf = $pdfReader->parseFile($file->getPathname());
            $text = $pdf->getText();
            $transactions = $this->parsePdf($text, $bank);
        }

        if (empty($transactions)) {
            throw new \Exception("Не удалось найти транзакции в файле. Проверьте, что файл не пустой и формат соответствует банку.");
        }

        return $transactions;
    }


    public function calculate_stats($transactions, $bank)
    {
        // Считаем итоги
        $income = 0;
        $expense = 0;

        foreach ($transactions as $t) {
            if ($t['amount'] > 0) {
                $income += $t['amount'];
            } else {
                $expense += abs($t['amount']);
            }
        }
        // Группировка по категориям
        $categoryTotals = [];
        foreach ($transactions as $t) {
            if ($t['amount'] < 0) {
                $cat = $t['category'];
                $categoryTotals[$cat] = round(($categoryTotals[$cat] ?? 0) + abs($t['amount']),2);
            }
        }
        arsort($categoryTotals);

        $stats = [
            'bank' => $bank,
            'income' => $income,
            'expense' => $expense,
            'turnover' => $income + $expense,
            'transactions' => $transactions,
            'categoryTotals'=> $categoryTotals,
        ];
        // dd($stats);
        return $stats;
    }

    private function parseExcel($rows, $bank)
    {
        $result = [];

        foreach ($rows as $row) {
            if ($bank === 'sber') {
                $rawDate = trim($row[0] ?? '');
                $dateObj = \DateTime::createFromFormat('d/m/Y', $rawDate);
    
                if (!$dateObj) {
                    continue;
                }
                
                $date = $dateObj->format('Y-m-d');
                $description = $row[1];
                $incomeRaw = $this->cleanAmount($row[2] ?? 0);
                $expenseRaw = $this->cleanAmount($row[3] ?? 0);
                $category = $row[4] ?? 'нет категории';

                if ($incomeRaw > 0) {
                    $amount = $incomeRaw;
                } elseif ($expenseRaw > 0) {
                    $amount = -$expenseRaw; // Делаем расход отрицательным для удобства расчетов
                } else {
                    $amount = 0;
                }
            // Добавить логику для Excel tbank
            } elseif ($bank === 'tbank') { 
                $date = '2026-03-04';
                $amount = 100;
                $description = 'описание';
                $category = 'категория';

            }

            if ($amount != 0) {
                $result[] = [
                    'date' => $date,
                    'amount' => $amount,
                    'description' => $description,
                    'category' => $category,
                ];
            }
        }

        return $result;
    }


    private function cleanText($text)
    {
        // Убираем странные символы в начале, если они есть
        $text = preg_replace('/^[?\s]+/', '', $text); 
        
        // Если слово начинается с "естораны", скорее всего это "Рестораны"
        if (str_starts_with($text, 'естораны')) {
            $text = 'Рестораны' . mb_substr($text, 8);
        }

        return trim($text);
    }


    private function parsePdf($text, $bank)
    {
        $result = [];

        $text = str_replace(["\t", "\xc2\xa0", "\xa0", "\u{A0}"], ' ', $text);
        $text = preg_replace('/ +/', ' ', $text);


        if ($bank === 'sber') {
            $startMarker = 'Расшифровка операций';
            $endMarker = 'Дата формирования документа';

            $startPos = mb_strpos($text, $startMarker);
            $endPos = mb_strpos($text, $endMarker);

            if ($startPos !== false) {
                // Берем текст после стартового маркера
                $text = mb_substr($text, $startPos + mb_strlen($startMarker));
            }
            if ($endPos !== false) {
                // Отрезаем всё, что после финального маркера (учитываем, что текст уже обрезан сверху)
                $currentEndPos = mb_strpos($text, $endMarker);
                $text = mb_substr($text, 0, $currentEndPos);
            }

            $firstPattern = '/^(\d{2}\.\d{2}\.\d{4}) (\d{2}:\d{2}) (\d{6})\s*(.+?) ([+-]?[\d ]+[.,]\d{2}) ([\d ]+[.,]\d{2})$/u';
            $secondPattern = '/^\d{2}\.\d{2}\.\d{4} (.+)$/u';

            $lines = explode("\n", $text);

            for ($i = 0; $i < count($lines); $i++) {
                $line = trim($lines[$i]);

                // first pattern
                if (preg_match($firstPattern, $line, $matches)) {
                    $dateObj = \DateTime::createFromFormat('d.m.Y', trim($matches[1]));
                    if (!$dateObj) {
                        continue;
                    }
                    
                    $date = $dateObj->format('Y-m-d');
                    $time = trim($matches[2]);
                    $code = trim($matches[3]);
                    $category = $this->cleanText(trim($matches[4]));
                    $amount_no_clear = $matches[5];
                    $balance = $this->cleanAmount($matches[6]);

                    // second pattern
                    if (isset($lines[$i + 1])) {
                        $nextLine = trim($lines[$i + 1]);
                        if (preg_match($secondPattern, $nextLine, $descMatches)) {
                            // dd($descMatches);
                            $description = trim($descMatches[1]);
                            $end_index = mb_stripos($description, 'операция');
                            if ($end_index !== false) {
                                $description = mb_substr($description, 0, $end_index);
                            }
                            $description = rtrim(trim($description), ".");
                            $i++; 
                        }
                    }
                        // пока пропускаю инфу из 3ей строки, 
                        // // third pattern
                        // if (isset($lines[$i + 1])) {
                        //     $line3 = trim($lines[$i + 1]);
                        //     if (str_contains($line3, '****')) {
                        //         $description .= " (" . $line3 . ")";
                        //         $i++; // поглощаем строку №3
                        //     }
                        // }

                    $amount = $this->cleanAmount($amount_no_clear);
                    if (strpos($amount_no_clear, '+') === false && $amount_no_clear > 0) {
                        $amount = -$amount;
                    }

                    $result[] = [
                        'date' => $date,
                        'amount' => $amount,
                        'description' => $description,
                        'category' => $category,
                    ];

                }
            }
        } elseif ($bank === 'tbank') {
            // Логика для tbank
        }

        return $result;
    }


    private function cleanAmount($value)
    {
        if (is_null($value) || $value === '') return 0;

        // Если это уже число (float/int), просто возвращаем
        if (is_numeric($value)) return (float)$value;

        // Если это строка (например "1 250,50 ₽")
        // Удаляем всё, кроме цифр, точек, запятых и минуса
        $value = preg_replace('/[^0-9,.-]/u', '', $value);

        // Заменяем запятую на точку
        $value = str_replace(',', '.', $value);
        return (float)$value;
    }

}