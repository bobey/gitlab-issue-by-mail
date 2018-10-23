<?php
namespace Velocityorg\GitlabIssueByMail\Command;

use Velocityorg\GitlabIssueByMail\Configuration\ParametersConfiguration;
use Fetch\Message;
use Fetch\Server;
use Gitlab\Client as GitlabClient;
use Gitlab\Model\Project as GitlabProject;
use Symfony\Component\Config\Definition\Processor;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Yaml\Parser;

class FetchMailCommand extends Command {
    protected function configure() {
        $this
            ->setName('gitlab:fetch-mail')
            ->setDescription('Fetch e-mails from specified address and create Gitlab issue(s) from the result');
    }

    protected function execute(InputInterface $input, OutputInterface $output) {
        $yaml = new Parser();

        $config = $yaml->parse(file_get_contents('config.yaml'));

        $processor = new Processor();
        $configuration = new ParametersConfiguration();
        $processedConfiguration = $processor->processConfiguration($configuration, [$config]);

        // Gitlab parameters
        $token = $processedConfiguration['gitlab']['token'];
        $projectId = $processedConfiguration['gitlab']['projectId'];
        $gitlabUrl = $processedConfiguration['gitlab']['host'];

        // Mail parameters
        $server = $processedConfiguration['mail']['server'];
        $port = $processedConfiguration['mail']['port'];
        $type = $processedConfiguration['mail']['type'];
        $username = $processedConfiguration['mail']['username'];
        $password = $processedConfiguration['mail']['password'];

        $server = new Server($server, $port);
        $server->setAuthentication($username, $password);

        //$client = new GitlabClient(sprintf('%s/api/v4/', $gitlabUrl));
        $client = GitlabClient::create($gitlabUrl)
            ->authenticate($token, GitlabClient::AUTH_URL_TOKEN);

        /** @var Message[] $messages */
        $messages = $server->getMessages();

        $project = new GitlabProject($projectId, $client);

        foreach ($messages as $message) {
            $issueTitle = $message->getSubject();
            $issueContent = $message->getMessageBody();

            $project->createIssue(
                $issueTitle,
                [
                    'description' => $issueContent
                ]
            );

            if ($output->getVerbosity() <= OutputInterface::VERBOSITY_VERBOSE) {
                $output->writeln(sprintf('<info>Created a new issue: <comment>%s</comment></info>', $issueTitle));
            }

            $message->delete();
        }

        $output->writeln(count($messages) ?
            sprintf('<info>Created %d new issue%s</info>', count($messages), count($messages) > 1 ? 's' : '') :
            '<info>No new issue created</info>'
        );

        $server->expunge();
    }
}
