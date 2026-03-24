<?php

namespace ATWX\BlogExtensions\Extensions;

use ATWX\BlogExtensions\BlogExtensionSettings;
use LeKoala\CmsActions\CustomAction;
use LeKoala\CmsActions\CustomLink;
use SilverStripe\Blog\Model\Blog;
use SilverStripe\Control\Controller;
use SilverStripe\Core\Extension;
use SilverStripe\Forms\DatetimeField;
use SilverStripe\Forms\DropdownField;
use SilverStripe\Forms\FieldList;
use SilverStripe\Forms\LiteralField;
use SilverStripe\Security\Permission;
use SilverStripe\Core\ClassInfo;
use TractorCow\Fluent\State\FluentState;

/**
 * Example extension for BlogPost
 * 
 * This is a template to show how to extend the blog module.
 * Add your custom fields and methods here.
 */
class BlogPostExtension extends Extension
{
    private static $db = [
        "ExpireDate" => "Datetime",
    ];    

    /**
     * Update CMS fields
     */
    public function updateCMSFields(FieldList $fields)
    {
        if (BlogExtensionSettings::config()->get('enable_expiredates')) {
            // Add ExpireDate field if the setting is enabled
            $expireDateField = DatetimeField::create('ExpireDate', 'Ablaufdatum')
                ->setDescription('Optional: Beitrag wird nach diesem Datum nicht mehr angezeigt');
            
            $fields->insertAfter('PublishDate', $expireDateField);
        }
        
        $categoriesField = $fields->dataFieldByName('Categories');
        if ($categoriesField && method_exists($categoriesField, 'setCanCreate')) {
            $categoriesField->setCanCreate(true);
            if (BlogExtensionSettings::config()->get('enable_category_dropdown')) {
                if (method_exists($categoriesField, 'setShouldLazyLoad')) {
                    // Deaktiviert Lazy Loading, damit die Kategorien im Dropdown erscheinen
                    $categoriesField->setShouldLazyLoad(false);
                }
            }
        }
        
        $tagsField = $fields->dataFieldByName('Tags');
        if ($tagsField && method_exists($tagsField, 'setCanCreate')) {
            $tagsField->setCanCreate(true);
        }
        
        $fields->removeByName('ParentID');
        
        $blogs = Blog::get();
        $blogCount = $blogs->count();
        
        if ($blogCount === 1) {
            $singleBlog = $blogs->first();
            if (!$this->owner->ParentID) {
                $this->owner->ParentID = $singleBlog->ID;
            }
        } elseif ($blogCount > 1) {
            $blogOptions = $blogs->map('ID', 'Title')->toArray();
            
            $defaultValue = $this->owner->ParentID;
            if (!$defaultValue && !empty($blogOptions)) {
                $defaultValue = array_key_first($blogOptions);
            }
            
            $parentField = DropdownField::create('ParentID', 'Blog', $blogOptions)
                ->setDescription('Wähle das übergeordnete Blog aus')
                ->setValue($defaultValue)
                ->setAttribute('required', 'required');
            
            $fields->addFieldToTab('Root.Main', $parentField, 'Title');
        }


        //Move Summary and Metadata fields to a new "Meta" tab. First create the tab if it doesn't exist, then move the fields.
        if (!$fields->fieldByName('Root.Meta')) {
            $fields->findOrMakeTab('Root.Meta', 'Meta');
        }
        $fields->addFieldsToTab('Root.Meta', [
            $fields->dataFieldByName('MenuTitle'),
            $fields->dataFieldByName('Summary'),
            $fields->dataFieldByName('MetaDescription'),
            $fields->dataFieldByName('ExtraMeta'),
            $fields->dataFieldByName('FeaturedImage'),
            $fields->dataFieldByName('URLSegment'),
        ]);
        $fields->removeByName('CustomSummary');
        $fields->removeByName('Metadata');

        //Set Title for Summary Field
        $summaryField = $fields->dataFieldByName('Summary');
        if ($summaryField) {
            $summaryField->setTitle('Summary');
        }

        //Add preview link to the bottom of the form
        $fields->addFieldToTab('Root.Main', LiteralField::create('Content', ''), 'Content');
    }

    /**
     * Erweitere canView um ExpireDate-Prüfung
     * 
     * @param bool|null $result
     * @param \SilverStripe\Security\Member|null $member
     */
    public function canView(&$result, $member = null)
    {
        if ($result === false) {
            return;
        }
        
        if ($this->owner->canEdit($member)) {
            return;
        }
        
        // Prüfe ExpireDate, wenn das Feature aktiviert ist
        if (BlogExtensionSettings::config()->get('enable_expiredates')) {
            $expireDate = $this->owner->dbObject('ExpireDate');
            
            if ($expireDate->exists() && $expireDate->InPast()) {
                $result = false;
            }
        }
    }

