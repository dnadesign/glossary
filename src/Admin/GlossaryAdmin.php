<?php

namespace DNADesign\Glossary\Admins;

use DNADesign\Glossary\Models\TermDefinition;
use DNADesign\Glossary\Models\TextDefinition;
use DNADesign\Glossary\Models\TermDefinitionContext;
use SilverStripe\Admin\ModelAdmin;

class GlossaryAdmin extends ModelAdmin
{
    private static $managed_models = [
        Terms::class
    ];

    private static $url_segment = 'glossary';

    private static $menu_title = 'Term Definition';

    private static $menu_icon = 'dnadesign/silverstripe-glossaary:client/icons/glossary-icon.svg';

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
     * Check if USer are allowed to create contexts
     *
     * @return boolean
     */
    private function showContextTab()
    {
        return TextDefinition::contexts_in_use();
    }
}
