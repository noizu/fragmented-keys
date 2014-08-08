<?php
namespace NoizuLabs\FragmentedKeys;
use NoizuLabs\FragmentedKeys as FragmentedKeys;

class KeyRing implements FragmentedKeys\IKeyRing
{
    /**
     * defaults for tag by name 'User', 'Npc', etc.
     * @var array
     */
    protected $tagOptions = array(); 

    /**
     * defaults for all tags
     */
    protected $globalOptions = array();
    
    /**
     * Key specific options for tags. 
     * @var array
     */
    protected $keyTagParams = array();

    /**
     * Memoization: key params with tag globals already applied. 
     * @var array 
     */
    protected $keyTagParamsComposite = array(); 

    /**
     * Global per Key options. E.g. if you want to set a different prefix for all tags on a key. 
     * @var type 
     */
    protected $keyOptions = array();

    /**
     * Available Cache Handlers
     * @var array(ICacheHandler)
     */
    protected $cacheHandlers = array(); 

    /**
     * Default Cache Handler
     * @var string
     */
    protected $defaultCacheHandler = null;

    /**
     *
     * @var string 
     */
    protected $defaultPrefix = null;


    public function __construct(array $globalOptions, array $globalTagOptions, $defaultCacheHandler = "default", array $cacheHandlers = array(), $defaultPrefix = null) {
        if(empty($handlers) || !isset($handlers[$defaultCacheHandler])) {
            $handlers[$defaultCacheHandler] = Configuration::getDefaultCacheHandler();
        }

        if(is_null($defaultPrefix)) {
            $defaultPrefix = Configuration::getGlobalPrefix();
        }

        $this->tagOptions = $globalTagOptions;
        $this->globalOptions = $globalOptions;
        $this->defaultCacheHandler = $defaultCacheHandler;
        $this->cacheHandlers = $cacheHandlers;
    }

    /**
     * set tag option defaults.
     * @param type $tag
     * @param type $options
     */
    public function setTagOptions($tag, array $options) 
    {
        $this->tagOptions[$tag] = $options; 
        $this->keyTagParamsComposite = null;
    }
    
    public function setGlobalOptions(array $options)
    {
        $this->globalOptions = $options;
        $this->keyTagParamsComposite = null;        
    }
    
    /**
     * get tag option defaults. 
     * @param type $tag
     * @param type $options
     */
    public function getTagOptions($tag, $options)
    {
        return isset($this->tagOptions[$tag]) ? $this->tagOptions[$tag] : array();
    }
    
    public function getGlobalOptions()
    {
        return $this->globalOptions;
    }
    
    /**
     * Define a key and the list of tag parameters. 
     * Current supported values:  
     *   Required:  tag - tag group name
     *   Optional:  cacheHandler - cacheHandler index name (handler array set in keyring constructor method)
     *   Optional:  type - Constant, Standard, *Future*
     *   Optional:  version - initial version value. 
     * @example 
     * $ring->DefineKey("apples",  array(
     *    "orchard",
     *    array( "tag"=>"tree", cacheHandler => 'apc' )),
     *    array( "tag"=>"branch", 'type' => 'Constant'))
     *    ), 
     *    array ("type" =>"standard")
     * );
     * $key = $keyRing->getAppleKey($orchardId, $treeId, $branchId);
     * 
     * @param array $key name of key (can be called with $class->getKey{ucfirstword($key))($tagInstance1, $tagInstance2);
     * @param array $tags settings for each specific tag (define expected param list)
     * @param array $globals options that apply to all tags
     */
    public function DefineKey($key, array $params, array $globals = array())
    {
        $p = array(); 
        foreach($params as $param) {
            if(is_string($param)) {
                $param = array('tag' => $param);
            } else if (!isset($param['tag'])) {
                throw new \Exception("All ParamKeys passed to KeyRing->DefineKey must either be an array with a 'tag; field or be a string to use for the tag group name");
            }
            $p[] = $param;
        }        
        $this->keyTagParams[$key] = $p;
        $this->keyOptions[$key] = isset($globals) ? $globals : array();
        $this->keyTagParamsComposite[$key] = null;                    
    }
    
