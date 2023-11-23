<?php

use DNADesign\Glossary\Extensions\GlossaryTermContextExtension;
use DNADesign\Glossary\Extensions\TermDefinitionContextExtension;
use DNADesign\Glossary\Models\GlossaryTerm;
use DNADesign\Glossary\Models\TermDefinition;
use DNADesign\Glossary\Shortcodes\GlossaryTermShortcodeProvider;
use SilverStripe\Core\Manifest\ModuleLoader;
use SilverStripe\Forms\HTMLEditor\TinyMCEConfig;
use SilverStripe\ORM\DB;
use SilverStripe\View\Parsers\ShortcodeParser;
use SilverStripe\View\Requirements;

call_user_func(function () {
    $module = ModuleLoader::inst()->getManifest()->getModule('dnadesign/silverstripe-glossary');

    // Enable insert-link to internal pages
    TinyMCEConfig::get('cms')
        ->enablePlugins([
            'glossary' => $module->getResource('client/js/tinymce/plugins/glossary/plugin.js')
        ])
       ->addButtonsToLine(2, 'glossary');
       
    // Make sure the Glossary table exists before requiring otherwise it will break dev/build
    if (in_array(GlossaryTerm::config()->get('table_name'), DB::table_list())) {
        // Add necessary extension to allow user to manage term definition context
        if (TermDefinition::contexts_in_use()) {
            TermDefinition::add_extension(TermDefinitionContextExtension::class);
            // Also check that the relational table exists otherwise it will break dev/build
            // TODO: perhaps the table name can be figured out from the schema
            if (in_array(TermDefinition::config()->get('table_name').'_Contexts', DB::table_list())) {
                GlossaryTerm::add_extension(GlossaryTermContextExtension::class);
            }
        }
        // Add options for the wysiwyg selector
        Requirements::customScript(sprintf('var glossaryTermOptions = %s', GlossaryTerm::getOptionsForCmsSelector()));
        Requirements::customScript(sprintf('var glossaryTermsAdditionalFields = %s', GlossaryTerm::getAdditionalCmsSelectorFields()));
    }
});
// Initiate Short Code
ShortcodeParser::get('default')
    ->register('glossary', [GlossaryTermShortcodeProvider::class, 'handle_shortcode']);
