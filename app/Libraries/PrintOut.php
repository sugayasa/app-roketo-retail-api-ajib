<?php
namespace App\Libraries;
use App\Models\MainOperation;

class PrintOut
{
    public function generatePrintOutNotaRetail($detailNotaPenjualan, $daftarBarangNota, $daftarBiayaLain, $daftarHargaPaket, $daftarDiskonEvent, $isArsip = false)
    {

        $mainOperation      =   new MainOperation();
        $idToko             =   $detailNotaPenjualan['IDTOKO'];
        $nomorNotaPenjualan =   $detailNotaPenjualan['NOTAPENJUALANNOMOR'];
        $inputUser          =   $detailNotaPenjualan['INPUTUSER'];
        $tanggalTransaksi   =   $detailNotaPenjualan['INPUTTANGGAL'];
        $waktuTransaksi     =   $detailNotaPenjualan['INPUTWAKTU'];
        $namaCustomer       =   $detailNotaPenjualan['NAMACUSTOMER'];
        $metodeBayar        =   $detailNotaPenjualan['METODEBAYAR'];
        $totalHargaBarang   =   $detailNotaPenjualan['TOTALHARGABARANG'];
        $totalHargaDiskon   =   $detailNotaPenjualan['TOTALHARGADISKON'];
        $totalHargaLain     =   $detailNotaPenjualan['TOTALHARGALAIN'];
        $totalHargaAkhir    =   $detailNotaPenjualan['TOTALHARGAAKHIR'];
        $totalBayar         =   $detailNotaPenjualan['TOTALBAYAR'];
        $catatanNota        =   $detailNotaPenjualan['CATATAN'];

        $detailToko         =   $mainOperation->getDetailToko($idToko);
        $namaToko           =   isset($detailToko['NAMA']) ? $detailToko['NAMA'] : '-';
        $alamatToko         =   isset($detailToko['ALAMAT']) ? $detailToko['ALAMAT'] : '-';
        $dataPrintNota      =   view('print/NotaPenjualan', [
            'namaToko'          =>  $namaToko,
            'alamatToko'        =>  $alamatToko,
            'nomorNotaPenjualan'=>  $nomorNotaPenjualan,
            'inputUser'         =>  $inputUser,
            'tanggalTransaksi'  =>  $tanggalTransaksi,
            'waktuTransaksi'    =>  $waktuTransaksi,
            'namaCustomer'      =>  $namaCustomer,
            'metodeBayar'       =>  $metodeBayar,
            'daftarBarangNota'  =>  $daftarBarangNota,
            'daftarBiayaLain'   =>  $daftarBiayaLain,
            'daftarHargaPaket'  =>  $daftarHargaPaket,
            'daftarDiskonEvent' =>  $daftarDiskonEvent,
            'totalHargaBarang'  =>  $totalHargaBarang,
            'totalHargaDiskon'  =>  $totalHargaDiskon,
            'totalHargaLain'    =>  $totalHargaLain,
            'totalHargaAkhir'   =>  $totalHargaAkhir,
            'totalBayar'        =>  $totalBayar,
            'catatanNota'       =>  $catatanNota,
            'isArsip'           =>  $isArsip
        ]);

        return $dataPrintNota;
    }
}