    /** 
     * Factory Method that retuns a Tag with specified cache handler etc.
     * @param type $tag
     * @param type $instance
     * @param type $options
     */     
    public function Tag($tag, $instance, array $options = null)
    {
        //========================
        // Determine Handler
        //========================
        if(isset($options['cacheHandler'])) {
            if (isset($this->cacheHandlers[$options['cacheHandler']]))
            {
                $handler = $this->cacheHandlers[$options['cacheHandler']];
            } else {
                trigger_error("KeyRing Tag requested but that cache handler was not provided during construction of keyring", E_USER_ERROR);
            }
        } else {
            $handler = $this->cacheHandlers[$this->defaultCacheHandler];
        }
        
        //==================================
        // Check if Tag Version Specified
        //===================================
        $version = isset($options['version']) ? $options['version'] : null;                
        
        //==================================
        // Check if Prefix Specified
        //===================================
        $prefix = isset($options['prefix']) ? $options['prefix'] : $this->defaultPrefix;                
        
        // Determine Type
        if(isset($options['type'])) {
            switch(strtolower($options['type']))
            {
                case 'standard':                   
                    $tag = new FragmentedKeys\Tag\Standard($tag, $instance, $version, $handler, $prefix);
                    break;                
                case 'constant':
                    $tag = new FragmentedKeys\Tag\Constant($tag, $instance, $version, $handler, $prefix);
                    break;
            }
        }        
        return $tag;         
    }            

    public function getKeyObj($key, $tags)
    {
        if(empty($this->keyTagParams[$key])) {
            throw new Exception("Key {$key} has not been fully defined. Please call \$ring->DefineKey($name, ...) with the params for your key.");
        }

        if(count($this->keyTagParams[$key]) != count($tags)) {
            throw new Exception("Key Param mismatch. Expected " . count($this->keyTagParams) . " but was called with " . count($tags) . " args.\n" . print_r($this->keyTagParams[$key],1));
        }
        
        //======================================
        // Load Composite Options for Keys 
        //======================================
        if(!isset($this->keyTagParamsComposite[$key])) 
        {
            $keyParams = array();            
            $keyOptions = $this->keyOptions[$key];
            foreach( $this->keyTagParams[$key] as $paramOptions ) {
                $tagOptions = isset($this->tagOptions[$paramOptions['tag']]) ? $this->tagOptions[$paramOptions['tag']] : array();
                // apply global key options if not overriden by key-param options
                $param = array_merge($this->globalOptions, $tagOptions, $keyOptions, $paramOptions);
                $keyParams[] = $param;
            }
            $this->keyTagParamsComposite[$key] = $keyParams;
        }

        $finalTags = array(); 
        for ($i = 0; $i < count($this->keyTagParamsComposite[$key]); $i++) {
            $finalTags[] = $this->Tag( $this->keyTagParamsComposite[$key][$i]['tag'], $tags[$i], $this->keyTagParamsComposite[$key][$i]);
        }        
        $key = new FragmentedKeys\Key\Standard($key, $finalTags); 
        return $key; 
    }

    /**
     * Magic method to allow a user to easily call defined keys. You can ofcourse extend this class and override these
     * methods get{$KeyName}Key to call $this->getKey with your list of params in order to provide better ide support. 
     * @param type $key
     * @param type $args
     * @throws Exception
     */
    public function __call($key, $args) {
        if (substr($key, 0,3) === 'get' && substr($key, -6) === 'KeyObj')
        {
            $keyIndex = substr($key,3,-6);
            
            if(array_key_exists($keyIndex, $this->keyOptions)) {
                return $this->getKeyObj($keyIndex, $args);
            } else {
                throw new \Exception("Code is attempting to invoke undefined key {$key} in " . __CLASS__);
            }   
        } else {
            throw new \Exception("Method Does Not Exist {$key}");
        }
    }
}