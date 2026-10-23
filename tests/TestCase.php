<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;

abstract class TestCase extends BaseTestCase
{
    public function refreshApplication(): void
    {
        parent::refreshApplication();

        $connection = Config::get('database.default');
        $config = Config::get("database.connections.{$connection}", []);

        $driver = $config['driver'] ?? DB::connection($connection)->getDriverName();
        $database = $config['database'] ?? DB::connection($connection)->getDatabaseName();

        $autorise = false;

        if ($driver === 'sqlite' && $database === ':memory:') {
            $autorise = true;
        }

        if ($driver === 'mysql' && $database === 'sunu_sante_test') {
            $autorise = true;
        }

        if (! $autorise) {
            $this->fail('Garde-fou tests : connexion non autorisee. driver='.$driver.' database='.$database.'. Seul SQLite en memoire (:memory:) ou MySQL avec sunu_sante_test est autorise.');
        }
    }
}
