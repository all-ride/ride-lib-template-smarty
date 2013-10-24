<?php

namespace pallo\library\template;

/**
 * Smarty implementation for a template
 */
class SmartyTemplate extends GenericTemplate {

    /**
     * Id of the template resource
     * @var string
     */
    protected $resourceId;

    /**
     * Sets the template resource id
     * @param string $resource Template resource id
     * @return null
     */
    public function setResourceId($resourceId) {
        $this->resourceId = $resourceId;
    }

    /**
     * Gets the template resource id
     * @return string|null Template resource id
     */
    public function getResourceId() {
        return $this->resourceId;
    }

}