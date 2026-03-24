<?php

namespace ATWX\BlogExtensions\GridField;

use SilverStripe\Forms\GridField\AbstractGridFieldComponent;
use SilverStripe\Forms\GridField\GridField;
use SilverStripe\Forms\GridField\GridField_ActionMenuItem;
use SilverStripe\Forms\GridField\GridField_ActionProvider;
use SilverStripe\Forms\GridField\GridField_ColumnProvider;
use SilverStripe\Forms\GridField\GridField_FormAction;
use SilverStripe\ORM\DataObjectInterface;
use SilverStripe\Core\Validation\ValidationException;
use SilverStripe\Model\ModelData;

/**
 * Diese Komponente fügt eine Duplizieren-Aktion zum GridField hinzu
 */
class GridFieldDuplicateAction extends AbstractGridFieldComponent implements 
    GridField_ColumnProvider, 
    GridField_ActionProvider, 
    GridField_ActionMenuItem
{
    /**
     * @inheritdoc
     */
    public function getTitle($gridField, $record, $columnName)
    {
        return _t(__CLASS__ . '.Duplicate', "Duplizieren");
    }

    /**
     * @inheritdoc
     */
    public function getGroup($gridField, $record, $columnName)
    {
        return GridField_ActionMenuItem::DEFAULT_GROUP;
    }

    /**
     * @inheritdoc
     */
    public function getExtraData($gridField, $record, $columnName)
    {
        $field = $this->getDuplicateAction($gridField, $record, $columnName);

        if ($field) {
            return $field->getAttributes();
        }

        return null;
    }

    /**
     * Add a column 'Actions'
     *
     * @param GridField $gridField
     * @param array $columns
     */
    public function augmentColumns($gridField, &$columns)
    {
        if (!in_array('Actions', $columns ?? [])) {
            $columns[] = 'Actions';
        }
    }

    /**
     * Return any special attributes that will be used for FormField::create_tag()
     *
     * @param GridField $gridField
     * @param DataObjectInterface&ModelData $record
     * @param string $columnName
     * @return array
     */
    public function getColumnAttributes($gridField, $record, $columnName)
    {
        return ['class' => 'grid-field__col-compact'];
    }

    /**
     * Add the title
     *
     * @param GridField $gridField
     * @param string $columnName
     * @return array
     */
    public function getColumnMetadata($gridField, $columnName)
    {
        if ($columnName == 'Actions') {
            return ['title' => ''];
        }
    }

    /**
     * Which columns are handled by this component
     *
     * @param GridField $gridField
     * @return array
     */
    public function getColumnsHandled($gridField)
    {
        return ['Actions'];
    }

    /**
     * Which GridField actions are this component handling
     *
     * @param GridField $gridField
     * @return array
     */
    public function getActions($gridField)
    {
        return ['duplicaterecord'];
    }

    /**
     * @param GridField $gridField
     * @param DataObjectInterface&ModelData $record
     * @param string $columnName
     * @return string|null the HTML for the column
     */
    public function getColumnContent($gridField, $record, $columnName)
    {
        $field = $this->getDuplicateAction($gridField, $record, $columnName);

        if ($field) {
            return $field->Field();
        }

        return null;
    }

    /**
     * Handle the actions and apply any changes to the GridField
     *
     * @param GridField $gridField
     * @param string $actionName
     * @param array $arguments
     * @param array $data Form data
     * @throws ValidationException
     */
    public function handleAction(GridField $gridField, $actionName, $arguments, $data)
    {
        if ($actionName !== 'duplicaterecord') {
            return;
        }

        $list = $gridField->getList();
        /** @var DataObjectInterface&ModelData $item */
        $item = $list->byID($arguments['RecordID']);
        
        if (!$item) {
            return;
        }

        if (!$item->canEdit()) {
            throw new ValidationException(
                _t(__CLASS__ . '.DuplicatePermissionsFailure', "Keine Berechtigung zum Duplizieren")
            );
        }

        // Speichere die Categories und Tags vor dem Duplizieren
        $originalCategories = $item->Categories()->column('ID');
        $originalTags = $item->Tags()->column('ID');

        // Dupliziere das DataObject (ohne Relationen, wir machen das manuell)
        $duplicate = $item->duplicate(false);
        
        // Setze PublishDate auf null, damit der duplizierte Beitrag als Entwurf erscheint
        if ($duplicate->hasField('PublishDate')) {
            $duplicate->PublishDate = null;
        }

        // Füge " (Kopie)" zum Titel hinzu
        if ($duplicate->hasField('Title')) {
            $duplicate->Title = $duplicate->Title . ' (Kopie)';
        }
        
        // Speichere zuerst, damit wir eine ID haben
        $duplicate->write();
        
        // Verknüpfe Categories und Tags manuell
        if (!empty($originalCategories)) {
            $duplicate->Categories()->setByIDList($originalCategories);
        }
        
        if (!empty($originalTags)) {
            $duplicate->Tags()->setByIDList($originalTags);
        }
        
        // Trigger onAfterWrite, damit Categories/Tags mit dem richtigen Blog verknüpft werden
        $duplicate->write();
    }

    /**
     * Erstellt den Duplicate-Button
     *
     * @param GridField $gridField
     * @param DataObjectInterface&ModelData $record
     * @param string $columnName
     * @return GridField_FormAction|null
     */
    protected function getDuplicateAction($gridField, $record, $columnName)
    {
        if (!$record->canEdit()) {
            return null;
        }

        $title = _t(__CLASS__ . '.Duplicate', "Duplizieren");

        $field = GridField_FormAction::create(
            $gridField,
            'DuplicateRecord' . $record->ID,
            false,
            "duplicaterecord",
            ['RecordID' => $record->ID]
        )
            ->addExtraClass('action--duplicate btn--icon-md font-icon-duplicate btn--no-text grid-field__icon-action action-menu--handled')
            ->setAttribute('classNames', 'action--duplicate font-icon-duplicate')
            ->setDescription($title)
            ->setAttribute('aria-label', $title);

        return $field;
    }
}
