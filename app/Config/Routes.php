<?php

namespace Config;

// Create a new instance of our RouteCollection class.
$routes = Services::routes();

/*
 * --------------------------------------------------------------------
 * Router Setup
 * --------------------------------------------------------------------
 */
$routes->setDefaultNamespace('App\Controllers');
$routes->setDefaultController('Index');
$routes->setDefaultMethod('index');
$routes->setTranslateURIDashes(false);
$routes->set404Override('App\Controllers\Index::response404');
// The Auto Routing (Legacy) is very dangerous. It is easy to create vulnerable apps
// where controller filters or CSRF protection are bypassed.
// If you don't want to define all routes, please use the Auto Routing (Improved).
// Set `$autoRoutesImproved` to true in `app/Config/Feature.php` and set the following to true.
// $routes->setAutoRoute(false);

/*
 * --------------------------------------------------------------------
 * Route Definitions
 * --------------------------------------------------------------------
 */

// We get a performance increase by specifying the default
// route since we don't have to scan directories.
$routes->options('(:any)', function () {
    $response   =   service('response');
    $response->setHeader('Access-Control-Allow-Methods', 'GET, POST, OPTIONS');
    $response->setHeader('Access-Control-Allow-Origin', '*');
    $response->setHeader('Access-Control-Allow-Headers', '*');
    return $response;
});

$routes->post('/', 'Index::response404');
$routes->get('/', 'Index::response404');

$routes->post('access/check', 'Access::check');
$routes->get('access/captcha/(:any)', 'Access::captcha/$1');
$routes->get('access/logout/(:any)', 'Access::logout/$1');

$routes->get('databaseTool/migrate', 'DatabaseTool::migrate', ['filter' => 'databaseTool']);
$routes->get('databaseTool/rollback', 'DatabaseTool::rollback', ['filter' => 'databaseTool']);
$routes->get('databaseTool/seed/(:any)', 'DatabaseTool::seed/$1', ['filter' => 'databaseTool']);

$routes->group('access', ['filter' => 'auth:mustNotBeLoggedIn'], function($routes) {
    $routes->post('login', 'Access::login', ['filter' => 'auth:mustNotBeLoggedIn']);
});

$routes->group('access', ['filter' => 'auth:mustBeLoggedIn'], function($routes) {
    $routes->post('getDataOption', 'Access::getDataOption');
    $routes->post('getDataOptionByKey/(:any)/(:any)/(:any)', 'Access::getDataOptionByKey/$1/$2/$3');
    $routes->post('detailProfile', 'Access::detailProfile');
    $routes->post('saveDetailProfile', 'Access::saveDetailProfile');
});

$routes->group('foto', [], function($routes) {
    $routes->get('logoPerusahaan/(:any)', 'Foto::logoPerusahaan/$1');
    $routes->get('barang/(:any)', 'Foto::barang/$1');
    $routes->get('pembayaran/(:any)', 'Foto::pembayaran/$1');
});

