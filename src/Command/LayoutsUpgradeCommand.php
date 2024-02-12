<?php
namespace Command;

use BlocksEdit\Command\Args;
use BlocksEdit\Command\Command;
use BlocksEdit\Command\InputInterface;
use BlocksEdit\Command\OutputInterface;
use BlocksEdit\Html\LayoutUpgrade;
use BlocksEdit\IO\Paths;
use BlocksEdit\IO\PathsTrait;
use BlocksEdit\Logging\LoggerTrait;
use Exception;
use Repository\TemplatesRepository;
use Service\LayoutUpgradeMessageQueue;

/**
 * Class LayoutsUpgradeCommand
 */
class LayoutsUpgradeCommand extends Command
{
    use PathsTrait;
    use LoggerTrait;

    static $name = 'layouts:upgrade';

    /**
     * @var LayoutUpgradeMessageQueue
     */
    protected $layoutUpgradeMessageQueue;

    /**
     * @var LayoutUpgrade
     */
    protected $layoutUpgrade;

    /**
     * @var TemplatesRepository
     */
    protected $templatesRepository;

    /**
     * Constructor
     *
     * @param LayoutUpgradeMessageQueue $layoutUpgradeMessageQueue
     * @param LayoutUpgrade             $layoutUpgrade
     * @param TemplatesRepository       $templatesRepository
     */
    public function __construct(
        LayoutUpgradeMessageQueue $layoutUpgradeMessageQueue,
        LayoutUpgrade $layoutUpgrade,
        TemplatesRepository $templatesRepository
    )
    {
        $this->layoutUpgradeMessageQueue = $layoutUpgradeMessageQueue;
        $this->layoutUpgrade             = $layoutUpgrade;
        $this->templatesRepository       = $templatesRepository;
    }

    /**
     * {@inheritDoc}
     */
    public static function getHelp(): string
    {
        return 'Process layouts queued for upgrade.';
    }

    /**
     * {@inheritDoc}
     */
    public function run(Args $args, OutputInterface $output, InputInterface $input)
    {
        $output->writeLine('Checking for jobs');
        while($message = $this->layoutUpgradeMessageQueue->receive()) {
            $tid      = (int)$message->getData();
            $template = $this->templatesRepository->findByID($tid);
            if (!$template) {
                $output->writeLine('Template not found!');
                $this->layoutUpgradeMessageQueue->delete($message);
                continue;
            }

            $location = Paths::combine($this->paths->dirTemplate($tid), $template['tmp_location']);
            if (!file_exists($location)) {
                $output->writeLine('Template file does not exist!');
                $this->layoutUpgradeMessageQueue->delete($message);
                continue;
            }

            try {
                $this->logger->debug(sprintf('Upgrading layouts for template %d', $tid));
                $output->writeLine('Upgrading %d', $tid);

                $this->layoutUpgrade->upgrade($tid, file_get_contents($location));
                $this->layoutUpgradeMessageQueue->delete($message);
            } catch (Exception $e) {
                $this->logger->error($e->getMessage());
                $output->errorLine($e->getMessage());
            }
        }
    }
}
