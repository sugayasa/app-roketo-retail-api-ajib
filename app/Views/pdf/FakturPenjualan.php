<!DOCTYPE html>
<html>
    <head>
        <title>Faktur Penjualan</title>
        <?= isset($cssStyle) ? $cssStyle : '' ?>
        <?= isset($infoTableStyle) ? $infoTableStyle : '' ?>
        <style>
            .info-box {
                width: 100%;
                float: right;
                font-size: 7pt;
            }
            .info-table {
                width: 100%;
                border-collapse: collapse;
            }
            .info-table td {
                padding: 1px 0;
            }
            .info-table .label {
                width: 20%;
                font-weight: bold;
            }
            .info-table .colon {
                width: 5%;
            }
            .info-table .data {
                text-align: left;
            }
            .col-no { width: 4%; text-align: right; }
            .col-kode { width: 14%; }
            .col-nama { width: 42%; }
            .col-jumlah { width: 8%; text-align: right; }
            .col-satuan { width: 4%; padding-left: 2px; }
            .col-harga { width: 10%; text-align: right; }
            .col-potongan { width: 8%; text-align: right; }
            .col-total { width: 10%; text-align: right; }

            .summary-signature {
                width: 100%;
                margin-top: 10px;
            }
            .footer-left {
                width: 50%;
                float: left;
            }
            .footer-right {
                width: 50%;
                float: right;
                text-align: right;
            }
            .footer-table {
                width: 100%;
            }
            .footer-table td, .footer-table-td {
                font-size: 7pt;
            }

            .signatures {
                width: 100%;
                margin-top: 40px;
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
        <?= $kopSurat ?>
        <div class="divider"></div>
        <h3 class="clearfix" style="margin-top: 20px; text-align: center; font-size: 11pt;">Faktur Penjualan</h3>
        <div class="header-container clearfix" style="margin-top: 24px; margin-bottom: 24px;">
            <div class="info-box clearfix">
                <table style="width: 100%">
                    <tr>
                        <td style="width: 65%">
                            <table class="info-table">
                                <tr>
                                    <td class="label">No Transaksi</td>
                                    <td class="colon"> : </td>
                                    <td class="data"><?= $nomorSurat ?></td>
                                </tr>
                                <tr>
                                    <td class="label">Tanggal</td>
                                    <td class="colon"> : </td>
                                    <td class="data"><?= $tanggalSurat ?></td>
                                </tr>
                                <tr>
                                    <td class="label">Pelanggan</td>
                                    <td class="colon"> : </td>
                                    <td class="data" colspan="4"><?= $pelangganNama ?></td>
                                </tr>
                                <tr>
                                    <td class="label">Alamat</td>
                                    <td class="colon"> : </td>
                                    <td class="data" colspan="4"><?= $pelangganAlamat ?></td>
                                </tr>
                            </table>
                        </td>
                        <td style="width: 35%; vertical-align: top;">
                            <table class="info-table">
                                <tr>
                                    <td class="label">Dept</td>
                                    <td class="colon"> : </td>
                                    <td class="data"><?= $departemen ?></td>
                                </tr>
                                <tr>
                                    <td class="label">User</td>
                                    <td class="colon"> : </td>
                                    <td class="data"><?= $userAdmin ?></td>
                                </tr>
                            </table>
                        </td>
                    </tr>
                </table>
            </div>
        </div>
        <h4 style="font-size: 8pt;">Daftar Barang</h4>
        <table class="item-table">
            <thead>
                <tr>
                    <th class="col-no" style="text-align: right;">No</th>
                    <th class="col-kode">Kode Item</th>
                    <th class="col-nama">Nama Item</th>
                    <th class="col-jumlah" style="text-align: right;">Jumlah</th>
                    <th class="col-satuan">Satuan</th>
                    <th class="col-harga" style="text-align: right;">Harga</th>
                    <th class="col-potongan" style="text-align: right;">Potongan</th>
                    <th class="col-total" style="text-align: right;">Total</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $nomor      =   1;
                $totalItem  =   $totalPotongan =    $totalHargaBarang =    0;
                foreach($dataBarang as $keyBarang){
                    $totalItem          +=   $keyBarang->JUMLAH;
                    $totalPotongan      +=   $keyBarang->HARGADISKON * $keyBarang->JUMLAH;
                    $totalHargaBarang   +=   $keyBarang->HARGAGROSIR * $keyBarang->JUMLAH;
                ?>
                    <tr>
                        <td class="col-no"><?= $nomor ?></td>
                        <td class="col-kode"><?= $keyBarang->KODEBARANGSKU ?></td>
                        <td class="col-nama"><?= $keyBarang->DESKRIPSISKU ?></td>
                        <td class="col-jumlah"><?= number_format($keyBarang->JUMLAH, 0, ',', '.') ?></td>
                        <td class="col-satuan"><?= $keyBarang->KODESATUAN ?></td>
                        <td class="col-harga"><?= number_format($keyBarang->HARGAAWAL, 0, ',', '.') ?></td>
                        <td class="col-potongan"><?= number_format($keyBarang->HARGADISKON, 0, ',', '.') ?></td>
                        <td class="col-total"><?= number_format($keyBarang->HARGAGROSIR * $keyBarang->JUMLAH, 0, ',', '.') ?></td>
                    </tr>
                <?php
                $nomor++;
                }
                ?>
            </tbody>
        </table>

        <div class="summary-signature clearfix">
            <div class="footer-left">
                Keterangan : <br/> <p style="padding-left: 6px;"><?=$keterangan?></p>
            </div>
            <div class="footer-right">
                <table border="0" class="footer-table">
                    <tbody>
                        <tr>
                            <td style="width: 20%"></td>
                            <td style="width: 30%"></td>
                            <td style="width: 30%"></td>
                            <td style="width: 20%"></td>
                        </tr>
                        <tr>
                            <td style="text-align: right;" class="footer-table-td">Jumlah Item</td>
                            <td class="footer-table-td">: <?= number_format($totalItem, 0, ',', '.') ?></td>
                            <td style="text-align: right;" class="footer-table-td">Sub Total :</td>
                            <td style="text-align: right;" class="footer-table-td"><?= number_format($totalHargaBarang, 0, ',', '.') ?></td>
                        </tr>
                        <tr>
                            <td style="text-align: right;">Potongan</td>
                            <td>: <?= number_format($totalPotongan, 0, ',', '.') ?></td>
                            <td style="text-align: right;" class="footer-table-td">DP :</td>
                            <td style="text-align: right;" class="footer-table-td"><?= number_format($totalBayarDownPayment, 0, ',', '.') ?></td>
                        </tr>
                        <tr>
                            <td style="text-align: right;">Pembayaran</td>
                            <td>: <b><?= $caraPelunasan ?></b></td>
                            <td style="text-align: right;" class="footer-table-td">Tunai :</td>
                            <td style="text-align: right;" class="footer-table-td"><?= number_format($totalBayarPelunasan, 0, ',', '.') ?></td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <div class="signatures clearfix">
            <div class="signature-box">
                <span>Hormat Kami</span><br/><br/><br/><br/><br/><br/>
                <span style="font-size: 7pt;">( . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . )</span>
            </div>
            <div class="signature-box">
                <span>Konsumen</span><br/><br/><br/><br/><br/><br/>
                <span style="font-size: 7pt;">( . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . )</span>
            </div>
        </div>
    </body>
</html>