$routes->group('erp', ['filter' => 'auth:mustBeLoggedIn'], function($routes) {
    $routes->group('dashboard', ['filter' => 'auth:mustBeLoggedIn'], function($routes) {
        $functionRoute =   'ERP\Dashboard';
        $routes->post('getDataDashboard', $functionRoute.'::getDataDashboard');
    });

    $routes->group('master', ['filter' => 'auth:mustBeLoggedIn'], function($routes) {
        $routes->group('dataDasarBarang', function($routes) {
            $routes->group('atribut', function($routes) {
                $functionRoute  =   'ERP\Master\DataDasarBarang\Atribut';
                $routes->post('getList', $functionRoute.'::getList');
                $routes->post('saveData', $functionRoute.'::saveData');
            });

            $routes->group('merk', function($routes) {
                $functionRoute  =   'ERP\Master\DataDasarBarang\Merk';
                $routes->post('getList', $functionRoute.'::getList');
                $routes->post('saveData', $functionRoute.'::saveData');
            });

            $routes->group('kategori', function($routes) {
                $functionRoute  =   'ERP\Master\DataDasarBarang\Kategori';
                $routes->post('getList', $functionRoute.'::getList');
                $routes->post('saveData', $functionRoute.'::saveData');
            });
        });

        $routes->group('barang', function($routes) {
            $functionRoute  =   'ERP\Master\Barang';
            $routes->post('getList', $functionRoute.'::getList');
            $routes->post('getListSKU', $functionRoute.'::getListSKU');
            $routes->post('uploadImageBarang', $functionRoute.'::uploadImageBarang');
            $routes->post('saveDataBarang', $functionRoute.'::saveDataBarang');
            $routes->post('saveDataBarangSKU', $functionRoute.'::saveDataBarangSKU');
            $routes->post('getListAturanKonversiSKU', $functionRoute.'::getListAturanKonversiSKU');
            $routes->post('saveAturanKonversiSKU', $functionRoute.'::saveAturanKonversiSKU');
        });

        $routes->group('produsenDistributor', function($routes) {
            $functionRoute  =   'ERP\Master\ProdusenDistributor';
            $routes->post('getList', $functionRoute.'::getList');
            $routes->post('saveDataProdusenDistributor', $functionRoute.'::saveData');
        });

        $routes->group('gudang', function($routes) {
            $functionRoute  =   'ERP\Master\Gudang';
            $routes->post('getList', $functionRoute.'::getList');
            $routes->post('uploadImageLogoPerusahaan', $functionRoute.'::uploadImageLogoPerusahaan');
            $routes->post('saveDataGudang', $functionRoute.'::saveData');
        });

        $routes->group('toko', function($routes) {
            $functionRoute  =   'ERP\Master\Toko';
            $routes->post('getList', $functionRoute.'::getList');
            $routes->post('saveDataToko', $functionRoute.'::saveData');
            $routes->post('saveDataTokoTerdekat', $functionRoute.'::saveDataTokoTerdekat');
        });

        $routes->group('customer', function($routes) {
            $functionRoute  =   'ERP\Master\Customer';
            $routes->post('getList', $functionRoute.'::getList');
            $routes->post('saveDataCustomer', $functionRoute.'::saveData');
        });

        $routes->group('kelompokHargaGrosir', function($routes) {
            $functionRoute  =   'ERP\Master\KelompokHargaGrosir';
            $routes->post('getList', $functionRoute.'::getList');
            $routes->post('saveDataKelompokHargaGrosir', $functionRoute.'::saveData');
        });
    });

    $routes->group('stok', ['filter' => 'auth:mustBeLoggedIn'], function($routes) {
        $routes->group('pembelianBarang', function($routes) {
            $functionRoute  =   'ERP\Stok\PembelianBarang';
            $routes->post('getList', $functionRoute.'::getList');
            $routes->post('getDetail', $functionRoute.'::getDetail');
            $routes->post('getDataOptionMerkNamaBarang', $functionRoute.'::getDataOptionMerkNamaBarang');
            $routes->post('getDataOptionSKUBarang', $functionRoute.'::getDataOptionSKUBarang');
            $routes->post('addDataNotaPembelian', $functionRoute.'::addDataNotaPembelian');
            $routes->post('saveDataNotaBarangSKU', $functionRoute.'::saveDataNotaBarangSKU');
            $routes->post('deleteDataNotaBarangSKU', $functionRoute.'::deleteDataNotaBarangSKU');
            $routes->post('saveDataBarangInboundGudang', $functionRoute.'::saveDataBarangInboundGudang');
        });

        $routes->group('pengaturanHargaJual', function($routes) {
            $functionRoute  =   'ERP\Stok\PengaturanHargaJual';
            $routes->post('getListBarang', $functionRoute.'::getListBarang');
            $routes->post('getDetailHargaJual', $functionRoute.'::getDetailHargaJual');
            $routes->post('getUrlExcelHargaJualByFilter', $functionRoute.'::getUrlExcelHargaJualByFilter');
            $routes->post('getDetailHargaJualGrosir', $functionRoute.'::getDetailHargaJualGrosir');
            $routes->post('saveDetailHargaJual', $functionRoute.'::saveDetailHargaJual');
            $routes->post('saveDetailHargaJualGrosir', $functionRoute.'::saveDetailHargaJualGrosir');
        });

        $routes->group('pengaturanHargaJualPaket', function($routes) {
            $functionRoute  =   'ERP\Stok\PengaturanHargaJualPaket';
            $routes->post('getListPaket', $functionRoute.'::getListPaket');
            $routes->post('getDetailPaket', $functionRoute.'::getDetailPaket');
            $routes->post('getDetailBarangHarga', $functionRoute.'::getDetailBarangHarga');
            $routes->post('addHargaJualPaket', $functionRoute.'::addHargaJualPaket');
            $routes->post('updateHargaJualPaket', $functionRoute.'::updateHargaJualPaket');
        });

        $routes->group('pengaturanDiskon', function($routes) {
            $routes->group('retail', function($routes) {
                $functionRoute  =   'ERP\Stok\PengaturanDiskon';
                $routes->post('getListDiskonRetail', $functionRoute.'::getListDiskonRetail');
                $routes->post('saveDataDiskonRetail', $functionRoute.'::saveDataDiskonRetail');
            });
            $routes->group('event', function($routes) {
                $functionRoute  =   'ERP\Stok\PengaturanDiskon';
                $routes->post('getListDiskonEvent', $functionRoute.'::getListDiskonEvent');
                $routes->post('saveDataDiskonEvent', $functionRoute.'::saveDataDiskonEvent');
            });
            $routes->group('grosir', function($routes) {
                $functionRoute  =   'ERP\Stok\PengaturanDiskon';
                $routes->post('getListDiskonGrosir', $functionRoute.'::getListDiskonGrosir');
                $routes->post('saveDataDiskonGrosir', $functionRoute.'::saveDataDiskonGrosir');
            });
        });

        $routes->group('stokBarang', function($routes) {
            $functionRoute =   'ERP\Stok\StokBarang';
            $routes->post('getDaftarStokBarangGudang', $functionRoute.'::getDaftarStokBarangGudang');
            $routes->post('getDaftarStokBarangToko', $functionRoute.'::getDaftarStokBarangToko');
        });
        
        $routes->group('kartuStok', function($routes) {
            $functionRoute =   'ERP\Stok\KartuStok';
            $routes->post('getDetailKartuStokGudang', $functionRoute.'::getDetailKartuStokGudang');
            $routes->post('getDetailKartuStokToko', $functionRoute.'::getDetailKartuStokToko');
            $routes->post('getDataStokBarangGudangToko', $functionRoute.'::getDataStokBarangGudangToko');
            $routes->post('saveWorkOrderStokOpnameGudangToko', $functionRoute.'::saveWorkOrderStokOpnameGudangToko');
        });
    });

    $routes->group('laporan', ['filter' => 'auth:mustBeLoggedIn'], function($routes) {
        $routes->group('pembelianBarang', function($routes) {
            $functionRoute =   'ERP\Laporan\PembelianBarang';
            $routes->post('getDataPembelianBarang', $functionRoute.'::getDataPembelianBarang');
        });

        $routes->group('persediaanBarang', function($routes) {
            $functionRoute =   'ERP\Laporan\PersediaanBarang';
            $routes->post('getDataPersediaanBarangGudang', $functionRoute.'::getDataPersediaanBarangGudang');
            $routes->post('getDataPersediaanBarangToko', $functionRoute.'::getDataPersediaanBarangToko');
        });
    });

    $routes->group('monitoringToko', ['filter' => 'auth:mustBeLoggedIn'], function($routes) {
        $routes->group('dashboard', function($routes) {
            $functionRoute =   'ERP\MonitoringToko\Dashboard';
            $routes->post('getDataDashboard', $functionRoute.'::getDataDashboard');
        });

        $routes->group('monitoringPenjualan', function($routes) {
            $functionRoute =   'ERP\MonitoringToko\MonitoringPenjualan';
            $routes->post('getDataMonitoringPenjualan', $functionRoute.'::getDataMonitoringPenjualan');
        });

        $routes->group('monitoringStok', function($routes) {
            $functionRoute =   'ERP\MonitoringToko\MonitoringStok';
            $routes->post('getDataMonitoringStok', $functionRoute.'::getDataMonitoringStok');
        });
    });

    $routes->group('userSettings', ['filter' => 'auth:mustBeLoggedIn'], function($routes) {
        $routes->group('levelMenu', function($routes) {
            $functionRoute =   'ERP\UserSettings\LevelMenu';
            $routes->post('getList', $functionRoute.'::getList');
            $routes->post('getDetail', $functionRoute.'::getDetail');
            $routes->post('addLevel', $functionRoute.'::addLevel');
            $routes->post('saveLevelDetailAndMenuList', $functionRoute.'::saveLevelDetailAndMenuList');
        });

        $routes->group('userAdmin', function($routes) {
            $functionRoute =   'ERP\UserSettings\UserAdmin';
            $routes->post('getList', $functionRoute.'::getList');
            $routes->post('saveData', $functionRoute.'::saveData');
            $routes->post('updateStatus', $functionRoute.'::updateStatus');
        });
    });
});

