<?php
/**
 * @copyright 2013 Sébastien Armand
 * @license http://opensource.org/licenses/MIT MIT
 */

namespace Khepin\Medusa;

use Symfony\Component\Process\Process;

class Downloader
{
    protected $url;

    protected $package;

    public function __construct($package, $url)
    {
        $this->package = $package;
        $this->url = preg_replace('~^git@github.com:~', 'git://github.com/', $url);
    }

    /**
     * @param $in_dir
     * @return bool
     * @throws \Exception
     */
    public function download($in_dir)
    {
        $repo = $in_dir . '/' . $this->package . ".git";

        if (is_dir($repo)) {
            return false;
        }

        $cmd = ['git', 'clone', '--mirror', $this->url, $repo];

        $process = new Process($cmd);
        $process->setTimeout(3600);
        $process->run();

        if (!$process->isSuccessful()) {
            throw new \Exception($process->getErrorOutput());
        }

        $cmd = ['git', 'update-server-info', '-f'];

        $process = new Process($cmd);
        $process->setWorkingDirectory($repo)
                ->setTimeout(3600)
                ->run();

        if (!$process->isSuccessful()) {
            throw new \Exception($process->getErrorOutput());
        }

        $cmd = ['git', 'fsck'];
        $process = new Process($cmd);
        $process->setWorkingDirectory($repo)
                ->setTimeout(3600)
                ->run();

        if (!$process->isSuccessful()) {
            throw new \Exception($process->getErrorOutput());
        }
        return true;
    }
}
