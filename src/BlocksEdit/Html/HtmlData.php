<?php
namespace BlocksEdit\Html;

use simplehtmldom_1_5\simple_html_dom;

/**
 * Class HtmlData
 */
class HtmlData
{
    /**
     * @var string
     */
    protected $html = '';

    /**
     * @var simple_html_dom
     */
    protected $dom;

    /**
     * @var int
     */
    protected $version = 0;

    /**
     * Constructor
     *
     * @param simple_html_dom $dom
     * @param int             $version
     */
    public function __construct(simple_html_dom $dom, int $version)
    {
        $this->dom     = $dom;
        $this->version = $version;
    }

    /**
     * @return string
     */
    public function getHtml(): string
    {
        return (string)$this->dom;
    }

    /**
     * @param string $html
     *
     * @return HtmlData
     */
    public function setHtml(string $html): HtmlData
    {
        $this->dom = DomParser::fromString($html);

        return $this;
    }

    /**
     * @return int
     */
    public function getVersion(): int
    {
        return $this->version;
    }

    /**
     * @param int $version
     *
     * @return HtmlData
     */
    public function setVersion(int $version): HtmlData
    {
        $this->version = $version;

        return $this;
    }

    /**
     * @return simple_html_dom
     */
    public function getDom(): simple_html_dom
    {
        return $this->dom;
    }

    /**
     * @return string
     */
    public function __toString()
    {
        return $this->html;
    }
}
