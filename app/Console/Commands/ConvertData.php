<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use mysql_xdevapi\Exception;
use SplFileObject;
use Illuminate\Support\Facades\Redis;

class ConvertData extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'user:convert-data {--resume}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Reads user information from a text file and stores it in the corresponding table in the database';

    /**
     * base path of plain text includes users info
     *
     * @var string
     */
    static string $usersDataDir = '/public/users';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        Storage::makeDirectory(self::$usersDataDir);
        $userDataFilePath = storage_path('app' . self::$usersDataDir . '/users-list.txt');

        try {
            $splFileObject = new SplFileObject($userDataFilePath);
        } catch (\Exception $e) {
            $this->error($e->getMessage());
            exit(0);
        }

        $resume = $this->option('resume');
        $offsetFilePath = self::$usersDataDir . '/convertor-meta/offset.txt';
        $logFilePath = self::$usersDataDir . '/convertor-meta/log-error.txt';
        $offset = 1;
        if ($resume) {
            $offset = (int)Storage::get($offsetFilePath) ?? 1;
            $splFileObject->seek($offset);
        }

        if ($splFileObject->isFile()) {
            while (!$splFileObject->eof()) {
                $lineData = $splFileObject->getCurrentLine();
                $lineData = trim($lineData);
                if(empty($lineData)) {
                    continue;
                }
                $userInfo = explode("\t", $lineData);

                print_r($userInfo);

                $record = array(
                    'first_name' => $userInfo[0],
                    'last_name' => $userInfo[1],
                    'mobile' => $userInfo[2],
                    'national_code' => $userInfo[3]
                );

                try {
                    User::query()->create($record);
                } catch (\Exception $e) {
                    $this->error($e->getMessage());
                    $logData = 'line number in users-list.txt file : '.$offset.' | '.$lineData.'|'.$e->getMessage().PHP_EOL;
                    Storage::append($logFilePath, $logData);
                }

                if ($resume) {
                    Storage::put($offsetFilePath, $offset);
                }
                $offset++;

                echo $lineData;
            }
        } else {
            $this->error('the file not exists or is\'nt a regular file');
        }


    }
}
