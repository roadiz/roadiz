<?php

namespace RZ\Renzo\Core\Utils;

class XlsxExporter
{

    static function exportXlsx($data, $keys)
    {
        // Create new PHPExcel object
        $objPHPExcel = new \PHPExcel();

        // Set document properties
        $objPHPExcel->getProperties()->setCreator("Renzo CMS")
                                     ->setLastModifiedBy("Renzo CMS")
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