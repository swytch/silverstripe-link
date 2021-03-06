<?php declare(strict_types=1);

namespace SilverStripe\Link\Models;

use SilverStripe\Assets\File;
use SilverStripe\i18n\i18n;
use SilverStripe\Link\Type\Type;

/**
 * A link to a File track in asset-admin
 * @property File $File
 * @property int $FileID
 */
class FileLink extends Link
{

    private static $table_name = 'LinkFile';

    private static $has_one = [
        'File' => File::class
    ];


    public function generateLinkDescription(array $data): string
    {
        if (empty($data['FileID'])) {
            return '';
        }

        $file = File::get()->byID($data['FileID']);
        return $file ? $file->getFilename() : '';
    }

    public function LinkTypeHandlerName(): string
    {
        return 'InsertMediaModal';
    }

    public function getURL()
    {
        return $this->File ? $this->File->getURL() : '';
    }
}
