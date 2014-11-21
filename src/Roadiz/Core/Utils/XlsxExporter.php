<?php
/**
 * Copyright © 2014, REZO ZERO
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
 * Except as contained in this notice, the name of the REZO ZERO shall not
 * be used in advertising or otherwise to promote the sale, use or other dealings
 * in this Software without prior written authorization from the REZO ZERO SARL.
 *
 * @file XlsxExporter.php
 * @copyright REZO ZERO 2014
 * @author Maxime Constantinian
 */
namespace RZ\Roadiz\Core\Utils;

class XlsxExporter
{

    static function exportXlsx($data, $keys)
    {
        // Create new PHPExcel object
        $objPHPExcel = new \PHPExcel();

        // Set document properties
        $objPHPExcel->getProperties()->setCreator("Roadiz CMS")
                                     ->setLastModifiedBy("Roadiz CMS")
                                     ->setCategory("");

        $cacheMethod = \PHPExcel_CachedObjectStorageFactory:: cache_to_phpTemp;
        $cacheSettings = array(
            'memoryCacheSize' => '8MB'
        );
        \PHPExcel_Settings::setCacheStorageMethod($cacheMethod, $cacheSettings);

        $objPHPExcel->setActiveSheetIndex(0);

        foreach ($keys as $key => $value) {
           $objPHPExcel->getActiveSheet()->setCellValueByColumnAndRow($key, 1, $value);
        }
        foreach ($data as $key => $answer) {
            foreach ($answer as $k => $value) {
                if ($k == 1) {
                    $value = \PHPExcel_Shared_Date::PHPToExcel($value);
                    $objPHPExcel->getActiveSheet()
                        ->getStyle('B'.(2 + $key))
                        ->getNumberFormat()->setFormatCode('dd.mm.yyyy hh:MM:ss');
                }
                $objPHPExcel->getActiveSheet()->setCellValueByColumnAndRow($k, 2 + $key, $value);
            }
        }

        // Rename worksheet
        $objPHPExcel->getActiveSheet()->setTitle('answers');
        // Set active sheet index to the first sheet, so Excel opens this as the first sheet

        $objWriter = \PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel2007');
        ob_start();
        $objWriter->save('php://output');
        $return = ob_get_clean();
        return $return;
    }
}