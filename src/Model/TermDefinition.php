<?php

namespace DNADesign\Glossary\Model;

use SilverStripe\Forms\CompositeValidator;
use SilverStripe\Forms\RequiredFields;
use SilverStripe\ORM\DataObject;

class TermDefinition extends DataObject
{
    private static $table_name = 'TermDefinition';

    private static $db = [
        'Content' => 'HTMLText',
        'Sort' => 'Int'
    ];

    private static $has_one = [
        'Term' => GlossaryTerm::class
    ];

    private static $default_sort = 'Sort ASC';

    private static $summary_fields = [
        'Content' => 'Definition',
    ];

    public function getCMSFields()
    {
        $fields = parent::getCMSFields();

        $fields->removeByName([
            'Sort',
            'TermID'
        ]);

        return $fields;
    }

    /**
     * Required fields
     *
     * @return CompositeValidator
     */
    public function getCMSCompositeValidator(): CompositeValidator
    {
        $compositeValidator = parent::getCMSCompositeValidator();

        $compositeValidator->addValidator(RequiredFields::create(['Content']));

        return $compositeValidator;
    }

    /**
     * Return whether this text definition can have contexts.
     * Its parent Glossary Term need to be a locale present in
     *
     * @return boolean
     */
    public function requireContext()
    {
        $use = static::config()->get('use_context_for_locales');
        if (!$use || empty($use)) {
            return false;
        }

        //TODO: locales
        // $definition = $this->Term();
        // if ($definition) {
        //     $locale = $definition->Locale;
        //     if ($locale) {
        //         return in_array($locale, $use);
        //     }
        // }

        return false;
    }

    /**
     * Check if at least one locale requires contexts to be added to text definitions
     *
     * @return boolean
     */
    public static function contexts_in_use()
    {
        $use = static::config()->get('use_context_for_locales');
        if (!$use || empty($use)) {
            return false;
        }

        // Check that there is a sources available for the locale for which the context should be used
        $sources = GlossaryTerm::config()->get('sources');
        if ($sources && is_array($sources) && !empty($sources)) {
            $locales = array_intersect(array_values($use), array_keys($sources));
            return count($locales) > 0;
        }

        return false;
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
