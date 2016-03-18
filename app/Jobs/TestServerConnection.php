<?php

namespace REBELinBLUE\Deployer\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use REBELinBLUE\Deployer\Jobs\Job;
use REBELinBLUE\Deployer\Scripts\Parser as ScriptParser;
use REBELinBLUE\Deployer\Server;
use Symfony\Component\Process\Process;

/**
 * Tests if a server can successfully be SSHed into.
 */
class TestServerConnection extends Job implements ShouldQueue
{
    use InteractsWithQueue, SerializesModels;

    public $server;

    /**
     * Create a new command instance.
     *
     * @param  Server               $server
     * @return TestServerConnection
     */
    public function __construct(Server $server)
    {
        $this->server = $server;
    }

    /**
     * Execute the command.
     *
     * @return void
     */
    public function handle()
    {
        $this->server->status = Server::TESTING;
        $this->server->save();

        $key = tempnam(storage_path('app/'), 'sshkey');
        file_put_contents($key, $this->server->project->private_key);

        try {
            $process = new Process($this->generateSSHCommand($this->server, $key));
            $process->setTimeout(null);
            $process->run();

            if (!$process->isSuccessful()) {
                $this->server->status = Server::FAILED;
            } else {
                $this->server->status = Server::SUCCESSFUL;
            }
        } catch (\Exception $error) {
            $this->server->status = Server::FAILED;
        }

        $this->server->save();

        unlink($key);
    }

    /**
     * Generates the script to run the test.
     *
     * @param  Server $server
     * @param  string $private_key
     * @return string
     */
    private function generateSSHCommand(Server $server, $private_key)
    {
        $parser = new ScriptParser;

        $script = $parser->parseFile('TestServerConnection', [
            'project_path'   => $server->path,
            'test_file'      => time() . '_testing_deployer.txt',
            'test_directory' => time() . '_testing_deployer_dir',
        ]);

        return $parser->parseFile('RunScriptOverSSH', [
            'private_key' => $private_key,
            'username'    => $server->user,
            'port'        => $server->port,
            'ip_address'  => $server->ip_address,
            'script'      => $script,
        ]);
    }
}
