<?php
namespace BlocksEdit\Html;

use simplehtmldom_1_5\simple_html_dom;
use simplehtmldom_1_5\simple_html_dom_node;
use Sunra\PhpSimple\HtmlDomParser;

/**
 * Class DomParser
 *
 * Wraps HtmlDomParser to use our required file_get_html() params.
 */
class DomParser
{
    /**
     * @param string $filename
     *
     * @return simple_html_dom
     */
    public static function fromFile(string $filename): simple_html_dom
    {
        return HtmlDomParser::file_get_html($filename, false, null, 0, -1, false, true, 'UTF-8', false);
    }

    /**
     * @param string $string
     *
     * @return simple_html_dom
     */
    public static function fromString(string $string): simple_html_dom
    {
        return HtmlDomParser::str_get_html($string, false, true, 'UTF-8', false);
    }

    /**
     * @param simple_html_dom $dom
     * @param string          $selector
     * @param                 $idx
     *
     * @return simple_html_dom_node[]|simple_html_dom_node|null
     */
    public static function blockQuery(simple_html_dom $dom, string $selector, $idx = null)
    {
        $item = $dom->find('.' . $selector, $idx);
        if ($item) {
            return $item;
        }

        $found = [];
        foreach($dom->find('*[style]') as $item) {
            $style = $item->getAttribute('style');
            if ($style && strpos($style, '-' . $selector) !== false) {
                $found[] = $item;
            }
        }

        if (count($found) === 0) {
            return null;
        }
        if ($idx !== null) {
            return $found[$idx];
        }

        return $found;
    }
}
