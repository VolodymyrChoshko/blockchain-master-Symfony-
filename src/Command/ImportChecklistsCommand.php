<?php
namespace Command;

use BlocksEdit\Command\Args;
use BlocksEdit\Command\Command;
use BlocksEdit\Command\InputInterface;
use BlocksEdit\Command\OutputInterface;
use Repository\TemplatesRepository;
use BlocksEdit\System\Required;

/**
 * Class ImportChecklistsCommand
 */
class ImportChecklistsCommand extends Command
{
    static $name = 'import:checklists';

    /**
     * @var TemplatesRepository
     */
    protected $templatesRepository;

    /**
     * {@inheritDoc}
     */
    public static function getHelp(): string
    {
        return 'Import checklists.';
    }

    /**
     * @Required()
     * @param TemplatesRepository $templatesRepository
     */
    public function setTemplatesRepository(TemplatesRepository $templatesRepository)
    {
        $this->templatesRepository = $templatesRepository;
    }

    /**
     * {@inheritDoc}
     */
    public function run(Args $args, OutputInterface $output, InputInterface $input)
    {
        $settings = [
            'enabled' => true,
            'altText' => true,
            'links' => true,
            'previewText' => true,
            'trackingParams' => true
        ];
        foreach($this->templatesRepository->findGenerator('*') as $i => $template) {
            $template = $this->templatesRepository->findByID($template['tmp_id'], true);
            if (!$template) {
                continue;
            }
            $settings['trackingParams'] = $template->isTpaEnabled();
            $template->setChecklistSettings($settings);
            $this->templatesRepository->update($template);
            dump($template->getId());
        }
    }
}
