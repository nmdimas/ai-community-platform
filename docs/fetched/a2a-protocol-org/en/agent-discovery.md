---
source: https://a2a-protocol.org/latest/topics/agent-discovery/
fetched: 2026-03-06
lang: en
---

# Agent Discovery in A2A

To collaborate using the Agent2Agent (A2A) protocol, AI agents need to first find each other and understand their capabilities. A2A standardizes agent self-descriptions through the **Agent Card**. However, discovery methods for these Agent Cards vary by environment and requirements. The Agent Card defines what an agent offers. Various strategies exist for a client agent to discover these cards. The choice of strategy depends on the deployment environment and security requirements.

## The Role of the Agent Card

The Agent Card is a JSON document that serves as a digital "business card" for an A2A Server (the remote agent). It is crucial for agent discovery and interaction. The key information included in an Agent Card is as follows:

- **Identity:** Includes `name`, `description`, and `provider` information.
- **Service Endpoint:** Specifies the `url` for the A2A service.
- **A2A Capabilities:** Lists supported features such as `streaming` or `pushNotifications`.
- **Authentication:** Details the required `schemes` (e.g., "Bearer", "OAuth2").
- **Skills:** Describes the agent's tasks using `AgentSkill` objects, including `id`, `name`, `description`, `inputModes`, `outputModes`, and `examples`.

Client agents use the Agent Card to determine an agent's suitability, structure requests, and ensure secure communication.

## Discovery Strategies

### 1. Well-Known URI

This approach is recommended for public agents or agents intended for broad discovery within a specific domain.

- **Mechanism:** A2A Servers make their Agent Card discoverable by hosting it at a standardized, `well-known` URI on their domain. The standard path is `https://{agent-server-domain}/.well-known/agent-card.json`, following the principles of RFC 8615.

- **Process:**

  1. A client agent knows or programmatically discovers the domain of a potential A2A Server (e.g., `smart-thermostat.example.com`).
  2. The client performs an HTTP GET request to `https://smart-thermostat.example.com/.well-known/agent-card.json`.
  3. If the Agent Card exists and is accessible, the server returns it as a JSON response.

- **Advantages:** Ease of implementation, adheres to standards, facilitates automated discovery.

- **Considerations:** Best suited for open or domain-controlled discovery scenarios. Authentication is necessary at the endpoint serving the Agent Card if it contains sensitive details.

### 2. Curated Registries (Catalog-Based Discovery)

This approach is employed in enterprise environments or public marketplaces, where Agent Cards are often managed by a central registry.

- **Mechanism:** An intermediary service (the registry) maintains a collection of Agent Cards. Clients query this registry to find agents based on various criteria (e.g., skills offered, tags, provider name, capabilities).

- **Process:**

  1. A2A Servers publish their Agent Cards to the registry.
  2. Client agents query the registry's API, and search by criteria such as "specific skills".
  3. The registry returns matching Agent Cards or references.

- **Advantages:** Centralized management and governance, capability-based discovery, support for access controls and trust frameworks, applicable in both private and public marketplaces.

- **Considerations:** Requires deployment and maintenance of a registry service. The current A2A specification does not prescribe a standard API for curated registries.

### 3. Direct Configuration / Private Discovery

This approach is used for tightly coupled systems, private agents, or development purposes.

- **Mechanism:** Client applications utilize hardcoded details, configuration files, environment variables, or proprietary APIs for discovery.
- **Process:** The process is specific to the application's deployment and configuration strategy.
- **Advantages:** Straightforward for establishing connections within known, static relationships.
- **Considerations:** Inflexible for dynamic discovery scenarios. Changes to Agent Card information necessitate client reconfiguration.

## Securing Agent Cards

Agent Cards include sensitive information, such as URLs for internal or restricted agents and descriptions of sensitive skills.

### Protection Mechanisms

- **Authenticated Agent Cards:** Use authenticated extended agent cards for sensitive information or for serving a more detailed version of the card.
- **Secure Endpoints:** Implement access controls on the HTTP endpoint serving the Agent Card. Methods include:
  - Mutual TLS (mTLS)
  - Network restrictions (e.g., IP ranges)
  - HTTP Authentication (e.g., OAuth 2.0)
- **Registry Selective Disclosure:** Registries return different Agent Cards based on the client's identity and permissions.

Any Agent Card containing sensitive data must be protected with authentication and authorization mechanisms. The A2A specification recommends using out-of-band dynamic credentials rather than embedding static secrets within the Agent Card.

## Future Considerations

The A2A community explores standardizing registry interactions or advanced discovery protocols.
