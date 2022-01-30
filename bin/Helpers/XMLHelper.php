<?php

namespace App\Helpers;

use DOMComment;
use DOMElement;

class XMLHelper
{
    public static function toArray(DOMElement $domElement)
    {
        $children = [];

        foreach ($domElement->childNodes as $childElement) {
            if ($childElement instanceof DOMComment) {
                continue;
            }
            if ($childElement->nodeName == '#text') {
                continue;
            }

            $attributes = [];
            foreach ($childElement->attributes as $attrName => $attrValue) {
                // dump(compact('attrName', 'attrValue'));
                $attributes[$attrName] = $attrValue->value;
            }


            $child = [
                // 'element' => $childElement,
                'type' => $childElement->nodeName,
                'value' => $childElement->nodeValue,
                'attributes' => $attributes,
                'lineNumber' => $childElement->getLineNo(),
                'children' => self::toArray($childElement)
            ];

            $children[] = $child;
        }

        return $children;
    }
}
