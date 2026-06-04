<?php

require_once ROOTPATH . 'vendor/autoload.php';
use Dotenv\Dotenv;
$dotenv	=	Dotenv::createImmutable(ROOTPATH);
$dotenv->load();

/*
 | --------------------------------------------------------------------
 | App Namespace
 | --------------------------------------------------------------------
 |
 | This defines the default Namespace that is used throughout
 | CodeIgniter to refer to the Application directory. Change
 | this constant to change the namespace that all application
 | classes should use.
 |
 | NOTE: changing this will require manually modifying the
 | existing namespaces of App\* namespaced-classes.
 */
defined('APP_NAMESPACE') || define('APP_NAMESPACE', 'App');

/*
 | --------------------------------------------------------------------------
 | Composer Path
 | --------------------------------------------------------------------------
 |
 | The path that Composer's autoload file is expected to live. By default,
 | the vendor folder is in the Root directory, but you can customize that here.
 */
defined('COMPOSER_PATH') || define('COMPOSER_PATH', ROOTPATH . '../vendor/autoload.php');

/*
 |--------------------------------------------------------------------------
 | Timing Constants
 |--------------------------------------------------------------------------
 |
 | Provide simple ways to work with the myriad of PHP functions that
 | require information to be in seconds.
 */
defined('SECOND') || define('SECOND', 1);
defined('MINUTE') || define('MINUTE', 60);
defined('HOUR')   || define('HOUR', 3600);
defined('DAY')    || define('DAY', 86400);
defined('WEEK')   || define('WEEK', 604800);
defined('MONTH')  || define('MONTH', 2_592_000);
defined('YEAR')   || define('YEAR', 31_536_000);
defined('DECADE') || define('DECADE', 315_360_000);

/*
 | --------------------------------------------------------------------------
 | Exit Status Codes
 | --------------------------------------------------------------------------
 |
 | Used to indicate the conditions under which the script is exit()ing.
 | While there is no universal standard for error codes, there are some
 | broad conventions.  Three such conventions are mentioned below, for
 | those who wish to make use of them.  The CodeIgniter defaults were
 | chosen for the least overlap with these conventions, while still
 | leaving room for others to be defined in future versions and user
 | applications.
 |
 | The three main conventions used for determining exit status codes
 | are as follows:
 |
 |    Standard C/C++ Library (stdlibc):
 |       http://www.gnu.org/software/libc/manual/html_node/Exit-Status.html
 |       (This link also contains other GNU-specific conventions)
 |    BSD sysexits.h:
 |       http://www.gsp.com/cgi-bin/man.cgi?section=3&topic=sysexits
 |    Bash scripting:
 |       http://tldp.org/LDP/abs/html/exitcodes.html
 |
 */
defined('EXIT_SUCCESS')        || define('EXIT_SUCCESS', 0);        // no errors
defined('EXIT_ERROR')          || define('EXIT_ERROR', 1);          // generic error
defined('EXIT_CONFIG')         || define('EXIT_CONFIG', 3);         // configuration error
defined('EXIT_UNKNOWN_FILE')   || define('EXIT_UNKNOWN_FILE', 4);   // file not found
defined('EXIT_UNKNOWN_CLASS')  || define('EXIT_UNKNOWN_CLASS', 5);  // unknown class
defined('EXIT_UNKNOWN_METHOD') || define('EXIT_UNKNOWN_METHOD', 6); // unknown class member
defined('EXIT_USER_INPUT')     || define('EXIT_USER_INPUT', 7);     // invalid user input
defined('EXIT_DATABASE')       || define('EXIT_DATABASE', 8);       // database error
defined('EXIT__AUTO_MIN')      || define('EXIT__AUTO_MIN', 9);      // lowest automatically-assigned error code
defined('EXIT__AUTO_MAX')      || define('EXIT__AUTO_MAX', 125);    // highest automatically-assigned error code

