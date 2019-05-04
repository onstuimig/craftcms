<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\controllers;

use Craft;
use craft\behaviors\DraftBehavior;
use craft\behaviors\RevisionBehavior;
use craft\elements\Entry;
use craft\models\Section;
use craft\web\Controller;

/**
 * BaseEntriesController is a base class that any entry-related controllers, such as [[EntriesController]] and
 * [[EntryRevisionsController]], extend to share common functionality.
 * It extends [[Controller]], overwriting specific methods as required.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0
 */
abstract class BaseEntriesController extends Controller
{
    // Protected Methods
    // =========================================================================

    /**
     * Enforces all Edit Entry permissions.
     *
     * @param Entry $entry
     * @param bool $duplicate
     */
    protected function enforceEditEntryPermissions(Entry $entry, bool $duplicate = false)
    {
        $userSession = Craft::$app->getUser();
        $permissionSuffix = ':' . $entry->getSection()->uid;

        if (Craft::$app->getIsMultiSite()) {
            // Make sure they have access to this site
            $this->requirePermission('editSite:' . $entry->getSite()->uid);
        }

        // Make sure the user is allowed to edit entries in this section
        $this->requirePermission('editEntries' . $permissionSuffix);

        // Is it a new entry?
        if (!$entry->id || $duplicate) {
            // Make sure they have permission to create new entries in this section
            $this->requirePermission('createEntries' . $permissionSuffix);
            return;
        }

        if ($entry->draftId) {
            // If it's another user's draft, make sure they have permission to edit those
            /** @var Entry|DraftBehavior $entry */
            if ($entry->creatorId != $userSession->getId()) {
                $this->requirePermission('editPeerEntryDrafts' . $permissionSuffix);
            }
            return;
        }

        // If it's another user's entry (and it's not a Single), make sure they have permission to edit those
        if (
            $entry->authorId != $userSession->getIdentity()->id &&
            $entry->getSection()->type !== Section::TYPE_SINGLE
        ) {
            $this->requirePermission('editPeerEntries' . $permissionSuffix);
        }
    }

    /**
     * Returns the document title that should be used on an Edit Entry page.
     *
     * @param Entry
     * @return string
     */
    protected function docTitle(Entry $entry): string
    {
        $docTitle = $this->pageTitle($entry);

        if ($entry->draftId) {
            /** @var Entry|DraftBehavior $entry */
            $docTitle .= ' (' . $entry->draftName . ')';
        } else if ($entry->revisionId) {
            /** @var Entry|RevisionBehavior $entry */
            $docTitle .= ' (' . $entry->getRevisionLabel() . ')';
        }

        return $docTitle;
    }

    /**
     * Returns the page title that should be used on an Edit Entry page.
     *
     * @param Entry
     * @return string
     */
    protected function pageTitle(Entry $entry): string
    {
        return trim($entry->title) ?: Craft::t('app', 'Edit Entry');
    }
}
