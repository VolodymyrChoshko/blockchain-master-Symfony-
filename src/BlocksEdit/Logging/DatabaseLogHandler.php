<?php
namespace BlocksEdit\Logging;

use BlocksEdit\Http\Request;
use Monolog\Logger;
use Monolog\Handler\AbstractProcessingHandler;
use PDO;
use PDOStatement;
use Throwable;

/**
 * Class DatabaseLogHandler
 */
class DatabaseLogHandler extends AbstractProcessingHandler
{
    /**
     * @var PDO
     */
    protected $pdo;

    /**
     * @var Request
     */
    protected $request;

    /**
     * @var PDOStatement|null
     */
    protected $statement;

    /**
     * Constructor
     *
     * @param PDO     $pdo
     * @param Request $request
     * @param int     $level
     * @param bool    $bubble
     */
    public function __construct(PDO $pdo, Request $request, int $level = Logger::DEBUG, bool $bubble = true)
    {
        $this->pdo     = $pdo;
        $this->request = $request;

        parent::__construct($level, $bubble);
    }

    /**
     * @param array $record
     */
    protected function write(array $record)
    {
        if (!$this->statement) {
            $this->initialize();
        }

        $context = $record['context'];
        if (!$context) {
            $context = [
                'uri' => $this->request->getUri()
            ];
        } else if (is_array($context)) {
            $context['uri'] = $this->request->getUri();
        } else {
            $context = [
                'context' => $context,
                'uri'     => $this->request->getUri()
            ];
        }
        $context['server'] = $_SERVER;

        $trace   = debug_backtrace(false, 10);
        $trace   = array_slice($trace, 3);
        $message = $record['message'];
        $message .= "\n\n" . json_encode($trace, JSON_PRETTY_PRINT);

        try {
            $this->statement->execute([
                'channel'      => $record['channel'],
                'level'        => $record['level'],
                'message'      => $message,
                'context'      => json_encode($context),
                'date_created' => $record['datetime']->format('Y-m-d H:i:s'),
            ]);
        } catch (Throwable $e) {}
    }

    /**
     *
     */
    protected function initialize()
    {
        $this->statement = $this->pdo->prepare(
            'INSERT INTO log_records (log_channel, log_level, log_message, log_context, log_date_created)
            VALUES (:channel, :level, :message, :context, :date_created)'
        );
    }
}
