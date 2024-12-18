<?php

namespace App\Console\Commands;

use App\Helpers\Aws;
use App\Models\Upload;
use Carbon\Carbon;
use Illuminate\Console\Command;

class FixTheDatesForUploadedFiles extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'fix:the-dates-for-uploaded-files';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'This command is created to update the dates of the uploaded files that are having
                            issues on ux and live db. Some of the files showing the creation date as 1970-01-01
                            Ticket # 1241';

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
        try {
            /*
            * Get the records from database that are having issue in creation date.
            * After getting those files from database , we need to check instance of those files on AWS S3.
            * If there is file exist on S3 , get the file creation date from there and populates in the database again.
            * Also we need to check the created_at , updated_at having 2022 as value.
            * select * from uploads where updated_at=2022 order by created_at desc;
            */
            $getUploadedFiles = Upload::where('updated_at', '2022')
                                ->orderBy('created_at', 'desc')
                                ->get();

            foreach($getUploadedFiles as $file) {
                $s3FileName = $file->file_path; 
                
                $s3File = Aws::doesObjectExist($s3FileName);

                // Retrieve the LastModified date
                $lastModified = $s3File['@metadata']['headers']['last-modified'] ?? '';
                
                if(!empty($lastModified)) {                    
                    // Create a Carbon instance from the given date string
                    $date = Carbon::parse($lastModified)->getTimestamp();

                    $file->updated_at = $date ?? time();
                    $file->created_at = $date ?? time();
                    $file->save();

                    $this->info("File {$file->file_id} updated successfully.");
                }
            }

            $this->info("Script executed successfully.");

        } catch (\Throwable $th) {
            $this->error($th->getMessage());
        }
    }
}
