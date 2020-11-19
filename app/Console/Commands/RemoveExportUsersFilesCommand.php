<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class RemoveExportUsersFilesCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'remove:exports';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Remove exports files';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $path = storage_path('app/public/exports');
        $numOfDays = 10;

        if ($handle = opendir($path)) {
            while (false !== ($file = readdir($handle))) {
                if ($file != "." && $file != "..") {
                    $filelastmodified = filemtime($path . '/' . $file);
                    //24 hours in a day * 3600 seconds per hour
                    if ((time() - $filelastmodified) > 86400 * $numOfDays) {
                        unlink($path . '/' . $file);
                    }
                }
            }
            closedir($handle);
        }
    }
}
