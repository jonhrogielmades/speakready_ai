<?php

namespace App\Support;

use DOMComment;
use DOMDocument;
use DOMElement;
use DOMNode;

final class ModuleChapterHtml
{
    private const ALLOWED_ELEMENTS = [
        'h3',
        'p',
        'ul',
        'ol',
        'li',
        'strong',
        'em',
        'br',
    ];

    private const DROP_WITH_CONTENT = [
        'script',
        'style',
        'iframe',
        'object',
        'embed',
        'form',
        'input',
        'button',
        'textarea',
        'select',
        'option',
        'svg',
        'math',
        'link',
        'meta',
        'base',
        'template',
        'noscript',
    ];

    public static function sanitize(mixed $value): string
    {
        $html = trim((string) $value);

        if ($html === '') {
            return '';
        }

        if (! class_exists(DOMDocument::class)) {
            return self::sanitizeWithoutDom($html);
        }

        $document = new DOMDocument('1.0', 'UTF-8');
        $previousLibxmlState = libxml_use_internal_errors(true);

        try {
            $loaded = $document->loadHTML(
                '<!DOCTYPE html><html><head><meta charset="UTF-8"></head><body>'
                    .'<div id="speakready-module-chapter-root">'.$html.'</div></body></html>',
                LIBXML_NONET | LIBXML_HTML_NODEFDTD
            );
        } finally {
            libxml_clear_errors();
            libxml_use_internal_errors($previousLibxmlState);
        }

        if (! $loaded) {
            return self::sanitizeWithoutDom($html);
        }

        $root = $document->getElementById('speakready-module-chapter-root');
        if (! $root) {
            return self::sanitizeWithoutDom($html);
        }

        self::sanitizeChildren($root);

        $sanitized = '';
        foreach ($root->childNodes as $child) {
            $sanitized .= $document->saveHTML($child);
        }

        return trim($sanitized);
    }

    private static function sanitizeChildren(DOMNode $parent): void
    {
        $node = $parent->firstChild;

        while ($node) {
            $next = $node->nextSibling;

            if ($node instanceof DOMComment) {
                $parent->removeChild($node);
                $node = $next;
                continue;
            }

            if ($node instanceof DOMElement) {
                $tag = strtolower($node->tagName);

                if (in_array($tag, self::DROP_WITH_CONTENT, true)) {
                    $parent->removeChild($node);
                    $node = $next;
                    continue;
                }

                self::sanitizeChildren($node);

                if (! in_array($tag, self::ALLOWED_ELEMENTS, true)) {
                    while ($node->firstChild) {
                        $parent->insertBefore($node->firstChild, $node);
                    }

                    $parent->removeChild($node);
                    $node = $next;
                    continue;
                }

                while ($node->attributes->length > 0) {
                    $node->removeAttributeNode($node->attributes->item(0));
                }
            }

            $node = $next;
        }
    }

    private static function sanitizeWithoutDom(string $html): string
    {
        $dangerousTags = implode('|', self::DROP_WITH_CONTENT);
        $html = preg_replace(
            '/<('.$dangerousTags.')\b[^>]*>.*?<\/\1\s*>/is',
            '',
            $html
        ) ?? '';
        $html = preg_replace('/<('.$dangerousTags.')\b[^>]*\/?\s*>/is', '', $html) ?? '';
        $html = strip_tags($html, '<h3><p><ul><ol><li><strong><em><br>');

        return trim((string) preg_replace_callback(
            '/<\s*(\/?)\s*(h3|p|ul|ol|li|strong|em|br)\b[^>]*>/i',
            static function (array $matches): string {
                $tag = strtolower($matches[2]);

                if ($tag === 'br') {
                    return '<br>';
                }

                return '<'.($matches[1] === '/' ? '/' : '').$tag.'>';
            },
            $html
        ));
    }
}
