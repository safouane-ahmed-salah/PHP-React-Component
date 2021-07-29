<?php
/**
 * Class description
 *
 * @author  Safouane Ahmed Salah
 * @license MIT 
 */

namespace React;


abstract class Tag extends React{
    /**
     * List of the known html tags
     *
     * @var array
    */
    private static $htmlTags = [];
    
    /**
     * Namespace for the class that represents html tag
     *
     * @var string
    */
    private const tagNameSpace= 'React\Tag'; 

    /**
     * Attribute handlers of html tag
     * 
     * @var array [attribute => handler callback]
    */
    private static $attributeHandler = [];

    /** 
     * Setup the attribute handler
     * 
    */
    static function setup(): void {
        //Default Attributes
        $tags = file_get_contents(__DIR__.'/htmltags.txt');
        self::register(array_filter(explode(PHP_EOL, $tags)));

        //Default Attribute Handler [style]
        self::registerAttributeHandler('style',function($value){
            if(!is_array($value)) return $value;
            $styles = '';
            foreach($value as $key=> $val){ $styles .= "$key:". (is_int($val) ? $val.'px' : (string)$val) . ';'; }
            return $styles;
        });
        //End handlers -----
    }

    /**
     * register custom html tag
     * 
     * @param string|array $tags one or list of custom html tags   
     * 
     * @return void
    */
    static function register($tags): void {
        self::$htmlTags= array_unique(array_merge(self::$htmlTags, self::parseTags($tags)));
    }

    /**
     * check if class name is valid tag
     * 
     * @param string $class_name   
     * 
     * @return bool
    */
    static function isValid(string $class_name): bool{
        return in_array($class_name, array_map(function($tag){ return self::tagNameSpace.'\\'.$tag; },self::$htmlTags), true);
    }

    /**
     * register Extension Handler
     * 
     * @param string $attribute html attribute  
     * @param callable $handler handler   
     * 
     * @return void
    */
    static function registerAttributeHandler(string $attribute,callable $handler): void {
        self::$attributeHandler[$attribute] = $handler; 
    }

    /**
     * get Attribute Handler
     * 
     * @param string $attribute html attribute  
     * 
     * @return callable 
    */
    static function getAttributeHandler(string $attribute): ?callable {
        return self::$attributeHandler[$attribute] ?? null; 
    }

    /**  
     * Render the class that represent the html tag
     * 
     * @return string
    */
    function render(){
        $tag = (new \ReflectionClass($this))->getShortName();
        $innerHtml = '';
        $attr = []; 
        foreach($this->props as $k=> $v){ 
            if($k== 'children') continue;
            if($k == 'dangerouslyInnerHTML'){ //if has dangerouslyInnerHTML attribute
                $innerHtml = $v; 
                continue;
            } 
            $att = self::parseAttribute($k); //allow only [words or dash]
            if($fn = self::getAttributeHandler($att)){
                $v = (string)$fn($v);
            }elseif(is_object($v) || is_array($v)){
                $v = json_encode($v);
            }

            $val = htmlspecialchars($v); //escape html

            $attr[] = "$att='$val'"; 
        }

        $attributes = implode(' ',$attr);

        //if theres innerHtml then ignore children else escape any string passed as html 
        $children = $innerHtml ? [$innerHtml] : 
            array_map(function($v)use($tag){ return is_string($v) && $tag!='script' ? htmlspecialchars($v) : $v; }, $this->props->children);
        $children = implode('', $children);

        return "<$tag $attributes>$children</$tag>";
    }
}
