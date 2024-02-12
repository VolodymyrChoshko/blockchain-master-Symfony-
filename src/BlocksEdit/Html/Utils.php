<?php
namespace BlocksEdit\Html;

use simplehtmldom_1_5\simple_html_dom;

/**
 * Class Utils
 */
class Utils
{
    /**
     * @param simple_html_dom $dom
     *
     * @return simple_html_dom
     */
    public static function removeBlockHide(simple_html_dom $dom): simple_html_dom
    {
        $html = (string)$dom;
        $html = str_replace('<!-- block-hide -->', '<!-- block-hide-hidden ', $html);
        $html = str_replace('<!-- end-block-hide -->', ' hidden-end-block-hide -->', $html);

        return DomParser::fromString($html);
    }

    /**
     * @param simple_html_dom $dom
     *
     * @return simple_html_dom
     */
    public static function restoreBlockHide(simple_html_dom $dom): simple_html_dom
    {
        $html = (string)$dom;
        $html = str_replace('<!-- block-hide-hidden ', '<!-- block-hide -->', $html);
        $html = str_replace(' hidden-end-block-hide -->', '<!-- end-block-hide -->', $html);

        return DomParser::fromString($html);
    }
}
