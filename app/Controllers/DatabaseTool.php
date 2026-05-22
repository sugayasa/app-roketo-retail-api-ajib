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
            echo "Migration executed successfully!";
            exit();
        } catch (Exception $e) {
            echo "Internal error : " . $e->getMessage();
            exit();
        }
    }

    public function seed($name = null)
    {
        if (is_null($name)) {
            echo "Please provide a seeder name.";
            exit();
        }
        try {
            $seeder = \Config\Database::seeder();
            $seeder->call($name);
            echo "Seeding [$name] executed successfully!";
            exit();
        } catch (Exception $e) {
            echo "Internal error : " . $e->getMessage();
            exit();
        }
    }

    public function rollback()
    {
        try {
            $migrate = \Config\Services::migrations();
            $migrate->regress(-1);
            echo "Rollback executed successfully!";
            exit();
        } catch (Exception $e) {
            echo "Internal error : " . $e->getMessage();
            exit();
        }
    }
}