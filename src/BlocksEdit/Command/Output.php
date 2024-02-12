<?php
namespace BlocksEdit\Command;

/**
 * Class Output
 */
class Output implements OutputInterface
{
    /**
     * @var resource[]
     */
    protected $stdOut = [];

    /**
     * @var resource[]
     */
    protected $stdErr = [];

    /**
     * Constructor
     *
     * @param resource|null $stdOut
     * @param resource|null $stdErr
     */
    public function __construct($stdOut = null, $stdErr = null)
    {
        $this->stdOut[] = $stdOut ?? STDOUT;
        $this->stdErr[] = $stdErr ?? STDERR;
    }

    /**
     * {@inheritDoc}
     */
    public function getStdOut()
    {
        return $this->stdOut[0];
    }

    /**
     * {@inheritDoc}
     */
    public function getStdErr()
    {
        return $this->stdErr[0];
    }

    /**
     * {@inheritDoc}
     */
    public function appendStdOut($fileDescriptor)
    {
        $this->stdOut[] = $fileDescriptor;
    }

    /**
     * {@inheritDoc}
     */
    public function appendStdErr($fileDescriptor)
    {
        $this->stdErr[] = $fileDescriptor;
    }

    /**
     * {@inheritDoc}
     */
    public function writeLine(string $str, string ...$args): bool
    {
        if (count($args)) {
            $str = vsprintf($str . "\n", $args);
        } else {
            $str = $str . "\n";
        }

        foreach($this->stdOut as $out) {
            fwrite($out, $str);
        }

        return true;
    }

    /**
     * {@inheritDoc}
     */
    public function errorLine(string $str, string ...$args): bool
    {
        if (count($args)) {
            $str = vsprintf($str . "\n", $args);
        } else {
            $str = $str . "\n";
        }

        foreach($this->stdErr as $err) {
            fwrite($err, $str);
        }

        return true;
    }
}
