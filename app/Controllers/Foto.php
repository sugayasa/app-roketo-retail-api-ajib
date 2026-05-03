<?php

namespace App\Controllers;

use CodeIgniter\RESTful\ResourceController;
use CodeIgniter\API\ResponseTrait;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use Psr\Log\LoggerInterface;

class Foto extends ResourceController
{
    /**
     * Return an array of resource objects, themselves in array format
     *
     * @return mixed
     */
    use ResponseTrait;
    protected $request;
    public function initController(RequestInterface $request, ResponseInterface $response, LoggerInterface $logger) {
        parent::initController($request, $response, $logger);
    }

    public function index()
    {
        return $this->failForbidden('[E-AUTH-000] Forbidden Access');
    }

    public function logoPerusahaan($namaFile)
    {
        $fullFilePath   =   PATH_STORAGE_LOGO_PERUSAHAAN.$namaFile;
        if (!is_file($fullFilePath) || !file_exists($fullFilePath)) $fullFilePath   =   PATH_STORAGE_LOGO_PERUSAHAAN.'default-logo.png';

        $mimeType       =   mime_content_type($fullFilePath);
        $fileContent    =   file_get_contents($fullFilePath);

        return $this->response
            ->setHeader('Content-Type', $mimeType)
            ->setHeader('Content-Disposition', 'inline; filename="' . $namaFile . '"')
            ->setBody($fileContent);
    }

    public function barang($namaFoto)
    {
        $fullFilePath   =   PATH_STORAGE_FOTO_BARANG.$namaFoto;
        if (!is_file($fullFilePath) || !file_exists($fullFilePath)) $fullFilePath   =   PATH_STORAGE_FOTO_BARANG.'no-image.jpg';

        $mimeType       =   mime_content_type($fullFilePath);
        $fileContent    =   file_get_contents($fullFilePath);

        return $this->response
            ->setHeader('Content-Type', $mimeType)
            ->setHeader('Content-Disposition', 'inline; filename="' . $namaFoto . '"')
            ->setBody($fileContent);
    }

    public function pembayaran($namaFoto)
    {
        $fullFilePath   =   PATH_STORAGE_FOTO_PEMBAYARAN.$namaFoto;
        if (!is_file($fullFilePath) || !file_exists($fullFilePath)) $fullFilePath   =   PATH_STORAGE_FOTO_PEMBAYARAN.'no-image.jpg';

        $mimeType       =   mime_content_type($fullFilePath);
        $fileContent    =   file_get_contents($fullFilePath);

        return $this->response
            ->setHeader('Content-Type', $mimeType)
            ->setHeader('Content-Disposition', 'inline; filename="' . $namaFoto . '"')
            ->setBody($fileContent);
    }
}