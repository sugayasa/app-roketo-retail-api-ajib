<!DOCTYPE html>
<html>
<head>
    <title>Surat Jalan Penjualan</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            font-size: 7pt;
            margin: 0.5cm;
            padding: 0;
        }
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
        .clearfix::after {
            content: "";
            clear: both;
            display: table;
        }
        .divider {
            width: 100%;
            border-bottom: 1px solid #000;
            margin-top: 2px;
            margin-bottom: 2px;
            padding: 0;
        }
        .item-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }
        .item-table thead th {
            border-top: 2px solid #000;
            border-bottom: 2px solid #000;
            padding: 5px 3px;
            font-size: 7pt;
            text-align: left;
        }
        .item-table tbody td {
            border-bottom: 1px solid #ddd;
            padding: 5px 3px;
            font-size: 7pt;
            vertical-align: top;
        }
        .item-table tbody tr:last-child td {
            border-bottom: none;
        }
        .col-no { width: 4%; }
        .col-kode { width: 15%; }
        .col-nama { width: 45%; }
        .col-jumlah { width: 10%; text-align: right; }
        .col-unit { width: 5%; padding-left: 2px; }
        .col-check, .col-colly { 
            width: 8%; 
            text-align: center; 
            padding: 3px 0;
        }
        .checkbox-box {
            border: 1px solid #000;
            width: 12px;
            height: 12px;
            display: inline-block;
        }
        .summary-signature {
            width: 100%;
            margin-top: 10px;
        }
        .footer-left {
            width: 60%;
            float: left;
        }
        .footer-right {
            width: 40%;
            float: right;
            text-align: right;
        }
        .signatures {
            width: 100%;
            margin-top: 40px;
            text-align: center;
        }
        .signature-box {
            width: 33%;
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
                <td style="width: 70%">
                    <table class="info-table">
                        <tr>
                            <td class="label">No Transaksi</td>
                            <td class="colon"> : </td>
                            <td class="data">0039/JJL/BJB/0825</td>
                        </tr>
                        <tr>
                            <td class="label">Tanggal</td>
                            <td class="colon"> : </td>
                            <td class="data">25/08/2025</td>
                        </tr>
                        <tr>
                            <td class="label">Pelanggan</td>
                            <td class="colon"> : </td>
                            <td class="data" colspan="4">INI HELM KANDANGAN</td>
                        </tr>
                        <tr>
                            <td class="label">Alamat</td>
                            <td class="colon"> : </td>
                            <td class="data" colspan="4">Jl. A. Yani, Sungai Raya</td>
                        </tr>
                    </table>
                </td>
                <td style="width: 30%; vertical-align: top;">
                    <table class="info-table">
                        <tr>
                            <td class="label" style="text-align: right;">Dept</td>
                            <td class="colon"> : </td>
                            <td class="data">BJB</td>
                        </tr>
                        <tr>
                            <td class="label" style="text-align: right;">User</td>
                            <td class="colon"> : </td>
                            <td class="data">ADMIN</td>
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
            <th class="col-no">No.</th>
            <th class="col-kode">Kode Item</th>
            <th class="col-nama">Nama Item</th>
            <th class="col-jumlah" style="text-align: right;">Jumlah</th>
            <th class="col-unit">Satuan</th>
            <th class="col-check" style="text-align: center;">Check</th>
            <th class="col-colly" style="text-align: center;">Colly</th>
        </tr>
    </thead>
    <tbody>
        <tr>
            <td class="col-no">1</td>
            <td class="col-kode">80800018</td>
            <td class="col-nama">GM EVO SOLID SR BLACK-L</td>
            <td class="col-jumlah">2,00</td>
            <td class="col-unit">PCS</td>
            <td class="col-check"><span class="checkbox-box"></span></td>
            <td class="col-colly"><span class="checkbox-box"></span></td>
        </tr>
        <tr>
            <td class="col-no">2</td>
            <td class="col-kode">80800019</td>
            <td class="col-nama">GM EVO SOLID SR BLACK-M</td>
            <td class="col-jumlah">2,00</td>
            <td class="col-unit">PCS</td>
            <td class="col-check"><span class="checkbox-box"></span></td>
            <td class="col-colly"><span class="checkbox-box"></span></td>
        </tr>
        <tr>
            <td class="col-no">3</td>
            <td class="col-kode">70700026</td>
            <td class="col-nama">CARGLOSS HIJAB S. GREEN - AS</td>
            <td class="col-jumlah">2,00</td>
            <td class="col-unit">PCS</td>
            <td class="col-check"><span class="checkbox-box"></span></td>
            <td class="col-colly"><span class="checkbox-box"></span></td>
        </tr>
        <tr>
            <td class="col-no">1</td>
            <td class="col-kode">80800018</td>
            <td class="col-nama">GM EVO SOLID SR BLACK-L</td>
            <td class="col-jumlah">2,00</td>
            <td class="col-unit">PCS</td>
            <td class="col-check"><span class="checkbox-box"></span></td>
            <td class="col-colly"><span class="checkbox-box"></span></td>
        </tr>
        <tr>
            <td class="col-no">2</td>
            <td class="col-kode">80800019</td>
            <td class="col-nama">GM EVO SOLID SR BLACK-M</td>
            <td class="col-jumlah">2,00</td>
            <td class="col-unit">PCS</td>
            <td class="col-check"><span class="checkbox-box"></span></td>
            <td class="col-colly"><span class="checkbox-box"></span></td>
        </tr>
        <tr>
            <td class="col-no">3</td>
            <td class="col-kode">70700026</td>
            <td class="col-nama">CARGLOSS HIJAB S. GREEN - AS</td>
            <td class="col-jumlah">2,00</td>
            <td class="col-unit">PCS</td>
            <td class="col-check"><span class="checkbox-box"></span></td>
            <td class="col-colly"><span class="checkbox-box"></span></td>
        </tr>
        <tr>
            <td class="col-no">1</td>
            <td class="col-kode">80800018</td>
            <td class="col-nama">GM EVO SOLID SR BLACK-L</td>
            <td class="col-jumlah">2,00</td>
            <td class="col-unit">PCS</td>
            <td class="col-check"><span class="checkbox-box"></span></td>
            <td class="col-colly"><span class="checkbox-box"></span></td>
        </tr>
        <tr>
            <td class="col-no">2</td>
            <td class="col-kode">80800019</td>
            <td class="col-nama">GM EVO SOLID SR BLACK-M</td>
            <td class="col-jumlah">2,00</td>
            <td class="col-unit">PCS</td>
            <td class="col-check"><span class="checkbox-box"></span></td>
            <td class="col-colly"><span class="checkbox-box"></span></td>
        </tr>
        <tr>
            <td class="col-no">3</td>
            <td class="col-kode">70700026</td>
            <td class="col-nama">CARGLOSS HIJAB S. GREEN - AS</td>
            <td class="col-jumlah">2,00</td>
            <td class="col-unit">PCS</td>
            <td class="col-check"><span class="checkbox-box"></span></td>
            <td class="col-colly"><span class="checkbox-box"></span></td>
        </tr>
        <tr>
            <td class="col-no">1</td>
            <td class="col-kode">80800018</td>
            <td class="col-nama">GM EVO SOLID SR BLACK-L</td>
            <td class="col-jumlah">2,00</td>
            <td class="col-unit">PCS</td>
            <td class="col-check"><span class="checkbox-box"></span></td>
            <td class="col-colly"><span class="checkbox-box"></span></td>
        </tr>
        <tr>
            <td class="col-no">2</td>
            <td class="col-kode">80800019</td>
            <td class="col-nama">GM EVO SOLID SR BLACK-M</td>
            <td class="col-jumlah">2,00</td>
            <td class="col-unit">PCS</td>
            <td class="col-check"><span class="checkbox-box"></span></td>
            <td class="col-colly"><span class="checkbox-box"></span></td>
        </tr>
        <tr>
            <td class="col-no">3</td>
            <td class="col-kode">70700026</td>
            <td class="col-nama">CARGLOSS HIJAB S. GREEN - AS</td>
            <td class="col-jumlah">2,00</td>
            <td class="col-unit">PCS</td>
            <td class="col-check"><span class="checkbox-box"></span></td>
            <td class="col-colly"><span class="checkbox-box"></span></td>
        </tr>
        <tr>
            <td class="col-no">1</td>
            <td class="col-kode">80800018</td>
            <td class="col-nama">GM EVO SOLID SR BLACK-L</td>
            <td class="col-jumlah">2,00</td>
            <td class="col-unit">PCS</td>
            <td class="col-check"><span class="checkbox-box"></span></td>
            <td class="col-colly"><span class="checkbox-box"></span></td>
        </tr>
        <tr>
            <td class="col-no">2</td>
            <td class="col-kode">80800019</td>
            <td class="col-nama">GM EVO SOLID SR BLACK-M</td>
            <td class="col-jumlah">2,00</td>
            <td class="col-unit">PCS</td>
            <td class="col-check"><span class="checkbox-box"></span></td>
            <td class="col-colly"><span class="checkbox-box"></span></td>
        </tr>
        <tr>
            <td class="col-no">3</td>
            <td class="col-kode">70700026</td>
            <td class="col-nama">CARGLOSS HIJAB S. GREEN - AS</td>
            <td class="col-jumlah">2,00</td>
            <td class="col-unit">PCS</td>
            <td class="col-check"><span class="checkbox-box"></span></td>
            <td class="col-colly"><span class="checkbox-box"></span></td>
        </tr>
        <tr>
            <td class="col-no">1</td>
            <td class="col-kode">80800018</td>
            <td class="col-nama">GM EVO SOLID SR BLACK-L</td>
            <td class="col-jumlah">2,00</td>
            <td class="col-unit">PCS</td>
            <td class="col-check"><span class="checkbox-box"></span></td>
            <td class="col-colly"><span class="checkbox-box"></span></td>
        </tr>
        <tr>
            <td class="col-no">2</td>
            <td class="col-kode">80800019</td>
            <td class="col-nama">GM EVO SOLID SR BLACK-M</td>
            <td class="col-jumlah">2,00</td>
            <td class="col-unit">PCS</td>
            <td class="col-check"><span class="checkbox-box"></span></td>
            <td class="col-colly"><span class="checkbox-box"></span></td>
        </tr>
        <tr>
            <td class="col-no">3</td>
            <td class="col-kode">70700026</td>
            <td class="col-nama">CARGLOSS HIJAB S. GREEN - AS</td>
            <td class="col-jumlah">2,00</td>
            <td class="col-unit">PCS</td>
            <td class="col-check"><span class="checkbox-box"></span></td>
            <td class="col-colly"><span class="checkbox-box"></span></td>
        </tr>
        <tr>
            <td class="col-no">1</td>
            <td class="col-kode">80800018</td>
            <td class="col-nama">GM EVO SOLID SR BLACK-L</td>
            <td class="col-jumlah">2,00</td>
            <td class="col-unit">PCS</td>
            <td class="col-check"><span class="checkbox-box"></span></td>
            <td class="col-colly"><span class="checkbox-box"></span></td>
        </tr>
        <tr>
            <td class="col-no">2</td>
            <td class="col-kode">80800019</td>
            <td class="col-nama">GM EVO SOLID SR BLACK-M</td>
            <td class="col-jumlah">2,00</td>
            <td class="col-unit">PCS</td>
            <td class="col-check"><span class="checkbox-box"></span></td>
            <td class="col-colly"><span class="checkbox-box"></span></td>
        </tr>
        <tr>
            <td class="col-no">3</td>
            <td class="col-kode">70700026</td>
            <td class="col-nama">CARGLOSS HIJAB S. GREEN - AS</td>
            <td class="col-jumlah">2,00</td>
            <td class="col-unit">PCS</td>
            <td class="col-check"><span class="checkbox-box"></span></td>
            <td class="col-colly"><span class="checkbox-box"></span></td>
        </tr>
        <tr>
            <td class="col-no">1</td>
            <td class="col-kode">80800018</td>
            <td class="col-nama">GM EVO SOLID SR BLACK-L</td>
            <td class="col-jumlah">2,00</td>
            <td class="col-unit">PCS</td>
            <td class="col-check"><span class="checkbox-box"></span></td>
            <td class="col-colly"><span class="checkbox-box"></span></td>
        </tr>
        <tr>
            <td class="col-no">2</td>
            <td class="col-kode">80800019</td>
            <td class="col-nama">GM EVO SOLID SR BLACK-M</td>
            <td class="col-jumlah">2,00</td>
            <td class="col-unit">PCS</td>
            <td class="col-check"><span class="checkbox-box"></span></td>
            <td class="col-colly"><span class="checkbox-box"></span></td>
        </tr>
        <tr>
            <td class="col-no">3</td>
            <td class="col-kode">70700026</td>
            <td class="col-nama">CARGLOSS HIJAB S. GREEN - AS</td>
            <td class="col-jumlah">2,00</td>
            <td class="col-unit">PCS</td>
            <td class="col-check"><span class="checkbox-box"></span></td>
            <td class="col-colly"><span class="checkbox-box"></span></td>
        </tr>
        <tr>
            <td class="col-no">3</td>
            <td class="col-kode">70700026</td>
            <td class="col-nama">CARGLOSS HIJAB S. GREEN - AS</td>
            <td class="col-jumlah">2,00</td>
            <td class="col-unit">PCS</td>
            <td class="col-check"><span class="checkbox-box"></span></td>
            <td class="col-colly"><span class="checkbox-box"></span></td>
        </tr>
        <tr>
            <td class="col-no">3</td>
            <td class="col-kode">70700026</td>
            <td class="col-nama">CARGLOSS HIJAB S. GREEN - AS</td>
            <td class="col-jumlah">2,00</td>
            <td class="col-unit">PCS</td>
            <td class="col-check"><span class="checkbox-box"></span></td>
            <td class="col-colly"><span class="checkbox-box"></span></td>
        </tr>
    </tbody>
</table>

<div class="summary-signature clearfix">
    <div class="footer-left">
        Keterangan
    </div>
    <div class="footer-right">
        Jml Item: <span style="font-weight: bold; padding-left: 20px;">6,00</span>
    </div>
</div>

<div class="signatures clearfix">
    <div class="signature-box">
        <span>Hormat Kami</span><br/><br/><br/><br/>
        <span style="font-size: 7pt;">(.......................)</span>
    </div>
    <div class="signature-box">
        <span>Pengirim</span><br/><br/><br/><br/>
        <span style="font-size: 7pt;">(.......................)</span>
    </div>
    <div class="signature-box">
        <span>Penerima</span><br/><br/><br/><br/>
        <span style="font-size: 7pt;">(.......................)</span>
    </div>
</div>

</body>
</html>