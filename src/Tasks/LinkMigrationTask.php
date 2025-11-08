<?php

namespace App\Tasks;

use SilverStripe\Dev\BuildTask;
use SilverStripe\ORM\DB;
use SilverStripe\ORM\Queries\SQLInsert;
use SilverStripe\ORM\Queries\SQLSelect;
use SilverStripe\Link\Models\Link;
use SilverStripe\Link\Models\EmailLink;
use SilverStripe\Link\Models\ExternalLink;
use SilverStripe\Link\Models\FileLink;
use SilverStripe\Link\Models\PhoneLink;
use SilverStripe\Link\Models\SiteTreeLink;

class LinkMigrationTask extends BuildTask
{
    private static $segment = 'migrate-links';

    protected $title = 'Migrate Links to LinkField';

    protected $description = 'Migrates Link/LinkEmail/LinkExternal/etc tables to new LinkField structure';

    // Field mappings
    private static array $base_mapping = [
        'ID' => 'ID',
        'Created' => 'Created',
        'LastEdited' => 'LastEdited',
        'Title' => 'Title',
        'OpenInNew' => 'OpenInNew',
        'Style' => 'Style',
        'Colour' => 'Colour',
        'Icon' => 'Icon',
        'AriaLabel' => 'AriaLabel',
    ];

    private static array $email_mapping = [
        'ID' => 'ID',
        'Email' => 'Email',
    ];

    private static array $external_mapping = [
        'ID' => 'ID',
        'ExternalUrl' => 'ExternalUrl',
    ];

    private static array $file_mapping = [
        'ID' => 'ID',
        'FileID' => 'FileID',
    ];

    private static array $sitetree_mapping = [
        'ID' => 'ID',
        'PageID' => 'PageID',
        'Anchor' => 'Anchor',
    ];

    public function run($request)
    {
        echo "Starting link migration...\n\n";

        // Truncate target tables
        $this->truncateTables();

        // Get all links from base table
        $links = SQLSelect::create('*', 'Link')->execute();

        if ($links->numRecords() === 0) {
            echo "No links found to migrate.\n";
            return;
        }

        echo sprintf("Found %d links to migrate.\n\n", $links->numRecords());

        $counts = [
            'email' => 0,
            'external' => 0,
            'file' => 0,
            'sitetree' => 0,
            'phone' => 0,
            'unknown' => 0,
        ];

        foreach ($links as $link) {
            $className = $link['ClassName'];

            // Determine link type from ClassName
            if (strpos($className, 'EmailLink') !== false) {
                $this->migrateEmailLink($link);
                $counts['email']++;
            } elseif (strpos($className, 'ExternalLink') !== false) {
                $this->migrateExternalLink($link);
                $counts['external']++;
            } elseif (strpos($className, 'FileLink') !== false) {
                $this->migrateFileLink($link);
                $counts['file']++;
            } elseif (strpos($className, 'PhoneLink') !== false) {
                $this->migratePhoneLink($link);
                $counts['phone']++;
            } elseif (strpos($className, 'SiteTreeLink') !== false) {
                $this->migrateSiteTreeLink($link);
                $counts['sitetree']++;
            } else {
                echo "WARNING: Unknown link type: {$className} (ID: {$link['ID']})\n";
                $counts['unknown']++;
            }
        }

        echo "\n=== Migration Summary ===\n";
        echo "Email Links: {$counts['email']}\n";
        echo "External Links: {$counts['external']}\n";
        echo "File Links: {$counts['file']}\n";
        echo "Phone Links: {$counts['phone']}\n";
        echo "SiteTree Links: {$counts['sitetree']}\n";

        if ($counts['unknown'] > 0) {
            echo "UNKNOWN Types: {$counts['unknown']}\n";
        }

        echo "\nMigration complete!\n";
    }

    protected function truncateTables(): void
    {
        echo "Truncating target tables...\n";

        $tables = [
            'LinkField_Link',
            'LinkField_EmailLink',
            'LinkField_ExternalLink',
            'LinkField_FileLink',
            'LinkField_PhoneLink',
            'LinkField_SiteTreeLink',
        ];

        foreach ($tables as $table) {
            DB::get_conn()->clearTable($table);
        }

        echo "Tables truncated.\n\n";
    }

    protected function migrateEmailLink(array $baseData): void
    {
        // Insert base record
        $this->insertBase($baseData, EmailLink::class);

        // Get email-specific data
        $emailResult = SQLSelect::create('*', 'LinkEmail')
            ->setWhere(['ID' => $baseData['ID']])
            ->execute();

        // Get the first (and only) record
        if ($emailResult->numRecords() > 0) {
            $emailData = $emailResult->record();
            $assignments = $this->mapFields($emailData, $this->config()->get('email_mapping'));
            SQLInsert::create('LinkField_EmailLink', $assignments)->execute();
        }
    }

    protected function migrateExternalLink(array $baseData): void
    {
        // Insert base record
        $this->insertBase($baseData, ExternalLink::class);

        // Get external-specific data
        $externalResult = SQLSelect::create('*', 'LinkExternal')
            ->setWhere(['ID' => $baseData['ID']])
            ->execute();

        if ($externalResult->numRecords() > 0) {
            $externalData = $externalResult->record();
            $assignments = $this->mapFields($externalData, $this->config()->get('external_mapping'));
            SQLInsert::create('LinkField_ExternalLink', $assignments)->execute();
        }
    }

    protected function migrateFileLink(array $baseData): void
    {
        // Insert base record
        $this->insertBase($baseData, FileLink::class);

        // Get file-specific data
        $fileResult = SQLSelect::create('*', 'LinkFile')
            ->setWhere(['ID' => $baseData['ID']])
            ->execute();

        if ($fileResult->numRecords() > 0) {
            $fileData = $fileResult->record();
            $assignments = $this->mapFields($fileData, $this->config()->get('file_mapping'));
            SQLInsert::create('LinkField_FileLink', $assignments)->execute();
        }
    }

    protected function migratePhoneLink(array $baseData): void
    {
        // Insert base record
        $this->insertBase($baseData, PhoneLink::class);

        // Note: No LinkPhone table found in your schema
        // If phone links exist, they might be in a different table
        echo "WARNING: No phone link data table found for ID: {$baseData['ID']}\n";
    }

    protected function migrateSiteTreeLink(array $baseData): void
    {
        // Insert base record
        $this->insertBase($baseData, SiteTreeLink::class);

        // Get sitetree-specific data
        $sitetreeResult = SQLSelect::create('*', 'LinkSiteTree')
            ->setWhere(['ID' => $baseData['ID']])
            ->execute();

        if ($sitetreeResult->numRecords() > 0) {
            $sitetreeData = $sitetreeResult->record();
            $assignments = $this->mapFields($sitetreeData, $this->config()->get('sitetree_mapping'));

            // Handle anchor - remove leading # if present
            if (!empty($assignments['Anchor'])) {
                $assignments['Anchor'] = ltrim($assignments['Anchor'], '#');
            }

            SQLInsert::create('LinkField_SiteTreeLink', $assignments)->execute();
        }
    }

    protected function insertBase(array $data, string $className): void
    {
        $assignments = $this->mapFields($data, $this->config()->get('base_mapping'));
        $assignments['ClassName'] = $className;

        SQLInsert::create('LinkField_Link', $assignments)->execute();
    }

    protected function mapFields(array $sourceData, array $mapping): array
    {
        $result = [];

        foreach ($mapping as $sourceField => $targetField) {
            if (isset($sourceData[$sourceField])) {
                $result[$targetField] = $sourceData[$sourceField];
            }
        }

        return $result;
    }
}
