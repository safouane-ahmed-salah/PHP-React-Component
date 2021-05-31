<?php
/**
 * Class description
 *
 * @author  Safouane Ahmed Salah
 * @license MIT 
 */

namespace React;

abstract class Component{

    /**
     * List of the known html tags
     *
     * @var array
    */
    private static $htmlTags = ['div','p','img','small', 'a','ul','li', 'h1','h2','h3','h4','h5','h6','iframe','article', 'section', 'form','input','textarea','select','option', 'link', 'script', 'button', 'nav', 'title', 'meta', 'code', 'pre', 'span', 'i', 'svg', 'path', 'circle', 'g', 'header', 'b', 'style', 'figure', 'figcaption', 'video', 'audio'];
    
    /**
     * Namespace for the class that represents html tag
     *
     * @var string
    */
    private const tagNameSpace= 'React\Tag'; 
    
    /**
     * Associative array that holds the state of components
     *
     * @var array
    */
    protected $state = []; 

    /**
     * Associative array that holds the props passed to components
     *
     * @var array
    */
    protected $props = []; 

    /**
     * List of child components
     *
     * @var array
    */
    protected $children = [];

    /**
     * the queue that holds all previous component
     * used for registering the previous states
     * 
     * @var array
    */
    private static $queue = []; //queue of components 
    
    /**
     * A flag that indicates when the queue is already set
     * 
     * @var bool
    */
    private static $isQueued = false; 

    /**
     * the post object when state is changes
     * 
     * @var object
    */
    private static $post; 
    
    /**
     * List of already imported css and js files
     * 
     * @var array
    */
    private static $imported = [];

    /** 
     * run the first time when first component called 
     * responsible for:
     *  - setting all tags class component
     *  - setting the script that controls the state
     * 
     * @return void
    */
    static function setTags(): void {
        if(self::issetTags()) return;
        
        foreach(self::$htmlTags as $el){
            eval("namespace ". self::tagNameSpace ."; class $el extends \React\Component{}");
        }

        self::$post = json_decode(@$_POST['phpreact']);

        //script tag to setup setState function
        self::import('phpreact.js');
    }

    /**  
     * check if the classes created of correspendent tags
     * 
     * @return bool 
    */
    static function issetTags(): bool {
        return empty(self::$htmlTags) || class_exists(self::tagNameSpace. '\\' .self::$htmlTags[0]);
    }
    
    /** 
     * Get the current component tag name
     * 
     * @return string 
    */
    protected function getTagName(): string {
        return strtolower(trim(str_replace(self::tagNameSpace, '', get_class($this)), '\\'));
    }

    /**
     * check if the current component is html tag
     * 
     *  @return bool 
    */
    protected function isHtmlTag(): bool {
        return in_array($this->getTagName(), self::$htmlTags);
    }

    /**
     * register custom html tag
     * 
     * @param string|array $tags one or list of custom html tags   
     * 
     * @return void
    */
    static function registerTag(mixed $tags): void {
        self::$htmlTags= array_unique(array_merge(self::$htmlTags, self::parseTags($tags)));
    }

    /**
     * parsed html string
     * rule: lowercase and remove whitespace and specialchars
     * 
     *  @param  string|array $tags one or list of custom html tags to be parse
     *  @return array 
    */
    private static function parseTags(mixed $tags) : array {
        return array_map(function($tag){ return strtolower(self::parseAttribute($tag)); }, (array)$tags);
    }

    /**  
     * Parse html attribute
     * allow only [words or dash] for attribute or tag
     * 
     * @param string $attr the string to be parse
     * @return string
    */
    private static function parseAttribute(string $attr): string { 
        return preg_replace('/[^\w-]/','', $attr); //allow only [words or dash]
    }

    /**  
     * Check if the render is for updating a state
     * 
     * @return bool
    */
    private static function isSetState(): bool{
        return !!self::$post;
    }

    /**  
     * Import the assets you need 
     * 
     * @param string $file url or relative path to file
     * @return void
    */
    static function import(string $file): void{
        if(self::isSetState() || !self::issetTags()) return;

        $headers = @get_headers($file);
        //if url 
        if(strpos(@$headers[0],'200')!==false){
            $uri = $file;
        }else{
            $bt =  debug_backtrace();
            $dir =  dirname($bt[0]['file']);
            $realpath = realpath($dir.'/'. $file);
            if(!file_exists($realpath)) return;
            $uri = str_replace($_SERVER['DOCUMENT_ROOT'], '', $realpath);
        }

        if(in_array($uri, self::$imported)) return;
        self::$imported[] = $uri;

        $ext = strtolower(pathinfo($uri, PATHINFO_EXTENSION));
        switch($ext){
            case 'css':
                echo new \React\Tag\link(['href'=> $uri, 'rel'=> 'stylesheet']); break;
            case 'js':
                echo new \React\Tag\script(null,['src'=> $uri, 'defer'=> 'true']); break;
        }
    }

