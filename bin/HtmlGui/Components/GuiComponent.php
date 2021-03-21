<?php

namespace App\HtmlGui\Components;

use App\Compiler\Exceptions\CompilerException;
use App\Helpers\XMLHelper;
use App\HtmlGui\GuiAttributes;
use App\HtmlGui\GuiCollection;
use App\HtmlGui\GuiStyle;
use Tightenco\Collect\Support\Collection;

class GuiComponent
{
    public static $rgxAttribute = '/([\w\-]+)\s*=\s*"([\w\W]+?)\s*(?<!\\\)"(\\s|\\/?>|[\w\-])/';
    public static $attrReplaces = [
        '<' => '&lt;',
        '>' => '&gt;',
        '&' => '&amp;',
        '"' => '&quot;',
        '\'' => '&apos;',
    ];

    protected static $componentRegistry = [];

    public $id;
    protected $uniqid;
    public $type = 'unknown';
    public $text = '';
    public $x = '0';
    public $y = '0';
    public $classes = [];
    public $canvasX = 0;
    public $canvasY = 0;
    public $style;
    public $var = null;
    public $attributes;
    public $props;
    public $propValues;
    public $componentType = null;
    protected $components = [];
    protected $parent = null;
    protected $children;
    protected $blacklist = [];

    protected $code = null;
    protected $lineNumber = null;
    protected $file = null;

    private static $TYPE_REGISTRY = [
        //custom types are stored here
    ];

    const TYPES = [
        'div' => GuiTemplate::class,
        'button' => GuiButton::class,
        'label' => GuiLabel::class,
        'text-field' => GuiTextField::class,
        'script' => GuiScript::class,
    ];

    const SPECIAL_TYPES = [
        'component', 'prop'
    ];

    const TYPE_BLACKLIST = [
        'style', '@attributes'
    ];

    public static function registerType($tagName, $options = [])
    {
    }

    public static function create($type, GuiComponent $parent = null): GuiComponent
    {
        $typeClass = self::TYPES[$type];
        $component = new $typeClass;

        if ($parent) {
            $parent->addChild($component);
        }

        return $component;
    }

    public function __construct()
    {
        $this->uniqid = uniqid();
        $this->id = $this->uniqid;
        $this->style = new GuiStyle;
        $this->attributes = new GuiAttributes;
        $this->children = new GuiCollection;
        $this->props = new Collection;
        $this->propValues = new GuiAttributes;

        self::$componentRegistry[$this->uniqid] = $this;
    }

    public function setExceptionData($file, $lineNumber)
    {
        $this->file = $file;
        $this->lineNumber = $lineNumber;
    }

    public function isComponent()
    {
        return !is_null($this->componentType);
    }

    public function getComponents()
    {
        $root = $this->getRoot();
        if ($root) {
            return $root->getComponents();
        }

        return $this->components;
    }

    public function getComponent($name)
    {
        if (!$this->hasComponent($name)) {
            return null;
        }

        return $this->getComponents()[$name];
    }

    public function getChildren()
    {
        return new GuiCollection($this->children);
    }


    public function getId()
    {
        return $this->id;
    }

    public function getUniqid()
    {
        return $this->uniqid;
    }

    public function getRenderVar($prefix = '', $suffix = '')
    {
        if (empty($this->var)) {
            return $prefix . 'gui_component_' . $this->uniqid . $suffix;
        }
        return $prefix . $this->var . $suffix;
    }

    public function getJsId()
    {
        $useId = $this->attributes->get('id', '') ?: $this->uniqid;
        $showId = $useId !== $this->uniqid;
        $id = '';

        if ($showId) {
            $id .= $useId;
        }

        if ($this->attributes->has('key')) {
            if ($showId) {
                $id .= '_\' + ';
            }
            $id .= $this->attributes->get('key', null, true);
        }
        return $id;
    }

    public function getRenderId()
    {
        $id = $this->getJsId();

        if (str_ends_with($id, ' + ')) {
            $id =  preg_replace('/\s*\+\s*$/', '', $id);
        }

        return $id;
    }

    public function getStaticIds()
    {
        $jsId = $this->attributes->get('id', '') ?: $this->uniqid;
        $max = abs(intval($this->attributes->get('max', $this->hasParent() ? $this->getParent()->attributes->get('max', 0, true) : 0, true)));

        if ($max > 0) {
            $ids = [];
            for ($i = 0; $i < $max; $i++) {
                $ids[] = $jsId . '_' . $i;
            }

            return $ids;
        }

        return [$jsId];
    }

    public function setId($id)
    {
        return $this->id = $id;
    }

