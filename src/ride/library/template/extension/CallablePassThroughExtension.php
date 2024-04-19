<?php

namespace ride\library\template\extension;

use Smarty\Extension\Base;

/**
 * Add the ability to use any php function inside smarty templates
 * https://smarty-php.github.io/smarty/stable/upgrading/#variable-scope-bubbling:~:text=your%20PHP%20code.-,Using%20native%20PHP%2Dfunctions%20or%20userland%20functions%20in%20your%20templates,-You%20can%20no
 */
class CallablePassThroughExtension extends Base {

    public function getModifierCallback(string $modifierName) {
        if (is_callable($modifierName)) {
            return $modifierName;
        }

        return null;
    }
}
{

}
