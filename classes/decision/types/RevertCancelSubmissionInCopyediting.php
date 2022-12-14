<?php
/**
 * @file classes/decision/types/RevertCancelSubmissionInCopyediting.php
 *
 * Copyright (c) 2014-2022 Simon Fraser University
 * Copyright (c) 2000-2022 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class decision
 *
 * @brief A decision to cancel a submission in copyediting stage
 */

namespace PKP\decision\types;

use APP\decision\Decision;
use PKP\decision\types\RevertCancelSubmission;

class RevertCancelSubmissionInCopyediting extends RevertCancelSubmission
{
    public function getStageId(): int
    {
        return WORKFLOW_STAGE_ID_EDITING;
    }

    public function getDecision(): int
    {
        return Decision::REVERT_CANCEL_SUBMISSION_IN_COPYEDITING;
    }
}