    public function getCanvasX()
    {
        $x = 0;
        if ($this->hasParent()) {
            $x = $this->getParent()->getCanvasX();
        }

        $x += $this->style->get('margin.left', 0);
        $x += $this->style->get('border.width', 0);
        $x += $this->style->get('padding.left', 0);
        $x += $this->canvasX;

        return $x;
    }

    public function getCanvasY()
    {
        $y = 0;
        if ($this->hasParent()) {
            $y = $this->getParent()->getCanvasY();
        }

        $y += $this->style->get('margin.top', 0);
        $y += $this->style->get('border.width', 0);
        $y += $this->style->get('padding.top', 0);
        $y += $this->canvasY;

        return $y;
    }

    public function getCanvasWidth()
    {
        $width = $this->style->get('width', 0);
        $width += $this->style->get('padding.right', 0);
        $width += $this->style->get('border.width', 0);
        $width += $this->style->get('margin.right', 0);

        return $width;
    }

    public function getCanvasHeight()
    {
        $height = $this->style->get('width', 0);
        $height += $this->style->get('padding.bottom', 0);
        $height += $this->style->get('border.width', 0);
        $height += $this->style->get('margin.bottom', 0);

        return $height;
    }

    public function getCanvasEndX()
    {
        return $this->getCanvasX() + $this->getCanvasWidth();
    }

    public function getCanvasEndY()
    {
        return $this->getCanvasY() + $this->getCanvasWidth();
    }

    public function getRenderSuffixX()
    {
        return ($this->x == '0' || is_null($this->x) ? '' : ' + (' . $this->x . ')');
    }

    public function getRenderSuffixY()
    {
        return ($this->y == '0' || is_null($this->y) ? '' : ' + (' . $this->y . ')');
    }

    public function getRenderX()
    {
        $parents = $this->getParents()->reverse();
        $parents->push($this);


        return $this->getCanvasX() . $parents->map(function ($parent) {
            return $parent->getRenderSuffixX();
        })->join('');
    }

    public function getRenderY()
    {
        $parents = $this->getParents()->reverse();
        $parents->push($this);


        return $this->getCanvasY() . $parents->map(function ($parent) {
            return $parent->getRenderSuffixY();
        })->join('');
    }


    public function setParent(?GuiComponent $guiComponent)
    {
        //remove from previous parent 
        if ($this->hasParent()) {
            $this->getParent()->removeChild($this->uniqid);
        }
        if (!$guiComponent) {
            $this->parent = null;
        } else {
            $this->parent = $guiComponent->getUniqid();
        }

        return $this;
    }

    public function hasParent()
    {
        return $this->parent && isset(self::$componentRegistry[$this->parent]);
    }

    public function getParent()
    {
        return self::$componentRegistry[$this->parent] ?? null;
    }

    public function getParents()
    {
        $parents = [];
        $scannedIds = [];

        $current = $this;
        while (!is_null($current = $current->getParent())) {
            if (in_array($current->getId(), $scannedIds)) {
                break;
            }
            $scannedIds[] = $current->getId();
            $parents[] = $current;
        }

        return collect($parents);
    }

    public function getRoot()
    {
        return $this->getParents()->reverse()->values()[0] ?? null;
    }

    public function hasComponent($name)
    {
        return isset($this->getComponents()[$name]);
    }

    public function addComponent($name, $componentArray)
    {
        $root = $this->getRoot();
        if ($root) {
            $root->addComponent($name, $componentArray);
        } else {
            $this->components[$name] = $componentArray;
        }
    }

    public function addChild($children, $id = null)
    {
        if (!is_array($children)) {
            $children = [$children];
        }

        foreach ($children as $child) {
            $child->setParent($this);
            if ($id) {
                $child->setId($id);
            }
            $this->children[$child->getUniqid()] = $child;
        }

        return $this;
    }

    public function getChild($childUniqid)
    {
        return $this->children[$childUniqid] ?? null;
    }

    public function getNthChild($childIndex)
    {
        return $this->children->values()[$childIndex] ?? null;
    }

    public function hasChild($id)
    {
        return $this->children->has($id);
    }

    public function removeChild($childUniqids)
    {
        if (!is_array($childUniqids)) {
            $childUniqids = [$childUniqids];
        }

        foreach ($childUniqids as $childUniqid) {
            if (!$this->hasChild($childUniqid)) {
                continue;
            }

            $child = $this->getChild($childUniqid);
            $child->setParent(null);
            $this->children->forget($childUniqid);
        }

        return $this;
    }


    public function renderGuiIds()
    {
        //TODO:
        // - No For loops for ids,
        // - Use 'max' attributes to hardgen ids (10 rows of ids/generated for loop instead of template for loop)
        return [
            $this->getRenderId()
        ];
    }

