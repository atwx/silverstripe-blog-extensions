<?php

namespace ATWX\BlogExtensions\Extensions;

use SilverStripe\Blog\Model\Blog;
use SilverStripe\Control\Controller;
use SilverStripe\Core\Extension;
use SilverStripe\Forms\DatetimeField;
use SilverStripe\Forms\DropdownField;
use SilverStripe\Forms\FieldList;
use SilverStripe\Forms\LiteralField;
use SilverStripe\Security\Permission;

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
        $expireDateField = DatetimeField::create('ExpireDate', 'Ablaufdatum')
            ->setDescription('Optional: Beitrag wird nach diesem Datum nicht mehr angezeigt');
        
        $fields->insertAfter('PublishDate', $expireDateField);
        
        $categoriesField = $fields->dataFieldByName('Categories');
        if ($categoriesField && method_exists($categoriesField, 'setCanCreate')) {
            $categoriesField->setCanCreate(true);
            if (method_exists($categoriesField, 'setShouldLazyLoad')) {
                $categoriesField->setShouldLazyLoad(false);
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
        
        $expireDate = $this->owner->dbObject('ExpireDate');
        
        if ($expireDate->exists() && $expireDate->InPast()) {
            $result = false;
        }
    }

    //Change Summary fields to include Publishdate and Author
     public function updateSummaryFields(&$fields)
    {
        $fields['Author.Name'] = 'Author';
        $fields['PublishDate.Nice'] = 'Publish Date';
    }

    /**
     * Erweitere canCreateCategories um ModelAdmin-Support
     * 
     * Erlaubt das Erstellen neuer Kategorien auch wenn kein Parent Blog existiert
     * (z.B. im ModelAdmin)
     * 
     * @param bool|null $result
     * @param \SilverStripe\Security\Member|null $member
     */
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

    /**
     * Erweitere canCreateTags um ModelAdmin-Support
     * 
     * Erlaubt das Erstellen neuer Tags auch wenn kein Parent Blog existiert
     * (z.B. im ModelAdmin)
     * 
     * @param bool|null $result
     * @param \SilverStripe\Security\Member|null $member
     */
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

    /**
     * Stelle sicher, dass jeder BlogPost einen Parent hat
     */
    public function onBeforeWrite()
    {
        if (!$this->owner->ParentID) {
            $firstBlog = Blog::get()->first();
            if ($firstBlog) {
                $this->owner->ParentID = $firstBlog->ID;
            }
        }
    }

    /**
     * Example method to extend functionality
     */
    public function getCustomSummary()
    {
        return $this->owner->getSummary();
    }

    /**
     * Deaktiviert die Split-Screen-Vorschau im CMS
     * 
     * @param string $link
     * @param string $action
     */
    public function updatePreviewLink(&$link, $action)
    {
        $link = null;
    }

    /**
     * Fügt einen Vorschau-Button zur CMS Action-Bar hinzu
     * 
     * @param FieldList $actions
     */
    public function updateCMSActions(FieldList $actions)
    {        
        if ($actions && $this->owner->exists()) {
            // Erstelle einen Link zur Vorschau-Seite
            $previewLink = $this->owner->AbsoluteLink();
            
            if ($previewLink) {
                // Erstelle einen HTML-Link als Button
                $previewButton = LiteralField::create(
                    'PreviewButton',
                    sprintf(
                        '<a href="%s" class="btn btn-outline-secondary font-icon-eye" target="_blank" rel="noopener noreferrer">Vorschau</a>',
                        $previewLink
                    )
                );
                
                // Füge den Button zu den Actions hinzu
                $actions->push($previewButton);
            } else {
                // Fallback: Wenn kein Link generiert werden kann, zeige eine Fehlermeldung
                $errorButton = LiteralField::create(
                    'PreviewError',
                    '<span class="btn btn-outline-secondary font-icon-eye disabled" title="Vorschau nicht verfügbar">Vorschau</span>'
                );
                $actions->push($errorButton);
            }
        }
    }
}
