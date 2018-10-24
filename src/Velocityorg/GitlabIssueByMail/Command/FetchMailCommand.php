<?php
namespace Velocityorg\GitlabIssueByMail\Command;

use Velocityorg\Db;
use Fetch\Message;
use Fetch\Server;
use Gitlab\Client as GitlabClient;
use Gitlab\Model\Project as GitlabProject;
use Gitlab\Api\Users;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Yaml\Parser;

class FetchMailCommand extends Command {
    protected function configure()
    {
        $this
            ->setName('gitlab:fetch-mail')
            ->setDescription('Fetch e-mail(s) and create Gitlab issue(s) from the result');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $yaml = new Parser();
        $config = $yaml->parse(file_get_contents('config.yaml'));

        $dbConnection = new Db(
            $config['db']['type'] . ':dbname=' . $config['db']['database'],
            $config['db']['user'],
            $config['db']['password']
        );

        $db = $dbConnection->getConnection();

        $server = new Server(
            $config['mail']['server'],
            $config['mail']['port'],
            (array_key_exists('type', $config['mail'])) ? $config['mail']['type'] : 'imap'
        );

        $server->setAuthentication(
            $config['mail']['username'],
            $config['mail']['password']
        );

        $client = GitlabClient::create($config['gitlab']['host'])
            ->authenticate($config['gitlab']['token'], GitlabClient::AUTH_URL_TOKEN);

        /** @var Message[] $messages */
        $messages = $server->getMessages();

        $project = new GitlabProject(
            $config['gitlab']['projectId'],
            $client
        );

        if (count($messages) > 0) {
            $this->writeOutput($output, sprintf("Found <info>%d</info> new message(s)", count($messages)));
        } else {
            $this->writeOutput($output, "No messages found, goodbye");
        }

        foreach ($messages as $message) {
            //print_r($message->msgno);
            $headers = $message->getHeaders();

            $issueTitle = $message->getSubject();
            $issueContent = $message->getMessageBody();

            $email = $this->processAddressObject($message->getHeaders()->from);
            $userRecord = $this->getUserByEmail($client, $email['address']);

            // Only continue if we have a matching user record
            if ($userRecord) {
                $this->writeOutput($output, sprintf("E-mail from <comment>%s</comment> : <info>Matched user </info><comment>%s</comment>", $email['address'], $userRecord['username'] . ' (' . $userRecord['name']. ')'));

                $result = $project->createIssue(
                    $issueTitle,
                    [
                        'description' => $issueContent
                    ]
                );

                /*
                 * Update issue in the database
                 */
                $db->query(
                    'UPDATE issues SET',
                    [
                        'author_id' => $userRecord['id']
                    ],
                    'WHERE id = ?', $result->id
                );

                $this->writeOutput($output, sprintf('Created new issue %d : <info>%s</info>', $result->iid, $issueTitle));
            } else {
                $this->writeOutput($output, sprintf("E-mail from <comment>%s</comment> : <error>User not found, skipping</error></info>", $email['address']));
            }

            $this->writeOutput($output, sprintf('Deleting e-mail message <info>%d</info>', $message->getOverview()->msgno));

            $message->delete();
        }

        // Expunge deleted e-mail messages
        $server->expunge();
    }

    protected function processAddressObject($addresses) {
        $outputAddresses = [];

        if (is_array($addresses))
        {
            foreach ($addresses as $address) {
                if (property_exists($address, 'mailbox') && $address->mailbox != 'undisclosed-recipients')
                {
                    $currentAddress = [];
                    $currentAddress['address'] = $address->mailbox . '@' . $address->host;

                    if (isset($address->personal))
                    {
                        $currentAddress['name'] = $address->personal;
                    }

                    $outputAddresses[] = $currentAddress;
                }
            }
        }

        return $outputAddresses[0];
    }

    protected function getUserByEmail($client, $email)
    {
        $users = new Users($client);

        // Find the user with the given e-mail address
        $user = $users->all(
            [
                'search' => $email
            ]
        );

        return (is_array($user) && count($user) > 0) ? $user[0] : false;
    }

    protected function writeOutput(OutputInterface $output, $message)
    {
        $timestamp = date('Y-m-d h:i:s');

        if ($output->getVerbosity() <= OutputInterface::VERBOSITY_VERBOSE) {
            $output->writeln("${timestamp} ${message}");
        }
    }
}
