<?php
/**
 * Class description
 *
 * @author  Safouane Ahmed Salah
 * @license MIT 
 */

namespace React;

/**
 * Register dynamic class
*/
spl_autoload_register(['React\\Component', 'register_class']);

abstract class Component{

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
     * Extension handlers when import file
     * 
     * @var array [ext => handler callback]
    */
    private static $importHandler = [];

    /**
     * Attribute handlers of html tag
     * 
     * @var array [attribute => handler callback]
    */
    private static $attributeHandler = [];

    /** 
     * Setup the javascript for handling state
     * 
     * @param string $class_name
    */
    private static function setup(): void {
        if(self::$htmlTags) return;
        self::$post = json_decode(@$_POST['phpreact']);

        //Default Attributes
        $tags = file_get_contents(__DIR__.'/htmltags.txt');
        self::$htmlTags = array_filter(explode(PHP_EOL, $tags));

        //handlers ----
        //Default Extension handlers [css, js]
        self::registerExtHandler('css', function($uri,$file,$isUrl){ echo new \React\Tag\link(['href'=> $uri, 'rel'=> 'stylesheet']); });
        self::registerExtHandler('js', function($uri,$file,$isUrl){ echo new \React\Tag\script(['src'=> $uri, 'defer'=> 'true']); });

        //Default Attribute Handler [style]
        self::registerAttributeHandler('style',function($value){
            if(!is_array($value)) return $value;
            $styles = '';
            foreach($value as $key=> $val){ $styles .= "$key:". (is_int($val) ? $val.'px' : (string)$val) . ';'; }
            return $styles;
        });
        //End handlers -----

        //script tag to setup setState function
        self::import('phpreact.js');
    }

    /** 
     * Autoload class of htmlTag or function component
     * 
     * @param string $class_name
    */
    static function register_class($class_name){
        self::setup();

        //register tag Name
        $tagName = self::classToTagName($class_name);
        if(in_array($tagName, self::$htmlTags, true)){
            eval("namespace ". self::tagNameSpace ."; class $tagName extends \React\Component{}");
        }

        //function component
        if(function_exists($class_name)){
            $namspace = '';
            $class_arr = explode('\\', $class_name);
            $class = array_pop($class_arr);
            if($class_arr) $namspace = 'namespace '. implode('\\', $class_arr).';';

            eval("$namspace use React\\Component; class $class extends Component { function render(){ return $class(\$this); } }");
        }
    }
    
    /** 
     * Get the current component tag name
     * 
     * @return string 
    */
    protected function getTagName(): string {
        return self::classToTagName(get_class($this));
    }

    /** 
     * Get the current component tag name
     * 
     * @return string 
    */
    static function classToTagName(string $class_name): string {
        return strtolower(trim(str_replace(self::tagNameSpace, '', $class_name), '\\'));
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
     * register Extension Handler
     * 
     * @param string $ext file extension   
     * @param callable $handler file content   
     * 
     * @return void
    */
    static function registerExtHandler(string $ext,callable $handler): void {
        self::$importHandler[$ext] = $handler; 
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
        if(self::isSetState()) return;

        $headers = @get_headers($file);
        //if url 
        if(strpos(@$headers[0],'200')!==false){
            $realpath = $uri = $file;
            $isUrl = true;
        }else{
            $bt =  debug_backtrace();
            $dir =  dirname($bt[0]['file']);
            $realpath = realpath($dir.'/'. $file);
            if(!file_exists($realpath)) return;
            $uri = str_replace($_SERVER['DOCUMENT_ROOT'], '', $realpath);
            $isUrl = false;
        }

        if(in_array($uri, self::$imported)) return;
        self::$imported[] = $uri;

        $ext = strtolower(pathinfo($uri, PATHINFO_EXTENSION));
        if(array_key_exists($ext, self::$importHandler)){
            self::$importHandler[$ext]($uri, $realpath, $isUrl);
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
            if($k== 'children') continue;
            if($k == 'dangerouslyInnerHTML'){ //if has dangerouslyInnerHTML attribute
                $innerHtml = $v; 
                continue;
            } 
            $att = self::parseAttribute($k); //allow only [words or dash]
            if(array_key_exists($att, self::$attributeHandler)){
                $v = (string)self::$attributeHandler[$att]($v);
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

    /**  
     * Retrieve the component from the queue
     * 
     * @return Component
    */
    private function getQueueComponent(){
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
        if(!$this->isHtmlTag() && $components instanceof Component && $components->isHtmlTag() && (array)$this->state){
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
     * Get Props 
     * 
     * @return object
    */
    function useProps(array $default = []){
        return (object)array_merge($default, (array)$this->props);
    }

    /**  
     * Get State
     * 
     * @return object
    */
    function useState(array $default = []){
        return (object)array_merge($default, (array)$this->state);
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
    function __construct($args = [], $children = []){

        if(!is_array($args)) $args = [$args];
        if(!is_array($children)) $children = [$children];

        $isProps = $this->isProps($args);
        
        $props = [];
        if($isProps){
            $props = $args;
            if(!array_key_exists('children', $props)) $props['children'] = [];
            if(!is_array($props['children'])) $props['children'] = [$props['children']];
            if($children) $props['children'] = $children; 
        }else{
            $props['children'] = $args;
        }

        //set properties
        $this->props = (object)array_merge((array)$this->props, $props);
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