<?php

namespace pallo\library\template\engine;

use pallo\library\system\file\File;
use pallo\library\template\exception\ResourceNotSetException;
use pallo\library\template\theme\ThemeModel;
use pallo\library\template\Template;
use pallo\library\template\ThemedTemplate;

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
     * Instance of the Smarty
     * @var Smarty
     */
    protected $smarty;

    /**
     * Implementation of the resource handler
     * @var pallo\library\template\SmartyResourceHandler
     */
    protected $resourceHandler;

    /**
     * Constructs a new Smarty template engine
     * @param pallo\library\template\SmartyResourceHandler $resourceHandler
     * Resource handler for the template engine
     * @param pallo\library\system\file\File $compileDirectory Directory for
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
     * @param pallo\library\template\SmartyResourceHandler
     * $resourceHandler Handler of template resources
     * @return null
     */
    public function setResourceHandler(SmartyResourceHandler $resourceHandler) {
        $this->smarty->registerResource('pallo', $resourceHandler);
        $this->smarty->default_resource_type = 'pallo';

        $this->resourceHandler = $resourceHandler;
    }

    /**
     * Gets the resource handler of the template engine
     * @return pallo\library\template\SmartyResourceHandler
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
     * @param pallo\library\template\Template $template Template to render
     * @return string Rendered template
     * @throws pallo\library\template\exception\ResourceNotSetException when
     * no template resource was set to the template
     * @throws pallo\library\template\exception\ResourceNotFoundException when
     * the template resource could not be found by the engine
     */
    public function render(Template $template) {
        $resource = $template->getResource();
        if (!$resource) {
            throw new ResourceNotSetException();
        }

        if ($template instanceof ThemedTemplate) {
            $themeHierarchy = $this->getTheme($template);

            $this->resourceHandler->setThemes($themeHierarchy);

            $templateId = $template->getResourceId();
            if ($templateId) {
                $this->resourceHandler->setTemplateId($templateId);
                $this->smarty->compile_id = $templateId;
            }
        }

        $this->smarty->assign($template->getVariables());

        try {
            $output = $this->smarty->fetch($resource);
            $exception = null;
        } catch (Exception $e) {
            ob_get_clean();

            $exception = $e;
        }

        $this->resourceHandler->setThemes(null);
        $this->resourceHandler->setTemplateId(null);
        $this->smarty->compile_id = null;
        $this->smarty->clearAllAssign();

        if ($exception) {
            throw $exception;
        }

        return $output;
    }

}