<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Symfony\Component\Process\Process;
use Symfony\Component\Process\Exception\ProcessFailedException;

class CreatePotFile extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'lang:create-pot';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create gettext POT file';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $this->info('This will overwrite the current ./lang/messages.pot file!');

        if ($this->confirm('Are you sure you want to continue?'))
        {
            // Truncate messages.pot
            $truncateProcess1 = new Process(['rm', './lang/messages.pot']);
            $truncateProcess1->run();

            if (!$truncateProcess1->isSuccessful())
            {
                throw new ProcessFailedException($truncateProcess1);
            }

            $truncateProcess2 = new Process(['touch', './lang/messages.pot']);
            $truncateProcess2->run();

            if (!$truncateProcess2->isSuccessful())
            {
                throw new ProcessFailedException($truncateProcess2);
            }

            // Generate messages.pot
            $process1 = new Process([
                'xgettext', 
                '--files-from=./dev/list', 
                '-k_gettext', 
                '-k_ngettext:1,2', 
                '-k_pgettext:1c,2', 
                '-k_npgettext:1c,2,3',
                '--language=PHP',
                '--no-wrap',
                '-o',
                './lang/messages.pot'
            ]);
            $process1->run();

            if (!$process1->isSuccessful())
            {
                throw new ProcessFailedException($process1);
            }

            $process2 = new Process([
                'xgettext',
                '--files-from=./dev/list-blade',
                '-k_gettext',
                '-k_ngettext:1,2',
                '-k_pgettext:1c,2',
                '-k_npgettext:1c,2,3',
                '--language=Python',
                '--no-wrap',
                '-j',
                '--copyright-holder=Haudenschilt LLC',
                '--package-name=Family Connections',
                '--package-version='.config('fcms.version'),
                '-o',
                './lang/messages.pot'
            ]);
            $process2->run();

            if (!$process2->isSuccessful())
            {
                throw new ProcessFailedException($process2);
            }

            $this->info('File [./lang/messages.pot] created successfully.');
        }

        return Command::SUCCESS;
    }
}
