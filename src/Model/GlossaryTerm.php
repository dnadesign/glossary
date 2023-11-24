<?php

namespace DNADesign\Glossary\Model;

use DNADesign\Glossary\Model\TermDefinition;
use DNADesign\Glossary\Shortcodes\GlossaryTermShortcodeProvider;
use Psr\SimpleCache\CacheInterface;
use SilverStripe\Core\Injector\Injector;
use SilverStripe\Forms\DropdownField;
use SilverStripe\Forms\GridField\GridFieldAddExistingAutocompleter;
use SilverStripe\Forms\GridField\GridFieldDeleteAction;
use SilverStripe\Forms\LiteralField;
use SilverStripe\Forms\ToggleCompositeField;
use SilverStripe\i18n\i18n;
use SilverStripe\ORM\DataObject;
use SilverStripe\ORM\FieldType\DBField;
use SilverStripe\ORM\FieldType\DBText;
use SilverStripe\Security\Permission;
use SilverStripe\Security\PermissionProvider;
use Symbiote\GridFieldExtensions\GridFieldOrderableRows;

class GlossaryTerm extends DataObject implements PermissionProvider
{
    private static $table_name = 'GlossaryTerm';

    private static $db = [
        'Term' => 'Varchar(100)',
        //TODO: locales
        // 'Locale' => 'Varchar(10)'
    ];

    private static $has_many = [
        'Definitions' => TermDefinition::class
    ];

    //TODO: locales
    // private static $defaults = [
    //     'Locale' => 'mi_NZ'
    // ];

    private static $summary_fields = [
        'ID' => 'ID',
        'Term' => 'Term',
        //TODO: locales
        // 'getLanguageName' => 'Language',
        'Definitions.Count' => 'Definitions #'
    ];

    private static $default_sort = 'Term ASC';

    public function getCMSFields()
    {
        $this->beforeUpdateCMSFields(function ($fields) {
            //TODO: locales
            // // Language
            // $language = DropdownField::create('Locale', 'Language', $this->getLanguageOptions());
            // $fields->replaceField('Locale', $language);

            if ($this->IsInDB()) {
                // Definitions
                $definitions = $fields->dataFieldByName('Definitions');
                if ($definitions) {
                    $config = $definitions->getConfig();
                    if ($config) {
                        $config->addComponent(new GridFieldOrderableRows('Sort'));
                        // Delete text definition rather than unlinking them
                        $config->removeComponentsByType(GridFieldAddExistingAutocompleter::class);
                        $delete = $config->getComponentByType(GridFieldDeleteAction::class);
                        if ($delete) {
                            $delete->setRemoveRelation(false);
                        }
                    }

                    $fields->removeByName('Definitions');
                    $fields->addFieldToTab('Root.Main', $definitions);
                }

                // Signatures
                $list = sprintf('<div class="field">%s</div>', implode('<br/>', $this->getSignaturesList()));
                $signatures = ToggleCompositeField::create('Signatures', 'Shortcodes Signatures', LiteralField::create('SignaturesList', $list));
                $fields->addFieldToTab('Root.Main', $signatures);
            }
        });

        return parent::getCMSFields();
    }

    /**
     * For CMS
     *
     * @return string
     */
    public function getTitle()
    {
        return $this->Term;
    }

    // /**
    //  * Return an array of Locale > Language Name
    //  * to choose from as source of the definition
    //  *
    //  * @return array
    //  */
    // public function getLanguageOptions()
    // {
    //     $options = [];

    //     $sources = array_keys($this->config()->get('sources'));
    //     foreach ($sources as $locale) {
    //         $options[$locale] =  \Locale::getDisplayLanguage($locale, i18n::get_locale());
    //     }

    //     $this->extend('updateLanguageOptions', $options);

    //     return $options;
    // }

    /**
     * Return the right service for the language
     *
     * @return TranslationService|null
     */
    public function getSourceService()
    {
        if ($this->Locale) {
            $sources = $this->config()->get('sources');
            if ($sources && is_array($sources) && isset($sources[$this->Locale])) {
                $class = $sources[$this->Locale];
                $service = new $class();
                if ($service->enabled()) {
                    return $service;
                }
            }
        }

        return null;
    }

    /**
     * Return the language as a string valid for the html lang attribute
     *
     * @return string
     */
    public function getLangAttr()
    {
        $lang = '';

        $language = $this->Locale;
        if ($language) {
            $lang = substr($language, 0, 2);
        }

        $this->extend('updateLangAttribute', $lang);

        return $lang;
    }

    /**
     * Return the list of text definitions that could be displayed
     * if the template allows for it.
     *
     * @return DataList
     */
    public function getDefinitionsToDisplay($args = null)
    {
        $definitions = $this->Definitions();

        $this->extend('updateDefinitionsToDisplay', $definitions, $args);

        return $definitions;
    }

    /**
     * Return the language as a readable string
     *
     * @return DBText
     */
    public function getLanguageName()
    {
        $locale = $this->Locale ? $this->Locale : '';
        return DBField::create_field(DBText::class, \Locale::getDisplayLanguage($locale, i18n::get_locale()));
    }

