<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class OptimizeUserName extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'update:username';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '3 cols user name to 2 cols user name';

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
     * @return int
     */
    public function handle()
    {
        $users = User::all();

        foreach ($users as $user) {
            $user->first_name = $user->middle_name . $user->first_name;
            $user->middle_name = '';

            $user->save();
        }
        return 0;
    }
}