/**
 * @deprecated Use \CodeIgniter\Events\Events::PRIORITY_LOW instead.
 */
define('EVENT_PRIORITY_LOW', 200);

/**
 * @deprecated Use \CodeIgniter\Events\Events::PRIORITY_NORMAL instead.
 */
define('EVENT_PRIORITY_NORMAL', 100);

/**
 * @deprecated Use \CodeIgniter\Events\Events::PRIORITY_HIGH instead.
 */
define('EVENT_PRIORITY_HIGH', 10);
$url			=	!empty($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : "-";
$domain			=	explode(".", $url);
$subdomain		=	$domain[0];
$productionURL	=	$subdomain == "roketo" ? true : false;

$arrProdusenDistributor =	[
    ["ID"=>"1", "VALUE"=>"Produsen"],
    ["ID"=>"2", "VALUE"=>"Distributor"]
];

$arrHours       =   [];
for($i=0; $i<24; $i++){
    $arrHours[] =   array("ID"=>str_pad($i, 2, '0', STR_PAD_LEFT), "VALUE"=>str_pad($i, 2, '0', STR_PAD_LEFT));
}
$strArrHour =   implode(',', array_column($arrHours, 'VALUE'));

$arrMinutes =   [];
for($i=0; $i<60; $i++){
    $arrMinutes[]   =   array("ID"=>str_pad($i, 2, '0', STR_PAD_LEFT), "VALUE"=>str_pad($i, 2, '0', STR_PAD_LEFT));
}

$arrMinuteInterval   =	[
    ["ID"=>"00", "VALUE"=>"00"],
    ["ID"=>"15", "VALUE"=>"15"],
    ["ID"=>"30", "VALUE"=>"30"],
    ["ID"=>"45", "VALUE"=>"45"],
];
$strArrMinuteInterval=  implode(',', array_column($arrMinuteInterval, 'VALUE'));

$arrMonth   =	[
    ["ID"=>"01", "VALUE"=>"Januari"],
    ["ID"=>"02", "VALUE"=>"Februari"],
    ["ID"=>"03", "VALUE"=>"Maret"],
    ["ID"=>"04", "VALUE"=>"April"],
    ["ID"=>"05", "VALUE"=>"Mei"],
    ["ID"=>"06", "VALUE"=>"Juni"],
    ["ID"=>"07", "VALUE"=>"Juli"],
    ["ID"=>"08", "VALUE"=>"Agustus"],
    ["ID"=>"09", "VALUE"=>"September"],
    ["ID"=>"10", "VALUE"=>"Oktober"],
    ["ID"=>"11", "VALUE"=>"November"],
    ["ID"=>"12", "VALUE"=>"Desember"]
];

$arrMonthBahasa =	[
    1 => "Januari",
    2 => "Februari",
    3 => "Maret",
    4 => "April",
    5 => "Mei",
    6 => "Juni",
    7 => "Juli",
    8 => "Agustus",
    9 => "September",
    10 => "Oktober",
    11 => "November",
    12 => "Desember"
];

$minYear    =	2025;
$thisYear   =	date('Y');
$last1Year  =	date("Y", strtotime("-1 year"));
$last2Year  =	date("Y", strtotime("-2 year"));
$arrYear    =	[
    ["ID"=>$thisYear, "VALUE"=>$thisYear]
];

if($last1Year > $minYear) $arrYear[]=    ["ID"=>$last1Year, "VALUE"=>$last1Year];
if($last2Year > $minYear) $arrYear[]=    ["ID"=>$last2Year, "VALUE"=>$last2Year];

defined('APP_IS_DEVELOPMENT')                           || define('APP_IS_DEVELOPMENT', $_ENV['CI_ENVIRONMENT'] == 'development' ? true : false);
defined('APP_NAME')                                     || define('APP_NAME', $_ENV['APP_NAME'] ?: 'Roketo | ERP');
defined('APP_NAME_FORMAL')                              || define('APP_NAME_FORMAL', $_ENV['APP_NAME_FORMAL'] ?: 'Roketo | Enterprise Resource Planning');
defined('APP_TIMEZONE')                                 || define('APP_TIMEZONE', $_ENV['APP_TIMEZONE'] ?: 'Asia/Jakarta');
defined('APP_MAIN_DATABASE_NAME')                       || define('APP_MAIN_DATABASE_NAME', $_ENV['APP_MAIN_DATABASE_NAME'] ?: 'db_default');
defined('APP_MIN_YEAR')                                 || define('APP_MIN_YEAR', $minYear);
defined('APP_DATABASE_TOOL_SECRET_KEY')                 || define('APP_DATABASE_TOOL_SECRET_KEY', $_ENV['APP_DATABASE_TOOL_SECRET_KEY'] ?: 'secretkey');
defined('APP_EXPORT_EXCEL_DEFAULT_PASSWORD')            || define('APP_EXPORT_EXCEL_DEFAULT_PASSWORD', $_ENV['APP_EXPORT_EXCEL_DEFAULT_PASSWORD'] ?: 'password');
defined('MAX_INACTIVE_SESSION_MINUTES')                 || define('MAX_INACTIVE_SESSION_MINUTES', $_ENV['MAX_INACTIVE_SESSION_MINUTES'] ?: 60);
defined('MAX_BARANG_SKU_STOK_OPNAME')                   || define('MAX_BARANG_SKU_STOK_OPNAME', $_ENV['MAX_BARANG_SKU_STOK_OPNAME'] ?: 10);
defined('LOG_USER_REQUEST')                             || define('LOG_USER_REQUEST', $_ENV['LOG_USER_REQUEST'] ?: false);

defined('PRODUCTION_URL')						        || define('PRODUCTION_URL', $productionURL);
defined('BASE_URL')                                     || define('BASE_URL', $_ENV['BASE_URL'] ?: 'https://example.com/');
defined('BASE_URL_ERP_APPS')                            || define('BASE_URL_ERP_APPS', $_ENV['BASE_URL_ERP_APPS'] ?: 'https://example.com/');
defined('BASE_URL_WH_APPS')                             || define('BASE_URL_WH_APPS', $_ENV['BASE_URL_WH_APPS'] ?: 'https://example.com/');
defined('BASE_URL_POS_APPS')                            || define('BASE_URL_POS_APPS', $_ENV['BASE_URL_POS_APPS'] ?: 'https://example.com/');

defined('URL_LOGO_PERUSAHAAN')                          || define('URL_LOGO_PERUSAHAAN', BASE_URL.$_ENV['URL_PATH_LOGO_PERUSAHAAN'] ?: BASE_URL.'foto/logoPerusahaan/');
defined('URL_FOTO_BARANG')                              || define('URL_FOTO_BARANG', BASE_URL.$_ENV['URL_PATH_FOTO_BARANG'] ?: BASE_URL.'foto/barang/');
defined('URL_BUKTI_PEMBAYARAN')                         || define('URL_BUKTI_PEMBAYARAN', BASE_URL.$_ENV['URL_PATH_FOTO_PEMBAYARAN'] ?: BASE_URL.'foto/pembayaran/');

defined('URL_EXCEL_ERP_DATA_PEMBELIAN_BARANG')          || define('URL_EXCEL_ERP_DATA_PEMBELIAN_BARANG', $_ENV['URL_EXCEL_ERP_DATA_PEMBELIAN_BARANG'] ?: 'erp/laporan/pembelian/excelDataPembelianBarang/');
defined('URL_EXCEL_ERP_DATA_PERSEDIAAN_BARANG_GUDANG')  || define('URL_EXCEL_ERP_DATA_PERSEDIAAN_BARANG_GUDANG', $_ENV['URL_EXCEL_ERP_DATA_PERSEDIAAN_BARANG_GUDANG'] ?: 'erp/laporan/persediaanBarang/excelDataPersediaanBarangGudang/');
defined('URL_EXCEL_ERP_DATA_PERSEDIAAN_BARANG_TOKO')    || define('URL_EXCEL_ERP_DATA_PERSEDIAAN_BARANG_TOKO', $_ENV['URL_EXCEL_ERP_DATA_PERSEDIAAN_BARANG_TOKO'] ?: 'erp/laporan/persediaanBarang/excelDataPersediaanBarangToko/');
defined('URL_EXCEL_ERP_DATA_STOK_GUDANG')               || define('URL_EXCEL_ERP_DATA_STOK_GUDANG', $_ENV['URL_EXCEL_ERP_DATA_STOK_GUDANG'] ?: 'erp/stok/stokBarang/excelDataStokGudang/');
defined('URL_EXCEL_ERP_DATA_STOK_TOKO')                 || define('URL_EXCEL_ERP_DATA_STOK_TOKO', $_ENV['URL_EXCEL_ERP_DATA_STOK_TOKO'] ?: 'erp/stok/stokBarang/excelDataStokToko/');
defined('URL_EXCEL_ERP_DATA_HARGA_BARANG_RETAIL')       || define('URL_EXCEL_ERP_DATA_HARGA_BARANG_RETAIL', $_ENV['URL_EXCEL_ERP_DATA_HARGA_BARANG_RETAIL'] ?: 'erp/stok/pengaturanHargaJual/excelDataHargaJualRetail/');

defined('URL_EXCEL_WH_DATA_STOK_GUDANG')                || define('URL_EXCEL_WH_DATA_STOK_GUDANG', $_ENV['URL_EXCEL_WH_DATA_STOK_GUDANG'] ?: 'wh/stok/stokBarang/excelDataStokGudang/');
defined('URL_EXCEL_WH_DATA_STOK_TOKO')                  || define('URL_EXCEL_WH_DATA_STOK_TOKO', $_ENV['URL_EXCEL_WH_DATA_STOK_TOKO'] ?: 'wh/stok/stokBarang/excelDataStokToko/');

defined('URL_PRINT_NOTA_PENJUALAN_RETAIL')              || define('URL_PRINT_NOTA_PENJUALAN_RETAIL', $_ENV['URL_PRINT_NOTA_PENJUALAN_RETAIL'] ?: 'pos/laporan/penjualan/printNotaPenjualanRetail/');
defined('URL_EXCEL_DATA_PEMBELIAN_BARANG')              || define('URL_EXCEL_DATA_PEMBELIAN_BARANG', $_ENV['URL_EXCEL_DATA_PEMBELIAN_BARANG'] ?: 'pos/laporan/pembelian/excelDataPembelianBarang/');
defined('URL_EXCEL_REKAP_PENJUALAN_RETAIL_PER_TANGGAL') || define('URL_EXCEL_REKAP_PENJUALAN_RETAIL_PER_TANGGAL', $_ENV['URL_EXCEL_REKAP_PENJUALAN_RETAIL_PER_TANGGAL'] ?: 'pos/laporan/penjualan/excelRekapPerTanggal/');
defined('URL_EXCEL_REKAP_PENJUALAN_RETAIL_PER_NOTA')    || define('URL_EXCEL_REKAP_PENJUALAN_RETAIL_PER_NOTA', $_ENV['URL_EXCEL_REKAP_PENJUALAN_RETAIL_PER_NOTA'] ?: 'pos/laporan/penjualan/excelRekapPerNota/');
defined('URL_EXCEL_DETAIL_PENJUALAN_RETAIL_PER_NOTA')   || define('URL_EXCEL_DETAIL_PENJUALAN_RETAIL_PER_NOTA', $_ENV['URL_EXCEL_DETAIL_PENJUALAN_RETAIL_PER_NOTA'] ?: 'pos/laporan/penjualan/excelDetailPerNota/');
defined('URL_EXCEL_REKAP_PENJUALAN_RETAIL_PER_BARANG')  || define('URL_EXCEL_REKAP_PENJUALAN_RETAIL_PER_BARANG', $_ENV['URL_EXCEL_REKAP_PENJUALAN_RETAIL_PER_BARANG'] ?: 'pos/laporan/penjualan/excelRekapPerBarang/');
defined('URL_EXCEL_DETAIL_PENJUALAN_RETAIL_PER_BARANG') || define('URL_EXCEL_DETAIL_PENJUALAN_RETAIL_PER_BARANG', $_ENV['URL_EXCEL_DETAIL_PENJUALAN_RETAIL_PER_BARANG'] ?: 'pos/laporan/penjualan/excelDetailPerBarang/');
defined('URL_EXCEL_DATA_MUTASI_BARANG')                 || define('URL_EXCEL_DATA_MUTASI_BARANG', $_ENV['URL_EXCEL_DATA_MUTASI_BARANG'] ?: 'pos/laporan/mutasiBarang/excelDataMutasiBarang/');
defined('URL_EXCEL_DATA_TAGIHAN')                       || define('URL_EXCEL_DATA_TAGIHAN', $_ENV['URL_EXCEL_DATA_TAGIHAN'] ?: 'pos/laporan/tagihan/excelDataTagihan/');

defined('OPTION_PRODUSEN_DISTRIBUTOR')                  || define('OPTION_PRODUSEN_DISTRIBUTOR', $arrProdusenDistributor);
defined('OPTION_HOURS')						            || define('OPTION_HOURS', $arrHours);
defined('OPTION_HOUR_STRARR')                           || define('OPTION_HOUR_STRARR', $strArrHour);
defined('OPTION_MINUTES')                               || define('OPTION_MINUTES', $arrMinutes);
defined('OPTION_MINUTEINTERVAL')                        || define('OPTION_MINUTEINTERVAL', $arrMinuteInterval);
defined('OPTION_MINUTEINTERVAL_STRARR')                 || define('OPTION_MINUTEINTERVAL_STRARR', $strArrMinuteInterval);
defined('OPTION_MONTH')						            || define('OPTION_MONTH', $arrMonth);
defined('OPTION_MONTH_BAHASA')                          || define('OPTION_MONTH_BAHASA', $arrMonthBahasa);
defined('OPTION_YEAR')						            || define('OPTION_YEAR', $arrYear);

defined('PATH_STORAGE')						            || define('PATH_STORAGE', $_ENV['PATH_STORAGE'] ?: 'storage/');
defined('PATH_STORAGE_LOGO_PERUSAHAAN')                 || define('PATH_STORAGE_LOGO_PERUSAHAAN', PATH_STORAGE.$_ENV['PATH_STORAGE_LOGO_PERUSAHAAN'] ?: PATH_STORAGE.'logoPerusahaan/');
defined('PATH_STORAGE_FOTO_BARANG')                     || define('PATH_STORAGE_FOTO_BARANG', PATH_STORAGE.$_ENV['PATH_STORAGE_FOTO_BARANG'] ?: PATH_STORAGE.'barang/');
defined('PATH_STORAGE_FOTO_PEMBAYARAN')                 || define('PATH_STORAGE_FOTO_PEMBAYARAN', PATH_STORAGE.$_ENV['PATH_STORAGE_FOTO_PEMBAYARAN'] ?: PATH_STORAGE.'pembayaran/');
defined('PATH_STORAGE_FILE_FAKTUR_PENJUALAN')           || define('PATH_STORAGE_FILE_FAKTUR_PENJUALAN', PATH_STORAGE.$_ENV['PATH_STORAGE_FILE_FAKTUR_PENJUALAN'] ?: PATH_STORAGE.'fakturPenjualan/');