    /**
     * Produce the json used by TinyMCE to populate the list of words
     * than can be used in order to link to ta definition
     *
     * @return json
     */
    public static function getOptionsForCmsSelector()
    {
        $definitions = static::get();

        $cacheKey = static::singleton()->getCacheKey();

        //TODO: cache
        // // Attempt to load from cache
        // $cache = Injector::inst()->get(CacheInterface::class . '.glossaryCache');
        // $options = ($cache->has($cacheKey)) ? $cache->get($cacheKey) : [];

        // If no options have been cached, then create the json
        if (empty($options)) {
            if ($definitions->count() > 0) {
                $options = [
                    ['value' => "0", 'text' => 'Select a word']
                ];

                foreach ($definitions as $desc) {
                    $options[] = ['value' => "$desc->ID", 'text' => $desc->Term];
                }
            }

            static::singleton()->extend('updateOptionsForCmsSelector', $options);

            $options = json_encode($options);

            //TODO: cache
            // // set a value and save it via the adapter
            // $cache->set($cacheKey, $options);
        }

        return $options;
    }

    /**
     * Return a string reflecting a change in the GlossaryTerm
     * to be used for refreshing the cache when needed
     *
     * @return string
     */
    public function getCacheKey()
    {
        $glossaryTerms = static::get();
        $textDefinitions = TermDefinition::get();

        $params = [
            $glossaryTerms->count(),
            strtotime($glossaryTerms->max('LastEdited')?:''),
            $textDefinitions->count(),
            strtotime($textDefinitions->max('LastEdited')?:''),
        ];

        $this->extend('updateCacheKeyParams', $params);

        return implode('.', $params);
    }

    /**
     * This method allows to inject additional fields that will appear under the "word" selector
     * when adding a definition in the wysiwyg. SeeTinyMCE docs for format.
     *
     * @return json
     */
    public static function getAdditionalCmsSelectorFields()
    {
        $fields = [];

        static::singleton()->extend('updateAdditionalCmsSelectorFields', $fields);

        return json_encode($fields);
    }

    /**
     * This method checks if the Term Definition is requested by ID (default behaviour)
     * but gives the opportunity to extensions to find the object with a different identifier pattern
     *
     * @param string|int $identifier
     * @return Term
     */
    public static function getByIdentifier($identifier)
    {
        $definition = null;

        if (is_numeric($identifier)) {
            $definition = static::get()->byID($identifier);
        }

        static::singleton()->extend('getByAlternateIdentifier', $definition, $identifier);

        return $definition;
    }

    /**
     * Return an array of all the possible shortcode signature that can be used
     * in a text area (in case they need to be manually added)
     *
     * @return array
     */
    public function getSignaturesList()
    {
        $list = [];

        $codes = GlossaryTermShortcodeProvider::get_shortcodes();
        foreach ($codes as $code) {
            $signature = sprintf('[%s id="%s"]%s[/%s]', $code, $this->ID, $this->Term, $code);
            $list[] = $signature;
        }

        $this->extend('updateSignaturedList', $list, $codes);

        return $list;
    }

    /**
     * Build a representation of this GlossaryTerm as a JSON object
     * that can be included in a template from the ShortCode render
     *
     * @return json
     */
    public function toJSON($args = null)
    {
        $data = [
            'id' => $this->ID,
            'term' => $this->Term,
            'lang' => $this->getLanguageName()->Raw()
        ];

        $definitionsToDisplay = $this->getDefinitionsToDisplay($args);

        if ($definitionsToDisplay->count() > 0) {
            $definitions = [];

            foreach ($definitionsToDisplay as $definition) {
                $definitions[] = [
                    'id' => $definition->ID,
                    'content' => $definition->Content
                ];
            }

            $data['definitions'] = $definitions;
        }

        return json_encode($data);
    }

    /**
     * Permissions
     */
    public function providePermissions()
    {
        return [
            'VIEW_DEFINITION' => array(
                'name' => _t(
                    __CLASS__ . '.ViewDefinition',
                    'View Glossary Term Definitions'
                ),
                'category' => _t(
                    __CLASS__ . '.Category',
                    'Glossary'
                )
            ),
            'CREATE_DEFINITION' => array(
                'name' => _t(
                    __CLASS__ . '.CreateDefinition',
                    'Create Glossary Term Definitions'
                ),
                'category' => _t(
                    __CLASS__ . '.Category',
                    'Glossary'
                )
            ),
            'EDIT_DEFINITION' => array(
                'name' => _t(
                    __CLASS__ . '.EditDefinition',
                    'Edit Glossary Term Definitions'
                ),
                'category' => _t(
                    __CLASS__ . '.Category',
                    'Glossary'
                )
            ),
            'DELETE_DEFINITION' => array(
                'name' => _t(
                    __CLASS__ . '.DeleteDefinition',
                    'Delete Glossary Term Definitions'
                ),
                'category' => _t(
                    __CLASS__ . '.Category',
                    'Glossary'
                )
            )
        ];
    }

    public function canView($member = false)
    {
        return Permission::check('VIEW_DEFINITION');
    }

    public function canCreate($member = null, $context = [])
    {
        return Permission::check('CREATE_DEFINITION');
    }

    public function canEdit($member = false)
    {
        return Permission::check('EDIT_DEFINITION');
    }

    public function canDelete($member = false)
    {
        return Permission::check('DELETE_DEFINITION');
    }
}
