<?php

namespace ride\library\template\engine;

use ride\library\system\file\File;
use ride\library\template\exception\ResourceNotSetException;
use ride\library\template\theme\ThemeModel;
use ride\library\template\Template;
use ride\library\template\ThemedTemplate;

use \Exception;
use \Smarty;

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
     * Instance of the Smarty
     * @var Smarty
     */
    protected $smarty;

    /**
     * Implementation of the resource handler
     * @var \ride\library\template\SmartyResourceHandler
     */
    protected $resourceHandler;

    /**
     * Constructs a new Smarty template engine
     * @param \ride\library\template\SmartyResourceHandler $resourceHandler
     * Resource handler for the template engine
     * @param \ride\library\system\file\File $compileDirectory Directory for
     * the compiled templates
     * @return null
     */
    public function __construct(SmartyResourceHandler $resourceHandler, File $compileDirectory, ThemeModel $themeModel) {
        $compileDirectory->create();

        $this->smarty = new Smarty();
        $this->smarty->caching = false;
        $this->smarty->compile_dir = $compileDirectory->getPath();

        $this->setResourceHandler($resourceHandler);
        $this->setThemeModel($themeModel);
    }

    /**
     * Sets the resource handler for the template engine
     * @param ride\library\template\SmartyResourceHandler
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
     * @return \ride\library\template\SmartyResourceHandler
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

        foreach ($directories as $directory) {
            $this->smarty->addPluginsDir($directory);
        }
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
        } catch (Exception $e) {
            ob_get_clean();

            $exception = $e;
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
     * @return string Absolute path of the template resource
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

        $file = $this->resourceHandler->getFile($resource);

        return $file->getAbsolutePath();
    }

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

    protected function postProcess() {
        $this->resourceHandler->setThemes(null);
        $this->resourceHandler->setTemplateId(null);
        $this->smarty->compile_id = null;
        $this->smarty->clearAllAssign();
    }

}