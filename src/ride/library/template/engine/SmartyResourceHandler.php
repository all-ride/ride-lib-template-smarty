<?php

namespace ride\library\template\engine;

use ride\library\system\file\browser\FileBrowser;
use ride\library\template\exception\ResourceNotFoundException;

use \Smarty_Resource_Custom;
use \Smarty;

// load Smarty to get the Smarty_Resource_Custom class is loaded
Smarty::SMARTY_VERSION;

/**
 * Default resource handler for Smarty according to the Zibo standards
 */
class SmartyResourceHandler extends Smarty_Resource_Custom {

    /**
     * File browser to lookup the templates
     * @var \ride\library\system\file\browser\FileBrowser;
     */
    protected $fileBrowser;

    /**
     * Path for the file browser
     * @var string
     */
    protected $path;

    /**
     * Themes to use when looking for resources
     * @var array
     */
    protected $themes;

    /**
     * Id of the template
     * @var string
     */
    protected $templateId;

    /**
     * Constructs a new resource handler
     * @param \ride\library\system\file\browser\FileBrowser $fileBrowser File
     * browser to lookup the templates
     * @return null
     */
    public function __construct(FileBrowser $fileBrowser, $path = null) {
        $this->fileBrowser = $fileBrowser;

        $this->setPath($path);
    }

    /**
     * Sets the path for the file browser
     * @param string $path
     * @return null
     * @throws \ride\library\template\exception\TemplateException when the
     * provided path is invalid or empty
     */
    public function setPath($path) {
        if ($path !== null && (!is_string($path) || !$path)) {
            throw new TemplateException('Could not set the path for the file browser: provided path is empty or invalid');
        }

        $this->path = $path;
    }

    /**
     * Sets the themes used for looking the template resource
     * @param array $themes Array with the name of the themes as key
     * @return null
     * @throws \ride\library\template\exception\TemplateException when the
     * provided theme is invalid or empty
     */
    public function setThemes(array $themes = null) {
        $this->themes = $themes;
    }

    /**
     * Sets the id of the template. Don't forget to set compile_id on the
     * Smarty engine itself.
     * @param string $templateId Id of the template
     * @return null
     */
    public function setTemplateId($templateId) {
        $this->templateId = $templateId;
    }

    /**
     * Fetches a template
     * @param string $name Relative path of the template to the view folder
     * without the extension
     * @param string $source Content of the template source file
     * @param int $timestamp timestamp of the last modification date
     */
    protected function fetch($name, &$source, &$timestamp) {
        $templateFile = $this->getFile($name);

        $source = $templateFile->read();
        $timestamp = $templateFile->getModificationTime();
    }

    /**
     * Fetches the modification date of a template
     * @param string $name Relative path of the template to the view folder
     * without the extension
     * @param int $timestamp Timestamp of the last modification date
     * @return boolean True if template is found, false otherwise
     */
    protected function fetchTimestamp($name) {
        $templateFile = $this->getFile($name);

        return $templateFile->getModificationTime();
    }

    /**
     * Get the source file of a template
     * @param string $name Relative path of the template to the view folder
     * without the extension
     * @return \ride\library\system\file\File instance of a File if the source
     * is found, null otherwise
     */
    public function getFile($name) {
        $file = null;

        if ($this->themes) {
            foreach ($this->themes as $theme => $null) {
                try {
                    $file = $this->getThemeFile($name, $theme);

                    break;
                } catch (ResourceNotFoundException $exception) {
                    $file = null;
                }
            }
        }

        if (!$file) {
            $file = $this->getThemeFile($name);
        }

        return $file;
    }

    /**
     * Gets the source file of a template
     * @param string $name Relative path of the template to the view folder
     * without the extension
     * @return \ride\library\system\file\File Instance of a File if the source
     * is found, null otherwise
     */
    protected function getThemeFile($name, $theme = null) {
        $path = '';
        if ($theme) {
            $path = $theme . '/';
        }

        if ($this->path) {
            $path = $this->path . '/' . $path;
        }

        if ($path) {
            $path = rtrim($path, '/') . '/';
        }

        $file = null;

        if ($this->templateId) {
            $fileName = $path . $name . '.' . $this->templateId . '.' . SmartyEngine::EXTENSION;

            $file = $this->fileBrowser->getFile($fileName);
        }

        if (!$file) {
            $fileName = $path . $name . '.' . SmartyEngine::EXTENSION;

            $file = $this->fileBrowser->getFile($fileName);
        }

        if (!$file) {
            throw new ResourceNotFoundException($fileName);
        }

        return $file;
    }

}