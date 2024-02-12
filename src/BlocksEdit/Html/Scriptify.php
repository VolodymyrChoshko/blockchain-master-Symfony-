<?php
namespace BlocksEdit\Html;

use simplehtmldom_1_5\simple_html_dom;

/**
 * Class Scriptify
 */
class Scriptify
{
    const WHITELIST = [
        'font-awesome'
    ];

    /**
     * @param simple_html_dom $dom
     *
     * @return simple_html_dom
     */
    public function hideScriptTags(simple_html_dom $dom): simple_html_dom
    {
        $toReplace = [];
        foreach($dom->find('script') as $item) {
            $src = $item->getAttribute('src');
            if ($src && !$this->isScriptWhitelisted($src)) {
                $item->setAttribute('data-be-src', $item->getAttribute('src'));
                $item->removeAttribute('src');
            } else if (!$src) {
                $toReplace[] = $item->outertext();
            }
        }

        $html = (string)$dom;
        foreach($toReplace as $outer) {
            $comment = sprintf('<!-- [be-hidden-start] %s [be-hidden-end] -->', $outer);
            $html    = str_replace($outer, $comment, $html);
        }

        return DomParser::fromString($html);
    }

    /**
     * @param simple_html_dom $dom
     *
     * @return simple_html_dom
     */
    public function restoreScriptTags(simple_html_dom $dom): simple_html_dom
    {
        foreach($dom->find('script') as $element) {
            if ($element->getAttribute('data-be-src')) {
                $element->setAttribute('src', $element->getAttribute('data-be-src'));
                $element->removeAttribute('data-be-src');
            }
        }

        $toReplace = [];
        foreach($dom->find('comment') as $element) {
            if (strpos($element->outertext(), '<!-- [be-hidden-start]') !== false) {
                $toReplace[] = $element->outertext();
            }
        }

        $html = (string)$dom;
        foreach($toReplace as $comment) {
            $replace = preg_replace('/<!-- \[be-hidden-start] (.*?) \[be-hidden-end] -->/s', '$1', $comment);
            $html    = str_replace($comment, $replace, $html);
        }

        return DomParser::fromString($html);
    }

    /**
     * @param string $src
     *
     * @return bool
     */
    protected function isScriptWhitelisted(string $src): bool
    {
        foreach(self::WHITELIST as $item) {
            if (stripos($src, $item) !== false) {
                return true;
            }
        }

        return false;
    }
}
