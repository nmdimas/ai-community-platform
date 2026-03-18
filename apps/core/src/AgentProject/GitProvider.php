<?php

declare(strict_types=1);

namespace App\AgentProject;

enum GitProvider: string
{
    /** GitHub.com hosted repositories */
    case GitHub = 'github';

    /** GitLab.com hosted repositories */
    case GitLab = 'gitlab';

    /** Self-hosted Git instances (GitLab CE/EE, Gitea, Forgejo, etc.) */
    case SelfHosted = 'self_hosted';
}
