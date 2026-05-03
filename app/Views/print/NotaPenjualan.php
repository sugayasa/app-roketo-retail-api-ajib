<?php
$charLength         =   32;
$charSeparator      =   '-';
$namaToko           =   strlen($namaToko) >= $charLength ? str_pad(substr($namaToko, 0, $charLength - 2), $charLength, " ", STR_PAD_BOTH) : str_pad($namaToko, $charLength, " ", STR_PAD_BOTH);
$alamatToko         =   strlen($alamatToko) >= $charLength ? str_pad(substr($alamatToko, 0, $charLength - 2), $charLength, " ", STR_PAD_BOTH) : str_pad($alamatToko, $charLength, " ", STR_PAD_BOTH);
$inputUserMaxLen    =   $charLength - strlen($waktuTransaksi) - 7;
$inputUser          =   strlen($inputUser) >= $inputUserMaxLen ? substr($inputUser, 0, $inputUserMaxLen) : $inputUser;
$namaCustomerMaxLen =   $charLength - strlen($metodeBayar) - 8;
$namaCustomer       =   strlen($namaCustomer) >= $namaCustomerMaxLen ? substr($namaCustomer, 0, $namaCustomerMaxLen) : $namaCustomer;
$metodeBayarMaxLen  =   $charLength - strlen($namaCustomer) - 8;
$metodeBayar        =   strlen($metodeBayar) >= $metodeBayarMaxLen ? substr($metodeBayar, 0, $metodeBayarMaxLen) : str_pad($metodeBayar, $metodeBayarMaxLen, " ", STR_PAD_RIGHT);

echo $namaToko;
echo "\n";
echo $alamatToko;
echo "\n";
echo str_repeat($charSeparator, $charLength);
echo "\n";
echo "No.: ".$nomorNotaPenjualan.str_repeat(" ", $charLength - strlen($nomorNotaPenjualan) - strlen($tanggalTransaksi) - 5).$tanggalTransaksi;
echo "\n";
echo "Ksr: ".$inputUser.str_repeat(" ", $charLength - strlen($inputUser) - strlen($waktuTransaksi) - 5).$waktuTransaksi;
echo "\n";
echo "Pel: ".$namaCustomer." / ".$metodeBayar;
echo "\n";
echo str_repeat($charSeparator, $charLength);

