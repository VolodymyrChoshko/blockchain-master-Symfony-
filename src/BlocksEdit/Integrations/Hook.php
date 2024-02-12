<?php
namespace BlocksEdit\Integrations;

use BlocksEdit\Http\Request;
use Entity\Source;

/**
 * Class Hook
 */
class Hook
{
    /**
     * @var string
     */
    protected $name;

    /**
     * @var Request
     */
    protected $request;

    /**
     * @var array
     */
    protected $email = [];

    /**
     * @var array
     */
    protected $template = [];

    /**
     * @var array
     */
    protected $responses = [];

    /**
     * @var Source[]
     */
    protected $dispatchedSources = [];

    /**
     * Constructor
     *
     * @param string  $name
     * @param Request $request
     * @param array   $email
     * @param array   $template
     */
    public function __construct(string $name, Request $request, array $email = [], array $template = [])
    {
        $this->name     = $name;
        $this->request  = $request;
        $this->email    = $email;
        $this->template = $template;
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @return Request
     */
    public function getRequest(): Request
    {
        return $this->request;
    }

    /**
     * @return array
     */
    public function getEmail(): array
    {
        return $this->email;
    }

    /**
     * @return array
     */
    public function getTemplate(): array
    {
        return $this->template;
    }

    /**
     * @param mixed $resp
     *
     * @return $this
     */
    public function addResponse($resp): Hook
    {
        $this->responses[] = $resp;

        return $this;
    }

    /**
     * @return array
     */
    public function getResponses(): array
    {
        return $this->responses;
    }

    /**
     * @param Source $source
     *
     * @return $this
     */
    public function addDispatchedSource(Source $source): Hook
    {
        $this->dispatchedSources[] = $source;

        return $this;
    }

    /**
     * @return Source[]
     */
    public function getDispatchedSources(): array
    {
        return $this->dispatchedSources;
    }
}
