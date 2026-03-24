<?php

namespace ATWX\BlogExtensions\Admins;

use Colymba\BulkManager\BulkManager;
use SilverStripe\Admin\ModelAdmin;
use SilverStripe\Blog\Model\BlogPost;

class BlogAdmin extends ModelAdmin
{
    private static $url_segment = 'blog';

    private static $menu_title = 'Blog';

    private static $menu_icon_class = 'font-icon-news';

    private static $managed_models = [
        BlogPost::class,
    ];

    //Add bulk editing to gridfield
    public function getEditForm($id = null, $fields = null)
    {        $form = parent::getEditForm($id, $fields);
        $gridField = $form->Fields()->fieldByName($this->sanitiseClassName(BlogPost::class));
        if ($gridField) {
            $gridField->getConfig()->addComponent(new BulkManager());
        }
        return $form;
    }
}
