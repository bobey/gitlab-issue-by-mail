<?php

namespace Bobey\GitlabIssueByMail\Command;

use Bobey\GitlabIssueByMail\Configuration\ParametersConfiguration;
use Fetch\Message;
use Fetch\Server;
use Gitlab\Client as GitlabClient;
use Gitlab\Model\Project as GitlabProject;
use League\HTMLToMarkdown\HtmlConverter;
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

        $attachmentPath = __DIR__ . '/../../../../attachments/';
        $file = __DIR__ . '/../../../../config/parameters.yml';

        $config = $yaml->parse(file_get_contents($file));

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

        $server = new Server($server, $port, $type);
        $server->setAuthentication($username, $password);

        $client = new GitlabClient(sprintf('%s/api/v4/', $gitlabUrl));
        if ($output->getVerbosity() >= OutputInterface::VERBOSITY_VERY_VERBOSE) {
            $output->writeln('Trying to connect to ' . $gitlabUrl);
        }
        $client->authenticate($token, GitlabClient::AUTH_HTTP_TOKEN);

        $project = new GitlabProject($projectId, $client);

        /** @var Message[] $messages */
        $messages = $server->getMessages();

        $htmlConverter = new HtmlConverter();
        $htmlConverter->getConfig()->setOption('remove_nodes', 'meta head');
        foreach ($messages as $message) {

            $issueTitle = imap_utf8($message->getSubject());
            $issueContent = $message->getMessageBody(true);
            $attachments = $message->getAttachments();

            if ($output->getVerbosity() >= OutputInterface::VERBOSITY_VERY_VERBOSE) {
                $output->writeln(sprintf('<info>Trying to create issue: <comment>%s</comment></info>',
                    $issueTitle));
            }
            $markdown = $htmlConverter->convert($issueContent);
            if (!empty($attachments)) {
                if ($output->getVerbosity() >= OutputInterface::VERBOSITY_VERY_VERBOSE) {
                    $output->writeln(sprintf('<info>Found <comment>%s</comment> attachements</info>',
                        count($attachments)));
                }
                $markdown .= "\n\n### Pièces jointes\n\n";
                foreach ($attachments as $attachment) {
                    $filename = str_replace(' ', '-', imap_utf8($attachment->getFileName()));

                    if ($attachment->saveAs($attachmentPath . $filename)) {
                        if ($output->getVerbosity() >= OutputInterface::VERBOSITY_VERY_VERBOSE) {
                            $output->writeln(sprintf('<info>Trying to upload file <comment>%s</comment></info>',
                                $filename));
                        }

                        $ch = curl_init($gitlabUrl . '/api/v4/projects/' . $project->id . '/uploads');

                        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
                        curl_setopt($ch, CURLOPT_HTTPHEADER, [
                            'PRIVATE-TOKEN: ' . $token
                        ]);
                        $file = new \CURLFile($attachmentPath . $filename);
                        $postData = array(
                            'file' => $file,
                        );
                        curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
                        $response = curl_exec($ch);
                        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                        if ($httpCode == "201") {
                            $json = json_decode($response);
                            if (isset($json->markdown)) {
                                $markdown .= ' ' . $json->markdown . " \n\n";
                                if ($output->getVerbosity() >= OutputInterface::VERBOSITY_VERY_VERBOSE) {
                                    $output->writeln(sprintf('<info>Uploaded a file</info>'));
                                }
                            }
                        } else {
                            $error = curl_error($ch);
                            if ($output->getVerbosity() >= OutputInterface::VERBOSITY_VERY_VERBOSE) {
                                $output->writeln(sprintf('<error>%s</error>', $error));
                            }
                        }
                        curl_close($ch);
                    }
                }
            }

            $project->createIssue($issueTitle, [
                'description' => $markdown,
            ]);

            if ($output->getVerbosity() >= OutputInterface::VERBOSITY_VERY_VERBOSE) {
                $output->writeln(sprintf('<info>Created a new issue: <comment>%s</comment></info>', $issueTitle));
            }

            $message->delete();
        }
        if ($output->getVerbosity() >= OutputInterface::VERBOSITY_VERBOSE) {
            $output->writeln(count($messages) ?
                sprintf('<info>Created %d new issue%s</info>', count($messages), count($messages) > 1 ? 's' : '') :
                '<info>No new issue created</info>'
            );
        }

        $server->expunge();
    }
}
