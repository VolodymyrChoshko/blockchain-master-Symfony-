<?php
namespace Command;

use BlocksEdit\Command\Args;
use BlocksEdit\Command\Command;
use Aws\Sqs\SqsClient;
use BlocksEdit\Command\InputInterface;
use BlocksEdit\Command\OutputInterface;
use Entity\NoSend;
use Repository\NoSendRepository;

/**
 * Class EmailBouncesCommand
 */
class EmailBouncesCommand extends Command
{
    static $name = 'email:bounces';

    /**
     * @var SqsClient
     */
    protected $client;

    /**
     * @var NoSendRepository
     */
    protected $noSendRepository;

    /**
     * Constructor
     *
     * @param NoSendRepository $noSendRepository
     */
    public function __construct(NoSendRepository $noSendRepository)
    {
        $this->noSendRepository = $noSendRepository;
    }

    /**
     * {@inheritDoc}
     */
    public static function getHelp(): string
    {
        return 'Checks for email bounces and updates the no send database.';
    }

    /**
     * {@inheritDoc}
     */
    public function run(Args $args, OutputInterface $output, InputInterface $input)
    {
        $this->client = new SqsClient($this->config->aws);

        $resp = $this->client->getQueueUrl([
            'QueueName' => 'ses-bounces-queue'
        ]);
        $queueUrl = $resp->get('QueueUrl');

        while($item = $this->receiveMessages($queueUrl)) {
            $body = json_decode($item['Body'], true);
            foreach($body['bounce']['bouncedRecipients'] as $recipient) {
                $noSend = (new NoSend())
                    ->setEmail($recipient['emailAddress'])
                    ->setReason('bounce: ' . $recipient['status']);
                $this->noSendRepository->insert($noSend);
                $output->writeLine(
                    sprintf('Added %s to no send list', $recipient['emailAddress'])
                );
            }

            $this->client->deleteMessage([
                'QueueUrl'      => $queueUrl,
                'ReceiptHandle' => $item['ReceiptHandle']
            ]);
        }

        $resp = $this->client->getQueueUrl([
            'QueueName' => 'ses-complaints-queue'
        ]);
        $queueUrl = $resp->get('QueueUrl');

        while($item = $this->receiveMessages($queueUrl)) {
            $body = json_decode($item['Body'], true);
            foreach($body['complaint']['complainedRecipients'] as $recipient) {
                $noSend = (new NoSend())
                    ->setEmail($recipient['emailAddress'])
                    ->setReason('complaint');
                $this->noSendRepository->insert($noSend);
                $output->writeLine(
                    sprintf('Added %s to no send list', $recipient['emailAddress'])
                );
            }

            $this->client->deleteMessage([
                'QueueUrl'      => $queueUrl,
                'ReceiptHandle' => $item['ReceiptHandle']
            ]);
        }
    }

    /**
     * @param string $queueUrl
     *
     * @return array
     */
    protected function receiveMessages(string $queueUrl): array
    {
        $result = $this->client->receiveMessage([
            'QueueUrl'            => $queueUrl,
            'MaxNumberOfMessages' => 1
        ]);

        $messages = (array)$result->get('Messages');
        if ($messages) {
            $messages = $messages[0];
        }

        return $messages;
    }
}
