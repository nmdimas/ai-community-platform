<?php

declare(strict_types=1);

namespace App\AgentProject;

enum ProjectStatus: string
{
    /** Project record created but not yet active */
    case Draft = 'draft';

    /** Project is active and managed by the platform */
    case Active = 'active';

    /** Project has been archived and is excluded from active listings */
    case Archived = 'archived';
}
