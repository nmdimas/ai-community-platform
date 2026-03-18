<?php

declare(strict_types=1);

namespace App\AgentProject;

enum SandboxType: string
{
    /** Use a platform-provided sandbox template (e.g., php-symfony-agent) */
    case Template = 'template';

    /** Agent provides its own Dockerfile or pre-built Docker image */
    case CustomImage = 'custom_image';

    /** Agent is already defined as a compose service in the stack */
    case ComposeService = 'compose_service';
}
