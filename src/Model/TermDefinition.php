<?php

namespace DNADesign\Glossary\Models;

use DNADesign\Glossary\Models\Term;
use SilverStripe\Forms\CompositeValidator;
use SilverStripe\Forms\RequiredFields;
use SilverStripe\ORM\DataObject;

class Definition extends DataObject
{
    private static $table_name = 'TextDefinition';

    private static $db = [
        'UID' => 'Varchar(100)',
        'Content' => 'Text',
        'Sort' => 'Int'
    ];

    private static $has_one = [
        'Term' => GlossaryTerm::class
    ];

    private static $default_sort = 'Sort ASC';

    private static $summary_fields = [
        'UID' => 'UID',
        'Content' => 'Definition',
    ];

    public function getCMSFields()
    {
        $fields = parent::getCMSFields();

        $fields->removeByName([
            'UID',
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