$routes->group('warehouse', ['filter' => 'auth:mustBeLoggedIn'], function($routes) {
    $routes->group('dashboard', ['filter' => 'auth:mustBeLoggedIn'], function($routes) {
        $functionRoute =   'WH\Dashboard';
        $routes->post('getDataDashboard', $functionRoute.'::getDataDashboard');
    });

    $routes->group('stok', ['filter' => 'auth:mustBeLoggedIn'], function($routes) {
        $routes->group('inboundBarang', ['filter' => 'auth:mustBeLoggedIn'], function($routes) {
            $functionRoute  =   'WH\Stok\InboundBarang';
            $routes->post('getListNotaAktif', $functionRoute.'::getListNotaAktif');
            $routes->post('getDetailNota', $functionRoute.'::getDetailNota');
            $routes->post('saveInboundPerBarang', $functionRoute.'::saveInboundPerBarang');
            $routes->post('getListNotaHistori', $functionRoute.'::getListNotaHistori');
        });

        $routes->group('stokBarang', ['filter' => 'auth:mustBeLoggedIn'], function($routes) {
            $functionRoute =   'WH\Stok\StokBarang';
            $routes->post('getDaftarStokBarangGudang', $functionRoute.'::getDaftarStokBarangGudang');
            $routes->post('getDaftarStokBarangToko', $functionRoute.'::getDaftarStokBarangToko');
        });

        $routes->group('kartuStok', ['filter' => 'auth:mustBeLoggedIn'], function($routes) {
            $functionRoute =   'WH\Stok\KartuStok';
            $routes->post('getDetailKartuStokGudang', $functionRoute.'::getDetailKartuStokGudang');
            $routes->post('getDetailKartuStokToko', $functionRoute.'::getDetailKartuStokToko');
        });

        $routes->group('stokToko', ['filter' => 'auth:mustBeLoggedIn'], function($routes) {
            $functionRoute  =   'WH\Stok\StokToko';
            $routes->post('getDataStokToko', $functionRoute.'::getDataStokToko');
            $routes->post('uploadImagePembayaran', $functionRoute.'::uploadImagePembayaran');
            $routes->post('checkStokBarangMutasi', $functionRoute.'::checkStokBarangMutasi');
            $routes->post('saveNotaMutasiStok', $functionRoute.'::saveNotaMutasiStok');
            $routes->post('getDataNotaPengajuanStok', $functionRoute.'::getDataNotaPengajuanStok');
            $routes->post('getDetailNotaPengajuanStok', $functionRoute.'::getDetailNotaPengajuanStok');
            $routes->post('downloadPDFCheckListStok', $functionRoute.'::downloadPDFCheckListStok');
            $routes->post('saveProsesNotaPengajuanStok', $functionRoute.'::saveProsesNotaPengajuanStok');
            $routes->post('getDataHistoryNotaStok', $functionRoute.'::getDataHistoryNotaStok');
            $routes->post('getDetailHistoryNotaStok', $functionRoute.'::getDetailHistoryNotaStok');
        });

        $routes->group('stokOpname', ['filter' => 'auth:mustBeLoggedIn'], function($routes) {
            $functionRoute =   'WH\Stok\StokOpname';
            $routes->post('getListDataStokOpname', $functionRoute.'::getListDataStokOpname');
            $routes->post('checkStokSaveOpnameBarang', $functionRoute.'::checkStokSaveOpnameBarang');
            $routes->post('updatePenjelasanStokOpname', $functionRoute.'::updatePenjelasanStokOpname');
        });
    });

    $routes->group('tagihan', function($routes) {
        $functionRoute  =   'WH\Tagihan';
        $routes->post('getDaftarTagihanToko', $functionRoute.'::getDaftarTagihanToko');
        $routes->post('savePelunasanTagihan', $functionRoute.'::savePelunasanTagihan');
    });

    $routes->group('suratJalan', function($routes) {
        $functionRoute  =   'WH\SuratJalan';
        $routes->post('getDaftarSuratJalan', $functionRoute.'::getDaftarSuratJalan');
        $routes->post('getDetailSuratJalan', $functionRoute.'::getDetailSuratJalan');
        $routes->post('getDaftarNotaMutasiRekap', $functionRoute.'::getDaftarNotaMutasiRekap');
        $routes->post('getDetailNotaMutasiBarang', $functionRoute.'::getDetailNotaMutasiBarang');
        $routes->post('saveSuratJalan', $functionRoute.'::saveSuratJalan');
        $routes->post('generatePDFSuratJalan', $functionRoute.'::generatePDFSuratJalan');
    });

    $routes->group('laporan', ['filter' => 'auth:mustBeLoggedIn'], function($routes) {
        $routes->group('mutasiTokoGrosir', function($routes) {
            $functionRoute =   'WH\Laporan\MutasiTokoGrosir';
            $routes->post('getLaporanRekapPerTanggal', $functionRoute.'::getLaporanRekapPerTanggal');
            $routes->post('getLaporanRekapPerToko', $functionRoute.'::getLaporanRekapPerToko');
            $routes->post('getLaporanRekapPerNota', $functionRoute.'::getLaporanRekapPerNota');
            $routes->post('getLaporanDetailPerNota', $functionRoute.'::getLaporanDetailPerNota');
            $routes->post('getLaporanRekapPerBarang', $functionRoute.'::getLaporanRekapPerBarang');
            $routes->post('getLaporanDetailPerBarang', $functionRoute.'::getLaporanDetailPerBarang');
        });
        $routes->group('mutasiBarang', function($routes) {
            $functionRoute =   'WH\Laporan\MutasiBarang';
            $routes->post('getLaporanDetailMutasiBarang', $functionRoute.'::getLaporanDetailMutasiBarang');
        });
        $routes->group('tagihan', function($routes) {
            $functionRoute =   'WH\Laporan\Tagihan';
            $routes->post('getLaporanDetailTagihan', $functionRoute.'::getLaporanDetailTagihan');
        });
    });
});

