---
source: https://a2a-protocol.org/latest/topics/what-is-a2a/
fetched: 2026-03-06
lang: en
---

# What is A2A?

## Overview

The A2A protocol is an open standard enabling seamless communication between AI agents. The A2A protocol is an open standard that enables seamless communication and collaboration between AI agents.

A2A allows agents from different developers, frameworks, and organizations to work together through a standardized communication approach, breaking down traditional silos in agent deployment.

## Problems A2A Solves

The protocol addresses several critical integration challenges:

- **Agent Exposure Issues**: Developers often wrap agents as tools, which limits capabilities since agents are designed to negotiate directly
- **Custom Integrations**: Each interaction typically requires bespoke point-to-point solutions
- **Scalability Challenges**: Systems become difficult to maintain as agent interactions multiply
- **Security Gaps**: Ad hoc communication often lacks consistent security measures
- **Limited Interoperability**: Prevents organic formation of complex AI ecosystems

## Core Benefits

Key advantages of implementing A2A include:

- **Secure Collaboration**: Uses HTTPS and maintains opaque operations so agents cannot expose internal workings
- **Interoperability**: Agents from various vendors and frameworks work together seamlessly
- **Agent Autonomy**: Agents retain individual capabilities while collaborating
- **Reduced Complexity**: Standardized communication reduces integration overhead
- **Long-Running Operations**: Supports asynchronous execution and streaming

## Key Design Principles

A2A development prioritizes:

- **Simplicity**: Leverages existing standards (HTTP, JSON-RPC, Server-Sent Events)
- **Enterprise Readiness**: Addresses authentication, authorization, security, and monitoring needs
- **Asynchronous Operations**: Natively supports long-running tasks and streaming
- **Modality Independence**: Supports diverse content types beyond plain text
- **Opaque Execution**: Agents collaborate without exposing internal logic or proprietary tools

## A2A in the Broader Agent Stack

A2A complements other technologies:

- **MCP (Model Context Protocol)**: Focuses on connecting LLMs with tools and data
- **A2A**: Enables agent-to-agent collaboration with multi-turn reasoning and negotiation
- **ADK (Agent Development Kit)**: An open-source framework for building agents
- **LLMs**: Provide foundational reasoning capabilities

The practice of encapsulating an agent as a simple tool is fundamentally limiting, as it fails to capture the agent's full capabilities.

## A2A Request Lifecycle

The typical interaction sequence involves four steps:

1. **Agent Discovery**: Client retrieves the agent card from the A2A server
2. **Authentication**: Client parses security schemes and obtains JWT tokens if needed
3. **sendMessage API**: Client sends message and receives task response
4. **sendMessageStream API**: Server streams task updates, status changes, and artifacts back to client
