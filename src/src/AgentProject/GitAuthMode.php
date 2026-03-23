<?php

declare(strict_types=1);

namespace App\AgentProject;

enum GitAuthMode: string
{
    /** Authenticate using a personal access token or deploy token */
    case Token = 'token';

    /** Authenticate using an SSH key pair */
    case SshKey = 'ssh_key';

    /** No authentication required (public repository) */
    case None = 'none';
}