$iBarangNota   =   $totalJumlahBarang   =   0;
foreach($daftarBarangNota as $keyBarangNota){
    if($iBarangNota > 0) echo "\n".str_repeat(" ", $charLength);
    $merkKodeSKU    =   $keyBarangNota->NAMAMERK.' - '.$keyBarangNota->KODESKU;
    $hargaAwal      =   number_format($keyBarangNota->HARGAAWAL, 0, ',', '.');
    $jumlahBarang   =   number_format($keyBarangNota->JUMLAH, 0, ',', '.');
    $barisSubTotal  =   number_format($jumlahBarang, 0, ',', '.')." ".$keyBarangNota->KODESATUAN.' x '.$hargaAwal;
    $hargaSubTotal  =   str_pad(number_format($keyBarangNota->HARGASUBTOTAL, 0, ',', '.'), 10, " ", STR_PAD_LEFT);

    echo "\n";
    echo strlen($merkKodeSKU) >= $charLength ? substr($merkKodeSKU, 0, $charLength) : str_pad($merkKodeSKU, $charLength, " ", STR_PAD_RIGHT);
    echo "\n";
    echo strlen($keyBarangNota->DESKRIPSISKU) >= $charLength ? substr($keyBarangNota->DESKRIPSISKU, 0, $charLength) : str_pad($keyBarangNota->DESKRIPSISKU, $charLength, " ", STR_PAD_RIGHT);
    echo "\n";
    echo str_pad($barisSubTotal, $charLength - strlen($hargaSubTotal) - 3, " ", STR_PAD_RIGHT)." = ".$hargaSubTotal;

    if(intval($keyBarangNota->TIPEDISKON) > 0){
        $tipeDiskonStr  =   $keyBarangNota->TIPEDISKON == 1 ? " -[".$keyBarangNota->JUMLAHDISKON.'% OFF]' : ' -[-'.number_format($keyBarangNota->JUMLAHDISKON,0,',','.').']';
        $hargaDiskon    =   str_pad(number_format($keyBarangNota->HARGADISKON * $jumlahBarang, 0, ',', '.'), 10, " ", STR_PAD_LEFT);

        echo "\n";
        echo str_pad($tipeDiskonStr.' x '.$jumlahBarang, $charLength - strlen($hargaDiskon) - 3, " ", STR_PAD_RIGHT)." = ".$hargaDiskon;
        echo "\n";
        echo str_pad(substr(" -".$keyBarangNota->DESKRIPSIDISKON, 0, 23), $charLength, " ", STR_PAD_RIGHT);
    }

    if(intval($keyBarangNota->TIPEDISKONPAKET) > 0){
        $tipeDiskonStr  =   $keyBarangNota->TIPEDISKONPAKET == 1 ? " -[".$keyBarangNota->JUMLAHDISKONPAKET.'% OFF]' : ' -[-'.number_format($keyBarangNota->JUMLAHDISKONPAKET,0,',','.').']';
        $hargaDiskon    =   str_pad(number_format($keyBarangNota->HARGADISKON * $jumlahBarang, 0, ',', '.'), 10, " ", STR_PAD_LEFT);

        echo "\n";
        echo str_pad($tipeDiskonStr.' x '.$jumlahBarang, $charLength - strlen($hargaDiskon) - 3, " ", STR_PAD_RIGHT)." = ".$hargaDiskon;
        echo "\n";

        $deskripsiPaket     = " -PAKET ".$keyBarangNota->DESKRIPSIDISKONPAKET;
        $arrDeskripsiPaket  = str_split($deskripsiPaket, $charLength - 1);
        foreach($arrDeskripsiPaket as $iDesPaket => $keyDeskripsiPaket){
            $spaceAdditional= $iDesPaket == 0 ? "" : " ";
            if($iDesPaket > 0) echo "\n";
            echo str_pad(substr($spaceAdditional.$keyDeskripsiPaket, 0, $charLength - 1), $charLength, " ", STR_PAD_RIGHT);
        }
    }    

    $totalJumlahBarang +=  $keyBarangNota->JUMLAH;
    $iBarangNota++;
}

if(!empty($daftarBiayaLain)){
    echo "\n".str_repeat(" ", $charLength);
    foreach($daftarBiayaLain as $keyBiayaLain){
        $nominalBiayaLain =   str_pad(number_format($keyBiayaLain->NOMINAL, 0, ',', '.'), 10, " ", STR_PAD_LEFT);
        $namaBiayaLain    =   "+ ".$keyBiayaLain->JENISBIAYA;
        $namaBiayaLain    =   strlen($namaBiayaLain) > $charLength - strlen($nominalBiayaLain) - 3 ? substr($namaBiayaLain, 0, $charLength - strlen($nominalBiayaLain) - 3) : $namaBiayaLain;

        echo "\n";
        echo str_pad($namaBiayaLain, $charLength - strlen($nominalBiayaLain) - 3, " ", STR_PAD_RIGHT)." = ".$nominalBiayaLain;
    }
}

if(!empty($daftarDiskonEvent)){
    echo "\n".str_repeat(" ", $charLength);
    foreach($daftarDiskonEvent as $keyDiskonEvent){
        $nominalDiskonEvent =   str_pad("-".number_format($keyDiskonEvent->NOMINAL, 0, ',', '.'), 10, " ", STR_PAD_LEFT);
        $namaDiskonEvent    =   $keyDiskonEvent->NAMAEVENT;
        $namaDiskonEvent    =   strlen($namaDiskonEvent) > $charLength - strlen($nominalDiskonEvent) - 3 ? substr($namaDiskonEvent, 0, $charLength - strlen($nominalDiskonEvent) - 3) : $namaDiskonEvent;

        echo "\n";
        echo str_pad($namaDiskonEvent, $charLength - strlen($nominalDiskonEvent) - 3, " ", STR_PAD_RIGHT)." = ".$nominalDiskonEvent;
    }
}

echo "\n";
echo str_repeat($charSeparator, $charLength);
echo "\n";