    //Change Summary fields to include Publishdate and Author
     public function updateSummaryFields(&$fields)
    {
        if (BlogExtensionSettings::config()->get('overhauled_summary_fields')) {
            unset($fields['Title']);
            $fields['FeaturedImage.CMSThumbnail'] = 'Featured Image';
            $fields['Title'] = 'Title';
            $fields['RenderAuthors'] = 'Authors';
            $fields['PublishDate.Nice'] = 'Publish Date';
        }
    }

    // Render method to show authors in the summary field
    public function RenderAuthors()
    {
        $authors = $this->owner->getCredits();
        $authorNames = $authors->map('ID', 'Name')->toArray();
        return implode(', ', $authorNames);
    }

    
    //Makes sure that blog duplication works properly, even in the modeladmin
    public function updateCanCreateCategories(&$result, $member = null)
    {
        if ($result === true) {
            return;
        }
        
        $parent = $this->owner->Parent();
        if (!$parent || !$parent->exists()) {
            if (Permission::checkMember($member, 'ADMIN')) {
                $result = true;
                return;
            }
            
            if ($this->owner->canEdit($member)) {
                $result = true;
            }
        }
    }

    public function updateCanCreateTags(&$result, $member = null)
    {
        
        if ($result === true) {
            return;
        }
        
        $parent = $this->owner->Parent();
        if (!$parent || !$parent->exists()) {
            if (Permission::checkMember($member, 'ADMIN')) {
                $result = true;
                return;
            }
            
            if ($this->owner->canEdit($member)) {
                $result = true;
            }
        }
    }

    public function onBeforeWrite()
    {        
        // Makes sure that the BlogPost always has a prent. Even if created with ModelAdmin
        if (!$this->owner->ParentID) {
            $firstBlog = Blog::get()->first();
            if ($firstBlog) {
                $this->owner->ParentID = $firstBlog->ID;
            }
        }
    }

    public function updatePreviewLink(&$link, $action)
    {
        // Deactivates the splitview editing
        if (BlogExtensionSettings::config()->get('disable_splitview_editing')) {
            $link = null;
        }
    }

    public function updateCMSActions(FieldList $actions)
    {
        // Enable a preview button if the setting is enabled and the record exists
        if (BlogExtensionSettings::config()->get('enable_preview_button')) {
            if ($actions && $this->owner->exists()) {
                $previewLink = $this->owner->AbsoluteLink();

                //add stage parameter to preview link if the owner is not published yet
                if ($this->hasVersionedSupport() && !$this->owner->isPublished()) {
                    $previewLink = Controller::join_links($previewLink, '?stage=Stage');
                }
                
                if ($previewLink) {
                    // Create custom link button for preview
                    $previewButton = CustomLink::create('Preview', 'Vorschau', $previewLink)
                        ->setNewWindow(true)
                        ->setButtonIcon('eye')
                        ->setNoAjax(true);
                    
                    $actions->push($previewButton);
                }
            }
        }
    }

    /**
     * Prüft ob Fluent installiert und aktiviert ist
     * 
     * @return bool
     */
    public function hasFluentSupport()
    {
        return class_exists('TractorCow\Fluent\Extension\FluentExtension') 
            && $this->owner->hasExtension('TractorCow\Fluent\Extension\FluentExtension');
    }

    /**
     * Prüft ob Versioned installiert und aktiviert ist
     * 
     * @return bool
     */
    public function hasVersionedSupport()
    {
        return class_exists('SilverStripe\Versioned\Versioned') 
            && $this->owner->hasExtension('SilverStripe\Versioned\Versioned');
    }

    /**
     * Gibt die aktuelle Fluent Locale zurück (falls Fluent aktiv)
     * 
     * @return string|null
     */
    public function getCurrentLocale()
    {
        if (!$this->hasFluentSupport()) {
            return null;
        }

        if (class_exists('TractorCow\Fluent\State\FluentState')) {
            return FluentState::singleton()->getLocale();
        }

        return null;
    }

    /**
     * Beispiel: Fügt Fluent-Locale-Info zum Summary Field in der GridField hinzu
     */
    public function updateGridFieldColumns(&$columns)
    {
        if ($this->hasFluentSupport() && isset($columns['Title'])) {
            // Du könntest hier die Locale-Info hinzufügen
            // z.B. $columns['LocaleInfo'] = 'Sprache';
        }
    }
}
