<?php
namespace BlocksEdit\Media;

use BlocksEdit\Http\Mime;

/**
 * Class AbstractCDN
 */
abstract class AbstractCDN implements CDNInterface
{
    /**
     * @var array
     */
    protected $config;

    /**
     * @var Mime
     */
    protected $mimeTypes;

    /**
     * Constructor
     *
     * @param array $config
     * @param Mime  $mimeTypes
     */
    public function __construct(array $config, Mime $mimeTypes)
    {
        $this->config    = $config;
        $this->mimeTypes = $mimeTypes;
    }
}
