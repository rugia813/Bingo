<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class startBingo extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'startBingo';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

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
        while(true) {
            $num_pool = range(1, 6 * 23);
            shuffle($num_pool);
            for ($i=0; $i < 6 * 23; $i++) { 
                echo $num_pool[$i] . ', ';
                \Redis::publish('bingo', json_encode(array_slice($num_pool, 0, $i)));
                sleep(1);
            }
            echo "\n";
            \Redis::publish('bingo', 'clear');
            sleep(10);
        };
    }
}
