<?php
/**
 * Copyright © 2014, Ambroise Maupate and Julien Blanchet
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is furnished
 * to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in all
 * copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS
 * OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL
 * THE AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS
 * IN THE SOFTWARE.
 *
 * Except as contained in this notice, the name of the ROADIZ shall not
 * be used in advertising or otherwise to promote the sale, use or other dealings
 * in this Software without prior written authorization from Ambroise Maupate and Julien Blanchet.
 *
 * @file XlsxExporter.php
 * @author Maxime Constantinian
 */
namespace RZ\Roadiz\Utils;

use Doctrine\Common\Collections\Collection;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Shared\Date;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\Translation\TranslatorInterface;

class XlsxExporter
{
    /**
     * @var TranslatorInterface
     */
    private $translator;

    /**
     * XlsxExporter constructor.
     * @param TranslatorInterface $translator
     */
    public function __construct(TranslatorInterface $translator)
    {
        $this->translator = $translator;
    }

    /**
     * Export an array of data to XLSX format.
     *
     * @param  \IteratorAggregate|array $data
     * @param  array $keys
     * @return string
     */
    public function exportXlsx($data, $keys = [])
    {
        $spreadsheet = new Spreadsheet();

        // Set document properties
        $spreadsheet->getProperties()->setCreator("Roadiz CMS")
            ->setLastModifiedBy("Roadiz CMS")
            ->setCategory("");

        $spreadsheet->setActiveSheetIndex(0);
        $activeSheet = $spreadsheet->getActiveSheet();
        $activeRow = 1;
        $hasGlobalHeader = false;

        $headerStyles = [
            'font' => [
                'bold' => true,
                'color' => ['rgb' => 'FF0000'],
                'size' => 11,
                'name' => 'Verdana',
            ],
            'width' => 50,
        ];

        /*
         * Add headers row
         */
        if (count($keys) > 0) {
            foreach ($keys as $key => $value) {
                $columnAlpha = Coordinate::stringFromColumnIndex($key + 1);
                $activeSheet->getStyle($columnAlpha . ($activeRow))->applyFromArray($headerStyles);
                $activeSheet->setCellValueByColumnAndRow($key + 1, $activeRow, $this->translator->trans($value));
            }
            $activeRow++;
            $hasGlobalHeader = true;
        }

        $headerkeys = $keys;

        foreach ($data as $answer) {
            /*
             * If headers have changed
             * we print them
             */
            if (false === $hasGlobalHeader &&
                $headerkeys != array_keys($answer)) {
                $headerkeys = array_keys($answer);
                foreach ($headerkeys as $key => $value) {
                    $columnAlpha = Coordinate::stringFromColumnIndex($key + 1);
                    $activeSheet->getStyle($columnAlpha . $activeRow)->applyFromArray($headerStyles);
                    $activeSheet->setCellValueByColumnAndRow($key + 1, $activeRow, $this->translator->trans($value));
                }
                $activeRow++;
            }

            /*
             * Print values
             */
            $answer = array_values($answer);
            foreach ($answer as $k => $value) {
                $columnAlpha = Coordinate::stringFromColumnIndex($k + 1);

                if ($value instanceof Collection ||
                    is_array($value)) {
                    continue;
                }

                if ($value instanceof \DateTime) {
                    $value = Date::PHPToExcel($value);
                    $activeSheet->getStyle($columnAlpha . ($activeRow))
                        ->getNumberFormat()
                        ->setFormatCode('dd.mm.yyyy hh:MM:ss');
                }
                /*
                 * Set value into cell
                 */
                $activeSheet->getStyle($columnAlpha . $activeRow)->getAlignment()->setWrapText(true);
                $activeSheet->setCellValueByColumnAndRow($k + 1, $activeRow, $this->translator->trans($value));
            }

            $activeRow++;
        }

        /*
         * autosize
         */
        foreach (range('A', $spreadsheet->getActiveSheet()->getHighestDataColumn()) as $col) {
            $spreadsheet->getActiveSheet()
                    ->getColumnDimension($col)
                    ->setWidth(50);
        }


        // Set active sheet index to the first sheet, so Excel opens this as the first sheet
        $writer = new Xlsx($spreadsheet);
        ob_start();
        $writer->save('php://output');
        return ob_get_clean();
    }
}
