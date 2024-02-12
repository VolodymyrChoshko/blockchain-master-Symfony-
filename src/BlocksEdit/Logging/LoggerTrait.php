<?php
namespace BlocksEdit\Logging;

use Psr\Log\LoggerInterface;
use BlocksEdit\System\Required;

/**
 *
 */
trait LoggerTrait
{
    /**
     * The logger instance.
     *
     * @var LoggerInterface|null
     */
    protected $logger;

    /**
     * @Required()
     *
     * @param LoggerInterface $logger
     */
    public function setLogger(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }
}
