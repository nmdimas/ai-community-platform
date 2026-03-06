---
source: https://a2a-protocol.org/latest/topics/life-of-a-task/
fetched: 2026-03-06
lang: en
---

# Life of a Task

## Overview

In the Agent2Agent (A2A) Protocol, interactions can be either simple and stateless or complex and long-running. Agents respond in two fundamental ways:

- **Stateless `Message`**: For immediate, self-contained interactions
- **Stateful `Task`**: For processing through a defined lifecycle until reaching an interrupted or terminal state

## Group Related Interactions

A `contextId` logically groups multiple `Task` and `Message` objects, providing continuity across interactions.

- First message generates a new `contextId` and optionally a `taskId`
- Subsequent messages include the same `contextId` to continue the interaction
- Clients optionally attach `taskId` to indicate continuation of a specific task

The `contextId` enables collaboration toward shared goals while managing internal conversational state.

## Agent Response: Message or Task

The choice depends on interaction nature and agent capabilities:

- **Messages for Trivial Interactions**: Suitable for transactional exchanges without long-running processing
- **Tasks for Stateful Interactions**: Used when mapping intent to capabilities requiring substantial, trackable work

### Agent Types

- **Message-only Agents**: Always respond with `Message` objects; use `contextId` for continuity
- **Task-generating Agents**: Always respond with `Task` objects, avoiding the Message vs. Task decision
- **Hybrid Agents**: Generate both types; use messages to negotiate scope, then create tasks for execution tracking

## Task Refinements

Clients refine outputs by sending new requests using the same `contextId` as the original task. Clients provide `referenceTaskIds` in `Message` objects, and agents respond with either a new `Task` or `Message`.

## Task Immutability

Terminal states (completed, canceled, rejected, failed) are permanent. Subsequent interactions must initiate new tasks within the same `contextId`.

**Benefits:**
- Reliable task references and clean input-output mapping
- Clear units of work for granular tracking
- Simplified agent development by removing restart ambiguity

## Parallel Follow-ups

A2A supports parallel work through distinct tasks within the same `contextId`, enabling dependency tracking and sequential execution when prerequisites complete.

Example workflow:
- Task 1: Book flight
- Task 2: Book hotel (depends on Task 1)
- Task 3: Book activity (depends on Task 1)
- Task 4: Add spa reservation (depends on Task 2)

## Referencing Previous Artifacts

Serving agents infer relevant artifacts from referenced tasks or `contextId`. If ambiguity exists, agents return `input-required` state requesting clarification.

## Tracking Artifact Mutation

Clients manage artifact linkage and version history, not the serving agent. Agents should use consistent `artifact-name` when creating refined versions.

**Client responsibilities:**
- Explicitly reference specific artifacts for refinement
- Maintain version history
- Present latest acceptable version to users

## Example Follow-up Scenario

**Step 1:** Client requests image generation
```json
{
  "jsonrpc": "2.0",
  "id": "req-001",
  "method": "SendMessage",
  "params": {
    "message": {
      "role": "user",
      "parts": [{ "text": "Generate an image of a sailboat on the ocean." }],
      "messageId": "msg-user-001"
    }
  }
}
```

**Step 2:** Agent responds with completed task containing boat image
```json
{
  "jsonrpc": "2.0",
  "id": "req-001",
  "result": {
    "task": {
      "id": "task-boat-gen-123",
      "contextId": "ctx-conversation-abc",
      "status": { "state": "TASK_STATE_COMPLETED" },
      "artifacts": [{
        "artifactId": "artifact-boat-v1-xyz",
        "name": "sailboat_image.png",
        "description": "A generated image of a sailboat on the ocean.",
        "parts": [{
          "filename": "sailboat_image.png",
          "mediaType": "image/png",
          "raw": "base64_encoded_png_data_of_a_sailboat"
        }]
      }]
    }
  }
}
```

**Step 3:** Client requests refinement, referencing original task
```json
{
  "jsonrpc": "2.0",
  "id": "req-002",
  "method": "SendMessage",
  "params": {
    "message": {
      "role": "user",
      "messageId": "msg-user-002",
      "contextId": "ctx-conversation-abc",
      "referenceTaskIds": ["task-boat-gen-123"],
      "parts": [{ "text": "Please modify the sailboat to be red." }]
    }
  }
}
```

**Step 4:** Agent responds with new task, same context, same artifact name
```json
{
  "jsonrpc": "2.0",
  "id": "req-002",
  "result": {
    "task": {
      "id": "task-boat-color-456",
      "contextId": "ctx-conversation-abc",
      "status": { "state": "TASK_STATE_COMPLETED" },
      "artifacts": [{
        "artifactId": "artifact-boat-v2-red-pqr",
        "name": "sailboat_image.png",
        "description": "A generated image of a red sailboat on the ocean.",
        "parts": [{
          "filename": "sailboat_image.png",
          "mediaType": "image/png",
          "raw": "base64_encoded_png_data_of_a_RED_sailboat"
        }]
      }]
    }
  }
}
```