$barisJnsBrgQty     =   "Jns: ".number_format(count($daftarBarangNota), 0, ',', '.')." | Qty: ".number_format($totalJumlahBarang, 0, ',', '.');
$totalHargaBarangNum=   str_pad(number_format($totalHargaBarang, 0, ',', '.'), 10, " ", STR_PAD_LEFT);
echo str_pad($barisJnsBrgQty, ($charLength - strlen($totalHargaBarangNum) - 3), " ", STR_PAD_RIGHT)." = ".$totalHargaBarangNum;
echo "\n";

$totalHargaLainNum  =   str_pad(number_format($totalHargaLain, 0, ',', '.'), 10, " ", STR_PAD_LEFT);
echo str_pad("Biaya Lain", ($charLength - strlen($totalHargaLainNum) - 3), " ", STR_PAD_RIGHT)." = ".$totalHargaLainNum;
echo "\n";

$totalHargaDiskonNum=   str_pad("-".number_format($totalHargaDiskon, 0, ',', '.'), 10, " ", STR_PAD_LEFT);
echo str_pad("Diskon Total", ($charLength - strlen($totalHargaDiskonNum) - 3), " ", STR_PAD_RIGHT)." = ".$totalHargaDiskonNum;
echo "\n";

$grandTotalHargaNum =   str_pad(number_format($totalHargaAkhir, 0, ',', '.'), 10, " ", STR_PAD_LEFT);
echo str_pad("Grand Total", ($charLength - strlen($grandTotalHargaNum) - 3), " ", STR_PAD_RIGHT)." = ".$grandTotalHargaNum;

echo "\n";
echo str_repeat($charSeparator, $charLength);
echo "\n";

$totalBayarNum     =   str_pad(number_format($totalBayar, 0, ',', '.'), 10, " ", STR_PAD_LEFT);
echo str_pad("Bayar ", ($charLength - strlen($totalBayarNum) - 3), " ", STR_PAD_RIGHT)." = ".$totalBayarNum;
echo "\n";

$totalKembalian     =   $totalBayar - $totalHargaAkhir;
$totalKembalianNum  =   str_pad(number_format($totalKembalian, 0, ',', '.'), 10, " ", STR_PAD_LEFT);
echo str_pad("Kembali ", ($charLength - strlen($totalKembalianNum) - 3), " ", STR_PAD_RIGHT)." = ".$totalKembalianNum;
echo "\n".str_repeat(" ", $charLength);
echo "\n";
echo str_pad("Note: ", $charLength, " ", STR_PAD_RIGHT);
echo "\n";

$arrCatatanNota =   str_split($catatanNota, $charLength - 2);
foreach($arrCatatanNota as $keyCatatanNota){
    echo "  ".str_pad($keyCatatanNota, $charLength - 2, " ", STR_PAD_RIGHT);
    echo "\n";
}

if(!empty($daftarHargaPaket)){
    foreach($daftarHargaPaket as $keyHargaPaket){
        $namaHargaPaket     =   $keyHargaPaket->JUMLAHPAKET." x ".$keyHargaPaket->NAMAHARGARETAILPAKET;
        $arrNamaHargaPaket  =   str_split($namaHargaPaket, $charLength);
        foreach($arrNamaHargaPaket as $keyNamaHargaPaket){
            echo str_pad($keyNamaHargaPaket, $charLength, " ", STR_PAD_RIGHT);
            echo "\n";
        }
    }
}

echo str_repeat($charSeparator, $charLength);
echo "\n";

$catatanKaki    =   $isArsip ? "------ ARSIP TOKO ------" : "Barang yang sudah dibeli tidak dapat dikembalikan";
$arrCatatanKaki =   str_split($catatanKaki, $charLength - 2);
foreach($arrCatatanKaki as $keyCatatanKaki){
    echo str_pad($keyCatatanKaki, $charLength, " ", STR_PAD_BOTH);
    echo "\n";
}

$tanggalWaktuCetak  =   "*** ".date('d-m-Y H:i:s')." ***";
$tanggalWaktuCetak  =   strlen($tanggalWaktuCetak) >= $charLength ? str_pad(substr($tanggalWaktuCetak, 0, $charLength - 2), $charLength, " ", STR_PAD_BOTH) : str_pad($tanggalWaktuCetak, $charLength, " ", STR_PAD_BOTH);
echo $tanggalWaktuCetak;
echo "\n".str_repeat(" ", $charLength);
echo "\n".str_repeat($charSeparator, $charLength);
echo "\n".str_repeat(" ", $charLength);
?>