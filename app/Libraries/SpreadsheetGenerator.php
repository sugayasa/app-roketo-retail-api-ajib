<?php
namespace App\Libraries;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Alignment;

class SpreadsheetGenerator
{
    public function getDocumentProperties($rowStartDocument, $arrTitleData, $arrFilterData, $arrHeaderData)
    {
        $rowStartFilter         =   $rowStartDocument + count($arrTitleData) + 1;
        $rowStartTableHeader    =   $rowStartFilter + count($arrFilterData) + 1;
        $arrRowSpanNumber       =   array_column($arrHeaderData, 1);
        $maxRowSpanNumber       =   max($arrRowSpanNumber);
        $rowNumberTableContent  =   $rowStartTableHeader + $maxRowSpanNumber;
        $rowFirstTable          =   $rowStartTableHeader;

        return [
            'rowStartFilter'        =>  $rowStartFilter,
            'rowStartTableHeader'   =>  $rowStartTableHeader,
            'rowNumberTableContent' =>  $rowNumberTableContent,
            'rowFirstTable'         =>  $rowFirstTable,
        ];
    }

    public function setDocumentTitle($activeWorksheet, $arrTitle, $firstColumn, $lastColumn, $rowNumber = 1)
    {
        foreach($arrTitle as $title){
            $activeWorksheet->setCellValue($firstColumn.$rowNumber, $title);
            $activeWorksheet->mergeCells($firstColumn.$rowNumber.':'.$lastColumn.$rowNumber);
            $activeWorksheet->getStyle($firstColumn.$rowNumber)->getFont()->setBold(true);
            $activeWorksheet->getStyle($firstColumn.$rowNumber)->getAlignment()->setHorizontal('center')->setVertical('center');
            $rowNumber++;
        }
    }

    public function setDocumentFilter($activeWorksheet, $arrFilterData, $lastColumn, $rowNumber = 1)
    {
        foreach($arrFilterData as $filterData){
            $filterTitle    =   $filterData[0];
            $filterValue    =   $filterData[1];

            $activeWorksheet->setCellValue('A'.$rowNumber, $filterTitle);
            $activeWorksheet->setCellValue('B'.$rowNumber, ': '.$filterValue);
            $activeWorksheet->getStyle('A'.$rowNumber.':B'.$rowNumber)->getFont()->setBold(true);
            $activeWorksheet->mergeCells('B'.$rowNumber.':'.$lastColumn.$rowNumber);
            $rowNumber++;
        }
    }

    public function setDocumentTableHeader($activeWorksheet, $arrHeaderData, $rowNumber = 1)
    {
        $rowNumberLast  =   $rowNumber;
        foreach($arrHeaderData as $headerData){
            $arrColumnHeader    =   $headerData[0];
            $headerRowSpan      =   $headerData[1];
            $headerTitle        =   $headerData[2];
            $headerWidth        =   $headerData[3];
            $headerAlignment    =   $headerData[4];
            $additionalRowNumber=   $headerData[5] ?? 0;
            $verticalAlignment  =   $headerRowSpan == 1 ? Alignment::VERTICAL_TOP : Alignment::VERTICAL_CENTER;

            $activeWorksheet->setCellValue($arrColumnHeader[0].($rowNumber + $additionalRowNumber), $headerTitle);
            $activeWorksheet->getStyle($arrColumnHeader[0].($rowNumber + $additionalRowNumber))->getAlignment()->setHorizontal($headerAlignment)->setVertical($verticalAlignment);
            if($headerWidth && $headerWidth > 0) $activeWorksheet->getColumnDimension($arrColumnHeader[0])->setWidth($headerWidth);
            
            if($headerRowSpan > 1){
                $rowNumberLastRowSpan   =   $rowNumber + $headerRowSpan - 1;
                $rowNumberLast          =   $rowNumberLast >= $rowNumberLastRowSpan ? $rowNumberLast : $rowNumberLastRowSpan;
                $activeWorksheet->mergeCells($arrColumnHeader[0].$rowNumber.':'.$arrColumnHeader[0].$rowNumberLastRowSpan);

            } else {
                if(count($arrColumnHeader) > 1){
                    $activeWorksheet->mergeCells($arrColumnHeader[0].($rowNumber + $additionalRowNumber).':'.end($arrColumnHeader).($rowNumber + $additionalRowNumber));
                }
            }
        }
        
        $columnHeaderFirst   =   $arrHeaderData[0][0][0];
        $columnHeaderLast    =   end(end($arrHeaderData)[0]);
        $activeWorksheet->getStyle($columnHeaderFirst.$rowNumber.':'.$columnHeaderLast.$rowNumberLast)->getFont()->setBold(true);
    }

    public function setDocumentTableStyle($activeWorksheet, $firstColumn, $lastColumn, $rowFirstTable, $rowNumberTableContent)
    {
        $styleArray = [
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN
                ]
            ]
        ];

        $activeWorksheet->getStyle($firstColumn.$rowFirstTable.':'.$lastColumn.($rowNumberTableContent - 1))->applyFromArray($styleArray)->getAlignment()->setVertical('top')->setWrapText(true);
        $activeWorksheet->setBreak($firstColumn.$rowNumberTableContent, \PhpOffice\PhpSpreadsheet\Worksheet\Worksheet::BREAK_ROW)->setBreak(++$lastColumn.$rowNumberTableContent, \PhpOffice\PhpSpreadsheet\Worksheet\Worksheet::BREAK_COLUMN);

        $activeWorksheet->setShowGridLines(false);
        $activeWorksheet->getPageSetup()->setFitToWidth(1)->setFitToHeight(0);
    }
    
    public function setDocumentPassword($spreadsheet, $activeWorksheet)
    {
        $spreadsheet->getSecurity()->setLockWindows(true)->setLockStructure(true)->setWorkbookPassword("password");
        $activeWorksheet->getProtection()->setSheet(true)->setPassword(APP_EXPORT_EXCEL_DEFAULT_PASSWORD);
    }

    public function writeDocumentOutput($spreadsheet, $documentTitle = 'Data')
    {
        $writer =   new Xlsx($spreadsheet);
        header('Content-Type: application/vnd.ms-excel');
        header('Content-Disposition: attachment;filename="'.$documentTitle.'_'.date('YmdHis').'.xlsx"');
        header('Cache-Control: max-age=0');

        ob_end_clean();
        $writer->save('php://output');
        exit;
    }
}