<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Throwable;

class ProvisionRuntimeDatabase extends Command
{
    protected $signature = 'app:provision-runtime-db';

    protected $description = 'Restrict the PostgreSQL runtime account to application data';

    public function handle(): int
    {
        $username = (string) env('SIMPEG_DB_USER', 'cuti_online_app');
        $password = (string) env('SIMPEG_DB_PASSWORD');
        $database = DB::connection();
        if ($database->getDriverName() !== 'pgsql'
            || ! preg_match('/\A[a-z][a-z0-9_]{2,62}\z/', $username)
            || $username === $database->getConfig('username')
            || strlen($password) < 32) {
            $this->error('Gunakan akun PostgreSQL runtime terpisah dengan kata sandi minimal 32 karakter.');

            return self::FAILURE;
        }

        try {
            $database->transaction(function () use ($database, $username, $password): void {
                $pdo = $database->getPdo();
                $role = $database->selectOne('SELECT oid FROM pg_roles WHERE rolname = ?', [$username]);
                $identifier = '"'.$username.'"';
                $database->statement('REVOKE CREATE ON SCHEMA public FROM PUBLIC');
                if ($role !== null && $database->selectOne(
                    "SELECT 1 FROM pg_auth_members WHERE member = ?
                     UNION ALL SELECT 1 FROM pg_namespace WHERE nspowner = ?
                     UNION ALL SELECT 1 FROM pg_class WHERE relowner = ?
                     UNION ALL SELECT 1 FROM pg_database WHERE datdba = ? LIMIT 1",
                    array_fill(0, 4, $role->oid)
                ) !== null) {
                    throw new \RuntimeException('Runtime role must not own objects or inherit memberships.');
                }

                $verb = $role === null ? 'CREATE' : 'ALTER';
                $pdo->exec("{$verb} ROLE {$identifier} LOGIN NOSUPERUSER NOCREATEDB NOCREATEROLE NOINHERIT NOREPLICATION NOBYPASSRLS PASSWORD ".$pdo->quote($password));
                $database->statement("GRANT USAGE ON SCHEMA public TO {$identifier}");
                $database->statement("GRANT SELECT, INSERT, UPDATE, DELETE ON ALL TABLES IN SCHEMA public TO {$identifier}");
                $database->statement("GRANT USAGE, SELECT ON ALL SEQUENCES IN SCHEMA public TO {$identifier}");
                $database->statement("REVOKE ALL ON TABLE public.migrations FROM {$identifier}");
            });
        } catch (Throwable) {
            $this->error('Akun runtime tidak dapat disiapkan. Periksa izin akun migrasi dan gunakan akun runtime khusus.');

            return self::FAILURE;
        }

        $this->info('Akun database runtime sudah disiapkan.');

        return self::SUCCESS;
    }
}
