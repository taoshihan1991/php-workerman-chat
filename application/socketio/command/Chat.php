<?php
/**
 * Created by PhpStorm.
 * User: 1609123282
 * Date: 19-6-21
 * Time: 上午10:54
 */
namespace app\socketio\command;

use app\socketio\service\Server;
use think\console\Command;
use think\console\Input;
use think\console\input\Argument;
use think\console\input\Option;
use think\console\Output;

class Chat extends Command
{
    protected function configure()
    {
        $this->setName('chat')
            ->addArgument('action', Argument::OPTIONAL, "start|stop|restart|reload|status|connections", 'start')
            ->addOption('host', 'H', Option::VALUE_OPTIONAL, 'the host of workerman server.', null)
            ->addOption('port', 'p', Option::VALUE_OPTIONAL, 'the port of workerman server.', null)
            ->addOption('daemon', 'd', Option::VALUE_NONE, 'Run the workerman server in daemon mode.')
            ->setDescription('phpsocket.io Server for ThinkPHP');
    }

    protected function execute(Input $input, Output $output)
    {
        $action = $input->getArgument('action');

        if (DIRECTORY_SEPARATOR !== '\\') {
            if (!in_array($action, ['start', 'stop', 'reload', 'restart', 'status', 'connections'])) {
                $output->writeln("<error>Invalid argument action:{$action}, Expected start|stop|restart|reload|status|connections .</error>");
                return false;
            }

            global $argv;
            array_shift($argv);
            array_shift($argv);
            array_shift($argv);
            array_unshift($argv, 'think', $action);
        }

        $logo =<<<EOL
             _       _                                         ____  
 __      __ | |__   (_)  ___   _ __     ___   _ __    __   __ |___ \ 
 \ \ /\ / / | '_ \  | | / __| | '_ \   / _ \ | '__|   \ \ / /   __) |
  \ V  V /  | | | | | | \__ \ | |_) | |  __/ | |       \ V /   / __/ 
   \_/\_/   |_| |_| |_| |___/ | .__/   \___| |_|        \_/   |_____|
                              |_|
EOL;

        $output->writeln($logo . PHP_EOL);

        $httpPort = config('service_socketio.http_port');
        $ApiServerShow = <<<EOL
API SERVER LISTEN: http://0.0.0.0:{$httpPort}
EOL;
        $output->writeln($ApiServerShow . PHP_EOL);

        // 运行socket.io服务
        Server::run();
    }
}