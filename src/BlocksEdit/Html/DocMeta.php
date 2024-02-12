<?php
namespace BlocksEdit\Html;

use simplehtmldom_1_5\simple_html_dom;

/**
 * Class DocMeta
 */
class DocMeta
{
    /**
     * @param simple_html_dom $doc
     *
     * @return array
     */
    public function getMetaTags(simple_html_dom $doc): array
    {
        $domLang          = $doc->find('*[lang]', 0);
        $domDescription   = $doc->find('meta[name="description"]', 0);
        $domAppTitle      = $doc->find('meta[name="apple-mobile-web-app-title"]', 0);
        $domOGTitle       = $doc->find('meta[property="og:title"]', 0);
        $domOGDescription = $doc->find('meta[property="og:description"]', 0);
        $domOGImage       = $doc->find('meta[property="og:image"]', 0);
        $domOGUrl         = $doc->find('meta[property="og:url"]', 0);

        return [
            'lang'          => $domLang ? $domLang->getAttribute('lang') : null,
            'description'   => $domDescription ? $domDescription->getAttribute('content') : null,
            'appTitle'      => $domAppTitle ? $domAppTitle->getAttribute('content') : null,
            'ogTitle'       => $domOGTitle ? $domOGTitle->getAttribute('content') : null,
            'ogDescription' => $domOGDescription ? $domOGDescription->getAttribute('content') : null,
            'ogUrl'         => $domOGUrl ? $domOGUrl->getAttribute('content') : null,
            'ogImage'       => $domOGImage ? $domOGImage->getAttribute('content') : null,
            'ogImageID'     => $domOGImage ? $domOGImage->getAttribute('data-be-img-id') : null,
        ];
    }

    /**
     * @param simple_html_dom $email
     * @param simple_html_dom $template
     *
     * @return simple_html_dom
     */
    public function updateMetaTags(simple_html_dom $email, simple_html_dom $template): simple_html_dom
    {
        $meta = $this->getMetaTags($email);
        if (isset($meta['lang'])) {
            foreach($template->find('*[lang]') as $item) {
                $item->setAttribute('lang', $meta['lang']);
            }
        }

        if (isset($meta['description'])) {
            $tag = $template->find('meta[name="description"]', 0);
            if ($tag) {
                $tag->setAttribute('content', $meta['description']);
            }
        }
        if (isset($meta['appTitle'])) {
            $tag = $template->find('meta[name="apple-mobile-web-app-title"]', 0);
            if ($tag) {
                $tag->setAttribute('content', $meta['appTitle']);
            }
        }
        if (isset($meta['ogTitle'])) {
            $tag = $template->find('meta[property="og:title"]', 0);
            if ($tag) {
                $tag->setAttribute('content', $meta['ogTitle']);
            }
        }
        if (isset($meta['ogDescription'])) {
            $tag = $template->find('meta[property="og:description"]', 0);
            if ($tag) {
                $tag->setAttribute('content', $meta['ogDescription']);
            }
        }
        if (isset($meta['ogImage'])) {
            $tag = $template->find('meta[property="og:image"]', 0);
            if ($tag) {
                $tag->setAttribute('content', $meta['ogImage']);
                if (isset($meta['ogImageID'])) {
                    $tag->setAttribute('data-be-img-id', $meta['ogImageID']);
                    $tag->setAttribute('data-be-hosted', '1');
                }
            }
        }
        if (isset($meta['ogUrl'])) {
            $tag = $template->find('meta[property="og:url"]', 0);
            if ($tag) {
                $tag->setAttribute('content', $meta['ogUrl']);
            }
        }

        return $template;
    }
}
