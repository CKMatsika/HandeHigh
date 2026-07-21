<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;

class BackupDatabaseCommand extends Command
{
    protected $signature = 'backup:database {--school= : Specific school ID to backup} {--compress : Compress the backup file}';
    protected $description = 'Backup the database with school-specific options';

    public function handle()
    {
        $this->info('Starting database backup...');
        
        $schoolId = $this->option('school');
        $compress = $this->option('compress');
        
        try {
            $backupPath = $this->createBackup($schoolId, $compress);
            
            $this->info("Backup created successfully: {$backupPath}");
            
            // Clean old backups (keep last 30 days)
            $this->cleanOldBackups();
            
            return 0;
        } catch (\Exception $e) {
            $this->error("Backup failed: " . $e->getMessage());
            return 1;
        }
    }

    protected function createBackup($schoolId = null, $compress = false)
    {
        $timestamp = Carbon::now()->format('Y_m_d_His');
        $database = config('database.connections.sqlite.database');
        
        if ($schoolId) {
            $filename = "school_{$schoolId}_backup_{$timestamp}.sql";
        } else {
            $filename = "full_backup_{$timestamp}.sql";
        }
        
        $backupPath = storage_path("app/backups/{$filename}");
        
        // Ensure backup directory exists
        $backupDir = dirname($backupPath);
        if (!File::exists($backupDir)) {
            File::makeDirectory($backupDir, 0755, true);
        }
        
        // Create backup
        if ($schoolId) {
            $this->createSchoolSpecificBackup($database, $backupPath, $schoolId);
        } else {
            $this->createFullBackup($database, $backupPath);
        }
        
        if ($compress) {
            $this->compressBackup($backupPath);
            $backupPath .= '.gz';
        }
        
        return $backupPath;
    }

    protected function createSchoolSpecificBackup($database, $backupPath, $schoolId)
    {
        $tables = $this->getSchoolTables();
        $sql = "-- School ID: {$schoolId} Backup\n";
        $sql .= "-- Generated: " . Carbon::now()->toDateTimeString() . "\n\n";
        
        foreach ($tables as $table) {
            $sql .= $this->getTableData($table, $schoolId);
        }
        
        File::put($backupPath, $sql);
    }

    protected function createFullBackup($database, $backupPath)
    {
        // For SQLite, we can copy the entire database file
        if (config('database.default') === 'sqlite') {
            File::copy($database, $backupPath);
        } else {
            // For MySQL/PostgreSQL, use mysqldump or pg_dump
            $this->createSqlBackup($backupPath);
        }
    }

    protected function getSchoolTables()
    {
        return [
            'schools', 'users', 'students', 'parents', 'teachers',
            'enrollments', 'invoices', 'payments', 'ledger_entries',
            'classes', 'subjects', 'assessments', 'results',
            'books', 'borrow_records', 'audit_logs'
        ];
    }

    protected function getTableData($table, $schoolId)
    {
        $sql = "-- Table: {$table}\n";
        
        // Get table structure
        $structure = DB::select("SELECT sql FROM sqlite_master WHERE type='table' AND name='{$table}'");
        if ($structure) {
            $sql .= $structure[0]->sql . ";\n\n";
        }
        
        // Get table data for specific school
        if ($table === 'schools') {
            $data = DB::table($table)->where('id', $schoolId)->get();
        } else {
            $data = DB::table($table)->where('school_id', $schoolId)->get();
        }
        
        foreach ($data as $row) {
            $values = [];
            foreach ($row as $value) {
                $values[] = $value === null ? 'NULL' : "'" . addslashes($value) . "'";
            }
            $sql .= "INSERT INTO {$table} VALUES (" . implode(', ', $values) . ");\n";
        }
        
        $sql .= "\n";
        return $sql;
    }

    protected function createSqlBackup($backupPath)
    {
        $command = $this->getBackupCommand();
        shell_exec($command);
    }

    protected function getBackupCommand()
    {
        $database = config('database.connections.mysql.database');
        $username = config('database.connections.mysql.username');
        $password = config('database.connections.mysql.password');
        $host = config('database.connections.mysql.host');
        
        return "mysqldump -h {$host} -u {$username} -p{$password} {$database} > {$backupPath}";
    }

    protected function compressBackup($backupPath)
    {
        $compressed = $backupPath . '.gz';
        $data = File::get($backupPath);
        $gzdata = gzencode($data, 9);
        File::put($compressed, $gzdata);
        File::delete($backupPath);
    }

    protected function cleanOldBackups()
    {
        $backupDir = storage_path('app/backups');
        $files = File::files($backupDir);
        
        foreach ($files as $file) {
            if ($file->getMTime() < strtotime('-30 days')) {
                File::delete($file->getPathname());
                $this->info("Deleted old backup: " . $file->getFilename());
            }
        }
    }
}