    public function renderJs()
    {
        //
    }

    public function onXmlSet($attribute, $value, $isJs = false)
    {
        //
    }

    public function isAttributeBlacklisted($attribute)
    {
        return in_array($attribute, $this->blacklist);
    }


    public static function fromFile($file)
    {
        $contents = file_get_contents($file);
        $gui = self::fromXml($contents, $file);

        return $gui;
    }

    public static function getXmlArray($xmlString)
    {
        $contents = "<gui>{$xmlString}</gui>";
        $xml = new \DOMDocument;
        $contents = preg_replace_callback(GuiComponent::$rgxAttribute, function ($matches) {
            $escaped = str_replace(
                array_keys(GuiComponent::$attrReplaces),
                array_values(GuiComponent::$attrReplaces),
                $matches[2]
            );

            return "$matches[1]=\"$escaped\"$matches[3]";
        }, $contents);

        $contents = trim($contents);
        $xml->loadXML($contents);
        $xmlRootElement = $xml->documentElement;

        $xmlData = XMLHelper::toArray($xmlRootElement);
        if (count($xmlData) > 1) {
            throw new CompilerException('HTML Gui can only have one root element!');
        }
        return $xmlData[0];
    }

    public static function fromXml($xmlString, $file = null)
    {
        $root = self::fromArray(self::getXmlArray($xmlString), compact('file'));

        return $root;
    }

    public static function fromArray($xmlArray, $options = [], GuiComponent $parent = null)
    {
        //Loop and format attributes
        foreach ($xmlArray['attributes'] as $attrName => $attrValue) {
            $isJs = false;
            $oldAttrName = $attrName;
            if (strpos($attrName, 'js-') === 0) {
                $attrName = preg_replace('/^js\-/', '', $attrName);
                $isJs = true;
            }


            $attrValue = str_replace(
                array_values(self::$attrReplaces),
                array_keys(self::$attrReplaces),
                $attrValue
            );
            unset($xmlArray['attributes'][$oldAttrName]);

            $xmlArray['old_attributes'][$oldAttrName] = $attrValue;
            $xmlArray['attributes'][$attrName] = [
                'value' => $attrValue,
                'isJs' => $isJs
            ];
        }

        $component = null;
        $typeClass = self::TYPES[$xmlArray['type']] ?? null;
        if ($typeClass) {
            $component = new $typeClass;
        }
        if ($component) {
            if (isset($xmlArray['lineNumber']) && isset($options['file'])) {
                $component->setExceptionData($xmlArray['lineNumber'], $options['file']);
            }
            if (isset($xmlArray['old_attributes'])) {
                $component->attributes = new GuiAttributes($xmlArray['old_attributes']);
            }

            foreach ($xmlArray['attributes'] as $attrName => $attrData) {
                $attrValue = $attrData['value'];
                $isJs = $attrData['isJs'];

                switch ($attrName) {
                    case 'x':
                    case 'y':
                        if ($isJs) {
                            $component->{$attrName} = $attrValue;
                        } else {
                            $component->{'canvas' . strtoupper($attrName)} = intval($attrValue);
                        }
                        break;
                    case 'id':
                        $component->setId($attrValue);
                        break;
                    case 'var':
                        $component->var = $attrValue;
                        break;
                    case 'text':
                        $component->text = $isJs ? $attrValue : json_encode($attrValue);
                        break;
                }
            }
            if ($parent) {
                $parent->addChild($component);
            }
            if (count($xmlArray['children'])) {
                foreach ($xmlArray['children'] as $xmlChild) {
                    self::fromArray($xmlChild, $options, $component);
                }
            } else if (empty($component->text)) {
                $component->text = $xmlArray['value'];
            }
            return $component;
        } else {
            switch ($xmlArray['type']) {
                case 'component':
                    if (!$parent) {
                        break;
                    }
                    $attributes = new GuiAttributes($xmlArray['old_attributes']);
                    if (!$attributes->has('name')) {
                        throw new CompilerException('The \'name\' attribute on <component> is required!');
                    }
                    if (!$attributes->has('file')) {
                        throw new CompilerException('The \'file\' attribute on <component> is required!');
                    }

                    $name = $attributes->get('name', null, true);
                    $file = $attributes->get('file', null, true);

                    $rgxCurrentDir = '/^\.\//';
                    if (preg_match($rgxCurrentDir, $file)) {
                        $file = preg_replace($rgxCurrentDir, dirname($options['file'] ?? '') . '/', $file);
                    }

                    if (!file_exists($file)) {
                        throw new CompilerException('File in <component> #6' . $file . '#r does not exist!');
                    }

                    if ($parent->hasComponent($name)) {
                        throw new CompilerException('Custom component \'' . $name . '\' already registered in this gui instance');
                    }
                    $contents = file_get_contents($file);
                    $parent->addComponent($name, self::getXmlArray($contents));
                    break;
                case 'prop';
                    if (!$parent) {
                        break;
                    }
                    $propName = $xmlArray['attributes']['name']['value'] ?? null;
                    if (!$propName) {
                        throw new CompilerException('The \'name\' attribute is required on a <prop>');
                    }
                    $propDefault = $xmlArray['attributes']['default'] ?? ['isJs' => true, 'value' => 'undefined'];
                    $propDefault['isJs'] = true;

                    $propRequired = $xmlArray['attributes']['required']['value'] ?? false;
                    $parent->props[$propName] = [
                        'name' => $propName,
                        'default' => $propDefault,
                        'required' => $propRequired
                    ];


                    break;
            }
            if ($parent && !in_array($xmlArray['type'], ['component', 'prop'])) {
                if ($parent->hasComponent($xmlArray['type'])) {
                    $componentArray = $parent->getComponent($xmlArray['type']);
                    $component = self::fromArray($componentArray, $options, $parent);
                    $component->componentType = $xmlArray['type'];
                    $component->propValues = new GuiAttributes($xmlArray['old_attributes']);
                }
            }

            if (count($xmlArray['children'] ?? [])) {
                foreach ($xmlArray['children'] as $xmlChild) {
                    self::fromArray($xmlChild, $options, $component);
                }
            }
        }

        return $component;
    }

