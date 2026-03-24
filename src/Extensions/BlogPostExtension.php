<?php

namespace ATWX\BlogExtensions\Extensions;

use SilverStripe\Core\Extension;
use SilverStripe\Forms\FieldList;

/**
 * Example extension for BlogPost
 * 
 * This is a template to show how to extend the blog module.
 * Add your custom fields and methods here.
 */
class BlogPostExtension extends Extension
{
    // Add custom database fields
    private static $db = [
        // Example: 'CustomField' => 'Varchar(255)',
    ];

    // Add has_one relations
    private static $has_one = [
        // Example: 'CustomImage' => Image::class,
    ];

    // Add many_many relations
    private static $many_many = [
        // Example: 'CustomTags' => CustomTag::class,
    ];

    /**
     * Update CMS fields
     */
    public function updateCMSFields(FieldList $fields)
    {
        // Add your custom fields to the CMS here
        // Example:
        // $fields->addFieldToTab('Root.Main', TextField::create('CustomField', 'Custom Field'));
    }

    /**
     * Example method to extend functionality
     */
    public function getCustomSummary()
    {
        return $this->owner->getSummary();
    }
}
