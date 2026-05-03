<?php
namespace App\Libraries;
use Mpdf\Mpdf;

class MPDFGenerator
{
    /**
     * Generate a PDF from a view file.
     *
     * @param string $html_content The HTML content to render (usually from view())
     * @param string $filename The name of the file to save/stream
     * @param array $paper_size Paper size (e.g., 'A4')
     * @param string $orientation Paper orientation ('P' for Portrait, 'L' for Landscape)
     */
    public function generatePDFFile($htmlContent, $filename, $paperSize = 'A4', $orientation = 'P')
    {
        $mpdf   =   new Mpdf([
            'default_font'  =>  'sans-serif',
            'format'        =>  $paperSize,
            'orientation'   =>  $orientation
        ]);

        $mpdf->SetHTMLHeader('');
        $mpdf->SetHTMLFooter('');
        $mpdf->WriteHTML($htmlContent);

        $pdfContent =   $mpdf->Output(PATH_STORAGE_FILE_FAKTUR_PENJUALAN.$filename, 'F');
        return $filename;
    }

    public function generatePDFFileOutput($htmlContent, $filename, $paperSize = 'A4', $orientation = 'P')
    {
        $mpdf   =   new Mpdf([
            'default_font'  =>  'sans-serif',
            'format'        =>  $paperSize,
            'orientation'   =>  $orientation
        ]);

        $mpdf->SetHTMLHeader('');
        $mpdf->SetHTMLFooter('');
        $mpdf->WriteHTML($htmlContent);

        $pdfContent =   $mpdf->Output($filename, 'S');
        return $pdfContent;
    }
}