$routes->group('pos', ['filter' => 'auth:mustBeLoggedIn'], function($routes) {
    $routes->group('dashboard', ['filter' => 'auth:mustBeLoggedIn'], function($routes) {
        $functionRoute =   'POS\Dashboard';
        $routes->post('getDataDashboard', $functionRoute.'::getDataDashboard');
    });
    
    $routes->group('master', ['filter' => 'auth:mustBeLoggedIn'], function($routes) {
        $routes->group('customer', function($routes) {
            $functionRoute  =   'POS\Master\Customer';
            $routes->post('getList', $functionRoute.'::getList');
            $routes->post('saveDataCustomer', $functionRoute.'::saveData');
        });
    });

    $routes->group('stok', ['filter' => 'auth:mustBeLoggedIn'], function($routes) {
        $routes->group('stokBarang', ['filter' => 'auth:mustBeLoggedIn'], function($routes) {
            $functionRoute =   'POS\Stok\StokBarang';
            $routes->post('getDaftarStokBarang', $functionRoute.'::getDaftarStokBarang');
        });

        $routes->group('kartuStok', ['filter' => 'auth:mustBeLoggedIn'], function($routes) {
            $functionRoute =   'POS\Stok\KartuStok';
            $routes->post('getDetailKartuStok', $functionRoute.'::getDetailKartuStok');
        });

        $routes->group('pengaturanStok', ['filter' => 'auth:mustBeLoggedIn'], function($routes) {
            $functionRoute =   'POS\Stok\PengaturanStok';
            $routes->post('getListBarangStokPenjualan', $functionRoute.'::getListBarangStokPenjualan');
            $routes->post('saveRequestStok', $functionRoute.'::saveRequestStok');
            $routes->post('getListNotaPenerimaanStokAktif', $functionRoute.'::getListNotaPenerimaanStokAktif');
            $routes->post('getDetailNotaPenerimaanStokAktif', $functionRoute.'::getDetailNotaPenerimaanStokAktif');
            $routes->post('saveInboundStokPerBarang', $functionRoute.'::saveInboundStokPerBarang');
            $routes->post('getDataHistoryNotaStok', $functionRoute.'::getDataHistoryNotaStok');
            $routes->post('getDetailHistoryNotaStok', $functionRoute.'::getDetailHistoryNotaStok');
        });

        $routes->group('stokOpname', ['filter' => 'auth:mustBeLoggedIn'], function($routes) {
            $functionRoute =   'POS\Stok\StokOpname';
            $routes->post('getListDataStokOpname', $functionRoute.'::getListDataStokOpname');
            $routes->post('checkStokSaveOpnameBarang', $functionRoute.'::checkStokSaveOpnameBarang');
            $routes->post('updatePenjelasanStokOpname', $functionRoute.'::updatePenjelasanStokOpname');
        });
    });

    $routes->group('penjualan', ['filter' => 'auth:mustBeLoggedIn'], function($routes) {
        $functionRoute =   'POS\Penjualan';
        $routes->post('getListBarang', $functionRoute.'::getListBarang');
        $routes->post('getListPaket', $functionRoute.'::getListPaket');
        $routes->post('getDataStokHargaJualBarang', $functionRoute.'::getDataStokHargaJualBarang');
        $routes->post('getDetailStokHargaJualPaket', $functionRoute.'::getDetailStokHargaJualPaket');
        $routes->post('savePenjualan', $functionRoute.'::savePenjualan');
        $routes->post('getListCustomer', $functionRoute.'::getListCustomer');
        $routes->post('getRingkasanPenjualanDataCustomer', $functionRoute.'::getRingkasanPenjualanDataCustomer');
        $routes->post('addNewCustomer', $functionRoute.'::addNewCustomer');
        $routes->post('savePenjualanBarang', $functionRoute.'::savePenjualanBarang');
    });

    $routes->group('laporan', ['filter' => 'auth:mustBeLoggedIn'], function($routes) {
        $routes->group('pembelianBarang', function($routes) {
            $functionRoute =   'POS\Laporan\PembelianBarang';
            $routes->post('getDataPembelianBarang', $functionRoute.'::getDataPembelianBarang');
        });
        $routes->group('penjualan', function($routes) {
            $functionRoute =   'POS\Laporan\Penjualan';
            $routes->post('getLaporanRekapPerTanggal', $functionRoute.'::getLaporanRekapPerTanggal');
            $routes->post('getLaporanRekapPerNota', $functionRoute.'::getLaporanRekapPerNota');
            $routes->post('getLaporanDetailPerNota', $functionRoute.'::getLaporanDetailPerNota');
            $routes->post('getLaporanRekapPerBarang', $functionRoute.'::getLaporanRekapPerBarang');
            $routes->post('getLaporanDetailPerBarang', $functionRoute.'::getLaporanDetailPerBarang');
        });
        $routes->group('mutasiBarang', function($routes) {
            $functionRoute =   'POS\Laporan\MutasiBarang';
            $routes->post('getLaporanDetailMutasiBarang', $functionRoute.'::getLaporanDetailMutasiBarang');
        });
        $routes->group('tagihan', function($routes) {
            $functionRoute =   'POS\Laporan\Tagihan';
            $routes->post('getLaporanDetailTagihan', $functionRoute.'::getLaporanDetailTagihan');
        });
    });
});

