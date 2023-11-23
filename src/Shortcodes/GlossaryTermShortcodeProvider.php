<?php

namespace DNADesign\Glossary\Shortcodes;

use DNADesign\Glossary\Models\Definition;
use SilverStripe\View\Parsers\ShortcodeHandler;

class GlossaryTermShortcodeProvider implements ShortcodeHandler
{
    /**
     * Gets the list of shortcodes provided by this handler
     *
     * @return mixed
     */
    public static function get_shortcodes()
    {
        return ['glossary'];
    }

    public static function handle_shortcode($arguments, $content, $parser, $shortcode, $extra = [])
    {
        if (!isset($arguments['id'])) {
            return $content;
        }

        $glossaryTerm = Definition::getByIdentifier($arguments['id']);
        if (!$glossaryTerm || !$glossaryTerm->exists()) {
            return $content;
        }

        $result = $glossaryTerm
                    ->customise([
                        'Content' => $content,
                        'DefinitionsToDisplay' => $glossaryTerm->getDefinitionsToDisplay($arguments),
                        'JSON' =>  $glossaryTerm->toJSON($arguments)
                    ])
                    ->renderWith('DNADesign\\Glossary\\TermDefinition');

        return $result;
    }
}
