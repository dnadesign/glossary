<?php

namespace DNADesign\Glossary\Extensions;

use DNADesign\Glossary\Model\TermDefinition;
use DNADesign\Glossary\Model\TermDefinitionContext;
use SilverStripe\Forms\FieldList;
use SilverStripe\Forms\ListboxField;
use SilverStripe\ORM\DataExtension;
use SilverStripe\ORM\FieldType\DBField;
use SilverStripe\ORM\FieldType\DBHTMLText;

class TermDefinitionContextExtension extends DataExtension
{
    private static $many_many = [
        'Contexts' => TermDefinitionContext::class
    ];

    /**
     * Add context selector
     *
     * @param FieldList $fields
     * @return void
     */
    public function updateCMSFields(FieldList $fields)
    {
        // Remove Context Tab
        $fields->removeByName('Contexts');

        // Show context selector only of parent is in a locale that requires context
        if ($this->owner->requireContext()) {
            $contexts = ListboxField::create('Contexts', 'Contexts', TermDefinitionContext::get()->map());
            $fields->addFieldsToTab('Root.Main', $contexts);
        }
    }

    /**
     * Add context list to gridfield
     *
     * @param array $fields
     * @return void
     */
    public function updateSummaryFields(&$fields)
    {
        if (TermDefinition::contexts_in_use()) {
            $fields['getContextsList'] = 'Contexts';
        }
    }

    /**
     * Return the comma separated list of context name
     *
     * @return DBHTMLText
     */
    public function getContextsList()
    {
        $list = $this->owner->Contexts()->column('Name');
        return DBField::create_field(DBHTMLText::class, implode(',', $list));
    }
}
