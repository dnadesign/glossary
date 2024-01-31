<?php

namespace DNADesign\Glossary\Admins;

use SilverStripe\Admin\ModelAdmin;
use SilverStripe\Forms\LiteralField;
use DNADesign\Glossary\Model\GlossaryTerm;
use DNADesign\Glossary\Model\TermDefinition;
use DNADesign\Glossary\Model\TermDefinitionContext;

class GlossaryAdmin extends ModelAdmin
{
    private static $managed_models = [
        GlossaryTerm::class
    ];

    private static $url_segment = 'glossary';

    private static $menu_title = 'Glossary';

    private static $menu_icon = 'dnadesign/silverstripe-glossary:client/icons/glossary-icon.svg';

    /**
     * Hide the Context tab if not required
     *
     * @return array
     */
    public function getManagedModels()
    {
        $models = parent::getManagedModels();

        if ($this->showContextTab() === false) {
            unset($models[TermDefinitionContext::class]);
        }

        return $models;
    }

    /**
     * Check if Users are allowed to create contexts
     *
     * @return boolean
     */
    private function showContextTab()
    {
        return TermDefinition::contexts_in_use();
    }
}
