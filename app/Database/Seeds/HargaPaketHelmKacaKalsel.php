<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;
use App\Models\MainOperation;

class HargaPaketHelmKacaKalsel extends Seeder
{
    public function run()
    {
        $mainOperation      =   new MainOperation();
        $arrIdToko          =   [400,401,402,403,406,407,409];
        $arrDataKombinasi   =   [
            [
                'arrIdBarangSKUHelm'    =>  [50869,50870,50871,50872,50873,50874,50875,50876,50877,50878,50879,50880,50887,50888,50889,50890,50891,50892,50893,50894],
                'arrIdBarangSKUKaca'    =>  [51401,51402,51403,51404,51405,51406,51407,51408,51409,51410],
                'hargaSKUHelm'          =>  184500,
                'hargaSKUKaca'          =>  75500
            ],
            [
                'arrIdBarangSKUHelm'    =>  [50869,50870,50871,50872,50873,50874,50875,50876,50877,50878,50879,50880,50887,50888,50889,50890,50891,50892,50893,50894],
                'arrIdBarangSKUKaca'    =>  [51500,51501,51502,51503],
                'hargaSKUHelm'          =>  195000,
                'hargaSKUKaca'          =>  65000
            ],
            [
                'arrIdBarangSKUHelm'    =>  [50869,50870,50871,50872,50873,50874,50875,50876,50877,50878,50879,50880,50887,50888,50889,50890,50891,50892,50893,50894],
                'arrIdBarangSKUKaca'    =>  [51411],
                'hargaSKUHelm'          =>  195000,
                'hargaSKUKaca'          =>  65000
            ],
            [
                'arrIdBarangSKUHelm'    =>  [50869,50870,50871,50872,50873,50874,50875,50876,50877,50878,50879,50880,50887,50888,50889,50890,50891,50892,50893,50894],
                'arrIdBarangSKUKaca'    =>  [50015],
                'hargaSKUHelm'          =>  200000,
                'hargaSKUKaca'          =>  60000
            ],
            [
                'arrIdBarangSKUHelm'    =>  [50869,50870,50871,50872,50873,50874,50875,50876,50877,50878,50879,50880,50887,50888,50889,50890,50891,50892,50893,50894],
                'arrIdBarangSKUKaca'    =>  [51400],
                'hargaSKUHelm'          =>  180000,
                'hargaSKUKaca'          =>  100000
            ],
            [
                'arrIdBarangSKUHelm'    =>  [50869,50870,50871,50872,50873,50874,50875,50876,50877,50878,50879,50880,50887,50888,50889,50890,50891,50892,50893,50894],
                'arrIdBarangSKUKaca'    =>  [51507],
                'hargaSKUHelm'          =>  190000,
                'hargaSKUKaca'          =>  60000
            ],
            [
                'arrIdBarangSKUHelm'    =>  [50869,50870,50871,50872,50873,50874,50875,50876,50877,50878,50879,50880,50887,50888,50889,50890,50891,50892,50893,50894],
                'arrIdBarangSKUKaca'    =>  [51412,51413],
                'hargaSKUHelm'          =>  190000,
                'hargaSKUKaca'          =>  60000
            ],
            [
                'arrIdBarangSKUHelm'    =>  [50869,50870,50871,50872,50873,50874,50875,50876,50877,50878,50879,50880,50887,50888,50889,50890,50891,50892,50893,50894],
                'arrIdBarangSKUKaca'    =>  [51513,51514,51515],
                'hargaSKUHelm'          =>  190000,
                'hargaSKUKaca'          =>  60000
            ],
            [
                'arrIdBarangSKUHelm'    =>  [50869,50870,50871,50872,50873,50874,50875,50876,50877,50878,50879,50880,50887,50888,50889,50890,50891,50892,50893,50894],
                'arrIdBarangSKUKaca'    =>  [51505,51506],
                'hargaSKUHelm'          =>  190000,
                'hargaSKUKaca'          =>  60000
            ],
            [
                'arrIdBarangSKUHelm'    =>  [50895,50896,50897,50898,50899,50900,50901,50902,50903,50904,50905,50906,50907,50908,50909,50910,50911,50912,50913,50914,50915,50916,50917,50918,50919,50920,50921,50922,50923,50924],
                'arrIdBarangSKUKaca'    =>  [51401,51402,51403,51404,51405,51406,51407,51408,51409,51410],
                'hargaSKUHelm'          =>  175000,
                'hargaSKUKaca'          =>  75000
            ],
            [
                'arrIdBarangSKUHelm'    =>  [50895,50896,50897,50898,50899,50900,50901,50902,50903,50904,50905,50906,50907,50908,50909,50910,50911,50912,50913,50914,50915,50916,50917,50918,50919,50920,50921,50922,50923,50924],
                'arrIdBarangSKUKaca'    =>  [51500,51501,51502,51503],
                'hargaSKUHelm'          =>  185000,
                'hargaSKUKaca'          =>  65000
            ],
            [
                'arrIdBarangSKUHelm'    =>  [50895,50896,50897,50898,50899,50900,50901,50902,50903,50904,50905,50906,50907,50908,50909,50910,50911,50912,50913,50914,50915,50916,50917,50918,50919,50920,50921,50922,50923,50924],
                'arrIdBarangSKUKaca'    =>  [51411],
                'hargaSKUHelm'          =>  185000,
                'hargaSKUKaca'          =>  65000
            ],
            [
                'arrIdBarangSKUHelm'    =>  [50895,50896,50897,50898,50899,50900,50901,50902,50903,50904,50905,50906,50907,50908,50909,50910,50911,50912,50913,50914,50915,50916,50917,50918,50919,50920,50921,50922,50923,50924],
                'arrIdBarangSKUKaca'    =>  [50015],
                'hargaSKUHelm'          =>  185000,
                'hargaSKUKaca'          =>  65000
            ],
            [
                'arrIdBarangSKUHelm'    =>  [50895,50896,50897,50898,50899,50900,50901,50902,50903,50904,50905,50906,50907,50908,50909,50910,50911,50912,50913,50914,50915,50916,50917,50918,50919,50920,50921,50922,50923,50924],
                'arrIdBarangSKUKaca'    =>  [51400],
                'hargaSKUHelm'          =>  170000,
                'hargaSKUKaca'          =>  90000
            ],
            [
                'arrIdBarangSKUHelm'    =>  [50895,50896,50897,50898,50899,50900,50901,50902,50903,50904,50905,50906,50907,50908,50909,50910,50911,50912,50913,50914,50915,50916,50917,50918,50919,50920,50921,50922,50923,50924],
                'arrIdBarangSKUKaca'    =>  [51507],
                'hargaSKUHelm'          =>  175000,
                'hargaSKUKaca'          =>  50000
            ],
            [
                'arrIdBarangSKUHelm'    =>  [50895,50896,50897,50898,50899,50900,50901,50902,50903,50904,50905,50906,50907,50908,50909,50910,50911,50912,50913,50914,50915,50916,50917,50918,50919,50920,50921,50922,50923,50924],
                'arrIdBarangSKUKaca'    =>  [51412,51413],
                'hargaSKUHelm'          =>  175000,
                'hargaSKUKaca'          =>  50000
            ],
            [
                'arrIdBarangSKUHelm'    =>  [50895,50896,50897,50898,50899,50900,50901,50902,50903,50904,50905,50906,50907,50908,50909,50910,50911,50912,50913,50914,50915,50916,50917,50918,50919,50920,50921,50922,50923,50924],
                'arrIdBarangSKUKaca'    =>  [51513,51514,51515],
                'hargaSKUHelm'          =>  175000,
                'hargaSKUKaca'          =>  50000
            ],
            [
                'arrIdBarangSKUHelm'    =>  [50895,50896,50897,50898,50899,50900,50901,50902,50903,50904,50905,50906,50907,50908,50909,50910,50911,50912,50913,50914,50915,50916,50917,50918,50919,50920,50921,50922,50923,50924],
                'arrIdBarangSKUKaca'    =>  [51505,51506],
                'hargaSKUHelm'          =>  175000,
                'hargaSKUKaca'          =>  50000
            ],
            [
                'arrIdBarangSKUHelm'    =>  [50881,50882,50883,50884,50885,50886],
                'arrIdBarangSKUKaca'    =>  [51401,51402,51403,51404,51405,51406,51407,51408,51409,51410],
                'hargaSKUHelm'          =>  185000,
                'hargaSKUKaca'          =>  75000
            ],
            [
                'arrIdBarangSKUHelm'    =>  [50881,50882,50883,50884,50885,50886],
                'arrIdBarangSKUKaca'    =>  [51500,51501,51502,51503],
                'hargaSKUHelm'          =>  195000,
                'hargaSKUKaca'          =>  65000
            ],
            [
                'arrIdBarangSKUHelm'    =>  [50881,50882,50883,50884,50885,50886],
                'arrIdBarangSKUKaca'    =>  [51411],
                'hargaSKUHelm'          =>  195000,
                'hargaSKUKaca'          =>  65000
            ],
            [
                'arrIdBarangSKUHelm'    =>  [50881,50882,50883,50884,50885,50886],
                'arrIdBarangSKUKaca'    =>  [50015],
                'hargaSKUHelm'          =>  200000,
                'hargaSKUKaca'          =>  60000
            ],
            [
                'arrIdBarangSKUHelm'    =>  [50881,50882,50883,50884,50885,50886],
                'arrIdBarangSKUKaca'    =>  [51400],
                'hargaSKUHelm'          =>  180000,
                'hargaSKUKaca'          =>  100000
            ],
            [
                'arrIdBarangSKUHelm'    =>  [50881,50882,50883,50884,50885,50886],
                'arrIdBarangSKUKaca'    =>  [51507],
                'hargaSKUHelm'          =>  190000,
                'hargaSKUKaca'          =>  60000
            ],
            [
                'arrIdBarangSKUHelm'    =>  [50881,50882,50883,50884,50885,50886],
                'arrIdBarangSKUKaca'    =>  [51412,51413],
                'hargaSKUHelm'          =>  190000,
                'hargaSKUKaca'          =>  60000
            ],
            [
                'arrIdBarangSKUHelm'    =>  [50881,50882,50883,50884,50885,50886],
                'arrIdBarangSKUKaca'    =>  [51513,51514,51515],
                'hargaSKUHelm'          =>  190000,
                'hargaSKUKaca'          =>  60000
            ],
            [
                'arrIdBarangSKUHelm'    =>  [50881,50882,50883,50884,50885,50886],
                'arrIdBarangSKUKaca'    =>  [51505,51506],
                'hargaSKUHelm'          =>  190000,
                'hargaSKUKaca'          =>  60000
            ],
            [
                'arrIdBarangSKUHelm'    =>  [50752,50753,50754,50755,50756,50757,50758,50759,50760,50761,50762,50763,50764,50765,50766,50767,50768,50769,50770,50771,50772,50773,50774],
                'arrIdBarangSKUKaca'    =>  [51411],
                'hargaSKUHelm'          =>  110000,
                'hargaSKUKaca'          =>  40000
            ],
            [
                'arrIdBarangSKUHelm'    =>  [50752,50753,50754,50755,50756,50757,50758,50759,50760,50761,50762,50763,50764,50765,50766,50767,50768,50769,50770,50771,50772,50773,50774],
                'arrIdBarangSKUKaca'    =>  [51501,51502],
                'hargaSKUHelm'          =>  110000,
                'hargaSKUKaca'          =>  40000
            ],
            [
                'arrIdBarangSKUHelm'    =>  [50752,50753,50754,50755,50756,50757,50758,50759,50760,50761,50762,50763,50764,50765,50766,50767,50768,50769,50770,50771,50772,50773,50774],
                'arrIdBarangSKUKaca'    =>  [51507],
                'hargaSKUHelm'          =>  110000,
                'hargaSKUKaca'          =>  20000
            ],
            [
                'arrIdBarangSKUHelm'    =>  [50752,50753,50754,50755,50756,50757,50758,50759,50760,50761,50762,50763,50764,50765,50766,50767,50768,50769,50770,50771,50772,50773,50774],
                'arrIdBarangSKUKaca'    =>  [51412,51413],
                'hargaSKUHelm'          =>  110000,
                'hargaSKUKaca'          =>  20000
            ],
            [
                'arrIdBarangSKUHelm'    =>  [50752,50753,50754,50755,50756,50757,50758,50759,50760,50761,50762,50763,50764,50765,50766,50767,50768,50769,50770,50771,50772,50773,50774],
                'arrIdBarangSKUKaca'    =>  [51513,51514,51515],
                'hargaSKUHelm'          =>  110000,
                'hargaSKUKaca'          =>  20000
            ],
            [
                'arrIdBarangSKUHelm'    =>  [50752,50753,50754,50755,50756,50757,50758,50759,50760,50761,50762,50763,50764,50765,50766,50767,50768,50769,50770,50771,50772,50773,50774],
                'arrIdBarangSKUKaca'    =>  [51505,51506],
                'hargaSKUHelm'          =>  110000,
                'hargaSKUKaca'          =>  20000
            ],
            [
                'arrIdBarangSKUHelm'    =>  [50794,50795,50796,50797],
                'arrIdBarangSKUKaca'    =>  [51411],
                'hargaSKUHelm'          =>  125000,
                'hargaSKUKaca'          =>  45000
            ],
            [
                'arrIdBarangSKUHelm'    =>  [50794,50795,50796,50797],
                'arrIdBarangSKUKaca'    =>  [51501,51502],
                'hargaSKUHelm'          =>  125000,
                'hargaSKUKaca'          =>  45000
            ],
            [
                'arrIdBarangSKUHelm'    =>  [50794,50795,50796,50797],
                'arrIdBarangSKUKaca'    =>  [51507],
                'hargaSKUHelm'          =>  125000,
                'hargaSKUKaca'          =>  25000
            ],
            [
                'arrIdBarangSKUHelm'    =>  [50794,50795,50796,50797],
                'arrIdBarangSKUKaca'    =>  [51412,51413],
                'hargaSKUHelm'          =>  125000,
                'hargaSKUKaca'          =>  25000
            ],
            [
                'arrIdBarangSKUHelm'    =>  [50794,50795,50796,50797],
                'arrIdBarangSKUKaca'    =>  [51513,51514,51515],
                'hargaSKUHelm'          =>  125000,
                'hargaSKUKaca'          =>  25000
            ],
            [
                'arrIdBarangSKUHelm'    =>  [50794,50795,50796,50797],
                'arrIdBarangSKUKaca'    =>  [51505,51506],
                'hargaSKUHelm'          =>  125000,
                'hargaSKUKaca'          =>  25000
            ]
        ];

        foreach($arrIdToko as $idToko) {
            foreach($arrDataKombinasi as $dataKombinasi) {
                $arrIdBarangSKUHelm    =   $dataKombinasi['arrIdBarangSKUHelm'];
                $arrIdBarangSKUKaca    =   $dataKombinasi['arrIdBarangSKUKaca'];
                $hargaSKUHelm          =   $dataKombinasi['hargaSKUHelm'];
                $hargaSKUKaca          =   $dataKombinasi['hargaSKUKaca'];

                foreach($arrIdBarangSKUHelm as $idBarangSKUHelm) {
                    $namaSKUHelm =   $mainOperation->getDetailBarangSKU($idBarangSKUHelm)['DESKRIPSI'];
                    foreach($arrIdBarangSKUKaca as $idBarangSKUKaca) {
                        $namaSKUKaca    =   $mainOperation->getDetailBarangSKU($idBarangSKUKaca)['DESKRIPSI'];
                        $procInsertPaket=   $mainOperation->insertDataTable('t_hargaretailpaket', [
                            'IDTOKO'                =>  $idToko,
                            'NAMAHARGARETAILPAKET'  =>  $namaSKUHelm . ' + ' . $namaSKUKaca,
                            'JUMLAHBARANG'          =>  2,
                            'STATUS'                =>  1
                        ]);

                        if($procInsertPaket['status']) {
                            $idHargaRetailPaket    =   $procInsertPaket['insertID'];
                            $mainOperation->insertDataTable('t_hargaretailpaketsku', [
                                'IDHARGARETAILPAKET'=>  $idHargaRetailPaket,
                                'IDBARANGSKU'       =>  $idBarangSKUHelm,
                                'IDBARANGSATUAN'    =>  100,
                                'JUMLAH'            =>  1,
                                'HARGA'             =>  $hargaSKUHelm
                            ]);

                            $mainOperation->insertDataTable('t_hargaretailpaketsku', [
                                'IDHARGARETAILPAKET'=>  $idHargaRetailPaket,
                                'IDBARANGSKU'       =>  $idBarangSKUKaca,
                                'IDBARANGSATUAN'    =>  100,
                                'JUMLAH'            =>  1,
                                'HARGA'             =>  $hargaSKUKaca
                            ]);
                        }
                    }
                }
            }
        }
    }
}
