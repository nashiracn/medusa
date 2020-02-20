<?php
/**
 * @copyright 2013 Sébastien Armand
 * @license http://opensource.org/licenses/MIT MIT
 */

namespace Khepin\Medusa\Command;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Process\Process;

class UpdateReposCommand extends Command
{

    protected function configure()
    {
        $this
            ->setName('update')
            ->setDescription('Fetch latest updates for each mirrored package')
            ->setDefinition(array(
                new InputArgument('config', InputArgument::OPTIONAL, 'A config file', 'medusa.json')
            ))
            ->setHelp(<<<EOT
The <info>update</info> command reads the given medusa.json file and updates
each mirrored git repository.
EOT
            )
        ;
    }

    /**
     * @param InputInterface $input The input instance
     * @param OutputInterface $output The output instance
     * @return int
     * @throws \Exception
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $config = json_decode(file_get_contents($input->getArgument('config')));
        $dir = $config->repodir;
        $repos = glob($dir.'/*/*.git');

        foreach ($repos as $repo) {
            $fetchCmd = ['git', 'fetch', '--prune'];
            $updateCmd = ['git', 'update-server-info', '-f'];

            $output->writeln(' - Fetching latest changes in <info>'.$repo.'</info>');
            $process = new Process($fetchCmd);
            $process->setWorkingDirectory($repo)
                    ->setTimeout(300)
                    ->run();

            if (!$process->isSuccessful()) {
                throw new \Exception($process->getErrorOutput());
            }

            $output->writeln($process->getOutput());

            $process = new Process($updateCmd);
            $process->setWorkingDirectory($repo)
                    ->setTimeout(600)
                    ->run();

            if (!$process->isSuccessful()) {
                throw new \Exception($process->getErrorOutput());
            }

            $output->writeln($process->getOutput());
        }
        return 0;
    }
}
