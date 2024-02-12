<?php
namespace Service\Export;

use BlocksEdit\Html\BeautifyHtml;
use BlocksEdit\Html\DomParser;
use HTMLPurifier;
use HTMLPurifier_Config;
use ForceUTF8\Encoding;
use simplehtmldom_1_5\simple_html_dom_node;

/**
 * Class HtmlToText
 */
class HtmlToText
{
    /**
     * @var HTMLPurifier
     */
    protected $purifier;

    /**
     * Constructor
     */
    public function __construct()
    {
        $config = HTMLPurifier_Config::createDefault();
        $config->set('Cache.DefinitionImpl', null);
        $this->purifier = new HTMLPurifier($config);
    }

    /**
     * @param string $html
     *
     * @return string
     */
    public function toText(string $html): string
    {
        $dom = $this->stripTags($html);
        $this->formatImages($dom);
        $this->formatLinks($dom);

        foreach ($dom->find('h1,h2,h3,h4,h5,h6') as $item) {
            $item->outertext = '** ' . $this->trim($item->innertext()) . "\n------------------------------------------------------------";
        }

        $lines = $this->toLines((string)$dom);
        $html  = join("\n\n", $lines);
        $html  = str_replace("\n------------------------------------------------------------", '------------------------------------------------------------', $html);
        $html = $this->trim(strip_tags($html));

        return Encoding::toUTF8($html);
    }

    /**
     * @param string $html
     *
     * @return string
     */
    public function toRichText(string $html): string
    {
        $dom = $this->stripTags($html);
        $this->formatImages($dom);
        $this->formatLinks($dom);

        foreach ($dom->find('i') as $item) {
            $item->outertext = '{\i ' . trim($item->innertext()) . '\i0}';
        }
        foreach ($dom->find('h1,h2,h3,h4,h5,h6,b,strong') as $item) {
            $item->outertext = '{\b ' . trim(strip_tags($item->innertext())) . '\b0}';
        }
        /*foreach($dom->find('b') as $item) {
            $item->outertext = '{\b ' . trim($item->innertext()) . '\b0}';
        }
        foreach($dom->find('strong') as $item) {
            $item->outertext = '{\b ' . trim($item->innertext()) . '\b0}';
        }*/

        $lines = $this->toLines((string)$dom);
        $html = join('\\line\\line', $lines);
        $html = str_replace('’', "\\'", $html);
        $html = $this->purifier->purify($html);

/*        $document = new Document('{\rtf1\ansi ' . $html . '}');
        echo $document->save('/media/sean/work2/www/blocksedit/test.rtf');
        die();*/

        return '{\rtf1\ansi ' . $html . '}';
    }

    /**
     * @param simple_html_dom_node $dom
     *
     * @return simple_html_dom_node
     */
    protected function formatLinks(simple_html_dom_node $dom): simple_html_dom_node
    {
        foreach ($dom->find('a') as $item) {
            $inner = '';
            $first = $item->firstChild();
            if ($first && $first->tag === 'img' && $this->trim($first->getAttribute('alt'))) {
                $inner = 'Image: ' . $first->getAttribute('alt') . ' (' . $item->getAttribute('href') . ')';
            } else {
                if ($this->trim($item->innertext())) {
                    $inner = $this->trim($item->innertext()) . ' (' . $item->getAttribute('href') . ')';
                }
            }
            $item->outertext = $inner;
        }

        return $dom;
    }

    /**
     * @param simple_html_dom_node $dom
     *
     * @return simple_html_dom_node
     */
    protected function formatImages(simple_html_dom_node $dom): simple_html_dom_node
    {
        foreach ($dom->find('img') as $item) {
            if ($this->trim($item->getAttribute('alt'))) {
                $item->outertext = 'Image: ' . $this->trim($item->getAttribute('alt'));
            } else {
                $item->outertext = '';
            }
        }

        return $dom;
    }

    /**
     * @param string $html
     *
     * @return simple_html_dom_node
     */
    protected function stripTags(string $html): simple_html_dom_node
    {
        /*$dom = DomParser::fromString($html);
        foreach ($dom->find('.be-code-edit') as $item) {
            $item->outertext = '';
        }
        foreach ($dom->find('.block-script-sec') as $item) {
            $item->outertext = '';
        }
        $html = (string)$dom;*/

        $beautify = new BeautifyHtml();
        $html = $beautify->beautify($html);
        $config = HTMLPurifier_Config::createDefault();
        $config->set('HTML.Allowed', 'a[href],img[src|alt],b,strong,i,h1,h2,h3,h4,h5,h6');
        $config->set('Cache.DefinitionImpl', null);
        $purifier = new HTMLPurifier($config);
        $html = $purifier->purify($html);

        // $html = strip_tags($html, '<body><a><img><b><strong><i><h1><h2><h3><h4><h5><h6>');
        $dom = DomParser::fromString('<body>' . $html . '</body>');

        return $dom->find('body', 0);
    }

    /**
     * @param string $text
     *
     * @return array
     */
    protected function toLines(string $text): array
    {
        $dom  = DomParser::fromString($text);
        $body = $dom->find('body', 0);
        if ($body) {
            $text = $this->trim((string)$body->innertext());
        } else {
            $text = $this->trim((string)$dom);
        }

        $lines = [];
        $text = str_replace('&nbsp;', ' ', $text);
        $split = preg_split('/\r?\n/', $text);
        foreach ($split as $line) {
            $line = $this->trim($line);
            if ($line) {
                $lines[] = $line;
            }
        }

        return $lines;
    }

    /**
     * @param string $trim
     *
     * @return string
     */
    protected function trim(string $trim): string
    {
        return trim($trim, " \t\n\r\0\x0B\xC2\xA0");
    }
}
