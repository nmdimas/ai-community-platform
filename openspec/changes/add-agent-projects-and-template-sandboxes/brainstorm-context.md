# Brainstorm Context

This note captures the product/architecture reasoning discussed before the formal proposal was
written. It is intentionally compact and is meant to preserve decision context for future LLM or
human readers.

## Initial Product Direction

The original product idea was a web-adapted development pipeline:

- create a task from the website
- monitor its movement through pipeline stages
- visualize the flow as a kanban board
- open logs, artifacts, comments, and summaries in a readable modal/drawer view

The desired board model was:

- one card = one development task
- columns = pipeline agents/stages
- skipped stages move the card forward automatically
- summarizer produces the final summary and artifacts

## Why This Expanded Beyond A Simple Kanban

During discussion, it became clear that a UI alone is insufficient. The platform also needs to
answer:

- where the source code lives
- which repository is canonical
- where commits/tags should be pushed
- which sandbox/toolchain should build and test the code
- how deploy should run after release
- which credentials are allowed at each stage

This led to the need for a first-class project-level record rather than task-only orchestration.

## Agent Project Direction

We converged on a dedicated `Agent Project` concept to hold the reusable configuration that should
not be re-entered for every task.

Expected project responsibilities:

- remote repository metadata
- local checkout path
- credential references
- sandbox selection
- release/versioning policy
- deploy contract
- runtime service mapping

## Repository Model Decision

Two source models were considered:

- legacy: code in `apps/<agent-name>`
- managed external: code in `projects/<agent-name>` with remote repo as source of truth

The final MVP decision was stricter:

- support only one managed source model
- managed agent projects always use a remote git repository as source of truth
- local code is checked out under `projects/<slug>`
- the existing `apps/` directories are migration sources only

Additional repository constraints from the discussion:

- the remote repository may be private
- the remote repository may live on self-hosted GitLab
- support should not assume GitHub-only hosting

## Sandbox Direction

We rejected the idea of a single universal build container because current agents already use
different stacks:

- PHP/Symfony
- Python/FastAPI
- Node/web

The direction chosen was:

- one repository model
- multiple sandbox models
- project-level sandbox configuration

Preferred sandbox options:

- built-in template
- custom image / Dockerfile
- compose-service-backed sandbox

An additional important constraint from the discussion:

- development/build sandbox and release/deploy sandbox should not automatically share the same
  permissions
- push/tag/deploy credentials should only be resolved for explicit release actions

## Release / Deploy Flow Direction

We discussed adding post-summary actions in the pipeline UI:

- create follow-up task
- deploy / release

That led to the idea of a dedicated release/deploy stage or agent after summarizer, responsible for:

- version bump
- commit/tag
- push to remote repository
- redeploying the updated agent
- post-deploy health verification

## MVP Staging Decision

The implementation should be staged.

Stage 1:

- establish `Agent Project`
- establish repo-only managed flow
- establish sandbox template contract

Stage 2:

- migrate `hello-agent`
- validate the PHP/Symfony template

Stage 3:

- migrate `news-maker-agent`
- validate the Python/FastAPI template

Stage 4:

- migrate `wiki-agent`
- validate the Node/web template

## Concrete Repositories Chosen For Stage Migration

The following repositories were created as the target extraction destinations:

- `https://github.com/nmdimas/a2a-hello-agent.git`
- `https://github.com/nmdimas/a2a-news-maker-agent.git`
- `https://github.com/nmdimas/a2a-wiki-agent.git`

These repositories also anchor the first three real sandbox templates.
