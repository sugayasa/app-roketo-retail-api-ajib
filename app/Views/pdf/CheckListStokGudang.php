<!DOCTYPE html>
<html>
<head>
    <title>Check List Stok Gudang</title>
    <?= isset($cssStyle) ? $cssStyle : '' ?>
    <?= isset($infoTableStyle) ? $infoTableStyle : '' ?>
    <style>
        .info-box {
            width: 100%;
            float: right;
            font-size: 7pt;
        }
        .divider {
            width: 100%;
            border-bottom: 1px solid #000;
            margin-top: 2px;
            margin-bottom: 2px;
            padding: 0;
        }
        .col-no { width: 3%; text-align: right; }
        .col-kode { width: 10%; }
        .col-nama { width: 32%; }
        .col-satuan { width: 5%; padding-left: 2px; }
        .col-stokData { width: 10%; text-align: right; }
        .col-permintaan { width: 10%; text-align: right; }
        .col-persetujuan { width: 10%; text-align: right; }
        .col-stokFisik { width: 10%; text-align: right; }
        .col-statusCek { width: 10%; text-align: right; }
        .checkbox-box {
            border: 1px solid #000;
            width: 20px;
            height: 20px;
            display: inline-block;
        }
        .summary-signature {
            width: 100%;
            margin-top: 10px;
        }
        .signatures {
            width: 100%;
            margin-top: 60px;
            text-align: center;
        }
        .signature-box {
            width: 50%;
            float: left;
        }
        .signature-line {
            display: inline-block;
            margin-top: 40px;
            border-bottom: 1px solid #000;
            width: 80%;
        }
    </style>
</head>
<body>
<h3 class="clearfix" style="margin-top: 20px; text-align: center; font-size: 11pt;">Faktur Penjualan</h3>

<div class="header-container clearfix" style="margin-top: 24px; margin-bottom: 24px;">
    <div class="info-box clearfix">
        <table style="width: 100%">
            <tr>
                <td style="width: 65%">
                    <table class="info-table">
                        <tr>
                            <td class="label">No. Nota</td>
                            <td class="colon"> : </td>
                            <td class="data"><?= $detailNotaMutasi['NOTAMUTASINOMOR'] ?></td>
                        </tr>
                        <tr>
                            <td class="label">Tgl. Permintaan</td>
                            <td class="colon"> : </td>
                            <td class="data"><?= $detailNotaMutasi['REQUESTTANGGALWAKTU'] ?></td>
                        </tr>
                        <tr>
                            <td class="label">Toko</td>
                            <td class="colon"> : </td>
                            <td class="data" colspan="4"><?= $detailNotaMutasi['NAMATOKO'] ?></td>
                        </tr>
                        <tr>
                            <td class="label">Alamat</td>
                            <td class="colon"> : </td>
                            <td class="data" colspan="4"><?= $detailNotaMutasi['ALAMATTOKO'] ?></td>
                        </tr>
                    </table>
                </td>
                <td style="width: 35%; vertical-align: top;">
                    <table class="info-table">
                        <tr>
                            <td class="label" style="text-align: right;">Total SKU</td>
                            <td class="colon"> : </td>
                            <td class="data"><?= $detailNotaMutasi['TOTALSKU'] ?></td>
                        </tr>
                        <tr>
                            <td class="label" style="text-align: right;">Req. User</td>
                            <td class="colon"> : </td>
                            <td class="data"><?= $detailNotaMutasi['REQUESTUSER'] ?></td>
                        </tr>
                    </table>
                </td>
            </tr>
        </table>
    </div>
</div>

<h4 style="font-size: 8pt;">Daftar Barang Cek Fisik</h4>
<table class="item-table">
    <thead>
        <tr>
            <th class="col-no">No.</th>
            <th class="col-kode">Kode Item</th>
            <th class="col-nama">Nama Item</th>
            <th class="col-satuan">Satuan</th>
            <th class="col-stokData" style="text-align: right;">Stok Data</th>
            <th class="col-permintaan" style="text-align: right;">Permintaan</th>
            <th class="col-persetujuan" style="text-align: right;">Persetujuan</th>
            <th class="col-stokFisik" style="text-align: right;">Stok Fisik</th>
            <th class="col-statusCek" style="text-align: right;">Status Cek</th>
        </tr>
    </thead>
    <tbody>
        <?php
            $nomor  =   1;
            foreach($dataBarangSKU as $keyBarangSKU):
        ?>
        <tr>
            <td class="col-no"><?= $nomor ?></td>
            <td class="col-kode"><?= $keyBarangSKU->KODESKU ?></td>
            <td class="col-nama"><?= $keyBarangSKU->DESKRIPSISKU ?></td>
            <td class="col-satuan"><?= $keyBarangSKU->KODESATUAN ?></td>
            <td class="col-stokData"><?= number_format($keyBarangSKU->STOKGUDANG, 0, ',', '.') ?></td>
            <td class="col-permintaan"><?= number_format($keyBarangSKU->JUMLAHREQUEST, 0, ',', '.') ?></td>
            <td class="col-persetujuan"><b><?= number_format($keyBarangSKU->JUMLAHPERSETUJUANDRAFT, 0, ',', '.') ?></b></td>
            <td class="col-stokFisik"></td>
            <td class="col-statusCek"><span class="checkbox-box">&nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp;</span></td>
        </tr>
        <?php
            $nomor++;
            endforeach;
        ?>
    </tbody>
</table>

<div class="signatures clearfix">
    <div class="signature-box">
        <span>Petugas Work Order</span><br/><br/><br/><br/><br/>
        <span style="font-size: 7pt;">(..............................................)</span>
    </div>
    <div class="signature-box">
        <span>Pelaksana</span><br/><br/><br/><br/><br/>
        <span style="font-size: 7pt;">(..............................................)</span>
    </div>
</div>

</body>
</html>