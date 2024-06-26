<?php

namespace ride\library\template\engine;

use ride\library\system\file\File;
use ride\library\template\exception\ResourceNotSetException;
use ride\library\template\exception\TemplateException;
use ride\library\template\extension\CallablePassThroughExtension;
use ride\library\template\Template;
use ride\library\template\ThemedTemplate;

use \Exception;
use Smarty\Smarty;

/**
 * Implementation of the Smarty template engine
 */
class SmartyEngine extends AbstractEngine {

    /**
     * Name of this engine
     * @var string
     */
    const NAME = 'smarty';

    /**
     * Extension for the template resources
     * @var string
     */
    const EXTENSION = 'tpl';

    /**
     * Tag to open a block comment
     * @var string
     */
    const COMMENT_OPEN = '{*';

    /**
     * Tag to close a block comment
     * @var string
     */
    const COMMENT_CLOSE = '*}';

    /**
     * Instance of the Smarty
     * @var Smarty
     */
    protected $smarty;

    /**
     * Implementation of the resource handler
     * @var \ride\library\template\engine\SmartyResourceHandler
     */
    protected $resourceHandler;

    protected $loadedPlugins = [];

    /**
     * Constructs a new Smarty template engine
     * @param \ride\library\template\engine\SmartyResourceHandler $resourceHandler
     * Resource handler for the template engine
     * @param \ride\library\system\file\File $compileDirectory Directory for
     * the compiled templates
     * @param bool $escapeHtml Set to true for auto escaping of rendered
     * variables
     * @return null
     */
    public function __construct(SmartyResourceHandler $resourceHandler, File $compileDirectory, $escapeHtml = false) {
        try {
            $compileDirectory->create();
        } catch (Exception $e) {
            // you'll figure it out...
        }

        $this->smarty = new Smarty();
        $this->smarty->caching = false;
        $this->smarty->setCompileDir($compileDirectory->getPath());
        $this->smarty->escape_html = $escapeHtml;
        $this->smarty->addExtension(new CallablePassThroughExtension());


        $this->setResourceHandler($resourceHandler);
    }

    /**
     * Sets the resource handler for the template engine
     * @param SmartyResourceHandler $resourceHandler
     * $resourceHandler Handler of template resources
     * @return null
     */
    public function setResourceHandler(SmartyResourceHandler $resourceHandler) {
        $this->smarty->registerResource('ride', $resourceHandler);
        $this->smarty->default_resource_type = 'ride';

        $this->resourceHandler = $resourceHandler;
    }

    /**
     * Gets the resource handler of the template engine
     * @return \ride\library\template\engine\SmartyResourceHandler
     */
    public function getResourceHandler() {
        return $this->resourceHandler;
    }

    /**
     * Adds a plugin directory
     * @param string|array $directory
     * @return null
     */
    public function addPluginDirectory($directory) {
        if (!is_array($directory)) {
            $directories = array($directory);
        } else {
            $directories = $directory;
        }

        foreach (array_reverse($directories) as $directory) {
            foreach ((array)$directory as $v) {
                $path = $this->smarty->_realpath(rtrim($v ?? '', '/\\') . DIRECTORY_SEPARATOR, true);
                $this->loadPluginsFromDir($path);
            }
        }
    }

    public function loadPluginsFromDir(string $path)
    {
        foreach([
                    'function',
                    'modifier',
                    'block',
                    'compiler',
                    'prefilter',
                    'postfilter',
                    'outputfilter',
                ] as $type) {
            foreach (glob($path  . $type . '.?*.php') as $filename) {
                $pluginName = $this->getPluginNameFromFilename($filename);
                if ($pluginName !== null) {
                    $functionOrClassName = 'smarty_' . $type . '_' . $pluginName;
                    if (!in_array($functionOrClassName, $this->loadedPlugins, true)){
                        require_once $filename;
                        if ((function_exists($functionOrClassName) || class_exists($functionOrClassName))) {
                            $this->loadedPlugins[] = $functionOrClassName;
                            $this->smarty->registerPlugin($type, $pluginName, $functionOrClassName, true, []);
                        }
                    }
                }
            }
        }

        $type = 'resource';
        foreach (glob($path  . $type . '.?*.php') as $filename) {
            $pluginName = $this->getPluginNameFromFilename($filename);
            if ($pluginName !== null) {
                $className = 'smarty_' . $type . '_' . $pluginName;

                if (!in_array($className, $this->loadedPlugins, true)){
                    require_once $filename;
                    if (class_exists($className)) {
                        $this->loadedPlugins[] = $className;
                        $this->smarty->registerResource($pluginName, new $className());
                    }
                }
            }
        }

        $type = 'cacheresource';
        foreach (glob($path  . $type . '.?*.php') as $filename) {
            $pluginName = $this->getPluginNameFromFilename($filename);
            if ($pluginName !== null) {
                $className = 'smarty_' . $type . '_' . $pluginName;

                if (!in_array($className, $this->loadedPlugins, true) && class_exists($className)){
                    require_once $filename;
                    $this->loadedPlugins[] = $className;
                    $this->smarty->registerCacheResource($pluginName, new $className());
                }
            }
        }
    }

