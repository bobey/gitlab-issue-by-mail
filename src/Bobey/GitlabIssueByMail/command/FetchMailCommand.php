<?php

namespace Bobey\GitlabIssueByMail\Command;

use Gitlab\Client as GitlabClient;
use Gitlab\Model\Project as GitlabProject;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class FetchMailCommand extends Command
{
	protected function configure()
	{
		$this
			->setName('gitlab:fetch-mail')
			->setDescription('Fetch emails from given mail address and create Gitlab Issues from it')
			->addOption(
				'token',
				null,
				InputOption::VALUE_REQUIRED,
				'Your gitlab private token'
			)
			->addOption(
				'projectId',
				null,
				InputOption::VALUE_REQUIRED,
				'Your gitlab project ID'
			)
		;
	}

	protected function execute(InputInterface $input, OutputInterface $output)
	{
		$token = $input->getOption('token');
		$projectId = $input->getOption('projectId');

		$client = new GitlabClient('https://gitlab.com/api/v3/');
		$client->authenticate($token, GitlabClient::AUTH_URL_TOKEN);

		$project = new GitlabProject($projectId, $client);

		$project->createIssue('This does not work..', array(
			'description' => 'This doesnt work properly. Please fix!',
			'assignee_id' => null,
		));
	}
}