//EXCEL, PDF, OTHER LINK ROUTE
$routes->group('erp', [], function($routes) {
    $routes->group('stok', [], function($routes) {
        $routes->group('stokBarang', function($routes) {
            $functionRoute  =   'ERP\Stok\StokBarang';
            $routes->get('excelDataStokGudang/(:any)', $functionRoute.'::excelDataStokGudang/$1');
            $routes->get('excelDataStokToko/(:any)', $functionRoute.'::excelDataStokToko/$1');
        });

        $routes->group('pengaturanHargaJual', function($routes) {
            $functionRoute  =   'ERP\Stok\PengaturanHargaJual';
            $routes->get('excelDataHargaJualRetail/(:any)', $functionRoute.'::excelDataHargaJualRetail/$1');
            $routes->get('excelDataHargaJualGrosir/(:any)', $functionRoute.'::excelDataHargaJualGrosir/$1');
        });
    });

    $routes->group('laporan', [], function($routes) {
        $routes->group('pembelian', function($routes) {
            $functionRoute =   'ERP\Laporan\PembelianBarang';
            $routes->get('excelDataPembelianBarang/(:any)', $functionRoute.'::excelDataPembelianBarang/$1');
        });

        $routes->group('persediaanBarang', function($routes) {
            $functionRoute =   'ERP\Laporan\PersediaanBarang';
            $routes->get('excelDataPersediaanBarangGudang/(:any)', $functionRoute.'::excelDataPersediaanBarangGudang/$1');
            $routes->get('excelDataPersediaanBarangToko/(:any)', $functionRoute.'::excelDataPersediaanBarangToko/$1');
        });
    });
});

