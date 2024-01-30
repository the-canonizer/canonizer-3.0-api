<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

use App\Models\ActivityLog;
use App\Models\CommandHistory;
use App\Models\Upload;
use Carbon\Carbon;
use Throwable;

class UpdateFilePathsToRelativePaths extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'change:filepathstorelativepaths';

    /**
     * The console command made for updating the files full paths to relative paths
     * in uploads table.
     * @var string
     */
    protected $description = 'This command will change uploaded full file paths to relative paths.';

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
        $commandHistory = (new CommandHistory())->create([
            'name' => $this->signature,
            'parameters' => [],
            'started_at' => Carbon::now()->timestamp,
        ]);

        try {
            $getUploadedFiles = Upload::all();

            foreach ($getUploadedFiles as $record) {
                $relativePath = $this->convertToRelativePath($record->file_path);
                
                $record->update([
                    'file_path' => $relativePath,
                ]);
            }

            $this->info('S3 URLs converted to relative paths successfully.');

        } catch (Throwable $th) {
            dd("here", $th->getMessage());
            $commandHistory->error_output = json_encode($th);
            $commandHistory->save();
        }

        $commandHistory->finished_at = Carbon::now()->timestamp;
        $commandHistory->save();
    }

    /**
     * Convert an absolute S3 URL to a relative path.
     *
     * @param  string  $url
     * @return string
     */
    private function convertToRelativePath($url)
    {
        // Define the base URLs you want to handle
        $baseUrls = [
            env('AWS_URL').'/',
            env('AWS_PUBLIC_URL').'/'
        ];

        // Check each base URL and replace it with an empty string
        foreach ($baseUrls as $baseUrl) {
            if (strpos($url, $baseUrl) === 0) {
                return str_replace($baseUrl, '', $url);
            }
        }

        // If no matching base URL is found, return the original URL
        return $url;
    }
}
