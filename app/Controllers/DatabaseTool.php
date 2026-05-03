<?php

namespace App\Controllers;

use Config\Services;
use Exception;

class DatabaseTool extends BaseController
{
    public function migrate()
    {
        try {
            $migrate = \Config\Services::migrations();
            $migrate->latest();
            return "Migration executed successfully!";
        } catch (Exception $e) {
            return "Internal error : " . $e->getMessage();
        }
    }

    public function seed($name = null)
    {
        if (is_null($name)) return "Please provide a seeder name.";
        try {
            $seeder = \Config\Database::seeder();
            $seeder->call($name);
            return "Seeding [$name] executed successfully!";
        } catch (Exception $e) {
            return "Internal error : " . $e->getMessage();
        }
    }

    public function rollback()
    {
        try {
            $migrate = \Config\Services::migrations();
            $migrate->regress(-1);
            return "Rollback executed successfully!";
        } catch (Exception $e) {
            return "Internal error : " . $e->getMessage();
        }
    }
}