    public function render($renderFunc = 'renderJs')
    {
        $renderFuncOptions = [
            'renderJs' => [
                'renderConstructs' => true,
                'renderComponentFuncs' => true,
                'baseAttributes' => false,
            ],
            'renderIds' => [
                'renderConstructs' => false,
                'renderComponentFuncs' => true,
                'baseAttributes' => true,
            ]
        ];
        if (!in_array($renderFunc, array_keys($renderFuncOptions))) {
            return false;
        }

        //The render functions that shows js constructs like 'for' and 'if'
        $renderConstructs = $renderFuncOptions[$renderFunc]['renderConstructs'];
        $renderComponentFuncs = $renderFuncOptions[$renderFunc]['renderComponentFuncs'];
        $baseAttributes = $renderFuncOptions[$renderFunc]['baseAttributes'];

        $js = '';

        $jsFor = $this->attributes->get('for', null, true);
        $jsIf = $this->attributes->get('if', null, true);

        if ($renderConstructs) {
            if ($jsFor) {
                $js .= "for($jsFor) {\n";
            } elseif ($jsIf) {
                $js .= "if($jsIf) {\n";
            }
        }

        $js .= $this->{$renderFunc}() ?: '';

        foreach ($this->children as $child) {
            if (!$child->isComponent()) {
                $js .= "\n" . $child->render($renderFunc);
            } else {
                if ($renderComponentFuncs) {
                    $js .= "\n/*LETTT*/let func_" . $child->getUniqid() . ' = ' . $child->renderAsFunction('', $baseAttributes ? null : [], $renderFunc);
                    if ($baseAttributes) {
                        $attributes = $child->propValues->only(['id']);

                        $js .= '(null, ' . $attributes->toPropJs() . ')';
                    }
                } else {
                    $js .= "\n" . $child->render($renderFunc);
                }
            }
        }

        if (($jsFor || $jsIf) && $renderConstructs) {
            $js .= "\n}";
        }

        return $js;
    }

    public function renderIds()
    {
        return collect($this->getStaticIds())
            ->map(function ($id) {
                return 'id(' . $id . ')';
            })
            ->join("\n");
    }

    public function renderAsFunction($alias = '', $runWithArgs = null, $renderFunc = 'renderJs')
    {
        $js = 'function' . (!empty($alias) ? ' ' . $alias : '') .
            '(gui = null, $props = {}, $data = []){' . "\n";;

        $js .= "/*TEST1*/\n";
        $js .= $this->render($renderFunc);
        $js .= "/*== TEST1*/\n";

        $js .= "\n}";

        if (is_array($runWithArgs)) {
            $propsJs = '{}';
            if ($this->isComponent()) {
                $propsJs = $this->propValues->toPropJs();
            }
            $js .= '(gui, ' . $propsJs . ', [' . implode(', ', $runWithArgs) . '])';
        }

        return $js;
    }

    protected function abort($message)
    {
        throw new CompilerException($message, $this->file ? file_get_contents($this->file) : null, $this->lineNumber ?? null, $this->file ?? null);
    }
}
