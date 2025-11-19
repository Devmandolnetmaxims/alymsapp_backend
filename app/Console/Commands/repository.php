<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class repository extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'make:repository {name}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Make Repository file';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $name = $this->argument('name');
        $content ='<?php

namespace App\Http\Repository;

class '.$name.'
{
    //
}';

        file_put_contents(app_path('Http\Repository'.'\\'.$name . '.php'), $content);

        $this->info('File generated successfully.');
    }
}
