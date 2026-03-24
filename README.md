# SilverStripe Blog Extensions

Extension for the [SilverStripe Blog module](https://github.com/silverstripe/silverstripe-blog) with additional features and improvements.

## Requirements

* SilverStripe ^6.0
* SilverStripe Blog ^5.0
* PHP ^8.0

## Installation

```bash
composer require atwx/silverstripe-blog-extensions
```

## Features

### Core Features
- **Expire Dates**: Add expiration dates to blog posts - posts won't be visible after the expire date
- **Enhanced Summary Fields**: Featured image thumbnails, authors, and publish dates in GridField
- **Parent Blog Selection**: Dropdown for selecting parent blog in ModelAdmin
- **Custom Duplicate Action**: Properly duplicates blog posts including categories and tags
- **Preview Button**: Quick preview button in CMS actions toolbar
- **Meta Tab**: Organized meta fields (Summary, MetaDescription, FeaturedImage, URLSegment)
- **Category & Tag Management**: Create categories and tags directly from BlogPost edit form

### Optional Module Support

This module automatically detects and integrates with the following optional modules if they're installed:

#### Fluent Multi-Language Support
If `tractorcow/silverstripe-fluent` is installed, blog posts become translatable:
- Title, Content, Summary, MetaDescription, and URLSegment are localizable
- Categories, Tags, Dates remain global across locales
- Full integration with Fluent's locale switching

**Installation:**
```bash
composer require tractorcow/silverstripe-fluent:^7.0
```

#### Versioned Support  
If `silverstripe/versioned` is installed (usually part of framework), blog posts get draft/published workflow:
- Draft and published stages
- Version history tracking
- GridField versioning controls

**Note:** The Blog module usually includes Versioned by default.

## Configuration

All features can be toggled via YAML configuration:

```yaml
ATWX\BlogExtensions\BlogExtensionSettings:
  overhauled_summary_fields: true    # Enhanced GridField columns
  enable_preview_button: true        # Preview button in CMS
  disable_splitview_editing: false   # Disable split-screen editing mode
  enable_blog_modeladmin: true       # Enable Blog ModelAdmin
  enable_expiredates: true           # Expire date functionality
  enable_category_dropdown: true     # Category dropdown in edit form
  enable_fluent_support: true        # Fluent integration (if installed)
  enable_versioned_support: true     # Versioned integration (if installed)
```

### Customizing Fluent Fields

You can customize which fields are translatable:

```yaml
ATWX\BlogExtensions\BlogExtensionSettings:
  fluent_translatable_fields:
    - 'Title'
    - 'Content'
    - 'Summary'
    - 'MetaDescription'
  fluent_excluded_fields:
    - 'PublishDate'
    - 'ExpireDate'
```

## Usage

After installation, run:
```bash
sake dev/build flush=1
```

The extension is automatically applied to `SilverStripe\Blog\Model\BlogPost` via YAML configuration.

### Checking Optional Module Support in Code

The extension provides helper methods to check for optional module support:

```php
// In your extension or template code
if ($blogPost->hasFluentSupport()) {
    $locale = $blogPost->getCurrentLocale();
}

if ($blogPost->hasVersionedSupport()) {
    $isPublished = $blogPost->isPublished();
}
```

## License

See [LICENSE](LICENSE)
