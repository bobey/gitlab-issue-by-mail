<?php

namespace Bobey\GitlabIssueByMail\Command;

use Bobey\GitlabIssueByMail\Configuration\ParametersConfiguration;
use Gitlab\Client as GitlabClient;
use Gitlab\Model\Project as GitlabProject;
use Symfony\Component\Config\Definition\Processor;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Yaml\Parser;

class FetchMailCommand extends Command
{
    protected function configure()
    {
        $this
            ->setName('gitlab:fetch-mail')
            ->setDescription('Fetch emails from given mail address and create Gitlab Issues from it');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $yaml = new Parser();

        $config = $yaml->parse(file_get_contents( __DIR__ . '/../../../../config/parameters.yml'));

        $processor = new Processor();
        $configuration = new ParametersConfiguration();
        $processedConfiguration = $processor->processConfiguration($configuration, [$config]);

        $token = $processedConfiguration['gitlab']['token'];
        $projectId = $processedConfiguration['gitlab']['projectId'];
        $gitlabUrl = $processedConfiguration['gitlab']['host'];

        $client = new GitlabClient(sprintf('%s/api/v3/', $gitlabUrl));
        $client->authenticate($token, GitlabClient::AUTH_URL_TOKEN);

        $project = new GitlabProject($projectId, $client);

        $project->createIssue('This does not work..', array(
            'description' => 'This doesnt work properly. Please fix!',
            'assignee_id' => null,
        ));
    }
}
