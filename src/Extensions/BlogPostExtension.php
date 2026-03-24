<?php

namespace ATWX\BlogExtensions\Extensions;

use SilverStripe\Core\Extension;
use SilverStripe\Forms\FieldList;
use SilverStripe\Forms\DatetimeField;

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
        // Füge ExpireDate-Feld hinzu
        $expireDateField = DatetimeField::create('ExpireDate', 'Ablaufdatum')
            ->setDescription('Optional: Beitrag wird nach diesem Datum nicht mehr angezeigt');
        
        // Füge das Feld nach dem PublishDate ein
        $fields->insertAfter('PublishDate', $expireDateField);
    }

    /**
     * Erweitere canView um ExpireDate-Prüfung
     * 
     * @param bool|null $result
     * @param \SilverStripe\Security\Member|null $member
     */
    public function canView(&$result, $member = null)
    {
        // Wenn bereits false, nichts ändern
        if ($result === false) {
            return;
        }
        
        // Wenn User editieren kann, immer anzeigen
        if ($this->owner->canEdit($member)) {
            return;
        }
        
        // Prüfe ExpireDate
        $expireDate = $this->owner->dbObject('ExpireDate');
        
        // Wenn ExpireDate gesetzt ist und in der Vergangenheit liegt, nicht anzeigen
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
}
