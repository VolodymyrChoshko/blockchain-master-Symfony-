<?php
namespace Command;

use BlocksEdit\Command\Args;
use BlocksEdit\Command\Command;
use BlocksEdit\Command\InputInterface;
use BlocksEdit\Command\OutputInterface;
use BlocksEdit\Media\CDNInterface;
use Repository\ImagesRepository;

/**
 * Class CdnCleanCommand
 */
class CdnCleanCommand extends Command
{
    static $name = 'cdn:clean';

    /**
     * @var ImagesRepository
     */
    protected $imagesRepository;

    /**
     * @var CDNInterface
     */
    protected $cdn;

    /**
     * {@inheritDoc}
     */
    public static function getHelp(): string
    {
        return 'Removes unused CDN images.';
    }

    /**
     * Constructor
     *
     * @param ImagesRepository $imagesRepository
     * @param CDNInterface     $cdn
     */
    public function __construct(ImagesRepository $imagesRepository, CDNInterface $cdn)
    {
        $this->imagesRepository = $imagesRepository;
        $this->cdn              = $cdn;
    }

    /**
     * {@inheritDoc}
     */
    public function run(Args $args, OutputInterface $output, InputInterface $input)
    {
        foreach($this->imagesRepository->findByTempUndeleted() as $image) {
            $output->writeLine('Deleting ' . $image->getCdnUrl());
            if (substr($image->getCdnUrl(), 0, 4) === 'http') {
                $this->cdn->removeByURL($image->getCdnUrl());
                $image
                    ->setIsDeleted(true)
                    ->setIsCdnDeleted(true);
                $this->imagesRepository->update($image);
            }
        }

        foreach($this->imagesRepository->findByNextUnused() as $image) {
            $output->writeLine('Deleting ' . $image->getCdnUrl());
            if (substr($image->getCdnUrl(), 0, 4) === 'http') {
                $this->cdn->removeByURL($image->getCdnUrl());
                $image
                    ->setIsDeleted(true)
                    ->setIsCdnDeleted(true);
                $this->imagesRepository->update($image);
            }
        }

        foreach($this->imagesRepository->findByCdnUndeleted() as $image) {
            $output->writeLine('Deleting ' . $image->getCdnUrl());
            if (substr($image->getCdnUrl(), 0, 4) === 'http') {
                $this->cdn->removeByURL($image->getCdnUrl());
                $image->setIsCdnDeleted(true);
                $this->imagesRepository->update($image);
            }
        }
    }
}
