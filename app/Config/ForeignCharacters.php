<?php

namespace Config;

use CodeIgniter\Config\ForeignCharacters as BaseForeignCharacters;

class ForeignCharacters extends BaseForeignCharacters
{
    public $characterList = [
        "ä" => "ae",
        "ö" => "oe",
        "ü" => "ue",
        "Ä" => "Ae",
        "Ü" => "Ue",
        "Ö" => "Oe",
        "'" => "`"
    ];
}