$routes->group('wh', [], function($routes) {
    $routes->group('stok', [], function($routes) {
        $routes->group('stokBarang', function($routes) {
            $functionRoute =   'WH\Stok\StokBarang';
            $routes->get('excelDataStokGudang/(:any)', $functionRoute.'::excelDataStokGudang/$1');
            $routes->get('excelDataStokToko/(:any)', $functionRoute.'::excelDataStokToko/$1');
        });
    });
});

$routes->group('pos', [], function($routes) {
    $routes->group('laporan', [], function($routes) {
        $routes->group('pembelian', function($routes) {
            $functionRoute =   'POS\Laporan\PembelianBarang';
            $routes->get('excelDataPembelianBarang/(:any)', $functionRoute.'::excelDataPembelianBarang/$1');
        });
        $routes->group('penjualan', function($routes) {
            $functionRoute =   'POS\Laporan\Penjualan';
            $routes->get('printNotaPenjualanRetail/(:any)', $functionRoute.'::printNotaPenjualanRetail/$1');
            $routes->get('excelRekapPerTanggal/(:any)', $functionRoute.'::excelRekapPerTanggal/$1');
            $routes->get('excelRekapPerNota/(:any)', $functionRoute.'::excelRekapPerNota/$1');
            $routes->get('excelDetailPerNota/(:any)', $functionRoute.'::excelDetailPerNota/$1');
            $routes->get('excelRekapPerBarang/(:any)', $functionRoute.'::excelRekapPerBarang/$1');
            $routes->get('excelDetailPerBarang/(:any)', $functionRoute.'::excelDetailPerBarang/$1');
        });
        $routes->group('mutasiBarang', function($routes) {
            $functionRoute =   'POS\Laporan\MutasiBarang';
            $routes->get('excelDataMutasiBarang/(:any)', $functionRoute.'::excelDataMutasiBarang/$1');
        });
        $routes->group('tagihan', function($routes) {
            $functionRoute =   'POS\Laporan\Tagihan';
            $routes->get('excelDataTagihan/(:any)', $functionRoute.'::excelDataTagihan/$1');
        });
    });
});

//TEST ROUTE
$routes->group('warehouse', [], function($routes) {
    $routes->group('stok', [], function($routes) {
        $routes->group('stokToko', [], function($routes) {
            $functionRoute =   'WH\Stok\StokToko';
            $routes->get('downloadPDFCheckListStok', $functionRoute.'::downloadPDFCheckListStok');
            $routes->get('generatePDFFakturPenjualan', $functionRoute.'::generatePDFFakturPenjualan');
        });
    });
    $routes->group('suratJalan', [], function($routes) {
        $functionRoute =   'WH\SuratJalan';
        $routes->get('generatePDFSuratJalan', $functionRoute.'::generatePDFSuratJalan');
    });
});

/*
 * --------------------------------------------------------------------
 * Additional Routing
 * --------------------------------------------------------------------
 *
 * There will often be times that you need additional routing and you
 * need it to be able to override any defaults in this file. Environment
 * based routes is one such time. require() additional route files here
 * to make that happen.
 *
 * You will have access to the $routes object within that file without
 * needing to reload it.
 */
if (is_file(APPPATH . 'Config/' . ENVIRONMENT . '/Routes.php')) {
    require APPPATH . 'Config/' . ENVIRONMENT . '/Routes.php';
}
