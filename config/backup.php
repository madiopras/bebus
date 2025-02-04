<?php

return [

    'backup' => [
        /*
         * The name of this application. You can use this name to monitor
         * the backups.
         */
        'name' => env('APP_NAME', 'laravel-backup'),

        'source' => [
            'files' => [
                /*
                 * The list of directories and files that will be included in the backup.
                 */
                'include' => [
                    base_path(),  // Menyertakan seluruh aplikasi Laravel
                ],

                /*
                 * These directories and files will be excluded from the backup.
                 *
                 * Directories used by the backup process will automatically be excluded.
                 */
                'exclude' => [
                    base_path('vendor'),  // Mengecualikan folder vendor
                    base_path('node_modules'),  // Mengecualikan folder node_modules
                    base_path('storage'),  // Mengecualikan folder storage (kecuali jika dibutuhkan untuk backup)
                ],

                /*
                 * Determines if symlinks should be followed.
                 */
                'follow_links' => false,

                /*
                 * Determines if it should avoid unreadable folders.
                 */
                'ignore_unreadable_directories' => false,

                /*
                 * This path is used to make directories in resulting zip-file relative
                 * Set to `null` to include complete absolute path
                 * Example: base_path()
                 */
                'relative_path' => null,
            ],

            /*
             * The names of the connections to the databases that should be backed up
             * MySQL, PostgreSQL, SQLite and Mongo databases are supported.
             */
            'databases' => [
                'mysql',  // Menggunakan database MySQL
            ],
        ],

        /*
         * The database dump can be compressed to decrease disk space usage.
         * You can use compression methods like Gzip, Zip, etc.
         */
        'database_dump_compressor' => \Spatie\DbDumper\Compressors\GzipCompressor::class,

        /*
         * If specified, the database dumped file name will contain a timestamp (e.g.: 'Y-m-d-H-i-s').
         */
        'database_dump_file_timestamp_format' => 'Y-m-d-H-i-s',

        /*
         * The base of the dump filename, either 'database' or 'connection'.
         * If 'database' (default), the dumped filename will contain the database name.
         */
        'database_dump_filename_base' => 'database',

        /*
         * The file extension used for the database dump files.
         * For MySQL it is .sql
         */
        'database_dump_file_extension' => 'sql',

        'destination' => [
            /*
             * The compression algorithm to be used for creating the zip archive.
             * You can choose from algorithms such as ZipArchive::CM_DEFAULT, ZipArchive::CM_DEFLATE, etc.
             */
            'compression_method' => ZipArchive::CM_DEFAULT,

            /*
             * The compression level corresponding to the used algorithm.
             * 1 is the fastest, 9 is the strongest compression.
             */
            'compression_level' => 9,

            /*
             * The filename prefix used for the backup zip file.
             */
            'filename_prefix' => 'backup-',  // Prefix nama file backup

            /*
             * The disk names on which the backups will be stored.
             * You can specify 'local', 's3', 'ftp', etc.
             */
            'disks' => [
                'local',  // Menyimpan backup di disk lokal (sesuaikan dengan konfigurasi disk yang kamu gunakan)
                // 's3', // Uncomment jika menggunakan S3 untuk penyimpanan cloud
            ],
        ],

        /*
         * The directory where the temporary files will be stored.
         */
        'temporary_directory' => storage_path('app/backup-temp'),

        /*
         * The password to be used for archive encryption.
         * Set to `null` to disable encryption.
         */
        'password' => env('BACKUP_ARCHIVE_PASSWORD', null),

        /*
         * The encryption algorithm to be used for archive encryption.
         * 'default' will use AES-256 encryption.
         */
        'encryption' => 'default',

        /*
         * The number of attempts, in case the backup command encounters an exception.
         */
        'tries' => 1,

        /*
         * The number of seconds to wait before attempting a new backup if the previous try failed.
         * Set to `0` for none.
         */
        'retry_delay' => 0,
    ],

    /*
     * Notifications configuration.
     * You can receive notifications via email, Slack, Discord, etc.
     */
    'notifications' => [
        'notifications' => [
            \Spatie\Backup\Notifications\Notifications\BackupHasFailedNotification::class => ['mail'],
            \Spatie\Backup\Notifications\Notifications\UnhealthyBackupWasFoundNotification::class => ['mail'],
            \Spatie\Backup\Notifications\Notifications\CleanupHasFailedNotification::class => ['mail'],
            \Spatie\Backup\Notifications\Notifications\BackupWasSuccessfulNotification::class => ['mail'],
            \Spatie\Backup\Notifications\Notifications\HealthyBackupWasFoundNotification::class => ['mail'],
            \Spatie\Backup\Notifications\Notifications\CleanupWasSuccessfulNotification::class => ['mail'],
        ],

        'mail' => [
            'to' => 'your@example.com',  // Ganti dengan email penerima notifikasi
            'from' => [
                'address' => env('MAIL_FROM_ADDRESS', 'hello@example.com'),
                'name' => env('MAIL_FROM_NAME', 'Example'),
            ],
        ],

        /*
         * Slack, Discord, etc. notifications are also available. Configure these if needed.
         */
    ],

    /*
     * Backup monitoring. Set the requirements for healthy backups here.
     */
    'monitor_backups' => [
        [
            'name' => env('APP_NAME', 'laravel-backup'),
            'disks' => ['local'],
            'health_checks' => [
                \Spatie\Backup\Tasks\Monitor\HealthChecks\MaximumAgeInDays::class => 1,  // Backup yang lebih tua dari 1 hari dianggap tidak sehat
                \Spatie\Backup\Tasks\Monitor\HealthChecks\MaximumStorageInMegabytes::class => 5000,  // Maksimum ukuran backup 5GB
            ],
        ],
    ],

    'cleanup' => [
        /*
         * The strategy that will be used to cleanup old backups. Default strategy keeps backups
         * for a certain number of days, then keeps daily, weekly, and monthly backups.
         */
        'strategy' => \Spatie\Backup\Tasks\Cleanup\Strategies\DefaultStrategy::class,

        'default_strategy' => [
            /*
             * Keep backups for 7 days, then keep daily backups for 16 days, weekly backups for 8 weeks, etc.
             */
            'keep_all_backups_for_days' => 7,
            'keep_daily_backups_for_days' => 16,
            'keep_weekly_backups_for_weeks' => 8,
            'keep_monthly_backups_for_months' => 4,
            'keep_yearly_backups_for_years' => 2,
            'delete_oldest_backups_when_using_more_megabytes_than' => 5000,  // Menghapus backup lama jika penggunaan disk melebihi 5GB
        ],

        /*
         * The number of attempts for cleanup if an exception is encountered.
         */
        'tries' => 1,

        /*
         * The number of seconds to wait before attempting a new cleanup if the previous try failed.
         */
        'retry_delay' => 0,
    ],

];