    /**
     * @param $filename
     *
     * @return string|null
     */
    private function getPluginNameFromFilename($filename) {
        if (!preg_match('/.*\.([a-z_A-Z0-9]+)\.php$/',$filename,$matches)) {
            return null;
        }
        return $matches[1];
    }

    /**
     * Gets the Smarty engine itself
     * @return Smarty
     */
    public function getSmarty() {
        return $this->smarty;
    }

    /**
     * Renders a template
     * @param \ride\library\template\Template $template Template to render
     * @return string Rendered template
     * @throws \ride\library\template\exception\ResourceNotSetException when
     * no template resource was set to the template
     * @throws \ride\library\template\exception\ResourceNotFoundException when
     * the template resource could not be found by the engine
     */
    public function render(Template $template) {
        $resource = $template->getResource();
        if (!$resource) {
            throw new ResourceNotSetException();
        }

        $this->preProcess($template);

        try {
            $this->smarty->assign($template->getVariables());

            $output = $this->smarty->fetch($resource);
            $exception = null;
        } catch (Exception $exception) {
            ob_get_clean();

            $exception = new TemplateException('Could not render ' . $resource, 0, $exception);
        }

        $this->postProcess();

        if ($exception) {
            throw $exception;
        }

        return $output;
    }

    /**
     * Gets the template resource
     * @param \ride\library\template\Template $template Template to get the
     * resource of
     * @return \ride\library\system\file\File $file File instance for the
     * template resource
     * @throws \ride\library\template\exception\ResourceNotSetException when
     * no template was set to the template
     * @throws \ride\library\template\exception\ResourceNotFoundException when
     * the template could not be found by the engine
     */
    public function getFile(Template $template) {
        $resource = $template->getResource();
        if (!$resource) {
            throw new ResourceNotSetException();
        }

        $this->preProcess($template);

        return $this->resourceHandler->getFile($resource);
    }

    /**
     * Gets the available template resources for the provided namespace
     * @param string $namespace
     * @param string $theme
     * @return array Array with the relative path of the resource as key and the
     * name as value
     */
    public function getFiles($namespace, $theme = null) {
        $theme = $this->themeModel->getTheme($theme);
        $themeHierarchy = $this->getThemeHierarchy($theme);

        $this->resourceHandler->setThemes($themeHierarchy);

        $files = $this->resourceHandler->getFiles($namespace);

        $this->postProcess();

        return $files;
    }

    /**
     * Preprocess this engine before performing a template action
     * @param \ride\library\template\Template $template
     * @return null
     */
    protected function preProcess(Template $template) {
        if (!$template instanceof ThemedTemplate) {
            return;
        }

        $themeHierarchy = $this->getTheme($template);

        $this->resourceHandler->setThemes($themeHierarchy);
        $this->smarty->compile_id = $template->getTheme();

        $templateId = $template->getResourceId();
        if ($templateId) {
            $this->resourceHandler->setTemplateId($templateId);
            $this->smarty->compile_id .= '-' . $templateId;
        }
    }

    /**
     * Postprocess this engine after performing a template action
     * @return null
     */
    protected function postProcess() {
        $this->resourceHandler->setThemes(null);
        $this->resourceHandler->setTemplateId(null);
        $this->smarty->compile_id = null;
        $this->smarty->clearAllAssign();
    }

}
