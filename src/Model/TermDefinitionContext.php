<?php

namespace DNADesign\Glossary\Models;

use DNADesign\Glossary\Models\GlossaryTerm;
use SilverStripe\ORM\DataObject;

class TermDefinitionContext extends DataObject
{
    private static $table_name = 'TermDefinitionContext';

    private static $singular_name = 'Context';

    private static $plural_name = 'Contexts';

    private static $db = [
        'Name' => 'Varchar(255)'
    ];

    private static $belongs_many_many = [
        'Definitions' => TermDefinition::class
    ];

    public function getCMSFields()
    {
        $fields = parent::getCMSFields();

        // As we cannot create Term Definition without a Glossary Term, remove the definitions tab to avoid confusion
        $fields->removeByName('Definitions');

        return $fields;
    }

    /**
     * Return an array of all available context formatted to be used by the TinyMCE config
     *
     * @return array
     */
    public static function getOptionsForCmsSelector()
    {
        $contexts = static::get();

        $options = [];

        if ($contexts && $contexts->exists()) {
            $options[] = ['value' => 0, 'text' => 'Select a context'];

            foreach ($contexts as $context) {
                $options[] =  ['value' => $context->ID, 'text' => $context->Name];
            }
        }

        static::singleton()->extend('updateOptionsForCmsSelector', $options);

        return $options;
    }

    /**
     * Permissions
     */
    public function canView($member = null)
    {
        return GlossaryTerm::singleton()->canView($member);
    }

    public function canCreate($member = null, $context = [])
    {
        return GlossaryTerm::singleton()->canCreate($member, $context);
    }

    public function canEdit($member = null)
    {
        return GlossaryTerm::singleton()->canEdit($member);
    }

    public function canDelete($member = null)
    {
        return GlossaryTerm::singleton()->canDelete($member);
    }
}