    /**  
     * Render the class that represent the html tag
     * 
     * @return string
    */
    function render(){
        if(!$this->isHtmlTag()) return '';

        $tag = $this->getTagName();
        $innerHtml = '';
        $attr = []; 
        foreach($this->props as $k=> $v){ 
            if($k == 'dangerouslyInnerHTML'){ //if has dangerouslyInnerHTML attribute
                $innerHtml = $v; 
                continue;
            } 
            $att = self::parseAttribute($k); //allow only [words or dash]
            $val = htmlspecialchars( is_object($v) || is_array($v) ? json_encode($v) : $v); //escape html

            $attr[] = "$att='$val'"; 
        }

        $attributes = implode(' ',$attr);

        //if theres innerHtml then ignore children else escape any string passed as html 
        $children = $innerHtml ? [$innerHtml] : 
            array_map(function($v)use($tag){ return is_string($v) && $tag!='script' ? htmlspecialchars($v) : $v; }, $this->children);
        $children = implode('', $children);

        return "<$tag $attributes>$children</$tag>";
    }

    /**  
     * Retrieve the component from the queue
     * 
     * @return Component
    */
    private function getQueueComponent(): Component {
        $encode = array_shift(self::$queue);
        return $encode ? unserialize(base64_decode($encode)) : null;
    }

    /**  
     * Render the update state
     * 
     * @return string
    */
    private function stateManager(): string {
        $component = null;

        if(!self::$queue && !self::$isQueued){
            $post = self::$post;
            self::$queue = $post->components;
            self::$isQueued = true;
            $component = $this->getQueueComponent();
            $oldState = $component->state;
            $component->state = (object)array_merge((array)$oldState, (array)$post->state);
            $component->componentDidUpdate($oldState, $component->state);
        }elseif(!$this->isHtmlTag()){
            $component = $this->getQueueComponent();
        }

        if(!$component) $component = $this;

        return $component->handleRender();
    }

    /**  
     * Convert a Component to html string 
     * 
     * @return string
    */
    private function handleRender(): string {
        $components = $this->render();

        //save state of custom component in top html wrapper
        if(!$this->isHtmlTag() && $components instanceof Component && $components->isHtmlTag()){
            $components->props = (object)array_merge((array)$components->props, ['component'=> base64_encode(serialize($this)), 'component-state'=> $this->state]);
        }

        if(!is_array($components)) $components = [$components]; //must be list of components

        //if custom component the render should return component or list of components
        if(!$this->isHtmlTag()) $components = array_filter($components, function($v){ return $v instanceof Component; });
        
        return implode('', $components);
    }

    /**  
     * Convert a component to html string 
     * 
     * @return string
    */
    function __toString(): string {
        return self::isSetState() ? $this->stateManager() : $this->handleRender();
    }

    /**  
     * Check if array is associative array 
     * 
     * @return bool
    */
    private function isProps(array $array): bool{
        return !empty($array) && array_keys($array) !== range(0, count($array) - 1);
    }

    /**  
     * Construct the tag with list of child component and props 
     * 
     * 2 possible usage: Component($children, $props) or Component($props)  
     * 
     * @param string|array|Component $children 
     *  1- string|[string] allowed only if html tag
     *  2- associative array then it will be considered props
     *  3- Component|[Component] 
     * @param array $props associative array of key=> value
     * 
    */
    function __construct($children = [], array $props = []){
        self::setTags();

        if(!is_array($children)) $children = [$children];

        $isProps = $this->isProps($children);

        //set properties
        $this->props = (object)array_merge((array)$this->props, $isProps ? $children : $props);
        $this->children = $isProps ? [] : $children;
        $this->state = (object)$this->state;
    }

    /** 
     * Hook when state is changed
     * 
     * @param object $oldState  the previous state 
     * @param object $currentState the current state
     * 
     * @return void 
    */
    function componentDidUpdate($oldState, $currentState){}

    /** 
     * Hook before rendering the component
     * 
     * @return void 
    */
    function beforeRender(){}
}
