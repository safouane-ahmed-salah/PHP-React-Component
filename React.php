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
spl_autoload_register(['React\\React', 'register_class']);

abstract class React{    
    /**
     * Associative array that holds the props passed to components
     *
     * @var array
    */
    protected $props = []; 
    
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
     * is Setup done 
     * 
     * @var bool 
    */
    private static $isSetup = false;


    /** 
     * Setup the javascript for handling state
     * 
     * @param string $class_name
    */
    private static function setup(): void {
        if(self::$isSetup) return;
        self::$isSetup = true;
        
        Tag::setup(); //Tag component

        //Default Extension handlers [css, js]
        self::registerExtHandler('css', function($uri,$file,$isUrl, $ver){ echo new Tag\link(['href'=> $uri. ($ver? "?v=$ver" : '') , 'rel'=> 'stylesheet']); });
        self::registerExtHandler('js', function($uri,$file,$isUrl, $ver){ echo new Tag\script(['src'=> $uri. ($ver? "?v=$ver" : ''), 'defer'=> 'true']); });

        
        Component::setup(); //setup component
    }

    /** 
     * Autoload class of htmlTag or function component
     * 
     * @param string $class_name
    */
    static function register_class($class_name){
        self::setup();
        $isTag = Tag::isValid($class_name);
        if(!$isTag && !function_exists($class_name)) return;

        $extend = '\\React\\'.($isTag ? 'Tag' : 'Func');
        $namspace = '';
        $class_arr = explode('\\', $class_name);
        $class = array_pop($class_arr);
        if($class_arr) $namspace = 'namespace '. implode('\\', $class_arr).';';
        eval("$namspace class $class extends $extend {}");
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

    static function getExtHandler(string $ext): ?callable {
        return self::$importHandler[$ext] ?? null; 
    }

    /**
     * parsed html string
     * rule: lowercase and remove whitespace and specialchars
     * 
     *  @param  string|array $tags one or list of custom html tags to be parse
     *  @return array 
    */
    protected static function parseTags($tags) : array {
        return array_map(function($tag){ return strtolower(self::parseAttribute($tag)); }, (array)$tags);
    }

    /**  
     * Parse html attribute
     * allow only [words or dash] for attribute or tag
     * 
     * @param string $attr the string to be parse
     * @return string
    */
    static function parseAttribute(string $attr): string { 
        return preg_replace('/[^\w-]/','', $attr); //allow only [words or dash]
    }

    /**  
     * Import the assets you need 
     * 
     * @param string $file url or relative path to file
     * @param string $ver version of imported file
     * @return void
    */
    static function import(string $file, String $ver = ''): void{
        if(Component::isSetState()) return;
        self::setup();

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

        $fn = self::getExtHandler($ext);
        if($fn){
            $fn($uri, $realpath, $isUrl, $ver);
        }
    }

    function render(){}

    /**  
     * Convert a component to html string 
     * 
     * @return string
    */
    function __toString(): string {
        return $this->render();
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
     * @param string|array|Component $args 
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
    }
}