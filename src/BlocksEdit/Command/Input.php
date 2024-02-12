<?php
namespace BlocksEdit\Command;

/**
 * Class Input
 */
class Input implements InputInterface
{
    /**
     * @var OutputInterface
     */
    protected $output;

    /**
     * Constructor
     *
     * @param OutputInterface $output
     */
    public function __construct(OutputInterface $output)
    {
        $this->output = $output;
    }

    /**
     * {@inheritDoc}
     */
    public function read(string $msg, $default = '')
    {
        $value = readline(sprintf('%s: ', $msg));
        if (!$value) {
            return $default;
        }

        return $value;
    }

    /**
     * {@inheritDoc}
     */
    public function readOrOpt(string $msg, string $opt, $default = '')
    {
        if ($opt) {
            return $opt;
        }

        return $this->read($msg, $default);
    }

    /**
     * {@inheritDoc}
     */
    public function readOption(string $msg, array $options = ['y', 'n'], $default = 'n')
    {
        $displayOptions = [];
        foreach($options as $option) {
            if ($option === $default) {
                $displayOptions[] = strtoupper($option);
            } else {
                $displayOptions[] = strtolower($option);
            }
        }

        $msg = sprintf('%s [%s]: ', $msg, join(',', $displayOptions));
        while(true) {
            $value = strtolower(readline($msg));
            if (!$value) {
                return $default;
            }
            if (!in_array($value, $options)) {
                $this->output->writeLine('Invalid option.');
                continue;
            }
            break;
        }

        return $value;
    }
}
