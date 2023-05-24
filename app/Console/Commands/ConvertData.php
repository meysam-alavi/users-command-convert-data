<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use SplFileObject;
use Illuminate\Support\Facades\Redis;

/**
 * ConvertData Command Class
 */
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
        $logFilePath = self::$usersDataDir . '/convertor-meta/log-error.txt';
        if ($resume) {
            $offset = (int)Redis::command('get', ['offset']) ?? 1;
            $splFileObject->seek($offset);
        }

        if ($splFileObject->isFile()) {
            $logData = '';
            while (!$splFileObject->eof()) {
                $lineData = $splFileObject->getCurrentLine();
                $lineData = trim($lineData);
                if(!empty($lineData)) {
                    $userInfo = explode("\t", $lineData);
                    $record = array(
                        'first_name' => $userInfo[0],
                        'last_name' => $userInfo[1],
                        'mobile' => $userInfo[2],
                        'national_code' => $userInfo[3]
                    );

                    $offset = (int)Redis::command('get', ['offset']) ?? 1;
                    try {
                        User::query()->create($record);
                        $this->info("registered: users-list.txt line: $offset => national_code {$userInfo[3]}");
                    } catch (\Exception $e) {
                        $this->error("not registered: users-list.txt line: $offset => national_code {$userInfo[3]}");
                        $logData .= 'users-list.txt line: '.$offset.' | '.$lineData.'|'.$e->getMessage().PHP_EOL;
                    }

                    Redis::command('incr', ['offset', 1]);
                }
            }

            Storage::append($logFilePath, $logData);
        } else {
            $this->error('the file not exists or is\'nt a regular file');
        }
    }
}
