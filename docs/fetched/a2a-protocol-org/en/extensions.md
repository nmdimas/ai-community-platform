---
source: https://a2a-protocol.org/latest/topics/extensions/
fetched: 2026-03-06
lang: en
---

# Extensions in A2A

Extensions allow for extending the A2A protocol with new data, requirements, RPC methods, and state machines. Agents declare their support for specific extensions in their Agent Card, and clients can opt in to the behavior offered by an extension as part of requests they make to the agent.

## Scope of Extensions

Extensions enable several use cases:

- **Data-only Extensions**: Exposing new, structured information in the Agent Card that doesn't impact the request-response flow
- **Profile Extensions**: Overlaying additional structure and state change requirements on core request-response messages
- **Method Extensions (Extended Skills)**: Adding entirely new RPC methods beyond the core set
- **State Machine Extensions**: Adding new states or transitions to the task state machine

## List of Example Extensions

| Extension | Description |
|-----------|-------------|
| Secure Passport Extension | Adds a trusted, contextual layer for immediate personalization and reduced overhead (v1) |
| Hello World or Timestamp Extension | Augments base A2A types by adding timestamps to metadata fields (v1) |
| Traceability Extension | Python implementation and basic usage of the Traceability Extension (v1) |
| Agent Gateway Protocol (AGP) Extension | Introduces Autonomous Squads and routes Intent payloads based on declared Capabilities (v1) |

## Limitations

Extensions cannot:

- Change the definition of core data structures
- Add new values to enum types

Extensions should place custom attributes in the `metadata` map present on core data structures.

## Extension Declaration

Agents declare support for extensions in their Agent Card by including `AgentExtension` objects within their `AgentCapabilities` object.

| Field | Type | Required | Description |
|-------|------|----------|-------------|
| `uri` | string | No | The unique URI identifying the extension |
| `description` | string | No | A human-readable description of how the agent uses the extension |
| `required` | boolean | No | If true, the client must understand and comply with the extension's requirements |
| `params` | object | No | Extension-specific configuration parameters |

### Example Agent Card with Extension

```json
{
  "name": "Magic 8-ball",
  "description": "An agent that can tell your future... maybe.",
  "version": "0.1.0",
  "url": "https://example.com/agents/eightball",
  "capabilities": {
    "streaming": true,
    "extensions": [
      {
        "uri": "https://example.com/ext/konami-code/v1",
        "description": "Provide cheat codes to unlock new fortunes",
        "required": false,
        "params": {
          "hints": [
            "When your sims need extra cash fast",
            "You might deny it, but we've seen the evidence of those cows."
          ]
        }
      }
    ]
  },
  "defaultInputModes": ["text/plain"],
  "defaultOutputModes": ["text/plain"],
  "skills": [
    {
      "id": "fortune",
      "name": "Fortune teller",
      "description": "Seek advice from the mystical magic 8-ball",
      "tags": ["mystical", "untrustworthy"]
    }
  ]
}
```

## Required Extensions

When an Agent Card declares an extension as `required: true`, it signals to clients that some aspect of the extension impacts how requests are structured or processed. Clients must abide by required extensions. Agents shouldn't mark data-only extensions as required.

## Extension Specification

The detailed behavior of an extension is defined by its specification, which should contain at least:

- The specific URI(s) that identify the extension
- The schema and meaning of objects in the `params` field
- Schemas of additional data structures communicated between client and agent
- Details of new request-response flows or any other required logic

## Extension Dependencies

Extensions might depend on other extensions — either required dependencies or optional ones. Extension specifications should document these dependencies. It is the client's responsibility to activate an extension and all its required dependencies.

## Extension Activation

Extensions default to being inactive. Clients and agents perform negotiation to determine which extensions are active for a specific request.

1. **Client Request**: A client requests extension activation by including the `A2A-Extensions` header in the HTTP request
2. **Agent Processing**: Agents identify supported extensions in the request and perform activation
3. **Response**: The agent response SHOULD include the `A2A-Extensions` header, listing all successfully activated extensions

### Example Request

```
POST /agents/eightball HTTP/1.1
Host: example.com
Content-Type: application/json
A2A-Extensions: https://example.com/ext/konami-code/v1
Content-Length: 519

{
  "jsonrpc": "2.0",
  "method": "SendMessage",
  "id": "1",
  "params": {
    "message": {
      "messageId": "1",
      "role": "ROLE_USER",
      "parts": [{"text": "Oh magic 8-ball, will it rain today?"}]
    },
    "metadata": {
      "https://example.com/ext/konami-code/v1/code": "motherlode"
    }
  }
}
```

### Corresponding Response

```
HTTP/1.1 200 OK
Content-Type: application/json
A2A-Extensions: https://example.com/ext/konami-code/v1
Content-Length: 338

{
  "jsonrpc": "2.0",
  "id": "1",
  "result": {
    "message": {
      "messageId": "2",
      "role": "ROLE_AGENT",
      "parts": [{"text": "That's a bingo!"}]
    }
  }
}
```

## Implementation Considerations

### Versioning

- Use the extension's URI as the primary version identifier (e.g., `https://example.com/ext/my-extension/v1`)
- A new URI MUST be used when introducing breaking changes
- If a client requests an unsupported version, the agent SHOULD ignore the activation request

### Discoverability and Publication

- The extension specification document should be hosted at the extension's URI
- Authors are encouraged to use permanent identifier services like `w3id.org`

### Packaging and Reusability

Extension logic should be packaged into reusable libraries:

- Distribute as a standard package for the language ecosystem (PyPI for Python, npm for TypeScript/JavaScript)
- Provide streamlined integration with minimal code required

### Security

- **Input Validation**: Any new data fields, parameters, or methods must be rigorously validated
- **Scope of Required Extensions**: Be mindful when marking extensions as `required: true`
- **Authentication and Authorization**: New extension methods must be subject to the same checks as core A2A methods. Extensions MUST NOT provide a way to bypass the agent's primary security controls
