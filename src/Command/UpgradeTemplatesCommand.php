<?php
namespace Command;

use BlocksEdit\System\Required;
use BlocksEdit\Command\Args;
use BlocksEdit\Command\Command;
use BlocksEdit\Command\InputInterface;
use BlocksEdit\Command\OutputInterface;
use Exception;
use Repository\TemplatesRepository;
use Service\TemplateUpgrader;

/**
 * Class UpgradeTemplatesCommand
 */
class UpgradeTemplatesCommand extends Command
{
    static $name = 'upgrade:templates';

    /**
     * @var TemplatesRepository
     */
    protected $templatesRepository;

    /**
     * @Required()
     * @param TemplatesRepository $templatesRepository
     */
    public function setTemplatesRepository(TemplatesRepository $templatesRepository)
    {
        $this->templatesRepository = $templatesRepository;
    }

    /**
     * @var TemplateUpgrader
     */
    protected $templateUpgrader;

    /**
     * @Required()
     * @param TemplateUpgrader $templateUpgrader
     */
    public function setTemplateUpgrader(TemplateUpgrader $templateUpgrader)
    {
        $this->templateUpgrader = $templateUpgrader;
    }

    /**
     * {@inheritDoc}
     */
    public static function getHelp(): string
    {
        return 'Upgrade templates to use versioning.';
    }

    /**
     * {@inheritDoc}
     */
    public function run(Args $args, OutputInterface $output, InputInterface $input)
    {
        $output->writeLine("Have you? sudo chmod 0777 public/screenshots/templates");
        $output->writeLine("Have you? sudo chmod 0777 public/screenshots/components");
        $output->writeLine("Have you? sudo chmod 0777 public/screenshots/components/mobile");
        $output->writeLine("Have you? sudo chmod 0777 public/screenshots/sections");
        $output->writeLine("Have you? sudo chmod 0777 public/screenshots/sections/mobile");
        $val = $input->read("Are you ready? [N/y]");
        if ($val !== 'y' && $val !== 'Y') {
            return;
        }

        $this->templateUpgrader->setOutput($output);
        $outFile = fopen('upgrade-out-7.log', 'w');
        $errFile = fopen('upgrade-err-7.log', 'w');
        $output->appendStdOut($outFile);
        $output->appendStdErr($errFile);

        $offset = $args->getArg(0, 0);
        $output->writeLine('Upgrade starting from offset %d.', $offset);
        foreach($this->templatesRepository->findGenerator('*', $offset) as $i => $template) {
            try {
                $output->writeLine('Checking #%d %d...', $i, $template['tmp_id']);
                // if (empty($template['tmp_tmh_enabled'])) {
                    $this->templateUpgrader->upgrade2(
                        $template['tmp_id'],
                        $template['tmp_usr_id']
                    );
                // }
            } catch (Exception $e) {
                $output->errorLine("\tERROR: " . $e->getMessage());
            }
        }

        $output->writeLine("DONE!");
        fclose($outFile);
        fclose($errFile);
    }
}
